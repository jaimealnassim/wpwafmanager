<?php
declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Turnstile: manage Cloudflare Turnstile widgets for client sites.
 *
 * This is an agency tool: it creates and maintains widgets in any Cloudflare
 * account the token can see, and hands out the site key and secret key to put
 * into each client site. It does not add Turnstile to the site it runs on.
 *
 * Widgets live at account level in Cloudflare:
 *   GET    /accounts/{account_id}/challenges/widgets
 *   POST   /accounts/{account_id}/challenges/widgets
 *   GET    /accounts/{account_id}/challenges/widgets/{sitekey}   (includes the secret)
 *   PUT    /accounts/{account_id}/challenges/widgets/{sitekey}   (name, domains, mode required)
 *   POST   /accounts/{account_id}/challenges/widgets/{sitekey}/rotate_secret
 *   DELETE /accounts/{account_id}/challenges/widgets/{sitekey}
 *
 * Secrets are never stored by the plugin. They are fetched from Cloudflare
 * only when the user asks to see them, or returned once after create/rotate.
 */
final class WPWAF_Turnstile extends WPWAF_Module {

	/** Token permission shown when Cloudflare rejects a call. */
	private const PERMISSION = 'Account → Turnstile → Edit';

	private const MODES = [ 'managed', 'non-interactive', 'invisible' ];

	/** Hostnames a widget can hold (Enterprise maximum; Free is 10, enforced by Cloudflare). */
	private const MAX_DOMAINS = 200;

	public static function init(): void {
		foreach ( [ 'list', 'create', 'update', 'rotate', 'delete', 'keys' ] as $a ) {
			add_action( 'wp_ajax_wpwaf_ts_' . $a, [ self::class, 'ajax_' . $a ] );
		}
	}

	// ── Input ────────────────────────────────────────────────────────────────

	private static function sitekey(): string {
		$k = self::post( 'sitekey' );
		if ( ! preg_match( '/^[A-Za-z0-9_-]{1,32}$/', $k ) ) {
			wp_send_json_error( [ 'message' => 'A valid site key is required.' ] );
		}
		return $k;
	}

	private static function mode(): string {
		$m = self::post( 'mode', 'managed' );
		if ( ! in_array( $m, self::MODES, true ) ) {
			wp_send_json_error( [ 'message' => 'Choose a widget mode: Managed, Non-interactive or Invisible.' ] );
		}
		return $m;
	}

	private static function name(): string {
		$n = self::post( 'name' );
		if ( $n === '' || strlen( $n ) > 254 ) {
			wp_send_json_error( [ 'message' => 'Widget name must be 1 to 254 characters.' ] );
		}
		return $n;
	}

	/** Hostnames from a JSON array; schemes, paths, ports and wildcards are not accepted by Cloudflare. */
	private static function domains(): array {
		$raw = self::post_json( 'domains' );
		$out = [];
		foreach ( $raw as $d ) {
			$d = strtolower( trim( (string) $d ) );
			if ( $d === '' ) {
				continue;
			}
			if ( ! self::valid_host( $d ) ) {
				wp_send_json_error( [ 'message' => "\"{$d}\" is not a valid hostname. Enter hostnames only, like example.com, without https://, paths, ports or *." ] );
			}
			$out[ $d ] = $d;
		}
		if ( ! $out ) {
			wp_send_json_error( [ 'message' => 'Add at least one hostname.' ] );
		}
		if ( count( $out ) > self::MAX_DOMAINS ) {
			wp_send_json_error( [ 'message' => 'A widget can hold at most 200 hostnames (10 on the Free plan).' ] );
		}
		return array_values( $out );
	}

	public static function valid_host( string $d ): bool {
		if ( filter_var( $d, FILTER_VALIDATE_IP ) ) {
			return true;
		}
		return strlen( $d ) <= 253
			&& (bool) preg_match( '/^([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)*[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?$/', $d );
	}

	private static function widget_path( string $account_id, string $sitekey = '' ): string {
		return "accounts/{$account_id}/challenges/widgets" . ( $sitekey !== '' ? '/' . rawurlencode( $sitekey ) : '' );
	}

	/** Public view of a widget: never includes the secret. */
	private static function public_widget( array $w ): array {
		return [
			'sitekey'     => (string) ( $w['sitekey'] ?? '' ),
			'name'        => (string) ( $w['name'] ?? '' ),
			'domains'     => array_values( array_map( 'strval', is_array( $w['domains'] ?? null ) ? $w['domains'] : [] ) ),
			'mode'        => (string) ( $w['mode'] ?? '' ),
			'region'      => (string) ( $w['region'] ?? '' ),
			'created_on'  => (string) ( $w['created_on'] ?? '' ),
			'modified_on' => (string) ( $w['modified_on'] ?? '' ),
		];
	}

	// ── AJAX ─────────────────────────────────────────────────────────────────

	public static function ajax_list(): void {
		self::guard();
		$account_id = self::post_id( 'account_id' );
		$res        = self::api()->call_all( self::widget_path( $account_id ), 100 );
		if ( ! $res['success'] ) {
			self::respond( $res, self::PERMISSION );
		}
		$widgets = array_map( [ self::class, 'public_widget' ], is_array( $res['result'] ) ? $res['result'] : [] );
		wp_send_json_success( [ 'widgets' => $widgets ] );
	}

	/** Create a widget. Cloudflare returns the secret here, so it is passed back once to show the user. */
	public static function ajax_create(): void {
		self::guard();
		$account_id = self::post_id( 'account_id' );
		$body       = [ 'name' => self::name(), 'domains' => self::domains(), 'mode' => self::mode() ];
		$res        = self::api()->call( 'POST', self::widget_path( $account_id ), $body );
		if ( ! $res['success'] || ! is_array( $res['result'] ) ) {
			self::respond( $res, self::PERMISSION );
		}
		wp_send_json_success( [
			'widget' => self::public_widget( $res['result'] ),
			'secret' => (string) ( $res['result']['secret'] ?? '' ),
		] );
	}

	public static function ajax_update(): void {
		self::guard();
		$account_id = self::post_id( 'account_id' );
		$sitekey    = self::sitekey();
		$body       = [ 'name' => self::name(), 'domains' => self::domains(), 'mode' => self::mode() ];

		// PUT replaces the widget, so optional settings already on it (clearance
		// level, region and the Enterprise options) are carried over unchanged.
		$cur = self::api()->call( 'GET', self::widget_path( $account_id, $sitekey ) );
		if ( ! $cur['success'] || ! is_array( $cur['result'] ) ) {
			self::respond( $cur, self::PERMISSION );
		}
		foreach ( [ 'clearance_level', 'bot_fight_mode', 'ephemeral_id', 'offlabel', 'region' ] as $k ) {
			if ( array_key_exists( $k, $cur['result'] ) ) {
				$body[ $k ] = $cur['result'][ $k ];
			}
		}
		$res = self::api()->call( 'PUT', self::widget_path( $account_id, $sitekey ), $body );
		if ( ! $res['success'] || ! is_array( $res['result'] ) ) {
			self::respond( $res, self::PERMISSION );
		}
		wp_send_json_success( [ 'widget' => self::public_widget( $res['result'] ) ] );
	}

	/**
	 * Rotate the secret. Unless invalidate_immediately is set, the old secret
	 * keeps working for 2 hours so client sites can be updated.
	 */
	public static function ajax_rotate(): void {
		self::guard();
		$account_id = self::post_id( 'account_id' );
		$sitekey    = self::sitekey();
		$res        = self::api()->call( 'POST', self::widget_path( $account_id, $sitekey ) . '/rotate_secret', [
			'invalidate_immediately' => self::post_bool( 'invalidate_immediately' ),
		] );
		if ( ! $res['success'] || ! is_array( $res['result'] ) ) {
			self::respond( $res, self::PERMISSION );
		}
		$res['result']['sitekey'] = $res['result']['sitekey'] ?? $sitekey;
		wp_send_json_success( [
			'widget' => self::public_widget( $res['result'] ),
			'secret' => (string) ( $res['result']['secret'] ?? '' ),
		] );
	}

	public static function ajax_delete(): void {
		self::guard();
		$account_id = self::post_id( 'account_id' );
		$sitekey    = self::sitekey();
		$res        = self::api()->call( 'DELETE', self::widget_path( $account_id, $sitekey ) );
		if ( ! $res['success'] ) {
			self::respond( $res, self::PERMISSION );
		}
		wp_send_json_success( [ 'sitekey' => $sitekey ] );
	}

	/** Site key and secret key for a widget, fetched on request to paste into a client site. */
	public static function ajax_keys(): void {
		self::guard();
		$account_id = self::post_id( 'account_id' );
		$sitekey    = self::sitekey();
		$res        = self::api()->call( 'GET', self::widget_path( $account_id, $sitekey ) );
		if ( ! $res['success'] || ! is_array( $res['result'] ) ) {
			self::respond( $res, self::PERMISSION );
		}
		if ( empty( $res['result']['secret'] ) ) {
			wp_send_json_error( [ 'message' => 'Cloudflare did not return the secret key for this widget. Check the token has Account → Turnstile → Edit.' ] );
		}
		wp_send_json_success( [
			'sitekey' => (string) ( $res['result']['sitekey'] ?? $sitekey ),
			'secret'  => (string) $res['result']['secret'],
		] );
	}
}
