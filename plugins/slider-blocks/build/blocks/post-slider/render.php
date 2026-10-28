<?php
/**
 * Render the Blog Post block.
 *
 * The work (query, cache, markup, schema) lives in \GutSlider\Blocks\PostSlider, which
 * the "Load more" endpoint uses too.
 *
 * @param array $attributes The block attributes.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo \GutSlider\Blocks\PostSlider::render( $attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
