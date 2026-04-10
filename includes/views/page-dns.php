<?php defined( 'ABSPATH' ) || exit; ?>
<?php
$record_types = WPWAF_DNS::RECORD_TYPES;
$ttl_options  = WPWAF_DNS::TTL_OPTIONS;
$proxyable    = WPWAF_DNS::PROXYABLE;
$nonce        = wp_create_nonce( 'wpwaf_nonce' );
$ajax_url     = admin_url( 'admin-ajax.php' );
?>
<style>
:root { --dns-orange:#FF6A00; --dns-border:#e2e6ea; --dns-bg:#f8f9fb; --dns-dark:#1a1a2e; --dns-muted:#6b7280; }
.wrap { margin:0; }
.cfwaf-dns-wrap { font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif; max-width:1200px; padding:24px 20px; color:var(--dns-dark); }
.cfwaf-dns-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:24px; flex-wrap:wrap; gap:12px; }
.cfwaf-dns-header h1 { font-size:22px; font-weight:700; margin:0; display:flex; align-items:center; gap:8px; }
.cfwaf-dns-header h1 .dashicons { color:var(--dns-orange); font-size:24px; width:24px; height:24px; }
.cfwaf-dns-no-creds { background:#fff8f5; border:1px solid #fcd9c0; border-radius:8px; padding:20px 24px; color:#92400e; }

/* Zone picker */
.cfwaf-dns-controls { display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:20px; }
.cfwaf-dns-select { padding:8px 12px; border:1px solid var(--dns-border); border-radius:6px; font-size:13px; color:var(--dns-dark); background:#fff; min-width:260px; cursor:pointer; }
.cfwaf-dns-select:focus { outline:none; border-color:var(--dns-orange); box-shadow:0 0 0 2px rgba(255,106,0,.12); }
.cfwaf-dns-btn { display:inline-flex; align-items:center; gap:6px; padding:8px 14px; border-radius:6px; font-size:13px; font-weight:600; cursor:pointer; border:none; transition:all .15s; white-space:nowrap; }
.cfwaf-dns-btn-primary { background:var(--dns-orange); color:#fff; }
.cfwaf-dns-btn-primary:hover { background:#d95500; }
.cfwaf-dns-btn-secondary { background:#fff; color:var(--dns-dark); border:1px solid var(--dns-border); }
.cfwaf-dns-btn-secondary:hover { background:var(--dns-bg); }
.cfwaf-dns-btn-danger { background:#fff; color:#e53e3e; border:1px solid #fecaca; }
.cfwaf-dns-btn-danger:hover { background:#fee2e2; }
.cfwaf-dns-btn-sm { padding:4px 10px; font-size:11px; }
.cfwaf-dns-btn:disabled { opacity:.5; cursor:not-allowed; }

/* Search + add bar */
.cfwaf-dns-toolbar { display:flex; align-items:center; gap:10px; margin-bottom:14px; flex-wrap:wrap; }
.cfwaf-dns-search { padding:7px 12px; border:1px solid var(--dns-border); border-radius:6px; font-size:13px; width:260px; }
.cfwaf-dns-search:focus { outline:none; border-color:var(--dns-orange); }
.cfwaf-dns-filter { padding:7px 10px; border:1px solid var(--dns-border); border-radius:6px; font-size:12px; color:var(--dns-dark); background:#fff; cursor:pointer; }
.cfwaf-dns-count { margin-left:auto; font-size:12px; color:var(--dns-muted); }

/* Table */
.cfwaf-dns-table-wrap { background:#fff; border:1px solid var(--dns-border); border-radius:8px; overflow:hidden; }
.cfwaf-dns-table { width:100%; border-collapse:collapse; font-size:13px; }
.cfwaf-dns-table th { background:var(--dns-bg); padding:10px 12px; text-align:left; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:var(--dns-muted); border-bottom:1px solid var(--dns-border); white-space:nowrap; }
.cfwaf-dns-table td { padding:10px 12px; border-bottom:1px solid #f1f3f5; vertical-align:middle; }
.cfwaf-dns-table tr:last-child td { border-bottom:none; }
.cfwaf-dns-table tr:hover td { background:#fafbfc; }
.cfwaf-dns-table td.wrap-cell { max-width:260px; word-break:break-all; font-family:monospace; font-size:12px; }
.cfwaf-type-badge { display:inline-block; padding:2px 8px; border-radius:4px; font-size:11px; font-weight:700; background:#f1f5f9; color:#334155; font-family:monospace; }
.cfwaf-type-badge.A    { background:#dbeafe; color:#1e40af; }
.cfwaf-type-badge.AAAA { background:#ede9fe; color:#5b21b6; }
.cfwaf-type-badge.CNAME{ background:#d1fae5; color:#065f46; }
.cfwaf-type-badge.MX   { background:#fef3c7; color:#92400e; }
.cfwaf-type-badge.TXT  { background:#e0f2fe; color:#0369a1; }
.cfwaf-type-badge.NS   { background:#f0fdf4; color:#14532d; }
.cfwaf-type-badge.SRV  { background:#fdf4ff; color:#6b21a8; }

/* Proxy toggle */
.cfwaf-proxy-toggle { display:inline-flex; align-items:center; gap:5px; cursor:pointer; background:none; border:none; padding:0; font-size:12px; }
.cfwaf-proxy-icon { font-size:18px; transition:opacity .15s; }
.cfwaf-proxy-icon.proxied   { opacity:1; filter:none; }
.cfwaf-proxy-icon.unproxied { opacity:.4; filter:grayscale(1); }
.cfwaf-proxy-label { color:var(--dns-muted); font-size:11px; }

/* TTL */
.cfwaf-ttl { font-size:12px; color:var(--dns-muted); }

/* Actions */
.cfwaf-dns-actions { display:flex; gap:4px; }

/* Modal */
.cfwaf-dns-modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:100000; display:flex; align-items:center; justify-content:center; padding:20px; }
.cfwaf-dns-modal { background:#fff; border-radius:10px; width:100%; max-width:580px; max-height:90vh; overflow-y:auto; box-shadow:0 20px 60px rgba(0,0,0,.25); }
.cfwaf-dns-modal-header { display:flex; align-items:center; justify-content:space-between; padding:18px 20px; border-bottom:1px solid var(--dns-border); position:sticky; top:0; background:#fff; z-index:1; }
.cfwaf-dns-modal-header h3 { margin:0; font-size:16px; font-weight:700; }
.cfwaf-dns-modal-close { background:none; border:none; font-size:20px; cursor:pointer; color:var(--dns-muted); line-height:1; padding:0; }
.cfwaf-dns-modal-body { padding:20px; display:flex; flex-direction:column; gap:14px; }
.cfwaf-dns-modal-footer { padding:14px 20px; border-top:1px solid var(--dns-border); display:flex; gap:8px; justify-content:flex-end; }
.cfwaf-dns-field { display:flex; flex-direction:column; gap:4px; }
.cfwaf-dns-field label { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:var(--dns-muted); }
.cfwaf-dns-field input, .cfwaf-dns-field select, .cfwaf-dns-field textarea { padding:8px 10px; border:1px solid var(--dns-border); border-radius:6px; font-size:13px; color:var(--dns-dark); background:#fff; width:100%; box-sizing:border-box; }
.cfwaf-dns-field input:focus, .cfwaf-dns-field select:focus, .cfwaf-dns-field textarea:focus { outline:none; border-color:var(--dns-orange); box-shadow:0 0 0 2px rgba(255,106,0,.12); }
.cfwaf-dns-field textarea { font-family:monospace; resize:vertical; min-height:80px; }
.cfwaf-dns-field-row { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.cfwaf-dns-field-row-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px; }
.cfwaf-proxy-field { display:flex; align-items:center; gap:10px; padding:10px; background:var(--dns-bg); border-radius:6px; }
.cfwaf-proxy-field label { font-size:13px; font-weight:600; margin:0; cursor:pointer; }
.cfwaf-proxy-field input[type=checkbox] { width:16px; height:16px; accent-color:var(--dns-orange); cursor:pointer; }
.cfwaf-proxy-hint { font-size:11px; color:var(--dns-muted); margin-top:2px; }
.cfwaf-dns-hint { font-size:11px; color:var(--dns-muted); margin-top:2px; }

/* SRV extra fields */
.cfwaf-dns-srv-fields, .cfwaf-dns-caa-fields { display:flex; flex-direction:column; gap:12px; padding:12px; background:var(--dns-bg); border-radius:6px; border:1px solid var(--dns-border); }
.cfwaf-dns-srv-fields legend, .cfwaf-dns-caa-fields legend { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:var(--dns-muted); margin-bottom:8px; display:block; }

/* States */
.cfwaf-dns-loading { text-align:center; padding:40px; color:var(--dns-muted); }
.cfwaf-dns-empty { text-align:center; padding:40px; color:var(--dns-muted); font-style:italic; }
.cfwaf-dns-error { background:#fee2e2; border:1px solid #fca5a5; border-radius:6px; padding:12px 16px; color:#b91c1c; font-size:13px; margin-bottom:16px; }
.cfwaf-dns-spinner { display:inline-block; width:14px; height:14px; border:2px solid rgba(255,106,0,.3); border-top-color:var(--dns-orange); border-radius:50%; animation:cfwaf-spin .6s linear infinite; }
.cfwaf-dns-modal-drag-handle { display:none; width:36px; height:4px; background:#d1d5db; border-radius:2px; margin:10px auto 0; }
@keyframes cfwaf-spin { to { transform:rotate(360deg); } }

/* Toast */
.cfwaf-dns-toast { position:fixed; bottom:24px; right:24px; padding:10px 16px; border-radius:6px; font-size:13px; font-weight:600; color:#fff; z-index:999999; animation:cfwaf-fadein .2s ease; pointer-events:none; }
.cfwaf-dns-toast.ok  { background:#059669; }
.cfwaf-dns-toast.err { background:#dc2626; }
@keyframes cfwaf-fadein { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:translateY(0); } }
</style>

<div class="cfwaf-dns-wrap">
	<div class="cfwaf-dns-header">
		<h1><span class="dashicons dashicons-admin-site-alt3"></span> DNS Manager</h1>
		<div style="font-size:12px;color:var(--dns-muted);">Manage DNS records across your Cloudflare zones</div>
	</div>

	<?php if ( ! $has_creds ) : ?>
	<div class="cfwaf-dns-no-creds">
		<strong>No Cloudflare account connected.</strong> Please <a href="<?php echo admin_url('admin.php?page=wpwafmanager'); ?>">add your credentials</a> on the WAF Rules page first.
	</div>
	<?php else : ?>

	<div class="cfwaf-dns-controls">
		<select id="cfwaf-dns-zone-select" class="cfwaf-dns-select">
			<option value="">— Select a zone —</option>
		</select>
		<button class="cfwaf-dns-btn cfwaf-dns-btn-secondary" id="cfwaf-dns-refresh">
			<span class="dashicons dashicons-update" style="font-size:14px;width:14px;height:14px;"></span> Refresh
		</button>
		<button class="cfwaf-dns-btn cfwaf-dns-btn-primary" id="cfwaf-dns-add-btn" disabled>
			<span class="dashicons dashicons-plus-alt2" style="font-size:14px;width:14px;height:14px;"></span> Add Record
		</button>
	</div>

	<div id="cfwaf-dns-error" class="cfwaf-dns-error" style="display:none;"></div>

	<div id="cfwaf-dns-records-panel" style="display:none;">
		<div class="cfwaf-dns-toolbar">
			<input type="search" id="cfwaf-dns-search" class="cfwaf-dns-search" placeholder="Search name, content, type…">
			<select id="cfwaf-dns-type-filter" class="cfwaf-dns-filter">
				<option value="">All types</option>
				<?php foreach ( $record_types as $t ) echo "<option value='{$t}'>{$t}</option>"; ?>
			</select>
			<select id="cfwaf-dns-proxy-filter" class="cfwaf-dns-filter">
				<option value="">All proxy states</option>
				<option value="1">☁ Proxied</option>
				<option value="0">⊘ DNS only</option>
			</select>
			<span class="cfwaf-dns-count" id="cfwaf-dns-count"></span>
		</div>
		<div class="cfwaf-dns-table-wrap">
			<table class="cfwaf-dns-table">
				<thead>
					<tr>
						<th>Type</th>
						<th>Name</th>
						<th>Content</th>
						<th>Proxy</th>
						<th>TTL</th>
						<th>Comment</th>
						<th style="width:100px;">Actions</th>
					</tr>
				</thead>
				<tbody id="cfwaf-dns-tbody">
					<tr><td colspan="7" class="cfwaf-dns-loading"><span class="cfwaf-dns-spinner"></span> Loading…</td></tr>
				</tbody>
			</table>
		</div>
	</div>

	<!-- Add / Edit Modal -->
	<div id="cfwaf-dns-modal" class="cfwaf-dns-modal-overlay" style="display:none;" role="dialog" aria-modal="true">
		<div class="cfwaf-dns-modal">
			<div class="cfwaf-dns-modal-drag-handle"></div>
			<div class="cfwaf-dns-modal-header">
				<h3 id="cfwaf-dns-modal-title">Add DNS Record</h3>
				<button class="cfwaf-dns-modal-close" id="cfwaf-dns-modal-close" aria-label="Close">&times;</button>
			</div>
			<div class="cfwaf-dns-modal-body">
				<input type="hidden" id="cfwaf-dns-record-id">

				<div class="cfwaf-dns-field-row">
					<div class="cfwaf-dns-field">
						<label for="cfwaf-dns-type">Type</label>
						<select id="cfwaf-dns-type">
							<?php foreach ( $record_types as $t ) echo "<option value='{$t}'>{$t}</option>"; ?>
						</select>
					</div>
					<div class="cfwaf-dns-field">
						<label for="cfwaf-dns-name">Name</label>
						<input type="text" id="cfwaf-dns-name" placeholder="@ or subdomain">
					</div>
				</div>

				<!-- Standard content field (hidden for SRV/CAA) -->
				<div class="cfwaf-dns-field" id="cfwaf-dns-content-field">
					<label for="cfwaf-dns-record-content">Content</label>
					<input type="text" id="cfwaf-dns-record-content" placeholder="IP address or value">
					<span class="cfwaf-dns-hint" id="cfwaf-dns-content-hint"></span>
				</div>

				<!-- MX / URI priority -->
				<div class="cfwaf-dns-field" id="cfwaf-dns-priority-field" style="display:none;">
					<label for="cfwaf-dns-priority">Priority</label>
					<input type="number" id="cfwaf-dns-priority" min="0" max="65535" value="10">
				</div>

				<!-- SRV fields -->
				<div id="cfwaf-dns-srv-wrap" style="display:none;">
					<div class="cfwaf-dns-srv-fields">
						<legend>SRV Record Data</legend>
						<div class="cfwaf-dns-field-row">
							<div class="cfwaf-dns-field"><label>Service</label><input type="text" id="cfwaf-srv-service" placeholder="_sip"></div>
							<div class="cfwaf-dns-field"><label>Protocol</label>
								<select id="cfwaf-srv-proto"><option value="_tcp">TCP</option><option value="_udp">UDP</option><option value="_tls">TLS</option></select>
							</div>
						</div>
						<div class="cfwaf-dns-field-row-3">
							<div class="cfwaf-dns-field"><label>Priority</label><input type="number" id="cfwaf-srv-priority" value="10" min="0"></div>
							<div class="cfwaf-dns-field"><label>Weight</label><input type="number" id="cfwaf-srv-weight" value="0" min="0"></div>
							<div class="cfwaf-dns-field"><label>Port</label><input type="number" id="cfwaf-srv-port" value="0" min="0" max="65535"></div>
						</div>
						<div class="cfwaf-dns-field"><label>Target</label><input type="text" id="cfwaf-srv-target" placeholder="sip.example.com"></div>
					</div>
				</div>

				<!-- CAA fields -->
				<div id="cfwaf-dns-caa-wrap" style="display:none;">
					<div class="cfwaf-dns-caa-fields">
						<legend>CAA Record Data</legend>
						<div class="cfwaf-dns-field-row">
							<div class="cfwaf-dns-field"><label>Flags</label><input type="number" id="cfwaf-caa-flags" value="0" min="0" max="255"></div>
							<div class="cfwaf-dns-field"><label>Tag</label>
								<select id="cfwaf-caa-tag"><option value="issue">issue</option><option value="issuewild">issuewild</option><option value="iodef">iodef</option></select>
							</div>
						</div>
						<div class="cfwaf-dns-field"><label>Value (CA domain)</label><input type="text" id="cfwaf-caa-value" placeholder="letsencrypt.org"></div>
					</div>
				</div>

				<!-- Proxy toggle -->
				<div class="cfwaf-proxy-field" id="cfwaf-dns-proxy-wrap">
					<input type="checkbox" id="cfwaf-dns-proxied">
					<label for="cfwaf-dns-proxied">☁ Proxy through Cloudflare (orange cloud)</label>
					<span class="cfwaf-proxy-hint">Enables CDN, DDoS protection, and hides origin IP</span>
				</div>

				<div class="cfwaf-dns-field-row">
					<div class="cfwaf-dns-field">
						<label for="cfwaf-dns-ttl">TTL</label>
						<select id="cfwaf-dns-ttl">
							<?php foreach ( $ttl_options as $v => $label ) echo "<option value='{$v}'>{$label}</option>"; ?>
						</select>
						<span class="cfwaf-dns-hint">Auto when proxy is enabled</span>
					</div>
					<div class="cfwaf-dns-field">
						<label for="cfwaf-dns-comment">Comment (optional)</label>
						<input type="text" id="cfwaf-dns-comment" placeholder="e.g. Mail server">
					</div>
				</div>

				<div id="cfwaf-dns-modal-error" class="cfwaf-dns-error" style="display:none;"></div>
			</div>
			<div class="cfwaf-dns-modal-footer">
				<button class="cfwaf-dns-btn cfwaf-dns-btn-secondary" id="cfwaf-dns-modal-cancel">Cancel</button>
				<button class="cfwaf-dns-btn cfwaf-dns-btn-primary" id="cfwaf-dns-modal-save">Save Record</button>
			</div>
		</div>
	</div>

	<!-- Delete confirm modal -->
	<div id="cfwaf-dns-delete-modal" class="cfwaf-dns-modal-overlay" style="display:none;">
		<div class="cfwaf-dns-modal" style="max-width:400px;">
			<div class="cfwaf-dns-modal-header"><h3>Delete DNS Record</h3><button class="cfwaf-dns-modal-close" id="cfwaf-dns-delete-cancel">&times;</button></div>
			<div class="cfwaf-dns-modal-body">
				<p style="margin:0;">Are you sure you want to delete <strong id="cfwaf-dns-delete-name"></strong>? This cannot be undone.</p>
			</div>
			<div class="cfwaf-dns-modal-footer">
				<button class="cfwaf-dns-btn cfwaf-dns-btn-secondary" id="cfwaf-dns-delete-cancel2">Cancel</button>
				<button class="cfwaf-dns-btn cfwaf-dns-btn-danger" id="cfwaf-dns-delete-confirm">Delete</button>
			</div>
		</div>
	</div>

	<?php endif; ?>
</div>

<script>
'use strict';
(function(){
const NONCE    = <?php echo wp_json_encode( $nonce ); ?>;
const AJAX_URL = <?php echo wp_json_encode( $ajax_url ); ?>;
const PROXYABLE= <?php echo wp_json_encode( $proxyable ); ?>;
const TTL_MAP  = <?php echo wp_json_encode( $ttl_options ); ?>;

let currentZone = '';
let allRecords  = [];
let editingId   = '';
let deletingId  = '';

function qs(sel){ return document.querySelector(sel); }
function qsa(sel){ return Array.from(document.querySelectorAll(sel)); }
function toast(msg, ok=true){
	const el = document.createElement('div');
	el.className = 'cfwaf-dns-toast ' + (ok?'ok':'err');
	el.textContent = msg;
	document.body.appendChild(el);
	setTimeout(()=>{ el.style.opacity='0'; el.style.transition='opacity .3s'; setTimeout(()=>el.remove(),300); }, 3000);
}
function ajax(action, data, cb){
	const fd = new FormData();
	fd.append('action', action);
	fd.append('nonce', NONCE);
	Object.entries(data).forEach(([k,v])=>fd.append(k,v));
	fetch(AJAX_URL,{method:'POST',body:fd})
		.then(r=>r.json()).then(cb)
		.catch(e=>cb({success:false,data:{message:e.message}}));
}

// ── Zone loading ─────────────────────────────────────────────────────────────
ajax('wpwaf_list_zones',{},(res)=>{
	const sel = qs('#cfwaf-dns-zone-select');
	if(!res.success){ sel.innerHTML='<option>Error loading zones</option>'; return; }
	(res.data.zones||[]).forEach(z=>{
		const o = document.createElement('option');
		o.value = z.id; o.textContent = z.name + ' [' + z.plan + ']';
		sel.appendChild(o);
	});
});

qs('#cfwaf-dns-zone-select').addEventListener('change', function(){
	currentZone = this.value;
	qs('#cfwaf-dns-add-btn').disabled = !currentZone;
	if(currentZone){ loadRecords(); qs('#cfwaf-dns-records-panel').style.display=''; }
	else { qs('#cfwaf-dns-records-panel').style.display='none'; }
});

qs('#cfwaf-dns-refresh')?.addEventListener('click',()=>{ if(currentZone) loadRecords(); });

function loadRecords(){
	const content = qs('#cfwaf-dns-records-panel');
	const tbody   = qs('#cfwaf-dns-tbody');
	content.style.display = '';
	tbody.innerHTML = '<tr><td colspan="7" class="cfwaf-dns-loading"><span class="cfwaf-dns-spinner"></span> Loading records…</td></tr>';
	qs('#cfwaf-dns-error').style.display = 'none';
	ajax('wpwaf_dns_list',{zone_id:currentZone},(res)=>{
		if(!res.success){
			showError(res.data?.message||'Failed to load records');
			tbody.innerHTML='<tr><td colspan="7" class="cfwaf-dns-empty">Failed to load records.</td></tr>';
			return;
		}
		allRecords = res.data.records||[];
		renderTable();
	});
}

function showError(msg){
	const el = qs('#cfwaf-dns-error');
	el.textContent = '⚠ ' + msg;
	el.style.display = '';
}

// ── Filtering ────────────────────────────────────────────────────────────────
function getFiltered(){
	const q     = (qs('#cfwaf-dns-search')?.value||'').toLowerCase();
	const type  = qs('#cfwaf-dns-type-filter')?.value||'';
	const proxy = qs('#cfwaf-dns-proxy-filter')?.value;
	return allRecords.filter(r=>{
		if(type && r.type !== type) return false;
		if(proxy === '1' && !r.proxied) return false;
		if(proxy === '0' && r.proxied) return false;
		if(q){
			const hay = (r.name+' '+r.content+' '+r.type+' '+(r.comment||'')).toLowerCase();
			if(!hay.includes(q)) return false;
		}
		return true;
	});
}

['cfwaf-dns-search','cfwaf-dns-type-filter','cfwaf-dns-proxy-filter'].forEach(id=>{
	qs('#'+id)?.addEventListener('input', renderTable);
	qs('#'+id)?.addEventListener('change', renderTable);
});

// ── Render table ─────────────────────────────────────────────────────────────
function fmtTTL(ttl){ return TTL_MAP[ttl] || (ttl+'s'); }
function fmtContent(r){
	if(r.type==='SRV' && r.data) return `${r.data.service||''} ${r.data.proto||''} ${r.data.priority||0} ${r.data.weight||0} ${r.data.port||0} ${r.data.target||''}`;
	if(r.type==='CAA' && r.data) return `${r.data.flags||0} ${r.data.tag||''} "${r.data.value||''}"`;
	if(r.type==='MX') return `${r.priority||''} ${r.content}`;
	return r.content||'';
}

function renderTable(){
	const rows = getFiltered();
	const tbody = qs('#cfwaf-dns-tbody');
	qs('#cfwaf-dns-count').textContent = rows.length + ' / ' + allRecords.length + ' records';
	if(!rows.length){ tbody.innerHTML='<tr><td colspan="7" class="cfwaf-dns-empty">No records match your filters.</td></tr>'; return; }
	tbody.innerHTML = rows.map(r=>{
		const tc = `cfwaf-type-badge ${r.type}`;
		const canProxy = PROXYABLE.includes(r.type);
		const proxyHTML = canProxy
			? `<button class="cfwaf-proxy-toggle" data-id="${r.id}" data-proxied="${r.proxied?'1':'0'}" title="${r.proxied?'Proxied — click to disable':'DNS only — click to enable proxy'}">
				<span class="cfwaf-proxy-icon ${r.proxied?'proxied':'unproxied'}">☁</span>
				<span class="cfwaf-proxy-label">${r.proxied?'Proxied':'DNS only'}</span>
			   </button>`
			: `<span class="cfwaf-proxy-label" style="color:#d1d5db">—</span>`;
		const content = fmtContent(r);
		const shortContent = content.length > 60 ? content.slice(0,60)+'…' : content;
		return `<tr>
			<td><span class="${tc}">${r.type}</span></td>
			<td style="font-weight:600;max-width:180px;word-break:break-all">${r.name}</td>
			<td class="wrap-cell" title="${content.replace(/"/g,'&quot;')}">${shortContent}</td>
			<td>${proxyHTML}</td>
			<td class="cfwaf-ttl">${r.proxied?'Auto':fmtTTL(r.ttl)}</td>
			<td style="font-size:11px;color:var(--dns-muted);max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${r.comment||''}</td>
			<td>
				<div class="cfwaf-dns-actions">
					<button class="cfwaf-dns-btn cfwaf-dns-btn-secondary cfwaf-dns-btn-sm cfwaf-edit-btn" data-id="${r.id}">Edit</button>
					<button class="cfwaf-dns-btn cfwaf-dns-btn-danger cfwaf-dns-btn-sm cfwaf-delete-btn" data-id="${r.id}" data-name="${r.name}">✕</button>
				</div>
			</td>
		</tr>`;
	}).join('');

	// Proxy toggle handlers
	qsa('.cfwaf-proxy-toggle').forEach(btn=>{
		btn.addEventListener('click',()=>{
			const id = btn.dataset.id;
			const nowProxied = btn.dataset.proxied !== '1';
			btn.disabled = true;
			ajax('wpwaf_dns_toggle_proxy',{zone_id:currentZone,record_id:id,proxied:nowProxied?'1':'0'},(res)=>{
				btn.disabled = false;
				if(res.success){ loadRecords(); toast(nowProxied?'☁ Proxy enabled':'⊘ Proxy disabled'); }
				else toast('Failed: '+(res.data?.message||'error'),false);
			});
		});
	});

	// Edit handlers
	qsa('.cfwaf-edit-btn').forEach(btn=>{
		btn.addEventListener('click',()=>{
			const rec = allRecords.find(r=>r.id===btn.dataset.id);
			if(rec) openModal(rec);
		});
	});

	// Delete handlers
	qsa('.cfwaf-delete-btn').forEach(btn=>{
		btn.addEventListener('click',()=>{
			deletingId = btn.dataset.id;
			qs('#cfwaf-dns-delete-name').textContent = btn.dataset.name;
			qs('#cfwaf-dns-delete-modal').style.display='flex';
		});
	});
}

// ── Modal ────────────────────────────────────────────────────────────────────
function openModal(rec=null){
	editingId = rec ? rec.id : '';
	qs('#cfwaf-dns-modal-title').textContent = rec ? 'Edit DNS Record' : 'Add DNS Record';
	qs('#cfwaf-dns-modal-error').style.display = 'none';
	qs('#cfwaf-dns-modal-save').textContent = rec ? 'Save Changes' : 'Save Record';

	// Populate fields
	const typeEl = qs('#cfwaf-dns-type');
	typeEl.value = rec?.type || 'A';
	qs('#cfwaf-dns-name').value    = rec?.name    || '';
	qs('#cfwaf-dns-comment').value = rec?.comment || '';
	qs('#cfwaf-dns-ttl').value     = rec?.ttl     || 1;
	qs('#cfwaf-dns-proxied').checked = !!rec?.proxied;
	qs('#cfwaf-dns-priority').value  = rec?.priority || 10;

	if(rec?.type === 'SRV' && rec.data){
		qs('#cfwaf-srv-service').value  = rec.data.service  || '';
		qs('#cfwaf-srv-proto').value    = rec.data.proto    || '_tcp';
		qs('#cfwaf-srv-priority').value = rec.data.priority || 0;
		qs('#cfwaf-srv-weight').value   = rec.data.weight   || 0;
		qs('#cfwaf-srv-port').value     = rec.data.port     || 0;
		qs('#cfwaf-srv-target').value   = rec.data.target   || '';
	} else if(rec?.type === 'CAA' && rec.data){
		qs('#cfwaf-caa-flags').value = rec.data.flags || 0;
		qs('#cfwaf-caa-tag').value   = rec.data.tag   || 'issue';
		qs('#cfwaf-caa-value').value = rec.data.value || '';
	} else {
		qs('#cfwaf-dns-record-content').value = rec?.content || '';
	}

	updateTypeUI(typeEl.value);
	qs('#cfwaf-dns-modal').style.display='flex';
	qs('#cfwaf-dns-name').focus();
}

function closeModal(){ qs('#cfwaf-dns-modal').style.display='none'; editingId=''; }

function updateTypeUI(type){
	const isSRV = type === 'SRV';
	const isCAA = type === 'CAA';
	const hasPriority = ['MX','SRV','URI'].includes(type);
	const canProxy = PROXYABLE.includes(type);

	qs('#cfwaf-dns-content-field').style.display = (isSRV||isCAA) ? 'none' : '';
	qs('#cfwaf-dns-srv-wrap').style.display       = isSRV ? '' : 'none';
	qs('#cfwaf-dns-caa-wrap').style.display       = isCAA ? '' : 'none';
	qs('#cfwaf-dns-priority-field').style.display = hasPriority ? '' : 'none';
	qs('#cfwaf-dns-proxy-wrap').style.display     = canProxy ? '' : 'none';

	// Hints
	const hints = {
		'A':'IPv4 address (e.g. 203.0.113.1)','AAAA':'IPv6 address',
		'CNAME':'Target hostname (e.g. alias.example.com)',
		'MX':'Mail server hostname','TXT':'Text value (SPF, DKIM, verification, etc.)',
		'NS':'Nameserver hostname','PTR':'Hostname for reverse DNS',
		'HTTPS':'Priority + target + params','SVCB':'Service binding value',
		'TLSA':'TLSA association data','SSHFP':'SSH fingerprint value',
		'SMIMEA':'S/MIME cert data','DNSKEY':'DNSSEC key data',
		'DS':'Delegation signer data','CERT':'Certificate data',
		'NAPTR':'Order weight flags service regexp replacement',
		'LOC':'Location data','URI':'Priority weight target',
		'SPF':'SPF policy (deprecated, use TXT instead)',
	};
	qs('#cfwaf-dns-content-hint').textContent = hints[type]||'';
}

qs('#cfwaf-dns-type')?.addEventListener('change', function(){ updateTypeUI(this.value); });
qs('#cfwaf-dns-proxied')?.addEventListener('change', function(){
	if(this.checked){ qs('#cfwaf-dns-ttl').value = 1; qs('#cfwaf-dns-ttl').disabled = true; }
	else { qs('#cfwaf-dns-ttl').disabled = false; }
});
qs('#cfwaf-dns-add-btn')?.addEventListener('click',()=>openModal());
qs('#cfwaf-dns-modal-close')?.addEventListener('click',closeModal);
qs('#cfwaf-dns-modal-cancel')?.addEventListener('click',closeModal);
qs('#cfwaf-dns-modal')?.addEventListener('click',(e)=>{ if(e.target===qs('#cfwaf-dns-modal')) closeModal(); });

// ── Save ─────────────────────────────────────────────────────────────────────
qs('#cfwaf-dns-modal-save')?.addEventListener('click',()=>{
	const type = qs('#cfwaf-dns-type').value;
	const record = {
		type,
		name:    qs('#cfwaf-dns-name').value.trim(),
		content: qs('#cfwaf-dns-record-content').value.trim(),
		ttl:     parseInt(qs('#cfwaf-dns-ttl').value),
		proxied: qs('#cfwaf-dns-proxied').checked,
		comment: qs('#cfwaf-dns-comment').value.trim(),
		priority:parseInt(qs('#cfwaf-dns-priority').value||'0'),
	};
	if(type==='SRV'){
		record.data = {
			service: qs('#cfwaf-srv-service').value.trim(),
			proto:   qs('#cfwaf-srv-proto').value,
			priority:parseInt(qs('#cfwaf-srv-priority').value),
			weight:  parseInt(qs('#cfwaf-srv-weight').value),
			port:    parseInt(qs('#cfwaf-srv-port').value),
			target:  qs('#cfwaf-srv-target').value.trim(),
		};
		delete record.content;
	}
	if(type==='CAA'){
		record.data = {
			flags: parseInt(qs('#cfwaf-caa-flags').value),
			tag:   qs('#cfwaf-caa-tag').value,
			value: qs('#cfwaf-caa-value').value.trim(),
		};
		delete record.content;
	}

	const errEl = qs('#cfwaf-dns-modal-error');
	errEl.style.display = 'none';
	const saveBtn = qs('#cfwaf-dns-modal-save');
	saveBtn.disabled = true;
	saveBtn.textContent = 'Saving…';

	const action  = editingId ? 'wpwaf_dns_update' : 'wpwaf_dns_create';
	const payload = { zone_id:currentZone, record:JSON.stringify(record) };
	if(editingId) payload.record_id = editingId;

	ajax(action, payload, (res)=>{
		saveBtn.disabled = false;
		saveBtn.textContent = editingId ? 'Save Changes' : 'Save Record';
		if(!res.success){
			errEl.textContent = '⚠ ' + (res.data?.message||'Save failed');
			errEl.style.display = '';
			return;
		}
		closeModal();
		loadRecords();
		toast(editingId ? '✓ Record updated' : '✓ Record created');
	});
});

// ── Delete ────────────────────────────────────────────────────────────────────
[qs('#cfwaf-dns-delete-cancel'),qs('#cfwaf-dns-delete-cancel2')].forEach(b=>{
	b?.addEventListener('click',()=>{ qs('#cfwaf-dns-delete-modal').style.display='none'; deletingId=''; });
});
qs('#cfwaf-dns-delete-confirm')?.addEventListener('click',()=>{
	if(!deletingId) return;
	const btn = qs('#cfwaf-dns-delete-confirm');
	btn.disabled = true; btn.textContent = 'Deleting…';
	ajax('wpwaf_dns_delete',{zone_id:currentZone,record_id:deletingId},(res)=>{
		btn.disabled=false; btn.textContent='Delete';
		qs('#cfwaf-dns-delete-modal').style.display='none';
		if(res.success){ loadRecords(); toast('✓ Record deleted'); }
		else toast('Failed: '+(res.data?.message||'error'),false);
		deletingId='';
	});
});

document.addEventListener('keydown',(e)=>{
	if(e.key==='Escape'){
		if(qs('#cfwaf-dns-modal').style.display!=='none') closeModal();
		if(qs('#cfwaf-dns-delete-modal').style.display!=='none') qs('#cfwaf-dns-delete-modal').style.display='none';
	}
});
})();
</script>
