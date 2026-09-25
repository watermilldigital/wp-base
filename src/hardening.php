<?php
/**
 * Security hardening.
 *
 * Must-use so it loads regardless of which theme is active, with no
 * activation step required.
 */

/*
 * Removes every XML-RPC method. The `xmlrpc_enabled` filter only gates
 * authenticated calls; unauthenticated ones like pingback.ping (an
 * amplification vector) still run unless the method table itself is empty.
 */
add_filter( 'xmlrpc_methods', '__return_empty_array' );
remove_action( 'wp_head', 'rsd_link' ); // Only advertises the XML-RPC endpoint above.

/*
 * Application passwords allow password-based auth against the REST API.
 * Re-enable per project if an integration needs it.
 */
add_filter( 'wp_is_application_passwords_available', '__return_false' );

/*
 * Username enumeration: hide the REST users endpoints from logged-out
 * requests (the block editor is logged in, so it keeps working), stop
 * ?author=N resolving to an author slug, and drop the users sitemap,
 * which lists every author archive URL.
 */
add_filter(
	'rest_endpoints',
	function ( array $endpoints ): array {
		if ( ! is_user_logged_in() ) {
			foreach ( array_keys( $endpoints ) as $route ) {
				if ( str_starts_with( $route, '/wp/v2/users' ) ) {
					unset( $endpoints[ $route ] );
				}
			}
		}

		return $endpoints;
	}
);

add_action(
	'template_redirect',
	function (): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only check, nothing is processed.
		if ( isset( $_GET['author'] ) && ! is_admin() ) {
			wp_safe_redirect( home_url( '/' ), 301 );
			exit;
		}
	},
	1
);

add_filter(
	'wp_sitemaps_add_provider',
	fn( $provider, string $name ) => 'users' === $name ? false : $provider,
	10,
	2
);
