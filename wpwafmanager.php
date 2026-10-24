<?php
/**
 * Plugin Name:       WP WAF Manager
 * Plugin URI:        https://wpwafmanager.com
 * Description:       Manage Cloudflare from WordPress: WAF rules, DNS, bots, SSL, cache, alerts, Turnstile, Page Rules migration, IP rules and email routing across every client zone.
 * Version:           1.0.25
 * Requires at least: 6.0
 * Tested up to:      7.1
 * Requires PHP:      8.0
 * Author:            WP WAF Manager
 * Author URI:        https://www.wpwafmanager.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wpwafmanager
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

// ── Second copy guard ─────────────────────────────────────────────────────────
// If another copy of the plugin (a different folder) is already running, stop
// here before anything is defined twice, and tell the admin which copy to remove.
if ( defined( 'WPWAF_VERSION' ) ) {
	$wpwaf_dup_this    = basename( __DIR__ );
	$wpwaf_dup_running = defined( 'WPWAF_DIR' ) ? basename( untrailingslashit( (string) WPWAF_DIR ) ) : '';
	$wpwaf_dup_version = (string) WPWAF_VERSION;
	add_action( 'admin_notices', static function () use ( $wpwaf_dup_this, $wpwaf_dup_running, $wpwaf_dup_version ): void {
		if ( ! current_user_can( 'activate_plugins' ) ) return;
		printf(
			'<div class="notice notice-error"><p><strong>WP WAF Manager:</strong> %s</p></div>',
			esc_html( sprintf(
				'Two copies of the plugin are active. Version %1$s is running from the "%2$s" folder, so the copy in the "%3$s" folder was not loaded. Deactivate and delete the copy you do not use. Your settings are stored in the database and are kept.',
				$wpwaf_dup_version,
				$wpwaf_dup_running,
				$wpwaf_dup_this
			) )
		);
	} );
	unset( $wpwaf_dup_this, $wpwaf_dup_running, $wpwaf_dup_version );
	return;
}

define( 'WPWAF_VERSION', '1.0.25' );
define( 'WPWAF_DIR',     plugin_dir_path( __FILE__ ) );
define( 'WPWAF_URL',     plugin_dir_url( __FILE__ ) );

// Safe updates and deletions (folder pinning, shared settings protection).
require_once WPWAF_DIR . 'includes/class-upgrade-safety.php';
WPWAF_Upgrade_Safety::init( __FILE__ );

// ── SureCart Licensing & Auto-Updates ─────────────────────────────────────────
// Initialised on the init hook per the SDK documentation.
add_action( 'init', static function (): void {
	if ( ! class_exists( 'SureCart\Licensing\Client' ) ) {
		require_once __DIR__ . '/licensing/src/Client.php';
	}
	$client = new \SureCart\Licensing\Client( 'WP WAF Manager', 'pt_UumoJYi5t8NxZvFKzDuhtBtg', __FILE__ );
	$client->set_textdomain( 'wpwafmanager' );
	$client->settings()->add_page( [
		'type'        => 'submenu',
		'parent_slug' => 'wpwafmanager',
		'page_title'  => 'License',
		'menu_title'  => 'License',
		'capability'  => 'manage_options',
		'menu_slug'   => $client->slug . '-manage-license',
	] );
} );

// Core classes - order matters (API must load before consumers).
require_once WPWAF_DIR . 'includes/class-settings.php';
require_once WPWAF_DIR . 'includes/class-cloudflare-api.php';
require_once WPWAF_DIR . 'includes/class-accounts.php';
require_once WPWAF_DIR . 'includes/class-service-ips.php';
require_once WPWAF_DIR . 'includes/class-rule-builder.php';
require_once WPWAF_DIR . 'includes/class-profiles.php';
require_once WPWAF_DIR . 'includes/class-domain-profiles.php';
require_once WPWAF_DIR . 'includes/class-access.php';
require_once WPWAF_DIR . 'includes/class-zone-flares.php';
require_once WPWAF_DIR . 'includes/class-module.php';
require_once WPWAF_DIR . 'includes/class-bots.php';
require_once WPWAF_DIR . 'includes/class-ssl-insights.php';
require_once WPWAF_DIR . 'includes/class-alerts.php';
require_once WPWAF_DIR . 'includes/class-turnstile.php';
require_once WPWAF_DIR . 'includes/class-page-rules.php';
require_once WPWAF_DIR . 'includes/class-analytics.php';
require_once WPWAF_DIR . 'includes/class-reports.php';

// Bootstrap profiles on first run.
add_action( 'plugins_loaded', [ 'WPWAF_Profiles', 'bootstrap' ], 5 );
add_action( 'plugins_loaded', [ 'WPWAF_Domain_Profiles', 'bootstrap' ], 5 );
require_once WPWAF_DIR . 'includes/class-dns.php';
require_once WPWAF_DIR . 'includes/class-zone-status.php';
require_once WPWAF_DIR . 'includes/class-admin.php';
require_once WPWAF_DIR . 'includes/class-ajax.php';
require_once WPWAF_DIR . 'includes/class-update-notifier.php';

// ── Admin bar quick-purge & first-run notice ──────────────────────────────────
add_action( 'admin_bar_menu', static function ( WP_Admin_Bar $bar ): void {
	if ( ! WPWAF_Access::current_user_can() ) return;
	if ( ! WPWAF_Accounts::active() ) return;
	if ( ! WPWAF_Settings::get( 'admin_bar_enabled', true ) ) return;

	$zone_id   = WPWAF_Settings::get( 'admin_bar_zone_id', '' );
	$zone_name = WPWAF_Settings::get( 'admin_bar_zone_name', '' );
	$purge_all = WPWAF_Settings::get( 'admin_bar_purge_all', true );

	// If a zone is configured and direct purge is on, AJAX will handle the click.
	// Otherwise link to Zone Controls page.
	$href = ( $zone_id && $purge_all )
		? '#'
		: admin_url( 'admin.php?page=wpwafmanager-zone-controls' );

	$label = $zone_name ? '🗑 Purge: ' . $zone_name : '🗑 Purge CF Cache';

	$bar->add_node( [
		'id'    => 'wpwaf-purge',
		'title' => esc_html( $label ),
		'href'  => $href,
		'meta'  => [
			'title'       => 'WP WAF Manager: Quick Cache Purge',
			'data-zone'   => esc_attr( $zone_id ),
			'data-direct' => $zone_id && $purge_all ? '1' : '0',
		],
	] );
}, 80 );

// Admin-bar JS for direct AJAX purge, printed on admin pages and on the front
// end wherever the admin bar shows.
$wpwaf_admin_bar_purge_js = static function (): void {
	if ( ! is_admin_bar_showing() ) return;
	if ( ! WPWAF_Access::current_user_can() ) return;
	if ( ! WPWAF_Settings::get( 'admin_bar_enabled', true ) ) return;
	$zone_id   = WPWAF_Settings::get( 'admin_bar_zone_id', '' );
	$purge_all = WPWAF_Settings::get( 'admin_bar_purge_all', true );
	if ( ! $zone_id || ! $purge_all ) return;
	if ( ! WPWAF_Accounts::active() ) return;

	$zone_name = WPWAF_Settings::get( 'admin_bar_zone_name', '' );
	$label     = $zone_name ? '🗑 Purge: ' . $zone_name : '🗑 Purge CF Cache';
	$data = [
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'wpwaf_nonce' ),
		'zoneId'  => $zone_id,
		'label'   => $label,
	];
	$json = wp_json_encode( $data );
	?>
	<script id="wpwaf-adminbar-purge">
	(function(){
		var d=<?php echo $json; ?>;
		var btn=document.querySelector('#wp-admin-bar-wpwaf-purge a');
		if(!btn||!d.zoneId)return;
		btn.setAttribute('href','#');
		btn.addEventListener('click',function(e){
			e.preventDefault();
			btn.textContent='Purging\u2026';
			var fd=new FormData();
			fd.append('action','wpwaf_purge_cache');
			fd.append('nonce',d.nonce);
			fd.append('zone_id',d.zoneId);
			fd.append('mode','all');
			fetch(d.ajaxUrl,{method:'POST',body:fd})
				.then(function(r){return r.json();})
				.then(function(r){
					btn.textContent=r.success?'Cache Purged':'Failed';
					setTimeout(function(){btn.textContent=d.label;},3000);
				})
				.catch(function(){
					btn.textContent='Failed';
					setTimeout(function(){btn.textContent=d.label;},3000);
				});
		});
	})();
	</script>
	<?php
};
add_action( 'admin_footer', $wpwaf_admin_bar_purge_js );
add_action( 'wp_footer', $wpwaf_admin_bar_purge_js );


add_action( 'admin_notices', static function (): void {
	// Only on our own pages, only if no account connected.
	$screen = get_current_screen();
	if ( ! $screen || strpos( $screen->id, 'wpwafmanager' ) === false ) return;
	if ( WPWAF_Accounts::active() ) return;
	// Don't show on the main credentials page.
	if ( isset( $_GET['page'] ) && in_array( $_GET['page'], [ 'wpwafmanager', 'wpwafmanager-settings' ], true ) ) return;
	printf(
		'<div class="notice notice-warning"><p><strong>WP WAF Manager:</strong> No Cloudflare account connected. '
		. '<a href="%s">Add your API credentials →</a></p></div>',
		esc_url( admin_url( 'admin.php?page=wpwafmanager' ) )
	);
} );

add_action( 'plugins_loaded', static function (): void {
	// Migrate any options saved under the old cf_waf_* prefix (pre-rename).
	foreach ( [
		'cf_waf_accounts'             => 'wpwaf_accounts',
		'cf_waf_active_account'       => 'wpwaf_active_account',
		'cf_waf_rule_settings'        => 'wpwaf_rule_settings',
		'cf_waf_zone_status_settings' => 'wpwaf_zone_status_settings',
		'cf_waf_zone_status_cache'    => 'wpwaf_zone_status_cache',
	] as $old => $new ) {
		$val = get_option( $old );
		if ( $val !== false && get_option( $new ) === false ) {
			update_option( $new, $val, false );
			delete_option( $old );
		}
	}

	WPWAF_Accounts::migrate_legacy(); // one-time migration, exits early if not needed
	// Move saved rule settings and profiles to the October 2026 ruleset once.
	if ( get_option( 'wpwaf_rules_version' ) !== WPWAF_Service_IPs::RULES_DATE ) {
		WPWAF_Rule_Builder::migrate_stored();
	}

	WPWAF_Admin::init();
	WPWAF_Ajax::init();
	WPWAF_Zone_Status::init();
	WPWAF_Update_Notifier::init();
	WPWAF_Module::register_shared();
	WPWAF_Bots::init();
	WPWAF_SSL_Insights::init();
	WPWAF_Alerts::init();
	WPWAF_Turnstile::init();
	WPWAF_Page_Rules::init();
	WPWAF_Analytics::init();
	WPWAF_Reports::init();
} );

register_activation_hook( __FILE__, static function (): void {
	// Schedule zone-status sync cron on first activation if auto-sync is enabled.
	$s = WPWAF_Zone_Status::get_settings();
	if ( $s['enabled'] && ! wp_next_scheduled( 'wpwaf_zone_status_sync' ) ) {
		wp_schedule_single_event( time() + $s['sync_interval'], 'wpwaf_zone_status_sync' );
	}
} );

register_deactivation_hook( __FILE__, static function (): void {
	// Remove the scheduled cron event on deactivation to keep WP-Cron clean.
	$ts = wp_next_scheduled( 'wpwaf_zone_status_sync' );
	if ( $ts ) {
		wp_unschedule_event( $ts, 'wpwaf_zone_status_sync' );
	}
	// Client Reports check for due emails hourly; stop that while deactivated.
	wp_clear_scheduled_hook( 'wpwaf_reports_cron' );
} );
