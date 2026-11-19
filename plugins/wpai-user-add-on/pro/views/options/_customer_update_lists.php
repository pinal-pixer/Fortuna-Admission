<?php
			// Let's provide lists of billing & shipping fields.

			$billing_fields_list = array(
				'billing_first_name',  'billing_last_name', 'billing_company',
				'billing_address_1', 'billing_address_2', 'billing_city',
				'billing_postcode', 'billing_country', 'billing_state',
				'billing_email', 'billing_phone'
			);

			$shipping_fields_list = array(
				'shipping_first_name', 'shipping_last_name', 'shipping_company',
				'shipping_address_1', 'shipping_address_2', 'shipping_city',
				'shipping_postcode', 'shipping_country', 'shipping_state'
			);

			?>
			<div class="input">
				<input type="hidden" name="pmsci_billing_fields_list" value="0" />
				<input type="hidden" name="pmsci_is_update_billing_fields" value="0" />
				<input type="checkbox" id="is_update_billing_fields" name="pmsci_is_update_billing_fields" value="1" <?php echo $post['pmsci_is_update_billing_fields'] ? 'checked="checked"': '' ?>  class="switcher"/>
				<label for="is_update_billing_fields"><?php _e('Billing Fields', 'wp_all_import_user_add_on') ?></label>
				<div class="switcher-target-is_update_billing_fields" style="padding-left:17px;">
					<div class="input">
						<input type="radio" id="update_billing_fields_logic_full_update" name="pmsci_update_billing_fields_logic" value="full_update" <?php echo ( "full_update" == $post['pmsci_update_billing_fields_logic'] ) ? 'checked="checked"': '' ?> class="switcher"/>
						<label for="update_billing_fields_logic_full_update"><?php _e('Update all Billing Fields', 'wp_all_import_user_add_on') ?></label>
					</div>
					<div class="input">
						<input type="radio" id="update_billing_fields_logic_only" name="pmsci_update_billing_fields_logic" value="only" <?php echo ( "only" == $post['pmsci_update_billing_fields_logic'] ) ? 'checked="checked"': '' ?> class="switcher"/>
						<label for="update_billing_fields_logic_only"><?php _e('Update only these Billing Fields, leave the rest alone', 'wp_all_import_user_add_on') ?></label>
						<div class="switcher-target-update_billing_fields_logic_only pmxi_choosen" style="padding-left:17px;">

							<span class="hidden choosen_values"><?php if (!empty($billing_fields_list)) echo esc_html(implode(',', $billing_fields_list));?></span>
							<input class="choosen_input" value="<?php if (!empty($post['pmsci_billing_fields_list']) and "only" == $post['pmsci_update_billing_fields_logic']) echo esc_html(implode(',', $post['pmsci_billing_fields_list'])); ?>" type="hidden" name="pmsci_billing_fields_only_list"/>
						</div>
					</div>
					<div class="input">
						<input type="radio" id="update_billing_fields_logic_all_except" name="pmsci_update_billing_fields_logic" value="all_except" <?php echo ( "all_except" == $post['pmsci_update_billing_fields_logic'] ) ? 'checked="checked"': '' ?> class="switcher"/>
						<label for="update_billing_fields_logic_all_except"><?php _e('Leave these fields alone, update all other Billing Fields', 'wp_all_import_user_add_on') ?></label>
						<div class="switcher-target-update_billing_fields_logic_all_except pmxi_choosen" style="padding-left:17px;">

							<span class="hidden choosen_values"><?php if (!empty($billing_fields_list)) echo esc_html(implode(',', $billing_fields_list));?></span>
							<input class="choosen_input" value="<?php if (!empty($post['pmsci_billing_fields_list']) and "all_except" == $post['pmsci_update_billing_fields_logic']) echo esc_html(implode(',', $post['pmsci_billing_fields_list'])); ?>" type="hidden" name="pmsci_billing_fields_except_list"/>
						</div>
					</div>
				</div>
			</div>
			<div class="input">
				<input type="hidden" name="pmsci_shipping_fields_list" value="0" />
				<input type="hidden" name="pmsci_is_update_shipping_fields" value="0" />
				<input type="checkbox" id="is_update_shipping_fields" name="pmsci_is_update_shipping_fields" value="1" <?php echo $post['pmsci_is_update_shipping_fields'] ? 'checked="checked"': '' ?>  class="switcher"/>
				<label for="is_update_shipping_fields"><?php _e('Shipping Fields', 'wp_all_import_user_add_on') ?></label>
				<div class="switcher-target-is_update_shipping_fields" style="padding-left:17px;">
					<div class="input">
						<input type="radio" id="update_shipping_fields_logic_full_update" name="pmsci_update_shipping_fields_logic" value="full_update" <?php echo ( "full_update" == $post['pmsci_update_shipping_fields_logic'] ) ? 'checked="checked"': '' ?> class="switcher"/>
						<label for="update_shipping_fields_logic_full_update"><?php _e('Update all Shipping Fields', 'wp_all_import_user_add_on') ?></label>
					</div>
					<div class="input">
						<input type="radio" id="update_shipping_fields_logic_only" name="pmsci_update_shipping_fields_logic" value="only" <?php echo ( "only" == $post['pmsci_update_shipping_fields_logic'] ) ? 'checked="checked"': '' ?> class="switcher"/>
						<label for="update_shipping_fields_logic_only"><?php _e('Update only these Shipping Fields, leave the rest alone', 'wp_all_import_user_add_on') ?></label>
						<div class="switcher-target-update_shipping_fields_logic_only pmxi_choosen" style="padding-left:17px;">

							<span class="hidden choosen_values"><?php if (!empty($shipping_fields_list)) echo esc_html(implode(',', $shipping_fields_list));?></span>
							<input class="choosen_input" value="<?php if (!empty($post['pmsci_shipping_fields_list']) and "only" == $post['pmsci_update_shipping_fields_logic']) echo esc_html(implode(',', $post['pmsci_shipping_fields_list'])); ?>" type="hidden" name="pmsci_shipping_fields_only_list"/>
						</div>
					</div>
					<div class="input">
						<input type="radio" id="update_shipping_fields_logic_all_except" name="pmsci_update_shipping_fields_logic" value="all_except" <?php echo ( "all_except" == $post['pmsci_update_shipping_fields_logic'] ) ? 'checked="checked"': '' ?> class="switcher"/>
						<label for="update_shipping_fields_logic_all_except"><?php _e('Leave these fields alone, update all other Shipping Fields', 'wp_all_import_user_add_on') ?></label>
						<div class="switcher-target-update_shipping_fields_logic_all_except pmxi_choosen" style="padding-left:17px;">

							<span class="hidden choosen_values"><?php if (!empty($shipping_fields_list)) echo esc_html(implode(',', $shipping_fields_list));?></span>
							<input class="choosen_input" value="<?php if (!empty($post['pmsci_shipping_fields_list']) and "all_except" == $post['pmsci_update_shipping_fields_logic']) echo esc_html(implode(',', $post['pmsci_shipping_fields_list'])); ?>" type="hidden" name="pmsci_shipping_fields_except_list"/>
						</div>
					</div>
				</div>
			</div>
