<?php
declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Detailed zone analytics for the Zone Status detail view and client reports.
 *
 * Everything comes from the Cloudflare GraphQL Analytics API. What a zone can
 * query depends on its plan, so the zone's own "settings" node is read first
 * (enabled, maxDuration, notOlderThan per dataset) and each section is built
 * from the datasets that zone actually has:
 *
 *   traffic   httpRequests1hGroups (24 hours) or httpRequests1dGroups (days):
 *             time series, totals, previous period, and the country, status,
 *             content type, browser, TLS, threat and IP class breakdowns.
 *   security  firewallEventsAdaptiveGroups: events by action, source,
 *             country, path and IP, plus an hourly series.
 *   top       httpRequestsAdaptiveGroups (visitor traffic only): top paths,
 *             hostnames, devices, browsers and cache status.
 *
 * Each section is fetched on its own, so one that the plan or token cannot
 * read is reported as unavailable without hiding the rest.
 */
final class WPWAF_Analytics extends WPWAF_Module {

	public const RANGES = [
		'24h'   => 'Last 24 hours',
		'7d'    => 'Last 7 days',
		'30d'   => 'Last 30 days',
		'month' => 'Last calendar month',
	];

	private const DATASETS = [ 'httpRequests1hGroups', 'httpRequests1dGroups', 'httpRequestsAdaptiveGroups', 'firewallEventsAdaptiveGroups' ];

	/** Minutes a detail result is cached, to stay well inside the GraphQL rate limit. */
	private const CACHE_MINUTES = 10;

	public static function init(): void {
		add_action( 'wp_ajax_wpwaf_zs_detail', [ self::class, 'ajax_detail' ] );
	}

	// ── AJAX ─────────────────────────────────────────────────────────────────

	public static function ajax_detail(): void {
		self::guard();
		$zone_id = self::post_id( 'zone_id' );
		$range   = self::post( 'range', '7d' );
		if ( ! isset( self::RANGES[ $range ] ) ) {
			$range = '7d';
		}
		$report = self::report( self::api(), $zone_id, $range, self::post_bool( 'refresh' ), WPWAF_Accounts::active_id() );
		wp_send_json_success( $report );
	}

	// ── Public API (also used by client reports) ─────────────────────────────

	/**
	 * Full analytics for one zone and range. $cache_scope keeps cached results
	 * apart per plugin account; pass '' to skip the cache entirely.
	 */
	public static function report( WPWAF_API $api, string $zone_id, string $range, bool $refresh = false, string $cache_scope = '' ): array {
		$key = 'wpwaf_zsd_' . md5( $cache_scope . '|' . $zone_id . '|' . $range );
		if ( $cache_scope !== '' && ! $refresh ) {
			$hit = get_transient( $key );
			if ( is_array( $hit ) ) {
				$hit['cached'] = true;
				return $hit;
			}
		}

		$win      = self::window( $range );
		$datasets = self::datasets( $api, $zone_id );
		$report   = [
			'zone_id'      => $zone_id,
			'range'        => $range,
			'label'        => self::RANGES[ $range ],
			'since'        => gmdate( 'c', $win['since'] ),
			'until'        => gmdate( 'c', $win['until'] ),
			'generated_at' => time(),
			'datasets'     => $datasets,
			'traffic'      => self::traffic( $api, $zone_id, $win, $datasets ),
			'security'     => self::security( $api, $zone_id, $win, $datasets ),
			'top'          => self::top( $api, $zone_id, $win, $datasets ),
			'cached'       => false,
		];

		if ( $cache_scope !== '' ) {
			set_transient( $key, $report, self::CACHE_MINUTES * MINUTE_IN_SECONDS );
		}
		return $report;
	}

	/**
	 * Time window for a range, in UTC timestamps, plus the previous window of
	 * the same length for comparisons and the granularity to chart it at.
	 */
	public static function window( string $range, ?int $now = null ): array {
		$now = $now ?? time();
		switch ( $range ) {
			case '24h':
				$until = $now;
				$since = $now - DAY_IN_SECONDS;
				return [ 'since' => $since, 'until' => $until, 'prev_since' => $since - DAY_IN_SECONDS, 'prev_until' => $since, 'granularity' => 'hour' ];
			case '30d':
				$days = 30;
				break;
			case 'month':
				$first_this = strtotime( gmdate( 'Y-m-01 00:00:00', $now ) . ' UTC' );
				$first_prev = strtotime( gmdate( 'Y-m-01 00:00:00', $first_this - DAY_IN_SECONDS ) . ' UTC' );
				$first_pp   = strtotime( gmdate( 'Y-m-01 00:00:00', $first_prev - DAY_IN_SECONDS ) . ' UTC' );
				return [ 'since' => $first_prev, 'until' => $first_this, 'prev_since' => $first_pp, 'prev_until' => $first_prev, 'granularity' => 'day' ];
			default:
				$days = 7;
		}
		// Whole days, ending with today (which is still in progress).
		$today = strtotime( gmdate( 'Y-m-d 00:00:00', $now ) . ' UTC' );
		$since = $today - ( $days - 1 ) * DAY_IN_SECONDS;
		$until = $now;
		return [ 'since' => $since, 'until' => $until, 'prev_since' => $since - $days * DAY_IN_SECONDS, 'prev_until' => $since, 'granularity' => 'day' ];
	}

	/**
	 * Which analytics datasets this zone can query, from its settings node.
	 * Unknown (settings could not be read) is reported as null so callers
	 * still try the query and report a failure if it happens.
	 */
	public static function datasets( WPWAF_API $api, string $zone_id ): array {
		$fields = '';
		foreach ( self::DATASETS as $d ) {
			$fields .= "{$d}{enabled maxDuration notOlderThan}";
		}
		$res = $api->graphql(
			'query ZoneSettings($zoneTag:String!){viewer{zones(filter:{zoneTag:$zoneTag}){settings{' . $fields . '}}}}',
			[ 'zoneTag' => $zone_id ]
		);
		$set = $res['data']['viewer']['zones'][0]['settings'] ?? [];
		$out = [];
		foreach ( self::DATASETS as $d ) {
			$s         = is_array( $set[ $d ] ?? null ) ? $set[ $d ] : null;
			$out[ $d ] = $s === null ? null : [
				'enabled'        => ! empty( $s['enabled'] ),
				'max_duration'   => (int) ( $s['maxDuration'] ?? 0 ),
				'not_older_than' => (int) ( $s['notOlderThan'] ?? 0 ),
			];
		}
		return $out;
	}

	// ── Traffic ──────────────────────────────────────────────────────────────

	private const TRAFFIC_SUM = 'requests cachedRequests bytes cachedBytes threats pageViews encryptedRequests';
	private const TRAFFIC_MAPS = 'countryMap{clientCountryName requests bytes threats} responseStatusMap{edgeResponseStatus requests} contentTypeMap{edgeResponseContentTypeName requests bytes} browserMap{uaBrowserFamily pageViews} clientSSLMap{clientSSLProtocol requests} threatPathingMap{threatPathingName requests} ipClassMap{ipType requests}';

	private static function traffic( WPWAF_API $api, string $zone_id, array $win, array $datasets ): array {
		$hourly  = $win['granularity'] === 'hour';
		$dataset = $hourly ? 'httpRequests1hGroups' : 'httpRequests1dGroups';
		$info    = $datasets[ $dataset ] ?? null;
		if ( is_array( $info ) && ! $info['enabled'] ) {
			return [ 'ok' => false, 'message' => 'Traffic analytics are not available for this zone.' ];
		}
		// Stay inside the dataset's lookback and maximum query length.
		$clamped = self::clamp( $win, $info );
		$win     = array_merge( $win, [ 'since' => $clamped['since'], 'until' => $clamped['until'] ] );

		if ( $hourly ) {
			$filter = '{datetime_geq:$since,datetime_lt:$until}';
			$pfilter = '{datetime_geq:$psince,datetime_lt:$puntil}';
			$vars   = [
				'since'  => gmdate( 'Y-m-d\TH:00:00\Z', $win['since'] ),
				'until'  => gmdate( 'Y-m-d\TH:i:s\Z', $win['until'] ),
				'psince' => gmdate( 'Y-m-d\TH:00:00\Z', $win['prev_since'] ),
				'puntil' => gmdate( 'Y-m-d\TH:00:00\Z', $win['prev_until'] ),
			];
			$types  = '$since:Time!,$until:Time!,$psince:Time!,$puntil:Time!';
			$dim    = 'datetime';
		} else {
			$filter = '{date_geq:$since,date_leq:$until}';
			$pfilter = '{date_geq:$psince,date_leq:$puntil}';
			$vars   = [
				'since'  => gmdate( 'Y-m-d', $win['since'] ),
				'until'  => gmdate( 'Y-m-d', $win['until'] - 1 ),
				'psince' => gmdate( 'Y-m-d', $win['prev_since'] ),
				'puntil' => gmdate( 'Y-m-d', $win['prev_until'] - 1 ),
			];
			$types  = '$since:Date!,$until:Date!,$psince:Date!,$puntil:Date!';
			$dim    = 'date';
		}
		$vars['zoneTag'] = $zone_id;

		$build = static function ( bool $maps ) use ( $dataset, $filter, $pfilter, $types, $dim ): string {
			return 'query ZoneTraffic($zoneTag:String!,' . $types . '){viewer{zones(filter:{zoneTag:$zoneTag}){'
				. "series:{$dataset}(limit:1000,filter:{$filter},orderBy:[{$dim}_ASC]){dimensions{{$dim}} sum{" . self::TRAFFIC_SUM . '} uniq{uniques}}'
				. "totals:{$dataset}(limit:1,filter:{$filter}){sum{" . self::TRAFFIC_SUM . ( $maps ? ' ' . self::TRAFFIC_MAPS : '' ) . '} uniq{uniques}}'
				. "previous:{$dataset}(limit:1,filter:{$pfilter}){sum{" . self::TRAFFIC_SUM . '} uniq{uniques}}'
				. '}}}';
		};

		$res     = $api->graphql( $build( true ), $vars );
		$no_maps = false;
		if ( ! $res['success'] ) {
			// Retry without the breakdown maps in case a field is not offered.
			$res     = $api->graphql( $build( false ), $vars );
			$no_maps = true;
		}
		if ( ! $res['success'] ) {
			return [ 'ok' => false, 'message' => self::friendly( $res['message'] ) ];
		}

		$zone   = $res['data']['viewer']['zones'][0] ?? [];
		$series = [];
		foreach ( (array) ( $zone['series'] ?? [] ) as $g ) {
			$t = (string) ( $g['dimensions'][ $dim ] ?? '' );
			if ( $t === '' ) {
				continue;
			}
			$series[] = array_merge( [ 't' => $t ], self::sum_row( $g ) );
		}
		$totals   = self::sum_groups( (array) ( $zone['totals'] ?? [] ) );
		$previous = self::sum_groups( (array) ( $zone['previous'] ?? [] ) );
		$maps     = self::maps( (array) ( $zone['totals'] ?? [] ) );

		return [
			'ok'            => true,
			'granularity'   => $hourly ? 'hour' : 'day',
			'series'        => $series,
			'totals'        => $totals,
			'previous'      => $previous,
			'countries'     => $maps['countries'],
			'statuses'      => $maps['statuses'],
			'content_types' => $maps['content_types'],
			'browsers'      => $maps['browsers'],
			'tls'           => $maps['tls'],
			'threat_types'  => $maps['threat_types'],
			'ip_classes'    => $maps['ip_classes'],
			'maps_missing'  => $no_maps,
			'note'          => $clamped['note'],
		];
	}

	private static function sum_row( array $g ): array {
		$s = is_array( $g['sum'] ?? null ) ? $g['sum'] : [];
		return [
			'requests'     => (int) ( $s['requests'] ?? 0 ),
			'cached'       => (int) ( $s['cachedRequests'] ?? 0 ),
			'bytes'        => (int) ( $s['bytes'] ?? 0 ),
			'cached_bytes' => (int) ( $s['cachedBytes'] ?? 0 ),
			'threats'      => (int) ( $s['threats'] ?? 0 ),
			'page_views'   => (int) ( $s['pageViews'] ?? 0 ),
			'encrypted'    => (int) ( $s['encryptedRequests'] ?? 0 ),
			'uniques'      => (int) ( $g['uniq']['uniques'] ?? 0 ),
		];
	}

	/** Add up groups (normally one) and derive the rates the screen shows. */
	private static function sum_groups( array $groups ): array {
		$t = [ 'requests' => 0, 'cached' => 0, 'bytes' => 0, 'cached_bytes' => 0, 'threats' => 0, 'page_views' => 0, 'encrypted' => 0, 'uniques' => 0 ];
		foreach ( $groups as $g ) {
			foreach ( self::sum_row( (array) $g ) as $k => $v ) {
				$t[ $k ] += $v;
			}
		}
		$t['uncached']   = max( 0, $t['requests'] - $t['cached'] );
		$t['cache_rate'] = $t['requests'] > 0 ? round( $t['cached'] / $t['requests'] * 100, 1 ) : 0.0;
		$t['ssl_rate']   = $t['requests'] > 0 ? round( $t['encrypted'] / $t['requests'] * 100, 1 ) : 0.0;
		return $t;
	}

	/** Merge the breakdown maps from the totals group(s), largest first. */
	private static function maps( array $groups ): array {
		$acc = [ 'countries' => [], 'statuses' => [], 'content_types' => [], 'browsers' => [], 'tls' => [], 'threat_types' => [], 'ip_classes' => [] ];
		$add = static function ( string $bucket, string $key, array $vals ) use ( &$acc ): void {
			if ( $key === '' ) {
				return;
			}
			foreach ( $vals as $k => $v ) {
				$acc[ $bucket ][ $key ][ $k ] = ( $acc[ $bucket ][ $key ][ $k ] ?? 0 ) + (int) $v;
			}
		};
		foreach ( $groups as $g ) {
			$s = is_array( $g['sum'] ?? null ) ? $g['sum'] : [];
			foreach ( (array) ( $s['countryMap'] ?? [] ) as $m ) {
				$add( 'countries', (string) ( $m['clientCountryName'] ?? '' ), [ 'requests' => $m['requests'] ?? 0, 'bytes' => $m['bytes'] ?? 0, 'threats' => $m['threats'] ?? 0 ] );
			}
			foreach ( (array) ( $s['responseStatusMap'] ?? [] ) as $m ) {
				$add( 'statuses', (string) ( $m['edgeResponseStatus'] ?? '' ), [ 'requests' => $m['requests'] ?? 0 ] );
			}
			foreach ( (array) ( $s['contentTypeMap'] ?? [] ) as $m ) {
				$add( 'content_types', (string) ( $m['edgeResponseContentTypeName'] ?? '' ), [ 'requests' => $m['requests'] ?? 0, 'bytes' => $m['bytes'] ?? 0 ] );
			}
			foreach ( (array) ( $s['browserMap'] ?? [] ) as $m ) {
				$add( 'browsers', (string) ( $m['uaBrowserFamily'] ?? '' ), [ 'page_views' => $m['pageViews'] ?? 0 ] );
			}
			foreach ( (array) ( $s['clientSSLMap'] ?? [] ) as $m ) {
				$add( 'tls', (string) ( $m['clientSSLProtocol'] ?? '' ), [ 'requests' => $m['requests'] ?? 0 ] );
			}
			foreach ( (array) ( $s['threatPathingMap'] ?? [] ) as $m ) {
				$add( 'threat_types', (string) ( $m['threatPathingName'] ?? '' ), [ 'requests' => $m['requests'] ?? 0 ] );
			}
			foreach ( (array) ( $s['ipClassMap'] ?? [] ) as $m ) {
				$add( 'ip_classes', (string) ( $m['ipType'] ?? '' ), [ 'requests' => $m['requests'] ?? 0 ] );
			}
		}
		$out = [];
		foreach ( $acc as $bucket => $rows ) {
			$list = [];
			foreach ( $rows as $key => $vals ) {
				$list[] = array_merge( [ 'key' => (string) $key ], $vals );
			}
			$sort = $bucket === 'browsers' ? 'page_views' : 'requests';
			usort( $list, static fn( $a, $b ) => ( $b[ $sort ] ?? 0 ) <=> ( $a[ $sort ] ?? 0 ) );
			$out[ $bucket ] = array_slice( $list, 0, 25 );
		}
		return $out;
	}

	// ── Security ─────────────────────────────────────────────────────────────

	private static function security( WPWAF_API $api, string $zone_id, array $win, array $datasets ): array {
		$info = $datasets['firewallEventsAdaptiveGroups'] ?? null;
		if ( is_array( $info ) && ! $info['enabled'] ) {
			return [ 'ok' => false, 'message' => 'Security event analytics are not included with this zone\'s Cloudflare plan.' ];
		}
		$clamped = self::clamp( $win, $info );
		$vars    = [
			'zoneTag' => $zone_id,
			'since'   => gmdate( 'Y-m-d\TH:i:s\Z', $clamped['since'] ),
			'until'   => gmdate( 'Y-m-d\TH:i:s\Z', $clamped['until'] ),
		];
		$f   = '{datetime_geq:$since,datetime_lt:$until}';
		$grp = static fn( string $alias, string $dim, int $limit ): string =>
			"{$alias}:firewallEventsAdaptiveGroups(limit:{$limit},filter:{$f},orderBy:[count_DESC]){count dimensions{{$dim}}}";

		$q = 'query ZoneSecurity($zoneTag:String!,$since:Time!,$until:Time!){viewer{zones(filter:{zoneTag:$zoneTag}){'
			. $grp( 'byAction', 'action', 20 )
			. $grp( 'bySource', 'source', 20 )
			. $grp( 'byCountry', 'clientCountryName', 15 )
			. $grp( 'byPath', 'clientRequestPath', 15 )
			. $grp( 'byIP', 'clientIP', 15 )
			. "series:firewallEventsAdaptiveGroups(limit:1000,filter:{$f},orderBy:[datetimeHour_ASC]){count dimensions{datetimeHour}}"
			. '}}}';
		$res = $api->graphql( $q, $vars );
		if ( ! $res['success'] ) {
			return [ 'ok' => false, 'message' => self::friendly( $res['message'] ) ];
		}
		$zone = $res['data']['viewer']['zones'][0] ?? [];
		$list = static function ( string $alias, string $dim ) use ( $zone ): array {
			$out = [];
			foreach ( (array) ( $zone[ $alias ] ?? [] ) as $g ) {
				$out[] = [ 'key' => (string) ( $g['dimensions'][ $dim ] ?? '' ), 'count' => (int) ( $g['count'] ?? 0 ) ];
			}
			return $out;
		};
		$by_action = $list( 'byAction', 'action' );
		$series    = [];
		foreach ( (array) ( $zone['series'] ?? [] ) as $g ) {
			$series[] = [ 't' => (string) ( $g['dimensions']['datetimeHour'] ?? '' ), 'count' => (int) ( $g['count'] ?? 0 ) ];
		}
		if ( $win['granularity'] === 'day' ) {
			$series = self::hours_to_days( $series );
		}
		return [
			'ok'          => true,
			'total'       => array_sum( array_column( $by_action, 'count' ) ),
			'by_action'   => $by_action,
			'by_source'   => $list( 'bySource', 'source' ),
			'by_country'  => $list( 'byCountry', 'clientCountryName' ),
			'by_path'     => $list( 'byPath', 'clientRequestPath' ),
			'by_ip'       => $list( 'byIP', 'clientIP' ),
			'series'      => $series,
			'granularity' => $win['granularity'],
			'note'        => $clamped['note'],
		];
	}

	// ── Top lists (visitor traffic) ──────────────────────────────────────────

	private static function top( WPWAF_API $api, string $zone_id, array $win, array $datasets ): array {
		$info = $datasets['httpRequestsAdaptiveGroups'] ?? null;
		if ( is_array( $info ) && ! $info['enabled'] ) {
			return [ 'ok' => false, 'message' => 'Top pages and devices are not included with this zone\'s Cloudflare plan.' ];
		}
		$clamped = self::clamp( $win, $info );
		$vars    = [
			'zoneTag' => $zone_id,
			'since'   => gmdate( 'Y-m-d\TH:i:s\Z', $clamped['since'] ),
			'until'   => gmdate( 'Y-m-d\TH:i:s\Z', $clamped['until'] ),
		];
		$f   = '{datetime_geq:$since,datetime_lt:$until,requestSource:"eyeball"}';
		$grp = static fn( string $alias, string $dim, int $limit ): string =>
			"{$alias}:httpRequestsAdaptiveGroups(limit:{$limit},filter:{$f},orderBy:[count_DESC]){count sum{edgeResponseBytes} dimensions{{$dim}}}";

		$q = 'query ZoneTop($zoneTag:String!,$since:Time!,$until:Time!){viewer{zones(filter:{zoneTag:$zoneTag}){'
			. $grp( 'paths', 'clientRequestPath', 15 )
			. $grp( 'hosts', 'clientRequestHTTPHost', 10 )
			. $grp( 'devices', 'clientDeviceType', 10 )
			. $grp( 'browsers', 'userAgentBrowser', 10 )
			. $grp( 'cache', 'cacheStatus', 15 )
			. '}}}';
		$res = $api->graphql( $q, $vars );
		if ( ! $res['success'] ) {
			return [ 'ok' => false, 'message' => self::friendly( $res['message'] ) ];
		}
		$zone = $res['data']['viewer']['zones'][0] ?? [];
		$list = static function ( string $alias, string $dim ) use ( $zone ): array {
			$out = [];
			foreach ( (array) ( $zone[ $alias ] ?? [] ) as $g ) {
				$out[] = [
					'key'   => (string) ( $g['dimensions'][ $dim ] ?? '' ),
					'count' => (int) ( $g['count'] ?? 0 ),
					'bytes' => (int) ( $g['sum']['edgeResponseBytes'] ?? 0 ),
				];
			}
			return $out;
		};
		return [
			'ok'       => true,
			'paths'    => $list( 'paths', 'clientRequestPath' ),
			'hosts'    => $list( 'hosts', 'clientRequestHTTPHost' ),
			'devices'  => $list( 'devices', 'clientDeviceType' ),
			'browsers' => $list( 'browsers', 'userAgentBrowser' ),
			'cache'    => $list( 'cache', 'cacheStatus' ),
			'note'     => $clamped['note'],
		];
	}

	// ── Helpers ──────────────────────────────────────────────────────────────

	/**
	 * Fit a window inside a dataset's lookback (notOlderThan) and maximum
	 * query length (maxDuration). Returns the window and a note when shortened.
	 */
	private static function clamp( array $win, ?array $info ): array {
		$since = $win['since'];
		$until = $win['until'];
		$note  = '';
		if ( is_array( $info ) ) {
			if ( $info['not_older_than'] > 0 ) {
				$since = max( $since, time() - $info['not_older_than'] + 60 );
			}
			if ( $info['max_duration'] > 0 && $until - $since > $info['max_duration'] ) {
				$since = $until - $info['max_duration'];
			}
			if ( $until <= $since ) {
				$since = $until - 3600;
			}
			if ( $since > $win['since'] ) {
				$note = 'This zone\'s plan allows ' . self::span( $until - $since ) . ' of this data, so this section covers ' . self::span( $until - $since ) . ' up to ' . gmdate( 'M j, H:i', $until ) . ' UTC.';
			}
		}
		return [ 'since' => $since, 'until' => $until, 'note' => $note ];
	}

	private static function span( int $seconds ): string {
		if ( $seconds >= DAY_IN_SECONDS * 2 ) {
			return (int) round( $seconds / DAY_IN_SECONDS ) . ' days';
		}
		if ( $seconds >= DAY_IN_SECONDS ) {
			return '1 day';
		}
		return max( 1, (int) round( $seconds / HOUR_IN_SECONDS ) ) . ' hours';
	}

	private static function hours_to_days( array $series ): array {
		$days = [];
		foreach ( $series as $row ) {
			$d = substr( (string) $row['t'], 0, 10 );
			if ( $d !== '' ) {
				$days[ $d ] = ( $days[ $d ] ?? 0 ) + (int) $row['count'];
			}
		}
		$out = [];
		foreach ( $days as $d => $c ) {
			$out[] = [ 't' => $d, 'count' => $c ];
		}
		return $out;
	}

	/** Plain-language version of the most common GraphQL errors. */
	private static function friendly( string $msg ): string {
		$m = strtolower( $msg );
		if ( str_contains( $m, 'not authorized' ) || str_contains( $m, 'authentication' ) || str_contains( $m, 'permission' ) ) {
			return 'Your API token needs Zone → Analytics → Read for this section. (' . $msg . ')';
		}
		if ( str_contains( $m, 'does not have access' ) || str_contains( $m, 'not entitled' ) || str_contains( $m, 'plan' ) ) {
			return 'Not included with this zone\'s Cloudflare plan. (' . $msg . ')';
		}
		if ( str_contains( $m, 'rate limit' ) || str_contains( $m, 'quota' ) ) {
			return 'Cloudflare\'s analytics rate limit was reached. Wait a few minutes and refresh. (' . $msg . ')';
		}
		return $msg;
	}
}
