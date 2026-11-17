/* WP WAF Manager: Zone Controls screen.
 * Values from PHP arrive in window.wpwafPage, printed by WPWAF_Admin::page_script()
 * in includes/views/page-zone-controls.php. */
'use strict';
(function(){
const NONCE    = wpwafPage.nonce;
const AJAX_URL = wpwafPage.ajax_url;

let allZones = [];
// Real Under Attack / Development Mode state per zone, loaded in small batches.
const zoneState = {};
let stateQueue = [];
let stateBusy  = false;

function applyState(zoneId){
  const st = zoneState[zoneId];
  const attack = qs(`[data-zone="${zoneId}"].cfwaf-zc-attack-toggle`);
  const dev    = qs(`[data-zone="${zoneId}"].cfwaf-zc-devmode-toggle`);
  if (!st){
    if (attack) attack.disabled = true;
    if (dev)    dev.disabled = true;
    const label = qs('#zc-attack-label-' + zoneId);
    if (label) label.querySelector('.cfwaf-zc-attack-sub').textContent = 'Checking…';
    return;
  }
  if (attack) attack.disabled = false;
  if (dev)    { dev.disabled = false; dev.checked = !!st.dev; }
  updateAttackUI(zoneId, !!st.attack);
}

function loadStates(ids){
  ids.forEach(id => { if (!zoneState[id] && !stateQueue.includes(id)) stateQueue.push(id); });
  if (stateBusy || !stateQueue.length) return;
  stateBusy = true;
  const batch = stateQueue.splice(0, 10);
  ajax('wpwaf_zone_quick_state', {zone_ids: batch.join(',')}, (res) => {
    const states = (res.success && res.data.states) ? res.data.states : {};
    batch.forEach(id => {
      // When a zone could not be read, fall back to Off so the switch still works.
      zoneState[id] = states[id] || {attack:false, dev:false};
      applyState(id);
    });
    stateBusy = false;
    loadStates([]);
  });
}

// Placeholder and hint for each purge type. Cloudflare takes up to 100 items per purge.
const PURGE_HELP = {
  urls:     ['One full URL per line&#10;https://example.com/blog/post/', 'Full URLs, one per line. Up to 100 per purge.'],
  hosts:    ['One hostname per line&#10;www.example.com', 'Purges every cached file on each hostname. Up to 100 per purge.'],
  prefixes: ['One prefix per line, no https://&#10;www.example.com/blog/', 'Purges everything under each path. Up to 100 per purge.'],
  tags:     ['One cache tag per line&#10;product-123', 'Purges content whose Cache-Tag header matches. Up to 100 per purge.'],
};
const PURGE_DONE = { urls:'URLs', hosts:'hostnames', prefixes:'prefixes', tags:'cache tags' };

function qs(s,ctx){ return (ctx||document).querySelector(s); }
function esc(v){ return String(v ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }
function toast(msg, ok=true, icon){
  const el = document.createElement('div');
  el.className = 'cfwaf-zc-toast ' + (ok?'ok':'err');
  if (window.wpwafToastBody) el.innerHTML = wpwafToastBody(msg, ok, icon); else el.textContent = msg;
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
  if (!res.success){ grid.innerHTML = '<div class="cfwaf-zc-loading">Failed to load zones: ' + esc(res.data?.message||'error') + '</div>'; return; }
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
  zones.forEach(z => { initCard(z.id, z.name); applyState(z.id); });
  loadStates(zones.map(z => z.id));
}

function cardHTML(z){
  z = Object.assign({}, z, { id: esc(z.id) });
  return `<div class="cfwaf-zc-card" data-zone="${z.id}">
    <div class="cfwaf-zc-card-header">
      <h3 title="${esc(z.name)}">${esc(z.name)}</h3>
      <span class="cfwaf-zc-plan">${esc(z.plan)}</span>
    </div>
    <!-- Under Attack -->
    <div class="cfwaf-zc-attack-row safe" id="zc-attack-row-${z.id}">
      <div>
        <div class="cfwaf-zc-attack-label" id="zc-attack-label-${z.id}">${wpwafIcon('zap')} Under Attack Mode <span class="cfwaf-zc-attack-sub">Off</span></div>
      </div>
      <label class="cfwaf-zc-switch" title="Toggle Under Attack Mode">
        <input type="checkbox" class="cfwaf-zc-attack-toggle" data-zone="${z.id}">
        <span class="cfwaf-zc-switch-slider"></span>
      </label>
    </div>
    <!-- Dev Mode -->
    <div class="cfwaf-zc-devmode-row">
      <div class="cfwaf-zc-devmode-label">${wpwafIcon('wrench')} Development Mode</div>
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
          <button class="cfwaf-zc-btn cfwaf-zc-btn-purge cfwaf-zc-purge-all" data-zone="${z.id}">${wpwafIcon('trash')} Purge Everything</button>
          <button class="cfwaf-zc-btn cfwaf-zc-btn-ghost cfwaf-zc-purge-urls-toggle" data-zone="${z.id}" aria-expanded="false">More options</button>
        </div>
        <div class="cfwaf-zc-purge-more" id="zc-url-row-${z.id}">
          <select class="cfwaf-zc-purge-type" id="zc-purge-type-${z.id}" aria-label="Purge by">
            <option value="urls">By URL</option>
            <option value="hosts">By hostname</option>
            <option value="prefixes">By prefix</option>
            <option value="tags">By cache tag</option>
          </select>
          <textarea id="zc-url-input-${z.id}" class="cfwaf-zc-url-input" placeholder="${PURGE_HELP.urls[0]}" rows="3" style="width:100%;font-size:11px;padding:6px;border:1px solid #e2e6ea;border-radius:6px;font-family:monospace;resize:vertical;box-sizing:border-box;"></textarea>
          <div class="cfwaf-zc-purge-actions">
            <span class="cfwaf-zc-purge-hint" id="zc-purge-hint-${z.id}">${PURGE_HELP.urls[1]}</span>
            <button class="cfwaf-zc-btn cfwaf-zc-btn-orange cfwaf-zc-purge-url-btn" data-zone="${z.id}">Purge</button>
          </div>
        </div>
      </div>
      <!-- Zone Settings -->
      <div>
        <button class="cfwaf-zc-settings-toggle" data-zone="${z.id}">
          <span>${wpwafIcon('settings')} Zone Settings</span>
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
        zoneState[zoneId] = Object.assign({}, zoneState[zoneId], {attack:on});
        updateAttackUI(zoneId, on);
        toast(on ? 'Under Attack enabled on ' + zoneName : 'Under Attack disabled on ' + zoneName, true, on ? 'zap' : 'check');
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
      if (res.success){ zoneState[zoneId] = Object.assign({}, zoneState[zoneId], {dev:on}); toast((on ? 'Dev mode on' : 'Dev mode off') + ': ' + zoneName, true, on ? 'wrench' : 'check'); }
      else { this.checked = !on; toast('Failed: ' + (res.data?.message||'error'), false); }
    });
  });

  // Purge everything
  qs('.cfwaf-zc-purge-all', card)?.addEventListener('click', function(){
    if (!confirm('Purge ALL cache for ' + zoneName + '?')) return;
    this.disabled = true; this.innerHTML = wpwafIconText('hourglass', 'Purging…');
    const btn = this;
    ajax('wpwaf_purge_cache', {zone_id:zoneId, mode:'all'}, (res) => {
      btn.disabled = false; btn.innerHTML = wpwafIconText('trash', 'Purge Everything');
      res.success ? toast('Cache purged: ' + zoneName) : toast('Failed: ' + (res.data?.message||'error'), false);
    });
  });

  // Show/hide URL purge
  qs('.cfwaf-zc-purge-urls-toggle', card)?.addEventListener('click', function(){
    const open = qs('#zc-url-row-' + zoneId).classList.toggle('open');
    this.textContent = open ? 'Cancel' : 'More options';
    this.setAttribute('aria-expanded', open ? 'true' : 'false');
  });

  // Purge type picker updates the placeholder and hint.
  qs('#zc-purge-type-' + zoneId, card)?.addEventListener('change', function(){
    const help = PURGE_HELP[this.value] || PURGE_HELP.urls;
    const ta = qs('#zc-url-input-' + zoneId);
    if (ta) ta.placeholder = help[0].replace(/&#10;/g, '\n');
    const hint = qs('#zc-purge-hint-' + zoneId);
    if (hint) hint.textContent = help[1];
  });

  // Purge by URL, hostname, prefix or cache tag
  qs('.cfwaf-zc-purge-url-btn', card)?.addEventListener('click', function(){
    const items = qs('#zc-url-input-' + zoneId)?.value || '';
    const mode  = qs('#zc-purge-type-' + zoneId)?.value || 'urls';
    if (!items.trim()){ toast('Enter at least one item, one per line', false); return; }
    this.disabled = true; this.textContent = '…';
    const btn = this;
    ajax('wpwaf_purge_cache', {zone_id:zoneId, mode, items}, (res) => {
      btn.disabled = false; btn.textContent = 'Purge';
      res.success ? toast((res.data?.count || '') + ' ' + (PURGE_DONE[mode] || 'items') + ' purged: ' + zoneName) : toast('Failed: ' + (res.data?.message||'error'), false);
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
  if (label)  label.querySelector('.cfwaf-zc-attack-sub').textContent = on ? 'ON: all visitors challenged' : 'Off';
  if (toggle) toggle.checked = on;
}

// ── Load settings for a zone ──────────────────────────────────────────────────
function loadSettings(zoneId, body){
  body.innerHTML = '<div class="cfwaf-zc-loading-settings"><span class="cfwaf-zc-spinner"></span> Loading…</div>';
  ajax('wpwaf_zone_settings_load', {zone_id:zoneId}, (res) => {
    body.dataset.loaded = '1';
    if (!res.success){ body.innerHTML = '<div style="font-size:12px;color:#dc2626;">Failed: ' + esc(res.data?.message||'error') + '</div>'; return; }
    const s = res.data.settings || {};
    s.__tiered  = res.data.tiered;   // 'on' | 'off' | null when it could not be read
    s.__managed = res.data.managed;  // { transform_id: {enabled, has_conflict} } | null

    // Sync attack/devmode toggles from actual settings
    const secLevel = s['security_level'] || 'medium';
    zoneState[zoneId] = {attack: secLevel === 'under_attack', dev: s['development_mode'] === 'on'};
    applyState(zoneId);

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
  const cacheLevel = s['cache_level'] || 'aggressive';
  const bct = String(s['browser_cache_ttl'] || 14400);
  const alwaysHttps = s['always_use_https'] || 'off';
  const rocketLoader = s['rocket_loader'] || 'off';
  const hotlink = s['hotlink_protection'] || 'off';
  const emailObf = s['email_obfuscation'] || 'off';

  return [
    row(wpwafIconText('lock', 'SSL Mode'), 'Encryption mode between visitors and Cloudflare',
      sel('ssl', [['off','Off'],['flexible','Flexible'],['full','Full'],['strict','Full (Strict)']], ssl)),
    row('↗ Always Use HTTPS', 'Redirect all HTTP to HTTPS',
      tog('always_use_https', alwaysHttps, 'green')),
    row(wpwafIconText('database', 'Cache Level'), 'How query strings affect caching of static files',
      sel('cache_level', [['aggressive','Standard'],['simplified','Ignore query string'],['basic','No query string']], cacheLevel)),
    row(wpwafIconText('timer', 'Browser Cache TTL'), 'How long browsers should cache resources',
      sel('browser_cache_ttl', [['1800','30 min'],['3600','1 hr'],['7200','2 hrs'],['14400','4 hrs'],['28800','8 hrs'],['57600','16 hrs'],['86400','1 day'],['604800','1 week'],['2592000','1 month']], bct)),
    row(wpwafIconText('rocket', 'Rocket Loader'), 'Async JS loading to improve page speed',
      tog('rocket_loader', rocketLoader, 'green')),
    row(wpwafIconText('link', 'Hotlink Protection'), 'Block image hotlinking from other sites',
      tog('hotlink_protection', hotlink, 'green')),
    row(wpwafIconText('mail', 'Email Obfuscation'), 'Hide email addresses from scrapers',
      tog('email_obfuscation', emailObf, 'green')),
    row(wpwafIconText('globe', 'Smart Tiered Cache'), 'Fewer requests to your server by caching through one upper-tier data center',
      s.__tiered === null || s.__tiered === undefined
        ? '<span class="cfwaf-zc-setting-hint">Unavailable</span>'
        : `<label class="cfwaf-zc-switch green"><input type="checkbox" class="cfwaf-zc-tiered-toggle" data-zone="${zoneId}" ${s.__tiered==='on'?'checked':''}><span class="cfwaf-zc-switch-slider"></span></label>`),
    '<div class="cfwaf-zc-setting-group">Security headers</div>',
    managedRow(zoneId, s.__managed, 'add_security_headers', wpwafIconText('shield', 'Add security headers'), 'Adds X-Content-Type-Options, X-Frame-Options, Referrer-Policy and more'),
    managedRow(zoneId, s.__managed, 'remove_x-powered-by_header', wpwafIconText('eye-off', 'Remove X-Powered-By'), 'Hides the PHP version your server reports'),
    '<div class="cfwaf-zc-setting-note"><strong>Security Level:</strong> Cloudflare retired the Low, Medium and High levels in 2025 and now uses this setting only for Under Attack mode. Use the Under Attack switch at the top of this card.</div>',
  ].join('');
}

// Managed Transform row. Cloudflare will not enable a transform that conflicts with one already on.
function managedRow(zoneId, managed, id, label, hint){
  const row = (ctrl, h) => `<div class="cfwaf-zc-setting-row">
      <div><div class="cfwaf-zc-setting-name">${label}</div><div class="cfwaf-zc-setting-hint">${h}</div></div>
      <div class="cfwaf-zc-setting-control">${ctrl}</div>
    </div>`;
  if (!managed) return row('<span class="cfwaf-zc-setting-hint">Unavailable</span>', 'Needs the Zone → Managed Headers → Edit token permission');
  const t = managed[id];
  if (!t) return row('<span class="cfwaf-zc-setting-hint">Unavailable</span>', 'Not offered for this zone');
  const blocked = t.has_conflict && !t.enabled;
  return row(`<label class="cfwaf-zc-switch green"><input type="checkbox" class="cfwaf-zc-managed-toggle" data-zone="${zoneId}" data-transform="${id}" ${t.enabled?'checked':''} ${blocked?'disabled':''}><span class="cfwaf-zc-switch-slider"></span></label>`,
    blocked ? 'Turn off the conflicting Managed Transform in Cloudflare first' : hint);
}

function wireSettings(zoneId, body){
  // Dropdowns
  body.querySelectorAll('.cfwaf-zc-setting-select').forEach(sel => {
    sel.dataset.prev = sel.value;
    sel.addEventListener('change', function(){
      const key = this.dataset.key, value = this.value;
      this.disabled = true;
      ajax('wpwaf_zone_setting_update', {zone_id:zoneId, key, value}, (res) => {
        this.disabled = false;
        if (res.success){
          this.dataset.prev = value;
          toast(key.replace(/_/g,' ') + ' updated');
        } else {
          this.value = this.dataset.prev; // put back what Cloudflare still has
          toast('Failed: ' + (res.data?.message||'error'), false);
        }
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
        res.success ? toast(key.replace(/_/g,' ') + ' ' + value) : (this.checked=!this.checked, toast('Failed: '+(res.data?.message||'error'),false));
      });
    });
  });
  // Smart Tiered Cache
  body.querySelectorAll('.cfwaf-zc-tiered-toggle').forEach(tog => {
    tog.addEventListener('change', function(){
      const on = this.checked;
      this.disabled = true;
      ajax('wpwaf_zone_tiered_cache', {zone_id:zoneId, value: on ? '1' : '0'}, (res) => {
        this.disabled = false;
        res.success ? toast('Smart Tiered Cache ' + (on ? 'on' : 'off')) : (this.checked=!on, toast('Failed: '+(res.data?.message||'error'),false));
      });
    });
  });
  // Managed Transforms
  body.querySelectorAll('.cfwaf-zc-managed-toggle').forEach(tog => {
    tog.addEventListener('change', function(){
      const on = this.checked;
      this.disabled = true;
      ajax('wpwaf_zone_managed_header', {zone_id:zoneId, transform:this.dataset.transform, value: on ? '1' : '0'}, (res) => {
        this.disabled = false;
        res.success ? toast((this.dataset.transform === 'add_security_headers' ? 'Security headers' : 'X-Powered-By removal') + ' ' + (on ? 'on' : 'off'))
                    : (this.checked=!on, toast('Failed: '+(res.data?.message||'error'),false));
      });
    });
  });

}

// ── Enable Under Attack on ALL zones ─────────────────────────────────────────
qs('#cfwaf-zc-attack-all-on')?.addEventListener('click', function(){
  if (!allZones.length){ toast('Load zones first', false); return; }
  const count = allZones.length;
  if (!confirm('Enable Under Attack Mode on ALL ' + count + ' zones? This will challenge every visitor on every domain.')) return;
  this.disabled = true; this.innerHTML = wpwafIconText('hourglass', 'Enabling…');
  const btn = this;
  let done = 0, failed = 0;
  allZones.forEach(z => {
    ajax('wpwaf_zone_setting_update', {zone_id:z.id, key:'security_level', value:'under_attack'}, (res) => {
      if (res.success){ done++; zoneState[z.id] = Object.assign({}, zoneState[z.id], {attack:true}); updateAttackUI(z.id, true); }
      else failed++;
      if (done + failed === count){
        btn.disabled = false;
        btn.innerHTML = wpwafIconText('check', 'Enabled on ' + done + '/' + count);
        toast('Under Attack enabled on ' + done + ' zone' + (done!==1?'s':''), failed===0, failed===0 ? 'zap' : 'x');
      }
    });
  });
});
})();
