<?php
/**
 * Cloudways Varnish purge.
 *
 * Clears the whole site's Varnish cache when published content, terms, menus
 * or the Customizer change, and adds a "Purge cache" admin bar button for
 * anything else (e.g. plugin settings). Uses the same request Cloudways' own
 * Breeze plugin sends: PURGE /.* to the local Varnish with the site's Host.
 *
 * Only active when the request came through Varnish, so it does nothing on
 * other hosts, locally, or from WP-CLI (the deploy workflow purges itself).
 */

if ( empty( $_SERVER['HTTP_X_VARNISH'] ) ) {
	return;
}

/**
 * Queue a purge for the end of the request, so a save that fires several
 * hooks sends one request.
 */
function wp_base_queue_varnish_purge(): void {
	if ( ! has_action( 'shutdown', 'wp_base_purge_varnish' ) ) {
		add_action( 'shutdown', 'wp_base_purge_varnish' );
	}
}

/**
 * Purge the site's whole Varnish cache. Non-blocking: never slows down a save.
 */
function wp_base_purge_varnish(): void {
	$home = wp_parse_url( home_url() );

	wp_remote_request(
		( $home['scheme'] ?? 'http' ) . '://127.0.0.1/.*',
		array(
			'method'    => 'PURGE',
			'headers'   => array( 'Host' => $home['host'] ),
			'sslverify' => false, // Connecting to 127.0.0.1, so the certificate never matches.
			'blocking'  => false,
			'timeout'   => 1,
		)
	);
}

// ponytail: purges the whole site on any change; per-URL purges (Cloudways' URLPURGE) if cache hit rate matters.
add_action(
	'transition_post_status',
	function ( string $new_status, string $old_status ): void {
		if ( 'publish' === $new_status || 'publish' === $old_status ) {
			wp_base_queue_varnish_purge();
		}
	},
	10,
	2
);

foreach ( array( 'edited_term', 'delete_term', 'wp_update_nav_menu', 'customize_save_after', 'switch_theme' ) as $wp_base_hook ) {
	add_action( $wp_base_hook, 'wp_base_queue_varnish_purge' );
}
unset( $wp_base_hook );

add_action(
	'admin_bar_menu',
	function ( WP_Admin_Bar $bar ): void {
		if ( current_user_can( 'edit_others_posts' ) ) {
			$bar->add_node(
				array(
					'id'    => 'wp-base-purge-varnish',
					'title' => __( 'Purge cache', 'wp-base' ),
					'href'  => wp_nonce_url( admin_url( 'admin-post.php?action=wp_base_purge_varnish' ), 'wp_base_purge_varnish' ),
				)
			);
		}
	},
	100
);

add_action(
	'admin_post_wp_base_purge_varnish',
	function (): void {
		if ( ! current_user_can( 'edit_others_posts' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to purge the cache.', 'wp-base' ), 403 );
		}

		check_admin_referer( 'wp_base_purge_varnish' );
		wp_base_purge_varnish();
		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url() );
		exit;
	}
);
