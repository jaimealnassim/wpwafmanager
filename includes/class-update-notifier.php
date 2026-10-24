<?php
declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Checks GitHub daily for a newer release.json and shows a dashboard-only
 * admin notice to free users when an update is available.
 *
 * - Pro users (active SureCart license) see nothing - SureCart handles their updates.
 * - Notice is dismissible; the dismiss is stored for 24 hours so it comes back daily.
 * - Version check result is cached in a transient for 24 hours.
 */
class WPWAF_Update_Notifier {

	/** GitHub raw URL for release.json. */
	private const REMOTE_JSON_URL = 'https://raw.githubusercontent.com/jaimealnassim/wpwafmanager/main/release.json';

	/** Link shown for free users to download the latest zip. */
	private const GITHUB_RELEASES_URL = 'https://github.com/jaimealnassim/wpwafmanager/releases/latest';

	/** Link shown to upgrade to Pro. */
	private const PRO_URL = 'https://wpwafmanager.com';

	/** Transient key for cached remote version (24 h). */
	private const TRANSIENT_CHECK = 'wpwaf_update_check';

	/** Transient key for dismissed state (24 h). */
	private const TRANSIENT_DISMISSED = 'wpwaf_update_dismissed';

	public static function init(): void {
		add_action( 'admin_notices',  [ __CLASS__, 'maybe_show_notice' ] );
		add_action( 'wp_ajax_wpwaf_dismiss_update', [ __CLASS__, 'ajax_dismiss' ] );
	}

	// ── Public ─────────────────────────────────────────────────────────────────

	public static function maybe_show_notice(): void {
		// Dashboard only.
		$screen = get_current_screen();
		if ( ! $screen || $screen->id !== 'dashboard' ) return;

		// Admins only.
		if ( ! current_user_can( 'manage_options' ) ) return;

		// Pro users - SureCart handles their updates.
		if ( self::is_pro_active() ) return;

		// Already dismissed today.
		if ( get_transient( self::TRANSIENT_DISMISSED ) ) return;

		// Fetch remote version (cached 24 h).
		$remote_version = self::get_remote_version();
		if ( ! $remote_version ) return;

		// Only show if remote is actually newer.
		if ( ! version_compare( $remote_version, WPWAF_VERSION, '>' ) ) return;

		self::render_notice( $remote_version );
	}

	public static function ajax_dismiss(): void {
		check_ajax_referer( 'wpwaf_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_die( -1 );
		set_transient( self::TRANSIENT_DISMISSED, 1, DAY_IN_SECONDS );
		wp_send_json_success();
	}

	// ── Private ─────────────────────────────────────────────────────────────────

	/**
	 * Returns true if a SureCart activation ID is stored locally.
	 * No API call - just checks the saved option.
	 */
	private static function is_pro_active(): bool {
		// The SureCart SDK names this option after the client name, lower-cased
		// with spaces removed: "WP WAF Manager" becomes wpwafmanager_license_options.
		$opts = get_option( 'wpwafmanager_license_options', [] );
		return is_array( $opts ) && ! empty( $opts['sc_activation_id'] );
	}

	/**
	 * Returns the remote version string, or null on failure.
	 * Result is cached in a transient for 24 hours.
	 */
	private static function get_remote_version(): ?string {
		$cached = get_transient( self::TRANSIENT_CHECK );
		if ( $cached !== false ) {
			return $cached ?: null; // empty string means last fetch failed.
		}

		$response = wp_remote_get( self::REMOTE_JSON_URL, [
			'timeout'    => 8,
			'user-agent' => 'WP WAF Manager/' . WPWAF_VERSION . '; ' . get_bloginfo( 'url' ),
		] );

		$version = '';
		if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( ! empty( $body['version'] ) && is_string( $body['version'] ) ) {
				$version = sanitize_text_field( $body['version'] );
			}
		}

		// Cache for 24 hours - even on failure (empty string) to avoid hammering GitHub.
		set_transient( self::TRANSIENT_CHECK, $version, DAY_IN_SECONDS );

		return $version ?: null;
	}

	private static function render_notice( string $remote_version ): void {
		$nonce = wp_create_nonce( 'wpwaf_nonce' );
		?>
		<div class="notice notice-warning wpwaf-update-notice" id="wpwaf-update-notice" style="display:flex;align-items:center;justify-content:space-between;gap:16px;padding:12px 16px;flex-wrap:wrap;">
			<div style="display:flex;align-items:center;gap:10px;">
				<span class="dashicons dashicons-shield-alt" style="color:#FF6A00;font-size:22px;width:22px;height:22px;flex-shrink:0;"></span>
				<p style="margin:0;font-size:14px;">
					<strong>WP WAF Manager <?php echo esc_html( $remote_version ); ?> is available.</strong>
					You are running <?php echo esc_html( WPWAF_VERSION ); ?>.
				</p>
			</div>
			<div style="display:flex;align-items:center;gap:10px;flex-shrink:0;">
				<a href="<?php echo esc_url( self::GITHUB_RELEASES_URL ); ?>" target="_blank" rel="noopener noreferrer"
				   style="display:inline-flex;align-items:center;gap:5px;padding:6px 14px;background:#f1f3f5;border:1px solid #d1d5db;border-radius:5px;font-size:13px;font-weight:600;color:#1a1a2e;text-decoration:none;">
					<span class="dashicons dashicons-download" style="font-size:14px;width:14px;height:14px;margin-top:1px;"></span>
					Download free
				</a>
				<a href="<?php echo esc_url( self::PRO_URL ); ?>" target="_blank" rel="noopener noreferrer"
				   style="display:inline-flex;align-items:center;gap:5px;padding:6px 14px;background:#FF6A00;border:1px solid #d95500;border-radius:5px;font-size:13px;font-weight:600;color:#fff;text-decoration:none;">
					Upgrade to Pro for automatic updates
				</a>
				<button type="button" id="wpwaf-update-dismiss"
						style="background:none;border:none;cursor:pointer;color:#6b7280;font-size:18px;line-height:1;padding:4px;"
						aria-label="Dismiss">
					&times;
				</button>
			</div>
		</div>
		<script>
		(function(){
			var btn = document.getElementById('wpwaf-update-dismiss');
			if (!btn) return;
			btn.addEventListener('click', function(){
				var notice = document.getElementById('wpwaf-update-notice');
				if (notice) notice.style.display = 'none';
				var fd = new FormData();
				fd.append('action', 'wpwaf_dismiss_update');
				fd.append('nonce', <?php echo wp_json_encode( $nonce ); ?>);
				fetch(<?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>, { method:'POST', body:fd });
			});
		})();
		</script>
		<?php
	}
}
