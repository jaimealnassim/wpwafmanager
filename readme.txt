=== WP WAF Manager ===
Contributors: nahnuplugins
Tags: waf, firewall, dns, security, cloudflare, ip blocking, email routing, cache purge
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Deploy and manage your entire Cloudflare security stack from WordPress — WAF rules, DNS, zone analytics, cache control, IP blocking, security events, and email routing.

== Description ==

**WP WAF Manager** puts your entire Cloudflare setup inside WordPress. Deploy battle-tested WAF rules in one click, manage DNS, monitor traffic, purge cache, block IPs, and set up email forwarding — without ever opening the Cloudflare dashboard for day-to-day tasks.

= WAF Rules Builder =

Deploy five pre-configured, battle-tested security rules to any Cloudflare zone in one click, based on the open-source [wafrules.com](https://wafrules.com) ruleset:

* **Allow Good Bots** — Whitelist Cloudflare verified bot categories (Googlebot, Bingbot, uptime monitors, payment processors) plus a custom IP allowlist
* **Block Aggressive Crawlers** — Block SEO scrapers, exploit scanners (SQLMap, Nikto, Masscan, Nmap), and sensitive WordPress paths (xmlrpc.php, wp-config.php, install.php)
* **Block Web Hosts & TOR** — Block traffic from cloud hosting ASNs (DigitalOcean, Vultr, Hetzner, OVH, Contabo, and more) and TOR exit nodes
* **Challenge Large Cloud Providers** — Managed challenge for AWS EC2, Google Cloud, and Azure traffic
* **Challenge VPN & wp-login** — Managed challenge for NordVPN, ExpressVPN, Surfshark, and other VPN providers plus the WordPress login page

Additional: custom IP allowlist, live expression preview, multi-zone deployment, JSON export/import, automatic Free plan compatibility.

= DNS Manager =

Full DNS record management without leaving WordPress:

* 21 record types: A, AAAA, CAA, CERT, CNAME, DNSKEY, DS, HTTPS, LOC, MX, NAPTR, NS, PTR, SMIMEA, SPF, SRV, SSHFP, SVCB, TLSA, TXT, URI
* Add, edit, and delete records with type-aware form fields
* Cloudflare proxy toggle per record, TTL control, search and filter

= Zone Analytics =

Monitor all your Cloudflare zones from one page:

* 6 metrics per zone: Requests, Bandwidth, Cache Rate %, Pageviews, Threats, Cached Requests
* Powered by the Cloudflare GraphQL Analytics API
* Auto-sync via WP-Cron (off by default — enable in Settings)
* Zone picker: choose exactly which zones to monitor — starts empty, no surprise API calls on first install
* Analytics window from last 24 hours to 30 days

= Zone Controls =

Manage Cloudflare settings per zone from one dashboard:

* **Under Attack Mode** — toggle per zone, or enable across ALL zones instantly for DDoS emergencies
* **Development Mode** — toggle per zone
* **Cache Purge** — purge everything or specific URLs
* Per-zone settings: SSL mode, Always Use HTTPS, Security Level, Cache Level, Browser Cache TTL, Rocket Loader, Hotlink Protection, Email Obfuscation

= IP Access Rules =

Account-level IP rules that apply instantly across all zones:

* Target types: IP address, IP range (CIDR), Country code, ASN
* Actions: Allow, Block, Managed Challenge, JS Challenge
* Works with multiple accounts

= Security Events =

View recent Cloudflare firewall events per zone:

* Powered by the Cloudflare GraphQL Analytics API
* Filter by action (Block, Challenge, Allow, Skip…)
* Time range from 1 hour to 7 days, up to 500 events
* Requires Cloudflare Pro plan or higher

= Email Routing =

Manage Cloudflare Email Routing (free on all plans) without leaving WordPress:

* Enable or disable Email Routing per zone
* Add destination addresses and trigger Cloudflare verification emails
* Set specific forwarding rules (e.g. contact@yourdomain.com → you@gmail.com)
* Configure the Catch-All rule — forward all unmatched email to one inbox, with on/off toggle
* Sync button to refresh data after making changes

= Plugin Settings =

* Admin Bar Quick Purge — zone picker, instant AJAX purge or navigate to Zone Controls
* Zone analytics auto-sync interval and default time range
* Access control — minimum role picker (Administrator recommended)
* Menu visibility — hide Security Events, Email Routing, or the dashboard widget
* Keep data on uninstall toggle (on by default)
* Test Connection — verify API credentials instantly

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
* API Token with: Zone → WAF → Edit, Zone → Zone → Read, Zone → DNS → Edit
* Account → Firewall Services → Edit (for IP Access Rules)
* Account → Email Routing Addresses → Edit (for Email Routing)
* Security Events requires a Cloudflare Pro plan or higher zone

== Installation ==

1. Upload the `wpwafmanager` folder to `/wp-content/plugins/` or install via Plugins → Add New → Upload Plugin
2. Activate via Plugins → Installed Plugins
3. Navigate to **WAF Manager** in the admin sidebar
4. Enter your Cloudflare API Token and click **Verify & Save**
5. Optionally go to **WAF Manager → License** and enter your Pro license key for automatic updates

= Creating Your API Token =

1. Visit [Cloudflare API Tokens](https://dash.cloudflare.com/profile/api-tokens)
2. Click Create Token and use the "Edit zone DNS" template as a starting point
3. Add: Zone → WAF → Edit, Zone → DNS → Edit, Account → Firewall Services → Edit, Account → Email Routing Addresses → Edit
4. Set Zone Resources to "All zones" (or specify zones)
5. Copy the token into the plugin and click Verify & Save

== Frequently Asked Questions ==

= Does this work on Cloudflare Free plans? =

Yes — WAF Rules, DNS Manager, Zone Analytics, Zone Controls, IP Access Rules, and Email Routing all work on Free plans. Security Events requires a Pro plan or higher.

= Does this replace the Cloudflare dashboard? =

No — it handles the most common day-to-day tasks. Advanced configuration (Workers, Pages, R2, Bulk Redirects, etc.) still requires the Cloudflare dashboard. Email Routing setup (verifying destination addresses) also happens in the Cloudflare dashboard; this plugin manages your existing routing rules.

= Is my API key stored securely? =

Credentials are stored in your WordPress database with base64 obfuscation and autoload=false — they are never loaded on front-end page requests. Use an API Token with minimum required permissions rather than your Global API Key.

= Can I manage multiple client sites? =

Yes. Use the multi-account feature to connect each client's Cloudflare account. Switch between accounts from the WAF Rules page.

= What happens when I delete the plugin? =

By default, all data is kept (safe for testing or temporary removal). To have all plugin data removed on delete, go to Settings and disable "Keep data on uninstall" before deleting.

= What is the difference between free and Pro? =

The free version on GitHub is fully featured. Pro at [wpwafmanager.com](https://wpwafmanager.com) is available as a one-time purchase. It adds automatic plugin updates directly in your WP admin and priority support.

= Why are zones blank on first install? =

Zone Analytics starts with no zones selected and auto-sync off. This prevents surprise API calls to Cloudflare on first activation. Select your zones in Zone Analytics → Settings and enable auto-sync when ready.

== Changelog ==

= 1.0.0 – April 2026 =
* Initial public release
* WAF Rules Builder — 5 battle-tested rules based on the wafrules.com ruleset (updated March 2026)
* DNS Manager — 21 record types, add/edit/delete, proxy toggle, TTL, search/filter
* Zone Analytics — Cloudflare GraphQL API, 6 stats per zone, WP-Cron auto-sync, zone picker
* Zone Controls — Under Attack mode (per zone + all zones), Dev Mode, cache purge, per-zone settings
* IP Access Rules — account-level, 4 target types, 4 actions, multi-account support
* Security Events — GraphQL-powered, filter by action, time range 1h–7d (Pro plan zones)
* Email Routing — destination address management, forwarding rules, catch-all rule, sync button
* Plugin Settings — admin bar purge, access control role picker, menu visibility, uninstall toggle
* WordPress Dashboard Widget — account/zone summary with quick links
* Multi-account support with credential expiry and obfuscation
* SureCart Pro licensing with auto-update support
* Automatic Cloudflare Free plan compatibility for WAF rules

== Upgrade Notice ==

= 1.0.0 =
Initial release. If upgrading from the cloudflare-waf-manager plugin, deactivate and delete it first — both plugins cannot run simultaneously.
