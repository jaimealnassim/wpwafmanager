<?php
defined( 'ABSPATH' ) || exit;
$nonce         = wp_create_nonce( 'wpwaf_nonce' );
$ajax_url      = admin_url( 'admin-ajax.php' );
$allowed_zones = array_values( $settings['allowed_zones'] ?? [] );

// Filter cache to only show allowed zones (if any selected)
$display_cache = empty( $allowed_zones )
	? $cache
	: array_filter( $cache, fn( $k ) => in_array( $k, $allowed_zones, true ), ARRAY_FILTER_USE_KEY );

if ( ! function_exists( 'cfwaf_fmt_bytes' ) ) {
	function cfwaf_fmt_bytes( int $b ): string {
		if ( $b >= 1073741824 ) return round( $b / 1073741824, 1 ) . ' GB';
		if ( $b >= 1048576 )   return round( $b / 1048576, 1 )   . ' MB';
		if ( $b >= 1024 )      return round( $b / 1024, 1 )       . ' KB';
		return $b . ' B';
	}
}
if ( ! function_exists( 'cfwaf_fmt_num' ) ) {
	function cfwaf_fmt_num( int $n ): string {
		if ( $n >= 1000000 ) return round( $n / 1000000, 1 ) . 'M';
		if ( $n >= 1000 )    return round( $n / 1000, 1 )    . 'K';
		return (string) $n;
	}
}
?>
<style>
:root{--zs-orange:#FF6A00;--zs-border:#e2e6ea;--zs-bg:#f8f9fb;--zs-dark:#1a1a2e;--zs-muted:#6b7280;}
.cfwaf-zs-wrap{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;max-width:1400px;padding:24px 20px;color:var(--zs-dark);}
.cfwaf-zs-header{display:flex;align-items:center;gap:10px;margin-bottom:20px;}
.cfwaf-zs-header h1{font-size:22px;font-weight:700;margin:0;display:flex;align-items:center;gap:8px;}
.cfwaf-zs-header h1 .dashicons{color:var(--zs-orange);font-size:24px;width:24px;height:24px;}
.cfwaf-zs-no-creds{background:#fff8f5;border:1px solid #fcd9c0;border-radius:8px;padding:20px 24px;color:#92400e;}

/* Settings bar */
.cfwaf-zs-settings-bar{background:#fff;border:1px solid var(--zs-border);border-radius:8px;padding:14px 18px;margin-bottom:16px;display:flex;align-items:center;gap:14px;flex-wrap:wrap;}
.cfwaf-zs-settings-bar label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--zs-muted);}
.cfwaf-zs-select{padding:6px 10px;border:1px solid var(--zs-border);border-radius:6px;font-size:13px;color:var(--zs-dark);background:#fff;cursor:pointer;}
.cfwaf-zs-select:focus{outline:none;border-color:var(--zs-orange);}
.cfwaf-zs-toggle-wrap{display:flex;align-items:center;gap:7px;font-size:13px;font-weight:600;}
.cfwaf-zs-toggle-wrap input[type=checkbox]{width:15px;height:15px;accent-color:var(--zs-orange);cursor:pointer;}
.cfwaf-zs-btn{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:6px;font-size:13px;font-weight:600;cursor:pointer;border:none;transition:all .15s;white-space:nowrap;}
.cfwaf-zs-btn-primary{background:var(--zs-orange);color:#fff;}
.cfwaf-zs-btn-primary:hover{background:#d95500;}
.cfwaf-zs-btn-secondary{background:#fff;color:var(--zs-dark);border:1px solid var(--zs-border);}
.cfwaf-zs-btn-secondary:hover{background:var(--zs-bg);}
.cfwaf-zs-btn:disabled{opacity:.5;cursor:not-allowed;}
.cfwaf-zs-next-sync{margin-left:auto;font-size:12px;color:var(--zs-muted);}

/* Zone picker */
.cfwaf-zs-zone-picker{background:#fff;border:1px solid var(--zs-border);border-radius:8px;margin-bottom:20px;overflow:hidden;}
.cfwaf-zs-zone-picker-header{display:flex;align-items:center;justify-content:space-between;padding:12px 16px;cursor:pointer;user-select:none;}
.cfwaf-zs-zone-picker-header h3{margin:0;font-size:13px;font-weight:700;color:var(--zs-dark);display:flex;align-items:center;gap:8px;}
.cfwaf-zs-zone-count{font-size:11px;font-weight:400;color:var(--zs-muted);}
.cfwaf-zs-picker-chevron{font-size:12px;color:var(--zs-muted);display:flex;align-items:center;gap:4px;}
.cfwaf-zs-zone-picker-body{border-top:1px solid var(--zs-border);padding:14px 16px;}
.cfwaf-zs-zone-picker-hint{font-size:12px;color:var(--zs-muted);margin:0 0 12px;}
.cfwaf-zs-zone-list{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:6px;max-height:260px;overflow-y:auto;margin-bottom:12px;padding-right:4px;}
.cfwaf-zs-zone-item{display:flex;align-items:center;gap:8px;padding:7px 10px;border:1px solid var(--zs-border);border-radius:6px;cursor:pointer;transition:all .12s;}
.cfwaf-zs-zone-item:hover,.cfwaf-zs-zone-item.selected{border-color:var(--zs-orange);background:#fff8f5;}
.cfwaf-zs-zone-item input[type=checkbox]{width:14px;height:14px;accent-color:var(--zs-orange);cursor:pointer;flex-shrink:0;margin:0;}
.cfwaf-zs-zone-item-name{font-size:12px;font-weight:600;color:var(--zs-dark);flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.cfwaf-zs-zone-item-plan{font-size:10px;color:var(--zs-muted);flex-shrink:0;}
.cfwaf-zs-zone-picker-actions{display:flex;align-items:center;gap:8px;}
.cfwaf-zs-link-btn{background:none;border:none;font-size:12px;color:var(--zs-orange);cursor:pointer;padding:0;font-weight:600;}
.cfwaf-zs-link-btn:hover{text-decoration:underline;}
.cfwaf-zs-zone-loading{padding:20px;text-align:center;color:var(--zs-muted);font-size:13px;}

/* Zone grid */
.cfwaf-zs-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:14px;}

/* Zone card */
.cfwaf-zs-card{background:#fff;border:1px solid var(--zs-border);border-radius:10px;overflow:hidden;transition:box-shadow .15s;}
.cfwaf-zs-card:hover{box-shadow:0 4px 12px rgba(0,0,0,.07);}
.cfwaf-zs-card-header{padding:12px 14px 10px;border-bottom:1px solid var(--zs-border);display:flex;align-items:center;gap:8px;}
.cfwaf-zs-card-header h3{margin:0;font-size:13px;font-weight:700;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--zs-dark);}
.cfwaf-zs-plan-badge{font-size:10px;font-weight:600;padding:2px 7px;border-radius:10px;background:#f1f5f9;color:#64748b;border:1px solid #e2e8f0;white-space:nowrap;flex-shrink:0;}
.cfwaf-zs-plan-badge.pro{background:#ede9fe;color:#5b21b6;border-color:#c4b5fd;}
.cfwaf-zs-plan-badge.business{background:#fef3c7;color:#92400e;border-color:#fde68a;}
.cfwaf-zs-plan-badge.enterprise{background:#d1fae5;color:#065f46;border-color:#a7f3d0;}

/* Stats grid */
.cfwaf-zs-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:0;padding:4px 0;border-bottom:1px solid var(--zs-border);}
.cfwaf-zs-stats-row2{display:grid;grid-template-columns:repeat(3,1fr);gap:0;padding:4px 0;}
.cfwaf-zs-stat{display:flex;flex-direction:column;align-items:center;padding:14px 6px;position:relative;}
.cfwaf-zs-stat+.cfwaf-zs-stat::before{content:'';position:absolute;left:0;top:20%;height:60%;width:1px;background:var(--zs-border);}
.cfwaf-zs-stat-value{font-size:20px;font-weight:800;color:var(--zs-dark);line-height:1;letter-spacing:-.5px;}
.cfwaf-zs-stat-label{font-size:9px;font-weight:700;color:var(--zs-muted);text-transform:uppercase;letter-spacing:.6px;margin-top:5px;}
.cfwaf-zs-stat.cache-rate .cfwaf-zs-stat-value{color:#059669;}
.cfwaf-zs-stat.cache-rate.zero .cfwaf-zs-stat-value{color:var(--zs-muted);}
.cfwaf-zs-stat.threats .cfwaf-zs-stat-value{color:#dc2626;}
.cfwaf-zs-stat.threats.zero .cfwaf-zs-stat-value{color:var(--zs-muted);}

/* Footer */
.cfwaf-zs-card-footer{padding:7px 14px;background:var(--zs-bg);border-top:1px solid var(--zs-border);display:flex;align-items:center;justify-content:flex-end;}
.cfwaf-zs-sync-info{font-size:11px;color:var(--zs-muted);}

/* States */
.cfwaf-zs-no-data{background:#fff;border:1px solid var(--zs-border);border-radius:8px;padding:40px;text-align:center;color:var(--zs-muted);}
.cfwaf-zs-spinner{display:inline-block;width:14px;height:14px;border:2px solid rgba(255,106,0,.3);border-top-color:var(--zs-orange);border-radius:50%;animation:zs-spin .6s linear infinite;vertical-align:middle;}
@keyframes zs-spin{to{transform:rotate(360deg);}}
.cfwaf-zs-toast{position:fixed;bottom:24px;right:24px;padding:10px 16px;border-radius:6px;font-size:13px;font-weight:600;color:#fff;z-index:999999;}
.cfwaf-zs-toast.ok{background:#059669;}.cfwaf-zs-toast.err{background:#dc2626;}

</style>

<div class="cfwaf-zs-wrap">
	<div class="cfwaf-zs-header">
		<h1><span class="dashicons dashicons-chart-area"></span> Zone Status</h1>
	</div>

	<?php if ( ! $has_creds ) : ?>
	<div class="cfwaf-zs-no-creds">
		<strong>No Cloudflare account connected.</strong> Please <a href="<?php echo admin_url('admin.php?page=wpwafmanager'); ?>">add your credentials</a> on the WAF Rules page first.
	</div>
	<?php else : ?>

	<!-- Settings bar -->
	<div class="cfwaf-zs-settings-bar">
		<div class="cfwaf-zs-toggle-wrap">
			<input type="checkbox" id="cfwaf-zs-enabled" <?php checked( $settings['enabled'] ); ?>>
			<label for="cfwaf-zs-enabled">Auto-sync</label>
		</div>
		<div style="display:flex;align-items:center;gap:6px;">
			<label>Every</label>
			<select id="cfwaf-zs-interval" class="cfwaf-zs-select">
				<?php
				foreach ( [ 300=>'5 min', 600=>'10 min', 900=>'15 min', 1800=>'30 min',
				             3600=>'1 hour', 7200=>'2 hrs', 21600=>'6 hrs', 86400=>'24 hrs' ] as $v => $l ) {
					printf( '<option value="%d"%s>%s</option>', $v, selected( $settings['sync_interval'], $v, false ), $l );
				}
				?>
			</select>
		</div>
		<div style="display:flex;align-items:center;gap:6px;">
			<label>Window</label>
			<select id="cfwaf-zs-days" class="cfwaf-zs-select">
				<?php foreach ( [1=>'24h',3=>'3 days',7=>'7 days',14=>'14 days',30=>'30 days'] as $d => $l ) {
					printf( '<option value="%d"%s>%s</option>', $d, selected( $settings['days_analytics'], $d, false ), $l );
				} ?>
			</select>
		</div>
		<button class="cfwaf-zs-btn cfwaf-zs-btn-secondary" id="cfwaf-zs-save-settings">Save</button>
		<button class="cfwaf-zs-btn cfwaf-zs-btn-primary" id="cfwaf-zs-sync-now">
			<span class="dashicons dashicons-update" style="font-size:14px;width:14px;height:14px;margin-top:1px;"></span> Sync Now
		</button>
		<div class="cfwaf-zs-next-sync" id="cfwaf-zs-next-sync-info">
			<?php
			if ( $next_sync > 0 ) {
				$diff = $next_sync - time();
				echo esc_html( $diff > 0 ? 'Next sync in ~' . ceil( $diff / 60 ) . 'm' : 'Sync pending…' );
			} else {
				echo 'No sync scheduled';
			}
			?>
		</div>
	</div>

	<!-- Zone picker -->
	<div class="cfwaf-zs-zone-picker">
		<div class="cfwaf-zs-zone-picker-header" id="cfwaf-zs-zone-picker-toggle">
			<h3>🌐 Zones to Sync <span class="cfwaf-zs-zone-count" id="cfwaf-zs-zone-count-label">
				<?php echo empty( $allowed_zones ) ? '' : '— ' . count( $allowed_zones ) . ' selected'; ?>
			</span></h3>
			<span class="cfwaf-zs-picker-chevron" id="cfwaf-zs-picker-chevron">▼ Configure</span>
		</div>
		<div class="cfwaf-zs-zone-picker-body" id="cfwaf-zs-zone-picker-body" style="display:none;">
			<p class="cfwaf-zs-zone-picker-hint">Select which zones to sync. Leave all unchecked to sync all zones.</p>
			<div class="cfwaf-zs-zone-list" id="cfwaf-zs-zone-list">
				<div class="cfwaf-zs-zone-loading"><span class="cfwaf-zs-spinner"></span> Loading zones…</div>
			</div>
			<div class="cfwaf-zs-zone-picker-actions">
				<button class="cfwaf-zs-link-btn" id="cfwaf-zs-select-all-zones">Select all</button>
				<button class="cfwaf-zs-link-btn" id="cfwaf-zs-deselect-all-zones">Deselect all</button>
				<button class="cfwaf-zs-btn cfwaf-zs-btn-secondary" id="cfwaf-zs-save-zones" style="margin-left:auto;">Save Zone Selection</button>
			</div>
		</div>
	</div>

	<!-- Zone cards -->
	<div id="cfwaf-zs-grid">
	<?php
	$allowed_zones = $settings['allowed_zones'] ?? [];
	if ( empty( $display_cache ) ) :
	?>
		<div class="cfwaf-zs-no-data">
		<?php if ( empty( $allowed_zones ) ) : ?>
			<p style="font-size:28px;margin:0 0 8px;">🌐</p>
			<p style="font-size:15px;font-weight:700;margin:0 0 6px;color:var(--zs-dark);">No zones selected</p>
			<p style="margin:0;font-size:13px;">Open the <strong>Zone Picker</strong> above, select the zones you want to monitor, then click <strong>Save &amp; Sync</strong>.</p>
		<?php else : ?>
			<p style="font-size:28px;margin:0 0 8px;">📡</p>
			<p style="font-size:15px;font-weight:700;margin:0 0 6px;color:var(--zs-dark);">No data yet</p>
			<p style="margin:0;font-size:13px;">Click <strong>Sync Now</strong> to fetch analytics for your selected zones.</p>
		<?php endif; ?>
		</div>
	<?php else : ?>
		<div class="cfwaf-zs-grid">
		<?php foreach ( $display_cache as $zone_id => $entry ) :
			$zone      = $entry['zone']      ?? [];
			$analytics = $entry['overview']['analytics'] ?? [];
			$synced_at = $entry['synced_at'] ?? 0;
			$plan_raw  = strtolower( $zone['plan'] ?? '' );
			$plan_class = str_contains( $plan_raw, 'pro' ) ? 'pro'
				: ( str_contains( $plan_raw, 'business' ) ? 'business'
				: ( str_contains( $plan_raw, 'enterprise' ) ? 'enterprise' : '' ) );
		?>
		<div class="cfwaf-zs-card">
			<div class="cfwaf-zs-card-header">
				<h3 title="<?php echo esc_attr( $zone['name'] ?? $zone_id ); ?>"><?php echo esc_html( $zone['name'] ?? $zone_id ); ?></h3>
				<span class="cfwaf-zs-plan-badge <?php echo $plan_class; ?>"><?php echo esc_html( $zone['plan'] ?? 'Free' ); ?></span>
			</div>
			<?php
			$reqs       = (int)( $analytics['requests']   ?? 0 );
			$bw         = (int)( $analytics['bandwidth']  ?? 0 );
			$pv         = (int)( $analytics['pageviews']  ?? 0 );
			$threats_n  = (int)( $analytics['threats']    ?? 0 );
			$cr         = (float)( $analytics['cache_rate'] ?? 0 );
			$cr_zero    = $cr == 0 ? ' zero' : '';
			$thr_zero   = $threats_n == 0 ? ' zero' : '';
			?>
			<!-- Row 1: Requests, Bandwidth, Cache Hit Rate -->
			<div class="cfwaf-zs-stats">
				<div class="cfwaf-zs-stat">
					<span class="cfwaf-zs-stat-value"><?php echo cfwaf_fmt_num( $reqs ); ?></span>
					<span class="cfwaf-zs-stat-label">Requests</span>
				</div>
				<div class="cfwaf-zs-stat">
					<span class="cfwaf-zs-stat-value"><?php echo cfwaf_fmt_bytes( $bw ); ?></span>
					<span class="cfwaf-zs-stat-label">Bandwidth</span>
				</div>
				<div class="cfwaf-zs-stat cache-rate<?php echo $cr_zero; ?>">
					<span class="cfwaf-zs-stat-value"><?php echo esc_html( $cr ); ?>%</span>
					<span class="cfwaf-zs-stat-label">Cache Rate</span>
				</div>
			</div>
			<!-- Row 2: Pageviews, Threats, Cached count -->
			<div class="cfwaf-zs-stats-row2">
				<div class="cfwaf-zs-stat">
					<span class="cfwaf-zs-stat-value"><?php echo cfwaf_fmt_num( $pv ); ?></span>
					<span class="cfwaf-zs-stat-label">Pageviews</span>
				</div>
				<div class="cfwaf-zs-stat threats<?php echo $thr_zero; ?>">
					<span class="cfwaf-zs-stat-value"><?php echo cfwaf_fmt_num( $threats_n ); ?></span>
					<span class="cfwaf-zs-stat-label">Threats</span>
				</div>
				<div class="cfwaf-zs-stat">
					<span class="cfwaf-zs-stat-value"><?php echo cfwaf_fmt_num( (int)($analytics['cached']??0) ); ?></span>
					<span class="cfwaf-zs-stat-label">Cached Req</span>
				</div>
			</div>
			<div class="cfwaf-zs-card-footer">
				<span class="cfwaf-zs-sync-info">Synced <?php echo $synced_at ? human_time_diff( $synced_at ) . ' ago' : 'never'; ?></span>
			</div>
		</div>
		<?php endforeach; ?>
		</div>
	<?php endif; ?>
	</div>

	<?php endif; ?>
</div>

<script>
'use strict';
(function(){
const NONCE       = <?php echo wp_json_encode( $nonce ); ?>;
const AJAX_URL    = <?php echo wp_json_encode( $ajax_url ); ?>;
const SAVED_ZONES = <?php echo wp_json_encode( $allowed_zones ); ?>;

function toast(msg, ok=true){
	const el = document.createElement('div');
	el.className = 'cfwaf-zs-toast ' + (ok?'ok':'err');
	el.textContent = msg;
	document.body.appendChild(el);
	setTimeout(()=>{el.style.opacity='0';el.style.transition='opacity .3s';setTimeout(()=>el.remove(),300);},3000);
}
function ajax(action, data, cb){
	const fd = new FormData();
	fd.append('action',action); fd.append('nonce',NONCE);
	Object.entries(data).forEach(([k,v])=>fd.append(k,v));
	fetch(AJAX_URL,{method:'POST',body:fd}).then(r=>r.json()).then(cb)
		.catch(e=>cb({success:false,data:{message:e.message}}));
}

// ── Zone picker ───────────────────────────────────────────────────────────────
let allZones      = [];
let selectedZones = new Set(SAVED_ZONES);
let pickerLoaded  = false;

// Load zones silently on page load for count label
ajax('wpwaf_list_zones', {}, (res) => {
	if (!res.success) return;
	allZones = res.data.zones || [];
	updateZoneCountLabel();
	// If picker is already open (shouldn't be, but just in case) render it
	if (pickerLoaded) renderZonePicker();
});

let pickerOpen = false;
function togglePicker(){
	const body    = document.getElementById('cfwaf-zs-zone-picker-body');
	const chevron = document.getElementById('cfwaf-zs-picker-chevron');
	pickerOpen = !pickerOpen;
	body.style.display  = pickerOpen ? '' : 'none';
	chevron.textContent = pickerOpen ? '▲ Close' : '▼ Configure';
	if (pickerOpen) {
		pickerLoaded = true;
		if (allZones.length > 0) {
			renderZonePicker();
		} else {
			ajax('wpwaf_list_zones', {}, (res) => {
				allZones = res.success ? (res.data.zones || []) : [];
				renderZonePicker();
				updateZoneCountLabel();
			});
		}
	}
}
document.getElementById('cfwaf-zs-zone-picker-toggle')?.addEventListener('click', togglePicker);

function renderZonePicker(){
	const list = document.getElementById('cfwaf-zs-zone-list');
	if (!allZones.length) { list.innerHTML = '<div class="cfwaf-zs-zone-loading">No zones found.</div>'; return; }
	list.innerHTML = allZones.map(z => {
		const checked = selectedZones.has(z.id) ? 'checked' : '';
		const sel     = selectedZones.has(z.id) ? ' selected' : '';
		return `<label class="cfwaf-zs-zone-item${sel}" data-id="${z.id}">
			<input type="checkbox" value="${z.id}" ${checked} class="cfwaf-zs-zone-check">
			<span class="cfwaf-zs-zone-item-name" title="${z.name}">${z.name}</span>
			<span class="cfwaf-zs-zone-item-plan">${z.plan.replace(' Website','').replace(' Plan','')}</span>
		</label>`;
	}).join('');
	document.querySelectorAll('.cfwaf-zs-zone-check').forEach(cb => {
		cb.addEventListener('change', function(){
			if (this.checked) selectedZones.add(this.value);
			else              selectedZones.delete(this.value);
			this.closest('.cfwaf-zs-zone-item').classList.toggle('selected', this.checked);
			updateZoneCountLabel();
		});
	});
}

function updateZoneCountLabel(){
	const el = document.getElementById('cfwaf-zs-zone-count-label');
	if (!el) return;
	if (selectedZones.size === 0) {
		el.textContent = allZones.length ? '— All ' + allZones.length + ' zones' : '';
	} else {
		el.textContent = '— ' + selectedZones.size + ' of ' + (allZones.length || '?') + ' selected';
	}
}

document.getElementById('cfwaf-zs-select-all-zones')?.addEventListener('click', () => {
	allZones.forEach(z => selectedZones.add(z.id));
	renderZonePicker(); updateZoneCountLabel();
});
document.getElementById('cfwaf-zs-deselect-all-zones')?.addEventListener('click', () => {
	selectedZones.clear();
	renderZonePicker(); updateZoneCountLabel();
});

function saveZonesPayload(extraData){
	const fd = new FormData();
	fd.append('action','wpwaf_zone_status_settings');
	fd.append('nonce', NONCE);
	Object.entries(extraData).forEach(([k,v]) => fd.append(k,v));
	selectedZones.forEach(id => fd.append('allowed_zones[]', id));
	return fd;
}

document.getElementById('cfwaf-zs-save-zones')?.addEventListener('click', function(){
	this.disabled = true; this.textContent = 'Saving…';
	const self = this;
	const enabled  = document.getElementById('cfwaf-zs-enabled').checked;
	const interval = document.getElementById('cfwaf-zs-interval').value;
	const days     = document.getElementById('cfwaf-zs-days').value;
	const fd = saveZonesPayload({enabled:enabled?'1':'0',sync_interval:interval,days_analytics:days});
	fetch(AJAX_URL,{method:'POST',body:fd}).then(r=>r.json()).then(res=>{
		self.disabled=false; self.textContent='Save Zone Selection';
		if (res.success){ toast('✓ Zone selection saved — Sync Now to apply'); updateZoneCountLabel(); }
		else toast('Failed: '+(res.data?.message||'error'),false);
	}).catch(()=>{self.disabled=false; self.textContent='Save Zone Selection';});
});

// ── Settings save ─────────────────────────────────────────────────────────────
document.getElementById('cfwaf-zs-save-settings')?.addEventListener('click', function(){
	const enabled  = document.getElementById('cfwaf-zs-enabled').checked;
	const interval = document.getElementById('cfwaf-zs-interval').value;
	const days     = document.getElementById('cfwaf-zs-days').value;
	this.disabled = true; this.textContent = 'Saving…';
	const self = this;
	const fd = saveZonesPayload({enabled:enabled?'1':'0',sync_interval:interval,days_analytics:days});
	fetch(AJAX_URL,{method:'POST',body:fd}).then(r=>r.json()).then(res=>{
		self.disabled=false; self.textContent='Save';
		if (res.success){
			toast('✓ Settings saved');
			const ns = res.data?.next_sync||0;
			const el = document.getElementById('cfwaf-zs-next-sync-info');
			if (el && ns){ const diff=Math.round((ns*1000-Date.now())/60000); el.textContent=diff>0?'Next sync in ~'+diff+'m':'Sync pending…'; }
		} else toast('Failed: '+(res.data?.message||'error'),false);
	}).catch(()=>{self.disabled=false; self.textContent='Save';});
});

// ── Sync now ──────────────────────────────────────────────────────────────────
document.getElementById('cfwaf-zs-sync-now')?.addEventListener('click', function(){
	this.disabled=true;
	this.innerHTML='<span class="cfwaf-zs-spinner"></span> Syncing…';
	const self=this;
	ajax('wpwaf_zone_status_sync',{},(res)=>{
		self.disabled=false;
		self.innerHTML='<span class="dashicons dashicons-update" style="font-size:14px;width:14px;height:14px;margin-top:1px;"></span> Sync Now';
		if(res.success){ toast('✓ Sync complete — refreshing…'); setTimeout(()=>location.reload(),1000); }
		else toast('Sync failed: '+(res.data?.message||'error'),false);
	});
});

// Countdown ticker
(function tick(){
	const el = document.getElementById('cfwaf-zs-next-sync-info');
	if (!el || !el.textContent.includes('~')) return;
	setTimeout(tick, 60000);
})();
})();
</script>
