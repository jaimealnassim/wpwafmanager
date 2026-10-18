<?php
defined( 'ABSPATH' ) || exit;
$guide_url = WPWAF_Page_Rules::GUIDE_URL;
?>
<style>
:root{--pr-orange:#FF6A00;--pr-border:#e2e6ea;--pr-bg:#f8f9fb;--pr-dark:#1a1a2e;--pr-muted:#6b7280;--pr-green:#059669;--pr-red:#dc2626;}
.wpwaf-pr-wrap{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;color:var(--pr-dark);}
.wpwaf-pr-header{display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:20px;}
.wpwaf-pr-header h1{font-size:22px;font-weight:700;margin:0;padding:0;display:flex;align-items:center;gap:8px;color:var(--pr-dark);line-height:1.3;}
.wpwaf-pr-header h1 .dashicons{color:var(--pr-orange);font-size:24px;width:24px;height:24px;}
.wpwaf-pr-header-sub{font-size:12px;color:var(--pr-muted);margin-top:3px;}
.wpwaf-pr-notice{background:#fff8f5;border:1px solid #fcd9c0;border-radius:8px;padding:16px 20px;color:#92400e;font-size:13px;line-height:1.6;}
.wpwaf-pr-notice--error{background:#fef2f2;border-color:#fca5a5;color:#7f1d1d;}

/* Intro + help */
.wpwaf-pr-intro{display:grid;grid-template-columns:minmax(0,1.4fr) minmax(0,1fr);gap:16px;margin-bottom:20px;}
.wpwaf-pr-box{background:#fff;border:1px solid var(--pr-border);border-radius:10px;padding:16px 18px;font-size:13px;line-height:1.6;color:#374151;}
.wpwaf-pr-box h2{font-size:13px;font-weight:700;margin:0 0 6px;color:var(--pr-dark);}
.wpwaf-pr-box p{margin:0 0 8px;}
.wpwaf-pr-box p:last-child{margin-bottom:0;}
.wpwaf-pr-box ul{margin:4px 0 0;padding-left:18px;}
.wpwaf-pr-box li{margin:2px 0;}
.wpwaf-pr-box a{color:var(--pr-orange);}
.wpwaf-pr-box code{font-size:11px;background:#f3f4f6;padding:1px 5px;border-radius:4px;}

/* Toolbar */
.wpwaf-pr-toolbar{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:16px;}
.wpwaf-pr-search{height:34px;flex:1 1 220px;min-width:0;padding:0 12px;border:1px solid var(--pr-border);border-radius:6px;font-size:13px;background:#fff;}
.wpwaf-pr-search:focus{outline:none;border-color:var(--pr-orange);box-shadow:0 0 0 2px rgba(255,106,0,.1);}
.wpwaf-pr-check{display:inline-flex;align-items:center;gap:6px;font-size:12px;color:var(--pr-muted);white-space:nowrap;}
.wpwaf-pr-check--wrap{white-space:normal;align-items:flex-start;color:#374151;line-height:1.5;}
.wpwaf-pr-check--wrap input{margin-top:2px;flex-shrink:0;}
.wpwaf-pr-progress{font-size:12px;color:var(--pr-muted);white-space:nowrap;}

/* Buttons */
.wpwaf-pr-btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;height:32px;padding:0 14px;font-size:12px;font-weight:600;border-radius:6px;border:1px solid #d1d5db;background:#fff;color:#374151;cursor:pointer;transition:background .15s,border-color .15s;white-space:nowrap;text-decoration:none;line-height:1;}
.wpwaf-pr-btn:hover{background:#f3f4f6;border-color:#9ca3af;color:#374151;}
.wpwaf-pr-btn--primary{background:var(--pr-orange);border-color:var(--pr-orange);color:#fff;}
.wpwaf-pr-btn--primary:hover{background:#d95500;border-color:#d95500;color:#fff;}
.wpwaf-pr-btn--sm{height:28px;padding:0 10px;font-size:11px;}
.wpwaf-pr-btn:disabled{opacity:.5;cursor:not-allowed;}

/* Summary */
.wpwaf-pr-summary{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:20px;}
.wpwaf-pr-stat{background:#fff;border:1px solid var(--pr-border);border-radius:10px;padding:12px 14px;}
.wpwaf-pr-stat-num{font-size:22px;font-weight:700;line-height:1.2;}
.wpwaf-pr-stat-label{font-size:11px;color:var(--pr-muted);margin-top:2px;}

/* Zone cards */
.wpwaf-pr-zones{display:flex;flex-direction:column;gap:14px;}
.wpwaf-pr-zone{background:#fff;border:1px solid var(--pr-border);border-radius:10px;overflow:hidden;}
.wpwaf-pr-zone-head{display:flex;align-items:center;gap:10px;flex-wrap:wrap;padding:12px 16px;border-bottom:1px solid var(--pr-border);background:var(--pr-bg);}
.wpwaf-pr-zone-name{font-size:14px;font-weight:700;overflow-wrap:anywhere;min-width:0;}
.wpwaf-pr-zone-meta{font-size:11px;color:var(--pr-muted);}
.wpwaf-pr-zone-actions{margin-left:auto;display:flex;gap:8px;flex-wrap:wrap;}
.wpwaf-pr-plan{display:inline-block;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:600;background:#f3f4f6;color:#6b7280;white-space:nowrap;}
.wpwaf-pr-plan--pro{background:#fef3c7;color:#92400e;}
.wpwaf-pr-plan--business{background:#dbeafe;color:#1e40af;}
.wpwaf-pr-plan--enterprise{background:#f3e8ff;color:#6b21a8;}
.wpwaf-pr-zone-body{padding:4px 16px;}
.wpwaf-pr-zone-empty{padding:12px 0;font-size:12px;color:var(--pr-muted);}
.wpwaf-pr-zone-warn{margin:10px 0 0;font-size:12px;color:#92400e;background:#fff8f5;border:1px solid #fcd9c0;border-radius:6px;padding:8px 10px;}

/* Rule rows */
.wpwaf-pr-rule{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:12px;padding:14px 0;border-bottom:1px solid #f1f3f5;}
.wpwaf-pr-rule:last-child{border-bottom:none;}
.wpwaf-pr-rule-top{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:6px;}
.wpwaf-pr-pattern{font-family:Menlo,Consolas,monospace;font-size:12px;font-weight:600;color:#111;overflow-wrap:anywhere;min-width:0;}
.wpwaf-pr-badge{display:inline-block;padding:1px 7px;border-radius:20px;font-size:10px;font-weight:700;white-space:nowrap;background:#f3f4f6;color:#6b7280;}
.wpwaf-pr-badge--off{background:#fee2e2;color:#991b1b;}
.wpwaf-pr-badge--on{background:#dcfce7;color:#166534;}
.wpwaf-pr-badge--auto{background:#fff1e6;color:#c2410c;}
.wpwaf-pr-badge--done{background:#dcfce7;color:#166534;}
.wpwaf-pr-actions{display:flex;flex-direction:column;gap:4px;}
.wpwaf-pr-action{font-size:12px;line-height:1.5;color:#374151;overflow-wrap:anywhere;}
.wpwaf-pr-action strong{color:var(--pr-dark);}
.wpwaf-pr-action-val{color:var(--pr-muted);}
.wpwaf-pr-arrow{color:var(--pr-orange);font-weight:700;margin:0 4px;}
.wpwaf-pr-reason{margin-top:6px;font-size:11px;color:var(--pr-muted);line-height:1.5;}
.wpwaf-pr-reason a{color:var(--pr-orange);}
.wpwaf-pr-rule-btns{display:flex;flex-direction:column;align-items:flex-end;gap:6px;}

/* Modal */
.wpwaf-pr-modal-bg{position:fixed;inset:0;background:rgba(17,24,39,.45);z-index:100000;display:flex;align-items:center;justify-content:center;padding:16px;}
.wpwaf-pr-modal{background:#fff;border-radius:12px;box-shadow:0 20px 50px rgba(0,0,0,.25);width:min(640px,100%);max-height:calc(100vh - 32px);overflow:auto;padding:20px 22px;box-sizing:border-box;}
.wpwaf-pr-modal h2{font-size:16px;margin:0 0 4px;}
.wpwaf-pr-modal-sub{font-size:12px;color:var(--pr-muted);margin-bottom:14px;overflow-wrap:anywhere;}
.wpwaf-pr-field{margin-bottom:12px;}
.wpwaf-pr-field-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--pr-muted);margin-bottom:4px;}
.wpwaf-pr-code{display:block;font-family:Menlo,Consolas,monospace;font-size:11px;line-height:1.6;background:#0f172a;color:#e2e8f0;border-radius:6px;padding:10px 12px;white-space:pre-wrap;overflow-wrap:anywhere;}
.wpwaf-pr-modal ul{margin:0;padding-left:18px;font-size:12px;line-height:1.6;}
.wpwaf-pr-modal-notes{font-size:12px;color:#374151;background:var(--pr-bg);border:1px solid var(--pr-border);border-radius:8px;padding:10px 12px;line-height:1.6;}
.wpwaf-pr-modal-notes p{margin:0 0 6px;}
.wpwaf-pr-modal-notes p:last-child{margin:0;}
.wpwaf-pr-modal-foot{display:flex;justify-content:flex-end;gap:8px;margin-top:16px;flex-wrap:wrap;}

/* Toast + spinner */
.wpwaf-pr-toast{position:fixed;bottom:24px;right:24px;max-width:calc(100vw - 48px);padding:10px 16px;border-radius:6px;font-size:13px;font-weight:600;color:#fff;z-index:100001;box-sizing:border-box;}
.wpwaf-pr-toast.ok{background:var(--pr-green);}
.wpwaf-pr-toast.err{background:var(--pr-red);}
.wpwaf-pr-spinner{display:inline-block;width:12px;height:12px;border:2px solid rgba(255,106,0,.3);border-top-color:var(--pr-orange);border-radius:50%;animation:wpwaf-pr-spin .6s linear infinite;vertical-align:middle;}
@keyframes wpwaf-pr-spin{to{transform:rotate(360deg);}}
.wpwaf-pr-empty{text-align:center;padding:40px 16px;color:#9ca3af;font-size:13px;background:#fff;border:1px dashed var(--pr-border);border-radius:10px;}

@media (max-width:1100px){
  .wpwaf-pr-intro{grid-template-columns:minmax(0,1fr);}
}
@media (max-width:782px){
  .wpwaf-pr-summary{grid-template-columns:repeat(2,minmax(0,1fr));}
  .wpwaf-pr-rule{grid-template-columns:minmax(0,1fr);}
  .wpwaf-pr-rule-btns{flex-direction:row;flex-wrap:wrap;align-items:center;}
  .wpwaf-pr-zone-actions{margin-left:0;width:100%;}
  .wpwaf-pr-toolbar .wpwaf-pr-btn--primary{flex:1 1 100%;}
}
@media (max-width:480px){
  .wpwaf-pr-zone-head,.wpwaf-pr-zone-body{padding-left:12px;padding-right:12px;}
  .wpwaf-pr-zone-actions .wpwaf-pr-btn{flex:1 1 100%;}
  .wpwaf-pr-modal{padding:16px;}
  .wpwaf-pr-modal-foot .wpwaf-pr-btn{flex:1 1 100%;}
  .wpwaf-pr-toast{left:12px;right:12px;bottom:12px;max-width:none;}
}
</style>

<div class="wpwaf-pr-wrap">
	<div class="wpwaf-pr-header">
		<div>
			<h1><span class="dashicons dashicons-migrate"></span> Page Rules</h1>
			<div class="wpwaf-pr-header-sub">Find legacy Page Rules across your zones and move them to modern Cloudflare Rules</div>
		</div>
	</div>

<?php if ( ! $has_creds ) : ?>
	<div class="wpwaf-pr-notice">
		<strong>No Cloudflare account connected.</strong><br>
		Add a Cloudflare account on the main <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpwafmanager' ) ); ?>">WAF Rules</a> page first.
	</div>
<?php else : ?>

	<div class="wpwaf-pr-intro">
		<div class="wpwaf-pr-box">
			<h2>Why move off Page Rules?</h2>
			<p>Cloudflare has deprecated Page Rules. Their replacements (Single Redirects, Cache Rules, Configuration Rules and others) have higher limits and more options on every plan. Scan your zones to see which Page Rules you have and where each setting belongs now.</p>
			<p>This plugin migrates <strong>Forwarding URL</strong> rules to Single Redirects, and <strong>Cache Everything</strong> or <strong>Bypass</strong> cache rules to Cache Rules. New rules are added after your existing rules, and nothing is deleted. Once a new rule works, you can turn the old Page Rule off here and turn it back on at any time.</p>
			<p><a href="<?php echo esc_url( $guide_url ); ?>" target="_blank" rel="noopener">Cloudflare's Page Rules migration guide</a></p>
		</div>
		<div class="wpwaf-pr-box">
			<h2>API token permissions</h2>
			<ul>
				<li><code>Zone &gt; Page Rules &gt; Read</code> to scan</li>
				<li><code>Zone &gt; Page Rules &gt; Edit</code> to turn a Page Rule off or on</li>
				<li><code>Zone &gt; Single Redirect &gt; Edit</code> to migrate Forwarding URLs</li>
				<li><code>Zone &gt; Cache Rules &gt; Edit</code> to migrate cache settings</li>
			</ul>
		</div>
	</div>

	<div class="wpwaf-pr-toolbar">
		<button type="button" id="wpwaf-pr-scan" class="wpwaf-pr-btn wpwaf-pr-btn--primary">&#128269; Scan all zones</button>
		<input type="search" id="wpwaf-pr-search" class="wpwaf-pr-search" placeholder="Filter zones&hellip;">
		<label class="wpwaf-pr-check"><input type="checkbox" id="wpwaf-pr-only" checked> Only zones with Page Rules</label>
		<span class="wpwaf-pr-progress" id="wpwaf-pr-progress"></span>
	</div>

	<div class="wpwaf-pr-summary" id="wpwaf-pr-summary" style="display:none;">
		<div class="wpwaf-pr-stat"><div class="wpwaf-pr-stat-num" id="wpwaf-pr-s-zones">0</div><div class="wpwaf-pr-stat-label">Zones with Page Rules</div></div>
		<div class="wpwaf-pr-stat"><div class="wpwaf-pr-stat-num" id="wpwaf-pr-s-rules">0</div><div class="wpwaf-pr-stat-label">Page Rules found</div></div>
		<div class="wpwaf-pr-stat"><div class="wpwaf-pr-stat-num" id="wpwaf-pr-s-auto">0</div><div class="wpwaf-pr-stat-label">Can migrate automatically</div></div>
		<div class="wpwaf-pr-stat"><div class="wpwaf-pr-stat-num" id="wpwaf-pr-s-done">0</div><div class="wpwaf-pr-stat-label">Already migrated</div></div>
	</div>

	<div id="wpwaf-pr-zones" class="wpwaf-pr-zones">
		<div class="wpwaf-pr-empty">Click <strong>Scan all zones</strong> to list the Page Rules in every zone on this account.</div>
	</div>

<?php endif; ?>
</div>

<?php if ( $has_creds ) : ?>
<script>
'use strict';
(function(){
const NONCE     = <?php echo wp_json_encode( $nonce ); ?>;
const AJAX_URL  = <?php echo wp_json_encode( $ajax_url ); ?>;
const GUIDE_URL = <?php echo wp_json_encode( $guide_url ); ?>;
const CONCURRENCY = 3;

const esc = s => String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
const qs  = (s, c) => (c || document).querySelector(s);

let zones   = [];          // from wpwaf_list_zones
const state = {};          // zone id => { status:'pending'|'loading'|'done'|'error', data, error }
let scanning = false;

function toast(msg, ok = true){
  const el = document.createElement('div');
  el.className = 'wpwaf-pr-toast ' + (ok ? 'ok' : 'err');
  el.textContent = msg;
  document.body.appendChild(el);
  setTimeout(() => { el.style.transition = 'opacity .3s'; el.style.opacity = '0'; setTimeout(() => el.remove(), 300); }, ok ? 3000 : 6000);
}

function ajax(action, data){
  const fd = new FormData();
  fd.append('action', action);
  fd.append('nonce', NONCE);
  Object.entries(data || {}).forEach(([k, v]) => fd.append(k, v));
  return fetch(AJAX_URL, { method:'POST', body:fd, credentials:'same-origin' })
    .then(r => r.json())
    .catch(e => ({ success:false, data:{ message:e.message } }));
}

function planKey(name){
  const p = String(name || '').toLowerCase();
  if (p.includes('enterprise')) return 'enterprise';
  if (p.includes('business'))   return 'business';
  if (p.includes('pro'))        return 'pro';
  return 'free';
}

// ── Scan ─────────────────────────────────────────────────────────────────────
qs('#wpwaf-pr-scan').addEventListener('click', scanAll);
qs('#wpwaf-pr-search').addEventListener('input', render);
qs('#wpwaf-pr-only').addEventListener('change', render);

async function scanAll(){
  if (scanning) return;
  scanning = true;
  const btn = qs('#wpwaf-pr-scan');
  btn.disabled = true;
  btn.innerHTML = '<span class="wpwaf-pr-spinner"></span> Scanning&hellip;';
  setProgress('Loading zones&hellip;');

  const zr = await ajax('wpwaf_list_zones');
  if (!zr.success){
    qs('#wpwaf-pr-zones').innerHTML = '<div class="wpwaf-pr-notice wpwaf-pr-notice--error">Could not load zones: ' + esc(zr.data && zr.data.message || 'error') + '</div>';
    finishScan();
    return;
  }
  zones = (zr.data.zones || []).slice().sort((a, b) => a.name.localeCompare(b.name));
  zones.forEach(z => { state[z.id] = { status:'pending' }; });
  render();

  let next = 0, done = 0;
  const worker = async () => {
    while (next < zones.length){
      const z = zones[next++];
      await scanZone(z);
      done++;
      setProgress('Scanned ' + done + ' of ' + zones.length + ' zones');
    }
  };
  await Promise.all(Array.from({ length: Math.min(CONCURRENCY, zones.length) }, worker));
  finishScan();
}

function finishScan(){
  scanning = false;
  const btn = qs('#wpwaf-pr-scan');
  btn.disabled = false;
  btn.innerHTML = '&#8635; Scan again';
}

function setProgress(html){ qs('#wpwaf-pr-progress').innerHTML = html; }

async function scanZone(z){
  state[z.id] = { status:'loading' };
  renderZone(z);
  const res = await ajax('wpwaf_pr_scan', { zone_id:z.id, plan:z.plan || '' });
  if (res.success){
    state[z.id] = { status:'done', data:res.data };
  } else {
    state[z.id] = { status:'error', error:(res.data && res.data.message) || 'Scan failed' };
  }
  render();
}

// ── Render ───────────────────────────────────────────────────────────────────
function visibleZones(){
  const q    = qs('#wpwaf-pr-search').value.trim().toLowerCase();
  const only = qs('#wpwaf-pr-only').checked;
  return zones.filter(z => {
    if (q && !z.name.toLowerCase().includes(q)) return false;
    if (!only) return true;
    const s = state[z.id];
    if (!s || s.status === 'pending' || s.status === 'loading' || s.status === 'error') return true;
    return (s.data.rules || []).length > 0;
  });
}

function render(){
  if (!zones.length) return;
  updateSummary();
  const list = visibleZones();
  const wrap = qs('#wpwaf-pr-zones');
  if (!list.length){
    const allDone = zones.every(z => state[z.id] && state[z.id].status !== 'pending' && state[z.id].status !== 'loading');
    wrap.innerHTML = '<div class="wpwaf-pr-empty">' + (allDone && qs('#wpwaf-pr-only').checked && !qs('#wpwaf-pr-search').value
      ? '&#127881; No Page Rules found in any zone. Nothing to migrate.'
      : 'No zones match your filter.') + '</div>';
    return;
  }
  wrap.innerHTML = list.map(zoneHTML).join('');
}

function renderZone(z){
  const el = qs('#wpwaf-pr-zone-' + z.id);
  if (el) el.outerHTML = zoneHTML(z);
  else render();
}

function updateSummary(){
  let zc = 0, rc = 0, ac = 0, dc = 0, any = false;
  zones.forEach(z => {
    const s = state[z.id];
    if (!s || s.status !== 'done') return;
    any = true;
    const rules = s.data.rules || [];
    const mig = s.data.migrated || {};
    if (rules.length) zc++;
    rc += rules.length;
    rules.forEach(r => {
      if (mig[r.id]) dc++;
      else if (r.auto) ac++;
    });
  });
  qs('#wpwaf-pr-summary').style.display = any ? '' : 'none';
  qs('#wpwaf-pr-s-zones').textContent = zc;
  qs('#wpwaf-pr-s-rules').textContent = rc;
  qs('#wpwaf-pr-s-auto').textContent  = ac;
  qs('#wpwaf-pr-s-done').textContent  = dc;
}

function planPill(plan){
  const k = planKey(plan);
  return '<span class="wpwaf-pr-plan' + (k !== 'free' ? ' wpwaf-pr-plan--' + k : '') + '">' + esc(plan || 'Free') + '</span>';
}

function zoneHTML(z){
  const s = state[z.id] || { status:'pending' };
  let meta = '', body = '', actions = '';

  if (s.status === 'pending'){
    body = '<div class="wpwaf-pr-zone-empty">Waiting&hellip;</div>';
  } else if (s.status === 'loading'){
    body = '<div class="wpwaf-pr-zone-empty"><span class="wpwaf-pr-spinner"></span> Reading Page Rules&hellip;</div>';
  } else if (s.status === 'error'){
    body = '<div class="wpwaf-pr-zone-warn">' + esc(s.error) + '</div><div class="wpwaf-pr-zone-empty"></div>';
    actions = '<button type="button" class="wpwaf-pr-btn wpwaf-pr-btn--sm" data-pr-rescan="' + esc(z.id) + '">&#8635; Retry</button>';
  } else {
    const d = s.data, rules = d.rules || [], mig = d.migrated || {}, lim = d.limits || {};
    const counts = d.counts || {};
    meta = '<span class="wpwaf-pr-zone-meta">' + rules.length + ' of ' + esc(lim.page_rules) + ' Page Rules'
      + (counts.redirect != null ? ' &middot; Single Redirects ' + esc(counts.redirect) + '/' + esc(lim.redirect) : '')
      + (counts.cache != null ? ' &middot; Cache Rules ' + esc(counts.cache) + '/' + esc(lim.cache) : '')
      + '</span>';
    const eligible = rules.filter(r => r.auto && !mig[r.id]);
    if (eligible.length > 1){
      actions = '<button type="button" class="wpwaf-pr-btn wpwaf-pr-btn--sm" data-pr-all="' + esc(z.id) + '">Migrate all eligible (' + eligible.length + ')</button>';
    }
    actions += '<button type="button" class="wpwaf-pr-btn wpwaf-pr-btn--sm" data-pr-rescan="' + esc(z.id) + '">&#8635; Rescan</button>';
    body = (d.warning ? '<div class="wpwaf-pr-zone-warn">' + esc(d.warning) + '</div>' : '')
      + (rules.length ? rules.map(r => ruleHTML(z, r, mig[r.id])).join('') : '<div class="wpwaf-pr-zone-empty">No Page Rules in this zone.</div>');
  }

  return '<div class="wpwaf-pr-zone" id="wpwaf-pr-zone-' + esc(z.id) + '">'
    + '<div class="wpwaf-pr-zone-head"><span class="wpwaf-pr-zone-name">' + esc(z.name) + '</span>' + planPill(z.plan) + meta
    + (actions ? '<div class="wpwaf-pr-zone-actions">' + actions + '</div>' : '') + '</div>'
    + '<div class="wpwaf-pr-zone-body">' + body + '</div></div>';
}

function ruleHTML(z, r, migrated){
  const on = r.status === 'active';
  const kindLabel = r.kind === 'redirect' ? 'Single Redirect' : 'Cache Rule';
  let badges = '<span class="wpwaf-pr-badge">Priority ' + esc(r.priority) + '</span>'
    + '<span class="wpwaf-pr-badge ' + (on ? 'wpwaf-pr-badge--on' : 'wpwaf-pr-badge--off') + '">' + (on ? 'On' : 'Off') + '</span>';
  if (migrated) badges += '<span class="wpwaf-pr-badge wpwaf-pr-badge--done">&#10003; Migrated</span>';
  else if (r.auto) badges += '<span class="wpwaf-pr-badge wpwaf-pr-badge--auto">Auto migrate to ' + kindLabel + '</span>';

  const acts = (r.actions || []).map(a =>
    '<div class="wpwaf-pr-action"><strong>' + esc(a.label) + '</strong>'
    + (a.value ? ' <span class="wpwaf-pr-action-val">(' + esc(a.value) + ')</span>' : '')
    + '<span class="wpwaf-pr-arrow">&rarr;</span>' + esc(a.replacement) + '</div>').join('');

  let reason = '';
  if (migrated){
    reason = '<div class="wpwaf-pr-reason">A matching ' + (migrated.phase === 'http_request_dynamic_redirect' ? 'Single Redirect' : 'Cache Rule')
      + ' created by this plugin is in place. Test the site, then turn this Page Rule off.</div>';
  } else if (!r.auto){
    reason = '<div class="wpwaf-pr-reason"><strong>Migrate manually:</strong> ' + esc(r.reason)
      + ' <a href="' + esc(GUIDE_URL) + '" target="_blank" rel="noopener">How to migrate</a></div>';
  }

  let btns = '';
  if (!migrated && r.auto){
    btns += '<button type="button" class="wpwaf-pr-btn wpwaf-pr-btn--primary wpwaf-pr-btn--sm" data-pr-preview="' + esc(z.id) + '|' + esc(r.id) + '">Preview &amp; migrate</button>';
  }
  if (migrated && on){
    btns += '<button type="button" class="wpwaf-pr-btn wpwaf-pr-btn--sm" data-pr-status="' + esc(z.id) + '|' + esc(r.id) + '|disabled">Turn Page Rule off</button>';
  }
  if (!on && migrated){
    btns += '<button type="button" class="wpwaf-pr-btn wpwaf-pr-btn--sm" data-pr-status="' + esc(z.id) + '|' + esc(r.id) + '|active">Re-enable Page Rule</button>';
  }

  return '<div class="wpwaf-pr-rule">'
    + '<div><div class="wpwaf-pr-rule-top"><span class="wpwaf-pr-pattern">' + esc(r.pattern) + '</span>' + badges + '</div>'
    + '<div class="wpwaf-pr-actions">' + acts + '</div>' + reason + '</div>'
    + '<div class="wpwaf-pr-rule-btns">' + btns + '</div></div>';
}

// ── Actions ──────────────────────────────────────────────────────────────────
qs('#wpwaf-pr-zones').addEventListener('click', e => {
  const t = e.target.closest('button');
  if (!t) return;
  if (t.dataset.prRescan){
    const z = zones.find(x => x.id === t.dataset.prRescan);
    if (z) scanZone(z);
  } else if (t.dataset.prPreview){
    const [zid, rid] = t.dataset.prPreview.split('|');
    openPreview(zid, rid);
  } else if (t.dataset.prStatus){
    const [zid, rid, status] = t.dataset.prStatus.split('|');
    setStatus(zid, rid, status, t);
  } else if (t.dataset.prAll){
    migrateAll(t.dataset.prAll, t);
  }
});

function findRule(zid, rid){
  const s = state[zid];
  return s && s.data ? (s.data.rules || []).find(r => r.id === rid) : null;
}

function openPreview(zid, rid){
  const z = zones.find(x => x.id === zid), r = findRule(zid, rid);
  if (!z || !r || !r.preview) return;
  const p = r.preview, isRedirect = r.kind === 'redirect';
  const lim = state[zid].data.limits || {}, counts = state[zid].data.counts || {};
  const used = isRedirect ? counts.redirect : counts.cache;
  const max  = isRedirect ? lim.redirect : lim.cache;

  let fields = '<div class="wpwaf-pr-field"><div class="wpwaf-pr-field-label">When incoming requests match</div><code class="wpwaf-pr-code">' + esc(p.expression) + '</code></div>';
  if (isRedirect){
    fields += '<div class="wpwaf-pr-field"><div class="wpwaf-pr-field-label">Redirect to (' + esc(p.status_code) + ')</div><code class="wpwaf-pr-code">' + esc(p.target) + '</code></div>'
      + '<div class="wpwaf-pr-field"><label class="wpwaf-pr-check wpwaf-pr-check--wrap"><input type="checkbox" id="wpwaf-pr-keepqs" checked> Preserve query string (Cloudflare recommends this when migrating)</label></div>';
  } else {
    fields += '<div class="wpwaf-pr-field"><div class="wpwaf-pr-field-label">Then</div><ul>' + (p.settings || []).map(x => '<li>' + esc(x) + '</li>').join('') + '</ul></div>';
  }

  const notes = isRedirect
    ? '<p>The new Single Redirect is added after any redirects already in the zone. When two redirects match, Cloudflare uses the first one.</p>'
      + '<p>Single Redirects only work on hostnames that are proxied through Cloudflare (orange cloud).</p>'
    : '<p>Modern Rules take priority over Page Rules, so the new Cache Rule applies right away. Cache Rules that match the same request combine, and later rules win when settings conflict.</p>'
      + (r.preview.settings.join(' ').indexOf('Eligible') !== -1 ? '<p>Eligible for cache caches every response that matches, including HTML. Make sure the matched URLs never show logged-in or personal content.</p>' : '');

  const modal = document.createElement('div');
  modal.className = 'wpwaf-pr-modal-bg';
  modal.innerHTML = '<div class="wpwaf-pr-modal" role="dialog" aria-modal="true" aria-labelledby="wpwaf-pr-modal-title">'
    + '<h2 id="wpwaf-pr-modal-title">Migrate to a ' + (isRedirect ? 'Single Redirect' : 'Cache Rule') + '</h2>'
    + '<div class="wpwaf-pr-modal-sub">' + esc(z.name) + ' &middot; Page Rule <strong>' + esc(r.pattern) + '</strong>'
    + (used != null ? ' &middot; ' + esc(used) + ' of ' + esc(max) + ' ' + (isRedirect ? 'Single Redirects' : 'Cache Rules') + ' used' : '') + '</div>'
    + fields
    + '<div class="wpwaf-pr-modal-notes">' + notes + '<p>The Page Rule stays on until you turn it off. Nothing is deleted.</p></div>'
    + '<div class="wpwaf-pr-modal-foot"><button type="button" class="wpwaf-pr-btn" data-close>Cancel</button>'
    + '<button type="button" class="wpwaf-pr-btn wpwaf-pr-btn--primary" data-go>Create ' + (isRedirect ? 'Single Redirect' : 'Cache Rule') + '</button></div></div>';
  document.body.appendChild(modal);

  const close = () => { modal.remove(); document.removeEventListener('keydown', onKey); };
  const onKey = e => { if (e.key === 'Escape') close(); };
  document.addEventListener('keydown', onKey);
  modal.addEventListener('click', e => { if (e.target === modal || e.target.hasAttribute('data-close')) close(); });
  const go = qs('[data-go]', modal);
  go.focus();
  go.addEventListener('click', async () => {
    go.disabled = true;
    go.innerHTML = '<span class="wpwaf-pr-spinner"></span> Creating&hellip;';
    const keep = qs('#wpwaf-pr-keepqs', modal);
    const ok = await migrate(z, r, keep ? keep.checked : true);
    if (ok) close();
    else { go.disabled = false; go.textContent = 'Try again'; }
  });
}

async function migrate(z, r, keepQs){
  const res = await ajax('wpwaf_pr_migrate', { zone_id:z.id, rule_id:r.id, preserve_query_string:keepQs ? '1' : '0' });
  if (res.success || (res.data && res.data.already)){
    const d = state[z.id].data;
    d.migrated = d.migrated || {};
    d.migrated[r.id] = { phase:(res.data && res.data.phase) || r.phase, rule_id:(res.data && res.data.rule_id) || '' };
    if (res.success){
      if (r.kind === 'redirect' && d.counts && d.counts.redirect != null) d.counts.redirect++;
      if (r.kind === 'cache' && d.counts && d.counts.cache != null) d.counts.cache++;
    }
    render();
    toast(res.success ? '✓ ' + (r.kind === 'redirect' ? 'Single Redirect' : 'Cache Rule') + ' created for ' + z.name : res.data.message);
    return true;
  }
  toast('Migration failed: ' + ((res.data && res.data.message) || 'error'), false);
  return false;
}

async function migrateAll(zid, btn){
  const z = zones.find(x => x.id === zid);
  const d = state[zid] && state[zid].data;
  if (!z || !d) return;
  const mig = d.migrated || {};
  const eligible = (d.rules || []).filter(r => r.auto && !mig[r.id]);
  if (!eligible.length) return;
  if (!confirm('Create ' + eligible.length + ' new rules in ' + z.name + '? Your Page Rules stay on until you turn them off.')) return;

  // Redirects: first match wins, so the highest priority Page Rule goes first.
  // Cache Rules: later rules win, so the highest priority Page Rule goes last.
  const redirects = eligible.filter(r => r.kind === 'redirect').sort((a, b) => b.priority - a.priority);
  const caches    = eligible.filter(r => r.kind === 'cache').sort((a, b) => a.priority - b.priority);
  btn.disabled = true;
  btn.innerHTML = '<span class="wpwaf-pr-spinner"></span> Migrating&hellip;';
  let ok = 0;
  for (const r of redirects.concat(caches)){
    if (await migrate(z, r, true)) ok++;
    else break;
  }
  toast(ok + ' of ' + eligible.length + ' rules migrated in ' + z.name, ok === eligible.length);
  render();
}

async function setStatus(zid, rid, status, btn){
  const z = zones.find(x => x.id === zid), r = findRule(zid, rid);
  if (!z || !r) return;
  if (status === 'disabled' && !confirm('Turn off the Page Rule ' + r.pattern + '? You can turn it back on here at any time.')) return;
  btn.disabled = true;
  const res = await ajax('wpwaf_pr_set_status', { zone_id:zid, rule_id:rid, status });
  if (res.success){
    r.status = status;
    render();
    toast(status === 'disabled' ? '✓ Page Rule turned off' : '✓ Page Rule turned back on');
  } else {
    btn.disabled = false;
    toast('Failed: ' + ((res.data && res.data.message) || 'error'), false);
  }
}
})();
</script>
<?php endif; ?>
