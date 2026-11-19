			<div class="input">
				<input type="hidden" name="custom_fields_list" value="0" />
				<input type="hidden" name="is_update_custom_fields" value="0" />
				<input type="checkbox" id="is_update_custom_fields" name="is_update_custom_fields" value="1" <?php echo $post['is_update_custom_fields'] ? 'checked="checked"': '' ?>  class="switcher"/>
				<label for="is_update_custom_fields"><?php _e('Meta Fields', 'wp_all_import_user_add_on') ?></label>
				<!--a href="#help" class="wpallimport-help" title="<?php _e('If Keep Custom Fields box is checked, it will keep all Custom Fields, and add any new Custom Fields specified in Custom Fields section, as long as they do not overwrite existing fields. If \'Only keep this Custom Fields\' is specified, it will only keep the specified fields.', 'wp_all_import_user_add_on') ?>">?</a-->
				<div class="switcher-target-is_update_custom_fields" style="padding-left:17px;">
					<div class="input">
						<input type="radio" id="update_custom_fields_logic_full_update" name="update_custom_fields_logic" value="full_update" <?php echo ( "full_update" == $post['update_custom_fields_logic'] ) ? 'checked="checked"': '' ?> class="switcher"/>
						<label for="update_custom_fields_logic_full_update"><?php _e('Update all Meta Fields', 'wp_all_import_user_add_on') ?></label>
					</div>
					<div class="input">
						<input type="radio" id="update_custom_fields_logic_only" name="update_custom_fields_logic" value="only" <?php echo ( "only" == $post['update_custom_fields_logic'] ) ? 'checked="checked"': '' ?> class="switcher"/>
						<label for="update_custom_fields_logic_only"><?php _e('Update only these Meta Fields, leave the rest alone', 'wp_all_import_user_add_on') ?></label>
						<div class="switcher-target-update_custom_fields_logic_only pmxi_choosen"<?php if ( ! empty($meta_keys_lookup) ) echo ' data-meta-keys-lookup="' . esc_attr($meta_keys_lookup) . '"'; ?> style="padding-left:17px;">

							<span class="hidden choosen_values"><?php if (!empty($existing_meta_keys)) echo esc_html(implode(',', $existing_meta_keys));?></span>
							<input class="choosen_input" value="<?php if (!empty($post['custom_fields_list']) and "only" == $post['update_custom_fields_logic']) echo esc_html(implode(',', $post['custom_fields_list'])); ?>" type="hidden" name="custom_fields_only_list"/>
						</div>
					</div>
					<div class="input">
						<input type="radio" id="update_custom_fields_logic_all_except" name="update_custom_fields_logic" value="all_except" <?php echo ( "all_except" == $post['update_custom_fields_logic'] ) ? 'checked="checked"': '' ?> class="switcher"/>
						<label for="update_custom_fields_logic_all_except"><?php _e('Leave these fields alone, update all other Meta Fields', 'wp_all_import_user_add_on') ?></label>
						<div class="switcher-target-update_custom_fields_logic_all_except pmxi_choosen"<?php if ( ! empty($meta_keys_lookup) ) echo ' data-meta-keys-lookup="' . esc_attr($meta_keys_lookup) . '"'; ?> style="padding-left:17px;">

							<span class="hidden choosen_values"><?php if (!empty($existing_meta_keys)) echo esc_html(implode(',', $existing_meta_keys));?></span>
							<input class="choosen_input" value="<?php if (!empty($post['custom_fields_list']) and "all_except" == $post['update_custom_fields_logic']) echo esc_html(implode(',', $post['custom_fields_list'])); ?>" type="hidden" name="custom_fields_except_list"/>
						</div>
					</div>
				</div>
			</div>
