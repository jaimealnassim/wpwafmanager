/* WP WAF Manager: Clients & Reports screen.
 * Values from PHP arrive in window.wpwafPage, printed by WPWAF_Admin::page_script()
 * in includes/views/page-reports.php. */
'use strict';
(function(){
const NONCE    = wpwafPage.nonce;
const AJAX_URL = wpwafPage.ajax_url;
const BOOT     = wpwafPage.boot;

let reports  = BOOT.state.reports || [];
let log      = BOOT.state.log || [];
let branding = BOOT.branding || {};
let zones    = null;          // zones of the active account, loaded on demand
let editing  = null;          // report being edited (object) or null
let picked   = {};            // id -> name for the zone picker
let previewCtx = null;        // { id, opts } or { draft } or { brand }
let sendingId  = null;        // client id open in the Send report dialog

const qs  = (s,c) => (c||document).querySelector(s);
const qsa = (s,c) => Array.from((c||document).querySelectorAll(s));
function esc(v){ return String(v == null ? '' : v).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }
function toast(msg, ok=true, icon){
  const el = document.createElement('div');
  el.className = 'wpwaf-rp-toast ' + (ok ? 'ok' : 'err');
  if (window.wpwafToastBody) el.innerHTML = wpwafToastBody(msg, ok, icon); else el.textContent = msg;
  document.body.appendChild(el);
  setTimeout(() => { el.style.transition = 'opacity .3s'; el.style.opacity = '0'; setTimeout(() => el.remove(), 300); }, ok ? 3000 : 6000);
}
function ajax(action, data){
  const fd = new FormData();
  fd.append('action', action); fd.append('nonce', NONCE);
  Object.entries(data || {}).forEach(([k,v]) => fd.append(k, v));
  return fetch(AJAX_URL, {method:'POST', body:fd, credentials:'same-origin'})
    .then(r => r.json())
    .catch(e => ({success:false, data:{message:e.message}}));
}
function errMsg(res){ return (res && res.data && res.data.message) || 'Something went wrong.'; }
function busy(btn, on, label){
  if (!btn) return;
  if (on){ btn.dataset.label = btn.innerHTML; btn.disabled = true; btn.innerHTML = '<span class="wpwaf-rp-spinner"></span> ' + esc(label || 'Working…'); }
  else { btn.disabled = false; if (btn.dataset.label) btn.innerHTML = btn.dataset.label; }
}
function applyState(d){ if (d && d.reports){ reports = d.reports; log = d.log || []; renderList(); renderLog(); } }
function hourLabel(h){ const ap = h < 12 ? 'am' : 'pm'; const hh = h % 12 === 0 ? 12 : h % 12; return hh + ':00 ' + ap; }
function schedText(r){
  if (r.frequency === 'monthly') return 'Every month on day ' + r.monthday + ' at ' + hourLabel(r.hour);
  return 'Send by hand with Send report';
}
function plural(n, word){ return n + ' ' + word + (n === 1 ? '' : (/s$/.test(word) ? 'es' : 's')); }

// ── Client list ─────────────────────────────────────────────────────────────
function renderList(){
  const box = qs('#wpwaf-rp-list');
  if (!reports.length){
    box.innerHTML = '<div class="wpwaf-rp-empty"><strong>No clients yet</strong>Add a client, link their sites, and send them a branded summary of their traffic, speed and security from Cloudflare.</div>';
    return;
  }
  box.innerHTML = '<table class="wpwaf-rp-table"><thead><tr><th>Client</th><th>Sites</th><th>Monthly report</th><th>Last report</th><th style="text-align:right;">Actions</th></tr></thead><tbody>'
    + reports.map(r => {
      const z = r.zones || [];
      const zt = z.slice(0, 3).map(x => esc(x.name)).join(', ') + (z.length > 3 ? ' +' + (z.length - 3) + ' more' : '');
      const failed = r.last_status && !r.last_text;
      const on = r.frequency === 'monthly';
      const n = (r.recipients || []).length;
      return '<tr data-id="' + esc(r.id) + '">'
        + '<td data-label="Client"><div class="wpwaf-rp-name">' + esc(r.name) + '</div>'
        + '<div class="wpwaf-rp-sub">' + (r.contact ? esc(r.contact) + ' · ' : '') + plural(n, 'email address') + '</div>'
        + (r.notes ? '<div class="wpwaf-rp-notes">' + esc(r.notes) + '</div>' : '') + '</td>'
        + '<td data-label="Sites"><div style="overflow-wrap:anywhere;">' + (zt || '<span class="wpwaf-rp-sub">None</span>') + '</div></td>'
        + '<td data-label="Monthly report"><span class="wpwaf-rp-pill ' + (on ? 'wpwaf-rp-pill--on' : 'wpwaf-rp-pill--off') + '">' + (on ? 'On' : 'Off') + '</span>'
        + '<div class="wpwaf-rp-sub">' + esc(schedText(r)) + '</div>'
        + (on && r.next_text ? '<div class="wpwaf-rp-sub">Next: ' + esc(r.next_text) + '</div>' : '') + '</td>'
        + '<td data-label="Last report">' + (r.last_text ? esc(r.last_text) : '<span class="wpwaf-rp-sub">Never</span>')
        + (r.last_status ? '<div class="wpwaf-rp-sub ' + (failed ? 'wpwaf-rp-status-err' : '') + '">' + esc(r.last_status) + '</div>' : '') + '</td>'
        + '<td class="wpwaf-rp-td-actions"><div class="wpwaf-rp-actions">'
        + '<button type="button" class="wpwaf-rp-btn wpwaf-rp-btn--sm wpwaf-rp-btn--primary" data-act="send"' + (BOOT.has_creds ? '' : ' disabled title="Connect a Cloudflare account first"') + '>Send report</button>'
        + '<button type="button" class="wpwaf-rp-btn wpwaf-rp-btn--sm" data-act="preview">Preview</button>'
        + '<button type="button" class="wpwaf-rp-btn wpwaf-rp-btn--sm" data-act="edit">Edit</button>'
        + '<button type="button" class="wpwaf-rp-btn wpwaf-rp-btn--sm wpwaf-rp-btn--danger" data-act="delete">Delete</button>'
        + '</div></td></tr>';
    }).join('') + '</tbody></table>';
  qsa('[data-act]', box).forEach(b => b.addEventListener('click', onAction));
}
function findReport(id){ return reports.find(r => r.id === id); }

function onAction(){
  const id = this.closest('tr').dataset.id, r = findReport(id), act = this.dataset.act;
  if (!r) return;
  if (act === 'edit') openEditor(r);
  else if (act === 'preview') openPreview({id});
  else if (act === 'send') openSend(r);
  else if (act === 'delete'){
    if (!confirm('Delete the client "' + r.name + '"? Their monthly report stops and this cannot be undone. Their sites in Cloudflare are not affected.')) return;
    busy(this, true, '…');
    ajax('wpwaf_rp_delete', {id}).then(res => {
      if (!res.success){ busy(this, false); toast(errMsg(res), false); return; }
      if (editing && editing.id === id) closeEditor();
      applyState(res.data); toast('Client deleted');
    });
  }
}

// ── Send report dialog (one-time send) ──────────────────────────────────────
function openSend(r){
  sendingId = r.id;
  qs('#wpwaf-rp-send-title').textContent = 'Send report to ' + r.name;
  qs('#wpwaf-rp-send-meta').textContent = plural((r.zones || []).length, 'site') + ': ' + (r.zones || []).map(z => z.name).join(', ');
  const rs = qs('#wpwaf-rp-s-range');
  rs.innerHTML = Object.entries(BOOT.ranges).map(([k,v]) => '<option value="' + esc(k) + '">' + esc(v) + '</option>').join('');
  rs.value = BOOT.ranges[r.range] ? r.range : '30d';
  qs('#wpwaf-rp-s-recips').innerHTML = (r.recipients || []).map(e => '<label class="wpwaf-rp-check"><input type="checkbox" value="' + esc(e) + '" checked> ' + esc(e) + '</label>').join('')
    || '<span class="wpwaf-rp-sub">This client has no email addresses. Edit the client to add one.</span>';
  qs('#wpwaf-rp-s-intro').value = r.intro || '';
  qs('#wpwaf-rp-send').classList.add('open');
}
function closeSend(){ qs('#wpwaf-rp-send').classList.remove('open'); sendingId = null; }
function sendOpts(){
  return {
    range: qs('#wpwaf-rp-s-range').value,
    intro: qs('#wpwaf-rp-s-intro').value,
    recipients: JSON.stringify(qsa('#wpwaf-rp-s-recips input:checked').map(cb => cb.value))
  };
}
function doSend(btn, mode){
  if (!sendingId) return;
  const opts = sendOpts();
  const to = JSON.parse(opts.recipients);
  if (mode !== 'test' && !to.length){ toast('Choose at least one email address', false); return; }
  if (mode !== 'test' && !confirm('Send this report now to ' + to.join(', ') + '?')) return;
  busy(btn, true, mode === 'test' ? 'Sending…' : 'Sending…');
  ajax('wpwaf_rp_send', Object.assign({id: sendingId, mode}, opts)).then(res => {
    busy(btn, false);
    applyState(res.data);
    const msg = mode === 'test' && res.success ? 'Test sent to ' + BOOT.user_email : ((res.data && res.data.message) || (res.success ? 'Sent' : 'Send failed'));
    toast(msg, !!res.success);
    if (res.success && mode !== 'test') closeSend();
  });
}
qs('#wpwaf-rp-s-send').addEventListener('click', function(){ doSend(this, 'now'); });
qs('#wpwaf-rp-s-test').addEventListener('click', function(){ doSend(this, 'test'); });
qs('#wpwaf-rp-s-preview').addEventListener('click', () => { if (sendingId) openPreview({id: sendingId, opts: sendOpts()}); });
qs('#wpwaf-rp-s-cancel').addEventListener('click', closeSend);
qs('#wpwaf-rp-send-close').addEventListener('click', closeSend);
qs('#wpwaf-rp-send').addEventListener('click', e => { if (e.target.id === 'wpwaf-rp-send') closeSend(); });

// ── Editor ──────────────────────────────────────────────────────────────────
function fillSelects(){
  let md = ''; for (let i = 1; i <= 28; i++) md += '<option value="' + i + '">' + i + '</option>';
  qs('#wpwaf-rp-f-monthday').innerHTML = md;
  let hr = ''; for (let i = 0; i < 24; i++) hr += '<option value="' + i + '">' + hourLabel(i) + '</option>';
  qs('#wpwaf-rp-f-hour').innerHTML = hr;
  qs('#wpwaf-rp-f-sections').innerHTML = Object.entries(BOOT.sections).map(([k,v]) => '<label class="wpwaf-rp-check"><input type="checkbox" value="' + esc(k) + '"> ' + esc(v) + '</label>').join('');
  if ((BOOT.accounts || []).length > 1){
    qs('#wpwaf-rp-account-field').style.display = '';
    qs('#wpwaf-rp-f-account').innerHTML = BOOT.accounts.map(a => '<option value="' + esc(a.id) + '">' + esc(a.label || a.id) + (a.id === BOOT.active_id ? ' (active)' : '') + '</option>').join('');
  }
}
function syncFrequency(){
  const on = qs('#wpwaf-rp-f-monthly').checked;
  qs('#wpwaf-rp-monthly-fields').style.display = on ? '' : 'none';
  qs('#wpwaf-rp-auto-hint').style.display = on ? '' : 'none';
}
qs('#wpwaf-rp-f-monthly').addEventListener('change', syncFrequency);

function openEditor(r){
  editing = r ? JSON.parse(JSON.stringify(r)) : {
    id:'', name:'', contact:'', notes:'', account: BOOT.active_id, zones:[], recipients:[], frequency:'manual', monthday:1, hour:8,
    range:'30d', sections: Object.fromEntries(Object.keys(BOOT.sections).map(k => [k, true])), intro:''
  };
  qs('#wpwaf-rp-editor-title').textContent = r ? 'Edit client: ' + r.name : 'Add client';
  qs('#wpwaf-rp-f-name').value = editing.name;
  qs('#wpwaf-rp-f-contact').value = editing.contact || '';
  qs('#wpwaf-rp-f-notes').value = editing.notes || '';
  qs('#wpwaf-rp-f-recipients').value = (editing.recipients || []).join(', ');
  qs('#wpwaf-rp-f-monthly').checked = editing.frequency === 'monthly';
  qs('#wpwaf-rp-f-monthday').value = String(editing.monthday);
  qs('#wpwaf-rp-f-hour').value = String(editing.hour);
  qs('#wpwaf-rp-f-intro').value = editing.intro || '';
  if (qs('#wpwaf-rp-f-account option')) qs('#wpwaf-rp-f-account').value = editing.account || BOOT.active_id;
  qsa('#wpwaf-rp-f-sections input').forEach(cb => { cb.checked = !!(editing.sections || {})[cb.value]; });
  picked = {};
  (editing.zones || []).forEach(z => { picked[z.id] = z.name; });
  qs('#wpwaf-rp-zone-search').value = '';
  qs('#wpwaf-rp-next-hint').textContent = r && r.frequency === 'monthly' && r.next_text ? 'Next send: ' + r.next_text : '';
  syncFrequency();
  const ed = qs('#wpwaf-rp-editor');
  ed.style.display = '';
  ed.scrollIntoView({behavior:'smooth', block:'start'});
  loadZones();
}
function closeEditor(){ editing = null; qs('#wpwaf-rp-editor').style.display = 'none'; }
function collect(){
  return {
    id: editing ? editing.id : '',
    name: qs('#wpwaf-rp-f-name').value.trim(),
    contact: qs('#wpwaf-rp-f-contact').value.trim(),
    notes: qs('#wpwaf-rp-f-notes').value,
    account: qs('#wpwaf-rp-f-account option') ? qs('#wpwaf-rp-f-account').value : BOOT.active_id,
    zones: Object.entries(picked).map(([id, name]) => ({id, name})),
    recipients: qs('#wpwaf-rp-f-recipients').value,
    frequency: qs('#wpwaf-rp-f-monthly').checked ? 'monthly' : 'manual',
    monthday: qs('#wpwaf-rp-f-monthday').value,
    hour: qs('#wpwaf-rp-f-hour').value,
    range: (editing && editing.range) || '30d',
    sections: qsa('#wpwaf-rp-f-sections input:checked').map(cb => cb.value),
    intro: qs('#wpwaf-rp-f-intro').value
  };
}
qs('#wpwaf-rp-new').addEventListener('click', () => openEditor(null));
qs('#wpwaf-rp-cancel').addEventListener('click', closeEditor);
qs('#wpwaf-rp-save').addEventListener('click', function(){
  const data = collect();
  if (!data.name){ toast('Enter the client\'s name', false); return; }
  if (!data.zones.length){ toast('Link at least one site to this client', false); return; }
  if (!data.recipients.trim()){ toast('Add at least one email address', false); return; }
  busy(this, true, 'Saving…');
  ajax('wpwaf_rp_save', {report: JSON.stringify(data)}).then(res => {
    busy(this, false);
    if (!res.success){ toast(errMsg(res), false); return; }
    applyState(res.data);
    closeEditor();
    const saved = findReport(res.data.id);
    toast(saved && saved.frequency === 'monthly' && saved.next_text ? 'Client saved. Next monthly report: ' + saved.next_text : 'Client saved');
  });
});
qs('#wpwaf-rp-preview-draft').addEventListener('click', () => openPreview({draft: collect()}));

// ── Zone picker ─────────────────────────────────────────────────────────────
function loadZones(){
  const list = qs('#wpwaf-rp-zone-list');
  if (!BOOT.has_creds){ list.innerHTML = '<div class="wpwaf-rp-loading">Connect a Cloudflare account to pick zones.</div>'; updateZoneCount(); return; }
  if (zones){ renderZones(); return; }
  list.innerHTML = '<div class="wpwaf-rp-loading"><span class="wpwaf-rp-spinner"></span> Loading zones…</div>';
  ajax('wpwaf_list_zones', {}).then(res => {
    if (!res.success){ list.innerHTML = '<div class="wpwaf-rp-loading">' + esc(errMsg(res)) + '</div>'; return; }
    zones = (res.data.zones || []).map(z => ({id: z.id, name: z.name, plan: z.plan || ''}));
    renderZones();
  });
}
function renderZones(){
  const q = qs('#wpwaf-rp-zone-search').value.trim().toLowerCase();
  const known = new Set((zones || []).map(z => z.id));
  // Zones already in the report but not in this account's list stay visible.
  const extra = Object.entries(picked).filter(([id]) => !known.has(id)).map(([id, name]) => ({id, name, plan:'not in list'}));
  const all = extra.concat(zones || []).filter(z => !q || z.name.toLowerCase().includes(q));
  qs('#wpwaf-rp-zone-list').innerHTML = all.length
    ? all.map(z => '<label class="wpwaf-rp-zone"><input type="checkbox" value="' + esc(z.id) + '" data-name="' + esc(z.name) + '"' + (picked[z.id] ? ' checked' : '') + '><span>' + esc(z.name) + '</span>' + (z.plan ? '<small>' + esc(String(z.plan).replace(' Website', '')) + '</small>' : '') + '</label>').join('')
    : '<div class="wpwaf-rp-loading">No zones match.</div>';
  qsa('#wpwaf-rp-zone-list input').forEach(cb => cb.addEventListener('change', function(){
    if (this.checked){
      if (Object.keys(picked).length >= 20){ this.checked = false; toast('A client can have up to 20 sites', false); return; }
      picked[this.value] = this.dataset.name;
    } else delete picked[this.value];
    updateZoneCount();
  }));
  updateZoneCount();
}
function updateZoneCount(){ qs('#wpwaf-rp-zone-count').textContent = Object.keys(picked).length + ' selected'; }
qs('#wpwaf-rp-zone-search').addEventListener('input', () => { if (zones) renderZones(); });

// ── Preview ─────────────────────────────────────────────────────────────────
function openPreview(ctx){
  previewCtx = ctx;
  const modal = qs('#wpwaf-rp-modal');
  const sample = qs('#wpwaf-rp-modal-sample');
  if (!BOOT.has_creds){ sample.checked = true; sample.disabled = true; }
  else if (ctx.brand){ sample.checked = true; }
  qs('#wpwaf-rp-modal-test').style.display = ctx.id && !ctx.opts && BOOT.has_creds ? '' : 'none';
  modal.classList.add('open');
  loadPreview();
}
function loadPreview(){
  const ctx = previewCtx || {};
  const sample = qs('#wpwaf-rp-modal-sample').checked;
  qs('#wpwaf-rp-modal-title').textContent = 'Building preview…';
  qs('#wpwaf-rp-modal-meta').innerHTML = '<span class="wpwaf-rp-spinner"></span> Reading Cloudflare analytics';
  const data = {sample: sample ? '1' : '0'};
  if (ctx.id) data.id = ctx.id;
  if (ctx.opts) Object.assign(data, ctx.opts);
  else if (ctx.draft) data.report = JSON.stringify(ctx.draft);
  else data.report = JSON.stringify({name:'Your client', contact:'', zones:[], recipients:'', sections: Object.keys(BOOT.sections), range:'30d'});
  ajax('wpwaf_rp_preview', data).then(res => {
    if (!res.success){ qs('#wpwaf-rp-modal-title').textContent = 'Preview failed'; qs('#wpwaf-rp-modal-meta').textContent = errMsg(res); return; }
    qs('#wpwaf-rp-modal-title').textContent = res.data.subject;
    qs('#wpwaf-rp-modal-meta').innerHTML = (res.data.sample ? 'Showing sample numbers <span class="wpwaf-rp-sample">Sample data</span>' : 'Built from live Cloudflare data')
      + (!res.data.sample && !res.data.any_data ? '. <strong style="color:#b91c1c;">No analytics could be read for these zones, so a real send would be skipped.</strong>' : '');
    qs('#wpwaf-rp-frame').srcdoc = res.data.html;
  });
}
function closePreview(){ qs('#wpwaf-rp-modal').classList.remove('open'); qs('#wpwaf-rp-frame').srcdoc = ''; previewCtx = null; }
qs('#wpwaf-rp-modal-close').addEventListener('click', closePreview);
qs('#wpwaf-rp-modal-done').addEventListener('click', closePreview);
qs('#wpwaf-rp-modal').addEventListener('click', e => { if (e.target.id === 'wpwaf-rp-modal') closePreview(); });
document.addEventListener('keydown', e => {
  if (e.key !== 'Escape') return;
  if (qs('#wpwaf-rp-modal').classList.contains('open')) closePreview();
  else if (qs('#wpwaf-rp-send').classList.contains('open')) closeSend();
});
qs('#wpwaf-rp-modal-sample').addEventListener('change', loadPreview);
qs('#wpwaf-rp-modal-test').addEventListener('click', function(){
  if (!previewCtx || !previewCtx.id) return;
  const btn = this, id = previewCtx.id;
  busy(btn, true, 'Sending…');
  ajax('wpwaf_rp_send', {id, mode:'test'}).then(res => {
    busy(btn, false); applyState(res.data);
    toast(res.success ? 'Test sent to ' + BOOT.user_email : errMsg(res), !!res.success);
  });
});
qs('#wpwaf-rp-brand-preview').addEventListener('click', () => openPreview({brand: true}));

// ── Branding ────────────────────────────────────────────────────────────────
function fillBranding(){
  qs('#wpwaf-rp-b-agency').value = branding.agency_name || '';
  qs('#wpwaf-rp-b-color').value = branding.brand_color || '#FF6A00';
  qs('#wpwaf-rp-b-color-text').value = branding.brand_color || '#FF6A00';
  qs('#wpwaf-rp-b-logo').value = branding.logo_url || '';
  qs('#wpwaf-rp-b-fromname').value = branding.from_name || '';
  qs('#wpwaf-rp-b-fromemail').value = branding.from_email || '';
  qs('#wpwaf-rp-b-replyto').value = branding.reply_to || '';
  qs('#wpwaf-rp-b-footer').value = branding.footer_text || '';
  qs('#wpwaf-rp-b-cf').checked = !!branding.show_cloudflare;
  logoThumb();
}
function logoThumb(){
  const url = qs('#wpwaf-rp-b-logo').value.trim();
  const box = qs('#wpwaf-rp-b-logo-thumb');
  box.textContent = '';
  if (/^https?:\/\//i.test(url)){ const img = document.createElement('img'); img.src = url; img.alt = ''; box.appendChild(img); }
}
qs('#wpwaf-rp-b-logo').addEventListener('change', logoThumb);
qs('#wpwaf-rp-b-color').addEventListener('input', function(){ qs('#wpwaf-rp-b-color-text').value = this.value.toUpperCase(); });
qs('#wpwaf-rp-b-color-text').addEventListener('input', function(){ if (/^#[0-9a-f]{6}$/i.test(this.value)) qs('#wpwaf-rp-b-color').value = this.value; });
qs('#wpwaf-rp-b-logo-pick').addEventListener('click', function(){
  if (!window.wp || !wp.media){ qs('#wpwaf-rp-b-logo').focus(); toast('Paste the logo URL into the field.', false); return; }
  const frame = wp.media({ title: 'Choose a logo', library: { type: 'image' }, multiple: false, button: { text: 'Use this logo' } });
  frame.on('select', () => {
    const a = frame.state().get('selection').first().toJSON();
    qs('#wpwaf-rp-b-logo').value = a.url || '';
    logoThumb();
  });
  frame.open();
});
qs('#wpwaf-rp-b-save').addEventListener('click', function(){
  const color = qs('#wpwaf-rp-b-color-text').value.trim();
  if (!/^#[0-9a-f]{6}$/i.test(color)){ toast('Enter the brand color as a hex value like #FF6A00', false); return; }
  busy(this, true, 'Saving…');
  ajax('wpwaf_rp_branding_save', {branding: JSON.stringify({
    agency_name: qs('#wpwaf-rp-b-agency').value.trim(),
    brand_color: color,
    logo_url: qs('#wpwaf-rp-b-logo').value.trim(),
    from_name: qs('#wpwaf-rp-b-fromname').value.trim(),
    from_email: qs('#wpwaf-rp-b-fromemail').value.trim(),
    reply_to: qs('#wpwaf-rp-b-replyto').value.trim(),
    footer_text: qs('#wpwaf-rp-b-footer').value,
    show_cloudflare: qs('#wpwaf-rp-b-cf').checked
  })}).then(res => {
    busy(this, false);
    if (!res.success){ toast(errMsg(res), false); return; }
    branding = res.data.branding; fillBranding(); toast('Branding saved');
  });
});

// ── Log ─────────────────────────────────────────────────────────────────────
function renderLog(){
  const box = qs('#wpwaf-rp-log');
  if (!log.length){ box.innerHTML = '<div class="wpwaf-rp-empty">Nothing sent yet.</div>'; return; }
  const kinds = {scheduled:'Automatic (monthly)', manual:'Sent by hand', test:'Test'};
  box.innerHTML = '<table class="wpwaf-rp-table wpwaf-rp-log"><thead><tr><th>When</th><th>Client</th><th>Type</th><th>Result</th></tr></thead><tbody>'
    + log.map(l => '<tr>'
      + '<td data-label="When" style="white-space:nowrap;">' + esc(l.time_text || '') + '</td>'
      + '<td data-label="Client"><span style="overflow-wrap:anywhere;">' + esc(l.report) + '</span>' + (l.range && BOOT.ranges[l.range] ? '<div class="wpwaf-rp-sub">' + esc(BOOT.ranges[l.range]) + '</div>' : '') + '</td>'
      + '<td data-label="Type">' + esc(kinds[l.trigger] || l.trigger) + '</td>'
      + '<td data-label="Result"><span class="wpwaf-rp-pill ' + (l.ok ? 'wpwaf-rp-pill--on' : 'wpwaf-rp-pill--err') + '">' + (l.ok ? 'Sent' : 'Failed') + '</span><div class="wpwaf-rp-sub">' + esc(l.message) + '</div></td>'
      + '</tr>').join('') + '</tbody></table>';
}

qs('#wpwaf-rp-tz').textContent = BOOT.timezone || 'UTC';
fillSelects();
fillBranding();
renderList();
renderLog();

// Links from Zone Status: ?client=<id>&send=1 opens Send report, ?zone=<id>&zname=<name> starts a new client with that site.
(function(){
  const p = new URLSearchParams(location.search);
  const cid = p.get('client');
  if (cid && findReport(cid) && p.get('send') === '1' && BOOT.has_creds){ openSend(findReport(cid)); return; }
  if (cid && findReport(cid)){ openEditor(findReport(cid)); return; }
  const zid = p.get('zone'), zname = p.get('zname');
  if (zid && /^[a-f0-9]{32}$/.test(zid)){
    openEditor(null);
    picked[zid] = zname || zid;
    updateZoneCount();
    if (zones) renderZones();
  }
})();
})();
