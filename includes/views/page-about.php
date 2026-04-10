<?php defined( 'ABSPATH' ) || exit; ?>
<style>
.wpwaf-about-wrap{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;max-width:860px;padding:24px 20px;color:#1a1a2e;}
.wpwaf-about-hero{background:#111111;border-radius:12px;padding:36px 40px;margin-bottom:28px;display:flex;align-items:center;justify-content:space-between;gap:24px;flex-wrap:wrap;}
.wpwaf-about-hero-left{display:flex;align-items:center;gap:18px;}
.wpwaf-about-hero-icon{font-size:48px;width:48px;height:48px;color:#FF6A00;flex-shrink:0;}
.wpwaf-about-hero-title{margin:0;font-size:26px;font-weight:800;color:#fff;line-height:1.2;}
.wpwaf-about-hero-sub{margin:4px 0 0;font-size:13px;color:#9ca3af;}
.wpwaf-about-badge{display:inline-block;background:#FF6A00;color:#fff;font-size:11px;font-weight:700;padding:3px 10px;border-radius:10px;margin-left:10px;vertical-align:middle;}

/* Two column layout */
.wpwaf-about-cols{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;}
@media(max-width:700px){.wpwaf-about-cols{grid-template-columns:1fr;}}

/* Cards */
.wpwaf-about-card{background:#fff;border:1px solid #e2e6ea;border-radius:10px;padding:24px;display:flex;flex-direction:column;gap:14px;}
.wpwaf-about-card h2{margin:0;font-size:16px;font-weight:700;color:#1a1a2e;display:flex;align-items:center;gap:8px;}
.wpwaf-about-card p{margin:0;font-size:13px;color:#4b5563;line-height:1.7;}
.wpwaf-about-card ul{margin:0;padding-left:18px;font-size:13px;color:#4b5563;line-height:1.8;}
.wpwaf-about-card li{margin-bottom:2px;}

/* Free vs Pro */
.wpwaf-about-compare{background:#fff;border:1px solid #e2e6ea;border-radius:10px;overflow:hidden;margin-bottom:20px;}
.wpwaf-about-compare table{width:100%;border-collapse:collapse;font-size:13px;}
.wpwaf-about-compare th{padding:12px 16px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#6b7280;background:#f8f9fb;border-bottom:1px solid #e2e6ea;}
.wpwaf-about-compare th:not(:first-child){text-align:center;}
.wpwaf-about-compare td{padding:11px 16px;border-bottom:1px solid #f1f3f5;color:#374151;}
.wpwaf-about-compare tr:last-child td{border-bottom:none;}
.wpwaf-about-compare td:not(:first-child){text-align:center;font-size:16px;}
.wpwaf-about-compare .col-pro{background:#fff8f5;}
.wpwaf-about-compare th.col-pro{background:#fff1e6;color:#FF6A00;}

/* CTA buttons */
.wpwaf-about-cta{display:flex;gap:12px;flex-wrap:wrap;margin-top:4px;}
.wpwaf-about-btn{display:inline-flex;align-items:center;gap:8px;padding:11px 22px;border-radius:7px;font-size:14px;font-weight:700;text-decoration:none;transition:all .15s;cursor:pointer;border:none;}
.wpwaf-about-btn-primary{background:#FF6A00;color:#fff;}
.wpwaf-about-btn-primary:hover{background:#d95500;color:#fff;}
.wpwaf-about-btn-secondary{background:#fff;color:#000000 !important;border:2px solid #e2e6ea;}
.wpwaf-about-btn-secondary:hover{border-color:#FF6A00;color:#FF6A00 !important;}
.wpwaf-about-btn svg{width:18px;height:18px;fill:#000000 !important;color:#000000 !important;flex-shrink:0;}

/* Feature grid */
.wpwaf-about-features{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:12px;margin-bottom:20px;}
.wpwaf-about-feature{background:#fff;border:1px solid #e2e6ea;border-radius:8px;padding:16px 18px;}
.wpwaf-about-feature-icon{font-size:22px;margin-bottom:8px;display:block;}
.wpwaf-about-feature-title{font-size:13px;font-weight:700;color:#1a1a2e;margin:0 0 4px;}
.wpwaf-about-feature-desc{font-size:12px;color:#6b7280;margin:0;line-height:1.6;}

/* Version info */
.wpwaf-about-version{font-size:12px;color:#9ca3af;margin-top:4px;}
</style>

<div class="wpwaf-about-wrap">

	<!-- Hero -->
	<div class="wpwaf-about-hero">
		<div class="wpwaf-about-hero-left">
			<span class="dashicons dashicons-shield wpwaf-about-hero-icon"></span>
			<div>
				<h1 class="wpwaf-about-hero-title">
					WP WAF Manager
					<span class="wpwaf-about-badge">v<?php echo esc_html( WPWAF_VERSION ); ?></span>
				</h1>
				<p class="wpwaf-about-hero-sub">Visual Cloudflare security management — directly from your WordPress admin</p>
			</div>
		</div>
		<div class="wpwaf-about-cta">
			<a href="https://www.wpwafmanager.com" target="_blank" rel="noopener" class="wpwaf-about-btn wpwaf-about-btn-primary">
				🌐 wpwafmanager.com
			</a>
			<a href="https://github.com/jaimealnassim/wpwafmanager" target="_blank" rel="noopener" class="wpwaf-about-btn wpwaf-about-btn-secondary">
				<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"/></svg>
				GitHub
			</a>
		</div>
	</div>

	<!-- Feature grid -->
	<div class="wpwaf-about-features">
		<div class="wpwaf-about-feature">
			<span class="wpwaf-about-feature-icon">🛡</span>
			<div class="wpwaf-about-feature-title">WAF Rules Builder</div>
			<div class="wpwaf-about-feature-desc">Deploy 5 battle-tested security rules to any Cloudflare zone in one click. No expression language required.</div>
		</div>
		<div class="wpwaf-about-feature">
			<span class="wpwaf-about-feature-icon">🌐</span>
			<div class="wpwaf-about-feature-title">DNS Manager</div>
			<div class="wpwaf-about-feature-desc">Full DNS management with all 21 Cloudflare record types. Add, edit, delete, and toggle proxy without leaving WordPress.</div>
		</div>
		<div class="wpwaf-about-feature">
			<span class="wpwaf-about-feature-icon">📊</span>
			<div class="wpwaf-about-feature-title">Zone Analytics</div>
			<div class="wpwaf-about-feature-desc">Requests, bandwidth, cache rate, pageviews, and threats — synced automatically via the Cloudflare GraphQL API.</div>
		</div>
		<div class="wpwaf-about-feature">
			<span class="wpwaf-about-feature-icon">⚙️</span>
			<div class="wpwaf-about-feature-title">Zone Controls</div>
			<div class="wpwaf-about-feature-desc">Under Attack mode, Development mode, cache purge, SSL, security level, and more — all zones in one dashboard.</div>
		</div>
		<div class="wpwaf-about-feature">
			<span class="wpwaf-about-feature-icon">🔒</span>
			<div class="wpwaf-about-feature-title">IP Access Rules</div>
			<div class="wpwaf-about-feature-desc">Account-wide allowlist and blocklist. Block or challenge by IP, IP range, country, or ASN — applies to all zones instantly.</div>
		</div>
		<div class="wpwaf-about-feature">
			<span class="wpwaf-about-feature-icon">⚠️</span>
			<div class="wpwaf-about-feature-title">Security Events</div>
			<div class="wpwaf-about-feature-desc">View recent firewall events per zone. Filter by action, time range, and event source. Pro plan zones only.</div>
		</div>
		<div class="wpwaf-about-feature">
			<span class="wpwaf-about-feature-icon">📧</span>
			<div class="wpwaf-about-feature-title">Email Routing</div>
			<div class="wpwaf-about-feature-desc">Forward any address on your domain to any inbox. Set catch-all rules, manage destination addresses, and toggle routing — free on all Cloudflare plans.</div>
		</div>
		<div class="wpwaf-about-feature">
			<span class="wpwaf-about-feature-icon">👥</span>
			<div class="wpwaf-about-feature-title">Multi-Account</div>
			<div class="wpwaf-about-feature-desc">Connect multiple Cloudflare accounts. Perfect for agencies managing security across dozens of client sites.</div>
		</div>
	</div>

	<!-- Two columns: About + Get updates -->
	<div class="wpwaf-about-cols">

		<div class="wpwaf-about-card">
			<h2>🆓 Free Forever</h2>
			<p>WP WAF Manager is <strong>free and open-source</strong>, licensed under GPL-2.0. Every feature you see in this plugin is available at no cost.</p>
			<p>The full source code is on GitHub. Download updates, report issues, fork the project, or contribute improvements — for free, forever.</p>
			<div class="wpwaf-about-cta">
				<a href="https://github.com/jaimealnassim/wpwafmanager" target="_blank" rel="noopener" class="wpwaf-about-btn wpwaf-about-btn-secondary">
					<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"/></svg>
					Download on GitHub
				</a>
			</div>
		</div>

		<div class="wpwaf-about-card" style="border-color:#fcd9c0;background:#fffaf7;">
			<h2>⚡ Pro — Updates &amp; Support</h2>
			<p>The Pro version at <a href="https://www.wpwafmanager.com" target="_blank" rel="noopener" style="color:#FF6A00;font-weight:600;">wpwafmanager.com</a> is available as a <strong>one-time purchase</strong>. You get:</p>
			<ul>
				<li><strong>Automatic plugin updates</strong> — delivered directly in your WordPress admin, just like any premium plugin</li>
				<li><strong>Priority support</strong> — get help when you need it, for as long as your support is active</li>
				<li><strong>Support development</strong> — help keep the rules and plugin maintained and improved for everyone</li>
			</ul>
			<p style="font-size:12px;color:#9ca3af;margin-top:4px;">The plugin itself is identical — Pro simply adds the update delivery mechanism and support access.</p>
			<div class="wpwaf-about-cta">
				<a href="https://www.wpwafmanager.com" target="_blank" rel="noopener" class="wpwaf-about-btn wpwaf-about-btn-primary">
					Get Pro →
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpwafmanager-license' ) ); ?>" class="wpwaf-about-btn wpwaf-about-btn-secondary">
					🔑 Manage License
				</a>
			</div>
		</div>

	</div>

	<!-- Free vs Pro table -->
	<div class="wpwaf-about-compare">
		<table>
			<thead>
				<tr>
					<th style="width:60%;">Feature</th>
					<th>Free</th>
					<th class="col-pro">Pro</th>
				</tr>
			</thead>
			<tbody>
				<?php
				$rows = [
					[ 'WAF Rules Builder (5 rules)',      true, true  ],
					[ 'DNS Manager (21 record types)',    true, true  ],
					[ 'Zone Analytics Dashboard',         true, true  ],
					[ 'Zone Controls & Cache Purge',      true, true  ],
					[ 'IP Access Rules (account-wide)',   true, true  ],
					[ 'Security Events Viewer',           true, true  ],
					[ 'Multi-account support',            true, true  ],
					[ 'Automatic updates in WP Admin',          false, true ],
					[ 'Priority support', false, true ],
				];
				foreach ( $rows as [ $label, $free, $pro ] ) :
				?>
				<tr>
					<td><?php echo esc_html( $label ); ?></td>
					<td><?php echo $free ? '✅' : '—'; ?></td>
					<td class="col-pro"><?php echo $pro ? '✅' : '—'; ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>

	<!-- Credits -->
	<div class="wpwaf-about-card">
		<h2>📋 Credits &amp; Resources</h2>
		<ul>
			<li>WAF rules based on the five-rule pattern by <a href="https://webagencyhero.com" target="_blank" rel="noopener">Troy Glancy (WebAgencyHero)</a>, refined by <a href="https://wafrules.com" target="_blank" rel="noopener">Michael Bourne (URSA6) at wafrules.com</a></li>
			<li>Plugin by <a href="https://www.wpwafmanager.com" target="_blank" rel="noopener">WP WAF Manager</a> — built by <a href="https://nahnuplugins.com" target="_blank" rel="noopener">Nahnu Plugins</a></li>
			<li><a href="https://github.com/jaimealnassim/wpwafmanager" target="_blank" rel="noopener">View source on GitHub</a></li>
			<li><a href="https://developers.cloudflare.com/waf/" target="_blank" rel="noopener">Cloudflare WAF Documentation</a></li>
			<li><a href="https://developers.cloudflare.com/analytics/graphql-api/" target="_blank" rel="noopener">Cloudflare GraphQL Analytics API</a></li>
		</ul>
		<div class="wpwaf-about-version">
			WP WAF Manager v<?php echo esc_html( WPWAF_VERSION ); ?> &nbsp;·&nbsp;
			PHP <?php echo esc_html( PHP_VERSION ); ?> &nbsp;·&nbsp;
			WordPress <?php echo esc_html( get_bloginfo( 'version' ) ); ?>
		</div>
	</div>

</div>
