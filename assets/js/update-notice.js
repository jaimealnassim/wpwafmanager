/* WP WAF Manager: dismiss button of the "new version available" notice. */
(function(){
	var d = window.wpwafUpdateNotice || {};
	var btn = document.getElementById('wpwaf-update-dismiss');
	if (!btn) return;
	btn.addEventListener('click', function(){
		var notice = document.getElementById('wpwaf-update-notice');
		if (notice) notice.style.display = 'none';
		var fd = new FormData();
		fd.append('action', 'wpwaf_dismiss_update');
		fd.append('nonce', d.nonce);
		fetch(d.ajaxUrl, { method:'POST', body:fd });
	});
})();
