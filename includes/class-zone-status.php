<?php
declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Zone Status Manager - caches per-zone data and manages sync cron.
 */
class WPWAF_Zone_Status {

	private const OPT_CACHE    = 'wpwaf_zone_status_cache';
	private const OPT_SETTINGS = 'wpwaf_zone_status_settings';
	private const CRON_HOOK    = 'wpwaf_zone_status_sync';

	public static function init(): void {
		add_action( self::CRON_HOOK, [ __CLASS__, 'run_sync' ] );
	}

	// ── Settings ───────────────────────────────────────────────────────────────

	public static function get_settings(): array {
		return wp_parse_args( get_option( self::OPT_SETTINGS, [] ), [
			'enabled'        => false,
			'sync_interval'  => 3600,   // seconds
			'days_analytics' => 7,
			'allowed_zones'  => [],     // empty = all zones
		] );
	}

	public static function save_settings( array $s ): void {
		$s['enabled']        = (bool) ( $s['enabled'] ?? true );
		$s['sync_interval']  = max( 300, (int) ( $s['sync_interval'] ?? 3600 ) );
		$s['days_analytics'] = max( 1, min( 30, (int) ( $s['days_analytics'] ?? 7 ) ) );
		$s['allowed_zones']  = is_array( $s['allowed_zones'] ?? null )
			? array_values( array_filter( array_map( 'sanitize_text_field', $s['allowed_zones'] ) ) )
			: [];
		update_option( self::OPT_SETTINGS, $s, false );

		// Keep the same three values on the Settings page (and the dashboard widget) in step.
		$plugin = get_option( 'wpwaf_plugin_settings', [] );
		$plugin = is_array( $plugin ) ? $plugin : [];
		$plugin['analytics_auto_sync'] = $s['enabled'];
		$plugin['analytics_interval']  = $s['sync_interval'];
		$plugin['analytics_days']      = $s['days_analytics'];
		update_option( 'wpwaf_plugin_settings', $plugin, false );
		WPWAF_Settings::flush();

		if ( $s['enabled'] ) {
			self::reschedule_cron( $s['sync_interval'] );
		} else {
			self::clear_cron();
		}
	}

	// ── Cron ──────────────────────────────────────────────────────────────────

	public static function reschedule_cron( int $interval ): void {
		$ts = wp_next_scheduled( self::CRON_HOOK );
		if ( $ts ) wp_unschedule_event( $ts, self::CRON_HOOK );
		wp_schedule_single_event( time() + $interval, self::CRON_HOOK );
	}

	public static function clear_cron(): void {
		$ts = wp_next_scheduled( self::CRON_HOOK );
		if ( $ts ) wp_unschedule_event( $ts, self::CRON_HOOK );
	}

	// ── Sync ──────────────────────────────────────────────────────────────────

	/** WP-Cron entry point: syncs only while auto-sync is on. */
	public static function run_sync(): void {
		if ( ! self::get_settings()['enabled'] ) return;
		self::sync();
	}

	/**
	 * Fetches fresh analytics for the monitored zones and stores them for the
	 * Zone Status cards. Also used by Sync Now, which works with auto-sync off.
	 *
	 * @return array{success:bool,message?:string,zones?:int}
	 */
	public static function sync(): array {
		$settings = self::get_settings();
		$active   = WPWAF_Accounts::active();
		if ( ! $active ) return [ 'success' => false, 'message' => 'No Cloudflare account connected.' ];

		$api   = new WPWAF_API(
			$active['auth_method'],
			$active['api_token']  ?? '',
			$active['email']      ?? '',
			$active['api_key']    ?? '',
		);
		$zones = $api->list_zones();
		if ( empty( $zones['success'] ) ) {
			return [ 'success' => false, 'message' => (string) ( $zones['message'] ?? 'Could not load zones.' ) ];
		}

		$allowed = $settings['allowed_zones'] ?? [];
		$cache   = [];
		foreach ( $zones['zones'] as $zone ) {
			// Skip if zone not in allowlist (empty = all)
			if ( ! empty( $allowed ) && ! in_array( $zone['id'], $allowed, true ) ) continue;
			$cache[ $zone['id'] ] = [
				'zone'      => $zone,
				'overview'  => [ 'analytics' => array_diff_key( $api->get_zone_analytics( $zone['id'], (int)( $settings['days_analytics'] ?? 7 ) ), ['success'=>1] ) ],
				'synced_at' => time(),
			];
		}
		update_option( self::OPT_CACHE, $cache, false );
		if ( $settings['enabled'] ) {
			self::reschedule_cron( $settings['sync_interval'] );
		}
		return [ 'success' => true, 'zones' => count( $cache ) ];
	}

	public static function get_cache(): array {
		return (array) get_option( self::OPT_CACHE, [] );
	}

	public static function get_next_sync(): int {
		return (int) wp_next_scheduled( self::CRON_HOOK );
	}
}
