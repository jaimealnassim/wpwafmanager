<?php
declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Alerts & Health: Cloudflare notification policies and origin Health Checks.
 *
 * Alerts (account level), Cloudflare Notifications API:
 *   GET    /accounts/{account_id}/alerting/v3/available_alerts
 *   GET    /accounts/{account_id}/alerting/v3/policies
 *   GET    /accounts/{account_id}/alerting/v3/policies/{policy_id}
 *   POST   /accounts/{account_id}/alerting/v3/policies
 *   PUT    /accounts/{account_id}/alerting/v3/policies/{policy_id}
 *   DELETE /accounts/{account_id}/alerting/v3/policies/{policy_id}
 *   GET    /accounts/{account_id}/alerting/v3/destinations/webhooks
 *   POST   /accounts/{account_id}/alerting/v3/destinations/webhooks
 *
 * Health Checks (zone level, Pro plan and above), Cloudflare Health Checks API:
 *   GET    /zones/{zone_id}/healthchecks
 *   GET    /zones/{zone_id}/healthchecks/{healthcheck_id}
 *   POST   /zones/{zone_id}/healthchecks
 *   PATCH  /zones/{zone_id}/healthchecks/{healthcheck_id}
 *   DELETE /zones/{zone_id}/healthchecks/{healthcheck_id}
 */
final class WPWAF_Alerts extends WPWAF_Module {

	private const PERM_NOTIFY_READ  = 'Account → Notifications → Read (or Edit)';
	private const PERM_NOTIFY_EDIT  = 'Account → Notifications → Edit';
	private const PERM_HC_READ      = 'Zone → Health Checks → Read (or Edit)';
	private const PERM_HC_EDIT      = 'Zone → Health Checks → Edit';

	/** Filter keys accepted by the Notifications API (PolicyFilter object). */
	private const FILTER_KEYS = [
		'actions', 'affected_asns', 'affected_components', 'affected_locations', 'airport_code',
		'alert_trigger_preferences', 'alert_trigger_preferences_value', 'enabled', 'environment',
		'event', 'event_source', 'event_type', 'group_by', 'health_check_id', 'incident_impact',
		'input_id', 'insight_class', 'limit', 'logo_tag', 'megabits_per_second', 'new_health',
		'new_status', 'packets_per_second', 'pool_id', 'pop_names', 'product', 'project_id',
		'protocol', 'query_tag', 'requests_per_second', 'selectors', 'services', 'slo', 'status',
		'target_hostname', 'target_ip', 'target_zone_name', 'token_id', 'traffic_exclusions',
		'tunnel_id', 'tunnel_name', 'type', 'where', 'zones',
	];

	/** Health check regions accepted by the API. */
	private const HC_REGIONS = [ 'WNAM', 'ENAM', 'WEU', 'EEU', 'NSAM', 'SSAM', 'OC', 'ME', 'NAF', 'SAF', 'IN', 'SEAS', 'NEAS', 'ALL_REGIONS' ];

	public static function init(): void {
		add_action( 'wp_ajax_wpwaf_al_data',           [ self::class, 'ajax_data' ] );
		add_action( 'wp_ajax_wpwaf_al_policy_save',    [ self::class, 'ajax_policy_save' ] );
		add_action( 'wp_ajax_wpwaf_al_policy_toggle',  [ self::class, 'ajax_policy_toggle' ] );
		add_action( 'wp_ajax_wpwaf_al_policy_delete',  [ self::class, 'ajax_policy_delete' ] );
		add_action( 'wp_ajax_wpwaf_al_webhook_create', [ self::class, 'ajax_webhook_create' ] );
		add_action( 'wp_ajax_wpwaf_al_hc_list',        [ self::class, 'ajax_hc_list' ] );
		add_action( 'wp_ajax_wpwaf_al_hc_save',        [ self::class, 'ajax_hc_save' ] );
		add_action( 'wp_ajax_wpwaf_al_hc_toggle',      [ self::class, 'ajax_hc_toggle' ] );
		add_action( 'wp_ajax_wpwaf_al_hc_delete',      [ self::class, 'ajax_hc_delete' ] );
	}

	// ── Helpers ───────────────────────────────────────────────────────────────

	/** Policy, webhook and health check IDs: letters, digits and hyphens. */
	private static function post_ref( string $key ): string {
		$id = self::post( $key );
		if ( ! preg_match( '/^[A-Za-z0-9-]{1,64}$/', $id ) ) {
			wp_send_json_error( [ 'message' => "A valid {$key} is required." ] );
		}
		return $id;
	}

	/** Clean an array of strings. */
	private static function str_list( mixed $vals, int $max_len = 200 ): array {
		if ( ! is_array( $vals ) ) {
			$vals = [ $vals ];
		}
		$out = [];
		foreach ( $vals as $v ) {
			if ( ! is_scalar( $v ) ) {
				continue;
			}
			$v = trim( sanitize_text_field( (string) $v ) );
			if ( $v !== '' && strlen( $v ) <= $max_len ) {
				$out[] = $v;
			}
		}
		return array_values( array_unique( $out ) );
	}

	/** Keep only documented filter keys with string-array values. */
	private static function clean_filters( mixed $raw ): array {
		if ( ! is_array( $raw ) ) {
			return [];
		}
		$out = [];
		foreach ( $raw as $key => $vals ) {
			if ( ! is_string( $key ) || ! in_array( $key, self::FILTER_KEYS, true ) ) {
				continue;
			}
			$list = self::str_list( $vals );
			if ( $list ) {
				$out[ $key ] = $list;
			}
		}
		return $out;
	}

	/** Keep only email and webhook delivery entries in the documented { id } format. */
	private static function clean_mechanisms( mixed $raw ): array {
		$out = [];
		if ( ! is_array( $raw ) ) {
			return $out;
		}
		foreach ( (array) ( $raw['email'] ?? [] ) as $m ) {
			$email = sanitize_email( (string) ( is_array( $m ) ? ( $m['id'] ?? '' ) : $m ) );
			if ( $email !== '' && is_email( $email ) ) {
				$out['email'][] = [ 'id' => $email ];
			}
		}
		foreach ( (array) ( $raw['webhooks'] ?? [] ) as $m ) {
			$id = (string) ( is_array( $m ) ? ( $m['id'] ?? '' ) : $m );
			if ( preg_match( '/^[A-Za-z0-9-]{1,64}$/', $id ) ) {
				$out['webhooks'][] = [ 'id' => $id ];
			}
		}
		// PagerDuty is not managed here, but an existing PagerDuty destination is preserved on update.
		foreach ( (array) ( $raw['pagerduty'] ?? [] ) as $m ) {
			$id = (string) ( is_array( $m ) ? ( $m['id'] ?? '' ) : $m );
			if ( preg_match( '/^[A-Za-z0-9-]{1,64}$/', $id ) ) {
				$out['pagerduty'][] = [ 'id' => $id ];
			}
		}
		return $out;
	}

	/** Flatten the available_alerts map (category => [types]) into one list. */
	private static function flatten_available( mixed $result ): array {
		$list = [];
		if ( ! is_array( $result ) ) {
			return $list;
		}
		foreach ( $result as $category => $types ) {
			if ( ! is_array( $types ) ) {
				continue;
			}
			foreach ( $types as $t ) {
				if ( ! is_array( $t ) || empty( $t['type'] ) ) {
					continue;
				}
				$list[] = [
					'type'           => (string) $t['type'],
					'display_name'   => (string) ( $t['display_name'] ?? $t['type'] ),
					'description'    => (string) ( $t['description'] ?? '' ),
					'category'       => is_string( $category ) ? $category : '',
					'filter_options' => $t['filter_options'] ?? null,
				];
			}
		}
		return $list;
	}

	// ── Alerts ───────────────────────────────────────────────────────────────

	/** Policies, available alert types and webhook destinations for one account. */
	public static function ajax_data(): void {
		self::guard();
		$account = self::post_id( 'account_id' );
		$api     = self::api();

		$policies = $api->call( 'GET', "accounts/{$account}/alerting/v3/policies" );
		if ( ! $policies['success'] ) {
			self::respond( $policies, self::PERM_NOTIFY_READ );
		}
		$available = $api->call( 'GET', "accounts/{$account}/alerting/v3/available_alerts" );
		$webhooks  = $api->call( 'GET', "accounts/{$account}/alerting/v3/destinations/webhooks" );

		$webhook_list = [];
		foreach ( (array) ( $webhooks['success'] ? $webhooks['result'] : [] ) as $w ) {
			if ( is_array( $w ) && ! empty( $w['id'] ) ) {
				// The API never returns webhook secrets; drop the field if present anyway.
				$webhook_list[] = [
					'id'           => (string) $w['id'],
					'name'         => (string) ( $w['name'] ?? '' ),
					'url'          => (string) ( $w['url'] ?? '' ),
					'type'         => (string) ( $w['type'] ?? '' ),
					'last_success' => (string) ( $w['last_success'] ?? '' ),
					'last_failure' => (string) ( $w['last_failure'] ?? '' ),
				];
			}
		}

		wp_send_json_success( [
			'policies'        => is_array( $policies['result'] ) ? array_values( $policies['result'] ) : [],
			'available'       => $available['success'] ? self::flatten_available( $available['result'] ) : [],
			'available_error' => $available['success'] ? '' : $available['message'],
			'webhooks'        => $webhook_list,
			'webhooks_error'  => $webhooks['success'] ? '' : $webhooks['message'],
		] );
	}

	/** Create a policy, or update one when policy_id is sent. */
	public static function ajax_policy_save(): void {
		self::guard();
		$account = self::post_id( 'account_id' );
		$data    = self::post_json( 'policy' );

		$type = (string) ( $data['alert_type'] ?? '' );
		if ( ! preg_match( '/^[a-z0-9_]{2,100}$/', $type ) ) {
			wp_send_json_error( [ 'message' => 'Choose an alert type.' ] );
		}
		$name = sanitize_text_field( (string) ( $data['name'] ?? '' ) );
		if ( $name === '' ) {
			wp_send_json_error( [ 'message' => 'Give the alert a name.' ] );
		}

		$mechanisms = self::clean_mechanisms( $data['mechanisms'] ?? [] );
		if ( empty( $mechanisms['email'] ) && empty( $mechanisms['webhooks'] ) && empty( $mechanisms['pagerduty'] ) ) {
			wp_send_json_error( [ 'message' => 'Add at least one email address or webhook to deliver the alert to.' ] );
		}

		$body = [
			'name'        => $name,
			'alert_type'  => $type,
			'enabled'     => ! empty( $data['enabled'] ),
			'mechanisms'  => $mechanisms,
			'description' => sanitize_textarea_field( (string) ( $data['description'] ?? '' ) ),
		];
		$filters = self::clean_filters( $data['filters'] ?? [] );
		if ( $filters ) {
			$body['filters'] = $filters;
		}

		$policy_id = isset( $_POST['policy_id'] ) && $_POST['policy_id'] !== '' ? self::post_ref( 'policy_id' ) : '';
		$res       = $policy_id !== ''
			? self::api()->call( 'PUT', "accounts/{$account}/alerting/v3/policies/{$policy_id}", $body )
			: self::api()->call( 'POST', "accounts/{$account}/alerting/v3/policies", $body );
		self::respond( $res, self::PERM_NOTIFY_EDIT );
	}

	/** Turn a policy on or off, resending its current settings so nothing else changes. */
	public static function ajax_policy_toggle(): void {
		self::guard();
		$account   = self::post_id( 'account_id' );
		$policy_id = self::post_ref( 'policy_id' );
		$enabled   = self::post_bool( 'enabled' );
		$api       = self::api();

		$cur = $api->call( 'GET', "accounts/{$account}/alerting/v3/policies/{$policy_id}" );
		if ( ! $cur['success'] || ! is_array( $cur['result'] ) ) {
			self::respond( $cur, self::PERM_NOTIFY_READ );
		}
		$p    = $cur['result'];
		$body = [
			'name'       => (string) ( $p['name'] ?? '' ),
			'alert_type' => (string) ( $p['alert_type'] ?? '' ),
			'enabled'    => $enabled,
			'mechanisms' => self::clean_mechanisms( $p['mechanisms'] ?? [] ),
		];
		if ( ! empty( $p['description'] ) ) {
			$body['description'] = (string) $p['description'];
		}
		if ( ! empty( $p['alert_interval'] ) ) {
			$body['alert_interval'] = (string) $p['alert_interval'];
		}
		if ( ! empty( $p['filters'] ) && is_array( $p['filters'] ) ) {
			// Resend the existing filters as Cloudflare returned them.
			$body['filters'] = $p['filters'];
		}
		self::respond( $api->call( 'PUT', "accounts/{$account}/alerting/v3/policies/{$policy_id}", $body ), self::PERM_NOTIFY_EDIT );
	}

	public static function ajax_policy_delete(): void {
		self::guard();
		$account   = self::post_id( 'account_id' );
		$policy_id = self::post_ref( 'policy_id' );
		self::respond( self::api()->call( 'DELETE', "accounts/{$account}/alerting/v3/policies/{$policy_id}" ), self::PERM_NOTIFY_EDIT );
	}

	/** Add a webhook destination (name, URL, optional secret). */
	public static function ajax_webhook_create(): void {
		self::guard();
		$account = self::post_id( 'account_id' );
		$name    = self::post( 'name' );
		$url     = esc_url_raw( wp_unslash( (string) ( $_POST['url'] ?? '' ) ), [ 'https', 'http' ] );
		$secret  = self::post( 'secret' );
		if ( $name === '' || $url === '' ) {
			wp_send_json_error( [ 'message' => 'A webhook needs a name and a URL.' ] );
		}
		$body = [ 'name' => $name, 'url' => $url ];
		if ( $secret !== '' ) {
			$body['secret'] = $secret;
		}
		self::respond( self::api()->call( 'POST', "accounts/{$account}/alerting/v3/destinations/webhooks", $body ), self::PERM_NOTIFY_EDIT );
	}

	// ── Health Checks ────────────────────────────────────────────────────────

	public static function ajax_hc_list(): void {
		self::guard();
		$zone = self::post_id( 'zone_id' );
		$res  = self::api()->call_all( "zones/{$zone}/healthchecks", 50 );
		self::respond( $res, self::PERM_HC_READ );
	}

	/** Create a health check, or update one when healthcheck_id is sent. */
	public static function ajax_hc_save(): void {
		self::guard();
		$zone = self::post_id( 'zone_id' );
		$d    = self::post_json( 'check' );

		$name = (string) ( $d['name'] ?? '' );
		if ( ! preg_match( '/^[A-Za-z0-9_-]{1,100}$/', $name ) ) {
			wp_send_json_error( [ 'message' => 'The name can only contain letters, numbers, hyphens and underscores.' ] );
		}
		$address = strtolower( trim( (string) ( $d['address'] ?? '' ) ) );
		if ( ! preg_match( '/^[a-z0-9.:\-\[\]]{1,253}$/', $address ) ) {
			wp_send_json_error( [ 'message' => 'Enter the origin hostname or IP address.' ] );
		}
		$type = strtoupper( (string) ( $d['type'] ?? 'HTTPS' ) );
		if ( ! in_array( $type, [ 'HTTP', 'HTTPS', 'TCP' ], true ) ) {
			$type = 'HTTPS';
		}

		// When editing, start from the check as Cloudflare has it, so fields the
		// form does not show (description, extra request headers) are kept.
		$id      = isset( $_POST['healthcheck_id'] ) && $_POST['healthcheck_id'] !== '' ? self::post_ref( 'healthcheck_id' ) : '';
		$current = [];
		if ( $id !== '' ) {
			$cur = self::api()->call( 'GET', "zones/{$zone}/healthchecks/{$id}" );
			if ( ! $cur['success'] || ! is_array( $cur['result'] ) ) {
				self::respond( $cur, self::PERM_HC_EDIT );
			}
			$current = $cur['result'];
		}

		$int = static function ( string $k, int $min, int $max ) use ( $d ): ?int {
			if ( ! isset( $d[ $k ] ) || $d[ $k ] === '' ) {
				return null;
			}
			return max( $min, min( $max, (int) $d[ $k ] ) );
		};

		$body = [
			'name'      => $name,
			'address'   => $address,
			'type'      => $type,
			'suspended' => ! empty( $d['suspended'] ),
		];
		if ( array_key_exists( 'description', $d ) ) {
			$body['description'] = sanitize_text_field( (string) $d['description'] );
		} elseif ( isset( $current['description'] ) && is_string( $current['description'] ) ) {
			$body['description'] = $current['description'];
		}
		foreach ( [ 'interval' => [ 1, 86400 ], 'retries' => [ 0, 100 ], 'timeout' => [ 1, 3600 ], 'consecutive_fails' => [ 1, 100 ], 'consecutive_successes' => [ 1, 100 ] ] as $k => [ $min, $max ] ) {
			$v = $int( $k, $min, $max );
			if ( $v !== null ) {
				$body[ $k ] = $v;
			}
		}

		$regions = array_values( array_intersect( self::str_list( $d['check_regions'] ?? [] ), self::HC_REGIONS ) );
		if ( $regions ) {
			$body['check_regions'] = in_array( 'ALL_REGIONS', $regions, true ) ? [ 'ALL_REGIONS' ] : $regions;
		}

		$port = $int( 'port', 1, 65535 );
		if ( $type === 'TCP' ) {
			$body['tcp_config'] = [ 'method' => 'connection_established' ];
			if ( $port !== null ) {
				$body['tcp_config']['port'] = $port;
			}
		} else {
			$path = (string) ( $d['path'] ?? '/' );
			$path = '/' . ltrim( preg_replace( '/[^A-Za-z0-9\/\-._~%?&=+,;:@!$\'()*]/', '', $path ) ?? '', '/' );
			$codes = array_values( array_filter(
				self::str_list( $d['expected_codes'] ?? [ '200' ], 3 ),
				static fn( string $c ): bool => (bool) preg_match( '/^[1-5][0-9x]{2}$/', $c )
			) );
			$http = [
				'method'           => ( strtoupper( (string) ( $d['method'] ?? 'GET' ) ) === 'HEAD' ) ? 'HEAD' : 'GET',
				'path'             => $path,
				'expected_codes'   => $codes ?: [ '200' ],
				'follow_redirects' => ! empty( $d['follow_redirects'] ),
				'allow_insecure'   => ! empty( $d['allow_insecure'] ),
				'expected_body'    => sanitize_text_field( (string) ( $d['expected_body'] ?? '' ) ),
			];
			if ( $port !== null ) {
				$http['port'] = $port;
			}
			// Keep any request headers already on the check (an auth header for a
			// protected status page, for example) and only replace Host.
			$headers = is_array( $current['http_config']['header'] ?? null ) ? $current['http_config']['header'] : [];
			foreach ( array_keys( $headers ) as $hname ) {
				if ( strtolower( (string) $hname ) === 'host' ) {
					unset( $headers[ $hname ] );
				}
			}
			$host = strtolower( trim( (string) ( $d['host_header'] ?? '' ) ) );
			if ( $host !== '' && preg_match( '/^[a-z0-9.\-]{1,253}$/', $host ) ) {
				$headers['Host'] = [ $host ];
			}
			if ( $headers ) {
				$http['header'] = $headers;
			}
			$body['http_config'] = $http;
		}

		$res = $id !== ''
			? self::api()->call( 'PATCH', "zones/{$zone}/healthchecks/{$id}", $body )
			: self::api()->call( 'POST', "zones/{$zone}/healthchecks", $body );
		self::respond( $res, self::PERM_HC_EDIT );
	}

	/** Pause or resume a health check (suspended flag). */
	public static function ajax_hc_toggle(): void {
		self::guard();
		$zone      = self::post_id( 'zone_id' );
		$id        = self::post_ref( 'healthcheck_id' );
		$suspended = self::post_bool( 'suspended' );
		$api       = self::api();

		$cur = $api->call( 'GET', "zones/{$zone}/healthchecks/{$id}" );
		if ( ! $cur['success'] || ! is_array( $cur['result'] ) ) {
			self::respond( $cur, self::PERM_HC_READ );
		}
		$body = [
			'name'      => (string) ( $cur['result']['name'] ?? '' ),
			'address'   => (string) ( $cur['result']['address'] ?? '' ),
			'suspended' => $suspended,
		];
		self::respond( $api->call( 'PATCH', "zones/{$zone}/healthchecks/{$id}", $body ), self::PERM_HC_EDIT );
	}

	public static function ajax_hc_delete(): void {
		self::guard();
		$zone = self::post_id( 'zone_id' );
		$id   = self::post_ref( 'healthcheck_id' );
		self::respond( self::api()->call( 'DELETE', "zones/{$zone}/healthchecks/{$id}" ), self::PERM_HC_EDIT );
	}
}
