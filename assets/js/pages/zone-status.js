/* WP WAF Manager: Zone Status screen.
 * Values from PHP arrive in window.wpwafPage, printed by WPWAF_Admin::page_script()
 * in includes/views/page-zone-status.php. */
'use strict';
(function(){
const NONCE       = wpwafPage.nonce;
const AJAX_URL    = wpwafPage.ajax_url;
const SAVED_ZONES = wpwafPage.saved_zones;

function toast(msg, ok=true, icon){
	const el = document.createElement('div');
	el.className = 'cfwaf-zs-toast ' + (ok?'ok':'err');
	if (window.wpwafToastBody) el.innerHTML = wpwafToastBody(msg, ok, icon); else el.textContent = msg;
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
		el.textContent = allZones.length ? '· All ' + allZones.length + ' zones' : '';
	} else {
		el.textContent = '· ' + selectedZones.size + ' of ' + (allZones.length || '?') + ' selected';
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
		if (res.success){ toast('Zone selection saved. Click Sync Now to apply'); updateZoneCountLabel(); }
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
			toast('Settings saved');
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
		if(res.success){ toast('Sync complete, refreshing…'); setTimeout(()=>location.reload(),1000); }
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
