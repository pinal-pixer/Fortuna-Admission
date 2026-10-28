<?php
/**
 * Backward-compatibility wrappers for global functions.
 *
 * These functions are used by block render templates in src/ and must
 * remain available in the global namespace.
 *
 * @package GutSlider
 * @since   3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'gutslider_display_icon' ) ) {
	/**
	 * Retrieve an SVG icon by key.
	 *
	 * @since 1.0.0
	 *
	 * @param string $icon Icon identifier key.
	 * @return string SVG markup or empty string.
	 */
	function gutslider_display_icon( string $icon = '' ): string {
		return \GutSlider\Icons\Icons::get( $icon );
	}
}

if ( ! function_exists( 'gutslider_allowed_svg_tags' ) ) {
	/**
	 * Get allowed SVG tags for wp_kses sanitization.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array<string, bool>> Allowed SVG tags and attributes.
	 */
	function gutslider_allowed_svg_tags(): array {
		return \GutSlider\Icons\AllowedSvg::tags();
	}
}
