<?php
/**
 * Limits the block inserter to basic core blocks (text, images, buttons, embeds) plus
 * every non-core block, so the theme's own blocks and plugin blocks are always allowed.
 * Layout belongs in the theme's custom blocks, not in editors' columns and groups.
 *
 * Parent blocks need their children listed too (list → list-item, buttons → button).
 * To allow more core blocks on a project, add them from the theme:
 *     add_filter( 'wp_base_core_blocks', fn( array $blocks ): array => array( ...$blocks, 'core/gallery' ) );
 */

add_filter(
	'allowed_block_types_all',
	function (): array {
		$core_blocks = apply_filters(
			'wp_base_core_blocks',
			array(
				'core/paragraph',
				'core/heading',
				'core/list',
				'core/list-item',
				'core/quote',
				'core/image',
				'core/buttons',
				'core/button',
				'core/separator',
				'core/table',
				'core/embed',
			)
		);

		$other_blocks = array_filter(
			array_keys( WP_Block_Type_Registry::get_instance()->get_all_registered() ),
			fn( string $name ): bool => ! str_starts_with( $name, 'core/' )
		);

		return array_merge( $core_blocks, array_values( $other_blocks ) );
	}
);
