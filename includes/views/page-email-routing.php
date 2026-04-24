<?php
defined( 'ABSPATH' ) || exit;
$nonce     = wp_create_nonce( 'wpwaf_nonce' );
$ajax_url  = admin_url( 'admin-ajax.php' );
$accounts  = WPWAF_Accounts::all();
$active_id = WPWAF_Accounts::active_id();
?>
<style>
:root{--er-orange:#FF6A00;--er-border:#e2e6ea;--er-bg:#f8f9fb;--er-dark:#1a1a2e;--er-muted:#6b7280;}
.wpwaf-er-wrap{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;max-width:1000px;padding:24px 20px;color:var(--er-dark);}
.wpwaf-er-header{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:20px;flex-wrap:wrap;}
.wpwaf-er-header h1{font-size:22px;font-weight:700;margin:0 0 3px;display:flex;align-items:center;gap:8px;}
.wpwaf-er-header h1 .dashicons{color:var(--er-orange);font-size:24px;width:24px;height:24px;}
.wpwaf-er-header-sub{font-size:12px;color:var(--er-muted);}
.wpwaf-er-no-creds{background:#fff8f5;border:1px solid #fcd9c0;border-radius:8px;padding:20px 24px;color:#92400e;}
.wpwaf-er-btn{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:6px;font-size:13px;font-weight:600;cursor:pointer;border:none;transition:all .15s;white-space:nowrap;}
.wpwaf-er-btn-primary{background:var(--er-orange);color:#fff;}
.wpwaf-er-btn-primary:hover{background:#d95500;}
.wpwaf-er-btn-ghost{background:#fff;color:var(--er-dark);border:1px solid var(--er-border);}
.wpwaf-er-btn-ghost:hover{background:var(--er-bg);}
.wpwaf-er-btn-sm{padding:4px 10px;font-size:11px;border-radius:5px;}
.wpwaf-er-btn-danger{background:none;border:1px solid #fecaca;color:#e53e3e;}
.wpwaf-er-btn-danger:hover{background:#fee2e2;}
.wpwaf-er-btn:disabled{opacity:.5;cursor:not-allowed;}

/* Tabs */
.wpwaf-er-tabs{display:flex;border-bottom:2px solid var(--er-border);margin-bottom:20px;gap:0;}
.wpwaf-er-tab{padding:10px 18px;font-size:13px;font-weight:600;color:var(--er-muted);background:none;border:none;border-bottom:2px solid transparent;margin-bottom:-2px;cursor:pointer;}
.wpwaf-er-tab.active{color:var(--er-orange);border-bottom-color:var(--er-orange);}
.wpwaf-er-tab-panel{display:none;}
.wpwaf-er-tab-panel.active{display:block;}

/* Zone selector */
.wpwaf-er-select{padding:7px 10px;border:1px solid var(--er-border);border-radius:6px;font-size:13px;color:var(--er-dark);background:#fff;cursor:pointer;width:100%;max-width:380px;}
.wpwaf-er-select:focus{outline:none;border-color:var(--er-orange);}
.wpwaf-er-controls{display:flex;align-items:center;gap:10px;margin-bottom:16px;flex-wrap:wrap;}

/* Status bar */
.wpwaf-er-status-bar{background:#fff;border:1px solid var(--er-border);border-radius:8px;padding:12px 16px;margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;gap:10px;}
.wpwaf-er-status-on{display:flex;align-items:center;gap:6px;font-size:13px;font-weight:700;color:#059669;}
.wpwaf-er-status-off{display:flex;align-items:center;gap:6px;font-size:13px;font-weight:700;color:var(--er-muted);}
.wpwaf-er-status-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0;}
.wpwaf-er-status-dot.on{background:#059669;box-shadow:0 0 0 3px rgba(5,150,105,.2);}
.wpwaf-er-status-dot.off{background:#d1d5db;}

/* Destination addresses panel */
.wpwaf-er-addr-panel{background:#fff;border:1px solid var(--er-border);border-radius:8px;overflow:hidden;margin-bottom:20px;}
.wpwaf-er-addr-header{display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-bottom:1px solid var(--er-border);}
.wpwaf-er-addr-header h3{margin:0;font-size:13px;font-weight:700;}
.wpwaf-er-addr-list{padding:0;}
.wpwaf-er-addr-row{display:flex;align-items:center;gap:10px;padding:10px 16px;border-bottom:1px solid #f1f3f5;font-size:13px;}
.wpwaf-er-addr-row:last-child{border-bottom:none;}
.wpwaf-er-addr-email{flex:1;font-family:monospace;font-size:12px;}
.wpwaf-er-addr-badge{padding:2px 8px;border-radius:10px;font-size:10px;font-weight:700;}
.wpwaf-er-addr-badge.verified{background:#d1fae5;color:#065f46;}
.wpwaf-er-addr-badge.pending{background:#fef3c7;color:#92400e;}
.wpwaf-er-add-addr-row{display:flex;gap:8px;padding:12px 16px;background:var(--er-bg);border-top:1px solid var(--er-border);}
.wpwaf-er-add-addr-row input{flex:1;padding:7px 10px;border:1px solid var(--er-border);border-radius:6px;font-size:13px;}
.wpwaf-er-add-addr-row input:focus{outline:none;border-color:var(--er-orange);}

/* Rules table */
.wpwaf-er-table-wrap{background:#fff;border:1px solid var(--er-border);border-radius:8px;overflow:hidden;}
.wpwaf-er-table{width:100%;border-collapse:collapse;font-size:13px;}
.wpwaf-er-table th{background:var(--er-bg);padding:9px 14px;text-align:left;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--er-muted);border-bottom:1px solid var(--er-border);white-space:nowrap;}
.wpwaf-er-table td{padding:10px 14px;border-bottom:1px solid #f1f3f5;vertical-align:middle;}
.wpwaf-er-table tr:last-child td{border-bottom:none;}
.wpwaf-er-table tr:hover td{background:#fafbfc;}
.wpwaf-er-badge{display:inline-flex;padding:2px 8px;border-radius:10px;font-size:10px;font-weight:700;}
.wpwaf-er-badge.on{background:#d1fae5;color:#065f46;}
.wpwaf-er-badge.off{background:#f1f5f9;color:var(--er-muted);}
.wpwaf-er-action-badge{background:#e0f2fe;color:#0369a1;}

/* Notice */
.wpwaf-er-notice{background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:12px 16px;font-size:13px;color:#1e40af;margin-bottom:16px;display:flex;gap:10px;}
.wpwaf-er-notice-warn{background:#fff8f5;border-color:#fcd9c0;color:#92400e;}
.wpwaf-er-notice ul{margin:6px 0 0;padding-left:18px;}
.wpwaf-er-notice li{margin-bottom:3px;}

/* Modal */
.wpwaf-er-modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:100000;display:flex;align-items:center;justify-content:center;padding:20px;}
.wpwaf-er-modal{background:#fff;border-radius:10px;width:100%;max-width:460px;box-shadow:0 20px 60px rgba(0,0,0,.25);}
.wpwaf-er-modal-header{display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--er-border);}
.wpwaf-er-modal-header h3{margin:0;font-size:15px;font-weight:700;}
.wpwaf-er-modal-close{background:none;border:none;font-size:20px;cursor:pointer;color:var(--er-muted);}
.wpwaf-er-modal-body{padding:20px;display:flex;flex-direction:column;gap:14px;}
.wpwaf-er-modal-footer{padding:14px 20px;border-top:1px solid var(--er-border);display:flex;gap:8px;justify-content:flex-end;}
.wpwaf-er-field{display:flex;flex-direction:column;gap:4px;}
.wpwaf-er-field label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--er-muted);}
.wpwaf-er-field input,.wpwaf-er-field select{padding:8px 10px;border:1px solid var(--er-border);border-radius:6px;font-size:13px;box-sizing:border-box;width:100%;}
.wpwaf-er-field input:focus,.wpwaf-er-field select:focus{outline:none;border-color:var(--er-orange);}
.wpwaf-er-field-hint{font-size:11px;color:var(--er-muted);line-height:1.5;}
.wpwaf-er-modal-error{background:#fee2e2;border:1px solid #fca5a5;border-radius:6px;padding:10px 14px;color:#b91c1c;font-size:13px;}
.wpwaf-er-modal-success{background:#d1fae5;border:1px solid #6ee7b7;border-radius:6px;padding:10px 14px;color:#065f46;font-size:13px;}

/* Account selector bar */
.wpwaf-er-account-bar{display:flex;align-items:center;gap:10px;background:#fff;border:1px solid var(--er-border);border-radius:8px;padding:12px 16px;margin-bottom:16px;}
.wpwaf-er-account-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--er-muted);white-space:nowrap;}
.wpwaf-er-account-select{flex:1;padding:7px 10px;border:1px solid var(--er-border);border-radius:6px;font-size:13px;color:var(--er-dark);background:#fff;cursor:pointer;max-width:480px;}
.wpwaf-er-account-select:focus{outline:none;border-color:var(--er-orange);box-shadow:0 0 0 2px rgba(255,106,0,.12);}

/* Loading / empty */
.wpwaf-er-empty{text-align:center;padding:40px;color:var(--er-muted);font-style:italic;}
.wpwaf-er-loading{text-align:center;padding:30px;color:var(--er-muted);}
.wpwaf-er-spinner{display:inline-block;width:14px;height:14px;border:2px solid rgba(255,106,0,.3);border-top-color:var(--er-orange);border-radius:50%;animation:er-spin .6s linear infinite;vertical-align:middle;}
@keyframes er-spin{to{transform:rotate(360deg);}}

.wpwaf-er-toast{position:fixed;bottom:24px;right:24px;padding:10px 16px;border-radius:6px;font-size:13px;font-weight:600;color:#fff;z-index:999999;}
.wpwaf-er-toast.ok{background:#059669;}.wpwaf-er-toast.err{background:#dc2626;}

@media(max-width:660px){
  .wpwaf-er-modal-overlay{align-items:flex-end;padding:0;}
  .wpwaf-er-modal{max-width:100%;border-radius:16px 16px 0 0;}
  .wpwaf-er-add-addr-row{flex-direction:column;}
}
</style>

<div class="wpwaf-er-wrap">
  <div class="wpwaf-er-header">
    <div>
      <h1><span class="dashicons dashicons-email-alt"></span> Email Routing</h1>
      <div class="wpwaf-er-header-sub">Manage Cloudflare email forwarding — forward any address on your domain to any inbox</div>
    </div>
  </div>

  <?php if ( ! $has_creds ) : ?>
  <div class="wpwaf-er-no-creds">
    <strong>No Cloudflare account connected.</strong>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpwafmanager' ) ); ?>">Add credentials →</a>
  </div>
  <?php else : ?>

  <div class="wpwaf-er-notice">
    <span class="dashicons dashicons-info" style="flex-shrink:0;margin-top:1px;"></span>
    <div><strong>Free feature</strong> on all Cloudflare plans.
    How it works: (1) Add &amp; verify your destination inbox below. (2) Create a forwarding rule. (3) Emails sent to your domain get forwarded to your inbox.</div>
  </div>

  <!-- How it works -->
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px;">
    <div style="background:#fff;border:2px solid var(--er-border);border-radius:8px;padding:14px 16px;">
      <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--er-muted);margin-bottom:6px;">STEP 1 — ONE TIME SETUP</div>
      <div style="font-size:13px;font-weight:700;color:var(--er-dark);margin-bottom:4px;">📬 Add &amp; verify your inbox</div>
      <div style="font-size:12px;color:var(--er-muted);">Go to <strong>Destination Addresses</strong> tab → enter your Gmail or any inbox → Cloudflare emails you a verification link → click it.</div>
    </div>
    <div style="background:#fff;border:2px solid var(--er-border);border-radius:8px;padding:14px 16px;">
      <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--er-muted);margin-bottom:6px;">STEP 2 — CREATE RULES</div>
      <div style="font-size:13px;font-weight:700;color:var(--er-dark);margin-bottom:4px;">📋 Set up forwarding</div>
      <div style="font-size:12px;color:var(--er-muted);">Go to <strong>Forwarding Rules</strong> tab → pick a zone → click <strong>+ Add Rule</strong> → choose which address on your domain forwards to your verified inbox.</div>
    </div>
  </div>

  <!-- Account selector — always shown -->
  <div class="wpwaf-er-account-bar">
    <label class="wpwaf-er-account-label" for="wpwaf-er-account-select">Account</label>
    <select id="wpwaf-er-account-select" class="wpwaf-er-account-select">
      <?php foreach ( $accounts as $acc ) : ?>
      <option value="<?php echo esc_attr( $acc['id'] ); ?>" <?php selected( $acc['id'], $active_id ); ?>>
        <?php echo esc_html( $acc['label'] ?? 'Unnamed' ); ?> &mdash; <?php echo esc_html( $acc['auth_method'] === 'key' ? 'Email + Key' : 'API Token' ); ?>
      </option>
      <?php endforeach; ?>
    </select>
  </div>

  <!-- Tabs -->
  <div class="wpwaf-er-tabs">
    <button class="wpwaf-er-tab active" data-panel="rules">📋 Forwarding Rules</button>
    <button class="wpwaf-er-tab" data-panel="addresses" id="wpwaf-er-tab-addresses">📬 Destination Addresses <span id="wpwaf-er-addr-count-badge" style="background:#FF6A00;color:#fff;border-radius:10px;padding:1px 7px;font-size:10px;font-weight:700;margin-left:4px;display:none;"></span></button>
  </div>

  <!-- ── Tab: Forwarding Rules ──────────────────────────────────────────────── -->
  <div class="wpwaf-er-tab-panel active" id="wpwaf-er-panel-rules">

    <div class="wpwaf-er-controls">
      <select id="wpwaf-er-zone" class="wpwaf-er-select">
        <option value="">— Select a zone —</option>
      </select>
      <button class="wpwaf-er-btn wpwaf-er-btn-ghost" id="wpwaf-er-sync-btn" disabled title="Reload rules and catch-all from Cloudflare">↻ Sync</button>
      <button class="wpwaf-er-btn wpwaf-er-btn-primary" id="wpwaf-er-add-btn" disabled>+ Add Rule</button>
    </div>

    <div id="wpwaf-er-sync-notice" style="display:none;background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:10px 14px;margin-bottom:10px;font-size:12px;color:#92400e;display:flex;align-items:center;gap:8px;">
      <span>⚠</span>
      <span>Data is loaded from Cloudflare when you select a zone. If you've made changes outside this plugin, click <strong>↻ Sync</strong> to refresh. Always sync after making changes to see the latest state.</span>
    </div>
    <div class="wpwaf-er-status-bar" id="wpwaf-er-status-bar" style="display:none;">
      <div id="wpwaf-er-status-text" class="wpwaf-er-status-off">
        <span class="wpwaf-er-status-dot off"></span> Email Routing disabled
      </div>
      <button class="wpwaf-er-btn wpwaf-er-btn-ghost wpwaf-er-btn-sm" id="wpwaf-er-toggle-btn">Enable</button>
    </div>

    <div id="wpwaf-er-table-wrap" style="display:none;">
      <!-- Email setup card -->
      <div style="background:#fff;border:1px solid var(--er-border);border-radius:8px;padding:20px;margin-bottom:12px;">
        <div style="font-size:14px;font-weight:700;margin-bottom:4px;">📧 Email Forwarding Setup</div>
        <div style="font-size:12px;color:var(--er-muted);margin-bottom:18px;">Set a specific address on your domain (e.g. <code>info@yourdomain.com</code>) and choose where it forwards to.</div>

        <!-- Address row -->
        <div style="display:grid;grid-template-columns:1fr auto 1fr;align-items:center;gap:12px;margin-bottom:16px;">
          <div>
            <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--er-muted);display:block;margin-bottom:5px;">Email address on your domain</label>
            <div style="display:flex;align-items:center;border:1px solid var(--er-border);border-radius:6px;overflow:hidden;background:#fff;">
              <input type="text" id="wpwaf-er-catchall-local"
                placeholder="info, contact, hello…"
                style="border:none;outline:none;padding:9px 10px;font-size:13px;flex:1;min-width:0;">
              <span id="wpwaf-er-catchall-domain-suffix"
                style="padding:9px 10px;background:#f8f9fb;border-left:1px solid var(--er-border);font-size:13px;font-family:monospace;color:var(--er-muted);white-space:nowrap;">@yourdomain.com</span>
            </div>
            <div style="font-size:11px;color:var(--er-muted);margin-top:3px;">Leave blank to only use the Catch-All below</div>
          </div>
          <div style="font-size:20px;color:var(--er-muted);padding-top:20px;">→</div>
          <div>
            <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--er-muted);display:block;margin-bottom:5px;">Forward to (verified inbox)</label>
            <select id="wpwaf-er-catchall-dest" class="wpwaf-er-select" style="max-width:100%;width:100%;">
              <option value="">Loading addresses…</option>
            </select>
          </div>
        </div>

        <!-- Catch-all toggle row -->
        <div style="background:var(--er-bg);border:1px solid var(--er-border);border-radius:8px;padding:12px 14px;margin-bottom:14px;display:flex;align-items:center;justify-content:space-between;gap:12px;">
          <div>
            <div style="font-size:13px;font-weight:700;">Also enable Catch-All</div>
            <div style="font-size:11px;color:var(--er-muted);">Forward <em>all</em> email on this domain to the same inbox — even addresses you haven't specifically set up</div>
          </div>
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer;flex-shrink:0;">
            <input type="checkbox" id="wpwaf-er-catchall-enabled" style="accent-color:var(--er-orange);width:17px;height:17px;cursor:pointer;">
            <span id="wpwaf-er-catchall-status" style="font-size:12px;font-weight:600;color:var(--er-muted);">Off</span>
          </label>
        </div>

        <div style="display:flex;align-items:center;gap:10px;">
          <button class="wpwaf-er-btn wpwaf-er-btn-primary" id="wpwaf-er-catchall-save">Save</button>
          <span id="wpwaf-er-catchall-msg" style="font-size:13px;"></span>
        </div>
      </div>
      <!-- Specific rules table -->
      <div style="background:#fff;border:1px solid var(--er-border);border-radius:8px;overflow:hidden;">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;border-bottom:1px solid var(--er-border);">
          <div style="font-size:12px;font-weight:700;color:var(--er-muted);">SPECIFIC RULES <span style="font-weight:400;">(matched before catch-all)</span></div>
        </div>
        <table class="wpwaf-er-table">
          <thead>
            <tr>
              <th>From (on your domain)</th>
              <th>To (destination)</th>
              <th>Status</th>
              <th style="width:60px;"></th>
            </tr>
          </thead>
          <tbody id="wpwaf-er-tbody">
            <tr><td colspan="4" class="wpwaf-er-loading"><span class="wpwaf-er-spinner"></span></td></tr>
          </tbody>
        </table>
      </div>
    </div>

  </div>

  <!-- ── Tab: Destination Addresses ────────────────────────────────────────── -->
  <div class="wpwaf-er-tab-panel" id="wpwaf-er-panel-addresses">

    <div class="wpwaf-er-notice">
      <span class="dashicons dashicons-info" style="flex-shrink:0;margin-top:1px;"></span>
      <div>
        <strong>Destination addresses are account-level</strong> — they're shared across all your zones.
        Add an email address here, Cloudflare will send a verification link to it.
        Once verified, it becomes available to use in forwarding rules.
      </div>
    </div>

    <div class="wpwaf-er-addr-panel">
      <div class="wpwaf-er-addr-header">
        <h3>📬 Destination Addresses</h3>
        <button class="wpwaf-er-btn wpwaf-er-btn-ghost wpwaf-er-btn-sm" id="wpwaf-er-refresh-addrs">↻ Refresh</button>
      </div>
      <div id="wpwaf-er-addr-list" class="wpwaf-er-addr-list">
        <div class="wpwaf-er-loading"><span class="wpwaf-er-spinner"></span> Loading…</div>
      </div>
      <div class="wpwaf-er-add-addr-row">
        <input type="email" id="wpwaf-er-new-addr" placeholder="your@gmail.com — Cloudflare will send a verification email">
        <button class="wpwaf-er-btn wpwaf-er-btn-primary" id="wpwaf-er-add-addr-btn">Add &amp; Send Verification</button>
      </div>
      <div id="wpwaf-er-addr-msg" style="padding:0 16px 12px;font-size:12px;display:none;"></div>
    </div>

  </div>

  <!-- ── Add Rule Modal ─────────────────────────────────────────────────────── -->
  <div id="wpwaf-er-modal" class="wpwaf-er-modal-overlay" style="display:none;">
    <div class="wpwaf-er-modal">
      <div class="wpwaf-er-modal-header">
        <h3 id="wpwaf-er-modal-title">Add Forwarding Rule</h3>
        <button class="wpwaf-er-modal-close" id="wpwaf-er-modal-close">&times;</button>
      </div>
      <div class="wpwaf-er-modal-body">
        <div class="wpwaf-er-field">
          <label>Address on your domain to forward</label>
          <div style="display:flex;align-items:center;border:1px solid var(--er-border);border-radius:6px;overflow:hidden;background:#fff;">
            <input type="text" id="wpwaf-er-from"
              placeholder="contact, info, hello…"
              autocomplete="off"
              style="border:none;outline:none;padding:8px 10px;font-size:13px;flex:1;min-width:0;">
            <span id="wpwaf-er-from-suffix"
              style="padding:8px 10px;background:#f8f9fb;border-left:1px solid var(--er-border);font-size:13px;font-family:monospace;color:var(--er-muted);white-space:nowrap;">@yourdomain.com</span>
          </div>
          <span class="wpwaf-er-field-hint">Examples: <code>contact</code>, <code>info</code>, <code>hello</code>, <code>support</code></span>
        </div>
        <div class="wpwaf-er-field">
          <label>To — verified destination</label>
          <select id="wpwaf-er-to-select">
            <option value="">Loading verified addresses…</option>
          </select>
          <span class="wpwaf-er-field-hint">
            No addresses listed? Go to the <strong>Destination Addresses</strong> tab, add your inbox email, verify it, then come back here.
          </span>
        </div>
        <div class="wpwaf-er-field">
          <label>Rule name (optional)</label>
          <input type="text" id="wpwaf-er-name" placeholder="e.g. Forward hello to Gmail" maxlength="100">
        </div>
        <div id="wpwaf-er-modal-error" style="display:none;" class="wpwaf-er-modal-error"></div>
      </div>
      <div class="wpwaf-er-modal-footer">
        <button class="wpwaf-er-btn wpwaf-er-btn-ghost" id="wpwaf-er-modal-cancel">Cancel</button>
        <button class="wpwaf-er-btn wpwaf-er-btn-primary" id="wpwaf-er-modal-save">Add Rule</button>
      </div>
    </div>
  </div>

  <?php endif; ?>
</div>

<script>
'use strict';
document.addEventListener('DOMContentLoaded', function(){
const NONCE      = <?php echo wp_json_encode( $nonce ); ?>;
const AJAX_URL   = <?php echo wp_json_encode( $ajax_url ); ?>;
const ACTIVE_ID  = <?php echo wp_json_encode( $active_id ); ?>;
const HAS_MULTI  = <?php echo count( $accounts ) > 1 ? 'true' : 'false'; ?>;

let currentZone     = '';
let pluginAccountId = ACTIVE_ID; // which plugin account is active
let currentZoneName = '';
let routingEnabled  = false;
let accountId       = '';
let allAddresses    = [];

function qs(s){ return document.querySelector(s); }
function escHtml(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
function toast(msg, ok){
  ok = ok !== false;
  var el = document.createElement('div');
  el.className = 'wpwaf-er-toast ' + (ok ? 'ok' : 'err');
  el.textContent = msg;
  document.body.appendChild(el);
  setTimeout(function(){ el.style.opacity='0'; el.style.transition='opacity .3s'; setTimeout(function(){ el.remove(); }, 300); }, 3500);
}
function ajax(action, data, cb){
  var fd = new FormData();
  fd.append('action', action);
  fd.append('nonce', NONCE);
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
    accountId = ''; // reset CF account ID — will re-fetch
    allAddresses = [];
    currentZone = ''; currentZoneName = '';
    // Switch active account then reload zones
    ajax('wpwaf_switch_account', {account_id: pluginAccountId}, function(){
      // Reload zone dropdown
      var sel = qs('#wpwaf-er-zone');
      if(sel) sel.innerHTML = '<option value="">— Select a zone —</option>';
      if(qs('#wpwaf-er-status-bar')) qs('#wpwaf-er-status-bar').style.display = 'none';
      if(qs('#wpwaf-er-table-wrap')) qs('#wpwaf-er-table-wrap').style.display = 'none';
      if(qs('#wpwaf-er-add-btn')) qs('#wpwaf-er-add-btn').disabled = true;
      if(qs('#wpwaf-er-sync-btn')) qs('#wpwaf-er-sync-btn').disabled = true;
      var notice = qs('#wpwaf-er-sync-notice'); if(notice) notice.style.display = 'none';
      loadZones();
    });
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
  this.textContent = '↻ Syncing…';
  this.disabled = true;
  var btn = this;
  // Re-run full load then restore button
  loadZone();
  setTimeout(function(){ btn.textContent = '↻ Sync'; btn.disabled = false; toast('✓ Data refreshed from Cloudflare'); }, 1500);
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
    if(res.success){ routingEnabled = !routingEnabled; updateStatusBar(); toast(routingEnabled ? '✓ Email Routing enabled' : '✓ Disabled'); }
    else toast('Failed: ' + (res.data ? res.data.message : 'error'), false);
  });
});

// ── Catch-all rule ─────────────────────────────────────────────────────────────
function renderCatchAll(rule){
  var action  = rule.actions && rule.actions[0] ? rule.actions[0].type : 'drop';
  var dest    = rule.actions && rule.actions[0] && rule.actions[0].value ? rule.actions[0].value[0] : '';
  var enabled = rule.enabled !== false;

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
    sel.innerHTML = '<option value="">No verified addresses — see Destination Addresses tab</option>';
    return;
  }
  sel.innerHTML = '<option value="">— Select destination inbox —</option>'
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
  if(!dest){ msgEl.textContent = '⚠ Select a verified destination inbox'; msgEl.style.color = '#dc2626'; return; }
  msgEl.textContent = '';
  this.disabled = true;
  this.textContent = 'Saving…';
  var btn    = this;
  var done   = 0;
  var errors = [];
  var total  = (local && currentZoneName) ? 2 : 1;

  function finish(){
    done++;
    if(done < total) return;
    btn.disabled = false;
    btn.textContent = 'Save';
    if(errors.length){
      msgEl.textContent = '⚠ ' + errors.join('; ');
      msgEl.style.color = '#dc2626';
    } else {
      msgEl.textContent = '✓ Saved';
      msgEl.style.color = '#059669';
      setTimeout(function(){ msgEl.textContent = ''; }, 3000);
      loadRules();
      toast('✓ Email routing saved');
    }
  }

  // 1. Update catch-all rule
  ajax('wpwaf_catch_all_update', {
    zone_id:    currentZone,
    action_type: catchAllOn && dest ? 'forward' : 'drop',
    destination: catchAllOn ? dest : '',
    enabled:    '1'
  }, function(res){
    if(!res.success) errors.push('Catch-all: ' + (res.data ? res.data.message : 'failed'));
    finish();
  });

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
    // Filter out system catch-all rules (type:'all' or no matcher value) — managed in the card above
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
        if(!ruleId){ toast('Cannot delete — no rule ID found. Delete it directly in the Cloudflare dashboard.', false); return; }
        if(!confirm('Delete rule for "' + from + '"?')) return;
        this.disabled = true;
        this.textContent = '…';
        var delBtn = this;
        ajax('wpwaf_email_rule_delete', {zone_id:currentZone, rule_id:ruleId}, function(res){
          if(res.success){ loadRules(); toast('✓ Rule deleted'); }
          else {
            delBtn.disabled = false; delBtn.textContent = 'Delete';
            toast('Delete failed: ' + (res.data ? res.data.message : 'error') + ' — try deleting in the Cloudflare dashboard.', false);
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
    sel.innerHTML = '<option value="">— Select verified destination —</option>'
      + verified.map(function(a){ return '<option value="' + escHtml(a.email) + '">' + escHtml(a.email) + '</option>'; }).join('');
  } else {
    sel.innerHTML = '<option value="">No verified addresses — see Destination Addresses tab</option>';
    setTimeout(function(){
      closeModal();
      document.querySelectorAll('.wpwaf-er-tab').forEach(function(t){ t.classList.remove('active'); });
      document.querySelectorAll('.wpwaf-er-tab-panel').forEach(function(p){ p.classList.remove('active'); });
      var addrTab = qs('#wpwaf-er-tab-addresses');
      var addrPanel = qs('#wpwaf-er-panel-addresses');
      if(addrTab) addrTab.classList.add('active');
      if(addrPanel) addrPanel.classList.add('active');
      loadAddresses();
      toast('👆 First add a destination address, then create rules');
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
    if(!res.success){ errEl.textContent = '⚠ ' + (res.data ? res.data.message : 'Failed'); errEl.style.display = ''; return; }
    closeModal(); loadRules(); toast('✓ Forwarding rule created');
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
        + (a.verified ? '✓ Verified' : '⏳ Pending — check your inbox') + '</span>'
        + '</div>';
    }).join('');
  });
}

qs('#wpwaf-er-refresh-addrs').addEventListener('click', loadAddresses);

qs('#wpwaf-er-add-addr-btn').addEventListener('click', function(){
  var email = qs('#wpwaf-er-new-addr').value.trim();
  var msgEl = qs('#wpwaf-er-addr-msg');
  if(!email){ msgEl.textContent = '⚠ Enter a valid email address.'; msgEl.style.color = '#b91c1c'; msgEl.style.display = ''; return; }
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
      msgEl.textContent = '✓ Verification email sent to ' + email + '. Click the link, then refresh.';
      msgEl.style.color = '#059669'; msgEl.style.display = '';
      loadAddresses();
    } else {
      msgEl.textContent = '⚠ ' + (res.data ? res.data.message : 'Failed');
      msgEl.style.color = '#b91c1c'; msgEl.style.display = '';
    }
  });
});

});
</script>