<?php
declare( strict_types = 1 );

/**
 * WPWAF_Domain_Profiles - Named zone selection profiles.
 *
 * Stores named lists of zone IDs in wp_options under 'wpwaf_domain_profiles'.
 * The 'default' profile starts empty (no zones selected).
 * Maximum of 10 profiles enforced. Default cannot be deleted.
 * Active profile ID persists in 'wpwaf_active_domain_profile'.
 */
class WPWAF_Domain_Profiles {

	private const OPTION        = 'wpwaf_domain_profiles';
	private const ACTIVE_OPTION = 'wpwaf_active_domain_profile';
	private const MAX           = 10;

	/** Bootstrap: create the Default profile if profiles don't exist yet. */
	public static function bootstrap(): void {
		if ( get_option( self::OPTION ) !== false ) return;
		$profiles = [
			'default' => [
				'id'       => 'default',
				'name'     => 'Default',
				'zone_ids' => [],
			],
		];
		add_option( self::OPTION, $profiles, '', false );
		add_option( self::ACTIVE_OPTION, 'default', '', false );
	}

	/** Return all profiles keyed by ID. */
	public static function all(): array {
		$profiles = get_option( self::OPTION, [] );
		if ( ! is_array( $profiles ) || ! isset( $profiles['default'] ) ) {
			self::bootstrap();
			$profiles = get_option( self::OPTION, [] );
		}
		return $profiles;
	}

	/** Return the active profile ID. */
	public static function active_id(): string {
		$id       = get_option( self::ACTIVE_OPTION, 'default' );
		$profiles = self::all();
		return isset( $profiles[ $id ] ) ? $id : 'default';
	}

	/** Create a new profile from a list of zone IDs. Returns the new ID. */
	public static function create( string $name, array $zone_ids ): string {
		$profiles = self::all();
		if ( count( $profiles ) >= self::MAX ) return '';
		$id              = 'dp_' . substr( md5( uniqid( '', true ) ), 0, 8 );
		$profiles[ $id ] = [
			'id'       => $id,
			'name'     => $name,
			'zone_ids' => array_values( array_map( 'sanitize_text_field', $zone_ids ) ),
		];
		update_option( self::OPTION, $profiles, false );
		return $id;
	}

	/** Delete a profile. Default cannot be deleted. */
	public static function delete( string $id ): void {
		if ( $id === 'default' ) return;
		$profiles = self::all();
		unset( $profiles[ $id ] );
		update_option( self::OPTION, $profiles, false );
	}

	/** Return safe data for JS - id, name, zone_ids (no sensitive data). */
	public static function for_js(): array {
		return array_values( array_map( fn( $p ) => [
			'id'       => $p['id'],
			'name'     => $p['name'],
			'zone_ids' => $p['zone_ids'] ?? [],
		], self::all() ) );
	}
}
