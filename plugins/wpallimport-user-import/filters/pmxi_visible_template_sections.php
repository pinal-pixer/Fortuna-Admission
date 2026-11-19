<?php

if ( ! defined( 'ABSPATH' ) ) exit;
function pmui_pmxi_visible_template_sections( $sections, $post_type ){

	if ( 'import_users' == $post_type or 'shop_customer' == $post_type ) return array('main');

	return $sections;

}