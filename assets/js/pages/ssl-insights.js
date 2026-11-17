/* WP WAF Manager: SSL & Insights screen.
 * Values from PHP arrive in window.wpwafPage, printed by WPWAF_Admin::page_script()
 * in includes/views/page-ssl-insights.php. */
(function(){
'use strict';
if ( ! wpwafPage.has_creds ) { return; }
const NONCE    = wpwafPage.nonce;
const AJAX_URL = wpwafPage.ajax_url;
const CACHE    = wpwafPage.cache;

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

function toast(msg, ok = true, icon){
	const el = document.createElement('div');
	el.className = 'wpwaf-si-toast ' + (ok ? 'ok' : 'err');
	if (window.wpwafToastBody) el.innerHTML = wpwafToastBody(msg, ok, icon); else el.textContent = msg;
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
	return '<span class="wpwaf-si-pill wpwaf-si-pill--ok">' + wpwafIconText('check', 'OK') + '</span>';
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
			? '<button type="button" class="wpwaf-si-btn wpwaf-si-btn--icon wpwaf-si-scan-one" data-zone="' + esc(z.id) + '" title="Scan again" aria-label="Scan ' + esc(z.name) + ' again">' + wpwafIcon('refresh') + '</button>'
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
	if (!scanning) btn.innerHTML = wpwafIconText('refresh', filtered ? 'Scan ' + list.length + ' shown zone' + (list.length === 1 ? '' : 's') : 'Scan all zones');
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
			toast(failed ? 'Scanned ' + (done - failed) + ' of ' + total + ' zones. ' + failed + ' failed.' : 'Scanned ' + total + ' zone' + (total === 1 ? '' : 's'), !failed);
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
				toast((FIX_DONE[key] || cap(key)) + ' on ' + (z ? z.name : 'zone'));
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
		toast(dismiss ? 'Insight dismissed' : 'Insight restored');
	});
});

if (location.hash === '#insights') showTab('insights');
})();
