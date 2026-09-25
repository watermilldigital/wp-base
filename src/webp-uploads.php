<?php
/**
 * Convert uploaded JPEGs to WebP.
 *
 * The full-size image and every generated size are WebP; the original JPEG
 * stays on disk (as the attachment's `original_image`). If the server's image
 * library can't write WebP, WordPress ignores this and keeps JPEG.
 */

add_filter(
	'image_editor_output_format',
	fn( array $formats ): array => array( 'image/jpeg' => 'image/webp' ) + $formats
);
