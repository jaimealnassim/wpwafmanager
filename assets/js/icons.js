/* WP WAF Manager: SVG icon helpers for the admin screens (shapes after Lucide, ISC License).
 * The icon shapes come from PHP (WPWAF_Icons::ICONS) in window.wpwafIconShapes. */
(function(){
	var I = window.wpwafIconShapes || {};
	function esc(s){ return String(s == null ? '' : s).replace(/[&<>"']/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }
	/* Inline SVG markup for an icon name ('' when unknown). */
	window.wpwafIcon = function(name, cls){
		if (!I[name]) return '';
		return '<svg class="wpwaf-ic wpwaf-ic-' + name + (cls ? ' ' + cls : '') + '" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false" style="vertical-align:-0.125em;flex-shrink:0">' + I[name] + '</svg>';
	};
	/* Icon, a space and escaped text, as HTML. */
	window.wpwafIconText = function(name, text){
		var ic = window.wpwafIcon(name);
		return (ic ? ic + ' ' : '') + esc(text);
	};
	/* Sets an element to an icon plus plain text (the text is escaped). */
	window.wpwafSetText = function(el, name, text){
		if (el) el.innerHTML = window.wpwafIconText(name, text);
	};
	/* Toast body: a check for success, an X for errors, or the icon given. */
	window.wpwafToastBody = function(msg, ok, icon){
		return window.wpwafIconText(icon || (ok === false ? 'x' : 'check'), msg);
	};
	window.wpwafEsc = esc;
})();
