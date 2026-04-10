<?php
declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Registers and handles all wp_ajax_* actions for the plugin.
 * Every public handler calls $this->check() first, which verifies the nonce
 * and confirms the user has manage_options capability.
 */
class WPWAF_Ajax {

	public static function init(): void {
		$self = new self();

		// Credentials & zones
		add_action( 'wp_ajax_wpwaf_verify_credentials',   [ $self, 'verify_credentials' ] );
		add_action( 'wp_ajax_wpwaf_list_zones',           [ $self, 'list_zones' ] );
		add_action( 'wp_ajax_wpwaf_switch_account',       [ $self, 'switch_account' ] );
		add_action( 'wp_ajax_wpwaf_delete_account',       [ $self, 'delete_account' ] );

		// WAF rules
		add_action( 'wp_ajax_wpwaf_deploy_rules',         [ $self, 'deploy_rules' ] );
		add_action( 'wp_ajax_wpwaf_list_zone_rules',      [ $self, 'list_zone_rules' ] );
		add_action( 'wp_ajax_wpwaf_save_settings',        [ $self, 'save_settings' ] );
		add_action( 'wp_ajax_wpwaf_preview_rules',        [ $self, 'preview_rules' ] );

		// DNS
		add_action( 'wp_ajax_wpwaf_dns_list',             [ $self, 'dns_list' ] );
		add_action( 'wp_ajax_wpwaf_dns_create',           [ $self, 'dns_create' ] );
		add_action( 'wp_ajax_wpwaf_dns_update',           [ $self, 'dns_update' ] );
		add_action( 'wp_ajax_wpwaf_dns_delete',           [ $self, 'dns_delete' ] );
		add_action( 'wp_ajax_wpwaf_dns_toggle_proxy',     [ $self, 'dns_toggle_proxy' ] );

		// Plugin settings
		add_action( 'wp_ajax_wpwaf_save_plugin_settings', [ $self, 'save_plugin_settings' ] );
		add_action( 'wp_ajax_wpwaf_load_zones_for_settings', [ $self, 'load_zones_for_settings' ] );

		// Zone status
		add_action( 'wp_ajax_wpwaf_zone_status_sync',     [ $self, 'zone_status_sync' ] );
		add_action( 'wp_ajax_wpwaf_zone_status_settings', [ $self, 'zone_status_save_settings' ] );

		// Zone controls
		add_action( 'wp_ajax_wpwaf_purge_cache',          [ $self, 'purge_cache' ] );
		add_action( 'wp_ajax_wpwaf_zone_settings_load',   [ $self, 'zone_settings_load' ] );
		add_action( 'wp_ajax_wpwaf_zone_setting_update',  [ $self, 'zone_setting_update' ] );

		// Security events
		add_action( 'wp_ajax_wpwaf_security_events',      [ $self, 'security_events' ] );

		// Email routing
		add_action( 'wp_ajax_wpwaf_email_addresses_list',   [ $self, 'email_addresses_list' ] );
		add_action( 'wp_ajax_wpwaf_email_address_create',   [ $self, 'email_address_create' ] );
		add_action( 'wp_ajax_wpwaf_catch_all_get',          [ $self, 'catch_all_get' ] );
		add_action( 'wp_ajax_wpwaf_catch_all_update',        [ $self, 'catch_all_update' ] );
		add_action( 'wp_ajax_wpwaf_email_routing_get',     [ $self, 'email_routing_get' ] );
		add_action( 'wp_ajax_wpwaf_email_routing_toggle',  [ $self, 'email_routing_toggle' ] );
		add_action( 'wp_ajax_wpwaf_email_rules_list',      [ $self, 'email_rules_list' ] );
		add_action( 'wp_ajax_wpwaf_email_rule_create',     [ $self, 'email_rule_create' ] );
		add_action( 'wp_ajax_wpwaf_email_rule_update',     [ $self, 'email_rule_update' ] );
		add_action( 'wp_ajax_wpwaf_email_rule_delete',     [ $self, 'email_rule_delete' ] );
		add_action( 'wp_ajax_wpwaf_test_connection',       [ $self, 'test_connection' ] );

		// Account IP access rules
		add_action( 'wp_ajax_wpwaf_ip_rules_list',        [ $self, 'ip_rules_list' ] );
		add_action( 'wp_ajax_wpwaf_ip_rules_create',      [ $self, 'ip_rules_create' ] );
		add_action( 'wp_ajax_wpwaf_ip_rules_update',      [ $self, 'ip_rules_update' ] );
		add_action( 'wp_ajax_wpwaf_ip_rules_delete',      [ $self, 'ip_rules_delete' ] );
		add_action( 'wp_ajax_wpwaf_get_account_id',       [ $self, 'get_account_id' ] );
	}

	// ── Security gate ──────────────────────────────────────────────────────────

	private function check(): void {
		check_ajax_referer( 'wpwaf_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Insufficient permissions.' ], 403 );
			wp_die();
		}
	}

	/** Die immediately without any output if the check fails. */
	private function require_active_account(): void {
		if ( ! WPWAF_Accounts::active() ) {
			wp_send_json_error( [ 'message' => 'No Cloudflare account connected.' ], 400 );
			wp_die();
		}
	}

	// ── Account handlers ───────────────────────────────────────────────────────

	public function verify_credentials(): void {
		$this->check();

		$method     = sanitize_text_field( $_POST['auth_method'] ?? 'token' );
		$expiry     = max( 0, (int) ( $_POST['expiry'] ?? 0 ) );
		$label      = sanitize_text_field( $_POST['label'] ?? '' );
		$account_id = sanitize_text_field( $_POST['account_id'] ?? '' );
		$expires_at = $expiry > 0 ? time() + $expiry : 0;

		if ( $method === 'key' ) {
			$email   = sanitize_email( $_POST['email'] ?? '' );
			$api_key = sanitize_text_field( $_POST['api_key'] ?? '' );

			if ( empty( $email ) || empty( $api_key ) ) {
				wp_send_json_error( [ 'message' => 'Email and API Key are required.' ] );
				wp_die();
			}

			$api    = new WPWAF_API( auth_method: 'key', email: $email, api_key: $api_key );
			$result = $api->verify_credentials();
			if ( ! $result['success'] ) {
				wp_send_json_error( [ 'message' => $result['message'] ] );
				wp_die();
			}

			$id = WPWAF_Accounts::save( [
				'id'          => $account_id,
				'label'       => $label ?: $email,
				'auth_method' => 'key',
				'email'       => $email,
				'api_key'     => $api_key,
				'api_token'   => '',
				'expires_at'  => $expires_at,
			] );

		} else {
			$token = sanitize_text_field( $_POST['api_token'] ?? '' );

			// If the field contains our masked placeholder, re-use the stored token.
			if ( empty( $token ) || str_contains( $token, '****' ) ) {
				$acc   = WPWAF_Accounts::active();
				$token = $acc['api_token'] ?? '';
			}

			if ( empty( $token ) ) {
				wp_send_json_error( [ 'message' => 'No API token provided.' ] );
				wp_die();
			}

			$api    = new WPWAF_API( auth_method: 'token', api_token: $token );
			$result = $api->verify_credentials();
			if ( ! $result['success'] ) {
				wp_send_json_error( [ 'message' => $result['message'] ] );
				wp_die();
			}

			$count = count( WPWAF_Accounts::all() );
			$id    = WPWAF_Accounts::save( [
				'id'          => $account_id,
				'label'       => $label ?: ( 'Account ' . ( $count + 1 ) ),
				'auth_method' => 'token',
				'api_token'   => $token,
				'email'       => '',
				'api_key'     => '',
				'expires_at'  => $expires_at,
			] );
		}

		WPWAF_Accounts::switch_to( $id );

		wp_send_json_success( [
			'message'    => $result['message'],
			'account_id' => $id,
			'expires_at' => $expires_at,
			'accounts'   => WPWAF_Accounts::all(),
			'active_id'  => WPWAF_Accounts::active_id(),
		] );
		wp_die();
	}

	public function list_zones(): void {
		$this->check();
		$this->require_active_account();
		$result = WPWAF_Accounts::api()->list_zones();
		$result['success'] ? wp_send_json_success( $result ) : wp_send_json_error( $result );
		wp_die();
	}

	public function switch_account(): void {
		$this->check();
		$id = sanitize_text_field( $_POST['account_id'] ?? '' );
		if ( empty( $id ) ) {
			wp_send_json_error( [ 'message' => 'No account ID provided.' ] );
			wp_die();
		}
		// Verify the account exists before switching.
		$exists = array_filter( WPWAF_Accounts::all(), fn( $a ) => ( $a['id'] ?? '' ) === $id );
		if ( empty( $exists ) ) {
			wp_send_json_error( [ 'message' => 'Account not found.' ] );
			wp_die();
		}
		WPWAF_Accounts::switch_to( $id );
		wp_send_json_success( [ 'active_id' => $id, 'accounts' => WPWAF_Accounts::all() ] );
		wp_die();
	}

	public function delete_account(): void {
		$this->check();
		$id = sanitize_text_field( $_POST['account_id'] ?? '' );
		if ( empty( $id ) ) {
			wp_send_json_error( [ 'message' => 'No account ID provided.' ] );
			wp_die();
		}
		WPWAF_Accounts::delete( $id );
		wp_send_json_success( [
			'accounts'  => WPWAF_Accounts::all(),
			'active_id' => WPWAF_Accounts::active_id(),
		] );
		wp_die();
	}


	// ── WAF rule handlers ──────────────────────────────────────────────────────

	public function deploy_rules(): void {
		$this->check();
		$this->require_active_account();

		$zone_ids = [];
		if ( isset( $_POST['zone_ids'] ) && is_array( $_POST['zone_ids'] ) ) {
			$zone_ids = array_filter( array_map( 'sanitize_text_field', $_POST['zone_ids'] ) );
		}
		if ( empty( $zone_ids ) ) {
			wp_send_json_error( [ 'message' => 'No zones selected.' ] );
			wp_die();
		}

		$settings_raw = wp_unslash( $_POST['settings'] ?? '' );
		$settings     = json_decode( $settings_raw, associative: true );
		if ( ! is_array( $settings ) ) {
			$settings = get_option( 'wpwaf_rule_settings', WPWAF_Rule_Builder::default_settings() );
		}

		// Validate settings structure before using it.
		$settings = WPWAF_Rule_Builder::sanitize_settings( $settings );
		$rules    = WPWAF_Rule_Builder::build_rules( $settings );

		if ( empty( $rules ) ) {
			wp_send_json_error( [ 'message' => 'No rules to deploy — all rules are disabled.' ] );
			wp_die();
		}

		$api     = WPWAF_Accounts::api();
		$results = [];
		foreach ( $zone_ids as $zone_id ) {
			$results[ $zone_id ] = $api->deploy_rules( $zone_id, $rules );
		}

		$success_count = count( array_filter( $results, fn( $r ) => ! empty( $r['success'] ) ) );
		wp_send_json_success( [
			'results'       => $results,
			'success_count' => $success_count,
			'fail_count'    => count( $results ) - $success_count,
			'rule_count'    => count( $rules ),
		] );
		wp_die();
	}

	public function list_zone_rules(): void {
		$this->check();
		$this->require_active_account();
		$zone_id = sanitize_text_field( $_POST['zone_id'] ?? '' );
		if ( empty( $zone_id ) ) {
			wp_send_json_error( [ 'message' => 'No zone ID provided.' ] );
			wp_die();
		}
		$result = WPWAF_Accounts::api()->list_rules( $zone_id );
		$result['success'] ? wp_send_json_success( $result ) : wp_send_json_error( $result );
		wp_die();
	}

	public function save_settings(): void {
		$this->check();
		$settings_raw = wp_unslash( $_POST['settings'] ?? '' );
		$settings     = json_decode( $settings_raw, associative: true );
		if ( ! is_array( $settings ) ) {
			wp_send_json_error( [ 'message' => 'Invalid settings payload.' ] );
			wp_die();
		}
		// Sanitize before persisting — never store arbitrary JSON from POST.
		$settings = WPWAF_Rule_Builder::sanitize_settings( $settings );
		update_option( 'wpwaf_rule_settings', $settings, false );
		wp_send_json_success( [ 'message' => 'Settings saved.' ] );
		wp_die();
	}

	public function preview_rules(): void {
		$this->check();
		$settings_raw = wp_unslash( $_POST['settings'] ?? '' );
		$settings     = json_decode( $settings_raw, associative: true );
		if ( ! is_array( $settings ) ) {
			$settings = WPWAF_Rule_Builder::default_settings();
		}
		$settings = WPWAF_Rule_Builder::sanitize_settings( $settings );
		$rules    = WPWAF_Rule_Builder::build_rules( $settings );
		wp_send_json_success( [ 'rules' => $rules ] );
		wp_die();
	}

	// ── DNS handlers ───────────────────────────────────────────────────────────

	public function dns_list(): void {
		$this->check();
		$this->require_active_account();
		$zone_id = sanitize_text_field( $_POST['zone_id'] ?? '' );
		if ( empty( $zone_id ) ) {
			wp_send_json_error( [ 'message' => 'zone_id is required.' ] );
			wp_die();
		}
		$result = WPWAF_Accounts::api()->list_dns_records( $zone_id );
		$result['success'] ? wp_send_json_success( $result ) : wp_send_json_error( $result );
		wp_die();
	}

	public function dns_create(): void {
		$this->check();
		$this->require_active_account();
		$zone_id = sanitize_text_field( $_POST['zone_id'] ?? '' );
		if ( empty( $zone_id ) ) {
			wp_send_json_error( [ 'message' => 'zone_id is required.' ] );
			wp_die();
		}
		$raw = json_decode( wp_unslash( $_POST['record'] ?? '{}' ), true ) ?: [];
		$v   = WPWAF_DNS::sanitize_record( $raw );
		if ( ! $v['valid'] ) {
			wp_send_json_error( [ 'message' => $v['error'] ] );
			wp_die();
		}
		$result = WPWAF_Accounts::api()->create_dns_record( $zone_id, $v['data'] );
		$result['success'] ? wp_send_json_success( $result ) : wp_send_json_error( $result );
		wp_die();
	}

	public function dns_update(): void {
		$this->check();
		$this->require_active_account();
		$zone_id   = sanitize_text_field( $_POST['zone_id']   ?? '' );
		$record_id = sanitize_text_field( $_POST['record_id'] ?? '' );
		if ( empty( $zone_id ) || empty( $record_id ) ) {
			wp_send_json_error( [ 'message' => 'zone_id and record_id are required.' ] );
			wp_die();
		}
		$raw = json_decode( wp_unslash( $_POST['record'] ?? '{}' ), true ) ?: [];
		$v   = WPWAF_DNS::sanitize_record( $raw );
		if ( ! $v['valid'] ) {
			wp_send_json_error( [ 'message' => $v['error'] ] );
			wp_die();
		}
		$result = WPWAF_Accounts::api()->update_dns_record( $zone_id, $record_id, $v['data'] );
		$result['success'] ? wp_send_json_success( $result ) : wp_send_json_error( $result );
		wp_die();
	}

	public function dns_delete(): void {
		$this->check();
		$this->require_active_account();
		$zone_id   = sanitize_text_field( $_POST['zone_id']   ?? '' );
		$record_id = sanitize_text_field( $_POST['record_id'] ?? '' );
		if ( empty( $zone_id ) || empty( $record_id ) ) {
			wp_send_json_error( [ 'message' => 'zone_id and record_id are required.' ] );
			wp_die();
		}
		$result = WPWAF_Accounts::api()->delete_dns_record( $zone_id, $record_id );
		$result['success'] ? wp_send_json_success( [] ) : wp_send_json_error( $result );
		wp_die();
	}

	public function dns_toggle_proxy(): void {
		$this->check();
		$this->require_active_account();
		$zone_id   = sanitize_text_field( $_POST['zone_id']   ?? '' );
		$record_id = sanitize_text_field( $_POST['record_id'] ?? '' );
		$proxied   = filter_var( $_POST['proxied'] ?? false, FILTER_VALIDATE_BOOLEAN );
		if ( empty( $zone_id ) || empty( $record_id ) ) {
			wp_send_json_error( [ 'message' => 'zone_id and record_id are required.' ] );
			wp_die();
		}
		$result = WPWAF_Accounts::api()->update_dns_record( $zone_id, $record_id, [ 'proxied' => $proxied ] );
		$result['success'] ? wp_send_json_success( [] ) : wp_send_json_error( $result );
		wp_die();
	}

	// ── Per-account API helper ────────────────────────────────────────────────

	/**
	 * Return a WPWAF_API instance for a specific plugin account ID.
	 * Returns null if the account is not found.
	 */
	private function api_for( string $plugin_account_id ): ?WPWAF_API {
		if ( empty( $plugin_account_id ) ) return null;
		$accounts = WPWAF_Accounts::all();
		foreach ( $accounts as $acc ) {
			if ( ( $acc['id'] ?? '' ) !== $plugin_account_id ) continue;
			return new WPWAF_API(
				auth_method: $acc['auth_method'] ?? 'token',
				api_token:   $acc['api_token']   ?? '',
				email:       $acc['email']        ?? '',
				api_key:     $acc['api_key']      ?? '',
			);
		}
		return null;
	}

	/**
	 * Detect if an API error is a permissions/auth issue.
	 */
	private function is_permission_error( string $message ): bool {
		$signals = [ '403', '10000', '9109', 'Authentication', 'Unauthorized', 'permission', 'Forbidden', 'not authorized', 'access' ];
		foreach ( $signals as $s ) {
			if ( stripos( $message, $s ) !== false ) return true;
		}
		return false;
	}

	// ── Zone status handlers ───────────────────────────────────────────────────

	public function zone_status_sync(): void {
		$this->check();
		$this->require_active_account();
		WPWAF_Zone_Status::run_sync();
		wp_send_json_success( [
			'next_sync' => WPWAF_Zone_Status::get_next_sync(),
			'synced_at' => time(),
		] );
		wp_die();
	}

	public function zone_status_save_settings(): void {
		$this->check();
		$raw_zones = $_POST['allowed_zones'] ?? [];
		$s = [
			'enabled'        => filter_var( $_POST['enabled'] ?? true, FILTER_VALIDATE_BOOLEAN ),
			'sync_interval'  => max( 300, (int) ( $_POST['sync_interval']  ?? 3600 ) ),
			'days_analytics' => max( 1, min( 30, (int) ( $_POST['days_analytics'] ?? 7 ) ) ),
			'allowed_zones'  => is_array( $raw_zones )
				? array_values( array_filter( array_map( 'sanitize_text_field', $raw_zones ) ) )
				: [],
		];
		WPWAF_Zone_Status::save_settings( $s );
		wp_send_json_success( [
			'settings'  => WPWAF_Zone_Status::get_settings(),
			'next_sync' => WPWAF_Zone_Status::get_next_sync(),
		] );
		wp_die();
	}

	// ── Account IP access rule handlers ───────────────────────────────────────

	/**
	 * Resolve a Cloudflare account ID for a given plugin account (by plugin_account_id).
	 * Falls back to the active account if no plugin_account_id is provided.
	 */
	public function get_account_id(): void {
		$this->check();
		$this->require_active_account();

		$plugin_account_id = sanitize_text_field( $_POST['plugin_account_id'] ?? '' );
		$api               = $this->api_for( $plugin_account_id );

		if ( ! $api ) {
			wp_send_json_error( [ 'message' => 'Account not found.', 'code' => 'not_found' ] );
			wp_die();
		}

		$cf_account_id = $api->get_account_id();

		if ( empty( $cf_account_id ) ) {
			wp_send_json_error( [
				'message' => 'Could not retrieve Cloudflare account ID.',
				'code'    => 'no_account_id',
			] );
			wp_die();
		}

		wp_send_json_success( [ 'account_id' => $cf_account_id ] );
		wp_die();
	}

	public function ip_rules_list(): void {
		$this->check();
		$this->require_active_account();
		$account_id        = sanitize_text_field( $_POST['account_id']        ?? '' );
		$plugin_account_id = sanitize_text_field( $_POST['plugin_account_id'] ?? '' );
		if ( empty( $account_id ) ) {
			wp_send_json_error( [ 'message' => 'account_id is required.' ] );
			wp_die();
		}
		$api    = $this->api_for( $plugin_account_id ) ?? WPWAF_Accounts::api();
		$result = $api->list_ip_rules( $account_id );
		if ( ! $result['success'] ) {
			$result['perm_error'] = $this->is_permission_error( $result['message'] ?? '' );
		}
		$result['success'] ? wp_send_json_success( $result ) : wp_send_json_error( $result );
		wp_die();
	}

	public function ip_rules_create(): void {
		$this->check();
		$this->require_active_account();
		$account_id = sanitize_text_field( $_POST['account_id'] ?? '' );
		$mode       = sanitize_text_field( $_POST['mode']       ?? '' );
		$target     = sanitize_text_field( $_POST['target']     ?? 'ip' );
		$value      = sanitize_text_field( $_POST['value']      ?? '' );
		$note       = sanitize_text_field( $_POST['note']       ?? '' );

		$allowed_modes   = [ 'whitelist', 'block', 'challenge', 'js_challenge' ];
		$allowed_targets = [ 'ip', 'ip_range', 'country', 'asn' ];

		if ( empty( $account_id ) || empty( $value ) ) {
			wp_send_json_error( [ 'message' => 'account_id and value are required.' ] );
			wp_die();
		}
		if ( ! in_array( $mode, $allowed_modes, true ) ) {
			wp_send_json_error( [ 'message' => 'Invalid mode.' ] );
			wp_die();
		}
		if ( ! in_array( $target, $allowed_targets, true ) ) {
			wp_send_json_error( [ 'message' => 'Invalid target type.' ] );
			wp_die();
		}

		$data = [
			'mode'          => $mode,
			'configuration' => [ 'target' => $target, 'value' => $value ],
			'notes'         => $note,
		];

		$plugin_account_id = sanitize_text_field( $_POST['plugin_account_id'] ?? '' );
		$api    = $this->api_for( $plugin_account_id ) ?? WPWAF_Accounts::api();
		$result = $api->create_ip_rule( $account_id, $data );
		$result['success'] ? wp_send_json_success( $result ) : wp_send_json_error( $result );
		wp_die();
	}

	public function ip_rules_update(): void {
		$this->check();
		$this->require_active_account();
		$account_id = sanitize_text_field( $_POST['account_id'] ?? '' );
		$rule_id    = sanitize_text_field( $_POST['rule_id']    ?? '' );
		$note       = sanitize_text_field( $_POST['note']       ?? '' );
		$mode       = sanitize_text_field( $_POST['mode']       ?? '' );

		if ( empty( $account_id ) || empty( $rule_id ) ) {
			wp_send_json_error( [ 'message' => 'account_id and rule_id are required.' ] );
			wp_die();
		}

		$data = [ 'notes' => $note ];
		$allowed_modes = [ 'whitelist', 'block', 'challenge', 'js_challenge' ];
		if ( $mode !== '' && in_array( $mode, $allowed_modes, true ) ) {
			$data['mode'] = $mode;
		}

		$plugin_account_id = sanitize_text_field( $_POST['plugin_account_id'] ?? '' );
		$api    = $this->api_for( $plugin_account_id ) ?? WPWAF_Accounts::api();
		$result = $api->update_ip_rule( $account_id, $rule_id, $data );
		$result['success'] ? wp_send_json_success( $result ) : wp_send_json_error( $result );
		wp_die();
	}

	public function ip_rules_delete(): void {
		$this->check();
		$this->require_active_account();
		$account_id = sanitize_text_field( $_POST['account_id'] ?? '' );
		$rule_id    = sanitize_text_field( $_POST['rule_id']    ?? '' );
		if ( empty( $account_id ) || empty( $rule_id ) ) {
			wp_send_json_error( [ 'message' => 'account_id and rule_id are required.' ] );
			wp_die();
		}
		$plugin_account_id = sanitize_text_field( $_POST['plugin_account_id'] ?? '' );
		$api    = $this->api_for( $plugin_account_id ) ?? WPWAF_Accounts::api();
		$result = $api->delete_ip_rule( $account_id, $rule_id );
		$result['success'] ? wp_send_json_success( [] ) : wp_send_json_error( $result );
		wp_die();
	}

	// ── Zone Controls ──────────────────────────────────────────────────────────

	public function purge_cache(): void {
		$this->check();
		$this->require_active_account();
		$zone_id = sanitize_text_field( $_POST['zone_id'] ?? '' );
		$mode    = sanitize_text_field( $_POST['mode']    ?? 'all' );

		if ( empty( $zone_id ) ) {
			wp_send_json_error( [ 'message' => 'zone_id is required.' ] );
			wp_die();
		}

		if ( $mode === 'urls' ) {
			$raw_urls = wp_unslash( $_POST['urls'] ?? '' );
			$urls = array_values( array_filter(
				array_map( 'esc_url_raw', explode( "\n", $raw_urls ) )
			) );
			if ( empty( $urls ) ) {
				wp_send_json_error( [ 'message' => 'No valid URLs provided.' ] );
				wp_die();
			}
			$data = [ 'files' => $urls ];
		} else {
			$data = [ 'purge_everything' => true ];
		}

		$result = WPWAF_Accounts::api()->purge_cache( $zone_id, $data );
		$result['success'] ? wp_send_json_success( [] ) : wp_send_json_error( $result );
		wp_die();
	}

	public function zone_settings_load(): void {
		$this->check();
		$this->require_active_account();
		$zone_id = sanitize_text_field( $_POST['zone_id'] ?? '' );
		if ( empty( $zone_id ) ) {
			wp_send_json_error( [ 'message' => 'zone_id is required.' ] );
			wp_die();
		}
		$result = WPWAF_Accounts::api()->get_all_zone_settings( $zone_id );
		$result['success'] ? wp_send_json_success( $result ) : wp_send_json_error( $result );
		wp_die();
	}

	public function zone_setting_update(): void {
		$this->check();
		$this->require_active_account();
		$zone_id = sanitize_text_field( $_POST['zone_id'] ?? '' );
		$key     = sanitize_key( $_POST['key']     ?? '' );
		$value   = sanitize_text_field( wp_unslash( $_POST['value'] ?? '' ) );

		// Allowlist which settings can be updated for security
		$allowed_keys = [
			'security_level', 'development_mode', 'ssl', 'always_use_https',
			'cache_level', 'browser_cache_ttl', 'rocket_loader', 'always_online',
			'hotlink_protection', 'email_obfuscation', 'server_side_exclude',
			'browser_check', 'challenge_ttl',
		];
		if ( ! in_array( $key, $allowed_keys, true ) ) {
			wp_send_json_error( [ 'message' => "Setting '{$key}' cannot be updated." ] );
			wp_die();
		}

		$result = WPWAF_Accounts::api()->update_zone_setting_direct( $zone_id, $key, $value );
		$result['success'] ? wp_send_json_success( $result ) : wp_send_json_error( $result );
		wp_die();
	}

	// ── Security Events ────────────────────────────────────────────────────────

	public function security_events(): void {
		$this->check();
		$this->require_active_account();
		$zone_id = sanitize_text_field( $_POST['zone_id']       ?? '' );
		$limit   = min( 500, max( 10, (int) ( $_POST['limit']   ?? 100 ) ) );
		$action  = sanitize_text_field( $_POST['action_filter'] ?? '' );
		$hours   = min( 720, max( 1, (int) ( $_POST['hours']    ?? 24 ) ) );

		if ( empty( $zone_id ) ) {
			wp_send_json_error( [ 'message' => 'zone_id is required.' ] );
			wp_die();
		}

		$result = WPWAF_Accounts::api()->get_security_events( $zone_id, $limit, $action, $hours );
		if ( ! $result['success'] ) {
			wp_send_json_error( $result );
		} else {
			wp_send_json_success( $result );
		}
		wp_die();
	}

	// ── Plugin settings ────────────────────────────────────────────────────────

	public function save_plugin_settings(): void {
		$this->check();
		$raw = json_decode( wp_unslash( $_POST['settings'] ?? '{}' ), associative: true );
		if ( ! is_array( $raw ) ) {
			wp_send_json_error( [ 'message' => 'Invalid settings payload.' ] );
			wp_die();
		}
		$saved = WPWAF_Settings::save( $raw );

		// Sync analytics settings into zone status module if changed.
		if ( isset( $raw['analytics_auto_sync'], $raw['analytics_interval'], $raw['analytics_days'] ) ) {
			$zs = WPWAF_Zone_Status::get_settings();
			$zs['enabled']        = $saved['analytics_auto_sync'];
			$zs['sync_interval']  = $saved['analytics_interval'];
			$zs['days_analytics'] = $saved['analytics_days'];
			WPWAF_Zone_Status::save_settings( $zs );
		}

		wp_send_json_success( [ 'settings' => $saved ] );
		wp_die();
	}

	public function load_zones_for_settings(): void {
		$this->check();
		if ( ! WPWAF_Accounts::active() ) {
			wp_send_json_success( [ 'zones' => [] ] );
			wp_die();
		}
		$result = WPWAF_Accounts::api()->list_zones();
		if ( ! $result['success'] ) {
			wp_send_json_error( [ 'message' => $result['message'] ?? 'Could not load zones.' ] );
			wp_die();
		}
		wp_send_json_success( [ 'zones' => $result['zones'] ?? [] ] );
		wp_die();
	}

	// ── Email Routing handlers ────────────────────────────────────────────────

	public function email_addresses_list(): void {
		$this->check();
		$this->require_active_account();
		$account_id = sanitize_text_field( $_POST['account_id'] ?? '' );
		if ( empty( $account_id ) ) {
			// Auto-detect account ID
			$account_id = WPWAF_Accounts::api()->get_account_id();
		}
		if ( empty( $account_id ) ) {
			wp_send_json_error( [ 'message' => 'Could not determine account ID.' ] ); wp_die();
		}
		$result = WPWAF_Accounts::api()->list_email_addresses( $account_id );
		$result['success'] ? wp_send_json_success( $result ) : wp_send_json_error( $result );
		wp_die();
	}

	public function email_address_create(): void {
		$this->check();
		$this->require_active_account();
		$email = sanitize_email( $_POST['email'] ?? '' );
		if ( empty( $email ) ) {
			wp_send_json_error( [ 'message' => 'Valid email address required.' ] ); wp_die();
		}
		$account_id = sanitize_text_field( $_POST['account_id'] ?? '' );
		if ( empty( $account_id ) ) {
			$account_id = WPWAF_Accounts::api()->get_account_id();
		}
		if ( empty( $account_id ) ) {
			wp_send_json_error( [ 'message' => 'Could not determine account ID.' ] ); wp_die();
		}
		$result = WPWAF_Accounts::api()->create_email_address( $account_id, $email );
		$result['success'] ? wp_send_json_success( $result ) : wp_send_json_error( $result );
		wp_die();
	}

	public function catch_all_get(): void {
		$this->check();
		$this->require_active_account();
		$zone_id = sanitize_text_field( $_POST['zone_id'] ?? '' );
		if ( empty( $zone_id ) ) { wp_send_json_error( [ 'message' => 'zone_id required.' ] ); wp_die(); }
		$result = WPWAF_Accounts::api()->get_catch_all_rule( $zone_id );
		$result['success'] ? wp_send_json_success( $result ) : wp_send_json_error( $result );
		wp_die();
	}

	public function catch_all_update(): void {
		$this->check();
		$this->require_active_account();
		$zone_id  = sanitize_text_field( $_POST['zone_id']  ?? '' );
		$action   = sanitize_text_field( $_POST['action_type'] ?? 'drop' );
		$dest     = sanitize_email( $_POST['destination'] ?? '' );
		$enabled  = filter_var( $_POST['enabled'] ?? true, FILTER_VALIDATE_BOOLEAN );
		if ( empty( $zone_id ) ) { wp_send_json_error( [ 'message' => 'zone_id required.' ] ); wp_die(); }
		$allowed = [ 'forward', 'drop', 'worker' ];
		if ( ! in_array( $action, $allowed, true ) ) { wp_send_json_error( [ 'message' => 'Invalid action.' ] ); wp_die(); }
		$data = [
			'enabled' => $enabled,
			'actions' => $action === 'forward' && $dest
				? [ [ 'type' => 'forward', 'value' => [ $dest ] ] ]
				: [ [ 'type' => $action ] ],
		];
		$result = WPWAF_Accounts::api()->update_catch_all_rule( $zone_id, $data );
		$result['success'] ? wp_send_json_success( $result ) : wp_send_json_error( $result );
		wp_die();
	}

	public function email_routing_get(): void {
		$this->check();
		$this->require_active_account();
		$zone_id = sanitize_text_field( $_POST['zone_id'] ?? '' );
		if ( empty( $zone_id ) ) { wp_send_json_error( [ 'message' => 'zone_id required.' ] ); wp_die(); }
		$routing = WPWAF_Accounts::api()->get_email_routing( $zone_id );
		$routing['success'] ? wp_send_json_success( $routing ) : wp_send_json_error( $routing );
		wp_die();
	}

	public function email_routing_toggle(): void {
		$this->check();
		$this->require_active_account();
		$zone_id = sanitize_text_field( $_POST['zone_id'] ?? '' );
		$enable  = filter_var( $_POST['enable'] ?? false, FILTER_VALIDATE_BOOLEAN );
		if ( empty( $zone_id ) ) { wp_send_json_error( [ 'message' => 'zone_id required.' ] ); wp_die(); }
		$api    = WPWAF_Accounts::api();
		$result = $enable ? $api->enable_email_routing( $zone_id ) : $api->disable_email_routing( $zone_id );
		$result['success'] ? wp_send_json_success( [] ) : wp_send_json_error( $result );
		wp_die();
	}

	public function email_rules_list(): void {
		$this->check();
		$this->require_active_account();
		$zone_id = sanitize_text_field( $_POST['zone_id'] ?? '' );
		if ( empty( $zone_id ) ) { wp_send_json_error( [ 'message' => 'zone_id required.' ] ); wp_die(); }
		$result = WPWAF_Accounts::api()->list_email_rules( $zone_id );
		$result['success'] ? wp_send_json_success( $result ) : wp_send_json_error( $result );
		wp_die();
	}

	public function email_rule_create(): void {
		$this->check();
		$this->require_active_account();
		$zone_id     = sanitize_text_field( $_POST['zone_id']     ?? '' );
		$from        = sanitize_email(      $_POST['from']        ?? '' );
		$to          = sanitize_email(      $_POST['to']          ?? '' );
		$name        = sanitize_text_field( $_POST['name']        ?? '' );
		$action_type = sanitize_text_field( $_POST['action_type'] ?? 'forward' );
		if ( empty( $zone_id ) || empty( $from ) || empty( $to ) ) {
			wp_send_json_error( [ 'message' => 'zone_id, from, and to are required.' ] ); wp_die();
		}
		$allowed_actions = [ 'forward', 'worker', 'drop' ];
		if ( ! in_array( $action_type, $allowed_actions, true ) ) {
			wp_send_json_error( [ 'message' => 'Invalid action type.' ] ); wp_die();
		}
		$data = [
			'name'     => $name ?: "Forward {$from}",
			'enabled'  => true,
			'matchers' => [ [ 'type' => 'literal', 'field' => 'to', 'value' => $from ] ],
			'actions'  => [ [ 'type' => $action_type, 'value' => [ $to ] ] ],
		];
		$result = WPWAF_Accounts::api()->create_email_rule( $zone_id, $data );
		$result['success'] ? wp_send_json_success( $result ) : wp_send_json_error( $result );
		wp_die();
	}

	public function email_rule_update(): void {
		$this->check();
		$this->require_active_account();
		$zone_id = sanitize_text_field( $_POST['zone_id'] ?? '' );
		$rule_id = sanitize_text_field( $_POST['rule_id'] ?? '' );
		$enabled = filter_var( $_POST['enabled'] ?? true, FILTER_VALIDATE_BOOLEAN );
		$raw     = json_decode( wp_unslash( $_POST['rule'] ?? '{}' ), true ) ?: [];
		if ( empty( $zone_id ) || empty( $rule_id ) ) {
			wp_send_json_error( [ 'message' => 'zone_id and rule_id required.' ] ); wp_die();
		}
		$data = array_merge( $raw, [ 'enabled' => $enabled ] );
		$result = WPWAF_Accounts::api()->update_email_rule( $zone_id, $rule_id, $data );
		$result['success'] ? wp_send_json_success( $result ) : wp_send_json_error( $result );
		wp_die();
	}

	public function email_rule_delete(): void {
		$this->check();
		$this->require_active_account();
		$zone_id = sanitize_text_field( $_POST['zone_id'] ?? '' );
		$rule_id = sanitize_text_field( $_POST['rule_id'] ?? '' );
		if ( empty( $zone_id ) || empty( $rule_id ) ) {
			wp_send_json_error( [ 'message' => 'zone_id and rule_id required.' ] ); wp_die();
		}
		$result = WPWAF_Accounts::api()->delete_email_rule( $zone_id, $rule_id );
		$result['success'] ? wp_send_json_success( [] ) : wp_send_json_error( $result );
		wp_die();
	}

	// ── Test connection ────────────────────────────────────────────────────────

	public function test_connection(): void {
		$this->check();
		$active = WPWAF_Accounts::active();
		if ( ! $active ) {
			wp_send_json_error( [ 'message' => 'No account connected.' ] ); wp_die();
		}
		$result = WPWAF_Accounts::api()->verify_credentials();
		if ( $result['success'] ) {
			$zones  = WPWAF_Accounts::api()->list_zones();
			$count  = count( $zones['zones'] ?? [] );
			wp_send_json_success( [
				'message'    => $result['message'],
				'zone_count' => $count,
				'account'    => $active['label'] ?? 'Unknown',
			] );
		} else {
			wp_send_json_error( [ 'message' => $result['message'] ] );
		}
		wp_die();
	}
}
