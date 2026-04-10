<?php
declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

class WPWAF_Admin {

	public static function init(): void {
		$self = new self();
		add_action( 'admin_menu',            [ $self, 'register_menu' ] );
		add_action( 'admin_enqueue_scripts', [ $self, 'enqueue_assets' ] );
		// Dashboard widget — hooked here so $self is always available.
		if ( WPWAF_Settings::get( 'dashboard_widget', true ) ) {
			add_action( 'wp_dashboard_setup', [ $self, 'register_dashboard_widget' ] );
		}
	}

	public function register_menu(): void {
		$cap = WPWAF_Settings::required_capability();
		add_menu_page(
			'WP WAF Manager',
			'WAF Manager',
			$cap,
			'wpwafmanager',
			[ $this, 'render_page' ],
			'dashicons-shield',
			80
		);
		add_submenu_page(
			'wpwafmanager',
			'WAF Rules',
			'WAF Rules',
			$cap,
			'wpwafmanager',
			[ $this, 'render_page' ]
		);
		add_submenu_page(
			'wpwafmanager',
			'DNS Manager',
			'DNS Manager',
			$cap,
			'wpwafmanager-dns',
			[ $this, 'render_dns_page' ]
		);
		add_submenu_page(
			'wpwafmanager',
			'Zone Status',
			'Zone Status',
			$cap,
			'wpwafmanager-zone-status',
			[ $this, 'render_zone_status_page' ]
		);
		add_submenu_page(
			'wpwafmanager',
			'IP Access Rules',
			'IP Access Rules',
			$cap,
			'wpwafmanager-ip-rules',
			[ $this, 'render_ip_rules_page' ]
		);
		add_submenu_page(
			'wpwafmanager',
			'Zone Controls',
			'Zone Controls',
			$cap,
			'wpwafmanager-zone-controls',
			[ $this, 'render_zone_controls_page' ]
		);
		if ( ! WPWAF_Settings::get( 'hide_security_events', false ) ) {
			add_submenu_page(
				'wpwafmanager',
				'Security Events',
				'Security Events',
				$cap,
				'wpwafmanager-security-events',
				[ $this, 'render_security_events_page' ]
			);
		}
		if ( ! WPWAF_Settings::get( 'hide_email_routing', false ) ) {
			add_submenu_page(
				'wpwafmanager',
				'Email Routing',
				'Email Routing',
				$cap,
				'wpwafmanager-email-routing',
				[ $this, 'render_email_routing_page' ]
			);
		}
		add_submenu_page(
			'wpwafmanager',
			'Settings',
			'Settings',
			$cap,
			'wpwafmanager-settings',
			[ $this, 'render_settings_page' ]
		);
		add_submenu_page(
			'wpwafmanager',
			'About',
			'About',
			$cap,
			'wpwafmanager-about',
			[ $this, 'render_about_page' ]
		);
	}

	public function enqueue_assets( string $hook ): void {
		// Only enqueue on this plugin's pages.
		$plugin_pages = [
			'toplevel_page_wpwafmanager',
			'wpwafmanager_page_wpwafmanager-dns',
			'wpwafmanager_page_wpwafmanager-zone-status',
			'wpwafmanager_page_wpwafmanager-ip-rules',
			'wpwafmanager_page_wpwafmanager-zone-controls',
			'wpwafmanager_page_wpwafmanager-security-events',
			'wpwafmanager_page_wpwafmanager-email-routing',
			'wpwafmanager_page_wpwafmanager-settings',
			'wpwafmanager_page_wpwafmanager-about',
		];
		if ( ! in_array( $hook, $plugin_pages, true ) ) return;
		if ( ! current_user_can( WPWAF_Settings::required_capability() ) ) return;

		wp_enqueue_style(
			'wpwaf-mobile',
			WPWAF_URL . 'assets/css/mobile.css',
			[],
			WPWAF_VERSION
		);

		wp_enqueue_script(
			'wpwaf-touch',
			WPWAF_URL . 'assets/js/touch.js',
			[],
			WPWAF_VERSION,
			true  // load in footer
		);

		// Viewport meta — WordPress admin doesn't set this; needed for mobile scaling.
		add_action( 'admin_head', static function (): void {
			echo '<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">' . "\n";
		} );
	}

	public function render_page(): void {
		$accounts    = WPWAF_Accounts::all();
		$active      = WPWAF_Accounts::active();
		$active_id   = WPWAF_Accounts::active_id();
		$auth_method = $active['auth_method'] ?? 'token';
		$api_token   = $active['api_token']   ?? '';
		$email       = $active['email']        ?? '';
		$api_key     = $active['api_key']      ?? '';
		include WPWAF_DIR . 'includes/views/page-main.php';
	}

	public function render_dns_page(): void {
		$accounts  = WPWAF_Accounts::all();
		$active    = WPWAF_Accounts::active();
		$has_creds = ! empty( $active );
		include WPWAF_DIR . 'includes/views/page-dns.php';
	}

	public function render_zone_controls_page(): void {
		$active    = WPWAF_Accounts::active();
		$has_creds = ! empty( $active );
		include WPWAF_DIR . 'includes/views/page-zone-controls.php';
	}

	public function render_email_routing_page(): void {
		$active    = WPWAF_Accounts::active();
		$has_creds = ! empty( $active );
		include WPWAF_DIR . 'includes/views/page-email-routing.php';
	}

	public function render_settings_page(): void {
		$settings  = WPWAF_Settings::all();
		$has_creds = ! empty( WPWAF_Accounts::active() );
		include WPWAF_DIR . 'includes/views/page-settings.php';
	}

	public function register_dashboard_widget(): void {
		wp_add_dashboard_widget(
			'wpwaf_dashboard_widget',
			'🛡 WP WAF Manager',
			[ $this, 'render_dashboard_widget' ]
		);
	}

	public function render_dashboard_widget(): void {
		$active = WPWAF_Accounts::active();
		$cache  = WPWAF_Zone_Status::get_cache();
		$next   = WPWAF_Zone_Status::get_next_sync();
		?>
		<style>
		.wpwaf-dw{font-size:13px;}
		.wpwaf-dw-row{display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #f0f0f0;}
		.wpwaf-dw-row:last-child{border:none;}
		.wpwaf-dw-label{color:#666;}
		.wpwaf-dw-val{font-weight:600;color:#1a1a2e;}
		.wpwaf-dw-ok{color:#059669;}.wpwaf-dw-off{color:#6b7280;}
		.wpwaf-dw-links{margin-top:12px;display:flex;gap:10px;flex-wrap:wrap;}
		.wpwaf-dw-links a{font-size:12px;font-weight:600;text-decoration:none;color:#FF6A00;}
		.wpwaf-dw-links a:hover{text-decoration:underline;}
		</style>
		<div class="wpwaf-dw">
		<?php if ( ! $active ) : ?>
			<p style="color:#92400e;background:#fff8f5;padding:8px 12px;border-radius:6px;margin:0;">
				⚠ No account connected. <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpwafmanager' ) ); ?>">Add credentials →</a>
			</p>
		<?php else :
			$zone_count  = count( $cache );
			$auto_sync   = WPWAF_Settings::get( 'analytics_auto_sync', false );
			$last_sync   = 0;
			foreach ( $cache as $z ) $last_sync = max( $last_sync, $z['synced_at'] ?? 0 );
		?>
			<div class="wpwaf-dw-row">
				<span class="wpwaf-dw-label">Account</span>
				<span class="wpwaf-dw-val"><?php echo esc_html( $active['label'] ?? 'Connected' ); ?></span>
			</div>
			<div class="wpwaf-dw-row">
				<span class="wpwaf-dw-label">Zones monitored</span>
				<span class="wpwaf-dw-val"><?php echo $zone_count ? esc_html( $zone_count ) : '<span class="wpwaf-dw-off">None selected</span>'; ?></span>
			</div>
			<div class="wpwaf-dw-row">
				<span class="wpwaf-dw-label">Auto-sync</span>
				<span class="wpwaf-dw-val <?php echo $auto_sync ? 'wpwaf-dw-ok' : 'wpwaf-dw-off'; ?>"><?php echo $auto_sync ? '✓ On' : 'Off'; ?></span>
			</div>
			<div class="wpwaf-dw-row">
				<span class="wpwaf-dw-label">Last sync</span>
				<span class="wpwaf-dw-val"><?php echo $last_sync ? esc_html( human_time_diff( $last_sync ) . ' ago' ) : '<span class="wpwaf-dw-off">Never</span>'; ?></span>
			</div>
			<?php if ( $next ) : ?>
			<div class="wpwaf-dw-row">
				<span class="wpwaf-dw-label">Next sync</span>
				<span class="wpwaf-dw-val wpwaf-dw-ok"><?php echo esc_html( 'in ~' . ceil( ( $next - time() ) / 60 ) . 'm' ); ?></span>
			</div>
			<?php endif; ?>
			<div class="wpwaf-dw-links">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpwafmanager-zone-controls' ) ); ?>">Zone Controls</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpwafmanager-zone-status' ) ); ?>">Zone Analytics</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpwafmanager' ) ); ?>">WAF Rules</a>
			</div>
		<?php endif; ?>
		</div>
		<?php
	}

	public function render_about_page(): void {
		include WPWAF_DIR . 'includes/views/page-about.php';
	}

	public function render_security_events_page(): void {
		$active    = WPWAF_Accounts::active();
		$has_creds = ! empty( $active );
		include WPWAF_DIR . 'includes/views/page-security-events.php';
	}

	public function render_ip_rules_page(): void {
		$accounts  = WPWAF_Accounts::all();
		$active    = WPWAF_Accounts::active();
		$has_creds = ! empty( $active );
		include WPWAF_DIR . 'includes/views/page-ip-rules.php';
	}

	public function render_zone_status_page(): void {
		$accounts  = WPWAF_Accounts::all();
		$active    = WPWAF_Accounts::active();
		$has_creds = ! empty( $active );
		$settings  = WPWAF_Zone_Status::get_settings();
		$cache     = WPWAF_Zone_Status::get_cache();
		$next_sync = WPWAF_Zone_Status::get_next_sync();
		include WPWAF_DIR . 'includes/views/page-zone-status.php';
	}
}
