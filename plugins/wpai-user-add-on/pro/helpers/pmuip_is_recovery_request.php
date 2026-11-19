<?php

// Cancel and cleanup are how a stuck import is freed, so they are never refused.
function pmuip_is_recovery_request() {
	$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';
	return in_array( $action, array( 'cancel', 'cleanup' ), true );
}
