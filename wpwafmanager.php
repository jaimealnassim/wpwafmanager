<?php
/**
 * Plugin Name:       WP WAF Manager
 * Plugin URI:        https://wpwafmanager.com
 * Description:       Visual Cloudflare WAF rule builder, DNS manager, and zone analytics dashboard. Deploy battle-tested security rules to any Cloudflare zone in one click — no API docs required.
 * Version:           1.0.6
 * Requires at least: 6.0
 * Tested up to:      6.7
 * Requires PHP:      8.0
 * Author:            WP WAF Manager
 * Author URI:        https://www.wpwafmanager.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wpwafmanager
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

define( 'WPWAF_VERSION', '1.0.6' );
define( 'WPWAF_DIR',     plugin_dir_path( __FILE__ ) );
define( 'WPWAF_URL',     plugin_dir_url( __FILE__ ) );

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

// Core classes — order matters (API must load before consumers).
require_once WPWAF_DIR . 'includes/class-settings.php';
require_once WPWAF_DIR . 'includes/class-cloudflare-api.php';
require_once WPWAF_DIR . 'includes/class-accounts.php';
require_once WPWAF_DIR . 'includes/class-rule-builder.php';
require_once WPWAF_DIR . 'includes/class-dns.php';
require_once WPWAF_DIR . 'includes/class-zone-status.php';
require_once WPWAF_DIR . 'includes/class-admin.php';
require_once WPWAF_DIR . 'includes/class-ajax.php';

// ── Admin bar quick-purge & first-run notice ──────────────────────────────────
add_action( 'admin_bar_menu', static function ( WP_Admin_Bar $bar ): void {
	$cap = WPWAF_Settings::required_capability();
	if ( ! current_user_can( $cap ) || ! WPWAF_Accounts::active() ) return;
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
			'title'       => 'WP WAF Manager — Quick Cache Purge',
			'data-zone'   => esc_attr( $zone_id ),
			'data-direct' => $zone_id && $purge_all ? '1' : '0',
		],
	] );
}, 80 );

// Enqueue admin-bar JS for direct AJAX purge — using wp_footer for clean output.
add_action( 'admin_footer', static function (): void {
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
			btn.textContent='Purging…';
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
				});
		});
	})();
	</script>
	<?php
} );


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
	WPWAF_Admin::init();
	WPWAF_Ajax::init();
	WPWAF_Zone_Status::init();
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
} );
