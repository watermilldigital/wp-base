<?php
/**
 * Plugin Name: WP Base
 * Author: WaterMill Digital
 * Author URI: https://watermilldigital.com
 * Version: 3.3.0
 * Description: Shared site policy: hardening, non-production safety, and trimming WordPress defaults.
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * Every file in src/ loads. To skip one on a project, list it (without .php) in wp-config.php:
 *     define( 'WP_BASE_SKIP', array( 'disable-comments' ) );
 */

$wp_base_skip = defined( 'WP_BASE_SKIP' ) ? WP_BASE_SKIP : array();

foreach ( glob( __DIR__ . '/src/*.php' ) as $wp_base_file ) {
	if ( ! in_array( basename( $wp_base_file, '.php' ), $wp_base_skip, true ) ) {
		require_once $wp_base_file;
	}
}

unset( $wp_base_skip, $wp_base_file );
