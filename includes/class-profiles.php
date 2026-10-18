<?php
declare( strict_types = 1 );

/**
 * WPWAF_Profiles - Named WAF rule setting profiles.
 *
 * Profiles are stored as an associative array in wp_options under
 * 'wpwaf_profiles'. Each entry holds { id, name, settings }.
 * The 'default' profile is always present and cannot be deleted.
 * A maximum of 10 profiles (including Default) is enforced.
 *
 * The active profile ID is stored separately in 'wpwaf_active_profile'
 * so the last-used profile persists across page loads.
 */
class WPWAF_Profiles {

	private const OPTION        = 'wpwaf_profiles';
	private const ACTIVE_OPTION = 'wpwaf_active_profile';
	private const MAX           = 10;

	/**
	 * Bootstrap: called on plugin load.
	 * Creates the Default profile from current settings if profiles
	 * don't exist yet, so existing users' settings are preserved.
	 */
	public static function bootstrap(): void {
		if ( get_option( self::OPTION ) !== false ) return;

		$current = get_option( 'wpwaf_rule_settings', WPWAF_Rule_Builder::default_settings() );
		$current = is_array( $current ) ? $current : WPWAF_Rule_Builder::default_settings();

		$profiles = [
			'default' => [
				'id'       => 'default',
				'name'     => 'Default',
				'settings' => $current,
			],
		];
		add_option( self::OPTION, $profiles, '', false );
		add_option( self::ACTIVE_OPTION, 'default', '', false );
	}

	/** Return all profiles as an associative array keyed by ID. */
	public static function all(): array {
		$profiles = get_option( self::OPTION, [] );
		if ( ! is_array( $profiles ) || ! isset( $profiles['default'] ) ) {
			self::bootstrap();
			$profiles = get_option( self::OPTION, [] );
		}
		return $profiles;
	}

	/** Return the active profile ID (falls back to 'default'). */
	public static function active_id(): string {
		$id       = get_option( self::ACTIVE_OPTION, 'default' );
		$profiles = self::all();
		return isset( $profiles[ $id ] ) ? $id : 'default';
	}

	/** Return the active profile's settings. */
	public static function active_settings(): array {
		$profiles = self::all();
		$id       = self::active_id();
		return $profiles[ $id ]['settings'] ?? WPWAF_Rule_Builder::default_settings();
	}

	/**
	 * Create a new profile. Returns the new profile ID.
	 */
	public static function create( string $name, array $settings, string $notes = '' ): string {
		$profiles = self::all();
		if ( count( $profiles ) >= self::MAX ) return '';
		$id              = 'profile_' . substr( md5( uniqid( '', true ) ), 0, 8 );
		$profiles[ $id ] = [ 'id' => $id, 'name' => $name, 'notes' => $notes, 'settings' => $settings ];
		update_option( self::OPTION, $profiles, false );
		return $id;
	}

	/** Overwrite a profile's saved settings and optionally its notes. */
	public static function save_settings_to_profile( string $id, array $settings, string $notes = "\x00" ): void {
		$profiles = self::all();
		if ( ! isset( $profiles[ $id ] ) ) return;
		$profiles[ $id ]['settings'] = $settings;
		// Only update notes if explicitly passed (sentinel \x00 = not provided).
		if ( $notes !== "\x00" ) {
			$profiles[ $id ]['notes'] = $notes;
		}
		update_option( self::OPTION, $profiles, false );
	}

	/** Save notes for a profile without touching its settings. */
	public static function save_notes( string $id, string $notes ): void {
		$profiles = self::all();
		if ( ! isset( $profiles[ $id ] ) ) return;
		$profiles[ $id ]['notes'] = $notes;
		update_option( self::OPTION, $profiles, false );
	}

	/** Delete a profile by ID. Default cannot be deleted. */
	public static function delete( string $id ): void {
		if ( $id === 'default' ) return;
		$profiles = self::all();
		unset( $profiles[ $id ] );
		update_option( self::OPTION, $profiles, false );
	}

	/**
	 * Return a safe array for JS - settings are stripped out,
	 * only id, name, and notes are exposed to the frontend.
	 */
	public static function for_js(): array {
		return array_values( array_map( fn( $p ) => [
			'id'    => $p['id'],
			'name'  => $p['name'],
			'notes' => $p['notes'] ?? '',
		], self::all() ) );
	}
}
