/* WP WAF Manager: IP Access Rules screen.
 * Values from PHP arrive in window.wpwafPage, printed by WPWAF_Admin::page_script()
 * in includes/views/page-ip-rules.php. */
'use strict';
(function(){
const NONCE      = wpwafPage.nonce;
const AJAX_URL   = wpwafPage.ajax_url;
const ACTIVE_ID  = wpwafPage.active_id;

let accountId       = '';   // Cloudflare account ID
let pluginAccountId = ACTIVE_ID; // plugin's internal account ID
let allRules        = [];
let activeMode      = '';
let editingId       = '';

function qs(s){ return document.querySelector(s); }
function qsa(s){ return Array.from(document.querySelectorAll(s)); }
function toast(msg, ok=true, icon){
	const el = document.createElement('div');
	el.className = 'cfwaf-ip-toast ' + (ok?'ok':'err');
	if (window.wpwafToastBody) el.innerHTML = wpwafToastBody(msg, ok, icon); else el.textContent = msg;
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
	wpwafSetText(el, 'alert', msg);
	el.style.display = '';
}

// ── Account selector ─────────────────────────────────────────────────────────
const accountSel = qs('#cfwaf-ip-account-select');
if (accountSel) {
	accountSel.addEventListener('change', function(){
		pluginAccountId = this.value;
		accountId = ''; // reset Cloudflare account ID - will re-fetch
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
	whitelist:    { label:'Allow',        cls:'whitelist',   icon:'circle-check' },
	block:        { label:'Block',         cls:'block',       icon:'ban' },
	managed_challenge: { label:'Managed Challenge', cls:'challenge', icon:'shield' },
	challenge:    { label:'Challenge',     cls:'challenge',   icon:'alert' },
	js_challenge: { label:'JS Challenge',  cls:'js_challenge',icon:'lock' },
};
const TARGET_LABELS = { ip:'IP', ip_range:'CIDR', country:'Country', asn:'ASN' };

function fmtDate(ts){
	if (!ts) return '-';
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
			(allRules.length ? 'No rules match your filter.' : 'No IP rules yet. Click Add Rule to get started.') +
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
			<td><span class="cfwaf-ip-target">${escHtml(TARGET_LABELS[target]||target)}</span></td>
			<td><span class="cfwaf-ip-value">${target === 'country' && window.wpwafFlag ? window.wpwafFlag(value) : ''}${escHtml(value)}</span></td>
			<td><span class="cfwaf-ip-mode ${escHtml(m.cls)}">${wpwafIcon(m.icon)} ${escHtml(m.label)}</span></td>
			<td class="cfwaf-ip-note">${escHtml(note)}</td>
			<td style="font-size:11px;color:var(--ip-muted);white-space:nowrap;">${escHtml(added)}</td>
			<td>
				<div style="display:flex;gap:4px;">
					<button class="cfwaf-ip-btn cfwaf-ip-btn-edit cfwaf-ip-edit-btn" data-id="${escHtml(r.id)}">Edit</button>
					<button class="cfwaf-ip-btn cfwaf-ip-btn-danger cfwaf-ip-del-btn" data-id="${escHtml(r.id)}" data-value="${escHtml(value)}" aria-label="Delete rule" title="Delete">${wpwafIcon('x')}</button>
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
	// The older "Challenge" mode is only offered when editing a rule that already uses it.
	qs('#cfwaf-ip-mode-legacy-wrap').style.display = mode === 'challenge' ? '' : 'none';
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

	updateCountryNote();
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

// Cloudflare only allows Block by country on Enterprise plans.
function updateCountryNote(){
	const target = qs('#cfwaf-ip-target').value;
	const mode   = document.querySelector('input[name="cfwaf-ip-mode"]:checked')?.value || '';
	qs('#cfwaf-ip-country-note').style.display = (target === 'country' && mode === 'block') ? '' : 'none';
}

qs('#cfwaf-ip-target')?.addEventListener('change', () => { updateValueHint(); updateCountryNote(); });
document.querySelectorAll('input[name="cfwaf-ip-mode"]').forEach(r => r.addEventListener('change', updateCountryNote));
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
			if (!res.success){ wpwafSetText(errEl, 'alert', (res.data?.message||'Save failed')); errEl.style.display=''; return; }
			closeModal(); loadRules(); toast('Rule updated');
		});
	} else {
		ajax('wpwaf_ip_rules_create', {account_id:accountId, plugin_account_id:pluginAccountId, mode, target, value, note}, (res) => {
			saveBtn.disabled=false; saveBtn.textContent='Add Rule';
			if (!res.success){ wpwafSetText(errEl, 'alert', (res.data?.message||'Create failed')); errEl.style.display=''; return; }
			closeModal(); loadRules(); toast('Rule created');
		});
	}
});

// ── Delete ─────────────────────────────────────────────────────────────────────
function deleteRule(id, value){
	if (!confirm(`Delete rule for "${value}"? This applies to all zones immediately.`)) return;
	ajax('wpwaf_ip_rules_delete', {account_id:accountId, plugin_account_id:pluginAccountId, rule_id:id}, (res) => {
		if (res.success){ loadRules(); toast('Rule deleted'); }
		else toast('Failed: '+(res.data?.message||'error'), false);
	});
}

document.addEventListener('keydown', e => {
	if (e.key === 'Escape' && qs('#cfwaf-ip-modal').style.display !== 'none') closeModal();
});
})();
