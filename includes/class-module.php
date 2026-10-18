<?php
declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Shared base for feature modules (Bot Control, SSL & Insights, Alerts &
 * Health, Turnstile, Page Rules).
 *
 * Each module is a final class extending this one with a static init() that
 * registers its own wp_ajax_* actions. Every handler starts with
 * static::guard(), which applies the same nonce, access-list and
 * connected-account checks as the core AJAX handlers.
 */
abstract class WPWAF_Module {

	/** Nonce, plugin access list, and (optionally) a connected Cloudflare account. */
	protected static function guard( bool $needs_account = true ): void {
		check_ajax_referer( 'wpwaf_nonce', 'nonce' );
		if ( ! WPWAF_Access::current_user_can() ) {
			wp_send_json_error( [ 'message' => 'Insufficient permissions.' ], 403 );
		}
		if ( $needs_account && ! WPWAF_Accounts::active() ) {
			wp_send_json_error( [ 'message' => 'No Cloudflare account connected.' ], 400 );
		}
	}

	/** API client for the active plugin account. */
	protected static function api(): WPWAF_API {
		return WPWAF_Accounts::api();
	}

	/** Sanitised string from $_POST. */
	protected static function post( string $key, string $default = '' ): string {
		return isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( (string) $_POST[ $key ] ) ) : $default;
	}

	/** Cloudflare zone or account ID from $_POST: 32 lowercase hex characters. */
	protected static function post_id( string $key ): string {
		$id = strtolower( self::post( $key ) );
		if ( ! preg_match( '/^[a-f0-9]{32}$/', $id ) ) {
			wp_send_json_error( [ 'message' => "A valid {$key} is required." ] );
		}
		return $id;
	}

	/** Boolean from $_POST ('1', 'true', 'on', 'yes'). */
	protected static function post_bool( string $key ): bool {
		return filter_var( $_POST[ $key ] ?? false, FILTER_VALIDATE_BOOLEAN );
	}

	/** JSON-decoded array from $_POST (for structured payloads). */
	protected static function post_json( string $key ): array {
		$raw = isset( $_POST[ $key ] ) ? wp_unslash( (string) $_POST[ $key ] ) : '';
		$val = json_decode( $raw, true );
		return is_array( $val ) ? $val : [];
	}

	/** True when a Cloudflare error means the token lacks a permission. */
	protected static function is_permission_error( array $res ): bool {
		if ( in_array( (int) ( $res['code'] ?? 0 ), [ 9109, 10000, 10001 ], true ) ) {
			return true;
		}
		$msg = (string) ( $res['message'] ?? '' );
		foreach ( [ 'Authentication error', 'Unauthorized', 'not authorized', 'Forbidden', 'permission' ] as $s ) {
			if ( stripos( $msg, $s ) !== false ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Send a normalised WPWAF_API::call() result to the browser.
	 * $permission is the token permission the feature needs, shown to the user
	 * when Cloudflare rejects the call for lack of access.
	 */
	protected static function respond( array $res, string $permission = '', array $extra = [] ): void {
		if ( ! empty( $res['success'] ) ) {
			wp_send_json_success( array_merge( [ 'result' => $res['result'] ?? null ], $extra ) );
		}
		$out = [
			'message' => (string) ( $res['message'] ?? 'Cloudflare API error' ),
			'code'    => (int) ( $res['code'] ?? 0 ),
		];
		if ( self::is_permission_error( $res ) ) {
			$out['perm_error'] = true;
			if ( $permission !== '' ) {
				$out['message'] = "Your API token is missing a permission. Add {$permission} to the token in Cloudflare, then try again. ({$out['message']})";
			}
		}
		wp_send_json_error( $out );
	}

	// ── Shared endpoint: Cloudflare accounts for account-level screens ────────

	public static function register_shared(): void {
		add_action( 'wp_ajax_wpwaf_cf_accounts', [ static::class, 'ajax_cf_accounts' ] );
	}

	/** List the Cloudflare accounts the active credential can access. */
	public static function ajax_cf_accounts(): void {
		self::guard();
		$res = self::api()->list_cf_accounts();
		if ( empty( $res['success'] ) ) {
			self::respond( $res );
		}
		wp_send_json_success( [ 'accounts' => $res['accounts'] ] );
	}
}
