<?php
defined( 'ABSPATH' ) || exit;
/**
 * Zone Status detail view for one zone.
 * Vars from WPWAF_Admin::render_zone_status_page(): $zone, $zone_id, $range,
 * $cache, $has_creds, $nonce, $ajax_url.
 */
$wpwaf_back   = admin_url( 'admin.php?page=wpwafmanager-zone-status' );
$wpwaf_others = [];
foreach ( $cache as $wpwaf_id => $wpwaf_entry ) {
	if ( ! empty( $wpwaf_entry['zone']['name'] ) ) {
		$wpwaf_others[ $wpwaf_id ] = (string) $wpwaf_entry['zone']['name'];
	}
}
asort( $wpwaf_others );
?>
<style>
:root{--zd-orange:#FF6A00;--zd-border:#e2e6ea;--zd-bg:#f8f9fb;--zd-dark:#1a1a2e;--zd-muted:#6b7280;--zd-ink2:#52514e;--zd-grid:#e1e0d9;--zd-axis:#c3c2b7;
  --zd-s1:#2a78d6;--zd-s2:#eb6834;--zd-red:#e34948;--zd-good:#006300;--zd-bad:#b42318;}
.cfwaf-zd-wrap{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;color:var(--zd-dark);}
.cfwaf-zd-wrap *{box-sizing:border-box;}
.cfwaf-zd-back{display:inline-flex;align-items:center;gap:4px;font-size:12px;font-weight:600;color:var(--zd-muted);text-decoration:none;margin-bottom:10px;}
.cfwaf-zd-back:hover{color:var(--zd-orange);}
.cfwaf-zd-header{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:16px;}
.cfwaf-zd-header h1{font-size:22px;font-weight:700;margin:0;display:flex;align-items:center;gap:8px;flex-wrap:wrap;line-height:1.3;overflow-wrap:anywhere;}
.cfwaf-zd-header h1 .dashicons{color:var(--zd-orange);font-size:24px;width:24px;height:24px;}
.cfwaf-zd-sub{font-size:12px;color:var(--zd-muted);margin-top:3px;}
.cfwaf-zd-plan{font-size:10px;font-weight:600;padding:2px 8px;border-radius:10px;background:#f1f5f9;color:#64748b;border:1px solid #e2e8f0;white-space:nowrap;}
.cfwaf-zd-head-actions{display:flex;gap:8px;flex-wrap:wrap;align-items:center;}

/* Controls: one row above everything they scope */
.cfwaf-zd-controls{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:18px;}
.cfwaf-zd-seg{display:inline-flex;background:#fff;border:1px solid var(--zd-border);border-radius:8px;padding:3px;gap:2px;flex-wrap:wrap;}
.cfwaf-zd-seg button{border:none;background:none;padding:6px 12px;border-radius:6px;font-size:12px;font-weight:600;color:var(--zd-ink2);cursor:pointer;white-space:nowrap;}
.cfwaf-zd-seg button:hover{background:var(--zd-bg);}
.cfwaf-zd-seg button[aria-pressed="true"]{background:var(--zd-dark);color:#fff;}
.cfwaf-zd-select{padding:6px 10px;border:1px solid var(--zd-border);border-radius:6px;font-size:13px;background:#fff;color:var(--zd-dark);max-width:100%;}
.cfwaf-zd-btn{display:inline-flex;align-items:center;gap:6px;padding:7px 12px;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;border:1px solid var(--zd-border);background:#fff;color:var(--zd-dark);text-decoration:none;white-space:nowrap;}
.cfwaf-zd-btn:hover{background:var(--zd-bg);color:var(--zd-dark);}
.cfwaf-zd-btn-primary{background:var(--zd-orange);border-color:var(--zd-orange);color:#fff;}
.cfwaf-zd-btn-primary:hover{background:#d95500;color:#fff;}
.cfwaf-zd-btn:disabled{opacity:.5;cursor:not-allowed;}
.cfwaf-zd-meta{margin-left:auto;font-size:11px;color:var(--zd-muted);}

/* Stat tiles */
.cfwaf-zd-tiles{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:18px;}
.cfwaf-zd-tile{background:#fff;border:1px solid var(--zd-border);border-radius:10px;padding:14px 16px;min-width:0;}
.cfwaf-zd-tile-label{font-size:12px;color:var(--zd-ink2);font-weight:600;}
.cfwaf-zd-tile-value{font-size:24px;font-weight:700;margin-top:4px;letter-spacing:-.3px;line-height:1.15;overflow-wrap:anywhere;}
.cfwaf-zd-tile-delta{font-size:11px;margin-top:6px;color:var(--zd-muted);display:flex;align-items:center;gap:4px;flex-wrap:wrap;}
.cfwaf-zd-up-good,.cfwaf-zd-down-good{color:var(--zd-good);font-weight:700;}
.cfwaf-zd-up-bad,.cfwaf-zd-down-bad{color:var(--zd-bad);font-weight:700;}
.cfwaf-zd-tile-hint{font-size:11px;color:var(--zd-muted);margin-top:6px;line-height:1.4;}

/* Cards and charts */
.cfwaf-zd-section-title{font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--zd-muted);margin:22px 0 10px;}
.cfwaf-zd-grid2{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;}
.cfwaf-zd-grid3{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;}
.cfwaf-zd-card{background:#fff;border:1px solid var(--zd-border);border-radius:10px;padding:14px 16px 12px;min-width:0;position:relative;}
.cfwaf-zd-card-head{display:flex;align-items:flex-start;justify-content:space-between;gap:10px;margin-bottom:8px;}
.cfwaf-zd-card-title{font-size:14px;font-weight:700;margin:0;}
.cfwaf-zd-card-sub{font-size:11px;color:var(--zd-muted);margin-top:2px;line-height:1.4;}
.cfwaf-zd-link{background:none;border:none;color:var(--zd-ink2);font-size:11px;font-weight:600;cursor:pointer;padding:2px 4px;white-space:nowrap;text-decoration:underline;text-underline-offset:2px;}
.cfwaf-zd-link:hover{color:var(--zd-orange);}
.cfwaf-zd-legend{display:flex;gap:14px;flex-wrap:wrap;font-size:11px;color:var(--zd-ink2);margin:0 0 6px;}
.cfwaf-zd-legend span{display:inline-flex;align-items:center;gap:6px;}
.cfwaf-zd-legend i{display:inline-block;width:10px;height:10px;border-radius:2px;}
.cfwaf-zd-legend i.line{height:2px;width:14px;border-radius:2px;}
.cfwaf-zd-chart{position:relative;width:100%;height:220px;}
.cfwaf-zd-chart svg{display:block;width:100%;height:100%;overflow:visible;}
.cfwaf-zd-chart .tick{font-size:10px;fill:#898781;font-variant-numeric:tabular-nums;}
.cfwaf-zd-tip{position:absolute;pointer-events:none;background:#fff;border:1px solid var(--zd-border);border-radius:8px;box-shadow:0 6px 20px rgba(0,0,0,.10);padding:8px 10px;font-size:12px;min-width:140px;z-index:5;display:none;}
.cfwaf-zd-tip-title{font-size:11px;color:var(--zd-muted);margin-bottom:4px;}
.cfwaf-zd-tip-row{display:flex;align-items:center;gap:8px;justify-content:space-between;}
.cfwaf-zd-tip-row b{font-variant-numeric:tabular-nums;}
.cfwaf-zd-tip-key{display:inline-flex;align-items:center;gap:6px;color:var(--zd-ink2);}
.cfwaf-zd-tip-key i{display:inline-block;width:12px;height:2px;border-radius:2px;}
.cfwaf-zd-table{width:100%;border-collapse:collapse;font-size:12px;}
.cfwaf-zd-table th{text-align:left;font-size:10px;text-transform:uppercase;letter-spacing:.05em;color:var(--zd-muted);padding:6px 6px;border-bottom:1px solid var(--zd-border);}
.cfwaf-zd-table td{padding:6px 6px;border-bottom:1px solid #f1f3f5;font-variant-numeric:tabular-nums;overflow-wrap:anywhere;}
.cfwaf-zd-table-wrap{max-height:260px;overflow:auto;}

/* Ranked bar lists */
.cfwaf-zd-bars{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:7px;}
.cfwaf-zd-bar{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:2px 10px;font-size:12px;align-items:center;}
.cfwaf-zd-bar-label{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--zd-dark);}
.cfwaf-zd-bar-label.mono{font-family:SFMono-Regular,Menlo,Consolas,monospace;font-size:11px;}
.cfwaf-zd-bar-value{font-variant-numeric:tabular-nums;color:var(--zd-ink2);font-size:11px;white-space:nowrap;}
.cfwaf-zd-bar-track{grid-column:1 / -1;height:6px;background:#eef0f3;border-radius:4px;overflow:hidden;}
.cfwaf-zd-bar-fill{height:100%;background:var(--zd-s1);border-radius:0 4px 4px 0;min-width:2px;}
.cfwaf-zd-bar-fill.red{background:var(--zd-red);}
.cfwaf-zd-empty{font-size:12px;color:var(--zd-muted);padding:10px 0;}
.cfwaf-zd-note{font-size:11px;color:#92400e;background:#fffbeb;border:1px solid #fde68a;border-radius:6px;padding:6px 9px;margin:0 0 10px;line-height:1.45;}
.cfwaf-zd-unavail{background:#fff;border:1px dashed var(--zd-border);border-radius:10px;padding:16px;font-size:12px;color:var(--zd-muted);line-height:1.5;}
.cfwaf-zd-error{background:#fef2f2;border:1px solid #fca5a5;border-radius:8px;padding:14px 16px;color:#7f1d1d;font-size:13px;margin-bottom:16px;}
.cfwaf-zd-no-creds{background:#fff8f5;border:1px solid #fcd9c0;border-radius:8px;padding:20px 24px;color:#92400e;}
.cfwaf-zd-body.is-loading{opacity:.45;transition:opacity .2s;pointer-events:none;}
.cfwaf-zd-loading{padding:60px 20px;text-align:center;color:var(--zd-muted);font-size:13px;}
.cfwaf-zd-spinner{display:inline-block;width:14px;height:14px;border:2px solid rgba(255,106,0,.3);border-top-color:var(--zd-orange);border-radius:50%;animation:zd-spin .6s linear infinite;vertical-align:-2px;}
@keyframes zd-spin{to{transform:rotate(360deg);}}

@media (max-width:1200px){ .cfwaf-zd-grid3{grid-template-columns:repeat(2,minmax(0,1fr));} }
@media (max-width:960px){ .cfwaf-zd-tiles{grid-template-columns:repeat(2,minmax(0,1fr));} .cfwaf-zd-grid2{grid-template-columns:minmax(0,1fr);} }
@media (max-width:782px){
  .cfwaf-zd-grid3{grid-template-columns:minmax(0,1fr);}
  .cfwaf-zd-meta{margin-left:0;width:100%;}
  .cfwaf-zd-chart{height:190px;}
}
@media (max-width:480px){
  .cfwaf-zd-tiles{gap:8px;}
  .cfwaf-zd-tile{padding:12px;}
  .cfwaf-zd-tile-value{font-size:20px;}
  .cfwaf-zd-seg{width:100%;}
  .cfwaf-zd-seg button{flex:1 1 auto;padding:6px 8px;}
}
</style>

<div class="cfwaf-zs-wrap cfwaf-zd-wrap">
	<a class="cfwaf-zd-back" href="<?php echo esc_url( $wpwaf_back ); ?>">← All zones</a>
	<div class="cfwaf-zd-header">
		<div style="min-width:0;">
			<h1><span class="dashicons dashicons-chart-area"></span> <span id="cfwaf-zd-name"><?php echo esc_html( $zone['name'] ?: 'Zone analytics' ); ?></span>
				<span class="cfwaf-zd-plan" id="cfwaf-zd-plan"<?php echo empty( $zone['plan'] ) ? ' style="display:none;"' : ''; ?>><?php echo esc_html( (string) ( $zone['plan'] ?? '' ) ); ?></span></h1>
			<div class="cfwaf-zd-sub">Traffic, caching, visitors and security for this zone, straight from Cloudflare</div>
		</div>
		<div class="cfwaf-zd-head-actions">
			<?php if ( count( $wpwaf_others ) > 1 ) : ?>
			<select class="cfwaf-zd-select" id="cfwaf-zd-switch" aria-label="Switch zone">
				<?php foreach ( $wpwaf_others as $wpwaf_id => $wpwaf_name ) : ?>
				<option value="<?php echo esc_attr( $wpwaf_id ); ?>"<?php selected( $wpwaf_id, $zone_id ); ?>><?php echo esc_html( $wpwaf_name ); ?></option>
				<?php endforeach; ?>
			</select>
			<?php endif; ?>
			<?php if ( ! WPWAF_Settings::get( 'hide_reports', false ) ) :
				$wpwaf_clients = WPWAF_Reports::clients_for_zone( $zone_id );
				$wpwaf_rp_url  = admin_url( 'admin.php?page=wpwafmanager-reports' );
				if ( $wpwaf_clients ) :
					foreach ( array_slice( $wpwaf_clients, 0, 3 ) as $wpwaf_c ) : ?>
			<a class="cfwaf-zd-btn" href="<?php echo esc_url( add_query_arg( [ 'client' => $wpwaf_c['id'], 'send' => '1' ], $wpwaf_rp_url ) ); ?>" title="<?php echo esc_attr( $wpwaf_c['monthly'] ? 'Gets a monthly report automatically' : 'Reports sent by hand' ); ?>">✉ Send report to <?php echo esc_html( $wpwaf_c['name'] ); ?></a>
					<?php endforeach;
				else : ?>
			<a class="cfwaf-zd-btn" href="<?php echo esc_url( add_query_arg( [ 'zone' => $zone_id, 'zname' => (string) ( $zone['name'] ?? '' ) ], $wpwaf_rp_url ) ); ?>">+ Add a client for this site</a>
				<?php endif;
			endif; ?>
		</div>
	</div>

	<?php if ( ! $has_creds ) : ?>
	<div class="cfwaf-zd-no-creds"><strong>No Cloudflare account connected.</strong> Please <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpwafmanager' ) ); ?>">add your credentials</a> first.</div>
	<?php else : ?>

	<div class="cfwaf-zd-controls">
		<div class="cfwaf-zd-seg" role="group" aria-label="Time range" id="cfwaf-zd-range">
			<?php foreach ( WPWAF_Analytics::RANGES as $wpwaf_key => $wpwaf_label ) : ?>
			<button type="button" data-range="<?php echo esc_attr( $wpwaf_key ); ?>" aria-pressed="<?php echo $wpwaf_key === $range ? 'true' : 'false'; ?>"><?php echo esc_html( $wpwaf_label ); ?></button>
			<?php endforeach; ?>
		</div>
		<button type="button" class="cfwaf-zd-btn" id="cfwaf-zd-refresh">↻ Refresh</button>
		<button type="button" class="cfwaf-zd-btn" id="cfwaf-zd-csv" disabled>⬇ CSV</button>
		<span class="cfwaf-zd-meta" id="cfwaf-zd-meta"></span>
	</div>

	<div id="cfwaf-zd-body" class="cfwaf-zd-body">
		<div class="cfwaf-zd-loading"><span class="cfwaf-zd-spinner"></span> Loading analytics from Cloudflare…</div>
	</div>
	<?php endif; ?>
</div>

<?php if ( $has_creds ) : ?>
<script>
'use strict';
(function(){
const NONCE    = <?php echo wp_json_encode( $nonce ); ?>;
const AJAX_URL = <?php echo wp_json_encode( $ajax_url ); ?>;
const ZONE_ID  = <?php echo wp_json_encode( $zone_id ); ?>;
const BASE_URL = <?php echo wp_json_encode( admin_url( 'admin.php?page=wpwafmanager-zone-status' ) ); ?>;
const RANGES   = <?php echo wp_json_encode( WPWAF_Analytics::RANGES ); ?>;
let range  = <?php echo wp_json_encode( $range ); ?>;
let data   = null;
let zoneName = <?php echo wp_json_encode( (string) ( $zone['name'] ?? '' ) ); ?>;

const C = { s1:'#2a78d6', s2:'#eb6834', red:'#e34948', grid:'#e1e0d9', axis:'#c3c2b7', surface:'#ffffff' };
const qs  = (s,c) => (c||document).querySelector(s);
const qsa = (s,c) => Array.from((c||document).querySelectorAll(s));
function esc(v){ return String(v == null ? '' : v).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }
function ajax(action, d){
  const fd = new FormData();
  fd.append('action', action); fd.append('nonce', NONCE);
  Object.entries(d || {}).forEach(([k,v]) => fd.append(k, v));
  return fetch(AJAX_URL, {method:'POST', body:fd, credentials:'same-origin'}).then(r => r.json()).catch(e => ({success:false, data:{message:e.message}}));
}

// ── Formatting ───────────────────────────────────────────────────────────────
function num(n){
  n = Number(n) || 0;
  const a = Math.abs(n);
  if (a >= 1e9) return (n/1e9).toFixed(a >= 1e10 ? 0 : 1).replace(/\.0$/,'') + 'B';
  if (a >= 1e6) return (n/1e6).toFixed(a >= 1e7 ? 0 : 1).replace(/\.0$/,'') + 'M';
  if (a >= 1e3) return (n/1e3).toFixed(a >= 1e4 ? 0 : 1).replace(/\.0$/,'') + 'K';
  return String(Math.round(n));
}
function full(n){ return (Number(n) || 0).toLocaleString(); }
function bytes(b){
  b = Number(b) || 0;
  const u = ['B','KB','MB','GB','TB','PB']; let i = 0;
  while (b >= 1024 && i < u.length - 1){ b /= 1024; i++; }
  return (b >= 100 || i === 0 ? Math.round(b) : b.toFixed(1).replace(/\.0$/,'')) + ' ' + u[i];
}
function pct(n){ return (Math.round((Number(n) || 0) * 10) / 10) + '%'; }
const regionNames = (typeof Intl !== 'undefined' && Intl.DisplayNames) ? new Intl.DisplayNames(undefined, {type:'region'}) : null;
function country(code){
  code = String(code || '').toUpperCase();
  if (code === 'T1') return 'Tor network';
  if (!/^[A-Z]{2}$/.test(code) || code === 'XX') return 'Unknown';
  let name = code;
  try { name = regionNames ? regionNames.of(code) : code; } catch (e) { name = code; }
  const flag = String.fromCodePoint(...[...code].map(c => 0x1F1E6 + c.charCodeAt(0) - 65));
  return flag + ' ' + name;
}
// Friendlier names for Cloudflare's IP class and threat labels; unknown values show as sent.
const IP_CLASS = {clean:'Clean', badHost:'Known bad host', tor:'Tor', searchEngine:'Search engine', whitelist:'Allowlisted', greylist:'Greylisted', monitoringService:'Monitoring service', securityScanner:'Security scanner', backupService:'Backup service', mobilePlatform:'Mobile platform', noRecord:'No record', unknown:'Unknown'};
const THREAT = {'bad.ip':'Bad IP reputation', 'user.ban.ip':'Blocked by your IP rules'};
function tLabel(t, gran, short){
  if (!t) return '';
  const d = new Date(gran === 'hour' ? t : t + 'T00:00:00Z');
  if (isNaN(d)) return t;
  if (gran === 'hour') return d.toLocaleTimeString(undefined, {hour:'numeric'}) + (short ? '' : ', ' + d.toLocaleDateString(undefined, {month:'short', day:'numeric'}));
  return d.toLocaleDateString(undefined, {month:'short', day:'numeric', timeZone:'UTC'});
}
function niceMax(v){
  if (v <= 0) return 1;
  const p = Math.pow(10, Math.floor(Math.log10(v))); const f = v / p;
  return (f <= 1 ? 1 : f <= 2 ? 2 : f <= 2.5 ? 2.5 : f <= 5 ? 5 : 10) * p;
}

// ── Charts (SVG, one axis, recessive grid, crosshair tooltip) ───────────────
// spec: { labels:[t...], gran, series:[{name,color,values}], type:'bar'|'line', stacked, fmt }
function chart(el, spec){
  el.innerHTML = '';
  const W = Math.max(260, el.clientWidth), H = el.clientHeight || 220;
  const m = {t:8, r:8, b:22, l:44};
  const iw = W - m.l - m.r, ih = H - m.t - m.b;
  const n = spec.labels.length;
  if (!n){ el.innerHTML = '<div class="cfwaf-zd-empty">No data for this period.</div>'; return; }
  const totals = spec.labels.map((_, i) => spec.stacked ? spec.series.reduce((a, s) => a + (s.values[i] || 0), 0) : Math.max(...spec.series.map(s => s.values[i] || 0)));
  const top = Math.max(...totals, 0);
  // Byte axes step in 1024s so ticks read as round GB/MB values.
  const max = spec.fmt === bytes && top > 0
    ? (() => { const k = Math.min(5, Math.floor(Math.log(top) / Math.log(1024))); const u = Math.pow(1024, k); return niceMax(top / u) * u; })()
    : niceMax(top);
  const y = v => m.t + ih - (v / max) * ih;
  const band = iw / n;
  const x = i => m.l + band * i + band / 2;
  const NS = 'http://www.w3.org/2000/svg';
  const svg = document.createElementNS(NS, 'svg');
  svg.setAttribute('viewBox', `0 0 ${W} ${H}`);
  svg.setAttribute('role', 'img');
  svg.setAttribute('aria-label', spec.title || 'Chart');
  const add = (tag, attrs, parent) => { const e = document.createElementNS(NS, tag); Object.entries(attrs).forEach(([k,v]) => e.setAttribute(k, v)); (parent || svg).appendChild(e); return e; };

  // Grid and y ticks: 0, 1/2, max
  [0, 0.5, 1].forEach(f => {
    const v = max * f, yy = y(v);
    add('line', {x1:m.l, x2:W - m.r, y1:yy, y2:yy, stroke: f === 0 ? C.axis : C.grid, 'stroke-width':1});
    const t = add('text', {x:m.l - 6, y:yy + 3, 'text-anchor':'end', class:'tick'}); t.textContent = spec.fmt(v);
  });
  // X labels: at most ~7, evenly spaced
  const step = Math.max(1, Math.ceil(n / Math.max(2, Math.floor(iw / 70))));
  for (let i = 0; i < n; i += step){
    const t = add('text', {x:x(i), y:H - 6, 'text-anchor':'middle', class:'tick'}); t.textContent = tLabel(spec.labels[i], spec.gran, true);
  }

  if (spec.type === 'bar'){
    const bw = Math.max(2, Math.min(24, band * 0.7));
    for (let i = 0; i < n; i++){
      let base = 0;
      spec.series.forEach((s, si) => {
        const v = s.values[i] || 0; if (v <= 0) return;
        const y0 = y(base), y1 = y(base + v);
        const h = Math.max(1, y0 - y1 - (spec.stacked && si > 0 ? 2 : 0));
        const top = spec.stacked && si < spec.series.length - 1 && spec.series.slice(si + 1).some(o => (o.values[i] || 0) > 0);
        const r = top ? 0 : Math.min(4, bw / 2, h);
        // Rounded data-end only, square at the baseline.
        const x0 = x(i) - bw / 2, yy = y1 + (spec.stacked && si > 0 ? 0 : 0);
        const d = r > 0
          ? `M${x0},${yy + h} V${yy + r} Q${x0},${yy} ${x0 + r},${yy} H${x0 + bw - r} Q${x0 + bw},${yy} ${x0 + bw},${yy + r} V${yy + h} Z`
          : `M${x0},${yy + h} V${yy} H${x0 + bw} V${yy + h} Z`;
        add('path', {d, fill:s.color});
        base += v;
      });
    }
  } else {
    spec.series.forEach(s => {
      const pts = s.values.map((v, i) => `${x(i)},${y(v || 0)}`).join(' ');
      add('polyline', {points:pts, fill:'none', stroke:s.color, 'stroke-width':2, 'stroke-linejoin':'round', 'stroke-linecap':'round'});
    });
  }

  // Crosshair + tooltip on a full-height hit band per x.
  const cross = add('line', {x1:0, x2:0, y1:m.t, y2:m.t + ih, stroke:'#898781', 'stroke-width':1, visibility:'hidden'});
  const dots = spec.type === 'line' ? spec.series.map(s => add('circle', {r:4, fill:s.color, stroke:C.surface, 'stroke-width':2, visibility:'hidden'})) : [];
  const tip = document.createElement('div'); tip.className = 'cfwaf-zd-tip'; el.appendChild(tip);
  const show = i => {
    cross.setAttribute('x1', x(i)); cross.setAttribute('x2', x(i)); cross.setAttribute('visibility', 'visible');
    dots.forEach((d, si) => { d.setAttribute('cx', x(i)); d.setAttribute('cy', y(spec.series[si].values[i] || 0)); d.setAttribute('visibility', 'visible'); });
    tip.innerHTML = '';
    const tt = document.createElement('div'); tt.className = 'cfwaf-zd-tip-title'; tt.textContent = tLabel(spec.labels[i], spec.gran, false); tip.appendChild(tt);
    spec.series.forEach(s => {
      const row = document.createElement('div'); row.className = 'cfwaf-zd-tip-row';
      const k = document.createElement('span'); k.className = 'cfwaf-zd-tip-key';
      const sw = document.createElement('i'); sw.style.background = s.color; k.appendChild(sw); k.appendChild(document.createTextNode(s.name));
      const b = document.createElement('b'); b.textContent = spec.fmtFull ? spec.fmtFull(s.values[i] || 0) : spec.fmt(s.values[i] || 0);
      row.appendChild(k); row.appendChild(b); tip.appendChild(row);
    });
    tip.style.display = 'block';
    const tw = tip.offsetWidth, left = x(i) + 12 + tw > W ? x(i) - tw - 12 : x(i) + 12;
    tip.style.left = Math.max(0, left) + 'px'; tip.style.top = m.t + 'px';
  };
  const hide = () => { cross.setAttribute('visibility', 'hidden'); dots.forEach(d => d.setAttribute('visibility', 'hidden')); tip.style.display = 'none'; };
  for (let i = 0; i < n; i++){
    const hit = add('rect', {x:m.l + band * i, y:m.t, width:band, height:ih, fill:'transparent', tabindex:'0'});
    hit.addEventListener('pointerenter', () => show(i));
    hit.addEventListener('focus', () => show(i));
    hit.addEventListener('blur', hide);
  }
  svg.addEventListener('pointerleave', hide);
  el.insertBefore(svg, tip);
}

function legend(items, line){
  return '<div class="cfwaf-zd-legend">' + items.map(([name, color]) => '<span><i class="' + (line ? 'line' : '') + '" style="background:' + color + '"></i>' + esc(name) + '</span>').join('') + '</div>';
}

function bars(rows, opts){
  opts = opts || {};
  if (!rows || !rows.length) return '<div class="cfwaf-zd-empty">' + esc(opts.empty || 'No data for this period.') + '</div>';
  const vk = opts.value || 'requests';
  const total = opts.total || rows.reduce((a, r) => a + (Number(r[vk]) || 0), 0);
  const max = Math.max(...rows.map(r => Number(r[vk]) || 0), 1);
  return '<ul class="cfwaf-zd-bars">' + rows.slice(0, opts.limit || 10).map(r => {
    const v = Number(r[vk]) || 0;
    const share = total > 0 ? ' · ' + pct(v / total * 100) : '';
    const label = opts.label ? opts.label(r) : r.key;
    return '<li class="cfwaf-zd-bar" title="' + esc(label + ': ' + full(v)) + '"><span class="cfwaf-zd-bar-label' + (opts.mono ? ' mono' : '') + '">' + esc(label) + '</span>'
      + '<span class="cfwaf-zd-bar-value">' + esc(opts.fmt ? opts.fmt(v) : num(v)) + share + '</span>'
      + '<span class="cfwaf-zd-bar-track"><span class="cfwaf-zd-bar-fill' + (opts.red ? ' red' : '') + '" style="width:' + (v / max * 100).toFixed(1) + '%"></span></span></li>';
  }).join('') + '</ul>';
}

function card(title, sub, inner, opts){
  opts = opts || {};
  return '<div class="cfwaf-zd-card"' + (opts.id ? ' id="' + opts.id + '"' : '') + '><div class="cfwaf-zd-card-head"><div><h3 class="cfwaf-zd-card-title">' + esc(title) + '</h3>'
    + (sub ? '<div class="cfwaf-zd-card-sub">' + esc(sub) + '</div>' : '') + '</div>'
    + (opts.table ? '<button type="button" class="cfwaf-zd-link" data-table="' + opts.table + '">Table</button>' : '') + '</div>' + inner + '</div>';
}

// ── Stat tiles ──────────────────────────────────────────────────────────────
function delta(cur, prev, upGood, isRate){
  if (prev === null || prev === undefined) return '';
  const prevLabel = 'vs previous ' + ({'24h':'24 hours','7d':'7 days','30d':'30 days','month':'month'}[range] || 'period');
  if (isRate){
    const d = Math.round((cur - prev) * 10) / 10;
    if (!prev && !cur) return '<div class="cfwaf-zd-tile-delta">No change ' + prevLabel + '</div>';
    const cls = d === 0 ? '' : ((d > 0) === upGood ? 'cfwaf-zd-up-good' : 'cfwaf-zd-up-bad');
    return '<div class="cfwaf-zd-tile-delta"><span class="' + cls + '">' + (d > 0 ? '▲ +' : d < 0 ? '▼ ' : '') + d + ' pts</span> ' + prevLabel + '</div>';
  }
  if (!prev){ return cur ? '<div class="cfwaf-zd-tile-delta">New this period</div>' : '<div class="cfwaf-zd-tile-delta">No change ' + prevLabel + '</div>'; }
  const p = (cur - prev) / prev * 100, r = Math.round(p * 10) / 10;
  const cls = r === 0 ? '' : ((r > 0) === upGood ? 'cfwaf-zd-up-good' : 'cfwaf-zd-up-bad');
  return '<div class="cfwaf-zd-tile-delta"><span class="' + cls + '">' + (r > 0 ? '▲ +' : r < 0 ? '▼ ' : '') + r + '%</span> ' + prevLabel + '</div>';
}
function tile(label, value, deltaHtml, hint){
  return '<div class="cfwaf-zd-tile"><div class="cfwaf-zd-tile-label">' + esc(label) + '</div><div class="cfwaf-zd-tile-value">' + esc(value) + '</div>' + (deltaHtml || '') + (hint ? '<div class="cfwaf-zd-tile-hint">' + esc(hint) + '</div>' : '') + '</div>';
}

// ── Render ──────────────────────────────────────────────────────────────────
const charts = {};   // id -> spec, re-drawn on resize
const tables = {};   // id -> {head, rows}

function render(){
  const body = qs('#cfwaf-zd-body');
  const tr = data.traffic || {}, sec = data.security || {}, top = data.top || {};
  let html = '';

  if (tr.ok){
    const t = tr.totals, p = tr.previous || {};
    if (tr.note) html += '<div class="cfwaf-zd-note">' + esc(tr.note) + '</div>';
    html += '<div class="cfwaf-zd-tiles">'
      + tile('Requests', num(t.requests), delta(t.requests, p.requests, true))
      + tile('Unique visitors', num(t.uniques), delta(t.uniques, p.uniques, true))
      + tile('Page views', num(t.page_views), delta(t.page_views, p.page_views, true))
      + tile('Bandwidth', bytes(t.bytes), delta(t.bytes, p.bytes, true))
      + tile('Served from cache', pct(t.cache_rate), delta(t.cache_rate, p.cache_rate, true, true), full(t.cached) + ' of ' + full(t.requests) + ' requests')
      + tile('Bandwidth saved', bytes(t.cached_bytes), '', 'Data Cloudflare served so your server did not have to')
      + tile('Threats stopped', num(t.threats), delta(t.threats, p.threats, false))
      + tile('Encrypted traffic', pct(t.ssl_rate), '', 'Requests made over HTTPS')
      + '</div>';

    const labels = tr.series.map(r => r.t), gran = tr.granularity;
    const val = k => tr.series.map(r => Number(r[k]) || 0);
    charts.req = {title:'Requests', labels, gran, type:'bar', stacked:true, fmt:num, fmtFull:full, series:[
      {name:'Cached', color:C.s1, values:val('cached')},
      {name:'Not cached', color:C.s2, values:tr.series.map(r => Math.max(0, (r.requests||0) - (r.cached||0)))} ]};
    charts.bw = {title:'Bandwidth', labels, gran, type:'bar', stacked:true, fmt:bytes, series:[
      {name:'Cached', color:C.s1, values:val('cached_bytes')},
      {name:'Not cached', color:C.s2, values:tr.series.map(r => Math.max(0, (r.bytes||0) - (r.cached_bytes||0)))} ]};
    charts.vis = {title:'Visitors and page views', labels, gran, type:'line', stacked:false, fmt:num, fmtFull:full, series:[
      {name:'Unique visitors', color:C.s1, values:val('uniques')},
      {name:'Page views', color:C.s2, values:val('page_views')} ]};
    charts.thr = {title:'Threats', labels, gran, type:'bar', stacked:false, fmt:num, fmtFull:full, series:[
      {name:'Threats', color:C.red, values:val('threats')} ]};
    Object.keys(charts).forEach(id => {
      tables[id] = {head:['Time'].concat(charts[id].series.map(s => s.name)), rows:labels.map((l, i) => [tLabel(l, gran, false)].concat(charts[id].series.map(s => (charts[id].fmtFull || charts[id].fmt)(s.values[i] || 0))))};
    });

    html += '<div class="cfwaf-zd-section-title">Traffic</div><div class="cfwaf-zd-grid2">'
      + card('Requests', 'Every request Cloudflare handled, split by whether it came from cache', legend([['Cached', C.s1], ['Not cached', C.s2]]) + '<div class="cfwaf-zd-chart" data-chart="req"></div>', {table:'req'})
      + card('Bandwidth', 'Data sent to visitors', legend([['Cached', C.s1], ['Not cached', C.s2]]) + '<div class="cfwaf-zd-chart" data-chart="bw"></div>', {table:'bw'})
      + card('Visitors and page views', 'Unique visitors are counted per ' + (gran === 'hour' ? 'hour' : 'day'), legend([['Unique visitors', C.s1], ['Page views', C.s2]], true) + '<div class="cfwaf-zd-chart" data-chart="vis"></div>', {table:'vis'})
      + card('Threats', 'Requests Cloudflare identified as threats', '<div class="cfwaf-zd-chart" data-chart="thr"></div>', {table:'thr'})
      + '</div>';

    if (tr.maps_missing){
      html += '<div class="cfwaf-zd-note" style="margin-top:14px;">Country, status code and browser breakdowns are not available for this zone.</div>';
    } else {
      const statusGroups = {};
      (tr.statuses || []).forEach(s => { const g = String(s.key).charAt(0) + 'xx'; statusGroups[g] = (statusGroups[g] || 0) + (s.requests || 0); });
      const statusRows = Object.keys(statusGroups).sort().map(k => ({key:k, requests:statusGroups[k]}));
      const STATUS_HINT = {'2xx':'Success', '3xx':'Redirect', '4xx':'Client error', '5xx':'Server error', '1xx':'Informational'};
      html += '<div class="cfwaf-zd-section-title">Audience</div><div class="cfwaf-zd-grid3">'
        + card('Top countries', 'Requests by visitor country', bars(tr.countries, {label:r => country(r.key)}))
        + card('Browsers', 'Page views by browser', bars(tr.browsers, {value:'page_views'}))
        + card('Threats by country', 'Where threats came from', bars((tr.countries || []).filter(r => r.threats > 0).sort((a,b) => b.threats - a.threats), {value:'threats', label:r => country(r.key), red:true, empty:'No threats in this period.'}))
        + '</div><div class="cfwaf-zd-section-title">Responses</div><div class="cfwaf-zd-grid3">'
        + card('Status codes', 'Responses grouped by type', bars(statusRows, {label:r => r.key + ' ' + (STATUS_HINT[r.key] || '')}) + '<div style="margin-top:12px;">' + bars(tr.statuses, {label:r => 'HTTP ' + r.key, limit:8}) + '</div>')
        + card('Content types', 'What Cloudflare served', bars(tr.content_types, {label:r => r.key || 'other'}))
        + card('Encryption and IP types', 'TLS versions and visitor network types', bars(tr.tls, {label:r => r.key === 'none' ? 'Not encrypted' : r.key}) + '<div style="margin-top:12px;">' + bars(tr.ip_classes, {label:r => IP_CLASS[r.key] || r.key}) + '</div>')
        + '</div>';
      if ((tr.threat_types || []).length){
        html += '<div class="cfwaf-zd-grid3" style="margin-top:14px;">' + card('Threat types', 'How Cloudflare classified threats', bars(tr.threat_types, {red:true, label:r => THREAT[r.key] || r.key})) + '</div>';
      }
    }
  } else {
    html += '<div class="cfwaf-zd-error">Traffic analytics could not be loaded: ' + esc(tr.message || 'unknown error') + '</div>';
  }

  // Top pages and devices
  html += '<div class="cfwaf-zd-section-title">Pages and devices</div>';
  if (top.ok){
    if (top.note) html += '<div class="cfwaf-zd-note">' + esc(top.note) + '</div>';
    html += '<div class="cfwaf-zd-grid3">'
      + card('Top pages', 'Most requested paths from real visitors', bars(top.paths, {value:'count', mono:true}))
      + card('Hostnames', 'Requests by hostname', bars(top.hosts, {value:'count', mono:true}))
      + card('Cache status', 'How each request was served', bars(top.cache, {value:'count', label:r => r.key || 'unknown'}))
      + card('Devices', 'Desktop, mobile and tablet', bars(top.devices, {value:'count', label:r => r.key ? r.key.charAt(0).toUpperCase() + r.key.slice(1) : 'Unknown'}))
      + '</div>';
  } else {
    html += '<div class="cfwaf-zd-unavail">' + esc(top.message || 'Not available for this zone.') + '</div>';
  }

  // Security
  html += '<div class="cfwaf-zd-section-title">Security events</div>';
  if (sec.ok){
    if (sec.note) html += '<div class="cfwaf-zd-note">' + esc(sec.note) + '</div>';
    charts.sec = {title:'Security events', labels:(sec.series || []).map(r => r.t), gran:sec.granularity, type:'bar', stacked:false, fmt:num, fmtFull:full, series:[{name:'Events', color:C.red, values:(sec.series || []).map(r => r.count)}]};
    tables.sec = {head:['Time','Events'], rows:(sec.series || []).map(r => [tLabel(r.t, sec.granularity, false), full(r.count)])};
    const ACTIONS = {block:'Blocked', managed_challenge:'Managed challenge', challenge:'Challenge', jschallenge:'JS challenge', js_challenge:'JS challenge', skip:'Skipped (allowed)', allow:'Allowed', log:'Logged', connection_close:'Connection closed', challenge_solved:'Challenge solved', challenge_failed:'Challenge failed', jschallenge_solved:'JS challenge solved', managed_challenge_skipped:'Challenge skipped', managed_challenge_non_interactive_solved:'Challenge passed', managed_challenge_interactive_solved:'Challenge solved'};
    html += '<div class="cfwaf-zd-grid2">'
      + card('Events over time', full(sec.total) + ' firewall events in this period', '<div class="cfwaf-zd-chart" data-chart="sec"></div>', {table:'sec'})
      + card('What happened', 'Events by action', bars(sec.by_action, {value:'count', label:r => ACTIONS[r.key] || r.key, red:true}))
      + '</div><div class="cfwaf-zd-grid3" style="margin-top:14px;">'
      + card('Which feature acted', 'Events by Cloudflare security feature', bars(sec.by_source, {value:'count'}))
      + card('Top paths', 'Most targeted paths', bars(sec.by_path, {value:'count', mono:true}))
      + card('Top IP addresses', 'Most active sources', bars(sec.by_ip, {value:'count', mono:true}))
      + card('Countries', 'Events by country', bars(sec.by_country, {value:'count', label:r => country(r.key)}))
      + '</div>';
  } else {
    html += '<div class="cfwaf-zd-unavail">' + esc(sec.message || 'Not available for this zone.') + '</div>';
  }

  body.innerHTML = html;
  drawCharts();
  qsa('[data-table]', body).forEach(b => b.addEventListener('click', toggleTable));
  qs('#cfwaf-zd-csv').disabled = !(tr.ok && tr.series.length);
  const gen = new Date((data.generated_at || 0) * 1000);
  qs('#cfwaf-zd-meta').textContent = RANGES[range] + ' · ' + (data.cached ? 'Cached ' : 'Loaded ') + gen.toLocaleTimeString(undefined, {hour:'numeric', minute:'2-digit'});
}

function drawCharts(){
  qsa('[data-chart]').forEach(el => { const s = charts[el.dataset.chart]; if (s && !el.dataset.tableOn) chart(el, s); });
}
function toggleTable(){
  const id = this.dataset.table, el = qs('[data-chart="' + id + '"]');
  if (!el || !tables[id]) return;
  if (el.dataset.tableOn){
    delete el.dataset.tableOn; el.style.height = ''; this.textContent = 'Table'; chart(el, charts[id]); return;
  }
  el.dataset.tableOn = '1'; el.style.height = 'auto'; this.textContent = 'Chart';
  const t = tables[id];
  el.innerHTML = '<div class="cfwaf-zd-table-wrap"><table class="cfwaf-zd-table"><thead><tr>' + t.head.map(h => '<th>' + esc(h) + '</th>').join('') + '</tr></thead><tbody>'
    + t.rows.map(r => '<tr>' + r.map(c => '<td>' + esc(c) + '</td>').join('') + '</tr>').join('') + '</tbody></table></div>';
}

let resizeTimer = null;
window.addEventListener('resize', () => { clearTimeout(resizeTimer); resizeTimer = setTimeout(drawCharts, 150); });

// ── Load ────────────────────────────────────────────────────────────────────
function load(refresh){
  const body = qs('#cfwaf-zd-body');
  if (data) body.classList.add('is-loading');
  qs('#cfwaf-zd-refresh').disabled = true;
  ajax('wpwaf_zs_detail', {zone_id:ZONE_ID, range, refresh: refresh ? '1' : '0'}).then(res => {
    qs('#cfwaf-zd-refresh').disabled = false;
    body.classList.remove('is-loading');
    if (!res.success){
      body.innerHTML = '<div class="cfwaf-zd-error">Could not load analytics: ' + esc((res.data && res.data.message) || 'error') + '</div>';
      return;
    }
    data = res.data;
    Object.keys(charts).forEach(k => delete charts[k]);
    render();
  });
}

qsa('#cfwaf-zd-range button').forEach(b => b.addEventListener('click', () => {
  if (b.dataset.range === range) return;
  range = b.dataset.range;
  qsa('#cfwaf-zd-range button').forEach(x => x.setAttribute('aria-pressed', x === b ? 'true' : 'false'));
  history.replaceState(null, '', BASE_URL + '&zone=' + ZONE_ID + '&range=' + range);
  load(false);
}));
qs('#cfwaf-zd-refresh').addEventListener('click', () => load(true));
qs('#cfwaf-zd-switch')?.addEventListener('change', function(){ location.href = BASE_URL + '&zone=' + encodeURIComponent(this.value) + '&range=' + range; });

// CSV of the time series for agencies.
qs('#cfwaf-zd-csv').addEventListener('click', () => {
  const tr = data && data.traffic; if (!tr || !tr.ok) return;
  const cols = ['t','requests','cached','bytes','cached_bytes','uniques','page_views','threats','encrypted'];
  const head = ['time','requests','cached_requests','bytes','cached_bytes','unique_visitors','page_views','threats','encrypted_requests'];
  const lines = [head.join(',')].concat(tr.series.map(r => cols.map(c => '"' + String(r[c] == null ? '' : r[c]).replace(/"/g, '""') + '"').join(',')));
  const blob = new Blob([lines.join('\n')], {type:'text/csv'});
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = (zoneName || ZONE_ID) + '-' + range + '-analytics.csv';
  document.body.appendChild(a); a.click(); a.remove();
  setTimeout(() => URL.revokeObjectURL(a.href), 1000);
});

// Fill in the zone name and plan when the zone is not in the synced list.
if (!zoneName){
  ajax('wpwaf_list_zones', {}).then(res => {
    const z = res.success && (res.data.zones || []).find(x => x.id === ZONE_ID);
    if (!z) return;
    zoneName = z.name;
    qs('#cfwaf-zd-name').textContent = z.name;
    const p = qs('#cfwaf-zd-plan'); p.textContent = z.plan || ''; p.style.display = z.plan ? '' : 'none';
  });
}

load(false);
})();
</script>
<?php endif; ?>
