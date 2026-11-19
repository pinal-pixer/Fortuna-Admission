<?php

function pmuip_release_import( $import_id ) {
	if ( ! class_exists( 'PMXI_Import_Record' ) ) return;

	$import = new PMXI_Import_Record();
	$import->getById( (int) $import_id );
	if ( ! $import->isEmpty() ) $import->set( array( 'executing' => 0, 'processing' => 0, 'triggered' => 0 ) )->save();
}
