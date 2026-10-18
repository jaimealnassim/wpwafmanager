<?php
declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * WPWAF_Access - User-level access control.
 *
 * When no allowlist is configured (empty array), all users who pass the
 * minimum-role check can access the plugin - backward compatible default.
 *
 * When an allowlist is saved, ONLY those specific user IDs can access the
 * plugin, regardless of role. Super Admins on multisite always have access
 * and cannot be locked out.
 *
 * The current user is always protected from locking themselves out.
 *
 * Option key: wpwaf_allowed_user_ids  (array of int user IDs, autoload=false)
 */
class WPWAF_Access {

	private const OPTION = 'wpwaf_allowed_user_ids';

	/**
	 * Return the saved allowlist. Empty = all qualifying admins allowed.
	 *
	 * @return int[]
	 */
	public static function allowed_ids(): array {
		$ids = get_option( self::OPTION, [] );
		return is_array( $ids ) ? array_values( array_map( 'intval', $ids ) ) : [];
	}

	/**
	 * Check if the current user can access the plugin.
	 *
	 * Priority:
	 *   1. Super Admin on multisite → always true.
	 *   2. Must pass the minimum-role capability check.
	 *   3. If allowlist is empty → true (all qualifying admins allowed).
	 *   4. If allowlist is set → must be in the list.
	 */
	public static function current_user_can(): bool {
		// Super Admin on multisite always has access.
		if ( is_multisite() && is_super_admin() ) return true;

		// Must pass the minimum-role check first.
		$cap = WPWAF_Settings::required_capability();
		if ( ! current_user_can( $cap ) ) return false;

		// No allowlist = all qualifying users may access.
		$ids = self::allowed_ids();
		if ( empty( $ids ) ) return true;

		return in_array( get_current_user_id(), $ids, true );
	}

	/**
	 * Save the allowlist.
	 *
	 * @param int[] $user_ids  Array of user IDs to allow.
	 * @return string  Empty on success, error message on failure.
	 */
	public static function save( array $user_ids ): string {
		$user_ids = array_values( array_unique( array_map( 'intval', $user_ids ) ) );

		// Guard: current user must remain in the list (can't lock yourself out).
		$current = get_current_user_id();
		if ( ! empty( $user_ids ) && ! in_array( $current, $user_ids, true ) ) {
			return 'You cannot remove your own access. Your account must remain in the list.';
		}

		// Empty list = "all admins" - store as empty array.
		update_option( self::OPTION, $user_ids, false );
		return '';
	}

	/**
	 * Return all users who hold the Administrator role,
	 * ready for the settings page UI.
	 *
	 * @return WP_User[]
	 */
	public static function admin_users(): array {
		return get_users( [
			'role__in' => [ 'administrator' ],
			'orderby'  => 'display_name',
			'order'    => 'ASC',
			'number'   => 100,
		] ) ?: [];
	}
}
