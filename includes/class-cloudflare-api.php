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
		private string $auth_method,
		private string $api_token = '',
		private string $email = '',
		private string $api_key = '',
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

	/**
	 * True when an endpoint path is made only of plain segments, so a value
	 * placed into it can never climb to another endpoint ("..") or cut the
	 * path short ("#"). The query string, if any, is not checked.
	 */
	private static function safe_endpoint( string $endpoint ): bool {
		$path = explode( '?', $endpoint, 2 )[0];
		if ( str_contains( $endpoint, '#' ) || $path === '' ) {
			return false;
		}
		foreach ( explode( '/', trim( $path, '/' ) ) as $segment ) {
			if ( $segment === '' || $segment === '.' || $segment === '..' || ! preg_match( '/^[A-Za-z0-9_.\-]+$/', $segment ) ) {
				return false;
			}
		}
		return true;
	}

	private function request( string $method, string $endpoint, array $body = [] ): array {
		if ( ! self::safe_endpoint( $endpoint ) ) {
			return [ 'success' => false, 'errors' => [ [ 'message' => 'Invalid Cloudflare API path.' ] ] ];
		}
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
		$out  = $code ? "[{$code}] {$msg}" : $msg;
		if ( (int) $code === 20127 ) {
			$out .= '. Your rule expression exceeds Cloudflare\'s 4096-character limit. Try moving some IPs from the Custom IP Allowlist to the IP Access Rules module instead.';
		}
		return $out;
	}

	// ── Generic access for feature modules ─────────────────────────────────────

	/**
	 * Make a Cloudflare API v4 call and return a normalised result:
	 *   success (bool), result (mixed), result_info (array), message (string),
	 *   code (int, first Cloudflare error code or 0).
	 * Feature modules (Bot Control, SSL & Insights, Alerts, Turnstile, Page
	 * Rules) use this instead of adding a dedicated method for every endpoint.
	 */
	public function call( string $method, string $endpoint, array $body = [] ): array {
		$result = $this->request( $method, $endpoint, $body );
		if ( empty( $result['success'] ) ) {
			return [
				'success'     => false,
				'result'      => $result['result'] ?? null,
				'result_info' => [],
				'message'     => $this->first_error( $result ),
				'code'        => (int) ( $result['errors'][0]['code'] ?? 0 ),
			];
		}
		return [
			'success'     => true,
			'result'      => $result['result'] ?? null,
			'result_info' => is_array( $result['result_info'] ?? null ) ? $result['result_info'] : [],
			'message'     => '',
			'code'        => 0,
		];
	}

	/**
	 * GET every page of a paginated list endpoint and merge the results.
	 * $endpoint may already contain a query string.
	 */
	public function call_all( string $endpoint, int $per_page = 50, int $max_pages = 40 ): array {
		$items = [];
		$page  = 1;
		$sep   = str_contains( $endpoint, '?' ) ? '&' : '?';
		do {
			$res = $this->call( 'GET', "{$endpoint}{$sep}per_page={$per_page}&page={$page}" );
			if ( ! $res['success'] ) {
				return $res;
			}
			$items       = array_merge( $items, is_array( $res['result'] ) ? $res['result'] : [] );
			$total_pages = (int) ( $res['result_info']['total_pages'] ?? 1 );
			$page++;
		} while ( $page <= $total_pages && $page <= $max_pages );
		return [ 'success' => true, 'result' => $items, 'result_info' => [], 'message' => '', 'code' => 0 ];
	}

	/**
	 * Run a GraphQL Analytics API query.
	 * Returns success, data (the "data" object) and message (first error).
	 * GraphQL can return partial data alongside errors; success is false
	 * whenever any error is present so callers can fall back cleanly.
	 */
	public function graphql( string $query, array $variables = [] ): array {
		$args = [
			'method'  => 'POST',
			'headers' => $this->auth_headers(),
			'body'    => wp_json_encode( [ 'query' => $query, 'variables' => (object) $variables ] ),
			'timeout' => 25,
		];
		$args['headers']['Content-Type'] = 'application/json';
		$response = wp_remote_request( self::CF_BASE . 'graphql', $args );
		if ( is_wp_error( $response ) ) {
			return [ 'success' => false, 'data' => [], 'message' => $response->get_error_message() ];
		}
		$decoded = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $decoded ) ) {
			return [ 'success' => false, 'data' => [], 'message' => 'Invalid response from the Cloudflare GraphQL API.' ];
		}
		$data = is_array( $decoded['data'] ?? null ) ? $decoded['data'] : [];
		if ( ! empty( $decoded['errors'] ) ) {
			return [ 'success' => false, 'data' => $data, 'message' => (string) ( $decoded['errors'][0]['message'] ?? 'GraphQL error' ) ];
		}
		return [ 'success' => true, 'data' => $data, 'message' => '' ];
	}

	/** Cloudflare accounts this credential can see (id, name). */
	public function list_cf_accounts(): array {
		$res = $this->call_all( 'accounts' );
		if ( ! $res['success'] ) {
			return $res;
		}
		$accounts = [];
		foreach ( $res['result'] as $a ) {
			if ( ! empty( $a['id'] ) ) {
				$accounts[] = [ 'id' => (string) $a['id'], 'name' => (string) ( $a['name'] ?? $a['id'] ) ];
			}
		}
		return [ 'success' => true, 'accounts' => $accounts ];
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

	/**
	 * Deploys the plugin's rules to a zone's custom rules.
	 *
	 * The plugin's own rules (named "[CF WAF] ...") are replaced and placed first,
	 * so Rule 1's Skip runs before everything else. Other custom rules in the
	 * zone are kept after them, unless $replace_all is true.
	 */
	public function deploy_rules( string $zone_id, array $new_rules, bool $replace_all = false ): array {
		$ruleset = $this->get_waf_ruleset( $zone_id );
		$others  = [];

		if ( $ruleset ) {
			$detail = $this->get_ruleset_detail( $zone_id, $ruleset['id'] );
			if ( empty( $detail['success'] ) ) {
				return [ 'success' => false, 'message' => $detail['message'] ?? 'Could not read the zone\'s custom rules.' ];
			}
			$existing_ids = [];
			foreach ( $detail['rules'] as $rule ) {
				$desc = (string) ( $rule['description'] ?? '' );
				if ( WPWAF_Rule_Builder::is_plugin_rule( $desc ) ) {
					$slot = WPWAF_Rule_Builder::slot_for( $desc );
					if ( $slot !== null && ! isset( $existing_ids[ $slot ] ) && ! empty( $rule['id'] ) ) {
						$existing_ids[ $slot ] = (string) $rule['id'];
					}
					continue;
				}
				if ( $replace_all ) continue;
				unset( $rule['version'], $rule['last_updated'] );
				$others[] = $rule;
			}
			// Update the plugin's existing rules in place so their history in Cloudflare is kept.
			foreach ( $new_rules as $i => $rule ) {
				$slot = WPWAF_Rule_Builder::slot_for( (string) $rule['description'] );
				if ( $slot !== null && isset( $existing_ids[ $slot ] ) ) {
					$new_rules[ $i ]['id'] = $existing_ids[ $slot ];
				}
			}
		}

		$do_request = function( array $rules ) use ( $zone_id, $ruleset, $others ): array {
			$all = array_merge( $rules, $others );
			if ( $ruleset ) {
				return $this->request( 'PUT', "zones/{$zone_id}/rulesets/{$ruleset['id']}", [ 'rules' => $all ] );
			}
			return $this->request( 'POST', "zones/{$zone_id}/rulesets", [
				'name'  => 'WAF Custom Rules',
				'kind'  => 'zone',
				'phase' => 'http_request_firewall_custom',
				'rules' => $all,
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
			$message = $this->first_error( $result );
			if ( $others ) {
				$message  = rtrim( $message );
				$message .= preg_match( '/[.!?]$/', $message ) ? '' : '.';
				$message .= sprintf( ' Nothing was changed. This zone also has %d other custom rule(s); if the plan does not allow that many rules, remove some in Cloudflare or deploy with "Replace all custom rules in the zone".', count( $others ) );
			}
			return [ 'success' => false, 'message' => $message ];
		}
		$count   = count( $new_rules );
		$message = "{$count} rule(s) deployed.";
		if ( $others ) {
			$message .= sprintf( ' %d other custom rule(s) kept after them.', count( $others ) );
		}
		return [ 'success' => true, 'message' => $message, 'kept' => count( $others ) ];
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
	 * and returns zeros - GraphQL is the current standard.
	 */
	public function get_zone_analytics( string $zone_id, int $days = 7 ): array {
		$since = gmdate( 'Y-m-d', strtotime( "-{$days} days" ) );
		$until = gmdate( 'Y-m-d' );

		// Use Cloudflare GraphQL Analytics API - the legacy REST endpoint is deprecated.
		$query = 'query ZoneAnalytics($zoneTag:String!,$since:Date!,$until:Date!){'
			. 'viewer{zones(filter:{zoneTag:$zoneTag}){'
			. 'httpRequests1dGroups(limit:31,filter:{date_geq:$since,date_leq:$until},orderBy:[date_ASC]){'
			. 'dimensions{date} sum{requests cachedRequests bytes cachedBytes threats pageViews}'
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
		// Override Content-Type - auth_headers() sets application/json already for token auth
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

		$series = []; // one entry per day for the overview sparkline
		foreach ( $groups as $group ) {
			$sum       = $group['sum'] ?? [];
			$series[]  = [
				'date'     => (string) ( $group['dimensions']['date'] ?? '' ),
				'requests' => (int) ( $sum['requests'] ?? 0 ),
				'threats'  => (int) ( $sum['threats'] ?? 0 ),
			];
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
			'series'     => $series,
		];
	}

	// ── Zone Overview (analytics only - settings removed) ─────────────────────

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

	/**
	 * Retrieve the Cloudflare account ID from a known zone ID.
	 * Requires only Zone → Zone → Read - always available with standard tokens.
	 */
	public function get_account_id_from_zone( string $zone_id ): string {
		$result = $this->request( 'GET', "zones/{$zone_id}" );
		return $result['result']['account']['id'] ?? '';
	}

	// ── Account-Level IP Access Rules ─────────────────────────────────────────

	public function list_ip_rules( string $account_id, string $mode = '' ): array {
		$rules = [];
		$page  = 1;
		do {
			$qs     = "per_page=100&page={$page}&order=configuration.value";
			if ( $mode !== '' ) $qs .= "&mode={$mode}";
			$result = $this->request( 'GET', "accounts/{$account_id}/firewall/access_rules/rules?{$qs}" );
			if ( empty( $result['success'] ) ) {
				return [ 'success' => false, 'message' => $this->first_error( $result ) ];
			}
			$rules        = array_merge( $rules, $result['result'] ?? [] );
			$total_pages  = (int) ( $result['result_info']['total_pages'] ?? 1 );
			$page++;
		} while ( $page <= $total_pages );
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

	// ── Zone Settings (one request per setting) ─────────────────────────────────
	// Cloudflare deprecated the bulk GET /zones/{zone_id}/settings endpoint
	// (it stops working on 31 March 2027), so each setting is read with
	// GET /zones/{zone_id}/settings/{setting_id}. The requests run in parallel.

	/**
	 * Everything the Zone Controls settings panel shows, in one parallel batch:
	 * the listed zone settings, Smart Tiered Cache and Managed Transforms.
	 */
	public function get_zone_controls( string $zone_id, array $setting_ids ): array {
		$paths = [];
		foreach ( $setting_ids as $id ) {
			$id = preg_replace( '/[^a-z0-9_]/', '', strtolower( (string) $id ) );
			if ( $id !== '' ) {
				$paths[ 'setting:' . $id ] = "zones/{$zone_id}/settings/{$id}";
			}
		}
		$paths['tiered']  = "zones/{$zone_id}/cache/tiered_cache_smart_topology_enable";
		$paths['managed'] = "zones/{$zone_id}/managed_headers";

		$settings = [];
		$errors   = [];
		$tiered   = null;
		$managed  = null;
		foreach ( $this->get_many( $paths ) as $key => $result ) {
			$ok = ! empty( $result['success'] );
			if ( str_starts_with( $key, 'setting:' ) ) {
				$id = substr( $key, 8 );
				if ( $ok && array_key_exists( 'value', (array) ( $result['result'] ?? [] ) ) ) {
					$settings[ $id ] = $result['result']['value'];
				} else {
					$errors[ $id ] = $this->first_error( $result );
				}
			} elseif ( $key === 'tiered' ) {
				if ( $ok ) {
					$tiered = (string) ( $result['result']['value'] ?? 'off' );
				} else {
					$errors['tiered'] = $this->first_error( $result );
				}
			} else {
				if ( $ok ) {
					$managed = [];
					foreach ( (array) ( $result['result']['managed_response_headers'] ?? [] ) as $t ) {
						if ( ! empty( $t['id'] ) ) {
							$managed[ (string) $t['id'] ] = [
								'enabled'      => ! empty( $t['enabled'] ),
								'has_conflict' => ! empty( $t['has_conflict'] ),
							];
						}
					}
				} else {
					$errors['managed'] = $this->first_error( $result );
				}
			}
		}
		if ( empty( $settings ) && ! empty( $errors ) ) {
			return [ 'success' => false, 'message' => reset( $errors ), 'errors' => $errors ];
		}
		return [ 'success' => true, 'settings' => $settings, 'tiered' => $tiered, 'managed' => $managed, 'errors' => $errors ];
	}

	/** Smart Tiered Cache: PATCH /zones/{zone_id}/cache/tiered_cache_smart_topology_enable, value on|off. */
	public function set_smart_tiered_cache( string $zone_id, bool $on ): array {
		$res = $this->call( 'PATCH', "zones/{$zone_id}/cache/tiered_cache_smart_topology_enable", [ 'value' => $on ? 'on' : 'off' ] );
		return $res['success']
			? [ 'success' => true, 'value' => (string) ( $res['result']['value'] ?? ( $on ? 'on' : 'off' ) ) ]
			: [ 'success' => false, 'message' => $res['message'] ];
	}

	/** Managed Transforms: PATCH /zones/{zone_id}/managed_headers for one response transform. */
	public function set_managed_response_header( string $zone_id, string $transform_id, bool $enabled ): array {
		// The API reference lists both arrays as required; an empty request
		// list changes nothing.
		$res = $this->call( 'PATCH', "zones/{$zone_id}/managed_headers", [
			'managed_request_headers'  => [],
			'managed_response_headers' => [ [ 'id' => $transform_id, 'enabled' => $enabled ] ],
		] );
		if ( ! $res['success'] ) {
			return [ 'success' => false, 'message' => $res['message'] ];
		}
		foreach ( (array) ( $res['result']['managed_response_headers'] ?? [] ) as $t ) {
			if ( ( $t['id'] ?? '' ) === $transform_id ) {
				return [ 'success' => true, 'enabled' => ! empty( $t['enabled'] ) ];
			}
		}
		return [ 'success' => true, 'enabled' => $enabled ];
	}

	/**
	 * GET several endpoints, in parallel when possible. Keys are preserved.
	 * Uses the Requests library bundled with WordPress (WpOrg\Requests on
	 * WordPress 6.2 and later, the global Requests class before that) with the
	 * WordPress CA bundle. Requests that fail, or every request when the site
	 * customises outbound HTTP, go through wp_remote_request() instead.
	 */
	public function get_many( array $paths ): array {
		$out = [];
		if ( empty( $paths ) ) return $out;

		// Parallel requests go straight through the Requests library, which
		// skips the WordPress HTTP layer. Only use them when nothing in that
		// layer needs to apply: no proxy, no external-request blocking and no
		// filter that intercepts or rewrites requests.
		$class = class_exists( '\\WpOrg\\Requests\\Requests' ) ? '\\WpOrg\\Requests\\Requests'
			: ( class_exists( '\\Requests' ) ? '\\Requests' : '' );
		$wp_http_customised = defined( 'WP_PROXY_HOST' )
			|| ( defined( 'WP_HTTP_BLOCK_EXTERNAL' ) && WP_HTTP_BLOCK_EXTERNAL )
			|| ( function_exists( 'has_filter' ) && ( has_filter( 'pre_http_request' ) || has_filter( 'http_request_args' ) ) );

		$pending = [];
		foreach ( $paths as $key => $path ) {
			if ( self::safe_endpoint( (string) $path ) ) {
				$pending[ $key ] = (string) $path;
			} else {
				$out[ $key ] = [ 'success' => false, 'errors' => [ [ 'message' => 'Invalid Cloudflare API path.' ] ] ];
			}
		}

		if ( $class !== '' && ! $wp_http_customised && count( $pending ) > 1 ) {
			$requests = [];
			foreach ( $pending as $key => $path ) {
				$requests[ $key ] = [
					'url'     => self::CF_BASE . ltrim( $path, '/' ),
					'headers' => $this->auth_headers(),
					'type'    => 'GET',
				];
			}
			$options = [ 'timeout' => 20, 'connect_timeout' => 10 ];
			if ( defined( 'ABSPATH' ) && defined( 'WPINC' ) && is_readable( ABSPATH . WPINC . '/certificates/ca-bundle.crt' ) ) {
				$options['verify'] = ABSPATH . WPINC . '/certificates/ca-bundle.crt';
			}
			try {
				$responses = $class::request_multiple( $requests, $options );
				foreach ( $pending as $key => $path ) {
					$r       = $responses[ $key ] ?? null;
					$decoded = ( is_object( $r ) && ! ( $r instanceof \Throwable ) && isset( $r->body ) ) ? json_decode( (string) $r->body, true ) : null;
					if ( is_array( $decoded ) ) {
						$out[ $key ] = $decoded;
						unset( $pending[ $key ] ); // done; anything left is retried below
					}
				}
			} catch ( \Throwable $e ) {
				// Retry everything below through the WordPress HTTP API.
			}
		}

		// Sequential WordPress HTTP API requests: the normal path when parallel
		// requests are not used, and the retry for any that failed.
		foreach ( $pending as $key => $path ) {
			$out[ $key ] = $this->request( 'GET', $path );
		}

		// Keep the caller's key order.
		$ordered = [];
		foreach ( array_keys( $paths ) as $key ) {
			$ordered[ $key ] = $out[ $key ];
		}
		return $ordered;
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
		// The filter does NOT accept zoneTag - zone is already selected by zones(filter:{zoneTag:...}).
		$has_action = $action_filter !== '';
		$since      = gmdate( 'Y-m-d\TH:i:s\Z', time() - ( $hours * 3600 ) );
		$until      = gmdate( 'Y-m-d\TH:i:s\Z' );

		// Build filter object - datetime range is REQUIRED by the schema
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
			$msg      = $decoded['errors'][0]['message'] ?? 'GraphQL error';
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

	// ── Email Routing - catch-all rule ────────────────────────────────────────

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
