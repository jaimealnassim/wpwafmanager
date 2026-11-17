/* WP WAF Manager: Bot Control screen.
 * Values from PHP arrive in window.wpwafPage, printed by WPWAF_Admin::page_script()
 * in includes/views/page-bots.php. */
'use strict';
(function(){
const NONCE    = wpwafPage.nonce;
const AJAX_URL = wpwafPage.ajax_url;
const FLARES   = wpwafPage.flares;
const PRESETS  = wpwafPage.presets;
const CONCURRENCY = 3;

/* Field definitions. Values and plan scope follow the Cloudflare Bot Management API. */
const AI_OPTS = [['disabled','Allow'],['only_on_ad_pages','Block on pages with ads'],['block','Block everywhere']];
const FIELDS = {
  ai_search:   {group:'ai', label:'AI search crawlers', hint:'Bots that index content to answer questions later', opts:AI_OPTS},
  ai_user:     {group:'ai', label:'AI assistants and agents', hint:'Bots that fetch pages in real time for a person', opts:AI_OPTS},
  ai_training: {group:'ai', label:'AI training crawlers', hint:'Disallow adds a robots.txt rule and blocks training-only crawlers', opts:[['disabled','Allow'],['disallow','Disallow in robots.txt'],['only_on_ad_pages','Block on pages with ads'],['block','Block everywhere']]},
  crawler_protection: {group:'ai', label:'Crawler trap', hint:'Sends AI scrapers into a maze of generated links', opts:[['enabled','On'],['disabled','Off']]},
  is_robots_txt_managed: {group:'robots', label:'Managed robots.txt', hint:'Cloudflare adds AI crawler rules above your own robots.txt', bool:true},
  bot_preference_sync_enabled: {group:'robots', label:'Sync AI choices to robots.txt', hint:'Writes your AI crawler choices into robots.txt', bool:true},
  fight_mode: {group:'bfm', label:'Bot Fight Mode', hint:'Challenges traffic that matches known bot patterns', bool:true},
  sbfm_definitely_automated: {group:'sbfm', label:'Definitely automated', hint:'Traffic that is almost certainly a bot', opts:[['allow','Allow'],['managed_challenge','Managed Challenge'],['block','Block']]},
  sbfm_likely_automated: {group:'sbfm', label:'Likely automated', hint:'Business plan and above', opts:[['allow','Allow'],['managed_challenge','Managed Challenge'],['block','Block']], biz:true},
  sbfm_verified_bots: {group:'sbfm', label:'Verified bots', hint:'Known good bots such as search engines', opts:[['allow','Allow'],['block','Block']]},
  sbfm_static_resource_protection: {group:'sbfm', label:'Static resource protection', hint:'Also checks images, CSS and JS. Can block real visitors', bool:true},
  optimize_wordpress: {group:'sbfm', label:'Optimize for WordPress', hint:'Lets WordPress loopback requests through', bool:true},
};
const GROUPS = [
  {id:'ai',     title:'AI crawlers',              tag:'All plans'},
  {id:'robots', title:'robots.txt',               tag:'All plans'},
  {id:'bfm',    title:'Bot Fight Mode',           tag:'Free plan'},
  {id:'sbfm',   title:'Super Bot Fight Mode',     tag:'Pro plan and above'},
];

let zones = [];
const state = {};          // zone id -> {config, type, allowed}
const selected = new Set();

function qs(s,ctx){ return (ctx||document).querySelector(s); }
function esc(s){ return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }
function toast(msg, ok=true, icon){
  const el = document.createElement('div');
  el.className = 'wpwaf-bt-toast ' + (ok ? 'ok' : 'err');
  if (window.wpwafToastBody) el.innerHTML = wpwafToastBody(msg, ok, icon); else el.textContent = msg;
  document.body.appendChild(el);
  setTimeout(() => { el.style.transition = 'opacity .3s'; el.style.opacity = '0'; setTimeout(() => el.remove(), 300); }, 3200);
}
function ajax(action, data){
  const fd = new FormData();
  fd.append('action', action); fd.append('nonce', NONCE);
  Object.entries(data || {}).forEach(([k,v]) => fd.append(k, v));
  return fetch(AJAX_URL, {method:'POST', body:fd, credentials:'same-origin'})
    .then(r => r.json())
    .catch(e => ({success:false, data:{message:e.message}}));
}
function errMsg(res){ return (res && res.data && res.data.message) ? res.data.message : 'Request failed'; }

/* Run tasks with a small concurrency limit to stay inside Cloudflare rate limits. */
async function runPool(items, worker){
  let i = 0;
  const next = async () => { while (i < items.length) { const item = items[i++]; await worker(item); } };
  await Promise.all(Array.from({length: Math.min(CONCURRENCY, items.length)}, next));
}

function planClass(plan){
  const p = String(plan || '').toLowerCase();
  if (p.includes('enterprise')) return 'ent';
  if (p.includes('business')) return 'biz';
  if (p.includes('pro')) return 'pro';
  return 'free';
}
function flareFor(zoneId){
  const f = FLARES[zoneId];
  if (!f || !f.flare_id) return null;
  const p = PRESETS.find(x => x.id === f.flare_id) || {label:'Custom', color:'#9ca3af'};
  return {id:f.flare_id, label: (f.flare_id === 'custom' && f.custom_label) ? f.custom_label : p.label, color:p.color};
}
function flareHTML(zoneId){
  const f = flareFor(zoneId);
  return f ? `<span class="wpwaf-bt-flare" style="color:${esc(f.color)};border-color:${esc(f.color)};background:${esc(f.color)}14;">${esc(f.label)}</span>` : '';
}
function optLabel(field, val){
  const d = FIELDS[field];
  if (!d) return String(val);
  if (d.bool) return val ? 'On' : 'Off';
  const o = (d.opts || []).find(x => x[0] === val);
  return o ? o[1] : String(val);
}

/* ── Bulk policy form ─────────────────────────────────────────────────────── */
function bulkFieldHTML(key){
  const d = FIELDS[key];
  const opts = d.bool ? [['on','On'],['off','Off']] : d.opts;
  return `<div class="wpwaf-bt-field">
    <div class="wpwaf-bt-field-text"><div class="wpwaf-bt-field-name">${esc(d.label)}</div><div class="wpwaf-bt-field-hint">${esc(d.hint)}</div></div>
    <select data-bulk="${key}" aria-label="${esc(d.label)}"><option value="">No change</option>${opts.map(o => `<option value="${o[0]}">${esc(o[1])}</option>`).join('')}</select>
  </div>`;
}
function renderBulk(){
  qs('#wpwaf-bt-bulk-groups').innerHTML = GROUPS.map(g =>
    `<div class="wpwaf-bt-group">
      <div class="wpwaf-bt-group-title">${esc(g.title)} <span class="wpwaf-bt-plan-tag">${esc(g.tag)}</span></div>
      ${Object.keys(FIELDS).filter(k => FIELDS[k].group === g.id).map(bulkFieldHTML).join('')}
    </div>`).join('');
}
function bulkChanges(){
  const out = {};
  document.querySelectorAll('[data-bulk]').forEach(sel => {
    if (!sel.value) return;
    const d = FIELDS[sel.dataset.bulk];
    out[sel.dataset.bulk] = d.bool ? (sel.value === 'on') : sel.value;
  });
  return out;
}

/* ── Bulk zone picker ─────────────────────────────────────────────────────── */
function pickFiltered(){
  const q = (qs('#wpwaf-bt-pick-search').value || '').toLowerCase();
  const fl = qs('#wpwaf-bt-pick-flare').value;
  return zones.filter(z => z.name.toLowerCase().includes(q) && (!fl || (flareFor(z.id) || {}).id === fl || (fl.startsWith('custom:') && (flareFor(z.id) || {}).label === fl.slice(7))));
}
function renderPicker(){
  const list = qs('#wpwaf-bt-pick-list');
  const shown = pickFiltered();
  if (!shown.length){ list.innerHTML = '<div class="wpwaf-bt-empty">No zones match.</div>'; updatePickCount(); return; }
  list.innerHTML = shown.map(z => `<label class="wpwaf-bt-pick-item">
      <input type="checkbox" value="${esc(z.id)}" ${selected.has(z.id) ? 'checked' : ''}>
      <span class="wpwaf-bt-pick-name">${esc(z.name)}</span>
      <span class="wpwaf-bt-pill wpwaf-bt-pill--${planClass(z.plan)}">${esc(String(z.plan || '').replace(/ Website$/,''))}</span>
    </label>`).join('');
  updatePickCount();
}
function updatePickCount(){
  qs('#wpwaf-bt-pick-count').textContent = selected.size + ' of ' + zones.length + ' selected';
}
function renderFlareFilter(){
  const seen = new Map();
  zones.forEach(z => { const f = flareFor(z.id); if (f){ const key = f.id === 'custom' ? 'custom:' + f.label : f.id; seen.set(key, f.label); } });
  const sel = qs('#wpwaf-bt-pick-flare');
  if (!seen.size){ sel.style.display = 'none'; return; }
  sel.innerHTML = '<option value="">All flares</option>' + [...seen].map(([k,l]) => `<option value="${esc(k)}">${esc(l)}</option>`).join('');
  sel.style.display = '';
}

/* ── Zone list ────────────────────────────────────────────────────────────── */
function zoneHTML(z){
  return `<div class="wpwaf-bt-zone" data-zone="${esc(z.id)}">
    <div class="wpwaf-bt-zone-row">
      <div class="wpwaf-bt-zone-name">${esc(z.name)}</div>
      <div class="wpwaf-bt-zone-meta">
        <span class="wpwaf-bt-pill wpwaf-bt-pill--${planClass(z.plan)}">${esc(z.plan)}</span>
        ${flareHTML(z.id)}
        <button type="button" class="wpwaf-bt-btn wpwaf-bt-btn--sm" data-open="${esc(z.id)}">Settings</button>
      </div>
      <div class="wpwaf-bt-summary" data-summary="${esc(z.id)}"></div>
    </div>
    <div class="wpwaf-bt-zone-edit" data-edit="${esc(z.id)}"></div>
  </div>`;
}
function renderList(){
  const q = (qs('#wpwaf-bt-search').value || '').toLowerCase();
  const shown = zones.filter(z => z.name.toLowerCase().includes(q));
  qs('#wpwaf-bt-count').textContent = zones.length + ' zone' + (zones.length === 1 ? '' : 's');
  const list = qs('#wpwaf-bt-list');
  if (!shown.length){ list.innerHTML = '<div class="wpwaf-bt-empty">No zones match your filter.</div>'; return; }
  list.innerHTML = shown.map(zoneHTML).join('');
  shown.forEach(z => { if (state[z.id]) { renderSummary(z.id); renderEditor(z.id); } });
}
function renderSummary(zoneId){
  const box = qs(`[data-summary="${zoneId}"]`);
  const st = state[zoneId];
  if (!box || !st) return;
  const c = st.config, chips = [];
  const chip = (text, kind) => chips.push(`<span class="wpwaf-bt-chip ${kind ? 'wpwaf-bt-chip--' + kind : ''}">${esc(text)}</span>`);
  if ('ai_training' in c) chip('AI training: ' + optLabel('ai_training', c.ai_training), c.ai_training === 'disabled' ? '' : 'block');
  if ('ai_search' in c) chip('AI search: ' + optLabel('ai_search', c.ai_search), c.ai_search === 'disabled' ? '' : 'block');
  if ('ai_user' in c) chip('AI agents: ' + optLabel('ai_user', c.ai_user), c.ai_user === 'disabled' ? '' : 'block');
  if ('is_robots_txt_managed' in c) chip('Managed robots.txt: ' + (c.is_robots_txt_managed ? 'On' : 'Off'), c.is_robots_txt_managed ? 'on' : '');
  if (st.type === 'bfm' && 'fight_mode' in c) chip('Bot Fight Mode: ' + (c.fight_mode ? 'On' : 'Off'), c.fight_mode ? 'on' : '');
  if ((st.type === 'sbfm_d' || st.type === 'sbfm_l') && 'sbfm_definitely_automated' in c) chip('Definitely automated: ' + optLabel('sbfm_definitely_automated', c.sbfm_definitely_automated), c.sbfm_definitely_automated === 'allow' ? '' : 'on');
  if (st.type === 'sub') chip('Enterprise Bot Management: managed in Cloudflare', '');
  box.innerHTML = chips.join('');
}
function editorFieldHTML(zoneId, key, st){
  const d = FIELDS[key];
  const allowed = st.allowed.includes(key);
  const val = st.config[key];
  let ctrl;
  if (!allowed) {
    ctrl = `<span class="wpwaf-bt-field-hint">${d.biz ? 'Business plan and above' : 'Not on this plan'}</span>`;
  } else if (d.bool) {
    ctrl = `<label class="wpwaf-bt-switch"><input type="checkbox" data-zfield="${key}" data-zone="${esc(zoneId)}" ${val ? 'checked' : ''} aria-label="${esc(d.label)}"><span class="wpwaf-bt-slider"></span></label>`;
  } else {
    const opts = d.opts.slice();
    if (val !== undefined && !opts.some(o => o[0] === val)) opts.push([val, String(val)]);
    ctrl = `<select data-zfield="${key}" data-zone="${esc(zoneId)}" aria-label="${esc(d.label)}">${val === undefined ? '<option value="" selected>Not set</option>' : ''}${opts.map(o => `<option value="${esc(o[0])}" ${o[0] === val ? 'selected' : ''}>${esc(o[1])}</option>`).join('')}</select>`;
  }
  return `<div class="wpwaf-bt-field ${allowed ? '' : 'is-off'}">
    <div class="wpwaf-bt-field-text"><div class="wpwaf-bt-field-name">${esc(d.label)}</div><div class="wpwaf-bt-field-hint">${esc(d.hint)}</div></div>
    ${ctrl}
  </div>`;
}
function renderEditor(zoneId){
  const box = qs(`[data-edit="${zoneId}"]`);
  const st = state[zoneId];
  if (!box || !st) return;
  const groups = GROUPS.filter(g => {
    if (g.id === 'bfm') return st.type === 'bfm';
    if (g.id === 'sbfm') return st.type === 'sbfm_d' || st.type === 'sbfm_l';
    return true;
  });
  let html = `<div class="wpwaf-bt-groups">` + groups.map(g =>
    `<div class="wpwaf-bt-group">
      <div class="wpwaf-bt-group-title">${esc(g.title)}</div>
      ${Object.keys(FIELDS).filter(k => FIELDS[k].group === g.id).map(k => editorFieldHTML(zoneId, k, st)).join('')}
    </div>`).join('') + `</div>`;
  if (st.type === 'sub') html += `<p class="wpwaf-bt-field-hint" style="margin:10px 0 0;">This zone uses Enterprise Bot Management. Its bot score rules are managed in the Cloudflare dashboard.</p>`;
  box.innerHTML = html;
}

function loadZone(zone){
  return ajax('wpwaf_bt_get', {zone_id: zone.id, plan: zone.plan || ''}).then(res => {
    if (res.success){
      state[zone.id] = {config: res.data.config || {}, type: res.data.type, allowed: res.data.allowed || []};
      renderSummary(zone.id); renderEditor(zone.id);
    } else {
      const box = qs(`[data-edit="${zone.id}"]`);
      if (box) box.innerHTML = `<div class="wpwaf-bt-error">${esc(errMsg(res))}</div>`;
      const sum = qs(`[data-summary="${zone.id}"]`);
      if (sum) sum.innerHTML = `<span class="wpwaf-bt-chip wpwaf-bt-chip--block">Could not load settings</span>`;
    }
    return res;
  });
}

/* Single setting change from a zone editor. */
function saveZoneField(el){
  const zoneId = el.dataset.zone, key = el.dataset.zfield;
  const zone = zones.find(z => z.id === zoneId);
  const st = state[zoneId];
  if (!zone || !st) return;
  const d = FIELDS[key];
  const value = d.bool ? el.checked : el.value;
  if (!d.bool && value === '') return;
  if (key === 'fight_mode' && value === true && !confirm('Turn on Bot Fight Mode for ' + zone.name + '?\n\nIt cannot be skipped by custom rules and can block uptime monitors, payment webhooks, XML-RPC and server-to-server REST calls.')) { el.checked = false; return; }
  const prev = st.config[key];
  el.disabled = true;
  ajax('wpwaf_bt_apply', {zone_id: zoneId, plan: zone.plan || '', changes: JSON.stringify({[key]: value})}).then(res => {
    el.disabled = false;
    if (res.success && (res.data.applied || []).includes(key)){
      st.config = res.data.config || st.config;
      renderSummary(zoneId);
      toast('Saved: ' + d.label + ' on ' + zone.name);
    } else {
      if (d.bool) el.checked = !!prev; else el.value = prev === undefined ? '' : prev;
      toast(res.success ? (d.label + ' is not available on this plan') : ('Failed: ' + errMsg(res)), false);
    }
  });
}

/* ── Bulk apply ───────────────────────────────────────────────────────────── */
function resultRow(name, ok, msg){
  return `<div class="wpwaf-bt-result ${ok ? 'ok' : 'err'}"><span class="wpwaf-bt-result-icon">${wpwafIcon(ok ? 'check' : 'x')}</span><div style="min-width:0;"><div class="wpwaf-bt-result-zone">${esc(name)}</div><div class="wpwaf-bt-result-msg">${esc(msg)}</div></div></div>`;
}
async function applyBulk(){
  const changes = bulkChanges();
  const keys = Object.keys(changes);
  if (!keys.length){ toast('Pick at least one setting to change', false); return; }
  const targets = zones.filter(z => selected.has(z.id));
  if (!targets.length){ toast('Select at least one zone', false); return; }
  let msg = 'Apply ' + keys.length + ' setting' + (keys.length === 1 ? '' : 's') + ' to ' + targets.length + ' zone' + (targets.length === 1 ? '' : 's') + '?';
  if (changes.fight_mode === true) msg += '\n\nBot Fight Mode cannot be skipped by custom rules and can block uptime monitors, payment webhooks, XML-RPC and server-to-server REST calls.';
  if (!confirm(msg)) return;

  const btn = qs('#wpwaf-bt-apply'), note = qs('#wpwaf-bt-apply-note'), box = qs('#wpwaf-bt-results');
  btn.disabled = true;
  box.innerHTML = ''; box.classList.add('show');
  let done = 0, ok = 0;
  note.textContent = '0 of ' + targets.length + ' done';
  await runPool(targets, async z => {
    const res = await ajax('wpwaf_bt_apply', {zone_id: z.id, plan: z.plan || '', changes: JSON.stringify(changes)});
    done++;
    if (res.success){
      const applied = res.data.applied || [], skipped = res.data.skipped || [];
      state[z.id] = {config: res.data.config || {}, type: res.data.type, allowed: res.data.allowed || []};
      renderSummary(z.id); renderEditor(z.id);
      let m = applied.length ? 'Updated: ' + applied.map(k => FIELDS[k].label).join(', ') : 'Nothing to update on this plan';
      if (skipped.length) m += '. Not on this plan, skipped: ' + skipped.map(k => FIELDS[k].label).join(', ');
      if (applied.length) ok++;
      box.insertAdjacentHTML('beforeend', resultRow(z.name, applied.length > 0, m));
    } else {
      box.insertAdjacentHTML('beforeend', resultRow(z.name, false, errMsg(res)));
    }
    note.textContent = done + ' of ' + targets.length + ' done';
  });
  btn.disabled = false;
  note.textContent = 'Updated ' + ok + ' of ' + targets.length + ' zone' + (targets.length === 1 ? '' : 's');
  toast('Bot settings updated on ' + ok + ' zone' + (ok === 1 ? '' : 's'), ok === targets.length);
}

/* ── Events ───────────────────────────────────────────────────────────────── */
document.addEventListener('change', e => {
  const t = e.target;
  if (t.matches && t.matches('#wpwaf-bt-pick-list input[type=checkbox]')){
    t.checked ? selected.add(t.value) : selected.delete(t.value);
    updatePickCount();
  } else if (t.matches && t.matches('[data-zfield]')){
    saveZoneField(t);
  } else if (t.id === 'wpwaf-bt-pick-flare'){
    renderPicker();
  }
});
document.addEventListener('click', e => {
  const open = e.target.closest && e.target.closest('[data-open]');
  if (open){
    const id = open.dataset.open;
    const row = qs(`.wpwaf-bt-zone[data-zone="${id}"]`);
    const isOpen = row.classList.toggle('open');
    open.textContent = isOpen ? 'Close' : 'Settings';
    if (isOpen && !state[id]){
      qs(`[data-edit="${id}"]`).innerHTML = '<div class="wpwaf-bt-loading"><span class="wpwaf-bt-spinner"></span> Loading settings&hellip;</div>';
      loadZone(zones.find(z => z.id === id));
    }
  }
});
qs('#wpwaf-bt-pick-search').addEventListener('input', renderPicker);
qs('#wpwaf-bt-search').addEventListener('input', renderList);
qs('#wpwaf-bt-pick-all').addEventListener('click', () => { pickFiltered().forEach(z => selected.add(z.id)); renderPicker(); });
qs('#wpwaf-bt-pick-none').addEventListener('click', () => { selected.clear(); renderPicker(); });
qs('#wpwaf-bt-apply').addEventListener('click', applyBulk);
qs('#wpwaf-bt-load-all').addEventListener('click', async function(){
  if (!zones.length) return;
  this.disabled = true;
  const label = this.textContent;
  let n = 0;
  await runPool(zones, async z => { await loadZone(z); n++; this.textContent = 'Checking ' + n + ' of ' + zones.length; });
  this.disabled = false; this.textContent = label;
});

/* ── Init ─────────────────────────────────────────────────────────────────── */
renderBulk();
ajax('wpwaf_list_zones', {}).then(res => {
  if (!res.success){
    const m = `<div class="wpwaf-bt-empty">Failed to load zones: ${esc(errMsg(res))}</div>`;
    qs('#wpwaf-bt-list').innerHTML = m; qs('#wpwaf-bt-pick-list').innerHTML = m;
    qs('#wpwaf-bt-count').textContent = '0 zones';
    return;
  }
  zones = (res.data.zones || []).slice().sort((a,b) => a.name.localeCompare(b.name));
  if (zones.length > 8) qs('#wpwaf-bt-search').style.display = '';
  renderFlareFilter();
  renderPicker();
  renderList();
});
})();
