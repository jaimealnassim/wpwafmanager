<?php
defined( 'ABSPATH' ) || exit;
$nonce    = wp_create_nonce( 'wpwaf_nonce' );
$ajax_url = admin_url( 'admin-ajax.php' );
?>
<style>
:root{--se-orange:#FF6A00;--se-border:#e2e6ea;--se-bg:#f8f9fb;--se-dark:#1a1a2e;--se-muted:#6b7280;}
.cfwaf-se-wrap{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;max-width:1200px;padding:24px 20px;color:var(--se-dark);}
.cfwaf-se-header{margin-bottom:20px;}
.cfwaf-se-header h1{font-size:22px;font-weight:700;margin:0 0 3px;display:flex;align-items:center;gap:8px;}
.cfwaf-se-header h1 .dashicons{color:var(--se-orange);font-size:24px;width:24px;height:24px;}
.cfwaf-se-header-sub{font-size:12px;color:var(--se-muted);}
.cfwaf-se-no-creds{background:#fff8f5;border:1px solid #fcd9c0;border-radius:8px;padding:20px 24px;color:#92400e;}
.cfwaf-se-plan-notice{background:#faf5ff;border:1px solid #d8b4fe;border-radius:8px;padding:16px 18px;margin-bottom:16px;color:#6b21a8;font-size:13px;display:flex;gap:10px;align-items:flex-start;}.cfwaf-se-plan-notice strong{display:block;margin-bottom:4px;font-size:14px;}.cfwaf-se-plan-notice ul{margin:8px 0 0;padding-left:18px;font-size:12px;}.cfwaf-se-plan-notice li{margin-bottom:3px;}

/* Controls bar */
.cfwaf-se-controls{background:#fff;border:1px solid var(--se-border);border-radius:8px;padding:14px 16px;margin-bottom:16px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;}
.cfwaf-se-select{padding:7px 10px;border:1px solid var(--se-border);border-radius:6px;font-size:13px;color:var(--se-dark);background:#fff;cursor:pointer;}
.cfwaf-se-select:focus{outline:none;border-color:var(--se-orange);}
.cfwaf-se-zone-select{flex:1;min-width:200px;}
.cfwaf-se-btn{display:inline-flex;align-items:center;gap:6px;padding:7px 16px;border-radius:6px;font-size:13px;font-weight:600;cursor:pointer;border:none;transition:all .15s;white-space:nowrap;}
.cfwaf-se-btn-primary{background:var(--se-orange);color:#fff;}
.cfwaf-se-btn-primary:hover{background:#d95500;}
.cfwaf-se-btn:disabled{opacity:.5;cursor:not-allowed;}
.cfwaf-se-count-badge{margin-left:auto;font-size:12px;color:var(--se-muted);white-space:nowrap;}

/* Filter tabs */
.cfwaf-se-filters{display:flex;gap:6px;margin-bottom:14px;flex-wrap:wrap;}
.cfwaf-se-filter-tab{padding:5px 12px;border:1px solid var(--se-border);border-radius:20px;font-size:12px;font-weight:600;cursor:pointer;background:#fff;color:var(--se-muted);transition:all .12s;}
.cfwaf-se-filter-tab:hover{border-color:#d1d5db;color:var(--se-dark);}
.cfwaf-se-filter-tab.active{background:var(--se-dark);color:#fff;border-color:var(--se-dark);}

/* Summary bar */
.cfwaf-se-summary{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:10px;margin-bottom:16px;}
.cfwaf-se-summary-card{background:#fff;border:1px solid var(--se-border);border-radius:8px;padding:10px 14px;text-align:center;}
.cfwaf-se-summary-num{font-size:22px;font-weight:800;color:var(--se-dark);line-height:1;}
.cfwaf-se-summary-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--se-muted);margin-top:4px;}
.cfwaf-se-summary-card.block .cfwaf-se-summary-num{color:#dc2626;}
.cfwaf-se-summary-card.challenge .cfwaf-se-summary-num{color:#d97706;}
.cfwaf-se-summary-card.jschallenge .cfwaf-se-summary-num{color:#0284c7;}
.cfwaf-se-summary-card.managed .cfwaf-se-summary-num{color:#7c3aed;}
.cfwaf-se-summary-card.allow .cfwaf-se-summary-num{color:#059669;}

/* Table */
.cfwaf-se-table-wrap{background:#fff;border:1px solid var(--se-border);border-radius:8px;overflow:hidden;}
.cfwaf-se-table-scroll{overflow-x:auto;-webkit-overflow-scrolling:touch;}
.cfwaf-se-table{width:100%;border-collapse:collapse;font-size:12px;}
.cfwaf-se-table th{background:var(--se-bg);padding:9px 12px;text-align:left;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--se-muted);border-bottom:1px solid var(--se-border);white-space:nowrap;}
.cfwaf-se-table td{padding:9px 12px;border-bottom:1px solid #f1f3f5;vertical-align:top;}
.cfwaf-se-table tr:last-child td{border-bottom:none;}
.cfwaf-se-table tr:hover td{background:#fafbfc;}

/* Action badges */
.cfwaf-se-action{display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:10px;font-size:10px;font-weight:700;white-space:nowrap;}
.cfwaf-se-action.block{background:#fee2e2;color:#b91c1c;}
.cfwaf-se-action.challenge,.cfwaf-se-action.managed_challenge{background:#fef3c7;color:#92400e;}
.cfwaf-se-action.js_challenge{background:#e0f2fe;color:#0369a1;}
.cfwaf-se-action.allow,.cfwaf-se-action.skip{background:#d1fae5;color:#065f46;}
.cfwaf-se-action.log{background:#f1f5f9;color:#475569;}

/* IP + country */
.cfwaf-se-ip{font-family:monospace;font-size:12px;font-weight:600;}
.cfwaf-se-country{font-size:10px;color:var(--se-muted);}

/* Path */
.cfwaf-se-path{font-family:monospace;font-size:11px;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}

/* UA */
.cfwaf-se-ua{font-size:11px;color:var(--se-muted);max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}

/* Source badge */
.cfwaf-se-source{font-size:10px;padding:2px 6px;background:#f1f5f9;border-radius:4px;color:#475569;font-weight:600;}

/* Time */
.cfwaf-se-time{font-size:11px;color:var(--se-muted);white-space:nowrap;}

/* Ray ID */
.cfwaf-se-ray{font-family:monospace;font-size:10px;color:var(--se-muted);}

/* States */
.cfwaf-se-empty{text-align:center;padding:40px;color:var(--se-muted);}
.cfwaf-se-spinner{display:inline-block;width:14px;height:14px;border:2px solid rgba(255,106,0,.3);border-top-color:var(--se-orange);border-radius:50%;animation:se-spin .6s linear infinite;vertical-align:middle;}
@keyframes se-spin{to{transform:rotate(360deg);}}
.cfwaf-se-error{background:#fee2e2;border:1px solid #fca5a5;border-radius:6px;padding:12px 16px;color:#b91c1c;font-size:13px;margin-bottom:14px;}

@media(max-width:660px){
  .cfwaf-se-controls{flex-direction:column;align-items:stretch;}
  .cfwaf-se-zone-select{min-width:0;}
  .cfwaf-se-btn-primary{justify-content:center;}
  .cfwaf-se-count-badge{margin-left:0;}
  .cfwaf-se-table{min-width:700px;}
  .cfwaf-se-table-scroll{-webkit-mask-image:linear-gradient(to right,black 85%,transparent 100%);mask-image:linear-gradient(to right,black 85%,transparent 100%);}
}
</style>

<div class="cfwaf-se-wrap">
  <div class="cfwaf-se-header">
    <h1><span class="dashicons dashicons-warning"></span> Security Events</h1>
    <div class="cfwaf-se-header-sub">Live read from Cloudflare — no data is saved or synced. Select a zone and click Load Events.</div>
  </div>

  <?php if ( ! $has_creds ) : ?>
  <div class="cfwaf-se-no-creds">
    <strong>No Cloudflare account connected.</strong> Please <a href="<?php echo esc_url( admin_url('admin.php?page=wpwafmanager') ); ?>">add your credentials</a> first.
  </div>
  <?php else : ?>

  <!-- Controls -->
  <div class="cfwaf-se-controls">
    <select id="cfwaf-se-zone" class="cfwaf-se-select cfwaf-se-zone-select">
      <option value="">— Select a zone —</option>
    </select>
    <select id="cfwaf-se-hours" class="cfwaf-se-select">
      <option value="1">Last 1 hour</option>
      <option value="6">Last 6 hours</option>
      <option value="24" selected>Last 24 hours</option>
      <option value="72">Last 3 days</option>
      <option value="168">Last 7 days</option>
    </select>
    <select id="cfwaf-se-limit" class="cfwaf-se-select">
      <option value="50">50 events</option>
      <option value="100" selected>100 events</option>
      <option value="200">200 events</option>
      <option value="500">500 events</option>
    </select>
    <button id="cfwaf-se-load" class="cfwaf-se-btn cfwaf-se-btn-primary" disabled>
      <span class="dashicons dashicons-update" style="font-size:14px;width:14px;height:14px;"></span> Load Events
    </button>
    <span class="cfwaf-se-count-badge" id="cfwaf-se-count"></span>
  </div>

  <!-- Plan notice -->
  <div id="cfwaf-se-plan-notice" class="cfwaf-se-plan-notice" style="display:none;">
    <span class="dashicons dashicons-lock" style="flex-shrink:0;font-size:20px;width:20px;height:20px;margin-top:2px;color:#7c3aed;"></span>
    <div>
      <strong>Pro plan required for Security Events</strong>
      The <code>firewallEventsAdaptive</code> GraphQL dataset requires a Cloudflare <strong>Pro plan or higher</strong>.
      Free zones do not have access to firewall event logs via the API.
      <ul>
        <li>Upgrade this zone to Pro at <a href="https://dash.cloudflare.com" target="_blank" rel="noopener">dash.cloudflare.com</a> → Your zone → Overview → Upgrade</li>
        <li>Or view events manually in Cloudflare Dashboard → Security → Events</li>
      </ul>
    </div>
  </div>
  <div id="cfwaf-se-error" class="cfwaf-se-error" style="display:none;"></div>

  <!-- Filter pills (shown after load) -->
  <div class="cfwaf-se-filters" id="cfwaf-se-filters" style="display:none;">
    <button class="cfwaf-se-filter-tab active" data-action="">All</button>
    <button class="cfwaf-se-filter-tab" data-action="block">🚫 Block</button>
    <button class="cfwaf-se-filter-tab" data-action="managed_challenge">⚠️ Managed Challenge</button>
    <button class="cfwaf-se-filter-tab" data-action="js_challenge">🔒 JS Challenge</button>
    <button class="cfwaf-se-filter-tab" data-action="challenge">🟡 Challenge</button>
    <button class="cfwaf-se-filter-tab" data-action="allow">✅ Allow</button>
    <button class="cfwaf-se-filter-tab" data-action="log">📋 Log</button>
  </div>

  <!-- Summary (shown after load) -->
  <div class="cfwaf-se-summary" id="cfwaf-se-summary" style="display:none;"></div>

  <!-- Events table -->
  <div class="cfwaf-se-table-wrap" id="cfwaf-se-table-wrap" style="display:none;">
    <div class="cfwaf-se-table-scroll">
      <table class="cfwaf-se-table">
        <thead>
          <tr>
            <th>Time</th>
            <th>Action</th>
            <th>IP Address</th>
            <th>Method</th>
            <th>Path</th>
            <th>Rule / Source</th>
            <th>User Agent</th>
            <th>Ray ID</th>
          </tr>
        </thead>
        <tbody id="cfwaf-se-tbody">
        </tbody>
      </table>
    </div>
  </div>

  <div id="cfwaf-se-empty" class="cfwaf-se-empty" style="display:none;">
    No events match the current filter.
  </div>

  <?php endif; ?>
</div>

<script>
'use strict';
(function(){
const NONCE    = <?php echo wp_json_encode( $nonce ); ?>;
const AJAX_URL = <?php echo wp_json_encode( $ajax_url ); ?>;

let allEvents   = [];
let activeFilter = '';

function qs(s){ return document.querySelector(s); }
function ajax(action, data, cb){
  const fd = new FormData();
  fd.append('action',action); fd.append('nonce',NONCE);
  Object.entries(data).forEach(([k,v]) => fd.append(k,v));
  fetch(AJAX_URL,{method:'POST',body:fd}).then(r=>r.json()).then(cb)
    .catch(e=>cb({success:false,data:{message:e.message}}));
}

// ── Load zones ─────────────────────────────────────────────────────────────
ajax('wpwaf_list_zones', {}, (res) => {
  const sel = qs('#cfwaf-se-zone');
  if (!res.success){ sel.innerHTML='<option>Error loading zones</option>'; return; }
  (res.data.zones||[]).forEach(z => {
    const o = document.createElement('option');
    o.value = z.id;
    o.textContent = z.name + ' [' + z.plan + ']';
    sel.appendChild(o);
  });
});

qs('#cfwaf-se-zone')?.addEventListener('change', function(){
  qs('#cfwaf-se-load').disabled = !this.value;
  // Reset notices when zone changes
  qs('#cfwaf-se-plan-notice').style.display = 'none';
  qs('#cfwaf-se-error').style.display = 'none';
  qs('#cfwaf-se-count').textContent = '';
  qs('#cfwaf-se-filters').style.display = 'none';
  qs('#cfwaf-se-summary').style.display = 'none';
  qs('#cfwaf-se-table-wrap').style.display = 'none';
  qs('#cfwaf-se-empty').style.display = 'none';
});

// ── Load events ────────────────────────────────────────────────────────────
qs('#cfwaf-se-load')?.addEventListener('click', function(){
  const zoneId = qs('#cfwaf-se-zone').value;
  const limit  = qs('#cfwaf-se-limit').value;
  if (!zoneId) return;

  this.disabled = true;
  this.innerHTML = '<span class="cfwaf-se-spinner"></span> Loading…';
  qs('#cfwaf-se-error').style.display = 'none';
  qs('#cfwaf-se-filters').style.display = 'none';
  qs('#cfwaf-se-summary').style.display = 'none';
  qs('#cfwaf-se-table-wrap').style.display = 'none';
  qs('#cfwaf-se-empty').style.display = 'none';
  qs('#cfwaf-se-count').textContent = '';
  activeFilter = '';

  const self = this;
  const hours = qs('#cfwaf-se-hours').value;
  ajax('wpwaf_security_events', {zone_id:zoneId, limit, hours}, (res) => {
    self.disabled = false;
    self.innerHTML = '<span class="dashicons dashicons-update" style="font-size:14px;width:14px;height:14px;"></span> Load Events';

    if (!res.success){
      const msg = res.data?.message || 'Failed to load events';
      const isPlanErr = res.data?.plan_error
        || /not entitled|upgrade|pro plan|not available|firewallEventsAdaptive/i.test(msg);
      if (isPlanErr){
        qs('#cfwaf-se-plan-notice').style.display = '';
      } else {
        qs('#cfwaf-se-error').textContent = '⚠ ' + msg;
        qs('#cfwaf-se-error').style.display = '';
      }
      return;
    }
    // Hide any previous notices on success
    qs('#cfwaf-se-plan-notice').style.display = 'none';
    qs('#cfwaf-se-error').style.display = 'none';

    allEvents = res.data.events || [];
    qs('#cfwaf-se-count').textContent = allEvents.length + ' event' + (allEvents.length!==1?'s':'') + ' loaded';

    // Reset filter tabs
    document.querySelectorAll('.cfwaf-se-filter-tab').forEach(t => t.classList.toggle('active', t.dataset.action===''));

    qs('#cfwaf-se-filters').style.display = allEvents.length ? '' : 'none';
    renderSummary();
    renderTable('');
  });
});

// ── Filter tabs ─────────────────────────────────────────────────────────────
document.querySelectorAll('.cfwaf-se-filter-tab').forEach(tab => {
  tab.addEventListener('click', function(){
    document.querySelectorAll('.cfwaf-se-filter-tab').forEach(t => t.classList.remove('active'));
    this.classList.add('active');
    activeFilter = this.dataset.action;
    renderTable(activeFilter);
  });
});

// ── Summary ─────────────────────────────────────────────────────────────────
function renderSummary(){
  const counts = {};
  allEvents.forEach(e => { counts[e.action] = (counts[e.action]||0)+1; });
  const total  = allEvents.length;
  const defs   = [
    ['Total','total','',total],
    ['Blocked','block','block',counts['block']||0],
    ['Challenged','challenge','challenge',(counts['challenge']||0)+(counts['managed_challenge']||0)],
    ['JS Challenge','js_challenge','jschallenge',counts['js_challenge']||0],
    ['Allowed','allow','allow',counts['allow']||0],
  ];
  const el = qs('#cfwaf-se-summary');
  el.innerHTML = defs.map(([l,k,cls,n]) =>
    `<div class="cfwaf-se-summary-card ${cls}">
      <div class="cfwaf-se-summary-num">${n}</div>
      <div class="cfwaf-se-summary-label">${l}</div>
    </div>`
  ).join('');
  el.style.display = '';
}

// ── Table ───────────────────────────────────────────────────────────────────
const ACTION_ICONS = {block:'🚫',managed_challenge:'⚠️',js_challenge:'🔒',challenge:'🟡',allow:'✅',log:'📋',skip:'↩'};
const SOURCE_MAP   = {firewallRules:'WAF Rules',firewallCustomRules:'Custom Rules',firewallManagedRules:'Managed Rules',
  firewallRateLimit:'Rate Limit',ipAccessRules:'IP Rules',uaBlock:'UA Block',bic:'Browser Check',
  securityLevel:'Security Level',hot:'Hotlink',rateLimit:'Rate Limit',waf:'WAF'};

function fmtTime(iso){
  if (!iso) return '—';
  const d = new Date(iso);
  return d.toLocaleDateString()+' '+d.toLocaleTimeString(undefined,{hour:'2-digit',minute:'2-digit',second:'2-digit'});
}
function escHtml(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

function renderTable(filterAction){
  const rows  = filterAction ? allEvents.filter(e => e.action === filterAction) : allEvents;
  const tbody = qs('#cfwaf-se-tbody');
  const wrap  = qs('#cfwaf-se-table-wrap');
  const empty = qs('#cfwaf-se-empty');

  if (!rows.length){
    wrap.style.display  = 'none';
    empty.style.display = '';
    return;
  }
  empty.style.display = 'none';
  wrap.style.display  = '';

  const path = e => {
    let p = escHtml(e.clientRequestPath || '/');
    if (e.clientRequestQuery) p += '?' + escHtml(e.clientRequestQuery);
    return p;
  };

  tbody.innerHTML = rows.map(e => {
    const src  = SOURCE_MAP[e.source] || escHtml(e.source || '—');
    const rule = e.ruleId && e.ruleId !== '0' ? `<br><span class="cfwaf-se-ray" style="margin-top:3px;display:block;">${escHtml(e.ruleId.slice(0,16))}…</span>` : '';
    return `<tr>
      <td class="cfwaf-se-time">${fmtTime(e.datetime)}</td>
      <td><span class="cfwaf-se-action ${e.action}">${ACTION_ICONS[e.action]||''} ${escHtml(e.action)}</span></td>
      <td>
        <div class="cfwaf-se-ip">${escHtml(e.clientIP||'—')}</div>
        <div class="cfwaf-se-country">${escHtml(e.clientCountryName||'')} ${e.clientAsn?'AS'+escHtml(String(e.clientAsn)):''}</div>
      </td>
      <td style="font-size:11px;font-weight:600;">${escHtml(e.clientRequestHTTPMethodName||'—')}</td>
      <td><div class="cfwaf-se-path" title="${path(e)}">${path(e)}</div></td>
      <td><span class="cfwaf-se-source">${src}</span>${rule}</td>
      <td><div class="cfwaf-se-ua" title="${escHtml(e.userAgent||'')}">${escHtml(e.userAgent||'—')}</div></td>
      <td class="cfwaf-se-ray">${escHtml(e.rayName||'—')}</td>
    </tr>`;
  }).join('');
}
})();
</script>
