<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<ul style="padding-left: 35px;">
    <?php if ( ! empty($post['is_update_acf'])): ?>
        <li>
            <?php
            switch($post['update_acf_logic']){
                case 'full_update':
                    esc_html_e('All advanced custom fields', 'wp_all_import_user_add_on');
                    break;
                case 'mapped':
                    esc_html_e('Only ACF presented in import options', 'wp_all_import_user_add_on');
                    break;
                case 'only':
                    // translators: %s is the list of ACF field names.
                    printf(esc_html__('Only these ACF : %s', 'wp_all_import_user_add_on'), esc_html($post['acf_only_list']));
                    break;
                case 'all_except':
                    // translators: %s is the list of ACF field names.
                    printf(esc_html__('All ACF except these: %s', 'wp_all_import_user_add_on'), esc_html($post['acf_except_list']));
                    break;
            } ?>
        </li>
    <?php endif; ?>
    <?php if ( ! empty($post['is_update_custom_fields'])): ?>
        <li>
            <?php
            switch($post['update_custom_fields_logic']){
                case 'full_update':
                    esc_html_e('All custom fields', 'wp_all_import_user_add_on');
                    break;
                case 'only':
                    // translators: %s is the list of custom field names.
                    printf(esc_html__('Only these custom fields : %s', 'wp_all_import_user_add_on'), esc_html($post['custom_fields_only_list']));
                    break;
                case 'all_except':
                    // translators: %s is the list of custom field names.
                    printf(esc_html__('All custom fields except these: %s', 'wp_all_import_user_add_on'), esc_html($post['custom_fields_except_list']));
                    break;
            } ?>
        </li>
    <?php endif; ?>
</ul>
