<?php

// WP All Import Pro releases that search meta keys over AJAX define this helper; older ones
// still need the full key list printed into the page.
function pmuip_meta_keys_lookup( $custom_type ) {
	return function_exists('wp_all_import_search_meta_keys') ? (string) $custom_type : '';
}
