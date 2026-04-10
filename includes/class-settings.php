<?php
declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Plugin-wide settings manager.
 * Stored as a single wp_options entry with autoload=false.
 */
class WPWAF_Settings {

	private const OPTION = 'wpwaf_plugin_settings';

	/** Default values for every setting. */
	public static function defaults(): array {
		return [
			// Admin bar
			'admin_bar_enabled'   => true,
			'admin_bar_zone_id'   => '',
			'admin_bar_zone_name' => '',
			'admin_bar_purge_all' => true,   // true = purge everything, false = go to Zone Controls

			// Zone analytics
			'analytics_auto_sync' => false,
			'analytics_interval'  => 3600,   // seconds
			'analytics_days'      => 7,

			// Access
			'required_capability' => 'manage_options',

			// Menu visibility
			'hide_security_events'  => false,
			'dashboard_widget'      => true,
			'hide_email_routing'    => false,
			'keep_data_on_uninstall' => true,
		];
	}

	/** Return all settings, merged with defaults. */
	public static function all(): array {
		$saved = get_option( self::OPTION, [] );
		return wp_parse_args( is_array( $saved ) ? $saved : [], self::defaults() );
	}

	/** Return a single setting value. */
	public static function get( string $key, mixed $fallback = null ): mixed {
		$all = self::all();
		return $all[ $key ] ?? $fallback;
	}

	/**
	 * Map a role name to its representative capability for current_user_can() checks.
	 */
	public static function required_capability(): string {
		$role_caps = [
			'administrator' => 'manage_options',
			'editor'        => 'edit_others_posts',
			'author'        => 'publish_posts',
			'contributor'   => 'edit_posts',
			'subscriber'    => 'read',
		];
		$role = self::get( 'minimum_role', 'administrator' );
		return $role_caps[ $role ] ?? 'manage_options';
	}

	/** Validate and save incoming settings array. Returns sanitised array. */
	public static function save( array $raw ): array {
		$s = [
			'admin_bar_enabled'    => (bool) ( $raw['admin_bar_enabled']    ?? true ),
			'admin_bar_zone_id'    => sanitize_text_field( $raw['admin_bar_zone_id']   ?? '' ),
			'admin_bar_zone_name'  => sanitize_text_field( $raw['admin_bar_zone_name'] ?? '' ),
			'admin_bar_purge_all'  => (bool) ( $raw['admin_bar_purge_all']  ?? true ),
			'analytics_auto_sync'  => (bool) ( $raw['analytics_auto_sync']  ?? false ),
			'analytics_interval'   => max( 300, (int) ( $raw['analytics_interval'] ?? 3600 ) ),
			'analytics_days'       => max( 1, min( 30, (int) ( $raw['analytics_days'] ?? 7 ) ) ),
			'minimum_role'         => in_array( $raw['minimum_role'] ?? '', [ 'administrator', 'editor', 'author', 'contributor', 'subscriber' ], true )
				? sanitize_text_field( $raw['minimum_role'] )
				: 'administrator',
			'hide_security_events'  => (bool) ( $raw['hide_security_events']  ?? false ),
			'dashboard_widget'      => (bool) ( $raw['dashboard_widget']      ?? true ),
			'hide_email_routing'    => (bool) ( $raw['hide_email_routing']    ?? false ),
			'keep_data_on_uninstall' => (bool) ( $raw['keep_data_on_uninstall'] ?? true ),
		];
		update_option( self::OPTION, $s, false );
		return $s;
	}
}
