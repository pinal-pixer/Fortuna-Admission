<?php

/**
 * Returns the `closed` class for a customer section, omitting it when at least one
 * of the listed pmsci_customer fields holds a value bound to the import file so the
 * section renders expanded. Falls back to `closed` when the core helpers are missing.
 *
 * @param array $post         Import options.
 * @param array $fields       Fields whose default is empty, so any value the user entered counts.
 * @param array $token_fields Fields with a non-empty default, so only a template token counts.
 */
function pmuip_section_closed_class( $post, $fields, $token_fields = array() ) {

	if ( ! function_exists('wp_all_import_has_mapped_value') || ! function_exists('wp_all_import_has_template_token') ) return 'closed';

	$customer = isset($post['pmsci_customer']) && is_array($post['pmsci_customer']) ? $post['pmsci_customer'] : array();

	foreach ($fields as $field) {
		if ( isset($customer[$field]) && wp_all_import_has_mapped_value($customer[$field]) ) return '';
	}

	foreach ($token_fields as $field) {
		if ( isset($customer[$field]) && wp_all_import_has_template_token($customer[$field]) ) return '';
	}

	return 'closed';
}
