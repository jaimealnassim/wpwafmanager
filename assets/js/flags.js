/* WP WAF Manager: country flag images for the screens that build country rows in JavaScript.
 * window.wpwafFlag( code, spacer ) returns the flag <img> HTML, or, when there is no flag,
 * an empty spacer (spacer = true) so rows stay aligned, else an empty string.
 * Settings come from PHP (WPWAF_Flags) in window.wpwafFlagConfig. */
(function(){
	var c = window.wpwafFlagConfig || { base: '', codes: [] };
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
