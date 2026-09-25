<?php
/**
 * Removes WordPress's emoji detection script and styles. Every modern
 * browser renders emoji natively, so they're dead weight on each page.
 *
 * The print_emoji_styles removals are legacy hooks that wp_enqueue_emoji_styles()
 * normally unhooks, so they'd start running once that's removed.
 */

remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_enqueue_scripts', 'wp_enqueue_emoji_styles' );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'embed_head', 'print_emoji_detection_script' );
remove_action( 'enqueue_embed_scripts', 'wp_enqueue_emoji_styles' );
remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );

// Admin hooks are added after mu-plugins load (wp-admin/includes/admin-filters.php).
add_action(
	'admin_init',
	function (): void {
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'admin_enqueue_scripts', 'wp_enqueue_emoji_styles' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
	}
);
