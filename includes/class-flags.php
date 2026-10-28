<?php
declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Country flag images (assets/flags, 64x48 PNG, named by lowercase ISO 3166-1
 * alpha-2 code). Used instead of emoji flags, which Windows shows as two letters.
 */
final class WPWAF_Flags {

	/** Codes that have a flag image. */
	private const CODES = [
		'ad', 'ae', 'af', 'ag', 'ai', 'al', 'am', 'ao', 'aq', 'ar', 'as', 'at', 'au', 'aw', 'ax', 'az', 'ba', 'bb',
		'bd', 'be', 'bf', 'bg', 'bh', 'bi', 'bj', 'bl', 'bm', 'bn', 'bo', 'bq', 'br', 'bs', 'bt', 'bv', 'bw', 'by',
		'bz', 'ca', 'cc', 'cd', 'cf', 'cg', 'ch', 'ci', 'ck', 'cl', 'cm', 'cn', 'co', 'cr', 'cu', 'cv', 'cw', 'cx',
		'cy', 'cz', 'de', 'dj', 'dk', 'dm', 'do', 'dz', 'ec', 'ee', 'eg', 'eh', 'er', 'es', 'et', 'eu', 'fi', 'fj',
		'fk', 'fm', 'fo', 'fr', 'ga', 'gb', 'gd', 'ge', 'gf', 'gg', 'gh', 'gi', 'gl', 'gm', 'gn', 'gp', 'gq', 'gr',
		'gs', 'gt', 'gu', 'gw', 'gy', 'hk', 'hm', 'hn', 'hr', 'ht', 'hu', 'id', 'ie', 'il', 'im', 'in', 'io', 'iq',
		'ir', 'is', 'it', 'je', 'jm', 'jo', 'jp', 'ke', 'kg', 'kh', 'ki', 'km', 'kn', 'kp', 'kr', 'kw', 'ky', 'kz',
		'la', 'lb', 'lc', 'li', 'lk', 'lr', 'ls', 'lt', 'lu', 'lv', 'ly', 'ma', 'mc', 'md', 'me', 'mf', 'mg', 'mh',
		'mk', 'ml', 'mm', 'mn', 'mo', 'mp', 'mq', 'mr', 'ms', 'mt', 'mu', 'mv', 'mw', 'mx', 'my', 'mz', 'na', 'nc',
		'ne', 'nf', 'ng', 'ni', 'nl', 'no', 'np', 'nr', 'nu', 'nz', 'om', 'pa', 'pe', 'pf', 'pg', 'ph', 'pk', 'pl',
		'pm', 'pn', 'pr', 'ps', 'pt', 'pw', 'py', 'qa', 're', 'ro', 'rs', 'ru', 'rw', 'sa', 'sb', 'sc', 'sd', 'se',
		'sg', 'sh', 'si', 'sj', 'sk', 'sl', 'sm', 'sn', 'so', 'sr', 'ss', 'st', 'sv', 'sx', 'sy', 'sz', 'tc', 'td',
		'tf', 'tg', 'th', 'tj', 'tk', 'tl', 'tm', 'tn', 'to', 'tr', 'tt', 'tv', 'tw', 'tz', 'ua', 'ug', 'um', 'us',
		'uy', 'uz', 'va', 'vc', 've', 'vg', 'vi', 'vn', 'vu', 'wf', 'ws', 'ye', 'yt', 'za', 'zm', 'zw'
	];

	private static bool $printed = false;

	/** True when a flag image exists for the two-letter code. */
	public static function has( string $code ): bool {
		return in_array( strtolower( $code ), self::CODES, true );
	}

	/** URL of the flag for the code, or an empty string when there is none. */
	public static function url( string $code ): string {
		$code = strtolower( $code );
		return self::has( $code ) ? WPWAF_URL . 'assets/flags/' . $code . '.png' : '';
	}

	/**
	 * Flag <img> for the code. Decorative (empty alt): the country name or code
	 * is always shown next to it. Returns an empty string when there is no flag.
	 */
	public static function img( string $code ): string {
		$url = self::url( $code );
		if ( $url === '' ) return '';
		return sprintf(
			'<img class="wpwaf-flag" src="%s" alt="" width="20" height="15" loading="lazy" decoding="async">',
			esc_url( $url )
		);
	}

	/**
	 * Prints window.wpwafFlag( code, spacer ) once per page for the screens that
	 * build country rows in JavaScript. It returns the flag <img> HTML, or, when
	 * there is no flag (Tor, unknown), an empty spacer if spacer is true so rows
	 * stay aligned, else an empty string.
	 */
	public static function print_script(): void {
		if ( self::$printed ) return;
		self::$printed = true;
		$config = wp_json_encode( [
			'base'  => WPWAF_URL . 'assets/flags/',
			'codes' => self::CODES,
		] );
		?>
<script id="wpwaf-flags">
(function(){
	var c = <?php echo $config; // JSON from wp_json_encode. ?>;
	var set = {};
	c.codes.forEach(function(k){ set[k] = true; });
	window.wpwafFlag = function(code, spacer){
		var k = String(code || '').toLowerCase();
		if (/^[a-z]{2}$/.test(k) && set[k]) {
			return '<img class="wpwaf-flag" src="' + c.base + k + '.png" alt="" width="20" height="15" loading="lazy" decoding="async">';
		}
		return spacer ? '<span class="wpwaf-flag wpwaf-flag--none" aria-hidden="true"></span>' : '';
	};
})();
</script>
		<?php
	}
}
