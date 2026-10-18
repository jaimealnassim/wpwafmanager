<?php
declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

class WPWAF_Admin {

	/**
	 * Hook suffixes WordPress returned when the plugin's screens were
	 * registered. WordPress builds submenu hook names from the translated
	 * parent menu title ("waf-manager_page_..."), so they are recorded here
	 * rather than guessed from the slugs.
	 *
	 * @var string[]
	 */
	private static array $page_hooks = [];

	public static function init(): void {
		$self = new self();
		add_action( 'admin_menu',            [ $self, 'register_menu' ] );
		add_action( 'admin_menu',            [ $self, 'maybe_hide_menu' ], 999 );
		add_action( 'admin_enqueue_scripts', [ $self, 'enqueue_assets' ] );
		add_filter( 'admin_body_class',      [ $self, 'body_class' ] );

		// Hide plugin from Plugins screen for locked-out users.
		if ( ! WPWAF_Access::current_user_can() ) {
			add_filter( 'all_plugins', static function ( array $plugins ): array {
				unset( $plugins['wpwafmanager/wpwafmanager.php'] );
				return $plugins;
			} );
		}
		// Dashboard widget - only show to users with access.
		if ( WPWAF_Access::current_user_can() && WPWAF_Settings::get( 'dashboard_widget', true ) ) {
			add_action( 'wp_dashboard_setup', [ $self, 'register_dashboard_widget' ] );
		}
	}

	/**
	 * Every submenu screen, in menu order.
	 * slug => [ menu title, render method, Settings key that hides it or '' ].
	 */
	private static function pages(): array {
		return [
			'wpwafmanager'                 => [ 'WAF Rules',       'render_page',                 '' ],
			'wpwafmanager-zones'           => [ 'Zones',           'render_zones_page',           '' ],
			'wpwafmanager-dns'             => [ 'DNS Manager',     'render_dns_page',             '' ],
			'wpwafmanager-zone-status'     => [ 'Zone Status',     'render_zone_status_page',     '' ],
			'wpwafmanager-reports'         => [ 'Clients & Reports', 'render_reports_page',         'hide_reports' ],
			'wpwafmanager-ip-rules'        => [ 'IP Access Rules', 'render_ip_rules_page',        '' ],
			'wpwafmanager-zone-controls'   => [ 'Zone Controls',   'render_zone_controls_page',   '' ],
			'wpwafmanager-bots'            => [ 'Bot Control',     'render_bots_page',            'hide_bots' ],
			'wpwafmanager-ssl-insights'    => [ 'SSL & Insights',  'render_ssl_insights_page',    'hide_ssl_insights' ],
			'wpwafmanager-security-events' => [ 'Security Events', 'render_security_events_page', 'hide_security_events' ],
			'wpwafmanager-alerts'          => [ 'Alerts & Health', 'render_alerts_page',          'hide_alerts' ],
			'wpwafmanager-turnstile'       => [ 'Turnstile',       'render_turnstile_page',       'hide_turnstile' ],
			'wpwafmanager-page-rules'      => [ 'Page Rules',      'render_page_rules_page',      'hide_page_rules' ],
			'wpwafmanager-email-routing'   => [ 'Email Routing',   'render_email_routing_page',   'hide_email_routing' ],
			'wpwafmanager-settings'        => [ 'Settings',        'render_settings_page',        '' ],
			'wpwafmanager-about'           => [ 'About',           'render_about_page',           '' ],
		];
	}

	/** Remove the plugin menu entirely for users not in the access allowlist. */
	public function maybe_hide_menu(): void {
		if ( WPWAF_Access::current_user_can() ) return;
		remove_menu_page( 'wpwafmanager' );
		// Also remove all submenu pages so they're not accessible via direct URL.
		foreach ( array_keys( self::pages() ) as $slug ) {
			if ( $slug !== 'wpwafmanager' ) {
				remove_submenu_page( 'wpwafmanager', $slug );
			}
		}
	}

	public function register_menu(): void {
		$cap = WPWAF_Settings::required_capability();
		$hook = add_menu_page(
			'WP WAF Manager',
			'WAF Manager',
			$cap,
			'wpwafmanager',
			[ $this, 'render_page' ],
			'dashicons-shield',
			80
		);
		if ( is_string( $hook ) && $hook !== '' ) {
			self::$page_hooks[] = $hook;
		}
		foreach ( self::pages() as $slug => [ $title, $method, $hide_key ] ) {
			if ( $hide_key !== '' && WPWAF_Settings::get( $hide_key, false ) ) {
				continue;
			}
			$hook = add_submenu_page( 'wpwafmanager', $title, $title, $cap, $slug, [ $this, $method ] );
			if ( is_string( $hook ) && $hook !== '' ) {
				self::$page_hooks[] = $hook;
			}
		}
	}

	/** Admin page hook suffixes that belong to this plugin. */
	private static function plugin_page_hooks(): array {
		return array_values( array_unique( self::$page_hooks ) );
	}

	/**
	 * Tag plugin pages with a body class. The shared layout and responsive
	 * stylesheets scope their selectors to it so they take precedence over each
	 * page's inline <style> block, which prints later in the document.
	 */
	public function body_class( string $classes ): string {
		$hook = $GLOBALS['hook_suffix'] ?? '';
		if ( in_array( $hook, self::plugin_page_hooks(), true ) ) {
			$classes .= ' wpwaf-admin';
		}
		return $classes;
	}

	public function enqueue_assets( string $hook ): void {
		// Only enqueue on this plugin's pages.
		if ( ! in_array( $hook, self::plugin_page_hooks(), true ) ) return;
		if ( ! current_user_can( WPWAF_Settings::required_capability() ) ) return;

		wp_enqueue_style(
			'wpwaf-layout',
			WPWAF_URL . 'assets/css/layout.css',
			[],
			WPWAF_VERSION
		);

		wp_enqueue_style(
			'wpwaf-mobile',
			WPWAF_URL . 'assets/css/mobile.css',
			[ 'wpwaf-layout' ],
			WPWAF_VERSION
		);

		wp_enqueue_script(
			'wpwaf-touch',
			WPWAF_URL . 'assets/js/touch.js',
			[],
			WPWAF_VERSION,
			true  // load in footer
		);

		// Viewport meta - WordPress admin doesn't set this; needed for mobile scaling.
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

	public function render_zones_page(): void {
		$accounts  = WPWAF_Accounts::all();
		$active    = WPWAF_Accounts::active();
		$has_creds = ! empty( $active );
		$flares    = WPWAF_Zone_Flares::all();
		$presets   = WPWAF_Zone_Flares::presets();
		$nonce     = wp_create_nonce( 'wpwaf_nonce' );
		include WPWAF_DIR . 'includes/views/page-zones.php';
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
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpwafmanager-zone-status' ) ); ?>">Zone Status</a>
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

		// ?zone=<id> opens the detail view for one zone.
		$zone_id = isset( $_GET['zone'] ) ? strtolower( sanitize_text_field( wp_unslash( (string) $_GET['zone'] ) ) ) : '';
		if ( preg_match( '/^[a-f0-9]{32}$/', $zone_id ) ) {
			$zone     = is_array( $cache[ $zone_id ]['zone'] ?? null ) ? $cache[ $zone_id ]['zone'] : [ 'id' => $zone_id, 'name' => '', 'plan' => '' ];
			$range    = isset( $_GET['range'] ) ? sanitize_key( wp_unslash( (string) $_GET['range'] ) ) : '7d';
			$range    = isset( WPWAF_Analytics::RANGES[ $range ] ) ? $range : '7d';
			$nonce    = wp_create_nonce( 'wpwaf_nonce' );
			$ajax_url = admin_url( 'admin-ajax.php' );
			include WPWAF_DIR . 'includes/views/page-zone-detail.php';
			return;
		}
		include WPWAF_DIR . 'includes/views/page-zone-status.php';
	}

	public function render_reports_page(): void {
		$this->render_module_page( 'page-reports.php' );
	}

	public function render_bots_page(): void {
		$this->render_module_page( 'page-bots.php' );
	}

	public function render_ssl_insights_page(): void {
		$this->render_module_page( 'page-ssl-insights.php' );
	}

	public function render_alerts_page(): void {
		$this->render_module_page( 'page-alerts.php' );
	}

	public function render_turnstile_page(): void {
		$this->render_module_page( 'page-turnstile.php' );
	}

	public function render_page_rules_page(): void {
		$this->render_module_page( 'page-page-rules.php' );
	}

	/** Shared view variables for the feature module screens. */
	private function render_module_page( string $view ): void {
		$accounts  = WPWAF_Accounts::all();
		$active    = WPWAF_Accounts::active();
		$has_creds = ! empty( $active );
		$nonce     = wp_create_nonce( 'wpwaf_nonce' );
		$ajax_url  = admin_url( 'admin-ajax.php' );
		include WPWAF_DIR . 'includes/views/' . $view;
	}
}
