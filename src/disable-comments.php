<?php
/**
 * Turns comments and pingbacks off site-wide: closes them everywhere
 * (front end, wp-comments-post.php and the REST API all respect this),
 * hides existing ones, and removes them from the editor and wp-admin.
 *
 * Skip this on a project that needs comments (including WooCommerce
 * product reviews, which are comments) — see millstone.php.
 */

add_filter( 'comments_open', '__return_false' );
add_filter( 'pings_open', '__return_false' );
add_filter( 'comments_array', '__return_empty_array' );
add_filter( 'feed_links_show_comments_feed', '__return_false' );

add_action(
	'init',
	function (): void {
		foreach ( get_post_types() as $post_type ) {
			remove_post_type_support( $post_type, 'comments' );
			remove_post_type_support( $post_type, 'trackbacks' );
		}
	},
	100
);

add_action(
	'admin_menu',
	function (): void {
		remove_menu_page( 'edit-comments.php' );
		remove_submenu_page( 'options-general.php', 'options-discussion.php' );
	}
);

add_action(
	'admin_bar_menu',
	fn( WP_Admin_Bar $bar ) => $bar->remove_node( 'comments' ),
	100
);

// Dashboard: zero the "At a Glance" count and empty the Activity widget's recent comments.
add_filter(
	'wp_count_comments',
	fn() => (object) array_fill_keys( array( 'approved', 'moderated', 'spam', 'trash', 'post-trashed', 'total_comments', 'all' ), 0 )
);
add_filter(
	'comments_pre_query',
	fn( $comments ) => is_admin() && 'dashboard' === get_current_screen()?->id ? array() : $comments
);
