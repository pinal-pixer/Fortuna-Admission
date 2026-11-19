<?php

/**
 * Default values for the import options owned by the pro layer. The free base plugin
 * strips these from its own defaults, so they are merged back through the
 * pmui_default_import_options filter.
 */
function pmuip_default_import_options() {
	return array(
		'do_not_send_password_notification' => 1,
		'pmsci_customer' => array(
			'import_customers' => 0,
			'first_name' => '',
			'last_name' => '',
			'role' => 'customer',
			'nickname' => '',
			'description' => '',
			'login' => '',
			'pass' => '',
			'nicename' => '',
			'email' => '',
			'registered' => '',
			'display_name' => '',
			'url' => '',
			'billing_first_name' => '',
			'billing_last_name' => '',
			'billing_company' => '',
			'billing_address_1' => '',
			'billing_address_2' => '',
			'billing_city' => '',
			'billing_postcode' => '',
			'billing_country' => '',
			'billing_state' => '',
			'billing_email' => '',
			'billing_phone' => '',
			'shipping_source' => 'copy',
			'shipping_first_name' => '',
			'shipping_last_name' => '',
			'shipping_company' => '',
			'shipping_address_1' => '',
			'shipping_address_2' => '',
			'shipping_city' => '',
			'shipping_postcode' => '',
			'shipping_country' => '',
			'shipping_state' => '',
		),
		'pmsci_is_update_billing_fields' => 1,
		'pmsci_is_update_shipping_fields' => 1,
		'pmsci_update_billing_fields_logic' => 'full_update',
		'pmsci_billing_fields_list' => array(),
		'pmsci_billing_fields_only_list' => array(),
		'pmsci_billing_fields_except_list' => array(),
		'pmsci_update_shipping_fields_logic' => 'full_update',
		'pmsci_shipping_fields_list' => array(),
		'pmsci_shipping_fields_only_list' => array(),
		'pmsci_shipping_fields_except_list' => array()
	);
}
