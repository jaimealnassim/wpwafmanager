<?php
declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * SSL & Insights: certificate expiry and HTTPS checks, Cloudflare Security Insights.
 *
 * Certificates & HTTPS (zone level)
 *   GET   /zones/{zone_id}/ssl/certificate_packs?status=all
 *   GET   /zones/{zone_id}/settings/{setting_id}   (one call per setting; the
 *         bulk /zones/{zone_id}/settings endpoint is deprecated)
 *   PATCH /zones/{zone_id}/settings/{setting_id}   (safe one-click fixes only)
 *
 * Security Insights (account level)
 *   GET   /accounts/{account_id}/security-center/insights
 *   PUT   /accounts/{account_id}/security-center/insights/{issue_id}/dismiss
 */
final class WPWAF_SSL_Insights extends WPWAF_Module {

	/** Last scan results, keyed by plugin account ID then zone ID. autoload=false. */
	public const CACHE_OPTION = 'wpwaf_si_scan_cache';

	private const PERM_CERTS     = 'Zone → SSL and Certificates → Read';
	private const PERM_SETTINGS  = 'Zone → Zone Settings → Read';
	private const PERM_FIX       = 'Zone → Zone Settings → Edit';
	private const PERM_INSIGHTS  = 'Account → Security Center Insights → Read';
	private const PERM_DISMISS   = 'Account → Security Center Insights → Edit';

	/** HTTPS-related zone settings read during a scan. */
	private const SETTINGS = [ 'ssl', 'always_use_https', 'automatic_https_rewrites', 'min_tls_version', 'tls_1_3', 'security_header' ];

	/**
	 * One-click fixes: fix key => [ setting_id, value ]. Nothing else can be
	 * changed from this screen. SSL mode can only be raised to Full or Full
	 * (strict), never lowered; the screen asks for confirmation first because
	 * the origin must accept HTTPS (Full) or have a valid certificate (strict).
	 */
	private const FIXES = [
		'always_use_https'         => [ 'always_use_https', 'on' ],
		'automatic_https_rewrites' => [ 'automatic_https_rewrites', 'on' ],
		'min_tls_version'          => [ 'min_tls_version', '1.2' ],
		'ssl_full'                 => [ 'ssl', 'full' ],
		'ssl_strict'               => [ 'ssl', 'strict' ],
	];

	public static function init(): void {
		add_action( 'wp_ajax_wpwaf_si_zone_scan', [ self::class, 'ajax_zone_scan' ] );
		add_action( 'wp_ajax_wpwaf_si_fix',       [ self::class, 'ajax_fix' ] );
		add_action( 'wp_ajax_wpwaf_si_insights',  [ self::class, 'ajax_insights' ] );
		add_action( 'wp_ajax_wpwaf_si_dismiss',   [ self::class, 'ajax_dismiss' ] );
	}

	// ── Scan cache ────────────────────────────────────────────────────────────

	/** Cached scans for the active plugin account: zone_id => scan. */
	public static function cached_scans(): array {
		$all = get_option( self::CACHE_OPTION, [] );
		$key = WPWAF_Accounts::active_id();
		return ( is_array( $all ) && isset( $all[ $key ] ) && is_array( $all[ $key ] ) ) ? $all[ $key ] : [];
	}

	private static function store_scan( string $zone_id, array $scan ): void {
		$all = get_option( self::CACHE_OPTION, [] );
		$all = is_array( $all ) ? $all : [];
		$key = WPWAF_Accounts::active_id();
		$mine = isset( $all[ $key ] ) && is_array( $all[ $key ] ) ? $all[ $key ] : [];
		$mine[ $zone_id ] = $scan;
		// Keep the option bounded on very large accounts.
		if ( count( $mine ) > 500 ) {
			uasort( $mine, static fn( $a, $b ) => ( $b['scanned_at'] ?? 0 ) <=> ( $a['scanned_at'] ?? 0 ) );
			$mine = array_slice( $mine, 0, 500, true );
		}
		$all[ $key ] = $mine;
		update_option( self::CACHE_OPTION, $all, false );
	}

	// ── Certificates & HTTPS ──────────────────────────────────────────────────

	/** Scan one zone: certificate packs plus the HTTPS settings. Partial results are kept. */
	public static function ajax_zone_scan(): void {
		self::guard();
		$zone_id = self::post_id( 'zone_id' );
		$api     = self::api();
		$errors  = [];

		$certs = null;
		$res   = $api->call_all( "zones/{$zone_id}/ssl/certificate_packs?status=all", 50, 10 );
		if ( $res['success'] ) {
			$certs = self::summarise_packs( is_array( $res['result'] ) ? $res['result'] : [] );
		} else {
			$errors['certs'] = self::error_text( $res, self::PERM_CERTS );
		}

		$settings = [];
		foreach ( self::SETTINGS as $id ) {
			$r = $api->call( 'GET', "zones/{$zone_id}/settings/{$id}" );
			if ( $r['success'] && is_array( $r['result'] ) ) {
				$settings[ $id ] = [
					'value'    => self::clean_value( $id, $r['result']['value'] ?? null ),
					'editable' => (bool) ( $r['result']['editable'] ?? true ),
				];
			} elseif ( ! $r['success'] ) {
				// One permission message is enough; record the first failure only.
				if ( ! isset( $errors['settings'] ) ) {
					$errors['settings'] = self::error_text( $r, self::PERM_SETTINGS );
				}
			}
		}

		$scan = [
			'scanned_at' => time(),
			'certs'      => $certs,
			'settings'   => $settings,
			'errors'     => $errors,
		];
		self::store_scan( $zone_id, $scan );
		wp_send_json_success( [ 'scan' => $scan ] );
	}

	/** Apply one of the allowlisted fixes to a zone. */
	public static function ajax_fix(): void {
		self::guard();
		$zone_id = self::post_id( 'zone_id' );
		$fix     = self::post( 'fix' );
		if ( ! isset( self::FIXES[ $fix ] ) ) {
			wp_send_json_error( [ 'message' => 'That setting cannot be changed from this screen.' ] );
		}
		[ $setting, $value ] = self::FIXES[ $fix ];
		$res = self::api()->call( 'PATCH', "zones/{$zone_id}/settings/{$setting}", [ 'value' => $value ] );
		if ( $res['success'] ) {
			// Keep the cached scan in step so a reload shows the fixed value.
			$cache = self::cached_scans();
			if ( isset( $cache[ $zone_id ]['settings'][ $setting ] ) ) {
				$scan = $cache[ $zone_id ];
				$scan['settings'][ $setting ]['value'] = self::clean_value( $setting, $res['result']['value'] ?? $value );
				self::store_scan( $zone_id, $scan );
			}
			wp_send_json_success( [ 'setting' => $setting, 'value' => self::clean_value( $setting, $res['result']['value'] ?? $value ) ] );
		}
		self::respond( $res, self::PERM_FIX );
	}

	/**
	 * Reduce certificate packs to what the screen shows: type, status, CA,
	 * hosts and the soonest certificate expiry. Deleted packs are skipped.
	 */
	private static function summarise_packs( array $packs ): array {
		$out = [];
		foreach ( $packs as $p ) {
			if ( ! is_array( $p ) || ( $p['status'] ?? '' ) === 'deleted' ) {
				continue;
			}
			$soonest = null;
			$issuer  = '';
			foreach ( (array) ( $p['certificates'] ?? [] ) as $c ) {
				$exp = isset( $c['expires_on'] ) ? strtotime( (string) $c['expires_on'] ) : false;
				if ( $exp && ( $soonest === null || $exp < $soonest ) ) {
					$soonest = $exp;
					$issuer  = (string) ( $c['issuer'] ?? '' );
				}
			}
			$hosts = array_values( array_filter( array_map( 'strval', (array) ( $p['hosts'] ?? [] ) ) ) );
			$out[] = [
				'type'       => sanitize_key( (string) ( $p['type'] ?? '' ) ),
				'status'     => sanitize_key( (string) ( $p['status'] ?? '' ) ),
				'ca'         => sanitize_key( (string) ( $p['certificate_authority'] ?? '' ) ),
				'issuer'     => sanitize_text_field( $issuer ),
				'expires_on' => $soonest ?: 0,
				'hosts'      => array_map( 'sanitize_text_field', array_slice( $hosts, 0, 10 ) ),
				'host_count' => count( $hosts ),
			];
		}
		return $out;
	}

	/** Keep setting values to known shapes before caching or sending them. */
	private static function clean_value( string $id, mixed $value ): mixed {
		if ( $id === 'security_header' ) {
			$hsts = is_array( $value ) && is_array( $value['strict_transport_security'] ?? null ) ? $value['strict_transport_security'] : [];
			return [
				'enabled'            => (bool) ( $hsts['enabled'] ?? false ),
				'max_age'            => (int) ( $hsts['max_age'] ?? 0 ),
				'include_subdomains' => (bool) ( $hsts['include_subdomains'] ?? false ),
				'preload'            => (bool) ( $hsts['preload'] ?? false ),
			];
		}
		return is_scalar( $value ) ? sanitize_text_field( (string) $value ) : '';
	}

	/** Plain error text, with the missing permission named when that is the cause. */
	private static function error_text( array $res, string $permission ): string {
		$msg = (string) ( $res['message'] ?? 'Cloudflare API error' );
		if ( self::is_permission_error( $res ) ) {
			return "Missing token permission: {$permission}.";
		}
		return $msg;
	}

	// ── Security Insights ─────────────────────────────────────────────────────

	/**
	 * List insights for a Cloudflare account. view: 'active' (not dismissed),
	 * 'dismissed', or 'all' (both lists merged).
	 */
	public static function ajax_insights(): void {
		self::guard();
		$account_id = self::post_id( 'account_id' );
		$view       = self::post( 'view', 'active' );
		$wanted     = match ( $view ) {
			'dismissed' => [ 'true' ],
			'all'       => [ 'false', 'true' ],
			default     => [ 'false' ],
		};

		$issues = [];
		foreach ( $wanted as $dismissed ) {
			$page = 1;
			$seen = 0; // issues fetched for this list, counted on their own
			do {
				$res = self::api()->call( 'GET', "accounts/{$account_id}/security-center/insights?dismissed={$dismissed}&per_page=1000&page={$page}" );
				if ( ! $res['success'] ) {
					self::respond( $res, self::PERM_INSIGHTS );
				}
				$result = is_array( $res['result'] ) ? $res['result'] : [];
				$batch  = is_array( $result['issues'] ?? null ) ? $result['issues'] : [];
				foreach ( $batch as $i ) {
					if ( is_array( $i ) && ! empty( $i['id'] ) ) {
						$issues[] = self::clean_issue( $i );
					}
				}
				$seen += count( $batch );
				$count = (int) ( $result['count'] ?? 0 );
				$page++;
			} while ( ! empty( $batch ) && $seen < $count && $page <= 10 );
		}

		wp_send_json_success( [ 'issues' => $issues ] );
	}

	/** Dismiss (archive) or restore an insight. */
	public static function ajax_dismiss(): void {
		self::guard();
		$account_id = self::post_id( 'account_id' );
		$issue_id   = self::post( 'issue_id' );
		if ( $issue_id === '' || ! preg_match( '/^[A-Za-z0-9_-]{1,128}$/', $issue_id ) ) {
			wp_send_json_error( [ 'message' => 'A valid issue_id is required.' ] );
		}
		$dismiss = self::post_bool( 'dismiss' );
		$res     = self::api()->call( 'PUT', "accounts/{$account_id}/security-center/insights/{$issue_id}/dismiss", [ 'dismiss' => $dismiss ] );
		if ( $res['success'] ) {
			wp_send_json_success( [ 'issue_id' => $issue_id, 'dismissed' => $dismiss ] );
		}
		self::respond( $res, self::PERM_DISMISS );
	}

	private static function clean_issue( array $i ): array {
		$link = (string) ( $i['resolve_link'] ?? '' );
		return [
			'id'          => sanitize_text_field( (string) $i['id'] ),
			'dismissed'   => (bool) ( $i['dismissed'] ?? false ),
			'issue_class' => sanitize_text_field( (string) ( $i['issue_class'] ?? '' ) ),
			'issue_type'  => sanitize_key( (string) ( $i['issue_type'] ?? '' ) ),
			'severity'    => strtolower( sanitize_text_field( (string) ( $i['severity'] ?? '' ) ) ),
			'subject'     => sanitize_text_field( (string) ( $i['subject'] ?? '' ) ),
			'since'       => sanitize_text_field( (string) ( $i['since'] ?? '' ) ),
			'status'      => sanitize_key( (string) ( $i['status'] ?? '' ) ),
			'zone_tag'    => sanitize_text_field( (string) ( $i['payload']['zone_tag'] ?? '' ) ),
			'resolve_text' => sanitize_text_field( (string) ( $i['resolve_text'] ?? '' ) ),
			'resolve_link' => str_starts_with( $link, 'https://' ) ? esc_url_raw( $link ) : '',
		];
	}
}
