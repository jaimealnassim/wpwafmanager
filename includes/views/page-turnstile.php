<?php
defined( 'ABSPATH' ) || exit;
?>
<style>
:root{--ts-orange:#FF6A00;--ts-border:#e2e6ea;--ts-bg:#f8f9fb;--ts-dark:#1a1a2e;--ts-muted:#6b7280;--ts-green:#059669;--ts-red:#dc2626;}
.wpwaf-ts-wrap{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;color:var(--ts-dark);}
.wpwaf-ts-wrap *{box-sizing:border-box;}
.wpwaf-ts-header{display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:20px;}
.wpwaf-ts-header h1{font-size:22px;font-weight:700;margin:0;padding:0;display:flex;align-items:center;gap:8px;color:var(--ts-dark);line-height:1.3;}
.wpwaf-ts-header h1 .dashicons{color:var(--ts-orange);font-size:24px;width:24px;height:24px;}
.wpwaf-ts-header-sub{font-size:12px;color:var(--ts-muted);margin-top:3px;}
.wpwaf-ts-notice{background:#fff8f5;border:1px solid #fcd9c0;border-radius:8px;padding:16px 20px;color:#92400e;font-size:13px;line-height:1.6;margin-bottom:16px;}
.wpwaf-ts-notice--error{background:#fef2f2;border-color:#fca5a5;color:#7f1d1d;}
.wpwaf-ts-notice--ok{background:#ecfdf5;border-color:#a7f3d0;color:#065f46;}
.wpwaf-ts-notice code{background:rgba(0,0,0,.05);padding:1px 5px;border-radius:4px;font-size:12px;overflow-wrap:anywhere;}

/* Cards */
.wpwaf-ts-card{background:#fff;border:1px solid var(--ts-border);border-radius:10px;margin-bottom:20px;overflow:hidden;}
.wpwaf-ts-card-head{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;padding:14px 18px;border-bottom:1px solid var(--ts-border);}
.wpwaf-ts-card-head h2{margin:0;font-size:15px;font-weight:700;display:flex;align-items:center;gap:8px;color:var(--ts-dark);}
.wpwaf-ts-card-body{padding:16px 18px;}

.wpwaf-ts-mono{font-family:SFMono-Regular,Menlo,Consolas,monospace;font-size:12px;overflow-wrap:anywhere;}
.wpwaf-ts-chips{display:flex;flex-wrap:wrap;gap:5px;}
.wpwaf-ts-chip{display:inline-block;padding:2px 8px;border-radius:20px;background:#f1f5f9;border:1px solid #e2e8f0;color:#334155;font-size:11px;font-weight:600;max-width:100%;overflow-wrap:anywhere;}

/* Buttons and inputs */
.wpwaf-ts-btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;height:32px;padding:0 14px;font-size:12px;font-weight:600;border-radius:6px;border:1px solid #d1d5db;background:#fff;color:#374151;cursor:pointer;transition:background .15s,border-color .15s;white-space:nowrap;text-decoration:none;}
.wpwaf-ts-btn:hover{background:#f3f4f6;border-color:#9ca3af;}
.wpwaf-ts-btn--primary{background:var(--ts-orange);border-color:var(--ts-orange);color:#fff;}
.wpwaf-ts-btn--primary:hover{background:#e05c00;border-color:#e05c00;color:#fff;}
.wpwaf-ts-btn--danger:hover{background:#fee2e2;border-color:#fca5a5;color:#b91c1c;}
.wpwaf-ts-btn--sm{height:28px;padding:0 10px;font-size:11px;}
.wpwaf-ts-btn:disabled{opacity:.5;cursor:not-allowed;}
.wpwaf-ts-actions{display:flex;flex-wrap:wrap;gap:8px;}
.wpwaf-ts-input,.wpwaf-ts-select,.wpwaf-ts-textarea{width:100%;max-width:100%;padding:7px 10px;border:1px solid var(--ts-border);border-radius:6px;font-size:13px;color:var(--ts-dark);background:#fff;}
.wpwaf-ts-textarea{font-family:SFMono-Regular,Menlo,Consolas,monospace;font-size:12px;resize:vertical;min-height:84px;}
.wpwaf-ts-input:focus,.wpwaf-ts-select:focus,.wpwaf-ts-textarea:focus{outline:none;border-color:var(--ts-orange);box-shadow:0 0 0 2px rgba(255,106,0,.1);}
.wpwaf-ts-field{margin-bottom:12px;}
.wpwaf-ts-field>label{display:block;font-size:12px;font-weight:600;margin-bottom:4px;}
.wpwaf-ts-field-hint{font-size:11px;color:var(--ts-muted);margin-top:4px;line-height:1.5;}
.wpwaf-ts-form{background:var(--ts-bg);border:1px solid var(--ts-border);border-radius:8px;padding:16px;margin-bottom:16px;}
.wpwaf-ts-form-grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:0 16px;}

/* Toolbar + usage */
.wpwaf-ts-toolbar{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:14px;}
.wpwaf-ts-toolbar .wpwaf-ts-select{width:auto;min-width:220px;flex:0 1 320px;}
.wpwaf-ts-usage{display:flex;align-items:center;gap:8px;font-size:12px;color:var(--ts-muted);margin-left:auto;}
.wpwaf-ts-meter{width:110px;height:6px;border-radius:6px;background:#e5e7eb;overflow:hidden;}
.wpwaf-ts-meter span{display:block;height:100%;background:var(--ts-orange);}
.wpwaf-ts-meter.is-full span{background:var(--ts-red);}

/* Widget table */
.wpwaf-ts-table{width:100%;border-collapse:collapse;}
.wpwaf-ts-table th{padding:9px 12px;font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.06em;text-align:left;border-bottom:1px solid var(--ts-border);background:#f8f9fa;}
.wpwaf-ts-table td{padding:11px 12px;font-size:13px;color:#374151;border-bottom:1px solid #f1f3f5;vertical-align:top;}
.wpwaf-ts-table tbody tr:last-child td{border-bottom:none;}
.wpwaf-ts-table td:first-child{width:26%;min-width:200px;}
.wpwaf-ts-name{font-weight:700;color:#111;overflow-wrap:anywhere;}
.wpwaf-ts-sub{font-size:11px;color:var(--ts-muted);margin-top:3px;}
.wpwaf-ts-mode{display:inline-block;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:600;background:#eef2ff;color:#3730a3;white-space:nowrap;}
.wpwaf-ts-count{font-size:11px;color:var(--ts-muted);margin-bottom:5px;}
.wpwaf-ts-count.is-full{color:var(--ts-red);font-weight:600;}
.wpwaf-ts-table td.wpwaf-ts-td-actions{text-align:right;}
.wpwaf-ts-td-actions .wpwaf-ts-actions{justify-content:flex-end;}
.wpwaf-ts-empty{text-align:center;padding:32px 16px;color:#9ca3af;font-size:13px;}

/* Keys panel */
.wpwaf-ts-keys-row td,.wpwaf-ts-edit-row td{background:var(--ts-bg);}
.wpwaf-ts-keys{display:grid;grid-template-columns:110px minmax(0,1fr) auto;gap:8px 12px;align-items:center;font-size:12px;}
.wpwaf-ts-keys dt{color:var(--ts-muted);font-weight:600;}
.wpwaf-ts-keys dd{margin:0;font-family:SFMono-Regular,Menlo,Consolas,monospace;background:#fff;border:1px solid var(--ts-border);border-radius:6px;padding:6px 9px;overflow-wrap:anywhere;min-width:0;}
.wpwaf-ts-keys-note{font-size:11px;color:#92400e;margin-top:10px;line-height:1.5;}

/* Help */
.wpwaf-ts-help{font-size:12px;color:#374151;line-height:1.7;}
.wpwaf-ts-help strong{color:var(--ts-dark);}
.wpwaf-ts-perm{display:inline-block;font-family:SFMono-Regular,Menlo,Consolas,monospace;font-size:12px;background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;padding:2px 8px;border-radius:5px;}

/* Spinner + toast */
.wpwaf-ts-spinner{display:inline-block;width:12px;height:12px;border:2px solid rgba(255,106,0,.3);border-top-color:var(--ts-orange);border-radius:50%;animation:wpwaf-ts-spin .6s linear infinite;vertical-align:-2px;}
@keyframes wpwaf-ts-spin{to{transform:rotate(360deg);}}
.wpwaf-ts-loading{padding:28px;text-align:center;color:var(--ts-muted);font-size:13px;}
.wpwaf-ts-toast{position:fixed;bottom:24px;right:24px;max-width:min(420px,calc(100vw - 32px));padding:10px 16px;border-radius:6px;font-size:13px;font-weight:600;color:#fff;z-index:999999;box-shadow:0 6px 18px rgba(0,0,0,.15);line-height:1.5;}
.wpwaf-ts-toast.ok{background:var(--ts-green);}.wpwaf-ts-toast.err{background:var(--ts-red);}

@media (max-width:782px){
  .wpwaf-ts-card-head,.wpwaf-ts-card-body{padding-left:14px;padding-right:14px;}
  .wpwaf-ts-form-grid{grid-template-columns:minmax(0,1fr);}
  .wpwaf-ts-toolbar .wpwaf-ts-select{flex:1 1 100%;min-width:0;width:100%;}
  .wpwaf-ts-usage{margin-left:0;}
  /* Widget table becomes stacked cards */
  .wpwaf-ts-table thead{display:none;}
  .wpwaf-ts-table,.wpwaf-ts-table tbody,.wpwaf-ts-table tr,.wpwaf-ts-table td{display:block;width:100%;}
  .wpwaf-ts-table tr{border-bottom:1px solid var(--ts-border);padding:8px 0;}
  .wpwaf-ts-table tbody tr:last-child{border-bottom:none;}
  .wpwaf-ts-table td,.wpwaf-ts-table td:first-child{border-bottom:none;padding:5px 2px;min-width:0;width:100%;}
  .wpwaf-ts-table td[data-label]:before{content:attr(data-label);display:block;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#9ca3af;margin-bottom:3px;}
  .wpwaf-ts-table td.wpwaf-ts-td-actions{text-align:left;}
  .wpwaf-ts-td-actions .wpwaf-ts-actions{justify-content:flex-start;}
  .wpwaf-ts-toast{left:16px;right:16px;bottom:16px;max-width:none;}
}
@media (max-width:480px){
  .wpwaf-ts-keys{grid-template-columns:minmax(0,1fr) auto;}
  .wpwaf-ts-keys dt{grid-column:1 / -1;margin-top:4px;}
  .wpwaf-ts-actions .wpwaf-ts-btn{flex:1 1 auto;}
  .wpwaf-ts-card-head .wpwaf-ts-btn{width:100%;}
}
</style>

<div class="wpwaf-ts-wrap">
	<div class="wpwaf-ts-header">
		<div>
			<h1><span class="dashicons dashicons-yes-alt"></span> Turnstile</h1>
			<div class="wpwaf-ts-header-sub">Create and manage Cloudflare Turnstile widgets for your client sites, and copy their keys into each site</div>
		</div>
	</div>

	<?php if ( ! $has_creds ) : ?>
	<div class="wpwaf-ts-notice">
		<strong>No Cloudflare account connected.</strong> Please <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpwafmanager' ) ); ?>">add your credentials</a> to manage Turnstile widgets.
	</div>
	<?php else : ?>

	<!-- Widgets -->
	<div class="wpwaf-ts-card">
		<div class="wpwaf-ts-card-head">
			<h2>🧩 Widgets</h2>
			<button type="button" class="wpwaf-ts-btn wpwaf-ts-btn--primary" id="wpwaf-ts-new-btn">+ Create widget</button>
		</div>
		<div class="wpwaf-ts-card-body">
			<div class="wpwaf-ts-toolbar">
				<select id="wpwaf-ts-account" class="wpwaf-ts-select" aria-label="Cloudflare account">
					<option value="">Loading accounts…</option>
				</select>
				<div class="wpwaf-ts-usage" id="wpwaf-ts-usage"></div>
			</div>

			<form class="wpwaf-ts-form" id="wpwaf-ts-new" style="display:none;" autocomplete="off">
				<div class="wpwaf-ts-form-grid">
					<div class="wpwaf-ts-field">
						<label for="wpwaf-ts-new-name">Widget name</label>
						<input type="text" id="wpwaf-ts-new-name" class="wpwaf-ts-input" maxlength="254" placeholder="Client name or site">
						<div class="wpwaf-ts-field-hint">Only for you. Not shown to visitors.</div>
					</div>
					<div class="wpwaf-ts-field">
						<label for="wpwaf-ts-new-mode">Mode</label>
						<select id="wpwaf-ts-new-mode" class="wpwaf-ts-select">
							<option value="managed">Managed (recommended)</option>
							<option value="non-interactive">Non-interactive</option>
							<option value="invisible">Invisible</option>
						</select>
						<div class="wpwaf-ts-field-hint">Managed shows a checkbox only when Cloudflare is unsure about the visitor.</div>
					</div>
				</div>
				<div class="wpwaf-ts-field">
					<label for="wpwaf-ts-new-domains">Hostnames</label>
					<textarea id="wpwaf-ts-new-domains" class="wpwaf-ts-textarea" rows="3" placeholder="client-site.com&#10;another-client.com"></textarea>
					<div class="wpwaf-ts-field-hint">One per line. Each hostname also covers its subdomains. No https://, paths, ports or *. Free plan: up to 10 hostnames per widget, so one widget can cover several client sites.</div>
				</div>
				<div class="wpwaf-ts-actions" style="margin-top:4px;">
					<button type="submit" class="wpwaf-ts-btn wpwaf-ts-btn--primary" id="wpwaf-ts-new-save">Create widget</button>
					<button type="button" class="wpwaf-ts-btn" id="wpwaf-ts-new-cancel">Cancel</button>
				</div>
			</form>

			<div id="wpwaf-ts-list"><div class="wpwaf-ts-loading"><span class="wpwaf-ts-spinner"></span> Loading widgets…</div></div>
		</div>
	</div>

	<?php endif; ?>

	<!-- Help -->
	<div class="wpwaf-ts-card">
		<div class="wpwaf-ts-card-head"><h2>🔑 Using a widget on a client site</h2></div>
		<div class="wpwaf-ts-card-body wpwaf-ts-help">
			Click <strong>Keys</strong> on a widget to copy its site key and secret key, then paste them into the Turnstile settings of the client site (for example a Turnstile, forms or security plugin). The widget's hostnames must include the client site's domain.<br>
			<strong>Rotating a secret</strong> gives the widget a new secret key. The old one keeps working for 2 hours, so update every client site that uses the widget within that time.<br>
			<strong>Token permission:</strong> managing widgets needs <span class="wpwaf-ts-perm">Account → Turnstile → Edit</span>. Edit the token at <a href="https://dash.cloudflare.com/profile/api-tokens" target="_blank" rel="noopener">dash.cloudflare.com/profile/api-tokens</a> and add it under Account permissions.<br>
			<strong>Plan limits:</strong> Free accounts can have up to 20 widgets with 10 hostnames each. Enterprise has unlimited widgets with up to 200 hostnames each.<br>
			Secret keys are never saved by this plugin. They are read from Cloudflare only when you ask to see them.
		</div>
	</div>
</div>

<script>
'use strict';
(function(){
const NONCE    = <?php echo wp_json_encode( $nonce ); ?>;
const AJAX_URL = <?php echo wp_json_encode( $ajax_url ); ?>;
const HAS_CREDS = <?php echo $has_creds ? 'true' : 'false'; ?>;
const FREE_WIDGETS = 20, FREE_HOSTS = 10;
const MODE_LABELS = { 'managed':'Managed', 'non-interactive':'Non-interactive', 'invisible':'Invisible' };
let accountId = '';
let widgets = [];

const qs  = (s,c) => (c||document).querySelector(s);
const qsa = (s,c) => Array.from((c||document).querySelectorAll(s));
function esc(v){ return String(v == null ? '' : v).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }
function toast(msg, ok=true){
  const el = document.createElement('div');
  el.className = 'wpwaf-ts-toast ' + (ok ? 'ok' : 'err');
  el.textContent = msg;
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
</script>
