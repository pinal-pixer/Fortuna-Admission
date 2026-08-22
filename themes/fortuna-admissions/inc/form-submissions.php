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

// ---------------------------------------------------------------------------
// Admin list columns
// ---------------------------------------------------------------------------

function fa_submission_columns( $columns ) {
    return array(
        'cb'           => $columns['cb'],
        'title'        => 'Submission',
        'fa_form_type' => 'Form',
        'fa_email'     => 'Email',
        'fa_podio'     => 'Podio',
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

        case 'fa_email':
            $email = $data['email'] ?? $data['student_email'] ?? $data['parent_email'] ?? '—';
            echo esc_html( $email );
            break;

        case 'fa_podio':
            $status = get_post_meta( $post_id, '_fa_podio_status', true );
            $note   = get_post_meta( $post_id, '_fa_podio_note', true );
            $colors = array(
                'success' => '#0a7a45',
                'failed'  => '#c0392b',
                'pending' => '#8a6d00',
            );
            $color = $colors[ $status ] ?? '#555';
            printf(
                '<span style="color:%s;font-weight:600;">%s</span>%s',
                esc_attr( $color ),
                esc_html( ucfirst( $status ) ),
                $note ? ' <small>(' . esc_html( $note ) . ')</small>' : ''
            );
            break;

        case 'fa_date':
            echo esc_html( get_post_meta( $post_id, '_fa_submitted_at', true ) );
            break;
    }
}
add_action( 'manage_fa_submission_posts_custom_column', 'fa_submission_column_content', 10, 2 );

// Make Podio status column sortable
function fa_submission_sortable_columns( $columns ) {
    $columns['fa_podio']     = '_fa_podio_status';
    $columns['fa_form_type'] = '_fa_form_type';
    return $columns;
}
add_filter( 'manage_edit-fa_submission_sortable_columns', 'fa_submission_sortable_columns' );

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
    $data   = get_post_meta( $post->ID, '_fa_submission_data', true );
    $status = get_post_meta( $post->ID, '_fa_podio_status', true );
    $note   = get_post_meta( $post->ID, '_fa_podio_note', true );
    $ip     = get_post_meta( $post->ID, '_fa_submitted_ip', true );
    $at     = get_post_meta( $post->ID, '_fa_submitted_at', true );

    echo '<table class="widefat striped" style="margin-top:8px;">';
    echo '<thead><tr><th>Field</th><th>Value</th></tr></thead><tbody>';

    if ( is_array( $data ) ) {
        foreach ( $data as $key => $value ) {
            printf(
                '<tr><td><strong>%s</strong></td><td>%s</td></tr>',
                esc_html( $key ),
                esc_html( is_array( $value ) ? implode( ', ', $value ) : $value )
            );
        }
    }

    echo '</tbody></table>';

    echo '<p style="margin-top:12px;">';
    echo '<strong>Podio status:</strong> ' . esc_html( ucfirst( $status ) );
    if ( $note ) echo ' — ' . esc_html( $note );
    echo '<br><strong>IP:</strong> ' . esc_html( $ip );
    echo '<br><strong>Submitted at:</strong> ' . esc_html( $at );
    echo '</p>';
}
