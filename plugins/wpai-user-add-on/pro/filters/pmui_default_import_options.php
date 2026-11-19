<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Merges the pro layer's option defaults into the free base plugin's default set.
 * Core builds its known-options list from the addon registry's defaults on every
 * screen, including direct Step 4 edits from Manage Imports, so this is the one
 * channel that reaches them all.
 */
function pmuip_pmui_default_import_options( $options ) {

	return $options + pmuip_default_import_options();
}
