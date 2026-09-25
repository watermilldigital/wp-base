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
				<style>
					#wp_base_watermill .inside { margin: 0; padding: 16px 12px; }
					.wp-base-watermill img { display: block; margin: 0 0 12px; }
					.wp-base-watermill p { margin: 0; }
					.wp-base-watermill ul { display: grid; gap: 8px; margin: 16px 0 0; padding: 16px 0 0; border-top: 1px solid #f0f0f1; }
					.wp-base-watermill li { display: flex; align-items: center; gap: 8px; margin: 0; }
					.wp-base-watermill svg { flex: none; width: 20px; height: 20px; color: #646970; }
				</style>
				<div class="wp-base-watermill">
					<img src="<?php echo esc_url( plugins_url( 'assets/watermill-logo.svg', dirname( __DIR__ ) . '/wp-base.php' ) ); ?>" alt="WaterMill Digital" width="176" height="24">
					<p>Need a change, a fix or some advice? Get in touch. We built this site, so we know it inside out.</p>
					<ul>
						<li>
							<?php // Heroicons (MIT): envelope, outline. ?>
							<svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" /></svg>
							<a href="mailto:ben@watermilldigital.com">ben@watermilldigital.com</a>
						</li>
						<li>
							<?php // Heroicons (MIT): globe-alt, outline. ?>
							<svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418" /></svg>
							<a href="https://watermilldigital.com" target="_blank" rel="noopener">watermilldigital.com</a>
						</li>
					</ul>
				</div>
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
