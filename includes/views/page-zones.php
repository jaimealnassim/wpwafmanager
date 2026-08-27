<?php defined( 'ABSPATH' ) || exit; ?>
<style>
.cfwaf-zones-header  { display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px; }
.cfwaf-zones-meta    { display:flex;align-items:center;gap:12px;flex-wrap:wrap; }
.cfwaf-zones-count   { font-size:12px;font-weight:600;color:#6b7280;background:#f3f4f6;border-radius:20px;padding:3px 10px; }
.cfwaf-zones-search  { height:34px;min-width:220px;padding:0 12px;border:1px solid #e5e7eb;border-radius:6px;font-size:13px;color:#111;background:#fff; }
.cfwaf-zones-search:focus { outline:none;border-color:#FF6A00;box-shadow:0 0 0 2px rgba(255,106,0,.1); }
.cfwaf-zones-table   { width:100%;border-collapse:collapse;background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.06);border:1px solid #e5e7eb; }
.cfwaf-zones-table thead tr { background:#f8f9fa; }
.cfwaf-zones-table th { padding:10px 16px;font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.06em;text-align:left;border-bottom:1px solid #e5e7eb; }
.cfwaf-zones-table td { padding:11px 16px;font-size:13px;color:#374151;border-bottom:1px solid #f1f3f5;vertical-align:middle; }
.cfwaf-zones-table tbody tr:last-child td { border-bottom:none; }
.cfwaf-zones-table tbody tr:hover td { background:#fafafa; }
.cfwaf-zone-name-cell { font-weight:600;color:#111;font-size:13px; }
.cfwaf-zone-plan-pill { display:inline-block;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:600; }
.cfwaf-zone-plan-free { background:#f3f4f6;color:#6b7280; }
.cfwaf-zone-plan-pro  { background:#fef3c7;color:#92400e; }
.cfwaf-zone-plan-biz  { background:#dbeafe;color:#1e40af; }
.cfwaf-zone-plan-ent  { background:#f3e8ff;color:#6b21a8; }
/* Flare */
.cfwaf-flare-add-btn  { display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;border:1px dashed #d1d5db;background:transparent;color:#9ca3af;font-size:11px;font-weight:600;cursor:pointer;transition:all .12s; }
.cfwaf-flare-add-btn:hover { border-color:#FF6A00;color:#FF6A00; }
.cfwaf-zones-flare-pill { display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;border:1px solid;font-size:11px;font-weight:700;cursor:pointer;transition:opacity .12s; }
.cfwaf-zones-flare-pill:hover { opacity:.85; }
.cfwaf-flare-remove-x { opacity:.6;font-size:14px;line-height:1;cursor:pointer;padding:0 2px; }
.cfwaf-flare-remove-x:hover { opacity:1; }
/* Picker */
.cfwaf-flare-picker { position:absolute;left:0;top:calc(100% + 6px);background:#fff;border:1px solid #e5e7eb;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.12);padding:14px;min-width:260px;z-index:999; }
.cfwaf-flare-picker-presets { display:flex;flex-wrap:wrap;gap:6px;margin-bottom:12px; }
.cfwaf-flare-option  { padding:3px 10px;border-radius:20px;border:1px solid;font-size:11px;font-weight:700;cursor:pointer;transition:all .12s;background:transparent;opacity:.8; }
.cfwaf-flare-option:hover,.cfwaf-flare-option.active { opacity:1;transform:scale(1.04); }
.cfwaf-flare-picker-custom { display:flex;gap:6px;border-top:1px solid #f1f3f5;padding-top:10px; }
.cfwaf-flare-picker-custom .cfwaf-input { height:30px;font-size:12px;flex:1; }
.cfwaf-flare-custom-save { height:30px;padding:0 12px;font-size:12px;background:#FF6A00;color:#fff;border:none;border-radius:6px;cursor:pointer;font-weight:600; }
.cfwaf-flare-custom-save:hover { background:#e05c00; }
/* No results */
.cfwaf-zones-empty { text-align:center;padding:40px;color:#9ca3af;font-size:13px; }
.cfwaf-btn-sm { display:inline-flex;align-items:center;gap:6px;height:32px;padding:0 14px;font-size:12px;font-weight:600;border-radius:6px;border:1px solid #d1d5db;background:#fff;color:#374151;cursor:pointer;transition:background .15s,border-color .15s;text-decoration:none; }
.cfwaf-btn-sm:hover { background:#f3f4f6;border-color:#9ca3af; }
</style>

<div class="cfwaf-wrap">
<div class="cfwaf-page-header">
	<div class="cfwaf-header-inner">
		<div class="cfwaf-header-title">
			<h1>Zones</h1>
			<p class="cfwaf-header-sub">Label your zones with flares so you can identify sites at a glance in the Deploy panel.</p>
		</div>
	</div>
</div>

<div style="max-width:960px;margin:0 auto;padding:20px;">

<?php if ( ! $has_creds ) : ?>
	<div class="cfwaf-zone-load-error">
		<strong>No Cloudflare account connected.</strong><br>
		Add a Cloudflare account on the main <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpwafmanager' ) ); ?>">WAF Rules</a> page first.
	</div>
<?php else : ?>

	<div class="cfwaf-zones-header">
		<div class="cfwaf-zones-meta">
			<span class="cfwaf-zones-count" id="cfwaf-zones-count">Loading&hellip;</span>
			<input type="search" id="cfwaf-zones-search" class="cfwaf-zones-search" placeholder="&#128269; Filter zones&hellip;" style="display:none;">
		</div>
		<button type="button" id="cfwaf-zones-refresh" class="cfwaf-btn cfwaf-btn-sm">&#8635; Refresh</button>
	</div>

	<div id="cfwaf-zones-loading" style="padding:60px;text-align:center;color:#9ca3af;">
		<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#FF6A00" stroke-width="2" style="animation:spin 1s linear infinite;display:inline-block;"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
		<p style="margin-top:10px;font-size:13px;">Loading zones&hellip;</p>
	</div>
	<style>@keyframes spin{to{transform:rotate(360deg)}}</style>

	<div id="cfwaf-zones-error" style="display:none;"></div>

	<div id="cfwaf-zones-table-wrap" style="display:none;">
		<table class="cfwaf-zones-table">
			<thead>
				<tr>
					<th>Domain</th>
					<th>Plan</th>
					<th>Flare</th>
				</tr>
			</thead>
			<tbody id="cfwaf-zones-tbody"></tbody>
		</table>
		<div id="cfwaf-zones-empty" class="cfwaf-zones-empty" style="display:none;">No zones match your search.</div>
	</div>

<?php endif; ?>
</div>
</div>

<script>
(function() {
	const ajaxUrl = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
	const nonce   = <?php echo wp_json_encode( $nonce ); ?>;
	const flares  = <?php echo wp_json_encode( $flares ); ?>;
	const presets = <?php echo wp_json_encode( $presets ); ?>;
	<?php if ( ! $has_creds ) { return; } ?>

	const esc = s => String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');

	function ajax(action, data, cb) {
		const fd = new URLSearchParams({ action, nonce, ...data });
		fetch(ajaxUrl, { method:'POST', body:fd, credentials:'same-origin',
			headers:{'Content-Type':'application/x-www-form-urlencoded'} })
			.then(r => r.json()).then(cb).catch(() => cb({ success:false }));
	}

	function planClass(plan) {
		if (!plan || plan.toLowerCase().includes('free')) return 'cfwaf-zone-plan-free';
		if (plan.toLowerCase().includes('pro'))          return 'cfwaf-zone-plan-pro';
		if (plan.toLowerCase().includes('business'))     return 'cfwaf-zone-plan-biz';
		if (plan.toLowerCase().includes('enterprise'))   return 'cfwaf-zone-plan-ent';
		return 'cfwaf-zone-plan-free';
	}

	function planLabel(plan) {
		if (!plan) return '—';
		if (plan.toLowerCase().includes('free'))       return 'Free';
		if (plan.toLowerCase().includes('pro'))        return 'Pro';
		if (plan.toLowerCase().includes('business'))   return 'Business';
		if (plan.toLowerCase().includes('enterprise')) return 'Enterprise';
		return plan;
	}

	function getPreset(id) { return presets.find(p => p.id === id) || null; }

	function flareCellHTML(zoneId) {
		const f = flares[zoneId];
		if (!f || !f.flare_id) {
			return '<button class="cfwaf-flare-add-btn" data-zone="' + esc(zoneId) + '">+ Add flare</button>';
		}
		const p     = getPreset(f.flare_id);
		const label = f.flare_id === 'custom' ? f.custom_label : (p ? p.label : f.flare_id);
		const color = p ? p.color : '#9ca3af';
		return '<span class="cfwaf-zones-flare-pill" style="color:' + color + ';border-color:' + color + '55;background:' + color + '18" data-zone="' + esc(zoneId) + '">' +
			esc(label) + '<span class="cfwaf-flare-remove-x" data-zone="' + esc(zoneId) + '" title="Remove flare">&times;</span></span>';
	}

	function buildPicker(zoneId) {
		const current = flares[zoneId] ? flares[zoneId].flare_id : '';
		const customVal = flares[zoneId] && flares[zoneId].flare_id === 'custom' ? flares[zoneId].custom_label : '';
		let pills = presets.filter(p => p.id !== 'custom').map(p =>
			'<button class="cfwaf-flare-option' + (current === p.id ? ' active' : '') + '" ' +
			'data-zone="' + esc(zoneId) + '" data-flare="' + esc(p.id) + '" ' +
			'style="color:' + p.color + ';border-color:' + p.color + ';background:' + p.color + (current===p.id?'30':'15') + '">' + esc(p.label) + '</button>'
		).join('');
		return '<div class="cfwaf-flare-picker" data-picker="' + esc(zoneId) + '">' +
			'<div class="cfwaf-flare-picker-presets">' + pills + '</div>' +
			'<div class="cfwaf-flare-picker-custom">' +
				'<input type="text" class="cfwaf-input cfwaf-flare-custom-input" placeholder="Custom label\u2026" value="' + esc(customVal) + '">' +
				'<button class="cfwaf-flare-custom-save" data-zone="' + esc(zoneId) + '">Save</button>' +
			'</div></div>';
	}

	let zones = [], pickerOpen = null;

	function closePicker() {
		if (pickerOpen) { pickerOpen.remove(); pickerOpen = null; }
	}

	function refreshCell(zoneId) {
		document.querySelectorAll('[data-flare-cell="' + zoneId + '"]').forEach(cell => {
			cell.innerHTML = flareCellHTML(zoneId);
			cell.style.position = '';
		});
	}

	function saveFlare(zoneId, flareId, customLabel) {
		if (flareId === '') delete flares[zoneId];
		else flares[zoneId] = { flare_id: flareId, custom_label: customLabel || '' };
		ajax('wpwaf_save_zone_flare', { zone_id: zoneId, flare_id: flareId, custom_label: customLabel || '' }, () => {});
		refreshCell(zoneId);
	}

	document.addEventListener('click', function(e) {
		const addBtn = e.target.closest('.cfwaf-flare-add-btn');
		const pill   = !e.target.classList.contains('cfwaf-flare-remove-x') && e.target.closest('.cfwaf-zones-flare-pill');
		const target = addBtn || pill;

		if (target) {
			e.stopPropagation();
			const zoneId = target.dataset.zone;
			if (pickerOpen && pickerOpen.dataset.picker === zoneId) { closePicker(); return; }
			closePicker();
			const cell = document.querySelector('[data-flare-cell="' + zoneId + '"]');
			if (!cell) return;
			cell.style.position = 'relative';
			cell.insertAdjacentHTML('beforeend', buildPicker(zoneId));
			pickerOpen = cell.querySelector('.cfwaf-flare-picker');
			return;
		}

		if (e.target.classList.contains('cfwaf-flare-remove-x')) {
			e.stopPropagation();
			closePicker();
			saveFlare(e.target.dataset.zone, '', '');
			return;
		}

		if (e.target.classList.contains('cfwaf-flare-option')) {
			e.stopPropagation();
			const zoneId = e.target.dataset.zone;
			closePicker();
			saveFlare(zoneId, e.target.dataset.flare, '');
			return;
		}

		if (e.target.classList.contains('cfwaf-flare-custom-save')) {
			e.stopPropagation();
			const zoneId = e.target.dataset.zone;
			const input  = e.target.closest('.cfwaf-flare-picker').querySelector('.cfwaf-flare-custom-input');
			const label  = input ? input.value.trim() : '';
			if (!label) return;
			closePicker();
			saveFlare(zoneId, 'custom', label);
			return;
		}

		if (!e.target.closest('.cfwaf-flare-picker')) closePicker();
	});

	// Search
	const searchInput = document.getElementById('cfwaf-zones-search');
	if (searchInput) {
		searchInput.addEventListener('input', function() {
			const q = this.value.trim().toLowerCase();
			let visible = 0;
			document.querySelectorAll('#cfwaf-zones-tbody tr').forEach(tr => {
				const name = (tr.dataset.zoneName || '').toLowerCase();
				const show = !q || name.includes(q);
				tr.style.display = show ? '' : 'none';
				if (show) visible++;
			});
			const emptyEl = document.getElementById('cfwaf-zones-empty');
			if (emptyEl) emptyEl.style.display = visible === 0 ? '' : 'none';
			updateCount(visible, zones.length, !!q);
		});
	}

	function updateCount(shown, total, filtered) {
		const el = document.getElementById('cfwaf-zones-count');
		if (!el) return;
		el.textContent = filtered ? shown + ' of ' + total + ' zones' : total + ' zone' + (total !== 1 ? 's' : '');
	}

	function renderTable() {
		const tbody = document.getElementById('cfwaf-zones-tbody');
		if (!tbody) return;
		tbody.innerHTML = '';
		zones.forEach(function(z) {
			const tr = document.createElement('tr');
			tr.dataset.zoneName = z.name;
			tr.innerHTML =
				'<td class="cfwaf-zone-name-cell">' + esc(z.name) + '</td>' +
				'<td><span class="cfwaf-zone-plan-pill ' + planClass(z.plan) + '">' + esc(planLabel(z.plan)) + '</span></td>' +
				'<td data-flare-cell="' + esc(z.id) + '">' + flareCellHTML(z.id) + '</td>';
			tbody.appendChild(tr);
		});
		updateCount(zones.length, zones.length, false);
		const search = document.getElementById('cfwaf-zones-search');
		if (search) search.style.display = zones.length > 8 ? '' : 'none';
	}

	function loadZones() {
		const loading  = document.getElementById('cfwaf-zones-loading');
		const errEl    = document.getElementById('cfwaf-zones-error');
		const tableWrap = document.getElementById('cfwaf-zones-table-wrap');
		const countEl  = document.getElementById('cfwaf-zones-count');
		if (loading)   loading.style.display = '';
		if (errEl)     { errEl.style.display = 'none'; errEl.innerHTML = ''; }
		if (tableWrap) tableWrap.style.display = 'none';
		if (countEl)   countEl.textContent = 'Loading\u2026';
		closePicker();

		ajax('wpwaf_list_zones', {}, function(res) {
			if (loading) loading.style.display = 'none';
			if (!res.success) {
				if (errEl) {
					errEl.style.display = '';
					const msg = (res.data && res.data.message) || 'Unknown error';
					errEl.innerHTML = '<div class="cfwaf-zone-load-error"><strong>\u2717 Could not load zones.</strong><br>' + esc(msg) + '</div>';
				}
				return;
			}
			zones = res.data.zones || [];
			renderTable();
			if (tableWrap) tableWrap.style.display = '';
		});
	}

	const refreshBtn = document.getElementById('cfwaf-zones-refresh');
	if (refreshBtn) refreshBtn.addEventListener('click', loadZones);

	loadZones();
})();
</script>
