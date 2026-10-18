<?php
declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Page Rules: audit legacy Page Rules and migrate them to modern Rules.
 *
 * Cloudflare has deprecated Page Rules in favour of Single Redirects, Cache
 * Rules, Configuration Rules, Origin Rules and Transform Rules. This module
 * lists every Page Rule across the connected zones, explains the modern
 * replacement for each setting, and migrates the two cases that translate
 * faithfully:
 *
 *   1. Forwarding URL       -> Single Redirect  (phase http_request_dynamic_redirect)
 *   2. Cache Level Cache Everything (optionally with Edge Cache TTL and
 *      Browser Cache TTL), or Cache Level Bypass on its own
 *                           -> Cache Rule       (phase http_request_cache_settings)
 *
 * Safety: a new rule is appended to the phase entry point ruleset with
 * POST /rulesets/{id}/rules, so existing rules are never overwritten. The
 * original Page Rule is only ever disabled (PATCH status), never deleted, and
 * only when the user asks.
 *
 * Sources:
 *   https://developers.cloudflare.com/rules/reference/page-rules-migration/
 *   https://developers.cloudflare.com/api/resources/page_rules/methods/list/
 *   https://developers.cloudflare.com/api/resources/page_rules/methods/edit/
 *   https://developers.cloudflare.com/rules/url-forwarding/single-redirects/settings/
 *   https://developers.cloudflare.com/ruleset-engine/rules-language/functions/ (wildcard_replace)
 *   https://developers.cloudflare.com/ruleset-engine/rulesets-api/add-rule/
 *   https://developers.cloudflare.com/cache/how-to/cache-rules/settings/
 */
final class WPWAF_Page_Rules extends WPWAF_Module {

	/** Marker written into the description of every rule this module creates. */
	public const MARKER = 'WP WAF Manager: migrated from Page Rule ';

	public const PHASE_REDIRECT = 'http_request_dynamic_redirect';
	public const PHASE_CACHE    = 'http_request_cache_settings';

	public const GUIDE_URL = 'https://developers.cloudflare.com/rules/reference/page-rules-migration/';

	private const PERM_READ  = 'Zone > Page Rules > Read';
	private const PERM_EDIT  = 'Zone > Page Rules > Edit';
	private const PERM_REDIR = 'Zone > Single Redirect > Edit';
	private const PERM_CACHE = 'Zone > Cache Rules > Edit';

	public static function init(): void {
		add_action( 'wp_ajax_wpwaf_pr_scan',       [ self::class, 'ajax_scan' ] );
		add_action( 'wp_ajax_wpwaf_pr_migrate',    [ self::class, 'ajax_migrate' ] );
		add_action( 'wp_ajax_wpwaf_pr_set_status', [ self::class, 'ajax_set_status' ] );
	}

	// ── Plan limits ───────────────────────────────────────────────────────────

	/** Normalise a Cloudflare plan name to free / pro / business / enterprise. */
	public static function plan_key( string $plan_name ): string {
		$p = strtolower( $plan_name );
		return match ( true ) {
			str_contains( $p, 'enterprise' ) => 'enterprise',
			str_contains( $p, 'business' )   => 'business',
			str_contains( $p, 'pro' )        => 'pro',
			default                          => 'free',
		};
	}

	/**
	 * Default rule quotas per plan.
	 * Page Rules: https://developers.cloudflare.com/rules/page-rules/
	 * Single Redirects: https://developers.cloudflare.com/rules/url-forwarding/#availability
	 * Cache Rules: https://developers.cloudflare.com/cache/how-to/cache-rules/
	 */
	public static function limits( string $plan_key ): array {
		$t = [
			'free'       => [ 'page_rules' => 3,   'redirect' => 10,  'cache' => 10 ],
			'pro'        => [ 'page_rules' => 20,  'redirect' => 25,  'cache' => 25 ],
			'business'   => [ 'page_rules' => 50,  'redirect' => 50,  'cache' => 50 ],
			'enterprise' => [ 'page_rules' => 125, 'redirect' => 300, 'cache' => 300 ],
		];
		return $t[ $plan_key ] ?? $t['free'];
	}

	// ── Setting to replacement map (Cloudflare migration guide) ───────────────

	/**
	 * Page Rule action id => [ label, plain-words replacement ].
	 * Ids from the Page Rules API; replacements from the migration guide.
	 */
	public static function action_map(): array {
		return [
			'always_use_https'            => [ 'Always Use HTTPS', 'Single Redirect from HTTP to HTTPS, or the Always Use HTTPS switch on Zone Controls' ],
			'automatic_https_rewrites'    => [ 'Automatic HTTPS Rewrites', 'Configuration Rule' ],
			'browser_cache_ttl'           => [ 'Browser Cache TTL', 'Cache Rule (Browser TTL)' ],
			'browser_check'               => [ 'Browser Integrity Check', 'Configuration Rule' ],
			'bypass_cache_on_cookie'      => [ 'Bypass Cache on Cookie', 'Cache Rule (Bypass cache with a cookie filter)' ],
			'cache_by_device_type'        => [ 'Cache By Device Type', 'Cache Rule (cache key by device type)' ],
			'cache_deception_armor'       => [ 'Cache Deception Armor', 'Cache Rule (cache key setting)' ],
			'cache_key_fields'            => [ 'Custom Cache Key', 'Cache Rule (cache key)' ],
			'cache_level'                 => [ 'Cache Level', 'Cache Rule (cache eligibility)' ],
			'cache_on_cookie'             => [ 'Cache on Cookie', 'Cache Rule (eligible for cache with a cookie filter)' ],
			'cache_ttl_by_status'         => [ 'Cache TTL by Status Code', 'Cache Rule (Edge TTL by status code)' ],
			'disable_apps'                => [ 'Disable Apps', 'Configuration Rule' ],
			'disable_performance'         => [ 'Disable Performance', 'Deprecated. Use a Configuration Rule that turns off Polish and Rocket Loader' ],
			'disable_security'            => [ 'Disable Security', 'Deprecated. Use a Configuration Rule (Email Obfuscation, Hotlink Protection) and WAF exceptions' ],
			'disable_zaraz'               => [ 'Disable Zaraz', 'Configuration Rule' ],
			'edge_cache_ttl'              => [ 'Edge Cache TTL', 'Cache Rule (Edge TTL)' ],
			'email_obfuscation'           => [ 'Email Obfuscation', 'Configuration Rule' ],
			'explicit_cache_control'      => [ 'Origin Cache Control', 'Cache Rule' ],
			'forwarding_url'              => [ 'Forwarding URL', 'Single Redirect' ],
			'host_header_override'        => [ 'Host Header Override', 'Origin Rule (Host Header)' ],
			'ip_geolocation'              => [ 'IP Geolocation Header', 'Managed Transform: Add visitor location headers' ],
			'mirage'                      => [ 'Mirage', 'Retired by Cloudflare. No replacement' ],
			'opportunistic_encryption'    => [ 'Opportunistic Encryption', 'Configuration Rule' ],
			'origin_error_page_pass_thru' => [ 'Origin Error Page Pass-thru', 'Cache Rule' ],
			'polish'                      => [ 'Polish', 'Configuration Rule' ],
			'resolve_override'            => [ 'Resolve Override', 'Origin Rule (DNS Record override)' ],
			'respect_strong_etag'         => [ 'Respect Strong ETags', 'Cache Rule' ],
			'response_buffering'          => [ 'Response Buffering', 'Deprecated. No replacement' ],
			'rocket_loader'               => [ 'Rocket Loader', 'Configuration Rule' ],
			'security_level'              => [ 'Security Level', 'Configuration Rule' ],
			'sort_query_string_for_cache' => [ 'Query String Sort', 'Cache Rule (cache key setting)' ],
			'ssl'                         => [ 'SSL', 'Configuration Rule' ],
			'true_client_ip_header'       => [ 'True Client IP Header', 'Managed Transform: Add True-Client-IP header' ],
			'waf'                         => [ 'Web Application Firewall', 'Deprecated. No replacement' ],
		];
	}

	// ── Translation (pure, unit tested) ───────────────────────────────────────

	/** Escape a value for use inside a double-quoted Rules language string. */
	private static function lit( string $s ): string {
		return '"' . str_replace( [ '\\', '"' ], [ '\\\\', '\\"' ], $s ) . '"';
	}

	/**
	 * Parse a Page Rule URL pattern into the parts needed for a filter expression.
	 *
	 * Returns [ 'ok' => true, scheme, host, path, prefix_wildcards, path_wildcards, expression ]
	 * or [ 'ok' => false, 'reason' => string ].
	 */
	public static function parse_pattern( string $pattern ): array {
		$p = trim( $pattern );
		if ( $p === '' ) {
			return [ 'ok' => false, 'reason' => 'The Page Rule has no URL pattern.' ];
		}
		if ( preg_match( '/\s/', $p ) ) {
			return [ 'ok' => false, 'reason' => 'The URL pattern contains spaces.' ];
		}
		if ( str_contains( $p, '\\' ) ) {
			return [ 'ok' => false, 'reason' => 'The URL pattern contains escaped characters, which need a manual review.' ];
		}
		if ( str_contains( $p, '**' ) ) {
			return [ 'ok' => false, 'reason' => 'Two wildcards in a row (**) are not allowed in modern Rules.' ];
		}
		if ( str_contains( $p, '?' ) ) {
			return [ 'ok' => false, 'reason' => 'The URL pattern matches a query string, which needs a manual review.' ];
		}

		$scheme = '';
		if ( preg_match( '#^([a-z*]+)://#i', $p, $m ) ) {
			$scheme = strtolower( $m[1] );
			$p      = substr( $p, strlen( $m[0] ) );
			if ( ! in_array( $scheme, [ 'http', 'https', '*', 'http*' ], true ) ) {
				return [ 'ok' => false, 'reason' => 'The URL pattern uses an unusual scheme, which needs a manual review.' ];
			}
		}

		$slash = strpos( $p, '/' );
		$host  = strtolower( $slash === false ? $p : substr( $p, 0, $slash ) );
		$path  = $slash === false ? '/' : substr( $p, $slash );
		if ( $path === '' ) {
			$path = '/';
		}

		if ( $host === '' || ! preg_match( '/^[a-z0-9.*-]+$/', $host ) ) {
			return [ 'ok' => false, 'reason' => 'The hostname part uses a port or characters that need a manual review.' ];
		}

		$prefix_wildcards = substr_count( $scheme, '*' ) + substr_count( $host, '*' );
		$path_wildcards   = substr_count( $path, '*' );

		$parts = [];
		if ( $scheme === 'http' || $scheme === 'https' ) {
			$parts[] = '(http.request.full_uri wildcard ' . self::lit( $scheme . '://*' ) . ')';
		}
		$parts[] = '(http.host wildcard ' . self::lit( $host ) . ')';
		if ( $path !== '/*' ) {
			$parts[] = '(http.request.uri.path wildcard ' . self::lit( $path ) . ')';
		}

		return [
			'ok'               => true,
			'scheme'           => $scheme,
			'host'             => $host,
			'path'             => $path,
			'prefix_wildcards' => $prefix_wildcards,
			'path_wildcards'   => $path_wildcards,
			'expression'       => implode( ' and ', $parts ),
		];
	}

	/**
	 * Translate a Forwarding URL target into a Single Redirect target_url.
	 * Page Rules reference wildcards as $1, $2 and escape a literal $ as \$.
	 */
	public static function translate_target( array $parsed, string $target ): array {
		$target = trim( $target );
		if ( ! preg_match( '#^https?://#i', $target ) ) {
			return [ 'ok' => false, 'reason' => 'The forwarding target is not a full http:// or https:// URL.' ];
		}
		if ( str_contains( $target, '?' ) ) {
			return [ 'ok' => false, 'reason' => 'The forwarding target contains a query string, so how query strings combine needs a manual review.' ];
		}

		$placeholder = "\x01";
		$t           = str_replace( '\\$', $placeholder, $target );
		if ( str_contains( $t, '\\' ) ) {
			return [ 'ok' => false, 'reason' => 'The forwarding target contains escaped characters, which need a manual review.' ];
		}

		preg_match_all( '/\$(\d+)/', $t, $m );
		$refs = array_map( 'intval', $m[1] );

		if ( ! $refs ) {
			$static = str_replace( $placeholder, '$', $t );
			if ( str_contains( $static, '$' ) ) {
				return [ 'ok' => false, 'reason' => 'The forwarding target contains a $ sign that is not a wildcard reference.' ];
			}
			return [ 'ok' => true, 'target_url' => [ 'value' => $static ], 'display' => $static ];
		}

		if ( count( $refs ) > 8 ) {
			return [ 'ok' => false, 'reason' => 'The target uses more than eight wildcard references, the most a Single Redirect allows.' ];
		}

		$prefix = (int) $parsed['prefix_wildcards'];
		$paths  = (int) $parsed['path_wildcards'];
		foreach ( $refs as $n ) {
			if ( $n < 1 || $n > $prefix + $paths ) {
				return [ 'ok' => false, 'reason' => "The target refers to \${$n}, but the URL pattern does not have that many wildcards." ];
			}
			if ( $n <= $prefix ) {
				return [ 'ok' => false, 'reason' => 'The target reuses part of the hostname or scheme, which needs a manual review.' ];
			}
		}

		// Remaining $ signs that are not $<digits> would be ambiguous.
		if ( preg_match( '/\$(?!\d)/', $t ) ) {
			return [ 'ok' => false, 'reason' => 'The forwarding target contains a $ sign that is not a wildcard reference.' ];
		}

		$replacement = preg_replace_callback(
			'/\$(\d+)/',
			static fn ( array $mm ): string => '${' . ( (int) $mm[1] - $prefix ) . '}',
			$t
		);
		// A literal $ in a wildcard_replace() replacement is written as $$.
		$replacement = str_replace( $placeholder, '$$', (string) $replacement );

		$expr = 'wildcard_replace(http.request.uri.path, ' . self::lit( (string) $parsed['path'] ) . ', ' . self::lit( $replacement ) . ')';
		return [ 'ok' => true, 'target_url' => [ 'expression' => $expr ], 'display' => $replacement ];
	}

	/**
	 * Analyse one Page Rule (as returned by the API).
	 *
	 * Returns:
	 *   kind     'redirect' | 'cache' | ''        (what it migrates to automatically)
	 *   auto     bool                             (can be migrated automatically)
	 *   reason   string                           (why not, when auto is false)
	 *   actions  [ [id, label, value, replacement] ]
	 *   rule     array|null                       (the rule body to add to the phase)
	 *   phase    string
	 *   preview  [ expression, target, status_code, settings ]
	 */
	public static function analyse( array $pr ): array {
		$pattern = '';
		foreach ( (array) ( $pr['targets'] ?? [] ) as $t ) {
			if ( ( $t['target'] ?? '' ) === 'url' ) {
				$pattern = (string) ( $t['constraint']['value'] ?? '' );
				break;
			}
		}

		$map     = self::action_map();
		$actions = [];
		$by_id   = [];
		foreach ( (array) ( $pr['actions'] ?? [] ) as $a ) {
			$id           = (string) ( $a['id'] ?? '' );
			$by_id[ $id ] = $a['value'] ?? null;
			$actions[]    = [
				'id'          => $id,
				'label'       => $map[ $id ][0] ?? $id,
				'value'       => self::value_text( $id, $a['value'] ?? null ),
				'replacement' => $map[ $id ][1] ?? 'See the Cloudflare migration guide',
			];
		}

		$id  = (string) ( $pr['id'] ?? '' );
		$out = [
			'id'       => $id,
			'pattern'  => $pattern,
			'status'   => (string) ( $pr['status'] ?? '' ),
			'priority' => (int) ( $pr['priority'] ?? 0 ),
			'actions'  => $actions,
			'kind'     => '',
			'auto'     => false,
			'reason'   => '',
			'phase'    => '',
			'rule'     => null,
			'preview'  => null,
		];

		$ids = array_keys( $by_id );
		$fail = static function ( string $reason ) use ( &$out ): array {
			$out['reason'] = $reason;
			return $out;
		};

		if ( $out['status'] !== 'active' ) {
			return $fail( 'This Page Rule is turned off, so there is nothing to migrate. Recreate it manually if you still need it.' );
		}

		// ── Forwarding URL ───────────────────────────────────────────────────
		if ( isset( $by_id['forwarding_url'] ) ) {
			if ( count( $ids ) !== 1 ) {
				return $fail( 'Forwarding URL is combined with other settings, so it needs a manual migration.' );
			}
			$fwd    = (array) $by_id['forwarding_url'];
			$status = (int) ( $fwd['status_code'] ?? 0 );
			if ( ! in_array( $status, [ 301, 302 ], true ) ) {
				return $fail( 'The forwarding status code is missing or not 301 or 302.' );
			}
			$parsed = self::parse_pattern( $pattern );
			if ( ! $parsed['ok'] ) {
				return $fail( $parsed['reason'] );
			}
			$tgt = self::translate_target( $parsed, (string) ( $fwd['url'] ?? '' ) );
			if ( ! $tgt['ok'] ) {
				return $fail( $tgt['reason'] );
			}
			$out['kind']    = 'redirect';
			$out['auto']    = true;
			$out['phase']   = self::PHASE_REDIRECT;
			$out['rule']    = [
				'action'            => 'redirect',
				'expression'        => $parsed['expression'],
				'description'       => self::MARKER . $id,
				'enabled'           => true,
				'action_parameters' => [
					'from_value' => [
						'target_url'            => $tgt['target_url'],
						'status_code'           => $status,
						'preserve_query_string' => true,
					],
				],
			];
			$out['preview'] = [
				'expression'  => $parsed['expression'],
				'target'      => $tgt['target_url']['value'] ?? $tgt['target_url']['expression'],
				'target_text' => $tgt['display'],
				'status_code' => $status,
				'settings'    => [],
			];
			return $out;
		}

		// ── Cache Level ──────────────────────────────────────────────────────
		$cache_ids = [ 'cache_level', 'edge_cache_ttl', 'browser_cache_ttl' ];
		if ( isset( $by_id['cache_level'] ) && ! array_diff( $ids, $cache_ids ) ) {
			$level = (string) $by_id['cache_level'];
			$parsed = self::parse_pattern( $pattern );
			if ( ! $parsed['ok'] ) {
				return $fail( $parsed['reason'] );
			}

			$params   = [];
			$settings = [];
			if ( $level === 'bypass' ) {
				if ( count( $ids ) !== 1 ) {
					return $fail( 'Cache Level Bypass is combined with cache TTL settings, so it needs a manual migration.' );
				}
				$params     = [ 'cache' => false ];
				$settings[] = 'Cache eligibility: Bypass cache';
			} elseif ( $level === 'cache_everything' ) {
				$params     = [ 'cache' => true ];
				$settings[] = 'Cache eligibility: Eligible for cache (caches everything, like Cache Everything)';
				if ( isset( $by_id['edge_cache_ttl'] ) ) {
					$edge = (int) $by_id['edge_cache_ttl'];
					if ( $edge < 1 ) {
						return $fail( 'The Edge Cache TTL value is not valid.' );
					}
					$params['edge_ttl'] = [ 'mode' => 'override_origin', 'default' => $edge ];
					$settings[]         = 'Edge TTL: ignore cache-control header, use ' . self::ttl_text( $edge );
				}
				if ( isset( $by_id['browser_cache_ttl'] ) ) {
					$browser = (int) $by_id['browser_cache_ttl'];
					if ( $browser === 0 ) {
						$params['browser_ttl'] = [ 'mode' => 'respect_origin' ];
						$settings[]            = 'Browser TTL: respect origin headers';
					} else {
						$params['browser_ttl'] = [ 'mode' => 'override_origin', 'default' => $browser ];
						$settings[]            = 'Browser TTL: override origin, use ' . self::ttl_text( $browser );
					}
				}
			} else {
				return $fail( 'This Cache Level value maps to cache key settings in Cache Rules, so it needs a manual migration.' );
			}

			$out['kind']    = 'cache';
			$out['auto']    = true;
			$out['phase']   = self::PHASE_CACHE;
			$out['rule']    = [
				'action'            => 'set_cache_settings',
				'expression'        => $parsed['expression'],
				'description'       => self::MARKER . $id,
				'enabled'           => true,
				'action_parameters' => $params,
			];
			$out['preview'] = [
				'expression'  => $parsed['expression'],
				'target'      => '',
				'target_text' => '',
				'status_code' => 0,
				'settings'    => $settings,
			];
			return $out;
		}

		if ( ( isset( $by_id['edge_cache_ttl'] ) || isset( $by_id['browser_cache_ttl'] ) ) && ! array_diff( $ids, $cache_ids ) ) {
			return $fail( 'In Cache Rules a TTL only applies to requests that are eligible for cache, which would also cache pages that are not cached today. Migrate this one manually.' );
		}

		return $fail( 'One or more of these settings has no automatic migration in this plugin.' );
	}

	private static function ttl_text( int $s ): string {
		return match ( true ) {
			$s % 86400 === 0 => ( $s / 86400 ) . ' day' . ( $s === 86400 ? '' : 's' ),
			$s % 3600 === 0  => ( $s / 3600 ) . ' hour' . ( $s === 3600 ? '' : 's' ),
			$s % 60 === 0    => ( $s / 60 ) . ' minute' . ( $s === 60 ? '' : 's' ),
			default          => $s . ' seconds',
		};
	}

	private static function value_text( string $id, mixed $v ): string {
		if ( $id === 'forwarding_url' && is_array( $v ) ) {
			return (int) ( $v['status_code'] ?? 0 ) . ' to ' . (string) ( $v['url'] ?? '' );
		}
		if ( in_array( $id, [ 'edge_cache_ttl', 'browser_cache_ttl' ], true ) && is_numeric( $v ) ) {
			return (int) $v === 0 ? 'Respect existing headers' : self::ttl_text( (int) $v );
		}
		if ( is_scalar( $v ) ) {
			return str_replace( '_', ' ', (string) $v );
		}
		return is_null( $v ) ? '' : 'Custom';
	}

	// ── Cloudflare helpers ────────────────────────────────────────────────────

	/** Find the zone entry point ruleset ids for the two phases we write to. */
	private static function entrypoints( string $zone_id ): array {
		$res = static::api()->call( 'GET', "zones/{$zone_id}/rulesets" );
		if ( ! $res['success'] ) {
			return [ 'success' => false, 'res' => $res ];
		}
		$found = [ self::PHASE_REDIRECT => '', self::PHASE_CACHE => '' ];
		foreach ( (array) $res['result'] as $rs ) {
			$phase = (string) ( $rs['phase'] ?? '' );
			if ( ( $rs['kind'] ?? '' ) === 'zone' && array_key_exists( $phase, $found ) ) {
				$found[ $phase ] = (string) ( $rs['id'] ?? '' );
			}
		}
		return [ 'success' => true, 'ids' => $found ];
	}

	/** Rules currently in a ruleset (empty when the ruleset does not exist yet). */
	private static function ruleset_rules( string $zone_id, string $ruleset_id ): array {
		if ( $ruleset_id === '' ) {
			return [ 'success' => true, 'rules' => [] ];
		}
		$res = static::api()->call( 'GET', "zones/{$zone_id}/rulesets/{$ruleset_id}" );
		if ( ! $res['success'] ) {
			return [ 'success' => false, 'res' => $res ];
		}
		return [ 'success' => true, 'rules' => (array) ( $res['result']['rules'] ?? [] ) ];
	}

	/** Page Rule id => [ phase, rule id ] for rules this module created earlier. */
	private static function migrated_from( array $rules, string $phase ): array {
		$out = [];
		foreach ( $rules as $r ) {
			$d = (string) ( $r['description'] ?? '' );
			if ( str_starts_with( $d, self::MARKER ) ) {
				$out[ substr( $d, strlen( self::MARKER ) ) ] = [ 'phase' => $phase, 'rule_id' => (string) ( $r['id'] ?? '' ) ];
			}
		}
		return $out;
	}

	private static function zone_plan( string $zone_id ): string {
		$res = static::api()->call( 'GET', "zones/{$zone_id}" );
		return self::plan_key( (string) ( $res['result']['plan']['name'] ?? '' ) );
	}

	// ── AJAX ──────────────────────────────────────────────────────────────────

	/** List and analyse one zone's Page Rules. */
	public static function ajax_scan(): void {
		static::guard();
		$zone_id = static::post_id( 'zone_id' );
		$plan    = self::plan_key( static::post( 'plan' ) );

		$res = static::api()->call( 'GET', "zones/{$zone_id}/pagerules" );
		if ( ! $res['success'] ) {
			static::respond( $res, self::PERM_READ );
		}

		$rules = [];
		foreach ( (array) $res['result'] as $pr ) {
			if ( is_array( $pr ) ) {
				$a = self::analyse( $pr );
				unset( $a['rule'] );
				$rules[] = $a;
			}
		}
		usort( $rules, static fn ( array $a, array $b ): int => $b['priority'] <=> $a['priority'] );

		$counts   = [ 'redirect' => null, 'cache' => null ];
		$migrated = [];
		$warning  = '';
		if ( $rules ) {
			$eps = self::entrypoints( $zone_id );
			if ( $eps['success'] ) {
				foreach ( [ 'redirect' => self::PHASE_REDIRECT, 'cache' => self::PHASE_CACHE ] as $key => $phase ) {
					$rr = self::ruleset_rules( $zone_id, $eps['ids'][ $phase ] );
					if ( $rr['success'] ) {
						$counts[ $key ] = count( $rr['rules'] );
						$migrated      += self::migrated_from( $rr['rules'], $phase );
					}
				}
			} else {
				$warning = 'Could not read this zone\'s modern Rules, so already migrated rules may not be marked. (' . ( $eps['res']['message'] ?? '' ) . ')';
			}
		}

		wp_send_json_success( [
			'rules'    => $rules,
			'limits'   => self::limits( $plan ),
			'counts'   => $counts,
			'migrated' => (object) $migrated,
			'warning'  => $warning,
		] );
	}

	/**
	 * Migrate one Page Rule. The rule body is rebuilt on the server from the
	 * live Page Rule; nothing the browser sends ends up in the expression.
	 */
	public static function ajax_migrate(): void {
		static::guard();
		$zone_id = static::post_id( 'zone_id' );
		$pr_id   = static::post_id( 'rule_id' );
		$keep_qs = ! isset( $_POST['preserve_query_string'] ) || static::post_bool( 'preserve_query_string' );

		$list = static::api()->call( 'GET', "zones/{$zone_id}/pagerules" );
		if ( ! $list['success'] ) {
			static::respond( $list, self::PERM_READ );
		}
		$pr = null;
		foreach ( (array) $list['result'] as $r ) {
			if ( ( $r['id'] ?? '' ) === $pr_id ) {
				$pr = $r;
				break;
			}
		}
		if ( ! $pr ) {
			wp_send_json_error( [ 'message' => 'That Page Rule no longer exists. Scan the zone again.' ] );
		}

		$a = self::analyse( $pr );
		if ( ! $a['auto'] || ! $a['rule'] ) {
			wp_send_json_error( [ 'message' => $a['reason'] ?: 'This Page Rule cannot be migrated automatically.' ] );
		}
		if ( $a['kind'] === 'redirect' ) {
			$a['rule']['action_parameters']['from_value']['preserve_query_string'] = $keep_qs;
		}
		$perm  = $a['kind'] === 'redirect' ? self::PERM_REDIR : self::PERM_CACHE;
		$phase = $a['phase'];

		$eps = self::entrypoints( $zone_id );
		if ( ! $eps['success'] ) {
			static::respond( $eps['res'], $perm );
		}
		$ruleset_id = $eps['ids'][ $phase ];
		$rr         = self::ruleset_rules( $zone_id, $ruleset_id );
		if ( ! $rr['success'] ) {
			static::respond( $rr['res'], $perm );
		}

		$already = self::migrated_from( $rr['rules'], $phase );
		if ( isset( $already[ $pr_id ] ) ) {
			wp_send_json_error( [ 'message' => 'This Page Rule was already migrated. The new rule is in place.', 'already' => true ] );
		}

		$limit = self::limits( self::zone_plan( $zone_id ) )[ $a['kind'] ];
		if ( count( $rr['rules'] ) >= $limit ) {
			$label = $a['kind'] === 'redirect' ? 'Single Redirects' : 'Cache Rules';
			wp_send_json_error( [ 'message' => "This zone already has {$limit} {$label}, the most its plan allows. Remove one in Cloudflare first." ] );
		}

		if ( $ruleset_id !== '' ) {
			$res = static::api()->call( 'POST', "zones/{$zone_id}/rulesets/{$ruleset_id}/rules", $a['rule'] );
		} else {
			$res = static::api()->call( 'POST', "zones/{$zone_id}/rulesets", [
				'name'  => $a['kind'] === 'redirect' ? 'Single Redirects' : 'Cache Rules',
				'kind'  => 'zone',
				'phase' => $phase,
				'rules' => [ $a['rule'] ],
			] );
		}
		if ( ! $res['success'] ) {
			static::respond( $res, $perm );
		}

		$new_id = '';
		foreach ( (array) ( $res['result']['rules'] ?? [] ) as $r ) {
			if ( ( $r['description'] ?? '' ) === self::MARKER . $pr_id ) {
				$new_id = (string) ( $r['id'] ?? '' );
			}
		}

		wp_send_json_success( [
			'phase'   => $phase,
			'kind'    => $a['kind'],
			'rule_id' => $new_id,
		] );
	}

	/** Turn a Page Rule off or back on. Never deletes. */
	public static function ajax_set_status(): void {
		static::guard();
		$zone_id = static::post_id( 'zone_id' );
		$pr_id   = static::post_id( 'rule_id' );
		$status  = static::post( 'status' );
		if ( ! in_array( $status, [ 'active', 'disabled' ], true ) ) {
			wp_send_json_error( [ 'message' => 'Status must be active or disabled.' ] );
		}
		$res = static::api()->call( 'PATCH', "zones/{$zone_id}/pagerules/{$pr_id}", [ 'status' => $status ] );
		static::respond( $res, self::PERM_EDIT, [ 'status' => $status ] );
	}
}
