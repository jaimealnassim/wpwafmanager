/* WP WAF Manager: DNS Manager screen.
 * Values from PHP arrive in window.wpwafPage, printed by WPWAF_Admin::page_script()
 * in includes/views/page-dns.php. */
'use strict';
(function(){
const NONCE    = wpwafPage.nonce;
const AJAX_URL = wpwafPage.ajax_url;
const PROXYABLE= wpwafPage.proxyable;
const TTL_MAP  = wpwafPage.ttl_map;

let currentZone = '';
let allRecords  = [];
let editingId   = '';
let deletingId  = '';

function qs(sel){ return document.querySelector(sel); }
function qsa(sel){ return Array.from(document.querySelectorAll(sel)); }
function toast(msg, ok=true, icon){
	const el = document.createElement('div');
	el.className = 'cfwaf-dns-toast ' + (ok?'ok':'err');
	if (window.wpwafToastBody) el.innerHTML = wpwafToastBody(msg, ok, icon); else el.textContent = msg;
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
	wpwafSetText(el, 'alert', msg);
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
			? `<button class="cfwaf-proxy-toggle" data-id="${r.id}" data-proxied="${r.proxied?'1':'0'}" title="${r.proxied?'Proxied: click to disable':'DNS only: click to enable proxy'}">
				<span class="cfwaf-proxy-icon ${r.proxied?'proxied':'unproxied'}">${wpwafIcon('cloud')}</span>
				<span class="cfwaf-proxy-label">${r.proxied?'Proxied':'DNS only'}</span>
			   </button>`
			: `<span class="cfwaf-proxy-label" style="color:#d1d5db">-</span>`;
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
					<button class="cfwaf-dns-btn cfwaf-dns-btn-danger cfwaf-dns-btn-sm cfwaf-delete-btn" data-id="${r.id}" data-name="${r.name}" aria-label="Delete record" title="Delete">${wpwafIcon('x')}</button>
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
				if(res.success){ loadRecords(); toast(nowProxied?'Proxy enabled':'Proxy disabled', true, nowProxied?'cloud':'cloud-off'); }
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
	// TTL is locked to Auto only while the record is proxied.
	if(rec?.proxied){ qs('#cfwaf-dns-ttl').value = 1; }
	qs('#cfwaf-dns-ttl').disabled = !!rec?.proxied;

	if(rec?.type === 'SRV' && rec.data){
		// Cloudflare keeps the service and protocol in the name: _sip._tcp.example.com
		const m = /^(_[^.]+)\.(_[^.]+)\.?(.*)$/.exec(rec.name || '');
		qs('#cfwaf-dns-name').value     = m ? (m[3] || '@') : (rec.name || '');
		qs('#cfwaf-srv-service').value  = rec.data.service || (m ? m[1] : '');
		qs('#cfwaf-srv-proto').value    = rec.data.proto || (m ? m[2] : '_tcp');
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
	const hasPriority = ['MX','URI'].includes(type);
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
			wpwafSetText(errEl, 'alert', (res.data?.message||'Save failed'));
			errEl.style.display = '';
			return;
		}
		closeModal();
		loadRecords();
		toast(editingId ? 'Record updated' : 'Record created');
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
		if(res.success){ loadRecords(); toast('Record deleted'); }
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
