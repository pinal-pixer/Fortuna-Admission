<?php

// An import that cannot be read is treated as one that needs the base, so the SDK's block stays on.
function pmuip_import_needs_base( $import_ids ) {
	if ( ! class_exists('PMXI_Import_Record') ) return true;

	$ids = array_filter( array_map( 'intval', is_array( $import_ids ) ? $import_ids : explode( ',', (string) $import_ids ) ) );
	if ( empty( $ids ) ) return true;

	foreach ( $ids as $id ) {
		$import = new PMXI_Import_Record();
		$import->getById( $id );
		if ( $import->isEmpty() || ! is_array( $import->options ) ) return true;
		if ( in_array( $import->options['custom_type'] ?? '', array( 'import_users', 'shop_customer' ), true ) ) return true;
	}

	return false;
}
