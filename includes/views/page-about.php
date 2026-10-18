<?php
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'wpwaf_about_icon' ) ) {
	/**
	 * Line icon for the About page (24px grid, drawn with the current text
	 * colour). Shapes are fixed strings, never user input.
	 */
	function wpwaf_about_icon( string $name, int $size = 20 ): string {
		static $shapes = [
			'shield'   => '<path d="M12 3l7 3v5c0 4.5-3 8.5-7 10-4-1.5-7-5.5-7-10V6l7-3z"/><path d="M9 12l2 2 4-4"/>',
			'tag'      => '<path d="M3 12V5a2 2 0 012-2h7l9 9-9 9-9-9z"/><circle cx="7.5" cy="7.5" r="1.5"/>',
			'globe'    => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18"/><path d="M12 3c2.5 2.7 3.8 5.7 3.8 9s-1.3 6.3-3.8 9c-2.5-2.7-3.8-5.7-3.8-9S9.5 5.7 12 3z"/>',
			'chart'    => '<path d="M3 20h18"/><path d="M6 20v-7"/><path d="M11 20V5"/><path d="M16 20v-10"/><path d="M21 20v-4"/>',
			'users'    => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c.8-3.4 3.4-5.5 6.5-5.5s5.7 2.1 6.5 5.5"/><circle cx="17" cy="9" r="2.5"/><path d="M16.5 14.6c2.4.3 4.2 2.2 5 5.4"/>',
			'lock'     => '<rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 018 0v3"/><path d="M12 14v3"/>',
			'sliders'  => '<path d="M4 6h10"/><path d="M18 6h2"/><circle cx="16" cy="6" r="2"/><path d="M4 12h4"/><path d="M12 12h8"/><circle cx="10" cy="12" r="2"/><path d="M4 18h12"/><circle cx="18" cy="18" r="2"/>',
			'bot'      => '<rect x="4" y="8" width="16" height="12" rx="3"/><path d="M12 4v4"/><circle cx="12" cy="3.5" r="1"/><circle cx="9" cy="13.5" r="1.2"/><circle cx="15" cy="13.5" r="1.2"/><path d="M9.5 17h5"/>',
			'cert'     => '<path d="M14 3H7a2 2 0 00-2 2v14a2 2 0 002 2h10a2 2 0 002-2V8z"/><path d="M14 3v5h5"/><path d="M9 14l2 2 4-4"/>',
			'alert'    => '<path d="M12 3.5l9 16H3z"/><path d="M12 10v4"/><path d="M12 17h.01"/>',
			'bell'     => '<path d="M6 10a6 6 0 0112 0c0 5 2 6.5 2 6.5H4S6 15 6 10z"/><path d="M10 20a2 2 0 004 0"/>',
			'check-sq' => '<rect x="3" y="3" width="18" height="18" rx="4"/><path d="M8 12l3 3 5-6"/>',
			'route'    => '<path d="M3 6h5l7 12h6"/><path d="M3 18h5l2.5-4.3"/><path d="M15 6h6"/><path d="M18 3l3 3-3 3"/><path d="M18 15l3 3-3 3"/>',
			'mail'     => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3.5 6.5l8.5 6 8.5-6"/>',
			'wrench'   => '<path d="M14.5 6.5a4 4 0 00-5.3 5.2L4 16.9V20h3.1l5.2-5.2a4 4 0 005.2-5.3l-2.4 2.4-2.6-.5-.5-2.6z"/>',
			'folder'   => '<path d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>',
			'target'   => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1"/>',
			'check-ci' => '<circle cx="12" cy="12" r="9"/><path d="M8 12.5l2.5 2.5L16 9.5"/>',
			'layers'   => '<path d="M12 3l9 5-9 5-9-5z"/><path d="M3 13l9 5 9-5"/>',
			'eye-off'  => '<path d="M3 3l18 18"/><path d="M10.6 6.1A9.6 9.6 0 0112 6c5 0 9 6 9 6a17 17 0 01-2.6 3.2"/><path d="M6.6 7.6C4.4 9.2 3 12 3 12s4 6 9 6c1.6 0 3-.4 4.3-1.1"/><path d="M9.9 10a3 3 0 004.1 4.1"/>',
			'key'      => '<circle cx="8" cy="15" r="4"/><path d="M11 12l9-9"/><path d="M17 6l3 3"/><path d="M14.5 8.5l2 2"/>',
			'zap'      => '<path d="M13 2L4 14h7l-1 8 9-12h-7z"/>',
			'layout'   => '<rect x="3" y="3" width="8" height="8" rx="1.5"/><rect x="13" y="3" width="8" height="5" rx="1.5"/><rect x="13" y="10" width="8" height="11" rx="1.5"/><rect x="3" y="13" width="8" height="8" rx="1.5"/>',
			'transfer' => '<path d="M12 3v12"/><path d="M7 10l5 5 5-5"/><path d="M4 21h16"/>',
			'unlock'   => '<rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 017.6-1.8"/>',
			'star'     => '<path d="M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1-4.4-4.3 6.1-.9z"/>',
			'list'     => '<path d="M9 6h12"/><path d="M9 12h12"/><path d="M9 18h12"/><path d="M4 6h.01"/><path d="M4 12h.01"/><path d="M4 18h.01"/>',
			'check'    => '<path d="M5 12.5l4.5 4.5L19 7.5"/>',
			'arrow'    => '<path d="M5 12h14"/><path d="M13 6l6 6-6 6"/>',
		];
		$inner = $shapes[ $name ] ?? $shapes['shield'];
		return '<svg class="wpwaf-about-svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $inner . '</svg>';
	}
}
?>
<style>
.wpwaf-about-wrap{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;max-width:860px;padding:24px 20px;color:#1a1a2e;}
.wpwaf-about-hero{background:#111111;border-radius:12px;padding:36px 40px;margin-bottom:28px;display:flex;align-items:center;justify-content:space-between;gap:24px;flex-wrap:wrap;}
.wpwaf-about-hero-left{display:flex;align-items:center;gap:18px;}
.wpwaf-about-hero-icon{width:48px;height:48px;color:#FF6A00;flex-shrink:0;display:inline-flex;}
.wpwaf-about-hero-title{margin:0;font-size:26px;font-weight:800;color:#fff;line-height:1.2;}
.wpwaf-about-hero-sub{margin:4px 0 0;font-size:13px;color:#9ca3af;}
.wpwaf-about-badge{display:inline-block;background:#FF6A00;color:#fff;font-size:11px;font-weight:700;padding:3px 10px;border-radius:10px;margin-left:10px;vertical-align:middle;}

/* Two column layout */
.wpwaf-about-cols{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;}
@media(max-width:700px){.wpwaf-about-cols{grid-template-columns:1fr;}}

/* Cards */
.wpwaf-about-card{background:#fff;border:1px solid #e2e6ea;border-radius:10px;padding:24px;display:flex;flex-direction:column;gap:14px;}
.wpwaf-about-card h2{margin:0;font-size:16px;font-weight:700;color:#1a1a2e;display:flex;align-items:center;gap:8px;}
.wpwaf-about-card p{margin:0;font-size:13px;color:#4b5563;line-height:1.7;}
.wpwaf-about-card ul{margin:0;padding-left:18px;font-size:13px;color:#4b5563;line-height:1.8;}
.wpwaf-about-card li{margin-bottom:2px;}

/* Free vs Pro */
.wpwaf-about-compare{background:#fff;border:1px solid #e2e6ea;border-radius:10px;overflow:hidden;margin-bottom:20px;}
.wpwaf-about-compare table{width:100%;border-collapse:collapse;font-size:13px;}
.wpwaf-about-compare th{padding:12px 16px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#6b7280;background:#f8f9fb;border-bottom:1px solid #e2e6ea;}
.wpwaf-about-compare th:not(:first-child){text-align:center;}
.wpwaf-about-compare td{padding:11px 16px;border-bottom:1px solid #f1f3f5;color:#374151;}
.wpwaf-about-compare tr:last-child td{border-bottom:none;}
.wpwaf-about-compare td:not(:first-child){text-align:center;font-size:16px;}
.wpwaf-about-compare .col-pro{background:#fff8f5;}
.wpwaf-about-compare th.col-pro{background:#fff1e6;color:#FF6A00;}

/* CTA buttons */
.wpwaf-about-cta{display:flex;gap:12px;flex-wrap:wrap;margin-top:4px;}
.wpwaf-about-btn{display:inline-flex;align-items:center;gap:8px;padding:11px 22px;border-radius:7px;font-size:14px;font-weight:700;text-decoration:none;transition:all .15s;cursor:pointer;border:none;}
.wpwaf-about-btn-primary{background:#FF6A00;color:#fff;}
.wpwaf-about-btn-primary:hover{background:#d95500;color:#fff;}
.wpwaf-about-btn-secondary{background:#fff;color:#000000 !important;border:2px solid #e2e6ea;}
.wpwaf-about-btn-secondary:hover{border-color:#FF6A00;color:#FF6A00 !important;}
.wpwaf-about-btn svg{width:18px;height:18px;fill:#000000 !important;color:#000000 !important;flex-shrink:0;}

/* Feature grid */
.wpwaf-about-features{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:12px;margin-bottom:20px;}
.wpwaf-about-feature{background:#fff;border:1px solid #e2e6ea;border-radius:8px;padding:16px 18px;}
.wpwaf-about-feature-icon{display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:9px;background:#fff4ec;color:#FF6A00;margin-bottom:10px;}
.wpwaf-about-card h2 .wpwaf-about-svg{color:#FF6A00;flex-shrink:0;}
.wpwaf-about-btn .wpwaf-about-svg{width:16px;height:16px;flex-shrink:0;fill:none !important;color:inherit !important;}
.wpwaf-about-yes{color:#059669;display:inline-flex;vertical-align:middle;}
.wpwaf-about-no{color:#c3c2b7;}
.wpwaf-about-feature-title{font-size:13px;font-weight:700;color:#1a1a2e;margin:0 0 4px;}
.wpwaf-about-feature-desc{font-size:12px;color:#6b7280;margin:0;line-height:1.6;}

.wpwaf-about-section-title{margin:8px 0 12px;padding:0;font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#6b7280;}
a.wpwaf-about-feature{display:block;text-decoration:none;color:inherit;transition:border-color .15s,box-shadow .15s,transform .15s;}
a.wpwaf-about-feature:hover,a.wpwaf-about-feature:focus-visible{border-color:#FF6A00;box-shadow:0 4px 14px rgba(255,106,0,.12);transform:translateY(-1px);outline:none;}
.wpwaf-about-feature.is-hidden{opacity:.65;}
.wpwaf-about-feature-flag{display:inline-block;margin-left:6px;padding:1px 7px;border-radius:10px;background:#f3f4f6;color:#6b7280;font-size:10px;font-weight:600;vertical-align:middle;}

/* Version info */
.wpwaf-about-version{font-size:12px;color:#9ca3af;margin-top:4px;}
</style>

<div class="wpwaf-about-wrap">

	<!-- Hero -->
	<div class="wpwaf-about-hero">
		<div class="wpwaf-about-hero-left">
			<span class="wpwaf-about-hero-icon"><?php echo wpwaf_about_icon( 'shield', 48 ); // fixed markup ?></span>
			<div>
				<h1 class="wpwaf-about-hero-title">
					WP WAF Manager
					<span class="wpwaf-about-badge">v<?php echo esc_html( WPWAF_VERSION ); ?></span>
				</h1>
				<p class="wpwaf-about-hero-sub">Visual Cloudflare security management, right inside your WordPress admin</p>
			</div>
		</div>
		<div class="wpwaf-about-cta">
			<a href="https://www.wpwafmanager.com" target="_blank" rel="noopener" class="wpwaf-about-btn wpwaf-about-btn-primary">
				<?php echo wpwaf_about_icon( 'globe', 16 ); ?> wpwafmanager.com
			</a>
			<a href="https://github.com/jaimealnassim/wpwafmanager" target="_blank" rel="noopener" class="wpwaf-about-btn wpwaf-about-btn-secondary">
				<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"/></svg>
				GitHub
			</a>
		</div>
	</div>

	<?php
	// One card per admin screen, linked straight to it. Screens hidden in
	// Settings are shown but marked, so the list always matches the plugin.
	$wpwaf_screens = [
		[ 'wpwafmanager',                 'shield', 'WAF Rules',       'Configure the five battle-tested rules, save them as named profiles with notes, and deploy to one zone or dozens in a single click.' ],
		[ 'wpwafmanager-zones',           'tag', 'Zones',           'Every zone in your account in one list. Tag each with a flare such as WooCommerce, LMS or Staging so you can spot it instantly when deploying.' ],
		[ 'wpwafmanager-dns',             'globe', 'DNS Manager',     'Add, edit, delete and proxy records across all 21 Cloudflare record types without leaving WordPress.' ],
		[ 'wpwafmanager-zone-status',     'chart', 'Zone Status',     'Every zone at a glance, then click one for charts, top countries, pages, status codes, devices and security events with change vs the previous period.' ],
		[ 'wpwafmanager-reports',         'users', 'Clients & Reports', 'Add clients, link them to their sites, and send each one a branded Cloudflare report by hand or automatically every month.', 'hide_reports' ],
		[ 'wpwafmanager-ip-rules',        'lock', 'IP Access Rules', 'Account-wide allow, block and challenge rules by IP, IP range, country or ASN. One change covers every zone.' ],
		[ 'wpwafmanager-zone-controls',   'sliders', 'Zone Controls',   'Under Attack mode, Development mode, cache purge by URL, hostname, prefix or tag, Smart Tiered Cache, security headers and more for every zone.' ],
		[ 'wpwafmanager-bots',            'bot', 'Bot Control',     'Block or allow AI search, assistant and training crawlers, manage robots.txt, and turn on Bot Fight Mode across every zone in one pass.', 'hide_bots' ],
		[ 'wpwafmanager-ssl-insights',    'cert', 'SSL & Insights', 'Certificate expiry, HTTPS settings and Cloudflare Security Insights for every zone, with one-click fixes for the safe ones.', 'hide_ssl_insights' ],
		[ 'wpwafmanager-security-events', 'alert', 'Security Events', 'Recent firewall events per zone, filterable by action, time range and source. Requires a Cloudflare Pro plan or higher.', 'hide_security_events' ],
		[ 'wpwafmanager-alerts',          'bell', 'Alerts & Health', 'Cloudflare alerts by email or webhook for DDoS attacks, SSL events and origin downtime, plus Health Checks that watch your servers.', 'hide_alerts' ],
		[ 'wpwafmanager-turnstile',       'check-sq', 'Turnstile',       'Create, edit and rotate Cloudflare Turnstile widgets for client sites, and copy their site and secret keys in one click.', 'hide_turnstile' ],
		[ 'wpwafmanager-page-rules',      'route', 'Page Rules',      'Scan every zone for legacy Page Rules, see where each setting belongs now, and move redirects and cache rules to modern Rules in one click.', 'hide_page_rules' ],
		[ 'wpwafmanager-email-routing',   'mail', 'Email Routing',   'Forward any address on your domain to any inbox, set catch-all rules and manage destination addresses. Free on every Cloudflare plan.', 'hide_email_routing' ],
		[ 'wpwafmanager-settings',        'wrench', 'Settings',        'Admin bar quick purge, analytics sync, access control by role or by individual admin, menu visibility and uninstall behavior.' ],
	];
	$wpwaf_tools = [
		[ 'folder', 'Rule Profiles',          'Save complete rule configurations under a name, with notes on why they exist. Switch with one click, up to 10 profiles.' ],
		[ 'target', 'Domain Profiles',        'Save groups of zones, such as "WooCommerce clients" or "Staging", and select them all before a deploy in one click.' ],
		[ 'check-ci', 'Custom Allowlists',      'Allow your own IPs, user agents and Cloudflare expressions in Rule 1. They survive every ruleset update.' ],
		[ 'layers', 'Multi-Account',          'Connect several Cloudflare accounts and switch between them. Built for agencies running many client sites.' ],
		[ 'eye-off', 'User Access Control',    'Choose exactly which administrators can see the plugin. Everyone else sees no menu, widget, admin bar button or plugin listing.' ],
		[ 'key', 'Encrypted Credentials',  'API tokens are encrypted at rest with libsodium, or kept out of the database entirely with wp-config.php constants.' ],
		[ 'zap', 'Admin Bar Quick Purge',  'Purge a zone\'s cache from the WordPress admin bar on any page.' ],
		[ 'layout', 'Dashboard Widget',       'A quick summary of connected accounts and zones on the WordPress dashboard.' ],
		[ 'transfer', 'Export &amp; Import',    'Move rule settings between WordPress installs as a JSON file.' ],
	];
	?>

	<h2 class="wpwaf-about-section-title">Every screen</h2>
	<div class="wpwaf-about-features">
		<?php foreach ( $wpwaf_screens as $screen ) :
			[ $slug, $icon, $title, $desc ] = $screen;
			$hidden = isset( $screen[4] ) && WPWAF_Settings::get( $screen[4], false );
			$tag    = $hidden ? 'div' : 'a';
			$href   = $hidden ? '' : ' href="' . esc_url( admin_url( 'admin.php?page=' . $slug ) ) . '"';
		?>
		<<?php echo $tag; ?> class="wpwaf-about-feature<?php echo $hidden ? ' is-hidden' : ' is-link'; ?>"<?php echo $href; ?>>
			<span class="wpwaf-about-feature-icon"><?php echo wpwaf_about_icon( $icon ); // fixed markup ?></span>
			<div class="wpwaf-about-feature-title">
				<?php echo esc_html( $title ); ?>
				<?php if ( $hidden ) : ?><span class="wpwaf-about-feature-flag">Hidden in Settings</span><?php endif; ?>
			</div>
			<div class="wpwaf-about-feature-desc"><?php echo esc_html( $desc ); ?></div>
		</<?php echo $tag; ?>>
		<?php endforeach; ?>
	</div>

	<h2 class="wpwaf-about-section-title">Built for agencies</h2>
	<div class="wpwaf-about-features">
		<?php foreach ( $wpwaf_tools as [ $icon, $title, $desc ] ) : ?>
		<div class="wpwaf-about-feature">
			<span class="wpwaf-about-feature-icon"><?php echo wpwaf_about_icon( $icon ); // fixed markup ?></span>
			<div class="wpwaf-about-feature-title"><?php echo $title; ?></div>
			<div class="wpwaf-about-feature-desc"><?php echo esc_html( $desc ); ?></div>
		</div>
		<?php endforeach; ?>
	</div>

	<!-- Two columns: About + Get updates -->
	<div class="wpwaf-about-cols">

		<div class="wpwaf-about-card">
			<h2><?php echo wpwaf_about_icon( 'unlock', 18 ); ?> Free Forever</h2>
			<p>WP WAF Manager is <strong>free and open-source</strong>, licensed under GPL-2.0. Every feature you see in this plugin is available at no cost.</p>
			<p>The full source code is on GitHub. Download updates, report issues, fork the project, or contribute improvements. Free, forever.</p>
			<div class="wpwaf-about-cta">
				<a href="https://github.com/jaimealnassim/wpwafmanager" target="_blank" rel="noopener" class="wpwaf-about-btn wpwaf-about-btn-secondary">
					<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"/></svg>
					Download on GitHub
				</a>
			</div>
		</div>

		<div class="wpwaf-about-card" style="border-color:#fcd9c0;background:#fffaf7;">
			<h2><?php echo wpwaf_about_icon( 'star', 18 ); ?> Pro: Updates &amp; Support</h2>
			<p>The Pro version at <a href="https://www.wpwafmanager.com" target="_blank" rel="noopener" style="color:#FF6A00;font-weight:600;">wpwafmanager.com</a> is available as a <strong>one-time purchase</strong>. You get:</p>
			<ul>
				<li><strong>Automatic plugin updates</strong> delivered directly in your WordPress admin, just like any premium plugin</li>
				<li><strong>Priority support</strong> to get help when you need it, for as long as your support is active</li>
				<li><strong>Support development</strong> and help keep the rules and plugin maintained and improved for everyone</li>
			</ul>
			<p style="font-size:12px;color:#9ca3af;margin-top:4px;">The plugin itself is identical. Pro simply adds the update delivery mechanism and support access.</p>
			<div class="wpwaf-about-cta">
				<a href="https://www.wpwafmanager.com" target="_blank" rel="noopener" class="wpwaf-about-btn wpwaf-about-btn-primary">
					Get Pro <?php echo wpwaf_about_icon( 'arrow', 16 ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpwafmanager-manage-license' ) ); ?>" class="wpwaf-about-btn wpwaf-about-btn-secondary">
					<?php echo wpwaf_about_icon( 'key', 16 ); ?> Manage License
				</a>
			</div>
		</div>

	</div>

	<!-- Free vs Pro table -->
	<div class="wpwaf-about-compare">
		<table>
			<thead>
				<tr>
					<th style="width:60%;">Feature</th>
					<th>Free</th>
					<th class="col-pro">Pro</th>
				</tr>
			</thead>
			<tbody>
				<?php
				$rows = [
					[ 'WAF Rules Builder (5 rules) and one-click deploy', true, true ],
					[ 'Rule Profiles and Domain Profiles',                true, true ],
					[ 'Zones list with flares',                           true, true ],
					[ 'Custom IP, user agent and expression allowlists',  true, true ],
					[ 'DNS Manager (21 record types)',                    true, true ],
					[ 'Zone Status analytics with detail view',           true, true ],
					[ 'Clients and white-label reports by email',         true, true ],
					[ 'Zone Controls, cache purge and security headers',  true, true ],
					[ 'Bot Control and AI crawler policy',                true, true ],
					[ 'SSL checks and Security Insights',                 true, true ],
					[ 'Alerts and Health Checks',                         true, true ],
					[ 'Turnstile widget management',                      true, true ],
					[ 'Page Rules audit and migration',                   true, true ],
					[ 'IP Access Rules (account-wide)',                   true, true ],
					[ 'Security Events viewer',                           true, true ],
					[ 'Email Routing',                                    true, true ],
					[ 'Multi-account and user access control',            true, true ],
					[ 'Automatic updates in WP Admin',                    false, true ],
					[ 'Priority support',                                 false, true ],
				];
				foreach ( $rows as [ $label, $free, $pro ] ) :
				?>
				<tr>
					<td><?php echo esc_html( $label ); ?></td>
					<td><?php echo $free ? '<span class="wpwaf-about-yes" role="img" aria-label="Included">' . wpwaf_about_icon( 'check', 18 ) . '</span>' : '<span class="wpwaf-about-no" aria-label="Not included">-</span>'; ?></td>
					<td class="col-pro"><?php echo $pro ? '<span class="wpwaf-about-yes" role="img" aria-label="Included">' . wpwaf_about_icon( 'check', 18 ) . '</span>' : '<span class="wpwaf-about-no" aria-label="Not included">-</span>'; ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>

	<!-- Credits -->
	<div class="wpwaf-about-card">
		<h2><?php echo wpwaf_about_icon( 'list', 18 ); ?> Credits &amp; Resources</h2>
		<ul>
			<li>WAF rules based on the five-rule pattern by <a href="https://webagencyhero.com" target="_blank" rel="noopener">Troy Glancy (WebAgencyHero)</a>, refined by <a href="https://wafrules.com" target="_blank" rel="noopener">Michael Bourne (URSA6) at wafrules.com</a></li>
			<li>Plugin by <a href="https://www.wpwafmanager.com" target="_blank" rel="noopener">WP WAF Manager</a>, built by <a href="https://nahnuplugins.com" target="_blank" rel="noopener">Nahnu Plugins</a></li>
			<li><a href="https://github.com/jaimealnassim/wpwafmanager" target="_blank" rel="noopener">View source on GitHub</a></li>
			<li><a href="https://developers.cloudflare.com/waf/" target="_blank" rel="noopener">Cloudflare WAF Documentation</a></li>
			<li><a href="https://developers.cloudflare.com/analytics/graphql-api/" target="_blank" rel="noopener">Cloudflare GraphQL Analytics API</a></li>
		</ul>
		<div class="wpwaf-about-version">
			WP WAF Manager v<?php echo esc_html( WPWAF_VERSION ); ?> &nbsp;·&nbsp;
			PHP <?php echo esc_html( PHP_VERSION ); ?> &nbsp;·&nbsp;
			WordPress <?php echo esc_html( get_bloginfo( 'version' ) ); ?>
		</div>
	</div>

</div>
