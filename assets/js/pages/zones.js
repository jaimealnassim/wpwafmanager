/* WP WAF Manager: Zones screen.
 * Values from PHP arrive in window.wpwafPage, printed by WPWAF_Admin::page_script()
 * in includes/views/page-zones.php. */
(function() {
	const ajaxUrl = wpwafPage.ajax_url;
	const nonce   = wpwafPage.nonce;
	const flares  = wpwafPage.flares;
	const presets = wpwafPage.presets;
	if ( ! wpwafPage.has_creds ) { return; }

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
		if (!plan) return '-';
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
					errEl.innerHTML = '<div class="cfwaf-zn-notice cfwaf-zn-notice--error"><strong>' + window.wpwafIcon('x') + ' Could not load zones.</strong><br>' + esc(msg) + '</div>';
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
