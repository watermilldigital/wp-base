<?php
/**
 * Replaces WordPress branding with WaterMill's: the login logo, "— WordPress"
 * in page titles, the admin bar W menu, the admin footer, the dashboard's
 * WordPress panels, and the "WordPress" sender name on system emails.
 */

/*
 * Login screen: WaterMill logo (inlined, so there's no asset URL to resolve
 * from a mu-plugin subdirectory), linking to watermilldigital.com.
 */
add_action(
	'login_enqueue_scripts',
	function (): void {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local file.
		$logo = rawurlencode( (string) file_get_contents( dirname( __DIR__ ) . '/assets/watermill-logo.svg' ) );
		wp_add_inline_style( 'login', ".login h1 a { background: url(\"data:image/svg+xml,$logo\") center / contain no-repeat; width: 220px; height: 30px; }" );
	}
);
add_filter( 'login_headerurl', fn() => 'https://watermilldigital.com' );
add_filter( 'login_headertext', fn() => 'WaterMill Digital' );

// "Log In ‹ Site — WordPress" and "Dashboard ‹ Site — WordPress" lose the suffix.
$wp_base_drop_wordpress = fn( string $title ): string => str_replace( ' &#8212; WordPress', '', $title );
add_filter( 'login_title', $wp_base_drop_wordpress );
add_filter( 'admin_title', $wp_base_drop_wordpress );
unset( $wp_base_drop_wordpress );

// The admin bar's W menu: About WordPress and WordPress.org links.
add_action( 'admin_bar_menu', fn( WP_Admin_Bar $bar ) => $bar->remove_node( 'wp-logo' ), 999 );

add_filter( 'admin_footer_text', fn() => 'Built by <a href="https://watermilldigital.com">WaterMill Digital</a>' );

// Dashboard: the Welcome panel and WordPress Events and News. The panel's hook is
// added after mu-plugins load (wp-admin/includes/admin-filters.php), hence admin_init.
add_action(
	'admin_init',
	function (): void {
		remove_action( 'welcome_panel', 'wp_welcome_panel' );
	}
);
add_action( 'wp_dashboard_setup', fn() => remove_meta_box( 'dashboard_primary', 'dashboard', 'side' ) );

// System emails from "WordPress" come from the site name instead. A name set by another plugin is left alone.
add_filter( 'wp_mail_from_name', fn( string $name ): string => 'WordPress' === $name ? wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) : $name );
