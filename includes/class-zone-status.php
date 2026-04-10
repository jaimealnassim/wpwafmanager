<?php
declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Zone Status Manager — caches per-zone data and manages sync cron.
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
			? array_filter( array_map( 'sanitize_text_field', $s['allowed_zones'] ) )
			: [];
		update_option( self::OPT_SETTINGS, $s, false );
		self::reschedule_cron( $s['sync_interval'] );
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

	public static function run_sync(): void {
		$settings = self::get_settings();
		if ( ! $settings['enabled'] ) return;

		$active = WPWAF_Accounts::active();
		if ( ! $active ) return;

		$api   = new WPWAF_API(
			$active['auth_method'],
			$active['api_token']  ?? '',
			$active['email']      ?? '',
			$active['api_key']    ?? '',
		);
		$zones = $api->list_zones();
		if ( empty( $zones['success'] ) ) return;

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
		self::reschedule_cron( $settings['sync_interval'] );
	}

	public static function get_cache(): array {
		return (array) get_option( self::OPT_CACHE, [] );
	}

	public static function get_next_sync(): int {
		return (int) wp_next_scheduled( self::CRON_HOOK );
	}
}
