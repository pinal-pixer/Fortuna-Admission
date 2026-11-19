<?php

if ( ! defined( 'ABSPATH' ) ) exit;

function pmui_pro_notice( $message, $campaign ) {

	if ( ! apply_filters( 'pmui_is_show_pro_notice', true, $campaign ) ) return;
	?>
	<div class="wpai-user-free-edition-notice">
		<a href="<?php echo esc_url( pmui_pro_notice_url( $campaign ) ); ?>" target="_blank" class="upgrade_link"><u><?php echo esc_html( $message ); ?></u></a>
		<p><?php esc_html_e( 'If you already own it, ensure it\'s installed and activated.', 'wpallimport-user-import' ); ?></p>
	</div>
	<?php
}
