<?php
declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Clients & Reports: agency clients linked to their sites, and white-label
 * Cloudflare stats emails sent to them by hand or automatically every month.
 *
 * Each client record holds the client's name, the contact to greet, the
 * email addresses reports go to, internal notes, the linked zones (sites),
 * an optional monthly schedule and the default report content. Branding,
 * clients and the send log are kept in three options (autoload off). An
 * hourly WP-Cron event sends the monthly reports that are due. Each client
 * remembers the plugin account it was set up with, so automatic sends keep
 * using the right Cloudflare credentials in multi-account setups.
 *
 * The email itself (includes/views/email-report.php) is white label: it
 * carries the agency's name, logo and colors and never names this plugin.
 */
final class WPWAF_Reports extends WPWAF_Module {

	public const OPT_BRANDING = 'wpwaf_report_branding';
	public const OPT_CLIENTS  = 'wpwaf_clients';
	public const OPT_REPORTS  = self::OPT_CLIENTS; // older name, kept for callers
	public const OPT_LOG      = 'wpwaf_report_log';
	public const CRON_HOOK    = 'wpwaf_reports_cron';

	/** Report sections that can be turned on or off. */
	public const SECTIONS = [
		'summary'   => 'Summary tiles',
		'traffic'   => 'Traffic chart',
		'cache'     => 'Cache and bandwidth',
		'countries' => 'Top countries',
		'security'  => 'Security summary',
		'pages'     => 'Top pages',
		'statuses'  => 'Status codes',
	];

	/** Reports are sent by hand, or automatically once a month. */
	private const FREQUENCIES = [ 'manual', 'monthly' ];
	private const MAX_REPORTS = 100;
	private const MAX_ZONES   = 20;
	private const MAX_RECIPS  = 20;
	private const LOG_SIZE    = 20;
	private const SENDS_PER_RUN = 5;

	public static function init(): void {
		foreach ( [ 'branding_save', 'save', 'delete', 'preview', 'send', 'state' ] as $a ) {
			add_action( 'wp_ajax_wpwaf_rp_' . $a, [ self::class, 'ajax_' . $a ] );
		}
		add_action( self::CRON_HOOK, [ self::class, 'run_due' ] );
		add_action( 'init', [ self::class, 'ensure_cron' ] );
		add_action( 'admin_enqueue_scripts', [ self::class, 'enqueue_media' ] );
	}

	/** Keep the hourly event scheduled. */
	public static function ensure_cron(): void {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + 300, 'hourly', self::CRON_HOOK );
		}
	}

	/** Media library for the logo picker, on this screen only. */
	public static function enqueue_media( string $hook ): void {
		if ( str_ends_with( $hook, '_page_wpwafmanager-reports' ) && function_exists( 'wp_enqueue_media' ) ) {
			wp_enqueue_media();
		}
	}

	// ── Storage ──────────────────────────────────────────────────────────────

	public static function branding_defaults(): array {
		return [
			'agency_name'     => get_bloginfo( 'name' ),
			'logo_url'        => '',
			'brand_color'     => '#FF6A00',
			'from_name'       => '',
			'from_email'      => '',
			'reply_to'        => '',
			'footer_text'     => '',
			'show_cloudflare' => false,
		];
	}

	public static function branding(): array {
		$saved = get_option( self::OPT_BRANDING, [] );
		return array_merge( self::branding_defaults(), is_array( $saved ) ? $saved : [] );
	}

	/** All clients keyed by id. */
	public static function all(): array {
		$r = get_option( self::OPT_REPORTS, [] );
		return is_array( $r ) ? $r : [];
	}

	private static function save_all( array $reports ): void {
		update_option( self::OPT_REPORTS, $reports, false );
	}

	public static function log(): array {
		$l = get_option( self::OPT_LOG, [] );
		return is_array( $l ) ? array_values( $l ) : [];
	}

	private static function add_log( array $entry ): void {
		$log = self::log();
		array_unshift( $log, $entry );
		update_option( self::OPT_LOG, array_slice( $log, 0, self::LOG_SIZE ), false );
	}

	// ── Validation ───────────────────────────────────────────────────────────

	private static function clean_color( string $c ): string {
		$c = trim( $c );
		return preg_match( '/^#[0-9a-fA-F]{6}$/', $c ) ? strtoupper( $c ) : '#FF6A00';
	}

	/** Header-safe display name: no line breaks, angle brackets, quotes or commas. */
	private static function clean_name( string $s ): string {
		return trim( (string) preg_replace( '/[\r\n<>",;]+/', ' ', sanitize_text_field( $s ) ) );
	}

	private static function clean_url( string $u ): string {
		$u = esc_url_raw( trim( $u ), [ 'http', 'https' ] );
		return is_string( $u ) ? $u : '';
	}

	/** Split a recipient list on commas, semicolons or new lines and keep valid addresses. */
	public static function parse_recipients( string $raw ): array {
		$out = [];
		foreach ( preg_split( '/[\s,;]+/', $raw ) ?: [] as $e ) {
			$e = sanitize_email( $e );
			if ( $e !== '' && is_email( $e ) && ! isset( $out[ strtolower( $e ) ] ) ) {
				$out[ strtolower( $e ) ] = $e;
			}
		}
		return array_slice( array_values( $out ), 0, self::MAX_RECIPS );
	}

	/** Validate a report coming from the browser. Returns [report, error]. */
	public static function sanitize_report( array $raw, array $existing = [] ): array {
		$freq = in_array( (string) ( $raw['frequency'] ?? '' ), self::FREQUENCIES, true ) ? (string) $raw['frequency'] : 'manual';

		$zones = [];
		foreach ( (array) ( $raw['zones'] ?? [] ) as $z ) {
			$id   = strtolower( (string) ( is_array( $z ) ? ( $z['id'] ?? '' ) : $z ) );
			$name = is_array( $z ) ? strtolower( sanitize_text_field( (string) ( $z['name'] ?? '' ) ) ) : '';
			if ( preg_match( '/^[a-f0-9]{32}$/', $id ) ) {
				$zones[ $id ] = [ 'id' => $id, 'name' => preg_match( '/^[a-z0-9.\-]{1,253}$/', $name ) ? $name : $id ];
			}
		}
		$zones = array_slice( array_values( $zones ), 0, self::MAX_ZONES );

		$recipients = self::parse_recipients( is_array( $raw['recipients'] ?? null ) ? implode( ',', $raw['recipients'] ) : (string) ( $raw['recipients'] ?? '' ) );

		$range_default = $freq === 'monthly' ? 'month' : '7d';
		$range         = (string) ( $raw['range'] ?? $range_default );
		if ( ! isset( WPWAF_Analytics::RANGES[ $range ] ) ) {
			$range = $range_default;
		}

		$sections = [];
		$in       = is_array( $raw['sections'] ?? null ) ? $raw['sections'] : array_keys( self::SECTIONS );
		foreach ( array_keys( self::SECTIONS ) as $k ) {
			$sections[ $k ] = in_array( $k, $in, true ) || ! empty( $in[ $k ] );
		}

		$account = sanitize_text_field( (string) ( $raw['account'] ?? '' ) );
		$valid   = array_column( WPWAF_Accounts::for_js(), 'id' );
		if ( ! in_array( $account, $valid, true ) ) {
			$account = WPWAF_Accounts::active_id();
		}

		$report = [
			'id'          => (string) ( $existing['id'] ?? '' ),
			// Client name (older drafts sent it as "client").
			'name'        => mb_substr( sanitize_text_field( (string) ( ( $raw['name'] ?? '' ) !== '' ? $raw['name'] : ( $raw['client'] ?? '' ) ) ), 0, 120 ),
			'contact'     => mb_substr( sanitize_text_field( (string) ( $raw['contact'] ?? '' ) ), 0, 120 ),
			'notes'       => mb_substr( sanitize_textarea_field( (string) ( $raw['notes'] ?? '' ) ), 0, 1000 ),
			'account'     => $account,
			'zones'       => $zones,
			'recipients'  => $recipients,
			'frequency'   => $freq,
			'monthday'    => max( 1, min( 28, (int) ( $raw['monthday'] ?? 1 ) ) ),
			'hour'        => max( 0, min( 23, (int) ( $raw['hour'] ?? 8 ) ) ),
			'range'       => $range,
			'sections'    => $sections,
			'intro'       => mb_substr( sanitize_textarea_field( (string) ( $raw['intro'] ?? '' ) ), 0, 2000 ),
			// Automatic sending is on exactly when the report is set to monthly.
			'enabled'     => $freq === 'monthly',
			'last_sent'   => (int) ( $existing['last_sent'] ?? 0 ),
			'last_status' => (string) ( $existing['last_status'] ?? '' ),
			'next_run'    => 0,
		];
		if ( $report['name'] === '' ) {
			return [ $report, 'Enter the client\'s name.' ];
		}
		if ( ! $zones ) {
			return [ $report, 'Link at least one site (zone) to this client.' ];
		}
		if ( ! $recipients ) {
			return [ $report, 'Add at least one valid email address for this client.' ];
		}
		$report['next_run'] = ( $report['enabled'] && $freq !== 'manual' ) ? self::next_run( $report, time() ) : 0;
		return [ $report, '' ];
	}

	// ── Scheduling ───────────────────────────────────────────────────────────

	/**
	 * Next send time (UTC timestamp) strictly after $from, in the site's
	 * timezone: the chosen day of the month (1 to 28, so every month has it)
	 * at the chosen hour. Wall clock time is kept across daylight saving
	 * changes. Manual reports have no next run.
	 */
	public static function next_run( array $report, int $from ): int {
		$freq = (string) ( $report['frequency'] ?? 'manual' );
		if ( $freq !== 'monthly' ) {
			return 0;
		}
		$tz   = wp_timezone();
		$now  = ( new DateTimeImmutable( '@' . $from ) )->setTimezone( $tz );
		$hour = max( 0, min( 23, (int) ( $report['hour'] ?? 8 ) ) );

		$day  = max( 1, min( 28, (int) ( $report['monthday'] ?? 1 ) ) );
		$cand = $now->setDate( (int) $now->format( 'Y' ), (int) $now->format( 'n' ), $day )->setTime( $hour, 0 );
		if ( $cand->getTimestamp() <= $from ) {
			$cand = $now->setDate( (int) $now->format( 'Y' ), (int) $now->format( 'n' ) + 1, $day )->setTime( $hour, 0 );
		}
		return $cand->getTimestamp();
	}

	/**
	 * Hourly cron: send reports that are due. A short lock stops two
	 * overlapping cron requests sending the same report, and next_run is moved
	 * forward and saved before sending, so a slow or failed send is never
	 * repeated in the same period. Missed periods are not caught up.
	 */
	public static function run_due(): void {
		if ( get_transient( 'wpwaf_reports_lock' ) ) {
			return;
		}
		set_transient( 'wpwaf_reports_lock', 1, 10 * MINUTE_IN_SECONDS );

		$now     = time();
		$reports = self::all();
		$due     = [];
		foreach ( $reports as $id => $r ) {
			if ( ( $r['frequency'] ?? 'manual' ) === 'monthly' && (int) ( $r['next_run'] ?? 0 ) > 0 && (int) $r['next_run'] <= $now ) {
				$due[] = $id;
			}
		}
		$due = array_slice( $due, 0, self::SENDS_PER_RUN );
		foreach ( $due as $id ) {
			$reports[ $id ]['next_run'] = self::next_run( $reports[ $id ], $now );
		}
		if ( $due ) {
			self::save_all( $reports );
		}
		foreach ( $due as $id ) {
			// Automatic monthly reports always cover the last calendar month.
			self::send( $id, '', 'scheduled', [ 'range' => 'month' ] );
		}
		delete_transient( 'wpwaf_reports_lock' );
	}

	// ── Building and sending ─────────────────────────────────────────────────

	/** API client for a report's plugin account. */
	private static function api_for_report( array $report ): ?WPWAF_API {
		$api = WPWAF_Accounts::api_for( (string) ( $report['account'] ?? '' ) );
		if ( $api === null && WPWAF_Accounts::active() && ( $report['account'] ?? '' ) === WPWAF_Accounts::active_id() ) {
			$api = WPWAF_Accounts::api();
		}
		return $api;
	}

	/**
	 * Analytics for every zone in a report.
	 * Returns [ zones => [ [id, name, data], ... ], any_data => bool ].
	 */
	public static function gather( array $report, ?WPWAF_API $api, bool $sample, bool $fresh ): array {
		$out = [];
		$any = false;
		foreach ( (array) $report['zones'] as $i => $z ) {
			$data = $sample || $api === null
				? self::sample_data( (string) $report['range'], $i )
				: WPWAF_Analytics::report( $api, (string) $z['id'], (string) $report['range'], $fresh, 'rp_' . (string) ( $report['account'] ?? '' ) );
			$any   = $any || ! empty( $data['traffic']['ok'] );
			$out[] = [ 'id' => (string) $z['id'], 'name' => (string) $z['name'], 'data' => $data ];
		}
		return [ 'zones' => $out, 'any_data' => $any ];
	}

	/** Subject and HTML for a report. */
	public static function build( array $report, array $zones, array $branding ): array {
		$first  = $zones[0]['data'] ?? [];
		$period = self::period_text( (string) ( $first['since'] ?? '' ), (string) ( $first['until'] ?? '' ), (string) ( $report['range'] ?? '7d' ) );
		$who    = (string) ( $report['name'] ?? '' ) !== '' ? (string) $report['name'] : ( $zones[0]['name'] ?? 'Website' );
		$subject = sprintf( '%s website report: %s', $who, $period );

		$ctx = [
			'brand'    => $branding,
			'report'   => $report,
			'zones'    => $zones,
			'period'   => $period,
			'subject'  => $subject,
		];
		ob_start();
		self::render_email( $ctx );
		$html = (string) ob_get_clean();
		// Subjects only need line breaks removed (header safety); commas and quotes are fine.
		$subject = trim( (string) preg_replace( '/\s+/', ' ', sanitize_text_field( $subject ) ) );
		return [ 'subject' => $subject, 'html' => $html ];
	}

	/** Render the email template with its context. */
	private static function render_email( array $ctx ): void {
		$brand  = $ctx['brand'];
		$report = $ctx['report'];
		$zones  = $ctx['zones'];
		$period = $ctx['period'];
		include WPWAF_DIR . 'includes/views/email-report.php';
	}

	/**
	 * Apply one-off choices for a single send or preview: the period
	 * ('range'), the message ('intro') and which of the client's addresses
	 * to use ('recipients'). Recipients outside the client's own list are
	 * ignored, so a send can only ever go to addresses saved on the client.
	 */
	public static function with_options( array $report, array $opts ): array {
		if ( isset( $opts['range'] ) && isset( WPWAF_Analytics::RANGES[ (string) $opts['range'] ] ) ) {
			$report['range'] = (string) $opts['range'];
		}
		if ( array_key_exists( 'intro', $opts ) && $opts['intro'] !== null ) {
			$report['intro'] = mb_substr( sanitize_textarea_field( (string) $opts['intro'] ), 0, 2000 );
		}
		if ( isset( $opts['recipients'] ) && is_array( $opts['recipients'] ) ) {
			$allowed = [];
			foreach ( (array) $report['recipients'] as $r ) {
				$allowed[ strtolower( (string) $r ) ] = (string) $r;
			}
			$chosen = [];
			foreach ( $opts['recipients'] as $r ) {
				$k = strtolower( trim( (string) $r ) );
				if ( isset( $allowed[ $k ] ) ) {
					$chosen[ $k ] = $allowed[ $k ];
				}
			}
			$report['recipients'] = array_values( $chosen );
		}
		return $report;
	}

	/**
	 * Send a client's report. $to_override sends only to that address (test
	 * send). $trigger is 'scheduled', 'manual' or 'test' and is written to the
	 * log. $opts holds one-off choices (see with_options()).
	 */
	public static function send( string $id, string $to_override = '', string $trigger = 'manual', array $opts = [] ): array {
		$reports = self::all();
		$report  = $reports[ $id ] ?? null;
		if ( ! is_array( $report ) ) {
			return [ 'ok' => false, 'message' => 'Client not found.' ];
		}
		$report = self::with_options( $report, $opts );
		if ( $to_override === '' && ! $report['recipients'] ) {
			return [ 'ok' => false, 'message' => 'Choose at least one of the client\'s email addresses.' ];
		}
		$api = self::api_for_report( $report );
		if ( $api === null ) {
			$res = [ 'ok' => false, 'message' => 'The Cloudflare account for this client is no longer connected.' ];
		} else {
			$data = self::gather( $report, $api, false, true );
			if ( ! $data['any_data'] ) {
				$res = [ 'ok' => false, 'message' => 'No analytics could be read for this client\'s sites, so nothing was sent. Check the sites belong to the client\'s Cloudflare account and the token has Zone → Analytics → Read.' ];
			} else {
				$built = self::build( $report, $data['zones'], self::branding() );
				$to    = $to_override !== '' ? [ $to_override ] : (array) $report['recipients'];
				$sent  = wp_mail( $to, $built['subject'], $built['html'], self::headers() );
				$res   = $sent
					? [ 'ok' => true, 'message' => 'Sent to ' . count( $to ) . ' recipient' . ( count( $to ) === 1 ? '' : 's' ) . '.' ]
					: [ 'ok' => false, 'message' => 'WordPress could not send the email. Check the site\'s email setup (an SMTP plugin is recommended).' ];
			}
		}

		self::add_log( [
			'time'    => time(),
			'report'  => (string) $report['name'],
			'range'   => (string) ( $report['range'] ?? '' ),
			'trigger' => $trigger,
			'to'      => $to_override !== '' ? 1 : count( (array) $report['recipients'] ),
			'ok'      => $res['ok'],
			'message' => $res['message'],
		] );
		if ( $trigger !== 'test' ) {
			$reports               = self::all(); // re-read: next_run may have been saved by the cron
			if ( isset( $reports[ $id ] ) ) {
				$reports[ $id ]['last_sent']   = $res['ok'] ? time() : (int) ( $reports[ $id ]['last_sent'] ?? 0 );
				$reports[ $id ]['last_status'] = $res['message'];
				self::save_all( $reports );
			}
		}
		return $res;
	}

	/** Mail headers: HTML, From and Reply-To from the branding settings. */
	public static function headers(): array {
		$b       = self::branding();
		$headers = [ 'Content-Type: text/html; charset=UTF-8' ];
		$from    = sanitize_email( (string) $b['from_email'] );
		if ( $from !== '' && is_email( $from ) ) {
			$name      = self::clean_name( (string) ( $b['from_name'] !== '' ? $b['from_name'] : $b['agency_name'] ) );
			$headers[] = 'From: ' . ( $name !== '' ? $name . ' <' . $from . '>' : $from );
		}
		$reply = sanitize_email( (string) $b['reply_to'] );
		if ( $reply !== '' && is_email( $reply ) ) {
			$headers[] = 'Reply-To: ' . $reply;
		}
		return $headers;
	}

	// ── Formatting helpers (used by the email template) ──────────────────────

	public static function compact( int|float $n ): string {
		$n = (float) $n;
		if ( $n >= 1e9 ) return rtrim( rtrim( number_format( $n / 1e9, 1 ), '0' ), '.' ) . 'B';
		if ( $n >= 1e6 ) return rtrim( rtrim( number_format( $n / 1e6, 1 ), '0' ), '.' ) . 'M';
		if ( $n >= 1e4 ) return rtrim( rtrim( number_format( $n / 1e3, 1 ), '0' ), '.' ) . 'K';
		return number_format( $n );
	}

	public static function bytes( int|float $b ): string {
		$b = (float) $b;
		foreach ( [ 'TB' => 1e12, 'GB' => 1e9, 'MB' => 1e6, 'KB' => 1e3 ] as $u => $d ) {
			if ( $b >= $d ) {
				return rtrim( rtrim( number_format( $b / $d, 1 ), '0' ), '.' ) . ' ' . $u;
			}
		}
		return number_format( $b ) . ' B';
	}

	/** Percent change, or null when there is no previous value to compare with. */
	public static function change( int|float $cur, int|float $prev ): ?float {
		if ( $prev <= 0 ) {
			return null;
		}
		return round( ( $cur - $prev ) / $prev * 100, 1 );
	}

	public static function period_text( string $since, string $until, string $range ): string {
		$s = $since !== '' ? strtotime( $since ) : false;
		$u = $until !== '' ? strtotime( $until ) : false;
		if ( ! $s || ! $u ) {
			return WPWAF_Analytics::RANGES[ $range ] ?? '';
		}
		if ( $range === '24h' ) {
			return gmdate( 'M j, Y', $u );
		}
		if ( $range === 'month' ) {
			return gmdate( 'F Y', $s );
		}
		$end = $u - 1;
		$fmt = gmdate( 'Y', $s ) === gmdate( 'Y', $end ) ? 'M j' : 'M j, Y';
		return gmdate( $fmt, $s ) . ' to ' . gmdate( 'M j, Y', $end );
	}

	/** Mix a #RRGGBB color with white. $amount 0 = unchanged, 1 = white. */
	public static function tint( string $hex, float $amount ): string {
		$hex = ltrim( self::clean_color( $hex ), '#' );
		$rgb = array_map( 'hexdec', str_split( $hex, 2 ) );
		$out = '#';
		foreach ( $rgb as $c ) {
			$out .= str_pad( dechex( (int) round( $c + ( 255 - $c ) * $amount ) ), 2, '0', STR_PAD_LEFT );
		}
		return strtoupper( $out );
	}

	/** Brand color as used in the email (validated). */
	public static function color( array $brand ): string {
		return self::clean_color( (string) ( $brand['brand_color'] ?? '' ) );
	}

	/** Country name from a two-letter code. */
	public static function country( string $code ): string {
		$code = strtoupper( trim( $code ) );
		if ( ! preg_match( '/^[A-Z]{2}$/', $code ) ) {
			return $code === '' ? 'Unknown' : $code;
		}
		if ( $code === 'T1' ) return 'Tor network';
		if ( $code === 'XX' ) return 'Unknown';
		if ( class_exists( 'Locale' ) ) {
			$name = \Locale::getDisplayRegion( '-' . $code, 'en' );
			if ( is_string( $name ) && $name !== '' && $name !== $code ) {
				return $name;
			}
		}
		return $code;
	}

	/** Plain names for Cloudflare security sources. */
	public static function source_name( string $s ): string {
		$map = [
			'firewallCustom'  => 'Custom security rules',
			'firewallrules'   => 'Custom security rules',
			'firewallManaged' => 'Managed rules',
			'waf'             => 'Managed rules',
			'ratelimit'       => 'Rate limiting',
			'rateLimit'       => 'Rate limiting',
			'botFight'        => 'Bot protection',
			'botManagement'   => 'Bot protection',
			'securitylevel'   => 'Security level',
			'securityLevel'   => 'Security level',
			'ip'              => 'IP rules',
			'iprange'         => 'IP rules',
			'asn'             => 'Network rules',
			'country'         => 'Country rules',
			'uablock'         => 'User agent rules',
			'zonelockdown'    => 'Zone lockdown',
			'bic'             => 'Browser integrity check',
			'hot'             => 'Hotlink protection',
			'l7ddos'          => 'DDoS protection',
			'validation'      => 'Request validation',
		];
		if ( isset( $map[ $s ] ) ) {
			return $map[ $s ];
		}
		$s = trim( (string) preg_replace( '/(?<!^)[A-Z]/', ' $0', $s ) );
		return $s === '' ? 'Other' : ucfirst( strtolower( $s ) );
	}

	// ── Sample data (preview without a connected account) ────────────────────

	public static function sample_data( string $range, int $seed = 0 ): array {
		$win   = WPWAF_Analytics::window( $range );
		$days  = $win['granularity'] === 'hour' ? 24 : max( 1, (int) ceil( ( $win['until'] - $win['since'] ) / DAY_IN_SECONDS ) );
		$scale = 1 + $seed * 0.35;
		$series = [];
		$tot    = [ 'requests' => 0, 'cached' => 0, 'bytes' => 0, 'cached_bytes' => 0, 'threats' => 0, 'page_views' => 0, 'encrypted' => 0, 'uniques' => 0 ];
		for ( $i = 0; $i < $days; $i++ ) {
			$t    = $win['granularity'] === 'hour' ? gmdate( 'Y-m-d\TH:00:00\Z', $win['since'] + $i * HOUR_IN_SECONDS ) : gmdate( 'Y-m-d', $win['since'] + $i * DAY_IN_SECONDS );
			$wave = 0.75 + 0.25 * sin( $i / 1.7 + $seed ) + ( ( $i * 37 + $seed * 11 ) % 9 ) / 40;
			$req  = (int) round( ( $win['granularity'] === 'hour' ? 1800 : 42000 ) * $wave * $scale );
			$row  = [
				'requests'     => $req,
				'cached'       => (int) round( $req * 0.64 ),
				'bytes'        => $req * 48000,
				'cached_bytes' => (int) round( $req * 48000 * 0.7 ),
				'threats'      => (int) round( $req * 0.012 ),
				'page_views'   => (int) round( $req * 0.31 ),
				'encrypted'    => (int) round( $req * 0.99 ),
				'uniques'      => (int) round( $req * 0.09 ),
			];
			foreach ( $row as $k => $v ) {
				$tot[ $k ] += $v;
			}
			$series[] = array_merge( [ 't' => $t ], $row );
		}
		$totals = $tot;
		$totals['uncached']   = $tot['requests'] - $tot['cached'];
		$totals['cache_rate'] = $tot['requests'] ? round( $tot['cached'] / $tot['requests'] * 100, 1 ) : 0.0;
		$totals['ssl_rate']   = $tot['requests'] ? round( $tot['encrypted'] / $tot['requests'] * 100, 1 ) : 0.0;
		$prev = [];
		foreach ( $totals as $k => $v ) {
			$prev[ $k ] = is_float( $v ) ? $v - 3.1 : (int) round( $v * 0.88 );
		}
		$req = $tot['requests'];
		return [
			'zone_id'  => str_repeat( 'a', 32 ),
			'range'    => $range,
			'label'    => WPWAF_Analytics::RANGES[ $range ] ?? '',
			'since'    => gmdate( 'c', $win['since'] ),
			'until'    => gmdate( 'c', $win['until'] ),
			'sample'   => true,
			'traffic'  => [
				'ok'          => true,
				'granularity' => $win['granularity'],
				'series'      => $series,
				'totals'      => $totals,
				'previous'    => $prev,
				'countries'   => [
					[ 'key' => 'US', 'requests' => (int) ( $req * 0.46 ), 'bytes' => 0, 'threats' => 0 ],
					[ 'key' => 'GB', 'requests' => (int) ( $req * 0.14 ), 'bytes' => 0, 'threats' => 0 ],
					[ 'key' => 'CA', 'requests' => (int) ( $req * 0.11 ), 'bytes' => 0, 'threats' => 0 ],
					[ 'key' => 'DE', 'requests' => (int) ( $req * 0.08 ), 'bytes' => 0, 'threats' => 0 ],
					[ 'key' => 'AU', 'requests' => (int) ( $req * 0.06 ), 'bytes' => 0, 'threats' => 0 ],
				],
				'statuses'    => [
					[ 'key' => '200', 'requests' => (int) ( $req * 0.86 ) ],
					[ 'key' => '304', 'requests' => (int) ( $req * 0.07 ) ],
					[ 'key' => '301', 'requests' => (int) ( $req * 0.03 ) ],
					[ 'key' => '404', 'requests' => (int) ( $req * 0.035 ) ],
					[ 'key' => '503', 'requests' => (int) ( $req * 0.005 ) ],
				],
				'content_types' => [], 'browsers' => [], 'tls' => [], 'threat_types' => [], 'ip_classes' => [],
				'note'        => '',
			],
			'security' => [
				'ok'         => true,
				'total'      => (int) ( $req * 0.015 ),
				'by_action'  => [
					[ 'key' => 'block', 'count' => (int) ( $req * 0.011 ) ],
					[ 'key' => 'managed_challenge', 'count' => (int) ( $req * 0.004 ) ],
				],
				'by_source'  => [
					[ 'key' => 'firewallCustom', 'count' => (int) ( $req * 0.01 ) ],
					[ 'key' => 'botFight', 'count' => (int) ( $req * 0.003 ) ],
					[ 'key' => 'securitylevel', 'count' => (int) ( $req * 0.002 ) ],
				],
				'by_country' => [ [ 'key' => 'CN', 'count' => (int) ( $req * 0.004 ) ], [ 'key' => 'RU', 'count' => (int) ( $req * 0.003 ) ] ],
				'by_path'    => [ [ 'key' => '/wp-login.php', 'count' => (int) ( $req * 0.006 ) ], [ 'key' => '/xmlrpc.php', 'count' => (int) ( $req * 0.004 ) ] ],
				'by_ip'      => [],
				'series'     => [],
				'note'       => '',
			],
			'top'      => [
				'ok'    => true,
				'paths' => [
					[ 'key' => '/', 'count' => (int) ( $req * 0.12 ), 'bytes' => 0 ],
					[ 'key' => '/shop/', 'count' => (int) ( $req * 0.05 ), 'bytes' => 0 ],
					[ 'key' => '/about/', 'count' => (int) ( $req * 0.03 ), 'bytes' => 0 ],
					[ 'key' => '/contact/', 'count' => (int) ( $req * 0.02 ), 'bytes' => 0 ],
					[ 'key' => '/blog/spring-training-plan/', 'count' => (int) ( $req * 0.015 ), 'bytes' => 0 ],
				],
				'hosts' => [], 'devices' => [], 'browsers' => [], 'cache' => [], 'note' => '',
			],
		];
	}

	// ── AJAX ─────────────────────────────────────────────────────────────────

	/** Clients linked to a zone (for the Zone Status detail view). */
	public static function clients_for_zone( string $zone_id ): array {
		$out = [];
		foreach ( self::all() as $c ) {
			foreach ( (array) ( $c['zones'] ?? [] ) as $z ) {
				if ( ( $z['id'] ?? '' ) === $zone_id ) {
					$out[] = [ 'id' => (string) $c['id'], 'name' => (string) $c['name'], 'monthly' => ( $c['frequency'] ?? '' ) === 'monthly' ];
					break;
				}
			}
		}
		usort( $out, static fn( $a, $b ) => strcasecmp( $a['name'], $b['name'] ) );
		return $out;
	}

	/** Clients and log in the shape the screen uses. */
	public static function state(): array {
		$reports = array_values( self::all() );
		usort( $reports, static fn( $a, $b ) => strcasecmp( (string) $a['name'], (string) $b['name'] ) );
		foreach ( $reports as &$r ) {
			$r['next_text'] = self::fmt_time( (int) ( $r['next_run'] ?? 0 ) );
			$r['last_text'] = self::fmt_time( (int) ( $r['last_sent'] ?? 0 ) );
		}
		unset( $r );
		$log = self::log();
		foreach ( $log as &$l ) {
			$l['time_text'] = self::fmt_time( (int) ( $l['time'] ?? 0 ) );
		}
		unset( $l );
		return [ 'reports' => $reports, 'log' => $log ];
	}

	/** Date and time in the site's timezone, or '' for 0. */
	private static function fmt_time( int $ts ): string {
		return $ts > 0 ? (string) wp_date( 'M j, Y g:i a', $ts ) : '';
	}

	public static function ajax_state(): void {
		self::guard( false );
		wp_send_json_success( self::state() );
	}

	public static function ajax_branding_save(): void {
		self::guard( false );
		$d = self::post_json( 'branding' );
		$b = [
			'agency_name'     => mb_substr( self::clean_name( (string) ( $d['agency_name'] ?? '' ) ), 0, 120 ),
			'logo_url'        => self::clean_url( (string) ( $d['logo_url'] ?? '' ) ),
			'brand_color'     => self::clean_color( (string) ( $d['brand_color'] ?? '' ) ),
			'from_name'       => mb_substr( self::clean_name( (string) ( $d['from_name'] ?? '' ) ), 0, 120 ),
			'from_email'      => '',
			'reply_to'        => '',
			'footer_text'     => mb_substr( sanitize_textarea_field( (string) ( $d['footer_text'] ?? '' ) ), 0, 1000 ),
			'show_cloudflare' => ! empty( $d['show_cloudflare'] ),
		];
		foreach ( [ 'from_email', 'reply_to' ] as $k ) {
			$e = sanitize_email( (string) ( $d[ $k ] ?? '' ) );
			if ( $e !== '' && ! is_email( $e ) ) {
				wp_send_json_error( [ 'message' => 'That ' . ( $k === 'from_email' ? 'From' : 'Reply-To' ) . ' address is not a valid email.' ] );
			}
			$b[ $k ] = $e;
		}
		update_option( self::OPT_BRANDING, $b, false );
		wp_send_json_success( [ 'branding' => self::branding() ] );
	}

	public static function ajax_save(): void {
		self::guard( false );
		$raw     = self::post_json( 'report' );
		$reports = self::all();
		$id      = sanitize_key( (string) ( $raw['id'] ?? '' ) );
		$existing = ( $id !== '' && isset( $reports[ $id ] ) ) ? $reports[ $id ] : [];
		if ( ! $existing && count( $reports ) >= self::MAX_REPORTS ) {
			wp_send_json_error( [ 'message' => 'You have reached the limit of ' . self::MAX_REPORTS . ' clients.' ] );
		}
		[ $report, $err ] = self::sanitize_report( $raw, $existing );
		if ( $err !== '' ) {
			wp_send_json_error( [ 'message' => $err ] );
		}
		if ( $report['id'] === '' ) {
			$report['id'] = 'c' . strtolower( wp_generate_password( 12, false, false ) );
		}
		$reports[ $report['id'] ] = $report;
		self::save_all( $reports );
		wp_send_json_success( array_merge( self::state(), [ 'id' => $report['id'] ] ) );
	}

	public static function ajax_delete(): void {
		self::guard( false );
		$id      = sanitize_key( self::post( 'id' ) );
		$reports = self::all();
		unset( $reports[ $id ] );
		self::save_all( $reports );
		wp_send_json_success( self::state() );
	}

	/**
	 * Email preview. POST id (saved report) or report (draft JSON), and
	 * sample=1 to use sample data. Without a connected account sample data
	 * is always used, so the design can be checked before setup.
	 */
	public static function ajax_preview(): void {
		self::guard( false );
		$sample  = self::post_bool( 'sample' ) || ! WPWAF_Accounts::active();
		$id      = sanitize_key( self::post( 'id' ) );
		$reports = self::all();
		if ( $id !== '' && isset( $reports[ $id ] ) ) {
			$report = $reports[ $id ];
		} else {
			[ $report ] = self::sanitize_report( self::post_json( 'report' ) );
		}
		$report = self::with_options( $report, self::send_options() );
		if ( ! $report['zones'] ) {
			$sample           = true;
			$report['zones']  = [ [ 'id' => str_repeat( 'a', 32 ), 'name' => 'example.com' ] ];
		}
		$api  = $sample ? null : self::api_for_report( $report );
		$data = self::gather( $report, $api, $sample || $api === null, self::post_bool( 'refresh' ) );
		$built = self::build( $report, $data['zones'], self::branding() );
		wp_send_json_success( [
			'subject'  => $built['subject'],
			'html'     => $built['html'],
			'sample'   => $sample || $api === null,
			'any_data' => $data['any_data'],
		] );
	}

	/** One-off send choices posted by the Send report dialog. */
	private static function send_options(): array {
		$opts  = [];
		$range = self::post( 'range' );
		if ( $range !== '' ) {
			$opts['range'] = $range;
		}
		if ( isset( $_POST['intro'] ) ) {
			$opts['intro'] = sanitize_textarea_field( wp_unslash( (string) $_POST['intro'] ) );
		}
		if ( isset( $_POST['recipients'] ) ) {
			$opts['recipients'] = array_map( 'strval', self::post_json( 'recipients' ) );
		}
		return $opts;
	}

	/** Send a client's report now, or a test to the current user. */
	public static function ajax_send(): void {
		self::guard();
		$id   = sanitize_key( self::post( 'id' ) );
		$mode = self::post( 'mode' ) === 'test' ? 'test' : 'manual';
		$to   = '';
		if ( $mode === 'test' ) {
			$user = wp_get_current_user();
			$to   = sanitize_email( (string) ( $user->user_email ?? '' ) );
			if ( $to === '' || ! is_email( $to ) ) {
				wp_send_json_error( [ 'message' => 'Your WordPress user has no valid email address.' ] );
			}
		}
		$res = self::send( $id, $to, $mode, self::send_options() );
		$out = array_merge( self::state(), [ 'message' => $res['message'] ] );
		$res['ok'] ? wp_send_json_success( $out ) : wp_send_json_error( $out );
	}
}
