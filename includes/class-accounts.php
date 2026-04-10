<?php
declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Manages multiple Cloudflare account credentials.
 *
 * Accounts are stored in wp_options as a serialised array.
 * The active account ID is stored separately.
 * Credentials are obfuscated with base64 — not true encryption, but prevents
 * casual plaintext reads from database exports and debug logs.
 */
class WPWAF_Accounts {

	private const OPTION_ACCOUNTS = 'wpwaf_accounts';
	private const OPTION_ACTIVE   = 'wpwaf_active_account';

	/** In-request static cache to avoid repeated get_option calls. */
	private static ?array $cache = null;

	// ── Read ──────────────────────────────────────────────────────────────────

	/** Return all saved accounts (credentials decoded). */
	public static function all(): array {
		if ( self::$cache !== null ) return self::$cache;
		$raw         = get_option( self::OPTION_ACCOUNTS, [] );
		self::$cache = is_array( $raw ) ? array_map( [ __CLASS__, 'decode' ], $raw ) : [];
		return self::$cache;
	}

	/** Return the active account ID. */
	public static function active_id(): string {
		return (string) get_option( self::OPTION_ACTIVE, '' );
	}

	/** Return the active account array, or null if none. */
	public static function active(): ?array {
		$id       = self::active_id();
		$accounts = self::all();
		foreach ( $accounts as $acc ) {
			if ( ( $acc['id'] ?? '' ) === $id ) return $acc;
		}
		return $accounts[0] ?? null;
	}

	// ── Write ─────────────────────────────────────────────────────────────────

	/** Add or update an account. Returns the account ID. */
	public static function save( array $data ): string {
		$accounts = self::all();
		$id       = $data['id'] ?? '';

		if ( empty( $id ) ) {
			$id         = 'acct_' . bin2hex( random_bytes( 6 ) );
			$data['id'] = $id;
		}

		$found = false;
		foreach ( $accounts as &$acc ) {
			if ( ( $acc['id'] ?? '' ) === $id ) {
				$acc   = array_merge( $acc, $data );
				$found = true;
				break;
			}
		}
		unset( $acc );

		if ( ! $found ) {
			$accounts[] = $data;
		}

		self::persist( $accounts );

		// Auto-activate if this is the first account.
		if ( empty( self::active_id() ) ) {
			update_option( self::OPTION_ACTIVE, $id, false );
		}

		return $id;
	}

	/** Delete an account by ID. */
	public static function delete( string $id ): void {
		$accounts = array_values(
			array_filter( self::all(), fn( $a ) => ( $a['id'] ?? '' ) !== $id )
		);
		self::persist( $accounts );

		if ( self::active_id() === $id ) {
			update_option( self::OPTION_ACTIVE, $accounts[0]['id'] ?? '', false );
		}
	}

	/** Switch the active account. */
	public static function switch_to( string $id ): void {
		update_option( self::OPTION_ACTIVE, $id, false );
	}

	// ── API factory ───────────────────────────────────────────────────────────

	/** Build a WPWAF_API instance from the active account. */
	public static function api(): WPWAF_API {
		$acc = self::active();

		if ( ! $acc ) {
			return new WPWAF_API( auth_method: 'token' );
		}

		// Auto-expire: delete the account if its credentials have expired.
		$expires = (int) ( $acc['expires_at'] ?? 0 );
		if ( $expires > 0 && time() > $expires ) {
			self::delete( $acc['id'] );
			return new WPWAF_API( auth_method: 'token' );
		}

		return new WPWAF_API(
			auth_method: $acc['auth_method'] ?? 'token',
			api_token:   $acc['api_token']   ?? '',
			email:       $acc['email']        ?? '',
			api_key:     $acc['api_key']      ?? '',
		);
	}

	// ── Migration ─────────────────────────────────────────────────────────────

	/** Migrate legacy single-account options into the multi-account system. */
	public static function migrate_legacy(): void {
		if ( ! empty( self::all() ) ) return;

		$method  = get_option( 'wpwaf_auth_method', '' );
		$token   = get_option( 'wpwaf_api_token', '' );
		$email   = get_option( 'wpwaf_email', '' );
		$api_key = get_option( 'wpwaf_api_key', '' );

		if ( empty( $method ) ) return;

		self::save( [
			'label'       => $email ?: 'Default Account',
			'auth_method' => $method,
			'api_token'   => $token,
			'email'       => $email,
			'api_key'     => $api_key,
			'expires_at'  => (int) get_option( 'wpwaf_cred_expires_at', 0 ),
		] );
	}

	// ── Internal ──────────────────────────────────────────────────────────────

	/** Encode credentials before persisting. */
	private static function encode( array $acc ): array {
		foreach ( [ 'api_token', 'api_key' ] as $field ) {
			if ( ! empty( $acc[ $field ] ) ) {
				$acc[ $field ] = base64_encode( $acc[ $field ] );
			}
		}
		$acc['_encoded'] = true;
		return $acc;
	}

	/** Decode credentials after reading. */
	private static function decode( array $acc ): array {
		if ( empty( $acc['_encoded'] ) ) return $acc;
		foreach ( [ 'api_token', 'api_key' ] as $field ) {
			if ( ! empty( $acc[ $field ] ) ) {
				$decoded = base64_decode( $acc[ $field ], strict: true );
				$acc[ $field ] = ( $decoded !== false ) ? $decoded : $acc[ $field ];
			}
		}
		unset( $acc['_encoded'] );
		return $acc;
	}

	/** Persist accounts array to the database, invalidating the in-request cache. */
	private static function persist( array $accounts ): void {
		$encoded = array_map( [ __CLASS__, 'encode' ], $accounts );
		// autoload=false: account list can be large and is only needed on WAF pages.
		update_option( self::OPTION_ACCOUNTS, $encoded, false );
		self::$cache = $accounts; // update in-request cache with decoded values
	}
}
