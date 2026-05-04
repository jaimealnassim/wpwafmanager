<?php declare( strict_types=1 ); defined( 'ABSPATH' ) || exit; ?>
<style>
<?php echo file_get_contents( WPWAF_DIR . 'assets/css/admin.css' ); ?>
/* details/summary reset */
.cfwaf-wrap details > summary { list-style: none; }
.cfwaf-wrap details > summary::-webkit-details-marker { display: none; }
.cfwaf-header-meta { display: flex; flex-direction: column; gap: 2px; text-align: right; }
.cfwaf-header-meta-updated { font-size: 11px; color: #6b7280; }
.cfwaf-header-meta-updated a { color: #9ca3af; text-decoration: none; }
.cfwaf-header-meta-updated a:hover { color: #fff; text-decoration: underline; }
</style>
<div class="cfwaf-wrap" id="cfwaf-app">

	<!-- Header -->
	<div class="cfwaf-header">
		<div class="cfwaf-header-inner">
			<a href="https://www.wpwafmanager.com" target="_blank" rel="noopener" class="cfwaf-logo" style="text-decoration:none;">
				<span class="dashicons dashicons-shield"></span>
				<div>
					<h1>WP WAF Manager <span style="font-size:12px;font-weight:500;color:#9ca3af;letter-spacing:.3px;">v<?php echo WPWAF_VERSION; ?></span></h1>
					<p>Visual WAF Rule Builder &amp; One-Click Deployer</p>
				</div>
			</a>
			<div class="cfwaf-header-meta">
				<div class="cfwaf-header-meta-updated">Rules last updated: May 1, 2026 &mdash; <a href="https://wafrules.com" target="_blank" rel="noopener">wafrules.com</a></div>
				<a href="https://github.com/jaimealnassim/wpwafmanager" target="_blank" rel="noopener" class="cfwaf-header-github" title="View on GitHub" style="justify-content:flex-end;">
					<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"/></svg>
					<span>GitHub</span>
				</a>
			</div>
		</div>
	</div>

	<div class="cfwaf-body">

		<!-- API Credentials -->
		<div class="cfwaf-card cfwaf-card-token">
			<div class="cfwaf-card-header">
				<h2><span class="dashicons dashicons-admin-network"></span> Cloudflare API Credentials</h2>
				<span class="cfwaf-status-badge<?php echo ( $api_token || ( $email && $api_key ) ) ? ' connected' : ''; ?>" id="cfwaf-token-status"><?php echo ( $api_token || ( $email && $api_key ) ) ? 'Connected' : 'Not Connected'; ?></span>
			</div>
			<div class="cfwaf-card-body">

				<?php if ( ! empty( $accounts ) ) : ?>
				<!-- Saved Accounts List -->
				<div style="margin-bottom:18px;">
					<p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:#6b7280;margin:0 0 10px;">Saved Accounts</p>
					<div class="cfwaf-accounts-list" id="cfwaf-accounts-list">
						<?php foreach ( $accounts as $acc ) :
							$is_active    = ( $acc['id'] === $active_id );
							$is_constant  = ! empty( $acc['_constant'] );
							$acc_label    = esc_html( $acc['label'] ?? 'Unnamed Account' );
							$acc_method   = $acc['auth_method'] === 'key' ? 'Email + Key' : 'API Token';
							$acc_expires  = (int) ( $acc['expires_at'] ?? 0 );
							$acc_meta     = $acc_method . ( $is_constant ? ' · Defined in wp-config.php' : ( $acc_expires > 0 ? ' · Expires ' . date( 'M j', $acc_expires ) : ' · Saved forever' ) );
						?>
						<div class="cfwaf-account-item<?php echo $is_active ? ' active-account' : ''; ?>" data-account-id="<?php echo esc_attr( $acc['id'] ); ?>">
							<div>
								<div class="cfwaf-account-item-name" id="cfwaf-acct-label-<?php echo esc_attr( $acc['id'] ); ?>"><?php echo $acc_label; ?></div>
								<div class="cfwaf-account-item-meta"><?php echo esc_html( $acc_meta ); ?></div>
							</div>
							<span class="cfwaf-account-badge<?php echo $is_active ? ' active' : ''; ?>"><?php echo $is_active ? 'Active' : 'Inactive'; ?></span>
							<div class="cfwaf-account-item-actions">
								<?php if ( ! $is_constant ) : ?>
									<?php if ( ! $is_active ) : ?>
									<button type="button" class="cfwaf-btn-account-switch" data-account-id="<?php echo esc_attr( $acc['id'] ); ?>">Switch</button>
									<?php endif; ?>
									<button type="button" class="cfwaf-btn-account-edit" style="background:#3B8BD4;color:#fff;border:none;border-radius:5px;padding:4px 12px;font-size:11px;font-weight:600;cursor:pointer;" data-edit-id="<?php echo esc_attr( $acc['id'] ); ?>">Edit</button>
									<button type="button" class="cfwaf-btn-account-delete" data-account-id="<?php echo esc_attr( $acc['id'] ); ?>">Remove</button>
								<?php else : ?>
									<span style="font-size:11px;color:#888;font-style:italic;">Read-only</span>
								<?php endif; ?>
							</div>
						</div>
						<?php endforeach; ?>
					</div>
				</div>
				<?php endif; ?>

				<!-- Add / Edit Account Form -->
				<details id="cfwaf-add-account-details"<?php echo empty( $accounts ) ? ' open' : ''; ?>>
					<summary class="cfwaf-account-form-toggle">
						<span class="dashicons dashicons-plus-alt2"></span>
						<span id="cfwaf-form-title"><?php echo empty( $accounts ) ? 'Connect Cloudflare Account' : 'Add Another Account'; ?></span>
					</summary>

					<div class="cfwaf-account-form">
						<input type="hidden" id="cfwaf-editing-account-id" value="">

						<div class="cfwaf-notice cfwaf-notice--info" style="margin-bottom:16px;padding:10px 14px;background:#eef6ff;border-left:3px solid #3B8BD4;border-radius:4px;font-size:12px;line-height:1.6;color:#1e3a5f;">
							<strong>Tip:</strong> You can also connect accounts via <code>wp-config.php</code> constants — no database storage, supports multiple accounts with labels. <a href="https://www.wpwafmanager.com/docs/main/general/connecting-to-cloudflare/" target="_blank" rel="noopener" style="color:#3B8BD4;text-decoration:underline;">See docs for details.</a>
						</div>

						<!-- Label row -->
						<div class="cfwaf-form-field">
							<label class="cfwaf-form-label" for="cfwaf-account-label">Account Name / Label</label>
							<input type="text" id="cfwaf-account-label" class="cfwaf-input cfwaf-input-md" placeholder="e.g. Main Account, Client A&hellip;">
						</div>

						<!-- Auth method tabs -->
						<div class="cfwaf-auth-toggle">
							<button class="cfwaf-auth-tab <?php echo $auth_method !== 'key' ? 'active' : ''; ?>" data-method="token">API Token</button>
							<button class="cfwaf-auth-tab <?php echo $auth_method === 'key' ? 'active' : ''; ?>" data-method="key">Email + Global API Key</button>
						</div>

						<!-- API Token panel -->
						<div class="cfwaf-auth-panel" id="cfwaf-auth-token" <?php echo $auth_method === 'key' ? 'style="display:none"' : ''; ?>>
							<p class="cfwaf-hint">Requires <strong>Zone &rarr; WAF &rarr; Edit</strong> and <strong>Zone &rarr; Zone &rarr; Read</strong> permissions. <a href="https://dash.cloudflare.com/profile/api-tokens" target="_blank" rel="noopener">Create token &rarr;</a></p>
							<div class="cfwaf-form-field">
								<label class="cfwaf-form-label" for="cfwaf-api-token">API Token</label>
								<input type="password" id="cfwaf-api-token" class="cfwaf-input" placeholder="Paste your Cloudflare API token&hellip;" value="" autocomplete="new-password">
							</div>
							<div class="cfwaf-expiry-row cfwaf-verify-row">
								<button class="cfwaf-btn cfwaf-btn-verify" id="cfwaf-verify-token">Verify &amp; Save</button>
								<span class="cfwaf-expiry-divider">|</span>
								<label class="cfwaf-expiry-label" for="cfwaf-token-expiry">Keep for</label>
								<select id="cfwaf-token-expiry" class="cfwaf-expiry-select">
									<option value="0">Forever</option>
									<option value="3600">1 hour</option>
									<option value="28800">8 hours</option>
									<option value="86400">1 day</option>
								</select>
								<span class="cfwaf-expiry-status" id="cfwaf-token-expiry-status"></span>
								<button class="cfwaf-btn cfwaf-btn-cancel-form" id="cfwaf-cancel-form" style="display:none">Cancel</button>
							</div>
						</div>

						<!-- Email + Key panel -->
						<div class="cfwaf-auth-panel" id="cfwaf-auth-key" <?php echo $auth_method !== 'key' ? 'style="display:none"' : ''; ?>>
							<p class="cfwaf-hint">API Token is recommended. Global API Key grants full account access. <a href="https://dash.cloudflare.com/profile/api-tokens" target="_blank" rel="noopener">Find your key &rarr;</a></p>
							<div class="cfwaf-cred-grid">
								<div class="cfwaf-form-field">
									<label class="cfwaf-form-label" for="cfwaf-email">Cloudflare Account Email</label>
									<input type="email" id="cfwaf-email" class="cfwaf-input" placeholder="you@example.com" value="" autocomplete="email">
								</div>
								<div class="cfwaf-form-field">
									<label class="cfwaf-form-label" for="cfwaf-api-key">Global API Key</label>
									<input type="password" id="cfwaf-api-key" class="cfwaf-input" placeholder="Your Global API Key&hellip;" value="" autocomplete="new-password">
								</div>
							</div>
							<div class="cfwaf-expiry-row cfwaf-verify-row">
								<button class="cfwaf-btn cfwaf-btn-verify" id="cfwaf-verify-key">Verify &amp; Save</button>
								<span class="cfwaf-expiry-divider">|</span>
								<label class="cfwaf-expiry-label" for="cfwaf-key-expiry">Keep for</label>
								<select id="cfwaf-key-expiry" class="cfwaf-expiry-select">
									<option value="0">Forever</option>
									<option value="3600">1 hour</option>
									<option value="28800">8 hours</option>
									<option value="86400">1 day</option>
								</select>
								<span class="cfwaf-expiry-status" id="cfwaf-key-expiry-status"></span>
								<button class="cfwaf-btn cfwaf-btn-cancel-form" id="cfwaf-cancel-form-key" style="display:none">Cancel</button>
							</div>
						</div>

					</div><!-- /.cfwaf-account-form -->
				</details>

			</div>
		</div>

		<!-- How To Use -->
		<div class="cfwaf-howto">
			<h2>How to Use This Plugin</h2>
			<div class="cfwaf-howto-steps">

				<div class="cfwaf-howto-step">
					<div class="cfwaf-step-num">1</div>
					<div class="cfwaf-step-content">
						<h3>Connect &amp; Configure</h3>
						<p>Enter your Cloudflare API credentials above and click <strong>Verify &amp; Save</strong>. Then customize the five WAF rules below to match your site&rsquo;s needs. Every checkbox option is saved to your WordPress database &mdash; your configuration persists between sessions. You can return any time and your settings will be exactly as you left them.</p>
					</div>
				</div>

				<div class="cfwaf-howto-step">
					<div class="cfwaf-step-num">2</div>
					<div class="cfwaf-step-content">
						<h3>Deploy to Your Sites</h3>
						<p>Once configured, scroll to the bottom and click <strong>Deploy Rules to Your Sites</strong>. All your Cloudflare zones will load automatically. Pick one site, a handful, or all of them at once &mdash; then click <strong>Deploy</strong>. The plugin pushes all five rules directly to Cloudflare via API in the correct order. No copy-pasting expressions into the Cloudflare dashboard required.</p>
						<p class="cfwaf-hint"><strong>Why order matters:</strong> Rule 1 (Allow Good Bots) must be first because it uses the Skip action to whitelist legitimate bots before any blocking rules run. Block rules come before Challenge rules because a blocked request stops evaluation immediately &mdash; no further rules are checked.</p>
					</div>
				</div>

				<div class="cfwaf-howto-step">
					<div class="cfwaf-step-num">3</div>
					<div class="cfwaf-step-content">
						<h3>Monitor &amp; Adjust</h3>
						<p>After deploying, monitor your Cloudflare Security Events log at <strong>Security &rarr; Analytics &rarr; Events</strong>. Watch closely for false positives &mdash; legitimate traffic being blocked or challenged. If a service you need is being blocked, enable it in Rule 1 and redeploy. These rules work well for most sites with default settings, but every site is different. Use the <strong>View Zone Rules</strong> tab above to inspect what&rsquo;s live on any zone at any time.</p>
					</div>
				</div>

			</div>
		</div>

		<!-- Tabs -->
		<div class="cfwaf-tabs" id="cfwaf-main-tabs" role="tablist" aria-label="WAF Manager sections">
			<button class="cfwaf-tab active" data-tab="rules" role="tab" aria-selected="true"><span class="dashicons dashicons-editor-ul"></span> Configure Rules</button>
			<button class="cfwaf-tab" data-tab="expressions" role="tab" aria-selected="false"><span class="dashicons dashicons-editor-code"></span> Expressions</button>
			<button class="cfwaf-tab" data-tab="details" role="tab" aria-selected="false"><span class="dashicons dashicons-info-outline"></span> Details</button>
			<button class="cfwaf-tab" data-tab="zones" role="tab" aria-selected="false"><span class="dashicons dashicons-admin-site"></span> Zone Rules</button>
		</div>
		<div class="cfwaf-swipe-dots" id="cfwaf-main-dots"></div>

		<!-- TAB: Configure Rules -->
		<div class="cfwaf-tab-content active" id="cfwaf-tab-rules">

			<!-- RULE 1 -->
			<div class="cfwaf-rule-card">
				<div class="cfwaf-rule-header">
					<div class="cfwaf-rule-info">
						<span class="cfwaf-rule-num cfwaf-num-skip">1</span>
						<div>
							<h3>Allow Good Bots</h3>
							<p>Grants unrestricted access to verified bots and whitelisted services. <strong>Skip</strong> action &mdash; must be Rule #1.</p>
						</div>
					</div>
					<div class="cfwaf-rule-controls">
						<span class="cfwaf-action-badge cfwaf-badge-skip">SKIP</span>
						<label class="cfwaf-toggle"><input type="checkbox" data-setting="rule1.enabled"><span></span></label>
					</div>
				</div>
				<div class="cfwaf-rule-body">

					<div class="cfwaf-section">
						<h4>Cloudflare Verified Bot Categories</h4>
						<p class="cfwaf-hint">Allow all bots in these Cloudflare-verified categories.</p>
						<div class="cfwaf-cat-grid">
							<?php
							$cats = [
								'Accessibility'              => 'Accessible Web Bot',
								'Academic Research'          => 'Library of Congress, TurnItInBot, Biblioth&egrave;que nationale de France',
								'Advertising & Marketing'    => 'Google Adsbot',
								'Aggregator'                 => 'Pinterest, Indeed Jobsbot',
								'AI Assistant'               => 'Perplexity-User, DuckAssistBot',
								'AI Crawler'                 => 'Google Bard, ChatGPT bot',
								'AI Search'                  => 'SearchGPT',
								'Archiver'                   => 'Internet Archive, CommonCrawl',
								'Feed Fetcher'               => 'RSS or Podcast feed updaters',
								'Monitoring & Analytics'     => 'Uptime Monitors, GT Metrix, Pingdom',
								'Page Preview'               => 'Facebook, Slack, Twitter, Discord link preview tools',
								'Search Engine Crawler'      => 'Googlebot, Bingbot, Yandexbot, Baidubot',
								'Search Engine Optimization' => 'Google Lighthouse, AddThis, Ahrefs, Moz, SEMrush',
								'Security'                   => 'Vulnerability Scanners, SSL DCV Check Tools',
								'Social Media Marketing'     => 'Brandwatch',
								'Webhooks'                   => 'Payment processors, WordPress Integration tools',
							];
							foreach ( $cats as $cat => $desc ) : ?>
							<label class="cfwaf-cat-card">
								<input type="checkbox" class="cfwaf-cat-check" value="<?php echo esc_attr( $cat ); ?>">
								<div class="cfwaf-cat-card-text">
									<span class="cfwaf-cat-card-name"><?php echo esc_html( $cat ); ?></span>
									<span class="cfwaf-cat-card-desc"><?php echo esc_html( $desc ); ?></span>
								</div>
							</label>
							<?php endforeach; ?>
						</div>
					</div>

					<div class="cfwaf-section">
						<h4>WordPress Backup Services</h4>
						<div class="cfwaf-checkbox-grid">
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule1.allow_backupbuddy"> BackupBuddy</label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule1.allow_blogvault"> BlogVault <span class="cfwaf-tag">CF: Monitoring</span></label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule1.allow_updraftplus"> UpdraftPlus</label>
						</div>
					</div>

					<div class="cfwaf-section">
						<h4>Website Monitoring Services</h4>
						<div class="cfwaf-checkbox-grid">
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule1.allow_betterstack"> BetterStack <span class="cfwaf-tag">CF: Monitoring</span></label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule1.allow_gtmetrix"> GTmetrix <span class="cfwaf-tag">CF: Monitoring</span></label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule1.allow_pingdom"> Pingdom <span class="cfwaf-tag">CF: Monitoring</span></label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule1.allow_statuscake"> StatusCake <span class="cfwaf-tag">CF: Monitoring</span></label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule1.allow_uptimerobot"> UptimeRobot <span class="cfwaf-tag">CF: Monitoring</span></label>
				<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule1.allow_usercentrics"> Usercentrics <span class="cfwaf-tag">UA: Usercentricsbot</span></label>
						</div>
					</div>

					<div class="cfwaf-section">
						<h4>Performance &amp; Image Optimization</h4>
						<div class="cfwaf-checkbox-grid">
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule1.allow_cf_image"> Cloudflare Image Resizing</label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule1.allow_exactdn"> Easy IO / ExactDN</label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule1.allow_ewww"> EWWW Image Optimizer</label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule1.allow_flyingpress"> FlyingPress</label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule1.allow_imagify"> Imagify</label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule1.allow_shortpixel"> ShortPixel</label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule1.allow_tinypng"> TinyPNG</label>
						</div>
					</div>

					<div class="cfwaf-section">
						<h4>SEO Crawlers</h4>
						<div class="cfwaf-checkbox-grid">
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule1.allow_ahrefs"> Ahrefs <span class="cfwaf-tag">CF: SEO</span></label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule1.allow_ahrefs_audit"> Ahrefs Site Audit <span class="cfwaf-tag">CF: SEO</span></label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule1.allow_mj12"> Majestic (MJ12bot)</label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule1.allow_rogerbot"> Moz Rogerbot <span class="cfwaf-tag">CF: SEO</span></label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule1.allow_screamingfrog"> Screaming Frog</label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule1.allow_semrush"> SEMrush <span class="cfwaf-tag">CF: SEO</span></label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule1.allow_siteauditbot"> SiteAuditBot <span class="cfwaf-tag">CF: SEO</span></label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule1.allow_semrush_ocob"> SEMrush OCOB <span class="cfwaf-tag">CF: SEO</span></label>
						</div>
					</div>

					<div class="cfwaf-section">
						<h4>Security &amp; Malware Scanners</h4>
						<div class="cfwaf-checkbox-grid">
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule1.allow_sitelock"> SiteLock <span class="cfwaf-tag">CF: Security</span></label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule1.allow_sucuri"> Sucuri <span class="cfwaf-tag">CF: Security</span></label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule1.allow_virustotal"> VirusTotal</label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule1.allow_wordfence"> Wordfence</label>
						</div>
					</div>

					<div class="cfwaf-section">
						<h4>Social Media Previews</h4>
						<div class="cfwaf-checkbox-grid">
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule1.allow_facebook"> Facebook <span class="cfwaf-tag">CF: Page Preview</span></label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule1.allow_linkedin"> LinkedIn <span class="cfwaf-tag">CF: Page Preview</span></label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule1.allow_twitter"> Twitter / X <span class="cfwaf-tag">CF: Page Preview</span></label>
						</div>
					</div>

					<div class="cfwaf-section">
						<h4>WordPress Management</h4>
						<div class="cfwaf-checkbox-grid">
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule1.allow_jetpack"> Jetpack <span class="cfwaf-tag">CF: Monitoring</span></label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule1.allow_mainwp"> MainWP <span class="cfwaf-tag">CF: Webhooks</span></label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule1.allow_managewp"> ManageWP <span class="cfwaf-tag">CF: Webhooks</span></label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule1.allow_godaddy_uptime"> GoDaddy Uptime Monitor</label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule1.allow_wpumbrella"> WP Umbrella</label>
						</div>
					</div>

					<div class="cfwaf-section">
						<div class="cfwaf-checkbox-grid">
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule1.allow_letsencrypt"> Allow Let&rsquo;s Encrypt Verification (ACME)</label>
						</div>
					</div>

					<div class="cfwaf-section">
						<h4>Allowlisted IP Addresses</h4>
						<p class="cfwaf-hint">Enter IP addresses or CIDR ranges to always skip all WAF rules. One per line. Supports IPv4, IPv6, and CIDR notation (e.g. <code>1.2.3.4</code>, <code>10.0.0.0/8</code>, <code>2001:db8::/32</code>).</p>
						<textarea id="cfwaf-allow-ips" class="cfwaf-ip-textarea" rows="4" placeholder="1.2.3.4&#10;192.168.0.0/24&#10;2001:db8::1" spellcheck="false" autocomplete="off"></textarea>
						<p class="cfwaf-hint cfwaf-hint-muted" id="cfwaf-allow-ips-count"></p>
					</div>

					<div class="cfwaf-notice cfwaf-notice-api-skip">
						<div class="cfwaf-notice-api-icon">
							<span class="dashicons dashicons-cloud"></span>
						</div>
						<div class="cfwaf-notice-api-body">
							<span class="cfwaf-notice-api-title">Skip action is configured automatically via API.</span>
							<p>When this rule is deployed, the plugin sets the action to Skip and configures it to bypass: All remaining custom rules, All rate limiting rules, All managed rules, All Super Bot Fight Mode Rules, Zone Lockdown, User Agent Blocking, Browser Integrity Check, Hotlink Protection, and Security Level — no manual Cloudflare dashboard setup required.</p>
						</div>
					</div>

				</div>
			</div>

			<!-- RULE 2 -->
			<div class="cfwaf-rule-card">
				<div class="cfwaf-rule-header">
					<div class="cfwaf-rule-info">
						<span class="cfwaf-rule-num cfwaf-num-block">2</span>
						<div>
							<h3>Block Aggressive Crawlers &amp; WP Paths</h3>
							<p>Blocks persistent bots, exploit scanners, and sensitive WordPress paths.</p>
						</div>
					</div>
					<div class="cfwaf-rule-controls">
						<span class="cfwaf-action-badge cfwaf-badge-block">BLOCK</span>
						<label class="cfwaf-toggle"><input type="checkbox" data-setting="rule2.enabled"><span></span></label>
					</div>
				</div>
				<div class="cfwaf-rule-body">

					<div class="cfwaf-section">
						<h4>Aggressive Crawlers</h4>
						<p class="cfwaf-hint">Yandex, Sogou, SEMrush, Ahrefs, and Baidu are Cloudflare verified bots &mdash; uncheck their category in Rule 1 first if you want to block them here.</p>
						<div class="cfwaf-checkbox-grid">
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule2.block_yandex"> Yandex</label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule2.block_sogou"> Sogou</label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule2.block_semrush"> SEMrush</label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule2.block_ahrefs"> Ahrefs</label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule2.block_baidu"> Baidu</label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule2.block_neevabot"> Neevabot</label>
						</div>
					</div>

					<div class="cfwaf-section">
						<h4>Generic Bot Patterns</h4>
						<div class="cfwaf-checkbox-grid">
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule2.block_python"> Python Requests</label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule2.block_crawl"> Generic &ldquo;crawl&rdquo; in User-Agent</label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule2.block_bot"> Generic &ldquo;bot&rdquo; in User-Agent</label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule2.block_spider"> Generic &ldquo;spider&rdquo; in User-Agent</label>
						</div>
					</div>

					<div class="cfwaf-section">
						<h4>Exploit Scanners</h4>
						<div class="cfwaf-checkbox-grid">
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule2.block_nikto"> Nikto</label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule2.block_sqlmap"> SQLMap</label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule2.block_masscan"> Masscan</label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule2.block_nmap"> Nmap</label>
						</div>
					</div>

					<div class="cfwaf-section">
						<h4>WordPress Path Protection</h4>
						<div class="cfwaf-checkbox-grid">
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule2.block_xmlrpc"> Block XML-RPC</label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule2.block_wpconfig"> Block wp-config</label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule2.block_wpjson"> Block WP-JSON (REST API)</label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule2.block_wpinstall"> Block install.php</label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule2.block_wlwmanifest"> Block WLW Manifest</label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule2.block_readme"> Block readme.html</label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule2.block_license"> Block license.txt</label>
						</div>
					</div>

					<div class="cfwaf-section">
						<h4>Attack Patterns</h4>
						<div class="cfwaf-checkbox-grid">
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule2.block_sqli_sleep"> Time-delay / Blind SQLi Primitives</label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule2.block_path_traversal"> Encoded Path Traversal / LFI</label>
						</div>
					</div>

				</div>
			</div>

			<!-- RULE 3 -->
			<div class="cfwaf-rule-card">
				<div class="cfwaf-rule-header">
					<div class="cfwaf-rule-info">
						<span class="cfwaf-rule-num cfwaf-num-block">3</span>
						<div>
							<h3>Block or Challenge Web Hosts / TOR</h3>
							<p>Blocks or challenges traffic from web hosting providers and TOR exit nodes.</p>
						</div>
					</div>
					<div class="cfwaf-rule-controls">
						<span class="cfwaf-action-badge" id="rule3-action-badge">BLOCK</span>
						<label class="cfwaf-toggle"><input type="checkbox" data-setting="rule3.enabled"><span></span></label>
					</div>
				</div>
				<div class="cfwaf-rule-body">

					<div class="cfwaf-section">
						<h4>Web Hosting Providers (ASN-based)</h4>
						<div class="cfwaf-checkbox-grid">
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule3.block_do"> DigitalOcean</label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule3.block_linode"> Linode (Akamai)</label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule3.block_vultr"> Vultr</label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule3.block_hetzner"> Hetzner</label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule3.block_ovh"> OVH</label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule3.block_contabo"> Contabo</label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule3.block_scaleway"> Scaleway</label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule3.block_dreamhost"> DreamHost</label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule3.block_m247"> M247</label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule3.block_leaseweb"> LeaseWeb</label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule3.block_godaddy"> GoDaddy</label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule3.block_alibaba"> Alibaba</label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule3.block_hostroyale"> HostRoyale</label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule3.block_cloudvider"> Cloudvider</label>
						</div>
					</div>

					<div class="cfwaf-section">
						<div class="cfwaf-checkbox-grid">
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule3.block_tor"> Block TOR Exit Nodes</label>
						</div>
					</div>

					<div class="cfwaf-section">
						<h4>Action</h4>
						<div class="cfwaf-radio-group">
							<label class="cfwaf-radio-label">
								<input type="radio" name="rule3_action" value="block" data-setting="rule3.action">
								<span><strong>Block</strong> <span class="cfwaf-recommended">Recommended</span> &mdash; Immediately block all matching traffic.</span>
							</label>
							<label class="cfwaf-radio-label">
								<input type="radio" name="rule3_action" value="managed_challenge" data-setting="rule3.action">
								<span><strong>Managed Challenge</strong> &mdash; Challenge instead of blocking. Use if you need to allow legitimate proxy connections.</span>
							</label>
						</div>
					</div>

				</div>
			</div>

			<!-- RULE 4 -->
			<div class="cfwaf-rule-card">
				<div class="cfwaf-rule-header">
					<div class="cfwaf-rule-info">
						<span class="cfwaf-rule-num cfwaf-num-challenge">4</span>
						<div>
							<h3>Challenge Large Providers / Country</h3>
							<p>Managed challenge for AWS, GCP, Azure and optionally visitors outside your target countries.</p>
						</div>
					</div>
					<div class="cfwaf-rule-controls">
						<span class="cfwaf-action-badge cfwaf-badge-challenge">CHALLENGE</span>
						<label class="cfwaf-toggle"><input type="checkbox" data-setting="rule4.enabled"><span></span></label>
					</div>
				</div>
				<div class="cfwaf-rule-body">

					<div class="cfwaf-section">
						<h4>Cloud Providers</h4>
						<div class="cfwaf-checkbox-grid">
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule4.challenge_aws"> Amazon AWS (16509, 14618, 7224)</label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule4.challenge_gcp"> Google Cloud (15169, 396982)</label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule4.challenge_azure"> Microsoft Azure (8075)</label>
						</div>
					</div>

					<div class="cfwaf-section">
						<h4>Country Restriction</h4>
						<label class="cfwaf-check-label">
							<input type="checkbox" data-setting="rule4.challenge_country" id="cfwaf-country-toggle">
							Challenge visitors from outside your selected countries
						</label>
						<p class="cfwaf-hint" style="margin-top:6px">When checked, a country picker will appear directly below this rule card.</p>
					</div>

				</div>
			</div>

			<!-- Country Picker Panel — outside accordion so it's always accessible -->
			<div class="cfwaf-country-panel" id="cfwaf-country-panel" style="display:none">

				<div class="cfwaf-deploy-panel-header">
					<h3><span class="dashicons dashicons-admin-site"></span> Select Allowed Countries</h3>
				</div>
				<p class="cfwaf-hint" style="margin:0 0 14px;">Select every country you want to <strong>allow</strong>. Visitors from all other countries will receive a managed challenge.</p>

				<div class="cfwaf-deploy-toolbar" style="margin-bottom:10px;flex-wrap:wrap;gap:8px;">
					<label class="cfwaf-check-label cfwaf-select-all-label" style="width:auto;padding:7px 14px;margin:0;">
						<input type="checkbox" id="cfwaf-country-select-all"> <strong>Select All</strong>
					</label>
					<button type="button" class="cfwaf-btn cfwaf-btn-ghost-dark" id="cfwaf-country-clear-all" style="padding:6px 14px;font-size:12px;">&#10005; Clear All</button>
					<input type="search" id="cfwaf-country-search" class="cfwaf-input cfwaf-search-input" placeholder="&#128269; Search countries&hellip;" style="max-width:220px;">
					<span style="margin-left:auto;font-size:12px;font-weight:600;color:#6b7280;" id="cfwaf-country-count">0 selected</span>
				</div>

				<div class="cfwaf-country-regions">
					<span class="cfwaf-country-regions-label">Quick Pick:</span>
					<button type="button" class="cfwaf-region-btn" data-region="north-america">&#127758; North America</button>
					<button type="button" class="cfwaf-region-btn" data-region="europe">&#127757; Europe</button>
					<button type="button" class="cfwaf-region-btn" data-region="asia">&#127759; Asia</button>
					<button type="button" class="cfwaf-region-btn" data-region="oceania">&#127756; Oceania</button>
					<button type="button" class="cfwaf-region-btn" data-region="latin-america">&#127758; Latin America</button>
					<button type="button" class="cfwaf-region-btn" data-region="mena">MENA</button>
					<button type="button" class="cfwaf-region-btn" data-region="gcc">GCC</button>
					<button type="button" class="cfwaf-region-btn" data-region="africa">&#127757; Africa</button>
				</div>

				<div class="cfwaf-zones-grid" id="cfwaf-country-grid" style="max-height:420px;grid-template-columns:repeat(auto-fill,minmax(175px,1fr));margin-top:12px;">
					<div class="cfwaf-zone-item" data-cc="AF"><input type="checkbox" class="cfwaf-country-check" value="AF"><div><div class="cfwaf-zone-name">Afghanistan</div><div class="cfwaf-zone-plan">AF</div></div></div>
					<div class="cfwaf-zone-item" data-cc="AL"><input type="checkbox" class="cfwaf-country-check" value="AL"><div><div class="cfwaf-zone-name">Albania</div><div class="cfwaf-zone-plan">AL</div></div></div>
					<div class="cfwaf-zone-item" data-cc="DZ"><input type="checkbox" class="cfwaf-country-check" value="DZ"><div><div class="cfwaf-zone-name">Algeria</div><div class="cfwaf-zone-plan">DZ</div></div></div>
					<div class="cfwaf-zone-item" data-cc="AD"><input type="checkbox" class="cfwaf-country-check" value="AD"><div><div class="cfwaf-zone-name">Andorra</div><div class="cfwaf-zone-plan">AD</div></div></div>
					<div class="cfwaf-zone-item" data-cc="AO"><input type="checkbox" class="cfwaf-country-check" value="AO"><div><div class="cfwaf-zone-name">Angola</div><div class="cfwaf-zone-plan">AO</div></div></div>
					<div class="cfwaf-zone-item" data-cc="AG"><input type="checkbox" class="cfwaf-country-check" value="AG"><div><div class="cfwaf-zone-name">Antigua &amp; Barbuda</div><div class="cfwaf-zone-plan">AG</div></div></div>
					<div class="cfwaf-zone-item" data-cc="AR"><input type="checkbox" class="cfwaf-country-check" value="AR"><div><div class="cfwaf-zone-name">Argentina</div><div class="cfwaf-zone-plan">AR</div></div></div>
					<div class="cfwaf-zone-item" data-cc="AM"><input type="checkbox" class="cfwaf-country-check" value="AM"><div><div class="cfwaf-zone-name">Armenia</div><div class="cfwaf-zone-plan">AM</div></div></div>
					<div class="cfwaf-zone-item" data-cc="AU"><input type="checkbox" class="cfwaf-country-check" value="AU"><div><div class="cfwaf-zone-name">Australia</div><div class="cfwaf-zone-plan">AU</div></div></div>
					<div class="cfwaf-zone-item" data-cc="AT"><input type="checkbox" class="cfwaf-country-check" value="AT"><div><div class="cfwaf-zone-name">Austria</div><div class="cfwaf-zone-plan">AT</div></div></div>
					<div class="cfwaf-zone-item" data-cc="AZ"><input type="checkbox" class="cfwaf-country-check" value="AZ"><div><div class="cfwaf-zone-name">Azerbaijan</div><div class="cfwaf-zone-plan">AZ</div></div></div>
					<div class="cfwaf-zone-item" data-cc="BS"><input type="checkbox" class="cfwaf-country-check" value="BS"><div><div class="cfwaf-zone-name">Bahamas</div><div class="cfwaf-zone-plan">BS</div></div></div>
					<div class="cfwaf-zone-item" data-cc="BH"><input type="checkbox" class="cfwaf-country-check" value="BH"><div><div class="cfwaf-zone-name">Bahrain</div><div class="cfwaf-zone-plan">BH</div></div></div>
					<div class="cfwaf-zone-item" data-cc="BD"><input type="checkbox" class="cfwaf-country-check" value="BD"><div><div class="cfwaf-zone-name">Bangladesh</div><div class="cfwaf-zone-plan">BD</div></div></div>
					<div class="cfwaf-zone-item" data-cc="BB"><input type="checkbox" class="cfwaf-country-check" value="BB"><div><div class="cfwaf-zone-name">Barbados</div><div class="cfwaf-zone-plan">BB</div></div></div>
					<div class="cfwaf-zone-item" data-cc="BY"><input type="checkbox" class="cfwaf-country-check" value="BY"><div><div class="cfwaf-zone-name">Belarus</div><div class="cfwaf-zone-plan">BY</div></div></div>
					<div class="cfwaf-zone-item" data-cc="BE"><input type="checkbox" class="cfwaf-country-check" value="BE"><div><div class="cfwaf-zone-name">Belgium</div><div class="cfwaf-zone-plan">BE</div></div></div>
					<div class="cfwaf-zone-item" data-cc="BZ"><input type="checkbox" class="cfwaf-country-check" value="BZ"><div><div class="cfwaf-zone-name">Belize</div><div class="cfwaf-zone-plan">BZ</div></div></div>
					<div class="cfwaf-zone-item" data-cc="BJ"><input type="checkbox" class="cfwaf-country-check" value="BJ"><div><div class="cfwaf-zone-name">Benin</div><div class="cfwaf-zone-plan">BJ</div></div></div>
					<div class="cfwaf-zone-item" data-cc="BT"><input type="checkbox" class="cfwaf-country-check" value="BT"><div><div class="cfwaf-zone-name">Bhutan</div><div class="cfwaf-zone-plan">BT</div></div></div>
					<div class="cfwaf-zone-item" data-cc="BO"><input type="checkbox" class="cfwaf-country-check" value="BO"><div><div class="cfwaf-zone-name">Bolivia</div><div class="cfwaf-zone-plan">BO</div></div></div>
					<div class="cfwaf-zone-item" data-cc="BA"><input type="checkbox" class="cfwaf-country-check" value="BA"><div><div class="cfwaf-zone-name">Bosnia &amp; Herzegovina</div><div class="cfwaf-zone-plan">BA</div></div></div>
					<div class="cfwaf-zone-item" data-cc="BW"><input type="checkbox" class="cfwaf-country-check" value="BW"><div><div class="cfwaf-zone-name">Botswana</div><div class="cfwaf-zone-plan">BW</div></div></div>
					<div class="cfwaf-zone-item" data-cc="BR"><input type="checkbox" class="cfwaf-country-check" value="BR"><div><div class="cfwaf-zone-name">Brazil</div><div class="cfwaf-zone-plan">BR</div></div></div>
					<div class="cfwaf-zone-item" data-cc="BN"><input type="checkbox" class="cfwaf-country-check" value="BN"><div><div class="cfwaf-zone-name">Brunei</div><div class="cfwaf-zone-plan">BN</div></div></div>
					<div class="cfwaf-zone-item" data-cc="BG"><input type="checkbox" class="cfwaf-country-check" value="BG"><div><div class="cfwaf-zone-name">Bulgaria</div><div class="cfwaf-zone-plan">BG</div></div></div>
					<div class="cfwaf-zone-item" data-cc="BF"><input type="checkbox" class="cfwaf-country-check" value="BF"><div><div class="cfwaf-zone-name">Burkina Faso</div><div class="cfwaf-zone-plan">BF</div></div></div>
					<div class="cfwaf-zone-item" data-cc="BI"><input type="checkbox" class="cfwaf-country-check" value="BI"><div><div class="cfwaf-zone-name">Burundi</div><div class="cfwaf-zone-plan">BI</div></div></div>
					<div class="cfwaf-zone-item" data-cc="CV"><input type="checkbox" class="cfwaf-country-check" value="CV"><div><div class="cfwaf-zone-name">Cabo Verde</div><div class="cfwaf-zone-plan">CV</div></div></div>
					<div class="cfwaf-zone-item" data-cc="KH"><input type="checkbox" class="cfwaf-country-check" value="KH"><div><div class="cfwaf-zone-name">Cambodia</div><div class="cfwaf-zone-plan">KH</div></div></div>
					<div class="cfwaf-zone-item" data-cc="CM"><input type="checkbox" class="cfwaf-country-check" value="CM"><div><div class="cfwaf-zone-name">Cameroon</div><div class="cfwaf-zone-plan">CM</div></div></div>
					<div class="cfwaf-zone-item" data-cc="CA"><input type="checkbox" class="cfwaf-country-check" value="CA"><div><div class="cfwaf-zone-name">Canada</div><div class="cfwaf-zone-plan">CA</div></div></div>
					<div class="cfwaf-zone-item" data-cc="CF"><input type="checkbox" class="cfwaf-country-check" value="CF"><div><div class="cfwaf-zone-name">Central African Rep.</div><div class="cfwaf-zone-plan">CF</div></div></div>
					<div class="cfwaf-zone-item" data-cc="TD"><input type="checkbox" class="cfwaf-country-check" value="TD"><div><div class="cfwaf-zone-name">Chad</div><div class="cfwaf-zone-plan">TD</div></div></div>
					<div class="cfwaf-zone-item" data-cc="CL"><input type="checkbox" class="cfwaf-country-check" value="CL"><div><div class="cfwaf-zone-name">Chile</div><div class="cfwaf-zone-plan">CL</div></div></div>
					<div class="cfwaf-zone-item" data-cc="CN"><input type="checkbox" class="cfwaf-country-check" value="CN"><div><div class="cfwaf-zone-name">China</div><div class="cfwaf-zone-plan">CN</div></div></div>
					<div class="cfwaf-zone-item" data-cc="CO"><input type="checkbox" class="cfwaf-country-check" value="CO"><div><div class="cfwaf-zone-name">Colombia</div><div class="cfwaf-zone-plan">CO</div></div></div>
					<div class="cfwaf-zone-item" data-cc="KM"><input type="checkbox" class="cfwaf-country-check" value="KM"><div><div class="cfwaf-zone-name">Comoros</div><div class="cfwaf-zone-plan">KM</div></div></div>
					<div class="cfwaf-zone-item" data-cc="CG"><input type="checkbox" class="cfwaf-country-check" value="CG"><div><div class="cfwaf-zone-name">Congo</div><div class="cfwaf-zone-plan">CG</div></div></div>
					<div class="cfwaf-zone-item" data-cc="CR"><input type="checkbox" class="cfwaf-country-check" value="CR"><div><div class="cfwaf-zone-name">Costa Rica</div><div class="cfwaf-zone-plan">CR</div></div></div>
					<div class="cfwaf-zone-item" data-cc="HR"><input type="checkbox" class="cfwaf-country-check" value="HR"><div><div class="cfwaf-zone-name">Croatia</div><div class="cfwaf-zone-plan">HR</div></div></div>
					<div class="cfwaf-zone-item" data-cc="CU"><input type="checkbox" class="cfwaf-country-check" value="CU"><div><div class="cfwaf-zone-name">Cuba</div><div class="cfwaf-zone-plan">CU</div></div></div>
					<div class="cfwaf-zone-item" data-cc="CY"><input type="checkbox" class="cfwaf-country-check" value="CY"><div><div class="cfwaf-zone-name">Cyprus</div><div class="cfwaf-zone-plan">CY</div></div></div>
					<div class="cfwaf-zone-item" data-cc="CZ"><input type="checkbox" class="cfwaf-country-check" value="CZ"><div><div class="cfwaf-zone-name">Czech Republic</div><div class="cfwaf-zone-plan">CZ</div></div></div>
					<div class="cfwaf-zone-item" data-cc="DK"><input type="checkbox" class="cfwaf-country-check" value="DK"><div><div class="cfwaf-zone-name">Denmark</div><div class="cfwaf-zone-plan">DK</div></div></div>
					<div class="cfwaf-zone-item" data-cc="DJ"><input type="checkbox" class="cfwaf-country-check" value="DJ"><div><div class="cfwaf-zone-name">Djibouti</div><div class="cfwaf-zone-plan">DJ</div></div></div>
					<div class="cfwaf-zone-item" data-cc="DM"><input type="checkbox" class="cfwaf-country-check" value="DM"><div><div class="cfwaf-zone-name">Dominica</div><div class="cfwaf-zone-plan">DM</div></div></div>
					<div class="cfwaf-zone-item" data-cc="DO"><input type="checkbox" class="cfwaf-country-check" value="DO"><div><div class="cfwaf-zone-name">Dominican Republic</div><div class="cfwaf-zone-plan">DO</div></div></div>
					<div class="cfwaf-zone-item" data-cc="EC"><input type="checkbox" class="cfwaf-country-check" value="EC"><div><div class="cfwaf-zone-name">Ecuador</div><div class="cfwaf-zone-plan">EC</div></div></div>
					<div class="cfwaf-zone-item" data-cc="EG"><input type="checkbox" class="cfwaf-country-check" value="EG"><div><div class="cfwaf-zone-name">Egypt</div><div class="cfwaf-zone-plan">EG</div></div></div>
					<div class="cfwaf-zone-item" data-cc="SV"><input type="checkbox" class="cfwaf-country-check" value="SV"><div><div class="cfwaf-zone-name">El Salvador</div><div class="cfwaf-zone-plan">SV</div></div></div>
					<div class="cfwaf-zone-item" data-cc="GQ"><input type="checkbox" class="cfwaf-country-check" value="GQ"><div><div class="cfwaf-zone-name">Equatorial Guinea</div><div class="cfwaf-zone-plan">GQ</div></div></div>
					<div class="cfwaf-zone-item" data-cc="ER"><input type="checkbox" class="cfwaf-country-check" value="ER"><div><div class="cfwaf-zone-name">Eritrea</div><div class="cfwaf-zone-plan">ER</div></div></div>
					<div class="cfwaf-zone-item" data-cc="EE"><input type="checkbox" class="cfwaf-country-check" value="EE"><div><div class="cfwaf-zone-name">Estonia</div><div class="cfwaf-zone-plan">EE</div></div></div>
					<div class="cfwaf-zone-item" data-cc="SZ"><input type="checkbox" class="cfwaf-country-check" value="SZ"><div><div class="cfwaf-zone-name">Eswatini</div><div class="cfwaf-zone-plan">SZ</div></div></div>
					<div class="cfwaf-zone-item" data-cc="ET"><input type="checkbox" class="cfwaf-country-check" value="ET"><div><div class="cfwaf-zone-name">Ethiopia</div><div class="cfwaf-zone-plan">ET</div></div></div>
					<div class="cfwaf-zone-item" data-cc="FJ"><input type="checkbox" class="cfwaf-country-check" value="FJ"><div><div class="cfwaf-zone-name">Fiji</div><div class="cfwaf-zone-plan">FJ</div></div></div>
					<div class="cfwaf-zone-item" data-cc="FI"><input type="checkbox" class="cfwaf-country-check" value="FI"><div><div class="cfwaf-zone-name">Finland</div><div class="cfwaf-zone-plan">FI</div></div></div>
					<div class="cfwaf-zone-item" data-cc="FR"><input type="checkbox" class="cfwaf-country-check" value="FR"><div><div class="cfwaf-zone-name">France</div><div class="cfwaf-zone-plan">FR</div></div></div>
					<div class="cfwaf-zone-item" data-cc="GA"><input type="checkbox" class="cfwaf-country-check" value="GA"><div><div class="cfwaf-zone-name">Gabon</div><div class="cfwaf-zone-plan">GA</div></div></div>
					<div class="cfwaf-zone-item" data-cc="GM"><input type="checkbox" class="cfwaf-country-check" value="GM"><div><div class="cfwaf-zone-name">Gambia</div><div class="cfwaf-zone-plan">GM</div></div></div>
					<div class="cfwaf-zone-item" data-cc="GE"><input type="checkbox" class="cfwaf-country-check" value="GE"><div><div class="cfwaf-zone-name">Georgia</div><div class="cfwaf-zone-plan">GE</div></div></div>
					<div class="cfwaf-zone-item" data-cc="DE"><input type="checkbox" class="cfwaf-country-check" value="DE"><div><div class="cfwaf-zone-name">Germany</div><div class="cfwaf-zone-plan">DE</div></div></div>
					<div class="cfwaf-zone-item" data-cc="GH"><input type="checkbox" class="cfwaf-country-check" value="GH"><div><div class="cfwaf-zone-name">Ghana</div><div class="cfwaf-zone-plan">GH</div></div></div>
					<div class="cfwaf-zone-item" data-cc="GR"><input type="checkbox" class="cfwaf-country-check" value="GR"><div><div class="cfwaf-zone-name">Greece</div><div class="cfwaf-zone-plan">GR</div></div></div>
					<div class="cfwaf-zone-item" data-cc="GD"><input type="checkbox" class="cfwaf-country-check" value="GD"><div><div class="cfwaf-zone-name">Grenada</div><div class="cfwaf-zone-plan">GD</div></div></div>
					<div class="cfwaf-zone-item" data-cc="GT"><input type="checkbox" class="cfwaf-country-check" value="GT"><div><div class="cfwaf-zone-name">Guatemala</div><div class="cfwaf-zone-plan">GT</div></div></div>
					<div class="cfwaf-zone-item" data-cc="GN"><input type="checkbox" class="cfwaf-country-check" value="GN"><div><div class="cfwaf-zone-name">Guinea</div><div class="cfwaf-zone-plan">GN</div></div></div>
					<div class="cfwaf-zone-item" data-cc="GW"><input type="checkbox" class="cfwaf-country-check" value="GW"><div><div class="cfwaf-zone-name">Guinea-Bissau</div><div class="cfwaf-zone-plan">GW</div></div></div>
					<div class="cfwaf-zone-item" data-cc="GY"><input type="checkbox" class="cfwaf-country-check" value="GY"><div><div class="cfwaf-zone-name">Guyana</div><div class="cfwaf-zone-plan">GY</div></div></div>
					<div class="cfwaf-zone-item" data-cc="HT"><input type="checkbox" class="cfwaf-country-check" value="HT"><div><div class="cfwaf-zone-name">Haiti</div><div class="cfwaf-zone-plan">HT</div></div></div>
					<div class="cfwaf-zone-item" data-cc="HN"><input type="checkbox" class="cfwaf-country-check" value="HN"><div><div class="cfwaf-zone-name">Honduras</div><div class="cfwaf-zone-plan">HN</div></div></div>
					<div class="cfwaf-zone-item" data-cc="HK"><input type="checkbox" class="cfwaf-country-check" value="HK"><div><div class="cfwaf-zone-name">Hong Kong</div><div class="cfwaf-zone-plan">HK</div></div></div>
					<div class="cfwaf-zone-item" data-cc="HU"><input type="checkbox" class="cfwaf-country-check" value="HU"><div><div class="cfwaf-zone-name">Hungary</div><div class="cfwaf-zone-plan">HU</div></div></div>
					<div class="cfwaf-zone-item" data-cc="IS"><input type="checkbox" class="cfwaf-country-check" value="IS"><div><div class="cfwaf-zone-name">Iceland</div><div class="cfwaf-zone-plan">IS</div></div></div>
					<div class="cfwaf-zone-item" data-cc="IN"><input type="checkbox" class="cfwaf-country-check" value="IN"><div><div class="cfwaf-zone-name">India</div><div class="cfwaf-zone-plan">IN</div></div></div>
					<div class="cfwaf-zone-item" data-cc="ID"><input type="checkbox" class="cfwaf-country-check" value="ID"><div><div class="cfwaf-zone-name">Indonesia</div><div class="cfwaf-zone-plan">ID</div></div></div>
					<div class="cfwaf-zone-item" data-cc="IR"><input type="checkbox" class="cfwaf-country-check" value="IR"><div><div class="cfwaf-zone-name">Iran</div><div class="cfwaf-zone-plan">IR</div></div></div>
					<div class="cfwaf-zone-item" data-cc="IQ"><input type="checkbox" class="cfwaf-country-check" value="IQ"><div><div class="cfwaf-zone-name">Iraq</div><div class="cfwaf-zone-plan">IQ</div></div></div>
					<div class="cfwaf-zone-item" data-cc="IE"><input type="checkbox" class="cfwaf-country-check" value="IE"><div><div class="cfwaf-zone-name">Ireland</div><div class="cfwaf-zone-plan">IE</div></div></div>
					<div class="cfwaf-zone-item" data-cc="IL"><input type="checkbox" class="cfwaf-country-check" value="IL"><div><div class="cfwaf-zone-name">Israel</div><div class="cfwaf-zone-plan">IL</div></div></div>
					<div class="cfwaf-zone-item" data-cc="IT"><input type="checkbox" class="cfwaf-country-check" value="IT"><div><div class="cfwaf-zone-name">Italy</div><div class="cfwaf-zone-plan">IT</div></div></div>
					<div class="cfwaf-zone-item" data-cc="JM"><input type="checkbox" class="cfwaf-country-check" value="JM"><div><div class="cfwaf-zone-name">Jamaica</div><div class="cfwaf-zone-plan">JM</div></div></div>
					<div class="cfwaf-zone-item" data-cc="JP"><input type="checkbox" class="cfwaf-country-check" value="JP"><div><div class="cfwaf-zone-name">Japan</div><div class="cfwaf-zone-plan">JP</div></div></div>
					<div class="cfwaf-zone-item" data-cc="JO"><input type="checkbox" class="cfwaf-country-check" value="JO"><div><div class="cfwaf-zone-name">Jordan</div><div class="cfwaf-zone-plan">JO</div></div></div>
					<div class="cfwaf-zone-item" data-cc="KZ"><input type="checkbox" class="cfwaf-country-check" value="KZ"><div><div class="cfwaf-zone-name">Kazakhstan</div><div class="cfwaf-zone-plan">KZ</div></div></div>
					<div class="cfwaf-zone-item" data-cc="KE"><input type="checkbox" class="cfwaf-country-check" value="KE"><div><div class="cfwaf-zone-name">Kenya</div><div class="cfwaf-zone-plan">KE</div></div></div>
					<div class="cfwaf-zone-item" data-cc="KI"><input type="checkbox" class="cfwaf-country-check" value="KI"><div><div class="cfwaf-zone-name">Kiribati</div><div class="cfwaf-zone-plan">KI</div></div></div>
					<div class="cfwaf-zone-item" data-cc="KW"><input type="checkbox" class="cfwaf-country-check" value="KW"><div><div class="cfwaf-zone-name">Kuwait</div><div class="cfwaf-zone-plan">KW</div></div></div>
					<div class="cfwaf-zone-item" data-cc="KG"><input type="checkbox" class="cfwaf-country-check" value="KG"><div><div class="cfwaf-zone-name">Kyrgyzstan</div><div class="cfwaf-zone-plan">KG</div></div></div>
					<div class="cfwaf-zone-item" data-cc="LA"><input type="checkbox" class="cfwaf-country-check" value="LA"><div><div class="cfwaf-zone-name">Laos</div><div class="cfwaf-zone-plan">LA</div></div></div>
					<div class="cfwaf-zone-item" data-cc="LV"><input type="checkbox" class="cfwaf-country-check" value="LV"><div><div class="cfwaf-zone-name">Latvia</div><div class="cfwaf-zone-plan">LV</div></div></div>
					<div class="cfwaf-zone-item" data-cc="LB"><input type="checkbox" class="cfwaf-country-check" value="LB"><div><div class="cfwaf-zone-name">Lebanon</div><div class="cfwaf-zone-plan">LB</div></div></div>
					<div class="cfwaf-zone-item" data-cc="LS"><input type="checkbox" class="cfwaf-country-check" value="LS"><div><div class="cfwaf-zone-name">Lesotho</div><div class="cfwaf-zone-plan">LS</div></div></div>
					<div class="cfwaf-zone-item" data-cc="LR"><input type="checkbox" class="cfwaf-country-check" value="LR"><div><div class="cfwaf-zone-name">Liberia</div><div class="cfwaf-zone-plan">LR</div></div></div>
					<div class="cfwaf-zone-item" data-cc="LY"><input type="checkbox" class="cfwaf-country-check" value="LY"><div><div class="cfwaf-zone-name">Libya</div><div class="cfwaf-zone-plan">LY</div></div></div>
					<div class="cfwaf-zone-item" data-cc="LI"><input type="checkbox" class="cfwaf-country-check" value="LI"><div><div class="cfwaf-zone-name">Liechtenstein</div><div class="cfwaf-zone-plan">LI</div></div></div>
					<div class="cfwaf-zone-item" data-cc="LT"><input type="checkbox" class="cfwaf-country-check" value="LT"><div><div class="cfwaf-zone-name">Lithuania</div><div class="cfwaf-zone-plan">LT</div></div></div>
					<div class="cfwaf-zone-item" data-cc="LU"><input type="checkbox" class="cfwaf-country-check" value="LU"><div><div class="cfwaf-zone-name">Luxembourg</div><div class="cfwaf-zone-plan">LU</div></div></div>
					<div class="cfwaf-zone-item" data-cc="MG"><input type="checkbox" class="cfwaf-country-check" value="MG"><div><div class="cfwaf-zone-name">Madagascar</div><div class="cfwaf-zone-plan">MG</div></div></div>
					<div class="cfwaf-zone-item" data-cc="MW"><input type="checkbox" class="cfwaf-country-check" value="MW"><div><div class="cfwaf-zone-name">Malawi</div><div class="cfwaf-zone-plan">MW</div></div></div>
					<div class="cfwaf-zone-item" data-cc="MY"><input type="checkbox" class="cfwaf-country-check" value="MY"><div><div class="cfwaf-zone-name">Malaysia</div><div class="cfwaf-zone-plan">MY</div></div></div>
					<div class="cfwaf-zone-item" data-cc="MV"><input type="checkbox" class="cfwaf-country-check" value="MV"><div><div class="cfwaf-zone-name">Maldives</div><div class="cfwaf-zone-plan">MV</div></div></div>
					<div class="cfwaf-zone-item" data-cc="ML"><input type="checkbox" class="cfwaf-country-check" value="ML"><div><div class="cfwaf-zone-name">Mali</div><div class="cfwaf-zone-plan">ML</div></div></div>
					<div class="cfwaf-zone-item" data-cc="MT"><input type="checkbox" class="cfwaf-country-check" value="MT"><div><div class="cfwaf-zone-name">Malta</div><div class="cfwaf-zone-plan">MT</div></div></div>
					<div class="cfwaf-zone-item" data-cc="MH"><input type="checkbox" class="cfwaf-country-check" value="MH"><div><div class="cfwaf-zone-name">Marshall Islands</div><div class="cfwaf-zone-plan">MH</div></div></div>
					<div class="cfwaf-zone-item" data-cc="MR"><input type="checkbox" class="cfwaf-country-check" value="MR"><div><div class="cfwaf-zone-name">Mauritania</div><div class="cfwaf-zone-plan">MR</div></div></div>
					<div class="cfwaf-zone-item" data-cc="MU"><input type="checkbox" class="cfwaf-country-check" value="MU"><div><div class="cfwaf-zone-name">Mauritius</div><div class="cfwaf-zone-plan">MU</div></div></div>
					<div class="cfwaf-zone-item" data-cc="MX"><input type="checkbox" class="cfwaf-country-check" value="MX"><div><div class="cfwaf-zone-name">Mexico</div><div class="cfwaf-zone-plan">MX</div></div></div>
					<div class="cfwaf-zone-item" data-cc="FM"><input type="checkbox" class="cfwaf-country-check" value="FM"><div><div class="cfwaf-zone-name">Micronesia</div><div class="cfwaf-zone-plan">FM</div></div></div>
					<div class="cfwaf-zone-item" data-cc="MD"><input type="checkbox" class="cfwaf-country-check" value="MD"><div><div class="cfwaf-zone-name">Moldova</div><div class="cfwaf-zone-plan">MD</div></div></div>
					<div class="cfwaf-zone-item" data-cc="MC"><input type="checkbox" class="cfwaf-country-check" value="MC"><div><div class="cfwaf-zone-name">Monaco</div><div class="cfwaf-zone-plan">MC</div></div></div>
					<div class="cfwaf-zone-item" data-cc="MN"><input type="checkbox" class="cfwaf-country-check" value="MN"><div><div class="cfwaf-zone-name">Mongolia</div><div class="cfwaf-zone-plan">MN</div></div></div>
					<div class="cfwaf-zone-item" data-cc="ME"><input type="checkbox" class="cfwaf-country-check" value="ME"><div><div class="cfwaf-zone-name">Montenegro</div><div class="cfwaf-zone-plan">ME</div></div></div>
					<div class="cfwaf-zone-item" data-cc="MA"><input type="checkbox" class="cfwaf-country-check" value="MA"><div><div class="cfwaf-zone-name">Morocco</div><div class="cfwaf-zone-plan">MA</div></div></div>
					<div class="cfwaf-zone-item" data-cc="MZ"><input type="checkbox" class="cfwaf-country-check" value="MZ"><div><div class="cfwaf-zone-name">Mozambique</div><div class="cfwaf-zone-plan">MZ</div></div></div>
					<div class="cfwaf-zone-item" data-cc="MM"><input type="checkbox" class="cfwaf-country-check" value="MM"><div><div class="cfwaf-zone-name">Myanmar</div><div class="cfwaf-zone-plan">MM</div></div></div>
					<div class="cfwaf-zone-item" data-cc="NA"><input type="checkbox" class="cfwaf-country-check" value="NA"><div><div class="cfwaf-zone-name">Namibia</div><div class="cfwaf-zone-plan">NA</div></div></div>
					<div class="cfwaf-zone-item" data-cc="NR"><input type="checkbox" class="cfwaf-country-check" value="NR"><div><div class="cfwaf-zone-name">Nauru</div><div class="cfwaf-zone-plan">NR</div></div></div>
					<div class="cfwaf-zone-item" data-cc="NP"><input type="checkbox" class="cfwaf-country-check" value="NP"><div><div class="cfwaf-zone-name">Nepal</div><div class="cfwaf-zone-plan">NP</div></div></div>
					<div class="cfwaf-zone-item" data-cc="NL"><input type="checkbox" class="cfwaf-country-check" value="NL"><div><div class="cfwaf-zone-name">Netherlands</div><div class="cfwaf-zone-plan">NL</div></div></div>
					<div class="cfwaf-zone-item" data-cc="NZ"><input type="checkbox" class="cfwaf-country-check" value="NZ"><div><div class="cfwaf-zone-name">New Zealand</div><div class="cfwaf-zone-plan">NZ</div></div></div>
					<div class="cfwaf-zone-item" data-cc="NI"><input type="checkbox" class="cfwaf-country-check" value="NI"><div><div class="cfwaf-zone-name">Nicaragua</div><div class="cfwaf-zone-plan">NI</div></div></div>
					<div class="cfwaf-zone-item" data-cc="NE"><input type="checkbox" class="cfwaf-country-check" value="NE"><div><div class="cfwaf-zone-name">Niger</div><div class="cfwaf-zone-plan">NE</div></div></div>
					<div class="cfwaf-zone-item" data-cc="NG"><input type="checkbox" class="cfwaf-country-check" value="NG"><div><div class="cfwaf-zone-name">Nigeria</div><div class="cfwaf-zone-plan">NG</div></div></div>
					<div class="cfwaf-zone-item" data-cc="MK"><input type="checkbox" class="cfwaf-country-check" value="MK"><div><div class="cfwaf-zone-name">North Macedonia</div><div class="cfwaf-zone-plan">MK</div></div></div>
					<div class="cfwaf-zone-item" data-cc="NO"><input type="checkbox" class="cfwaf-country-check" value="NO"><div><div class="cfwaf-zone-name">Norway</div><div class="cfwaf-zone-plan">NO</div></div></div>
					<div class="cfwaf-zone-item" data-cc="OM"><input type="checkbox" class="cfwaf-country-check" value="OM"><div><div class="cfwaf-zone-name">Oman</div><div class="cfwaf-zone-plan">OM</div></div></div>
					<div class="cfwaf-zone-item" data-cc="PK"><input type="checkbox" class="cfwaf-country-check" value="PK"><div><div class="cfwaf-zone-name">Pakistan</div><div class="cfwaf-zone-plan">PK</div></div></div>
					<div class="cfwaf-zone-item" data-cc="PW"><input type="checkbox" class="cfwaf-country-check" value="PW"><div><div class="cfwaf-zone-name">Palau</div><div class="cfwaf-zone-plan">PW</div></div></div>
					<div class="cfwaf-zone-item" data-cc="PA"><input type="checkbox" class="cfwaf-country-check" value="PA"><div><div class="cfwaf-zone-name">Panama</div><div class="cfwaf-zone-plan">PA</div></div></div>
					<div class="cfwaf-zone-item" data-cc="PG"><input type="checkbox" class="cfwaf-country-check" value="PG"><div><div class="cfwaf-zone-name">Papua New Guinea</div><div class="cfwaf-zone-plan">PG</div></div></div>
					<div class="cfwaf-zone-item" data-cc="PY"><input type="checkbox" class="cfwaf-country-check" value="PY"><div><div class="cfwaf-zone-name">Paraguay</div><div class="cfwaf-zone-plan">PY</div></div></div>
					<div class="cfwaf-zone-item" data-cc="PE"><input type="checkbox" class="cfwaf-country-check" value="PE"><div><div class="cfwaf-zone-name">Peru</div><div class="cfwaf-zone-plan">PE</div></div></div>
					<div class="cfwaf-zone-item" data-cc="PH"><input type="checkbox" class="cfwaf-country-check" value="PH"><div><div class="cfwaf-zone-name">Philippines</div><div class="cfwaf-zone-plan">PH</div></div></div>
					<div class="cfwaf-zone-item" data-cc="PL"><input type="checkbox" class="cfwaf-country-check" value="PL"><div><div class="cfwaf-zone-name">Poland</div><div class="cfwaf-zone-plan">PL</div></div></div>
					<div class="cfwaf-zone-item" data-cc="PT"><input type="checkbox" class="cfwaf-country-check" value="PT"><div><div class="cfwaf-zone-name">Portugal</div><div class="cfwaf-zone-plan">PT</div></div></div>
					<div class="cfwaf-zone-item" data-cc="QA"><input type="checkbox" class="cfwaf-country-check" value="QA"><div><div class="cfwaf-zone-name">Qatar</div><div class="cfwaf-zone-plan">QA</div></div></div>
					<div class="cfwaf-zone-item" data-cc="RO"><input type="checkbox" class="cfwaf-country-check" value="RO"><div><div class="cfwaf-zone-name">Romania</div><div class="cfwaf-zone-plan">RO</div></div></div>
					<div class="cfwaf-zone-item" data-cc="RU"><input type="checkbox" class="cfwaf-country-check" value="RU"><div><div class="cfwaf-zone-name">Russia</div><div class="cfwaf-zone-plan">RU</div></div></div>
					<div class="cfwaf-zone-item" data-cc="RW"><input type="checkbox" class="cfwaf-country-check" value="RW"><div><div class="cfwaf-zone-name">Rwanda</div><div class="cfwaf-zone-plan">RW</div></div></div>
					<div class="cfwaf-zone-item" data-cc="KN"><input type="checkbox" class="cfwaf-country-check" value="KN"><div><div class="cfwaf-zone-name">Saint Kitts &amp; Nevis</div><div class="cfwaf-zone-plan">KN</div></div></div>
					<div class="cfwaf-zone-item" data-cc="LC"><input type="checkbox" class="cfwaf-country-check" value="LC"><div><div class="cfwaf-zone-name">Saint Lucia</div><div class="cfwaf-zone-plan">LC</div></div></div>
					<div class="cfwaf-zone-item" data-cc="VC"><input type="checkbox" class="cfwaf-country-check" value="VC"><div><div class="cfwaf-zone-name">Saint Vincent</div><div class="cfwaf-zone-plan">VC</div></div></div>
					<div class="cfwaf-zone-item" data-cc="WS"><input type="checkbox" class="cfwaf-country-check" value="WS"><div><div class="cfwaf-zone-name">Samoa</div><div class="cfwaf-zone-plan">WS</div></div></div>
					<div class="cfwaf-zone-item" data-cc="SM"><input type="checkbox" class="cfwaf-country-check" value="SM"><div><div class="cfwaf-zone-name">San Marino</div><div class="cfwaf-zone-plan">SM</div></div></div>
					<div class="cfwaf-zone-item" data-cc="ST"><input type="checkbox" class="cfwaf-country-check" value="ST"><div><div class="cfwaf-zone-name">Sao Tome &amp; Principe</div><div class="cfwaf-zone-plan">ST</div></div></div>
					<div class="cfwaf-zone-item" data-cc="SA"><input type="checkbox" class="cfwaf-country-check" value="SA"><div><div class="cfwaf-zone-name">Saudi Arabia</div><div class="cfwaf-zone-plan">SA</div></div></div>
					<div class="cfwaf-zone-item" data-cc="SN"><input type="checkbox" class="cfwaf-country-check" value="SN"><div><div class="cfwaf-zone-name">Senegal</div><div class="cfwaf-zone-plan">SN</div></div></div>
					<div class="cfwaf-zone-item" data-cc="RS"><input type="checkbox" class="cfwaf-country-check" value="RS"><div><div class="cfwaf-zone-name">Serbia</div><div class="cfwaf-zone-plan">RS</div></div></div>
					<div class="cfwaf-zone-item" data-cc="SC"><input type="checkbox" class="cfwaf-country-check" value="SC"><div><div class="cfwaf-zone-name">Seychelles</div><div class="cfwaf-zone-plan">SC</div></div></div>
					<div class="cfwaf-zone-item" data-cc="SL"><input type="checkbox" class="cfwaf-country-check" value="SL"><div><div class="cfwaf-zone-name">Sierra Leone</div><div class="cfwaf-zone-plan">SL</div></div></div>
					<div class="cfwaf-zone-item" data-cc="SG"><input type="checkbox" class="cfwaf-country-check" value="SG"><div><div class="cfwaf-zone-name">Singapore</div><div class="cfwaf-zone-plan">SG</div></div></div>
					<div class="cfwaf-zone-item" data-cc="SK"><input type="checkbox" class="cfwaf-country-check" value="SK"><div><div class="cfwaf-zone-name">Slovakia</div><div class="cfwaf-zone-plan">SK</div></div></div>
					<div class="cfwaf-zone-item" data-cc="SI"><input type="checkbox" class="cfwaf-country-check" value="SI"><div><div class="cfwaf-zone-name">Slovenia</div><div class="cfwaf-zone-plan">SI</div></div></div>
					<div class="cfwaf-zone-item" data-cc="SB"><input type="checkbox" class="cfwaf-country-check" value="SB"><div><div class="cfwaf-zone-name">Solomon Islands</div><div class="cfwaf-zone-plan">SB</div></div></div>
					<div class="cfwaf-zone-item" data-cc="SO"><input type="checkbox" class="cfwaf-country-check" value="SO"><div><div class="cfwaf-zone-name">Somalia</div><div class="cfwaf-zone-plan">SO</div></div></div>
					<div class="cfwaf-zone-item" data-cc="ZA"><input type="checkbox" class="cfwaf-country-check" value="ZA"><div><div class="cfwaf-zone-name">South Africa</div><div class="cfwaf-zone-plan">ZA</div></div></div>
					<div class="cfwaf-zone-item" data-cc="SS"><input type="checkbox" class="cfwaf-country-check" value="SS"><div><div class="cfwaf-zone-name">South Sudan</div><div class="cfwaf-zone-plan">SS</div></div></div>
					<div class="cfwaf-zone-item" data-cc="ES"><input type="checkbox" class="cfwaf-country-check" value="ES"><div><div class="cfwaf-zone-name">Spain</div><div class="cfwaf-zone-plan">ES</div></div></div>
					<div class="cfwaf-zone-item" data-cc="LK"><input type="checkbox" class="cfwaf-country-check" value="LK"><div><div class="cfwaf-zone-name">Sri Lanka</div><div class="cfwaf-zone-plan">LK</div></div></div>
					<div class="cfwaf-zone-item" data-cc="SD"><input type="checkbox" class="cfwaf-country-check" value="SD"><div><div class="cfwaf-zone-name">Sudan</div><div class="cfwaf-zone-plan">SD</div></div></div>
					<div class="cfwaf-zone-item" data-cc="SR"><input type="checkbox" class="cfwaf-country-check" value="SR"><div><div class="cfwaf-zone-name">Suriname</div><div class="cfwaf-zone-plan">SR</div></div></div>
					<div class="cfwaf-zone-item" data-cc="SE"><input type="checkbox" class="cfwaf-country-check" value="SE"><div><div class="cfwaf-zone-name">Sweden</div><div class="cfwaf-zone-plan">SE</div></div></div>
					<div class="cfwaf-zone-item" data-cc="CH"><input type="checkbox" class="cfwaf-country-check" value="CH"><div><div class="cfwaf-zone-name">Switzerland</div><div class="cfwaf-zone-plan">CH</div></div></div>
					<div class="cfwaf-zone-item" data-cc="SY"><input type="checkbox" class="cfwaf-country-check" value="SY"><div><div class="cfwaf-zone-name">Syria</div><div class="cfwaf-zone-plan">SY</div></div></div>
					<div class="cfwaf-zone-item" data-cc="TW"><input type="checkbox" class="cfwaf-country-check" value="TW"><div><div class="cfwaf-zone-name">Taiwan</div><div class="cfwaf-zone-plan">TW</div></div></div>
					<div class="cfwaf-zone-item" data-cc="TJ"><input type="checkbox" class="cfwaf-country-check" value="TJ"><div><div class="cfwaf-zone-name">Tajikistan</div><div class="cfwaf-zone-plan">TJ</div></div></div>
					<div class="cfwaf-zone-item" data-cc="TZ"><input type="checkbox" class="cfwaf-country-check" value="TZ"><div><div class="cfwaf-zone-name">Tanzania</div><div class="cfwaf-zone-plan">TZ</div></div></div>
					<div class="cfwaf-zone-item" data-cc="TH"><input type="checkbox" class="cfwaf-country-check" value="TH"><div><div class="cfwaf-zone-name">Thailand</div><div class="cfwaf-zone-plan">TH</div></div></div>
					<div class="cfwaf-zone-item" data-cc="TL"><input type="checkbox" class="cfwaf-country-check" value="TL"><div><div class="cfwaf-zone-name">Timor-Leste</div><div class="cfwaf-zone-plan">TL</div></div></div>
					<div class="cfwaf-zone-item" data-cc="TG"><input type="checkbox" class="cfwaf-country-check" value="TG"><div><div class="cfwaf-zone-name">Togo</div><div class="cfwaf-zone-plan">TG</div></div></div>
					<div class="cfwaf-zone-item" data-cc="TO"><input type="checkbox" class="cfwaf-country-check" value="TO"><div><div class="cfwaf-zone-name">Tonga</div><div class="cfwaf-zone-plan">TO</div></div></div>
					<div class="cfwaf-zone-item" data-cc="TT"><input type="checkbox" class="cfwaf-country-check" value="TT"><div><div class="cfwaf-zone-name">Trinidad &amp; Tobago</div><div class="cfwaf-zone-plan">TT</div></div></div>
					<div class="cfwaf-zone-item" data-cc="TN"><input type="checkbox" class="cfwaf-country-check" value="TN"><div><div class="cfwaf-zone-name">Tunisia</div><div class="cfwaf-zone-plan">TN</div></div></div>
					<div class="cfwaf-zone-item" data-cc="TR"><input type="checkbox" class="cfwaf-country-check" value="TR"><div><div class="cfwaf-zone-name">Turkey</div><div class="cfwaf-zone-plan">TR</div></div></div>
					<div class="cfwaf-zone-item" data-cc="TM"><input type="checkbox" class="cfwaf-country-check" value="TM"><div><div class="cfwaf-zone-name">Turkmenistan</div><div class="cfwaf-zone-plan">TM</div></div></div>
					<div class="cfwaf-zone-item" data-cc="TV"><input type="checkbox" class="cfwaf-country-check" value="TV"><div><div class="cfwaf-zone-name">Tuvalu</div><div class="cfwaf-zone-plan">TV</div></div></div>
					<div class="cfwaf-zone-item" data-cc="UG"><input type="checkbox" class="cfwaf-country-check" value="UG"><div><div class="cfwaf-zone-name">Uganda</div><div class="cfwaf-zone-plan">UG</div></div></div>
					<div class="cfwaf-zone-item" data-cc="UA"><input type="checkbox" class="cfwaf-country-check" value="UA"><div><div class="cfwaf-zone-name">Ukraine</div><div class="cfwaf-zone-plan">UA</div></div></div>
					<div class="cfwaf-zone-item" data-cc="AE"><input type="checkbox" class="cfwaf-country-check" value="AE"><div><div class="cfwaf-zone-name">United Arab Emirates</div><div class="cfwaf-zone-plan">AE</div></div></div>
					<div class="cfwaf-zone-item" data-cc="GB"><input type="checkbox" class="cfwaf-country-check" value="GB"><div><div class="cfwaf-zone-name">United Kingdom</div><div class="cfwaf-zone-plan">GB</div></div></div>
					<div class="cfwaf-zone-item" data-cc="US"><input type="checkbox" class="cfwaf-country-check" value="US"><div><div class="cfwaf-zone-name">United States</div><div class="cfwaf-zone-plan">US</div></div></div>
					<div class="cfwaf-zone-item" data-cc="UY"><input type="checkbox" class="cfwaf-country-check" value="UY"><div><div class="cfwaf-zone-name">Uruguay</div><div class="cfwaf-zone-plan">UY</div></div></div>
					<div class="cfwaf-zone-item" data-cc="UZ"><input type="checkbox" class="cfwaf-country-check" value="UZ"><div><div class="cfwaf-zone-name">Uzbekistan</div><div class="cfwaf-zone-plan">UZ</div></div></div>
					<div class="cfwaf-zone-item" data-cc="VU"><input type="checkbox" class="cfwaf-country-check" value="VU"><div><div class="cfwaf-zone-name">Vanuatu</div><div class="cfwaf-zone-plan">VU</div></div></div>
					<div class="cfwaf-zone-item" data-cc="VE"><input type="checkbox" class="cfwaf-country-check" value="VE"><div><div class="cfwaf-zone-name">Venezuela</div><div class="cfwaf-zone-plan">VE</div></div></div>
					<div class="cfwaf-zone-item" data-cc="VN"><input type="checkbox" class="cfwaf-country-check" value="VN"><div><div class="cfwaf-zone-name">Vietnam</div><div class="cfwaf-zone-plan">VN</div></div></div>
					<div class="cfwaf-zone-item" data-cc="YE"><input type="checkbox" class="cfwaf-country-check" value="YE"><div><div class="cfwaf-zone-name">Yemen</div><div class="cfwaf-zone-plan">YE</div></div></div>
					<div class="cfwaf-zone-item" data-cc="ZM"><input type="checkbox" class="cfwaf-country-check" value="ZM"><div><div class="cfwaf-zone-name">Zambia</div><div class="cfwaf-zone-plan">ZM</div></div></div>
					<div class="cfwaf-zone-item" data-cc="ZW"><input type="checkbox" class="cfwaf-country-check" value="ZW"><div><div class="cfwaf-zone-name">Zimbabwe</div><div class="cfwaf-zone-plan">ZW</div></div></div>
					<div id="cfwaf-country-empty" style="display:none;grid-column:1/-1;text-align:center;padding:24px;color:#6b7280;font-size:13px;">No countries match your search.</div>
				</div>

			</div><!-- /cfwaf-country-panel -->

			<!-- RULE 5 -->
			<div class="cfwaf-rule-card">
				<div class="cfwaf-rule-header">
					<div class="cfwaf-rule-info">
						<span class="cfwaf-rule-num cfwaf-num-challenge">5</span>
						<div>
							<h3>Challenge VPN Connections &amp; wp-login</h3>
							<p>Managed challenge for known VPN providers and the WordPress login page.</p>
						</div>
					</div>
					<div class="cfwaf-rule-controls">
						<span class="cfwaf-action-badge cfwaf-badge-challenge">CHALLENGE</span>
						<label class="cfwaf-toggle"><input type="checkbox" data-setting="rule5.enabled"><span></span></label>
					</div>
				</div>
				<div class="cfwaf-rule-body">

					<div class="cfwaf-section">
						<h4>VPN Providers</h4>
						<label class="cfwaf-check-label cfwaf-select-all-label">
							<input type="checkbox" data-setting="rule5.challenge_all_vpn" id="cfwaf-vpn-all">
							<strong>Challenge All VPN Connections</strong> &mdash; Apply managed challenge to all known VPN providers below
						</label>
						<div class="cfwaf-checkbox-grid" style="margin-top:10px">
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule5.challenge_nordvpn" class="cfwaf-vpn-check"> NordVPN</label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule5.challenge_expressvpn" class="cfwaf-vpn-check"> ExpressVPN</label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule5.challenge_purevpn" class="cfwaf-vpn-check"> PureVPN</label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule5.challenge_surfshark" class="cfwaf-vpn-check"> Surfshark</label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule5.challenge_ipvanish" class="cfwaf-vpn-check"> IPVanish</label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule5.challenge_quadranet" class="cfwaf-vpn-check"> QuadraNet</label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule5.challenge_ovhfr" class="cfwaf-vpn-check"> OVH France</label>
					<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule5.challenge_internetutils" class="cfwaf-vpn-check"> Internet Utilities</label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule5.challenge_mullvad" class="cfwaf-vpn-check"> Mullvad VPN</label>
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule5.challenge_privlayer" class="cfwaf-vpn-check"> Private Layer</label>
						</div>
					</div>

					<div class="cfwaf-section">
						<h4>Protected Paths</h4>
						<div class="cfwaf-checkbox-grid">
							<label class="cfwaf-check-label"><input type="checkbox" data-setting="rule5.challenge_wplogin"> WordPress Login (wp-login.php)</label>
						</div>
					</div>

				</div>
			</div>

			<!-- Bottom action bar -->
			<div class="cfwaf-bottom-bar">
				<div class="cfwaf-bottom-left">
					<button class="cfwaf-btn cfwaf-btn-save" id="cfwaf-save-settings">
						<span class="dashicons dashicons-saved"></span> Save Settings
					</button>
					<button class="cfwaf-btn cfwaf-btn-reset" id="cfwaf-reset-settings">
						<span class="dashicons dashicons-image-rotate"></span> Reset All Settings to Default
					</button>
					<button class="cfwaf-btn cfwaf-btn-export" id="cfwaf-export-settings">
						<span class="dashicons dashicons-download"></span> Export Settings
					</button>
					<label class="cfwaf-btn cfwaf-btn-import" id="cfwaf-import-label">
						<span class="dashicons dashicons-upload"></span> Import Settings
						<input type="file" id="cfwaf-import-file" accept=".json" style="display:none">
					</label>
				</div>
				<button class="cfwaf-btn cfwaf-btn-deploy" id="cfwaf-toggle-deploy">
					<span class="dashicons dashicons-upload"></span> Deploy Rules to Your Sites
				</button>
			</div>

			<!-- Inline deploy zone picker -->
			<div class="cfwaf-deploy-panel" id="cfwaf-deploy-panel" style="display:none">
				<div class="cfwaf-deploy-panel-header">
					<h3><span class="dashicons dashicons-upload"></span> Select Sites to Deploy To</h3>
					<button class="cfwaf-btn cfwaf-btn-ghost-dark" id="cfwaf-deploy-close">&#10005; Cancel</button>
				</div>

				<div id="cfwaf-deploy-loading" class="cfwaf-loading-state">
					<span class="cfwaf-spinner"></span> Loading your Cloudflare zones&hellip;
				</div>

				<div id="cfwaf-deploy-ready" style="display:none">
					<div class="cfwaf-deploy-toolbar">
						<label class="cfwaf-check-label cfwaf-select-all-label">
							<input type="checkbox" id="cfwaf-select-all-zones"> <strong>Select All Zones</strong>
						</label>
						<input type="search" id="cfwaf-zone-search" class="cfwaf-input cfwaf-search-input" placeholder="&#128269; Filter zones&hellip;">
					</div>
					<div class="cfwaf-zones-grid" id="cfwaf-zones-grid"></div>
					<div class="cfwaf-deploy-rules-summary" id="cfwaf-deploy-summary"></div>
					<div id="cfwaf-deploy-results"></div>
					<div class="cfwaf-deploy-footer">
						<button class="cfwaf-btn cfwaf-btn-deploy cfwaf-btn-large" id="cfwaf-confirm-deploy" disabled>
							<span class="dashicons dashicons-upload"></span> Deploy to Selected Zones
						</button>
					</div>
				</div>

				<div id="cfwaf-deploy-no-creds" style="display:none">
					<p class="cfwaf-notice cfwaf-notice-error">Please verify your Cloudflare credentials above before deploying.</p>
				</div>
			</div>

		</div><!-- /tab-rules -->

		<!-- TAB: Rule Details -->
		<div class="cfwaf-tab-content" id="cfwaf-tab-details">

			<!-- Rule 1 Details -->
			<div class="cfwaf-detail-card">
				<div class="cfwaf-detail-header" data-detail="r1">
					<div class="cfwaf-detail-title">
						<span class="cfwaf-rule-num cfwaf-num-skip">1</span>
						<div>
							<strong>Allow Good Bots</strong>
							<span class="cfwaf-action-badge cfwaf-badge-skip">SKIP</span>
						</div>
					</div>
					<span class="cfwaf-detail-chevron dashicons dashicons-arrow-down-alt2"></span>
				</div>
				<div class="cfwaf-detail-body" id="cfwaf-detail-r1">
					<div class="cfwaf-detail-grid">
						<div class="cfwaf-detail-col">
							<h4><span class="dashicons dashicons-flag"></span> Purpose</h4>
							<p>This rule ensures that legitimate bots &mdash; such as search engine crawlers and monitoring services &mdash; can access your site without restrictions or challenges by using Cloudflare&rsquo;s Skip action. It must be Rule #1 so verified bots are whitelisted before any blocking or challenge rules run.</p>

							<h4><span class="dashicons dashicons-yes-alt"></span> Key Benefits</h4>
							<ul>
								<li>Ensures your site is properly indexed by search engines (Google, Bing, etc.)</li>
								<li>Allows monitoring tools to verify your site&rsquo;s uptime and performance</li>
								<li>Prevents disruptions to services that rely on API access</li>
								<li>Improves SEO by ensuring search engines can crawl your content efficiently</li>
								<li>Enables social media platforms to generate preview cards when your site is shared</li>
							</ul>
						</div>
						<div class="cfwaf-detail-col">
							<h4><span class="dashicons dashicons-warning"></span> Important Considerations</h4>
							<ul>
								<li>The Skip action is automatically configured via API when you deploy &mdash; no manual Cloudflare dashboard setup needed</li>
								<li>Be selective about which bot categories you allow if you have bandwidth or performance concerns</li>
								<li>If a bot you need is being blocked by other rules, enable it here to ensure it has unrestricted access</li>
							</ul>

							<h4><span class="dashicons dashicons-lightbulb"></span> Recommendation</h4>
							<p>Select only the Cloudflare verified bot categories you know you need. Always enable logging for proper testing and auditing.</p>
						</div>
					</div>
				</div>
			</div>

			<!-- Rule 2 Details -->
			<div class="cfwaf-detail-card">
				<div class="cfwaf-detail-header" data-detail="r2">
					<div class="cfwaf-detail-title">
						<span class="cfwaf-rule-num cfwaf-num-block">2</span>
						<div>
							<strong>Block Aggressive Crawlers &amp; WP Paths</strong>
							<span class="cfwaf-action-badge cfwaf-badge-block">BLOCK</span>
						</div>
					</div>
					<span class="cfwaf-detail-chevron dashicons dashicons-arrow-down-alt2"></span>
				</div>
				<div class="cfwaf-detail-body" id="cfwaf-detail-r2">
					<div class="cfwaf-detail-grid">
						<div class="cfwaf-detail-col">
							<h4><span class="dashicons dashicons-flag"></span> Purpose</h4>
							<p>This rule targets bots that consume excessive resources or crawl your site too aggressively, and protects sensitive WordPress paths from unauthorized access. It uses User-Agent matching and URI pattern detection to identify bad actors.</p>

							<h4><span class="dashicons dashicons-yes-alt"></span> Key Benefits</h4>
							<ul>
								<li>Reduces server load from aggressive crawlers that don&rsquo;t respect crawl limits</li>
								<li>Prevents bandwidth consumption from unauthorized SEO tools</li>
								<li>Blocks common penetration testing tools (Nikto, SQLMap, Masscan, Nmap)</li>
								<li>Protects sensitive WordPress paths (xmlrpc, wp-config, wp-json, install.php)</li>
								<li>Hides WordPress version info by blocking readme.html and license.txt</li>
								<li>Prevents XML-RPC amplification attacks and brute force attempts</li>
							</ul>
						</div>
						<div class="cfwaf-detail-col">
							<h4><span class="dashicons dashicons-warning"></span> Important Considerations</h4>
							<ul>
								<li>If you use SEO tools like Ahrefs or SEMrush, allow them in Rule #1 before blocking here</li>
								<li>If you use the WordPress REST API (wp-json), don&rsquo;t enable that path protection</li>
								<li>Some WordPress plugins or mobile apps may require XML-RPC access</li>
								<li>Monitor your Event logs after implementing &mdash; some legitimate bots may be caught</li>
							</ul>

							<h4><span class="dashicons dashicons-lightbulb"></span> Recommendation</h4>
							<p>Start with blocking generic unverified crawlers and bots first. For WordPress paths, enable xmlrpc and wlwmanifest blocking by default, and only enable wp-json blocking if you don&rsquo;t use the REST API.</p>
						</div>
					</div>
				</div>
			</div>

			<!-- Rule 3 Details -->
			<div class="cfwaf-detail-card">
				<div class="cfwaf-detail-header" data-detail="r3">
					<div class="cfwaf-detail-title">
						<span class="cfwaf-rule-num cfwaf-num-block">3</span>
						<div>
							<strong>Block or Challenge Web Hosts / TOR</strong>
							<span class="cfwaf-action-badge cfwaf-badge-block">BLOCK</span>
						</div>
					</div>
					<span class="cfwaf-detail-chevron dashicons dashicons-arrow-down-alt2"></span>
				</div>
				<div class="cfwaf-detail-body" id="cfwaf-detail-r3">
					<div class="cfwaf-detail-grid">
						<div class="cfwaf-detail-col">
							<h4><span class="dashicons dashicons-flag"></span> Purpose</h4>
							<p>This rule manages traffic from common web hosting providers and TOR exit nodes, which are frequently sources of automated attacks and malicious scripts. You can choose to block them entirely or use Managed Challenge to allow legitimate visitors through.</p>

							<h4><span class="dashicons dashicons-yes-alt"></span> Key Benefits</h4>
							<ul>
								<li>Blocks or challenges automated attacks from web hosting providers where malicious scripts often run</li>
								<li>Prevents TOR-based attacks while optionally allowing legitimate TOR users</li>
								<li>Reduces fraudulent transactions and spam registrations</li>
								<li>Helps prevent credential stuffing attacks</li>
								<li>Flexible: Block for maximum security or Managed Challenge for legitimate proxy users</li>
							</ul>
						</div>
						<div class="cfwaf-detail-col">
							<h4><span class="dashicons dashicons-warning"></span> Important Considerations</h4>
							<ul>
								<li>Some legitimate visitors may use TOR for privacy reasons</li>
								<li>Corporate traffic sometimes routes through cloud providers or proxies</li>
								<li>External services you use may be hosted on blocked ASNs &mdash; allowlist them in Rule #1</li>
								<li>Monitor WAF events after deployment to check for false positives</li>
							</ul>

							<h4><span class="dashicons dashicons-lightbulb"></span> Recommendation</h4>
							<p>Start with Block action for maximum security. If you see false positives or need to allow legitimate proxy connections, switch to Managed Challenge &mdash; it still blocks automated attacks while letting real humans through after a quick challenge.</p>
						</div>
					</div>
				</div>
			</div>

			<!-- Rule 4 Details -->
			<div class="cfwaf-detail-card">
				<div class="cfwaf-detail-header" data-detail="r4">
					<div class="cfwaf-detail-title">
						<span class="cfwaf-rule-num cfwaf-num-challenge">4</span>
						<div>
							<strong>Challenge Large Providers / Country</strong>
							<span class="cfwaf-action-badge cfwaf-badge-challenge">CHALLENGE</span>
						</div>
					</div>
					<span class="cfwaf-detail-chevron dashicons dashicons-arrow-down-alt2"></span>
				</div>
				<div class="cfwaf-detail-body" id="cfwaf-detail-r4">
					<div class="cfwaf-detail-grid">
						<div class="cfwaf-detail-col">
							<h4><span class="dashicons dashicons-flag"></span> Purpose</h4>
							<p>This rule adds security by challenging traffic from cloud provider IP ranges (AWS, Google Cloud, Azure) where many automated attacks originate, and optionally challenges visitors from outside your target country audience.</p>

							<h4><span class="dashicons dashicons-yes-alt"></span> Key Benefits</h4>
							<ul>
								<li>Reduces automated attacks that often originate from cloud providers</li>
								<li>Helps prevent credential stuffing and brute force attempts</li>
								<li>Can limit spam and bot registrations from contact forms</li>
								<li>Adds geographic protection if your site only serves specific countries</li>
								<li>Uses Managed Challenge &mdash; legitimate visitors pass through transparently</li>
							</ul>
						</div>
						<div class="cfwaf-detail-col">
							<h4><span class="dashicons dashicons-warning"></span> Important Considerations</h4>
							<ul>
								<li>If you target a multi-national audience, leave the country option unchecked</li>
								<li>Corporate traffic and remote workers sometimes route through cloud providers</li>
								<li>API integrations with third-party services might be affected if they use these ASNs</li>
								<li>The country picker is always accessible below the Rule 4 card when the option is enabled</li>
							</ul>

							<h4><span class="dashicons dashicons-lightbulb"></span> Recommendation</h4>
							<p>Managed Challenge is barely invasive to humans but very effective against bots. Check all cloud provider options to start. Only enable the country restriction if your site serves a specific geographic audience.</p>
						</div>
					</div>
				</div>
			</div>

			<!-- Rule 5 Details -->
			<div class="cfwaf-detail-card">
				<div class="cfwaf-detail-header" data-detail="r5">
					<div class="cfwaf-detail-title">
						<span class="cfwaf-rule-num cfwaf-num-challenge">5</span>
						<div>
							<strong>Challenge VPN Connections &amp; wp-login</strong>
							<span class="cfwaf-action-badge cfwaf-badge-challenge">CHALLENGE</span>
						</div>
					</div>
					<span class="cfwaf-detail-chevron dashicons dashicons-arrow-down-alt2"></span>
				</div>
				<div class="cfwaf-detail-body" id="cfwaf-detail-r5">
					<div class="cfwaf-detail-grid">
						<div class="cfwaf-detail-col">
							<h4><span class="dashicons dashicons-flag"></span> Purpose</h4>
							<p>This rule protects WordPress login paths from unauthorized access and adds security against connections coming through VPN providers, which are frequently used for manual and automated attacks against WordPress sites.</p>

							<h4><span class="dashicons dashicons-yes-alt"></span> Key Benefits</h4>
							<ul>
								<li>Prevents most brute force attacks and credential stuffing on wp-login.php</li>
								<li>Blocks automated attacks targeting WordPress vulnerabilities</li>
								<li>Adds security against attacks originating from VPN services</li>
								<li>The Managed Challenge is transparent to real humans but stops automated scripts</li>
							</ul>
						</div>
						<div class="cfwaf-detail-col">
							<h4><span class="dashicons dashicons-warning"></span> Important Considerations</h4>
							<ul>
								<li>Legitimate visitors may use VPNs &mdash; monitor the Challenge Solve Rate (low CSR = good)</li>
								<li>For higher security, consider using a Cloudflare Configuration Rule to set &ldquo;I&rsquo;m Under Attack&rdquo; mode on wp-login.php</li>
								<li>For the highest security, use Cloudflare Access to protect wp-login.php and wp-admin instead</li>
							</ul>

							<h4><span class="dashicons dashicons-lightbulb"></span> Recommendation</h4>
							<p>Enable wp-login.php protection and select all VPN providers. This is one of the most impactful rules for WordPress security and won&rsquo;t noticeably disrupt legitimate users.</p>
						</div>
					</div>
				</div>
			</div>

		</div><!-- /tab-details -->

		<!-- TAB: Preview Expressions -->
		<div class="cfwaf-tab-content" id="cfwaf-tab-expressions">
			<div class="cfwaf-card">
				<div class="cfwaf-card-header">
					<h2>Generated Rule Expressions</h2>
					<button class="cfwaf-btn cfwaf-btn-ghost-dark" id="cfwaf-refresh-preview">
						<span class="dashicons dashicons-update"></span> Refresh
					</button>
				</div>
				<div class="cfwaf-card-body" id="cfwaf-expressions-container">
					<p class="cfwaf-hint">Click Refresh to generate expressions from your current settings.</p>
				</div>
			</div>
		</div>

		<!-- TAB: View Zone Rules -->
		<div class="cfwaf-tab-content" id="cfwaf-tab-zones">
			<div class="cfwaf-card">
				<div class="cfwaf-card-header">
					<h2>Live Zone WAF Rules</h2>
				</div>
				<div class="cfwaf-card-body">
					<div class="cfwaf-zone-inspect-row">
						<select id="cfwaf-zone-inspect" class="cfwaf-input" style="flex:1">
							<option value="">-- Select a zone to inspect --</option>
						</select>
						<button class="cfwaf-btn cfwaf-btn-ghost-dark" id="cfwaf-inspect-zone">
							<span class="dashicons dashicons-search"></span> Load Rules
						</button>
					</div>
					<div id="cfwaf-zone-rules-output"></div>
				</div>
			</div>
		</div>

		<!-- Credits -->
		<div class="cfwaf-credits">
			<div class="cfwaf-credits-inner">
				<div class="cfwaf-credits-col">
					<h4>Additional Resources</h4>
					<ul>
						<li><a href="https://developers.cloudflare.com/waf/custom-rules/" target="_blank">Custom Rules Documentation</a></li>
						<li><a href="https://developers.cloudflare.com/ruleset-engine/rules-language/" target="_blank">Rules Language Reference</a></li>
						<li><a href="https://developers.cloudflare.com/bots/concepts/bot/" target="_blank">Verified Bot Categories</a></li>
						<li><a href="https://radar.cloudflare.com/traffic/verified-bots" target="_blank">Cloudflare Radar &amp; Verified Bots</a></li>
						<li><a href="https://github.com/jaimealnassim/wpwafmanager" target="_blank">Plugin on GitHub</a></li>
					</ul>
				</div>
				<div class="cfwaf-credits-col">
					<h4>Credits</h4>
					<ul>
						<li><a href="https://webagencyhero.com/" target="_blank"><strong>WebAgencyHero</strong></a> &mdash; Troy Glancy, for creating the original &ldquo;5 Cloudflare WAF rules&rdquo; that formed the foundation of this tool.</li>
						<li><strong>URSA6</strong> &mdash; Michael Bourne, for building <a href="https://wafrules.com" target="_blank">wafrules.com</a> and making broad changes and security adjustments to the rules.</li>
						<li><a href="https://www.nahnuplugins.com" target="_blank"><strong>Nahnu Plugins</strong></a> &mdash; Plugin development, UI enhancements, country picker, and one-click API deployment.</li>
					</ul>
				</div>
				<div class="cfwaf-credits-disclaimer">
					<strong>Trademark Notice:</strong> Cloudflare is a registered trademark of Cloudflare, Inc. This plugin is not affiliated with, endorsed by, or sponsored by Cloudflare, Inc. in any way.<br><br>
					<strong>Disclaimer:</strong> This plugin is provided &ldquo;as is&rdquo; without warranty of any kind, express or implied, including but not limited to warranties of merchantability, fitness for a particular purpose, or non-infringement. The creators and contributors are not responsible for any damage, data loss, security incidents, or other issues arising from the installation, configuration, or use of this plugin or the WAF rules it deploys. WAF rules interact directly with your live Cloudflare configuration &mdash; always test thoroughly in a staging environment before applying to production. Monitor your Cloudflare Security Events log closely after deployment and watch for false positives. By using this plugin you accept full responsibility for your Cloudflare WAF configuration.
				</div>
			</div>
		</div>

	</div><!-- /cfwaf-body -->
</div><!-- /cfwaf-wrap -->
<script>
/* Inline cfWAF data */
window.cfWAF = <?php
	$saved_settings = get_option( 'wpwaf_rule_settings', [] );
	if ( empty( $saved_settings ) ) $saved_settings = WPWAF_Rule_Builder::default_settings();
	$active     = WPWAF_Accounts::active();
	$has_creds  = false;
	if ( $active ) {
		$m = $active['auth_method'] ?? 'token';
		$has_creds = $m === 'key'
			? ( ! empty( $active['email'] ) && ! empty( $active['api_key'] ) )
			: ! empty( $active['api_token'] );
	}
	echo wp_json_encode( [
		'ajax_url'   => admin_url( 'admin-ajax.php' ),
		'nonce'      => wp_create_nonce( 'wpwaf_nonce' ),
		'settings'   => $saved_settings,
		'defaults'   => WPWAF_Rule_Builder::default_settings(),
		'has_creds'  => $has_creds,
		'expires_at' => (int) ( $active['expires_at'] ?? 0 ),
		'accounts'   => WPWAF_Accounts::all(),
		'active_id'  => WPWAF_Accounts::active_id(),
	] );
?>;
<?php echo file_get_contents( WPWAF_DIR . 'assets/js/admin.js' ); ?>
</script>
