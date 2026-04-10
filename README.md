# WP WAF Manager

> Deploy and manage your entire Cloudflare security stack directly from WordPress. WAF rules, DNS, zone analytics, cache control, IP blocking, security events, and email routing — no Cloudflare dashboard needed for day-to-day tasks.

[![License: GPL v2](https://img.shields.io/badge/License-GPLv2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0)
[![WordPress: 6.0+](https://img.shields.io/badge/WordPress-6.0%2B-3858e9)](https://wordpress.org)
[![PHP: 8.0+](https://img.shields.io/badge/PHP-8.0%2B-777BB4)](https://php.net)

**Free on GitHub.** Pro version available as a **one-time purchase** at [wpwafmanager.com](https://wpwafmanager.com).

---

## Features

### WAF Rules Builder

Five pre-configured, battle-tested security rules deployable in one click — based on the [wafrules.com](https://wafrules.com) ruleset (last updated March 2026):

| Rule | Action | Purpose |
|------|--------|---------|
| Allow Good Bots | Skip | Googlebot, Bingbot, uptime monitors, payment processors + custom IP allowlist |
| Block Aggressive Crawlers | Block | SEO scrapers, exploit scanners (SQLMap, Nikto, Masscan), sensitive WP paths |
| Block Web Hosts & TOR | Block | Cloud hosting ASNs (DigitalOcean, Vultr, Hetzner, OVH…) and TOR exit nodes |
| Challenge Large Cloud Providers | Managed Challenge | AWS EC2, Google Cloud, Azure |
| Challenge VPN & wp-login | Managed Challenge | NordVPN, ExpressVPN, Surfshark, other VPN providers + WordPress login |

- Custom IP allowlist (IPv4, IPv6, CIDR) to exclude trusted addresses from all rules
- Live expression preview before deployment
- Deploy to one zone, a selection, or all zones at once
- JSON export/import for sharing and backing up configurations
- Automatic Cloudflare Free plan compatibility — strips restricted phases on error 20120 and retries

### DNS Manager

Full DNS record management without leaving WordPress:

- **21 record types:** A, AAAA, CAA, CERT, CNAME, DNSKEY, DS, HTTPS, LOC, MX, NAPTR, NS, PTR, SMIMEA, SPF, SRV, SSHFP, SVCB, TLSA, TXT, URI
- Add, edit, delete with type-aware form fields (priority, weight, port for SRV; flags/tag for CAA)
- Cloudflare proxy toggle per record
- Search and filter by record type or proxy status
- TTL control with human-readable labels
- Swipeable table on mobile

### Zone Analytics

Monitor all your Cloudflare zones from one page:

- **6 metrics per zone:** Requests, Bandwidth, Cache Rate %, Pageviews, Threats, Cached Requests
- Powered by Cloudflare's **GraphQL Analytics API** (not the deprecated REST endpoint)
- Auto-sync via WP-Cron — configurable interval (5 min → 24 hours), off by default
- Zone picker — choose exactly which zones to sync (blank = none; no surprise API calls on first install)
- Analytics window: last 24 hours to 30 days
- Dashboard widget showing zone count, last sync time, and quick links

### Zone Controls

Manage common Cloudflare settings per zone from one page:

- **Under Attack Mode** — toggle per zone, plus emergency one-click enable across **all zones** for active DDoS events
- **Development Mode** — toggle per zone
- **Cache Purge** — purge everything or specific URLs per zone
- **Per-zone settings accordion** (loads on demand): SSL mode, Always Use HTTPS, Security Level, Cache Level, Browser Cache TTL, Rocket Loader, Hotlink Protection, Email Obfuscation

### IP Access Rules

Account-level rules that apply instantly across all zones — no per-zone deployment:

- **Target types:** IP address, IP range (CIDR), Country code, ASN
- **Actions:** Allow, Block, Managed Challenge, JS Challenge
- Filter rules by action type
- Per-account selector for multi-account setups

### Security Events

View recent Cloudflare firewall events per zone:

- Powered by the Cloudflare **GraphQL Analytics API**
- Filter by action type (Block, Challenge, Allow, Log, Skip…)
- Time range: 1 hour to 7 days, up to 500 events
- Columns: timestamp, action, IP + country + ASN, method, path, source rule, user agent, Ray ID
- **Requires Cloudflare Pro plan or higher** — not available on Free zones

### Email Routing

Manage Cloudflare Email Routing (free on all plans) directly from WordPress:

- Enable/disable Email Routing per zone
- **Destination Addresses tab** — add your Gmail or any inbox, trigger the Cloudflare verification email, view verified/pending status
- **Forwarding Rules** — set a specific address (e.g. `contact@yourdomain.com`) and choose which verified inbox it forwards to
- **Catch-All Rule** — forward all unmatched email on the domain to one inbox, with on/off toggle
- Sync button to refresh data from Cloudflare after external changes
- Requires `Account → Email Routing Addresses → Edit` API token permission

### Plugin Settings

- **Admin Bar Quick Purge** — toggle on/off, pick which zone to purge, choose instant AJAX purge or navigate to Zone Controls
- **Zone Analytics** — auto-sync toggle and interval, default time range
- **Access Control** — minimum WordPress role picker (Administrator through Subscriber) with ⚠ warnings below admin
- **Menu Display** — show/hide Security Events, Email Routing, and the WP Dashboard widget
- **Keep data on uninstall** — on by default; when off, all plugin data is removed on plugin delete
- **Test Connection** — verify API credentials are working, shows account name and zone count

### Multi-Account Support

- Connect multiple Cloudflare accounts (API Token or Email + Global API Key)
- Switch accounts instantly with labels
- Configurable credential expiry (1 hour to forever)
- Credentials stored with base64 obfuscation and `autoload=false`

### WordPress Dashboard Widget

A quick-glance summary widget on the WordPress dashboard homepage:

- Active account name
- Number of zones monitored
- Auto-sync status and last sync time
- Quick links to Zone Controls, Zone Analytics, WAF Rules
- Toggle on/off in Settings → Menu Display

---

## Requirements

- WordPress 6.0+
- PHP 8.0+
- Cloudflare account with at least one active zone

### API Token Permissions

| Feature | Permission needed |
|---------|------------------|
| WAF Rules | Zone → WAF → Edit |
| Zone info | Zone → Zone → Read |
| DNS Manager | Zone → DNS → Edit |
| IP Access Rules | Account → Firewall Services → Edit |
| Email Routing addresses | Account → Email Routing Addresses → Edit |
| Email Routing rules | Zone → Email Routing Rules → Edit |
| Security Events | Pro plan zone + Zone → Analytics → Read |

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

1. **Nonce verification** — `check_ajax_referer('wpwaf_nonce', 'nonce')` on every request
2. **Capability check** — `current_user_can(WPWAF_Settings::required_capability())` — configurable in Settings
3. **Active account guard** — API-dependent handlers reject requests if no Cloudflare account is connected

Additional hardening:

- All `$_POST` inputs sanitized with `sanitize_text_field()`, `sanitize_email()`, `filter_var()`, or explicit int/bool casts before use
- Settings validated against a strict schema before writing to the database (`WPWAF_Settings::save()`)
- IP allowlist entries validated as proper IPv4/IPv6/CIDR notation
- IP rule targets and actions validated against allowlists before sending to Cloudflare
- Zone setting keys validated against an allowlist to prevent arbitrary setting writes
- Email rule action types validated against `['forward', 'worker', 'drop']`
- All view output escaped with `esc_html()`, `esc_attr()`, `esc_url()`, or `wp_json_encode()`
- Credentials stored with `autoload=false` — never loaded on front-end requests
- API Tokens recommended over Global API Keys for least-privilege access
- `uninstall.php` registered — clean removal respects the "keep data" setting
- No front-end scripts or styles loaded — admin-only plugin

---

## Performance

- All `wp_options` entries use `autoload=false` — credentials and cache never loaded on public page requests
- Zone analytics data cached in a single option per account — single DB read for the analytics page
- WP-Cron auto-sync off by default — zero background API calls until explicitly enabled
- Zone analytics sync skips zones not in the allowed list — no surprise Cloudflare API usage on first install
- Assets (CSS/JS) only enqueued on the plugin's own admin pages — zero front-end overhead
- Static in-request API instance cache — credentials read once per request, not per AJAX call
- `admin_footer` hook for admin bar JS — avoids `wp_add_inline_script` string-escaping issues

---

## Architecture

```
wpwafmanager/
├── wpwafmanager.php                    Main file — hooks, SureCart licensing, admin bar, activation
├── uninstall.php                       Data cleanup on delete (respects keep_data_on_uninstall)
├── release.json                        SureCart auto-updater metadata
├── licensing/src/                      SureCart SDK (Client, License, Activation, Updater, Settings)
├── assets/
│   ├── css/admin.css                   Core admin UI styles
│   └── css/mobile.css                  Responsive breakpoints (900/660/480px)
│   ├── js/admin.js                     WAF rules page — vanilla JS, no jQuery dependency
│   └── js/touch.js                     Swipe/touch utilities for mobile tab navigation
└── includes/
    ├── class-cloudflare-api.php        REST + GraphQL API wrapper (all Cloudflare calls)
    ├── class-accounts.php              Multi-account credential storage and switching
    ├── class-rule-builder.php          WAF expression builder + settings schema validation
    ├── class-dns.php                   DNS record types, field definitions, sanitization
    ├── class-zone-status.php           Analytics cache management + WP-Cron scheduler
    ├── class-settings.php              Plugin-wide settings (roles, toggles, admin bar config)
    ├── class-admin.php                 Admin menu registration, asset enqueuing, dashboard widget
    ├── class-ajax.php                  All 35+ wp_ajax_* handlers
    └── views/
        ├── page-main.php               WAF Rules Builder
        ├── page-dns.php                DNS Manager
        ├── page-zone-status.php        Zone Analytics
        ├── page-zone-controls.php      Zone Controls
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
| Zone Analytics | ✅ | ✅ |
| Zone Controls | ✅ | ✅ |
| IP Access Rules | ✅ | ✅ |
| Security Events | ✅ | ✅ |
| Email Routing | ✅ | ✅ |
| Multi-account | ✅ | ✅ |
| Dashboard widget | ✅ | ✅ |
| Plugin Settings | ✅ | ✅ |
| Automatic WP updates | ❌ | ✅ |
| Priority support | ❌ | ✅ |
| Upcoming premium features | ❌ | ✅ |

---

## Contributing

Pull requests are welcome. Please open an issue first for major changes.

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/my-feature`
3. Commit your changes: `git commit -m 'Add my feature'`
4. Push and open a Pull Request

---

## Credits

WAF rules based on the five-rule pattern by [Troy Glancy (WebAgencyHero)](https://webagencyhero.com), refined and maintained by [Michael Bourne (URSA6)](https://wafrules.com). Plugin development by [Nahnu Plugins](https://nahnuplugins.com).

---

## License

[GPL-2.0-or-later](https://www.gnu.org/licenses/gpl-2.0.html)
