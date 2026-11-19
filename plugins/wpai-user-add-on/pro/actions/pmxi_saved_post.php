<?php

/**
 * Sends the welcome email to newly created users unless notifications are blocked.
 * The free base plugin no longer sends it from its import routine, so it is restored
 * here on the per-record hook core fires right after a record is saved.
 *
 * @param $user_id
 * @param $xml
 * @param $is_update
 * @return void
 */
function pmuip_pmxi_saved_post( $user_id, $xml, $is_update ) {
	if ( ! pmuip_base_is_active() ) return;

	if ( $is_update ) return;

	static $imports = array();
	$import_id = wp_all_import_get_import_id();
	if ( ! isset($imports[$import_id]) ) {
		$import = new PMXI_Import_Record();
		$import->getById( $import_id );
		$imports[$import_id] = $import;
	}
	$import = $imports[$import_id];
	if ( $import->isEmpty() or ! in_array($import->options['custom_type'], array('import_users', 'shop_customer')) ) return;

	if ( empty($import->options['do_not_send_password_notification']) ) {
		// Welcome Email
		wp_new_user_notification( $user_id, null, 'both' );
	}
}
