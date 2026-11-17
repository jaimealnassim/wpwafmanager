/* WP WAF Manager: Security Events screen.
 * Values from PHP arrive in window.wpwafPage, printed by WPWAF_Admin::page_script()
 * in includes/views/page-security-events.php. */
'use strict';
(function(){
const NONCE    = wpwafPage.nonce;
const AJAX_URL = wpwafPage.ajax_url;

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
        wpwafSetText(qs('#cfwaf-se-error'), 'alert', msg);
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
const ACTION_ICONS = {block:'ban',managed_challenge:'shield',js_challenge:'lock',challenge:'help',allow:'circle-check',log:'clipboard',skip:'undo'};
const SOURCE_MAP   = {firewallRules:'WAF Rules',firewallCustomRules:'Custom Rules',firewallManagedRules:'Managed Rules',
  firewallRateLimit:'Rate Limit',ipAccessRules:'IP Rules',uaBlock:'UA Block',bic:'Browser Check',
  securityLevel:'Security Level',hot:'Hotlink',rateLimit:'Rate Limit',waf:'WAF'};

function fmtTime(iso){
  if (!iso) return '-';
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
    const src  = SOURCE_MAP[e.source] || escHtml(e.source || '-');
    const rule = e.ruleId && e.ruleId !== '0' ? `<br><span class="cfwaf-se-ray" style="margin-top:3px;display:block;">${escHtml(e.ruleId.slice(0,16))}…</span>` : '';
    return `<tr>
      <td class="cfwaf-se-time">${fmtTime(e.datetime)}</td>
      <td><span class="cfwaf-se-action ${e.action}">${wpwafIcon(ACTION_ICONS[e.action]||'')} ${escHtml(e.action)}</span></td>
      <td>
        <div class="cfwaf-se-ip">${escHtml(e.clientIP||'-')}</div>
        <div class="cfwaf-se-country">${window.wpwafFlag ? window.wpwafFlag(e.clientCountryName) : ''}${escHtml(e.clientCountryName||'')} ${e.clientAsn?'AS'+escHtml(String(e.clientAsn)):''}</div>
      </td>
      <td style="font-size:11px;font-weight:600;">${escHtml(e.clientRequestHTTPMethodName||'-')}</td>
      <td><div class="cfwaf-se-path" title="${path(e)}">${path(e)}</div></td>
      <td><span class="cfwaf-se-source">${src}</span>${rule}</td>
      <td><div class="cfwaf-se-ua" title="${escHtml(e.userAgent||'')}">${escHtml(e.userAgent||'-')}</div></td>
      <td class="cfwaf-se-ray">${escHtml(e.rayName||'-')}</td>
    </tr>`;
  }).join('');
}
})();
