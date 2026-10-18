<?php
defined( 'ABSPATH' ) || exit;
$wpwaf_al_user  = wp_get_current_user();
$wpwaf_al_email = ( $wpwaf_al_user && ! empty( $wpwaf_al_user->user_email ) ) ? (string) $wpwaf_al_user->user_email : '';
?>
<style>
:root{--al-orange:#FF6A00;--al-border:#e2e6ea;--al-bg:#f8f9fb;--al-dark:#1a1a2e;--al-muted:#6b7280;--al-green:#059669;--al-red:#dc2626;}
.wpwaf-al-wrap{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;color:var(--al-dark);}
.wpwaf-al-wrap *{box-sizing:border-box;}
.wpwaf-al-header{display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:20px;}
.wpwaf-al-header h1{font-size:22px;font-weight:700;margin:0;padding:0;display:flex;align-items:center;gap:8px;color:var(--al-dark);line-height:1.3;}
.wpwaf-al-header h1 .dashicons{color:var(--al-orange);font-size:24px;width:24px;height:24px;}
.wpwaf-al-header-sub{font-size:12px;color:var(--al-muted);margin-top:3px;}
.wpwaf-al-notice{background:#fff8f5;border:1px solid #fcd9c0;border-radius:8px;padding:14px 18px;color:#92400e;font-size:13px;line-height:1.6;margin-bottom:16px;}
.wpwaf-al-notice--error{background:#fef2f2;border-color:#fca5a5;color:#7f1d1d;}
.wpwaf-al-notice--info{background:#eff6ff;border-color:#bfdbfe;color:#1e3a8a;}
.wpwaf-al-notice a{color:inherit;font-weight:600;}

/* Tabs */
.wpwaf-al-tabs{display:flex;border-bottom:2px solid var(--al-border);margin-bottom:20px;gap:0;overflow-x:auto;}
.wpwaf-al-tab{padding:10px 18px;font-size:13px;font-weight:600;color:var(--al-muted);background:none;border:none;border-bottom:2px solid transparent;margin-bottom:-2px;cursor:pointer;white-space:nowrap;}
.wpwaf-al-tab:hover{color:var(--al-dark);}
.wpwaf-al-tab.active{color:var(--al-orange);border-bottom-color:var(--al-orange);}
.wpwaf-al-panel{display:none;}
.wpwaf-al-panel.active{display:block;}

/* Toolbar */
.wpwaf-al-toolbar{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:16px;}
.wpwaf-al-toolbar label{font-size:12px;font-weight:600;color:var(--al-muted);}
.wpwaf-al-select,.wpwaf-al-input,.wpwaf-al-textarea{padding:7px 10px;border:1px solid var(--al-border);border-radius:6px;font-size:13px;color:var(--al-dark);background:#fff;max-width:100%;}
.wpwaf-al-select{min-width:220px;}
.wpwaf-al-wrap .wpwaf-al-input,.wpwaf-al-wrap .wpwaf-al-select{height:34px;min-height:34px;line-height:1.4;}
.wpwaf-al-wrap .wpwaf-al-select{padding:0 28px 0 10px;}
.wpwaf-al-input,.wpwaf-al-textarea{width:100%;}
.wpwaf-al-textarea{resize:vertical;font-family:inherit;min-height:64px;}
.wpwaf-al-select:focus,.wpwaf-al-input:focus,.wpwaf-al-textarea:focus{outline:none;border-color:var(--al-orange);box-shadow:0 0 0 2px rgba(255,106,0,.1);}

/* Buttons */
.wpwaf-al-btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;height:32px;padding:0 14px;font-size:12px;font-weight:600;border-radius:6px;border:1px solid #d1d5db;background:#fff;color:#374151;cursor:pointer;transition:background .15s,border-color .15s;white-space:nowrap;text-decoration:none;}
.wpwaf-al-btn:hover{background:#f3f4f6;border-color:#9ca3af;}
.wpwaf-al-btn--primary{background:var(--al-orange);border-color:var(--al-orange);color:#fff;}
.wpwaf-al-btn--primary:hover{background:#d95500;border-color:#d95500;color:#fff;}
.wpwaf-al-btn--danger:hover{background:#fee2e2;border-color:#fca5a5;color:#b91c1c;}
.wpwaf-al-btn--sm{height:28px;padding:0 10px;font-size:11px;}
.wpwaf-al-btn:disabled{opacity:.5;cursor:not-allowed;}

/* Cards and sections */
.wpwaf-al-section{background:#fff;border:1px solid var(--al-border);border-radius:10px;margin-bottom:20px;overflow:hidden;}
.wpwaf-al-section-head{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;padding:12px 16px;border-bottom:1px solid var(--al-border);}
.wpwaf-al-section-head h2{margin:0;font-size:14px;font-weight:700;color:var(--al-dark);}
.wpwaf-al-section-sub{font-size:11px;color:var(--al-muted);margin-top:2px;}
.wpwaf-al-section-body{padding:16px;}
.wpwaf-al-empty{padding:28px 16px;text-align:center;color:#9ca3af;font-size:13px;}
.wpwaf-al-loading{padding:28px 16px;text-align:center;color:var(--al-muted);font-size:13px;display:flex;align-items:center;justify-content:center;gap:8px;}

/* Table */
.wpwaf-al-table{width:100%;border-collapse:collapse;font-size:13px;}
.wpwaf-al-table th{background:var(--al-bg);padding:9px 14px;text-align:left;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--al-muted);border-bottom:1px solid var(--al-border);white-space:nowrap;}
.wpwaf-al-table td{padding:10px 14px;border-bottom:1px solid #f1f3f5;vertical-align:middle;}
.wpwaf-al-table tr:last-child td{border-bottom:none;}
.wpwaf-al-table tbody tr:hover td{background:#fafbfc;}
.wpwaf-al-name{font-weight:600;color:#111;overflow-wrap:anywhere;}
.wpwaf-al-meta{font-size:11px;color:var(--al-muted);margin-top:2px;overflow-wrap:anywhere;}
.wpwaf-al-actions{display:flex;gap:6px;justify-content:flex-end;flex-wrap:wrap;}
.wpwaf-al-pill{display:inline-block;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:600;white-space:nowrap;background:#f3f4f6;color:#4b5563;}
.wpwaf-al-pill--ok{background:#d1fae5;color:#065f46;}
.wpwaf-al-pill--bad{background:#fee2e2;color:#991b1b;}
.wpwaf-al-pill--off{background:#f3f4f6;color:#6b7280;}
.wpwaf-al-pill--pro{background:#fef3c7;color:#92400e;}
.wpwaf-al-chip{display:inline-block;padding:1px 7px;border-radius:4px;font-size:11px;background:var(--al-bg);border:1px solid var(--al-border);margin:2px 4px 2px 0;overflow-wrap:anywhere;}

/* Switch */
.wpwaf-al-switch{position:relative;display:inline-block;width:42px;height:24px;flex-shrink:0;vertical-align:middle;}
.wpwaf-al-switch input{opacity:0;width:0;height:0;}
.wpwaf-al-slider{position:absolute;cursor:pointer;inset:0;background:#d1d5db;border-radius:24px;transition:.2s;}
.wpwaf-al-slider:before{position:absolute;content:"";height:18px;width:18px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.2s;box-shadow:0 1px 3px rgba(0,0,0,.2);}
.wpwaf-al-switch input:checked+.wpwaf-al-slider{background:var(--al-orange);}
.wpwaf-al-switch input:checked+.wpwaf-al-slider:before{transform:translateX(18px);}
.wpwaf-al-switch input:disabled+.wpwaf-al-slider{opacity:.5;cursor:not-allowed;}

/* Presets */
.wpwaf-al-presets{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:12px;}
.wpwaf-al-preset{display:flex;flex-direction:column;gap:6px;text-align:left;padding:14px;border:1px solid var(--al-border);border-radius:10px;background:#fff;cursor:pointer;transition:border-color .15s,box-shadow .15s;font:inherit;color:inherit;min-width:0;}
.wpwaf-al-preset:hover,.wpwaf-al-preset.active{border-color:var(--al-orange);box-shadow:0 4px 14px rgba(255,106,0,.08);}
.wpwaf-al-preset-icon{font-size:20px;line-height:1;}
.wpwaf-al-preset-title{font-size:13px;font-weight:700;color:var(--al-dark);display:flex;align-items:center;gap:6px;flex-wrap:wrap;}
.wpwaf-al-preset-desc{font-size:12px;color:var(--al-muted);line-height:1.5;}
.wpwaf-al-other{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-top:14px;}
.wpwaf-al-other .wpwaf-al-select{flex:1 1 260px;min-width:0;}

/* Form */
.wpwaf-al-form{border-top:1px solid var(--al-border);margin-top:16px;padding-top:16px;display:none;}
.wpwaf-al-form.open{display:block;}
.wpwaf-al-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px 16px;}
.wpwaf-al-grid--3{grid-template-columns:repeat(3,minmax(0,1fr));}
.wpwaf-al-field{display:flex;flex-direction:column;gap:5px;min-width:0;}
.wpwaf-al-field--full{grid-column:1/-1;}
.wpwaf-al-field>label,.wpwaf-al-field-label{font-size:12px;font-weight:600;color:var(--al-dark);}
.wpwaf-al-hint{font-size:11px;color:var(--al-muted);line-height:1.5;}
.wpwaf-al-req{color:var(--al-red);}
.wpwaf-al-checks{display:flex;flex-wrap:wrap;gap:6px 14px;max-height:180px;overflow:auto;padding:8px 10px;border:1px solid var(--al-border);border-radius:6px;background:var(--al-bg);}
.wpwaf-al-check{display:inline-flex;align-items:center;gap:6px;font-size:12px;min-width:0;overflow-wrap:anywhere;}
.wpwaf-al-form-foot{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-top:16px;}
.wpwaf-al-inline{display:flex;align-items:center;gap:8px;font-size:12px;}

/* Help box */
.wpwaf-al-help{background:var(--al-bg);border:1px solid var(--al-border);border-radius:8px;margin-bottom:16px;font-size:12px;color:#374151;}
.wpwaf-al-help summary{cursor:pointer;padding:10px 14px;font-weight:600;}
.wpwaf-al-help-body{padding:0 14px 12px;line-height:1.6;}
.wpwaf-al-help code{font-size:11px;background:#fff;border:1px solid var(--al-border);border-radius:4px;padding:1px 5px;overflow-wrap:anywhere;}

/* Spinner and toast */
.wpwaf-al-spinner{display:inline-block;width:14px;height:14px;border:2px solid rgba(255,106,0,.3);border-top-color:var(--al-orange);border-radius:50%;animation:wpwaf-al-spin .6s linear infinite;}
@keyframes wpwaf-al-spin{to{transform:rotate(360deg);}}
.wpwaf-al-toast{position:fixed;bottom:24px;right:24px;max-width:calc(100vw - 48px);padding:10px 16px;border-radius:6px;font-size:13px;font-weight:600;color:#fff;z-index:999999;box-shadow:0 4px 16px rgba(0,0,0,.15);}
.wpwaf-al-toast.ok{background:var(--al-green);}
.wpwaf-al-toast.err{background:var(--al-red);}

@media (max-width:782px){
  .wpwaf-al-toolbar{align-items:stretch;}
  .wpwaf-al-toolbar .wpwaf-al-select{flex:1 1 100%;min-width:0;}
  .wpwaf-al-grid,.wpwaf-al-grid--3{grid-template-columns:1fr;}
  .wpwaf-al-table thead{display:none;}
  .wpwaf-al-table,.wpwaf-al-table tbody,.wpwaf-al-table tr,.wpwaf-al-table td{display:block;width:100%;}
  .wpwaf-al-table tr{border-bottom:1px solid var(--al-border);padding:8px 0;}
  .wpwaf-al-table tr:last-child{border-bottom:none;}
  .wpwaf-al-table td{border-bottom:none;padding:4px 14px;}
  .wpwaf-al-table td[data-label]:before{content:attr(data-label);display:block;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--al-muted);margin-bottom:2px;}
  .wpwaf-al-actions{justify-content:flex-start;}
  .wpwaf-al-table td.wpwaf-al-td-inline{display:inline-block;width:auto;vertical-align:middle;}
  .wpwaf-al-table td.wpwaf-al-td-inline[data-label]:before{display:none;}
  .wpwaf-al-toast{left:12px;right:12px;bottom:12px;max-width:none;}
}
@media (max-width:480px){
  .wpwaf-al-presets{grid-template-columns:1fr;}
  .wpwaf-al-section-body{padding:12px;}
  .wpwaf-al-form-foot .wpwaf-al-btn{flex:1 1 100%;}
  .wpwaf-al-tab{padding:10px 12px;}
}
</style>

<div class="wpwaf-al-wrap">
	<div class="wpwaf-al-header">
		<div>
			<h1><span class="dashicons dashicons-bell"></span> Alerts &amp; Health</h1>
			<div class="wpwaf-al-header-sub">Email and webhook alerts from Cloudflare, plus uptime checks on your origin servers</div>
		</div>
	</div>

<?php if ( ! $has_creds ) : ?>
	<div class="wpwaf-al-notice">
		<strong>No Cloudflare account connected.</strong><br>
		Add a Cloudflare account on the main <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpwafmanager' ) ); ?>">WAF Rules</a> page first.
	</div>
</div>
<?php return; endif; ?>

	<div class="wpwaf-al-tabs" role="tablist">
		<button type="button" class="wpwaf-al-tab active" data-tab="alerts" role="tab">&#128276; Alerts</button>
		<button type="button" class="wpwaf-al-tab" data-tab="health" role="tab">&#128154; Health Checks</button>
	</div>

	<!-- ═══ Alerts ═══ -->
	<div class="wpwaf-al-panel active" id="wpwaf-al-panel-alerts">
		<div class="wpwaf-al-toolbar">
			<label for="wpwaf-al-account">Cloudflare account</label>
			<select id="wpwaf-al-account" class="wpwaf-al-select"><option value="">Loading accounts&hellip;</option></select>
			<button type="button" class="wpwaf-al-btn" id="wpwaf-al-refresh">&#8635; Refresh</button>
		</div>

		<details class="wpwaf-al-help">
			<summary>&#128273; API token permissions for this page</summary>
			<div class="wpwaf-al-help-body">
				Alerts need <code>Account &rarr; Notifications &rarr; Edit</code> (Read is enough to view them).
				Health Checks need <code>Zone &rarr; Health Checks &rarr; Edit</code> (Read is enough to view them).
				Alerts are sent by Cloudflare, so they keep working even when this WordPress site is down.
			</div>
		</details>

		<div id="wpwaf-al-error"></div>

		<div class="wpwaf-al-section">
			<div class="wpwaf-al-section-head">
				<div>
					<h2>Your alerts</h2>
					<div class="wpwaf-al-section-sub">Notification policies on this Cloudflare account</div>
				</div>
				<span class="wpwaf-al-pill" id="wpwaf-al-policy-count"></span>
			</div>
			<div id="wpwaf-al-policies"><div class="wpwaf-al-loading"><span class="wpwaf-al-spinner"></span> Loading alerts&hellip;</div></div>
		</div>

		<div class="wpwaf-al-section" id="wpwaf-al-add-section">
			<div class="wpwaf-al-section-head">
				<div>
					<h2>Add an alert</h2>
					<div class="wpwaf-al-section-sub">Presets for WordPress sites. Only alert types available on this account are shown.</div>
				</div>
			</div>
			<div class="wpwaf-al-section-body">
				<div class="wpwaf-al-presets" id="wpwaf-al-presets"></div>
				<div class="wpwaf-al-other" id="wpwaf-al-other-row" style="display:none;">
					<select id="wpwaf-al-other" class="wpwaf-al-select" aria-label="Other alert types"></select>
					<button type="button" class="wpwaf-al-btn" id="wpwaf-al-other-go">Set up</button>
				</div>

				<form class="wpwaf-al-form" id="wpwaf-al-form" autocomplete="off">
					<div class="wpwaf-al-grid">
						<div class="wpwaf-al-field">
							<label for="wpwaf-al-f-name">Alert name <span class="wpwaf-al-req">*</span></label>
							<input type="text" id="wpwaf-al-f-name" class="wpwaf-al-input" maxlength="120">
						</div>
						<div class="wpwaf-al-field">
							<span class="wpwaf-al-field-label">Alert type</span>
							<div id="wpwaf-al-f-type" class="wpwaf-al-name"></div>
							<div id="wpwaf-al-f-typedesc" class="wpwaf-al-hint"></div>
						</div>
						<div class="wpwaf-al-field wpwaf-al-field--full" id="wpwaf-al-f-filters"></div>
						<div class="wpwaf-al-field">
							<label for="wpwaf-al-f-emails">Email to</label>
							<textarea id="wpwaf-al-f-emails" class="wpwaf-al-textarea" rows="3" placeholder="one@agency.com&#10;client@example.com"><?php echo esc_textarea( $wpwaf_al_email ); ?></textarea>
							<div class="wpwaf-al-hint">One address per line or comma separated.</div>
						</div>
						<div class="wpwaf-al-field">
							<span class="wpwaf-al-field-label">Webhooks</span>
							<div id="wpwaf-al-f-webhooks" class="wpwaf-al-checks"></div>
							<div class="wpwaf-al-hint">Add webhooks (Slack, Teams, your own endpoint) in the Webhooks section below.</div>
						</div>
						<div class="wpwaf-al-field wpwaf-al-field--full">
							<label for="wpwaf-al-f-desc">Description</label>
							<input type="text" id="wpwaf-al-f-desc" class="wpwaf-al-input" maxlength="300" placeholder="Optional note for your team">
						</div>
					</div>
					<div class="wpwaf-al-form-foot">
						<button type="submit" class="wpwaf-al-btn wpwaf-al-btn--primary" id="wpwaf-al-f-save">Create alert</button>
						<button type="button" class="wpwaf-al-btn" id="wpwaf-al-f-cancel">Cancel</button>
						<label class="wpwaf-al-inline"><input type="checkbox" id="wpwaf-al-f-enabled" checked> Turn on right away</label>
					</div>
				</form>
			</div>
		</div>

		<div class="wpwaf-al-section">
			<div class="wpwaf-al-section-head">
				<div>
					<h2>Webhooks</h2>
					<div class="wpwaf-al-section-sub">Where Cloudflare can POST alerts besides email</div>
				</div>
				<button type="button" class="wpwaf-al-btn wpwaf-al-btn--sm" id="wpwaf-al-wh-toggle">+ Add webhook</button>
			</div>
			<form class="wpwaf-al-section-body" id="wpwaf-al-wh-form" style="display:none;border-bottom:1px solid var(--al-border);" autocomplete="off">
				<div class="wpwaf-al-grid wpwaf-al-grid--3">
					<div class="wpwaf-al-field">
						<label for="wpwaf-al-wh-name">Name <span class="wpwaf-al-req">*</span></label>
						<input type="text" id="wpwaf-al-wh-name" class="wpwaf-al-input" maxlength="120" placeholder="Agency Slack">
					</div>
					<div class="wpwaf-al-field">
						<label for="wpwaf-al-wh-url">URL <span class="wpwaf-al-req">*</span></label>
						<input type="url" id="wpwaf-al-wh-url" class="wpwaf-al-input" placeholder="https://hooks.example.com/...">
					</div>
					<div class="wpwaf-al-field">
						<label for="wpwaf-al-wh-secret">Secret</label>
						<input type="password" id="wpwaf-al-wh-secret" class="wpwaf-al-input" placeholder="Optional">
						<div class="wpwaf-al-hint">Cloudflare never returns the secret after saving.</div>
					</div>
				</div>
				<div class="wpwaf-al-form-foot">
					<button type="submit" class="wpwaf-al-btn wpwaf-al-btn--primary" id="wpwaf-al-wh-save">Save webhook</button>
				</div>
			</form>
			<div id="wpwaf-al-webhooks"><div class="wpwaf-al-empty">No webhooks yet.</div></div>
		</div>
	</div>

	<!-- ═══ Health Checks ═══ -->
	<div class="wpwaf-al-panel" id="wpwaf-al-panel-health">
		<div class="wpwaf-al-toolbar">
			<label for="wpwaf-al-zone">Zone</label>
			<select id="wpwaf-al-zone" class="wpwaf-al-select"><option value="">Loading zones&hellip;</option></select>
			<button type="button" class="wpwaf-al-btn" id="wpwaf-al-hc-refresh">&#8635; Refresh</button>
			<button type="button" class="wpwaf-al-btn wpwaf-al-btn--primary" id="wpwaf-al-hc-add">+ Add health check</button>
		</div>

		<div class="wpwaf-al-notice wpwaf-al-notice--info">
			Health Checks ping your origin server from Cloudflare's network on a schedule and mark it healthy or unhealthy. They need a <strong>Pro plan or higher</strong> on the zone (Pro 10 checks, Business 50, Enterprise 1,000).
			To get an email when a check fails, use <strong>Alert me</strong> on a check, or the Health Check status preset on the Alerts tab.
		</div>

		<div id="wpwaf-al-hc-msg"></div>

		<div class="wpwaf-al-section" id="wpwaf-al-hc-form-section" style="display:none;">
			<div class="wpwaf-al-section-head">
				<div>
					<h2 id="wpwaf-al-hc-form-title">Add health check</h2>
					<div class="wpwaf-al-section-sub">Preset: an HTTPS request to your home page that expects a 200 response</div>
				</div>
			</div>
			<form class="wpwaf-al-section-body" id="wpwaf-al-hc-form" autocomplete="off">
				<div class="wpwaf-al-grid wpwaf-al-grid--3">
					<div class="wpwaf-al-field">
						<label for="wpwaf-al-hc-name">Name <span class="wpwaf-al-req">*</span></label>
						<input type="text" id="wpwaf-al-hc-name" class="wpwaf-al-input" maxlength="100" pattern="[A-Za-z0-9_\-]+" placeholder="wordpress-home">
						<div class="wpwaf-al-hint">Letters, numbers, hyphens and underscores only.</div>
					</div>
					<div class="wpwaf-al-field">
						<label for="wpwaf-al-hc-address">Origin address <span class="wpwaf-al-req">*</span></label>
						<input type="text" id="wpwaf-al-hc-address" class="wpwaf-al-input" placeholder="origin.example.com or 203.0.113.10">
						<div class="wpwaf-al-hint">Hostname or IP of the server behind Cloudflare.</div>
					</div>
					<div class="wpwaf-al-field">
						<label for="wpwaf-al-hc-type">Type</label>
						<select id="wpwaf-al-hc-type" class="wpwaf-al-select" style="min-width:0;">
							<option value="HTTPS">HTTPS</option>
							<option value="HTTP">HTTP</option>
							<option value="TCP">TCP (connection only)</option>
						</select>
					</div>
					<div class="wpwaf-al-field wpwaf-al-hc-http">
						<label for="wpwaf-al-hc-path">Path</label>
						<input type="text" id="wpwaf-al-hc-path" class="wpwaf-al-input" value="/">
					</div>
					<div class="wpwaf-al-field wpwaf-al-hc-http">
						<label for="wpwaf-al-hc-method">Method</label>
						<select id="wpwaf-al-hc-method" class="wpwaf-al-select" style="min-width:0;">
							<option value="GET">GET</option>
							<option value="HEAD">HEAD</option>
						</select>
					</div>
					<div class="wpwaf-al-field wpwaf-al-hc-http">
						<label for="wpwaf-al-hc-codes">Expected codes</label>
						<input type="text" id="wpwaf-al-hc-codes" class="wpwaf-al-input" value="200">
						<div class="wpwaf-al-hint">Comma separated, for example 200, 301 or 2xx.</div>
					</div>
					<div class="wpwaf-al-field wpwaf-al-hc-http">
						<label for="wpwaf-al-hc-host">Host header</label>
						<input type="text" id="wpwaf-al-hc-host" class="wpwaf-al-input" placeholder="www.example.com">
						<div class="wpwaf-al-hint">Recommended so your server returns the right site.</div>
					</div>
					<div class="wpwaf-al-field wpwaf-al-hc-http">
						<label for="wpwaf-al-hc-body">Response must contain</label>
						<input type="text" id="wpwaf-al-hc-body" class="wpwaf-al-input" placeholder="Optional text, checked in the first 10 KB">
					</div>
					<div class="wpwaf-al-field">
						<label for="wpwaf-al-hc-port">Port</label>
						<input type="number" id="wpwaf-al-hc-port" class="wpwaf-al-input" min="1" max="65535" placeholder="Default (443, 80)">
					</div>
					<div class="wpwaf-al-field">
						<label for="wpwaf-al-hc-interval">Interval (seconds)</label>
						<input type="number" id="wpwaf-al-hc-interval" class="wpwaf-al-input" min="1" value="60">
					</div>
					<div class="wpwaf-al-field">
						<label for="wpwaf-al-hc-retries">Retries on timeout</label>
						<input type="number" id="wpwaf-al-hc-retries" class="wpwaf-al-input" min="0" value="2">
					</div>
					<div class="wpwaf-al-field">
						<label for="wpwaf-al-hc-timeout">Timeout (seconds)</label>
						<input type="number" id="wpwaf-al-hc-timeout" class="wpwaf-al-input" min="1" value="5">
					</div>
					<div class="wpwaf-al-field wpwaf-al-field--full">
						<span class="wpwaf-al-field-label">Check regions</span>
						<div class="wpwaf-al-checks" id="wpwaf-al-hc-regions"></div>
						<div class="wpwaf-al-hint">Leave all unchecked to let Cloudflare choose. Each region checks from three data centers, so more regions means more requests to your origin. All Regions needs a Business or Enterprise plan.</div>
					</div>
					<div class="wpwaf-al-field wpwaf-al-field--full">
						<label class="wpwaf-al-inline wpwaf-al-hc-http"><input type="checkbox" id="wpwaf-al-hc-follow" checked> Follow redirects</label>
						<label class="wpwaf-al-inline wpwaf-al-hc-http"><input type="checkbox" id="wpwaf-al-hc-insecure"> Skip certificate validation (self-signed origin certificates)</label>
						<label class="wpwaf-al-inline"><input type="checkbox" id="wpwaf-al-hc-suspended"> Create paused</label>
					</div>
				</div>
				<div class="wpwaf-al-hint" style="margin-top:12px;">If your WAF rules or Bot Fight Mode block the check, allow the user agent <code>Cloudflare-Healthchecks</code>.</div>
				<div class="wpwaf-al-form-foot">
					<button type="submit" class="wpwaf-al-btn wpwaf-al-btn--primary" id="wpwaf-al-hc-save">Create health check</button>
					<button type="button" class="wpwaf-al-btn" id="wpwaf-al-hc-cancel">Cancel</button>
				</div>
			</form>
		</div>

		<div class="wpwaf-al-section">
			<div class="wpwaf-al-section-head">
				<div>
					<h2>Health checks</h2>
					<div class="wpwaf-al-section-sub" id="wpwaf-al-hc-sub">Pick a zone to see its checks</div>
				</div>
				<span class="wpwaf-al-pill" id="wpwaf-al-hc-count"></span>
			</div>
			<div id="wpwaf-al-hc-list"><div class="wpwaf-al-empty">Pick a zone to see its checks.</div></div>
		</div>
	</div>
</div>

<script>
'use strict';
(function(){
	const AJAX_URL = <?php echo wp_json_encode( $ajax_url ); ?>;
	const NONCE    = <?php echo wp_json_encode( $nonce ); ?>;

	// Presets for WordPress sites. A preset is shown only when Cloudflare lists its
	// type as available on the selected account; the name shown comes from Cloudflare.
	const PRESETS = [
		{ type:'dos_attack_l7', icon:'🛡', name:'HTTP DDoS attack', desc:'Email me when Cloudflare mitigates an HTTP DDoS attack on any of my sites.' },
		{ type:'universal_ssl_event_type', icon:'🔒', name:'SSL certificate events', desc:'Validation, issuance, renewal and expiry notices for Universal SSL certificates.' },
		{ type:'health_check_status_notification', icon:'💚', name:'Health Check status', desc:'Email me when a health check finds my origin server down or back up.', pro:true },
		{ type:'security_insights_alert', icon:'🔎', name:'Security Insights', desc:'New findings from Cloudflare Security Insights scans, such as exposed DNS records.' }
	];
	const REGIONS = [
		['WNAM','Western North America'],['ENAM','Eastern North America'],['WEU','Western Europe'],['EEU','Eastern Europe'],
		['NSAM','Northern South America'],['SSAM','Southern South America'],['OC','Oceania'],['ME','Middle East'],
		['NAF','Northern Africa'],['SAF','Southern Africa'],['IN','India'],['SEAS','South East Asia'],['NEAS','North East Asia'],
		['ALL_REGIONS','All Regions']
	];

	const esc = s => String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
	const qs  = (s, c) => (c || document).querySelector(s);
	const qsa = (s, c) => Array.from((c || document).querySelectorAll(s));

	function ajax(action, data, cb){
		const fd = new FormData();
		fd.append('action', action); fd.append('nonce', NONCE);
		Object.entries(data || {}).forEach(([k, v]) => fd.append(k, v));
		fetch(AJAX_URL, { method:'POST', body:fd, credentials:'same-origin' })
			.then(r => r.json()).then(cb)
			.catch(e => cb({ success:false, data:{ message:e.message || 'Request failed' } }));
	}
	function errMsg(res){ return (res && res.data && res.data.message) || 'Cloudflare API error'; }
	function toast(msg, ok){
		const el = document.createElement('div');
		el.className = 'wpwaf-al-toast ' + (ok === false ? 'err' : 'ok');
		el.textContent = msg;
		document.body.appendChild(el);
		setTimeout(() => { el.style.transition = 'opacity .3s'; el.style.opacity = '0'; setTimeout(() => el.remove(), 300); }, 3200);
	}
	function planLabel(plan){
		const p = String(plan || '').toLowerCase();
		if (p.includes('enterprise')) return 'Enterprise';
		if (p.includes('business')) return 'Business';
		if (p.includes('pro')) return 'Pro';
		if (p.includes('free')) return 'Free';
		return plan || 'Unknown';
	}
	function isFree(plan){ return String(plan || '').toLowerCase().includes('free'); }
	function fmtDate(s){
		if (!s) return '';
		const d = new Date(s);
		return isNaN(d) ? '' : d.toLocaleString(undefined, { year:'numeric', month:'short', day:'numeric', hour:'2-digit', minute:'2-digit' });
	}

	// ── State ───────────────────────────────────────────────────────────────
	let accountId = '';
	let zones = [];
	let zonesLoaded = false;
	let policies = [], available = [], webhooks = [];
	let formType = null;           // available alert type object being set up
	let pendingHealthIds = [];     // health check IDs passed from "Alert me"
	const hcCache = {};            // zone_id => checks

	// ── Tabs ────────────────────────────────────────────────────────────────
	function showTab(name){
		qsa('.wpwaf-al-tab').forEach(t => t.classList.toggle('active', t.dataset.tab === name));
		qsa('.wpwaf-al-panel').forEach(p => p.classList.toggle('active', p.id === 'wpwaf-al-panel-' + name));
		if (name === 'health') ensureZones(() => { if (!qs('#wpwaf-al-zone').value) pickFirstPaidZone(); });
	}
	qsa('.wpwaf-al-tab').forEach(t => t.addEventListener('click', () => showTab(t.dataset.tab)));

	// ── Zones (shared) ──────────────────────────────────────────────────────
	function ensureZones(cb){
		if (zonesLoaded){ cb && cb(); return; }
		ajax('wpwaf_list_zones', {}, res => {
			zonesLoaded = true;
			zones = res.success ? (res.data.zones || []) : [];
			const sel = qs('#wpwaf-al-zone');
			if (!res.success){
				sel.innerHTML = '<option value="">Could not load zones</option>';
				qs('#wpwaf-al-hc-msg').innerHTML = '<div class="wpwaf-al-notice wpwaf-al-notice--error">' + esc(errMsg(res)) + '</div>';
			} else {
				sel.innerHTML = '<option value="">Select a zone</option>' + zones.map(z =>
					'<option value="' + esc(z.id) + '">' + esc(z.name) + ' (' + esc(planLabel(z.plan)) + ')</option>').join('');
			}
			cb && cb();
		});
	}
	function zoneById(id){ return zones.find(z => z.id === id) || null; }
	function accountZones(){
		const own = zones.filter(z => !z.account_id || z.account_id === accountId);
		return own.length ? own : zones;
	}

	// ═══ Alerts ═════════════════════════════════════════════════════════════
	function loadAccounts(){
		ajax('wpwaf_cf_accounts', {}, res => {
			const sel = qs('#wpwaf-al-account');
			if (!res.success || !(res.data.accounts || []).length){
				sel.innerHTML = '<option value="">No accounts found</option>';
				qs('#wpwaf-al-policies').innerHTML = '<div class="wpwaf-al-empty">' + esc(res.success ? 'This API token cannot see any Cloudflare accounts.' : errMsg(res)) + '</div>';
				return;
			}
			const list = res.data.accounts;
			sel.innerHTML = list.map(a => '<option value="' + esc(a.id) + '">' + esc(a.name) + '</option>').join('');
			accountId = list[0].id;
			loadAlerts();
		});
	}
	qs('#wpwaf-al-account').addEventListener('change', function(){ accountId = this.value; closeForm(); loadAlerts(); });
	qs('#wpwaf-al-refresh').addEventListener('click', () => { if (accountId) loadAlerts(); });

	function loadAlerts(){
		qs('#wpwaf-al-error').innerHTML = '';
		qs('#wpwaf-al-policies').innerHTML = '<div class="wpwaf-al-loading"><span class="wpwaf-al-spinner"></span> Loading alerts&hellip;</div>';
		qs('#wpwaf-al-policy-count').textContent = '';
		ajax('wpwaf_al_data', { account_id: accountId }, res => {
			if (!res.success){
				policies = []; available = []; webhooks = [];
				qs('#wpwaf-al-policies').innerHTML = '<div class="wpwaf-al-empty">Alerts could not be loaded.</div>';
				qs('#wpwaf-al-error').innerHTML = '<div class="wpwaf-al-notice wpwaf-al-notice--error"><strong>Could not load alerts.</strong><br>' + esc(errMsg(res)) + '</div>';
				renderPresets(); renderWebhooks();
				return;
			}
			policies  = res.data.policies || [];
			available = res.data.available || [];
			webhooks  = res.data.webhooks || [];
			const notes = [];
			if (res.data.available_error) notes.push('Alert types could not be loaded: ' + res.data.available_error);
			if (res.data.webhooks_error)  notes.push('Webhooks could not be loaded: ' + res.data.webhooks_error);
			if (notes.length) qs('#wpwaf-al-error').innerHTML = '<div class="wpwaf-al-notice">' + notes.map(esc).join('<br>') + '</div>';
			renderPolicies(); renderPresets(); renderWebhooks();
			if (pendingHealthIds.length) openPresetByType('health_check_status_notification');
		});
	}

	function typeName(type){
		const t = available.find(a => a.type === type);
		return t ? t.display_name : type;
	}

	function renderPolicies(){
		const box = qs('#wpwaf-al-policies');
		qs('#wpwaf-al-policy-count').textContent = policies.length + ' alert' + (policies.length !== 1 ? 's' : '');
		if (!policies.length){
			box.innerHTML = '<div class="wpwaf-al-empty">No alerts on this account yet. Pick a preset below to add one.</div>';
			return;
		}
		box.innerHTML = '<table class="wpwaf-al-table"><thead><tr><th>Alert</th><th>Sends to</th><th>On</th><th></th></tr></thead><tbody>' +
			policies.map(p => {
				const m = p.mechanisms || {};
				const emails = (m.email || []).map(e => e.id);
				const hooks  = (m.webhooks || []).map(w => { const h = webhooks.find(x => x.id === w.id); return h ? h.name : 'Webhook'; });
				const pd     = (m.pagerduty || []).length;
				const chips  = emails.map(e => '<span class="wpwaf-al-chip">&#9993; ' + esc(e) + '</span>').join('') +
					hooks.map(h => '<span class="wpwaf-al-chip">&#128279; ' + esc(h) + '</span>').join('') +
					(pd ? '<span class="wpwaf-al-chip">PagerDuty</span>' : '');
				return '<tr data-id="' + esc(p.id) + '">' +
					'<td data-label="Alert"><div class="wpwaf-al-name">' + esc(p.name || '(no name)') + '</div><div class="wpwaf-al-meta">' + esc(typeName(p.alert_type)) + (p.description ? ' &middot; ' + esc(p.description) : '') + '</div></td>' +
					'<td data-label="Sends to">' + (chips || '<span class="wpwaf-al-meta">No delivery set</span>') + '</td>' +
					'<td data-label="On" class="wpwaf-al-td-inline"><label class="wpwaf-al-switch" title="Turn this alert on or off"><input type="checkbox" class="wpwaf-al-pol-toggle" data-id="' + esc(p.id) + '"' + (p.enabled ? ' checked' : '') + '><span class="wpwaf-al-slider"></span></label></td>' +
					'<td class="wpwaf-al-td-inline"><div class="wpwaf-al-actions"><button type="button" class="wpwaf-al-btn wpwaf-al-btn--sm wpwaf-al-btn--danger wpwaf-al-pol-del" data-id="' + esc(p.id) + '">Delete</button></div></td>' +
				'</tr>';
			}).join('') + '</tbody></table>';
	}

	qs('#wpwaf-al-policies').addEventListener('change', e => {
		const t = e.target.closest('.wpwaf-al-pol-toggle');
		if (!t) return;
		const on = t.checked;
		t.disabled = true;
		ajax('wpwaf_al_policy_toggle', { account_id: accountId, policy_id: t.dataset.id, enabled: on ? '1' : '0' }, res => {
			t.disabled = false;
			if (res.success){
				const p = policies.find(x => x.id === t.dataset.id); if (p) p.enabled = on;
				toast(on ? 'Alert turned on' : 'Alert turned off');
			} else { t.checked = !on; toast('Failed: ' + errMsg(res), false); }
		});
	});
	qs('#wpwaf-al-policies').addEventListener('click', e => {
		const b = e.target.closest('.wpwaf-al-pol-del');
		if (!b) return;
		const p = policies.find(x => x.id === b.dataset.id);
		if (!confirm('Delete the alert "' + (p ? p.name : '') + '"? Cloudflare will stop sending it.')) return;
		b.disabled = true;
		ajax('wpwaf_al_policy_delete', { account_id: accountId, policy_id: b.dataset.id }, res => {
			if (res.success){ policies = policies.filter(x => x.id !== b.dataset.id); renderPolicies(); toast('Alert deleted'); }
			else { b.disabled = false; toast('Failed: ' + errMsg(res), false); }
		});
	});

	// ── Presets ─────────────────────────────────────────────────────────────
	function renderPresets(){
		const box = qs('#wpwaf-al-presets');
		const shown = PRESETS.filter(p => available.some(a => a.type === p.type));
		box.innerHTML = shown.length ? shown.map(p =>
			'<button type="button" class="wpwaf-al-preset" data-type="' + esc(p.type) + '">' +
				'<span class="wpwaf-al-preset-icon">' + p.icon + '</span>' +
				'<span class="wpwaf-al-preset-title">' + esc(typeName(p.type)) + (p.pro ? ' <span class="wpwaf-al-pill wpwaf-al-pill--pro">Pro+</span>' : '') + '</span>' +
				'<span class="wpwaf-al-preset-desc">' + esc(p.desc) + '</span>' +
			'</button>').join('')
			: '<div class="wpwaf-al-empty" style="grid-column:1/-1;">' + (available.length ? 'None of the WordPress presets are available on this account. Use the list below.' : 'No alert types were returned for this account.') + '</div>';

		const other = available.filter(a => !PRESETS.some(p => p.type === a.type));
		const row = qs('#wpwaf-al-other-row');
		if (!other.length){ row.style.display = 'none'; return; }
		const groups = {};
		other.forEach(a => { (groups[a.category || 'Other'] = groups[a.category || 'Other'] || []).push(a); });
		qs('#wpwaf-al-other').innerHTML = '<option value="">Other alert types&hellip;</option>' +
			Object.keys(groups).sort().map(g => '<optgroup label="' + esc(g) + '">' +
				groups[g].sort((x, y) => x.display_name.localeCompare(y.display_name)).map(a => '<option value="' + esc(a.type) + '">' + esc(a.display_name) + '</option>').join('') +
			'</optgroup>').join('');
		row.style.display = '';
	}
	qs('#wpwaf-al-presets').addEventListener('click', e => {
		const b = e.target.closest('.wpwaf-al-preset');
		if (b) openPresetByType(b.dataset.type);
	});
	qs('#wpwaf-al-other-go').addEventListener('click', () => {
		const v = qs('#wpwaf-al-other').value;
		if (v) openPresetByType(v); else toast('Choose an alert type first', false);
	});

	// ── Filters from Cloudflare's filter_options ───────────────────────────
	// filter_options describes which filters an alert type takes. Parse it
	// defensively; when it cannot be read, no extra filters are offered.
	function parseFilterOptions(fo){
		if (!Array.isArray(fo)) return [];
		return fo.map(o => {
			if (!o || typeof o !== 'object') return null;
			const key = o.Key || o.key;
			if (typeof key !== 'string' || !key) return null;
			const optional = (o.Optional !== undefined ? o.Optional : (o.optional !== undefined ? o.optional : true));
			const raw = o.AvailableValues || o.available_values || null;
			const values = Array.isArray(raw) ? raw.map(v => {
				if (typeof v === 'string') return { id:v, label:v };
				if (v && typeof v === 'object'){ const id = v.ID || v.id; return id ? { id:String(id), label:String(v.Description || v.description || id) } : null; }
				return null;
			}).filter(Boolean) : [];
			return { key, optional: !!optional, values };
		}).filter(Boolean);
	}
	function filterLabel(key){
		const map = { zones:'Zones', health_check_id:'Health checks', insight_class:'Insight types', new_health:'Notify when', status:'Status', services:'Services', actions:'Actions', target_hostname:'Hostnames' };
		return map[key] || key.replace(/_/g, ' ').replace(/^./, c => c.toUpperCase());
	}
	function allHealthChecks(){
		const out = [];
		Object.keys(hcCache).forEach(zid => { const z = zoneById(zid); (hcCache[zid] || []).forEach(c => out.push({ id:c.id, label:c.name + (z ? ' (' + z.name + ')' : '') })); });
		return out;
	}
	function renderFilters(type){
		let defs = parseFilterOptions(type.filter_options);
		if (type.type === 'health_check_status_notification' && !defs.some(d => d.key === 'health_check_id')){
			defs = [{ key:'health_check_id', optional:true, values:[] }].concat(defs);
		}
		const box = qs('#wpwaf-al-f-filters');
		if (!defs.length){ box.innerHTML = ''; box.style.display = 'none'; return; }
		box.style.display = '';
		box.innerHTML = defs.map(d => {
			const req = d.optional ? '' : ' <span class="wpwaf-al-req">*</span>';
			let ctrl;
			if (d.key === 'zones'){
				const zs = accountZones();
				ctrl = zs.length ? '<div class="wpwaf-al-checks">' + zs.map(z =>
					'<label class="wpwaf-al-check"><input type="checkbox" value="' + esc(z.id) + '"> ' + esc(z.name) + ' <span class="wpwaf-al-meta">' + esc(planLabel(z.plan)) + '</span></label>').join('') + '</div>'
					: '<div class="wpwaf-al-hint">No zones loaded.</div>';
			} else if (d.key === 'health_check_id'){
				const hcs = allHealthChecks();
				ctrl = (hcs.length ? '<div class="wpwaf-al-checks">' + hcs.map(c =>
					'<label class="wpwaf-al-check"><input type="checkbox" value="' + esc(c.id) + '"' + (pendingHealthIds.includes(c.id) ? ' checked' : '') + '> ' + esc(c.label) + '</label>').join('') + '</div>'
					: '<div class="wpwaf-al-hint">Open the Health Checks tab and pick a zone to list its checks here, or paste check IDs below.</div>') +
					'<input type="text" class="wpwaf-al-input wpwaf-al-filter-extra" placeholder="Other health check IDs, comma separated" value="' + esc(pendingHealthIds.filter(id => !hcs.some(c => c.id === id)).join(', ')) + '">';
			} else if (d.values.length){
				ctrl = '<div class="wpwaf-al-checks">' + d.values.map(v =>
					'<label class="wpwaf-al-check"><input type="checkbox" value="' + esc(v.id) + '"> ' + esc(v.label) + '</label>').join('') + '</div>';
			} else {
				ctrl = '<input type="text" class="wpwaf-al-input wpwaf-al-filter-extra" placeholder="Comma separated values">';
			}
			return '<div class="wpwaf-al-field wpwaf-al-filter" data-key="' + esc(d.key) + '" data-required="' + (d.optional ? '0' : '1') + '" style="margin-bottom:10px;">' +
				'<span class="wpwaf-al-field-label">' + esc(filterLabel(d.key)) + req + '</span>' + ctrl + '</div>';
		}).join('');
	}
	function collectFilters(){
		const filters = {}; let missing = '';
		qsa('.wpwaf-al-filter', qs('#wpwaf-al-f-filters')).forEach(f => {
			const vals = qsa('input[type=checkbox]:checked', f).map(i => i.value);
			const extra = qs('.wpwaf-al-filter-extra', f);
			if (extra && extra.value.trim()) extra.value.split(',').map(s => s.trim()).filter(Boolean).forEach(v => vals.push(v));
			if (vals.length) filters[f.dataset.key] = Array.from(new Set(vals));
			else if (f.dataset.required === '1' && !missing) missing = filterLabel(f.dataset.key);
		});
		return { filters, missing };
	}

	// ── Alert form ──────────────────────────────────────────────────────────
	function renderWebhookChoices(){
		const box = qs('#wpwaf-al-f-webhooks');
		box.innerHTML = webhooks.length ? webhooks.map(w =>
			'<label class="wpwaf-al-check"><input type="checkbox" value="' + esc(w.id) + '"> ' + esc(w.name || w.url) + '</label>').join('')
			: '<span class="wpwaf-al-hint">No webhooks on this account.</span>';
	}
	function openPresetByType(type){
		const t = available.find(a => a.type === type);
		if (!t){ toast('That alert type is not available on this account', false); pendingHealthIds = []; return; }
		formType = t;
		const preset = PRESETS.find(p => p.type === type);
		qsa('.wpwaf-al-preset').forEach(b => b.classList.toggle('active', b.dataset.type === type));
		qs('#wpwaf-al-f-name').value = (preset ? preset.name : t.display_name) + ' alert';
		qs('#wpwaf-al-f-type').textContent = t.display_name;
		qs('#wpwaf-al-f-typedesc').textContent = t.description || (preset ? preset.desc : '');
		qs('#wpwaf-al-f-desc').value = '';
		qs('#wpwaf-al-f-enabled').checked = true;
		renderFilters(t);
		renderWebhookChoices();
		qs('#wpwaf-al-form').classList.add('open');
		pendingHealthIds = [];
		qs('#wpwaf-al-add-section').scrollIntoView({ behavior:'smooth', block:'start' });
	}
	function closeForm(){
		formType = null;
		qs('#wpwaf-al-form').classList.remove('open');
		qsa('.wpwaf-al-preset').forEach(b => b.classList.remove('active'));
	}
	qs('#wpwaf-al-f-cancel').addEventListener('click', closeForm);
	qs('#wpwaf-al-form').addEventListener('submit', e => {
		e.preventDefault();
		if (!formType) return;
		const name = qs('#wpwaf-al-f-name').value.trim();
		if (!name){ toast('Give the alert a name', false); return; }
		const emails = qs('#wpwaf-al-f-emails').value.split(/[\s,;]+/).map(s => s.trim()).filter(Boolean);
		const bad = emails.find(m => !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(m));
		if (bad){ toast('Check this email address: ' + bad, false); return; }
		const hooks = qsa('#wpwaf-al-f-webhooks input:checked').map(i => i.value);
		if (!emails.length && !hooks.length){ toast('Add an email address or pick a webhook', false); return; }
		const fl = collectFilters();
		if (fl.missing){ toast('Cloudflare requires: ' + fl.missing, false); return; }
		const policy = {
			name, alert_type: formType.type, enabled: qs('#wpwaf-al-f-enabled').checked,
			description: qs('#wpwaf-al-f-desc').value.trim(),
			mechanisms: { email: emails.map(id => ({ id })), webhooks: hooks.map(id => ({ id })) },
			filters: fl.filters
		};
		const btn = qs('#wpwaf-al-f-save');
		btn.disabled = true; btn.textContent = 'Creating…';
		ajax('wpwaf_al_policy_save', { account_id: accountId, policy: JSON.stringify(policy) }, res => {
			btn.disabled = false; btn.textContent = 'Create alert';
			if (res.success){ toast('Alert created'); closeForm(); loadAlerts(); }
			else toast('Failed: ' + errMsg(res), false);
		});
	});

	// ── Webhooks ────────────────────────────────────────────────────────────
	// Webhook URLs (Slack, Teams) often embed a secret token, so only the host and the end are shown.
	function maskUrl(u){
		const m = String(u || '').match(/^(https?:\/\/[^\/]+)(\/.*)?$/i);
		if (!m) return u || '';
		const path = m[2] || '';
		return path.length > 12 ? m[1] + '/\u2026' + path.slice(-6) : m[1] + path;
	}
	function renderWebhooks(){
		const box = qs('#wpwaf-al-webhooks');
		if (!webhooks.length){ box.innerHTML = '<div class="wpwaf-al-empty">No webhooks yet.</div>'; return; }
		box.innerHTML = '<table class="wpwaf-al-table"><thead><tr><th>Name</th><th>URL</th><th>Last delivery</th></tr></thead><tbody>' +
			webhooks.map(w => {
				const ok = w.last_success && (!w.last_failure || new Date(w.last_success) >= new Date(w.last_failure));
				const last = w.last_success || w.last_failure;
				const pill = !last ? '<span class="wpwaf-al-pill wpwaf-al-pill--off">Never used</span>'
					: (ok ? '<span class="wpwaf-al-pill wpwaf-al-pill--ok">Delivered</span>' : '<span class="wpwaf-al-pill wpwaf-al-pill--bad">Failed</span>');
				return '<tr><td data-label="Name"><div class="wpwaf-al-name">' + esc(w.name) + '</div>' + (w.type ? '<div class="wpwaf-al-meta">' + esc(w.type) + '</div>' : '') + '</td>' +
					'<td data-label="URL"><div class="wpwaf-al-meta" style="font-size:12px;" title="Shortened because webhook URLs often contain a secret token">' + esc(maskUrl(w.url)) + '</div></td>' +
					'<td data-label="Last delivery">' + pill + (last ? '<div class="wpwaf-al-meta">' + esc(fmtDate(last)) + '</div>' : '') + '</td></tr>';
			}).join('') + '</tbody></table>';
	}
	qs('#wpwaf-al-wh-toggle').addEventListener('click', function(){
		const f = qs('#wpwaf-al-wh-form');
		const open = f.style.display === 'none';
		f.style.display = open ? '' : 'none';
		this.textContent = open ? 'Cancel' : '+ Add webhook';
	});
	qs('#wpwaf-al-wh-form').addEventListener('submit', e => {
		e.preventDefault();
		const name = qs('#wpwaf-al-wh-name').value.trim(), url = qs('#wpwaf-al-wh-url').value.trim();
		if (!name || !/^https?:\/\//i.test(url)){ toast('Enter a name and a full URL', false); return; }
		const btn = qs('#wpwaf-al-wh-save');
		btn.disabled = true;
		ajax('wpwaf_al_webhook_create', { account_id: accountId, name, url, secret: qs('#wpwaf-al-wh-secret').value }, res => {
			btn.disabled = false;
			if (res.success){
				toast('Webhook saved');
				qs('#wpwaf-al-wh-form').reset();
				qs('#wpwaf-al-wh-form').style.display = 'none';
				qs('#wpwaf-al-wh-toggle').textContent = '+ Add webhook';
				loadAlerts();
			} else toast('Failed: ' + errMsg(res), false);
		});
	});

	// ═══ Health Checks ══════════════════════════════════════════════════════
	let hcZone = '';
	let editingId = '';

	qs('#wpwaf-al-hc-regions').innerHTML = REGIONS.map(([code, label]) =>
		'<label class="wpwaf-al-check"><input type="checkbox" value="' + code + '"> ' + esc(label) + '</label>').join('');

	function pickFirstPaidZone(){
		const z = zones.find(x => !isFree(x.plan)) || zones[0];
		if (z){ qs('#wpwaf-al-zone').value = z.id; selectZone(z.id); }
	}
	qs('#wpwaf-al-zone').addEventListener('change', function(){ selectZone(this.value); });
	qs('#wpwaf-al-hc-refresh').addEventListener('click', () => { if (hcZone) loadChecks(); });

	function selectZone(id){
		hcZone = id;
		closeHcForm();
		const z = zoneById(id);
		const msg = qs('#wpwaf-al-hc-msg');
		msg.innerHTML = '';
		qs('#wpwaf-al-hc-count').textContent = '';
		qs('#wpwaf-al-hc-add').disabled = true;
		if (!z){ qs('#wpwaf-al-hc-list').innerHTML = '<div class="wpwaf-al-empty">Pick a zone to see its checks.</div>'; qs('#wpwaf-al-hc-sub').textContent = 'Pick a zone to see its checks'; return; }
		qs('#wpwaf-al-hc-sub').textContent = z.name + ' · ' + planLabel(z.plan) + ' plan';
		if (isFree(z.plan)){
			qs('#wpwaf-al-hc-list').innerHTML = '<div class="wpwaf-al-empty">Health Checks are not available on Free zones.</div>';
			msg.innerHTML = '<div class="wpwaf-al-notice"><strong>' + esc(z.name) + ' is on the Free plan.</strong> Cloudflare Health Checks need a Pro plan or higher on the zone. Upgrade the zone in Cloudflare, or pick a Pro, Business or Enterprise zone.</div>';
			return;
		}
		qs('#wpwaf-al-hc-add').disabled = false;
		loadChecks();
	}

	function loadChecks(){
		const zid = hcZone;
		qs('#wpwaf-al-hc-list').innerHTML = '<div class="wpwaf-al-loading"><span class="wpwaf-al-spinner"></span> Loading health checks&hellip;</div>';
		ajax('wpwaf_al_hc_list', { zone_id: zid }, res => {
			if (zid !== hcZone) return;
			if (!res.success){
				qs('#wpwaf-al-hc-list').innerHTML = '<div class="wpwaf-al-empty">Health checks could not be loaded.</div>';
				qs('#wpwaf-al-hc-msg').innerHTML = '<div class="wpwaf-al-notice wpwaf-al-notice--error">' + esc(errMsg(res)) + '</div>';
				return;
			}
			hcCache[zid] = Array.isArray(res.data.result) ? res.data.result : [];
			renderChecks();
		});
	}

	function statusPill(c){
		const s = c.suspended ? 'suspended' : (c.status || 'unknown');
		const cls = { healthy:'ok', unhealthy:'bad', suspended:'off', unknown:'off' }[s] || 'off';
		const label = { healthy:'Healthy', unhealthy:'Unhealthy', suspended:'Paused', unknown:'Waiting for first check' }[s] || s;
		return '<span class="wpwaf-al-pill wpwaf-al-pill--' + cls + '">' + esc(label) + '</span>';
	}

	function renderChecks(){
		const list = hcCache[hcZone] || [];
		qs('#wpwaf-al-hc-count').textContent = list.length + ' check' + (list.length !== 1 ? 's' : '');
		const box = qs('#wpwaf-al-hc-list');
		if (!list.length){ box.innerHTML = '<div class="wpwaf-al-empty">No health checks on this zone yet. Use <strong>+ Add health check</strong> to monitor your home page.</div>'; return; }
		box.innerHTML = '<table class="wpwaf-al-table"><thead><tr><th>Check</th><th>Status</th><th>Schedule</th><th></th></tr></thead><tbody>' +
			list.map(c => {
				const http = c.http_config || {};
				const target = (c.type || 'HTTP') + ' ' + c.address + (c.type !== 'TCP' ? (http.path || '/') : '');
				const regions = (c.check_regions || []).map(r => { const f = REGIONS.find(x => x[0] === r); return f ? f[1] : r; }).join(', ') || 'Cloudflare default';
				return '<tr><td data-label="Check"><div class="wpwaf-al-name">' + esc(c.name) + '</div><div class="wpwaf-al-meta">' + esc(target) + '</div></td>' +
					'<td data-label="Status">' + statusPill(c) + (c.status === 'unhealthy' && c.failure_reason ? '<div class="wpwaf-al-meta" style="color:#991b1b;">' + esc(c.failure_reason) + '</div>' : '') + '</td>' +
					'<td data-label="Schedule"><div class="wpwaf-al-meta">Every ' + esc(c.interval || '?') + 's &middot; ' + esc(regions) + '</div>' + (c.modified_on ? '<div class="wpwaf-al-meta">Changed ' + esc(fmtDate(c.modified_on)) + '</div>' : '') + '</td>' +
					'<td><div class="wpwaf-al-actions">' +
						'<button type="button" class="wpwaf-al-btn wpwaf-al-btn--sm wpwaf-al-hc-alert" data-id="' + esc(c.id) + '">&#128276; Alert me</button>' +
						'<button type="button" class="wpwaf-al-btn wpwaf-al-btn--sm wpwaf-al-hc-pause" data-id="' + esc(c.id) + '" data-suspended="' + (c.suspended ? '1' : '0') + '">' + (c.suspended ? 'Resume' : 'Pause') + '</button>' +
						'<button type="button" class="wpwaf-al-btn wpwaf-al-btn--sm wpwaf-al-hc-edit" data-id="' + esc(c.id) + '">Edit</button>' +
						'<button type="button" class="wpwaf-al-btn wpwaf-al-btn--sm wpwaf-al-btn--danger wpwaf-al-hc-del" data-id="' + esc(c.id) + '">Delete</button>' +
					'</div></td></tr>';
			}).join('') + '</tbody></table>';
	}

	qs('#wpwaf-al-hc-list').addEventListener('click', e => {
		const b = e.target.closest('button');
		if (!b || !b.dataset.id) return;
		const c = (hcCache[hcZone] || []).find(x => x.id === b.dataset.id);
		if (b.classList.contains('wpwaf-al-hc-alert')){
			pendingHealthIds = [b.dataset.id];
			showTab('alerts');
			if (available.length) openPresetByType('health_check_status_notification');
			return;
		}
		if (b.classList.contains('wpwaf-al-hc-edit')){ if (c) openHcForm(c); return; }
		if (b.classList.contains('wpwaf-al-hc-pause')){
			const pause = b.dataset.suspended !== '1';
			b.disabled = true;
			ajax('wpwaf_al_hc_toggle', { zone_id: hcZone, healthcheck_id: b.dataset.id, suspended: pause ? '1' : '0' }, res => {
				b.disabled = false;
				if (res.success){ toast(pause ? 'Health check paused' : 'Health check resumed'); loadChecks(); }
				else toast('Failed: ' + errMsg(res), false);
			});
			return;
		}
		if (b.classList.contains('wpwaf-al-hc-del')){
			if (!confirm('Delete the health check "' + (c ? c.name : '') + '"? Alerts tied to it will stop firing.')) return;
			b.disabled = true;
			ajax('wpwaf_al_hc_delete', { zone_id: hcZone, healthcheck_id: b.dataset.id }, res => {
				if (res.success){ toast('Health check deleted'); loadChecks(); }
				else { b.disabled = false; toast('Failed: ' + errMsg(res), false); }
			});
		}
	});

	function syncTypeFields(){
		const tcp = qs('#wpwaf-al-hc-type').value === 'TCP';
		qsa('.wpwaf-al-hc-http').forEach(el => el.style.display = tcp ? 'none' : '');
	}
	qs('#wpwaf-al-hc-type').addEventListener('change', syncTypeFields);

	function openHcForm(c){
		const z = zoneById(hcZone);
		editingId = c ? c.id : '';
		const http = (c && c.http_config) || {};
		const host = http.header && Array.isArray(http.header.Host) ? http.header.Host[0] : '';
		qs('#wpwaf-al-hc-form-title').textContent = c ? 'Edit health check' : 'Add health check';
		qs('#wpwaf-al-hc-save').textContent = c ? 'Save changes' : 'Create health check';
		qs('#wpwaf-al-hc-name').value = c ? c.name : 'wordpress-home';
		qs('#wpwaf-al-hc-address').value = c ? c.address : '';
		qs('#wpwaf-al-hc-type').value = c ? (c.type || 'HTTPS') : 'HTTPS';
		qs('#wpwaf-al-hc-path').value = c ? (http.path || '/') : '/';
		qs('#wpwaf-al-hc-method').value = c ? (http.method || 'GET') : 'GET';
		qs('#wpwaf-al-hc-codes').value = c ? (http.expected_codes || ['200']).join(', ') : '200';
		qs('#wpwaf-al-hc-host').value = c ? host : (z ? z.name : '');
		qs('#wpwaf-al-hc-body').value = c ? (http.expected_body || '') : '';
		qs('#wpwaf-al-hc-port').value = c ? ((c.type === 'TCP' ? (c.tcp_config || {}).port : http.port) || '') : '';
		qs('#wpwaf-al-hc-interval').value = c ? (c.interval || '') : 60;
		qs('#wpwaf-al-hc-retries').value = c ? (c.retries != null ? c.retries : '') : 2;
		qs('#wpwaf-al-hc-timeout').value = c ? (c.timeout || '') : 5;
		qs('#wpwaf-al-hc-follow').checked = c ? !!http.follow_redirects : true;
		qs('#wpwaf-al-hc-insecure').checked = c ? !!http.allow_insecure : false;
		qs('#wpwaf-al-hc-suspended').checked = c ? !!c.suspended : false;
		qs('#wpwaf-al-hc-suspended').parentNode.lastChild.textContent = c ? ' Paused' : ' Create paused';
		const regs = (c && c.check_regions) || [];
		qsa('#wpwaf-al-hc-regions input').forEach(i => { i.checked = regs.includes(i.value); });
		syncTypeFields();
		qs('#wpwaf-al-hc-form-section').style.display = '';
		qs('#wpwaf-al-hc-form-section').scrollIntoView({ behavior:'smooth', block:'start' });
	}
	function closeHcForm(){ editingId = ''; qs('#wpwaf-al-hc-form-section').style.display = 'none'; }
	qs('#wpwaf-al-hc-add').addEventListener('click', () => { if (hcZone) openHcForm(null); });
	qs('#wpwaf-al-hc-cancel').addEventListener('click', closeHcForm);

	qs('#wpwaf-al-hc-form').addEventListener('submit', e => {
		e.preventDefault();
		const name = qs('#wpwaf-al-hc-name').value.trim();
		if (!/^[A-Za-z0-9_-]+$/.test(name)){ toast('Name: letters, numbers, hyphens and underscores only', false); return; }
		const address = qs('#wpwaf-al-hc-address').value.trim();
		if (!address){ toast('Enter the origin address', false); return; }
		const check = {
			name, address,
			type: qs('#wpwaf-al-hc-type').value,
			path: qs('#wpwaf-al-hc-path').value.trim() || '/',
			method: qs('#wpwaf-al-hc-method').value,
			expected_codes: qs('#wpwaf-al-hc-codes').value.split(',').map(s => s.trim()).filter(Boolean),
			host_header: qs('#wpwaf-al-hc-host').value.trim(),
			expected_body: qs('#wpwaf-al-hc-body').value.trim(),
			port: qs('#wpwaf-al-hc-port').value,
			interval: qs('#wpwaf-al-hc-interval').value,
			retries: qs('#wpwaf-al-hc-retries').value,
			timeout: qs('#wpwaf-al-hc-timeout').value,
			follow_redirects: qs('#wpwaf-al-hc-follow').checked,
			allow_insecure: qs('#wpwaf-al-hc-insecure').checked,
			suspended: qs('#wpwaf-al-hc-suspended').checked,
			check_regions: qsa('#wpwaf-al-hc-regions input:checked').map(i => i.value)
		};
		const btn = qs('#wpwaf-al-hc-save');
		const label = btn.textContent;
		btn.disabled = true; btn.textContent = 'Saving…';
		ajax('wpwaf_al_hc_save', { zone_id: hcZone, healthcheck_id: editingId, check: JSON.stringify(check) }, res => {
			btn.disabled = false; btn.textContent = label;
			if (res.success){ toast(editingId ? 'Health check updated' : 'Health check created'); closeHcForm(); loadChecks(); }
			else toast('Failed: ' + errMsg(res), false);
		});
	});

	// ── Start ───────────────────────────────────────────────────────────────
	loadAccounts();
	ensureZones();
})();
</script>
