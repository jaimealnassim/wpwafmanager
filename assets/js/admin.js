/* Cloudflare WAF Manager v1.0.0 — Vanilla JS, no jQuery */
'use strict';

(function () {

	// ── State ──────────────────────────────────────────────────────────────────
	const WAF    = window.cfWAF || {};
	let settings = JSON.parse(JSON.stringify(WAF.settings || {}));
	let zones    = [];
	let accounts = WAF.accounts ? JSON.parse(JSON.stringify(WAF.accounts)) : [];
	let activeId = WAF.active_id || '';

	// ── DOM Utilities ──────────────────────────────────────────────────────────
	function qs(sel, ctx)  { return (ctx || document).querySelector(sel); }
	function qsa(sel, ctx) { return Array.from((ctx || document).querySelectorAll(sel)); }
	function on(el, evt, fn) { if (el) el.addEventListener(evt, fn); }

	function delegate(parent, evt, sel, fn) {
		on(parent, evt, function (e) {
			const t = e.target.closest(sel);
			if (t && parent.contains(t)) fn(e, t);
		});
	}

	function escHtml(str) {
		return String(str)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

	// ── AJAX ───────────────────────────────────────────────────────────────────
	function ajax(action, data, cb) {
		const fd = new FormData();
		fd.append('action', action);
		fd.append('nonce', WAF.nonce);
		Object.keys(data).forEach(function (k) {
			if (Array.isArray(data[k])) {
				data[k].forEach(function (v) { fd.append(k + '[]', v); });
			} else {
				fd.append(k, data[k]);
			}
		});
		fetch(WAF.ajax_url, { method: 'POST', body: fd })
			.then(function (r) { return r.json(); })
			.then(cb)
			.catch(function (err) { cb({ success: false, data: { message: err.message } }); });
	}

	// ── Settings helpers ───────────────────────────────────────────────────────
	function getVal(obj, path) {
		return path.split('.').reduce(function (o, k) { return (o != null) ? o[k] : undefined; }, obj);
	}

	function setVal(obj, path, val) {
		const keys = path.split('.');
		let cur = obj;
		for (let i = 0; i < keys.length - 1; i++) {
			if (typeof cur[keys[i]] !== 'object' || cur[keys[i]] === null) cur[keys[i]] = {};
			cur = cur[keys[i]];
		}
		cur[keys[keys.length - 1]] = val;
	}

	// ── Toast ──────────────────────────────────────────────────────────────────
	function toast(msg, type) {
		const el = document.createElement('div');
		el.className = 'cfwaf-toast cfwaf-toast-' + (type === 'error' ? 'error' : 'success');
		el.textContent = msg;
		document.body.appendChild(el);
		setTimeout(function () {
			el.style.opacity = '0';
			setTimeout(function () { el.remove(); }, 350);
		}, 3500);
	}

	// ── Sync UI → settings ─────────────────────────────────────────────────────
	function syncFromUI() {
		const cats = qsa('.cfwaf-cat-check:checked').map(function (el) { return el.value; });
		setVal(settings, 'rule1.verified_categories', cats);

		const ipTA = document.getElementById('cfwaf-allow-ips');
		if (ipTA) {
			const ips = ipTA.value.split('\n').map(function(s){ return s.trim(); }).filter(function(s){ return s !== ''; });
			setVal(settings, 'rule1.allow_ips', ips);
		}

		qsa('[data-setting]').forEach(function (el) {
			const key = el.dataset.setting;
			if (el.type === 'checkbox') {
				setVal(settings, key, el.checked);
			} else if (el.type === 'radio' && el.checked) {
				setVal(settings, key, el.value);
			}
		});

		if (window._cfwafPickerGetValue) {
			setVal(settings, 'rule4.countries', window._cfwafPickerGetValue());
		}
	}

	// ── Sync settings → UI ─────────────────────────────────────────────────────
	function syncToUI() {
		const cats = getVal(settings, 'rule1.verified_categories') || [];
		qsa('.cfwaf-cat-check').forEach(function (el) {
			el.checked = cats.indexOf(el.value) !== -1;
		});

		const ipTA2 = document.getElementById('cfwaf-allow-ips');
		if (ipTA2) {
			const savedIPs = getVal(settings, 'rule1.allow_ips') || [];
			ipTA2.value = savedIPs.join('\n');
			updateIPCount();
		}

		qsa('[data-setting]').forEach(function (el) {
			const val = getVal(settings, el.dataset.setting);
			if (val === undefined) return;
			if (el.type === 'checkbox') {
				el.checked = Boolean(val);
			} else if (el.type === 'radio') {
				el.checked = (el.value === val);
			}
		});

		const savedCountries = getVal(settings, 'rule4.countries') || [];
		if (window._cfwafPickerSetValue) window._cfwafPickerSetValue(savedCountries);

		updateCountryPanel();
		updateRule3Badge();
		updateVpnState();
	}

	// ── UI state helpers ───────────────────────────────────────────────────────
	function updateCountryPanel() {
		const cb    = qs('#cfwaf-country-toggle');
		const panel = qs('#cfwaf-country-panel');
		if (!panel) return;
		const show = cb ? cb.checked : Boolean(getVal(settings, 'rule4.challenge_country'));
		panel.style.display = show ? '' : 'none';
	}

	function updateRule3Badge() {
		const badge = qs('#rule3-action-badge');
		if (!badge) return;
		const v = getVal(settings, 'rule3.action') || 'block';
		badge.textContent = (v === 'block') ? 'BLOCK' : 'CHALLENGE';
		badge.className   = 'cfwaf-action-badge ' + (v === 'block' ? 'cfwaf-badge-block' : 'cfwaf-badge-challenge');
	}

	function updateVpnState() {
		const allOn = Boolean(getVal(settings, 'rule5.challenge_all_vpn'));
		qsa('.cfwaf-vpn-check').forEach(function (el) {
			el.disabled = allOn;
			if (allOn) el.checked = true;
		});
	}

	// ── Tabs ───────────────────────────────────────────────────────────────────
	delegate(document, 'click', '.cfwaf-tab', function (e, tab) {
		qsa('.cfwaf-tab').forEach(function (t) { t.classList.remove('active'); });
		qsa('.cfwaf-tab-content').forEach(function (c) { c.classList.remove('active'); });
		tab.classList.add('active');
		const panel = qs('#cfwaf-tab-' + tab.dataset.tab);
		if (panel) panel.classList.add('active');
		if (tab.dataset.tab === 'expressions') refreshPreview();
	});

	// ── Rule accordion ─────────────────────────────────────────────────────────
	delegate(document, 'click', '.cfwaf-rule-header', function (e, header) {
		if (e.target.closest('.cfwaf-rule-controls')) return;
		const body = header.nextElementSibling;
		if (body && body.classList.contains('cfwaf-rule-body')) {
			body.classList.toggle('open');
		}
	});
	qsa('.cfwaf-rule-body').forEach(function (b) { b.classList.add('open'); });

	// ── Auth method toggle ─────────────────────────────────────────────────────
	delegate(document, 'click', '.cfwaf-auth-tab', function (e, btn) {
		qsa('.cfwaf-auth-tab').forEach(function (t) { t.classList.remove('active'); });
		btn.classList.add('active');
		const method = btn.dataset.method;
		['token', 'key'].forEach(function (m) {
			const p = qs('#cfwaf-auth-' + m);
			if (p) p.style.display = (m === method) ? '' : 'none';
		});
	});

	// ── Settings change handler ────────────────────────────────────────────────
	on(document, 'change', function (e) {
		const el = e.target;
		if (el.matches('[data-setting]')) {
			syncFromUI();
			const key = el.dataset.setting;
			if (key === 'rule4.challenge_country') updateCountryPanel();
			if (key === 'rule3.action')            updateRule3Badge();
			if (key === 'rule5.challenge_all_vpn') updateVpnState();
		} else if (el.matches('.cfwaf-cat-check')) {
			syncFromUI();
		} else if (el.matches('.cfwaf-zone-check')) {
			const item = el.closest('.cfwaf-zone-item');
			if (item) item.classList.toggle('selected', el.checked);
			updateDeployBtn();
		}
	});

	// ── Expiry helpers ─────────────────────────────────────────────────────────
	function formatExpiryStatus(expiresAt) {
		if (!expiresAt) return 'Saved forever';
		const diff = expiresAt - Math.floor(Date.now() / 1000);
		if (diff <= 0) return 'Credentials expired';
		if (diff < 3600) return 'Expires in ' + Math.ceil(diff / 60) + ' min';
		if (diff < 86400) return 'Expires in ' + Math.ceil(diff / 3600) + 'h';
		return 'Expires in ~1 day';
	}

	function showExpiryStatus(id, expiresAt) {
		const el = qs('#' + id);
		if (el) el.textContent = formatExpiryStatus(expiresAt);
	}

	// ── Credentials: verify token ──────────────────────────────────────────────
	on(qs('#cfwaf-verify-token'), 'click', function () {
		const raw    = (qs('#cfwaf-api-token').value || '').trim();
		const token  = raw.indexOf('*') !== -1 ? '' : raw;
		const expiry = qs('#cfwaf-token-expiry') ? qs('#cfwaf-token-expiry').value : '0';
		const label  = qs('#cfwaf-account-label') ? qs('#cfwaf-account-label').value.trim() : '';
		const editId = qs('#cfwaf-editing-account-id') ? qs('#cfwaf-editing-account-id').value.trim() : '';
		const btn    = qs('#cfwaf-verify-token');
		btn.textContent = 'Verifying\u2026';
		btn.disabled = true;
		ajax('wpwaf_verify_credentials', {
			auth_method: 'token', api_token: token,
			expiry: expiry, label: label, account_id: editId
		}, function (res) {
			btn.textContent = 'Verify & Save';
			btn.disabled = false;
			if (res.success) {
				setConnected(res.data.message);
				WAF.has_creds  = true;
				WAF.expires_at = res.data.expires_at || 0;
				accounts = res.data.accounts || [];
				activeId = res.data.active_id || '';
				renderAccountsList();
				showExpiryStatus('cfwaf-token-expiry-status', WAF.expires_at);
				resetAccountForm();
				loadZones();
			} else {
				toast('\u2717 ' + ((res.data && res.data.message) || 'Token invalid'), 'error');
				const badge = qs('#cfwaf-token-status');
				if (badge) { badge.textContent = 'Error'; badge.classList.remove('connected'); }
			}
		});
	});

	// ── Credentials: verify email + key ───────────────────────────────────────
	on(qs('#cfwaf-verify-key'), 'click', function () {
		const email  = (qs('#cfwaf-email') ? qs('#cfwaf-email').value : '').trim();
		const rawKey = (qs('#cfwaf-api-key') ? qs('#cfwaf-api-key').value : '').trim();
		const apiKey = rawKey.indexOf('*') !== -1 ? '' : rawKey;
		const expiry = qs('#cfwaf-key-expiry') ? qs('#cfwaf-key-expiry').value : '0';
		const label  = qs('#cfwaf-account-label') ? qs('#cfwaf-account-label').value.trim() : '';
		const editId = qs('#cfwaf-editing-account-id') ? qs('#cfwaf-editing-account-id').value.trim() : '';
		if (!email)  { toast('Please enter your Cloudflare account email.', 'error'); return; }
		if (!apiKey && rawKey.indexOf('*') === -1) { toast('Please enter your Global API Key.', 'error'); return; }
		const btn = qs('#cfwaf-verify-key');
		btn.textContent = 'Verifying\u2026';
		btn.disabled = true;
		ajax('wpwaf_verify_credentials', {
			auth_method: 'key', email: email, api_key: apiKey,
			expiry: expiry, label: label, account_id: editId
		}, function (res) {
			btn.textContent = 'Verify & Save';
			btn.disabled = false;
			if (res.success) {
				setConnected(res.data.message);
				WAF.has_creds  = true;
				WAF.expires_at = res.data.expires_at || 0;
				accounts = res.data.accounts || [];
				activeId = res.data.active_id || '';
				renderAccountsList();
				showExpiryStatus('cfwaf-key-expiry-status', WAF.expires_at);
				resetAccountForm();
				loadZones();
			} else {
				toast('\u2717 ' + ((res.data && res.data.message) || 'Credentials invalid'), 'error');
			}
		});
	});

	function setConnected(msg) {
		const badge = qs('#cfwaf-token-status');
		if (badge) { badge.textContent = 'Connected'; badge.classList.add('connected'); }
		toast('\u2713 ' + msg);
	}

	// ── Zones ──────────────────────────────────────────────────────────────────
	function loadZones() {
		ajax('wpwaf_list_zones', {}, function (res) {
			if (res.success) {
				zones = res.data.zones || [];
				populateInspectDropdown();
			}
		});
	}

	function populateInspectDropdown() {
		const sel = qs('#cfwaf-zone-inspect');
		if (!sel) return;
		while (sel.options.length > 1) sel.remove(1);
		zones.forEach(function (z) {
			sel.add(new Option(z.name + ' [' + z.plan + ']', z.id));
		});
	}

	// ── Deploy panel ───────────────────────────────────────────────────────────
	on(qs('#cfwaf-toggle-deploy'), 'click', openDeployPanel);
	on(qs('#cfwaf-deploy-close'), 'click', function () {
		const p = qs('#cfwaf-deploy-panel');
		if (p) p.style.display = 'none';
	});

	function openDeployPanel() {
		syncFromUI();
		const panel     = qs('#cfwaf-deploy-panel');
		const loadingEl = qs('#cfwaf-deploy-loading');
		const readyEl   = qs('#cfwaf-deploy-ready');
		const noCredsEl = qs('#cfwaf-deploy-no-creds');
		const resultsEl = qs('#cfwaf-deploy-results');
		if (!panel) return;
		panel.style.display = '';
		panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
		[loadingEl, readyEl, noCredsEl].forEach(function (el) { if (el) el.style.display = 'none'; });
		if (resultsEl) resultsEl.innerHTML = '';

		if (!WAF.has_creds) { if (noCredsEl) noCredsEl.style.display = ''; return; }

		if (loadingEl) loadingEl.style.display = '';
		buildRulesSummary();

		if (zones.length > 0) {
			renderZones(zones);
		} else {
			ajax('wpwaf_list_zones', {}, function (res) {
				if (res.success) {
					zones = res.data.zones || [];
					populateInspectDropdown();
					renderZones(zones);
				} else if (loadingEl) {
					loadingEl.textContent = 'Failed: ' + ((res.data && res.data.message) || 'Error');
				}
			});
		}
	}

	function buildRulesSummary() {
		const el = qs('#cfwaf-deploy-summary');
		if (!el) return;
		const rules = [
			{ key: 'rule1', label: 'Allow Good Bots',                     action: 'Skip' },
			{ key: 'rule2', label: 'Block Aggressive Crawlers & WP Paths', action: 'Block' },
			{ key: 'rule3', label: 'Block Web Hosts & TOR',                action: getVal(settings, 'rule3.action') === 'block' ? 'Block' : 'Challenge' },
			{ key: 'rule4', label: 'Challenge Large Providers / Country',  action: 'Challenge' },
			{ key: 'rule5', label: 'Challenge VPN & wp-login',             action: 'Challenge' },
		];
		const enabled = rules.filter(function (r) { return getVal(settings, r.key + '.enabled'); });
		el.innerHTML = enabled.length
			? '<p>Rules to be deployed in order:</p><ul>' + enabled.map(function (r) {
				return '<li><strong>' + r.action + ':</strong> ' + r.label + '</li>';
			}).join('') + '</ul>'
			: '';
	}

	function renderZones(list) {
		const loadingEl = qs('#cfwaf-deploy-loading');
		const readyEl   = qs('#cfwaf-deploy-ready');
		const gridEl    = qs('#cfwaf-zones-grid');
		if (loadingEl) loadingEl.style.display = 'none';
		if (readyEl)   readyEl.style.display = '';
		if (!gridEl) return;
		gridEl.innerHTML = '';
		list.forEach(function (z) {
			const div = document.createElement('div');
			div.className = 'cfwaf-zone-item';
			div.innerHTML =
				'<input type="checkbox" class="cfwaf-zone-check" value="' + escHtml(z.id) + '">' +
				'<div><div class="cfwaf-zone-name">' + escHtml(z.name) + '</div>' +
				'<div class="cfwaf-zone-plan">' + escHtml(z.plan) + '</div></div>';
			gridEl.appendChild(div);
		});
		updateDeployBtn();
	}

	delegate(document, 'click', '.cfwaf-zone-item', function (e, item) {
		if (e.target.type === 'checkbox') return;
		const cb = item.querySelector('input[type="checkbox"]');
		if (!cb) return;
		cb.checked = !cb.checked;
		item.classList.toggle('selected', cb.checked);
		updateDeployBtn();
	});

	on(qs('#cfwaf-select-all-zones'), 'change', function (e) {
		qsa('.cfwaf-zone-check').forEach(function (cb) {
			cb.checked = e.target.checked;
			const item = cb.closest('.cfwaf-zone-item');
			if (item) item.classList.toggle('selected', e.target.checked);
		});
		updateDeployBtn();
	});

	on(qs('#cfwaf-zone-search'), 'input', function (e) {
		const q = e.target.value.toLowerCase();
		qsa('.cfwaf-zone-item').forEach(function (item) {
			const name = (item.querySelector('.cfwaf-zone-name') || {}).textContent || '';
			item.style.display = name.toLowerCase().indexOf(q) !== -1 ? '' : 'none';
		});
	});

	function updateDeployBtn() {
		const n   = qsa('.cfwaf-zone-check:checked').length;
		const btn = qs('#cfwaf-confirm-deploy');
		if (!btn) return;
		btn.disabled = n === 0;
		btn.innerHTML = '<span class="dashicons dashicons-upload"></span> Deploy to ' + n + ' Zone' + (n !== 1 ? 's' : '');
	}

	on(qs('#cfwaf-confirm-deploy'), 'click', function () {
		const ids = qsa('.cfwaf-zone-check:checked').map(function (cb) { return cb.value; });
		if (!ids.length) return;
		const btn = qs('#cfwaf-confirm-deploy');
		btn.innerHTML = '<span class="dashicons dashicons-update"></span> Deploying\u2026';
		btn.disabled  = true;
		ajax('wpwaf_deploy_rules', { zone_ids: ids, settings: JSON.stringify(settings) }, function (res) {
			btn.disabled = false;
			updateDeployBtn();
			const resultsEl = qs('#cfwaf-deploy-results');
			if (!resultsEl) return;
			const results = (res.data && res.data.results) ? res.data.results : {};
			let html = '';
			Object.keys(results).forEach(function (zid) {
				const r    = results[zid];
				const zone = zones.find(function (z) { return z.id === zid; });
				const name = escHtml(zone ? zone.name : zid);
				const cls  = r.success ? 'ok' : 'fail';
				const icon = r.success ? '\u2713' : '\u2717';
				const detail = r.success ? 'Rules deployed successfully' : escHtml(r.message || 'Unknown error');
				html += '<div class="cfwaf-result-item ' + cls + '">' + icon + ' <strong>' + name + '</strong> \u2014 ' + detail + '</div>';
			});
			resultsEl.innerHTML = html;
			const sc = (res.data && res.data.success_count) || 0;
			const rc = (res.data && res.data.rule_count)    || 0;
			const fc = (res.data && res.data.fail_count)    || 0;
			if (sc > 0) toast('\u2713 Deployed ' + rc + ' rules to ' + sc + ' zone(s)!');
			if (fc > 0) toast('\u2717 ' + fc + ' zone(s) failed \u2014 see results below.', 'error');
		});
	});

	// ── Save / Reset settings ──────────────────────────────────────────────────
	on(qs('#cfwaf-save-settings'), 'click', function () {
		syncFromUI();
		const btn = qs('#cfwaf-save-settings');
		btn.innerHTML = '<span class="dashicons dashicons-update"></span> Saving\u2026';
		btn.disabled  = true;
		ajax('wpwaf_save_settings', { settings: JSON.stringify(settings) }, function (res) {
			btn.innerHTML = '<span class="dashicons dashicons-saved"></span> Save Settings';
			btn.disabled  = false;
			res.success ? toast('\u2713 Settings saved!') : toast('\u2717 Save failed', 'error');
		});
	});

	on(qs('#cfwaf-reset-settings'), 'click', function () {
		if (!confirm('Reset all WAF rule settings to defaults? This cannot be undone.')) return;
		settings = JSON.parse(JSON.stringify(WAF.defaults));
		syncToUI();
		toast('Settings reset to defaults.');
	});

	// ── Expression preview ─────────────────────────────────────────────────────
	on(qs('#cfwaf-refresh-preview'), 'click', refreshPreview);

	function refreshPreview() {
		syncFromUI();
		const con = qs('#cfwaf-expressions-container');
		if (!con) return;
		con.innerHTML = '<div class="cfwaf-loading-state"><span class="cfwaf-spinner"></span> Building expressions\u2026</div>';
		ajax('wpwaf_preview_rules', { settings: JSON.stringify(settings) }, function (res) {
			con.innerHTML = '';
			if (!res.success) { con.innerHTML = '<p class="cfwaf-notice cfwaf-notice-error">Failed to generate expressions.</p>'; return; }
			const rules = (res.data && res.data.rules) ? res.data.rules : [];
			if (!rules.length) { con.innerHTML = '<p class="cfwaf-hint">No rules are currently enabled.</p>'; return; }
			rules.forEach(function (rule) {
				const cls = rule.action === 'skip' ? 'cfwaf-badge-skip' : rule.action === 'block' ? 'cfwaf-badge-block' : 'cfwaf-badge-challenge';
				const div = document.createElement('div');
				div.className = 'cfwaf-expr-block';
				div.innerHTML =
					'<div class="cfwaf-expr-header">' +
					'<h4>' + escHtml(rule.description) + ' <span class="cfwaf-action-badge ' + cls + '">' + rule.action.toUpperCase() + '</span></h4>' +
					'<button class="cfwaf-copy-btn" data-expr="' + escHtml(rule.expression) + '">Copy</button>' +
					'</div>' +
					'<div class="cfwaf-expr-body"><code>' + escHtml(rule.expression) + '</code></div>';
				con.appendChild(div);
			});
		});
	}

	delegate(document, 'click', '.cfwaf-copy-btn', function (e, btn) {
		const expr = btn.dataset.expr;
		if (navigator.clipboard) {
			navigator.clipboard.writeText(expr).then(function () {
				btn.textContent = 'Copied!';
				setTimeout(function () { btn.textContent = 'Copy'; }, 2000);
			});
		} else {
			const ta = document.createElement('textarea');
			ta.value = expr;
			ta.style.cssText = 'position:fixed;top:0;left:0;opacity:0';
			document.body.appendChild(ta);
			ta.select();
			document.execCommand('copy');
			ta.remove();
			btn.textContent = 'Copied!';
			setTimeout(function () { btn.textContent = 'Copy'; }, 2000);
		}
	});

	// ── Zone rules inspection ──────────────────────────────────────────────────
	on(qs('#cfwaf-inspect-zone'), 'click', function () {
		const zid = qs('#cfwaf-zone-inspect') ? qs('#cfwaf-zone-inspect').value : '';
		if (!zid) return;
		const out = qs('#cfwaf-zone-rules-output');
		if (!out) return;
		out.innerHTML = '<div class="cfwaf-loading-state"><span class="cfwaf-spinner"></span> Loading rules\u2026</div>';
		ajax('wpwaf_list_zone_rules', { zone_id: zid }, function (res) {
			out.innerHTML = '';
			if (!res.success) { out.innerHTML = '<p class="cfwaf-notice cfwaf-notice-error">' + escHtml((res.data && res.data.message) || 'Error loading rules.') + '</p>'; return; }
			const rules = (res.data && res.data.rules) ? res.data.rules : [];
			if (!rules.length) { out.innerHTML = '<p class="cfwaf-hint">No WAF custom rules found on this zone.</p>'; return; }
			rules.forEach(function (r) {
				const cls   = r.action === 'skip' ? 'cfwaf-badge-skip' : r.action === 'block' ? 'cfwaf-badge-block' : 'cfwaf-badge-challenge';
				const dot   = r.enabled ? '\u25cf' : '\u25cb';
				const color = r.enabled ? '#059669' : '#dc2626';
				const div   = document.createElement('div');
				div.className = 'cfwaf-zone-rule-item';
				div.innerHTML =
					'<h4>' + escHtml(r.description || 'Unnamed Rule') +
					' <span class="cfwaf-action-badge ' + cls + '">' + escHtml((r.action || '').toUpperCase()) + '</span>' +
					' <span style="font-size:11px;color:' + color + '">' + dot + (r.enabled ? ' Active' : ' Disabled') + '</span></h4>' +
					'<code style="font-size:11px;word-break:break-all;display:block;margin-top:4px">' + escHtml(r.expression || '') + '</code>';
				out.appendChild(div);
			});
		});
	});

	// ── Rule Details accordion ─────────────────────────────────────────────────
	delegate(document, 'click', '.cfwaf-detail-header', function (e, header) {
		const key  = header.dataset.detail;
		const body = qs('#cfwaf-detail-' + key);
		if (!body) return;
		const isOpen = body.classList.contains('open');
		body.classList.toggle('open', !isOpen);
		header.classList.toggle('open', !isOpen);
	});

	// ── Multi-account ──────────────────────────────────────────────────────────
	function renderAccountsList() {
		const list = qs('#cfwaf-accounts-list');
		if (!list) return;
		list.innerHTML = '';
		accounts.forEach(function (acc) {
			const isActive = acc.id === activeId;
			const method   = acc.auth_method === 'key' ? 'Email + Key' : 'API Token';
			const expTs    = acc.expires_at || 0;
			const expStr   = expTs > 0
				? ' · Expires ' + new Date(expTs * 1000).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
				: ' · Saved forever';
			const div = document.createElement('div');
			div.className = 'cfwaf-account-item' + (isActive ? ' active-account' : '');
			div.innerHTML =
				'<div>' +
				'<div class="cfwaf-account-item-name">' + escHtml(acc.label || 'Unnamed') + '</div>' +
				'<div class="cfwaf-account-item-meta">' + escHtml(method + expStr) + '</div>' +
				'</div>' +
				'<span class="cfwaf-account-badge' + (isActive ? ' active' : '') + '">' + (isActive ? 'Active' : 'Inactive') + '</span>' +
				'<div class="cfwaf-account-item-actions">' +
				(!isActive ? '<button type="button" class="cfwaf-btn-account-switch" data-account-id="' + escHtml(acc.id) + '">Switch</button>' : '') +
				'<button type="button" class="cfwaf-btn-account-edit" data-edit-id="' + escHtml(acc.id) + '">Edit</button>' +
				'<button type="button" class="cfwaf-btn-account-delete" data-account-id="' + escHtml(acc.id) + '">Remove</button>' +
				'</div>';
			list.appendChild(div);
		});
		const wrapper = list.parentElement;
		if (wrapper) wrapper.style.display = accounts.length ? '' : 'none';
		const title = qs('#cfwaf-form-title');
		if (title) title.textContent = accounts.length ? 'Add Another Account' : 'Connect Cloudflare Account';
	}

	delegate(document, 'click', '.cfwaf-btn-account-switch', function (e, btn) {
		const id = btn.dataset.accountId;
		if (!id) return;
		ajax('wpwaf_switch_account', { account_id: id }, function (res) {
			if (!res.success) return;
			accounts = res.data.accounts;
			activeId = res.data.active_id;
			renderAccountsList();
			setConnected('Switched to account');
			zones = [];
			loadZones();
		});
	});

	delegate(document, 'click', '.cfwaf-btn-account-delete', function (e, btn) {
		if (!confirm('Remove this account? This cannot be undone.')) return;
		const id = btn.dataset.accountId;
		ajax('wpwaf_delete_account', { account_id: id }, function (res) {
			if (!res.success) return;
			accounts = res.data.accounts;
			activeId = res.data.active_id;
			renderAccountsList();
			toast('\u2713 Account removed');
			if (!accounts.length) {
				const badge = qs('#cfwaf-token-status');
				if (badge) { badge.textContent = 'Not Connected'; badge.classList.remove('connected'); }
			}
		});
	});

	delegate(document, 'click', '.cfwaf-btn-account-edit', function (e, btn) {
		const id  = btn.dataset.editId;
		const acc = accounts.find(function (a) { return a.id === id; });
		if (!acc) return;

		// Prefill label
		if (qs('#cfwaf-account-label'))      qs('#cfwaf-account-label').value = acc.label || '';
		if (qs('#cfwaf-editing-account-id')) qs('#cfwaf-editing-account-id').value = acc.id;

		// Prefill expiry selects — pick closest option
		var expiresAt = acc.expires_at || 0;
		var expiryVal = '0'; // forever
		if (expiresAt > 0) {
			var remaining = expiresAt - Math.floor(Date.now() / 1000);
			if (remaining <= 3600)      expiryVal = '3600';
			else if (remaining <= 28800) expiryVal = '28800';
			else                         expiryVal = '86400';
		}
		if (qs('#cfwaf-token-expiry')) qs('#cfwaf-token-expiry').value = expiryVal;
		if (qs('#cfwaf-key-expiry'))   qs('#cfwaf-key-expiry').value   = expiryVal;

		// Open panel and set auth method tab
		const details = qs('#cfwaf-add-account-details');
		if (details) details.open = true;
		if (qs('#cfwaf-form-title')) qs('#cfwaf-form-title').textContent = 'Edit Account';
		const method = acc.auth_method || 'token';
		qsa('.cfwaf-auth-tab').forEach(function (t) { t.classList.toggle('active', t.dataset.method === method); });
		if (qs('#cfwaf-auth-token')) qs('#cfwaf-auth-token').style.display = method === 'key' ? 'none' : '';
		if (qs('#cfwaf-auth-key'))   qs('#cfwaf-auth-key').style.display   = method === 'key' ? '' : 'none';

		// Show cancel buttons
		qsa('.cfwaf-btn-cancel-form').forEach(function(b) { b.style.display = ''; });
		// Scroll into view
		const card = qs('.cfwaf-card-token');
		if (card) card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
	});

	// ── Country grid picker ────────────────────────────────────────────────────
	function initCountryPicker() {
		const grid      = qs('#cfwaf-country-grid');
		const searchEl  = qs('#cfwaf-country-search');
		const selAllEl  = qs('#cfwaf-country-select-all');
		const clrAllEl  = qs('#cfwaf-country-clear-all');
		const countEl   = qs('#cfwaf-country-count');
		const emptyEl   = qs('#cfwaf-country-empty');
		if (!grid) return;

		// Region country codes
		var REGIONS = {
			'north-america': ['US','CA','MX'],
			'europe': ['AL','AD','AT','BY','BE','BA','BG','HR','CY','CZ','DK','EE','FI','FR','DE','GR','HU','IS','IE','IT','LV','LI','LT','LU','MT','MD','MC','ME','MK','NL','NO','PL','PT','RO','RU','SM','RS','SK','SI','ES','SE','CH','UA','GB'],
			'asia': ['AF','AM','AZ','BH','BD','BT','BN','KH','CN','GE','HK','IN','ID','IR','IQ','IL','JP','JO','KZ','KW','KG','LA','LB','MY','MV','MN','MM','NP','KP','OM','PK','PH','QA','SA','SG','KR','LK','SY','TW','TJ','TH','TL','TR','TM','AE','UZ','VN','YE'],
			'oceania': ['AU','FJ','KI','MH','FM','NR','NZ','PW','PG','WS','SB','TO','TV','VU'],
			'latin-america': ['AR','BB','BZ','BO','BR','CL','CO','CR','CU','DM','DO','EC','SV','GD','GT','GY','HT','HN','JM','MX','NI','PA','PY','PE','KN','LC','VC','TT','UY','VE'],
			'mena': ['DZ','BH','EG','IR','IQ','JO','KW','LB','LY','MA','OM','PS','QA','SA','SY','TN','AE','YE'],
			'gcc': ['BH','KW','OM','QA','SA','AE'],
			'africa': ['DZ','AO','BJ','BW','BF','BI','CV','CM','CF','TD','KM','CG','DJ','EG','GQ','ER','SZ','ET','GA','GM','GH','GN','GW','KE','LS','LR','LY','MG','MW','ML','MR','MU','MA','MZ','NA','NE','NG','RW','ST','SN','SC','SL','SO','ZA','SS','SD','TZ','TG','TN','UG','ZM','ZW']
		};

		var selected = new Set(getVal(settings, 'rule4.countries') || []);

		function countryItems() { return qsa('.cfwaf-zone-item[data-cc]', grid); }

		function syncSettings() {
			setVal(settings, 'rule4.countries', Array.from(selected));
		}

		function updateCount() {
			var n = selected.size;
			if (countEl) countEl.textContent = n + ' selected';
			if (selAllEl) selAllEl.checked = (n > 0 && n === countryItems().filter(function(i){ return i.style.display !== 'none'; }).length);
		}

		function renderGrid() {
			countryItems().forEach(function(item) {
				var cc = item.dataset.cc;
				var cb = item.querySelector('.cfwaf-country-check');
				var isSelected = selected.has(cc);
				if (cb) cb.checked = isSelected;
				item.classList.toggle('selected', isSelected);
			});
			updateCount();
			// Sync region button active states
			qsa('.cfwaf-region-btn').forEach(function(b) {
				var rCodes = REGIONS[b.dataset.region] || [];
				b.classList.toggle('active', rCodes.length > 0 && rCodes.every(function(cc) { return selected.has(cc); }));
			});
		}

		function filterGrid(q) {
			var visible = 0;
			countryItems().forEach(function(item) {
				var name = (item.querySelector('.cfwaf-zone-name') || {}).textContent || '';
				var cc   = item.dataset.cc || '';
				var match = !q || name.toLowerCase().indexOf(q) !== -1 || cc.toLowerCase().indexOf(q) !== -1;
				item.style.display = match ? '' : 'none';
				if (match) visible++;
			});
			if (emptyEl) emptyEl.style.display = visible === 0 ? '' : 'none';
		}

		// Click on card toggles country
		delegate(grid, 'click', '.cfwaf-zone-item[data-cc]', function(e, item) {
			if (e.target.type === 'checkbox') return; // let change handle it
			var cc = item.dataset.cc;
			if (!cc) return;
			selected.has(cc) ? selected.delete(cc) : selected.add(cc);
			item.classList.toggle('selected', selected.has(cc));
			var cb = item.querySelector('.cfwaf-country-check');
			if (cb) cb.checked = selected.has(cc);
			syncSettings(); updateCount();
		});

		on(grid, 'change', function(e) {
			if (!e.target.classList.contains('cfwaf-country-check')) return;
			var cc = e.target.value;
			e.target.checked ? selected.add(cc) : selected.delete(cc);
			var item = e.target.closest('.cfwaf-zone-item');
			if (item) item.classList.toggle('selected', e.target.checked);
			syncSettings(); updateCount();
		});

		// Search
		if (searchEl) on(searchEl, 'input', function() { filterGrid(searchEl.value.toLowerCase()); });

		// Select All / Clear All
		if (selAllEl) on(selAllEl, 'change', function() {
			countryItems().forEach(function(item) {
				if (item.style.display === 'none') return;
				var cc = item.dataset.cc;
				selAllEl.checked ? selected.add(cc) : selected.delete(cc);
				var cb = item.querySelector('.cfwaf-country-check');
				if (cb) cb.checked = selAllEl.checked;
				item.classList.toggle('selected', selAllEl.checked);
			});
			syncSettings(); updateCount();
		});

		if (clrAllEl) on(clrAllEl, 'click', function() {
			selected.clear();
			renderGrid();
			syncSettings();
		});

		// Region quick-picks — additive, multiple can be active at once
		delegate(document, 'click', '.cfwaf-region-btn', function(e, btn) {
			var region = btn.dataset.region;
			var codes  = REGIONS[region] || [];
			// If all countries in this region are currently selected → deselect them (toggle off)
			// Otherwise → add them all (toggle on), even if some were already selected
			var allAlreadyOn = codes.every(function(cc) { return selected.has(cc); });
			codes.forEach(function(cc) {
				allAlreadyOn ? selected.delete(cc) : selected.add(cc);
			});
			// Update this button's active state based on whether all its countries are now selected
			btn.classList.toggle('active', !allAlreadyOn);
			// Sync all region buttons to reflect current selection state
			qsa('.cfwaf-region-btn').forEach(function(b) {
				var rCodes = REGIONS[b.dataset.region] || [];
				b.classList.toggle('active', rCodes.length > 0 && rCodes.every(function(cc) { return selected.has(cc); }));
			});
			renderGrid(); syncSettings();
		});

		// Expose for syncToUI
		window._cfwafPickerSetValue = function(vals) {
			selected = new Set(vals || []);
			renderGrid(); syncSettings();
		};
		window._cfwafPickerGetValue = function() { return Array.from(selected); };

		renderGrid();
	}

	// ── Cancel form button ───────────────────────────────────────────────────
	function resetAccountForm() {
		if (qs('#cfwaf-editing-account-id'))  qs('#cfwaf-editing-account-id').value = '';
		if (qs('#cfwaf-account-label'))       qs('#cfwaf-account-label').value = '';
		if (qs('#cfwaf-api-token'))           qs('#cfwaf-api-token').value = '';
		if (qs('#cfwaf-email'))               qs('#cfwaf-email').value = '';
		if (qs('#cfwaf-api-key'))             qs('#cfwaf-api-key').value = '';
		if (qs('#cfwaf-form-title'))          qs('#cfwaf-form-title').textContent = accounts.length ? 'Add Another Account' : 'Connect Cloudflare Account';
		qsa('.cfwaf-btn-cancel-form').forEach(function(b) { b.style.display = 'none'; });
		const details = qs('#cfwaf-add-account-details');
		if (details) details.open = false;
	}
	delegate(document, 'click', '.cfwaf-btn-cancel-form', function() {
		resetAccountForm();
	});

	// ── IP allowlist textarea ─────────────────────────────────────────────────
	function updateIPCount() {
		const ta = document.getElementById('cfwaf-allow-ips');
		const countEl = document.getElementById('cfwaf-allow-ips-count');
		if (!ta || !countEl) return;
		const ips = ta.value.split('\n').map(function(s){ return s.trim(); }).filter(function(s){ return s !== ''; });
		countEl.textContent = ips.length ? ips.length + ' IP' + (ips.length !== 1 ? 's' : '') + ' allowlisted' : '';
	}
	on(document.getElementById('cfwaf-allow-ips'), 'input', function() {
		syncFromUI();
		updateIPCount();
	});

	// ── Expose settings helpers for export/import IIFE ───────────────────────
	window._cfwafGetSettings = function() { return settings; };
	window._cfwafSetSettings = function(s) { settings = s; };
	// Also expose sync functions
	window._cfwafSyncFromUI = syncFromUI;
	window._cfwafSyncToUI  = syncToUI;

	// ── Boot
	if (WAF.has_creds) {
		const badge = qs('#cfwaf-token-status');
		if (badge) { badge.textContent = 'Connected'; badge.classList.add('connected'); }
		loadZones();
	}

	showExpiryStatus('cfwaf-token-expiry-status', WAF.expires_at || 0);
	showExpiryStatus('cfwaf-key-expiry-status',   WAF.expires_at || 0);

	syncToUI();
	updateCountryPanel();
	initCountryPicker();

})();

// Injected after boot — Export / Import settings as JSON

(function () {

	// ── Known valid schema keys per rule ──────────────────────────────────────
	var SCHEMA = {
		rule1: {
			enabled: 'boolean',
			verified_categories: 'array',
			allow_ips: 'array',
			allow_backupbuddy: 'boolean', allow_blogvault: 'boolean', allow_updraftplus: 'boolean',
			allow_betterstack: 'boolean', allow_gtmetrix: 'boolean', allow_pingdom: 'boolean',
			allow_statuscake: 'boolean', allow_uptimerobot: 'boolean', allow_cf_image: 'boolean',
			allow_exactdn: 'boolean', allow_ewww: 'boolean', allow_flyingpress: 'boolean',
			allow_imagify: 'boolean', allow_shortpixel: 'boolean', allow_tinypng: 'boolean',
			allow_ahrefs: 'boolean', allow_ahrefs_audit: 'boolean', allow_mj12: 'boolean',
			allow_rogerbot: 'boolean', allow_screamingfrog: 'boolean', allow_semrush: 'boolean',
			allow_siteauditbot: 'boolean', allow_semrush_ocob: 'boolean', allow_sitelock: 'boolean',
			allow_sucuri: 'boolean', allow_virustotal: 'boolean', allow_wordfence: 'boolean',
			allow_facebook: 'boolean', allow_linkedin: 'boolean', allow_twitter: 'boolean',
			allow_jetpack: 'boolean', allow_mainwp: 'boolean', allow_managewp: 'boolean',
			allow_godaddy_uptime: 'boolean', allow_wpumbrella: 'boolean', allow_letsencrypt: 'boolean',
		},
		rule2: {
			enabled: 'boolean',
			block_yandex: 'boolean', block_sogou: 'boolean', block_semrush: 'boolean',
			block_ahrefs: 'boolean', block_baidu: 'boolean', block_neevabot: 'boolean',
			block_python: 'boolean', block_crawl: 'boolean', block_bot: 'boolean',
			block_spider: 'boolean', block_nikto: 'boolean', block_sqlmap: 'boolean',
			block_masscan: 'boolean', block_nmap: 'boolean', block_xmlrpc: 'boolean',
			block_wpconfig: 'boolean', block_wpjson: 'boolean', block_wpinstall: 'boolean',
			block_wlwmanifest: 'boolean', block_readme: 'boolean', block_license: 'boolean',
			block_sqli_sleep: 'boolean', block_path_traversal: 'boolean',
		},
		rule3: {
			enabled: 'boolean', action: 'string',
			block_do: 'boolean', block_linode: 'boolean', block_vultr: 'boolean',
			block_hetzner: 'boolean', block_ovh: 'boolean', block_contabo: 'boolean',
			block_scaleway: 'boolean', block_dreamhost: 'boolean', block_m247: 'boolean',
			block_leaseweb: 'boolean', block_godaddy: 'boolean', block_alibaba: 'boolean',
			block_hostroyale: 'boolean', block_cloudvider: 'boolean', block_tor: 'boolean',
		},
		rule4: {
			enabled: 'boolean', challenge_aws: 'boolean', challenge_gcp: 'boolean',
			challenge_azure: 'boolean', challenge_country: 'boolean', countries: 'array',
		},
		rule5: {
			enabled: 'boolean', challenge_all_vpn: 'boolean',
			challenge_nordvpn: 'boolean', challenge_expressvpn: 'boolean', challenge_purevpn: 'boolean',
			challenge_surfshark: 'boolean', challenge_ipvanish: 'boolean', challenge_quadranet: 'boolean',
			challenge_ovhfr: 'boolean', challenge_mullvad: 'boolean', challenge_privlayer: 'boolean',
			challenge_wplogin: 'boolean',
		},
	};

	function validateImport(data) {
		var errors = [];
		var rules = ['rule1','rule2','rule3','rule4','rule5'];
		rules.forEach(function(rule) {
			if (!data[rule]) return;
			var allowed = SCHEMA[rule];
			Object.keys(data[rule]).forEach(function(key) {
				if (!(key in allowed)) {
					errors.push(rule + ': unknown key "' + key + '"');
					return;
				}
				var val = data[rule][key];
				var expectedType = allowed[key];
				if (expectedType === 'boolean' && typeof val !== 'boolean') {
					errors.push(rule + '.' + key + ' must be boolean, got ' + typeof val);
				} else if (expectedType === 'array' && !Array.isArray(val)) {
					errors.push(rule + '.' + key + ' must be array, got ' + typeof val);
				} else if (expectedType === 'string' && typeof val !== 'string') {
					errors.push(rule + '.' + key + ' must be string, got ' + typeof val);
				}
			});
		});
		// Validate rule3.action values
		if (data.rule3 && data.rule3.action && !['block','managed_challenge'].includes(data.rule3.action)) {
			errors.push('rule3.action must be "block" or "managed_challenge"');
		}
		return errors;
	}

	// ── Export ────────────────────────────────────────────────────────────────
	var exportBtn = document.getElementById('cfwaf-export-settings');
	if (exportBtn) {
		exportBtn.addEventListener('click', function() {
			if (window._cfwafSyncFromUI) window._cfwafSyncFromUI();
			var currentSettings = window._cfwafGetSettings ? window._cfwafGetSettings() : {};
			var payload = {
				_meta: {
					plugin: 'Cloudflare WAF Manager',
					version: '1.0.0',
					exported: new Date().toISOString(),
				},
				settings: currentSettings,
			};
			var blob = new Blob([JSON.stringify(payload, null, 2)], { type: 'application/json' });
			var url = URL.createObjectURL(blob);
			var a = document.createElement('a');
			var ts = new Date().toISOString().replace(/:/g,'-').replace(/\..*/,'');
			a.href = url;
			a.download = 'cfwaf-settings-' + ts + '.json';
			document.body.appendChild(a);
			a.click();
			a.remove();
			URL.revokeObjectURL(url);
			toast('\u2713 Settings exported!');
		});
	}

	// ── Import ────────────────────────────────────────────────────────────────
	var importFile = document.getElementById('cfwaf-import-file');
	if (importFile) {
		importFile.addEventListener('change', function(e) {
			var file = e.target.files[0];
			if (!file) return;
			var reader = new FileReader();
			reader.onload = function(ev) {
				try {
					var parsed = JSON.parse(ev.target.result);
					// Accept either {settings:{...}} wrapper or bare settings object
					var imported = parsed.settings || parsed;
					// Validate schema
					var errors = validateImport(imported);
					if (errors.length) {
						console.error('[CF WAF Import] Validation errors:', errors);
						toast('\u2717 Import failed: ' + errors[0], 'error');
						return;
					}
					// Merge imported settings (imported values win)
					var WAF = window.cfWAF || {};
					var current = window._cfwafGetSettings ? window._cfwafGetSettings() : (WAF.settings || {});
					['rule1','rule2','rule3','rule4','rule5'].forEach(function(rule) {
						if (!imported[rule]) return;
						if (!current[rule]) current[rule] = {};
						Object.assign(current[rule], imported[rule]);
					});
					// Push back to plugin state
					if (window._cfwafSetSettings) window._cfwafSetSettings(current);
					// Sync to UI
					if (typeof syncToUI === 'function') syncToUI();
					// Auto-save to DB
					var WAFajax = window.cfWAF || {};
					var fd = new FormData();
					fd.append('action', 'wpwaf_save_settings');
					fd.append('nonce', WAFajax.nonce);
					fd.append('settings', JSON.stringify(current));
					fetch(WAFajax.ajax_url, { method: 'POST', body: fd })
						.then(function(r) { return r.json(); })
						.then(function(res) {
							if (res.success) {
								toast('\u2713 Settings imported and saved!');
							} else {
								toast('\u2713 Settings imported (save failed — try Save Settings manually)');
							}
						})
						.catch(function() {
							toast('\u2713 Settings imported (offline save skipped)');
						});
				} catch(err) {
					console.error('[CF WAF Import] Parse error:', err);
					toast('\u2717 Import failed: invalid JSON file', 'error');
				}
				// Reset file input so same file can be re-imported
				importFile.value = '';
			};
			reader.readAsText(file);
		});
	}

})();
