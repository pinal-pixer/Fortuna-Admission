<?php

function pmuip_pmxi_confirm_data_to_import( $isWizard, $post ) {

	if ( ! pmuip_base_is_active() ) return;

	if ( in_array($post['custom_type'], array('import_users', 'shop_customer'), true) ) {

		include PMUI_PRO_ROOT_DIR . '/pro/views/confirm/_user_meta_update_lists.php';

	}

	if ( $post['custom_type'] == 'shop_customer' ) {

		include PMUI_PRO_ROOT_DIR . '/pro/views/confirm/_customer_update_lists.php';

	}

}
