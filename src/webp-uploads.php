<?php
/**
 * Convert uploaded JPEGs and PNGs to WebP.
 *
 * The full-size image and every generated size are WebP (WebP keeps PNG
 * transparency); the original upload stays on disk (as the attachment's
 * `original_image`). If the server's image library can't write WebP,
 * WordPress ignores this and keeps the original format.
 *
 * Compression is WordPress's own: every size is re-encoded at the editor's
 * default quality and metadata is stripped, so no optimisation plugin is needed.
 */

add_filter(
	'image_editor_output_format',
	fn( array $formats ): array => array(
		'image/jpeg' => 'image/webp',
		'image/png'  => 'image/webp',
	) + $formats
);
