<?php

function pmuip_admin_enqueue_scripts() {

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$page = isset($_GET['page']) ? sanitize_key( wp_unslash($_GET['page']) ) : '';

	if ( 0 !== strpos( $page, 'pmxi-admin' ) and 0 !== strpos( $page, 'pmui-' ) ) return;

	wp_enqueue_style( 'pmuip-admin-style', PMUI_PRO_ROOT_URL . '/pro/static/css/admin.css', array(), PMUI_PRO_VERSION );
}
