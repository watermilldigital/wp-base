<?php
/**
 * Plugin Name: WaterMill Baseline
 * Description: Shared site policy: hardening, non-production safety, and trimming WordPress defaults.
 *
 * Every file in src/ loads. To skip one on a project, list it (without .php) in wp-config.php:
 *     define( 'WATERMILL_BASELINE_SKIP', array( 'disable-comments' ) );
 */

$watermill_baseline_skip = defined( 'WATERMILL_BASELINE_SKIP' ) ? WATERMILL_BASELINE_SKIP : array();

foreach ( glob( __DIR__ . '/src/*.php' ) as $watermill_baseline_file ) {
	if ( ! in_array( basename( $watermill_baseline_file, '.php' ), $watermill_baseline_skip, true ) ) {
		require_once $watermill_baseline_file;
	}
}

unset( $watermill_baseline_skip, $watermill_baseline_file );
