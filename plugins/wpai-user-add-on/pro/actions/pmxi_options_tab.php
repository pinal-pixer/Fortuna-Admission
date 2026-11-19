<?php

/**
 * Extends the Step 4 options the free base plugin renders: the email notification
 * sections and the user meta keys used by the autocomplete fields (the free base
 * plugin renders __META_KEYS as an empty list). When WP All Import Pro searches meta
 * keys over AJAX, only the lookup type is printed.
 */
function pmuip_pmxi_options_tab( $isWizard, $post ) {

	if ( ! pmuip_base_is_active() ) return;

	if ( $post['custom_type'] == 'import_users' or $post['custom_type'] == 'shop_customer' ) {

		include PMUI_PRO_ROOT_DIR . '/pro/views/options/_notifications.php';

		$meta_keys_lookup = pmuip_meta_keys_lookup( $post['custom_type'] );

		if ( $meta_keys_lookup ) {
			?>
			<script type="text/javascript">
				__META_KEYS_LOOKUP = <?php echo wp_json_encode( $meta_keys_lookup, JSON_HEX_TAG | JSON_HEX_AMP ); ?>;
			</script>
			<?php
		} else {
			// A footer handle, so this assignment lands after the base plugin's own on either core.
			$existing_meta_keys = pmuip_get_existing_meta_keys( $post['custom_type'] );
			wp_register_script( 'pmuip-meta-keys', false, array( 'pmxi-admin-script' ), PMUI_PRO_VERSION, true );
			wp_enqueue_script( 'pmuip-meta-keys' );
			wp_add_inline_script( 'pmuip-meta-keys', 'var __META_KEYS = ' . wp_json_encode( $existing_meta_keys, JSON_HEX_TAG | JSON_HEX_AMP ) . ';', 'after' );
		}
	}

}
