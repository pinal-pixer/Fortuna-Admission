<ul style="padding-left: 35px;">
    <?php if ( ! empty($post['pmsci_is_update_billing_fields'])): ?>
        <li>
            <?php
            switch($post['pmsci_update_billing_fields_logic']){
                case 'full_update':
                    _e('All billing fields', 'wp_all_import_user_add_on');
                    break;
                case 'only':
                    printf(__('Only these billing fields : %s', 'wp_all_import_user_add_on'), $post['pmsci_billing_fields_only_list']);
                    break;
                case 'all_except':
                    printf(__('All billing fields except these: %s', 'wp_all_import_user_add_on'), $post['pmsci_billing_fields_except_list']);
                    break;
            } ?>
        </li>
    <?php endif; ?>
    <?php if ( ! empty($post['pmsci_is_update_shipping_fields'])): ?>
        <li>
            <?php
            switch($post['pmsci_update_shipping_fields_logic']){
                case 'full_update':
                    _e('All shipping fields', 'wp_all_import_user_add_on');
                    break;
                case 'only':
                    printf(__('Only these shipping fields : %s', 'wp_all_import_user_add_on'), $post['pmsci_shipping_fields_only_list']);
                    break;
                case 'all_except':
                    printf(__('All shipping fields except these: %s', 'wp_all_import_user_add_on'), $post['pmsci_shipping_fields_except_list']);
                    break;
            } ?>
        </li>
    <?php endif; ?>
</ul>
