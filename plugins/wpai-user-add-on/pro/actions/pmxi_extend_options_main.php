<?php

/**
 * Renders the WooCommerce Customer sections on Step 3. The free base plugin renders
 * nothing for shop_customer imports besides its (suppressed) upgrade notices.
 */
function pmuip_pmxi_extend_options_main( $post_type, $post ) {

	if ( ! pmuip_base_is_active() ) return;

	if ( $post_type == 'shop_customer' ) {

		include PMUI_PRO_ROOT_DIR . '/pro/views/shop_customer/index.php';

	}

}
