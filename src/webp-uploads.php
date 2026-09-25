<?php
/**
 * Convert uploaded JPEGs to WebP.
 *
 * The full-size image and every generated size are WebP; the original JPEG
 * stays on disk (as the attachment's `original_image`). If the server's image
 * library can't write WebP, WordPress ignores this and keeps JPEG.
 *
 * This is also the upload compression: every size, full size included, is
 * re-encoded at WordPress's default quality with metadata stripped, so no
 * optimisation plugin is needed.
 *
 * PNGs stay PNG. They're mostly logos, screenshots and diagrams, which
 * WordPress's lossy WebP makes 2-6x larger and blurs text in.
 */

add_filter(
	'image_editor_output_format',
	fn( array $formats ): array => array( 'image/jpeg' => 'image/webp' ) + $formats
);
