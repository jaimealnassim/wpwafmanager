<?php
declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Cloudflare API wrapper.
 * Supports API Token auth and Email + Global API Key auth.
 * PHP 8.0+ with strict types.
 */
class WPWAF_API {

	private const CF_BASE = 'https://api.cloudflare.com/client/v4/';

	public function __construct(
		private readonly string $auth_method,
		private readonly string $api_token = '',
		private readonly string $email = '',
		private readonly string $api_key = '',
	) {}

	private function auth_headers(): array {
		return match ( $this->auth_method ) {
			'key'   => [
				'X-Auth-Email' => $this->email,
				'X-Auth-Key'   => $this->api_key,
				'Content-Type' => 'application/json',
			],
			default => [
				'Authorization' => 'Bearer ' . $this->api_token,
				'Content-Type'  => 'application/json',
			],
		};
	}

	private function request( string $method, string $endpoint, array $body = [] ): array {
		$args = [
			'method'  => strtoupper( $method ),
			'headers' => $this->auth_headers(),
			'timeout' => 20,
		];
		if ( ! empty( $body ) ) {
			$args['body'] = wp_json_encode( $body );
		}
		$response = wp_remote_request( self::CF_BASE . ltrim( $endpoint, '/' ), $args );
		if ( is_wp_error( $response ) ) {
			return [
				'success' => false,
				'errors'  => [ [ 'message' => $response->get_error_message() ] ],
			];
		}
		$decoded = json_decode( wp_remote_retrieve_body( $response ), associative: true );
		return is_array( $decoded ) ? $decoded : [
			'success' => false,
			'errors'  => [ [ 'message' => 'Invalid API response' ] ],
		];
	}

	private function first_error( array $result ): string {
		$msg  = $result['errors'][0]['message'] ?? 'Unknown Cloudflare API error';
		$code = $result['errors'][0]['code'] ?? '';
		return $code ? "[{$code}] {$msg}" : $msg;
	}

	public function verify_credentials(): array {
		if ( $this->auth_method === 'key' ) {
			$result = $this->request( 'GET', 'user' );
			if ( empty( $result['success'] ) ) {
				return [ 'success' => false, 'message' => $this->first_error( $result ) ];
			}
			$email = $result['result']['email'] ?? $this->email;
			return [ 'success' => true, 'message' => "Authenticated as {$email}" ];
		}
		$result = $this->request( 'GET', 'user/tokens/verify' );
		if ( empty( $result['success'] ) ) {
			return [ 'success' => false, 'message' => $this->first_error( $result ) ];
		}
		$status = $result['result']['status'] ?? 'active';
		return [ 'success' => true, 'message' => "Token status: {$status}" ];
	}

	public function list_zones(): array {
		$zones = [];
		$page  = 1;
		do {
			$result = $this->request( 'GET', "zones?per_page=50&page={$page}&status=active" );
			if ( empty( $result['success'] ) ) {
				return [ 'success' => false, 'message' => $this->first_error( $result ) ];
			}
				foreach ( $result['result'] as $zone ) {
				$zones[] = [
					'id'         => $zone['id'],
					'name'       => $zone['name'],
					'plan'       => $zone['plan']['name'] ?? 'Unknown',
					'account_id' => $zone['account']['id'] ?? '',
				];
			}
			$total_pages = (int) ( $result['result_info']['total_pages'] ?? 1 );
			$page++;
		} while ( $page <= $total_pages );
		return [ 'success' => true, 'zones' => $zones ];
	}

	private function get_waf_ruleset( string $zone_id ): ?array {
		$result = $this->request( 'GET', "zones/{$zone_id}/rulesets" );
		if ( empty( $result['success'] ) || empty( $result['result'] ) ) {
			return null;
		}
		foreach ( $result['result'] as $rs ) {
			if ( ( $rs['phase'] ?? '' ) === 'http_request_firewall_custom' && ( $rs['kind'] ?? '' ) === 'zone' ) {
				return $rs;
			}
		}
		return null;
	}

	private function get_ruleset_detail( string $zone_id, string $ruleset_id ): array {
		$result = $this->request( 'GET', "zones/{$zone_id}/rulesets/{$ruleset_id}" );
		if ( empty( $result['success'] ) ) {
			return [ 'success' => false, 'message' => $this->first_error( $result ) ];
		}
		return [ 'success' => true, 'rules' => $result['result']['rules'] ?? [] ];
	}

	public function list_rules( string $zone_id ): array {
		$ruleset = $this->get_waf_ruleset( $zone_id );
		if ( ! $ruleset ) {
			return [ 'success' => true, 'rules' => [], 'ruleset_id' => null ];
		}
		$data = $this->get_ruleset_detail( $zone_id, $ruleset['id'] );
		if ( empty( $data['success'] ) ) {
			return $data;
		}
		return [ 'success' => true, 'rules' => $data['rules'], 'ruleset_id' => $ruleset['id'] ];
	}

	public function deploy_rules( string $zone_id, array $new_rules ): array {
		$ruleset          = $this->get_waf_ruleset( $zone_id );

		$do_request = function( array $rules ) use ( $zone_id, $ruleset ): array {
			if ( $ruleset ) {
				// Replace all existing rules with the new set (clean deploy).
				return $this->request( 'PUT', "zones/{$zone_id}/rulesets/{$ruleset['id']}", [ 'rules' => $rules ] );
			}
			return $this->request( 'POST', "zones/{$zone_id}/rulesets", [
				'name'  => 'WAF Custom Rules',
				'kind'  => 'zone',
				'phase' => 'http_request_firewall_custom',
				'rules' => $rules,
			] );
		};

		$result = $do_request( $new_rules );

		// Error 20120: a skip phase is not authorized on this plan (e.g. Free/Pro).
		// Strip the restricted phase and retry automatically.
		if ( empty( $result['success'] ) && (int) ( $result['errors'][0]['code'] ?? 0 ) === 20120 ) {
			$restricted = [ 'http_request_firewall_custom', 'http_request_sbfm' ];
			$free_rules = array_map( function( array $rule ) use ( $restricted ) {
				if ( ( $rule['action'] ?? '' ) === 'skip' && isset( $rule['action_parameters']['phases'] ) ) {
					$phases = array_values( array_diff( $rule['action_parameters']['phases'], $restricted ) );
					if ( empty( $phases ) ) {
						unset( $rule['action_parameters']['phases'] );
					} else {
						$rule['action_parameters']['phases'] = $phases;
					}
				}
				return $rule;
			}, $new_rules );
			$result = $do_request( $free_rules );
		}

		if ( empty( $result['success'] ) ) {
			return [ 'success' => false, 'message' => $this->first_error( $result ) ];
		}
		$count = count( $new_rules );
		return [ 'success' => true, 'message' => "{$count} rule(s) deployed." ];
	}

	// ── DNS Records ────────────────────────────────────────────────────────────

	public function list_dns_records( string $zone_id ): array {
		$records = [];
		$page    = 1;
		do {
			$result = $this->request( 'GET', "zones/{$zone_id}/dns_records?per_page=100&page={$page}" );
			if ( empty( $result['success'] ) ) {
				return [ 'success' => false, 'message' => $this->first_error( $result ) ];
			}
			$records = array_merge( $records, $result['result'] ?? [] );
			$total   = (int) ( $result['result_info']['total_pages'] ?? 1 );
			$page++;
		} while ( $page <= $total );
		return [ 'success' => true, 'records' => $records ];
	}

	public function create_dns_record( string $zone_id, array $data ): array {
		$result = $this->request( 'POST', "zones/{$zone_id}/dns_records", $data );
		if ( empty( $result['success'] ) ) {
			return [ 'success' => false, 'message' => $this->first_error( $result ) ];
		}
		return [ 'success' => true, 'record' => $result['result'] ];
	}

	public function update_dns_record( string $zone_id, string $record_id, array $data ): array {
		$result = $this->request( 'PATCH', "zones/{$zone_id}/dns_records/{$record_id}", $data );
		if ( empty( $result['success'] ) ) {
			return [ 'success' => false, 'message' => $this->first_error( $result ) ];
		}
		return [ 'success' => true, 'record' => $result['result'] ];
	}

	public function delete_dns_record( string $zone_id, string $record_id ): array {
		$result = $this->request( 'DELETE', "zones/{$zone_id}/dns_records/{$record_id}" );
		if ( empty( $result['success'] ) ) {
			return [ 'success' => false, 'message' => $this->first_error( $result ) ];
		}
		return [ 'success' => true ];
	}

	// ── Zone Settings ──────────────────────────────────────────────────────────

	public function get_zone_settings( string $zone_id ): array {
		$keys = [ 'security_level', 'ssl', 'development_mode', 'always_online',
		           'cache_level', 'minify', 'rocket_loader', 'browser_cache_ttl' ];
		$out = [];
		foreach ( $keys as $key ) {
			$r = $this->request( 'GET', "zones/{$zone_id}/settings/{$key}" );
			if ( ! empty( $r['success'] ) ) {
				$out[ $key ] = $r['result']['value'] ?? null;
			}
		}
		// Under Attack Mode is security_level = 'under_attack'
		$out['under_attack'] = ( ( $out['security_level'] ?? '' ) === 'under_attack' );
		return [ 'success' => true, 'settings' => $out ];
	}

	public function update_zone_setting( string $zone_id, string $key, mixed $value ): array {
		$result = $this->request( 'PATCH', "zones/{$zone_id}/settings/{$key}", [ 'value' => $value ] );
		if ( empty( $result['success'] ) ) {
			return [ 'success' => false, 'message' => $this->first_error( $result ) ];
		}
		return [ 'success' => true ];
	}

	public function set_under_attack( string $zone_id, bool $on ): array {
		return $this->update_zone_setting( $zone_id, 'security_level', $on ? 'under_attack' : 'medium' );
	}

	public function set_dev_mode( string $zone_id, bool $on ): array {
		return $this->update_zone_setting( $zone_id, 'development_mode', $on ? 'on' : 'off' );
	}

	// ── Zone Analytics ─────────────────────────────────────────────────────────

	/**
	 * Fetch zone analytics via Cloudflare GraphQL Analytics API.
	 * The legacy REST endpoint (zones/{id}/analytics/dashboard) is deprecated
	 * and returns zeros — GraphQL is the current standard.
	 */
	public function get_zone_analytics( string $zone_id, int $days = 7 ): array {
		$since = gmdate( 'Y-m-d', strtotime( "-{$days} days" ) );
		$until = gmdate( 'Y-m-d' );

		// Use Cloudflare GraphQL Analytics API — the legacy REST endpoint is deprecated.
		$query = 'query ZoneAnalytics($zoneTag:String!,$since:Date!,$until:Date!){'
			. 'viewer{zones(filter:{zoneTag:$zoneTag}){'
			. 'httpRequests1dGroups(limit:31,filter:{date_geq:$since,date_leq:$until}){'
			. 'sum{requests cachedRequests bytes cachedBytes threats pageViews}'
			. '}}}}';

		$payload = [
			'query'     => $query,
			'variables' => [
				'zoneTag' => $zone_id,
				'since'   => $since,
				'until'   => $until,
			],
		];

		$args = [
			'method'  => 'POST',
			'headers' => $this->auth_headers(),
			'body'    => wp_json_encode( $payload ),
			'timeout' => 20,
		];
		// Override Content-Type — auth_headers() sets application/json already for token auth
		$args['headers']['Content-Type'] = 'application/json';

		$response = wp_remote_request( 'https://api.cloudflare.com/client/v4/graphql', $args );

		if ( is_wp_error( $response ) ) {
			return [ 'success' => false, 'requests' => 0, 'bandwidth' => 0, 'threats' => 0, 'pageviews' => 0, 'cached' => 0 ];
		}

		$body    = wp_remote_retrieve_body( $response );
		$decoded = json_decode( $body, associative: true );
		$groups  = $decoded['data']['viewer']['zones'][0]['httpRequests1dGroups'] ?? [];

		$requests  = 0;
		$cached    = 0;
		$bytes     = 0;
		$threats   = 0;
		$pageviews = 0;

		foreach ( $groups as $group ) {
			$sum       = $group['sum'] ?? [];
			$requests  += (int) ( $sum['requests']       ?? 0 );
			$cached    += (int) ( $sum['cachedRequests'] ?? 0 );
			$bytes     += (int) ( $sum['bytes']          ?? 0 );
			$threats   += (int) ( $sum['threats']        ?? 0 );
			$pageviews += (int) ( $sum['pageViews']      ?? 0 );
		}

		$cache_rate = $requests > 0 ? round( ( $cached / $requests ) * 100, 1 ) : 0;

		return [
			'success'    => true,
			'requests'   => $requests,
			'bandwidth'  => $bytes,
			'threats'    => $threats,
			'pageviews'  => $pageviews,
			'cached'     => $cached,
			'cache_rate' => $cache_rate,
		];
	}

	// ── Zone Overview (analytics only — settings removed) ─────────────────────

	public function get_zone_overview( string $zone_id ): array {
		$analytics = $this->get_zone_analytics( $zone_id );
		return [
			'success'   => true,
			'settings'  => [],
			'analytics' => array_diff_key( $analytics, [ 'success' => 1 ] ),
		];
	}

	// ── Account ID ────────────────────────────────────────────────────────────

	/**
	 * Retrieve the Cloudflare account ID associated with this token.
	 * Uses the first account returned by /accounts, or extracts from zones.
	 */
	public function get_account_id(): string {
		$result = $this->request( 'GET', 'accounts?per_page=1' );
		if ( ! empty( $result['success'] ) && ! empty( $result['result'][0]['id'] ) ) {
			return $result['result'][0]['id'];
		}
		// Fallback: extract from first zone
		$zones = $this->request( 'GET', 'zones?per_page=1&status=active' );
		return $zones['result'][0]['account']['id'] ?? '';
	}

	// ── Account-Level IP Access Rules ─────────────────────────────────────────

	public function list_ip_rules( string $account_id, string $mode = '', int $page = 1 ): array {
		$qs  = "per_page=100&page={$page}&order=configuration.value";
		if ( $mode !== '' ) $qs .= "&mode={$mode}";
		$result = $this->request( 'GET', "accounts/{$account_id}/firewall/access_rules/rules?{$qs}" );
		if ( empty( $result['success'] ) ) {
			return [ 'success' => false, 'message' => $this->first_error( $result ) ];
		}
		$rules      = $result['result'] ?? [];
		$total_pages = (int) ( $result['result_info']['total_pages'] ?? 1 );
		if ( $page < $total_pages ) {
			$more = $this->list_ip_rules( $account_id, $mode, $page + 1 );
			if ( ! empty( $more['success'] ) ) {
				$rules = array_merge( $rules, $more['rules'] );
			}
		}
		return [ 'success' => true, 'rules' => $rules ];
	}

	public function create_ip_rule( string $account_id, array $data ): array {
		$result = $this->request( 'POST', "accounts/{$account_id}/firewall/access_rules/rules", $data );
		if ( empty( $result['success'] ) ) {
			return [ 'success' => false, 'message' => $this->first_error( $result ) ];
		}
		return [ 'success' => true, 'rule' => $result['result'] ];
	}

	public function update_ip_rule( string $account_id, string $rule_id, array $data ): array {
		$result = $this->request( 'PATCH', "accounts/{$account_id}/firewall/access_rules/rules/{$rule_id}", $data );
		if ( empty( $result['success'] ) ) {
			return [ 'success' => false, 'message' => $this->first_error( $result ) ];
		}
		return [ 'success' => true, 'rule' => $result['result'] ];
	}

	public function delete_ip_rule( string $account_id, string $rule_id ): array {
		$result = $this->request( 'DELETE', "accounts/{$account_id}/firewall/access_rules/rules/{$rule_id}" );
		if ( empty( $result['success'] ) ) {
			return [ 'success' => false, 'message' => $this->first_error( $result ) ];
		}
		return [ 'success' => true ];
	}

	// ── Cache Purge ───────────────────────────────────────────────────────────

	public function purge_cache( string $zone_id, array $data ): array {
		$result = $this->request( 'POST', "zones/{$zone_id}/purge_cache", $data );
		if ( empty( $result['success'] ) ) {
			return [ 'success' => false, 'message' => $this->first_error( $result ) ];
		}
		return [ 'success' => true ];
	}

	// ── Zone Settings (all at once) ────────────────────────────────────────────

	public function get_all_zone_settings( string $zone_id ): array {
		$result = $this->request( 'GET', "zones/{$zone_id}/settings" );
		if ( empty( $result['success'] ) ) {
			return [ 'success' => false, 'message' => $this->first_error( $result ) ];
		}
		// Key the settings by id for easy lookup
		$settings = [];
		foreach ( $result['result'] ?? [] as $s ) {
			if ( isset( $s['id'] ) ) $settings[ $s['id'] ] = $s['value'];
		}
		return [ 'success' => true, 'settings' => $settings ];
	}

	public function update_zone_setting_direct( string $zone_id, string $key, mixed $value ): array {
		$result = $this->request( 'PATCH', "zones/{$zone_id}/settings/{$key}", [ 'value' => $value ] );
		if ( empty( $result['success'] ) ) {
			return [ 'success' => false, 'message' => $this->first_error( $result ) ];
		}
		return [ 'success' => true, 'value' => $result['result']['value'] ?? $value ];
	}

	// ── Security Events (GraphQL) ──────────────────────────────────────────────

	public function get_security_events( string $zone_id, int $limit = 100, string $action_filter = '', int $hours = 24 ): array {
		// firewallEventsAdaptive requires: Pro plan+, datetime range filter, and zone scoped via parent zones().
		// The filter does NOT accept zoneTag — zone is already selected by zones(filter:{zoneTag:...}).
		$has_action = $action_filter !== '';
		$since      = gmdate( 'Y-m-d\TH:i:s\Z', time() - ( $hours * 3600 ) );
		$until      = gmdate( 'Y-m-d\TH:i:s\Z' );

		// Build filter object — datetime range is REQUIRED by the schema
		$filter_fields = 'datetime_geq:$since,datetime_leq:$until';
		if ( $has_action ) $filter_fields .= ',action:$action';

		$query = 'query SecurityEvents($zoneTag:String!,$limit:Int!,$since:Time!,$until:Time!'
			. ( $has_action ? ',$action:String!' : '' )
			. '){'
			. 'viewer{zones(filter:{zoneTag:$zoneTag}){'
			. "firewallEventsAdaptive(limit:\$limit,orderBy:[datetime_DESC],filter:{{$filter_fields}}){{"
			. 'action clientIP clientCountryName clientAsn'
			. ' clientRequestHTTPHost clientRequestHTTPMethodName'
			. ' clientRequestPath clientRequestQuery'
			. ' datetime rayName ruleId source userAgent'
			. '}}}}';

		$variables = [ 'zoneTag' => $zone_id, 'limit' => $limit, 'since' => $since, 'until' => $until ];
		if ( $has_action ) $variables['action'] = $action_filter;
		$args = [
			'method'  => 'POST',
			'headers' => $this->auth_headers(),
			'body'    => wp_json_encode( [ 'query' => $query, 'variables' => $variables ] ),
			'timeout' => 20,
		];
		$args['headers']['Content-Type'] = 'application/json';

		$response = wp_remote_request( 'https://api.cloudflare.com/client/v4/graphql', $args );
		if ( is_wp_error( $response ) ) {
			return [ 'success' => false, 'message' => $response->get_error_message() ];
		}

		$decoded = json_decode( wp_remote_retrieve_body( $response ), associative: true );
		if ( ! empty( $decoded['errors'] ) ) {
			$msg = $decoded['errors'][0]['message'] ?? 'GraphQL error';
			return [ 'success' => false, 'message' => $msg ];
		}

		if ( ! empty( $decoded['errors'] ) ) {
			$msg      = $decoded['errors'][0]['message'] ?? 'GraphQL error';
			$path     = $decoded['errors'][0]['path'] ?? [];
			// Detect Pro plan requirement
			$plan_err = stripos( $msg, 'not entitled' ) !== false
				|| stripos( $msg, 'plan' ) !== false
				|| stripos( $msg, 'upgrade' ) !== false
				|| stripos( $msg, 'not available' ) !== false;
			return [ 'success' => false, 'message' => $msg, 'plan_error' => $plan_err ];
		}

		$events = $decoded['data']['viewer']['zones'][0]['firewallEventsAdaptive'] ?? [];
		return [ 'success' => true, 'events' => $events, 'count' => count( $events ) ];
	}

	// ── Email Routing ─────────────────────────────────────────────────────────

	public function get_email_routing( string $zone_id ): array {
		$result = $this->request( 'GET', "zones/{$zone_id}/email/routing" );
		if ( empty( $result['success'] ) ) {
			return [ 'success' => false, 'message' => $this->first_error( $result ) ];
		}
		return [ 'success' => true, 'routing' => $result['result'] ?? [] ];
	}

	public function enable_email_routing( string $zone_id ): array {
		$result = $this->request( 'POST', "zones/{$zone_id}/email/routing/enable" );
		if ( empty( $result['success'] ) ) {
			return [ 'success' => false, 'message' => $this->first_error( $result ) ];
		}
		return [ 'success' => true ];
	}

	public function disable_email_routing( string $zone_id ): array {
		$result = $this->request( 'POST', "zones/{$zone_id}/email/routing/disable" );
		if ( empty( $result['success'] ) ) {
			return [ 'success' => false, 'message' => $this->first_error( $result ) ];
		}
		return [ 'success' => true ];
	}

	public function list_email_rules( string $zone_id ): array {
		$result = $this->request( 'GET', "zones/{$zone_id}/email/routing/rules" );
		if ( empty( $result['success'] ) ) {
			return [ 'success' => false, 'message' => $this->first_error( $result ) ];
		}
		return [ 'success' => true, 'rules' => $result['result'] ?? [] ];
	}

	public function create_email_rule( string $zone_id, array $data ): array {
		$result = $this->request( 'POST', "zones/{$zone_id}/email/routing/rules", $data );
		if ( empty( $result['success'] ) ) {
			return [ 'success' => false, 'message' => $this->first_error( $result ) ];
		}
		return [ 'success' => true, 'rule' => $result['result'] ?? [] ];
	}

	public function update_email_rule( string $zone_id, string $rule_id, array $data ): array {
		$result = $this->request( 'PUT', "zones/{$zone_id}/email/routing/rules/{$rule_id}", $data );
		if ( empty( $result['success'] ) ) {
			return [ 'success' => false, 'message' => $this->first_error( $result ) ];
		}
		return [ 'success' => true, 'rule' => $result['result'] ?? [] ];
	}

	public function delete_email_rule( string $zone_id, string $rule_id ): array {
		$result = $this->request( 'DELETE', "zones/{$zone_id}/email/routing/rules/{$rule_id}" );
		if ( empty( $result['success'] ) ) {
			return [ 'success' => false, 'message' => $this->first_error( $result ) ];
		}
		return [ 'success' => true ];
	}

/**
	 * Destination addresses are account-level (verified emails Cloudflare can forward to).
	 */
	public function list_email_addresses( string $account_id ): array {
		$result = $this->request( 'GET', "accounts/{$account_id}/email/routing/addresses?per_page=50" );
		if ( empty( $result['success'] ) ) {
			return [ 'success' => false, 'message' => $this->first_error( $result ) ];
		}
		return [ 'success' => true, 'addresses' => $result['result'] ?? [] ];
	}

	public function create_email_address( string $account_id, string $email ): array {
		$result = $this->request( 'POST', "accounts/{$account_id}/email/routing/addresses",
			[ 'email' => $email ]
		);
		if ( empty( $result['success'] ) ) {
			return [ 'success' => false, 'message' => $this->first_error( $result ) ];
		}
		return [ 'success' => true, 'address' => $result['result'] ?? [] ];
	}

	// ── Email Routing — catch-all rule ────────────────────────────────────────

	public function get_catch_all_rule( string $zone_id ): array {
		$result = $this->request( 'GET', "zones/{$zone_id}/email/routing/rules/catch_all" );
		if ( empty( $result['success'] ) ) {
			return [ 'success' => false, 'message' => $this->first_error( $result ) ];
		}
		return [ 'success' => true, 'rule' => $result['result'] ?? [] ];
	}

	public function update_catch_all_rule( string $zone_id, array $data ): array {
		$result = $this->request( 'PUT', "zones/{$zone_id}/email/routing/rules/catch_all", $data );
		if ( empty( $result['success'] ) ) {
			return [ 'success' => false, 'message' => $this->first_error( $result ) ];
		}
		return [ 'success' => true, 'rule' => $result['result'] ?? [] ];
	}
}
