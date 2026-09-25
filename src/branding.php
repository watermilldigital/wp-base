<?php
/**
 * Replaces WordPress branding with WaterMill's: the login logo, "— WordPress"
 * in page titles, the admin bar W menu and "Howdy", the admin footer, the dashboard's
 * WordPress panels (with a WaterMill contact widget in their place), and the
 * "WordPress" sender name on system emails.
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

// The admin bar's W menu (About WordPress, WordPress.org links), and "Howdy, " before the
// user's name. 9992: just after WordPress adds the account menu (wp_admin_bar_my_account_item, 9991).
add_action(
	'admin_bar_menu',
	function ( WP_Admin_Bar $bar ): void {
		$bar->remove_node( 'wp-logo' );

		$account = $bar->get_node( 'my-account' );
		if ( $account ) {
			// The dropdown's screen-reader label is a separate copy in meta. Passing meta replaces all of it, so merge.
			$bar->add_node(
				array(
					'id'    => 'my-account',
					'title' => str_replace( 'Howdy, ', '', $account->title ),
					'meta'  => array( 'menu_title' => str_replace( 'Howdy, ', '', $account->meta['menu_title'] ?? '' ) ) + $account->meta,
				)
			);
		}
	},
	9992
);

add_filter( 'admin_footer_text', fn() => 'Built by <a href="https://watermilldigital.com">WaterMill Digital</a>' );

// Dashboard: the Welcome panel and WordPress Events and News. The panel's hook is
// added after mu-plugins load (wp-admin/includes/admin-filters.php), hence admin_init.
add_action(
	'admin_init',
	function (): void {
		remove_action( 'welcome_panel', 'wp_welcome_panel' );
	}
);

/*
 * Dashboard: a WaterMill contact widget, first in the main column (users can still
 * drag it elsewhere; WordPress remembers their order).
 */
add_action(
	'wp_dashboard_setup',
	function (): void {
		global $wp_meta_boxes;

		remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );

		wp_add_dashboard_widget(
			'wp_base_watermill',
			'WaterMill Digital',
			function (): void {
				?>
				<p><img src="<?php echo esc_url( plugins_url( 'assets/watermill-logo.svg', dirname( __DIR__ ) . '/wp-base.php' ) ); ?>" alt="WaterMill Digital" width="176" height="24"></p>
				<p>Need a change, a fix or some advice? Get in touch. We built this site, so we know it inside out.</p>
				<p>
					<span class="dashicons dashicons-email" aria-hidden="true"></span>
					<a href="mailto:ben@watermilldigital.com">ben@watermilldigital.com</a><br>
					<span class="dashicons dashicons-admin-site-alt3" aria-hidden="true"></span>
					<a href="https://watermilldigital.com" target="_blank" rel="noopener">watermilldigital.com</a>
				</p>
				<?php
			}
		);

		$core = $wp_meta_boxes['dashboard']['normal']['core'];
		$wp_meta_boxes['dashboard']['normal']['core'] = array( 'wp_base_watermill' => $core['wp_base_watermill'] ) + $core; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- reordering is the point.
	}
);

// A user with a saved dashboard layout would get the widget at the bottom of a column. Until
// their layout includes it (i.e. they've moved it themselves), put it first in the main column.
add_filter(
	'get_user_option_meta-box-order_dashboard',
	function ( $order ) {
		if ( is_array( $order ) && ! str_contains( implode( ',', $order ), 'wp_base_watermill' ) ) {
			$order['normal'] = ltrim( 'wp_base_watermill,' . ( $order['normal'] ?? '' ), ',' );
		}

		return $order;
	}
);

// System emails from "WordPress" come from the site name instead. A name set by another plugin is left alone.
add_filter( 'wp_mail_from_name', fn( string $name ): string => 'WordPress' === $name ? wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) : $name );
