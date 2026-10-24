# WP WAF Manager

> Deploy and manage your entire Cloudflare security stack directly from WordPress. WAF rules, DNS, zone analytics, cache control, IP blocking, security events, and email routing. No Cloudflare dashboard needed for day-to-day tasks.

[![License: GPL v2](https://img.shields.io/badge/License-GPLv2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0)
[![WordPress: 6.0+](https://img.shields.io/badge/WordPress-6.0%2B-3858e9)](https://wordpress.org)
[![PHP: 8.0+](https://img.shields.io/badge/PHP-8.0%2B-777BB4)](https://php.net)

**Free on GitHub.** Pro version available as a **one-time purchase** at [wpwafmanager.com](https://wpwafmanager.com).

---

## Features

### WAF Rules Builder

Five pre-configured, battle-tested security rules deployable in one click, based on the [wafrules.com](https://wafrules.com) ruleset (last updated October 21, 2026):

| Rule | Action | Purpose |
|------|--------|---------|
| Allow Good Bots | Skip | Every Cloudflare verified bot (or chosen categories), services matched by their published IP lists (ManageWP, WP Umbrella, ShortPixel, Patchstack and more), Let's Encrypt + your server IPs, custom user agents and expressions |
| Block Aggressive Crawlers & WordPress Paths | Block | Aggressive crawlers, exploit scanners (SQLMap, Nikto, Masscan), sensitive WP paths and exposed files |
| Block or Challenge Web Hosts / TOR & Exploit URIs | Block or Managed Challenge | Cloud hosting ASNs (DigitalOcean, Vultr, Hetzner, OVH and more), TOR exit nodes and six exploit URI groups (SQL injection, file inclusion, XSS and more) |
| Challenge Large Providers / Country | Managed Challenge | AWS EC2, Google Cloud, Azure and the countries you pick |
| Challenge VPN Connections & wp-login | Managed Challenge | NordVPN, ExpressVPN, Surfshark, other VPN providers + WordPress login |

- Rule 1 skips the later custom rules, rate limiting, Super Bot Fight Mode and legacy security products for allowed traffic. Cloudflare managed rules still run
- Your server IPs (IPv4, IPv6, CIDR), custom user agents and custom Cloudflare expressions to exclude trusted traffic from all rules
- Saved settings from older versions are moved to the new rule layout automatically
- Rule Profiles: save complete configurations under a name, with notes, and switch in one click
- Domain Profiles: save groups of zones and select them all before a deploy in one click
- Live expression preview before deployment
- Deploy to one zone, a selection, or all zones at once. Deploys replace only the plugin's rules and keep other custom rules on the zone, or replace all custom rules when you choose
- Each rule is checked against Cloudflare's 4,096 character expression limit before deploy
- JSON export/import for sharing and backing up configurations
- Automatic Cloudflare Free plan compatibility: strips restricted phases on error 20120 and retries

### Zones

Every zone in your active Cloudflare account in one list, with its plan. Tag each zone with a flare (WooCommerce, SureCart, Fluent Cart, LearnDash, Staging, Client and more, or a custom label). Flares appear next to each zone in the deploy panel.

### DNS Manager

Full DNS record management without leaving WordPress:

- **21 record types:** A, AAAA, CAA, CERT, CNAME, DNSKEY, DS, HTTPS, LOC, MX, NAPTR, NS, PTR, SMIMEA, SPF, SRV, SSHFP, SVCB, TLSA, TXT, URI
- Add, edit, delete with type-aware form fields (priority, weight, port for SRV; flags/tag for CAA)
- Cloudflare proxy toggle per record
- Search and filter by record type or proxy status
- TTL control with human-readable labels
- Swipeable table on mobile

### Zone Status

Monitor all your Cloudflare zones from one page:

- **6 metrics per zone:** Requests, Bandwidth, Cache Rate %, Pageviews, Threats, Cached Requests
- Powered by Cloudflare's **GraphQL Analytics API** (not the deprecated REST endpoint)
- Auto-sync via WP-Cron: configurable interval (5 min → 24 hours), off by default
- Zone picker: choose exactly which zones to sync (blank = none; no surprise API calls on first install)
- Analytics window: last 24 hours to 30 days
- Daily traffic sparkline on every zone card
- **Zone detail view** (click any zone): stat tiles with change vs the previous period; cached vs not cached requests and bandwidth; visitors and page views; threats; top countries, browsers, status codes, content types, TLS, threat types and IP classes; top pages, hostnames, devices and cache status; security events by action, source, path, IP and country; chart table views and CSV export
- Datasets are discovered per zone through the GraphQL `settings` node, so each section only asks for what the zone's plan includes and adapts to its lookback and maximum query window
- Dashboard widget showing zone count, last sync time, and quick links

### Clients & Reports

- Clients: name, contact name for the greeting, email addresses, internal notes and linked sites (zones)
- Send report dialog for one-time sends: choose the period, which of the client's addresses to use (never anyone else) and a message for that email; preview and test first
- Optional automatic monthly report per client, covering the last calendar month
- White-label emails: agency name, logo, brand color, From name, Reply-To and footer; the plugin is never mentioned
- A report covers all of the client's sites, with an overview table and a section per site
- The Zone Status detail view shows each site's clients with a Send report button, or a shortcut to add a client for that site
- KPI tiles with change vs the previous period, email-safe table-based charts (traffic by day or hour, countries, top pages, security actions, page responses) and plain-language explanations
- Monthly reports go out on a chosen day and hour in the site's timezone (hourly WP-Cron check, never double-sends)
- Sections a zone's plan cannot provide are left out; a send log keeps the last 20 emails

### Zone Controls

Manage common Cloudflare settings per zone from one page:

- **Under Attack Mode**: toggle per zone, plus emergency one-click enable across **all zones** for active DDoS events
- **Development Mode**: toggle per zone
- **Cache Purge**: purge everything, or by URL, hostname, prefix or cache tag (up to 100 items per purge, every plan)
- **Smart Tiered Cache**: on or off per zone
- **Security headers**: Managed Transforms to add security headers and remove `X-Powered-By`
- **Per-zone settings accordion** (loads on demand): SSL mode, Always Use HTTPS, Cache Level, Browser Cache TTL, Rocket Loader, Hotlink Protection, Email Obfuscation
- Settings are read through the per-setting endpoint, in parallel, because Cloudflare retires the bulk `GET /zones/{id}/settings` endpoint on March 31, 2027
- Security Level is no longer offered: Cloudflare retired Low, Medium and High in 2025 and uses it only for Under Attack mode

### Bot Control

- AI crawler policy per zone for search crawlers, assistants and agents, and training crawlers: allow, block on pages with ads, or block everywhere
- Managed robots.txt, syncing AI choices into robots.txt, and the crawler trap
- Bot Fight Mode on Free zones; Super Bot Fight Mode actions and Optimize for WordPress on Pro and above
- Bulk apply one policy to many zones (filter by search or flare) with per-zone results; unsupported settings are skipped per plan
- Bot Fight Mode cannot be skipped by custom rules, so the screen warns that it can block uptime monitors, payment webhooks, XML-RPC and server-to-server REST calls

### SSL & Insights

- Scan every zone for certificate expiry and status, plus SSL mode, Always Use HTTPS, Automatic HTTPS Rewrites, Minimum TLS, TLS 1.3 and HSTS
- Plain-language warnings and one-click fixes for the safe settings (Always Use HTTPS, Automatic HTTPS Rewrites, Minimum TLS 1.2)
- Security Insights: Cloudflare's account scan findings with severity filter, dismiss and restore

### IP Access Rules

Account-level rules that apply instantly across all zones: no per-zone deployment:

- **Target types:** IP address, IP range (CIDR), Country code, ASN
- **Actions:** Allow, Block, Managed Challenge, JS Challenge
- Filter rules by action type
- Per-account selector for multi-account setups
- Warns before saving a country Block, which Cloudflare only allows on Enterprise plans

### Security Events

View recent Cloudflare firewall events per zone:

- Powered by the Cloudflare **GraphQL Analytics API**
- Filter by action type (Block, Challenge, Allow, Log, Skip…)
- Time range: 1 hour to 7 days, up to 500 events
- Columns: timestamp, action, IP + country + ASN, method, path, source rule, user agent, Ray ID
- **Requires Cloudflare Pro plan or higher**: not available on Free zones

### Alerts & Health

- Cloudflare notification policies on any account the token can see: list, turn on or off, delete
- Presets for HTTP DDoS attack, Universal SSL, Health Check status and Security Insights alerts, offered only when the account supports them
- Email and webhook delivery (Slack, Teams or your own endpoint), and adding webhook destinations
- Health Checks on Pro plans and above: status, failure reason, create, edit, pause, delete, and an "Alert me" shortcut

### Turnstile

- Create, edit, rotate and delete Turnstile widgets in any account, with usage against the Free plan limits (20 widgets, 10 hostnames each)
- Copy each widget's site key and secret key into the client site; secrets are never stored by the plugin

### Page Rules

- Scan every zone for Cloudflare's deprecated Page Rules and see the modern replacement for each setting
- One-click migration of Forwarding URL rules to Single Redirects, and Cache Everything or Bypass rules to Cache Rules
- Preview before creating; new rules are appended to the phase ruleset, existing rules are never overwritten, and plan limits are checked
- Turn a migrated Page Rule off, or back on. Nothing is ever deleted

### Email Routing

Manage Cloudflare Email Routing (free on all plans) directly from WordPress:

- Enable/disable Email Routing per zone
- **Destination Addresses tab**: add your Gmail or any inbox, trigger the Cloudflare verification email, view verified/pending status
- **Forwarding Rules**: set a specific address (e.g. `contact@yourdomain.com`) and choose which verified inbox it forwards to
- **Catch-All Rule**: forward all unmatched email on the domain to one inbox, with on/off toggle
- Sync button to refresh data from Cloudflare after external changes
- Requires `Account → Email Routing Addresses → Edit` API token permission

### Plugin Settings

- **Admin Bar Quick Purge**: toggle on/off, pick which zone to purge, choose instant AJAX purge or navigate to Zone Controls
- **Zone Status**: auto-sync toggle and interval, default time range
- **Access Control**: minimum WordPress role picker (Administrator through Subscriber) with ⚠ warnings below admin
- **User Access**: choose exactly which administrators can see the plugin; everyone else sees no menu, widget, admin bar button or plugin listing
- **Menu Display**: show/hide Security Events, Email Routing, and the WP Dashboard widget
- **Keep data on uninstall**: on by default; when off, all plugin data is removed on plugin delete
- **Test Connection**: verify API credentials are working, shows account name and zone count

### Multi-Account Support

- Connect multiple Cloudflare accounts (API Token or Email + Global API Key)
- Switch accounts instantly with labels
- Configurable credential expiry (1 hour to forever)
- Credentials encrypted at rest with libsodium, stored with `autoload=false`, or defined as `wp-config.php` constants

### WordPress Dashboard Widget

A quick-glance summary widget on the WordPress dashboard homepage:

- Active account name
- Number of zones monitored
- Auto-sync status and last sync time
- Quick links to Zone Controls, Zone Status, WAF Rules
- Toggle on/off in Settings → Menu Display

---

## Requirements

- WordPress 6.0+
- PHP 8.0+
- Cloudflare account with at least one active zone

### API Token Permissions

| Feature | Permission needed |
|---------|------------------|
| WAF Rules | Zone → Zone WAF → Edit |
| Zone info and Zones list | Zone → Zone → Read |
| DNS Manager | Zone → DNS → Edit (Read for view-only) |
| Zone Controls | Zone → Zone Settings → Edit |
| Cache purge (Zone Controls, admin bar) | Zone → Cache Purge → Purge |
| Zone Status analytics | Zone → Analytics → Read |
| IP Access Rules | Account → Account Firewall Access Rules → Edit |
| Email Routing addresses | Account → Email Routing Addresses → Edit |
| Email Routing rules | Zone → Email Routing Rules → Edit |
| Security Events | Pro plan zone + Zone → Analytics → Read |
| Security headers (Zone Controls) | Zone → Managed Headers → Edit |
| Bot Control | Zone → Bot Management → Edit |
| SSL & Insights: certificates | Zone → SSL and Certificates → Read |
| SSL & Insights: HTTPS fixes | Zone → Zone Settings → Edit |
| SSL & Insights: Security Insights | Account → Security Center Insights → Read (Edit to dismiss) |
| Alerts | Account → Notifications → Edit |
| Health Checks | Zone → Health Checks → Edit |
| Turnstile | Account → Turnstile → Edit |
| Page Rules | Zone → Page Rules → Edit, Zone → Single Redirect → Edit, Zone → Cache Rules → Edit |

---

## Installation

### From GitHub

```bash
git clone https://github.com/jaimealnassim/wpwafmanager.git
cp -r wpwafmanager /path/to/wp-content/plugins/
```

Or download the zip and upload via **WP Admin → Plugins → Add New → Upload Plugin**.

### Creating Your API Token

1. Go to [Cloudflare API Tokens](https://dash.cloudflare.com/profile/api-tokens)
2. Click **Create Token** → use the "Edit zone DNS" template as a starting point
3. Add all required permissions (see table above)
4. Set Zone Resources to "All zones" (or specific zones)
5. Paste the token into WP WAF Manager and click **Verify & Save**

---

## Security

Every AJAX endpoint is protected by the same three-layer check:

1. **Nonce verification**: `check_ajax_referer('wpwaf_nonce', 'nonce')` on every request
2. **Capability check**: `current_user_can(WPWAF_Settings::required_capability())`, configurable in Settings
3. **Active account guard**: API-dependent handlers reject requests if no Cloudflare account is connected

Additional hardening:

- All `$_POST` inputs sanitized with `sanitize_text_field()`, `sanitize_email()`, `filter_var()`, or explicit int/bool casts before use
- Settings validated against a strict schema before writing to the database (`WPWAF_Settings::save()`)
- IP allowlist entries validated as proper IPv4/IPv6/CIDR notation
- IP rule targets and actions validated against allowlists before sending to Cloudflare
- Zone setting keys validated against an allowlist to prevent arbitrary setting writes
- Email rule action types validated against `['forward', 'worker', 'drop']`
- All view output escaped with `esc_html()`, `esc_attr()`, `esc_url()`, or `wp_json_encode()`
- Credentials stored with `autoload=false`: never loaded on front-end requests
- API Tokens recommended over Global API Keys for least-privilege access
- `uninstall.php` registered: clean removal respects the "keep data" setting
- No front-end scripts or styles loaded: admin-only plugin

---

## Performance

- All `wp_options` entries use `autoload=false`: credentials and cache never loaded on public page requests
- Zone analytics data cached in a single option per account: single DB read for the analytics page
- WP-Cron auto-sync off by default: zero background API calls until explicitly enabled
- Zone analytics sync skips zones not in the allowed list: no surprise Cloudflare API usage on first install
- Assets (CSS/JS) only enqueued on the plugin's own admin pages: zero front-end overhead
- Static in-request API instance cache: credentials read once per request, not per AJAX call
- `admin_footer` hook for admin bar JS: avoids `wp_add_inline_script` string-escaping issues

---

## Architecture

```
wpwafmanager/
├── wpwafmanager.php                    Main file: hooks, SureCart licensing, admin bar, activation
├── uninstall.php                       Data cleanup on delete (respects keep_data_on_uninstall)
├── release.json                        SureCart auto-updater metadata
├── licensing/src/                      SureCart SDK (Client, License, Activation, Updater, Settings)
├── assets/
│   ├── css/admin.css                   Core admin UI styles
│   ├── css/layout.css                  Shared page frame for every screen
│   ├── css/mobile.css                  Responsive breakpoints (900/660/480px)
│   ├── js/admin.js                     WAF rules page: vanilla JS, no jQuery dependency
│   └── js/touch.js                     Swipe/touch utilities for mobile tab navigation
└── includes/
    ├── class-cloudflare-api.php        REST + GraphQL API wrapper (all Cloudflare calls)
    ├── class-accounts.php              Multi-account credential storage and switching
    ├── class-rule-builder.php          WAF expression builder + settings schema validation
    ├── class-dns.php                   DNS record types, field definitions, sanitization
    ├── class-zone-status.php           Analytics cache management + WP-Cron scheduler
    ├── class-settings.php              Plugin-wide settings (roles, toggles, admin bar config)
    ├── class-admin.php                 Admin menu registration, asset enqueuing, dashboard widget
    ├── class-ajax.php                  Core wp_ajax_* handlers
    ├── class-analytics.php             Plan-aware zone analytics (GraphQL) for the detail view and reports
    ├── class-reports.php               Clients, white-label report emails and monthly scheduling
    ├── class-module.php                Shared base for feature modules (security checks, API calls, errors)
    ├── class-bots.php                  Bot Control
    ├── class-ssl-insights.php          SSL & Insights
    ├── class-alerts.php                Alerts & Health
    ├── class-turnstile.php             Turnstile widget management
    ├── class-page-rules.php            Page Rules audit and migration
    └── views/
        ├── page-main.php               WAF Rules Builder
        ├── page-dns.php                DNS Manager
        ├── page-zone-status.php        Zone Status
        ├── page-zone-detail.php        Zone analytics detail view
        ├── page-reports.php            Clients & Reports
        ├── email-report.php            Client report email template
        ├── page-zone-controls.php      Zone Controls
        ├── page-bots.php               Bot Control
        ├── page-ssl-insights.php       SSL & Insights
        ├── page-alerts.php             Alerts & Health
        ├── page-turnstile.php          Turnstile
        ├── page-page-rules.php         Page Rules
        ├── page-ip-rules.php           IP Access Rules
        ├── page-security-events.php    Security Events
        ├── page-email-routing.php      Email Routing
        ├── page-settings.php           Plugin Settings
        └── page-about.php             About + Free vs Pro
```

---

## Free vs Pro

| Feature | Free (GitHub) | Pro ([wpwafmanager.com](https://wpwafmanager.com)) |
|---------|:---:|:---:|
| WAF Rules Builder | ✅ | ✅ |
| DNS Manager | ✅ | ✅ |
| Zone Status | ✅ | ✅ |
| Clients & Reports | ✅ | ✅ |
| Zone Controls | ✅ | ✅ |
| Bot Control | ✅ | ✅ |
| SSL & Insights | ✅ | ✅ |
| Alerts & Health | ✅ | ✅ |
| Turnstile | ✅ | ✅ |
| Page Rules | ✅ | ✅ |
| IP Access Rules | ✅ | ✅ |
| Security Events | ✅ | ✅ |
| Email Routing | ✅ | ✅ |
| Multi-account | ✅ | ✅ |
| Dashboard widget | ✅ | ✅ |
| Plugin Settings | ✅ | ✅ |
| Automatic WP updates | ❌ | ✅ |
| Priority support | ❌ | ✅ |

---

## Changelog

Full changelog at [wpwafmanager.com/changelog](https://www.wpwafmanager.com/changelog/).
