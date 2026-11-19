<?php
/**
 * Form Submissions – local WordPress backup layer.
 *
 * Every consultation form submission is saved here before the Podio webhook
 * is attempted. If Podio is unreachable the data is still captured and the
 * record is flagged so it can be retried or exported later.
 */

// ---------------------------------------------------------------------------
// Custom Post Type
// ---------------------------------------------------------------------------

function fa_register_submission_cpt() {
    register_post_type( 'fa_submission', array(
        'labels' => array(
            'name'               => 'Form Submissions',
            'singular_name'      => 'Form Submission',
            'all_items'          => 'All Submissions',
            'search_items'       => 'Search Submissions',
            'not_found'          => 'No submissions found.',
            'not_found_in_trash' => 'No submissions found in trash.',
        ),
        'public'       => false,
        'show_ui'      => true,
        'show_in_menu' => true,
        'menu_icon'    => 'dashicons-clipboard',
        'supports'     => array( 'title' ),
        'capabilities' => array(
            'create_posts' => 'do_not_allow',
        ),
        'map_meta_cap' => true,
    ) );
}
add_action( 'init', 'fa_register_submission_cpt' );

// ---------------------------------------------------------------------------
// Save helper
// ---------------------------------------------------------------------------

/**
 * Save a form submission to WordPress.
 *
 * @param  string $form_type  'law' | 'ug' | 'mim'
 * @param  array  $data       Sanitized form fields.
 * @return int|false          Post ID on success, false on failure.
 */
function fa_save_form_submission( $form_type, $data ) {
    $first = $data['first_name'] ?? $data['student_first'] ?? '';
    $last  = $data['last_name']  ?? $data['student_last']  ?? '';
    $name  = trim( $first . ' ' . $last );

    $title = strtoupper( $form_type ) . ' – ' . ( $name ?: 'Unknown' ) . ' – ' . current_time( 'Y-m-d H:i' );

    $post_id = wp_insert_post( array(
        'post_type'   => 'fa_submission',
        'post_title'  => sanitize_text_field( $title ),
        'post_status' => 'publish',
    ) );

    if ( is_wp_error( $post_id ) ) {
        error_log( 'fa_save_form_submission error: ' . $post_id->get_error_message() );
        return false;
    }

    update_post_meta( $post_id, '_fa_form_type',       $form_type );
    update_post_meta( $post_id, '_fa_podio_status',    'pending' );
    update_post_meta( $post_id, '_fa_submission_data', $data );
    update_post_meta( $post_id, '_fa_submitted_ip',    sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '' ) );
    update_post_meta( $post_id, '_fa_submitted_at',    current_time( 'mysql' ) );

    return $post_id;
}

/**
 * Update the Podio delivery status on a saved submission.
 *
 * @param int    $post_id
 * @param string $status  'success' | 'failed'
 * @param string $note    Optional error message or HTTP code.
 */
function fa_update_podio_status( $post_id, $status, $note = '' ) {
    update_post_meta( $post_id, '_fa_podio_status', $status );
    if ( $note ) {
        update_post_meta( $post_id, '_fa_podio_note', sanitize_text_field( $note ) );
    }
}

/**
 * Record a form-handler error so it surfaces in the WP admin "Form Submissions"
 * list. Lets the team monitor nonce failures, missing fields, upload issues,
 * and webhook failures without needing server-log access.
 *
 * @param string $form_type  'mba' | 'ug' | 'law' | 'mim'
 * @param string $stage      'step1' | 'step2' | 'single'
 * @param string $error_code Short machine code, e.g. 'nonce_failed'
 * @param string $error_msg  Human message
 * @param array  $data       Any posted data we were able to capture
 */
function fa_log_form_error( $form_type, $stage, $error_code, $error_msg, $data = array() ) {
    $first = $data['first_name'] ?? $data['student_first'] ?? '';
    $last  = $data['last_name']  ?? $data['student_last']  ?? '';
    $name  = trim( $first . ' ' . $last );

    $title = sprintf(
        '[ERROR] %s %s — %s — %s',
        strtoupper( $form_type ),
        strtoupper( $stage ),
        ( $name ?: ( $data['email'] ?? 'Unknown' ) ),
        current_time( 'Y-m-d H:i' )
    );

    $post_id = wp_insert_post( array(
        'post_type'   => 'fa_submission',
        'post_title'  => sanitize_text_field( $title ),
        'post_status' => 'publish',
    ) );

    if ( is_wp_error( $post_id ) ) {
        error_log( 'fa_log_form_error: ' . $post_id->get_error_message() );
        return false;
    }

    update_post_meta( $post_id, '_fa_form_type',       $form_type );
    update_post_meta( $post_id, '_fa_form_stage',      $stage );
    update_post_meta( $post_id, '_fa_podio_status',    'error' );
    update_post_meta( $post_id, '_fa_error_code',      sanitize_text_field( $error_code ) );
    update_post_meta( $post_id, '_fa_error_message',   sanitize_text_field( $error_msg ) );
    update_post_meta( $post_id, '_fa_submission_data', $data );
    update_post_meta( $post_id, '_fa_submitted_ip',    sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '' ) );
    update_post_meta( $post_id, '_fa_submitted_at',    current_time( 'mysql' ) );
    update_post_meta( $post_id, '_fa_user_agent',      sanitize_text_field( substr( $_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255 ) ) );

    return $post_id;
}

// ---------------------------------------------------------------------------
// Admin list columns
// ---------------------------------------------------------------------------

function fa_submission_columns( $columns ) {
    return array(
        'cb'           => $columns['cb'],
        'title'        => 'Submission',
        'fa_form_type' => 'Form',
        'fa_stage'     => 'Stage',
        'fa_email'     => 'Email',
        'fa_podio'     => 'Status',
        'fa_date'      => 'Submitted',
    );
}
add_filter( 'manage_fa_submission_posts_columns', 'fa_submission_columns' );

function fa_submission_column_content( $column, $post_id ) {
    $data = get_post_meta( $post_id, '_fa_submission_data', true );

    switch ( $column ) {
        case 'fa_form_type':
            echo esc_html( strtoupper( get_post_meta( $post_id, '_fa_form_type', true ) ) );
            break;

        case 'fa_stage':
            $stage = get_post_meta( $post_id, '_fa_form_stage', true );
            if ( ! $stage ) {
                // Legacy records: infer from data shape.
                if ( is_array( $data ) && ( ! empty( $data['business_schools'] ) || ! empty( $data['current_employer'] ) ) ) {
                    $stage = 'step2';
                } elseif ( is_array( $data ) && ( ( $data['form_stage'] ?? '' ) === 'partial' ) ) {
                    $stage = 'step1';
                } else {
                    $stage = 'single';
                }
            }
            $labels = array(
                'step1'  => 'Step 1',
                'step2'  => 'Step 2',
                'single' => 'Single',
            );
            echo esc_html( $labels[ $stage ] ?? ucfirst( $stage ) );
            break;

        case 'fa_email':
            $email = $data['email'] ?? $data['student_email'] ?? $data['parent_email'] ?? '—';
            echo esc_html( $email );
            break;

        case 'fa_podio':
            $status = get_post_meta( $post_id, '_fa_podio_status', true );
            $note   = get_post_meta( $post_id, '_fa_podio_note', true );
            $err_code = get_post_meta( $post_id, '_fa_error_code', true );
            $err_msg  = get_post_meta( $post_id, '_fa_error_message', true );
            $colors = array(
                'success' => '#0a7a45',
                'failed'  => '#c0392b',
                'pending' => '#8a6d00',
                'error'   => '#c0392b',
            );
            $color = $colors[ $status ] ?? '#555';
            $extra = '';
            if ( $status === 'error' ) {
                $extra = $err_code ? ' <small>(' . esc_html( $err_code ) . ')</small>' : '';
            } elseif ( $note ) {
                $extra = ' <small>(' . esc_html( $note ) . ')</small>';
            }
            printf(
                '<span style="color:%s;font-weight:600;">%s</span>%s',
                esc_attr( $color ),
                esc_html( ucfirst( $status ?: 'unknown' ) ),
                $extra
            );
            break;

        case 'fa_date':
            echo esc_html( get_post_meta( $post_id, '_fa_submitted_at', true ) );
            break;
    }
}
add_action( 'manage_fa_submission_posts_custom_column', 'fa_submission_column_content', 10, 2 );

// Make columns sortable
function fa_submission_sortable_columns( $columns ) {
    $columns['fa_podio']     = '_fa_podio_status';
    $columns['fa_form_type'] = '_fa_form_type';
    $columns['fa_stage']     = '_fa_form_stage';
    return $columns;
}
add_filter( 'manage_edit-fa_submission_sortable_columns', 'fa_submission_sortable_columns' );

// ---------------------------------------------------------------------------
// Admin list filters — Form, Stage, Status
// ---------------------------------------------------------------------------

function fa_submission_admin_filters() {
    global $typenow;
    if ( $typenow !== 'fa_submission' ) return;

    $current_type = isset( $_GET['fa_form_type_filter'] ) ? sanitize_text_field( wp_unslash( $_GET['fa_form_type_filter'] ) ) : '';
    $current_stage = isset( $_GET['fa_stage_filter'] ) ? sanitize_text_field( wp_unslash( $_GET['fa_stage_filter'] ) ) : '';
    $current_status = isset( $_GET['fa_status_filter'] ) ? sanitize_text_field( wp_unslash( $_GET['fa_status_filter'] ) ) : '';

    $types = array( '' => 'All forms', 'mba' => 'MBA', 'ug' => 'UG', 'law' => 'Law', 'mim' => 'MIM' );
    echo '<select name="fa_form_type_filter">';
    foreach ( $types as $v => $label ) {
        printf( '<option value="%s"%s>%s</option>', esc_attr( $v ), selected( $current_type, $v, false ), esc_html( $label ) );
    }
    echo '</select>';

    $stages = array( '' => 'All stages', 'step1' => 'Step 1', 'step2' => 'Step 2', 'single' => 'Single' );
    echo '<select name="fa_stage_filter">';
    foreach ( $stages as $v => $label ) {
        printf( '<option value="%s"%s>%s</option>', esc_attr( $v ), selected( $current_stage, $v, false ), esc_html( $label ) );
    }
    echo '</select>';

    $statuses = array( '' => 'All statuses', 'success' => 'Success', 'pending' => 'Pending', 'failed' => 'Failed', 'error' => 'Error' );
    echo '<select name="fa_status_filter">';
    foreach ( $statuses as $v => $label ) {
        printf( '<option value="%s"%s>%s</option>', esc_attr( $v ), selected( $current_status, $v, false ), esc_html( $label ) );
    }
    echo '</select>';
}
add_action( 'restrict_manage_posts', 'fa_submission_admin_filters' );

function fa_submission_apply_filters( $query ) {
    global $pagenow;
    if ( ! is_admin() || $pagenow !== 'edit.php' ) return;
    if ( ( $_GET['post_type'] ?? '' ) !== 'fa_submission' ) return;

    $meta_query = array();

    if ( ! empty( $_GET['fa_form_type_filter'] ) ) {
        $meta_query[] = array(
            'key'   => '_fa_form_type',
            'value' => sanitize_text_field( wp_unslash( $_GET['fa_form_type_filter'] ) ),
        );
    }

    if ( ! empty( $_GET['fa_stage_filter'] ) ) {
        $meta_query[] = array(
            'key'   => '_fa_form_stage',
            'value' => sanitize_text_field( wp_unslash( $_GET['fa_stage_filter'] ) ),
        );
    }

    if ( ! empty( $_GET['fa_status_filter'] ) ) {
        $meta_query[] = array(
            'key'   => '_fa_podio_status',
            'value' => sanitize_text_field( wp_unslash( $_GET['fa_status_filter'] ) ),
        );
    }

    if ( $meta_query ) {
        $query->set( 'meta_query', $meta_query );
    }
}
add_action( 'pre_get_posts', 'fa_submission_apply_filters' );

// ---------------------------------------------------------------------------
// Admin detail meta box – show all submitted fields
// ---------------------------------------------------------------------------

function fa_submission_meta_box() {
    add_meta_box(
        'fa_submission_data',
        'Submitted Data',
        'fa_submission_meta_box_html',
        'fa_submission',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'fa_submission_meta_box' );

function fa_submission_meta_box_html( $post ) {
    $data     = get_post_meta( $post->ID, '_fa_submission_data', true );
    $status   = get_post_meta( $post->ID, '_fa_podio_status', true );
    $note     = get_post_meta( $post->ID, '_fa_podio_note', true );
    $err_code = get_post_meta( $post->ID, '_fa_error_code', true );
    $err_msg  = get_post_meta( $post->ID, '_fa_error_message', true );
    $stage    = get_post_meta( $post->ID, '_fa_form_stage', true );
    $ip       = get_post_meta( $post->ID, '_fa_submitted_ip', true );
    $at       = get_post_meta( $post->ID, '_fa_submitted_at', true );
    $ua       = get_post_meta( $post->ID, '_fa_user_agent', true );

    if ( $status === 'error' || $err_code ) {
        echo '<div style="background:#fdecea;border-left:4px solid #c0392b;padding:12px;margin-bottom:12px;">';
        echo '<strong style="color:#c0392b;">Error</strong>';
        if ( $err_code ) echo ' <code>' . esc_html( $err_code ) . '</code>';
        if ( $err_msg ) echo '<br>' . esc_html( $err_msg );
        echo '</div>';
    }

    echo '<table class="widefat striped" style="margin-top:8px;">';
    echo '<thead><tr><th>Field</th><th>Value</th></tr></thead><tbody>';

    if ( is_array( $data ) && $data ) {
        foreach ( $data as $key => $value ) {
            printf(
                '<tr><td><strong>%s</strong></td><td>%s</td></tr>',
                esc_html( $key ),
                esc_html( is_array( $value ) ? implode( ', ', $value ) : (string) $value )
            );
        }
    } else {
        echo '<tr><td colspan="2"><em>No form data captured.</em></td></tr>';
    }

    echo '</tbody></table>';

    echo '<p style="margin-top:12px;">';
    echo '<strong>Status:</strong> ' . esc_html( ucfirst( $status ?: 'unknown' ) );
    if ( $note ) echo ' — ' . esc_html( $note );
    if ( $stage ) echo '<br><strong>Stage:</strong> ' . esc_html( ucfirst( $stage ) );
    echo '<br><strong>IP:</strong> ' . esc_html( $ip );
    echo '<br><strong>Submitted at:</strong> ' . esc_html( $at );
    if ( $ua ) echo '<br><strong>User agent:</strong> <code style="font-size:11px;">' . esc_html( $ua ) . '</code>';
    echo '</p>';
}
