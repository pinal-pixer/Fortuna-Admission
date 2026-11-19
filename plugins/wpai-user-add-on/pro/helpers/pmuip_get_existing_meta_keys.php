<?php

function pmuip_get_existing_meta_keys( $custom_type ) {
	global $wpdb;
	// Both the reimport section and the options tab need this list during the same Step 4
	// render; the grouped usermeta scan is expensive, so run it once per request.
	static $cache = array();
	if ( isset( $cache[ $custom_type ] ) ) {
		return $cache[ $custom_type ];
	}
	// Get All meta keys in the system
	$existing_meta_keys = array();
	$meta_keys = new PMXI_Model_List();
	$meta_keys->setTable($wpdb->usermeta);
	$meta_keys->setColumns('umeta_id', 'meta_key')->getBy(NULL, "umeta_id", NULL, NULL, "meta_key");

	if ( $custom_type == 'shop_customer' ) {

		$user_fields = array('first_name', 'last_name', 'nickname', 'description', PMXI_Plugin::getInstance()->getWPPrefix() . 'capabilities');
		$billing_fields = array('billing_first_name','billing_last_name','billing_company','billing_address_1','billing_address_2','billing_city','billing_postcode','billing_country','billing_state','billing_phone','billing_email');
		$shipping_fields = array('shipping_first_name','shipping_last_name','shipping_company','shipping_address_1','shipping_address_2','shipping_city','shipping_postcode','shipping_country','shipping_state');

		$hide_fields = array_merge($user_fields, $billing_fields, $shipping_fields);

	} else {

		$hide_fields = array('first_name', 'last_name', 'nickname', 'description', PMXI_Plugin::getInstance()->getWPPrefix() . 'capabilities');

	}

	if ( ! empty($meta_keys) and $meta_keys->count() ){
		foreach ($meta_keys as $meta_key) { if (in_array($meta_key['meta_key'], $hide_fields) or strpos($meta_key['meta_key'], '_wp') === 0) continue;
			$existing_meta_keys[] = $meta_key['meta_key'];
		}
	}

	$cache[ $custom_type ] = $existing_meta_keys;

	return $existing_meta_keys;
}
