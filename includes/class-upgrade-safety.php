<?php
declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Keeps updates and deletions safe.
 *
 * WordPress installs an update into a folder named after the root folder inside
 * the update zip. If that name differs from the folder the plugin is installed
 * in (for example "wpwafmanager-main" from a GitHub download), WordPress removes
 * the old folder, puts the new version somewhere else and the active plugin path
 * no longer exists. This class:
 *
 * 1. Pins updates and zip uploads of this plugin to the folder it is installed in.
 * 2. Keeps settings when a second, unused copy of the plugin is deleted, because
 *    every copy shares the same options in the database.
 * 3. Tells the admin when a second copy is installed.
 */
final class WPWAF_Upgrade_Safety {

	private const MAIN_FILE   = 'wpwafmanager.php';
	private const PLUGIN_NAME = 'WP WAF Manager';
	private const SETTINGS    = 'wpwaf_plugin_settings';

	/** Basename of the running copy, e.g. "wpwafmanager/wpwafmanager.php". */
	private static string $basename = '';

	public static function init( string $main_file ): void {
		self::$basename = plugin_basename( $main_file );
		add_filter( 'upgrader_source_selection', [ __CLASS__, 'pin_folder' ], 10, 4 );
		add_action( 'pre_uninstall_plugin', [ __CLASS__, 'protect_shared_data' ], 10, 1 );
		add_action( 'admin_notices', [ __CLASS__, 'duplicate_notice' ] );
	}

	// ── Updates ───────────────────────────────────────────────────────────────

	/**
	 * upgrader_source_selection: rename the extracted package folder to the
	 * folder this copy runs from, so the update replaces it in place.
	 *
	 * @param string|WP_Error $source        Extracted package folder.
	 * @param string          $remote_source Working folder the package was unzipped into.
	 * @param mixed           $upgrader      WP_Upgrader instance.
	 * @param mixed           $hook_extra    Context passed by the upgrader.
	 * @return string|WP_Error
	 */
	public static function pin_folder( $source, $remote_source, $upgrader = null, $hook_extra = [] ) {
		if ( is_wp_error( $source ) || ! is_string( $source ) || ! is_array( $hook_extra ) ) {
			return $source;
		}

		$is_update  = ( $hook_extra['plugin'] ?? '' ) === self::$basename;
		$is_install = ( $hook_extra['type'] ?? '' ) === 'plugin' && ( $hook_extra['action'] ?? '' ) === 'install';
		if ( ! $is_update && ! $is_install ) {
			return $source;
		}

		$folder = dirname( self::$basename );
		if ( '.' === $folder || '' === $folder ) {
			return $source;
		}

		global $wp_filesystem;
		if ( ! is_object( $wp_filesystem ) ) {
			return $source;
		}

		$dir = self::locate_package( $source );
		if ( null === $dir ) {
			// Not this plugin (install) or an unexpected package (update): leave it
			// to the core checks, which report a clear error.
			return $source;
		}

		if ( basename( $dir ) === $folder ) {
			return trailingslashit( $dir );
		}

		$target = trailingslashit( $remote_source ) . $folder;
		if ( $wp_filesystem->exists( $target ) ) {
			return trailingslashit( $dir );
		}

		if ( ! $wp_filesystem->move( $dir, $target, true ) ) {
			return new WP_Error(
				'wpwaf_rename_failed',
				sprintf( 'WP WAF Manager could not prepare the update for the "%s" folder. Nothing was changed.', $folder )
			);
		}

		return trailingslashit( $target );
	}

	/**
	 * Returns the folder inside the package that holds this plugin's main file
	 * (the package root, or one level down for zips with an extra wrapper folder).
	 */
	private static function locate_package( string $source ): ?string {
		$source = untrailingslashit( $source );
		if ( self::is_our_main_file( $source . '/' . self::MAIN_FILE ) ) {
			return $source;
		}

		global $wp_filesystem;
		$list = $wp_filesystem->dirlist( $source, false, false );
		if ( ! is_array( $list ) ) {
			return null;
		}
		foreach ( $list as $name => $item ) {
			if ( ( $item['type'] ?? '' ) !== 'd' ) continue;
			$sub = $source . '/' . $name;
			if ( self::is_our_main_file( $sub . '/' . self::MAIN_FILE ) ) {
				return $sub;
			}
		}
		return null;
	}

	private static function is_our_main_file( string $path ): bool {
		global $wp_filesystem;
		if ( ! $wp_filesystem->is_file( $path ) ) {
			return false;
		}
		$head = substr( (string) $wp_filesystem->get_contents( $path ), 0, 8192 );
		return 1 === preg_match( '/^[ \t\/*#@]*Plugin Name:\s*' . preg_quote( self::PLUGIN_NAME, '/' ) . '\s*$/mi', $head );
	}

	// ── Deletion ──────────────────────────────────────────────────────────────

	/**
	 * pre_uninstall_plugin: when another copy of this plugin is being deleted
	 * while this copy is still installed, make its uninstall routine keep the
	 * shared settings, whatever the "Delete data on uninstall" choice is.
	 */
	public static function protect_shared_data( $plugin ): void {
		if ( ! is_string( $plugin ) || $plugin === self::$basename ) {
			return;
		}
		$data = get_file_data( WP_PLUGIN_DIR . '/' . $plugin, [ 'Name' => 'Plugin Name' ] );
		if ( ( $data['Name'] ?? '' ) !== self::PLUGIN_NAME ) {
			return;
		}

		$keep = static function ( $value ) {
			$value = is_array( $value ) ? $value : [];
			$value['keep_data_on_uninstall'] = true;
			return $value;
		};
		add_filter( 'option_' . self::SETTINGS, $keep, PHP_INT_MAX );
		add_filter( 'default_option_' . self::SETTINGS, $keep, PHP_INT_MAX );
	}

	// ── Notice ────────────────────────────────────────────────────────────────

	/** Lists other installed copies on the Plugins screen and on our own screens. */
	public static function duplicate_notice(): void {
		if ( ! current_user_can( 'activate_plugins' ) ) return;
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen ) return;
		if ( ! in_array( $screen->id, [ 'plugins', 'plugins-network' ], true ) && strpos( (string) $screen->id, 'wpwafmanager' ) === false ) return;

		$others = self::other_copies();
		if ( ! $others ) return;
		// A second active copy already shows its own notice from the main plugin file.
		foreach ( array_keys( $others ) as $file ) {
			if ( is_plugin_active( $file ) ) return;
		}

		$items = '';
		foreach ( $others as $file => $version ) {
			$items .= sprintf(
				'<li>Folder <code>%1$s</code>, version %2$s</li>',
				esc_html( dirname( $file ) ),
				esc_html( $version )
			);
		}
		printf(
			'<div class="notice notice-warning"><p><strong>WP WAF Manager:</strong> %1$s</p><ul style="list-style:disc;margin-left:20px">%2$s</ul><p>%3$s</p></div>',
			esc_html( sprintf( 'You are using the copy in the "%s" folder. Another copy of the plugin is also installed:', dirname( self::$basename ) ) ),
			$items, // Escaped above.
			esc_html( 'You can delete the other copy from the Plugins screen. Your settings are shared by both copies and will be kept.' )
		);
	}

	/** @return array<string,string> Plugin file => version for every other installed copy. */
	private static function other_copies(): array {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$out = [];
		foreach ( get_plugins() as $file => $data ) {
			if ( $file === self::$basename ) continue;
			if ( ( $data['Name'] ?? '' ) !== self::PLUGIN_NAME ) continue;
			$out[ $file ] = (string) ( $data['Version'] ?? '' );
		}
		return $out;
	}
}
