<?php

$pmuip_bs_fields = array(
	'billing_first_name', 'billing_last_name', 'billing_company', 'billing_address_1', 'billing_address_2',
	'billing_city', 'billing_postcode', 'billing_country', 'billing_state', 'billing_email', 'billing_phone',
	'shipping_first_name', 'shipping_last_name', 'shipping_company', 'shipping_address_1', 'shipping_address_2',
	'shipping_city', 'shipping_postcode', 'shipping_country', 'shipping_state',
);

?>
<div class="wpallimport-collapsed <?php echo pmuip_section_closed_class($post, $pmuip_bs_fields); ?>">
	<div class="wpallimport-content-section">
		<div class="wpallimport-collapsed-header">
			<h3><?php _e('Billing & Shipping Info','wpai_woocommerce_addon_plugin');?></h3>	
		</div>
		<div class="wpallimport-collapsed-content" style="padding:0;">
            <div class="wpallimport-collapsed-content-inner">
                <div class="pmsci-customer-fields">
                    <div id="pmsci_billing">

                        <!-- BILLING -->

                        <?php include( '_tabs/_customer_billing.php' ); ?>

                    </div>
                    <div id="pmsci_shipping">

                        <!-- SHIPPING -->

                        <?php include( '_tabs/_customer_shipping.php' ); ?>

                    </div>
                    <div class="clear"></div>
                </div>
            </div>
		</div>
	</div>
</div>