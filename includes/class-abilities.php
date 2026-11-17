<?php
declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Abilities for AI tools and MCP (WordPress Abilities API, WordPress 6.9+).
 *
 * Registers what WP WAF Manager can do as abilities, so that WordPress itself
 * (the /wp-abilities/v1 REST routes) and MCP plugins such as the official MCP
 * Adapter can list and run them. Every ability is marked public, which is what
 * both look for, and runs with the same permission check as the plugin's
 * screens (WPWAF_Access): the user an AI tool signs in as must be allowed to
 * use the plugin.
 *
 * Settings > AI & MCP chooses what is offered: everything, read only, or
 * nothing. On WordPress before 6.9 the hooks below never fire, so nothing is
 * registered.
 *
 * Abilities reuse the code behind the screens: WPWAF_Rule_Builder::deploy()
 * and ::save(), WPWAF_API::purge_request(), ::zone_setting_value() and the
 * IP rule lists, so a check added there applies to both.
 */
final class WPWAF_Abilities {

	/** Ability category and name prefix. */
	public const CATEGORY = 'wpwafmanager';

	/** Zones from Cloudflare, per account, for the current request. */
	private static array $zones = [];

	/** REST namespace and route of the plugin's own MCP server (with the MCP Adapter). */
	public const MCP_NAMESPACE = 'wpwafmanager';
	public const MCP_ROUTE     = 'mcp';

	public static function init(): void {
		add_action( 'wp_abilities_api_categories_init', [ __CLASS__, 'register_category' ] );
		add_action( 'wp_abilities_api_init', [ __CLASS__, 'register_abilities' ] );
		add_action( 'mcp_adapter_init', [ __CLASS__, 'register_mcp_server' ] );
	}

	/**
	 * With the MCP Adapter plugin active, adds a "WP WAF Manager" MCP server at
	 * /wp-json/wpwafmanager/mcp that lists every ability as its own tool. (The
	 * adapter's default server reaches the same abilities through its
	 * discover and execute tools.) Only users allowed to use the plugin can
	 * connect.
	 *
	 * @param object $adapter The WP\MCP\Core\McpAdapter instance.
	 */
	public static function register_mcp_server( $adapter ): void {
		$transport = 'WP\MCP\Transport\HttpTransport';
		if ( self::mode() === 'off' || ! is_object( $adapter ) || ! method_exists( $adapter, 'create_server' ) || ! class_exists( $transport ) ) {
			return;
		}
		$tools = array_values( array_filter( self::names(), 'wp_has_ability' ) );
		if ( empty( $tools ) ) {
			return;
		}
		$adapter->create_server(
			'wpwafmanager',
			self::MCP_NAMESPACE,
			self::MCP_ROUTE,
			'WP WAF Manager',
			'Cloudflare security for this site and the agency\'s zones: WAF rules, cache, zone settings, IP Access Rules, DNS, analytics and security events.',
			WPWAF_VERSION,
			[ $transport ],
			null,
			null,
			$tools,
			[],
			[],
			[ __CLASS__, 'can_use' ]
		);
	}

	/** Address of the plugin's MCP server, for Settings. */
	public static function mcp_url(): string {
		return rest_url( self::MCP_NAMESPACE . '/' . self::MCP_ROUTE );
	}

	/** 'all', 'read' or 'off', from Settings > AI & MCP. */
	public static function mode(): string {
		$mode = (string) WPWAF_Settings::get( 'abilities', 'all' );
		return in_array( $mode, [ 'all', 'read', 'off' ], true ) ? $mode : 'all';
	}

	public static function register_category(): void {
		if ( self::mode() === 'off' || ! function_exists( 'wp_register_ability_category' ) ) {
			return;
		}
		wp_register_ability_category(
			self::CATEGORY,
			[
				'label'       => 'WP WAF Manager',
				'description' => 'Cloudflare security for this site and the agency\'s other zones: WAF rules, cache, zone settings, IP Access Rules, DNS, analytics and security events.',
			]
		);
	}

	public static function register_abilities(): void {
		$mode = self::mode();
		if ( $mode === 'off' || ! function_exists( 'wp_register_ability' ) ) {
			return;
		}
		foreach ( self::definitions() as $name => $def ) {
			if ( $mode === 'read' && empty( $def['readonly'] ) ) {
				continue;
			}
			wp_register_ability( self::CATEGORY . '/' . $name, self::args( $def ) );
		}
	}

	/** Names of the abilities registered with the current setting. */
	public static function names(): array {
		$mode = self::mode();
		if ( $mode === 'off' ) {
			return [];
		}
		$out = [];
		foreach ( self::definitions() as $name => $def ) {
			if ( $mode === 'all' || ! empty( $def['readonly'] ) ) {
				$out[] = self::CATEGORY . '/' . $name;
			}
		}
		return $out;
	}

	/** Who may run any ability: the same users who may use the plugin's screens. */
	public static function can_use(): bool {
		return WPWAF_Access::current_user_can();
	}

	/** Full wp_register_ability() arguments for one definition. */
	private static function args( array $def ): array {
		$readonly = ! empty( $def['readonly'] );
		// An object schema with a default, so a call without arguments ({} or
		// nothing) is valid. Empty "properties" and "required" are left out:
		// PHP would send them as JSON lists, not objects.
		$input = [
			'type'                 => 'object',
			'additionalProperties' => false,
			'default'              => [],
		];
		if ( ! empty( $def['input'] ) ) {
			$input['properties'] = $def['input'];
		}
		if ( ! empty( $def['required'] ) ) {
			$input['required'] = $def['required'];
		}
		return [
			'label'               => $def['label'],
			'description'         => $def['description'],
			'category'            => self::CATEGORY,
			'input_schema'        => $input,
			'output_schema'       => [
				'type'       => 'object',
				'properties' => $def['output'],
			],
			'execute_callback'    => [ __CLASS__, $def['callback'] ],
			'permission_callback' => [ __CLASS__, 'can_use' ],
			'meta'                => [
				'annotations' => [
					'readonly'    => $readonly,
					'destructive' => $readonly ? false : ( $def['destructive'] ?? true ),
					'idempotent'  => $readonly ? true : ( $def['idempotent'] ?? false ),
				],
				// Offered to the REST API and to MCP clients.
				'public'      => true,
			],
		];
	}

	// ── Definitions ───────────────────────────────────────────────────────────

	/** Input properties used by several abilities. */
	private static function common(): array {
		return [
			'account' => [
				'type'        => 'string',
				'description' => 'Plugin account ID from get-overview (accounts[].id). Leave out to use the active account.',
			],
			'zone'    => [
				'type'        => 'string',
				'minLength'   => 1,
				'description' => 'The zone: its Cloudflare zone ID (32 hex characters) or its domain name, such as example.com. A subdomain such as www.example.com finds example.com.',
			],
			'cloudflare_account_id' => [
				'type'        => 'string',
				'pattern'     => '^[a-fA-F0-9]{32}$',
				'description' => 'Cloudflare account ID (32 hex characters). Leave out to use the account of the zone given, or the first account the API token can see.',
			],
		];
	}

	private static function definitions(): array {
		$c          = self::common();
		$any        = [ 'string', 'integer', 'number', 'boolean', 'object', 'array', 'null' ];
		$objects    = [ 'type' => 'array', 'items' => [ 'type' => 'object' ] ];
		$zone_out   = [ 'type' => 'string', 'description' => 'Cloudflare zone ID.' ];
		$rules_desc = 'WAF rules as Cloudflare custom rules: description, expression, action and, for the Allow Good Bots skip rule, action_parameters.';

		return [
			// ── Read ─────────────────────────────────────────────────────────
			'get-overview'           => [
				'readonly'    => true,
				'label'       => 'Get WP WAF Manager overview',
				'description' => 'Start here. Returns the plugin version, the date of the bundled WAF rule data, the connected Cloudflare accounts (labels and IDs only, never credentials), the active account, the active Rule Profile, which of the five WAF rules are turned on, when zone analytics were last synced, and which abilities are available.',
				'callback'    => 'get_overview',
				'output'      => [
					'plugin_version' => [ 'type' => 'string' ],
					'rules_data_date' => [ 'type' => 'string', 'description' => 'Date (YYYY-MM-DD) of the service IP lists and rule data in this version.' ],
					'accounts'       => $objects,
					'active_account' => [ 'type' => 'string' ],
					'rule_profile'   => [ 'type' => 'object' ],
					'rules_enabled'  => [ 'type' => 'object', 'description' => 'rule1 to rule5: true when the rule is turned on.' ],
					'rule_names'     => [ 'type' => 'object' ],
					'zone_analytics' => [ 'type' => 'object' ],
					'abilities'      => [ 'type' => 'array', 'items' => [ 'type' => 'string' ] ],
				],
			],
			'list-zones'             => [
				'readonly'    => true,
				'label'       => 'List Cloudflare zones',
				'description' => 'Lists the active zones (domains) in a Cloudflare account, with each zone\'s ID, name, plan and Cloudflare account ID.',
				'callback'    => 'list_zones',
				'input'       => [ 'account' => $c['account'] ],
				'output'      => [
					'count' => [ 'type' => 'integer' ],
					'zones' => [
						'type'  => 'array',
						'items' => [
							'type'       => 'object',
							'properties' => [
								'id'         => [ 'type' => 'string' ],
								'name'       => [ 'type' => 'string' ],
								'plan'       => [ 'type' => 'string' ],
								'account_id' => [ 'type' => 'string' ],
							],
						],
					],
				],
			],
			'get-waf-rules'          => [
				'readonly'    => true,
				'label'       => 'Get WAF rule settings and expressions',
				'description' => 'Returns the saved WAF rule settings (rule1 to rule5 with all their options) and the five Cloudflare custom rules built from them, exactly as they would be deployed. Pass settings to preview a change without saving it; values not given keep their saved value. too_long names a rule that is over Cloudflare\'s 4,096-character limit.',
				'callback'    => 'get_waf_rules',
				'input'       => [
					'settings' => [
						'type'        => 'object',
						'description' => 'Optional changes to preview, in the same shape as the returned settings, for example {"rule5": {"enabled": false}}.',
					],
				],
				'output'      => [
					'settings' => [ 'type' => 'object' ],
					'rules'    => $objects + [ 'description' => $rules_desc ],
					'too_long' => [ 'type' => [ 'string', 'null' ] ],
				],
			],
			'get-zone-rules'         => [
				'readonly'    => true,
				'label'       => 'Get a zone\'s deployed custom rules',
				'description' => 'Lists the WAF custom rules deployed on a Cloudflare zone, in order. managed_by_plugin is true for the rules WP WAF Manager deploys; the others were made elsewhere and are kept when rules are deployed.',
				'callback'    => 'get_zone_rules',
				'input'       => [ 'zone' => $c['zone'], 'account' => $c['account'] ],
				'required'    => [ 'zone' ],
				'output'      => [
					'zone_id'      => $zone_out,
					'ruleset_id'   => [ 'type' => [ 'string', 'null' ] ],
					'plugin_rules' => [ 'type' => 'integer' ],
					'rules'        => $objects,
				],
			],
			'get-zone-settings'      => [
				'readonly'    => true,
				'label'       => 'Get a zone\'s settings',
				'description' => 'Reads a zone\'s security level (under_attack means I\'m Under Attack mode), development mode, SSL mode, Always Use HTTPS, cache level, browser cache TTL, Rocket Loader, hotlink protection, email obfuscation, Smart Tiered Cache and Managed Transforms.',
				'callback'    => 'get_zone_settings',
				'input'       => [ 'zone' => $c['zone'], 'account' => $c['account'] ],
				'required'    => [ 'zone' ],
				'output'      => [
					'zone_id'            => $zone_out,
					'settings'           => [ 'type' => 'object' ],
					'smart_tiered_cache' => [ 'type' => [ 'string', 'null' ] ],
					'managed_transforms' => [ 'type' => [ 'object', 'null' ] ],
					'errors'             => [ 'type' => 'object', 'description' => 'Settings that could not be read, with the reason (often a missing API token permission).' ],
				],
			],
			'get-zone-analytics'     => [
				'readonly'    => true,
				'label'       => 'Get zone traffic analytics',
				'description' => 'Returns the traffic totals saved by the last Zone Status sync for each zone: requests, bandwidth in bytes, page views, threats and cache rate (percent), over the number of days set in Zone Status. Run sync-zone-analytics first for fresh numbers.',
				'callback'    => 'get_zone_analytics',
				'input'       => [ 'zone' => array_merge( $c['zone'], [ 'description' => 'Optional: one zone (ID or domain name). Leave out for every synced zone.' ] ) ],
				'output'      => [
					'days'      => [ 'type' => 'integer' ],
					'next_sync' => [ 'type' => 'integer', 'description' => 'Unix time of the next automatic sync, 0 when none is scheduled.' ],
					'zones'     => $objects,
				],
			],
			'get-security-events'    => [
				'readonly'    => true,
				'label'       => 'Get recent security events',
				'description' => 'Lists recent firewall events on a zone (newest first): action, client IP, country, ASN, host, method, path, user agent, the rule or source that acted, and the Ray ID. Needs a Cloudflare Pro plan or higher for the zone.',
				'callback'    => 'get_security_events',
				'input'       => [
					'zone'    => $c['zone'],
					'account' => $c['account'],
					'hours'   => [ 'type' => 'integer', 'minimum' => 1, 'maximum' => 720, 'default' => 24, 'description' => 'How far back to look, in hours.' ],
					'limit'   => [ 'type' => 'integer', 'minimum' => 10, 'maximum' => 500, 'default' => 100 ],
					'action'  => [ 'type' => 'string', 'description' => 'Only events with this action, such as block, managed_challenge or skip.' ],
				],
				'required'    => [ 'zone' ],
				'output'      => [
					'zone_id' => $zone_out,
					'hours'   => [ 'type' => 'integer' ],
					'count'   => [ 'type' => 'integer' ],
					'events'  => $objects,
				],
			],
			'list-ip-access-rules'   => [
				'readonly'    => true,
				'label'       => 'List IP Access Rules',
				'description' => 'Lists the account-level IP Access Rules (allow, block or challenge by IP, IP range, country or ASN) that apply to every zone in the Cloudflare account.',
				'callback'    => 'list_ip_access_rules',
				'input'       => [
					'account'               => $c['account'],
					'cloudflare_account_id' => $c['cloudflare_account_id'],
					'zone'                  => array_merge( $c['zone'], [ 'description' => 'Optional: a zone in the Cloudflare account, used to find the account.' ] ),
					'mode'                  => [ 'type' => 'string', 'enum' => WPWAF_API::IP_RULE_MODES, 'description' => 'Only rules with this action (whitelist means allow).' ],
				],
				'output'      => [
					'cloudflare_account_id' => [ 'type' => 'string' ],
					'count'                 => [ 'type' => 'integer' ],
					'rules'                 => $objects,
				],
			],
			'list-dns-records'       => [
				'readonly'    => true,
				'label'       => 'List DNS records',
				'description' => 'Lists a zone\'s DNS records: type, name, content, whether the record is proxied through Cloudflare, and TTL.',
				'callback'    => 'list_dns_records',
				'input'       => [ 'zone' => $c['zone'], 'account' => $c['account'] ],
				'required'    => [ 'zone' ],
				'output'      => [
					'zone_id' => $zone_out,
					'count'   => [ 'type' => 'integer' ],
					'records' => $objects,
				],
			],

			// ── Change ───────────────────────────────────────────────────────
			'deploy-waf-rules'       => [
				'label'       => 'Deploy WAF rules to zones',
				'description' => 'Deploys the saved WAF rules (see get-waf-rules) to one or more zones. The plugin\'s rules are updated in place and other custom rules in the zone are kept after them, unless replace_all is true, which removes every other custom rule in the zone. Returns the result for each zone.',
				'callback'    => 'deploy_waf_rules',
				'destructive' => true,
				'idempotent'  => true,
				'input'       => [
					'zones'       => [
						'type'        => 'array',
						'items'       => [ 'type' => 'string', 'minLength' => 1 ],
						'minItems'    => 1,
						'maxItems'    => 100,
						'description' => 'Zones to deploy to: zone IDs or domain names.',
					],
					'replace_all' => [ 'type' => 'boolean', 'default' => false, 'description' => 'Remove every other custom rule in each zone. Leave false to keep them.' ],
					'account'     => $c['account'],
				],
				'required'    => [ 'zones' ],
				'output'      => [
					'rule_count'    => [ 'type' => 'integer' ],
					'success_count' => [ 'type' => 'integer' ],
					'fail_count'    => [ 'type' => 'integer' ],
					'results'       => $objects,
				],
			],
			'update-waf-rule-settings' => [
				'label'       => 'Change WAF rule settings',
				'description' => 'Changes the saved WAF rule settings and the active Rule Profile. Give only what changes, in the shape get-waf-rules returns, for example {"rule4": {"enabled": true}}; each rule given is merged over its saved options and everything is validated. Nothing changes in Cloudflare until deploy-waf-rules runs.',
				'callback'    => 'update_waf_rule_settings',
				'destructive' => true,
				'idempotent'  => true,
				'input'       => [
					'settings' => [
						'type'        => 'object',
						'description' => 'rule1 to rule5, each an object with only the options to change.',
					],
				],
				'required'    => [ 'settings' ],
				'output'      => [
					'settings' => [ 'type' => 'object' ],
					'rules'    => $objects + [ 'description' => $rules_desc ],
					'too_long' => [ 'type' => [ 'string', 'null' ] ],
				],
			],
			'purge-cache'            => [
				'label'       => 'Purge Cloudflare cache',
				'description' => 'Purges a zone\'s Cloudflare cache: everything, or up to 100 URLs, hostnames, prefixes (host and path, such as www.example.com/blog/) or cache tags.',
				'callback'    => 'purge_cache',
				'destructive' => true,
				'idempotent'  => true,
				'input'       => [
					'zone'    => $c['zone'],
					'account' => $c['account'],
					'type'    => [ 'type' => 'string', 'enum' => WPWAF_API::PURGE_MODES, 'default' => 'all', 'description' => 'all purges everything; the others purge only the items given.' ],
					'items'   => [ 'type' => 'array', 'items' => [ 'type' => 'string' ], 'maxItems' => 100, 'description' => 'URLs, hostnames, prefixes or tags, for every type except all.' ],
				],
				'required'    => [ 'zone' ],
				'output'      => [
					'zone_id' => $zone_out,
					'type'    => [ 'type' => 'string' ],
					'count'   => [ 'type' => 'integer', 'description' => 'Items purged; 0 when everything was purged.' ],
				],
			],
			'update-zone-setting'    => [
				'label'       => 'Change a zone setting',
				'description' => 'Changes one zone setting. Values: security_level under_attack (I\'m Under Attack mode) or medium; development_mode, always_use_https, rocket_loader, always_online, hotlink_protection, email_obfuscation and browser_check on or off; ssl off, flexible, full or strict; cache_level aggressive, basic or simplified; browser_cache_ttl and challenge_ttl a number of seconds.',
				'callback'    => 'update_zone_setting',
				'destructive' => true,
				'idempotent'  => true,
				'input'       => [
					'zone'    => $c['zone'],
					'account' => $c['account'],
					'setting' => [ 'type' => 'string', 'enum' => array_keys( WPWAF_API::EDITABLE_ZONE_SETTINGS ) ],
					'value'   => [ 'type' => [ 'string', 'integer' ] ],
				],
				'required'    => [ 'zone', 'setting', 'value' ],
				'output'      => [
					'zone_id' => $zone_out,
					'setting' => [ 'type' => 'string' ],
					'value'   => [ 'type' => $any ],
				],
			],
			'create-ip-access-rule'  => [
				'label'       => 'Add an IP Access Rule',
				'description' => 'Adds an account-level IP Access Rule that applies to every zone in the Cloudflare account: allow (whitelist), block or challenge an IP address, IP range (CIDR), country (two-letter code) or ASN.',
				'callback'    => 'create_ip_access_rule',
				'destructive' => false,
				'idempotent'  => false,
				'input'       => [
					'mode'                  => [ 'type' => 'string', 'enum' => WPWAF_API::IP_RULE_MODES ],
					'target'                => [ 'type' => 'string', 'enum' => WPWAF_API::IP_RULE_TARGETS ],
					'value'                 => [ 'type' => 'string', 'minLength' => 1, 'description' => 'The IP address, CIDR range, two-letter country code or ASN (such as AS13335).' ],
					'note'                  => [ 'type' => 'string', 'description' => 'Why the rule exists; shown in Cloudflare.' ],
					'account'               => $c['account'],
					'cloudflare_account_id' => $c['cloudflare_account_id'],
					'zone'                  => array_merge( $c['zone'], [ 'description' => 'Optional: a zone in the Cloudflare account, used to find the account.' ] ),
				],
				'required'    => [ 'mode', 'target', 'value' ],
				'output'      => [
					'cloudflare_account_id' => [ 'type' => 'string' ],
					'rule'                  => [ 'type' => 'object' ],
				],
			],
			'delete-ip-access-rule'  => [
				'label'       => 'Remove an IP Access Rule',
				'description' => 'Removes an account-level IP Access Rule by its ID (from list-ip-access-rules).',
				'callback'    => 'delete_ip_access_rule',
				'destructive' => true,
				'idempotent'  => true,
				'input'       => [
					'rule_id'               => [ 'type' => 'string', 'pattern' => '^[a-fA-F0-9]{32}$' ],
					'account'               => $c['account'],
					'cloudflare_account_id' => $c['cloudflare_account_id'],
					'zone'                  => array_merge( $c['zone'], [ 'description' => 'Optional: a zone in the Cloudflare account, used to find the account.' ] ),
				],
				'required'    => [ 'rule_id' ],
				'output'      => [
					'cloudflare_account_id' => [ 'type' => 'string' ],
					'rule_id'               => [ 'type' => 'string' ],
					'deleted'               => [ 'type' => 'boolean' ],
				],
			],
			'sync-zone-analytics'    => [
				'label'       => 'Refresh zone analytics',
				'description' => 'Fetches fresh traffic totals from Cloudflare for the zones chosen in Zone Status (active account), as the Sync button does. Read them with get-zone-analytics.',
				'callback'    => 'sync_zone_analytics',
				'destructive' => false,
				'idempotent'  => true,
				'output'      => [
					'zones'     => [ 'type' => 'integer' ],
					'synced_at' => [ 'type' => 'integer' ],
					'next_sync' => [ 'type' => 'integer' ],
				],
			],
		];
	}

	// ── Helpers ───────────────────────────────────────────────────────────────

	private static function error( string $code, string $message, int $status = 400 ): WP_Error {
		return new WP_Error( 'wpwaf_' . $code, $message, [ 'status' => $status ] );
	}

	/** An error for a failed Cloudflare call. */
	private static function cf_error( array $result, string $fallback = 'Cloudflare request failed.' ): WP_Error {
		return self::error( 'cloudflare', (string) ( $result['message'] ?? $fallback ), 502 );
	}

	/**
	 * The Cloudflare client for the account asked for (a plugin account ID),
	 * or the active account.
	 *
	 * @return WPWAF_API|WP_Error
	 */
	private static function api( array $input ) {
		$id = isset( $input['account'] ) ? sanitize_text_field( (string) $input['account'] ) : '';
		if ( $id !== '' ) {
			$api = WPWAF_Accounts::api_for( $id );
			return $api ?? self::error( 'unknown_account', "No connected account with ID '{$id}' (or it has expired). get-overview lists the accounts.", 404 );
		}
		if ( ! WPWAF_Accounts::active() ) {
			return self::error( 'no_account', 'No Cloudflare account is connected. Connect one on the WAF Rules screen first.', 409 );
		}
		return WPWAF_Accounts::api();
	}

	/**
	 * The account's zones, fetched once per request.
	 *
	 * @return array|WP_Error
	 */
	private static function zones( WPWAF_API $api, array $input ) {
		$key = (string) ( $input['account'] ?? '' );
		if ( ! isset( self::$zones[ $key ] ) ) {
			$res = $api->list_zones();
			if ( empty( $res['success'] ) ) {
				return self::cf_error( $res, 'Could not load zones.' );
			}
			self::$zones[ $key ] = (array) $res['zones'];
		}
		return self::$zones[ $key ];
	}

	/**
	 * A zone ID from a zone ID or a domain name. A subdomain matches its zone
	 * (www.example.com finds example.com; the longest matching zone wins).
	 *
	 * @return string|WP_Error
	 */
	private static function zone_id( WPWAF_API $api, array $input, string $zone ) {
		$zone = strtolower( trim( $zone ) );
		if ( preg_match( '/^[a-f0-9]{32}$/', $zone ) ) {
			return $zone;
		}
		$host = rtrim( explode( '/', (string) preg_replace( '#^[a-z][a-z0-9+.-]*://#', '', $zone ) )[0], '.' );
		if ( $host === '' ) {
			return self::error( 'zone_not_found', 'Give a zone ID or a domain name.' );
		}
		$zones = self::zones( $api, $input );
		if ( is_wp_error( $zones ) ) {
			return $zones;
		}
		$best = null;
		foreach ( $zones as $z ) {
			$name = strtolower( (string) ( $z['name'] ?? '' ) );
			if ( $name !== '' && ( $host === $name || str_ends_with( $host, '.' . $name ) ) ) {
				if ( $best === null || strlen( $name ) > strlen( (string) $best['name'] ) ) {
					$best = $z;
				}
			}
		}
		if ( $best === null ) {
			return self::error( 'zone_not_found', "No active zone for '{$host}' in this Cloudflare account. list-zones shows the zones.", 404 );
		}
		return (string) $best['id'];
	}

	/**
	 * Client and zone ID for abilities that act on one zone.
	 *
	 * @return array{0: WPWAF_API, 1: string}|WP_Error
	 */
	private static function api_and_zone( array $input ) {
		$api = self::api( $input );
		if ( is_wp_error( $api ) ) {
			return $api;
		}
		$zone_id = self::zone_id( $api, $input, (string) ( $input['zone'] ?? '' ) );
		return is_wp_error( $zone_id ) ? $zone_id : [ $api, $zone_id ];
	}

	/**
	 * The Cloudflare account ID for IP Access Rules: given, from the zone given,
	 * or the first account the token can see.
	 *
	 * @return string|WP_Error
	 */
	private static function cf_account_id( WPWAF_API $api, array $input ) {
		$id = strtolower( (string) ( $input['cloudflare_account_id'] ?? '' ) );
		if ( $id === '' && ! empty( $input['zone'] ) ) {
			$zone_id = self::zone_id( $api, $input, (string) $input['zone'] );
			if ( is_wp_error( $zone_id ) ) {
				return $zone_id;
			}
			$id = $api->get_account_id_from_zone( $zone_id );
		}
		if ( $id === '' ) {
			$id = $api->get_account_id();
		}
		if ( ! preg_match( '/^[a-f0-9]{32}$/', $id ) ) {
			return self::error( 'no_cf_account', 'Could not find the Cloudflare account. Give cloudflare_account_id, or a zone in the account.' );
		}
		return $id;
	}

	/** Saved settings with the given rule options merged over them. */
	private static function merged_settings( $changes ): array {
		$settings = WPWAF_Rule_Builder::saved_settings();
		foreach ( (array) $changes as $rule => $options ) {
			if ( isset( $settings[ $rule ] ) && is_array( $settings[ $rule ] ) && is_array( $options ) ) {
				$settings[ $rule ] = array_merge( $settings[ $rule ], $options );
			}
		}
		return WPWAF_Rule_Builder::sanitize_settings( $settings );
	}

	// ── Read ──────────────────────────────────────────────────────────────────

	public static function get_overview(): array {
		$settings = WPWAF_Rule_Builder::saved_settings();
		$enabled  = [];
		foreach ( array_keys( WPWAF_Rule_Builder::NAMES ) as $rule ) {
			$enabled[ $rule ] = ! empty( $settings[ $rule ]['enabled'] );
		}
		$profiles  = WPWAF_Profiles::all();
		$profile   = WPWAF_Profiles::active_id();
		$cache     = WPWAF_Zone_Status::get_cache();
		$synced_at = 0;
		foreach ( $cache as $entry ) {
			$synced_at = max( $synced_at, (int) ( $entry['synced_at'] ?? 0 ) );
		}
		return [
			'plugin_version'  => WPWAF_VERSION,
			'rules_data_date' => WPWAF_Service_IPs::RULES_DATE,
			'accounts'        => array_map(
				static fn( array $a ): array => [
					'id'         => $a['id'],
					'label'      => $a['label'],
					'expires_at' => $a['expires_at'],
				],
				WPWAF_Accounts::for_js()
			),
			'active_account'  => WPWAF_Accounts::active_id(),
			'rule_profile'    => [
				'id'   => $profile,
				'name' => (string) ( $profiles[ $profile ]['name'] ?? $profile ),
			],
			'rules_enabled'   => $enabled,
			'rule_names'      => WPWAF_Rule_Builder::NAMES,
			'zone_analytics'  => [
				'zones'     => count( $cache ),
				'synced_at' => $synced_at,
				'next_sync' => WPWAF_Zone_Status::get_next_sync(),
			],
			'abilities'       => self::names(),
		];
	}

	/** @return array|WP_Error */
	public static function list_zones( $input = [] ) {
		$input = (array) $input;
		$api   = self::api( $input );
		if ( is_wp_error( $api ) ) {
			return $api;
		}
		$zones = self::zones( $api, $input );
		if ( is_wp_error( $zones ) ) {
			return $zones;
		}
		return [ 'count' => count( $zones ), 'zones' => array_values( $zones ) ];
	}

	public static function get_waf_rules( $input = [] ): array {
		$input    = (array) $input;
		$settings = self::merged_settings( $input['settings'] ?? [] );
		$rules    = WPWAF_Rule_Builder::build_rules( $settings );
		return [
			'settings' => $settings,
			'rules'    => $rules,
			'too_long' => WPWAF_Rule_Builder::too_long( $rules ),
		];
	}

	/** @return array|WP_Error */
	public static function get_zone_rules( $input = [] ) {
		$ctx = self::api_and_zone( (array) $input );
		if ( is_wp_error( $ctx ) ) {
			return $ctx;
		}
		[ $api, $zone_id ] = $ctx;
		$res = $api->list_rules( $zone_id );
		if ( empty( $res['success'] ) ) {
			return self::cf_error( $res );
		}
		$rules = [];
		$ours  = 0;
		foreach ( (array) $res['rules'] as $rule ) {
			$rule                      = (array) $rule;
			$rule['managed_by_plugin'] = WPWAF_Rule_Builder::is_plugin_rule( (string) ( $rule['description'] ?? '' ) );
			$ours                     += $rule['managed_by_plugin'] ? 1 : 0;
			$rules[]                   = $rule;
		}
		return [
			'zone_id'      => $zone_id,
			'ruleset_id'   => isset( $res['ruleset_id'] ) ? (string) $res['ruleset_id'] : null,
			'plugin_rules' => $ours,
			'rules'        => $rules,
		];
	}

	/** @return array|WP_Error */
	public static function get_zone_settings( $input = [] ) {
		$ctx = self::api_and_zone( (array) $input );
		if ( is_wp_error( $ctx ) ) {
			return $ctx;
		}
		[ $api, $zone_id ] = $ctx;
		$res = $api->get_zone_controls( $zone_id, WPWAF_API::ZONE_CONTROL_SETTINGS );
		if ( empty( $res['success'] ) ) {
			return self::cf_error( $res );
		}
		return [
			'zone_id'            => $zone_id,
			'settings'           => (array) $res['settings'],
			'smart_tiered_cache' => $res['tiered'],
			'managed_transforms' => $res['managed'],
			'errors'             => (array) $res['errors'],
		];
	}

	/** @return array|WP_Error */
	public static function get_zone_analytics( $input = [] ) {
		$input    = (array) $input;
		$cache    = WPWAF_Zone_Status::get_cache();
		$settings = WPWAF_Zone_Status::get_settings();
		$only     = '';
		if ( ! empty( $input['zone'] ) ) {
			$want = strtolower( trim( (string) $input['zone'] ) );
			foreach ( $cache as $id => $entry ) {
				$name = strtolower( (string) ( $entry['zone']['name'] ?? '' ) );
				if ( $want === (string) $id || ( $name !== '' && ( $want === $name || str_ends_with( $want, '.' . $name ) ) ) ) {
					$only = (string) $id;
					break;
				}
			}
			if ( $only === '' ) {
				return self::error( 'zone_not_found', 'That zone has no synced analytics. Zone Status shows which zones are synced; sync-zone-analytics refreshes them.', 404 );
			}
		}
		$zones = [];
		foreach ( $cache as $id => $entry ) {
			if ( $only !== '' && (string) $id !== $only ) {
				continue;
			}
			$a       = (array) ( $entry['overview']['analytics'] ?? [] );
			$zones[] = [
				'zone_id'    => (string) $id,
				'name'       => (string) ( $entry['zone']['name'] ?? '' ),
				'plan'       => (string) ( $entry['zone']['plan'] ?? '' ),
				'synced_at'  => (int) ( $entry['synced_at'] ?? 0 ),
				'requests'   => (int) ( $a['requests'] ?? 0 ),
				'bandwidth'  => (int) ( $a['bandwidth'] ?? 0 ),
				'page_views' => (int) ( $a['pageviews'] ?? 0 ),
				'threats'    => (int) ( $a['threats'] ?? 0 ),
				'cache_rate' => (float) ( $a['cache_rate'] ?? 0 ),
			];
		}
		return [
			'days'      => (int) ( $settings['days_analytics'] ?? 7 ),
			'next_sync' => WPWAF_Zone_Status::get_next_sync(),
			'zones'     => $zones,
		];
	}

	/** @return array|WP_Error */
	public static function get_security_events( $input = [] ) {
		$input = (array) $input;
		$ctx   = self::api_and_zone( $input );
		if ( is_wp_error( $ctx ) ) {
			return $ctx;
		}
		[ $api, $zone_id ] = $ctx;
		$hours = min( 720, max( 1, (int) ( $input['hours'] ?? 24 ) ) );
		$limit = min( 500, max( 10, (int) ( $input['limit'] ?? 100 ) ) );
		$res   = $api->get_security_events( $zone_id, $limit, sanitize_key( (string) ( $input['action'] ?? '' ) ), $hours );
		if ( empty( $res['success'] ) ) {
			return self::cf_error( $res );
		}
		return [
			'zone_id' => $zone_id,
			'hours'   => $hours,
			'count'   => (int) $res['count'],
			'events'  => array_values( (array) $res['events'] ),
		];
	}

	/** @return array|WP_Error */
	public static function list_ip_access_rules( $input = [] ) {
		$input = (array) $input;
		$api   = self::api( $input );
		if ( is_wp_error( $api ) ) {
			return $api;
		}
		$account_id = self::cf_account_id( $api, $input );
		if ( is_wp_error( $account_id ) ) {
			return $account_id;
		}
		$mode = (string) ( $input['mode'] ?? '' );
		$res  = $api->list_ip_rules( $account_id, in_array( $mode, WPWAF_API::IP_RULE_MODES, true ) ? $mode : '' );
		if ( empty( $res['success'] ) ) {
			return self::cf_error( $res );
		}
		$rules = array_map(
			static fn( $r ): array => [
				'id'         => (string) ( $r['id'] ?? '' ),
				'mode'       => (string) ( $r['mode'] ?? '' ),
				'target'     => (string) ( $r['configuration']['target'] ?? '' ),
				'value'      => (string) ( $r['configuration']['value'] ?? '' ),
				'notes'      => (string) ( $r['notes'] ?? '' ),
				'created_on' => (string) ( $r['created_on'] ?? '' ),
			],
			(array) $res['rules']
		);
		return [
			'cloudflare_account_id' => $account_id,
			'count'                 => count( $rules ),
			'rules'                 => $rules,
		];
	}

	/** @return array|WP_Error */
	public static function list_dns_records( $input = [] ) {
		$ctx = self::api_and_zone( (array) $input );
		if ( is_wp_error( $ctx ) ) {
			return $ctx;
		}
		[ $api, $zone_id ] = $ctx;
		$res = $api->list_dns_records( $zone_id );
		if ( empty( $res['success'] ) ) {
			return self::cf_error( $res );
		}
		$records = array_map(
			static fn( $r ): array => [
				'id'      => (string) ( $r['id'] ?? '' ),
				'type'    => (string) ( $r['type'] ?? '' ),
				'name'    => (string) ( $r['name'] ?? '' ),
				'content' => (string) ( $r['content'] ?? '' ),
				'proxied' => ! empty( $r['proxied'] ),
				'ttl'     => (int) ( $r['ttl'] ?? 1 ),
			],
			(array) $res['records']
		);
		return [ 'zone_id' => $zone_id, 'count' => count( $records ), 'records' => $records ];
	}

	// ── Change ────────────────────────────────────────────────────────────────

	/** @return array|WP_Error */
	public static function deploy_waf_rules( $input = [] ) {
		$input = (array) $input;
		$api   = self::api( $input );
		if ( is_wp_error( $api ) ) {
			return $api;
		}
		$ids = [];
		foreach ( (array) ( $input['zones'] ?? [] ) as $zone ) {
			$id = self::zone_id( $api, $input, (string) $zone );
			if ( is_wp_error( $id ) ) {
				return $id;
			}
			$ids[ $id ] = $id;
		}
		$result = WPWAF_Rule_Builder::deploy( $api, array_values( $ids ), WPWAF_Rule_Builder::saved_settings(), ! empty( $input['replace_all'] ) );
		if ( is_wp_error( $result ) ) {
			$result->add_data( [ 'status' => 400 ] );
			return $result;
		}
		$results = [];
		foreach ( $result['results'] as $zone_id => $r ) {
			$results[] = [
				'zone_id' => (string) $zone_id,
				'success' => ! empty( $r['success'] ),
				'message' => (string) ( $r['message'] ?? '' ),
			];
		}
		$result['results'] = $results;
		return $result;
	}

	public static function update_waf_rule_settings( $input = [] ): array {
		$input    = (array) $input;
		$settings = WPWAF_Rule_Builder::save( self::merged_settings( $input['settings'] ?? [] ), WPWAF_Profiles::active_id() );
		$rules    = WPWAF_Rule_Builder::build_rules( $settings );
		return [
			'settings' => $settings,
			'rules'    => $rules,
			'too_long' => WPWAF_Rule_Builder::too_long( $rules ),
		];
	}

	/** @return array|WP_Error */
	public static function purge_cache( $input = [] ) {
		$input = (array) $input;
		$ctx   = self::api_and_zone( $input );
		if ( is_wp_error( $ctx ) ) {
			return $ctx;
		}
		[ $api, $zone_id ] = $ctx;
		$type = (string) ( $input['type'] ?? 'all' );
		$data = WPWAF_API::purge_request( $type, array_map( 'strval', (array) ( $input['items'] ?? [] ) ) );
		if ( is_wp_error( $data ) ) {
			$data->add_data( [ 'status' => 400 ] );
			return $data;
		}
		$res = $api->purge_cache( $zone_id, $data );
		if ( empty( $res['success'] ) ) {
			return self::cf_error( $res );
		}
		return [
			'zone_id' => $zone_id,
			'type'    => $type,
			'count'   => isset( $data['purge_everything'] ) ? 0 : count( (array) reset( $data ) ),
		];
	}

	/** @return array|WP_Error */
	public static function update_zone_setting( $input = [] ) {
		$input = (array) $input;
		$key   = sanitize_key( (string) ( $input['setting'] ?? '' ) );
		$value = WPWAF_API::zone_setting_value( $key, sanitize_text_field( (string) ( $input['value'] ?? '' ) ) );
		if ( is_wp_error( $value ) ) {
			$value->add_data( [ 'status' => 400 ] );
			return $value;
		}
		$ctx = self::api_and_zone( $input );
		if ( is_wp_error( $ctx ) ) {
			return $ctx;
		}
		[ $api, $zone_id ] = $ctx;
		$res = $api->update_zone_setting_direct( $zone_id, $key, $value );
		if ( empty( $res['success'] ) ) {
			return self::cf_error( $res );
		}
		return [ 'zone_id' => $zone_id, 'setting' => $key, 'value' => $value ];
	}

	/** @return array|WP_Error */
	public static function create_ip_access_rule( $input = [] ) {
		$input  = (array) $input;
		$mode   = (string) ( $input['mode'] ?? '' );
		$target = (string) ( $input['target'] ?? '' );
		$value  = sanitize_text_field( (string) ( $input['value'] ?? '' ) );
		if ( ! in_array( $mode, WPWAF_API::IP_RULE_MODES, true ) || ! in_array( $target, WPWAF_API::IP_RULE_TARGETS, true ) || $value === '' ) {
			return self::error( 'invalid_rule', 'mode, target and value are required.' );
		}
		$api = self::api( $input );
		if ( is_wp_error( $api ) ) {
			return $api;
		}
		$account_id = self::cf_account_id( $api, $input );
		if ( is_wp_error( $account_id ) ) {
			return $account_id;
		}
		$res = $api->create_ip_rule(
			$account_id,
			[
				'mode'          => $mode,
				'configuration' => [ 'target' => $target, 'value' => $value ],
				'notes'         => sanitize_text_field( (string) ( $input['note'] ?? '' ) ),
			]
		);
		if ( empty( $res['success'] ) ) {
			return self::cf_error( $res );
		}
		return [ 'cloudflare_account_id' => $account_id, 'rule' => (array) $res['rule'] ];
	}

	/** @return array|WP_Error */
	public static function delete_ip_access_rule( $input = [] ) {
		$input   = (array) $input;
		$rule_id = strtolower( (string) ( $input['rule_id'] ?? '' ) );
		if ( ! preg_match( '/^[a-f0-9]{32}$/', $rule_id ) ) {
			return self::error( 'invalid_rule', 'rule_id must be the rule\'s 32-character ID from list-ip-access-rules.' );
		}
		$api = self::api( $input );
		if ( is_wp_error( $api ) ) {
			return $api;
		}
		$account_id = self::cf_account_id( $api, $input );
		if ( is_wp_error( $account_id ) ) {
			return $account_id;
		}
		$res = $api->delete_ip_rule( $account_id, $rule_id );
		if ( empty( $res['success'] ) ) {
			return self::cf_error( $res );
		}
		return [ 'cloudflare_account_id' => $account_id, 'rule_id' => $rule_id, 'deleted' => true ];
	}

	/** @return array|WP_Error */
	public static function sync_zone_analytics() {
		if ( ! WPWAF_Accounts::active() ) {
			return self::error( 'no_account', 'No Cloudflare account is connected. Connect one on the WAF Rules screen first.', 409 );
		}
		$res = WPWAF_Zone_Status::sync();
		if ( empty( $res['success'] ) ) {
			return self::cf_error( $res, 'Sync failed.' );
		}
		return [
			'zones'     => (int) ( $res['zones'] ?? 0 ),
			'synced_at' => time(),
			'next_sync' => WPWAF_Zone_Status::get_next_sync(),
		];
	}
}
