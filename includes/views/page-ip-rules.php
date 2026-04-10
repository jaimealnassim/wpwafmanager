<?php
defined( 'ABSPATH' ) || exit;
$nonce     = wp_create_nonce( 'wpwaf_nonce' );
$ajax_url  = admin_url( 'admin-ajax.php' );
$active_id = WPWAF_Accounts::active_id();
?>
<style>
:root{--ip-orange:#FF6A00;--ip-border:#e2e6ea;--ip-bg:#f8f9fb;--ip-dark:#1a1a2e;--ip-muted:#6b7280;}
.cfwaf-ip-wrap{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;max-width:1100px;padding:24px 20px;color:var(--ip-dark);}
.cfwaf-ip-header{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px;}
.cfwaf-ip-header h1{font-size:22px;font-weight:700;margin:0;display:flex;align-items:center;gap:8px;}
.cfwaf-ip-header h1 .dashicons{color:var(--ip-orange);font-size:24px;width:24px;height:24px;}
.cfwaf-ip-header-sub{font-size:12px;color:var(--ip-muted);margin-top:3px;}
.cfwaf-ip-no-creds{background:#fff8f5;border:1px solid #fcd9c0;border-radius:8px;padding:20px 24px;color:#92400e;}
.cfwaf-ip-notice{background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:14px 16px;font-size:13px;color:#1e40af;margin-bottom:20px;display:flex;gap:10px;align-items:flex-start;}
.cfwaf-ip-notice strong{font-weight:700;}
.cfwaf-ip-notice-warn{background:#fff8f5;border-color:#fcd9c0;color:#92400e;}

/* Toolbar */
.cfwaf-ip-toolbar{display:flex;align-items:center;gap:10px;margin-bottom:16px;flex-wrap:wrap;}
.cfwaf-ip-search{padding:7px 12px;border:1px solid var(--ip-border);border-radius:6px;font-size:13px;width:240px;}
.cfwaf-ip-search:focus{outline:none;border-color:var(--ip-orange);}
.cfwaf-ip-filter-tabs{display:flex;border:1px solid var(--ip-border);border-radius:6px;overflow:hidden;}
.cfwaf-ip-filter-tab{padding:6px 14px;font-size:12px;font-weight:600;cursor:pointer;background:#fff;border:none;color:var(--ip-muted);transition:all .15s;}
.cfwaf-ip-filter-tab.active{background:var(--ip-dark);color:#fff;}
.cfwaf-ip-count{margin-left:auto;font-size:12px;color:var(--ip-muted);}
.cfwaf-ip-btn{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:6px;font-size:13px;font-weight:600;cursor:pointer;border:none;transition:all .15s;white-space:nowrap;}
.cfwaf-ip-btn-primary{background:var(--ip-orange);color:#fff;}
.cfwaf-ip-btn-primary:hover{background:#d95500;}
.cfwaf-ip-btn-secondary{background:#fff;color:var(--ip-dark);border:1px solid var(--ip-border);}
.cfwaf-ip-btn-secondary:hover{background:var(--ip-bg);}
.cfwaf-ip-btn-danger{background:none;border:1px solid #fecaca;color:#e53e3e;padding:4px 10px;font-size:11px;border-radius:5px;}
.cfwaf-ip-btn-danger:hover{background:#fee2e2;}
.cfwaf-ip-btn-edit{background:none;border:1px solid var(--ip-border);color:var(--ip-dark);padding:4px 10px;font-size:11px;border-radius:5px;}
.cfwaf-ip-btn-edit:hover{background:var(--ip-bg);}
.cfwaf-ip-btn:disabled{opacity:.5;cursor:not-allowed;}

/* Table */
.cfwaf-ip-table-wrap{background:#fff;border:1px solid var(--ip-border);border-radius:8px;overflow:hidden;}
.cfwaf-ip-table{width:100%;border-collapse:collapse;font-size:13px;}
.cfwaf-ip-table th{background:var(--ip-bg);padding:10px 14px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--ip-muted);border-bottom:1px solid var(--ip-border);white-space:nowrap;}
.cfwaf-ip-table td{padding:10px 14px;border-bottom:1px solid #f1f3f5;vertical-align:middle;}
.cfwaf-ip-table tr:last-child td{border-bottom:none;}
.cfwaf-ip-table tr:hover td{background:#fafbfc;}

/* Mode badges */
.cfwaf-ip-mode{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:10px;font-size:11px;font-weight:700;}
.cfwaf-ip-mode.whitelist{background:#d1fae5;color:#065f46;}
.cfwaf-ip-mode.block{background:#fee2e2;color:#b91c1c;}
.cfwaf-ip-mode.challenge{background:#fef3c7;color:#92400e;}
.cfwaf-ip-mode.js_challenge{background:#e0f2fe;color:#0369a1;}

/* Target type badge */
.cfwaf-ip-target{font-size:10px;padding:2px 6px;border-radius:4px;font-weight:700;font-family:monospace;background:#f1f5f9;color:#475569;margin-right:4px;}

/* Value cell */
.cfwaf-ip-value{font-family:monospace;font-size:13px;font-weight:600;}

/* Note */
.cfwaf-ip-note{font-size:11px;color:var(--ip-muted);font-style:italic;}

/* States */
.cfwaf-ip-loading{text-align:center;padding:40px;color:var(--ip-muted);}
.cfwaf-ip-empty{text-align:center;padding:40px;color:var(--ip-muted);font-style:italic;}
.cfwaf-ip-spinner{display:inline-block;width:14px;height:14px;border:2px solid rgba(255,106,0,.3);border-top-color:var(--ip-orange);border-radius:50%;animation:ip-spin .6s linear infinite;vertical-align:middle;}
@keyframes ip-spin{to{transform:rotate(360deg);}}
.cfwaf-ip-error{background:#fee2e2;border:1px solid #fca5a5;border-radius:6px;padding:12px 16px;color:#b91c1c;font-size:13px;margin-bottom:16px;}

/* Modal */
.cfwaf-ip-modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:100000;display:flex;align-items:center;justify-content:center;padding:20px;}
.cfwaf-ip-modal{background:#fff;border-radius:10px;width:100%;max-width:500px;box-shadow:0 20px 60px rgba(0,0,0,.25);}
.cfwaf-ip-modal-header{display:flex;align-items:center;justify-content:space-between;padding:18px 20px;border-bottom:1px solid var(--ip-border);}
.cfwaf-ip-modal-header h3{margin:0;font-size:16px;font-weight:700;}
.cfwaf-ip-modal-close{background:none;border:none;font-size:20px;cursor:pointer;color:var(--ip-muted);line-height:1;padding:4px;}
.cfwaf-ip-modal-body{padding:20px;display:flex;flex-direction:column;gap:14px;}
.cfwaf-ip-modal-footer{padding:14px 20px;border-top:1px solid var(--ip-border);display:flex;gap:8px;justify-content:flex-end;}
.cfwaf-ip-field{display:flex;flex-direction:column;gap:4px;}
.cfwaf-ip-field label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--ip-muted);}
.cfwaf-ip-field input,.cfwaf-ip-field select,.cfwaf-ip-field textarea{padding:8px 10px;border:1px solid var(--ip-border);border-radius:6px;font-size:13px;color:var(--ip-dark);background:#fff;width:100%;box-sizing:border-box;}
.cfwaf-ip-field input:focus,.cfwaf-ip-field select:focus,.cfwaf-ip-field textarea:focus{outline:none;border-color:var(--ip-orange);box-shadow:0 0 0 2px rgba(255,106,0,.12);}
.cfwaf-ip-field-hint{font-size:11px;color:var(--ip-muted);margin-top:2px;}
.cfwaf-ip-field-row{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.cfwaf-ip-modal-error{background:#fee2e2;border:1px solid #fca5a5;border-radius:6px;padding:10px 14px;color:#b91c1c;font-size:13px;}

/* Mode selector */
.cfwaf-ip-mode-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px;}
.cfwaf-ip-mode-option{position:relative;}
.cfwaf-ip-mode-option input{position:absolute;opacity:0;width:0;height:0;}
.cfwaf-ip-mode-option label{display:flex;align-items:center;gap:8px;padding:10px 12px;border:2px solid var(--ip-border);border-radius:7px;cursor:pointer;transition:all .12s;font-size:13px;font-weight:600;}
.cfwaf-ip-mode-option input:checked+label{border-color:var(--ip-orange);background:#fff8f5;}
.cfwaf-ip-mode-option label:hover{border-color:#d1d5db;}
.cfwaf-ip-mode-dot{width:10px;height:10px;border-radius:50%;flex-shrink:0;}
.cfwaf-ip-mode-dot.whitelist{background:#059669;}
.cfwaf-ip-mode-dot.block{background:#dc2626;}
.cfwaf-ip-mode-dot.challenge{background:#d97706;}
.cfwaf-ip-mode-dot.js_challenge{background:#0284c7;}

/* Toast */
.cfwaf-ip-toast{position:fixed;bottom:24px;right:24px;padding:10px 16px;border-radius:6px;font-size:13px;font-weight:600;color:#fff;z-index:999999;}
.cfwaf-ip-toast.ok{background:#059669;}.cfwaf-ip-toast.err{background:#dc2626;}
/* Account selector bar */
.cfwaf-ip-account-bar{display:flex;align-items:center;gap:10px;background:#fff;border:1px solid var(--ip-border);border-radius:8px;padding:12px 16px;margin-bottom:16px;}
.cfwaf-ip-account-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--ip-muted);white-space:nowrap;}
.cfwaf-ip-account-select{flex:1;padding:7px 10px;border:1px solid var(--ip-border);border-radius:6px;font-size:13px;color:var(--ip-dark);background:#fff;cursor:pointer;max-width:480px;}
.cfwaf-ip-account-select:focus{outline:none;border-color:var(--ip-orange);box-shadow:0 0 0 2px rgba(255,106,0,.12);}
/* Permission & API error notices */
.cfwaf-ip-perm-notice{background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:14px 16px;font-size:13px;color:#1e40af;margin-bottom:16px;display:flex;gap:10px;align-items:flex-start;}
.cfwaf-ip-perm-notice ul{margin:8px 0 0;padding-left:18px;}
.cfwaf-ip-perm-notice li{margin-bottom:4px;font-size:12px;}
.cfwaf-ip-api-error{border-radius:8px;padding:14px 16px;font-size:13px;margin-bottom:16px;display:flex;gap:10px;align-items:flex-start;}
</style>

<div class="cfwaf-ip-wrap">
	<div class="cfwaf-ip-header">
		<div>
			<h1><span class="dashicons dashicons-shield-alt"></span> IP Access Rules</h1>
			<div class="cfwaf-ip-header-sub">Account-level rules — apply instantly across <strong>all zones</strong> in your Cloudflare account</div>
		</div>
		<button class="cfwaf-ip-btn cfwaf-ip-btn-primary" id="cfwaf-ip-add-btn" disabled>
			<span class="dashicons dashicons-plus-alt2" style="font-size:14px;width:14px;height:14px;"></span> Add Rule
		</button>
	</div>

	<?php if ( ! $has_creds ) : ?>
	<div class="cfwaf-ip-no-creds">
		<strong>No Cloudflare account connected.</strong> Please <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpwafmanager' ) ); ?>">add your credentials</a> on the WAF Rules page first.
	</div>
	<?php else : ?>

	<!-- Account selector -->
	<div class="cfwaf-ip-account-bar">
		<label class="cfwaf-ip-account-label" for="cfwaf-ip-account-select">Account</label>
		<select id="cfwaf-ip-account-select" class="cfwaf-ip-account-select">
			<?php foreach ( $accounts as $acc ) : ?>
			<option value="<?php echo esc_attr( $acc['id'] ); ?>" <?php selected( $acc['id'], $active_id ); ?>>
				<?php echo esc_html( $acc['label'] ?? 'Unnamed' ); ?> &mdash; <?php echo esc_html( $acc['auth_method'] === 'key' ? 'Email + Key' : 'API Token' ); ?>
			</option>
			<?php endforeach; ?>
		</select>
	</div>

	<!-- Permission notice (shown when API call fails) -->
	<div class="cfwaf-ip-notice cfwaf-ip-perm-notice" id="cfwaf-ip-perm-notice" style="display:none;">
		<span class="dashicons dashicons-lock" style="flex-shrink:0;font-size:20px;width:20px;height:20px;margin-top:1px;color:#1e40af;"></span>
		<div>
			<strong>Missing permission: Account → Firewall Services → Edit</strong><br>
			This account's API Token doesn't have access to account-level IP rules.
			<ul style="margin:8px 0 0;padding-left:18px;font-size:12px;">
				<li>Go to <a href="https://dash.cloudflare.com/profile/api-tokens" target="_blank" rel="noopener">Cloudflare → My Profile → API Tokens</a></li>
				<li>Edit your token and add <strong>Account → Firewall Services → Edit</strong></li>
				<li>Or use <strong>Email + Global API Key</strong> — it has full access automatically</li>
			</ul>
		</div>
	</div>
	<!-- Generic API error (non-permission) -->
	<div class="cfwaf-ip-notice cfwaf-ip-api-error" id="cfwaf-ip-api-error" style="display:none;background:#fff8f5;border-color:#fcd9c0;">
		<span class="dashicons dashicons-warning" style="flex-shrink:0;font-size:20px;width:20px;height:20px;margin-top:1px;color:#92400e;"></span>
		<div><strong>API Error:</strong> <span id="cfwaf-ip-api-error-msg"></span></div>
	</div>

	<div class="cfwaf-ip-notice cfwaf-ip-notice-warn">
		<span class="dashicons dashicons-warning" style="flex-shrink:0;margin-top:1px;"></span>
		<div><strong>Account-wide:</strong> These rules apply to <em>all zones</em> in your Cloudflare account — including zones not managed by this plugin. Changes take effect immediately.</div>
	</div>

	<div id="cfwaf-ip-error" class="cfwaf-ip-error" style="display:none;"></div>

	<div class="cfwaf-ip-toolbar">
		<input type="search" id="cfwaf-ip-search" class="cfwaf-ip-search" placeholder="Search IP, country, note…">
		<div class="cfwaf-ip-filter-tabs" id="cfwaf-ip-filter-tabs" role="tablist">
			<button class="cfwaf-ip-filter-tab active" data-mode="" role="tab">All</button>
			<button class="cfwaf-ip-filter-tab" data-mode="whitelist" role="tab">✅ Allow</button>
			<button class="cfwaf-ip-filter-tab" data-mode="block" role="tab">🚫 Block</button>
			<button class="cfwaf-ip-filter-tab" data-mode="challenge" role="tab">⚠️ Challenge</button>
			<button class="cfwaf-ip-filter-tab" data-mode="js_challenge" role="tab">🔒 JS Challenge</button>
		</div>
		<span class="cfwaf-ip-count" id="cfwaf-ip-count"></span>
	</div>

	<div class="cfwaf-ip-table-wrap">
		<table class="cfwaf-ip-table">
			<thead>
				<tr>
					<th>Type</th>
					<th>Value</th>
					<th>Mode</th>
					<th>Note</th>
					<th>Added</th>
					<th style="width:110px;">Actions</th>
				</tr>
			</thead>
			<tbody id="cfwaf-ip-tbody">
				<tr><td colspan="6" class="cfwaf-ip-loading"><span class="cfwaf-ip-spinner"></span> Loading…</td></tr>
			</tbody>
		</table>
	</div>

	<!-- Add / Edit Modal -->
	<div id="cfwaf-ip-modal" class="cfwaf-ip-modal-overlay" style="display:none;" role="dialog" aria-modal="true">
		<div class="cfwaf-ip-modal">
			<div class="cfwaf-ip-modal-header">
				<h3 id="cfwaf-ip-modal-title">Add IP Rule</h3>
				<button class="cfwaf-ip-modal-close" id="cfwaf-ip-modal-close">&times;</button>
			</div>
			<div class="cfwaf-ip-modal-body">
				<input type="hidden" id="cfwaf-ip-rule-id">

				<div class="cfwaf-ip-field-row">
					<div class="cfwaf-ip-field">
						<label for="cfwaf-ip-target">Target type</label>
						<select id="cfwaf-ip-target">
							<option value="ip">IP Address</option>
							<option value="ip_range">IP Range (CIDR)</option>
							<option value="country">Country Code</option>
							<option value="asn">ASN</option>
						</select>
					</div>
					<div class="cfwaf-ip-field">
						<label for="cfwaf-ip-value">Value</label>
						<input type="text" id="cfwaf-ip-value" placeholder="1.2.3.4" autocomplete="off" spellcheck="false">
						<span class="cfwaf-ip-field-hint" id="cfwaf-ip-value-hint">IPv4 or IPv6 address</span>
					</div>
				</div>

				<div class="cfwaf-ip-field">
					<label>Action</label>
					<div class="cfwaf-ip-mode-grid">
						<div class="cfwaf-ip-mode-option">
							<input type="radio" name="cfwaf-ip-mode" id="cfwaf-ip-mode-whitelist" value="whitelist" checked>
							<label for="cfwaf-ip-mode-whitelist"><span class="cfwaf-ip-mode-dot whitelist"></span> Allow (Whitelist)</label>
						</div>
						<div class="cfwaf-ip-mode-option">
							<input type="radio" name="cfwaf-ip-mode" id="cfwaf-ip-mode-block" value="block">
							<label for="cfwaf-ip-mode-block"><span class="cfwaf-ip-mode-dot block"></span> Block</label>
						</div>
						<div class="cfwaf-ip-mode-option">
							<input type="radio" name="cfwaf-ip-mode" id="cfwaf-ip-mode-challenge" value="challenge">
							<label for="cfwaf-ip-mode-challenge"><span class="cfwaf-ip-mode-dot challenge"></span> Managed Challenge</label>
						</div>
						<div class="cfwaf-ip-mode-option">
							<input type="radio" name="cfwaf-ip-mode" id="cfwaf-ip-mode-js" value="js_challenge">
							<label for="cfwaf-ip-mode-js"><span class="cfwaf-ip-mode-dot js_challenge"></span> JS Challenge</label>
						</div>
					</div>
				</div>

				<div class="cfwaf-ip-field">
					<label for="cfwaf-ip-note">Note (optional)</label>
					<input type="text" id="cfwaf-ip-note" placeholder="e.g. Office IP, Client server, Bad actor…" maxlength="200">
				</div>

				<div id="cfwaf-ip-modal-error" style="display:none;" class="cfwaf-ip-modal-error"></div>
			</div>
			<div class="cfwaf-ip-modal-footer">
				<button class="cfwaf-ip-btn cfwaf-ip-btn-secondary" id="cfwaf-ip-modal-cancel">Cancel</button>
				<button class="cfwaf-ip-btn cfwaf-ip-btn-primary" id="cfwaf-ip-modal-save">Add Rule</button>
			</div>
		</div>
	</div>

	<?php endif; ?>
</div>

<script>
'use strict';
(function(){
const NONCE      = <?php echo wp_json_encode( $nonce ); ?>;
const AJAX_URL   = <?php echo wp_json_encode( $ajax_url ); ?>;
const ACTIVE_ID  = <?php echo wp_json_encode( $active_id ); ?>;

let accountId       = '';   // Cloudflare account ID
let pluginAccountId = ACTIVE_ID; // plugin's internal account ID
let allRules        = [];
let activeMode      = '';
let editingId       = '';

function qs(s){ return document.querySelector(s); }
function qsa(s){ return Array.from(document.querySelectorAll(s)); }
function toast(msg, ok=true){
	const el = document.createElement('div');
	el.className = 'cfwaf-ip-toast ' + (ok?'ok':'err');
	el.textContent = msg;
	document.body.appendChild(el);
	setTimeout(()=>{ el.style.opacity='0'; el.style.transition='opacity .3s'; setTimeout(()=>el.remove(),300); },3000);
}
function ajax(action, data, cb){
	const fd = new FormData();
	fd.append('action',action); fd.append('nonce',NONCE);
	Object.entries(data).forEach(([k,v])=>fd.append(k,v));
	fetch(AJAX_URL,{method:'POST',body:fd}).then(r=>r.json()).then(cb)
		.catch(e=>cb({success:false,data:{message:e.message}}));
}
function showError(msg){
	const el = qs('#cfwaf-ip-error');
	if (!el) return;
	el.textContent = '⚠ ' + msg;
	el.style.display = '';
}

// ── Account selector ─────────────────────────────────────────────────────────
const accountSel = qs('#cfwaf-ip-account-select');
if (accountSel) {
	accountSel.addEventListener('change', function(){
		pluginAccountId = this.value;
		accountId = ''; // reset Cloudflare account ID — will re-fetch
		qs('#cfwaf-ip-add-btn').disabled = true;
		hideNotices();
		bootForAccount();
	});
}

function hideNotices() {
	['cfwaf-ip-perm-notice','cfwaf-ip-api-error','cfwaf-ip-error'].forEach(id => {
		const el = qs('#'+id); if (el) el.style.display='none';
	});
}

function showPermError() {
	qs('#cfwaf-ip-perm-notice').style.display = '';
	qs('#cfwaf-ip-tbody').innerHTML = '<tr><td colspan="6" class="cfwaf-ip-empty">Fix the permission issue above, then reload the page.</td></tr>';
}

function showApiError(msg) {
	const el = qs('#cfwaf-ip-api-error');
	if (el) { el.style.display=''; qs('#cfwaf-ip-api-error-msg').textContent = msg; }
	qs('#cfwaf-ip-tbody').innerHTML = '<tr><td colspan="6" class="cfwaf-ip-empty">Could not load rules.</td></tr>';
}

// ── Boot: get Cloudflare account ID then load rules ───────────────────────────
function bootForAccount() {
	qs('#cfwaf-ip-tbody').innerHTML = '<tr><td colspan="6" class="cfwaf-ip-loading"><span class="cfwaf-ip-spinner"></span> Connecting…</td></tr>';
	const payload = {};
	if (pluginAccountId) payload.plugin_account_id = pluginAccountId;
	ajax('wpwaf_get_account_id', payload, (res) => {
		if (!res.success) {
			// Distinguish permission errors from other failures
			const msg = res.data?.message || '';
			const isPermErr = /403|10000|9109|auth|permission|Forbidden|not authorized|access/i.test(msg)
				|| res.data?.code === 'no_account_id';
			if (isPermErr) showPermError();
			else showApiError(msg || 'Could not get account ID.');
			return;
		}
		accountId = res.data.account_id;
		qs('#cfwaf-ip-add-btn').disabled = false;
		loadRules();
	});
}
bootForAccount();

// ── Load rules ────────────────────────────────────────────────────────────────
function loadRules(){
	qs('#cfwaf-ip-tbody').innerHTML = '<tr><td colspan="6" class="cfwaf-ip-loading"><span class="cfwaf-ip-spinner"></span> Loading…</td></tr>';
	ajax('wpwaf_ip_rules_list', {account_id: accountId, plugin_account_id: pluginAccountId}, (res) => {
		if (!res.success){ showError(res.data?.message||'Failed to load rules'); return; }
		allRules = res.data.rules || [];
		renderTable();
	});
}

// ── Filter tabs ───────────────────────────────────────────────────────────────
qsa('.cfwaf-ip-filter-tab').forEach(tab => {
	tab.addEventListener('click', function(){
		qsa('.cfwaf-ip-filter-tab').forEach(t => t.classList.remove('active'));
		this.classList.add('active');
		activeMode = this.dataset.mode;
		renderTable();
	});
});

qs('#cfwaf-ip-search')?.addEventListener('input', renderTable);

// ── Render ────────────────────────────────────────────────────────────────────
const MODE_LABELS = {
	whitelist:    { label:'Allow',        cls:'whitelist',   icon:'✅' },
	block:        { label:'Block',         cls:'block',       icon:'🚫' },
	challenge:    { label:'Challenge',     cls:'challenge',   icon:'⚠️' },
	js_challenge: { label:'JS Challenge',  cls:'js_challenge',icon:'🔒' },
};
const TARGET_LABELS = { ip:'IP', ip_range:'CIDR', country:'Country', asn:'ASN' };

function fmtDate(ts){
	if (!ts) return '—';
	return new Date(ts).toLocaleDateString(undefined, {month:'short',day:'numeric',year:'numeric'});
}

function renderTable(){
	const q    = (qs('#cfwaf-ip-search')?.value || '').toLowerCase();
	const rows = allRules.filter(r => {
		if (activeMode && r.mode !== activeMode) return false;
		if (q){
			const hay = ((r.configuration?.value||'') + ' ' + (r.notes||'') + ' ' + (r.configuration?.target||'')).toLowerCase();
			if (!hay.includes(q)) return false;
		}
		return true;
	});

	qs('#cfwaf-ip-count').textContent = rows.length + ' / ' + allRules.length + ' rules';

	if (!rows.length){
		qs('#cfwaf-ip-tbody').innerHTML = '<tr><td colspan="6" class="cfwaf-ip-empty">' +
			(allRules.length ? 'No rules match your filter.' : 'No IP rules yet — click Add Rule to get started.') +
			'</td></tr>';
		return;
	}

	qs('#cfwaf-ip-tbody').innerHTML = rows.map(r => {
		const m      = MODE_LABELS[r.mode] || { label: r.mode, cls: '', icon: '' };
		const target = r.configuration?.target || 'ip';
		const value  = r.configuration?.value  || '';
		const note   = r.notes || '';
		const added  = fmtDate(r.created_on);
		return `<tr>
			<td><span class="cfwaf-ip-target">${TARGET_LABELS[target]||target}</span></td>
			<td><span class="cfwaf-ip-value">${escHtml(value)}</span></td>
			<td><span class="cfwaf-ip-mode ${m.cls}">${m.icon} ${m.label}</span></td>
			<td class="cfwaf-ip-note">${escHtml(note)}</td>
			<td style="font-size:11px;color:var(--ip-muted);white-space:nowrap;">${added}</td>
			<td>
				<div style="display:flex;gap:4px;">
					<button class="cfwaf-ip-btn cfwaf-ip-btn-edit cfwaf-ip-edit-btn" data-id="${r.id}">Edit</button>
					<button class="cfwaf-ip-btn cfwaf-ip-btn-danger cfwaf-ip-del-btn" data-id="${r.id}" data-value="${escHtml(value)}">✕</button>
				</div>
			</td>
		</tr>`;
	}).join('');

	qsa('.cfwaf-ip-edit-btn').forEach(btn => {
		btn.addEventListener('click', () => {
			const rule = allRules.find(r => r.id === btn.dataset.id);
			if (rule) openModal(rule);
		});
	});
	qsa('.cfwaf-ip-del-btn').forEach(btn => {
		btn.addEventListener('click', () => deleteRule(btn.dataset.id, btn.dataset.value));
	});
}

function escHtml(str){ return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

// ── Modal ──────────────────────────────────────────────────────────────────────
const VALUE_HINTS = {
	ip:       'IPv4 (1.2.3.4) or IPv6 (2001:db8::1)',
	ip_range: 'CIDR notation: 1.2.3.0/24 or 2001:db8::/32',
	country:  'ISO 3166-1 two-letter code: US, GB, DE…',
	asn:      'Autonomous System Number: AS13335',
};

function openModal(rule=null){
	editingId = rule ? rule.id : '';
	qs('#cfwaf-ip-modal-title').textContent = rule ? 'Edit IP Rule' : 'Add IP Rule';
	qs('#cfwaf-ip-modal-save').textContent  = rule ? 'Save Changes' : 'Add Rule';
	qs('#cfwaf-ip-modal-error').style.display = 'none';
	qs('#cfwaf-ip-rule-id').value = rule?.id || '';
	qs('#cfwaf-ip-target').value  = rule?.configuration?.target || 'ip';
	qs('#cfwaf-ip-value').value   = rule?.configuration?.value  || '';
	qs('#cfwaf-ip-note').value    = rule?.notes || '';

	// Set mode radio
	const mode = rule?.mode || 'whitelist';
	const radio = qs(`input[name="cfwaf-ip-mode"][value="${mode}"]`);
	if (radio) radio.checked = true;

	// Disable target/value when editing (Cloudflare doesn't allow changing them)
	qs('#cfwaf-ip-target').disabled = !!rule;
	qs('#cfwaf-ip-value').disabled  = !!rule;
	if (rule) {
		qs('#cfwaf-ip-value').style.background = '#f9fafb';
	} else {
		qs('#cfwaf-ip-value').style.background = '';
		updateValueHint();
	}

	qs('#cfwaf-ip-modal').style.display = 'flex';
	if (!rule) qs('#cfwaf-ip-value').focus();
}

function closeModal(){
	qs('#cfwaf-ip-modal').style.display = 'none';
	editingId = '';
	qs('#cfwaf-ip-target').disabled = false;
	qs('#cfwaf-ip-value').disabled  = false;
}

function updateValueHint(){
	const target = qs('#cfwaf-ip-target').value;
	qs('#cfwaf-ip-value-hint').textContent = VALUE_HINTS[target] || '';
	qs('#cfwaf-ip-value').placeholder = {
		ip:'1.2.3.4', ip_range:'192.168.0.0/24', country:'US', asn:'AS13335'
	}[target] || '';
}

qs('#cfwaf-ip-target')?.addEventListener('change', updateValueHint);
qs('#cfwaf-ip-add-btn')?.addEventListener('click', () => openModal());
qs('#cfwaf-ip-modal-close')?.addEventListener('click', closeModal);
qs('#cfwaf-ip-modal-cancel')?.addEventListener('click', closeModal);
qs('#cfwaf-ip-modal')?.addEventListener('click', e => { if (e.target === qs('#cfwaf-ip-modal')) closeModal(); });

// ── Save ──────────────────────────────────────────────────────────────────────
qs('#cfwaf-ip-modal-save')?.addEventListener('click', () => {
	const mode   = document.querySelector('input[name="cfwaf-ip-mode"]:checked')?.value || 'whitelist';
	const target = qs('#cfwaf-ip-target').value;
	const value  = qs('#cfwaf-ip-value').value.trim();
	const note   = qs('#cfwaf-ip-note').value.trim();
	const errEl  = qs('#cfwaf-ip-modal-error');
	const saveBtn = qs('#cfwaf-ip-modal-save');

	if (!value){ errEl.textContent='Value is required.'; errEl.style.display=''; return; }
	errEl.style.display = 'none';
	saveBtn.disabled = true;
	saveBtn.textContent = 'Saving…';

	if (editingId) {
		ajax('wpwaf_ip_rules_update', {account_id:accountId, plugin_account_id:pluginAccountId, rule_id:editingId, mode, note}, (res) => {
			saveBtn.disabled=false; saveBtn.textContent='Save Changes';
			if (!res.success){ errEl.textContent='⚠ '+(res.data?.message||'Save failed'); errEl.style.display=''; return; }
			closeModal(); loadRules(); toast('✓ Rule updated');
		});
	} else {
		ajax('wpwaf_ip_rules_create', {account_id:accountId, plugin_account_id:pluginAccountId, mode, target, value, note}, (res) => {
			saveBtn.disabled=false; saveBtn.textContent='Add Rule';
			if (!res.success){ errEl.textContent='⚠ '+(res.data?.message||'Create failed'); errEl.style.display=''; return; }
			closeModal(); loadRules(); toast('✓ Rule created');
		});
	}
});

// ── Delete ─────────────────────────────────────────────────────────────────────
function deleteRule(id, value){
	if (!confirm(`Delete rule for "${value}"? This applies to all zones immediately.`)) return;
	ajax('wpwaf_ip_rules_delete', {account_id:accountId, plugin_account_id:pluginAccountId, rule_id:id}, (res) => {
		if (res.success){ loadRules(); toast('✓ Rule deleted'); }
		else toast('Failed: '+(res.data?.message||'error'), false);
	});
}

document.addEventListener('keydown', e => {
	if (e.key === 'Escape' && qs('#cfwaf-ip-modal').style.display !== 'none') closeModal();
});
})();
</script>
