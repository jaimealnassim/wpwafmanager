<?php
defined( 'ABSPATH' ) || exit;
$wpwaf_si_cache = $has_creds ? WPWAF_SSL_Insights::cached_scans() : [];
?>
<style>
:root{--si-orange:#FF6A00;--si-border:#e2e6ea;--si-bg:#f8f9fb;--si-dark:#1a1a2e;--si-muted:#6b7280;--si-red:#dc2626;--si-amber:#b45309;--si-green:#059669;}
.wpwaf-si-wrap{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;color:var(--si-dark);}
.wpwaf-si-wrap *{box-sizing:border-box;}
.wpwaf-si-header{display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:20px;}
.wpwaf-si-header h1{font-size:22px;font-weight:700;margin:0;padding:0;display:flex;align-items:center;gap:8px;color:var(--si-dark);line-height:1.3;}
.wpwaf-si-header h1 .dashicons{color:var(--si-orange);font-size:24px;width:24px;height:24px;}
.wpwaf-si-header-sub{font-size:12px;color:var(--si-muted);margin-top:3px;}
.wpwaf-si-notice{background:#fff8f5;border:1px solid #fcd9c0;border-radius:8px;padding:16px 20px;color:#92400e;font-size:13px;line-height:1.6;}
.wpwaf-si-notice--error{background:#fef2f2;border-color:#fca5a5;color:#7f1d1d;}

/* Tabs */
.wpwaf-si-tabs{display:flex;border-bottom:2px solid var(--si-border);margin-bottom:20px;gap:0;overflow-x:auto;scrollbar-width:none;}
.wpwaf-si-tabs::-webkit-scrollbar{display:none;}
.wpwaf-si-tab{padding:10px 18px;font-size:13px;font-weight:600;color:var(--si-muted);background:none;border:none;border-bottom:2px solid transparent;margin-bottom:-2px;cursor:pointer;white-space:nowrap;}
.wpwaf-si-tab:hover{color:var(--si-dark);}
.wpwaf-si-tab.active{color:var(--si-orange);border-bottom-color:var(--si-orange);}
.wpwaf-si-panel{display:none;}
.wpwaf-si-panel.active{display:block;}

/* Help box */
.wpwaf-si-help{background:#fff;border:1px solid var(--si-border);border-radius:8px;margin-bottom:16px;font-size:12px;color:#374151;}
.wpwaf-si-help summary{cursor:pointer;padding:10px 14px;font-weight:600;color:var(--si-dark);list-style:none;display:flex;align-items:center;gap:6px;}
.wpwaf-si-help summary::-webkit-details-marker{display:none;}
.wpwaf-si-help summary:before{content:"▸";color:var(--si-muted);font-size:11px;}
.wpwaf-si-help[open] summary:before{content:"▾";}
.wpwaf-si-help-body{padding:0 14px 12px;line-height:1.6;}
.wpwaf-si-help-body ul{margin:6px 0 0 18px;padding:0;list-style:disc;}
.wpwaf-si-help code{font-size:11px;background:var(--si-bg);padding:1px 5px;border-radius:4px;}

/* Summary */
.wpwaf-si-summary{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:16px;}
.wpwaf-si-stat{background:#fff;border:1px solid var(--si-border);border-radius:10px;padding:12px 14px;min-width:0;}
.wpwaf-si-stat-num{font-size:22px;font-weight:700;line-height:1.2;}
.wpwaf-si-stat-label{font-size:11px;font-weight:600;color:var(--si-muted);text-transform:uppercase;letter-spacing:.5px;margin-top:2px;}
.wpwaf-si-stat.is-warn .wpwaf-si-stat-num{color:var(--si-amber);}
.wpwaf-si-stat.is-crit .wpwaf-si-stat-num{color:var(--si-red);}

/* Toolbar */
.wpwaf-si-toolbar{display:flex;align-items:center;gap:10px;margin-bottom:16px;flex-wrap:wrap;}
.wpwaf-si-search{height:34px;padding:0 12px;border:1px solid var(--si-border);border-radius:6px;font-size:13px;flex:1 1 200px;min-width:0;background:#fff;}
.wpwaf-si-search:focus,.wpwaf-si-select:focus{outline:none;border-color:var(--si-orange);box-shadow:0 0 0 2px rgba(255,106,0,.1);}
.wpwaf-si-select{height:34px;padding:0 28px 0 10px;border:1px solid var(--si-border);border-radius:6px;font-size:13px;background-color:#fff;min-width:0;max-width:100%;}
.wpwaf-si-check{display:inline-flex;align-items:center;gap:6px;font-size:12px;color:#374151;white-space:nowrap;}
.wpwaf-si-meta{font-size:12px;color:var(--si-muted);white-space:nowrap;}
.wpwaf-si-progress{font-size:12px;color:var(--si-muted);display:none;align-items:center;gap:6px;}
.wpwaf-si-progress.on{display:inline-flex;}

/* Buttons */
.wpwaf-si-btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;height:34px;padding:0 14px;font-size:12px;font-weight:600;border-radius:6px;border:1px solid #d1d5db;background:#fff;color:#374151;cursor:pointer;white-space:nowrap;transition:background .15s,border-color .15s;text-decoration:none;}
.wpwaf-si-btn:hover{background:#f3f4f6;border-color:#9ca3af;color:#111;}
.wpwaf-si-btn--primary{background:var(--si-orange);border-color:var(--si-orange);color:#fff;}
.wpwaf-si-btn--primary:hover{background:#e05c00;border-color:#e05c00;color:#fff;}
.wpwaf-si-btn--sm{height:26px;padding:0 10px;font-size:11px;}
.wpwaf-si-btn--icon{width:28px;height:28px;padding:0;font-size:14px;}
.wpwaf-si-btn:disabled{opacity:.5;cursor:not-allowed;}

/* Zone cards */
.wpwaf-si-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:16px;align-items:start;}
.wpwaf-si-card{background:#fff;border:1px solid var(--si-border);border-radius:10px;overflow:hidden;min-width:0;display:flex;flex-direction:column;}
.wpwaf-si-card-head{display:flex;align-items:flex-start;gap:8px;padding:12px 14px;border-bottom:1px solid var(--si-border);}
.wpwaf-si-card-title{flex:1;min-width:0;}
.wpwaf-si-card-title h3{margin:0;font-size:13px;font-weight:700;overflow-wrap:anywhere;line-height:1.35;}
.wpwaf-si-card-sub{font-size:11px;color:var(--si-muted);margin-top:5px;display:flex;align-items:center;gap:6px;flex-wrap:wrap;}
.wpwaf-si-card.is-unscanned .wpwaf-si-card-head{border-bottom:none;}
.wpwaf-si-card.is-unscanned{background:#fcfcfd;}
.wpwaf-si-card-body{padding:10px 14px 12px;display:flex;flex-direction:column;gap:10px;}
.wpwaf-si-section-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--si-muted);margin-bottom:4px;}
.wpwaf-si-rows{display:flex;flex-direction:column;}
.wpwaf-si-row{display:flex;align-items:center;justify-content:space-between;gap:8px;padding:6px 0;border-bottom:1px solid #f1f3f5;font-size:12px;}
.wpwaf-si-row:last-child{border-bottom:none;}
.wpwaf-si-row-name{font-weight:600;min-width:0;}
.wpwaf-si-row-hint{font-size:11px;color:var(--si-muted);font-weight:400;overflow-wrap:anywhere;}
.wpwaf-si-row-val{display:flex;align-items:center;gap:6px;flex-shrink:0;}
.wpwaf-si-cert{padding:7px 0;border-bottom:1px solid #f1f3f5;font-size:12px;}
.wpwaf-si-cert:last-child{border-bottom:none;}
.wpwaf-si-cert-top{display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;}
.wpwaf-si-cert-name{font-weight:600;}
.wpwaf-si-cert-meta{font-size:11px;color:var(--si-muted);margin-top:2px;overflow-wrap:anywhere;}

/* Pills */
.wpwaf-si-pill{display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:600;white-space:nowrap;background:#f3f4f6;color:#4b5563;}
.wpwaf-si-pill--ok{background:#ecfdf5;color:#047857;}
.wpwaf-si-pill--warn{background:#fffbeb;color:#92400e;}
.wpwaf-si-pill--crit{background:#fef2f2;color:#b91c1c;}
.wpwaf-si-pill--info{background:#eff6ff;color:#1e40af;}
.wpwaf-si-plan{font-size:10px;font-weight:600;padding:2px 7px;border-radius:10px;background:#f1f5f9;color:#64748b;border:1px solid #e2e8f0;white-space:nowrap;}

/* Issues */
.wpwaf-si-issues{display:flex;flex-direction:column;gap:6px;margin:0;padding:0;list-style:none;}
.wpwaf-si-issue{display:flex;gap:8px;align-items:flex-start;font-size:12px;line-height:1.5;padding:7px 10px;border-radius:6px;background:var(--si-bg);}
.wpwaf-si-issue--crit{background:#fef2f2;color:#7f1d1d;}
.wpwaf-si-issue--warn{background:#fffbeb;color:#78350f;}
.wpwaf-si-issue--info{background:#eff6ff;color:#1e3a8a;}
.wpwaf-si-issue-text{flex:1;min-width:0;overflow-wrap:anywhere;}
.wpwaf-si-issue .wpwaf-si-btn{flex-shrink:0;}
.wpwaf-si-fixes{display:flex;flex-direction:column;gap:4px;flex-shrink:0;align-items:stretch;}
.wpwaf-si-error{font-size:12px;color:#b91c1c;background:#fef2f2;border-radius:6px;padding:7px 10px;overflow-wrap:anywhere;}
.wpwaf-si-empty{text-align:center;padding:40px;color:#9ca3af;font-size:13px;background:#fff;border:1px dashed var(--si-border);border-radius:10px;}
.wpwaf-si-unscanned{font-size:12px;color:var(--si-muted);display:flex;align-items:center;justify-content:space-between;gap:8px;}

/* Insights */
.wpwaf-si-sevbar{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px;}
.wpwaf-si-note{font-size:12px;color:var(--si-muted);margin:0 0 14px;line-height:1.6;}
.wpwaf-si-table-wrap{background:#fff;border:1px solid var(--si-border);border-radius:8px;overflow:hidden;}
.wpwaf-si-table{width:100%;border-collapse:collapse;font-size:13px;table-layout:auto;}
.wpwaf-si-table th{background:var(--si-bg);padding:9px 14px;text-align:left;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--si-muted);border-bottom:1px solid var(--si-border);white-space:nowrap;}
.wpwaf-si-table td{padding:10px 14px;border-bottom:1px solid #f1f3f5;vertical-align:top;}
.wpwaf-si-table tr:last-child td{border-bottom:none;}
.wpwaf-si-table tr.is-dismissed td{opacity:.6;}
.wpwaf-si-issue-title{font-weight:600;overflow-wrap:anywhere;}
.wpwaf-si-issue-desc{font-size:12px;color:var(--si-muted);margin-top:2px;overflow-wrap:anywhere;}
.wpwaf-si-issue-desc a{color:var(--si-orange);}
.wpwaf-si-subject{overflow-wrap:anywhere;}
.wpwaf-si-cell-act{text-align:right;white-space:nowrap;}

/* Spinner and toast */
.wpwaf-si-spinner{display:inline-block;width:12px;height:12px;border:2px solid rgba(255,106,0,.3);border-top-color:var(--si-orange);border-radius:50%;animation:wpwaf-si-spin .6s linear infinite;flex-shrink:0;}
@keyframes wpwaf-si-spin{to{transform:rotate(360deg);}}
.wpwaf-si-loading{text-align:center;padding:40px;color:var(--si-muted);font-size:13px;}
.wpwaf-si-toast{position:fixed;bottom:24px;right:24px;max-width:calc(100vw - 48px);padding:10px 16px;border-radius:6px;font-size:13px;font-weight:600;color:#fff;z-index:999999;transition:opacity .3s;}
.wpwaf-si-toast.ok{background:#059669;}
.wpwaf-si-toast.err{background:#dc2626;}

@media (max-width:1100px){
  .wpwaf-si-summary{grid-template-columns:repeat(2,minmax(0,1fr));}
}
@media (max-width:782px){
  .wpwaf-si-grid{grid-template-columns:minmax(0,1fr);}
  .wpwaf-si-toolbar .wpwaf-si-search{flex:1 1 100%;}
  .wpwaf-si-toolbar .wpwaf-si-select{flex:1 1 140px;}
  .wpwaf-si-table thead{display:none;}
  .wpwaf-si-table,.wpwaf-si-table tbody,.wpwaf-si-table tr,.wpwaf-si-table td{display:block;width:100%;}
  .wpwaf-si-table tr{border-bottom:1px solid var(--si-border);padding:10px 12px;}
  .wpwaf-si-table tr:last-child{border-bottom:none;}
  .wpwaf-si-table td{border:none;padding:3px 0;}
  .wpwaf-si-table td[data-label]:before{content:attr(data-label) ": ";font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:var(--si-muted);}
  .wpwaf-si-cell-act{text-align:left;padding-top:8px !important;}
  .wpwaf-si-toast{left:12px;right:12px;bottom:12px;max-width:none;}
}
@media (max-width:480px){
  .wpwaf-si-summary{gap:8px;}
  .wpwaf-si-stat{padding:10px 12px;}
  .wpwaf-si-stat-num{font-size:18px;}
  .wpwaf-si-toolbar .wpwaf-si-btn{flex:1 1 100%;}
  .wpwaf-si-tab{padding:10px 12px;}
  .wpwaf-si-row{flex-wrap:wrap;}
}
</style>

<div class="wpwaf-si-wrap">
	<div class="wpwaf-si-header">
		<div>
			<h1><span class="dashicons dashicons-lock"></span> SSL &amp; Insights</h1>
			<div class="wpwaf-si-header-sub">Certificate expiry, HTTPS settings and Cloudflare Security Insights across your zones</div>
		</div>
	</div>

<?php if ( ! $has_creds ) : ?>
	<div class="wpwaf-si-notice">
		<strong>No Cloudflare account connected.</strong><br>
		Add a Cloudflare account on the main <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpwafmanager' ) ); ?>">WAF Rules</a> page first.
	</div>
<?php else : ?>

	<div class="wpwaf-si-tabs" role="tablist">
		<button type="button" class="wpwaf-si-tab active" data-tab="certs" role="tab">🔒 Certificates &amp; HTTPS</button>
		<button type="button" class="wpwaf-si-tab" data-tab="insights" role="tab">🛡 Security Insights</button>
	</div>

	<!-- ── Certificates & HTTPS ─────────────────────────────────────────── -->
	<div class="wpwaf-si-panel active" id="wpwaf-si-panel-certs" role="tabpanel">
		<details class="wpwaf-si-help">
			<summary>What this checks and the token permissions it needs</summary>
			<div class="wpwaf-si-help-body">
				Each scan reads the zone's edge certificates and six HTTPS settings: SSL mode, Always Use HTTPS, Automatic HTTPS Rewrites, Minimum TLS version, TLS 1.3 and HSTS. Cloudflare renews Universal and Advanced certificates on its own, so those are only flagged when renewal looks stuck. Custom certificates you uploaded are flagged 30 days before they expire.
				<ul>
					<li><strong>Zone → SSL and Certificates → Read</strong> to read certificates</li>
					<li><strong>Zone → Zone Settings → Read</strong> to read HTTPS settings (Edit for the one-click fixes)</li>
				</ul>
			</div>
		</details>

		<div class="wpwaf-si-summary">
			<div class="wpwaf-si-stat"><div class="wpwaf-si-stat-num" id="wpwaf-si-stat-zones">0</div><div class="wpwaf-si-stat-label">Zones</div></div>
			<div class="wpwaf-si-stat"><div class="wpwaf-si-stat-num" id="wpwaf-si-stat-scanned">0</div><div class="wpwaf-si-stat-label">Scanned</div></div>
			<div class="wpwaf-si-stat" id="wpwaf-si-stat-exp-box"><div class="wpwaf-si-stat-num" id="wpwaf-si-stat-exp">0</div><div class="wpwaf-si-stat-label">Expiring soon</div></div>
			<div class="wpwaf-si-stat" id="wpwaf-si-stat-iss-box"><div class="wpwaf-si-stat-num" id="wpwaf-si-stat-iss">0</div><div class="wpwaf-si-stat-label">Zones with issues</div></div>
		</div>

		<div class="wpwaf-si-toolbar">
			<input type="search" id="wpwaf-si-search" class="wpwaf-si-search" placeholder="Filter zones…">
			<select id="wpwaf-si-show" class="wpwaf-si-select" aria-label="Show">
				<option value="all">All zones</option>
				<option value="issues">With issues</option>
				<option value="unscanned">Not scanned</option>
			</select>
			<span class="wpwaf-si-progress" id="wpwaf-si-progress"><span class="wpwaf-si-spinner"></span><span id="wpwaf-si-progress-text"></span></span>
			<button type="button" class="wpwaf-si-btn wpwaf-si-btn--primary" id="wpwaf-si-scan-all">↻ Scan all zones</button>
		</div>

		<div id="wpwaf-si-grid" class="wpwaf-si-grid">
			<div class="wpwaf-si-loading"><span class="wpwaf-si-spinner"></span> Loading zones…</div>
		</div>
	</div>

	<!-- ── Security Insights ────────────────────────────────────────────── -->
	<div class="wpwaf-si-panel" id="wpwaf-si-panel-insights" role="tabpanel">
		<details class="wpwaf-si-help">
			<summary>About Security Insights and the token permissions it needs</summary>
			<div class="wpwaf-si-help-body">
				Security Insights is Cloudflare's own scan of your account: DNS records that point nowhere, records that are not proxied, missing HTTPS settings and similar. It is available on every plan. Dismissing an insight archives it in Cloudflare; you can restore it at any time.
				<ul>
					<li><strong>Account → Security Center Insights → Read</strong> to list insights</li>
					<li><strong>Account → Security Center Insights → Edit</strong> to dismiss or restore them</li>
				</ul>
			</div>
		</details>
		<p class="wpwaf-si-note">Cloudflare runs these scans automatically, every 7 days on Free, Pro and Business plans and every 3 days on Enterprise. An issue you fix clears after the next scan.</p>

		<div class="wpwaf-si-toolbar">
			<select id="wpwaf-si-account" class="wpwaf-si-select" aria-label="Cloudflare account"><option value="">Loading accounts…</option></select>
			<select id="wpwaf-si-view" class="wpwaf-si-select" aria-label="Status">
				<option value="active">Open insights</option>
				<option value="dismissed">Dismissed</option>
				<option value="all">Open and dismissed</option>
			</select>
			<select id="wpwaf-si-sev" class="wpwaf-si-select" aria-label="Severity">
				<option value="">All severities</option>
				<option value="critical">Critical</option>
				<option value="moderate">Moderate</option>
				<option value="low">Low</option>
			</select>
			<input type="search" id="wpwaf-si-isearch" class="wpwaf-si-search" placeholder="Filter by subject or issue…">
			<button type="button" class="wpwaf-si-btn" id="wpwaf-si-irefresh">↻ Refresh</button>
		</div>
		<div class="wpwaf-si-sevbar" id="wpwaf-si-sevbar"></div>
		<div id="wpwaf-si-insights"><div class="wpwaf-si-empty">Choose an account to load its insights.</div></div>
	</div>

<?php endif; ?>
</div>

<script>
(function(){
'use strict';
<?php if ( ! $has_creds ) { echo '})();</script>'; return; } ?>
const NONCE    = <?php echo wp_json_encode( $nonce ); ?>;
const AJAX_URL = <?php echo wp_json_encode( $ajax_url ); ?>;
const CACHE    = <?php echo wp_json_encode( (object) $wpwaf_si_cache ); ?>;

const DAY = 86400;
const MANAGED_TYPES = ['universal','advanced','total_tls','managed_hostname'];
const TYPE_LABELS = {universal:'Universal',advanced:'Advanced',total_tls:'Total TLS',managed_hostname:'Managed hostname',sni_custom:'Custom (SNI)',legacy_custom:'Custom (legacy)',mh_custom:'Custom hostname',keyless:'Keyless'};
const CA_LABELS = {google:'Google Trust Services',lets_encrypt:"Let's Encrypt",ssl_com:'SSL.com'};
const BAD_STATUS = ['expired','initializing_timed_out','validation_timed_out','issuance_timed_out','deployment_timed_out','deletion_timed_out','inactive','deactivating'];
const OK_STATUS = ['active','staging_active','pending_cleanup','backup_issued'];
const SSL_LABELS = {off:'Off',flexible:'Flexible',full:'Full',strict:'Full (strict)',origin_pull:'Strict (origin pull)'};
// Button text for each fix, and what each SSL mode change needs from the server.
const FIX_LABELS = {always_use_https:'Fix', automatic_https_rewrites:'Fix', min_tls_version:'Fix', ssl_full:'Set Full', ssl_strict:'Set Full (strict)'};
const FIX_DONE   = {always_use_https:'Always Use HTTPS on', automatic_https_rewrites:'Automatic HTTPS Rewrites on', min_tls_version:'Minimum TLS set to 1.2', ssl_full:'SSL mode set to Full', ssl_strict:'SSL mode set to Full (strict)'};
const FIX_CONFIRM = {
	ssl_full:   'Set SSL mode to Full on {zone}?\n\nYour server must accept HTTPS on port 443. Any certificate works, even a self-signed one. If the server does not accept HTTPS, visitors will see a Cloudflare 525 error.\n\nYou can change it back in Zone Controls.',
	ssl_strict: 'Set SSL mode to Full (strict) on {zone}?\n\nYour server must accept HTTPS on port 443 with a valid, unexpired certificate for this hostname, from a public certificate authority or a Cloudflare Origin CA certificate. If not, visitors will see a Cloudflare 526 error.\n\nYou can change it back in Zone Controls.'
};

let zones = [];
const scans = Object.assign({}, CACHE);
let zoneNames = {};

const esc = s => String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
const qs  = (s, c) => (c || document).querySelector(s);
const qsa = (s, c) => Array.from((c || document).querySelectorAll(s));

function toast(msg, ok = true){
	const el = document.createElement('div');
	el.className = 'wpwaf-si-toast ' + (ok ? 'ok' : 'err');
	el.textContent = msg;
	document.body.appendChild(el);
	setTimeout(() => { el.style.opacity = '0'; setTimeout(() => el.remove(), 300); }, ok ? 3000 : 6000);
}
function ajax(action, data){
	const fd = new FormData();
	fd.append('action', action); fd.append('nonce', NONCE);
	Object.entries(data || {}).forEach(([k, v]) => fd.append(k, v));
	return fetch(AJAX_URL, {method:'POST', body:fd, credentials:'same-origin'})
		.then(r => r.json())
		.catch(e => ({success:false, data:{message:e.message}}));
}
function errMsg(res){ return (res && res.data && res.data.message) ? res.data.message : 'Request failed'; }
function fmtDate(ts){
	if (!ts) return '';
	return new Date(ts * 1000).toLocaleDateString(undefined, {year:'numeric', month:'short', day:'numeric'});
}
function ago(ts){
	if (!ts) return 'never';
	const s = Math.max(0, Math.floor(Date.now() / 1000) - ts);
	if (s < 60) return 'just now';
	if (s < 3600) return Math.floor(s / 60) + ' min ago';
	if (s < 86400) return Math.floor(s / 3600) + ' h ago';
	return Math.floor(s / 86400) + ' d ago';
}
function words(s){ return String(s || '').replace(/[_-]+/g, ' ').replace(/\s+/g, ' ').trim(); }
function cap(s){ s = words(s); return s.charAt(0).toUpperCase() + s.slice(1); }

// ── Tabs ─────────────────────────────────────────────────────────────────
let insightsStarted = false;
function showTab(name){
	qsa('.wpwaf-si-tab').forEach(t => t.classList.toggle('active', t.dataset.tab === name));
	qsa('.wpwaf-si-panel').forEach(p => p.classList.toggle('active', p.id === 'wpwaf-si-panel-' + name));
	if (name === 'insights' && !insightsStarted){ insightsStarted = true; loadAccounts(); }
}
qsa('.wpwaf-si-tab').forEach(t => t.addEventListener('click', () => {
	showTab(t.dataset.tab);
	try { history.replaceState(null, '', '#' + t.dataset.tab); } catch(e){}
}));

// ── Certificates & HTTPS: evaluation ─────────────────────────────────────
function evaluate(scan){
	const issues = [];
	let expiring = 0;
	if (!scan) return {issues, expiring, level:'none'};
	const now = Date.now() / 1000;
	const errs = scan.errors || {};
	if (errs.certs || errs.settings){
		issues.push({level:'warn', text:'Some checks could not run. See the messages below.'});
	}
	const certs = scan.certs;
	if (Array.isArray(certs)){
		if (!certs.length){
			issues.push({level:'crit', text:'No edge certificate found. Visitors cannot reach this zone over HTTPS through Cloudflare until one is issued.'});
		} else if (!certs.some(c => OK_STATUS.includes(c.status))){
			issues.push({level:'crit', text:'No active edge certificate. Check the certificate status in Cloudflare under SSL/TLS, Edge Certificates.'});
		}
		certs.forEach(c => {
			const label = TYPE_LABELS[c.type] || cap(c.type) || 'Certificate';
			if (BAD_STATUS.includes(c.status) && c.status !== 'expired'){
				issues.push({level:'warn', text: label + ' certificate status is "' + words(c.status) + '".'});
			}
			if (!c.expires_on) return;
			const days = Math.floor((c.expires_on - now) / DAY);
			const managed = MANAGED_TYPES.includes(c.type);
			if (days < 0){
				expiring++;
				issues.push({level:'crit', text: label + ' certificate expired on ' + fmtDate(c.expires_on) + '.'});
			} else if (managed && days <= 7){
				expiring++;
				issues.push({level:'warn', text: label + ' certificate expires in ' + days + ' day' + (days === 1 ? '' : 's') + ' and Cloudflare has not renewed it yet. Check for a validation problem in SSL/TLS, Edge Certificates.'});
			} else if (!managed && days <= 30){
				expiring++;
				issues.push({level:'warn', text: label + ' certificate expires in ' + days + ' day' + (days === 1 ? '' : 's') + '. Cloudflare does not renew custom certificates, so upload a new one before then.'});
			}
		});
	}
	const s = scan.settings || {};
	const val = k => (s[k] ? s[k].value : undefined);
	const ssl = val('ssl');
	if (ssl === 'off'){
		issues.push({level:'crit', text:'SSL mode is Off: visitors cannot use HTTPS through Cloudflare.', fixes:['ssl_full','ssl_strict'], fixSetting:'ssl'});
	} else if (ssl === 'flexible'){
		issues.push({level:'warn', text:'SSL mode is Flexible: traffic between Cloudflare and your server is not encrypted, and WordPress can loop on redirects. Use Full (strict) once the server has a valid certificate.', fixes:['ssl_full','ssl_strict'], fixSetting:'ssl'});
	} else if (ssl === 'full'){
		issues.push({level:'info', text:'SSL mode is Full: Cloudflare does not check your server certificate. Full (strict) is safer if the server has a valid or Cloudflare Origin certificate.', fixes:['ssl_strict'], fixSetting:'ssl'});
	}
	if (val('always_use_https') === 'off' && ssl !== 'off'){
		issues.push({level:'warn', text:'Always Use HTTPS is off, so visitors can still load pages over plain HTTP.', fix:'always_use_https'});
	}
	if (val('automatic_https_rewrites') === 'off'){
		issues.push({level:'info', text:'Automatic HTTPS Rewrites is off. Turning it on fixes many mixed content warnings.', fix:'automatic_https_rewrites'});
	}
	const tls = val('min_tls_version');
	if (tls === '1.0' || tls === '1.1'){
		issues.push({level:'warn', text:'Minimum TLS version is ' + tls + ', which allows outdated, insecure connections. TLS 1.2 is the safe minimum for almost every site.', fix:'min_tls_version'});
	}
	if (val('tls_1_3') === 'off'){
		issues.push({level:'info', text:'TLS 1.3 is off. Turn it on in Cloudflare under SSL/TLS, Edge Certificates for faster, more secure connections.'});
	}
	const order = {crit:3, warn:2, info:1};
	const top = issues.reduce((m, i) => Math.max(m, order[i.level]), 0);
	return {issues, expiring, level: top === 3 ? 'crit' : top === 2 ? 'warn' : top === 1 ? 'info' : 'ok'};
}

// ── Certificates & HTTPS: rendering ──────────────────────────────────────
function planLabel(plan){ return String(plan || '').replace(/ Website$/i, '') || 'Unknown'; }

function statusPill(ev, scan){
	if (!scan) return '<span class="wpwaf-si-pill">Not scanned</span>';
	const n = ev.issues.filter(i => i.level !== 'info').length;
	if (ev.level === 'crit') return '<span class="wpwaf-si-pill wpwaf-si-pill--crit">' + n + ' issue' + (n === 1 ? '' : 's') + '</span>';
	if (ev.level === 'warn') return '<span class="wpwaf-si-pill wpwaf-si-pill--warn">' + n + ' issue' + (n === 1 ? '' : 's') + '</span>';
	return '<span class="wpwaf-si-pill wpwaf-si-pill--ok">✓ OK</span>';
}

function certHTML(c){
	const label = TYPE_LABELS[c.type] || cap(c.type) || 'Certificate';
	const now = Date.now() / 1000;
	let exp = '';
	if (c.expires_on){
		const days = Math.floor((c.expires_on - now) / DAY);
		const cls = days < 0 ? 'crit' : (days <= (MANAGED_TYPES.includes(c.type) ? 7 : 30) ? 'warn' : 'ok');
		const txt = days < 0 ? 'Expired' : (days === 0 ? 'Expires today' : days + ' day' + (days === 1 ? '' : 's') + ' left');
		exp = '<span class="wpwaf-si-pill wpwaf-si-pill--' + cls + '" title="' + esc(fmtDate(c.expires_on)) + '">' + esc(txt) + '</span>';
	}
	const statusCls = OK_STATUS.includes(c.status) ? 'ok' : (BAD_STATUS.includes(c.status) ? 'crit' : 'info');
	const meta = [];
	if (c.expires_on) meta.push('Expires ' + fmtDate(c.expires_on));
	if (CA_LABELS[c.ca]) meta.push(CA_LABELS[c.ca]);
	else if (c.issuer) meta.push(c.issuer);
	if (c.hosts && c.hosts.length){
		const more = c.host_count > c.hosts.length ? ' +' + (c.host_count - c.hosts.length) : '';
		meta.push(c.hosts.slice(0, 3).join(', ') + (c.hosts.length > 3 ? ' +' + (c.host_count - 3) : more));
	}
	return '<div class="wpwaf-si-cert"><div class="wpwaf-si-cert-top"><span class="wpwaf-si-cert-name">' + esc(label) + '</span>'
		+ '<span class="wpwaf-si-row-val"><span class="wpwaf-si-pill wpwaf-si-pill--' + statusCls + '">' + esc(cap(c.status)) + '</span>' + exp + '</span></div>'
		+ (meta.length ? '<div class="wpwaf-si-cert-meta">' + esc(meta.join(' · ')) + '</div>' : '') + '</div>';
}

function settingRows(scan){
	const s = scan.settings || {};
	const pill = (v, good, bad) => {
		if (v === undefined) return '<span class="wpwaf-si-pill">Unknown</span>';
		const cls = good.includes(v) ? 'ok' : (bad.includes(v) ? 'warn' : '');
		return '<span class="wpwaf-si-pill' + (cls ? ' wpwaf-si-pill--' + cls : '') + '">' + esc(v) + '</span>';
	};
	const onoff = v => v === undefined ? undefined : (v === 'on' ? 'On' : v === 'off' ? 'Off' : v === 'zrt' ? 'On (0-RTT)' : v);
	const v = k => (s[k] ? s[k].value : undefined);
	const sslV = v('ssl');
	const rows = [
		['SSL mode', 'Encryption between visitors, Cloudflare and your server', sslV === undefined ? pill(undefined) : pill(SSL_LABELS[sslV] || sslV, ['Full (strict)','Strict (origin pull)'], ['Off','Flexible'])],
		['Always Use HTTPS', 'Redirects every HTTP request to HTTPS', pill(onoff(v('always_use_https')), ['On'], ['Off'])],
		['Automatic HTTPS Rewrites', 'Rewrites HTTP links to HTTPS where possible', pill(onoff(v('automatic_https_rewrites')), ['On'], [])],
		['Minimum TLS', 'Oldest TLS version Cloudflare accepts', pill(v('min_tls_version'), ['1.2','1.3'], ['1.0','1.1'])],
		['TLS 1.3', 'Newest, fastest TLS version', pill(onoff(v('tls_1_3')), ['On','On (0-RTT)'], [])],
	];
	const h = v('security_header');
	let hsts;
	if (h === undefined) hsts = pill(undefined);
	else if (h && h.enabled){
		const months = Math.round((h.max_age || 0) / (30 * DAY));
		const bits = [months ? months + ' mo' : 'max-age 0'];
		if (h.include_subdomains) bits.push('subdomains');
		if (h.preload) bits.push('preload');
		hsts = '<span class="wpwaf-si-pill wpwaf-si-pill--ok">On, ' + esc(bits.join(', ')) + '</span>';
	} else hsts = '<span class="wpwaf-si-pill">Off</span>';
	rows.push(['HSTS', 'Read only: browsers keep forcing HTTPS until max-age runs out, so change it in Cloudflare', hsts]);
	return rows.map(([n, hint, ctrl]) => '<div class="wpwaf-si-row"><div class="wpwaf-si-row-name">' + esc(n) + '<div class="wpwaf-si-row-hint">' + esc(hint) + '</div></div><div class="wpwaf-si-row-val">' + ctrl + '</div></div>').join('');
}

function cardHTML(z){
	const scan = scans[z.id];
	const ev = evaluate(scan);
	const head = '<div class="wpwaf-si-card-head"><div class="wpwaf-si-card-title"><h3>' + esc(z.name) + '</h3>'
		+ '<div class="wpwaf-si-card-sub"><span class="wpwaf-si-plan">' + esc(planLabel(z.plan)) + '</span>'
		+ (scan ? statusPill(ev, scan) + '<span class="wpwaf-si-card-when">Scanned ' + esc(ago(scan.scanned_at)) + '</span>' : '<span class="wpwaf-si-card-when">Not scanned yet</span>')
		+ '</div></div>'
		+ (scan
			? '<button type="button" class="wpwaf-si-btn wpwaf-si-btn--icon wpwaf-si-scan-one" data-zone="' + esc(z.id) + '" title="Scan again" aria-label="Scan ' + esc(z.name) + ' again">↻</button>'
			: '<button type="button" class="wpwaf-si-btn wpwaf-si-btn--sm wpwaf-si-scan-one" data-zone="' + esc(z.id) + '" aria-label="Scan ' + esc(z.name) + '">Scan</button>')
		+ '</div>';
	if (!scan){
		return '<div class="wpwaf-si-card is-unscanned" data-zone="' + esc(z.id) + '">' + head + '</div>';
	}
	const errs = scan.errors || {};
	const issues = ev.issues.map(i => {
		const keys = i.fixes || (i.fix ? [i.fix] : []);
		const s = scan.settings ? scan.settings[i.fixSetting || i.fix] : null;
		const editable = !s || s.editable !== false;
		const btns = keys.map(k => '<button type="button" class="wpwaf-si-btn wpwaf-si-btn--sm wpwaf-si-fix" data-zone="' + esc(z.id) + '" data-fix="' + esc(k) + '"' + (editable ? '' : ' disabled title="This setting cannot be changed on this zone\'s plan"') + '>' + esc(FIX_LABELS[k] || 'Fix') + '</button>').join('');
		return '<li class="wpwaf-si-issue wpwaf-si-issue--' + i.level + '"><span class="wpwaf-si-issue-text">' + esc(i.text) + '</span>' + (btns ? '<span class="wpwaf-si-fixes">' + btns + '</span>' : '') + '</li>';
	}).join('');
	const body = (issues ? '<ul class="wpwaf-si-issues">' + issues + '</ul>' : '')
		+ '<div><div class="wpwaf-si-section-label">Edge certificates</div>'
		+ (errs.certs ? '<div class="wpwaf-si-error">' + esc(errs.certs) + '</div>'
			: (Array.isArray(scan.certs) && scan.certs.length ? '<div class="wpwaf-si-rows">' + scan.certs.map(certHTML).join('') + '</div>' : '<div class="wpwaf-si-row-hint">None found</div>'))
		+ '</div>'
		+ '<div><div class="wpwaf-si-section-label">HTTPS settings</div>'
		+ (errs.settings ? '<div class="wpwaf-si-error">' + esc(errs.settings) + '</div>' : '')
		+ (scan.settings && Object.keys(scan.settings).length ? '<div class="wpwaf-si-rows">' + settingRows(scan) + '</div>' : '')
		+ '</div>';
	return '<div class="wpwaf-si-card" data-zone="' + esc(z.id) + '">' + head + '<div class="wpwaf-si-card-body">' + body + '</div></div>';
}

/** Zones with problems first, then healthy scanned zones, then unscanned; A to Z within each group. */
function rank(z){
	const scan = scans[z.id];
	if (!scan) return 3;
	const lvl = evaluate(scan).level;
	if (scan.errors && (scan.errors.certs || scan.errors.settings)) return 0;
	return lvl === 'crit' ? 0 : lvl === 'warn' ? 1 : 2;
}

function visibleZones(){
	const q = (qs('#wpwaf-si-search').value || '').toLowerCase().trim();
	const show = qs('#wpwaf-si-show').value;
	return zones.filter(z => {
		if (q && !z.name.toLowerCase().includes(q)) return false;
		if (show === 'unscanned') return !scans[z.id];
		if (show === 'issues'){ const ev = evaluate(scans[z.id]); return ev.level === 'crit' || ev.level === 'warn'; }
		return true;
	}).sort((a, b) => rank(a) - rank(b) || a.name.localeCompare(b.name));
}

function renderSummary(){
	let scanned = 0, expiring = 0, withIssues = 0;
	zones.forEach(z => {
		const scan = scans[z.id];
		if (!scan) return;
		scanned++;
		const ev = evaluate(scan);
		expiring += ev.expiring;
		if (ev.level === 'crit' || ev.level === 'warn') withIssues++;
	});
	qs('#wpwaf-si-stat-zones').textContent = zones.length;
	qs('#wpwaf-si-stat-scanned').textContent = scanned;
	qs('#wpwaf-si-stat-exp').textContent = expiring;
	qs('#wpwaf-si-stat-iss').textContent = withIssues;
	qs('#wpwaf-si-stat-exp-box').classList.toggle('is-warn', expiring > 0);
	qs('#wpwaf-si-stat-iss-box').classList.toggle('is-crit', withIssues > 0);
}

function renderGrid(){
	const list = visibleZones();
	const grid = qs('#wpwaf-si-grid');
	const btn = qs('#wpwaf-si-scan-all');
	const filtered = list.length !== zones.length;
	if (!scanning) btn.textContent = filtered ? '↻ Scan ' + list.length + ' shown zone' + (list.length === 1 ? '' : 's') : '↻ Scan all zones';
	btn.disabled = scanning || !list.length;
	renderSummary();
	if (!zones.length){ grid.innerHTML = '<div class="wpwaf-si-empty" style="grid-column:1/-1;">No active zones found in this account.</div>'; return; }
	if (!list.length){ grid.innerHTML = '<div class="wpwaf-si-empty" style="grid-column:1/-1;">No zones match this filter.</div>'; return; }
	grid.innerHTML = list.map(cardHTML).join('');
}

function replaceCard(zoneId){
	const old = qs('.wpwaf-si-card[data-zone="' + CSS.escape(zoneId) + '"]');
	const z = zones.find(x => x.id === zoneId);
	if (!old || !z) return;
	const tmp = document.createElement('div');
	tmp.innerHTML = cardHTML(z);
	old.replaceWith(tmp.firstElementChild);
	renderSummary();
}

// ── Scanning (three zones at a time) ─────────────────────────────────────
let scanning = false;
function scanZone(zoneId){
	const card = qs('.wpwaf-si-card[data-zone="' + CSS.escape(zoneId) + '"]');
	if (card){
		const when = qs('.wpwaf-si-card-when', card);
		if (when) when.innerHTML = '<span class="wpwaf-si-spinner" style="vertical-align:middle;"></span> Scanning…';
	}
	return ajax('wpwaf_si_zone_scan', {zone_id: zoneId}).then(res => {
		if (res.success && res.data && res.data.scan){
			scans[zoneId] = res.data.scan;
		} else {
			const prev = scans[zoneId];
			scans[zoneId] = {scanned_at: Math.floor(Date.now() / 1000), certs: prev ? prev.certs : null, settings: prev ? prev.settings : {}, errors: {certs: errMsg(res)}};
		}
		replaceCard(zoneId);
		return res.success;
	});
}

function scanMany(ids){
	if (scanning || !ids.length) return;
	scanning = true;
	const btn = qs('#wpwaf-si-scan-all');
	btn.disabled = true; btn.textContent = 'Scanning…';
	const prog = qs('#wpwaf-si-progress'), progText = qs('#wpwaf-si-progress-text');
	prog.classList.add('on');
	let next = 0, done = 0, failed = 0;
	const total = ids.length;
	progText.textContent = '0 of ' + total;
	return new Promise(resolve => {
		function worker(){
			if (next >= total) return Promise.resolve();
			const id = ids[next++];
			return scanZone(id).then(ok => {
				done++; if (!ok) failed++;
				progText.textContent = done + ' of ' + total;
			}).then(worker);
		}
		Promise.all([worker(), worker(), worker()]).then(() => {
			scanning = false;
			prog.classList.remove('on');
			renderGrid();
			toast(failed ? 'Scanned ' + (done - failed) + ' of ' + total + ' zones. ' + failed + ' failed.' : '✓ Scanned ' + total + ' zone' + (total === 1 ? '' : 's'), !failed);
			resolve();
		});
	});
}

qs('#wpwaf-si-scan-all').addEventListener('click', () => scanMany(visibleZones().map(z => z.id)));
qs('#wpwaf-si-search').addEventListener('input', renderGrid);
qs('#wpwaf-si-show').addEventListener('change', renderGrid);

qs('#wpwaf-si-grid').addEventListener('click', e => {
	const one = e.target.closest('.wpwaf-si-scan-one');
	if (one){
		if (scanning) return;
		one.disabled = true;
		scanZone(one.dataset.zone);
		return;
	}
	const fix = e.target.closest('.wpwaf-si-fix');
	if (fix){
		const zoneId = fix.dataset.zone, key = fix.dataset.fix;
		const z = zones.find(x => x.id === zoneId);
		if (FIX_CONFIRM[key] && !confirm(FIX_CONFIRM[key].replace('{zone}', z ? z.name : 'this zone'))) return;
		const label = fix.textContent;
		fix.disabled = true; fix.textContent = '…';
		ajax('wpwaf_si_fix', {zone_id: zoneId, fix: key}).then(res => {
			if (res.success){
				const scan = scans[zoneId];
				if (scan && scan.settings){
					scan.settings[res.data.setting] = Object.assign({}, scan.settings[res.data.setting] || {}, {value: res.data.value});
				}
				replaceCard(zoneId);
				toast('✓ ' + (FIX_DONE[key] || cap(key)) + ' on ' + (z ? z.name : 'zone'));
			} else {
				fix.disabled = false; fix.textContent = label;
				toast(errMsg(res), false);
			}
		});
	}
});

ajax('wpwaf_list_zones', {}).then(res => {
	if (!res.success){
		qs('#wpwaf-si-grid').innerHTML = '<div class="wpwaf-si-notice wpwaf-si-notice--error" style="grid-column:1/-1;">Could not load zones: ' + esc(errMsg(res)) + '</div>';
		return;
	}
	zones = (res.data.zones || []).slice().sort((a, b) => a.name.localeCompare(b.name));
	zones.forEach(z => { zoneNames[z.id] = z.name; });
	renderGrid();
});

// ── Security Insights ────────────────────────────────────────────────────
const SEV_ORDER = {critical:0, moderate:1, low:2};
const SEV_CLS = {critical:'crit', moderate:'warn', low:'info'};
const TYPE_TEXT = {compliance_violation:'Compliance', email_security:'Email security', exposed_infrastructure:'Exposed infrastructure', insecure_configuration:'Insecure configuration', weak_authentication:'Weak authentication', configuration_suggestion:'Suggestion'};
let insights = [];

function loadAccounts(){
	const sel = qs('#wpwaf-si-account');
	ajax('wpwaf_cf_accounts', {}).then(res => {
		if (!res.success){
			sel.innerHTML = '<option value="">No accounts</option>';
			qs('#wpwaf-si-insights').innerHTML = '<div class="wpwaf-si-notice wpwaf-si-notice--error">' + esc(errMsg(res)) + '</div>';
			return;
		}
		const accts = res.data.accounts || [];
		if (!accts.length){
			sel.innerHTML = '<option value="">No accounts</option>';
			qs('#wpwaf-si-insights').innerHTML = '<div class="wpwaf-si-empty">This API token cannot see any Cloudflare accounts.</div>';
			return;
		}
		sel.innerHTML = accts.map(a => '<option value="' + esc(a.id) + '">' + esc(a.name) + '</option>').join('');
		loadInsights();
	});
}

function loadInsights(){
	const account = qs('#wpwaf-si-account').value;
	if (!account) return;
	const box = qs('#wpwaf-si-insights');
	box.innerHTML = '<div class="wpwaf-si-loading"><span class="wpwaf-si-spinner"></span> Loading insights…</div>';
	qs('#wpwaf-si-sevbar').innerHTML = '';
	ajax('wpwaf_si_insights', {account_id: account, view: qs('#wpwaf-si-view').value}).then(res => {
		if (!res.success){
			insights = [];
			box.innerHTML = '<div class="wpwaf-si-notice wpwaf-si-notice--error">' + esc(errMsg(res)) + '</div>';
			return;
		}
		insights = (res.data.issues || []).slice().sort((a, b) => (SEV_ORDER[a.severity] ?? 9) - (SEV_ORDER[b.severity] ?? 9) || String(a.subject).localeCompare(String(b.subject)));
		renderInsights();
	});
}

function renderInsights(){
	const sev = qs('#wpwaf-si-sev').value;
	const q = (qs('#wpwaf-si-isearch').value || '').toLowerCase().trim();
	const counts = {critical:0, moderate:0, low:0};
	insights.forEach(i => { if (counts[i.severity] !== undefined) counts[i.severity]++; });
	qs('#wpwaf-si-sevbar').innerHTML = ['critical','moderate','low'].map(k =>
		'<span class="wpwaf-si-pill wpwaf-si-pill--' + SEV_CLS[k] + '">' + counts[k] + ' ' + cap(k) + '</span>').join('');

	const list = insights.filter(i => {
		if (sev && i.severity !== sev) return false;
		if (q){
			const hay = [i.subject, i.issue_class, i.issue_type, zoneNames[i.zone_tag] || ''].join(' ').toLowerCase();
			if (!hay.includes(q)) return false;
		}
		return true;
	});
	const box = qs('#wpwaf-si-insights');
	if (!insights.length){ box.innerHTML = '<div class="wpwaf-si-empty">No insights here. Nice work.</div>'; return; }
	if (!list.length){ box.innerHTML = '<div class="wpwaf-si-empty">No insights match this filter.</div>'; return; }

	box.innerHTML = '<div class="wpwaf-si-table-wrap"><table class="wpwaf-si-table"><thead><tr>'
		+ '<th>Severity</th><th>Issue</th><th>Subject</th><th>Type</th><th>Since</th><th></th></tr></thead><tbody>'
		+ list.map(i => {
			const since = i.since ? new Date(i.since) : null;
			const sinceTxt = since && !isNaN(since) ? since.toLocaleDateString(undefined, {year:'numeric', month:'short', day:'numeric'}) : '-';
			const zone = zoneNames[i.zone_tag] && zoneNames[i.zone_tag] !== i.subject ? '<div class="wpwaf-si-issue-desc">Zone: ' + esc(zoneNames[i.zone_tag]) + '</div>' : '';
			const how = i.resolve_text ? '<div class="wpwaf-si-issue-desc">' + esc(i.resolve_text)
				+ (i.resolve_link ? ' <a href="' + esc(i.resolve_link) + '" target="_blank" rel="noopener">Open in Cloudflare</a>' : '') + '</div>' : '';
			return '<tr' + (i.dismissed ? ' class="is-dismissed"' : '') + '>'
				+ '<td data-label="Severity"><span class="wpwaf-si-pill wpwaf-si-pill--' + (SEV_CLS[i.severity] || '') + '">' + esc(cap(i.severity) || 'Unknown') + '</span></td>'
				+ '<td><div class="wpwaf-si-issue-title">' + esc(cap(i.issue_class) || 'Issue') + '</div>' + how + '</td>'
				+ '<td data-label="Subject"><span class="wpwaf-si-subject">' + esc(i.subject || '-') + '</span>' + zone + '</td>'
				+ '<td data-label="Type">' + esc(TYPE_TEXT[i.issue_type] || cap(i.issue_type) || '-') + '</td>'
				+ '<td data-label="Since">' + esc(sinceTxt) + '</td>'
				+ '<td class="wpwaf-si-cell-act"><button type="button" class="wpwaf-si-btn wpwaf-si-btn--sm wpwaf-si-dismiss" data-id="' + esc(i.id) + '" data-dismiss="' + (i.dismissed ? '0' : '1') + '">' + (i.dismissed ? 'Restore' : 'Dismiss') + '</button></td>'
				+ '</tr>';
		}).join('')
		+ '</tbody></table></div>';
}

qs('#wpwaf-si-account').addEventListener('change', loadInsights);
qs('#wpwaf-si-view').addEventListener('change', loadInsights);
qs('#wpwaf-si-irefresh').addEventListener('click', loadInsights);
qs('#wpwaf-si-sev').addEventListener('change', renderInsights);
qs('#wpwaf-si-isearch').addEventListener('input', renderInsights);
qs('#wpwaf-si-insights').addEventListener('click', e => {
	const b = e.target.closest('.wpwaf-si-dismiss');
	if (!b) return;
	const id = b.dataset.id, dismiss = b.dataset.dismiss === '1';
	b.disabled = true; b.textContent = '…';
	ajax('wpwaf_si_dismiss', {account_id: qs('#wpwaf-si-account').value, issue_id: id, dismiss: dismiss ? '1' : '0'}).then(res => {
		if (!res.success){
			b.disabled = false; b.textContent = dismiss ? 'Dismiss' : 'Restore';
			toast(errMsg(res), false);
			return;
		}
		const view = qs('#wpwaf-si-view').value;
		insights = insights.map(i => i.id === id ? Object.assign({}, i, {dismissed: dismiss}) : i)
			.filter(i => view === 'all' || (view === 'dismissed' ? i.dismissed : !i.dismissed));
		renderInsights();
		toast(dismiss ? '✓ Insight dismissed' : '✓ Insight restored');
	});
});

if (location.hash === '#insights') showTab('insights');
})();
</script>
