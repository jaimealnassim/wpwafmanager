/* WP WAF Manager: Settings screen.
 * Values from PHP arrive in window.wpwafPage, printed by WPWAF_Admin::page_script()
 * in includes/views/page-settings.php. */
'use strict';
(function(){
const NONCE      = wpwafPage.nonce;
const AJAX_URL   = wpwafPage.ajax_url;
const HAS_CREDS  = wpwafPage.has_creds;
const SAVED_ZONE = wpwafPage.saved_zone;
const SAVED_NAME = wpwafPage.saved_name;

function qs(s){ return document.querySelector(s); }
function ajax(action, data, cb){
  const fd = new FormData();
  fd.append('action', action); fd.append('nonce', NONCE);
  Object.entries(data).forEach(([k,v]) => fd.append(k, v));
  fetch(AJAX_URL, {method:'POST', body:fd}).then(r=>r.json()).then(cb)
    .catch(e => cb({success:false, data:{message:e.message}}));
}

// ── Toggle labels ─────────────────────────────────────────────────────────────
function wireToggle(inputId, labelId, onText, offText, onDeps, offDeps){
  const input = qs('#' + inputId);
  const label = qs('#' + labelId);
  if (!input || !label) return;
  function update(){
    label.textContent = input.checked ? onText : offText;
    onDeps?.forEach(id => {
      const row = qs('#' + id);
      if (row) row.style.cssText = input.checked ? '' : 'opacity:.4;pointer-events:none';
    });
    offDeps?.forEach(id => {
      const row = qs('#' + id);
      if (row) row.style.cssText = !input.checked ? '' : 'opacity:.4;pointer-events:none';
    });
  }
  input.addEventListener('change', update);
  update();
}

wireToggle('st-admin-bar-enabled', 'st-admin-bar-enabled-lbl', 'Enabled', 'Disabled',
  ['st-row-zone','st-row-purge-action'], []);
wireToggle('st-analytics-auto-sync', 'st-analytics-auto-sync-lbl', 'Enabled', 'Disabled',
  ['st-row-interval'], []);
wireToggle('st-keep-data', 'st-keep-data-lbl', 'Keep data', 'Delete data on uninstall');
wireToggle('st-hide-security-events', 'st-hide-security-events-lbl', 'Hidden', 'Visible');
wireToggle('st-dashboard-widget',     'st-dashboard-widget-lbl',     'Visible','Hidden');
wireToggle('st-hide-email-routing',   'st-hide-email-routing-lbl',   'Hidden', 'Visible');

// ── Test Connection ────────────────────────────────────────────────────────
qs('#wpwaf-st-test-conn')?.addEventListener('click', function(){
  const btn = this;
  const res = qs('#wpwaf-st-test-result');
  btn.disabled = true; btn.textContent = 'Testing…'; res.textContent = '';
  ajax('wpwaf_test_connection', {}, (r) => {
    btn.disabled = false; btn.textContent = 'Test Connection';
    if (r.success) {
      res.style.color = '#059669';
      wpwafSetText(res, 'check', 'Connected: ' + r.data.account + ' · ' + r.data.zone_count + ' zone' + (r.data.zone_count !== 1 ? 's' : ''));
    } else {
      res.style.color = '#dc2626';
      wpwafSetText(res, 'x', (r.data?.message || 'Connection failed'));
    }
  });
});

// ── Load zones for dropdown ───────────────────────────────────────────────────
if (HAS_CREDS) {
  ajax('wpwaf_load_zones_for_settings', {}, (res) => {
    const sel = qs('#st-admin-bar-zone');
    if (!sel) return;
    // Remove the loading placeholder
    sel.innerHTML = '<option value="">Open Zone Controls page</option>';
    if (res.success && res.data.zones?.length) {
      res.data.zones.forEach(z => {
        const opt = document.createElement('option');
        opt.value       = z.id;
        opt.textContent = z.name + ' [' + z.plan + ']';
        opt.dataset.name = z.name;
        if (z.id === SAVED_ZONE) opt.selected = true;
        sel.appendChild(opt);
      });
    } else {
      sel.innerHTML += '<option value="" disabled>Could not load zones</option>';
    }
  });
}

// ── Save Access Users ────────────────────────────────────────────────────────
qs('#wpwaf-save-access-users')?.addEventListener('click', function(){
  const btn  = this;
  const msg  = qs('#wpwaf-access-msg');
  const cbs  = document.querySelectorAll('.wpwaf-user-access-cb:checked');
  const ids  = Array.from(cbs).map(cb => parseInt(cb.value, 10));
  btn.disabled = true; btn.textContent = 'Saving…'; msg.textContent = ''; msg.className = 'wpwaf-st-save-msg';
  ajax('wpwaf_save_access_users', { user_ids: JSON.stringify(ids) }, (res) => {
    btn.disabled = false; btn.textContent = 'Save Access Settings';
    if (res.success){
      wpwafSetText(msg, 'check', 'Access settings saved'); msg.className = 'wpwaf-st-save-msg ok';
    } else {
      wpwafSetText(msg, 'x', (res.data?.message || 'Save failed')); msg.className = 'wpwaf-st-save-msg err';
    }
  });
});

// ── Save ──────────────────────────────────────────────────────────────────────
qs('#wpwaf-st-save')?.addEventListener('click', function(){
  const btn   = this;
  const msg   = qs('#wpwaf-st-msg');
  const zoneSel = qs('#st-admin-bar-zone');
  const selectedOpt = zoneSel?.options[zoneSel.selectedIndex];

  const settings = {
    admin_bar_enabled:    qs('#st-admin-bar-enabled')?.checked ? '1' : '0',
    admin_bar_zone_id:    zoneSel?.value || '',
    admin_bar_zone_name:  selectedOpt?.dataset.name || selectedOpt?.textContent?.split(' [')[0] || '',
    admin_bar_purge_all:  qs('#st-admin-bar-purge-all')?.value === '1' ? '1' : '0',
    analytics_auto_sync:  qs('#st-analytics-auto-sync')?.checked ? '1' : '0',
    analytics_interval:   qs('#st-analytics-interval')?.value || '3600',
    analytics_days:       qs('#st-analytics-days')?.value || '7',
    minimum_role: document.querySelector('input[name="st-minimum-role"]:checked')?.value || 'administrator',
    keep_data_on_uninstall: qs('#st-keep-data')?.checked     ? '1' : '0',
    abilities:            qs('#st-abilities')?.value || 'all',
    hide_security_events: qs('#st-hide-security-events')?.checked ? '1' : '0',
    dashboard_widget:     qs('#st-dashboard-widget')?.checked     ? '1' : '0',
    hide_email_routing:   qs('#st-hide-email-routing')?.checked   ? '1' : '0',
  };
  document.querySelectorAll('.wpwaf-st-hide-screen').forEach(cb => { settings[cb.dataset.key] = cb.checked ? '1' : '0'; });

  btn.disabled     = true;
  btn.textContent  = 'Saving…';
  msg.textContent  = '';
  msg.className    = 'wpwaf-st-save-msg';

  ajax('wpwaf_save_plugin_settings', {settings: JSON.stringify(settings)}, (res) => {
    btn.disabled    = false;
    btn.textContent = 'Save Settings';
    if (res.success){
      wpwafSetText(msg, 'check', 'Settings saved');
      msg.className   = 'wpwaf-st-save-msg ok';
      // Reload after short delay so admin bar / menu changes take effect
      setTimeout(() => window.location.reload(), 800);
    } else {
      wpwafSetText(msg, 'x', (res.data?.message || 'Save failed'));
      msg.className   = 'wpwaf-st-save-msg err';
    }
  });
});
})();
