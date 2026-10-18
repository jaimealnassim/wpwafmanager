<?php
declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Bot Control: AI crawler policy, managed robots.txt, Bot Fight Mode.
 *
 * Uses the zone Bot Management configuration:
 *   GET /zones/{zone_id}/bot_management
 *   PUT /zones/{zone_id}/bot_management
 * Token permission: Zone > Bot Management > Edit.
 *
 * The API reference does not say whether PUT keeps fields that are left out,
 * so every update reads the current configuration first, overlays the
 * requested changes and sends the full writable set back. Nothing the user
 * did not touch can be reset.
 */
final class WPWAF_Bots extends WPWAF_Module {

	public const PERMISSION = 'Zone > Bot Management > Edit';

	/**
	 * Fields this screen may change, with their allowed values.
	 * 'bool' marks a boolean field. Values come from the Cloudflare API
	 * reference for the Bot Management configuration.
	 */
	private const FIELDS = [
		// AI crawler policy (all plans).
		'ai_search'                       => [ 'disabled', 'block', 'only_on_ad_pages' ],
		'ai_user'                         => [ 'disabled', 'block', 'only_on_ad_pages' ],
		'ai_training'                     => [ 'disabled', 'disallow', 'block', 'only_on_ad_pages' ],
		'is_robots_txt_managed'           => 'bool',
		'bot_preference_sync_enabled'     => 'bool',
		'crawler_protection'              => [ 'enabled', 'disabled' ],
		// Bot Fight Mode (Free).
		'fight_mode'                      => 'bool',
		// Super Bot Fight Mode (Pro and above).
		'sbfm_definitely_automated'       => [ 'allow', 'block', 'managed_challenge' ],
		'sbfm_likely_automated'           => [ 'allow', 'block', 'managed_challenge' ],
		'sbfm_verified_bots'              => [ 'allow', 'block' ],
		'sbfm_static_resource_protection' => 'bool',
		'optimize_wordpress'              => 'bool',
	];

	/** Fields available on every plan. */
	private const COMMON = [ 'ai_search', 'ai_user', 'ai_training', 'is_robots_txt_managed', 'bot_preference_sync_enabled', 'crawler_protection' ];

	/** Extra fields per configuration type. */
	private const BY_TYPE = [
		'bfm'    => [ 'fight_mode' ],
		'sbfm_d' => [ 'sbfm_definitely_automated', 'sbfm_verified_bots', 'sbfm_static_resource_protection', 'optimize_wordpress' ],
		'sbfm_l' => [ 'sbfm_definitely_automated', 'sbfm_likely_automated', 'sbfm_verified_bots', 'sbfm_static_resource_protection', 'optimize_wordpress' ],
		'sub'    => [],
	];

	/** Read-only fields that must never be sent back. */
	private const READ_ONLY = [ 'stale_zone_configuration', 'using_latest_model' ];

	public static function init(): void {
		add_action( 'wp_ajax_wpwaf_bt_get',   [ self::class, 'ajax_get' ] );
		add_action( 'wp_ajax_wpwaf_bt_apply', [ self::class, 'ajax_apply' ] );
	}

	/**
	 * Which configuration type applies to a zone.
	 * The zone plan decides first (Free: Bot Fight Mode, Pro: Super Bot Fight
	 * Mode Definitely, Business: Super Bot Fight Mode Likely). Enterprise zones
	 * with the Bot Management add-on return subscription fields, which this
	 * screen leaves alone. Field presence is the fallback when the plan is unknown.
	 */
	private static function config_type( string $plan, array $config ): string {
		$p = strtolower( $plan );
		if ( array_key_exists( 'auto_update_model', $config ) || array_key_exists( 'bm_cookie_enabled', $config ) ) {
			return 'sub';
		}
		if ( str_contains( $p, 'free' ) ) return 'bfm';
		if ( str_contains( $p, 'pro' ) ) return 'sbfm_d';
		if ( str_contains( $p, 'business' ) || str_contains( $p, 'enterprise' ) ) return 'sbfm_l';
		if ( array_key_exists( 'sbfm_likely_automated', $config ) ) return 'sbfm_l';
		if ( array_key_exists( 'sbfm_definitely_automated', $config ) ) return 'sbfm_d';
		if ( array_key_exists( 'fight_mode', $config ) ) return 'bfm';
		return 'unknown';
	}

	/** Fields the screen may change for a configuration type. */
	private static function allowed_for( string $type ): array {
		return array_merge( self::COMMON, self::BY_TYPE[ $type ] ?? [] );
	}

	/** Keep only the fields the screen shows, so the browser gets a small payload. */
	private static function public_config( array $config ): array {
		return array_intersect_key( $config, self::FIELDS );
	}

	/** Validate one requested change. Returns null when the value is not allowed. */
	private static function clean_value( string $field, mixed $value ): bool|string|null {
		$rule = self::FIELDS[ $field ] ?? null;
		if ( $rule === null ) return null;
		if ( $rule === 'bool' ) {
			$b = filter_var( $value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );
			return $b;
		}
		$v = is_string( $value ) ? sanitize_key( $value ) : '';
		return in_array( $v, $rule, true ) ? $v : null;
	}

	// ── AJAX ──────────────────────────────────────────────────────────────────

	/** Current bot settings for one zone. */
	public static function ajax_get(): void {
		self::guard();
		$zone_id = self::post_id( 'zone_id' );
		$plan    = self::post( 'plan' );
		$res     = self::api()->call( 'GET', "zones/{$zone_id}/bot_management" );
		if ( ! $res['success'] ) {
			self::respond( $res, self::PERMISSION );
		}
		$config = is_array( $res['result'] ) ? $res['result'] : [];
		$type   = self::config_type( $plan, $config );
		wp_send_json_success( [
			'config'  => self::public_config( $config ),
			'type'    => $type,
			'allowed' => self::allowed_for( $type ),
		] );
	}

	/**
	 * Apply a set of changes to one zone.
	 * POST: zone_id, plan, changes (JSON object field => value).
	 * Changes that do not apply to the zone's plan are skipped and reported.
	 */
	public static function ajax_apply(): void {
		self::guard();
		$zone_id = self::post_id( 'zone_id' );
		$plan    = self::post( 'plan' );
		$changes = self::post_json( 'changes' );

		$clean   = [];
		$invalid = [];
		foreach ( $changes as $field => $value ) {
			$field = (string) $field;
			$v     = self::clean_value( $field, $value );
			if ( $v === null ) {
				$invalid[] = $field;
				continue;
			}
			$clean[ $field ] = $v;
		}
		if ( $invalid ) {
			wp_send_json_error( [ 'message' => 'Unsupported setting or value: ' . implode( ', ', array_map( 'sanitize_key', $invalid ) ) ] );
		}
		if ( ! $clean ) {
			wp_send_json_error( [ 'message' => 'No changes to apply.' ] );
		}

		$api     = self::api();
		$current = $api->call( 'GET', "zones/{$zone_id}/bot_management" );
		if ( ! $current['success'] ) {
			self::respond( $current, self::PERMISSION );
		}
		$config  = is_array( $current['result'] ) ? $current['result'] : [];
		$type    = self::config_type( $plan, $config );
		$allowed = self::allowed_for( $type );

		$apply   = array_intersect_key( $clean, array_flip( $allowed ) );
		$skipped = array_keys( array_diff_key( $clean, $apply ) );

		if ( ! $apply ) {
			wp_send_json_success( [
				'config'  => self::public_config( $config ),
				'type'    => $type,
				'allowed' => $allowed,
				'applied' => [],
				'skipped' => $skipped,
			] );
		}

		// Full writable configuration as Cloudflare returned it, plus the changes.
		$body = array_diff_key( $config, array_flip( self::READ_ONLY ) );
		foreach ( $apply as $field => $value ) {
			$body[ $field ] = $value;
		}

		$res = $api->call( 'PUT', "zones/{$zone_id}/bot_management", $body );
		if ( ! $res['success'] ) {
			self::respond( $res, self::PERMISSION );
		}
		$updated = is_array( $res['result'] ) ? $res['result'] : array_merge( $config, $apply );
		wp_send_json_success( [
			'config'  => self::public_config( $updated ),
			'type'    => $type,
			'allowed' => $allowed,
			'applied' => array_keys( $apply ),
			'skipped' => $skipped,
		] );
	}
}
