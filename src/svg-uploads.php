<?php
/**
 * Allows SVG uploads, but only for users with unfiltered_html (Administrators
 * on single site, Super Admins on multisite). An SVG can carry <script>, so it's
 * limited to users who can already publish raw scripts in post content: no new
 * risk, and nothing to sanitise. Everyone else keeps WordPress's default list.
 */

add_filter(
	'upload_mimes',
	fn( array $mimes ): array => current_user_can( 'unfiltered_html' ) ? $mimes + array( 'svg' => 'image/svg+xml' ) : $mimes
);
