<?php

if ( ! defined( 'ABSPATH' ) ) exit;

function pmui_core_upgrade_url( $campaign ) {

	return 'https://www.wpallimport.com/checkout/?edd_action=add_to_cart&download_id=5839966&edd_options%5Bprice_id%5D=1&discount=welcome-upgrade-99&utm_source=wpallimport-user-import-free&utm_medium=upgrade-notice&utm_campaign=' . $campaign;
}
