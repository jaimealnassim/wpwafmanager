<?php
/**
 * White-label client report email.
 *
 * Variables: $brand (branding settings), $report (report settings), $zones
 * (list of [id, name, data] where data is WPWAF_Analytics::report() output),
 * $period (human period text).
 *
 * Email-safe by design: 600px table layout, inline styles only, no scripts,
 * no SVG, no external CSS. Charts are built from table cells. Sections a
 * zone's plan cannot provide are left out quietly.
 */
defined( 'ABSPATH' ) || exit;

$c       = WPWAF_Reports::color( $brand );
$c_light = WPWAF_Reports::tint( $c, 0.62 );
$c_wash  = WPWAF_Reports::tint( $c, 0.92 );
$ink     = '#111827';
$muted   = '#6b7280';
$line    = '#e5e7eb';
$font    = 'Arial,Helvetica,sans-serif';
$agency  = (string) ( $brand['agency_name'] ?? '' );
$sec     = (array) ( $report['sections'] ?? [] );
$on      = static fn( string $k ): bool => ! empty( $sec[ $k ] );
// Greet the contact person when one is set, otherwise the client by name.
$client  = (string) ( ( $report['contact'] ?? '' ) !== '' ? $report['contact'] : ( $report['name'] ?? '' ) );
$inner   = 536; // 600 minus 32px padding each side

/** Small helpers kept local to the template. */
$e   = static fn( $v ): string => esc_html( (string) $v );
$num = static fn( $n ): string => esc_html( WPWAF_Reports::compact( (float) $n ) );

$delta = static function ( $cur, $prev, bool $good_when_up = true, bool $points = false ) use ( $muted ): string {
	if ( $points ) {
		if ( ! is_numeric( $prev ) || (float) $prev <= 0 ) {
			return '';
		}
		$d = round( (float) $cur - (float) $prev, 1 );
	} else {
		$d = WPWAF_Reports::change( (float) $cur, (float) $prev );
		if ( $d === null ) {
			return '';
		}
	}
	if ( abs( $d ) < 0.1 ) {
		return '<span style="color:' . $muted . ';">No change</span>';
	}
	$up    = $d > 0;
	$color = $up === $good_when_up ? '#047857' : '#b91c1c';
	$arrow = $up ? '&#9650;' : '&#9660;';
	$text  = ( $up ? '+' : '' ) . number_format( $d, 1 ) . ( $points ? ' pts' : '%' );
	return '<span style="color:' . $color . ';">' . $arrow . ' ' . esc_html( $text ) . '</span>';
};

$heading = static function ( string $title, string $sub = '' ) use ( $c, $ink, $muted, $font ): string {
	return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 12px;"><tr>'
		. '<td style="border-left:3px solid ' . $c . ';padding:0 0 0 10px;font-family:' . $font . ';">'
		. '<div style="font-size:15px;line-height:20px;font-weight:bold;color:' . $ink . ';">' . esc_html( $title ) . '</div>'
		. ( $sub !== '' ? '<div style="font-size:12px;line-height:18px;color:' . $muted . ';">' . esc_html( $sub ) . '</div>' : '' )
		. '</td></tr></table>';
};

/** A horizontal bar row: label, bar, value. */
$hbar = static function ( string $label, float $value, float $max, string $value_text, string $color ) use ( $ink, $muted, $font, $line ): string {
	$pct = $max > 0 ? max( 1, (int) round( $value / $max * 100 ) ) : 0;
	return '<tr>'
		. '<td width="170" style="width:170px;padding:5px 10px 5px 0;font-family:' . $font . ';font-size:13px;line-height:18px;color:' . $ink . ';word-break:break-word;">' . esc_html( $label ) . '</td>'
		. '<td style="padding:5px 0;">'
		. '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>'
		. ( $pct > 0 ? '<td width="' . $pct . '%" style="width:' . $pct . '%;height:10px;line-height:10px;font-size:0;background:' . $color . ';border-radius:5px;">&nbsp;</td>' : '' )
		. ( $pct < 100 ? '<td style="height:10px;line-height:10px;font-size:0;">&nbsp;</td>' : '' )
		. '</tr></table></td>'
		. '<td width="90" align="right" style="width:90px;padding:5px 0 5px 10px;font-family:' . $font . ';font-size:13px;line-height:18px;color:' . $muted . ';white-space:nowrap;">' . esc_html( $value_text ) . '</td>'
		. '</tr>';
};

$section_open  = '<tr><td style="padding:8px 32px 20px;">';
$section_close = '</td></tr>';

// ── Totals across all zones (for the overview and the preheader) ─────────────
$all_req = 0;
$all_thr = 0;
foreach ( $zones as $z ) {
	$t        = $z['data']['traffic'] ?? [];
	$all_req += (int) ( $t['totals']['requests'] ?? 0 );
	$all_thr += (int) ( $t['totals']['threats'] ?? 0 );
}
$preheader = sprintf(
	'%s requests and %s threats stopped, %s.',
	WPWAF_Reports::compact( $all_req ),
	WPWAF_Reports::compact( $all_thr ),
	$period
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="x-apple-disable-message-reformatting">
<title><?php echo $e( $agency !== '' ? $agency . ' website report' : 'Website report' ); ?></title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;-webkit-text-size-adjust:100%;">
<div style="display:none;max-height:0;overflow:hidden;opacity:0;color:#f3f4f6;"><?php echo $e( $preheader ); ?></div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f3f4f6;">
<tr><td align="center" style="padding:24px 12px;">

<table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px;max-width:600px;background:#ffffff;border:1px solid <?php echo $line; ?>;border-radius:12px;">
	<tr><td style="height:5px;line-height:5px;font-size:0;background:<?php echo $c; ?>;border-radius:12px 12px 0 0;">&nbsp;</td></tr>

	<!-- Header -->
	<tr><td style="padding:24px 32px 8px;">
		<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
			<td valign="middle" style="font-family:<?php echo $font; ?>;">
				<?php if ( ! empty( $brand['logo_url'] ) ) : ?>
					<img src="<?php echo esc_url( (string) $brand['logo_url'] ); ?>" alt="<?php echo esc_attr( $agency ); ?>" height="40" style="display:block;height:40px;width:auto;max-width:220px;border:0;outline:none;text-decoration:none;">
				<?php else : ?>
					<div style="font-size:18px;line-height:24px;font-weight:bold;color:<?php echo $ink; ?>;"><?php echo $e( $agency ); ?></div>
				<?php endif; ?>
			</td>
			<td valign="middle" align="right" style="font-family:<?php echo $font; ?>;">
				<div style="font-size:11px;line-height:16px;font-weight:bold;letter-spacing:1px;text-transform:uppercase;color:<?php echo $muted; ?>;">Website report</div>
				<div style="font-size:13px;line-height:18px;color:<?php echo $ink; ?>;"><?php echo $e( $period ); ?></div>
			</td>
		</tr></table>
	</td></tr>

	<!-- Greeting -->
	<tr><td style="padding:16px 32px 8px;font-family:<?php echo $font; ?>;font-size:15px;line-height:23px;color:<?php echo $ink; ?>;">
		<p style="margin:0 0 10px;">Hi<?php echo $client !== '' ? ' ' . $e( $client ) : ''; ?>,</p>
		<?php if ( trim( (string) ( $report['intro'] ?? '' ) ) !== '' ) : ?>
			<p style="margin:0 0 10px;"><?php echo nl2br( $e( $report['intro'] ) ); ?></p>
		<?php else : ?>
			<p style="margin:0 0 10px;">Here is how your website performed <?php echo count( $zones ) > 1 ? 'across your sites' : ''; ?> for <?php echo $e( $period ); ?>, including visitors, speed and the security protection working in the background.</p>
		<?php endif; ?>
	</td></tr>

	<?php if ( count( $zones ) > 1 ) : ?>
	<!-- Overview of all sites -->
	<?php echo $section_open; ?>
		<?php echo $heading( 'All sites at a glance' ); ?>
		<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid <?php echo $line; ?>;border-radius:8px;">
			<tr style="background:#f9fafb;">
				<?php foreach ( [ 'Site', 'Visitors', 'Requests', 'Threats stopped', 'From cache' ] as $i => $h ) : ?>
				<td <?php echo $i ? 'align="right"' : ''; ?> style="padding:9px 10px;font-family:<?php echo $font; ?>;font-size:11px;font-weight:bold;letter-spacing:.5px;text-transform:uppercase;color:<?php echo $muted; ?>;border-bottom:1px solid <?php echo $line; ?>;"><?php echo $e( $h ); ?></td>
				<?php endforeach; ?>
			</tr>
			<?php foreach ( $zones as $z ) :
				$t  = $z['data']['traffic'] ?? [];
				$ok = ! empty( $t['ok'] );
				$tt = $t['totals'] ?? [];
			?>
			<tr>
				<td style="padding:9px 10px;font-family:<?php echo $font; ?>;font-size:13px;color:<?php echo $ink; ?>;border-bottom:1px solid <?php echo $line; ?>;word-break:break-all;"><?php echo $e( $z['name'] ); ?></td>
				<?php foreach ( [ 'uniques', 'requests', 'threats' ] as $k ) : ?>
				<td align="right" style="padding:9px 10px;font-family:<?php echo $font; ?>;font-size:13px;color:<?php echo $ink; ?>;border-bottom:1px solid <?php echo $line; ?>;"><?php echo $ok ? $num( $tt[ $k ] ?? 0 ) : '-'; ?></td>
				<?php endforeach; ?>
				<td align="right" style="padding:9px 10px;font-family:<?php echo $font; ?>;font-size:13px;color:<?php echo $ink; ?>;border-bottom:1px solid <?php echo $line; ?>;"><?php echo $ok ? $e( number_format( (float) ( $tt['cache_rate'] ?? 0 ), 0 ) . '%' ) : '-'; ?></td>
			</tr>
			<?php endforeach; ?>
		</table>
	<?php echo $section_close; ?>
	<?php endif; ?>

	<?php foreach ( $zones as $zi => $z ) :
		$d   = $z['data'] ?? [];
		$t   = $d['traffic'] ?? [];
		$s   = $d['security'] ?? [];
		$top = $d['top'] ?? [];
		if ( empty( $t['ok'] ) && empty( $s['ok'] ) && empty( $top['ok'] ) ) {
			continue; // nothing readable for this zone: leave it out quietly
		}
		$tt  = $t['totals'] ?? [];
		$pv  = $t['previous'] ?? [];
		$hourly = ( $t['granularity'] ?? 'day' ) === 'hour';

		// Security split into blocked and challenged, from the firewall events.
		$blocked    = 0;
		$challenged = 0;
		foreach ( (array) ( $s['by_action'] ?? [] ) as $a ) {
			$k = strtolower( (string) ( $a['key'] ?? '' ) );
			if ( in_array( $k, [ 'block', 'drop' ], true ) ) {
				$blocked += (int) $a['count'];
			} elseif ( str_contains( $k, 'challenge' ) ) {
				$challenged += (int) $a['count'];
			}
		}
	?>

	<!-- Site: <?php echo $e( $z['name'] ); ?> -->
	<tr><td style="padding:<?php echo $zi === 0 && count( $zones ) === 1 ? '8px' : '20px'; ?> 32px 4px;">
		<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:<?php echo $c_wash; ?>;border-radius:8px;"><tr>
			<td style="padding:12px 16px;font-family:<?php echo $font; ?>;">
				<div style="font-size:11px;line-height:16px;font-weight:bold;letter-spacing:1px;text-transform:uppercase;color:<?php echo $muted; ?>;">Website</div>
				<div style="font-size:19px;line-height:26px;font-weight:bold;color:<?php echo $ink; ?>;word-break:break-all;"><?php echo $e( $z['name'] ); ?></div>
			</td>
		</tr></table>
	</td></tr>

	<?php if ( $on( 'summary' ) && ! empty( $t['ok'] ) ) :
		$tiles = [
			[ 'Unique visitors', $num( $tt['uniques'] ?? 0 ), $delta( $tt['uniques'] ?? 0, $pv['uniques'] ?? 0 ) ],
			[ 'Page views', $num( $tt['page_views'] ?? 0 ), $delta( $tt['page_views'] ?? 0, $pv['page_views'] ?? 0 ) ],
			[ 'Requests', $num( $tt['requests'] ?? 0 ), $delta( $tt['requests'] ?? 0, $pv['requests'] ?? 0 ) ],
			[ 'Threats stopped', $num( $tt['threats'] ?? 0 ), $delta( $tt['threats'] ?? 0, $pv['threats'] ?? 0, false ) ],
			[ 'Served from cache', $e( number_format( (float) ( $tt['cache_rate'] ?? 0 ), 0 ) . '%' ), $delta( $tt['cache_rate'] ?? 0, $pv['cache_rate'] ?? 0, true, true ) ],
			[ 'Data delivered', $e( WPWAF_Reports::bytes( (float) ( $tt['bytes'] ?? 0 ) ) ), $delta( $tt['bytes'] ?? 0, $pv['bytes'] ?? 0 ) ],
		];
		$req  = (int) ( $tt['requests'] ?? 0 );
		$chg  = WPWAF_Reports::change( $req, (float) ( $pv['requests'] ?? 0 ) );
		$says = [];
		$says[] = 'Your site handled ' . WPWAF_Reports::compact( $req ) . ' requests from about ' . WPWAF_Reports::compact( (int) ( $tt['uniques'] ?? 0 ) ) . ' unique visitors'
			. ( $chg === null ? '.' : ( abs( $chg ) < 1 ? ', about the same as the previous period.' : ', ' . ( $chg > 0 ? 'up ' : 'down ' ) . number_format( abs( $chg ), 0 ) . '% on the previous period.' ) );
		if ( $req > 0 ) {
			$says[] = number_format( (float) ( $tt['cache_rate'] ?? 0 ), 0 ) . '% of requests were served straight from Cloudflare\'s cache, which keeps your site fast and takes load off your server.';
		}
		if ( ! empty( $s['ok'] ) && ( $blocked + $challenged ) > 0 ) {
			$says[] = 'Cloudflare blocked ' . WPWAF_Reports::compact( $blocked ) . ' requests' . ( $challenged ? ' and challenged ' . WPWAF_Reports::compact( $challenged ) . ' suspicious visitors' : '' ) . ' before they reached your site.';
		} elseif ( (int) ( $tt['threats'] ?? 0 ) > 0 ) {
			$says[] = 'Cloudflare stopped ' . WPWAF_Reports::compact( (int) $tt['threats'] ) . ' threats before they reached your site.';
		} else {
			$says[] = 'No threats reached your site in this period.';
		}
	?>
	<?php echo $section_open; ?>
		<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
			<?php foreach ( array_chunk( $tiles, 3 ) as $row ) : ?>
			<tr>
				<?php foreach ( $row as $i => $tile ) : ?>
				<td width="33%" valign="top" style="width:33%;padding:<?php echo $i === 0 ? '0 6px 12px 0' : ( $i === 2 ? '0 0 12px 6px' : '0 6px 12px' ); ?>;">
					<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid <?php echo $line; ?>;border-radius:8px;"><tr>
						<td style="padding:12px 12px 10px;font-family:<?php echo $font; ?>;">
							<div style="font-size:11px;line-height:15px;color:<?php echo $muted; ?>;"><?php echo $e( $tile[0] ); ?></div>
							<div style="font-size:22px;line-height:30px;font-weight:bold;color:<?php echo $ink; ?>;"><?php echo $tile[1]; ?></div>
							<div style="font-size:11px;line-height:15px;min-height:15px;"><?php echo $tile[2] !== '' ? $tile[2] : '&nbsp;'; ?></div>
						</td>
					</tr></table>
				</td>
				<?php endforeach; ?>
			</tr>
			<?php endforeach; ?>
		</table>
		<p style="margin:4px 0 0;font-family:<?php echo $font; ?>;font-size:14px;line-height:22px;color:#374151;"><?php echo $e( implode( ' ', $says ) ); ?></p>
		<p style="margin:6px 0 0;font-family:<?php echo $font; ?>;font-size:11px;line-height:16px;color:<?php echo $muted; ?>;">Changes compare with the previous period of the same length.</p>
	<?php echo $section_close; ?>
	<?php endif; ?>

	<?php if ( $on( 'traffic' ) && ! empty( $t['ok'] ) && ! empty( $t['series'] ) ) :
		$series = array_values( (array) $t['series'] );
		$n      = count( $series );
		$max    = max( 1, max( array_map( static fn( $r ) => (int) ( $r['requests'] ?? 0 ), $series ) ) );
		$colw   = max( 6, (int) floor( $inner / $n ) );
		$barw   = min( 28, max( 4, $colw - ( $colw > 14 ? 4 : 2 ) ) ); // thin bars, the rest of the column is air
		$H      = 120;
		$every  = $hourly ? 6 : ( $n <= 8 ? 1 : ( $n <= 16 ? 2 : 5 ) );
	?>
	<?php echo $section_open; ?>
		<?php echo $heading( $hourly ? 'Traffic by hour' : 'Traffic by day', 'Requests, split by where they were served from' ); ?>
		<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="<?php echo (int) ( $colw * $n ); ?>" style="width:<?php echo (int) ( $colw * $n ); ?>px;max-width:100%;">
			<tr>
				<?php foreach ( $series as $row ) :
					$req = (int) ( $row['requests'] ?? 0 );
					$ca  = min( $req, (int) ( $row['cached'] ?? 0 ) );
					$h   = $req > 0 ? max( 2, (int) round( $req / $max * $H ) ) : 0;
					$hc  = $req > 0 ? (int) round( $h * $ca / $req ) : 0;
					$hu  = $h - $hc;
					$tip = ( $hourly ? gmdate( 'M j H:00', (int) strtotime( (string) $row['t'] ) ) : gmdate( 'M j', (int) strtotime( (string) $row['t'] ) ) ) . ': ' . number_format( $req ) . ' requests';
				?>
				<td width="<?php echo $colw; ?>" valign="bottom" align="center" style="width:<?php echo $colw; ?>px;height:<?php echo $H; ?>px;vertical-align:bottom;padding:0;" title="<?php echo esc_attr( $tip ); ?>">
					<?php if ( $h > 0 ) : ?>
					<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="<?php echo $barw; ?>" style="width:<?php echo $barw; ?>px;">
						<?php if ( $hu > 0 ) : ?><tr><td height="<?php echo $hu; ?>" style="height:<?php echo $hu; ?>px;line-height:<?php echo $hu; ?>px;font-size:0;background:<?php echo $c_light; ?>;border-radius:<?php echo $barw >= 8 ? '3px 3px 0 0' : '0'; ?>;">&nbsp;</td></tr><?php endif; ?>
						<?php if ( $hc > 0 ) : ?><tr><td height="<?php echo $hc; ?>" style="height:<?php echo $hc; ?>px;line-height:<?php echo $hc; ?>px;font-size:0;background:<?php echo $c; ?>;<?php echo $hu === 0 && $barw >= 8 ? 'border-radius:3px 3px 0 0;' : ''; ?>">&nbsp;</td></tr><?php endif; ?>
					</table>
					<?php endif; ?>
				</td>
				<?php endforeach; ?>
			</tr>
			<tr>
				<?php foreach ( $series as $i => $row ) :
					$ts = (int) strtotime( (string) $row['t'] );
					$lb = '';
					if ( $i % $every === 0 ) {
						$lb = $hourly ? gmdate( 'H:00', $ts ) : ( $n <= 8 ? gmdate( 'D j', $ts ) : gmdate( 'M j', $ts ) );
					}
				?>
				<td width="<?php echo $colw; ?>" align="<?php echo $barw < $colw - 8 ? 'center' : 'left'; ?>" valign="top" style="width:<?php echo $colw; ?>px;padding:6px 0 0;font-family:<?php echo $font; ?>;font-size:10px;line-height:12px;color:<?php echo $muted; ?>;white-space:nowrap;border-top:1px solid <?php echo $line; ?>;"><?php echo $lb !== '' ? $e( $lb ) : '&nbsp;'; ?></td>
				<?php endforeach; ?>
			</tr>
		</table>
		<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin-top:10px;"><tr>
			<td style="width:10px;height:10px;line-height:10px;font-size:0;background:<?php echo $c; ?>;border-radius:2px;">&nbsp;</td>
			<td style="padding:0 16px 0 6px;font-family:<?php echo $font; ?>;font-size:12px;color:<?php echo $muted; ?>;">From Cloudflare's cache</td>
			<td style="width:10px;height:10px;line-height:10px;font-size:0;background:<?php echo $c_light; ?>;border-radius:2px;">&nbsp;</td>
			<td style="padding:0 0 0 6px;font-family:<?php echo $font; ?>;font-size:12px;color:<?php echo $muted; ?>;">From your server</td>
		</tr></table>
		<p style="margin:8px 0 0;font-family:<?php echo $font; ?>;font-size:12px;line-height:18px;color:<?php echo $muted; ?>;">Busiest <?php echo $hourly ? 'hour' : 'day'; ?>: <?php
			$peak = array_reduce( $series, static fn( $carry, $r ) => ( $carry === null || (int) $r['requests'] > (int) $carry['requests'] ) ? $r : $carry );
			echo $e( ( $hourly ? gmdate( 'M j, H:00', (int) strtotime( (string) $peak['t'] ) ) . ' UTC' : gmdate( 'l, M j', (int) strtotime( (string) $peak['t'] ) ) ) . ' with ' . number_format( (int) $peak['requests'] ) . ' requests.' );
		?></p>
	<?php echo $section_close; ?>
	<?php endif; ?>

	<?php if ( $on( 'cache' ) && ! empty( $t['ok'] ) && (int) ( $tt['requests'] ?? 0 ) > 0 ) :
		$rate  = (float) ( $tt['cache_rate'] ?? 0 );
		$rate_w = max( 1, min( 100, (int) round( $rate ) ) );
		$bytes = (float) ( $tt['bytes'] ?? 0 );
		$cb    = (float) ( $tt['cached_bytes'] ?? 0 );
		$saved = $bytes > 0 ? round( $cb / $bytes * 100 ) : 0;
	?>
	<?php echo $section_open; ?>
		<?php echo $heading( 'Speed and bandwidth' ); ?>
		<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
			<tr><td style="padding:0 0 6px;font-family:<?php echo $font; ?>;font-size:13px;color:<?php echo $ink; ?>;"><strong><?php echo $e( number_format( $rate, 0 ) . '%' ); ?></strong> of requests served from cache</td></tr>
			<tr><td style="padding:0 0 14px;">
				<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f3f4f6;border-radius:6px;"><tr>
					<td width="<?php echo $rate_w; ?>%" style="width:<?php echo $rate_w; ?>%;height:12px;line-height:12px;font-size:0;background:<?php echo $c; ?>;border-radius:6px;">&nbsp;</td>
					<?php if ( $rate_w < 100 ) : ?><td style="height:12px;line-height:12px;font-size:0;">&nbsp;</td><?php endif; ?>
				</tr></table>
			</td></tr>
			<tr><td style="font-family:<?php echo $font; ?>;font-size:14px;line-height:22px;color:#374151;">
				Cloudflare delivered <strong><?php echo $e( WPWAF_Reports::bytes( $bytes ) ); ?></strong> of pages, images and files to your visitors<?php echo $cb > 0 ? ', and <strong>' . $e( WPWAF_Reports::bytes( $cb ) ) . '</strong> (' . $e( $saved . '%' ) . ') of that came from its cache instead of your server' : ''; ?>.
				<?php if ( (float) ( $tt['ssl_rate'] ?? 0 ) > 0 ) : ?> <?php echo $e( number_format( (float) $tt['ssl_rate'], 0 ) . '%' ); ?> of requests used a secure HTTPS connection.<?php endif; ?>
			</td></tr>
		</table>
	<?php echo $section_close; ?>
	<?php endif; ?>

	<?php if ( $on( 'countries' ) && ! empty( $t['ok'] ) && ! empty( $t['countries'] ) ) :
		$rows  = array_slice( (array) $t['countries'], 0, 6 );
		$maxc  = max( 1, (int) ( $rows[0]['requests'] ?? 1 ) );
		$total = max( 1, (int) ( $tt['requests'] ?? 1 ) );
	?>
	<?php echo $section_open; ?>
		<?php echo $heading( 'Where your visitors come from', 'Share of all requests' ); ?>
		<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
			<?php foreach ( $rows as $row ) : ?>
				<?php echo $hbar( WPWAF_Reports::country( (string) $row['key'] ), (float) $row['requests'], (float) $maxc, number_format( (int) $row['requests'] / $total * 100, 1 ) . '%', $c ); ?>
			<?php endforeach; ?>
		</table>
	<?php echo $section_close; ?>
	<?php endif; ?>

	<?php if ( $on( 'security' ) ) :
		$show_sec = ! empty( $s['ok'] ) && (int) ( $s['total'] ?? 0 ) > 0;
		$thr      = (int) ( $tt['threats'] ?? 0 );
		if ( $show_sec || $thr > 0 || ! empty( $t['ok'] ) ) :
	?>
	<?php echo $section_open; ?>
		<?php echo $heading( 'Security', 'What Cloudflare stopped for you' ); ?>
		<?php if ( $show_sec ) :
			$acts = [];
			if ( $blocked ) $acts[] = [ 'Blocked', $blocked ];
			if ( $challenged ) $acts[] = [ 'Challenged (asked to prove they are human)', $challenged ];
			$other = max( 0, (int) $s['total'] - $blocked - $challenged );
			if ( $other ) $acts[] = [ 'Logged or allowed by your rules', $other ];
			$maxa = max( 1, max( array_column( $acts, 1 ) ) );
		?>
			<p style="margin:0 0 10px;font-family:<?php echo $font; ?>;font-size:14px;line-height:22px;color:#374151;">Cloudflare checked suspicious traffic <?php echo $e( number_format( (int) $s['total'] ) ); ?> times<?php echo ! empty( $s['note'] ) ? ' in the period your plan includes' : ''; ?>.</p>
			<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
				<?php foreach ( $acts as $a ) : echo $hbar( $a[0], (float) $a[1], (float) $maxa, number_format( (int) $a[1] ), '#b91c1c' ); endforeach; ?>
			</table>
			<?php
			$src = array_slice( (array) ( $s['by_source'] ?? [] ), 0, 3 );
			$pth = array_slice( (array) ( $s['by_path'] ?? [] ), 0, 3 );
			if ( $src || $pth ) : ?>
			<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:12px;"><tr>
				<?php if ( $src ) : ?>
				<td valign="top" width="50%" style="width:50%;padding-right:8px;font-family:<?php echo $font; ?>;">
					<div style="font-size:11px;font-weight:bold;letter-spacing:.5px;text-transform:uppercase;color:<?php echo $muted; ?>;padding-bottom:4px;">Top protection</div>
					<?php foreach ( $src as $r ) : ?><div style="font-size:13px;line-height:20px;color:<?php echo $ink; ?>;"><?php echo $e( WPWAF_Reports::source_name( (string) $r['key'] ) ); ?> <span style="color:<?php echo $muted; ?>;">(<?php echo $num( $r['count'] ); ?>)</span></div><?php endforeach; ?>
				</td>
				<?php endif; ?>
				<?php if ( $pth ) : ?>
				<td valign="top" width="50%" style="width:50%;padding-left:8px;font-family:<?php echo $font; ?>;">
					<div style="font-size:11px;font-weight:bold;letter-spacing:.5px;text-transform:uppercase;color:<?php echo $muted; ?>;padding-bottom:4px;">Most targeted pages</div>
					<?php foreach ( $pth as $r ) : ?><div style="font-size:13px;line-height:20px;color:<?php echo $ink; ?>;word-break:break-all;"><?php echo $e( $r['key'] ); ?> <span style="color:<?php echo $muted; ?>;">(<?php echo $num( $r['count'] ); ?>)</span></div><?php endforeach; ?>
				</td>
				<?php endif; ?>
			</tr></table>
			<?php endif; ?>
		<?php elseif ( $thr > 0 ) : ?>
			<p style="margin:0;font-family:<?php echo $font; ?>;font-size:14px;line-height:22px;color:#374151;">Cloudflare stopped <strong><?php echo $e( number_format( $thr ) ); ?></strong> threats before they reached your site, such as known bad bots and attack attempts.</p>
		<?php else : ?>
			<p style="margin:0;font-family:<?php echo $font; ?>;font-size:14px;line-height:22px;color:#374151;">No threats reached your site in this period. Cloudflare's protection stays on around the clock.</p>
		<?php endif; ?>
	<?php echo $section_close; ?>
	<?php endif; endif; ?>

	<?php if ( $on( 'pages' ) && ! empty( $top['ok'] ) && ! empty( $top['paths'] ) ) :
		$rows = array_slice( (array) $top['paths'], 0, 6 );
		$maxp = max( 1, (int) ( $rows[0]['count'] ?? 1 ) );
	?>
	<?php echo $section_open; ?>
		<?php echo $heading( 'Most visited pages', ! empty( $top['note'] ) ? 'Visitor requests in the most recent period your plan includes' : 'Visitor requests' ); ?>
		<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
			<?php foreach ( $rows as $row ) : echo $hbar( (string) $row['key'], (float) $row['count'], (float) $maxp, WPWAF_Reports::compact( (int) $row['count'] ), $c ); endforeach; ?>
		</table>
	<?php echo $section_close; ?>
	<?php endif; ?>

	<?php if ( $on( 'statuses' ) && ! empty( $t['ok'] ) && ! empty( $t['statuses'] ) ) :
		$groups = [ '2' => 0, '3' => 0, '4' => 0, '5' => 0 ];
		foreach ( (array) $t['statuses'] as $row ) {
			$g = substr( (string) $row['key'], 0, 1 );
			if ( isset( $groups[ $g ] ) ) {
				$groups[ $g ] += (int) $row['requests'];
			}
		}
		$labels = [
			'2' => 'Loaded successfully',
			'3' => 'Redirected or not changed',
			'4' => 'Not found or not allowed',
			'5' => 'Server errors',
		];
		$colors = [ '2' => '#047857', '3' => $c_light, '4' => '#d97706', '5' => '#b91c1c' ];
		$maxs   = max( 1, max( $groups ) );
		$sum    = max( 1, array_sum( $groups ) );
	?>
	<?php echo $section_open; ?>
		<?php echo $heading( 'Page responses', 'How your site answered requests' ); ?>
		<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
			<?php foreach ( $groups as $g => $v ) : if ( $v <= 0 ) continue; echo $hbar( $labels[ $g ], (float) $v, (float) $maxs, number_format( $v / $sum * 100, 1 ) . '%', $colors[ $g ] ); endforeach; ?>
		</table>
		<?php if ( $groups['5'] > 0 && $groups['5'] / $sum >= 0.01 ) : ?>
		<p style="margin:8px 0 0;font-family:<?php echo $font; ?>;font-size:12px;line-height:18px;color:<?php echo $muted; ?>;">Server errors were above 1% this period. We are keeping an eye on it.</p>
		<?php endif; ?>
	<?php echo $section_close; ?>
	<?php endif; ?>

	<?php endforeach; ?>

	<!-- Footer -->
	<tr><td style="padding:20px 32px 26px;border-top:1px solid <?php echo $line; ?>;font-family:<?php echo $font; ?>;font-size:12px;line-height:18px;color:<?php echo $muted; ?>;">
		<?php if ( $agency !== '' ) : ?><div style="font-weight:bold;color:<?php echo $ink; ?>;"><?php echo $e( $agency ); ?></div><?php endif; ?>
		<?php if ( trim( (string) ( $brand['footer_text'] ?? '' ) ) !== '' ) : ?><div><?php echo nl2br( $e( $brand['footer_text'] ) ); ?></div><?php endif; ?>
		<?php if ( ! empty( $brand['show_cloudflare'] ) ) : ?><div style="margin-top:8px;">Data provided by Cloudflare.</div><?php endif; ?>
	</td></tr>
</table>

<table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px;max-width:600px;"><tr>
	<td align="center" style="padding:14px 12px 0;font-family:<?php echo $font; ?>;font-size:11px;line-height:16px;color:#9ca3af;">
		You receive this report because <?php echo $e( $agency !== '' ? $agency : 'your web team' ); ?> looks after your website. Reply to this email with any questions.
	</td>
</tr></table>

</td></tr>
</table>
</body>
</html>
