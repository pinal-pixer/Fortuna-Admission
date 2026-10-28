<?php
/**
 * Marquee: the page output (see \GutSlider\Blocks\Marquee).
 *
 * @var array  $attributes Block attributes.
 * @var string $content    Rendered items.
 *
 * @package GutSlider
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo \GutSlider\Blocks\Marquee::render( $attributes, $content, $block ?? null ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
