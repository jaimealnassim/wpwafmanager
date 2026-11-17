/* WP WAF Manager: one-click cache purge from the admin bar (Settings, Admin bar).
 * Settings come from PHP in window.wpwafAdminBarPurge. */
(function(){
	var d=window.wpwafAdminBarPurge||{};
	var btn=document.querySelector('#wp-admin-bar-wpwaf-purge a');
	if(!btn||!d.zoneId)return;
	btn.setAttribute('href','#');
	btn.addEventListener('click',function(e){
		e.preventDefault();
		btn.innerHTML=d.busy;
		var fd=new FormData();
		fd.append('action','wpwaf_purge_cache');
		fd.append('nonce',d.nonce);
		fd.append('zone_id',d.zoneId);
		fd.append('mode','all');
		fetch(d.ajaxUrl,{method:'POST',body:fd})
			.then(function(r){return r.json();})
			.then(function(r){
				btn.innerHTML=r.success?d.done:d.failed;
				setTimeout(function(){btn.innerHTML=d.label;},3000);
			})
			.catch(function(){
				btn.innerHTML=d.failed;
				setTimeout(function(){btn.innerHTML=d.label;},3000);
			});
	});
})();
