<?php

/**
 * Contributes the parsed pmsci_customer_* arrays for WooCommerce Customer imports.
 * The free base plugin skips the shop_customer branch of its parse routine when the
 * pro layer is active, so the whole branch is provided here.
 *
 * @param array $parsed_data
 * @param PMXI_Import_Record $import
 * @param array $parsing_data - $import, $count, $xml, $logger, $chunk, $xpath_prefix
 *
 * @return array
 */
function pmuip_pmui_parsed_data( $parsed_data, $import, $parsing_data ) {

	if ( ! pmuip_base_is_active() ) return $parsed_data;

	if ( ! empty($import->options['pmui']['import_users']) or empty($import->options['pmsci_customer']['import_customers']) ) return $parsed_data;

	add_filter('user_has_cap', 'pmuip_filter_has_cap_unfiltered_html'); kses_init(); // do not perform special filtering for imported content

	$cxpath = $parsing_data['xpath_prefix'] . $import->xpath;

	$records    = array();
	$tmp_files  = array();

	$xml = $parsing_data['xml'];

	$parsing_data['chunk'] == 1 and $parsing_data['logger'] and call_user_func($parsing_data['logger'], __('Composing customers...', 'wp_all_import_user_add_on'));

	$customer_options = [
		'pass', 'nicename', 'email', 'display_name', 'url', 'first_name', 'last_name', 'description', 'nickname', 'role', 'billing_first_name', 'billing_last_name', 'billing_company', 'billing_address_1', 'billing_address_2',
		'billing_city', 'billing_postcode', 'billing_country', 'billing_state', 'billing_phone', 'billing_email', 'shipping_first_name', 'shipping_last_name', 'shipping_company', 'shipping_address_1', 'shipping_address_2', 'shipping_city',
		'shipping_postcode', 'shipping_country', 'shipping_state'
	];

	if ( ! empty($import->options['pmsci_customer']['login']) ) {
		$parsed_data['pmsci_customer_logins'] = XmlImportParser::factory($xml, $cxpath, $import->options['pmsci_customer']['login'], $file)->parse($records); $tmp_files[] = $file;
	} else {
		$parsing_data['count'] and $parsed_data['pmsci_customer_logins'] = array_fill(0, $parsing_data['count'], '');
	}

	foreach ( $customer_options as $customer_option ) {
		if ( ! empty($import->options['pmsci_customer'][$customer_option]) ) {
			$parsed_data['pmsci_customer_' . $customer_option] = XmlImportParser::factory($xml, $cxpath, $import->options['pmsci_customer'][$customer_option], $file)->parse($records); $tmp_files[] = $file;
		} else {
			$parsing_data['count'] and $parsed_data['pmsci_customer_' . $customer_option] = array_fill(0, $parsing_data['count'], '');
		}
	}

	if ( ! empty($import->options['pmsci_customer']['registered']) ) {
		$parsed_data['pmsci_customer_registered'] = XmlImportParser::factory($xml, $cxpath, $import->options['pmsci_customer']['registered'], $file)->parse($records); $tmp_files[] = $file;
		$warned = array(); // used to prevent the same notice displaying several times
		foreach ($parsed_data['pmsci_customer_registered'] as $i => $d) {
			if ($d == 'now') $d = current_time('mysql'); // Replace 'now' with the WordPress local time to account for timezone offsets (WordPress references its local time during publishing rather than the server’s time so it should use that)
			$time = strtotime($d);
			if (FALSE === $time) {
				in_array($d, $warned) or $parsing_data['logger'] and call_user_func($parsing_data['logger'], sprintf(__('<b>WARNING</b>: unrecognized date format `%s`, assigning current date', 'wp_all_import_user_add_on'), $warned[] = $d));
				$time = time();
			}
			$parsed_data['pmsci_customer_registered'][$i] = date('Y-m-d H:i:s', $time);
		}
	} else {
		$parsing_data['count'] and $parsed_data['pmsci_customer_registered'] = array_fill(0, $parsing_data['count'], '');
	}

	remove_filter('user_has_cap', 'pmuip_filter_has_cap_unfiltered_html'); kses_init(); // return any filtering rules back if they has been disabled for import procedure

	foreach ($tmp_files as $file) { // remove all temporary files created
		@unlink($file);
	}

	return $parsed_data;
}
