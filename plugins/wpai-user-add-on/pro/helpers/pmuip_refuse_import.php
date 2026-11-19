<?php

function pmuip_refuse_import( $context, $base_installed ) {
	$message = $base_installed
		// translators: %s is the minimum WP All Import Pro version.
		? sprintf( __( 'WP All Import - User Import Add-On Pro needs WP All Import Pro %s or newer to run users and customers imports. Update WP All Import Pro.', 'wp_all_import_user_add_on' ), defined( 'PMUI_MIN_PAID_CORE' ) ? PMUI_MIN_PAID_CORE : '5.1.0' )
		: __( 'WP All Import - User Import Add-On Pro needs WP All Import - User Import Add-On installed and active to run users and customers imports.', 'wp_all_import_user_add_on' );

	if ( 'record' === $context ) {
		throw new Exception( $message );
	}

	if ( 'scheduled' === $context ) {
		wp_send_json( array( 'status' => 424, 'message' => $message ), 424 );
	}

	if ( defined( 'WP_CLI' ) && WP_CLI && class_exists( 'WP_CLI' ) ) {
		WP_CLI::error( $message );
	}

	if ( wp_doing_ajax() ) {
		wp_send_json_error( array( 'message' => $message ), 400 );
	}

	wp_die( esc_html( $message ), esc_html__( 'Dependency Missing', 'wp_all_import_user_add_on' ), array( 'response' => 400 ) );
}
