<?php
defined( 'ABSPATH' ) || exit;

$wpwaf_rp_user = wp_get_current_user();
$wpwaf_rp_boot = [
	'state'      => WPWAF_Reports::state(),
	'branding'   => WPWAF_Reports::branding(),
	'accounts'   => array_map( static fn( $a ) => [ 'id' => $a['id'], 'label' => $a['label'] ], WPWAF_Accounts::for_js() ),
	'active_id'  => WPWAF_Accounts::active_id(),
	'user_email' => (string) ( $wpwaf_rp_user->user_email ?? '' ),
	'timezone'   => function_exists( 'wp_timezone_string' ) ? wp_timezone_string() : 'UTC',
	'ranges'     => WPWAF_Analytics::RANGES,
	'sections'   => WPWAF_Reports::SECTIONS,
	'has_creds'  => (bool) $has_creds,
];
?>
<style>
:root{--rp-orange:#FF6A00;--rp-border:#e2e6ea;--rp-bg:#f8f9fb;--rp-dark:#1a1a2e;--rp-muted:#6b7280;--rp-green:#059669;--rp-red:#dc2626;}
.wpwaf-rp-wrap{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;color:var(--rp-dark);}
.wpwaf-rp-wrap *{box-sizing:border-box;}
.wpwaf-rp-header{display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:20px;}
.wpwaf-rp-header h1{font-size:22px;font-weight:700;margin:0;padding:0;display:flex;align-items:center;gap:8px;color:var(--rp-dark);line-height:1.3;}
.wpwaf-rp-header h1 .dashicons{color:var(--rp-orange);font-size:24px;width:24px;height:24px;}
.wpwaf-rp-header-sub{font-size:12px;color:var(--rp-muted);margin-top:3px;}
.wpwaf-rp-notice{background:#fff8f5;border:1px solid #fcd9c0;border-radius:8px;padding:14px 18px;color:#92400e;font-size:13px;line-height:1.6;margin-bottom:16px;}

/* Cards */
.wpwaf-rp-card{background:#fff;border:1px solid var(--rp-border);border-radius:10px;margin-bottom:20px;overflow:hidden;}
.wpwaf-rp-card-head{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;padding:14px 18px;border-bottom:1px solid var(--rp-border);}
.wpwaf-rp-card-head h2{margin:0;font-size:15px;font-weight:700;display:flex;align-items:center;gap:8px;color:var(--rp-dark);}
.wpwaf-rp-card-sub{font-size:12px;color:var(--rp-muted);margin-top:2px;font-weight:400;}
.wpwaf-rp-card-body{padding:16px 18px;}

/* Buttons and inputs */
.wpwaf-rp-btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;height:32px;padding:0 14px;font-size:12px;font-weight:600;border-radius:6px;border:1px solid #d1d5db;background:#fff;color:#374151;cursor:pointer;transition:background .15s,border-color .15s;white-space:nowrap;text-decoration:none;}
.wpwaf-rp-btn:hover{background:#f3f4f6;border-color:#9ca3af;}
.wpwaf-rp-btn--primary{background:var(--rp-orange);border-color:var(--rp-orange);color:#fff;}
.wpwaf-rp-btn--primary:hover{background:#e05c00;border-color:#e05c00;color:#fff;}
.wpwaf-rp-btn--danger:hover{background:#fee2e2;border-color:#fca5a5;color:#b91c1c;}
.wpwaf-rp-btn--sm{height:28px;padding:0 10px;font-size:11px;}
.wpwaf-rp-btn:disabled{opacity:.5;cursor:not-allowed;}
.wpwaf-rp-actions{display:flex;flex-wrap:wrap;gap:6px;}
.wpwaf-rp-input,.wpwaf-rp-select,.wpwaf-rp-textarea{width:100%;max-width:100%;padding:7px 10px;border:1px solid var(--rp-border);border-radius:6px;font-size:13px;color:var(--rp-dark);background:#fff;min-height:34px;}
.wpwaf-rp-textarea{resize:vertical;min-height:72px;line-height:1.5;font-family:inherit;}
.wpwaf-rp-input:focus,.wpwaf-rp-select:focus,.wpwaf-rp-textarea:focus{outline:none;border-color:var(--rp-orange);box-shadow:0 0 0 2px rgba(255,106,0,.1);}
.wpwaf-rp-field{margin-bottom:14px;min-width:0;}
.wpwaf-rp-field>label,.wpwaf-rp-label{display:block;font-size:12px;font-weight:600;margin-bottom:4px;}
.wpwaf-rp-hint{font-size:11px;color:var(--rp-muted);margin-top:4px;line-height:1.5;}
.wpwaf-rp-grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:0 18px;}
.wpwaf-rp-grid3{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:0 12px;}
.wpwaf-rp-check{display:inline-flex;align-items:center;gap:7px;font-size:13px;cursor:pointer;line-height:1.4;}
.wpwaf-rp-check input{margin:0;accent-color:var(--rp-orange);width:15px;height:15px;flex-shrink:0;}
.wpwaf-rp-checks{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:8px 14px;}
.wpwaf-rp-color{display:flex;gap:8px;align-items:center;}
.wpwaf-rp-color input[type=color]{width:42px;height:34px;padding:2px;border:1px solid var(--rp-border);border-radius:6px;background:#fff;cursor:pointer;flex-shrink:0;}
.wpwaf-rp-logo{display:flex;gap:8px;align-items:center;}
.wpwaf-rp-logo img{max-height:34px;max-width:120px;border:1px solid var(--rp-border);border-radius:4px;padding:2px;background:#fff;}

/* Zone picker */
.wpwaf-rp-zones{border:1px solid var(--rp-border);border-radius:8px;overflow:hidden;}
.wpwaf-rp-zones-bar{display:flex;gap:8px;align-items:center;padding:8px;border-bottom:1px solid var(--rp-border);background:var(--rp-bg);flex-wrap:wrap;}
.wpwaf-rp-zones-bar .wpwaf-rp-input{flex:1 1 180px;min-width:0;}
.wpwaf-rp-zones-count{font-size:12px;color:var(--rp-muted);white-space:nowrap;}
.wpwaf-rp-zones-list{max-height:220px;overflow-y:auto;padding:6px 8px;display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:2px 12px;}
.wpwaf-rp-zone{display:flex;align-items:center;gap:7px;padding:5px 4px;font-size:13px;cursor:pointer;border-radius:5px;min-width:0;}
.wpwaf-rp-zone:hover{background:var(--rp-bg);}
.wpwaf-rp-zone input{margin:0;accent-color:var(--rp-orange);flex-shrink:0;}
.wpwaf-rp-zone span{overflow-wrap:anywhere;min-width:0;}
.wpwaf-rp-zone small{color:var(--rp-muted);font-size:10px;margin-left:auto;white-space:nowrap;}

/* Report list */
.wpwaf-rp-table{width:100%;border-collapse:collapse;}
.wpwaf-rp-table th{padding:9px 12px;font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.06em;text-align:left;border-bottom:1px solid var(--rp-border);background:#f8f9fa;}
.wpwaf-rp-table td{padding:12px;font-size:13px;color:#374151;border-bottom:1px solid #f1f3f5;vertical-align:top;}
.wpwaf-rp-table tbody tr:last-child td{border-bottom:none;}
.wpwaf-rp-name{font-weight:700;color:#111;overflow-wrap:break-word;}
.wpwaf-rp-sub{font-size:11px;color:var(--rp-muted);margin-top:3px;line-height:1.5;overflow-wrap:break-word;}
.wpwaf-rp-pill{display:inline-flex;align-items:center;gap:5px;padding:2px 9px;border-radius:20px;font-size:11px;font-weight:700;white-space:nowrap;}
.wpwaf-rp-pill--on{background:#d1fae5;color:#065f46;}
.wpwaf-rp-pill--off{background:#f3f4f6;color:#6b7280;}
.wpwaf-rp-pill--err{background:#fee2e2;color:#991b1b;}
.wpwaf-rp-status-ok{color:var(--rp-green);}
.wpwaf-rp-status-err{color:var(--rp-red);}
.wpwaf-rp-table td.wpwaf-rp-td-actions{width:1%;}
.wpwaf-rp-td-actions .wpwaf-rp-actions{justify-content:flex-end;}
/* Desktop: the four client actions sit on one row. */
@media (min-width:1024px){ .wpwaf-rp-td-actions .wpwaf-rp-actions{flex-wrap:nowrap;} }
.wpwaf-rp-empty{text-align:center;padding:34px 16px;color:#6b7280;font-size:13px;line-height:1.6;}
.wpwaf-rp-empty strong{display:block;color:var(--rp-dark);font-size:14px;margin-bottom:4px;}

/* Editor */
.wpwaf-rp-editor{border-top:3px solid var(--rp-orange);}
.wpwaf-rp-subhead{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--rp-muted);margin:6px 0 10px;padding-top:10px;border-top:1px solid #f1f3f5;}
.wpwaf-rp-subhead:first-child{border-top:none;padding-top:0;margin-top:0;}
.wpwaf-rp-switch-row{display:flex;align-items:center;gap:10px;font-size:13px;font-weight:600;}
.wpwaf-rp-switch{position:relative;display:inline-block;width:42px;height:24px;flex-shrink:0;}
.wpwaf-rp-switch input{opacity:0;width:0;height:0;position:absolute;}
.wpwaf-rp-slider{position:absolute;cursor:pointer;inset:0;background:#d1d5db;border-radius:24px;transition:.2s;}
.wpwaf-rp-slider:before{position:absolute;content:"";height:18px;width:18px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.2s;box-shadow:0 1px 3px rgba(0,0,0,.2);}
.wpwaf-rp-switch input:checked+.wpwaf-rp-slider{background:var(--rp-orange);}
.wpwaf-rp-switch input:checked+.wpwaf-rp-slider:before{transform:translateX(18px);}
.wpwaf-rp-switch input:focus-visible+.wpwaf-rp-slider{box-shadow:0 0 0 2px rgba(255,106,0,.35);}
.wpwaf-rp-footer{display:flex;gap:8px;flex-wrap:wrap;align-items:center;padding-top:14px;border-top:1px solid #f1f3f5;}
.wpwaf-rp-footer .wpwaf-rp-hint{margin:0 0 0 auto;}

/* Log */
.wpwaf-rp-log td{font-size:12px;}

/* Modal */
.wpwaf-rp-modal{position:fixed;inset:0;background:rgba(17,24,39,.55);z-index:100000;display:none;align-items:flex-start;justify-content:center;padding:40px 16px;overflow-y:auto;}
.wpwaf-rp-modal.open{display:flex;}
.wpwaf-rp-modal-box{background:#fff;border-radius:12px;width:min(720px,100%);box-shadow:0 20px 60px rgba(0,0,0,.25);overflow:hidden;}
.wpwaf-rp-modal-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;padding:14px 18px;border-bottom:1px solid var(--rp-border);}
.wpwaf-rp-modal-title{font-size:14px;font-weight:700;overflow-wrap:anywhere;}
.wpwaf-rp-modal-meta{font-size:12px;color:var(--rp-muted);margin-top:2px;}
.wpwaf-rp-modal-close{background:none;border:none;font-size:22px;line-height:1;color:var(--rp-muted);cursor:pointer;padding:0 4px;}
.wpwaf-rp-modal-body{background:#f3f4f6;}
.wpwaf-rp-modal-body iframe{display:block;width:100%;height:min(72vh,900px);border:0;background:#f3f4f6;}
.wpwaf-rp-modal-foot{display:flex;gap:8px;flex-wrap:wrap;align-items:center;padding:12px 18px;border-top:1px solid var(--rp-border);}
.wpwaf-rp-modal-foot .wpwaf-rp-check{margin-right:auto;font-size:12px;}
.wpwaf-rp-sample{display:inline-block;margin-left:6px;padding:1px 8px;border-radius:20px;background:#fef3c7;color:#92400e;font-size:11px;font-weight:700;}

/* Send dialog */
.wpwaf-rp-modal-box--form{width:min(560px,100%);}
.wpwaf-rp-modal-form{padding:16px 18px 4px;background:#fff;}
.wpwaf-rp-recips{display:flex;flex-direction:column;gap:6px;max-height:180px;overflow-y:auto;padding:8px 10px;border:1px solid var(--rp-border);border-radius:8px;}
.wpwaf-rp-recips .wpwaf-rp-check{overflow-wrap:anywhere;}
.wpwaf-rp-notes{font-size:11px;color:var(--rp-muted);margin-top:4px;white-space:pre-line;overflow-wrap:anywhere;}

/* Spinner + toast */
.wpwaf-rp-spinner{display:inline-block;width:12px;height:12px;border:2px solid rgba(255,106,0,.3);border-top-color:var(--rp-orange);border-radius:50%;animation:wpwaf-rp-spin .6s linear infinite;vertical-align:-2px;}
@keyframes wpwaf-rp-spin{to{transform:rotate(360deg);}}
.wpwaf-rp-loading{padding:24px;text-align:center;color:var(--rp-muted);font-size:13px;}
.wpwaf-rp-toast{position:fixed;bottom:24px;right:24px;max-width:min(420px,calc(100vw - 32px));padding:10px 16px;border-radius:6px;font-size:13px;font-weight:600;color:#fff;z-index:100001;box-shadow:0 6px 18px rgba(0,0,0,.15);line-height:1.5;}
.wpwaf-rp-toast.ok{background:var(--rp-green);}.wpwaf-rp-toast.err{background:var(--rp-red);}

@media (max-width:1100px){
  .wpwaf-rp-grid3{grid-template-columns:repeat(2,minmax(0,1fr));}
}
@media (max-width:782px){
  .wpwaf-rp-card-head,.wpwaf-rp-card-body{padding-left:14px;padding-right:14px;}
  .wpwaf-rp-grid{grid-template-columns:minmax(0,1fr);}
  .wpwaf-rp-table thead{display:none;}
  .wpwaf-rp-table,.wpwaf-rp-table tbody,.wpwaf-rp-table tr,.wpwaf-rp-table td{display:block;width:100%;}
  .wpwaf-rp-table tr{border-bottom:1px solid var(--rp-border);padding:8px 0;}
  .wpwaf-rp-table tbody tr:last-child{border-bottom:none;}
  .wpwaf-rp-table td{border-bottom:none;padding:5px 2px;}
  .wpwaf-rp-table td[data-label]:before{content:attr(data-label);display:block;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#9ca3af;margin-bottom:2px;}
  .wpwaf-rp-table td.wpwaf-rp-td-actions{width:100%;}
  .wpwaf-rp-td-actions .wpwaf-rp-actions{justify-content:flex-start;flex-wrap:wrap;}
  .wpwaf-rp-footer .wpwaf-rp-hint{margin:6px 0 0;flex-basis:100%;}
  .wpwaf-rp-modal{padding:12px 8px;}
  .wpwaf-rp-toast{left:16px;right:16px;bottom:16px;max-width:none;}
}
@media (max-width:480px){
  .wpwaf-rp-grid3{grid-template-columns:minmax(0,1fr);}
  .wpwaf-rp-card-head .wpwaf-rp-btn{width:100%;}
  .wpwaf-rp-footer .wpwaf-rp-btn{flex:1 1 auto;}
  .wpwaf-rp-zones-list{grid-template-columns:minmax(0,1fr);}
}
</style>

<div class="wpwaf-rp-wrap">
	<div class="wpwaf-rp-header">
		<div>
			<h1><span class="dashicons dashicons-groups"></span> Clients &amp; Reports</h1>
			<div class="wpwaf-rp-header-sub">Add your clients, link them to their sites, and send each one a white-label Cloudflare report by hand or automatically every month.</div>
		</div>
	</div>

	<?php if ( ! $has_creds ) : ?>
	<div class="wpwaf-rp-notice">
		<strong>No Cloudflare account connected.</strong> You can set up your branding and preview the email with sample data now. <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpwafmanager' ) ); ?>">Add your credentials</a> to link sites and send reports.
	</div>
	<?php endif; ?>

	<!-- Clients -->
	<div class="wpwaf-rp-card">
		<div class="wpwaf-rp-card-head">
			<div>
				<h2>👥 Clients</h2>
				<div class="wpwaf-rp-card-sub">Each client is linked to their sites. Times use the site's timezone (<span id="wpwaf-rp-tz"></span>).</div>
			</div>
			<button type="button" class="wpwaf-rp-btn wpwaf-rp-btn--primary" id="wpwaf-rp-new">+ Add client</button>
		</div>
		<div id="wpwaf-rp-list"></div>
	</div>

	<!-- Editor -->
	<div class="wpwaf-rp-card wpwaf-rp-editor" id="wpwaf-rp-editor" style="display:none;">
		<div class="wpwaf-rp-card-head"><h2 id="wpwaf-rp-editor-title">Add client</h2></div>
		<div class="wpwaf-rp-card-body">
			<div class="wpwaf-rp-subhead">Client</div>
			<div class="wpwaf-rp-grid">
				<div class="wpwaf-rp-field">
					<label for="wpwaf-rp-f-name">Client name</label>
					<input type="text" class="wpwaf-rp-input" id="wpwaf-rp-f-name" maxlength="120" placeholder="Northwind Outfitters">
					<div class="wpwaf-rp-hint">Shown in the report's subject line.</div>
				</div>
				<div class="wpwaf-rp-field">
					<label for="wpwaf-rp-f-contact">Contact name (optional)</label>
					<input type="text" class="wpwaf-rp-input" id="wpwaf-rp-f-contact" maxlength="120" placeholder="Sarah">
					<div class="wpwaf-rp-hint">The email greets this person ("Hi Sarah"). Empty uses the client name.</div>
				</div>
			</div>
			<div class="wpwaf-rp-grid">
				<div class="wpwaf-rp-field">
					<label for="wpwaf-rp-f-recipients">Email addresses</label>
					<textarea class="wpwaf-rp-textarea" id="wpwaf-rp-f-recipients" rows="2" placeholder="owner@client.com, manager@client.com"></textarea>
					<div class="wpwaf-rp-hint">Reports go to these addresses. Separate with commas or new lines. Up to 20.</div>
				</div>
				<div class="wpwaf-rp-field">
					<label for="wpwaf-rp-f-notes">Internal notes (optional)</label>
					<textarea class="wpwaf-rp-textarea" id="wpwaf-rp-f-notes" rows="2" maxlength="1000" placeholder="Plan, renewal date, who to call…"></textarea>
					<div class="wpwaf-rp-hint">Only you see these. They are never emailed.</div>
				</div>
			</div>

			<div class="wpwaf-rp-subhead">Sites</div>
			<div class="wpwaf-rp-field" id="wpwaf-rp-account-field" style="display:none;">
				<label for="wpwaf-rp-f-account">Cloudflare account</label>
				<select class="wpwaf-rp-select" id="wpwaf-rp-f-account"></select>
				<div class="wpwaf-rp-hint">Reports use this account's credentials. The zones below must belong to it. The list shows zones from the account that is active on the WAF Rules screen.</div>
			</div>
			<div class="wpwaf-rp-field">
				<span class="wpwaf-rp-label">Sites for this client</span>
				<div class="wpwaf-rp-zones">
					<div class="wpwaf-rp-zones-bar">
						<input type="search" class="wpwaf-rp-input" id="wpwaf-rp-zone-search" placeholder="Search sites…" aria-label="Search sites">
						<span class="wpwaf-rp-zones-count" id="wpwaf-rp-zone-count">0 selected</span>
					</div>
					<div class="wpwaf-rp-zones-list" id="wpwaf-rp-zone-list"><div class="wpwaf-rp-loading">Open the editor to load zones.</div></div>
				</div>
				<div class="wpwaf-rp-hint">A report covers all of the client's sites: an overview, then a section per site. Up to 20.</div>
			</div>

			<div class="wpwaf-rp-subhead">Monthly report</div>
			<div class="wpwaf-rp-field">
				<label class="wpwaf-rp-switch-row">
					<span class="wpwaf-rp-switch"><input type="checkbox" id="wpwaf-rp-f-monthly"><span class="wpwaf-rp-slider"></span></span>
					Send this client a report automatically every month
				</label>
				<div class="wpwaf-rp-hint">Automatic reports cover the last calendar month. You can also send a report by hand at any time with Send report.</div>
			</div>
			<div class="wpwaf-rp-grid3" id="wpwaf-rp-monthly-fields">
				<div class="wpwaf-rp-field" id="wpwaf-rp-monthday-field">
					<label for="wpwaf-rp-f-monthday">Day of the month</label>
					<select class="wpwaf-rp-select" id="wpwaf-rp-f-monthday"></select>
				</div>
				<div class="wpwaf-rp-field" id="wpwaf-rp-hour-field">
					<label for="wpwaf-rp-f-hour">Time</label>
					<select class="wpwaf-rp-select" id="wpwaf-rp-f-hour"></select>
				</div>
			</div>
			<div class="wpwaf-rp-hint" id="wpwaf-rp-auto-hint" style="margin:-6px 0 14px;">WordPress sends automatic emails when the site gets traffic, so a report can arrive a little after the chosen time on quiet sites.</div>

			<div class="wpwaf-rp-subhead">Report content</div>
			<div class="wpwaf-rp-field">
				<span class="wpwaf-rp-label">Sections</span>
				<div class="wpwaf-rp-checks" id="wpwaf-rp-f-sections"></div>
				<div class="wpwaf-rp-hint">Sections a zone's Cloudflare plan cannot provide are left out of the email automatically.</div>
			</div>
			<div class="wpwaf-rp-field">
				<label for="wpwaf-rp-f-intro">Default message (optional)</label>
				<textarea class="wpwaf-rp-textarea" id="wpwaf-rp-f-intro" rows="3" maxlength="2000" placeholder="Leave empty to use a short standard introduction."></textarea>
				<div class="wpwaf-rp-hint">Used for automatic reports and filled in when you send one by hand, where you can change it.</div>
			</div>

			<div class="wpwaf-rp-footer">
				<button type="button" class="wpwaf-rp-btn wpwaf-rp-btn--primary" id="wpwaf-rp-save">Save client</button>
				<button type="button" class="wpwaf-rp-btn" id="wpwaf-rp-preview-draft">Preview</button>
				<button type="button" class="wpwaf-rp-btn" id="wpwaf-rp-cancel">Cancel</button>
				<span class="wpwaf-rp-hint" id="wpwaf-rp-next-hint"></span>
			</div>
		</div>
	</div>

	<!-- Branding -->
	<div class="wpwaf-rp-card">
		<div class="wpwaf-rp-card-head">
			<div>
				<h2>🎨 Branding</h2>
				<div class="wpwaf-rp-card-sub">Every client report goes out under your agency's name. The plugin is never mentioned.</div>
			</div>
			<button type="button" class="wpwaf-rp-btn" id="wpwaf-rp-brand-preview">Preview with sample data</button>
		</div>
		<div class="wpwaf-rp-card-body">
			<div class="wpwaf-rp-grid">
				<div class="wpwaf-rp-field">
					<label for="wpwaf-rp-b-agency">Agency name</label>
					<input type="text" class="wpwaf-rp-input" id="wpwaf-rp-b-agency" maxlength="120">
				</div>
				<div class="wpwaf-rp-field">
					<label for="wpwaf-rp-b-color-text">Brand color</label>
					<div class="wpwaf-rp-color">
						<input type="color" id="wpwaf-rp-b-color" aria-label="Brand color picker">
						<input type="text" class="wpwaf-rp-input" id="wpwaf-rp-b-color-text" maxlength="7" placeholder="#FF6A00">
					</div>
					<div class="wpwaf-rp-hint">Used for the top bar, chart bars and accents.</div>
				</div>
			</div>
			<div class="wpwaf-rp-field">
				<label for="wpwaf-rp-b-logo">Logo</label>
				<div class="wpwaf-rp-logo">
					<input type="url" class="wpwaf-rp-input" id="wpwaf-rp-b-logo" placeholder="https://youragency.com/logo.png">
					<button type="button" class="wpwaf-rp-btn" id="wpwaf-rp-b-logo-pick">Choose</button>
					<span id="wpwaf-rp-b-logo-thumb"></span>
				</div>
				<div class="wpwaf-rp-hint">PNG or JPG works best in email. Shown 40px tall. Leave empty to show the agency name instead.</div>
			</div>
			<div class="wpwaf-rp-grid">
				<div class="wpwaf-rp-field">
					<label for="wpwaf-rp-b-fromname">From name</label>
					<input type="text" class="wpwaf-rp-input" id="wpwaf-rp-b-fromname" maxlength="120" placeholder="Defaults to the agency name">
				</div>
				<div class="wpwaf-rp-field">
					<label for="wpwaf-rp-b-fromemail">From email</label>
					<input type="email" class="wpwaf-rp-input" id="wpwaf-rp-b-fromemail" placeholder="reports@youragency.com">
					<div class="wpwaf-rp-hint">Use an address on a domain your site's mail setup is allowed to send for, or emails may land in spam. Empty uses the WordPress default.</div>
				</div>
				<div class="wpwaf-rp-field">
					<label for="wpwaf-rp-b-replyto">Reply-To</label>
					<input type="email" class="wpwaf-rp-input" id="wpwaf-rp-b-replyto" placeholder="hello@youragency.com">
				</div>
				<div class="wpwaf-rp-field">
					<label for="wpwaf-rp-b-footer">Footer text</label>
					<textarea class="wpwaf-rp-textarea" id="wpwaf-rp-b-footer" rows="2" maxlength="1000" placeholder="123 Main St, Springfield&#10;hello@youragency.com · (555) 010-0199"></textarea>
				</div>
			</div>
			<div class="wpwaf-rp-field">
				<label class="wpwaf-rp-check"><input type="checkbox" id="wpwaf-rp-b-cf"> Add a "Data provided by Cloudflare" line to the footer</label>
			</div>
			<div class="wpwaf-rp-footer">
				<button type="button" class="wpwaf-rp-btn wpwaf-rp-btn--primary" id="wpwaf-rp-b-save">Save branding</button>
			</div>
		</div>
	</div>

	<!-- Send log -->
	<div class="wpwaf-rp-card">
		<div class="wpwaf-rp-card-head">
			<div>
				<h2>🧾 Send log</h2>
				<div class="wpwaf-rp-card-sub">The last 20 sends, including tests.</div>
			</div>
		</div>
		<div id="wpwaf-rp-log"></div>
	</div>
</div>

<!-- Send report dialog -->
<div class="wpwaf-rp-modal" id="wpwaf-rp-send" role="dialog" aria-modal="true" aria-labelledby="wpwaf-rp-send-title">
	<div class="wpwaf-rp-modal-box wpwaf-rp-modal-box--form">
		<div class="wpwaf-rp-modal-head">
			<div style="min-width:0;">
				<div class="wpwaf-rp-modal-title" id="wpwaf-rp-send-title">Send report</div>
				<div class="wpwaf-rp-modal-meta" id="wpwaf-rp-send-meta"></div>
			</div>
			<button type="button" class="wpwaf-rp-modal-close" id="wpwaf-rp-send-close" aria-label="Close">&times;</button>
		</div>
		<div class="wpwaf-rp-modal-form">
			<div class="wpwaf-rp-field">
				<label for="wpwaf-rp-s-range">Period</label>
				<select class="wpwaf-rp-select" id="wpwaf-rp-s-range"></select>
			</div>
			<div class="wpwaf-rp-field">
				<span class="wpwaf-rp-label">Send to</span>
				<div class="wpwaf-rp-recips" id="wpwaf-rp-s-recips"></div>
			</div>
			<div class="wpwaf-rp-field">
				<label for="wpwaf-rp-s-intro">Message</label>
				<textarea class="wpwaf-rp-textarea" id="wpwaf-rp-s-intro" rows="3" maxlength="2000" placeholder="Leave empty to use a short standard introduction."></textarea>
				<div class="wpwaf-rp-hint">Only for this email. The client's default message is not changed.</div>
			</div>
		</div>
		<div class="wpwaf-rp-modal-foot">
			<button type="button" class="wpwaf-rp-btn" id="wpwaf-rp-s-preview">Preview</button>
			<button type="button" class="wpwaf-rp-btn" id="wpwaf-rp-s-test">Send test to me</button>
			<span style="flex:1 1 auto;"></span>
			<button type="button" class="wpwaf-rp-btn" id="wpwaf-rp-s-cancel">Cancel</button>
			<button type="button" class="wpwaf-rp-btn wpwaf-rp-btn--primary" id="wpwaf-rp-s-send">Send report</button>
		</div>
	</div>
</div>

<!-- Preview modal -->
<div class="wpwaf-rp-modal" id="wpwaf-rp-modal" role="dialog" aria-modal="true" aria-labelledby="wpwaf-rp-modal-title">
	<div class="wpwaf-rp-modal-box">
		<div class="wpwaf-rp-modal-head">
			<div style="min-width:0;">
				<div class="wpwaf-rp-modal-title" id="wpwaf-rp-modal-title">Preview</div>
				<div class="wpwaf-rp-modal-meta" id="wpwaf-rp-modal-meta"></div>
			</div>
			<button type="button" class="wpwaf-rp-modal-close" id="wpwaf-rp-modal-close" aria-label="Close preview">&times;</button>
		</div>
		<div class="wpwaf-rp-modal-body"><iframe id="wpwaf-rp-frame" title="Email preview" sandbox=""></iframe></div>
		<div class="wpwaf-rp-modal-foot">
			<label class="wpwaf-rp-check"><input type="checkbox" id="wpwaf-rp-modal-sample"> Use sample data</label>
			<button type="button" class="wpwaf-rp-btn" id="wpwaf-rp-modal-test" style="display:none;">Send test to me</button>
			<button type="button" class="wpwaf-rp-btn wpwaf-rp-btn--primary" id="wpwaf-rp-modal-done">Close</button>
		</div>
	</div>
</div>

<script>
'use strict';
(function(){
const NONCE    = <?php echo wp_json_encode( $nonce ); ?>;
const AJAX_URL = <?php echo wp_json_encode( $ajax_url ); ?>;
const BOOT     = <?php echo wp_json_encode( $wpwaf_rp_boot ); ?>;

let reports  = BOOT.state.reports || [];
let log      = BOOT.state.log || [];
let branding = BOOT.branding || {};
let zones    = null;          // zones of the active account, loaded on demand
let editing  = null;          // report being edited (object) or null
let picked   = {};            // id -> name for the zone picker
let previewCtx = null;        // { id, opts } or { draft } or { brand }
let sendingId  = null;        // client id open in the Send report dialog

const qs  = (s,c) => (c||document).querySelector(s);
const qsa = (s,c) => Array.from((c||document).querySelectorAll(s));
function esc(v){ return String(v == null ? '' : v).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }
function toast(msg, ok=true){
  const el = document.createElement('div');
  el.className = 'wpwaf-rp-toast ' + (ok ? 'ok' : 'err');
  el.textContent = msg;
  document.body.appendChild(el);
  setTimeout(() => { el.style.transition = 'opacity .3s'; el.style.opacity = '0'; setTimeout(() => el.remove(), 300); }, ok ? 3000 : 6000);
}
function ajax(action, data){
  const fd = new FormData();
  fd.append('action', action); fd.append('nonce', NONCE);
  Object.entries(data || {}).forEach(([k,v]) => fd.append(k, v));
  return fetch(AJAX_URL, {method:'POST', body:fd, credentials:'same-origin'})
    .then(r => r.json())
    .catch(e => ({success:false, data:{message:e.message}}));
}
function errMsg(res){ return (res && res.data && res.data.message) || 'Something went wrong.'; }
function busy(btn, on, label){
  if (!btn) return;
  if (on){ btn.dataset.label = btn.innerHTML; btn.disabled = true; btn.innerHTML = '<span class="wpwaf-rp-spinner"></span> ' + esc(label || 'Working…'); }
  else { btn.disabled = false; if (btn.dataset.label) btn.innerHTML = btn.dataset.label; }
}
function applyState(d){ if (d && d.reports){ reports = d.reports; log = d.log || []; renderList(); renderLog(); } }
function hourLabel(h){ const ap = h < 12 ? 'am' : 'pm'; const hh = h % 12 === 0 ? 12 : h % 12; return hh + ':00 ' + ap; }
function schedText(r){
  if (r.frequency === 'monthly') return 'Every month on day ' + r.monthday + ' at ' + hourLabel(r.hour);
  return 'Send by hand with Send report';
}
function plural(n, word){ return n + ' ' + word + (n === 1 ? '' : (/s$/.test(word) ? 'es' : 's')); }

// ── Client list ─────────────────────────────────────────────────────────────
function renderList(){
  const box = qs('#wpwaf-rp-list');
  if (!reports.length){
    box.innerHTML = '<div class="wpwaf-rp-empty"><strong>No clients yet</strong>Add a client, link their sites, and send them a branded summary of their traffic, speed and security from Cloudflare.</div>';
    return;
  }
  box.innerHTML = '<table class="wpwaf-rp-table"><thead><tr><th>Client</th><th>Sites</th><th>Monthly report</th><th>Last report</th><th style="text-align:right;">Actions</th></tr></thead><tbody>'
    + reports.map(r => {
      const z = r.zones || [];
      const zt = z.slice(0, 3).map(x => esc(x.name)).join(', ') + (z.length > 3 ? ' +' + (z.length - 3) + ' more' : '');
      const failed = r.last_status && !r.last_text;
      const on = r.frequency === 'monthly';
      const n = (r.recipients || []).length;
      return '<tr data-id="' + esc(r.id) + '">'
        + '<td data-label="Client"><div class="wpwaf-rp-name">' + esc(r.name) + '</div>'
        + '<div class="wpwaf-rp-sub">' + (r.contact ? esc(r.contact) + ' · ' : '') + plural(n, 'email address') + '</div>'
        + (r.notes ? '<div class="wpwaf-rp-notes">' + esc(r.notes) + '</div>' : '') + '</td>'
        + '<td data-label="Sites"><div style="overflow-wrap:anywhere;">' + (zt || '<span class="wpwaf-rp-sub">None</span>') + '</div></td>'
        + '<td data-label="Monthly report"><span class="wpwaf-rp-pill ' + (on ? 'wpwaf-rp-pill--on' : 'wpwaf-rp-pill--off') + '">' + (on ? 'On' : 'Off') + '</span>'
        + '<div class="wpwaf-rp-sub">' + esc(schedText(r)) + '</div>'
        + (on && r.next_text ? '<div class="wpwaf-rp-sub">Next: ' + esc(r.next_text) + '</div>' : '') + '</td>'
        + '<td data-label="Last report">' + (r.last_text ? esc(r.last_text) : '<span class="wpwaf-rp-sub">Never</span>')
        + (r.last_status ? '<div class="wpwaf-rp-sub ' + (failed ? 'wpwaf-rp-status-err' : '') + '">' + esc(r.last_status) + '</div>' : '') + '</td>'
        + '<td class="wpwaf-rp-td-actions"><div class="wpwaf-rp-actions">'
        + '<button type="button" class="wpwaf-rp-btn wpwaf-rp-btn--sm wpwaf-rp-btn--primary" data-act="send"' + (BOOT.has_creds ? '' : ' disabled title="Connect a Cloudflare account first"') + '>Send report</button>'
        + '<button type="button" class="wpwaf-rp-btn wpwaf-rp-btn--sm" data-act="preview">Preview</button>'
        + '<button type="button" class="wpwaf-rp-btn wpwaf-rp-btn--sm" data-act="edit">Edit</button>'
        + '<button type="button" class="wpwaf-rp-btn wpwaf-rp-btn--sm wpwaf-rp-btn--danger" data-act="delete">Delete</button>'
        + '</div></td></tr>';
    }).join('') + '</tbody></table>';
  qsa('[data-act]', box).forEach(b => b.addEventListener('click', onAction));
}
function findReport(id){ return reports.find(r => r.id === id); }

function onAction(){
  const id = this.closest('tr').dataset.id, r = findReport(id), act = this.dataset.act;
  if (!r) return;
  if (act === 'edit') openEditor(r);
  else if (act === 'preview') openPreview({id});
  else if (act === 'send') openSend(r);
  else if (act === 'delete'){
    if (!confirm('Delete the client "' + r.name + '"? Their monthly report stops and this cannot be undone. Their sites in Cloudflare are not affected.')) return;
    busy(this, true, '…');
    ajax('wpwaf_rp_delete', {id}).then(res => {
      if (!res.success){ busy(this, false); toast(errMsg(res), false); return; }
      if (editing && editing.id === id) closeEditor();
      applyState(res.data); toast('Client deleted');
    });
  }
}

// ── Send report dialog (one-time send) ──────────────────────────────────────
function openSend(r){
  sendingId = r.id;
  qs('#wpwaf-rp-send-title').textContent = 'Send report to ' + r.name;
  qs('#wpwaf-rp-send-meta').textContent = plural((r.zones || []).length, 'site') + ': ' + (r.zones || []).map(z => z.name).join(', ');
  const rs = qs('#wpwaf-rp-s-range');
  rs.innerHTML = Object.entries(BOOT.ranges).map(([k,v]) => '<option value="' + esc(k) + '">' + esc(v) + '</option>').join('');
  rs.value = BOOT.ranges[r.range] ? r.range : '30d';
  qs('#wpwaf-rp-s-recips').innerHTML = (r.recipients || []).map(e => '<label class="wpwaf-rp-check"><input type="checkbox" value="' + esc(e) + '" checked> ' + esc(e) + '</label>').join('')
    || '<span class="wpwaf-rp-sub">This client has no email addresses. Edit the client to add one.</span>';
  qs('#wpwaf-rp-s-intro').value = r.intro || '';
  qs('#wpwaf-rp-send').classList.add('open');
}
function closeSend(){ qs('#wpwaf-rp-send').classList.remove('open'); sendingId = null; }
function sendOpts(){
  return {
    range: qs('#wpwaf-rp-s-range').value,
    intro: qs('#wpwaf-rp-s-intro').value,
    recipients: JSON.stringify(qsa('#wpwaf-rp-s-recips input:checked').map(cb => cb.value))
  };
}
function doSend(btn, mode){
  if (!sendingId) return;
  const opts = sendOpts();
  const to = JSON.parse(opts.recipients);
  if (mode !== 'test' && !to.length){ toast('Choose at least one email address', false); return; }
  if (mode !== 'test' && !confirm('Send this report now to ' + to.join(', ') + '?')) return;
  busy(btn, true, mode === 'test' ? 'Sending…' : 'Sending…');
  ajax('wpwaf_rp_send', Object.assign({id: sendingId, mode}, opts)).then(res => {
    busy(btn, false);
    applyState(res.data);
    const msg = mode === 'test' && res.success ? 'Test sent to ' + BOOT.user_email : ((res.data && res.data.message) || (res.success ? 'Sent' : 'Send failed'));
    toast(msg, !!res.success);
    if (res.success && mode !== 'test') closeSend();
  });
}
qs('#wpwaf-rp-s-send').addEventListener('click', function(){ doSend(this, 'now'); });
qs('#wpwaf-rp-s-test').addEventListener('click', function(){ doSend(this, 'test'); });
qs('#wpwaf-rp-s-preview').addEventListener('click', () => { if (sendingId) openPreview({id: sendingId, opts: sendOpts()}); });
qs('#wpwaf-rp-s-cancel').addEventListener('click', closeSend);
qs('#wpwaf-rp-send-close').addEventListener('click', closeSend);
qs('#wpwaf-rp-send').addEventListener('click', e => { if (e.target.id === 'wpwaf-rp-send') closeSend(); });

// ── Editor ──────────────────────────────────────────────────────────────────
function fillSelects(){
  let md = ''; for (let i = 1; i <= 28; i++) md += '<option value="' + i + '">' + i + '</option>';
  qs('#wpwaf-rp-f-monthday').innerHTML = md;
  let hr = ''; for (let i = 0; i < 24; i++) hr += '<option value="' + i + '">' + hourLabel(i) + '</option>';
  qs('#wpwaf-rp-f-hour').innerHTML = hr;
  qs('#wpwaf-rp-f-sections').innerHTML = Object.entries(BOOT.sections).map(([k,v]) => '<label class="wpwaf-rp-check"><input type="checkbox" value="' + esc(k) + '"> ' + esc(v) + '</label>').join('');
  if ((BOOT.accounts || []).length > 1){
    qs('#wpwaf-rp-account-field').style.display = '';
    qs('#wpwaf-rp-f-account').innerHTML = BOOT.accounts.map(a => '<option value="' + esc(a.id) + '">' + esc(a.label || a.id) + (a.id === BOOT.active_id ? ' (active)' : '') + '</option>').join('');
  }
}
function syncFrequency(){
  const on = qs('#wpwaf-rp-f-monthly').checked;
  qs('#wpwaf-rp-monthly-fields').style.display = on ? '' : 'none';
  qs('#wpwaf-rp-auto-hint').style.display = on ? '' : 'none';
}
qs('#wpwaf-rp-f-monthly').addEventListener('change', syncFrequency);

function openEditor(r){
  editing = r ? JSON.parse(JSON.stringify(r)) : {
    id:'', name:'', contact:'', notes:'', account: BOOT.active_id, zones:[], recipients:[], frequency:'manual', monthday:1, hour:8,
    range:'30d', sections: Object.fromEntries(Object.keys(BOOT.sections).map(k => [k, true])), intro:''
  };
  qs('#wpwaf-rp-editor-title').textContent = r ? 'Edit client: ' + r.name : 'Add client';
  qs('#wpwaf-rp-f-name').value = editing.name;
  qs('#wpwaf-rp-f-contact').value = editing.contact || '';
  qs('#wpwaf-rp-f-notes').value = editing.notes || '';
  qs('#wpwaf-rp-f-recipients').value = (editing.recipients || []).join(', ');
  qs('#wpwaf-rp-f-monthly').checked = editing.frequency === 'monthly';
  qs('#wpwaf-rp-f-monthday').value = String(editing.monthday);
  qs('#wpwaf-rp-f-hour').value = String(editing.hour);
  qs('#wpwaf-rp-f-intro').value = editing.intro || '';
  if (qs('#wpwaf-rp-f-account option')) qs('#wpwaf-rp-f-account').value = editing.account || BOOT.active_id;
  qsa('#wpwaf-rp-f-sections input').forEach(cb => { cb.checked = !!(editing.sections || {})[cb.value]; });
  picked = {};
  (editing.zones || []).forEach(z => { picked[z.id] = z.name; });
  qs('#wpwaf-rp-zone-search').value = '';
  qs('#wpwaf-rp-next-hint').textContent = r && r.frequency === 'monthly' && r.next_text ? 'Next send: ' + r.next_text : '';
  syncFrequency();
  const ed = qs('#wpwaf-rp-editor');
  ed.style.display = '';
  ed.scrollIntoView({behavior:'smooth', block:'start'});
  loadZones();
}
function closeEditor(){ editing = null; qs('#wpwaf-rp-editor').style.display = 'none'; }
function collect(){
  return {
    id: editing ? editing.id : '',
    name: qs('#wpwaf-rp-f-name').value.trim(),
    contact: qs('#wpwaf-rp-f-contact').value.trim(),
    notes: qs('#wpwaf-rp-f-notes').value,
    account: qs('#wpwaf-rp-f-account option') ? qs('#wpwaf-rp-f-account').value : BOOT.active_id,
    zones: Object.entries(picked).map(([id, name]) => ({id, name})),
    recipients: qs('#wpwaf-rp-f-recipients').value,
    frequency: qs('#wpwaf-rp-f-monthly').checked ? 'monthly' : 'manual',
    monthday: qs('#wpwaf-rp-f-monthday').value,
    hour: qs('#wpwaf-rp-f-hour').value,
    range: (editing && editing.range) || '30d',
    sections: qsa('#wpwaf-rp-f-sections input:checked').map(cb => cb.value),
    intro: qs('#wpwaf-rp-f-intro').value
  };
}
qs('#wpwaf-rp-new').addEventListener('click', () => openEditor(null));
qs('#wpwaf-rp-cancel').addEventListener('click', closeEditor);
qs('#wpwaf-rp-save').addEventListener('click', function(){
  const data = collect();
  if (!data.name){ toast('Enter the client\'s name', false); return; }
  if (!data.zones.length){ toast('Link at least one site to this client', false); return; }
  if (!data.recipients.trim()){ toast('Add at least one email address', false); return; }
  busy(this, true, 'Saving…');
  ajax('wpwaf_rp_save', {report: JSON.stringify(data)}).then(res => {
    busy(this, false);
    if (!res.success){ toast(errMsg(res), false); return; }
    applyState(res.data);
    closeEditor();
    const saved = findReport(res.data.id);
    toast(saved && saved.frequency === 'monthly' && saved.next_text ? 'Client saved. Next monthly report: ' + saved.next_text : 'Client saved');
  });
});
qs('#wpwaf-rp-preview-draft').addEventListener('click', () => openPreview({draft: collect()}));

// ── Zone picker ─────────────────────────────────────────────────────────────
function loadZones(){
  const list = qs('#wpwaf-rp-zone-list');
  if (!BOOT.has_creds){ list.innerHTML = '<div class="wpwaf-rp-loading">Connect a Cloudflare account to pick zones.</div>'; updateZoneCount(); return; }
  if (zones){ renderZones(); return; }
  list.innerHTML = '<div class="wpwaf-rp-loading"><span class="wpwaf-rp-spinner"></span> Loading zones…</div>';
  ajax('wpwaf_list_zones', {}).then(res => {
    if (!res.success){ list.innerHTML = '<div class="wpwaf-rp-loading">' + esc(errMsg(res)) + '</div>'; return; }
    zones = (res.data.zones || []).map(z => ({id: z.id, name: z.name, plan: z.plan || ''}));
    renderZones();
  });
}
function renderZones(){
  const q = qs('#wpwaf-rp-zone-search').value.trim().toLowerCase();
  const known = new Set((zones || []).map(z => z.id));
  // Zones already in the report but not in this account's list stay visible.
  const extra = Object.entries(picked).filter(([id]) => !known.has(id)).map(([id, name]) => ({id, name, plan:'not in list'}));
  const all = extra.concat(zones || []).filter(z => !q || z.name.toLowerCase().includes(q));
  qs('#wpwaf-rp-zone-list').innerHTML = all.length
    ? all.map(z => '<label class="wpwaf-rp-zone"><input type="checkbox" value="' + esc(z.id) + '" data-name="' + esc(z.name) + '"' + (picked[z.id] ? ' checked' : '') + '><span>' + esc(z.name) + '</span>' + (z.plan ? '<small>' + esc(String(z.plan).replace(' Website', '')) + '</small>' : '') + '</label>').join('')
    : '<div class="wpwaf-rp-loading">No zones match.</div>';
  qsa('#wpwaf-rp-zone-list input').forEach(cb => cb.addEventListener('change', function(){
    if (this.checked){
      if (Object.keys(picked).length >= 20){ this.checked = false; toast('A client can have up to 20 sites', false); return; }
      picked[this.value] = this.dataset.name;
    } else delete picked[this.value];
    updateZoneCount();
  }));
  updateZoneCount();
}
function updateZoneCount(){ qs('#wpwaf-rp-zone-count').textContent = Object.keys(picked).length + ' selected'; }
qs('#wpwaf-rp-zone-search').addEventListener('input', () => { if (zones) renderZones(); });

// ── Preview ─────────────────────────────────────────────────────────────────
function openPreview(ctx){
  previewCtx = ctx;
  const modal = qs('#wpwaf-rp-modal');
  const sample = qs('#wpwaf-rp-modal-sample');
  if (!BOOT.has_creds){ sample.checked = true; sample.disabled = true; }
  else if (ctx.brand){ sample.checked = true; }
  qs('#wpwaf-rp-modal-test').style.display = ctx.id && !ctx.opts && BOOT.has_creds ? '' : 'none';
  modal.classList.add('open');
  loadPreview();
}
function loadPreview(){
  const ctx = previewCtx || {};
  const sample = qs('#wpwaf-rp-modal-sample').checked;
  qs('#wpwaf-rp-modal-title').textContent = 'Building preview…';
  qs('#wpwaf-rp-modal-meta').innerHTML = '<span class="wpwaf-rp-spinner"></span> Reading Cloudflare analytics';
  const data = {sample: sample ? '1' : '0'};
  if (ctx.id) data.id = ctx.id;
  if (ctx.opts) Object.assign(data, ctx.opts);
  else if (ctx.draft) data.report = JSON.stringify(ctx.draft);
  else data.report = JSON.stringify({name:'Your client', contact:'', zones:[], recipients:'', sections: Object.keys(BOOT.sections), range:'30d'});
  ajax('wpwaf_rp_preview', data).then(res => {
    if (!res.success){ qs('#wpwaf-rp-modal-title').textContent = 'Preview failed'; qs('#wpwaf-rp-modal-meta').textContent = errMsg(res); return; }
    qs('#wpwaf-rp-modal-title').textContent = res.data.subject;
    qs('#wpwaf-rp-modal-meta').innerHTML = (res.data.sample ? 'Showing sample numbers <span class="wpwaf-rp-sample">Sample data</span>' : 'Built from live Cloudflare data')
      + (!res.data.sample && !res.data.any_data ? '. <strong style="color:#b91c1c;">No analytics could be read for these zones, so a real send would be skipped.</strong>' : '');
    qs('#wpwaf-rp-frame').srcdoc = res.data.html;
  });
}
function closePreview(){ qs('#wpwaf-rp-modal').classList.remove('open'); qs('#wpwaf-rp-frame').srcdoc = ''; previewCtx = null; }
qs('#wpwaf-rp-modal-close').addEventListener('click', closePreview);
qs('#wpwaf-rp-modal-done').addEventListener('click', closePreview);
qs('#wpwaf-rp-modal').addEventListener('click', e => { if (e.target.id === 'wpwaf-rp-modal') closePreview(); });
document.addEventListener('keydown', e => {
  if (e.key !== 'Escape') return;
  if (qs('#wpwaf-rp-modal').classList.contains('open')) closePreview();
  else if (qs('#wpwaf-rp-send').classList.contains('open')) closeSend();
});
qs('#wpwaf-rp-modal-sample').addEventListener('change', loadPreview);
qs('#wpwaf-rp-modal-test').addEventListener('click', function(){
  if (!previewCtx || !previewCtx.id) return;
  const btn = this, id = previewCtx.id;
  busy(btn, true, 'Sending…');
  ajax('wpwaf_rp_send', {id, mode:'test'}).then(res => {
    busy(btn, false); applyState(res.data);
    toast(res.success ? 'Test sent to ' + BOOT.user_email : errMsg(res), !!res.success);
  });
});
qs('#wpwaf-rp-brand-preview').addEventListener('click', () => openPreview({brand: true}));

// ── Branding ────────────────────────────────────────────────────────────────
function fillBranding(){
  qs('#wpwaf-rp-b-agency').value = branding.agency_name || '';
  qs('#wpwaf-rp-b-color').value = branding.brand_color || '#FF6A00';
  qs('#wpwaf-rp-b-color-text').value = branding.brand_color || '#FF6A00';
  qs('#wpwaf-rp-b-logo').value = branding.logo_url || '';
  qs('#wpwaf-rp-b-fromname').value = branding.from_name || '';
  qs('#wpwaf-rp-b-fromemail').value = branding.from_email || '';
  qs('#wpwaf-rp-b-replyto').value = branding.reply_to || '';
  qs('#wpwaf-rp-b-footer').value = branding.footer_text || '';
  qs('#wpwaf-rp-b-cf').checked = !!branding.show_cloudflare;
  logoThumb();
}
function logoThumb(){
  const url = qs('#wpwaf-rp-b-logo').value.trim();
  const box = qs('#wpwaf-rp-b-logo-thumb');
  box.textContent = '';
  if (/^https?:\/\//i.test(url)){ const img = document.createElement('img'); img.src = url; img.alt = ''; box.appendChild(img); }
}
qs('#wpwaf-rp-b-logo').addEventListener('change', logoThumb);
qs('#wpwaf-rp-b-color').addEventListener('input', function(){ qs('#wpwaf-rp-b-color-text').value = this.value.toUpperCase(); });
qs('#wpwaf-rp-b-color-text').addEventListener('input', function(){ if (/^#[0-9a-f]{6}$/i.test(this.value)) qs('#wpwaf-rp-b-color').value = this.value; });
qs('#wpwaf-rp-b-logo-pick').addEventListener('click', function(){
  if (!window.wp || !wp.media){ qs('#wpwaf-rp-b-logo').focus(); toast('Paste the logo URL into the field.', false); return; }
  const frame = wp.media({ title: 'Choose a logo', library: { type: 'image' }, multiple: false, button: { text: 'Use this logo' } });
  frame.on('select', () => {
    const a = frame.state().get('selection').first().toJSON();
    qs('#wpwaf-rp-b-logo').value = a.url || '';
    logoThumb();
  });
  frame.open();
});
qs('#wpwaf-rp-b-save').addEventListener('click', function(){
  const color = qs('#wpwaf-rp-b-color-text').value.trim();
  if (!/^#[0-9a-f]{6}$/i.test(color)){ toast('Enter the brand color as a hex value like #FF6A00', false); return; }
  busy(this, true, 'Saving…');
  ajax('wpwaf_rp_branding_save', {branding: JSON.stringify({
    agency_name: qs('#wpwaf-rp-b-agency').value.trim(),
    brand_color: color,
    logo_url: qs('#wpwaf-rp-b-logo').value.trim(),
    from_name: qs('#wpwaf-rp-b-fromname').value.trim(),
    from_email: qs('#wpwaf-rp-b-fromemail').value.trim(),
    reply_to: qs('#wpwaf-rp-b-replyto').value.trim(),
    footer_text: qs('#wpwaf-rp-b-footer').value,
    show_cloudflare: qs('#wpwaf-rp-b-cf').checked
  })}).then(res => {
    busy(this, false);
    if (!res.success){ toast(errMsg(res), false); return; }
    branding = res.data.branding; fillBranding(); toast('Branding saved');
  });
});

// ── Log ─────────────────────────────────────────────────────────────────────
function renderLog(){
  const box = qs('#wpwaf-rp-log');
  if (!log.length){ box.innerHTML = '<div class="wpwaf-rp-empty">Nothing sent yet.</div>'; return; }
  const kinds = {scheduled:'Automatic (monthly)', manual:'Sent by hand', test:'Test'};
  box.innerHTML = '<table class="wpwaf-rp-table wpwaf-rp-log"><thead><tr><th>When</th><th>Client</th><th>Type</th><th>Result</th></tr></thead><tbody>'
    + log.map(l => '<tr>'
      + '<td data-label="When" style="white-space:nowrap;">' + esc(l.time_text || '') + '</td>'
      + '<td data-label="Client"><span style="overflow-wrap:anywhere;">' + esc(l.report) + '</span>' + (l.range && BOOT.ranges[l.range] ? '<div class="wpwaf-rp-sub">' + esc(BOOT.ranges[l.range]) + '</div>' : '') + '</td>'
      + '<td data-label="Type">' + esc(kinds[l.trigger] || l.trigger) + '</td>'
      + '<td data-label="Result"><span class="wpwaf-rp-pill ' + (l.ok ? 'wpwaf-rp-pill--on' : 'wpwaf-rp-pill--err') + '">' + (l.ok ? 'Sent' : 'Failed') + '</span><div class="wpwaf-rp-sub">' + esc(l.message) + '</div></td>'
      + '</tr>').join('') + '</tbody></table>';
}

qs('#wpwaf-rp-tz').textContent = BOOT.timezone || 'UTC';
fillSelects();
fillBranding();
renderList();
renderLog();

// Links from Zone Status: ?client=<id>&send=1 opens Send report, ?zone=<id>&zname=<name> starts a new client with that site.
(function(){
  const p = new URLSearchParams(location.search);
  const cid = p.get('client');
  if (cid && findReport(cid) && p.get('send') === '1' && BOOT.has_creds){ openSend(findReport(cid)); return; }
  if (cid && findReport(cid)){ openEditor(findReport(cid)); return; }
  const zid = p.get('zone'), zname = p.get('zname');
  if (zid && /^[a-f0-9]{32}$/.test(zid)){
    openEditor(null);
    picked[zid] = zname || zid;
    updateZoneCount();
    if (zones) renderZones();
  }
})();
})();
</script>
