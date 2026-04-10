<?php
defined( 'ABSPATH' ) || exit;
$nonce    = wp_create_nonce( 'wpwaf_nonce' );
$ajax_url = admin_url( 'admin-ajax.php' );
?>
<style>
:root{--zc-orange:#FF6A00;--zc-border:#e2e6ea;--zc-bg:#f8f9fb;--zc-dark:#1a1a2e;--zc-muted:#6b7280;}
.cfwaf-zc-wrap{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;max-width:1300px;padding:24px 20px;color:var(--zc-dark);}
.cfwaf-zc-header{display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:20px;}
.cfwaf-zc-header h1{font-size:22px;font-weight:700;margin:0;display:flex;align-items:center;gap:8px;}
.cfwaf-zc-header h1 .dashicons{color:var(--zc-orange);font-size:24px;width:24px;height:24px;}
.cfwaf-zc-header-sub{font-size:12px;color:var(--zc-muted);margin-top:3px;}
.cfwaf-zc-no-creds{background:#fff8f5;border:1px solid #fcd9c0;border-radius:8px;padding:20px 24px;color:#92400e;}

/* Attack banner */
.cfwaf-zc-attack-bar{background:#1a1a2e;border-radius:8px;padding:14px 18px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;}
.cfwaf-zc-attack-bar-text{color:#fff;}
.cfwaf-zc-attack-bar-text strong{display:flex;align-items:center;gap:6px;font-size:14px;margin-bottom:2px;}
.cfwaf-zc-attack-bar-text span{font-size:12px;color:#9ca3af;}
.cfwaf-zc-attack-all-btn{background:#dc2626;color:#fff;border:none;padding:10px 20px;border-radius:6px;font-size:13px;font-weight:700;cursor:pointer;white-space:nowrap;transition:background .15s;}
.cfwaf-zc-attack-all-btn:hover{background:#b91c1c;}
.cfwaf-zc-attack-all-btn.active{background:#059669;}
.cfwaf-zc-attack-all-btn.active:hover{background:#047857;}

/* Zone grid */
.cfwaf-zc-controls-bar{display:flex;align-items:center;gap:10px;margin-bottom:16px;flex-wrap:wrap;}
.cfwaf-zc-search{padding:7px 12px;border:1px solid var(--zc-border);border-radius:6px;font-size:13px;flex:1;min-width:200px;}
.cfwaf-zc-search:focus{outline:none;border-color:var(--zc-orange);}
.cfwaf-zc-count{font-size:12px;color:var(--zc-muted);white-space:nowrap;}
.cfwaf-zc-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:16px;}

/* Zone card */
.cfwaf-zc-card{background:#fff;border:1px solid var(--zc-border);border-radius:10px;overflow:hidden;transition:box-shadow .15s;}
.cfwaf-zc-card:hover{box-shadow:0 4px 16px rgba(0,0,0,.07);}
.cfwaf-zc-card-header{display:flex;align-items:center;gap:8px;padding:12px 14px;border-bottom:1px solid var(--zc-border);}
.cfwaf-zc-card-header h3{margin:0;font-size:13px;font-weight:700;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.cfwaf-zc-plan{font-size:10px;font-weight:600;padding:2px 7px;border-radius:10px;background:#f1f5f9;color:#64748b;border:1px solid #e2e8f0;flex-shrink:0;}

/* Under Attack row */
.cfwaf-zc-attack-row{display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:#fef2f2;border-bottom:1px solid #fecaca;}
.cfwaf-zc-attack-row.safe{background:var(--zc-bg);border-bottom-color:var(--zc-border);}
.cfwaf-zc-attack-label{display:flex;align-items:center;gap:7px;font-size:13px;font-weight:700;color:#b91c1c;}
.cfwaf-zc-attack-row.safe .cfwaf-zc-attack-label{color:var(--zc-muted);font-weight:600;}
.cfwaf-zc-attack-sub{font-size:11px;color:#9ca3af;font-weight:400;margin-left:4px;}

/* Toggle switch */
.cfwaf-zc-switch{position:relative;display:inline-block;width:42px;height:24px;flex-shrink:0;}
.cfwaf-zc-switch input{opacity:0;width:0;height:0;}
.cfwaf-zc-switch-slider{position:absolute;cursor:pointer;inset:0;background:#d1d5db;border-radius:24px;transition:.2s;}
.cfwaf-zc-switch-slider:before{position:absolute;content:"";height:18px;width:18px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.2s;box-shadow:0 1px 3px rgba(0,0,0,.2);}
.cfwaf-zc-switch input:checked+.cfwaf-zc-switch-slider{background:#dc2626;}
.cfwaf-zc-switch input:checked+.cfwaf-zc-switch-slider:before{transform:translateX(18px);}
.cfwaf-zc-switch.green input:checked+.cfwaf-zc-switch-slider{background:var(--zc-orange);}
.cfwaf-zc-switch input:disabled+.cfwaf-zc-switch-slider{opacity:.5;cursor:not-allowed;}

/* Card body */
.cfwaf-zc-card-body{padding:12px 14px;display:flex;flex-direction:column;gap:10px;}

/* Cache purge */
.cfwaf-zc-section-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--zc-muted);margin-bottom:4px;}
.cfwaf-zc-purge-row{display:flex;gap:8px;}
.cfwaf-zc-btn{display:inline-flex;align-items:center;gap:5px;padding:7px 12px;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;border:none;transition:all .15s;white-space:nowrap;}
.cfwaf-zc-btn-purge{background:#fff;color:var(--zc-dark);border:1px solid var(--zc-border);}
.cfwaf-zc-btn-purge:hover{background:#fee2e2;border-color:#fca5a5;color:#b91c1c;}
.cfwaf-zc-btn-orange{background:var(--zc-orange);color:#fff;}
.cfwaf-zc-btn-orange:hover{background:#d95500;}
.cfwaf-zc-btn-ghost{background:none;color:var(--zc-muted);border:1px solid var(--zc-border);font-size:11px;padding:4px 10px;}
.cfwaf-zc-btn-ghost:hover{background:var(--zc-bg);}
.cfwaf-zc-btn:disabled{opacity:.5;cursor:not-allowed;}
.cfwaf-zc-url-row{display:flex;gap:6px;margin-top:6px;}
.cfwaf-zc-url-input{flex:1;padding:6px 10px;border:1px solid var(--zc-border);border-radius:6px;font-size:12px;font-family:monospace;}
.cfwaf-zc-url-input:focus{outline:none;border-color:var(--zc-orange);}

/* Settings section */
.cfwaf-zc-settings-toggle{display:flex;align-items:center;justify-content:space-between;cursor:pointer;padding:0;background:none;border:none;width:100%;font-size:12px;font-weight:600;color:var(--zc-muted);text-transform:uppercase;letter-spacing:.5px;}
.cfwaf-zc-settings-toggle:hover{color:var(--zc-dark);}
.cfwaf-zc-settings-body{display:none;flex-direction:column;gap:8px;margin-top:8px;}
.cfwaf-zc-settings-body.open{display:flex;}
.cfwaf-zc-setting-row{display:flex;align-items:center;justify-content:space-between;padding:7px 0;border-bottom:1px solid #f1f3f5;}
.cfwaf-zc-setting-row:last-child{border-bottom:none;}
.cfwaf-zc-setting-name{font-size:12px;font-weight:600;color:var(--zc-dark);}
.cfwaf-zc-setting-hint{font-size:10px;color:var(--zc-muted);margin-top:1px;}
.cfwaf-zc-setting-control select{padding:4px 8px;border:1px solid var(--zc-border);border-radius:5px;font-size:12px;color:var(--zc-dark);background:#fff;cursor:pointer;}
.cfwaf-zc-setting-control select:focus{outline:none;border-color:var(--zc-orange);}
.cfwaf-zc-loading-settings{font-size:12px;color:var(--zc-muted);padding:8px 0;display:flex;align-items:center;gap:6px;}

/* Dev mode row */
.cfwaf-zc-devmode-row{display:flex;align-items:center;justify-content:space-between;padding:8px 14px;background:var(--zc-bg);border-bottom:1px solid var(--zc-border);}
.cfwaf-zc-devmode-label{font-size:12px;font-weight:600;color:var(--zc-dark);display:flex;align-items:center;gap:6px;}

/* Spinner */
.cfwaf-zc-spinner{display:inline-block;width:12px;height:12px;border:2px solid rgba(255,106,0,.3);border-top-color:var(--zc-orange);border-radius:50%;animation:zc-spin .6s linear infinite;}
@keyframes zc-spin{to{transform:rotate(360deg);}}

/* Toast */
.cfwaf-zc-toast{position:fixed;bottom:24px;right:24px;padding:10px 16px;border-radius:6px;font-size:13px;font-weight:600;color:#fff;z-index:999999;}
.cfwaf-zc-toast.ok{background:#059669;}.cfwaf-zc-toast.err{background:#dc2626;}

/* Loading / empty */
.cfwaf-zc-loading{text-align:center;padding:40px;color:var(--zc-muted);}
.cfwaf-zc-no-creds{background:#fff8f5;border:1px solid #fcd9c0;border-radius:8px;padding:20px 24px;color:#92400e;}

@media(max-width:660px){
  .cfwaf-zc-grid{grid-template-columns:1fr;}
  .cfwaf-zc-purge-row{flex-direction:column;}
  .cfwaf-zc-btn{justify-content:center;}
  .cfwaf-zc-attack-bar{flex-direction:column;}
  .cfwaf-zc-attack-all-btn{width:100%;justify-content:center;}
}
</style>

<div class="cfwaf-zc-wrap">
  <div class="cfwaf-zc-header">
    <div>
      <h1><span class="dashicons dashicons-controls-play"></span> Zone Controls</h1>
      <div class="cfwaf-zc-header-sub">Cache purge, Under Attack mode, and zone settings across all domains</div>
    </div>
  </div>

  <?php if ( ! $has_creds ) : ?>
  <div class="cfwaf-zc-no-creds">
    <strong>No Cloudflare account connected.</strong> Please <a href="<?php echo esc_url( admin_url('admin.php?page=wpwafmanager') ); ?>">add your credentials</a> first.
  </div>
  <?php else : ?>

  <!-- Emergency Under Attack banner -->
  <div class="cfwaf-zc-attack-bar">
    <div class="cfwaf-zc-attack-bar-text">
      <strong>⚡ Under Attack Mode</strong>
      <span>Enable JS challenge for ALL zones instantly — use during active DDoS attacks</span>
    </div>
    <button class="cfwaf-zc-attack-all-btn" id="cfwaf-zc-attack-all-on">⚡ Enable on All Zones</button>
  </div>

  <!-- Controls bar -->
  <div class="cfwaf-zc-controls-bar">
    <input type="search" id="cfwaf-zc-search" class="cfwaf-zc-search" placeholder="Search zones…">
    <span class="cfwaf-zc-count" id="cfwaf-zc-count"></span>
  </div>

  <div id="cfwaf-zc-grid" class="cfwaf-zc-grid">
    <div class="cfwaf-zc-loading"><span class="cfwaf-zc-spinner"></span> Loading zones…</div>
  </div>

  <?php endif; ?>
</div>

<script>
'use strict';
(function(){
const NONCE    = <?php echo wp_json_encode( $nonce ); ?>;
const AJAX_URL = <?php echo wp_json_encode( $ajax_url ); ?>;

let allZones = [];

function qs(s,ctx){ return (ctx||document).querySelector(s); }
function toast(msg, ok=true){
  const el = document.createElement('div');
  el.className = 'cfwaf-zc-toast ' + (ok?'ok':'err');
  el.textContent = msg;
  document.body.appendChild(el);
  setTimeout(()=>{ el.style.opacity='0'; el.style.transition='opacity .3s'; setTimeout(()=>el.remove(),300); }, 3000);
}
function ajax(action, data, cb){
  const fd = new FormData();
  fd.append('action',action); fd.append('nonce',NONCE);
  Object.entries(data).forEach(([k,v]) => fd.append(k,v));
  fetch(AJAX_URL,{method:'POST',body:fd}).then(r=>r.json()).then(cb)
    .catch(e=>cb({success:false,data:{message:e.message}}));
}

// ── Load zones ────────────────────────────────────────────────────────────────
ajax('wpwaf_list_zones', {}, (res) => {
  const grid = qs('#cfwaf-zc-grid');
  if (!res.success){ grid.innerHTML = '<div class="cfwaf-zc-loading">Failed to load zones: ' + (res.data?.message||'error') + '</div>'; return; }
  allZones = res.data.zones || [];
  renderGrid(allZones);
});

qs('#cfwaf-zc-search')?.addEventListener('input', function(){
  const q = this.value.toLowerCase();
  const filtered = allZones.filter(z => z.name.toLowerCase().includes(q));
  renderGrid(filtered);
});

// ── Render zone cards ─────────────────────────────────────────────────────────
function renderGrid(zones){
  const grid = qs('#cfwaf-zc-grid');
  qs('#cfwaf-zc-count').textContent = zones.length + ' zone' + (zones.length !== 1 ? 's' : '');
  if (!zones.length){ grid.innerHTML = '<div class="cfwaf-zc-loading">No zones match your search.</div>'; return; }
  grid.innerHTML = zones.map(z => cardHTML(z)).join('');
  // Wire up all cards
  zones.forEach(z => initCard(z.id, z.name));
}

function cardHTML(z){
  return `<div class="cfwaf-zc-card" data-zone="${z.id}">
    <div class="cfwaf-zc-card-header">
      <h3 title="${z.name}">${z.name}</h3>
      <span class="cfwaf-zc-plan">${z.plan}</span>
    </div>
    <!-- Under Attack -->
    <div class="cfwaf-zc-attack-row safe" id="zc-attack-row-${z.id}">
      <div>
        <div class="cfwaf-zc-attack-label" id="zc-attack-label-${z.id}">⚡ Under Attack Mode <span class="cfwaf-zc-attack-sub">Off</span></div>
      </div>
      <label class="cfwaf-zc-switch" title="Toggle Under Attack Mode">
        <input type="checkbox" class="cfwaf-zc-attack-toggle" data-zone="${z.id}">
        <span class="cfwaf-zc-switch-slider"></span>
      </label>
    </div>
    <!-- Dev Mode -->
    <div class="cfwaf-zc-devmode-row">
      <div class="cfwaf-zc-devmode-label">🔧 Development Mode</div>
      <label class="cfwaf-zc-switch green" title="Toggle Development Mode">
        <input type="checkbox" class="cfwaf-zc-devmode-toggle" data-zone="${z.id}">
        <span class="cfwaf-zc-switch-slider"></span>
      </label>
    </div>
    <!-- Cache Purge -->
    <div class="cfwaf-zc-card-body">
      <div>
        <div class="cfwaf-zc-section-label">Cache Purge</div>
        <div class="cfwaf-zc-purge-row">
          <button class="cfwaf-zc-btn cfwaf-zc-btn-purge cfwaf-zc-purge-all" data-zone="${z.id}">🗑 Purge Everything</button>
          <button class="cfwaf-zc-btn cfwaf-zc-btn-ghost cfwaf-zc-purge-urls-toggle" data-zone="${z.id}">By URL</button>
        </div>
        <div class="cfwaf-zc-url-row" id="zc-url-row-${z.id}" style="display:none;">
          <textarea id="zc-url-input-${z.id}" class="cfwaf-zc-url-input" placeholder="One URL per line&#10;https://example.com/page" rows="3" style="width:100%;font-size:11px;padding:6px;border:1px solid #e2e6ea;border-radius:6px;font-family:monospace;resize:vertical;box-sizing:border-box;"></textarea>
          <button class="cfwaf-zc-btn cfwaf-zc-btn-orange cfwaf-zc-purge-url-btn" data-zone="${z.id}" style="align-self:flex-end;flex-shrink:0;">Purge</button>
        </div>
      </div>
      <!-- Zone Settings -->
      <div>
        <button class="cfwaf-zc-settings-toggle" data-zone="${z.id}">
          <span>⚙ Zone Settings</span>
          <span class="cfwaf-zc-chevron">▼</span>
        </button>
        <div class="cfwaf-zc-settings-body" id="zc-settings-${z.id}"></div>
      </div>
    </div>
  </div>`;
}

// ── Wire up a card ────────────────────────────────────────────────────────────
function initCard(zoneId, zoneName){
  const card = qs(`[data-zone="${zoneId}"].cfwaf-zc-card`);
  if (!card) return;

  // Under Attack toggle
  const attackToggle = qs('.cfwaf-zc-attack-toggle', card);
  attackToggle?.addEventListener('change', function(){
    const on = this.checked;
    this.disabled = true;
    ajax('wpwaf_zone_setting_update', {zone_id:zoneId, key:'security_level', value: on ? 'under_attack' : 'medium'}, (res) => {
      this.disabled = false;
      if (res.success){
        updateAttackUI(zoneId, on);
        toast(on ? '⚡ Under Attack enabled on ' + zoneName : '✓ Under Attack disabled on ' + zoneName, !on || true);
      } else {
        this.checked = !on;
        toast('Failed: ' + (res.data?.message||'error'), false);
      }
    });
  });

  // Dev Mode toggle
  const devToggle = qs('.cfwaf-zc-devmode-toggle', card);
  devToggle?.addEventListener('change', function(){
    const on = this.checked;
    this.disabled = true;
    ajax('wpwaf_zone_setting_update', {zone_id:zoneId, key:'development_mode', value: on ? 'on' : 'off'}, (res) => {
      this.disabled = false;
      if (res.success) toast((on ? '🔧 Dev mode on' : '✓ Dev mode off') + ' — ' + zoneName);
      else { this.checked = !on; toast('Failed: ' + (res.data?.message||'error'), false); }
    });
  });

  // Purge everything
  qs('.cfwaf-zc-purge-all', card)?.addEventListener('click', function(){
    if (!confirm('Purge ALL cache for ' + zoneName + '?')) return;
    this.disabled = true; this.textContent = '⏳ Purging…';
    const btn = this;
    ajax('wpwaf_purge_cache', {zone_id:zoneId, mode:'all'}, (res) => {
      btn.disabled = false; btn.textContent = '🗑 Purge Everything';
      res.success ? toast('✓ Cache purged — ' + zoneName) : toast('Failed: ' + (res.data?.message||'error'), false);
    });
  });

  // Show/hide URL purge
  qs('.cfwaf-zc-purge-urls-toggle', card)?.addEventListener('click', function(){
    const row = qs('#zc-url-row-' + zoneId);
    const vis = row.style.display !== 'none';
    row.style.display = vis ? 'none' : '';
    this.textContent = vis ? 'By URL' : 'Cancel';
  });

  // Purge by URL
  qs('.cfwaf-zc-purge-url-btn', card)?.addEventListener('click', function(){
    const urls = qs('#zc-url-input-' + zoneId)?.value || '';
    if (!urls.trim()){ toast('Enter at least one URL', false); return; }
    this.disabled = true; this.textContent = '…';
    const btn = this;
    ajax('wpwaf_purge_cache', {zone_id:zoneId, mode:'urls', urls}, (res) => {
      btn.disabled = false; btn.textContent = 'Purge';
      res.success ? toast('✓ URLs purged — ' + zoneName) : toast('Failed: ' + (res.data?.message||'error'), false);
    });
  });

  // Settings accordion
  qs('.cfwaf-zc-settings-toggle', card)?.addEventListener('click', function(){
    const body = qs('#zc-settings-' + zoneId);
    const open = body.classList.toggle('open');
    qs('.cfwaf-zc-chevron', this).textContent = open ? '▲' : '▼';
    if (open && !body.dataset.loaded) loadSettings(zoneId, body);
  });
}

// ── Update Under Attack UI ─────────────────────────────────────────────────────
function updateAttackUI(zoneId, on){
  const row   = qs('#zc-attack-row-' + zoneId);
  const label = qs('#zc-attack-label-' + zoneId);
  const toggle = qs(`[data-zone="${zoneId}"].cfwaf-zc-attack-toggle`);
  if (row)    row.classList.toggle('safe', !on);
  if (label)  label.querySelector('.cfwaf-zc-attack-sub').textContent = on ? 'ON — All visitors challenged' : 'Off';
  if (toggle) toggle.checked = on;
}

// ── Load settings for a zone ──────────────────────────────────────────────────
function loadSettings(zoneId, body){
  body.innerHTML = '<div class="cfwaf-zc-loading-settings"><span class="cfwaf-zc-spinner"></span> Loading…</div>';
  ajax('wpwaf_zone_settings_load', {zone_id:zoneId}, (res) => {
    body.dataset.loaded = '1';
    if (!res.success){ body.innerHTML = '<div style="font-size:12px;color:#dc2626;">Failed: ' + (res.data?.message||'error') + '</div>'; return; }
    const s = res.data.settings;

    // Sync attack/devmode toggles from actual settings
    const secLevel = s['security_level'] || 'medium';
    updateAttackUI(zoneId, secLevel === 'under_attack');
    const devToggle = qs(`[data-zone="${zoneId}"].cfwaf-zc-devmode-toggle`);
    if (devToggle) devToggle.checked = s['development_mode'] === 'on';

    body.innerHTML = settingsHTML(zoneId, s);
    wireSettings(zoneId, body);
  });
}

function settingsHTML(zoneId, s){
  const row = (label, hint, ctrl) =>
    `<div class="cfwaf-zc-setting-row">
      <div><div class="cfwaf-zc-setting-name">${label}</div><div class="cfwaf-zc-setting-hint">${hint}</div></div>
      <div class="cfwaf-zc-setting-control">${ctrl}</div>
    </div>`;

  const sel = (key, opts, current) =>
    `<select class="cfwaf-zc-setting-select" data-zone="${zoneId}" data-key="${key}">`
    + opts.map(([v,l]) => `<option value="${v}"${current===v?' selected':''}>${l}</option>`).join('')
    + `</select>`;

  const tog = (key, current, cls='') =>
    `<label class="cfwaf-zc-switch ${cls}"><input type="checkbox" class="cfwaf-zc-setting-toggle" data-zone="${zoneId}" data-key="${key}" ${current==='on'?'checked':''}><span class="cfwaf-zc-switch-slider"></span></label>`;

  const ssl = s['ssl'] || 'off';
  const secLevel = s['security_level'] || 'medium';
  const cacheLevel = s['cache_level'] || 'aggressive';
  const bct = String(s['browser_cache_ttl'] || 14400);
  const alwaysHttps = s['always_use_https'] || 'off';
  const rocketLoader = s['rocket_loader'] || 'off';
  const hotlink = s['hotlink_protection'] || 'off';
  const emailObf = s['email_obfuscation'] || 'off';

  return [
    row('🔒 SSL Mode', 'Encryption mode between visitors and Cloudflare',
      sel('ssl', [['off','Off'],['flexible','Flexible'],['full','Full'],['strict','Full (Strict)']], ssl)),
    row('↗ Always Use HTTPS', 'Redirect all HTTP to HTTPS',
      tog('always_use_https', alwaysHttps, 'green')),
    row('⚡ Security Level', 'Challenge threshold for suspicious visitors',
      sel('security_level', [['essentially_off','Essentially Off'],['low','Low'],['medium','Medium'],['high','High']], secLevel === 'under_attack' ? 'high' : secLevel)),
    row('🗄 Cache Level', 'How aggressively to cache static content',
      sel('cache_level', [['bypass','Bypass'],['basic','Basic'],['simplified','Simplified'],['aggressive','Aggressive'],['cache_everything','Cache Everything']], cacheLevel)),
    row('⏱ Browser Cache TTL', 'How long browsers should cache resources',
      sel('browser_cache_ttl', [['1800','30 min'],['3600','1 hr'],['7200','2 hrs'],['14400','4 hrs'],['28800','8 hrs'],['57600','16 hrs'],['86400','1 day'],['604800','1 week'],['2592000','1 month']], bct)),
    row('🚀 Rocket Loader', 'Async JS loading to improve page speed',
      tog('rocket_loader', rocketLoader, 'green')),
    row('🔗 Hotlink Protection', 'Block image hotlinking from other sites',
      tog('hotlink_protection', hotlink, 'green')),
    row('✉ Email Obfuscation', 'Hide email addresses from scrapers',
      tog('email_obfuscation', emailObf, 'green')),
    `<div class="cfwaf-zc-setting-row">
      <div><div class="cfwaf-zc-setting-name">📦 Minification</div><div class="cfwaf-zc-setting-hint">Minify JS/CSS/HTML at Cloudflare edge</div></div>
      <div class="cfwaf-zc-setting-control" style="display:flex;gap:8px;align-items:center;">
        <label style="font-size:11px;font-weight:600;display:flex;align-items:center;gap:4px;cursor:pointer;"><input type="checkbox" class="cfwaf-zc-minify" data-zone="${zoneId}" data-type="js" ${minify.js==='on'?'checked':''} style="accent-color:var(--zc-orange)"> JS</label>
        <label style="font-size:11px;font-weight:600;display:flex;align-items:center;gap:4px;cursor:pointer;"><input type="checkbox" class="cfwaf-zc-minify" data-zone="${zoneId}" data-type="css" ${minify.css==='on'?'checked':''} style="accent-color:var(--zc-orange)"> CSS</label>
        <label style="font-size:11px;font-weight:600;display:flex;align-items:center;gap:4px;cursor:pointer;"><input type="checkbox" class="cfwaf-zc-minify" data-zone="${zoneId}" data-type="html" ${minify.html==='on'?'checked':''} style="accent-color:var(--zc-orange)"> HTML</label>
      </div>
    </div>`,
  ].join('');
}

function wireSettings(zoneId, body){
  // Dropdowns
  body.querySelectorAll('.cfwaf-zc-setting-select').forEach(sel => {
    sel.addEventListener('change', function(){
      const key = this.dataset.key, value = this.value;
      this.disabled = true;
      ajax('wpwaf_zone_setting_update', {zone_id:zoneId, key, value}, (res) => {
        this.disabled = false;
        res.success ? toast('✓ ' + key.replace(/_/g,' ') + ' updated') : toast('Failed: ' + (res.data?.message||'error'), false);
      });
    });
  });
  // Toggle switches
  body.querySelectorAll('.cfwaf-zc-setting-toggle').forEach(tog => {
    tog.addEventListener('change', function(){
      const key = this.dataset.key, value = this.checked ? 'on' : 'off';
      this.disabled = true;
      ajax('wpwaf_zone_setting_update', {zone_id:zoneId, key, value}, (res) => {
        this.disabled = false;
        res.success ? toast('✓ ' + key.replace(/_/g,' ') + ' ' + value) : (this.checked=!this.checked, toast('Failed: '+(res.data?.message||'error'),false));
      });
    });
  });

}

// ── Enable Under Attack on ALL zones ─────────────────────────────────────────
qs('#cfwaf-zc-attack-all-on')?.addEventListener('click', function(){
  if (!allZones.length){ toast('Load zones first', false); return; }
  const count = allZones.length;
  if (!confirm('Enable Under Attack Mode on ALL ' + count + ' zones? This will challenge every visitor on every domain.')) return;
  this.disabled = true; this.textContent = '⏳ Enabling…';
  const btn = this;
  let done = 0, failed = 0;
  allZones.forEach(z => {
    ajax('wpwaf_zone_setting_update', {zone_id:z.id, key:'security_level', value:'under_attack'}, (res) => {
      if (res.success){ done++; updateAttackUI(z.id, true); }
      else failed++;
      if (done + failed === count){
        btn.disabled = false;
        btn.textContent = '✓ Enabled on ' + done + '/' + count;
        toast('⚡ Under Attack enabled on ' + done + ' zone' + (done!==1?'s':''), failed===0);
      }
    });
  });
});
})();
</script>
