<?php
declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Builds Cloudflare WAF rule expressions matching wafrules.com exactly.
 * PHP 8.0+ with strict types.
 */
class WPWAF_Rule_Builder {

	public static function default_settings(): array {
		return [
			'rule1' => [
				'enabled'              => true,
				// Default: 10 core verified categories (matching wafrules.com screenshot)
				'verified_categories'  => [
					'Accessibility', 'Academic Research', 'Advertising & Marketing',
					'Feed Fetcher', 'Monitoring & Analytics', 'Page Preview',
					'Search Engine Crawler', 'Search Engine Optimization',
					'Security', 'Webhooks',
				],
				// All individual UA services are off by default — opt-in
				'allow_backupbuddy'    => false,
				'allow_blogvault'      => false,
				'allow_updraftplus'    => false,
				'allow_betterstack'    => false,
				'allow_gtmetrix'       => false,
				'allow_pingdom'        => false,
				'allow_statuscake'     => false,
				'allow_uptimerobot'    => false,
				'allow_usercentrics'   => false,
				'allow_cf_image'       => false,
				'allow_exactdn'        => false,
				'allow_ewww'           => false,
				'allow_flyingpress'    => false,
				'allow_imagify'        => false,
				'allow_shortpixel'     => false,
				'allow_tinypng'        => false,
				'allow_ahrefs'         => false,
				'allow_ahrefs_audit'   => false,
				'allow_mj12'           => false,
				'allow_rogerbot'       => false,
				'allow_screamingfrog'  => false,
				'allow_semrush'        => false,
				'allow_siteauditbot'   => false,
				'allow_semrush_ocob'   => false,
				'allow_sitelock'       => false,
				'allow_sucuri'         => false,
				'allow_virustotal'     => false,
				'allow_wordfence'      => false,
				'allow_facebook'       => false,
				'allow_linkedin'       => false,
				'allow_twitter'        => false,
				'allow_jetpack'        => false,
				'allow_mainwp'         => false,
				'allow_managewp'       => false,
				'allow_godaddy_uptime' => false,
				'allow_wpumbrella'     => false,
				'allow_letsencrypt'    => true,
				'allow_ips'            => [],
			],
			'rule2' => [
				'enabled'              => true,
				// Known search engines in verified categories — block=false to avoid conflict with Rule 1
				'block_yandex'         => false,
				'block_sogou'          => false,
				'block_semrush'        => false,
				'block_ahrefs'         => false,
				'block_baidu'          => false,
				'block_neevabot'       => true,
				'block_python'         => true,
				'block_crawl'          => true,
				'block_bot'            => true,
				'block_spider'         => true,
				'block_nikto'          => true,
				'block_sqlmap'         => true,
				'block_masscan'        => true,
				'block_nmap'           => true,
				// xmlrpc is a major brute-force vector — on by default
				'block_xmlrpc'         => true,
				'block_wpconfig'       => false,
				// wp-json needed by Gutenberg, WooCommerce, mobile apps — off by default
				'block_wpjson'         => false,
				'block_wpinstall'      => false,
				'block_wlwmanifest'    => true,
				'block_readme'         => true,
				'block_license'        => true,
				'block_sqli_sleep'     => true,
				'block_path_traversal' => true,
			],
			'rule3' => [
				'enabled'          => true,
				'block_do'         => true,
				'block_linode'     => true,
				'block_vultr'      => true,
				'block_hetzner'    => true,
				'block_ovh'        => true,
				'block_contabo'    => true,
				'block_scaleway'   => true,
				'block_dreamhost'  => true,
				'block_m247'       => true,
				'block_leaseweb'   => true,
				'block_godaddy'    => true,
				'block_alibaba'    => true,
				'block_hostroyale' => true,
				'block_cloudvider' => true,
				'block_tor'        => true,
				'action'           => 'block',
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
				'enabled'              => true,
				// All VPN providers enabled by default (matching wafrules.com)
				'challenge_all_vpn'    => true,
				'challenge_nordvpn'    => true,
				'challenge_expressvpn' => true,
				'challenge_purevpn'    => true,
				'challenge_surfshark'  => true,
				'challenge_ipvanish'   => true,
				'challenge_quadranet'  => true,
				'challenge_ovhfr'      => true,
				'challenge_internetutils' => true,
				'challenge_mullvad'    => true,
				'challenge_privlayer'  => true,
				'challenge_wplogin'    => true,
			],
		];

		return apply_filters( 'wpwaf_default_settings', $settings );

	}

	public static function build_rules( array $settings ): array {
		$rules = [];

		if ( ! empty( $settings['rule1']['enabled'] ) ) {
			$rules[] = self::build_rule1( $settings['rule1'] );
		}
		if ( ! empty( $settings['rule2']['enabled'] ) ) {
			$rules[] = self::build_rule2( $settings['rule2'] );
		}
		if ( ! empty( $settings['rule3']['enabled'] ) ) {
			$r = self::build_rule3( $settings['rule3'] );
			if ( $r ) $rules[] = $r;
		}
		if ( ! empty( $settings['rule4']['enabled'] ) ) {
			$expr = self::build_rule4_expr( $settings['rule4'] );
			if ( $expr ) {
				$rules[] = [
					'description' => '[CF WAF] Challenge Large Providers / Country',
					'expression'  => $expr,
					'action'      => 'managed_challenge',
					'enabled'     => true,
				];
			}
		}
		if ( ! empty( $settings['rule5']['enabled'] ) ) {
			$expr = self::build_rule5_expr( $settings['rule5'] );
			if ( $expr ) {
				$rules[] = [
					'description' => '[CF WAF] Challenge VPN & wp-login',
					'expression'  => $expr,
					'action'      => 'managed_challenge',
					'enabled'     => true,
				];
			}
		}

		return $rules;
	}

	/**
	 * Sanitize an incoming settings array from POST/JSON before storing.
	 * Ensures only known keys with correct types are persisted.
	 */
	public static function sanitize_settings( array $raw ): array {
		$defaults = self::default_settings();
		$out      = [];

		foreach ( $defaults as $rule => $rule_defaults ) {
			$rule_raw = $raw[ $rule ] ?? [];
			if ( ! is_array( $rule_raw ) ) continue;
			$out[ $rule ] = [];
			foreach ( $rule_defaults as $key => $default ) {
				$val = $rule_raw[ $key ] ?? $default;
				$out[ $rule ][ $key ] = match ( true ) {
					is_bool( $default )   => (bool) $val,
					is_int( $default )    => (int) $val,
					is_float( $default )  => (float) $val,
					is_array( $default )  => is_array( $val )
						? array_values( array_map( 'sanitize_text_field', $val ) )
						: $default,
					default               => sanitize_text_field( (string) $val ),
				};
			}
		}

		// Special case: validate allow_ips as actual IP/CIDR entries.
		if ( isset( $out['rule1']['allow_ips'] ) ) {
			$out['rule1']['allow_ips'] = array_values( array_filter(
				$out['rule1']['allow_ips'],
				function( string $ip ): bool {
					$ip = trim( $ip );
					if ( $ip === '' ) return false;
					// Accept plain IPv4, IPv6, or CIDR notation.
					if ( str_contains( $ip, '/' ) ) {
						[ $addr ] = explode( '/', $ip, 2 );
						return (bool) filter_var( $addr, FILTER_VALIDATE_IP );
					}
					return (bool) filter_var( $ip, FILTER_VALIDATE_IP );
				}
			) );
		}

		return $out;
	}

	private static function build_rule1( array $s ): array {
		$parts = [];

		if ( ! empty( $s['verified_categories'] ) && is_array( $s['verified_categories'] ) ) {
			$parts[] = '(cf.client.bot)';
			$cats    = array_map( fn( $c ) => "\"{$c}\"", $s['verified_categories'] );
			$parts[] = '(cf.verified_bot_category in {' . implode( ' ', $cats ) . '})';
		}

		$ua_map = [
			'allow_backupbuddy'    => [ 'BackupBuddy' ],
			'allow_blogvault'      => [ 'BlogVault' ],
			'allow_updraftplus'    => [ 'UpdraftPlus' ],
			'allow_betterstack'    => [ 'Better Stack Better Uptime Bot', 'Better Uptime Bot' ],
			'allow_gtmetrix'       => [ 'GTmetrix' ],
			'allow_pingdom'        => [ 'Pingdom' ],
			'allow_statuscake'     => [ 'StatusCake' ],
			'allow_uptimerobot'    => [ 'UptimeRobot' ],
			'allow_usercentrics'   => [ 'Usercentricsbot' ],
			'allow_cf_image'       => [ 'Cloudflare-Image-Resizing' ],
			'allow_exactdn'        => [ 'ExactDN' ],
			'allow_ewww'           => [ 'ewww' ],
			'allow_flyingpress'    => [ 'FlyingPress' ],
			'allow_imagify'        => [ 'Imagify' ],
			'allow_shortpixel'     => [ 'ShortPixel' ],
			'allow_tinypng'        => [ 'TinyPNG' ],
			'allow_ahrefs'         => [ 'AhrefsBot' ],
			'allow_ahrefs_audit'   => [ 'Ahrefs Site Audit' ],
			'allow_mj12'           => [ 'MJ12bot' ],
			'allow_rogerbot'       => [ 'rogerbot' ],
			'allow_screamingfrog'  => [ 'Screaming Frog SEO Spider' ],
			'allow_semrush'        => [ 'SemrushBot' ],
			'allow_siteauditbot'   => [ 'SiteAuditBot' ],
			'allow_semrush_ocob'   => [ 'SemrushBot-OCOB' ],
			'allow_sitelock'       => [ 'SiteLockSpider' ],
			'allow_sucuri'         => [ 'SucuriScan' ],
			'allow_virustotal'     => [ 'virustotal' ],
			'allow_wordfence'      => [ 'Wordfence' ],
			'allow_facebook'       => [ 'facebookexternalhit' ],
			'allow_linkedin'       => [ 'LinkedInBot' ],
			'allow_twitter'        => [ 'Twitterbot' ],
			'allow_jetpack'        => [ 'Jetpack' ],
			'allow_mainwp'         => [ 'MainWP' ],
			'allow_managewp'       => [ 'ManageWP' ],
			'allow_godaddy_uptime' => [ 'GoDaddy Uptime Monitor' ],
			'allow_wpumbrella'     => [ 'WPUmbrella' ],
		];

		/**
		 * Filter Rule 1 user-agent allow map.
		 *
		 * This allows plugins/themes to register additional toggleable UA services.
		 * To persist new toggle keys, also extend `wpwaf_default_settings`.
		 */
		$ua_map = apply_filters( 'wpwaf_rule1_ua_map', $ua_map, $s );

		foreach ( $ua_map as $key => $uas ) {
			if ( ! empty( $s[ $key ] ) && is_array( $uas ) ) {
				foreach ( $uas as $ua ) {
					$ua = self::escape_cf_string( (string) $ua );
					if ( $ua !== '' ) {
						$parts[] = "(http.user_agent contains \"{$ua}\")";
					}
				}
			}
		}

		/**
		 * Filter additional always-allowed user agents for Rule 1.
		 *
		 * These do not require saved settings toggles and are intended for
		 * programmatic allow-listing.
		 */
		$extra_user_agents = apply_filters( 'wpwaf_rule1_extra_user_agents', [], $s );

		if ( is_array( $extra_user_agents ) ) {
			foreach ( $extra_user_agents as $ua ) {
				$ua = self::escape_cf_string( (string) $ua );
				if ( $ua !== '' ) {
					$parts[] = "(http.user_agent contains \"{$ua}\")";
				}
			}
		}

		if ( ! empty( $s['allow_letsencrypt'] ) ) {
			$parts[] = '(http.user_agent contains "letsencrypt" and http.request.uri.path contains "acme-challenge")';
		}

		if ( ! empty( $s['allow_ips'] ) && is_array( $s['allow_ips'] ) ) {
			$ips = [];

			foreach ( $s['allow_ips'] as $ip ) {
				$ip = trim( (string) $ip );
				if ( $ip !== '' ) $ips[] = "\"{$ip}\"";
			}

			if ( ! empty( $ips ) ) {
				$parts[] = '(ip.src in {' . implode( ' ', $ips ) . '})';
			}
		}

		/**
		 * Filter additional complete Cloudflare allow expressions for Rule 1.
		 *
		 * Each item must be a valid Cloudflare expression fragment.
		 *
		 * Example:
		 * add_filter( 'wpwaf_rule1_extra_allow_expressions', function( array $expressions ): array {
		 *     $expressions[] = 'http.request.headers["x-trustwards-scanner"][0] eq "true"';
		 *     return $expressions;
		 * } );
		 */
		$extra_expressions = apply_filters( 'wpwaf_rule1_extra_allow_expressions', [], $s );

		if ( is_array( $extra_expressions ) ) {
			foreach ( $extra_expressions as $expr ) {
				$expr = trim( (string) $expr );

				if ( $expr !== '' ) {
					$parts[] = '(' . $expr . ')';
				}
			}
		}


		return [
			'description'       => '[CF WAF] Allow Good Bots',
			'expression'        => ! empty( $parts ) ? implode( ' or ', $parts ) : '(1 eq 0)',
			'action'            => 'skip',
			'action_parameters' => [
				'ruleset'  => 'current',
				'phases'   => [
					// Note: http_request_firewall_custom is intentionally omitted —
					// 'ruleset: current' already skips remaining custom rules,
					// and explicitly listing it causes [20120] errors on Free plans.
					'http_ratelimit',
					'http_request_firewall_managed',
					'http_request_sbfm',
				],
				'products' => [
					'zoneLockdown', 'uaBlock', 'bic', 'hot',
					'securityLevel', 'rateLimit', 'waf',
				],
			],
			'enabled' => true,
		];
	}

	private static function build_rule2( array $s ): array {
		$parts = [];

		$ua_blocks = [
			'block_yandex'   => 'yandex',
			'block_sogou'    => 'sogou',
			'block_semrush'  => 'semrush',
			'block_ahrefs'   => 'ahrefs',
			'block_baidu'    => 'baidu',
			'block_python'   => 'python-requests',
			'block_neevabot' => 'neevabot',
		];
		foreach ( $ua_blocks as $key => $ua ) {
			if ( ! empty( $s[ $key ] ) ) $parts[] = "(http.user_agent contains \"{$ua}\")";
		}

		if ( ! empty( $s['block_crawl'] ) )  $parts[] = '(http.user_agent contains "crawl" and not cf.client.bot)';
		if ( ! empty( $s['block_bot'] ) )    $parts[] = '(http.user_agent contains "bot" and not cf.client.bot)';
		if ( ! empty( $s['block_spider'] ) ) $parts[] = '(http.user_agent contains "spider" and not cf.client.bot)';
		if ( ! empty( $s['block_nikto'] ) )  $parts[] = '(http.user_agent contains "nikto")';
		if ( ! empty( $s['block_sqlmap'] ) ) $parts[] = '(http.user_agent contains "sqlmap")';
		if ( ! empty( $s['block_masscan'] )) $parts[] = '(http.user_agent contains "masscan")';
		if ( ! empty( $s['block_nmap'] ) )   $parts[] = '(http.user_agent contains "nmap")';

		if ( ! empty( $s['block_xmlrpc'] ) )      $parts[] = '(lower(http.request.uri.path) contains "xmlrpc")';
		if ( ! empty( $s['block_wpconfig'] ) )    $parts[] = '(lower(http.request.uri.path) contains "wp-config")';
		if ( ! empty( $s['block_wpjson'] ) )      $parts[] = '(lower(http.request.uri.path) contains "wp-json")';
		if ( ! empty( $s['block_wpinstall'] ) )   $parts[] = '(lower(http.request.uri.path) contains "/install.php")';
		if ( ! empty( $s['block_wlwmanifest'] ) ) $parts[] = '(lower(http.request.uri.path) contains "wlwmanifest")';
		if ( ! empty( $s['block_readme'] ) )      $parts[] = '(lower(http.request.uri.path) contains "readme.html")';
		if ( ! empty( $s['block_license'] ) )     $parts[] = '(lower(http.request.uri.path) contains "license.txt")';

		if ( ! empty( $s['block_sqli_sleep'] ) ) {
			foreach ( [ 'pg_sleep', 'pg_sleep(', 'sleep(', 'benchmark(', 'dbms_pipe', 'receive_message', 'waitfor%20delay', 'waitfor+delay', 'waitfor%09delay' ] as $t ) {
				$parts[] = "(lower(http.request.uri.query) contains \"{$t}\")";
			}
		}
		if ( ! empty( $s['block_path_traversal'] ) ) {
			foreach ( [ '%2fetc%2fpasswd', '%2fetc/passwd', '%5c..%5c', '%2e%2e%2f', '%2e%2e%5c' ] as $t ) {
				$parts[] = "(lower(http.request.uri) contains \"{$t}\")";
			}
		}

		return [
			'description' => '[CF WAF] Block Aggressive Crawlers & WP Paths',
			'expression'  => ! empty( $parts ) ? implode( ' or ', $parts ) : '(1 eq 0)',
			'action'      => 'block',
			'enabled'     => true,
		];
	}

	private static function build_rule3( array $s ): ?array {
		$asn_map = [
			'block_do'         => [ 14061 ],
			'block_linode'     => [ 63949 ],
			'block_vultr'      => [ 20473 ],
			// Hetzner: 24940 + 213230 (213230 belongs to Hetzner, not OVH)
			'block_hetzner'    => [ 24940, 213230 ],
			// OVH: 16276 + 35540 (213230 removed)
			'block_ovh'        => [ 16276, 35540 ],
			'block_contabo'    => [ 51167 ],
			'block_scaleway'   => [ 12876 ],
			'block_dreamhost'  => [ 26347 ],
			'block_m247'       => [ 9009 ],
			// Leaseweb: 398101 and 31815 removed (they belong to GoDaddy)
			'block_leaseweb'   => [ 60781, 205544, 27411, 7203, 30633, 395954 ],
			// GoDaddy: 398101 + 31815 added back where they belong
			'block_godaddy'    => [ 398101, 31815, 26496 ],
			'block_alibaba'    => [ 45102, 37963 ],
			'block_hostroyale' => [ 207990 ],
			'block_cloudvider' => [ 62240 ],
		];

		$asns   = [];
		$parts  = [];
		$action = ( $s['action'] ?? 'block' ) === 'block' ? 'block' : 'managed_challenge';

		foreach ( $asn_map as $key => $nums ) {
			if ( ! empty( $s[ $key ] ) ) $asns = [ ...$asns, ...$nums ];
		}
		if ( ! empty( $asns ) ) {
			$parts[] = '(ip.src.asnum in {' . implode( ' ', $asns ) . '} and not cf.client.bot and not http.request.uri.path contains "acme-challenge")';
		}
		if ( ! empty( $s['block_tor'] ) ) {
			$parts[] = '(ip.src.country in {"T1"})';
		}
		if ( empty( $parts ) ) return null;

		return [
			'description' => '[CF WAF] Block Web Hosts & TOR',
			'expression'  => implode( ' or ', $parts ),
			'action'      => $action,
			'enabled'     => true,
		];
	}

	private static function build_rule4_expr( array $s ): string {
		$asn_map = [
			'challenge_aws'   => [ 16509, 14618, 7224 ],
			'challenge_gcp'   => [ 15169, 396982 ],
			'challenge_azure' => [ 8075 ],
		];
		$asns  = [];
		$parts = [];
		$safe  = '"Search Engine Crawler" "Search Engine Optimization" "Monitoring & Analytics" "Advertising & Marketing" "Page Preview" "Academic Research" "Security" "Accessibility" "Webhooks" "Feed Fetcher"';

		foreach ( $asn_map as $key => $nums ) {
			if ( ! empty( $s[ $key ] ) ) $asns = [ ...$asns, ...$nums ];
		}
		if ( ! empty( $asns ) ) {
			$parts[] = '(ip.src.asnum in {' . implode( ' ', $asns ) . '} and not cf.client.bot and not cf.verified_bot_category in {' . $safe . '} and not http.request.uri.path contains "acme-challenge")';
		}
		if ( ! empty( $s['challenge_country'] ) && ! empty( $s['countries'] ) && is_array( $s['countries'] ) ) {
			$codes = [];
			foreach ( $s['countries'] as $c ) {
				$c = strtoupper( trim( (string) $c ) );
				if ( strlen( $c ) === 2 && ! in_array( $c, $codes, true ) ) {
					$codes[] = "\"{$c}\"";
				}
			}
			if ( ! empty( $codes ) ) {
				$parts[] = '(not ip.src.country in {' . implode( ' ', $codes ) . '} and not cf.client.bot and not cf.verified_bot_category in {' . $safe . '} and not http.request.uri.path contains "acme-challenge")';
			}
		}
		return implode( ' or ', $parts );
	}

	private static function build_rule5_expr( array $s ): string {
		$vpn_asns = [
			'challenge_nordvpn'    => [ 207137, 141039, 147049 ],
			// ExpressVPN: 9009 (M247/ExpressVPN shared), 16247, 51332
			'challenge_expressvpn' => [ 9009, 16247, 51332 ],
			// PureVPN: 394087, 60068, 212238
			'challenge_purevpn'    => [ 394087, 60068, 212238 ],
			// Surfshark: 46253 removed (that's IPVanish)
			'challenge_surfshark'  => [ 209854, 9009 ],
			// IPVanish: 46253 correct; was incorrectly 8100 (QuadraNet)
			'challenge_ipvanish'   => [ 46253 ],
			// QuadraNet: 8100 added (was missing)
			'challenge_quadranet'  => [ 8100, 62639 ],
			'challenge_ovhfr'      => [ 16276 ],
			// Internet Utilities: separate ASN block distinct from OVH France (matching wafrules.com)
			'challenge_internetutils' => [ 206092, 206074, 206164, 206150, 210277 ],
			// Mullvad: 216025 added
			'challenge_mullvad'    => [ 216025, 39351 ],
			'challenge_privlayer'  => [ 51852 ],
		];

		if ( ! empty( $s['challenge_all_vpn'] ) ) {
			foreach ( $vpn_asns as $key => $_ ) $s[ $key ] = true;
		}

		$asns  = [];
		$parts = [];
		foreach ( $vpn_asns as $key => $nums ) {
			if ( ! empty( $s[ $key ] ) ) $asns = [ ...$asns, ...$nums ];
		}
		// Deduplicate ASNs — some are shared across providers (9009, 209854)
		$asns = array_values( array_unique( $asns ) );

		if ( ! empty( $asns ) ) {
			$parts[] = '(ip.src.asnum in {' . implode( ' ', $asns ) . '})';
		}
		if ( ! empty( $s['challenge_wplogin'] ) ) {
			$parts[] = '(http.request.uri.path contains "wp-login.php")';
		}
		return implode( ' or ', $parts );
	}

	private static function escape_cf_string( string $value ): string {
		$value = trim( $value );
		$value = str_replace( [ '\\', '"' ], [ '\\\\', '\\"' ], $value );

		return $value;
	}
}
