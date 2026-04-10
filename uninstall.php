<?php
/**
 * Uninstall handler for WP WAF Manager.
 *
 * WordPress calls this file automatically when the plugin is deleted via
 * Plugins → Delete. It only runs on delete, not on deactivation.
 *
 * We check the keep_data_on_uninstall setting before removing anything.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Load just enough to read the setting — no full plugin bootstrap needed.
$settings = get_option( 'wpwaf_plugin_settings', [] );
$keep     = isset( $settings['keep_data_on_uninstall'] )
    ? (bool) $settings['keep_data_on_uninstall']
    : true; // default: keep data, fail-safe

if ( $keep ) {
    // User chose to keep data — do nothing.
    return;
}

// ── Delete all plugin data ────────────────────────────────────────────────────

$options_to_delete = [
    // Accounts & credentials
    'wpwaf_accounts',
    'wpwaf_active_account',

    // WAF rule settings
    'wpwaf_rule_settings',

    // Zone analytics
    'wpwaf_zone_status_settings',
    'wpwaf_zone_status_cache',

    // Plugin-wide settings (must be last so the keep flag is gone)
    'wpwaf_plugin_settings',

    // Legacy keys (from old cloudflare-waf-manager slug, if migration ran)
    'cf_waf_accounts',
    'cf_waf_active_account',
    'cf_waf_rule_settings',
    'cf_waf_zone_status_settings',
    'cf_waf_zone_status_cache',
];

foreach ( $options_to_delete as $option ) {
    delete_option( $option );
}

// Remove the WP-Cron scheduled event.
$ts = wp_next_scheduled( 'wpwaf_zone_status_sync' );
if ( $ts ) {
    wp_unschedule_event( $ts, 'wpwaf_zone_status_sync' );
}

// Remove the SureCart licensing transient cache if present.
$sc_transient = 'surecart_' . md5( 'wpwafmanager' ) . '_version_info';
delete_transient( $sc_transient );

// Multisite: remove options from every sub-site.
if ( is_multisite() ) {
    $sites = get_sites( [ 'number' => 0, 'fields' => 'ids' ] );
    foreach ( $sites as $site_id ) {
        switch_to_blog( $site_id );
        foreach ( $options_to_delete as $option ) {
            delete_option( $option );
        }
        restore_current_blog();
    }
}
