<?php
/**
 * WP-CLI commands, as `wp base <command>` (`wp help base` lists them).
 *
 * Each public method of WP_Base_CLI is one command, named by its @subcommand tag; its docblock is the command's help.
 * To add a command, add a method.
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/**
 * WaterMill site commands.
 */
class WP_Base_CLI {

	/**
	 * Purges the site's whole Varnish cache (Cloudways).
	 *
	 * Sends the same request as a content save or the admin bar's "Purge cache" button.
	 *
	 * ## EXAMPLES
	 *
	 *     wp @production base purge-varnish
	 *
	 * @subcommand purge-varnish
	 */
	public function purge_varnish(): void {
		if ( ! function_exists( 'wp_base_varnish_purge_request' ) ) {
			WP_CLI::error( 'varnish-purge is skipped on this site (WP_BASE_SKIP).' );
		}

		$response = wp_base_varnish_purge_request( true );

		if ( is_wp_error( $response ) ) {
			WP_CLI::error( 'Varnish purge failed: ' . $response->get_error_message() );
		}

		$code = (int) wp_remote_retrieve_response_code( $response );

		if ( $code < 200 || $code >= 300 ) {
			WP_CLI::error( "Varnish purge failed: HTTP {$code}. Is this a Cloudways server with Varnish on?" );
		}

		WP_CLI::success( 'Varnish cache purged.' );
	}
}

WP_CLI::add_command( 'base', 'WP_Base_CLI' );
