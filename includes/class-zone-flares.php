<?php
declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * WPWAF_Zone_Flares — Per-zone metadata labels ("flares").
 *
 * Flares are stored as an associative array in wp_options keyed by zone ID.
 * Each entry: { flare_id: string, custom_label: string }
 */
class WPWAF_Zone_Flares {

	private const OPTION = 'wpwaf_zone_flares';

	/** Preset flare definitions — id, label, color. */
	public static function presets(): array {
		return [
			[ 'id' => 'woo',         'label' => 'WooCommerce',           'color' => '#7f54b3' ],
			[ 'id' => 'surecart',    'label' => 'SureCart',              'color' => '#6366f1' ],
			[ 'id' => 'fluent',      'label' => 'Fluent Cart',           'color' => '#0ea5e9' ],
			[ 'id' => 'edd',         'label' => 'Easy Digital Downloads','color' => '#f59e0b' ],
			[ 'id' => 'learndash',   'label' => 'LearnDash',             'color' => '#e11d48' ],
			[ 'id' => 'lifter',      'label' => 'LifterLMS',             'color' => '#10b981' ],
			[ 'id' => 'memberpress', 'label' => 'MemberPress',           'color' => '#f97316' ],
			[ 'id' => 'restrict',    'label' => 'Restrict Content Pro',  'color' => '#8b5cf6' ],
			[ 'id' => 'staging',     'label' => 'Staging',               'color' => '#6b7280' ],
			[ 'id' => 'blog',        'label' => 'Blog',                  'color' => '#84cc16' ],
			[ 'id' => 'portfolio',   'label' => 'Portfolio',             'color' => '#14b8a6' ],
			[ 'id' => 'brochure',    'label' => 'Brochure',              'color' => '#64748b' ],
			[ 'id' => 'client',      'label' => 'Client',                'color' => '#ec4899' ],
			[ 'id' => 'agency',      'label' => 'Agency',                'color' => '#3b82f6' ],
			[ 'id' => 'custom',      'label' => 'Custom',                'color' => '#9ca3af' ],
		];
	}

	/** Return all saved flares. */
	public static function all(): array {
		$flares = get_option( self::OPTION, [] );
		return is_array( $flares ) ? $flares : [];
	}

	/** Save or remove a flare for a zone. */
	public static function set( string $zone_id, string $flare_id, string $custom_label = '' ): void {
		$flares = self::all();
		if ( $flare_id === '' ) {
			unset( $flares[ $zone_id ] );
		} else {
			$flares[ $zone_id ] = [
				'flare_id'     => sanitize_text_field( $flare_id ),
				'custom_label' => sanitize_text_field( $custom_label ),
			];
		}
		update_option( self::OPTION, $flares, false );
	}

	/** Return flares safe for JS output. */
	public static function for_js(): array {
		return self::all();
	}
}
