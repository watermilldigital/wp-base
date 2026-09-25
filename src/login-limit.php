<?php
/**
 * Brute-force protection for wp-login.php.
 *
 * After 5 failed logins from one IP, that IP can't log in for 15 minutes
 * (further attempts restart the 15 minutes). Counted per IP, not per
 * username, so an attacker can't lock a real user out. XML-RPC and
 * application passwords are already off (hardening.php), so this is the
 * only password login route.
 *
 * Behind Cloudflare, the visitor's IP comes from CF-Connecting-IP, trusted
 * only when the request really came from a Cloudflare address. If the
 * resolved IP isn't public (e.g. a proxy hides it as 127.0.0.1), limiting
 * is skipped rather than locking everyone out together.
 *
 * Blocking known IPs outright is done at Cloudflare (WAF → Tools), not here.
 */

const MILLSTONE_LOGIN_MAX_FAILURES = 5;
const MILLSTONE_LOGIN_LOCKOUT      = 15 * MINUTE_IN_SECONDS;

/*
 * https://www.cloudflare.com/ips/ — changes rarely. An out-of-date list
 * only means those edges fall back to the skip-limiting case above.
 */
const MILLSTONE_CLOUDFLARE_RANGES = array(
	'173.245.48.0/20',
	'103.21.244.0/22',
	'103.22.200.0/22',
	'103.31.4.0/22',
	'141.101.64.0/18',
	'108.162.192.0/18',
	'190.93.240.0/20',
	'188.114.96.0/20',
	'197.234.240.0/22',
	'198.41.128.0/17',
	'162.158.0.0/15',
	'104.16.0.0/13',
	'104.24.0.0/14',
	'172.64.0.0/13',
	'131.0.72.0/22',
	'2400:cb00::/32',
	'2606:4700::/32',
	'2803:f800::/32',
	'2405:b500::/32',
	'2405:8100::/32',
	'2a06:98c0::/29',
	'2c0f:f248::/32',
);

/**
 * Whether an IP (v4 or v6) falls inside a CIDR range.
 *
 * @param string $ip   IP address.
 * @param string $cidr Range, e.g. 104.16.0.0/13.
 */
function millstone_ip_in_range( string $ip, string $cidr ): bool {
	[ $subnet, $bits ] = explode( '/', $cidr );
	$ip                = inet_pton( $ip );
	$subnet            = inet_pton( $subnet );
	$bits              = (int) $bits;

	if ( false === $ip || false === $subnet || strlen( $ip ) !== strlen( $subnet ) ) {
		return false;
	}

	$whole = intdiv( $bits, 8 );
	$mask  = ( 0xff << ( 8 - $bits % 8 ) ) & 0xff;

	return substr( $ip, 0, $whole ) === substr( $subnet, 0, $whole )
		&& ( 0 === $bits % 8 || ( ord( $ip[ $whole ] ) & $mask ) === ( ord( $subnet[ $whole ] ) & $mask ) );
}

/**
 * The visitor's public IP, or null if it can't be determined.
 */
function millstone_login_ip(): ?string {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

	if ( isset( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
		foreach ( MILLSTONE_CLOUDFLARE_RANGES as $range ) {
			if ( millstone_ip_in_range( $ip, $range ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ) );
				break;
			}
		}
	}

	$public = filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE );

	return false === $public ? null : $public;
}

/**
 * Transient key holding an IP's failure count.
 *
 * @param string $ip IP address.
 */
function millstone_login_key( string $ip ): string {
	return 'millstone_login_' . md5( $ip );
}

// Runs after core's password check (priority 20), so a locked IP is refused even with the right password.
add_filter(
	'authenticate',
	function ( $user ) {
		$ip = millstone_login_ip();

		if ( null !== $ip && (int) get_transient( millstone_login_key( $ip ) ) >= MILLSTONE_LOGIN_MAX_FAILURES ) {
			return new WP_Error( 'millstone_login_locked', __( '<strong>Error:</strong> Too many failed login attempts. Try again in 15 minutes.', 'millstone' ) );
		}

		return $user;
	},
	30
);

add_action(
	'wp_login_failed',
	function (): void {
		$ip = millstone_login_ip();

		if ( null !== $ip ) {
			$key = millstone_login_key( $ip );
			set_transient( $key, (int) get_transient( $key ) + 1, MILLSTONE_LOGIN_LOCKOUT );
		}
	}
);

add_action(
	'wp_login',
	function (): void {
		$ip = millstone_login_ip();

		if ( null !== $ip ) {
			delete_transient( millstone_login_key( $ip ) );
		}
	}
);
