<?php
declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Published IP lists for services allowed by Rule 1 (Allow Good Bots).
 *
 * Source: the wafrules.com ruleset of October 21, 2026. Each list comes from the
 * service's own published page (see 'sources'). Services Cloudflare already
 * verifies as bots are covered by cf.client.bot; their lists are optional.
 */
final class WPWAF_Service_IPs {

	public const RULES_DATE = '2026-10-21';

	/** @return array<int,array<string,mixed>> */
	public static function all(): array {
		return [
			[
				'service'      => 'betterStack',
				'label'        => 'Better Stack',
				'settings'     => [ 'allow_betterstack' ],
				'verified_bot' => true,
				'updated'      => '2026-10-21',
				'sources'      => [ 'https://uptime.betterstack.com/ips.txt' ],
				'notes'        => 'Optional. Cloudflare already verifies this bot. Drop these IPs first when the expression approaches 4,096 characters.',
				'ips'          => [
					'5.161.90.230', '5.161.118.194', '5.223.56.56', '5.223.60.188',
					'5.223.73.226', '5.223.75.121', '45.79.236.121', '91.98.38.26',
					'91.99.189.210', '162.55.165.86', '168.119.246.194', '172.105.162.156',
					'178.156.154.250', '178.156.210.11', '178.156.217.188', '192.53.169.77',
					'194.195.248.157', '2400:8907::2000:2eff:fed6:8631', '2400:8907::2000:6fff:fe13:8fcc', '2400:8907::2000:d5ff:fecf:a8d3',
					'2400:8907::2000:f7ff:fe54:8db9', '2a01:4f8:c012:856d::1', '2a01:4f8:c013:3a76::1', '2a01:4f8:c014:24d5::1',
					'2a01:4f8:c014:2649::1', '2a01:4ff:f0:4f11::1', '2a01:4ff:f0:565a::1', '2a01:4ff:f0:5a81::1',
					'2a01:4ff:f0:abcd::1', '2a01:4ff:f0:eec4::1', '2a01:4ff:2f0:1c39::1', '2a01:4ff:2f0:1f88::1',
					'2a01:4ff:2f0:258a::1', '2a01:4ff:2f0:3b17::1',
				],
			],
			[
				'service'      => 'ewww',
				'label'        => 'EWWW Image Optimizer / Easy IO',
				'settings'     => [ 'allow_ewww', 'allow_easyio_cdn' ],
				'verified_bot' => false,
				'updated'      => '2026-10-21',
				'sources'      => [ 'https://optimize.exactlywww.com/exactdn/servers.php?format=json' ],
				'notes'        => 'Official Easy IO / ExactDN origin-fetch list. The same addresses back the EWWW and Easy IO CDN toggles. EWWW says the list changes as servers are added.',
				'ips'          => [
					'46.101.220.208', '64.225.61.176', '68.183.55.40', '104.236.68.181',
					'104.236.68.218', '104.236.74.18', '128.199.209.150', '157.245.195.245',
					'157.245.203.234', '161.35.138.231', '167.172.99.106', '209.38.214.70',
					'209.38.238.37', '2400:6180:0:d0::21a:f001', '2400:6180:0:d0::1467:c001', '2400:6180:0:d0::14ff:5001',
					'2604:a880:800:10::830:9001', '2604:a880:800:10::c1b:5001', '2604:a880:800:10::cbb:1', '2604:a880:800:10::cda:6001',
					'2604:a880:800:10::d2d:9001', '2604:a880:800:10::df6:f001', '2a03:b0c0:3:d0::175a:5001', '2a03:b0c0:3:d0::1bc4:4001',
					'2a03:b0c0:3:d0::1bf4:b001', '2a03:b0c0:3:d0::1c64:a001',
				],
			],
			[
				'service'      => 'gosweetspot',
				'label'        => 'GoSweetSpot',
				'settings'     => [ 'allow_gosweetspot' ],
				'verified_bot' => false,
				'updated'      => '2026-10-21',
				'sources'      => [ 'https://support.gosweetspot.com/hc/en-us/articles/5897637584911-IP-Addresses-for-Whitelisting' ],
				'notes'        => 'Integration-service IPs that send webhooks and fetch orders. Print-agent and deprecated addresses are left out.',
				'ips'          => [
					'202.12.108.52', '202.180.67.228', '202.180.68.52',
				],
			],
			[
				'service'      => 'imagify',
				'label'        => 'Imagify',
				'settings'     => [ 'allow_imagify' ],
				'verified_bot' => false,
				'updated'      => '2026-10-21',
				'sources'      => [ 'https://imagify.io/documentation/imagify-ip-addresses-api-key-isnt-valid/' ],
				'notes'        => 'Official Imagify firewall allowlist for optimization traffic between the site and Imagify.',
				'ips'          => [
					'46.30.211.203', '46.30.212.204', '46.30.212.205', '46.30.212.206',
					'46.30.212.207',
				],
			],
			[
				'service'      => 'manageWP',
				'label'        => 'ManageWP',
				'settings'     => [ 'allow_managewp' ],
				'verified_bot' => true,
				'updated'      => '2026-10-21',
				'sources'      => [ 'https://managewp.com/troubleshooting/what-are-managewp-ips-so-i-can-allow-them-in-my-firewall-settings/' ],
				'notes'        => 'Verified for most traffic. IPs stay in the Skip rule because other rules challenge AWS. The knowledge-base page links to the current .txt list.',
				'ips'          => [
					'3.218.31.131', '34.210.35.214', '34.210.224.7', '34.211.178.241',
					'34.211.180.66', '34.213.77.188', '34.214.48.135', '34.214.212.42',
					'34.218.121.176', '34.223.186.249', '35.162.140.124', '35.162.254.253',
					'44.224.135.238', '44.224.174.169', '44.224.250.144', '44.225.118.211',
					'44.225.177.160', '44.225.203.104', '44.226.97.20', '44.226.100.122',
					'44.226.111.14', '44.231.184.112', '44.235.15.76', '44.238.10.27',
					'44.238.58.95', '44.238.67.135', '44.238.241.95', '52.10.190.191',
					'52.10.225.96', '52.11.12.231', '52.11.29.70', '52.11.54.161',
					'52.11.187.168', '52.13.23.154', '52.24.142.159', '52.24.187.29',
					'52.24.232.158', '52.25.116.116', '52.25.139.76', '52.25.191.255',
					'52.26.122.21', '52.26.187.210', '52.27.171.126', '52.32.57.81',
					'52.34.126.117', '52.34.254.47', '52.35.72.129', '52.35.82.99',
					'52.36.28.80', '52.37.217.170', '52.39.177.152', '52.41.5.108',
					'52.41.237.12', '52.42.189.119', '52.43.13.71', '52.43.76.224',
					'52.43.127.200', '52.88.96.110', '52.88.119.122', '52.88.197.180',
					'52.88.215.225', '52.89.85.107', '52.89.94.121', '52.89.155.51',
					'54.70.65.107', '54.70.201.228', '54.71.54.102', '54.149.16.35',
					'54.162.61.185', '54.184.226.94', '54.184.234.227', '54.185.116.30',
					'54.186.128.167', '54.186.143.184', '54.186.244.128', '54.187.92.57',
					'54.189.93.69', '54.191.32.65', '54.191.40.136', '54.191.67.23',
					'54.191.80.119', '54.191.108.9', '54.191.135.209', '54.191.136.176',
					'54.191.137.17', '54.191.148.85', '54.191.149.8', '54.203.109.179',
					'54.214.47.164', '54.244.242.144',
				],
			],
			[
				'service'      => 'patchstack',
				'label'        => 'Patchstack',
				'settings'     => [ 'allow_patchstack' ],
				'verified_bot' => false,
				'updated'      => '2026-10-21',
				'sources'      => [ 'https://patchstack.com/ips-v4/', 'https://patchstack.com/ips-v6/' ],
				'notes'        => 'Patchstack says these addresses may change without notice. AWS us-east-2.',
				'ips'          => [
					'3.18.75.127', '3.128.109.187', '16.58.234.134', '18.118.231.120',
					'18.119.219.26', '18.219.14.208', '18.219.138.189', '18.220.35.243',
					'18.225.164.199', '52.15.237.250', '2600:1f16:141e:cf00::/56',
				],
			],
			[
				'service'      => 'semrushBot',
				'label'        => 'SEMrush',
				'settings'     => [ 'allow_semrush' ],
				'verified_bot' => true,
				'updated'      => '2026-10-21',
				'sources'      => [ 'https://www.semrush.com/kb/681-site-audit-troubleshooting' ],
				'notes'        => 'Optional Site Audit CIDR from Semrush KB 681. Cloudflare verifies most Semrush traffic.',
				'ips'          => [
					'85.208.98.128/25',
				],
			],
			[
				'service'      => 'shortPixel',
				'label'        => 'ShortPixel',
				'settings'     => [ 'allow_shortpixel' ],
				'verified_bot' => false,
				'updated'      => '2026-10-21',
				'sources'      => [ 'https://shortpixel.com/knowledge-base/article/ips-to-be-whitelisted/', 'https://api.shortpixel.com/shortpixel-whitelist-ips.php' ],
				'notes'        => 'Hetzner AS24940, which the web-host rule challenges. The user agent always contains ShortPixel, but the Skip rule does not match on user agent. IPv4 only; do not guess unlisted IPv6. The knowledge-base page is the source of truth and the JSON URL is a cross-check.',
				'ips'          => [
					'5.78.136.110', '5.161.47.113', '5.161.48.189', '23.88.6.90',
					'46.224.144.2', '136.243.103.55', '168.119.110.23', '176.9.21.54',
					'176.9.21.94', '176.9.77.187', '188.40.16.97',
				],
			],
			[
				'service'      => 'wpUmbrella',
				'label'        => 'WP Umbrella',
				'settings'     => [ 'allow_wpumbrella' ],
				'verified_bot' => false,
				'updated'      => '2026-10-21',
				'sources'      => [ 'https://wp-umbrella.com/ips-v4/', 'https://wp-umbrella.com/ips-v6/', 'https://wp-umbrella.com/ips-monitoring/', 'https://wp-umbrella.com/ips-broken-link-checker/', 'https://wp-umbrella.com/ips-lighthouse/' ],
				'notes'        => 'Management v4/v6, monitoring, broken-link checker, and Lighthouse. 2001:BC8:2B7F:801::292/64 is normalized to 2001:bc8:2b7f:801::/64. Listed in Cloudflare Radar, but real traffic does not verify. Unlisted DigitalOcean uptime monitors stay on the interim AS14061 clause and are not added here.',
				'ips'          => [
					'15.235.231.33', '51.159.196.255', '51.159.202.198', '51.178.24.243',
					'62.4.26.31', '64.23.156.169', '128.199.86.31', '128.199.121.7',
					'134.199.170.244', '142.93.161.1', '142.93.205.116', '143.244.149.185',
					'157.245.77.230', '159.223.167.118', '161.35.231.200', '165.227.83.88',
					'165.227.147.208', '167.99.218.133', '167.99.218.170', '188.166.163.58',
					'188.166.165.9', '206.189.101.66', '209.38.89.16', '212.83.142.5',
					'212.83.175.107', '212.129.45.77', '2001:bc8:2b7f:801::/64', '2001:41d0:306:1702::/64',
				],
			],
		];
	}

	/** The entry whose list a Rule 1 setting key adds, or null. */
	public static function for_setting( string $key ): ?array {
		foreach ( self::all() as $svc ) {
			if ( in_array( $key, $svc['settings'], true ) ) return $svc;
		}
		return null;
	}
}
