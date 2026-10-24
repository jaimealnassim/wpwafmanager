<?php
declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * DNS Manager helpers - validation and type definitions.
 */
class WPWAF_DNS {

	// All record types Cloudflare supports
	public const RECORD_TYPES = [
		'A', 'AAAA', 'CAA', 'CERT', 'CNAME', 'DNSKEY', 'DS',
		'HTTPS', 'LOC', 'MX', 'NAPTR', 'NS', 'PTR', 'SMIMEA',
		'SPF', 'SRV', 'SSHFP', 'SVCB', 'TLSA', 'TXT', 'URI',
	];

	// Types that support Cloudflare proxy (orange cloud)
	// Cloudflare proxies only the records used for IP address resolution.
	public const PROXYABLE = [ 'A', 'AAAA', 'CNAME' ];

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

		if ( $name === '' && $type === 'SRV' ) $name = '@';
		if ( $name === '' ) return [ 'valid' => false, 'error' => 'Name is required.' ];
		if ( $content === '' && ! in_array( $type, [ 'CAA', 'SRV' ], true ) ) return [ 'valid' => false, 'error' => 'Content is required.' ];

		$data = compact( 'type', 'name', 'content', 'ttl' );

		// Only proxyable types can be proxied
		if ( in_array( $type, self::PROXYABLE, true ) ) {
			$data['proxied'] = $proxied;
		} else {
			$data['proxied'] = false;
		}

		if ( $comment !== '' ) $data['comment'] = $comment;

		// Priority (MX, URI). SRV carries its priority inside data.
		if ( isset( $raw['priority'] ) && in_array( $type, [ 'MX', 'URI' ], true ) ) {
			$data['priority'] = (int) $raw['priority'];
		}

		// SRV: Cloudflare takes the service and protocol in the record name
		// (_sip._tcp.example.com) and priority, weight, port and target in data.
		if ( $type === 'SRV' ) {
			$d       = is_array( $raw['data'] ?? null ) ? $raw['data'] : [];
			$service = sanitize_text_field( (string) ( $d['service'] ?? '' ) );
			$proto   = sanitize_text_field( (string) ( $d['proto'] ?? '_tcp' ) );
			$target  = sanitize_text_field( (string) ( $d['target'] ?? '' ) );
			$port    = (int) ( $d['port'] ?? 0 );
			if ( $service !== '' && $service[0] !== '_' ) $service = '_' . $service;
			if ( ! preg_match( '/^_[a-z0-9-]{1,62}$/i', $service ) ) {
				return [ 'valid' => false, 'error' => 'Enter the SRV service, for example _sip.' ];
			}
			if ( ! in_array( $proto, [ '_tcp', '_udp', '_tls' ], true ) ) {
				return [ 'valid' => false, 'error' => 'Choose TCP, UDP or TLS for the SRV protocol.' ];
			}
			if ( $target === '' ) return [ 'valid' => false, 'error' => 'Enter the SRV target hostname.' ];
			if ( $port < 0 || $port > 65535 ) return [ 'valid' => false, 'error' => 'The SRV port must be between 0 and 65535.' ];

			$host         = ( $name === '@' ) ? '' : '.' . ltrim( $name, '.' );
			$data['name'] = $service . '.' . $proto . $host;
			$data['data'] = [
				'priority' => max( 0, min( 65535, (int) ( $d['priority'] ?? 0 ) ) ),
				'weight'   => max( 0, min( 65535, (int) ( $d['weight'] ?? 0 ) ) ),
				'port'     => $port,
				'target'   => $target,
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
