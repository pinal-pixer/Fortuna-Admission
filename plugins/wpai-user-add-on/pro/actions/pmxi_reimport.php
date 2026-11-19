<?php

/**
 * Renders the update lists the free base plugin strips from its "Choose which data to
 * update" section: the user meta list and, for customer imports, the billing and
 * shipping field lists. Runs at priority 9 so they render where the section fires
 * pmxi_reimport, ahead of other add-ons hooked there.
 */
function pmuip_pmxi_reimport( $post_type, $post ) {

	if ( ! pmuip_base_is_active() ) return;

	if ( $post_type != 'import_users' and $post_type != 'shop_customer' ) return;

	$meta_keys_lookup = pmuip_meta_keys_lookup( $post_type );

	$existing_meta_keys = $meta_keys_lookup ? array() : pmuip_get_existing_meta_keys( $post_type );

	include PMUI_PRO_ROOT_DIR . '/pro/views/options/_meta_update_list.php';

	if ( $post_type == 'shop_customer' ) {

		include PMUI_PRO_ROOT_DIR . '/pro/views/options/_customer_update_lists.php';

	}

}
