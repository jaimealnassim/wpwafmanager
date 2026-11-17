/* WP WAF Manager: Email Routing screen.
 * Values from PHP arrive in window.wpwafPage, printed by WPWAF_Admin::page_script()
 * in includes/views/page-email-routing.php. */
'use strict';
document.addEventListener('DOMContentLoaded', function(){
const NONCE      = wpwafPage.nonce;
const AJAX_URL   = wpwafPage.ajax_url;
const ACTIVE_ID  = wpwafPage.active_id;
const HAS_MULTI  = wpwafPage.has_multi;

let currentZone     = '';
let pluginAccountId = ACTIVE_ID; // which plugin account is active
let currentZoneName = '';
let routingEnabled  = false;
let accountId       = '';
let allAddresses    = [];

function qs(s){ return document.querySelector(s); }
function escHtml(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
function toast(msg, ok, icon){
  ok = ok !== false;
  var el = document.createElement('div');
  el.className = 'wpwaf-er-toast ' + (ok ? 'ok' : 'err');
  if (window.wpwafToastBody) el.innerHTML = wpwafToastBody(msg, ok, icon); else el.textContent = msg;
  document.body.appendChild(el);
  setTimeout(function(){ el.style.opacity='0'; el.style.transition='opacity .3s'; setTimeout(function(){ el.remove(); }, 300); }, 3500);
}
function ajax(action, data, cb){
  var fd = new FormData();
  fd.append('action', action);
  fd.append('nonce', NONCE);
  // Every request works on the account chosen above, without changing the
  // account the other screens use.
  if (typeof pluginAccountId !== 'undefined' && pluginAccountId && !('plugin_account_id' in data)) fd.append('plugin_account_id', pluginAccountId);
  Object.keys(data).forEach(function(k){ fd.append(k, data[k]); });
  fetch(AJAX_URL, {method:'POST', body:fd})
    .then(function(r){ return r.json(); })
    .then(cb)
    .catch(function(e){ cb({success:false, data:{message:e.message}}); });
}

// ── Tabs ───────────────────────────────────────────────────────────────────────
document.querySelectorAll('.wpwaf-er-tab').forEach(function(tab){
  tab.addEventListener('click', function(){
    document.querySelectorAll('.wpwaf-er-tab').forEach(function(t){ t.classList.remove('active'); });
    document.querySelectorAll('.wpwaf-er-tab-panel').forEach(function(p){ p.classList.remove('active'); });
    this.classList.add('active');
    var panel = qs('#wpwaf-er-panel-' + this.dataset.panel);
    if(panel) panel.classList.add('active');
    if(this.dataset.panel === 'addresses') loadAddresses();
  });
});

// ── Account selector ──────────────────────────────────────────────────────────
var accountSelEl = document.getElementById('wpwaf-er-account-select');
if (accountSelEl) {
  accountSelEl.addEventListener('change', function(){
    pluginAccountId = this.value;
    accountId = ''; // reset CF account ID - will re-fetch
    allAddresses = [];
    currentZone = ''; currentZoneName = '';
    // Reload zones for the chosen account (the active account is not changed).
    var sel = qs('#wpwaf-er-zone');
    if(sel) sel.innerHTML = '<option value="">Select a zone</option>';
    if(qs('#wpwaf-er-status-bar')) qs('#wpwaf-er-status-bar').style.display = 'none';
    if(qs('#wpwaf-er-table-wrap')) qs('#wpwaf-er-table-wrap').style.display = 'none';
    if(qs('#wpwaf-er-add-btn')) qs('#wpwaf-er-add-btn').disabled = true;
    if(qs('#wpwaf-er-sync-btn')) qs('#wpwaf-er-sync-btn').disabled = true;
    var notice = qs('#wpwaf-er-sync-notice'); if(notice) notice.style.display = 'none';
    loadZones();
  });
}

// ── Load zones ─────────────────────────────────────────────────────────────────
function loadZones(){
  ajax('wpwaf_list_zones', {}, function(res){
  var sel = qs('#wpwaf-er-zone');
  if(!sel) return;
  if(!res.success){ sel.innerHTML = '<option>Error loading zones</option>'; return; }
  (res.data.zones || []).forEach(function(z){
    var o = document.createElement('option');
    o.value = z.id;
    o.textContent = z.name + ' [' + z.plan + ']';
    o.dataset.name = z.name;
    sel.appendChild(o);
  });
    ajax('wpwaf_get_account_id', {}, function(r){ if(r.success) accountId = r.data.account_id; });
  });
}
loadZones();

qs('#wpwaf-er-zone').addEventListener('change', function(){
  currentZone = this.value;
  currentZoneName = this.options[this.selectedIndex] ? this.options[this.selectedIndex].dataset.name || '' : '';
  updateDomainSuffixes();
  if(!currentZone){
    qs('#wpwaf-er-status-bar').style.display = 'none';
    qs('#wpwaf-er-table-wrap').style.display = 'none';
    qs('#wpwaf-er-add-btn').disabled = true;
    return;
  }
  qs('#wpwaf-er-add-btn').disabled = false;
  qs('#wpwaf-er-sync-btn').disabled = false;
  var notice = qs('#wpwaf-er-sync-notice');
  if(notice) notice.style.display = 'flex';
  loadZone();
});

function updateDomainSuffixes(){
  var suffix = currentZoneName ? '@' + currentZoneName : '@yourdomain.com';
  [qs('#wpwaf-er-from-suffix'), qs('#wpwaf-er-catchall-domain-suffix')].forEach(function(el){
    if(el) el.textContent = suffix;
  });
}

qs('#wpwaf-er-sync-btn').addEventListener('click', function(){
  if(!currentZone) return;
  this.innerHTML = wpwafIconText('refresh', 'Syncing…');
  this.disabled = true;
  var btn = this;
  // Re-run full load then restore button
  loadZone();
  setTimeout(function(){ btn.innerHTML = wpwafIconText('refresh', 'Sync'); btn.disabled = false; toast('Data refreshed from Cloudflare'); }, 1500);
});

function loadZone(){
  qs('#wpwaf-er-status-bar').style.display = '';
  qs('#wpwaf-er-table-wrap').style.display = '';
  qs('#wpwaf-er-tbody').innerHTML = '<tr><td colspan="4" class="wpwaf-er-loading"><span class="wpwaf-er-spinner"></span></td></tr>';

  ajax('wpwaf_email_routing_get', {zone_id:currentZone}, function(res){
    if(!res.success) return;
    routingEnabled = (res.data.routing || {}).enabled === true;
    updateStatusBar();
  });

  // Load addresses first so catch-all dropdown is populated
  ajax('wpwaf_email_addresses_list', {zone_id:currentZone}, function(res){
    allAddresses = res.success ? (res.data.addresses || []) : [];
    var verified = allAddresses.filter(function(a){ return a.verified; });
    var badge = qs('#wpwaf-er-addr-count-badge');
    if(badge){ badge.textContent = verified.length; badge.style.display = verified.length ? '' : 'none'; }

    // Now load catch-all with addresses ready
    ajax('wpwaf_catch_all_get', {zone_id:currentZone}, function(r){
      if(r.success) renderCatchAll(r.data.rule || {});
    });
  });

  loadRules();
}

// ── Routing enable/disable ─────────────────────────────────────────────────────
function updateStatusBar(){
  var text = qs('#wpwaf-er-status-text');
  var btn  = qs('#wpwaf-er-toggle-btn');
  if(!text || !btn) return;
  if(routingEnabled){
    text.className = 'wpwaf-er-status-on';
    text.innerHTML = '<span class="wpwaf-er-status-dot on"></span> Email Routing enabled';
    btn.textContent = 'Disable';
  } else {
    text.className = 'wpwaf-er-status-off';
    text.innerHTML = '<span class="wpwaf-er-status-dot off"></span> Email Routing disabled';
    btn.textContent = 'Enable';
  }
}

qs('#wpwaf-er-toggle-btn').addEventListener('click', function(){
  this.disabled = true;
  var btn = this;
  ajax('wpwaf_email_routing_toggle', {zone_id:currentZone, enable:routingEnabled?'0':'1'}, function(res){
    btn.disabled = false;
    if(res.success){ routingEnabled = !routingEnabled; updateStatusBar(); toast(routingEnabled ? 'Email Routing enabled' : 'Disabled'); }
    else toast('Failed: ' + (res.data ? res.data.message : 'error'), false);
  });
});

// ── Catch-all rule ─────────────────────────────────────────────────────────────
var loadedCatchAll = {on:false, action:'drop', dest:''};
function renderCatchAll(rule){
  var action  = rule.actions && rule.actions[0] ? rule.actions[0].type : 'drop';
  var dest    = rule.actions && rule.actions[0] && rule.actions[0].value ? rule.actions[0].value[0] : '';
  // "On" means the catch-all is enabled and forwards somewhere. A disabled
  // catch-all, or one that drops mail, shows as Off.
  var enabled = rule.enabled !== false && action === 'forward';
  loadedCatchAll = {on:enabled, action:action, dest:dest};

  var enabledEl = qs('#wpwaf-er-catchall-enabled');
  var statusEl  = qs('#wpwaf-er-catchall-status');
  if(enabledEl) enabledEl.checked = enabled;
  if(statusEl){ statusEl.textContent = enabled ? 'On' : 'Off'; statusEl.style.color = enabled ? '#059669' : 'var(--er-muted)'; }
  populateCatchAllDest(dest);
}

function populateCatchAllDest(currentDest){
  var sel     = qs('#wpwaf-er-catchall-dest');
  if(!sel) return;
  var verified = allAddresses.filter(function(a){ return a.verified; });
  if(!verified.length){
    sel.innerHTML = '<option value="">No verified addresses. See the Destination Addresses tab</option>';
    return;
  }
  sel.innerHTML = '<option value="">Select destination inbox</option>'
    + verified.map(function(a){
        return '<option value="' + escHtml(a.email) + '"' + (a.email === currentDest ? ' selected' : '') + '>' + escHtml(a.email) + '</option>';
      }).join('');
}

qs('#wpwaf-er-catchall-enabled').addEventListener('change', function(){
  var statusEl = qs('#wpwaf-er-catchall-status');
  if(statusEl){ statusEl.textContent = this.checked ? 'On' : 'Off'; statusEl.style.color = this.checked ? '#059669' : 'var(--er-muted)'; }
});

qs('#wpwaf-er-catchall-save').addEventListener('click', function(){
  var local      = qs('#wpwaf-er-catchall-local') ? qs('#wpwaf-er-catchall-local').value.trim().replace(/@.*/, '') : '';
  var dest       = qs('#wpwaf-er-catchall-dest') ? qs('#wpwaf-er-catchall-dest').value.trim() : '';
  var catchAllOn = qs('#wpwaf-er-catchall-enabled') ? qs('#wpwaf-er-catchall-enabled').checked : false;
  var msgEl      = qs('#wpwaf-er-catchall-msg');
  if(!dest && (catchAllOn || local)){ wpwafSetText(msgEl, 'alert', 'Select a verified destination inbox'); msgEl.style.color = '#dc2626'; return; }
  msgEl.textContent = '';
  this.disabled = true;
  this.textContent = 'Saving…';
  var btn    = this;
  var done   = 0;
  var errors = [];
  // Only touch the catch-all when it should forward, or when it forwarded and is now turned off.
  // That leaves a catch-all set up in Cloudflare (a Worker, say) alone.
  var updateCatchAll = catchAllOn || loadedCatchAll.on;
  var total  = (updateCatchAll ? 1 : 0) + ((local && currentZoneName) ? 1 : 0);
  if(!total){ wpwafSetText(msgEl, 'alert', 'Enter an address on your domain, or turn on Catch-All'); msgEl.style.color = '#dc2626'; btn.disabled = false; btn.textContent = 'Save'; return; }

  function finish(){
    done++;
    if(done < total) return;
    btn.disabled = false;
    btn.textContent = 'Save';
    if(errors.length){
      wpwafSetText(msgEl, 'alert', errors.join('; '));
      msgEl.style.color = '#dc2626';
    } else {
      wpwafSetText(msgEl, 'check', 'Saved');
      msgEl.style.color = '#059669';
      setTimeout(function(){ msgEl.textContent = ''; }, 3000);
      loadRules();
      toast('Email routing saved');
    }
  }

  // 1. Update catch-all rule: forward when on, disabled when off (Cloudflare then
  //    rejects mail to addresses without a rule, so senders get a bounce).
  if(updateCatchAll){
    ajax('wpwaf_catch_all_update', {
      zone_id:     currentZone,
      action_type: catchAllOn ? 'forward' : 'drop',
      destination: catchAllOn ? dest : '',
      enabled:     catchAllOn ? '1' : '0'
    }, function(res){
      if(!res.success) errors.push('Catch-all: ' + (res.data ? res.data.message : 'failed'));
      else loadedCatchAll = {on:catchAllOn, action:catchAllOn ? 'forward' : 'drop', dest:catchAllOn ? dest : ''};
      finish();
    });
  }

  // 2. Create specific rule if local part entered
  if(local && currentZoneName){
    var from = local + '@' + currentZoneName;
    ajax('wpwaf_email_rule_create', {zone_id:currentZone, from:from, to:dest, name:'Forward '+from, action_type:'forward'}, function(res){
      var msg = res.data ? res.data.message || '' : '';
      if(!res.success && msg.indexOf('already exists') === -1) errors.push('Rule: ' + msg);
      finish();
    });
  }
});

// ── Specific forwarding rules ──────────────────────────────────────────────────
function loadRules(){
  ajax('wpwaf_email_rules_list', {zone_id:currentZone}, function(res){
    var tbody = qs('#wpwaf-er-tbody');
    if(!res.success){ tbody.innerHTML = '<tr><td colspan="4" class="wpwaf-er-empty">Could not load rules.</td></tr>'; return; }
    // Filter out system catch-all rules (type:'all' or no matcher value) - managed in the card above
    var rules = (res.data.rules || []).filter(function(r){
      if(!r.matchers || !r.matchers.length) return false;
      var m = r.matchers[0];
      if(m.type === 'all') return false;  // system catch-all
      if(!m.value) return false;           // no address set
      return true;
    });
    if(!rules.length){
      tbody.innerHTML = '<tr><td colspan="4" class="wpwaf-er-empty">No specific rules yet. Use the form above to add one.</td></tr>';
      return;
    }
    tbody.innerHTML = rules.map(function(r){
      var ruleId  = r.id || r.tag || '';
      var from    = r.matchers && r.matchers[0] ? r.matchers[0].value : '(no address)';
      var action  = r.actions && r.actions[0] ? r.actions[0].type : 'forward';
      var toArr   = r.actions && r.actions[0] ? r.actions[0].value : null;
      var to      = action === 'forward' && toArr && toArr.length ? toArr[0] : '(' + action + ')';
      var enabled = r.enabled !== false;
      return '<tr>'
        + '<td style="font-family:monospace;font-size:12px;">' + escHtml(from) + '</td>'
        + '<td style="font-family:monospace;font-size:12px;">' + escHtml(to) + '</td>'
        + '<td><label style="display:flex;align-items:center;gap:6px;cursor:pointer;">'
        + '<input type="checkbox" class="wpwaf-er-rule-toggle" data-id="' + escHtml(ruleId) + '" '
        + (enabled ? 'checked' : '') + ' style="accent-color:var(--er-orange);width:15px;height:15px;">'
        + '<span class="wpwaf-er-badge ' + (enabled ? 'on' : 'off') + '">' + (enabled ? 'Active' : 'Paused') + '</span>'
        + '</label></td>'
        + '<td>'
        + (ruleId ? '<button class="wpwaf-er-btn wpwaf-er-btn-danger wpwaf-er-btn-sm wpwaf-er-del-btn" data-id="' + escHtml(ruleId) + '" data-from="' + escHtml(from) + '">Delete</button>' : '<span style="font-size:11px;color:var(--er-muted);">Delete in CF dashboard</span>')
        + '</td>'
        + '</tr>';
    }).join('');

    tbody.querySelectorAll('.wpwaf-er-rule-toggle').forEach(function(cb){
      cb.addEventListener('change', function(){
        var ruleId = this.dataset.id;
        var rule   = rules.filter(function(r){ return r.id === ruleId; })[0];
        if(!rule) return;
        this.disabled = true;
        var updated = JSON.parse(JSON.stringify(rule));
        updated.enabled = this.checked;
        var tog = this;
        ajax('wpwaf_email_rule_update', {zone_id:currentZone, rule_id:ruleId, enabled:this.checked?'1':'0', rule:JSON.stringify(updated)}, function(res){
          tog.disabled = false;
          if(res.success) loadRules();
          else{ tog.checked = !tog.checked; toast('Failed: ' + (res.data ? res.data.message : 'error'), false); }
        });
      });
    });

    tbody.querySelectorAll('.wpwaf-er-del-btn').forEach(function(btn){
      btn.addEventListener('click', function(){
        var from   = this.dataset.from || 'this rule';
        var ruleId = this.dataset.id;
        if(!ruleId){ toast('Cannot delete: no rule ID found. Delete it directly in the Cloudflare dashboard.', false); return; }
        if(!confirm('Delete rule for "' + from + '"?')) return;
        this.disabled = true;
        this.textContent = '…';
        var delBtn = this;
        ajax('wpwaf_email_rule_delete', {zone_id:currentZone, rule_id:ruleId}, function(res){
          if(res.success){ loadRules(); toast('Rule deleted'); }
          else {
            delBtn.disabled = false; delBtn.textContent = 'Delete';
            toast('Delete failed: ' + (res.data ? res.data.message : 'error') + '. Try deleting in the Cloudflare dashboard.', false);
          }
        });
      });
    });
  });
}

// ── Add Rule Modal ─────────────────────────────────────────────────────────────
qs('#wpwaf-er-add-btn').addEventListener('click', openModal);
qs('#wpwaf-er-modal-close').addEventListener('click', closeModal);
qs('#wpwaf-er-modal-cancel').addEventListener('click', closeModal);
qs('#wpwaf-er-modal').addEventListener('click', function(e){ if(e.target === qs('#wpwaf-er-modal')) closeModal(); });
document.addEventListener('keydown', function(e){ if(e.key === 'Escape') closeModal(); });

qs('#wpwaf-er-from').addEventListener('input', function(){
  if(this.value.indexOf('@') !== -1) this.value = this.value.split('@')[0];
});

function openModal(){
  qs('#wpwaf-er-from').value = '';
  qs('#wpwaf-er-name').value = '';
  qs('#wpwaf-er-modal-error').style.display = 'none';
  qs('#wpwaf-er-modal-save').textContent = 'Add Rule';
  updateDomainSuffixes();
  // Populate dropdown
  var sel = qs('#wpwaf-er-to-select');
  var verified = allAddresses.filter(function(a){ return a.verified; });
  if(verified.length){
    sel.innerHTML = '<option value="">Select verified destination</option>'
      + verified.map(function(a){ return '<option value="' + escHtml(a.email) + '">' + escHtml(a.email) + '</option>'; }).join('');
  } else {
    sel.innerHTML = '<option value="">No verified addresses. See the Destination Addresses tab</option>';
    setTimeout(function(){
      closeModal();
      document.querySelectorAll('.wpwaf-er-tab').forEach(function(t){ t.classList.remove('active'); });
      document.querySelectorAll('.wpwaf-er-tab-panel').forEach(function(p){ p.classList.remove('active'); });
      var addrTab = qs('#wpwaf-er-tab-addresses');
      var addrPanel = qs('#wpwaf-er-panel-addresses');
      if(addrTab) addrTab.classList.add('active');
      if(addrPanel) addrPanel.classList.add('active');
      loadAddresses();
      toast('First add a destination address, then create rules', true, 'info');
    }, 300);
  }
  qs('#wpwaf-er-modal').style.display = 'flex';
  qs('#wpwaf-er-from').focus();
}
function closeModal(){ qs('#wpwaf-er-modal').style.display = 'none'; }

qs('#wpwaf-er-modal-save').addEventListener('click', function(){
  var fromLocal = qs('#wpwaf-er-from').value.trim().replace(/@.*/, '');
  var to        = qs('#wpwaf-er-to-select').value.trim();
  var name      = qs('#wpwaf-er-name').value.trim();
  var errEl     = qs('#wpwaf-er-modal-error');
  if(!fromLocal){ errEl.textContent = 'Enter the address prefix (e.g. hello)'; errEl.style.display = ''; return; }
  if(!to){ errEl.textContent = 'Select a verified destination address.'; errEl.style.display = ''; return; }
  errEl.style.display = 'none';
  this.disabled = true;
  this.textContent = 'Adding…';
  var btn  = this;
  var from = fromLocal + (currentZoneName ? '@' + currentZoneName : '');
  ajax('wpwaf_email_rule_create', {zone_id:currentZone, from:from, to:to, name:name, action_type:'forward'}, function(res){
    btn.disabled = false;
    btn.textContent = 'Add Rule';
    if(!res.success){ wpwafSetText(errEl, 'alert', (res.data ? res.data.message : 'Failed')); errEl.style.display = ''; return; }
    closeModal(); loadRules(); toast('Forwarding rule created');
  });
});

// ── Destination Addresses Tab ──────────────────────────────────────────────────
function loadAddresses(){
  var list = qs('#wpwaf-er-addr-list');
  list.innerHTML = '<div class="wpwaf-er-loading"><span class="wpwaf-er-spinner"></span> Loading…</div>';
  ajax('wpwaf_email_addresses_list', {zone_id:currentZone}, function(res){
    if(!res.success){ list.innerHTML = '<div class="wpwaf-er-empty">Could not load. Check API token has Account → Email Routing Addresses → Edit permission.</div>'; return; }
    allAddresses = res.data.addresses || [];
    var badge  = qs('#wpwaf-er-addr-count-badge');
    var vCount = allAddresses.filter(function(a){ return a.verified; }).length;
    if(badge){ badge.textContent = vCount; badge.style.display = vCount ? '' : 'none'; }
    if(currentZone) populateCatchAllDest('');
    if(!allAddresses.length){ list.innerHTML = '<div class="wpwaf-er-empty">No destination addresses yet. Add one below.</div>'; return; }
    list.innerHTML = allAddresses.map(function(a){
      return '<div class="wpwaf-er-addr-row">'
        + '<span class="wpwaf-er-addr-email">' + escHtml(a.email) + '</span>'
        + '<span class="wpwaf-er-addr-badge ' + (a.verified ? 'verified' : 'pending') + '">'
        + (a.verified ? wpwafIconText('check', 'Verified') : wpwafIconText('hourglass', 'Pending, check your inbox')) + '</span>'
        + '</div>';
    }).join('');
  });
}

qs('#wpwaf-er-refresh-addrs').addEventListener('click', loadAddresses);

qs('#wpwaf-er-add-addr-btn').addEventListener('click', function(){
  var email = qs('#wpwaf-er-new-addr').value.trim();
  var msgEl = qs('#wpwaf-er-addr-msg');
  if(!email){ wpwafSetText(msgEl, 'alert', 'Enter a valid email address.'); msgEl.style.color = '#b91c1c'; msgEl.style.display = ''; return; }
  this.disabled = true;
  this.textContent = 'Sending…';
  var btn  = this;
  var data = {email:email};
  if(accountId) data.account_id = accountId;
  ajax('wpwaf_email_address_create', data, function(res){
    btn.disabled = false;
    btn.textContent = 'Add & Send Verification';
    if(res.success){
      qs('#wpwaf-er-new-addr').value = '';
      wpwafSetText(msgEl, 'check', 'Verification email sent to ' + email + '. Click the link, then refresh.');
      msgEl.style.color = '#059669'; msgEl.style.display = '';
      loadAddresses();
    } else {
      wpwafSetText(msgEl, 'alert', (res.data ? res.data.message : 'Failed'));
      msgEl.style.color = '#b91c1c'; msgEl.style.display = '';
    }
  });
});

});
