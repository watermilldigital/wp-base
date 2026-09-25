<?php
/**
 * Plugin Name: Millstone
 * Description: Shared site policy: hardening, non-production safety, and trimming WordPress defaults.
 *
 * Every file in src/ loads. To skip one on a project, list it (without .php) in wp-config.php:
 *     define( 'MILLSTONE_SKIP', array( 'disable-comments' ) );
 */

$millstone_skip = defined( 'MILLSTONE_SKIP' ) ? MILLSTONE_SKIP : array();

foreach ( glob( __DIR__ . '/src/*.php' ) as $millstone_file ) {
	if ( ! in_array( basename( $millstone_file, '.php' ), $millstone_skip, true ) ) {
		require_once $millstone_file;
	}
}

unset( $millstone_skip, $millstone_file );
