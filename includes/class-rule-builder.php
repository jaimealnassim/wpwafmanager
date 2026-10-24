<?php
declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Builds the five Cloudflare WAF rule expressions.
 *
 * Matches the wafrules.com ruleset of October 21, 2026 (WPWAF_Service_IPs::RULES_DATE).
 * Plugin extras on top of that ruleset: custom user agents and custom
 * expressions in Rule 1, the optional wp-cron.php block in Rule 2, and a
 * multi-country picker in Rule 4.
 */
class WPWAF_Rule_Builder {

	/** Cloudflare's limit for one rule expression. */
	public const EXPRESSION_LIMIT = 4096;

	/** Prefix that marks the rules this plugin owns in a zone. */
	public const NAME_PREFIX = '[CF WAF] ';

	/** Rule names, in deploy order. */
	public const NAMES = [
		'rule1' => '[CF WAF] Allow Good Bots',
		'rule2' => '[CF WAF] Block Aggressive Crawlers & WordPress Paths',
		'rule3' => '[CF WAF] Block or Challenge Web Hosts / TOR & Exploit URIs',
		'rule4' => '[CF WAF] Challenge Large Providers / Country',
		'rule5' => '[CF WAF] Challenge VPN Connections & wp-login',
	];

	/** Names used by earlier versions, so existing rules are updated in place. */
	private const LEGACY_NAMES = [
		'[CF WAF] Block Aggressive Crawlers & WP Paths' => 'rule2',
		'[CF WAF] Block Web Hosts & TOR'                => 'rule3',
		'[CF WAF] Challenge VPN & wp-login'             => 'rule5',
	];

	/** True for a rule this plugin created (current or earlier name). */
	public static function is_plugin_rule( string $description ): bool {
		return str_starts_with( $description, self::NAME_PREFIX );
	}

	/** Which of the five rules a description belongs to, or null. */
	public static function slot_for( string $description ): ?string {
		$slot = array_search( $description, self::NAMES, true );
		if ( $slot !== false ) return (string) $slot;
		return self::LEGACY_NAMES[ $description ] ?? null;
	}

	/** Every Cloudflare verified bot category, in display order. */
	public const CATEGORIES = [
		'Accessibility', 'Academic Research', 'Advertising & Marketing', 'Aggregator',
		'AI Assistant', 'AI Crawler', 'AI Search', 'Archiver', 'Feed Fetcher',
		'Monitoring & Analytics', 'Page Preview', 'Search Engine Crawler',
		'Search Engine Optimization', 'Security', 'Social Media Marketing', 'Webhooks',
	];

	/** Categories selected by default (used when "Allow every verified bot" is off). */
	public const DEFAULT_CATEGORIES = [
		'Accessibility', 'Academic Research', 'Advertising & Marketing', 'Feed Fetcher',
		'Monitoring & Analytics', 'Page Preview', 'Search Engine Crawler',
		'Search Engine Optimization', 'Security', 'Webhooks',
	];

	/** Verified categories that Rule 4 never challenges. */
	private const RULE4_SAFE = '"Search Engine Crawler" "Search Engine Optimization" "Monitoring & Analytics" "Advertising & Marketing" "Page Preview" "Academic Research" "Security" "Accessibility" "Webhooks" "Feed Fetcher"';

	private const LETS_ENCRYPT  = '(starts_with(http.request.uri.path, "/.well-known/acme-challenge/"))';
	private const WPU_MONITORS  = '(ip.src.asnum eq 14061 and http.user_agent contains "WPUmbrella+" and http.request.method in {"GET" "HEAD"} and not http.request.uri.path contains "." and http.request.uri.query eq "")';
	private const FLYINGPRESS   = '(http.user_agent contains "FlyingPress (+https://flyingpress.com)" and http.request.method eq "GET")';
	private const APPLE_PREVIEW = '(http.user_agent contains "facebookexternalhit" and http.request.method eq "GET")';

	/**
	 * Rule 1 service toggles from before October 2026 that matched on user agent.
	 * key => [ user agents, verified category that now covers it (null = none) ].
	 */
	private const LEGACY_UA_TOGGLES = [
		'allow_backupbuddy'    => [ [ 'BackupBuddy' ], null ],
		'allow_blogvault'      => [ [ 'BlogVault' ], 'Monitoring & Analytics' ],
		'allow_updraftplus'    => [ [ 'UpdraftPlus' ], null ],
		'allow_gtmetrix'       => [ [ 'GTmetrix' ], 'Monitoring & Analytics' ],
		'allow_pingdom'        => [ [ 'Pingdom' ], 'Monitoring & Analytics' ],
		'allow_statuscake'     => [ [ 'StatusCake' ], 'Monitoring & Analytics' ],
		'allow_uptimerobot'    => [ [ 'UptimeRobot' ], 'Monitoring & Analytics' ],
		'allow_usercentrics'   => [ [ 'Usercentricsbot' ], 'Monitoring & Analytics' ],
		'allow_cf_image'       => [ [ 'Cloudflare-Image-Resizing' ], null ],
		'allow_tinypng'        => [ [ 'TinyPNG' ], null ],
		'allow_ahrefs'         => [ [ 'AhrefsBot' ], 'Search Engine Optimization' ],
		'allow_ahrefs_audit'   => [ [ 'Ahrefs Site Audit' ], 'Search Engine Optimization' ],
		'allow_mj12'           => [ [ 'MJ12bot' ], 'Search Engine Crawler' ],
		'allow_rogerbot'       => [ [ 'rogerbot' ], 'Search Engine Optimization' ],
		'allow_screamingfrog'  => [ [ 'Screaming Frog SEO Spider' ], null ],
		'allow_siteauditbot'   => [ [ 'SiteAuditBot' ], 'Search Engine Optimization' ],
		'allow_semrush_ocob'   => [ [ 'SemrushBot-OCOB' ], 'Search Engine Optimization' ],
		'allow_sitelock'       => [ [ 'SiteLockSpider' ], 'Security' ],
		'allow_sucuri'         => [ [ 'SucuriScan' ], 'Security' ],
		'allow_virustotal'     => [ [ 'virustotal' ], 'Security' ],
		'allow_wordfence'      => [ [ 'Wordfence' ], 'Security' ],
		'allow_facebook'       => [ [ 'facebookexternalhit' ], 'Page Preview' ],
		'allow_linkedin'       => [ [ 'LinkedInBot' ], 'Page Preview' ],
		'allow_twitter'        => [ [ 'Twitterbot' ], 'Page Preview' ],
		'allow_jetpack'        => [ [ 'Jetpack' ], 'Webhooks' ],
		'allow_mainwp'         => [ [ 'MainWP' ], 'Webhooks' ],
		'allow_godaddy_uptime' => [ [ 'GoDaddy Uptime Monitor' ], null ],
	];

	/** Optional category switches from before October 2026. */
	private const LEGACY_CATEGORY_TOGGLES = [
		'allow_aggregator'             => 'Aggregator',
		'allow_ai_assistant'           => 'AI Assistant',
		'allow_ai_crawler'             => 'AI Crawler',
		'allow_ai_search'              => 'AI Search',
		'allow_archiver'               => 'Archiver',
		'allow_social_media_marketing' => 'Social Media Marketing',
	];

	public static function default_settings(): array {
		return [
			'rule1' => [
				'enabled'                      => true,
				'include_client_bot'           => true,
				'verified_categories'          => self::DEFAULT_CATEGORIES,
				// Services with a published IP list (all off by default).
				'allow_betterstack'            => false,
				'allow_easyio_cdn'             => false,
				'allow_ewww'                   => false,
				'allow_imagify'                => false,
				'allow_shortpixel'             => false,
				'allow_semrush'                => false,
				'allow_patchstack'             => false,
				'allow_managewp'               => false,
				'allow_wpumbrella'             => false,
				'allow_wpumbrella_do_monitors' => true,
				'allow_gosweetspot'            => false,
				// Narrow expression matches.
				'allow_flyingpress'            => false,
				'allow_apple_messages'         => true,
				'allow_letsencrypt'            => true,
				// Your server IPs and plugin extras.
				'allow_ips'                    => [],
				'allow_user_agents'            => [],
				'custom_expressions'           => [],
			],
			'rule2' => [
				'enabled'               => true,
				// Verified bots: Rule 1 skips them unless their category is turned off there.
				'block_yandex'          => false,
				'block_sogou'           => false,
				'block_semrush'         => false,
				'block_ahrefs'          => false,
				'block_baidu'           => false,
				'block_neevabot'        => true,
				'block_python'          => true,
				'block_crawl'           => true,
				'block_bot'             => true,
				'block_spider'          => true,
				'block_nikto'           => true,
				'block_sqlmap'          => true,
				'block_masscan'         => true,
				'block_nmap'            => true,
				'block_xmlrpc'          => true,
				'block_wpconfig'        => false,
				// wp-json is needed by the block editor, WooCommerce and mobile apps.
				'block_wpjson'          => false,
				'block_wpinstall'       => false,
				'block_wlwmanifest'     => true,
				'block_readme'          => true,
				'block_license'         => true,
				'block_sensitive_paths' => true,
				// Plugin extra, off by default: breaks sites whose server cron calls wp-cron.php over HTTP.
				'block_wpcron'          => false,
			],
			'rule3' => [
				'enabled'               => true,
				'block_hosts'           => true,
				'block_do'              => true,
				'block_linode'          => true,
				'block_vultr'           => true,
				'block_hetzner'         => true,
				'block_ovh'             => true,
				'block_contabo'         => true,
				'block_scaleway'        => true,
				'block_dreamhost'       => true,
				'block_m247'            => true,
				'block_leaseweb'        => true,
				'block_godaddy'         => true,
				'block_alibaba'         => true,
				'block_hostroyale'      => true,
				'block_cloudvider'      => true,
				'block_tor'             => true,
				'action'                => 'block',
				// Exploit URI patterns, matched from any IP.
				'block_lfi_traversal'   => true,
				'block_legacy_paths'    => true,
				'block_foreign_cms'     => true,
				'block_time_delay_sqli' => true,
				'block_union_sqli'      => true,
				'block_reflected_xss'   => true,
			],
			'rule4' => [
				'enabled'           => true,
				'challenge_aws'     => true,
				'challenge_gcp'     => true,
				'challenge_azure'   => true,
				'challenge_country' => false,
				'countries'         => [ 'US' ],
			],
			'rule5' => [
				'enabled'                 => true,
				'challenge_all_vpn'       => true,
				'challenge_nordvpn'       => true,
				'challenge_expressvpn'    => true,
				'challenge_purevpn'       => true,
				'challenge_surfshark'     => true,
				'challenge_ipvanish'      => true,
				'challenge_quadranet'     => true,
				'challenge_ovhfr'         => true,
				'challenge_internetutils' => true,
				'challenge_mullvad'       => true,
				'challenge_privlayer'     => true,
				'challenge_wplogin'       => true,
			],
		];
	}

	// ── Migration ─────────────────────────────────────────────────────────────

	/**
	 * Converts settings saved before the October 21, 2026 ruleset to the new
	 * shape. Safe to run on settings that are already new.
	 *
	 * Old Rule 1 service switches matched on user agent. Services that a
	 * selected verified bot category now covers need nothing. Services that
	 * publish no IPs keep working by moving their user agent into
	 * "Custom User Agents to Allow", so no site loses access on update.
	 */
	public static function migrate( array $s ): array {
		$r1 = isset( $s['rule1'] ) && is_array( $s['rule1'] ) ? $s['rule1'] : null;
		if ( $r1 !== null && ! array_key_exists( 'include_client_bot', $r1 ) ) {
			$cats = isset( $r1['verified_categories'] ) && is_array( $r1['verified_categories'] ) ? array_values( $r1['verified_categories'] ) : [];
			// Before this ruleset, any selected category also added (cf.client.bot).
			$r1['include_client_bot'] = ! empty( $cats );
			foreach ( self::LEGACY_CATEGORY_TOGGLES as $key => $cat ) {
				if ( ! empty( $r1[ $key ] ) && ! in_array( $cat, $cats, true ) ) $cats[] = $cat;
				unset( $r1[ $key ] );
			}
			$r1['verified_categories'] = $cats;

			if ( array_key_exists( 'allow_exactdn', $r1 ) ) {
				$r1['allow_easyio_cdn'] = ! empty( $r1['allow_exactdn'] );
				unset( $r1['allow_exactdn'] );
			}

			$uas = isset( $r1['allow_user_agents'] ) && is_array( $r1['allow_user_agents'] ) ? array_values( $r1['allow_user_agents'] ) : [];
			foreach ( self::LEGACY_UA_TOGGLES as $key => [ $agents, $category ] ) {
				if ( ! empty( $r1[ $key ] ) ) {
					// Only verified services are covered; cf.client.bot never covers the rest.
					$covered = $category !== null && ( $r1['include_client_bot'] || in_array( $category, $r1['verified_categories'], true ) );
					if ( ! $covered ) {
						foreach ( $agents as $ua ) {
							if ( ! in_array( $ua, $uas, true ) ) $uas[] = $ua;
						}
					}
				}
				unset( $r1[ $key ] );
			}
			$r1['allow_user_agents'] = $uas;
			$s['rule1'] = $r1;
		}

		$r2 = isset( $s['rule2'] ) && is_array( $s['rule2'] ) ? $s['rule2'] : null;
		$r3 = isset( $s['rule3'] ) && is_array( $s['rule3'] ) ? $s['rule3'] : [];
		if ( $r2 !== null ) {
			// Time-delay SQLi and encoded traversal moved from Rule 2 to Rule 3.
			if ( array_key_exists( 'block_sqli_sleep', $r2 ) ) {
				if ( ! array_key_exists( 'block_time_delay_sqli', $r3 ) ) $r3['block_time_delay_sqli'] = (bool) $r2['block_sqli_sleep'];
				unset( $r2['block_sqli_sleep'] );
			}
			if ( array_key_exists( 'block_path_traversal', $r2 ) ) {
				if ( ! array_key_exists( 'block_lfi_traversal', $r3 ) ) $r3['block_lfi_traversal'] = (bool) $r2['block_path_traversal'];
				unset( $r2['block_path_traversal'] );
			}
			$s['rule2'] = $r2;
			if ( $r3 ) $s['rule3'] = $r3;
		}

		return $s;
	}

	/**
	 * One-time update of stored settings and every Rule Profile to the October
	 * 2026 ruleset. Records the user agents moved into "Custom User Agents to
	 * Allow" so the WAF Rules screen can say so once.
	 */
	public static function migrate_stored(): void {
		$moved = [];
		$track = static function ( array $before, array $after ) use ( &$moved ): void {
			$old = (array) ( $before['rule1']['allow_user_agents'] ?? [] );
			foreach ( (array) ( $after['rule1']['allow_user_agents'] ?? [] ) as $ua ) {
				if ( ! in_array( $ua, $old, true ) && ! in_array( $ua, $moved, true ) ) $moved[] = $ua;
			}
		};

		$global = get_option( 'wpwaf_rule_settings' );
		if ( is_array( $global ) && self::needs_migration( $global ) ) {
			$new = self::sanitize_settings( $global );
			$track( $global, $new );
			update_option( 'wpwaf_rule_settings', $new, false );
		}

		$profiles = get_option( 'wpwaf_profiles' );
		if ( is_array( $profiles ) ) {
			$changed = false;
			foreach ( $profiles as $id => $profile ) {
				$settings = $profile['settings'] ?? null;
				if ( is_array( $settings ) && self::needs_migration( $settings ) ) {
					$new = self::sanitize_settings( $settings );
					$track( $settings, $new );
					$profiles[ $id ]['settings'] = $new;
					$changed = true;
				}
			}
			if ( $changed ) update_option( 'wpwaf_profiles', $profiles, false );
		}

		if ( $moved ) update_option( 'wpwaf_rules_moved_agents', $moved, false );
		update_option( 'wpwaf_rules_version', WPWAF_Service_IPs::RULES_DATE, false );
	}

	/**
	 * True when settings still use the shape from before the October 2026 ruleset.
	 */
	public static function needs_migration( array $s ): bool {
		if ( isset( $s['rule1'] ) && is_array( $s['rule1'] ) && ! array_key_exists( 'include_client_bot', $s['rule1'] ) ) return true;
		if ( isset( $s['rule2'] ) && is_array( $s['rule2'] ) && ( array_key_exists( 'block_sqli_sleep', $s['rule2'] ) || array_key_exists( 'block_path_traversal', $s['rule2'] ) ) ) return true;
		return false;
	}

	// ── Sanitizing ────────────────────────────────────────────────────────────

	/**
	 * Sanitize an incoming settings array from POST/JSON before storing.
	 * Ensures only known keys with correct types are persisted.
	 */
	public static function sanitize_settings( array $raw ): array {
		$raw      = self::migrate( $raw );
		$defaults = self::default_settings();
		$out      = [];

		foreach ( $defaults as $rule => $rule_defaults ) {
			$rule_raw = $raw[ $rule ] ?? [];
			if ( ! is_array( $rule_raw ) ) $rule_raw = [];
			$out[ $rule ] = [];
			foreach ( $rule_defaults as $key => $default ) {
				$val = $rule_raw[ $key ] ?? $default;
				$out[ $rule ][ $key ] = match ( true ) {
					is_bool( $default )   => (bool) $val,
					is_int( $default )    => (int) $val,
					is_float( $default )  => (float) $val,
					is_array( $default )  => is_array( $val )
						? array_values( array_map( static fn( $v ) => sanitize_text_field( (string) $v ), $val ) )
						: $default,
					default               => sanitize_text_field( (string) $val ),
				};
			}
		}

		$out['rule1']['verified_categories'] = array_values( array_intersect( self::CATEGORIES, $out['rule1']['verified_categories'] ) );
		$out['rule1']['allow_ips']           = self::normalize_ips( $out['rule1']['allow_ips'] )['accepted'];

		$out['rule1']['allow_user_agents'] = array_values( array_filter(
			array_map( static fn( string $ua ) => substr( trim( $ua ), 0, 200 ), $out['rule1']['allow_user_agents'] ),
			static fn( string $ua ) => $ua !== ''
		) );

		$out['rule1']['custom_expressions'] = array_values( array_filter(
			array_map( static fn( $e ) => trim( wp_unslash( (string) $e ) ), $out['rule1']['custom_expressions'] ),
			static fn( $e ) => $e !== ''
		) );

		$out['rule3']['action'] = $out['rule3']['action'] === 'managed_challenge' ? 'managed_challenge' : 'block';

		$codes = [];
		foreach ( $out['rule4']['countries'] as $c ) {
			$c = strtoupper( trim( $c ) );
			if ( preg_match( '/^[A-Z]{2}$/', $c ) && ! in_array( $c, $codes, true ) ) $codes[] = $c;
		}
		$out['rule4']['countries'] = $codes;

		return $out;
	}

	// ── Building ──────────────────────────────────────────────────────────────

	public static function build_rules( array $settings ): array {
		$rules = [];

		if ( ! empty( $settings['rule1']['enabled'] ) ) {
			$rules[] = self::build_rule1( $settings['rule1'] );
		}
		if ( ! empty( $settings['rule2']['enabled'] ) ) {
			$expr = self::build_rule2_expr( $settings['rule2'] );
			if ( $expr !== '' ) {
				$rules[] = [ 'description' => self::NAMES['rule2'], 'expression' => $expr, 'action' => 'block', 'enabled' => true ];
			}
		}
		if ( ! empty( $settings['rule3']['enabled'] ) ) {
			$expr = self::build_rule3_expr( $settings['rule3'] );
			if ( $expr !== '' ) {
				$action  = ( $settings['rule3']['action'] ?? 'block' ) === 'managed_challenge' ? 'managed_challenge' : 'block';
				$rules[] = [ 'description' => self::NAMES['rule3'], 'expression' => $expr, 'action' => $action, 'enabled' => true ];
			}
		}
		if ( ! empty( $settings['rule4']['enabled'] ) ) {
			$expr = self::build_rule4_expr( $settings['rule4'] );
			if ( $expr !== '' ) {
				$rules[] = [ 'description' => self::NAMES['rule4'], 'expression' => $expr, 'action' => 'managed_challenge', 'enabled' => true ];
			}
		}
		if ( ! empty( $settings['rule5']['enabled'] ) ) {
			$expr = self::build_rule5_expr( $settings['rule5'] );
			if ( $expr !== '' ) {
				$rules[] = [ 'description' => self::NAMES['rule5'], 'expression' => $expr, 'action' => 'managed_challenge', 'enabled' => true ];
			}
		}

		return $rules;
	}

	/**
	 * Returns a message for the first rule whose expression is over Cloudflare's
	 * limit, or null when every rule fits.
	 */
	public static function too_long( array $rules ): ?string {
		foreach ( $rules as $rule ) {
			$len = strlen( (string) ( $rule['expression'] ?? '' ) );
			if ( $len < self::EXPRESSION_LIMIT ) continue;
			$name = substr( (string) $rule['description'], strlen( self::NAME_PREFIX ) );
			if ( ( $rule['action'] ?? '' ) === 'skip' ) {
				return sprintf( '%1$s is %2$s characters, over Cloudflare\'s %3$s-character limit. Turn off service IP lists in this order: Better Stack (verified anyway), then ManageWP, then ShortPixel. Or move IPs from Your server IPs to IP Access Rules.', $name, number_format( $len ), number_format( self::EXPRESSION_LIMIT ) );
			}
			return sprintf( '%1$s is %2$s characters, over Cloudflare\'s %3$s-character limit. Turn off some options in this rule.', $name, number_format( $len ), number_format( self::EXPRESSION_LIMIT ) );
		}
		return null;
	}

	/** Lower-cased substring match, as wafrules.com writes it. */
	private static function lc( string $field, string $needle ): string {
		$needle = str_replace( [ '\\', '"' ], [ '\\\\', '\\"' ], $needle );
		return "(lower({$field}) contains \"{$needle}\")";
	}

	private static function build_rule1( array $s ): array {
		$parts = [];

		if ( ! empty( $s['include_client_bot'] ) ) {
			$parts[] = '(cf.client.bot)';
		} else {
			$cats = array_values( array_intersect( self::CATEGORIES, (array) ( $s['verified_categories'] ?? [] ) ) );
			if ( $cats ) {
				$parts[] = '(cf.verified_bot_category in {' . implode( ' ', array_map( static fn( $c ) => "\"{$c}\"", $cats ) ) . '})';
			}
		}

		$ips = [];
		foreach ( WPWAF_Service_IPs::all() as $svc ) {
			foreach ( $svc['settings'] as $key ) {
				if ( ! empty( $s[ $key ] ) ) { $ips = array_merge( $ips, $svc['ips'] ); break; }
			}
		}
		$ips = array_merge( $ips, (array) ( $s['allow_ips'] ?? [] ) );
		$ips = self::normalize_ips( $ips )['accepted'];
		if ( $ips ) $parts[] = '(ip.src in {' . implode( ' ', $ips ) . '})';

		if ( ! empty( $s['allow_letsencrypt'] ) ) $parts[] = self::LETS_ENCRYPT;
		if ( ! empty( $s['allow_wpumbrella'] ) && ! empty( $s['allow_wpumbrella_do_monitors'] ) ) $parts[] = self::WPU_MONITORS;
		if ( ! empty( $s['allow_flyingpress'] ) ) $parts[] = self::FLYINGPRESS;
		if ( ! empty( $s['allow_apple_messages'] ) ) $parts[] = self::APPLE_PREVIEW;

		foreach ( (array) ( $s['allow_user_agents'] ?? [] ) as $ua ) {
			$ua = trim( (string) $ua );
			if ( $ua === '' ) continue;
			$parts[] = '(http.user_agent contains "' . str_replace( [ '\\', '"' ], [ '\\\\', '\\"' ], $ua ) . '")';
		}
		foreach ( (array) ( $s['custom_expressions'] ?? [] ) as $expr ) {
			$expr = trim( (string) $expr );
			if ( $expr === '' ) continue;
			$parts[] = $expr[0] === '(' ? $expr : "({$expr})";
		}

		return [
			'description'       => self::NAMES['rule1'],
			'expression'        => $parts ? implode( ' or ', $parts ) : '(1 eq 0)',
			'action'            => 'skip',
			'action_parameters' => [
				// Skips the remaining custom rules. Managed rules are deliberately
				// not skipped, so Cloudflare's emergency CVE patches still apply.
				'ruleset'  => 'current',
				'phases'   => [ 'http_ratelimit', 'http_request_sbfm' ],
				'products' => [ 'zoneLockdown', 'uaBlock', 'bic', 'hot', 'securityLevel', 'rateLimit' ],
			],
			'enabled'           => true,
		];
	}

	private static function build_rule2_expr( array $s ): string {
		$p  = [];
		$ua = 'http.user_agent';
		foreach ( [ 'block_yandex' => 'yandex', 'block_sogou' => 'sogou', 'block_semrush' => 'semrush', 'block_ahrefs' => 'ahrefs', 'block_baidu' => 'baidu', 'block_python' => 'python-requests', 'block_neevabot' => 'neevabot' ] as $key => $needle ) {
			if ( ! empty( $s[ $key ] ) ) $p[] = self::lc( $ua, $needle );
		}
		foreach ( [ 'block_crawl' => 'crawl', 'block_bot' => 'bot', 'block_spider' => 'spider' ] as $key => $needle ) {
			if ( ! empty( $s[ $key ] ) ) $p[] = '(' . self::lc( $ua, $needle ) . ' and not cf.client.bot)';
		}
		foreach ( [ 'block_nikto' => 'nikto', 'block_sqlmap' => 'sqlmap', 'block_masscan' => 'masscan', 'block_nmap' => 'nmap' ] as $key => $needle ) {
			if ( ! empty( $s[ $key ] ) ) $p[] = self::lc( $ua, $needle );
		}

		$path = 'http.request.uri.path';
		if ( ! empty( $s['block_xmlrpc'] ) )   $p[] = self::lc( $path, 'xmlrpc' );
		if ( ! empty( $s['block_wpconfig'] ) ) $p[] = self::lc( $path, 'wp-config' );
		if ( ! empty( $s['block_wpjson'] ) )   $p[] = self::lc( $path, 'wp-json' );
		if ( ! empty( $s['block_wpinstall'] ) ) {
			$p[] = self::lc( $path, '/install.php' );
			$p[] = self::lc( $path, 'setup-config.php' );
		}
		if ( ! empty( $s['block_wlwmanifest'] ) ) $p[] = self::lc( $path, 'wlwmanifest' );
		if ( ! empty( $s['block_readme'] ) )      $p[] = self::lc( $path, 'readme.html' );
		if ( ! empty( $s['block_license'] ) )     $p[] = self::lc( $path, 'license.txt' );
		if ( ! empty( $s['block_sensitive_paths'] ) ) {
			foreach ( [ '/.env', '/.git', 'composer.json', 'composer.lock', 'debug.log', 'phpunit', 'server-status' ] as $t ) $p[] = self::lc( $path, $t );
		}
		if ( ! empty( $s['block_wpcron'] ) ) $p[] = self::lc( $path, 'wp-cron.php' );

		return implode( ' or ', $p );
	}

	private static function build_rule3_expr( array $s ): string {
		$p = [];

		if ( ! empty( $s['block_hosts'] ) ) {
			$asn_map = [
				'block_do'         => [ 14061 ],
				'block_linode'     => [ 63949 ],
				'block_vultr'      => [ 20473 ],
				'block_hetzner'    => [ 24940, 213230 ],
				'block_ovh'        => [ 35540 ],
				'block_contabo'    => [ 51167 ],
				'block_scaleway'   => [ 12876 ],
				'block_dreamhost'  => [ 26347 ],
				'block_m247'       => [ 9009 ],
				'block_leaseweb'   => [ 60781, 205544, 27411, 7203, 30633, 395954 ],
				'block_godaddy'    => [ 398101, 31815, 26496 ],
				'block_alibaba'    => [ 45102, 37963 ],
				'block_hostroyale' => [ 207990 ],
				'block_cloudvider' => [ 62240 ],
			];
			$asns = [];
			foreach ( $asn_map as $key => $nums ) {
				if ( ! empty( $s[ $key ] ) ) $asns = array_merge( $asns, $nums );
			}
			if ( $asns ) {
				$p[] = '(ip.src.asnum in {' . implode( ' ', $asns ) . '} and not cf.client.bot and not http.request.uri.path contains "acme-challenge")';
			}
		}
		if ( ! empty( $s['block_tor'] ) ) $p[] = '(ip.src.country in {"T1"})';

		$query = 'http.request.uri.query';
		$uri   = 'http.request.uri';
		$groups = [
			'block_time_delay_sqli' => [ $query, [ 'pg_sleep', 'sleep(', 'benchmark(', 'dbms_pipe', 'receive_message', 'waitfor%20delay' ] ],
			'block_union_sqli'      => [ $query, [ 'union%2f%2a%2a%2fselect', 'union%20select', 'information_schema', 'concat(', 'load_file(', 'into%20outfile' ] ],
			'block_lfi_traversal'   => [ $uri, [ '../', '..%5c', '%2e%2e', 'etc/passwd', '%2fetc%2fpasswd', '%252fetc', '%00', '%5c..%5c' ] ],
			'block_legacy_paths'    => [ $uri, [ 'smb2www.pl', 'smbshr.pl', 'story.pl', 'browserweb/portal/portalbanner.htm', 'about/frmabout.aspx', 'symantec.jsp', 'webman/info.cgi', 'smpwservicescgi.exe', 'printenv' ] ],
			'block_foreign_cms'     => [ $uri, [ 'index.php?mode=administration', 'index.php?user/login', 'action=login&module=users', '/symphony/', '/spip/', '/taskfreak/', '/temenos/', '/tembria/', '/sympa/', '/lists/remindpasswd', '/wws/remindpasswd' ] ],
			'block_reflected_xss'   => [ $uri, [ '<script', '%3cscript', 'javascript:', 'onerror=', 'onload=' ] ],
		];
		foreach ( $groups as $key => [ $field, $needles ] ) {
			if ( empty( $s[ $key ] ) ) continue;
			foreach ( $needles as $n ) $p[] = self::lc( $field, $n );
		}

		return implode( ' or ', $p );
	}

	private static function build_rule4_expr( array $s ): string {
		$asn_map = [
			'challenge_aws'   => [ 16509, 14618, 7224 ],
			'challenge_gcp'   => [ 15169, 396982 ],
			'challenge_azure' => [ 8075 ],
		];
		$asns  = [];
		$parts = [];
		foreach ( $asn_map as $key => $nums ) {
			if ( ! empty( $s[ $key ] ) ) $asns = array_merge( $asns, $nums );
		}
		if ( $asns ) {
			$parts[] = '(ip.src.asnum in {' . implode( ' ', $asns ) . '} and not cf.client.bot and not cf.verified_bot_category in {' . self::RULE4_SAFE . '} and not http.request.uri.path contains "acme-challenge")';
		}
		if ( ! empty( $s['challenge_country'] ) && ! empty( $s['countries'] ) && is_array( $s['countries'] ) ) {
			$codes = [];
			foreach ( $s['countries'] as $c ) {
				$c = strtoupper( trim( (string) $c ) );
				if ( preg_match( '/^[A-Z]{2}$/', $c ) && ! in_array( "\"{$c}\"", $codes, true ) ) $codes[] = "\"{$c}\"";
			}
			if ( $codes ) {
				$parts[] = '(not ip.src.country in {' . implode( ' ', $codes ) . '} and not cf.client.bot and not cf.verified_bot_category in {' . self::RULE4_SAFE . '} and not http.request.uri.path contains "acme-challenge")';
			}
		}
		return implode( ' or ', $parts );
	}

	private static function build_rule5_expr( array $s ): string {
		$vpn_asns = [
			'challenge_nordvpn'       => [ 207137, 141039, 147049 ],
			'challenge_expressvpn'    => [ 9009, 16247, 51332 ],
			'challenge_purevpn'       => [ 394087, 60068, 212238 ],
			'challenge_surfshark'     => [ 209854, 9009 ],
			'challenge_ipvanish'      => [ 46253 ],
			'challenge_quadranet'     => [ 8100, 62639 ],
			'challenge_ovhfr'         => [ 16276 ],
			'challenge_internetutils' => [ 206092, 206074, 206164, 206150, 210277 ],
			'challenge_mullvad'       => [ 216025, 39351 ],
			'challenge_privlayer'     => [ 51852 ],
		];
		// "Challenge All VPN Connections" ticks every provider; with it off,
		// the providers ticked one by one are used.
		if ( ! empty( $s['challenge_all_vpn'] ) ) {
			foreach ( $vpn_asns as $key => $_ ) $s[ $key ] = true;
		}
		$parts = [];
		$asns  = [];
		foreach ( $vpn_asns as $key => $nums ) {
			if ( ! empty( $s[ $key ] ) ) $asns = array_merge( $asns, $nums );
		}
		// Some providers share an ASN (9009); list each once.
		$asns = array_values( array_unique( $asns ) );
		if ( $asns ) $parts[] = '(ip.src.asnum in {' . implode( ' ', $asns ) . '})';
		if ( ! empty( $s['challenge_wplogin'] ) ) $parts[] = '(http.request.uri.path contains "wp-login.php")';
		return implode( ' or ', $parts );
	}

	// ── IP lists ──────────────────────────────────────────────────────────────

	/**
	 * Validates, canonicalizes, de-duplicates and sorts IPs and CIDRs the same
	 * way wafrules.com does: host bits below the prefix are cleared, IPv6 is
	 * written in compressed lower case, IPv4 sorts before IPv6, then by address
	 * and prefix length.
	 *
	 * @param array<int,mixed> $list
	 * @return array{accepted: string[], invalid: string[]}
	 */
	public static function normalize_ips( array $list ): array {
		$seen = [];
		$bad  = [];
		foreach ( $list as $item ) {
			$item = trim( (string) $item );
			if ( $item === '' ) continue;
			$p = self::parse_ip( $item );
			if ( $p === null ) { $bad[] = $item; continue; }
			$seen[ $p['text'] ] = $p;
		}
		uasort( $seen, static function ( array $a, array $b ): int {
			if ( $a['v'] !== $b['v'] ) return $a['v'] - $b['v'];
			$c = strcmp( $a['bin'], $b['bin'] );
			return $c !== 0 ? $c : $a['prefix'] - $b['prefix'];
		} );
		return [ 'accepted' => array_keys( $seen ), 'invalid' => $bad ];
	}

	/** @return array{v:int,bin:string,prefix:int,text:string}|null */
	private static function parse_ip( string $s ): ?array {
		if ( preg_match( '/\s/', $s ) ) return null;
		$slash  = strpos( $s, '/' );
		$addr   = $slash === false ? $s : substr( $s, 0, $slash );
		$prefix = $slash === false ? null : substr( $s, $slash + 1 );
		if ( $prefix !== null && ! preg_match( '/^\d+$/', $prefix ) ) return null;

		if ( str_contains( $addr, ':' ) ) {
			$bin = self::parse_v6( $addr );
			$max = 128;
			$v   = 6;
		} else {
			$bin = self::parse_v4( $addr );
			$max = 32;
			$v   = 4;
		}
		if ( $bin === null ) return null;
		$len = $prefix === null ? $max : (int) $prefix;
		if ( $len < 0 || $len > $max ) return null;
		$bin  = self::mask( $bin, $len );
		$text = ( $v === 4 ? implode( '.', array_map( 'ord', str_split( $bin ) ) ) : self::format_v6( $bin ) ) . ( $len === $max ? '' : '/' . $len );
		return [ 'v' => $v, 'bin' => $bin, 'prefix' => $len, 'text' => $text ];
	}

	private static function parse_v4( string $s ): ?string {
		$parts = explode( '.', $s );
		if ( count( $parts ) !== 4 ) return null;
		$bin = '';
		foreach ( $parts as $p ) {
			if ( ! preg_match( '/^(0|[1-9]\d{0,2})$/', $p ) || (int) $p > 255 ) return null;
			$bin .= chr( (int) $p );
		}
		return $bin;
	}

	private static function parse_v6( string $s ): ?string {
		$s = strtolower( $s );
		if ( str_contains( $s, '%' ) || substr_count( $s, '::' ) > 1 ) return null;
		$halves = explode( '::', $s );
		$head   = $halves[0] === '' ? [] : explode( ':', $halves[0] );
		$tail   = count( $halves ) === 1 ? [] : ( $halves[1] === '' ? [] : explode( ':', $halves[1] ) );
		if ( count( $halves ) === 1 ) {
			if ( count( $head ) !== 8 ) return null;
			$groups = $head;
		} else {
			if ( count( $head ) + count( $tail ) > 7 ) return null;
			$groups = array_merge( $head, array_fill( 0, 8 - count( $head ) - count( $tail ), '0' ), $tail );
		}
		$bin = '';
		foreach ( $groups as $g ) {
			if ( ! preg_match( '/^[0-9a-f]{1,4}$/', $g ) ) return null;
			$bin .= pack( 'n', hexdec( $g ) );
		}
		return $bin;
	}

	private static function mask( string $bin, int $prefix ): string {
		$out = '';
		$len = strlen( $bin );
		for ( $i = 0; $i < $len; $i++ ) {
			$bits = max( 0, min( 8, $prefix - $i * 8 ) );
			$out .= chr( ord( $bin[ $i ] ) & ( $bits === 0 ? 0 : ( 0xFF << ( 8 - $bits ) ) & 0xFF ) );
		}
		return $out;
	}

	/** Compressed lower-case IPv6, collapsing the longest run of two or more zero groups. */
	private static function format_v6( string $bin ): string {
		$g = array_values( unpack( 'n8', $bin ) );
		$best = -1; $best_len = 0;
		for ( $i = 0; $i < 8; ) {
			if ( $g[ $i ] !== 0 ) { $i++; continue; }
			$j = $i;
			while ( $j < 8 && $g[ $j ] === 0 ) $j++;
			if ( $j - $i > $best_len ) { $best = $i; $best_len = $j - $i; }
			$i = $j;
		}
		$hex = array_map( static fn( int $x ) => dechex( $x ), $g );
		if ( $best_len < 2 ) return implode( ':', $hex );
		return implode( ':', array_slice( $hex, 0, $best ) ) . '::' . implode( ':', array_slice( $hex, $best + $best_len ) );
	}
}
