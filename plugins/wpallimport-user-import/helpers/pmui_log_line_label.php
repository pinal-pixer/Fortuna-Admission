<?php

if ( ! defined( 'ABSPATH' ) ) exit;

function pmui_log_line_label( $kind ) {

	if ( 'error' === $kind ) {
		return _x( 'ERROR', 'import log line label', 'wpallimport-user-import' );
	}

	return _x( 'WARNING', 'import log line label', 'wpallimport-user-import' );
}
