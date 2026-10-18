<?php
defined( 'ABSPATH' ) || exit;
$flares  = WPWAF_Zone_Flares::for_js();
$presets = WPWAF_Zone_Flares::presets();
?>
<style>
:root{--bt-orange:#FF6A00;--bt-border:#e2e6ea;--bt-bg:#f8f9fb;--bt-dark:#1a1a2e;--bt-muted:#6b7280;}
.wpwaf-bt-wrap{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;color:var(--bt-dark);}
.wpwaf-bt-wrap *{box-sizing:border-box;}
.wpwaf-bt-header{display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:20px;}
.wpwaf-bt-header h1{font-size:22px;font-weight:700;margin:0;padding:0;display:flex;align-items:center;gap:8px;color:var(--bt-dark);line-height:1.3;}
.wpwaf-bt-header h1 .dashicons{color:var(--bt-orange);font-size:24px;width:24px;height:24px;}
.wpwaf-bt-header-sub{font-size:12px;color:var(--bt-muted);margin-top:3px;}
.wpwaf-bt-notice{background:#fff8f5;border:1px solid #fcd9c0;border-radius:8px;padding:16px 20px;color:#92400e;font-size:13px;line-height:1.6;}

/* Info boxes */
.wpwaf-bt-info{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(320px,100%),1fr));gap:12px;margin-bottom:20px;}
.wpwaf-bt-box{border-radius:8px;padding:12px 16px;font-size:12px;line-height:1.6;}
.wpwaf-bt-box strong{display:block;font-size:13px;margin-bottom:2px;}
.wpwaf-bt-box code{font-size:11px;background:rgba(255,255,255,.7);padding:1px 5px;border-radius:4px;overflow-wrap:anywhere;}
.wpwaf-bt-box--help{background:#eff6ff;border:1px solid #bfdbfe;color:#1e3a8a;}
.wpwaf-bt-box--warn{background:#fffbeb;border:1px solid #fcd34d;color:#78350f;}

/* Cards */
.wpwaf-bt-card{background:#fff;border:1px solid var(--bt-border);border-radius:10px;margin-bottom:20px;overflow:hidden;}
.wpwaf-bt-card-head{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;padding:14px 18px;border-bottom:1px solid var(--bt-border);}
.wpwaf-bt-card-head h2{margin:0;font-size:15px;font-weight:700;color:var(--bt-dark);}
.wpwaf-bt-card-head p{margin:2px 0 0;font-size:12px;color:var(--bt-muted);}
.wpwaf-bt-card-body{padding:16px 18px;}

/* Policy groups */
.wpwaf-bt-groups{display:grid;grid-template-columns:repeat(auto-fill,minmax(min(300px,100%),1fr));gap:14px;align-items:start;}
.wpwaf-bt-group{border:1px solid var(--bt-border);border-radius:8px;padding:12px 14px;background:var(--bt-bg);min-width:0;}
.wpwaf-bt-group-title{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--bt-muted);margin-bottom:8px;display:flex;align-items:center;gap:6px;flex-wrap:wrap;}
.wpwaf-bt-plan-tag{font-size:10px;font-weight:600;text-transform:none;letter-spacing:0;padding:1px 7px;border-radius:10px;background:#fff;border:1px solid var(--bt-border);color:var(--bt-muted);}
.wpwaf-bt-field{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:7px 0;border-bottom:1px solid #eef0f3;}
.wpwaf-bt-field:last-child{border-bottom:none;}
.wpwaf-bt-field-text{min-width:0;flex:1 1 auto;}
.wpwaf-bt-field-name{font-size:12px;font-weight:600;color:var(--bt-dark);}
.wpwaf-bt-field-hint{font-size:11px;color:var(--bt-muted);margin-top:1px;line-height:1.4;}
.wpwaf-bt-field select{flex:0 0 auto;width:160px;max-width:50%;min-width:0;padding:4px 8px;border:1px solid var(--bt-border);border-radius:5px;font-size:12px;color:var(--bt-dark);background:#fff;}
.wpwaf-bt-field select:focus{outline:none;border-color:var(--bt-orange);}
.wpwaf-bt-field.is-off{opacity:.55;}

/* Switch */
.wpwaf-bt-switch{position:relative;display:inline-block;width:42px;height:24px;flex-shrink:0;}
.wpwaf-bt-switch input{opacity:0;width:0;height:0;}
.wpwaf-bt-slider{position:absolute;cursor:pointer;inset:0;background:#d1d5db;border-radius:24px;transition:.2s;}
.wpwaf-bt-slider:before{position:absolute;content:"";height:18px;width:18px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.2s;box-shadow:0 1px 3px rgba(0,0,0,.2);}
.wpwaf-bt-switch input:checked+.wpwaf-bt-slider{background:var(--bt-orange);}
.wpwaf-bt-switch input:checked+.wpwaf-bt-slider:before{transform:translateX(18px);}
.wpwaf-bt-switch input:disabled+.wpwaf-bt-slider{opacity:.5;cursor:not-allowed;}

/* Zone picker (bulk) */
.wpwaf-bt-pick{margin-top:16px;border:1px solid var(--bt-border);border-radius:8px;}
.wpwaf-bt-pick-bar{display:flex;align-items:center;gap:8px;flex-wrap:wrap;padding:10px 12px;border-bottom:1px solid var(--bt-border);background:var(--bt-bg);}
.wpwaf-bt-pick-bar input[type=search],.wpwaf-bt-pick-bar select{height:30px;padding:0 10px;border:1px solid var(--bt-border);border-radius:6px;font-size:12px;background:#fff;min-width:0;}
.wpwaf-bt-pick-bar input[type=search]{flex:1 1 180px;}
.wpwaf-bt-pick-count{font-size:12px;color:var(--bt-muted);margin-left:auto;white-space:nowrap;}
.wpwaf-bt-pick-list{max-height:260px;overflow-y:auto;padding:6px 12px;display:grid;grid-template-columns:repeat(auto-fill,minmax(min(260px,100%),1fr));gap:2px 16px;}
.wpwaf-bt-pick-item{display:flex;align-items:center;gap:8px;padding:5px 0;font-size:12px;min-width:0;cursor:pointer;}
.wpwaf-bt-pick-item input{margin:0;flex-shrink:0;}
.wpwaf-bt-pick-name{overflow-wrap:anywhere;min-width:0;}

/* Buttons */
.wpwaf-bt-btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;height:32px;padding:0 14px;font-size:12px;font-weight:600;border-radius:6px;border:1px solid #d1d5db;background:#fff;color:#374151;cursor:pointer;white-space:nowrap;transition:background .15s,border-color .15s;}
.wpwaf-bt-btn:hover{background:#f3f4f6;border-color:#9ca3af;}
.wpwaf-bt-btn--primary{background:var(--bt-orange);border-color:var(--bt-orange);color:#fff;}
.wpwaf-bt-btn--primary:hover{background:#d95500;border-color:#d95500;}
.wpwaf-bt-btn--sm{height:26px;padding:0 10px;font-size:11px;}
.wpwaf-bt-btn:disabled{opacity:.5;cursor:not-allowed;}
.wpwaf-bt-actions{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-top:16px;}
.wpwaf-bt-actions-note{font-size:12px;color:var(--bt-muted);}

/* Results */
.wpwaf-bt-results{margin-top:14px;border:1px solid var(--bt-border);border-radius:8px;display:none;max-height:340px;overflow-y:auto;}
.wpwaf-bt-results.show{display:block;}
.wpwaf-bt-result{display:flex;gap:10px;padding:8px 12px;border-bottom:1px solid #f1f3f5;font-size:12px;align-items:flex-start;}
.wpwaf-bt-result:last-child{border-bottom:none;}
.wpwaf-bt-result-icon{flex-shrink:0;width:16px;text-align:center;}
.wpwaf-bt-result-zone{font-weight:600;min-width:0;overflow-wrap:anywhere;}
.wpwaf-bt-result-msg{color:var(--bt-muted);margin-top:1px;overflow-wrap:anywhere;}
.wpwaf-bt-result.ok .wpwaf-bt-result-icon{color:#059669;}
.wpwaf-bt-result.err .wpwaf-bt-result-icon{color:#dc2626;}
.wpwaf-bt-result.err .wpwaf-bt-result-msg{color:#b91c1c;}

/* Zone list */
.wpwaf-bt-list-tools{display:flex;align-items:center;justify-content:flex-end;gap:10px;flex-wrap:wrap;flex:1 1 320px;}
.wpwaf-bt-search{height:32px;min-width:0;flex:0 1 240px;padding:0 12px;border:1px solid var(--bt-border);border-radius:6px;font-size:13px;}
.wpwaf-bt-search:focus{outline:none;border-color:var(--bt-orange);}
.wpwaf-bt-count{font-size:12px;font-weight:600;color:var(--bt-muted);background:#f3f4f6;border-radius:20px;padding:3px 10px;white-space:nowrap;}
.wpwaf-bt-zone{border-bottom:1px solid #f1f3f5;}
.wpwaf-bt-zone:last-child{border-bottom:none;}
.wpwaf-bt-zone-row{display:flex;align-items:center;gap:10px;padding:11px 18px;flex-wrap:wrap;}
.wpwaf-bt-zone-name{font-weight:600;font-size:13px;min-width:0;overflow-wrap:anywhere;flex:1 1 180px;}
.wpwaf-bt-zone-meta{display:flex;align-items:center;gap:6px;flex-wrap:wrap;}
.wpwaf-bt-pill{display:inline-block;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:600;white-space:nowrap;}
.wpwaf-bt-pill--free{background:#f3f4f6;color:#6b7280;}
.wpwaf-bt-pill--pro{background:#fef3c7;color:#92400e;}
.wpwaf-bt-pill--biz{background:#dbeafe;color:#1e40af;}
.wpwaf-bt-pill--ent{background:#f3e8ff;color:#6b21a8;}
.wpwaf-bt-flare{display:inline-block;padding:2px 8px;border-radius:20px;border:1px solid;font-size:11px;font-weight:700;white-space:nowrap;}
.wpwaf-bt-summary{display:flex;gap:6px;flex-wrap:wrap;flex:1 1 100%;}
.wpwaf-bt-summary:empty{display:none;}
.wpwaf-bt-chip{font-size:11px;padding:2px 8px;border-radius:12px;background:#f3f4f6;color:#374151;white-space:nowrap;}
.wpwaf-bt-chip--on{background:#ecfdf5;color:#065f46;}
.wpwaf-bt-chip--block{background:#fef2f2;color:#991b1b;}
.wpwaf-bt-zone-edit{display:none;padding:4px 18px 16px;}
.wpwaf-bt-zone.open .wpwaf-bt-zone-edit{display:block;}
.wpwaf-bt-empty{text-align:center;padding:32px;color:#9ca3af;font-size:13px;}
.wpwaf-bt-error{font-size:12px;color:#b91c1c;background:#fef2f2;border:1px solid #fecaca;border-radius:6px;padding:8px 12px;overflow-wrap:anywhere;}

/* Spinner and toast */
.wpwaf-bt-spinner{display:inline-block;width:12px;height:12px;border:2px solid rgba(255,106,0,.3);border-top-color:var(--bt-orange);border-radius:50%;animation:wpwaf-bt-spin .6s linear infinite;vertical-align:middle;}
@keyframes wpwaf-bt-spin{to{transform:rotate(360deg);}}
.wpwaf-bt-loading{font-size:12px;color:var(--bt-muted);padding:10px 0;display:flex;align-items:center;gap:8px;}
.wpwaf-bt-toast{position:fixed;bottom:24px;right:24px;max-width:calc(100vw - 48px);padding:10px 16px;border-radius:6px;font-size:13px;font-weight:600;color:#fff;z-index:999999;}
.wpwaf-bt-toast.ok{background:#059669;}.wpwaf-bt-toast.err{background:#dc2626;}

@media (max-width:782px){
  .wpwaf-bt-card-head,.wpwaf-bt-card-body{padding-left:14px;padding-right:14px;}
  .wpwaf-bt-zone-row{padding:10px 14px;}
  .wpwaf-bt-zone-edit{padding:4px 14px 14px;}
  .wpwaf-bt-pick-count{margin-left:0;flex:1 1 100%;}
  .wpwaf-bt-list-tools{justify-content:flex-start;}
  .wpwaf-bt-search{flex:1 1 200px;}
  .wpwaf-bt-toast{left:12px;right:12px;bottom:12px;max-width:none;}
}
@media (max-width:480px){
  .wpwaf-bt-field{flex-wrap:wrap;}
  .wpwaf-bt-field select{width:100%;max-width:none;flex:1 1 100%;}
  .wpwaf-bt-actions .wpwaf-bt-btn{flex:1 1 100%;}
  .wpwaf-bt-list-tools .wpwaf-bt-btn{flex:1 1 100%;}
}
</style>

<div class="wpwaf-bt-wrap">
	<div class="wpwaf-bt-header">
		<div>
			<h1><span class="dashicons dashicons-shield-alt"></span> Bot Control</h1>
			<div class="wpwaf-bt-header-sub">AI crawler policy, managed robots.txt and Bot Fight Mode for every zone</div>
		</div>
	</div>

<?php if ( ! $has_creds ) : ?>
	<div class="wpwaf-bt-notice">
		<strong>No Cloudflare account connected.</strong><br>
		Add a Cloudflare account on the main <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpwafmanager' ) ); ?>">WAF Rules</a> page first.
	</div>
<?php else : ?>

	<div class="wpwaf-bt-info">
		<div class="wpwaf-bt-box wpwaf-bt-box--help">
			<strong>API token permission</strong>
			This screen needs <code>Zone &rarr; Bot Management &rarr; Edit</code> on your Cloudflare API token. AI crawler settings work on every plan, including Free.
		</div>
		<div class="wpwaf-bt-box wpwaf-bt-box--warn">
			<strong>Bot Fight Mode cannot be skipped</strong>
			Custom rules, including Rule 1 (Allow Good Bots), cannot let traffic past Bot Fight Mode. It can challenge uptime monitors, payment webhooks, XML-RPC, mobile apps and server-to-server REST calls. Test a site before turning it on everywhere. Super Bot Fight Mode on Pro and above does respect skip rules.
		</div>
	</div>

	<!-- Bulk apply -->
	<div class="wpwaf-bt-card">
		<div class="wpwaf-bt-card-head">
			<div>
				<h2>Apply a policy to many zones</h2>
				<p>Leave a setting on "No change" to keep each zone's current value. Settings a zone's plan does not support are skipped.</p>
			</div>
		</div>
		<div class="wpwaf-bt-card-body">
			<div class="wpwaf-bt-groups" id="wpwaf-bt-bulk-groups"></div>

			<div class="wpwaf-bt-pick">
				<div class="wpwaf-bt-pick-bar">
					<input type="search" id="wpwaf-bt-pick-search" placeholder="Filter zones&hellip;" aria-label="Filter zones">
					<select id="wpwaf-bt-pick-flare" aria-label="Filter by flare" style="display:none;"></select>
					<button type="button" class="wpwaf-bt-btn wpwaf-bt-btn--sm" id="wpwaf-bt-pick-all">Select shown</button>
					<button type="button" class="wpwaf-bt-btn wpwaf-bt-btn--sm" id="wpwaf-bt-pick-none">Clear</button>
					<span class="wpwaf-bt-pick-count" id="wpwaf-bt-pick-count">0 selected</span>
				</div>
				<div class="wpwaf-bt-pick-list" id="wpwaf-bt-pick-list">
					<div class="wpwaf-bt-loading"><span class="wpwaf-bt-spinner"></span> Loading zones&hellip;</div>
				</div>
			</div>

			<div class="wpwaf-bt-actions">
				<button type="button" class="wpwaf-bt-btn wpwaf-bt-btn--primary" id="wpwaf-bt-apply">Apply to selected zones</button>
				<span class="wpwaf-bt-actions-note" id="wpwaf-bt-apply-note"></span>
			</div>
			<div class="wpwaf-bt-results" id="wpwaf-bt-results"></div>
		</div>
	</div>

	<!-- Zone list -->
	<div class="wpwaf-bt-card">
		<div class="wpwaf-bt-card-head">
			<div>
				<h2>Zones</h2>
				<p>Open a zone to see and change its current bot settings.</p>
			</div>
			<div class="wpwaf-bt-list-tools">
				<span class="wpwaf-bt-count" id="wpwaf-bt-count">Loading&hellip;</span>
				<input type="search" id="wpwaf-bt-search" class="wpwaf-bt-search" placeholder="Filter zones&hellip;" aria-label="Filter zones" style="display:none;">
				<button type="button" class="wpwaf-bt-btn wpwaf-bt-btn--sm" id="wpwaf-bt-load-all">Check all zones</button>
			</div>
		</div>
		<div id="wpwaf-bt-list">
			<div class="wpwaf-bt-empty"><span class="wpwaf-bt-spinner"></span> Loading zones&hellip;</div>
		</div>
	</div>

<?php endif; ?>
</div>

<?php if ( $has_creds ) : ?>
<script>
'use strict';
(function(){
const NONCE    = <?php echo wp_json_encode( $nonce ); ?>;
const AJAX_URL = <?php echo wp_json_encode( $ajax_url ); ?>;
const FLARES   = <?php echo wp_json_encode( (object) $flares ); ?>;
const PRESETS  = <?php echo wp_json_encode( $presets ); ?>;
const CONCURRENCY = 3;

/* Field definitions. Values and plan scope follow the Cloudflare Bot Management API. */
const AI_OPTS = [['disabled','Allow'],['only_on_ad_pages','Block on pages with ads'],['block','Block everywhere']];
const FIELDS = {
  ai_search:   {group:'ai', label:'AI search crawlers', hint:'Bots that index content to answer questions later', opts:AI_OPTS},
  ai_user:     {group:'ai', label:'AI assistants and agents', hint:'Bots that fetch pages in real time for a person', opts:AI_OPTS},
  ai_training: {group:'ai', label:'AI training crawlers', hint:'Disallow adds a robots.txt rule and blocks training-only crawlers', opts:[['disabled','Allow'],['disallow','Disallow in robots.txt'],['only_on_ad_pages','Block on pages with ads'],['block','Block everywhere']]},
  crawler_protection: {group:'ai', label:'Crawler trap', hint:'Sends AI scrapers into a maze of generated links', opts:[['enabled','On'],['disabled','Off']]},
  is_robots_txt_managed: {group:'robots', label:'Managed robots.txt', hint:'Cloudflare adds AI crawler rules above your own robots.txt', bool:true},
  bot_preference_sync_enabled: {group:'robots', label:'Sync AI choices to robots.txt', hint:'Writes your AI crawler choices into robots.txt', bool:true},
  fight_mode: {group:'bfm', label:'Bot Fight Mode', hint:'Challenges traffic that matches known bot patterns', bool:true},
  sbfm_definitely_automated: {group:'sbfm', label:'Definitely automated', hint:'Traffic that is almost certainly a bot', opts:[['allow','Allow'],['managed_challenge','Managed Challenge'],['block','Block']]},
  sbfm_likely_automated: {group:'sbfm', label:'Likely automated', hint:'Business plan and above', opts:[['allow','Allow'],['managed_challenge','Managed Challenge'],['block','Block']], biz:true},
  sbfm_verified_bots: {group:'sbfm', label:'Verified bots', hint:'Known good bots such as search engines', opts:[['allow','Allow'],['block','Block']]},
  sbfm_static_resource_protection: {group:'sbfm', label:'Static resource protection', hint:'Also checks images, CSS and JS. Can block real visitors', bool:true},
  optimize_wordpress: {group:'sbfm', label:'Optimize for WordPress', hint:'Lets WordPress loopback requests through', bool:true},
};
const GROUPS = [
  {id:'ai',     title:'AI crawlers',              tag:'All plans'},
  {id:'robots', title:'robots.txt',               tag:'All plans'},
  {id:'bfm',    title:'Bot Fight Mode',           tag:'Free plan'},
  {id:'sbfm',   title:'Super Bot Fight Mode',     tag:'Pro plan and above'},
];

let zones = [];
const state = {};          // zone id -> {config, type, allowed}
const selected = new Set();

function qs(s,ctx){ return (ctx||document).querySelector(s); }
function esc(s){ return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }
function toast(msg, ok=true){
  const el = document.createElement('div');
  el.className = 'wpwaf-bt-toast ' + (ok ? 'ok' : 'err');
  el.textContent = msg;
  document.body.appendChild(el);
  setTimeout(() => { el.style.transition = 'opacity .3s'; el.style.opacity = '0'; setTimeout(() => el.remove(), 300); }, 3200);
}
function ajax(action, data){
  const fd = new FormData();
  fd.append('action', action); fd.append('nonce', NONCE);
  Object.entries(data || {}).forEach(([k,v]) => fd.append(k, v));
  return fetch(AJAX_URL, {method:'POST', body:fd, credentials:'same-origin'})
    .then(r => r.json())
    .catch(e => ({success:false, data:{message:e.message}}));
}
function errMsg(res){ return (res && res.data && res.data.message) ? res.data.message : 'Request failed'; }

/* Run tasks with a small concurrency limit to stay inside Cloudflare rate limits. */
async function runPool(items, worker){
  let i = 0;
  const next = async () => { while (i < items.length) { const item = items[i++]; await worker(item); } };
  await Promise.all(Array.from({length: Math.min(CONCURRENCY, items.length)}, next));
}

function planClass(plan){
  const p = String(plan || '').toLowerCase();
  if (p.includes('enterprise')) return 'ent';
  if (p.includes('business')) return 'biz';
  if (p.includes('pro')) return 'pro';
  return 'free';
}
function flareFor(zoneId){
  const f = FLARES[zoneId];
  if (!f || !f.flare_id) return null;
  const p = PRESETS.find(x => x.id === f.flare_id) || {label:'Custom', color:'#9ca3af'};
  return {id:f.flare_id, label: (f.flare_id === 'custom' && f.custom_label) ? f.custom_label : p.label, color:p.color};
}
function flareHTML(zoneId){
  const f = flareFor(zoneId);
  return f ? `<span class="wpwaf-bt-flare" style="color:${esc(f.color)};border-color:${esc(f.color)};background:${esc(f.color)}14;">${esc(f.label)}</span>` : '';
}
function optLabel(field, val){
  const d = FIELDS[field];
  if (!d) return String(val);
  if (d.bool) return val ? 'On' : 'Off';
  const o = (d.opts || []).find(x => x[0] === val);
  return o ? o[1] : String(val);
}

/* ── Bulk policy form ─────────────────────────────────────────────────────── */
function bulkFieldHTML(key){
  const d = FIELDS[key];
  const opts = d.bool ? [['on','On'],['off','Off']] : d.opts;
  return `<div class="wpwaf-bt-field">
    <div class="wpwaf-bt-field-text"><div class="wpwaf-bt-field-name">${esc(d.label)}</div><div class="wpwaf-bt-field-hint">${esc(d.hint)}</div></div>
    <select data-bulk="${key}" aria-label="${esc(d.label)}"><option value="">No change</option>${opts.map(o => `<option value="${o[0]}">${esc(o[1])}</option>`).join('')}</select>
  </div>`;
}
function renderBulk(){
  qs('#wpwaf-bt-bulk-groups').innerHTML = GROUPS.map(g =>
    `<div class="wpwaf-bt-group">
      <div class="wpwaf-bt-group-title">${esc(g.title)} <span class="wpwaf-bt-plan-tag">${esc(g.tag)}</span></div>
      ${Object.keys(FIELDS).filter(k => FIELDS[k].group === g.id).map(bulkFieldHTML).join('')}
    </div>`).join('');
}
function bulkChanges(){
  const out = {};
  document.querySelectorAll('[data-bulk]').forEach(sel => {
    if (!sel.value) return;
    const d = FIELDS[sel.dataset.bulk];
    out[sel.dataset.bulk] = d.bool ? (sel.value === 'on') : sel.value;
  });
  return out;
}

/* ── Bulk zone picker ─────────────────────────────────────────────────────── */
function pickFiltered(){
  const q = (qs('#wpwaf-bt-pick-search').value || '').toLowerCase();
  const fl = qs('#wpwaf-bt-pick-flare').value;
  return zones.filter(z => z.name.toLowerCase().includes(q) && (!fl || (flareFor(z.id) || {}).id === fl || (fl.startsWith('custom:') && (flareFor(z.id) || {}).label === fl.slice(7))));
}
function renderPicker(){
  const list = qs('#wpwaf-bt-pick-list');
  const shown = pickFiltered();
  if (!shown.length){ list.innerHTML = '<div class="wpwaf-bt-empty">No zones match.</div>'; updatePickCount(); return; }
  list.innerHTML = shown.map(z => `<label class="wpwaf-bt-pick-item">
      <input type="checkbox" value="${esc(z.id)}" ${selected.has(z.id) ? 'checked' : ''}>
      <span class="wpwaf-bt-pick-name">${esc(z.name)}</span>
      <span class="wpwaf-bt-pill wpwaf-bt-pill--${planClass(z.plan)}">${esc(String(z.plan || '').replace(/ Website$/,''))}</span>
    </label>`).join('');
  updatePickCount();
}
function updatePickCount(){
  qs('#wpwaf-bt-pick-count').textContent = selected.size + ' of ' + zones.length + ' selected';
}
function renderFlareFilter(){
  const seen = new Map();
  zones.forEach(z => { const f = flareFor(z.id); if (f){ const key = f.id === 'custom' ? 'custom:' + f.label : f.id; seen.set(key, f.label); } });
  const sel = qs('#wpwaf-bt-pick-flare');
  if (!seen.size){ sel.style.display = 'none'; return; }
  sel.innerHTML = '<option value="">All flares</option>' + [...seen].map(([k,l]) => `<option value="${esc(k)}">${esc(l)}</option>`).join('');
  sel.style.display = '';
}

/* ── Zone list ────────────────────────────────────────────────────────────── */
function zoneHTML(z){
  return `<div class="wpwaf-bt-zone" data-zone="${esc(z.id)}">
    <div class="wpwaf-bt-zone-row">
      <div class="wpwaf-bt-zone-name">${esc(z.name)}</div>
      <div class="wpwaf-bt-zone-meta">
        <span class="wpwaf-bt-pill wpwaf-bt-pill--${planClass(z.plan)}">${esc(z.plan)}</span>
        ${flareHTML(z.id)}
        <button type="button" class="wpwaf-bt-btn wpwaf-bt-btn--sm" data-open="${esc(z.id)}">Settings</button>
      </div>
      <div class="wpwaf-bt-summary" data-summary="${esc(z.id)}"></div>
    </div>
    <div class="wpwaf-bt-zone-edit" data-edit="${esc(z.id)}"></div>
  </div>`;
}
function renderList(){
  const q = (qs('#wpwaf-bt-search').value || '').toLowerCase();
  const shown = zones.filter(z => z.name.toLowerCase().includes(q));
  qs('#wpwaf-bt-count').textContent = zones.length + ' zone' + (zones.length === 1 ? '' : 's');
  const list = qs('#wpwaf-bt-list');
  if (!shown.length){ list.innerHTML = '<div class="wpwaf-bt-empty">No zones match your filter.</div>'; return; }
  list.innerHTML = shown.map(zoneHTML).join('');
  shown.forEach(z => { if (state[z.id]) { renderSummary(z.id); renderEditor(z.id); } });
}
function renderSummary(zoneId){
  const box = qs(`[data-summary="${zoneId}"]`);
  const st = state[zoneId];
  if (!box || !st) return;
  const c = st.config, chips = [];
  const chip = (text, kind) => chips.push(`<span class="wpwaf-bt-chip ${kind ? 'wpwaf-bt-chip--' + kind : ''}">${esc(text)}</span>`);
  if ('ai_training' in c) chip('AI training: ' + optLabel('ai_training', c.ai_training), c.ai_training === 'disabled' ? '' : 'block');
  if ('ai_search' in c) chip('AI search: ' + optLabel('ai_search', c.ai_search), c.ai_search === 'disabled' ? '' : 'block');
  if ('ai_user' in c) chip('AI agents: ' + optLabel('ai_user', c.ai_user), c.ai_user === 'disabled' ? '' : 'block');
  if ('is_robots_txt_managed' in c) chip('Managed robots.txt: ' + (c.is_robots_txt_managed ? 'On' : 'Off'), c.is_robots_txt_managed ? 'on' : '');
  if (st.type === 'bfm' && 'fight_mode' in c) chip('Bot Fight Mode: ' + (c.fight_mode ? 'On' : 'Off'), c.fight_mode ? 'on' : '');
  if ((st.type === 'sbfm_d' || st.type === 'sbfm_l') && 'sbfm_definitely_automated' in c) chip('Definitely automated: ' + optLabel('sbfm_definitely_automated', c.sbfm_definitely_automated), c.sbfm_definitely_automated === 'allow' ? '' : 'on');
  if (st.type === 'sub') chip('Enterprise Bot Management: managed in Cloudflare', '');
  box.innerHTML = chips.join('');
}
function editorFieldHTML(zoneId, key, st){
  const d = FIELDS[key];
  const allowed = st.allowed.includes(key);
  const val = st.config[key];
  let ctrl;
  if (!allowed) {
    ctrl = `<span class="wpwaf-bt-field-hint">${d.biz ? 'Business plan and above' : 'Not on this plan'}</span>`;
  } else if (d.bool) {
    ctrl = `<label class="wpwaf-bt-switch"><input type="checkbox" data-zfield="${key}" data-zone="${esc(zoneId)}" ${val ? 'checked' : ''} aria-label="${esc(d.label)}"><span class="wpwaf-bt-slider"></span></label>`;
  } else {
    const opts = d.opts.slice();
    if (val !== undefined && !opts.some(o => o[0] === val)) opts.push([val, String(val)]);
    ctrl = `<select data-zfield="${key}" data-zone="${esc(zoneId)}" aria-label="${esc(d.label)}">${val === undefined ? '<option value="" selected>Not set</option>' : ''}${opts.map(o => `<option value="${esc(o[0])}" ${o[0] === val ? 'selected' : ''}>${esc(o[1])}</option>`).join('')}</select>`;
  }
  return `<div class="wpwaf-bt-field ${allowed ? '' : 'is-off'}">
    <div class="wpwaf-bt-field-text"><div class="wpwaf-bt-field-name">${esc(d.label)}</div><div class="wpwaf-bt-field-hint">${esc(d.hint)}</div></div>
    ${ctrl}
  </div>`;
}
function renderEditor(zoneId){
  const box = qs(`[data-edit="${zoneId}"]`);
  const st = state[zoneId];
  if (!box || !st) return;
  const groups = GROUPS.filter(g => {
    if (g.id === 'bfm') return st.type === 'bfm';
    if (g.id === 'sbfm') return st.type === 'sbfm_d' || st.type === 'sbfm_l';
    return true;
  });
  let html = `<div class="wpwaf-bt-groups">` + groups.map(g =>
    `<div class="wpwaf-bt-group">
      <div class="wpwaf-bt-group-title">${esc(g.title)}</div>
      ${Object.keys(FIELDS).filter(k => FIELDS[k].group === g.id).map(k => editorFieldHTML(zoneId, k, st)).join('')}
    </div>`).join('') + `</div>`;
  if (st.type === 'sub') html += `<p class="wpwaf-bt-field-hint" style="margin:10px 0 0;">This zone uses Enterprise Bot Management. Its bot score rules are managed in the Cloudflare dashboard.</p>`;
  box.innerHTML = html;
}

function loadZone(zone){
  return ajax('wpwaf_bt_get', {zone_id: zone.id, plan: zone.plan || ''}).then(res => {
    if (res.success){
      state[zone.id] = {config: res.data.config || {}, type: res.data.type, allowed: res.data.allowed || []};
      renderSummary(zone.id); renderEditor(zone.id);
    } else {
      const box = qs(`[data-edit="${zone.id}"]`);
      if (box) box.innerHTML = `<div class="wpwaf-bt-error">${esc(errMsg(res))}</div>`;
      const sum = qs(`[data-summary="${zone.id}"]`);
      if (sum) sum.innerHTML = `<span class="wpwaf-bt-chip wpwaf-bt-chip--block">Could not load settings</span>`;
    }
    return res;
  });
}

/* Single setting change from a zone editor. */
function saveZoneField(el){
  const zoneId = el.dataset.zone, key = el.dataset.zfield;
  const zone = zones.find(z => z.id === zoneId);
  const st = state[zoneId];
  if (!zone || !st) return;
  const d = FIELDS[key];
  const value = d.bool ? el.checked : el.value;
  if (!d.bool && value === '') return;
  if (key === 'fight_mode' && value === true && !confirm('Turn on Bot Fight Mode for ' + zone.name + '?\n\nIt cannot be skipped by custom rules and can block uptime monitors, payment webhooks, XML-RPC and server-to-server REST calls.')) { el.checked = false; return; }
  const prev = st.config[key];
  el.disabled = true;
  ajax('wpwaf_bt_apply', {zone_id: zoneId, plan: zone.plan || '', changes: JSON.stringify({[key]: value})}).then(res => {
    el.disabled = false;
    if (res.success && (res.data.applied || []).includes(key)){
      st.config = res.data.config || st.config;
      renderSummary(zoneId);
      toast('Saved: ' + d.label + ' on ' + zone.name);
    } else {
      if (d.bool) el.checked = !!prev; else el.value = prev === undefined ? '' : prev;
      toast(res.success ? (d.label + ' is not available on this plan') : ('Failed: ' + errMsg(res)), false);
    }
  });
}

/* ── Bulk apply ───────────────────────────────────────────────────────────── */
function resultRow(name, ok, msg){
  return `<div class="wpwaf-bt-result ${ok ? 'ok' : 'err'}"><span class="wpwaf-bt-result-icon">${ok ? '&#10003;' : '&#10005;'}</span><div style="min-width:0;"><div class="wpwaf-bt-result-zone">${esc(name)}</div><div class="wpwaf-bt-result-msg">${esc(msg)}</div></div></div>`;
}
async function applyBulk(){
  const changes = bulkChanges();
  const keys = Object.keys(changes);
  if (!keys.length){ toast('Pick at least one setting to change', false); return; }
  const targets = zones.filter(z => selected.has(z.id));
  if (!targets.length){ toast('Select at least one zone', false); return; }
  let msg = 'Apply ' + keys.length + ' setting' + (keys.length === 1 ? '' : 's') + ' to ' + targets.length + ' zone' + (targets.length === 1 ? '' : 's') + '?';
  if (changes.fight_mode === true) msg += '\n\nBot Fight Mode cannot be skipped by custom rules and can block uptime monitors, payment webhooks, XML-RPC and server-to-server REST calls.';
  if (!confirm(msg)) return;

  const btn = qs('#wpwaf-bt-apply'), note = qs('#wpwaf-bt-apply-note'), box = qs('#wpwaf-bt-results');
  btn.disabled = true;
  box.innerHTML = ''; box.classList.add('show');
  let done = 0, ok = 0;
  note.textContent = '0 of ' + targets.length + ' done';
  await runPool(targets, async z => {
    const res = await ajax('wpwaf_bt_apply', {zone_id: z.id, plan: z.plan || '', changes: JSON.stringify(changes)});
    done++;
    if (res.success){
      const applied = res.data.applied || [], skipped = res.data.skipped || [];
      state[z.id] = {config: res.data.config || {}, type: res.data.type, allowed: res.data.allowed || []};
      renderSummary(z.id); renderEditor(z.id);
      let m = applied.length ? 'Updated: ' + applied.map(k => FIELDS[k].label).join(', ') : 'Nothing to update on this plan';
      if (skipped.length) m += '. Not on this plan, skipped: ' + skipped.map(k => FIELDS[k].label).join(', ');
      if (applied.length) ok++;
      box.insertAdjacentHTML('beforeend', resultRow(z.name, applied.length > 0, m));
    } else {
      box.insertAdjacentHTML('beforeend', resultRow(z.name, false, errMsg(res)));
    }
    note.textContent = done + ' of ' + targets.length + ' done';
  });
  btn.disabled = false;
  note.textContent = 'Updated ' + ok + ' of ' + targets.length + ' zone' + (targets.length === 1 ? '' : 's');
  toast('Bot settings updated on ' + ok + ' zone' + (ok === 1 ? '' : 's'), ok === targets.length);
}

/* ── Events ───────────────────────────────────────────────────────────────── */
document.addEventListener('change', e => {
  const t = e.target;
  if (t.matches && t.matches('#wpwaf-bt-pick-list input[type=checkbox]')){
    t.checked ? selected.add(t.value) : selected.delete(t.value);
    updatePickCount();
  } else if (t.matches && t.matches('[data-zfield]')){
    saveZoneField(t);
  } else if (t.id === 'wpwaf-bt-pick-flare'){
    renderPicker();
  }
});
document.addEventListener('click', e => {
  const open = e.target.closest && e.target.closest('[data-open]');
  if (open){
    const id = open.dataset.open;
    const row = qs(`.wpwaf-bt-zone[data-zone="${id}"]`);
    const isOpen = row.classList.toggle('open');
    open.textContent = isOpen ? 'Close' : 'Settings';
    if (isOpen && !state[id]){
      qs(`[data-edit="${id}"]`).innerHTML = '<div class="wpwaf-bt-loading"><span class="wpwaf-bt-spinner"></span> Loading settings&hellip;</div>';
      loadZone(zones.find(z => z.id === id));
    }
  }
});
qs('#wpwaf-bt-pick-search').addEventListener('input', renderPicker);
qs('#wpwaf-bt-search').addEventListener('input', renderList);
qs('#wpwaf-bt-pick-all').addEventListener('click', () => { pickFiltered().forEach(z => selected.add(z.id)); renderPicker(); });
qs('#wpwaf-bt-pick-none').addEventListener('click', () => { selected.clear(); renderPicker(); });
qs('#wpwaf-bt-apply').addEventListener('click', applyBulk);
qs('#wpwaf-bt-load-all').addEventListener('click', async function(){
  if (!zones.length) return;
  this.disabled = true;
  const label = this.textContent;
  let n = 0;
  await runPool(zones, async z => { await loadZone(z); n++; this.textContent = 'Checking ' + n + ' of ' + zones.length; });
  this.disabled = false; this.textContent = label;
});

/* ── Init ─────────────────────────────────────────────────────────────────── */
renderBulk();
ajax('wpwaf_list_zones', {}).then(res => {
  if (!res.success){
    const m = `<div class="wpwaf-bt-empty">Failed to load zones: ${esc(errMsg(res))}</div>`;
    qs('#wpwaf-bt-list').innerHTML = m; qs('#wpwaf-bt-pick-list').innerHTML = m;
    qs('#wpwaf-bt-count').textContent = '0 zones';
    return;
  }
  zones = (res.data.zones || []).slice().sort((a,b) => a.name.localeCompare(b.name));
  if (zones.length > 8) qs('#wpwaf-bt-search').style.display = '';
  renderFlareFilter();
  renderPicker();
  renderList();
});
})();
</script>
<?php endif; ?>
