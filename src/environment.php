<?php
/**
 * Non-production safety.
 *
 * Anywhere WP_ENVIRONMENT_TYPE isn't `production` (set from WP_ENV in
 * wp-config.php): keep the site out of search engines and never send email,
 * so a staging copy of real data can't index or contact real customers.
 */

if ( 'production' !== wp_get_environment_type() ) {
	// Adds noindex to every page (robots meta tag), disables sitemaps, and shows the "discouraged" notice in wp-admin.
	add_filter( 'pre_option_blog_public', '__return_zero' );

	add_filter(
		'pre_wp_mail',
		function ( $short_circuit, array $atts ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- intentional: shows up in debug.log so dropped mail isn't a mystery.
			error_log( sprintf( 'Mail blocked (%s environment): "%s" to %s', wp_get_environment_type(), $atts['subject'], implode( ', ', (array) $atts['to'] ) ) );

			return false;
		},
		10,
		2
	);
}
