=== WP WAF Manager ===
Contributors: nahnuplugins
Tags: waf, firewall, cloudflare, security, turnstile
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 1.0.24.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Manage Cloudflare from WordPress: WAF rules, DNS, bots, SSL, cache, alerts, Turnstile, IP rules and email routing across every client zone.

== Description ==

**WP WAF Manager** puts your entire Cloudflare setup inside WordPress. Deploy battle-tested WAF rules in one click, manage DNS, monitor traffic, purge cache, block IPs, and set up email forwarding without ever opening the Cloudflare dashboard for day-to-day tasks.

= WAF Rules Builder =

Deploy five pre-configured, battle-tested security rules to any Cloudflare zone in one click, based on the open-source [wafrules.com](https://wafrules.com) ruleset:

* **Allow Good Bots**: Whitelist Cloudflare verified bot categories (Googlebot, Bingbot, uptime monitors, payment processors) plus custom IP, user agent and Cloudflare expression allowlists
* **Block Aggressive Crawlers**: Block SEO scrapers, exploit scanners (SQLMap, Nikto, Masscan, Nmap), and sensitive WordPress paths (xmlrpc.php, wp-config.php, install.php)
* **Block or Challenge Web Hosts & TOR**: Block or challenge traffic from cloud hosting ASNs (DigitalOcean, Vultr, Hetzner, OVH, Contabo, and more) and TOR exit nodes
* **Challenge Large Cloud Providers**: Managed challenge for AWS EC2, Google Cloud, and Azure traffic
* **Challenge VPN & wp-login**: Managed challenge for NordVPN, ExpressVPN, Surfshark, and other VPN providers plus the WordPress login page

Additional: live expression preview, multi-zone deployment, JSON export/import, automatic Free plan compatibility.

* **Rule Profiles**: save complete rule configurations under a name, with notes, and switch between them in one click (up to 10)
* **Domain Profiles**: save groups of zones and select them all before a deploy in one click (up to 10)

= Zones =

Every zone in your active Cloudflare account in one list, with its plan. Tag each zone with a flare (WooCommerce, SureCart, Fluent Cart, LearnDash, Staging, Client and more, or your own label). Flares appear next to each zone in the deploy panel.

= DNS Manager =

Full DNS record management without leaving WordPress:

* 21 record types: A, AAAA, CAA, CERT, CNAME, DNSKEY, DS, HTTPS, LOC, MX, NAPTR, NS, PTR, SMIMEA, SPF, SRV, SSHFP, SVCB, TLSA, TXT, URI
* Add, edit, and delete records with type-aware form fields
* Cloudflare proxy toggle per record, TTL control, search and filter

= Zone Status =

Monitor all your Cloudflare zones from one page:

* 6 metrics per zone: Requests, Bandwidth, Cache Rate %, Pageviews, Threats, Cached Requests
* Powered by the Cloudflare GraphQL Analytics API
* Auto-sync via WP-Cron (off by default, enable in Settings)
* Zone picker: choose exactly which zones to monitor. Starts empty, no surprise API calls on first install
* Analytics window from last 24 hours to 30 days
* A daily traffic sparkline on every zone card

Click any zone for full analytics:

* Last 24 hours, 7 days, 30 days or last calendar month, with change vs the previous period
* Requests and bandwidth split into cached and not cached, unique visitors, page views, threats, bandwidth saved and encrypted traffic
* Charts with hover details and a table view, plus a CSV download
* Top countries, browsers, status codes, content types, TLS versions, threat types and IP classes
* Top pages, hostnames, devices and cache status
* Security events by action, Cloudflare feature, path, IP and country
* Each section adapts to what the zone's Cloudflare plan includes and says so when data is limited

= Clients & Reports =

Keep a list of your clients, link each one to their sites, and send them white-label Cloudflare stats emails:

* Clients with a contact name, email addresses, internal notes and their linked sites
* Send report: pick the period, which of the client's addresses to use and a message for this email, preview it, send yourself a test, then send
* Optional automatic monthly report per client, covering the last calendar month
* Your agency name, logo, brand color, From name and Reply-To; the plugin is never mentioned
* A report covers all of the client's sites, with an overview and a section per site
* Zone Status links each site to its clients, with a Send report button
* Visitors, page views, requests, threats stopped, cache rate and data delivered, with change against the previous period
* Email-safe charts for daily or hourly traffic, top countries, top pages, security actions and page responses, explained in plain language
* Monthly reports go out on the day and hour you choose (site timezone); preview with live or sample data first
* Sections a zone's Cloudflare plan cannot provide are left out automatically
* Send log of the last 20 emails

= Zone Controls =

Manage Cloudflare settings per zone from one dashboard:

* **Under Attack Mode**: toggle per zone, or enable across ALL zones instantly for DDoS emergencies
* **Development Mode**: toggle per zone
* **Cache Purge**: purge everything, or purge by URL, hostname, prefix or cache tag (up to 100 per purge, on every plan)
* **Smart Tiered Cache**: on or off per zone
* **Security headers**: Cloudflare Managed Transforms to add security headers and remove the X-Powered-By header
* Per-zone settings: SSL mode, Always Use HTTPS, Cache Level, Browser Cache TTL, Rocket Loader, Hotlink Protection, Email Obfuscation
* Settings load one at a time through Cloudflare's per-setting API, ready for the retirement of the bulk settings endpoint on March 31, 2027

= Bot Control =

Manage bot settings for every zone from one screen:

* AI crawler policy for search crawlers, assistants and agents, and training crawlers: allow, block on pages with ads, or block everywhere (training can also be disallowed in robots.txt)
* Managed robots.txt and syncing your AI choices into robots.txt
* Crawler trap for AI scrapers that ignore your rules
* Bot Fight Mode on Free zones; Super Bot Fight Mode options on Pro and above, including Optimize for WordPress
* Apply one policy to many zones at once, with per-zone results; settings a plan does not support are skipped

Note: Bot Fight Mode cannot be skipped by custom rules, so it can challenge uptime monitors, payment webhooks, XML-RPC and server-to-server REST calls.

= SSL & Insights =

Check certificates and HTTPS across every zone, plus Cloudflare's own security scan:

* Certificate expiry and status per zone (Universal, Advanced and custom certificates)
* HTTPS settings at a glance: SSL mode, Always Use HTTPS, Automatic HTTPS Rewrites, Minimum TLS, TLS 1.3 and HSTS
* Plain-language warnings, such as Flexible SSL or TLS 1.0 still allowed
* One-click fixes for safe settings: Always Use HTTPS, Automatic HTTPS Rewrites, Minimum TLS 1.2
* Security Insights: list, filter, dismiss and restore Cloudflare's account scan findings

= IP Access Rules =

Account-level IP rules that apply instantly across all zones:

* Target types: IP address, IP range (CIDR), Country code, ASN
* Actions: Allow, Block, Managed Challenge, JS Challenge
* Works with multiple accounts
* Blocking by country needs a Cloudflare Enterprise plan; the form warns before you save

= Security Events =

View recent Cloudflare firewall events per zone:

* Powered by the Cloudflare GraphQL Analytics API
* Filter by action (Block, Challenge, Allow, Skip…)
* Time range from 1 hour to 7 days, up to 500 events
* Requires Cloudflare Pro plan or higher

= Alerts & Health =

Cloudflare notifications and origin monitoring without leaving WordPress:

* Alerts: list, turn on or off and delete Cloudflare notification policies on any account your token can see
* One-click presets for WordPress sites: HTTP DDoS attack, Universal SSL certificate events, Health Check status and Security Insights (only types available on your account are offered)
* Deliver alerts by email or webhook (Slack, Teams or your own endpoint), and add webhook destinations
* Health Checks (Pro plan and above): monitor your origin from Cloudflare's network with status, failure reason, pause/resume, edit and delete
* "Alert me" on any health check sets up a status alert for it

= Turnstile =

Create and manage Cloudflare Turnstile widgets for every client site:

* Create, edit, rotate and delete widgets in any Cloudflare account; usage shown against the Free plan limits (20 widgets, 10 hostnames each)
* Copy a widget's site key and secret key to paste into the client site's Turnstile settings
* Rotating a secret shows the new key, and the old key keeps working for 2 hours while client sites are updated
* Secret keys are never stored by the plugin; they are read from Cloudflare only when you ask to see them

= Page Rules Migration =

Cloudflare has deprecated Page Rules. Scan all your zones to see every Page Rule and the modern replacement for each setting:

* Forwarding URL rules migrate to Single Redirects in one click
* Cache Everything and Bypass cache rules migrate to Cache Rules in one click
* Preview the new rule before it is created; new rules are added after your existing ones and nothing is deleted
* Turn the old Page Rule off once the new rule works, and back on at any time

= Email Routing =

Manage Cloudflare Email Routing (free on all plans) without leaving WordPress:

* Enable or disable Email Routing per zone
* Add destination addresses and trigger Cloudflare verification emails
* Set specific forwarding rules (e.g. contact@yourdomain.com → you@gmail.com)
* Configure the Catch-All rule to forward all unmatched email to one inbox, with on/off toggle
* Sync button to refresh data after making changes

= Plugin Settings =

* Admin Bar Quick Purge: zone picker, instant AJAX purge or navigate to Zone Controls
* Zone analytics auto-sync interval and default time range
* Access control: minimum role picker (Administrator recommended)
* User access: choose exactly which administrators can see the plugin; everyone else sees no menu, widget, admin bar button or plugin listing
* Menu visibility: hide Security Events, Email Routing, or the dashboard widget
* Keep data on uninstall toggle (on by default)
* Test Connection: verify API credentials instantly

= Multi-Account Support =

* Connect multiple Cloudflare accounts (API Token or Email + Global API Key)
* Switch accounts instantly with labels
* Configurable credential expiry

= WordPress Dashboard Widget =

A quick-glance card on the WP dashboard: account name, zones monitored, sync status, last sync time, and quick links. Toggle off in Settings if not needed.

= Requirements =

* WordPress 6.0 or later
* PHP 8.0 or later
* A Cloudflare account with at least one active zone
* API Token with: Zone → Zone WAF → Edit, Zone → Zone → Read, Zone → DNS → Edit (or Read for view-only DNS)
* Zone → Zone Settings → Edit and Zone → Cache Purge → Purge (for Zone Controls and the admin bar purge)
* Zone → Analytics → Read (for Zone Status and Security Events)
* Account → Account Firewall Access Rules → Edit (for IP Access Rules)
* Account → Email Routing Addresses → Edit and Zone → Email Routing Rules → Edit (for Email Routing)
* Zone → Managed Headers → Edit (for security headers in Zone Controls)
* Zone → Bot Management → Edit (for Bot Control)
* Zone → SSL and Certificates → Read, and Account → Security Center Insights → Read or Edit (for SSL & Insights)
* Account → Notifications → Edit and Zone → Health Checks → Edit (for Alerts & Health)
* Account → Turnstile → Edit (for Turnstile)
* Zone → Page Rules → Edit, Zone → Single Redirect → Edit and Zone → Cache Rules → Edit (for Page Rules migration)
* Security Events and Health Checks require a Cloudflare Pro plan or higher zone

== Installation ==

1. Upload the `wpwafmanager` folder to `/wp-content/plugins/` or install via Plugins → Add New → Upload Plugin
2. Activate via Plugins → Installed Plugins
3. Navigate to **WAF Manager** in the admin sidebar
4. Enter your Cloudflare API Token and click **Verify & Save**
5. Optionally go to **WAF Manager → License** and enter your Pro license key for automatic updates

= Creating Your API Token =

1. Visit [Cloudflare API Tokens](https://dash.cloudflare.com/profile/api-tokens)
2. Click Create Token and use the "Edit zone DNS" template as a starting point
3. Add the permissions listed under Requirements for the features you use. Each feature only needs its own permissions.
4. Set Zone Resources to "All zones" (or specify zones)
5. Copy the token into the plugin and click Verify & Save

== Frequently Asked Questions ==

= Does this work on Cloudflare Free plans? =

Yes. Every screen works on Free plans. Security Events and Health Checks need a Pro plan or higher, Super Bot Fight Mode options need Pro or higher, and blocking a whole country with IP Access Rules needs Enterprise.

= Does this replace the Cloudflare dashboard? =

No. It handles the most common day-to-day tasks. Advanced configuration (Workers, Pages, R2, Bulk Redirects, etc.) still requires the Cloudflare dashboard. For Email Routing, the plugin adds destination addresses and sends the verification email, but the address owner still clicks the link Cloudflare sends before forwarding starts.

= Is my API key stored securely? =

Credentials are encrypted at rest with libsodium using a key derived from your site's AUTH_KEY, stored with autoload=false, and never sent to the browser. You can also define them as constants in wp-config.php so they never touch the database. Use an API Token with minimum required permissions rather than your Global API Key.

= Can I manage multiple client sites? =

Yes. Use the multi-account feature to connect each client's Cloudflare account and switch between them from the WAF Rules page. Rule Profiles, Domain Profiles and zone flares make it quick to deploy the right rules to the right group of sites.

= What happens when I delete the plugin? =

By default, all data is kept (safe for testing or temporary removal). To have all plugin data removed on delete, go to Settings and disable "Keep data on uninstall" before deleting.

= What is the difference between free and Pro? =

The free version on GitHub is fully featured. Pro at [wpwafmanager.com](https://wpwafmanager.com) is available as a one-time purchase. It adds automatic plugin updates directly in your WP admin and priority support.

= Why are zones blank on first install? =

Zone Status starts with no zones selected and auto-sync off. This prevents surprise API calls to Cloudflare on first activation. Pick your zones under Zones to Sync on the Zone Status page, then turn on auto-sync in Settings when ready.

== Changelog ==

Full changelog at https://www.wpwafmanager.com/changelog/

== Upgrade Notice ==

= 1.0.0 =
Initial release. If upgrading from the cloudflare-waf-manager plugin, deactivate and delete it first. Both plugins cannot run simultaneously.
