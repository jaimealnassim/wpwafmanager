<?php
declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Manages multiple Cloudflare account credentials.
 *
 * Accounts are stored in wp_options as a serialised array.
 * The active account ID is stored separately.
 *
 * Storage security
 * ────────────────
 * Credentials are encrypted at rest using libsodium (sodium_crypto_secretbox).
 * The 256-bit encryption key is derived from WordPress's AUTH_KEY constant via
 * sodium_crypto_generichash, so it is site-specific and never stored in the DB.
 * Each encrypted value is stored as "enc2:<base64(nonce . ciphertext)>".
 *
 * Legacy entries written by v1.0.x (plain base64, flagged with _encoded=true)
 * are transparently decoded and re-encrypted on the next write.
 *
 * wp-config constants (optional, highest priority)
 * ──────────────────────────────────────────────────
 * Define any of these in wp-config.php to bypass database storage entirely:
 *
 *   define( 'WPWAF_API_TOKEN', 'your-cloudflare-api-token' );
 *
 * For Global API Key auth instead:
 *   define( 'WPWAF_API_EMAIL', 'you@example.com' );
 *   define( 'WPWAF_API_KEY',   'your-global-api-key' );
 *
 * When a constant account is active, it is read-only — it cannot be edited
 * or deleted from the plugin UI.
 */
class WPWAF_Accounts {

	private const OPTION_ACCOUNTS = 'wpwaf_accounts';
	private const OPTION_ACTIVE   = 'wpwaf_active_account';
	private const CONST_ACCOUNT_PREFIX = 'const_account_';

	/** Encryption prefix — distinguishes sodium ciphertext from legacy base64. */
	private const ENC_PREFIX = 'enc2:';

	/** In-request static cache to avoid repeated get_option calls. */
	private static ?array $cache = null;

	// ── wp-config constant account ────────────────────────────────────────────

	/**
	 * Return virtual accounts built from wp-config constants.
	 *
	 * Supports two formats:
	 *
	 * Single account (convenience):
	 *   define( 'WPWAF_API_TOKEN', 'token' );
	 *   define( 'WPWAF_API_LABEL', 'My Agency' ); // optional label
	 *
	 * Multiple accounts with labels:
	 *   define( 'WPWAF_ACCOUNTS', [
	 *     [ 'label' => 'Main Site',  'api_token' => 'token1' ],
	 *     [ 'label' => 'Client A',   'api_token' => 'token2' ],
	 *     [ 'label' => 'Client B',   'api_email' => 'x@x.com', 'api_key' => 'key' ],
	 *   ] );
	 *
	 * Returns an empty array if no credential constants are defined.
	 */
	public static function constant_accounts(): array {
		// Multi-account array constant takes priority.
		if ( defined( 'WPWAF_ACCOUNTS' ) && is_array( WPWAF_ACCOUNTS ) ) {
			$accounts = [];
			foreach ( WPWAF_ACCOUNTS as $i => $def ) {
				if ( ! is_array( $def ) ) continue;
				$token   = (string) ( $def['api_token'] ?? '' );
				$email   = (string) ( $def['api_email'] ?? $def['email'] ?? '' );
				$api_key = (string) ( $def['api_key']   ?? '' );
				if ( empty( $token ) && ( empty( $email ) || empty( $api_key ) ) ) continue;
				$method  = ! empty( $token ) ? 'token' : 'key';
				$label   = (string) ( $def['label'] ?? ( 'wp-config.php account ' . ( $i + 1 ) ) );
				$accounts[] = [
					'id'          => self::CONST_ACCOUNT_PREFIX . $i,
					'label'       => $label . ' (wp-config.php)',
					'auth_method' => $method,
					'api_token'   => $token,
					'email'       => $email,
					'api_key'     => $api_key,
					'expires_at'  => 0,
					'_constant'   => true,
				];
			}
			return $accounts;
		}

		// Single-constant fallback.
		$token   = defined( 'WPWAF_API_TOKEN' ) ? (string) WPWAF_API_TOKEN : '';
		$email   = defined( 'WPWAF_API_EMAIL' ) ? (string) WPWAF_API_EMAIL : '';
		$api_key = defined( 'WPWAF_API_KEY' )   ? (string) WPWAF_API_KEY   : '';

		if ( empty( $token ) && ( empty( $email ) || empty( $api_key ) ) ) {
			return [];
		}

		$method = ! empty( $token ) ? 'token' : 'key';
		$label  = defined( 'WPWAF_API_LABEL' ) && WPWAF_API_LABEL !== '' ? (string) WPWAF_API_LABEL : 'wp-config.php';

		return [ [
			'id'          => self::CONST_ACCOUNT_PREFIX . '0',
			'label'       => $label . ' (wp-config.php)',
			'auth_method' => $method,
			'api_token'   => $token,
			'email'       => $email,
			'api_key'     => $api_key,
			'expires_at'  => 0,
			'_constant'   => true,
		] ];
	}

	/** Return true if the given account ID is a constant account. */
	public static function is_constant_account( string $id ): bool {
		return str_starts_with( $id, self::CONST_ACCOUNT_PREFIX );
	}

	// ── Read ──────────────────────────────────────────────────────────────────

	/**
	 * Return all accounts — constant account first (if defined), then DB accounts.
	 * Credentials are decrypted.
	 */
	public static function all(): array {
		if ( self::$cache !== null ) return self::$cache;

		$raw  = get_option( self::OPTION_ACCOUNTS, [] );
		$db   = is_array( $raw ) ? array_map( [ __CLASS__, 'decode' ], $raw ) : [];

		$consts = self::constant_accounts();
		self::$cache = ! empty( $consts ) ? array_merge( $consts, $db ) : $db;

		return self::$cache;
	}

	/** Return the active account ID. */
	public static function active_id(): string {
		// If constant accounts are defined the first takes priority.
		$consts = self::constant_accounts();
		if ( ! empty( $consts ) ) {
			return $consts[0]['id'];
		}
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
		// Constant account is read-only.
		// Constant accounts are read-only.
		if ( self::is_constant_account( $data['id'] ?? '' ) ) {
			return $data['id'];
		}

		// Work only on DB accounts (exclude the constant account from the array).
		$accounts = self::db_accounts();
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

		// Auto-activate if no DB active account set (constant account takes over
		// priority when defined, so only relevant when no constant is defined).
		if ( empty( get_option( self::OPTION_ACTIVE, '' ) ) && empty( self::constant_accounts() ) ) {
			update_option( self::OPTION_ACTIVE, $id, false );
		}

		return $id;
	}

	/** Delete an account by ID. */
	public static function delete( string $id ): void {
		// Constant account cannot be deleted.
		if ( self::is_constant_account( $id ) ) return;

		$accounts = array_values(
			array_filter( self::db_accounts(), fn( $a ) => ( $a['id'] ?? '' ) !== $id )
		);
		self::persist( $accounts );

		if ( get_option( self::OPTION_ACTIVE, '' ) === $id ) {
			update_option( self::OPTION_ACTIVE, $accounts[0]['id'] ?? '', false );
		}
	}

	/** Switch the active account. */
	public static function switch_to( string $id ): void {
		// Cannot switch away when constant accounts are defined.
		if ( ! empty( self::constant_accounts() ) ) return;
		update_option( self::OPTION_ACTIVE, $id, false );
	}

	// ── API factory ───────────────────────────────────────────────────────────

	/** Build a WPWAF_API instance from the active account. */
	public static function api(): WPWAF_API {
		$acc = self::active();

		if ( ! $acc ) {
			return new WPWAF_API( auth_method: 'token' );
		}

		// Auto-expire: skip for constant accounts (they have no expiry).
		if ( empty( $acc['_constant'] ) ) {
			$expires = (int) ( $acc['expires_at'] ?? 0 );
			if ( $expires > 0 && time() > $expires ) {
				self::delete( $acc['id'] );
				return new WPWAF_API( auth_method: 'token' );
			}
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
		if ( ! empty( self::db_accounts() ) ) return;

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

	/** Return only database-stored accounts (excludes the constant account). */
	private static function db_accounts(): array {
		$raw = get_option( self::OPTION_ACCOUNTS, [] );
		return is_array( $raw ) ? array_map( [ __CLASS__, 'decode' ], $raw ) : [];
	}

	/**
	 * Derive a 256-bit encryption key from AUTH_KEY.
	 * sodium_crypto_generichash produces a fixed-length key regardless of
	 * AUTH_KEY's length or character set.
	 */
	private static function encryption_key(): string {
		$seed = defined( 'AUTH_KEY' ) ? AUTH_KEY : 'wpwaf-fallback-key-no-auth-key-defined';
		return sodium_crypto_generichash( $seed, '', SODIUM_CRYPTO_SECRETBOX_KEYBYTES );
	}

	/**
	 * Encrypt a credential string.
	 * Returns "enc2:<base64(nonce . ciphertext)>".
	 */
	private static function encrypt( string $value ): string {
		if ( empty( $value ) ) return $value;
		$nonce      = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
		$ciphertext = sodium_crypto_secretbox( $value, $nonce, self::encryption_key() );
		return self::ENC_PREFIX . base64_encode( $nonce . $ciphertext );
	}

	/**
	 * Decrypt a credential string.
	 * Handles:
	 *   - "enc2:..." → sodium ciphertext (current format)
	 *   - Legacy base64 (_encoded flag) → plain base64 (v1.0.x format, re-encrypted on next write)
	 *   - Plaintext → returned as-is
	 */
	private static function decrypt( string $value, bool $is_legacy_base64 = false ): string {
		if ( empty( $value ) ) return $value;

		if ( str_starts_with( $value, self::ENC_PREFIX ) ) {
			$raw        = base64_decode( substr( $value, strlen( self::ENC_PREFIX ) ), true );
			if ( $raw === false || strlen( $raw ) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES ) return '';
			$nonce      = substr( $raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
			$ciphertext = substr( $raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
			$plain      = sodium_crypto_secretbox_open( $ciphertext, $nonce, self::encryption_key() );
			return ( $plain !== false ) ? $plain : '';
		}

		if ( $is_legacy_base64 ) {
			$decoded = base64_decode( $value, strict: true );
			return ( $decoded !== false ) ? $decoded : $value;
		}

		return $value;
	}

	/** Encrypt credentials before persisting. */
	private static function encode( array $acc ): array {
		foreach ( [ 'api_token', 'api_key' ] as $field ) {
			if ( ! empty( $acc[ $field ] ) ) {
				$acc[ $field ] = self::encrypt( $acc[ $field ] );
			}
		}
		// Remove legacy flag; enc2: prefix is self-identifying.
		unset( $acc['_encoded'] );
		$acc['_encrypted'] = true;
		return $acc;
	}

	/** Decrypt credentials after reading. */
	private static function decode( array $acc ): array {
		$is_legacy = ! empty( $acc['_encoded'] ) && empty( $acc['_encrypted'] );
		foreach ( [ 'api_token', 'api_key' ] as $field ) {
			if ( ! empty( $acc[ $field ] ) ) {
				$acc[ $field ] = self::decrypt( $acc[ $field ], $is_legacy );
			}
		}
		unset( $acc['_encoded'], $acc['_encrypted'] );
		return $acc;
	}

	/** Persist accounts array to the database, invalidating the in-request cache. */
	private static function persist( array $accounts ): void {
		$encoded = array_map( [ __CLASS__, 'encode' ], $accounts );
		// autoload=false: credentials are only needed on WAF admin pages.
		update_option( self::OPTION_ACCOUNTS, $encoded, false );
		self::$cache = null; // invalidate; will be rebuilt with constant account on next all() call
	}
}
