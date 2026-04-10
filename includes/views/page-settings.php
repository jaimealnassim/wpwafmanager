<?php
defined( 'ABSPATH' ) || exit;
$nonce    = wp_create_nonce( 'wpwaf_nonce' );
$ajax_url = admin_url( 'admin-ajax.php' );
$s        = $settings; // passed from render_settings_page()
?>
<style>
:root{--st-orange:#FF6A00;--st-border:#e2e6ea;--st-bg:#f8f9fb;--st-dark:#1a1a2e;--st-muted:#6b7280;}
.wpwaf-st-wrap{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;max-width:780px;padding:24px 20px;color:var(--st-dark);}
.wpwaf-st-header{margin-bottom:24px;}
.wpwaf-st-header h1{font-size:22px;font-weight:700;margin:0 0 4px;display:flex;align-items:center;gap:8px;}
.wpwaf-st-header h1 .dashicons{color:var(--st-orange);font-size:24px;width:24px;height:24px;}
.wpwaf-st-header-sub{font-size:12px;color:var(--st-muted);}

/* Section cards */
.wpwaf-st-section{background:#fff;border:1px solid var(--st-border);border-radius:10px;margin-bottom:20px;overflow:hidden;}
.wpwaf-st-section-header{padding:14px 20px;border-bottom:1px solid var(--st-border);display:flex;align-items:center;gap:10px;}
.wpwaf-st-section-header h2{margin:0;font-size:14px;font-weight:700;color:var(--st-dark);}
.wpwaf-st-section-icon{font-size:18px;line-height:1;}
.wpwaf-st-section-body{padding:0;}
.wpwaf-st-row{display:grid;grid-template-columns:240px 1fr;align-items:start;gap:16px;padding:16px 20px;border-bottom:1px solid #f1f3f5;}
.wpwaf-st-row:last-child{border-bottom:none;}
.wpwaf-st-label{font-size:13px;font-weight:600;color:var(--st-dark);padding-top:2px;}
.wpwaf-st-hint{font-size:11px;color:var(--st-muted);margin-top:3px;line-height:1.5;}
.wpwaf-st-control{display:flex;flex-direction:column;gap:6px;}

/* Form elements */
.wpwaf-st-select,.wpwaf-st-input{padding:7px 10px;border:1px solid var(--st-border);border-radius:6px;font-size:13px;color:var(--st-dark);background:#fff;width:100%;box-sizing:border-box;max-width:360px;}
.wpwaf-st-select:focus,.wpwaf-st-input:focus{outline:none;border-color:var(--st-orange);box-shadow:0 0 0 2px rgba(255,106,0,.1);}
.wpwaf-st-select:disabled,.wpwaf-st-input:disabled{opacity:.5;background:#f9fafb;cursor:not-allowed;}

/* Toggle switch */
.wpwaf-st-toggle-row{display:flex;align-items:center;gap:10px;}
.wpwaf-st-switch{position:relative;display:inline-block;width:44px;height:24px;flex-shrink:0;}
.wpwaf-st-switch input{opacity:0;width:0;height:0;}
.wpwaf-st-slider{position:absolute;cursor:pointer;inset:0;background:#d1d5db;border-radius:24px;transition:.2s;}
.wpwaf-st-slider:before{position:absolute;content:"";height:18px;width:18px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.2s;box-shadow:0 1px 3px rgba(0,0,0,.2);}
.wpwaf-st-switch input:checked+.wpwaf-st-slider{background:var(--st-orange);}
.wpwaf-st-switch input:checked+.wpwaf-st-slider:before{transform:translateX(20px);}
.wpwaf-st-switch-label{font-size:13px;color:var(--st-muted);}
.wpwaf-st-switch input:checked~.wpwaf-st-switch-label{color:var(--st-dark);font-weight:600;}

/* Zone loader */
.wpwaf-st-zone-loading{font-size:12px;color:var(--st-muted);display:flex;align-items:center;gap:6px;}
.wpwaf-st-spinner{display:inline-block;width:12px;height:12px;border:2px solid rgba(255,106,0,.3);border-top-color:var(--st-orange);border-radius:50%;animation:st-spin .6s linear infinite;}
@keyframes st-spin{to{transform:rotate(360deg);}}

/* Save bar */
.wpwaf-st-save-bar{background:#fff;border:1px solid var(--st-border);border-radius:10px;padding:16px 20px;display:flex;align-items:center;gap:12px;position:sticky;bottom:20px;box-shadow:0 4px 16px rgba(0,0,0,.08);}
.wpwaf-st-btn-save{background:var(--st-orange);color:#fff;border:none;padding:10px 24px;border-radius:7px;font-size:14px;font-weight:700;cursor:pointer;transition:background .15s;}
.wpwaf-st-btn-save:hover{background:#d95500;}
.wpwaf-st-btn-save:disabled{opacity:.6;cursor:not-allowed;}
.wpwaf-st-save-msg{font-size:13px;font-weight:600;}
.wpwaf-st-save-msg.ok{color:#059669;}
.wpwaf-st-save-msg.err{color:#dc2626;}

/* Dependency dim */
.wpwaf-st-row.dimmed .wpwaf-st-label,.wpwaf-st-row.dimmed .wpwaf-st-hint{opacity:.4;}
.wpwaf-st-row.dimmed .wpwaf-st-control{opacity:.4;pointer-events:none;}
/* Role selector */
.wpwaf-st-role-grid{display:flex;flex-direction:column;gap:6px;width:100%;}
.wpwaf-st-role-option{display:flex;align-items:flex-start;gap:10px;padding:10px 12px;border:2px solid var(--st-border);border-radius:8px;cursor:pointer;transition:all .12s;}
.wpwaf-st-role-option:has(input:checked){border-color:var(--st-orange);background:#fff8f5;}
.wpwaf-st-role-option input{margin-top:3px;accent-color:var(--st-orange);flex-shrink:0;width:16px;height:16px;}
.wpwaf-st-role-icon{font-size:18px;line-height:1;flex-shrink:0;margin-top:1px;}
.wpwaf-st-role-info{display:flex;flex-direction:column;gap:2px;}
.wpwaf-st-role-info strong{font-size:13px;color:var(--st-dark);}
.wpwaf-st-role-info span{font-size:11px;color:var(--st-muted);}
.wpwaf-st-role-warn{color:#d97706 !important;font-size:11px;}
.wpwaf-st-role-option.warn:has(input:checked){border-color:#d97706;background:#fffbeb;}


@media(max-width:660px){
  .wpwaf-st-row{grid-template-columns:1fr;}
  .wpwaf-st-select,.wpwaf-st-input{max-width:100%;}
}
</style>

<div class="wpwaf-st-wrap">
  <div class="wpwaf-st-header">
    <h1><span class="dashicons dashicons-admin-settings"></span> Settings</h1>
    <div class="wpwaf-st-header-sub">Global plugin preferences — applies across all pages</div>
  </div>

  <!-- ── Admin Bar ──────────────────────────────────────────────────────────── -->
  <div class="wpwaf-st-section">
    <div class="wpwaf-st-section-header">
      <span class="wpwaf-st-section-icon">🔧</span>
      <h2>Admin Bar Quick Purge</h2>
    </div>
    <div class="wpwaf-st-section-body">

      <div class="wpwaf-st-row">
        <div>
          <div class="wpwaf-st-label">Enable admin bar button</div>
          <div class="wpwaf-st-hint">Shows a cache purge shortcut in the WP admin bar</div>
        </div>
        <div class="wpwaf-st-control">
          <div class="wpwaf-st-toggle-row">
            <label class="wpwaf-st-switch">
              <input type="checkbox" id="st-admin-bar-enabled" <?php checked( $s['admin_bar_enabled'] ); ?>>
              <span class="wpwaf-st-slider"></span>
            </label>
            <span class="wpwaf-st-switch-label" id="st-admin-bar-enabled-lbl">
              <?php echo $s['admin_bar_enabled'] ? 'Enabled' : 'Disabled'; ?>
            </span>
          </div>
        </div>
      </div>

      <div class="wpwaf-st-row" id="st-row-zone" <?php echo ! $s['admin_bar_enabled'] ? 'style="opacity:.4;pointer-events:none"' : ''; ?>>
        <div>
          <div class="wpwaf-st-label">Zone to purge</div>
          <div class="wpwaf-st-hint">Which Cloudflare zone to purge when clicked. Leave blank to open Zone Controls instead.</div>
        </div>
        <div class="wpwaf-st-control">
          <?php if ( ! $has_creds ) : ?>
          <div class="wpwaf-st-zone-loading">No account connected — add credentials first</div>
          <?php else : ?>
          <select id="st-admin-bar-zone" class="wpwaf-st-select">
            <option value="">— Open Zone Controls page —</option>
            <option value="__loading__" disabled>Loading zones…</option>
          </select>
          <?php endif; ?>
        </div>
      </div>

      <div class="wpwaf-st-row" id="st-row-purge-action" <?php echo ! $s['admin_bar_enabled'] ? 'style="opacity:.4;pointer-events:none"' : ''; ?>>
        <div>
          <div class="wpwaf-st-label">Click action</div>
          <div class="wpwaf-st-hint">What happens when you click the admin bar button</div>
        </div>
        <div class="wpwaf-st-control">
          <select id="st-admin-bar-purge-all" class="wpwaf-st-select">
            <option value="1" <?php selected( $s['admin_bar_purge_all'], true ); ?>>Purge everything instantly (AJAX)</option>
            <option value="0" <?php selected( $s['admin_bar_purge_all'], false ); ?>>Open Zone Controls page</option>
          </select>
        </div>
      </div>

    </div>
  </div>

  <!-- ── Zone Analytics ────────────────────────────────────────────────────── -->
  <div class="wpwaf-st-section">
    <div class="wpwaf-st-section-header">
      <span class="wpwaf-st-section-icon">📊</span>
      <h2>Zone Analytics</h2>
    </div>
    <div class="wpwaf-st-section-body">

      <div class="wpwaf-st-row">
        <div>
          <div class="wpwaf-st-label">Auto-sync</div>
          <div class="wpwaf-st-hint">Sync analytics data automatically via WP-Cron</div>
        </div>
        <div class="wpwaf-st-control">
          <div class="wpwaf-st-toggle-row">
            <label class="wpwaf-st-switch">
              <input type="checkbox" id="st-analytics-auto-sync" <?php checked( $s['analytics_auto_sync'] ); ?>>
              <span class="wpwaf-st-slider"></span>
            </label>
            <span class="wpwaf-st-switch-label" id="st-analytics-auto-sync-lbl">
              <?php echo $s['analytics_auto_sync'] ? 'Enabled' : 'Disabled'; ?>
            </span>
          </div>
        </div>
      </div>

      <div class="wpwaf-st-row" id="st-row-interval">
        <div>
          <div class="wpwaf-st-label">Sync interval</div>
          <div class="wpwaf-st-hint">How often to fetch fresh data from Cloudflare</div>
        </div>
        <div class="wpwaf-st-control">
          <select id="st-analytics-interval" class="wpwaf-st-select">
            <option value="300"   <?php selected( $s['analytics_interval'], 300 );   ?>>Every 5 minutes</option>
            <option value="900"   <?php selected( $s['analytics_interval'], 900 );   ?>>Every 15 minutes</option>
            <option value="1800"  <?php selected( $s['analytics_interval'], 1800 );  ?>>Every 30 minutes</option>
            <option value="3600"  <?php selected( $s['analytics_interval'], 3600 );  ?>>Every hour</option>
            <option value="21600" <?php selected( $s['analytics_interval'], 21600 ); ?>>Every 6 hours</option>
            <option value="86400" <?php selected( $s['analytics_interval'], 86400 ); ?>>Every 24 hours</option>
          </select>
        </div>
      </div>

      <div class="wpwaf-st-row">
        <div>
          <div class="wpwaf-st-label">Default time range</div>
          <div class="wpwaf-st-hint">Days of analytics data to show on Zone Status page</div>
        </div>
        <div class="wpwaf-st-control">
          <select id="st-analytics-days" class="wpwaf-st-select">
            <option value="1"  <?php selected( $s['analytics_days'], 1 );  ?>>Last 24 hours</option>
            <option value="7"  <?php selected( $s['analytics_days'], 7 );  ?>>Last 7 days</option>
            <option value="14" <?php selected( $s['analytics_days'], 14 ); ?>>Last 14 days</option>
            <option value="30" <?php selected( $s['analytics_days'], 30 ); ?>>Last 30 days</option>
          </select>
        </div>
      </div>

    </div>
  </div>

  <!-- ── Access Control ────────────────────────────────────────────────────── -->
  <div class="wpwaf-st-section">
    <div class="wpwaf-st-section-header">
      <span class="wpwaf-st-section-icon">🔒</span>
      <h2>Access Control</h2>
    </div>
    <div class="wpwaf-st-section-body">

      <?php
      $current_role = $s['minimum_role'] ?? 'administrator';
      $roles = [
        'administrator' => [ 'label' => 'Administrator',  'desc' => 'Full site admin access',            'icon' => '🛡', 'warn' => false ],
        'editor'        => [ 'label' => 'Editor',         'desc' => 'Can manage all posts and pages',    'icon' => '✏️', 'warn' => true  ],
        'author'        => [ 'label' => 'Author',         'desc' => 'Can publish their own posts',       'icon' => '📝', 'warn' => true  ],
        'contributor'   => [ 'label' => 'Contributor',    'desc' => 'Can write but not publish',         'icon' => '👤', 'warn' => true  ],
        'subscriber'    => [ 'label' => 'Subscriber',     'desc' => 'Registered users only',             'icon' => '👁', 'warn' => true  ],
      ];
      ?>
      <div class="wpwaf-st-row">
        <div>
          <div class="wpwaf-st-label">Minimum role</div>
          <div class="wpwaf-st-hint">All users with this role <em>or higher</em> can access the plugin. Default is Administrator. Granting access below Administrator is not recommended for a security plugin.</div>
        </div>
        <div class="wpwaf-st-control">
          <div class="wpwaf-st-role-grid">
            <?php foreach ( $roles as $role_key => $role ) : ?>
            <label class="wpwaf-st-role-option <?php echo $role['warn'] ? 'warn' : ''; ?>">
              <input type="radio" name="st-minimum-role" value="<?php echo esc_attr( $role_key ); ?>"
                     id="st-role-<?php echo esc_attr( $role_key ); ?>"
                     <?php checked( $current_role, $role_key ); ?>>
              <span class="wpwaf-st-role-icon"><?php echo $role['icon']; ?></span>
              <span class="wpwaf-st-role-info">
                <strong><?php echo esc_html( $role['label'] ); ?></strong>
                <span><?php echo esc_html( $role['desc'] ); ?></span>
                <?php if ( $role['warn'] ) : ?>
                <span class="wpwaf-st-role-warn">⚠ Not recommended for security plugins</span>
                <?php endif; ?>
              </span>
            </label>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <div class="wpwaf-st-row">
        <div>
          <div class="wpwaf-st-label">Keep data on uninstall</div>
          <div class="wpwaf-st-hint">When <strong>on</strong>: all plugin data (accounts, settings, cache) is kept if you delete the plugin. When <strong>off</strong>: everything is permanently deleted on uninstall.</div>
        </div>
        <div class="wpwaf-st-control">
          <div class="wpwaf-st-toggle-row">
            <label class="wpwaf-st-switch">
              <input type="checkbox" id="st-keep-data" <?php checked( $s['keep_data_on_uninstall'] ); ?>>
              <span class="wpwaf-st-slider"></span>
            </label>
            <span class="wpwaf-st-switch-label" id="st-keep-data-lbl">
              <?php echo $s['keep_data_on_uninstall'] ? 'Keep data' : 'Delete data on uninstall'; ?>
            </span>
          </div>
        </div>
      </div>

      <div class="wpwaf-st-row">
        <div>
          <div class="wpwaf-st-label">Test connection</div>
          <div class="wpwaf-st-hint">Verify the active API credentials are still working</div>
        </div>
        <div class="wpwaf-st-control">
          <?php if ( $has_creds ) : ?>
          <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
            <button id="wpwaf-st-test-conn" class="wpwaf-st-btn-save" style="background:#1a1a2e;padding:8px 18px;font-size:13px;">Test Connection</button>
            <span id="wpwaf-st-test-result" style="font-size:13px;font-weight:600;"></span>
          </div>
          <?php else : ?>
          <span style="font-size:12px;color:var(--st-muted);">No account connected</span>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </div>

  <!-- ── Menu Display ──────────────────────────────────────────────────────── -->
  <div class="wpwaf-st-section">
    <div class="wpwaf-st-section-header">
      <span class="wpwaf-st-section-icon">📋</span>
      <h2>Menu Display</h2>
    </div>
    <div class="wpwaf-st-section-body">

      <div class="wpwaf-st-row">
        <div>
          <div class="wpwaf-st-label">Dashboard widget</div>
          <div class="wpwaf-st-hint">Show a WP WAF Manager summary card on the WordPress dashboard</div>
        </div>
        <div class="wpwaf-st-control">
          <div class="wpwaf-st-toggle-row">
            <label class="wpwaf-st-switch">
              <input type="checkbox" id="st-dashboard-widget" <?php checked( $s['dashboard_widget'] ); ?>>
              <span class="wpwaf-st-slider"></span>
            </label>
            <span class="wpwaf-st-switch-label" id="st-dashboard-widget-lbl">
              <?php echo $s['dashboard_widget'] ? 'Visible' : 'Hidden'; ?>
            </span>
          </div>
        </div>
      </div>

      <div class="wpwaf-st-row">
        <div>
          <div class="wpwaf-st-label">Hide Security Events</div>
          <div class="wpwaf-st-hint">Requires Cloudflare Pro plan. Hide if all your zones are on the Free plan.</div>
        </div>
        <div class="wpwaf-st-control">
          <div class="wpwaf-st-toggle-row">
            <label class="wpwaf-st-switch">
              <input type="checkbox" id="st-hide-security-events" <?php checked( $s['hide_security_events'] ); ?>>
              <span class="wpwaf-st-slider"></span>
            </label>
            <span class="wpwaf-st-switch-label" id="st-hide-security-events-lbl">
              <?php echo $s['hide_security_events'] ? 'Hidden' : 'Visible'; ?>
            </span>
          </div>
        </div>
      </div>

      <div class="wpwaf-st-row">
        <div>
          <div class="wpwaf-st-label">Hide Email Routing</div>
          <div class="wpwaf-st-hint">Hide the Email Routing page if you don't use Cloudflare email forwarding.</div>
        </div>
        <div class="wpwaf-st-control">
          <div class="wpwaf-st-toggle-row">
            <label class="wpwaf-st-switch">
              <input type="checkbox" id="st-hide-email-routing" <?php checked( $s['hide_email_routing'] ); ?>>
              <span class="wpwaf-st-slider"></span>
            </label>
            <span class="wpwaf-st-switch-label" id="st-hide-email-routing-lbl">
              <?php echo $s['hide_email_routing'] ? 'Hidden' : 'Visible'; ?>
            </span>
          </div>
        </div>
      </div>

    </div>
  </div>

  <!-- ── Save bar ────────────────────────────────────────────────────────────── -->
  <div class="wpwaf-st-save-bar">
    <button id="wpwaf-st-save" class="wpwaf-st-btn-save">Save Settings</button>
    <span id="wpwaf-st-msg" class="wpwaf-st-save-msg"></span>
  </div>

</div>

<script>
'use strict';
(function(){
const NONCE      = <?php echo wp_json_encode( $nonce ); ?>;
const AJAX_URL   = <?php echo wp_json_encode( $ajax_url ); ?>;
const HAS_CREDS  = <?php echo $has_creds ? 'true' : 'false'; ?>;
const SAVED_ZONE = <?php echo wp_json_encode( $s['admin_bar_zone_id'] ); ?>;
const SAVED_NAME = <?php echo wp_json_encode( $s['admin_bar_zone_name'] ); ?>;

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
      res.textContent = '✓ Connected — ' + r.data.account + ' · ' + r.data.zone_count + ' zone' + (r.data.zone_count !== 1 ? 's' : '');
    } else {
      res.style.color = '#dc2626';
      res.textContent = '✗ ' + (r.data?.message || 'Connection failed');
    }
  });
});

// ── Load zones for dropdown ───────────────────────────────────────────────────
if (HAS_CREDS) {
  ajax('wpwaf_load_zones_for_settings', {}, (res) => {
    const sel = qs('#st-admin-bar-zone');
    if (!sel) return;
    // Remove the loading placeholder
    sel.innerHTML = '<option value="">— Open Zone Controls page —</option>';
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
    hide_security_events: qs('#st-hide-security-events')?.checked ? '1' : '0',
    dashboard_widget:     qs('#st-dashboard-widget')?.checked     ? '1' : '0',
    hide_email_routing:   qs('#st-hide-email-routing')?.checked   ? '1' : '0',
  };

  btn.disabled     = true;
  btn.textContent  = 'Saving…';
  msg.textContent  = '';
  msg.className    = 'wpwaf-st-save-msg';

  ajax('wpwaf_save_plugin_settings', {settings: JSON.stringify(settings)}, (res) => {
    btn.disabled    = false;
    btn.textContent = 'Save Settings';
    if (res.success){
      msg.textContent = '✓ Settings saved';
      msg.className   = 'wpwaf-st-save-msg ok';
      // Reload after short delay so admin bar / menu changes take effect
      setTimeout(() => window.location.reload(), 800);
    } else {
      msg.textContent = '✗ ' + (res.data?.message || 'Save failed');
      msg.className   = 'wpwaf-st-save-msg err';
    }
  });
});
})();
</script>
