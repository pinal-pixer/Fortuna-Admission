<?php

/**
 * Keeps the core Custom Fields section (user meta) in the list for user imports whatever the
 * free base returned. Runs at priority 20, after the free base plugin's own handler.
 */
function pmuip_pmxi_visible_template_sections( $sections, $post_type ){

	if ( ! pmuip_base_is_active() ) return $sections;

	if ( ( 'import_users' == $post_type or 'shop_customer' == $post_type ) and is_array($sections) and ! in_array('cf', $sections) ) {
		$sections[] = 'cf';
	}

	return $sections;

}
