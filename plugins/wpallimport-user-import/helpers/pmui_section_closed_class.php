<?php

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! function_exists( 'pmui_section_closed_class' ) ) {
	/**
	 * Returns the `closed` class unless the section holds a field the user bound
	 * to their file, so a mapped section renders expanded. Falls back to closed
	 * when the core helper is missing.
	 *
	 * Every pmui[] field this covers defaults to '', so a literal the user typed
	 * counts as a mapping.
	 *
	 * @param array $post   Import options.
	 * @param array $fields pmui[] keys the section renders.
	 */
	function pmui_section_closed_class( $post, $fields ) {

		if ( ! function_exists( 'wp_all_import_has_mapped_value' ) ) {
			return 'closed';
		}

		$values = isset( $post['pmui'] ) && is_array( $post['pmui'] ) ? $post['pmui'] : array();

		foreach ( $fields as $field ) {
			if ( isset( $values[ $field ] ) && wp_all_import_has_mapped_value( $values[ $field ] ) ) {
				return '';
			}
		}

		return 'closed';
	}
}
