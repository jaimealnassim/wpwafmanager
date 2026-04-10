<?php
declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * DNS Manager helpers — validation and type definitions.
 */
class WPWAF_DNS {

	// All record types Cloudflare supports
	public const RECORD_TYPES = [
		'A', 'AAAA', 'CAA', 'CERT', 'CNAME', 'DNSKEY', 'DS',
		'HTTPS', 'LOC', 'MX', 'NAPTR', 'NS', 'PTR', 'SMIMEA',
		'SPF', 'SRV', 'SSHFP', 'SVCB', 'TLSA', 'TXT', 'URI',
	];

	// Types that support Cloudflare proxy (orange cloud)
	public const PROXYABLE = [ 'A', 'AAAA', 'CNAME', 'HTTPS', 'SRV', 'SVCB' ];

	// TTL options (0 = Auto)
	public const TTL_OPTIONS = [
		1     => 'Auto',
		60    => '1 min',
		120   => '2 min',
		300   => '5 min',
		600   => '10 min',
		900   => '15 min',
		1800  => '30 min',
		3600  => '1 hour',
		7200  => '2 hours',
		18000 => '5 hours',
		43200 => '12 hours',
		86400 => '1 day',
	];

	/**
	 * Sanitize and validate a record payload from user input.
	 * Returns [ 'valid' => bool, 'data' => array, 'error' => string ]
	 */
	public static function sanitize_record( array $raw ): array {
		$type = strtoupper( sanitize_text_field( $raw['type'] ?? '' ) );
		if ( ! in_array( $type, self::RECORD_TYPES, true ) ) {
			return [ 'valid' => false, 'error' => "Unsupported record type: {$type}" ];
		}

		$name    = sanitize_text_field( $raw['name'] ?? '' );
		$content = sanitize_textarea_field( $raw['content'] ?? '' );
		$ttl     = (int) ( $raw['ttl'] ?? 1 );
		$proxied = filter_var( $raw['proxied'] ?? false, FILTER_VALIDATE_BOOLEAN );
		$comment = sanitize_text_field( $raw['comment'] ?? '' );

		if ( $name === '' ) return [ 'valid' => false, 'error' => 'Name is required.' ];
		if ( $content === '' && $type !== 'CAA' ) return [ 'valid' => false, 'error' => 'Content is required.' ];

		$data = compact( 'type', 'name', 'content', 'ttl' );

		// Only proxyable types can be proxied
		if ( in_array( $type, self::PROXYABLE, true ) ) {
			$data['proxied'] = $proxied;
		} else {
			$data['proxied'] = false;
		}

		if ( $comment !== '' ) $data['comment'] = $comment;

		// Priority (MX, SRV, URI)
		if ( isset( $raw['priority'] ) && in_array( $type, [ 'MX', 'SRV', 'URI' ], true ) ) {
			$data['priority'] = (int) $raw['priority'];
		}

		// SRV data
		if ( $type === 'SRV' && isset( $raw['data'] ) && is_array( $raw['data'] ) ) {
			$data['data'] = [
				'service'  => sanitize_text_field( $raw['data']['service']  ?? '' ),
				'proto'    => sanitize_text_field( $raw['data']['proto']    ?? '_tcp' ),
				'name'     => sanitize_text_field( $raw['data']['name']     ?? '' ),
				'priority' => (int) ( $raw['data']['priority'] ?? 0 ),
				'weight'   => (int) ( $raw['data']['weight']   ?? 0 ),
				'port'     => (int) ( $raw['data']['port']     ?? 0 ),
				'target'   => sanitize_text_field( $raw['data']['target']   ?? '' ),
			];
			unset( $data['content'] );
		}

		// CAA data
		if ( $type === 'CAA' && isset( $raw['data'] ) && is_array( $raw['data'] ) ) {
			$data['data'] = [
				'flags' => (int) ( $raw['data']['flags'] ?? 0 ),
				'tag'   => sanitize_text_field( $raw['data']['tag']   ?? 'issue' ),
				'value' => sanitize_text_field( $raw['data']['value'] ?? '' ),
			];
			unset( $data['content'] );
		}

		return [ 'valid' => true, 'data' => $data ];
	}
}
