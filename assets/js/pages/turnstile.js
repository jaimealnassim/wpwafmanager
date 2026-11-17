/* WP WAF Manager: Turnstile screen.
 * Values from PHP arrive in window.wpwafPage, printed by WPWAF_Admin::page_script()
 * in includes/views/page-turnstile.php. */
'use strict';
(function(){
const NONCE    = wpwafPage.nonce;
const AJAX_URL = wpwafPage.ajax_url;
const HAS_CREDS = wpwafPage.has_creds;
const FREE_WIDGETS = 20, FREE_HOSTS = 10;
const MODE_LABELS = { 'managed':'Managed', 'non-interactive':'Non-interactive', 'invisible':'Invisible' };
let accountId = '';
let widgets = [];

const qs  = (s,c) => (c||document).querySelector(s);
const qsa = (s,c) => Array.from((c||document).querySelectorAll(s));
function esc(v){ return String(v == null ? '' : v).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }
function toast(msg, ok=true, icon){
  const el = document.createElement('div');
  el.className = 'wpwaf-ts-toast ' + (ok ? 'ok' : 'err');
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
  if (on){ btn.dataset.label = btn.innerHTML; btn.disabled = true; btn.innerHTML = '<span class="wpwaf-ts-spinner"></span> ' + esc(label || 'Working…'); }
  else { btn.disabled = false; if (btn.dataset.label) btn.innerHTML = btn.dataset.label; }
}
function parseDomains(text){
  return String(text || '').split(/[\s,]+/).map(s => s.trim().toLowerCase()).filter(Boolean);
}
function fmtDate(iso){
  if (!iso) return '';
  const d = new Date(iso);
  return isNaN(d) ? '' : d.toLocaleDateString(undefined, {year:'numeric', month:'short', day:'numeric'});
}
function copy(text, what){
  const done = () => toast(what + ' copied');
  if (navigator.clipboard && window.isSecureContext){
    navigator.clipboard.writeText(text).then(done, () => fallbackCopy(text) && done());
  } else if (fallbackCopy(text)) { done(); } else { toast('Copy failed. Select the text and copy it manually.', false); }
}
function fallbackCopy(text){
  const ta = document.createElement('textarea');
  ta.value = text; ta.setAttribute('readonly', ''); ta.style.position = 'fixed'; ta.style.opacity = '0';
  document.body.appendChild(ta); ta.select();
  let ok = false; try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
  ta.remove(); return ok;
}

// ── Accounts and list ───────────────────────────────────────────────────────
function loadAccounts(){
  const sel = qs('#wpwaf-ts-account');
  ajax('wpwaf_cf_accounts', {}).then(res => {
    if (!res.success){ sel.innerHTML = '<option value="">Could not load accounts</option>'; showListError(res); return; }
    const accts = res.data.accounts || [];
    if (!accts.length){ sel.innerHTML = '<option value="">No accounts found</option>'; qs('#wpwaf-ts-list').innerHTML = '<div class="wpwaf-ts-empty">This token cannot see any Cloudflare accounts.</div>'; return; }
    sel.innerHTML = accts.map(a => '<option value="' + esc(a.id) + '">' + esc(a.name) + '</option>').join('');
    sel.value = accts[0].id; accountId = accts[0].id;
    loadWidgets();
  });
  sel.addEventListener('change', () => { accountId = sel.value; loadWidgets(); });
}
function showListError(res){
  const perm = res && res.data && res.data.perm_error;
  qs('#wpwaf-ts-list').innerHTML = '<div class="wpwaf-ts-notice wpwaf-ts-notice--error" style="margin:0;">' + esc(errMsg(res))
    + (perm ? '<div style="margin-top:6px;">Needed permission: <span class="wpwaf-ts-perm">Account → Turnstile → Edit</span></div>' : '') + '</div>';
}
function loadWidgets(){
  if (!accountId) return;
  qs('#wpwaf-ts-list').innerHTML = '<div class="wpwaf-ts-loading"><span class="wpwaf-ts-spinner"></span> Loading widgets…</div>';
  qs('#wpwaf-ts-usage').innerHTML = '';
  ajax('wpwaf_ts_list', {account_id: accountId}).then(res => {
    if (!res.success){ showListError(res); return; }
    widgets = res.data.widgets || [];
    renderList();
  });
}
function renderUsage(){
  const n = widgets.length, pct = Math.min(100, Math.round(n / FREE_WIDGETS * 100));
  qs('#wpwaf-ts-usage').innerHTML = '<span>' + n + ' of ' + FREE_WIDGETS + ' widgets (Free plan)</span><span class="wpwaf-ts-meter' + (n >= FREE_WIDGETS ? ' is-full' : '') + '"><span style="width:' + pct + '%"></span></span>';
}
function renderList(){
  const box = qs('#wpwaf-ts-list');
  if (!box) return;
  renderUsage();
  if (!widgets.length){ box.innerHTML = '<div class="wpwaf-ts-empty">No Turnstile widgets in this account yet. Create one for a client site.</div>'; return; }
  box.innerHTML = '<table class="wpwaf-ts-table"><thead><tr><th>Widget</th><th>Hostnames</th><th>Mode</th><th style="text-align:right;">Actions</th></tr></thead><tbody>'
    + widgets.map(rowHTML).join('') + '</tbody></table>';
  qsa('[data-act]', box).forEach(b => b.addEventListener('click', onAction));
}
function rowHTML(w){
  const n = (w.domains || []).length;
  const chips = (w.domains || []).map(d => '<span class="wpwaf-ts-chip">' + esc(d) + '</span>').join('');
  const created = fmtDate(w.created_on);
  return '<tr data-key="' + esc(w.sitekey) + '">'
    + '<td data-label="Widget"><div class="wpwaf-ts-name">' + esc(w.name || 'Untitled') + '</div>'
    + '<div class="wpwaf-ts-sub wpwaf-ts-mono">' + esc(w.sitekey) + '</div>' + (created ? '<div class="wpwaf-ts-sub">Created ' + esc(created) + '</div>' : '') + '</td>'
    + '<td data-label="Hostnames"><div class="wpwaf-ts-count' + (n >= FREE_HOSTS ? ' is-full' : '') + '">' + n + ' of ' + FREE_HOSTS + ' (Free plan)</div><div class="wpwaf-ts-chips">' + (chips || '<span class="wpwaf-ts-sub">None</span>') + '</div></td>'
    + '<td data-label="Mode"><span class="wpwaf-ts-mode">' + esc(MODE_LABELS[w.mode] || w.mode) + '</span></td>'
    + '<td class="wpwaf-ts-td-actions"><div class="wpwaf-ts-actions">'
    + '<button type="button" class="wpwaf-ts-btn wpwaf-ts-btn--sm wpwaf-ts-btn--primary" data-act="keys">Keys</button>'
    + '<button type="button" class="wpwaf-ts-btn wpwaf-ts-btn--sm" data-act="edit">Edit</button>'
    + '<button type="button" class="wpwaf-ts-btn wpwaf-ts-btn--sm" data-act="rotate">Rotate secret</button>'
    + '<button type="button" class="wpwaf-ts-btn wpwaf-ts-btn--sm wpwaf-ts-btn--danger" data-act="delete">Delete</button>'
    + '</div></td></tr>';
}
function findWidget(key){ return widgets.find(w => w.sitekey === key); }
function rowFor(key){ return qsa('#wpwaf-ts-list tr[data-key]').find(r => r.dataset.key === key); }
function replaceWidget(w){
  if (!w || !w.sitekey) return;
  const i = widgets.findIndex(x => x.sitekey === w.sitekey);
  if (i >= 0) widgets[i] = Object.assign({}, widgets[i], w); else widgets.unshift(w);
  renderList();
}
function closePanels(){ qsa('.wpwaf-ts-edit-row, .wpwaf-ts-keys-row').forEach(r => r.remove()); }

// Show the site key and secret under a widget row, with copy buttons.
function showKeys(tr, sitekey, secret, note){
  closePanels();
  if (!tr) return;
  const row = document.createElement('tr');
  row.className = 'wpwaf-ts-keys-row';
  row.innerHTML = '<td colspan="4"><dl class="wpwaf-ts-keys">'
    + '<dt>Site key</dt><dd>' + esc(sitekey) + '</dd><button type="button" class="wpwaf-ts-btn wpwaf-ts-btn--sm" data-copy="site">Copy</button>'
    + '<dt>Secret key</dt><dd>' + esc(secret) + '</dd><button type="button" class="wpwaf-ts-btn wpwaf-ts-btn--sm" data-copy="secret">Copy</button>'
    + '</dl>'
    + (note ? '<div class="wpwaf-ts-keys-note">' + esc(note) + '</div>' : '')
    + '<div class="wpwaf-ts-actions" style="margin-top:10px;"><button type="button" class="wpwaf-ts-btn wpwaf-ts-btn--sm" data-copy="hide">Hide keys</button></div></td>';
  tr.after(row);
  qs('[data-copy="site"]', row).addEventListener('click', () => copy(sitekey, 'Site key'));
  qs('[data-copy="secret"]', row).addEventListener('click', () => copy(secret, 'Secret key'));
  qs('[data-copy="hide"]', row).addEventListener('click', () => row.remove());
}

function onAction(){
  const tr = this.closest('tr'), key = tr && tr.dataset.key, w = findWidget(key), act = this.dataset.act;
  if (!w) return;
  if (act === 'keys'){
    const open = tr.nextElementSibling && tr.nextElementSibling.classList.contains('wpwaf-ts-keys-row');
    if (open){ closePanels(); return; }
    busy(this, true, 'Loading…');
    ajax('wpwaf_ts_keys', {account_id: accountId, sitekey: key}).then(res => {
      busy(this, false);
      if (!res.success){ toast(errMsg(res), false); return; }
      showKeys(tr, res.data.sitekey, res.data.secret, 'Keep the secret key private. Paste it only into the client site\'s Turnstile settings.');
    });
  } else if (act === 'edit'){
    openEdit(tr, w);
  } else if (act === 'rotate'){
    const shared = (w.domains || []).length > 1 ? ' Every site using this widget needs the new key.' : '';
    if (!confirm('Rotate the secret key for "' + w.name + '"?' + shared + ' The old key keeps working for 2 hours, and the secret cannot be rotated again during that time.')) return;
    busy(this, true, 'Rotating…');
    ajax('wpwaf_ts_rotate', {account_id: accountId, sitekey: key, invalidate_immediately: '0'}).then(res => {
      busy(this, false);
      if (!res.success){ toast(errMsg(res), false); return; }
      toast('Secret rotated. The old key works for 2 more hours.');
      if (res.data.secret) showKeys(rowFor(key), key, res.data.secret, 'New secret key. Update every client site that uses this widget within 2 hours.');
    });
  } else if (act === 'delete'){
    if (!confirm('Delete the widget "' + w.name + '" from Cloudflare? Every site using it will stop showing the security check. This cannot be undone.')) return;
    busy(this, true, 'Deleting…');
    ajax('wpwaf_ts_delete', {account_id: accountId, sitekey: key}).then(res => {
      if (!res.success){ busy(this, false); toast(errMsg(res), false); return; }
      widgets = widgets.filter(x => x.sitekey !== key);
      renderList(); toast('Widget deleted');
    });
  }
}
function openEdit(tr, w){
  const open = tr.nextElementSibling && tr.nextElementSibling.classList.contains('wpwaf-ts-edit-row');
  closePanels();
  if (open) return;
  const row = document.createElement('tr');
  row.className = 'wpwaf-ts-edit-row';
  row.innerHTML = '<td colspan="4"><div class="wpwaf-ts-form-grid">'
    + '<div class="wpwaf-ts-field"><label>Widget name</label><input type="text" class="wpwaf-ts-input" data-f="name" maxlength="254" value="' + esc(w.name) + '"></div>'
    + '<div class="wpwaf-ts-field"><label>Mode</label><select class="wpwaf-ts-select" data-f="mode">'
    + Object.keys(MODE_LABELS).map(m => '<option value="' + m + '"' + (m === w.mode ? ' selected' : '') + '>' + MODE_LABELS[m] + '</option>').join('')
    + '</select></div></div>'
    + '<div class="wpwaf-ts-field"><label>Hostnames</label><textarea class="wpwaf-ts-textarea" rows="3" data-f="domains">' + esc((w.domains || []).join('\n')) + '</textarea>'
    + '<div class="wpwaf-ts-field-hint">One per line. Each hostname also covers its subdomains. Removing a hostname stops the widget working on that site.</div></div>'
    + '<div class="wpwaf-ts-actions"><button type="button" class="wpwaf-ts-btn wpwaf-ts-btn--primary" data-e="save">Save changes</button><button type="button" class="wpwaf-ts-btn" data-e="cancel">Cancel</button></div></td>';
  tr.after(row);
  qs('[data-e="cancel"]', row).addEventListener('click', () => row.remove());
  qs('[data-e="save"]', row).addEventListener('click', function(){
    const domains = parseDomains(qs('[data-f="domains"]', row).value);
    if (!domains.length){ toast('Add at least one hostname', false); return; }
    busy(this, true, 'Saving…');
    ajax('wpwaf_ts_update', {
      account_id: accountId, sitekey: w.sitekey,
      name: qs('[data-f="name"]', row).value.trim(),
      mode: qs('[data-f="mode"]', row).value,
      domains: JSON.stringify(domains)
    }).then(res => {
      if (!res.success){ busy(this, false); toast(errMsg(res), false); return; }
      replaceWidget(res.data.widget);
      toast('Widget updated');
    });
  });
}

// ── Create ──────────────────────────────────────────────────────────────────
function initCreate(){
  const form = qs('#wpwaf-ts-new');
  qs('#wpwaf-ts-new-btn').addEventListener('click', () => {
    const show = form.style.display === 'none';
    form.style.display = show ? '' : 'none';
    if (show) qs('#wpwaf-ts-new-name').focus();
  });
  qs('#wpwaf-ts-new-cancel').addEventListener('click', () => { form.style.display = 'none'; });
  form.addEventListener('submit', e => {
    e.preventDefault();
    if (!accountId){ toast('Choose a Cloudflare account first', false); return; }
    const name = qs('#wpwaf-ts-new-name').value.trim();
    const domains = parseDomains(qs('#wpwaf-ts-new-domains').value);
    if (!name){ toast('Enter a widget name', false); return; }
    if (!domains.length){ toast('Add at least one hostname', false); return; }
    const btn = qs('#wpwaf-ts-new-save');
    busy(btn, true, 'Creating…');
    ajax('wpwaf_ts_create', {account_id: accountId, name, mode: qs('#wpwaf-ts-new-mode').value, domains: JSON.stringify(domains)}).then(res => {
      busy(btn, false);
      if (!res.success){ toast(errMsg(res), false); return; }
      form.reset(); form.style.display = 'none';
      widgets.unshift(res.data.widget); renderList();
      toast('Widget created');
      if (res.data.secret) showKeys(rowFor(res.data.widget.sitekey), res.data.widget.sitekey, res.data.secret, 'Copy these keys into the client site\'s Turnstile settings. You can show them again any time with Keys.');
    });
  });
}

if (HAS_CREDS){ initCreate(); loadAccounts(); }
})();
