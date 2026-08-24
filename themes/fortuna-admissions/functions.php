<?php
/**
 * Fortuna Admissions Theme functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Fortuna Admissions
 * @since 1.0.0
 */

/**
 * Define Constants
 */
define( 'CHILD_THEME_FORTUNA_ADMISSIONS_VERSION', '1.0.5' );

/*------------------------------------*\
    Enqueue styles
\*------------------------------------*/
function child_enqueue_styles() {

	wp_enqueue_style( 'fortuna-admissions-theme-css', get_stylesheet_directory_uri() . '/dist/index.css', array('astra-theme-css'), CHILD_THEME_FORTUNA_ADMISSIONS_VERSION, 'all' );

}

add_action( 'wp_enqueue_scripts', 'child_enqueue_styles', 15 );

/*------------------------------------*\
    External Modules/Files
\*------------------------------------*/

require_once('inc/menu-walker.php');
require_once('inc/shortcodes.php');
require_once('inc/form-submissions.php');
require_once('blocks/index.php');

/*------------------------------------*\
    Functions
\*------------------------------------*/

// Load scripts (header.php)
function theme_scripts()
{
    if ($GLOBALS['pagenow'] != 'wp-login.php' && !is_admin()) {

        // Get dependency list from build
        $build_assets = include 'dist/index.asset.php';

        if ($build_assets['dependencies']) {
            // Add jQuery as dependency
            array_push($build_assets['dependencies'], 'jquery');
        }

        wp_register_script(
            'fortuna-admissions',
            get_stylesheet_directory_uri() . '/dist/index.js',
            $build_assets['dependencies'],
            $build_assets['version'],
            true
        );

        wp_enqueue_script('fortuna-admissions');
    }
}

add_action('wp_enqueue_scripts', 'theme_scripts'); // Add Custom Scripts to wp_head

// Serve responsive images for ACF images
function responsive_image($image_id, $max_width = false)
{
    if ($image_id != '') {

        // Get image full width
        $image_width = wp_get_attachment_image_src($image_id, 'full')[1];
        $image_src = wp_get_attachment_image_url($image_id, 'full');
        $image_srcset = wp_get_attachment_image_srcset($image_id);

        // Include sizes attribute if image has max width
        if ($max_width) {
            $image_max_width = ' sizes="(max-width: ' . $image_width . 'px) 100vw, ' . $image_width . 'px"';
        } else {
            $image_max_width = '';
        }
        echo 'src="' . $image_src . '" srcset="' . $image_srcset . '"' . $image_max_width;
    }
}

add_filter('author_link', 'custom_author_profile_link', 10, 3);

// Replace author archive url with custom profile link
function custom_author_profile_link($link, $author_id, $author_nicename) {
    $custom_url = get_field('profile_link', 'user_' . $author_id);

    if (!empty($custom_url['url'])) {
        return esc_url($custom_url['url']);
    }

    return $link;
}


/*------------------------------------*\
    Service-based post permalinks
    Maps: undergrad → /college/, business-school → /mba/, law-school → /law/
\*------------------------------------*/

function fa_get_service_url_prefixes() {
    return [
        'undergrad'       => 'college',
        'business-school' => 'mba',
        'law-school'      => 'law',
    ];
}

function fa_get_post_service_prefix($post_id) {
    $prefixes = fa_get_service_url_prefixes();
    $services = wp_get_post_terms($post_id, 'service', ['fields' => 'slugs']);

    if (is_wp_error($services) || empty($services)) {
        return '';
    }

    foreach ($services as $slug) {
        if (isset($prefixes[$slug])) {
            return $prefixes[$slug];
        }
    }

    return '';
}

/**
 * Filter post permalinks to include service prefix.
 */
function fa_service_post_link($permalink, $post) {
    if ($post->post_type !== 'post' || $post->post_status === 'auto-draft') {
        return $permalink;
    }

    $prefix = fa_get_post_service_prefix($post->ID);
    if (empty($prefix)) {
        return $permalink;
    }

    return trailingslashit(home_url()) . $prefix . '/' . $post->post_name . '/';
}
add_filter('post_link', 'fa_service_post_link', 10, 2);

/**
 * Register rewrite rules and query var for service-prefixed URLs.
 * Uses 'top' priority so these fire before WordPress's attachment rule
 * ([^/]+/([^/]+)/?$) which would otherwise match and cause a 404.
 */
function fa_service_rewrite_rules() {
    $prefixes = fa_get_service_url_prefixes();

    foreach ($prefixes as $url_prefix) {
        // /embed/ endpoint — must be registered before the general post rule
        // so WordPress's self-embed iframe (src ends in /embed/) resolves correctly.
        add_rewrite_rule(
            '^' . $url_prefix . '/([^/]+)/embed/?$',
            'index.php?fa_service_prefix=' . $url_prefix . '&name=$matches[1]&embed=true',
            'top'
        );

        add_rewrite_rule(
            '^' . $url_prefix . '/([^/]+)/?$',
            'index.php?fa_service_prefix=' . $url_prefix . '&name=$matches[1]',
            'top'
        );
    }
}
add_action('init', 'fa_service_rewrite_rules');

function fa_service_query_vars($vars) {
    $vars[] = 'fa_service_prefix';
    return $vars;
}
add_filter('query_vars', 'fa_service_query_vars');

/**
 * When our rewrite rule fires, check if the slug is actually a child page
 * of the parent (e.g. /mba/about-us/ is a page, not a post).
 * If it's a page, rewrite query vars to load the page instead.
 * If it's a post, validate the service prefix matches.
 */
function fa_resolve_service_post_request($query_vars) {
    if (empty($query_vars['fa_service_prefix']) || empty($query_vars['name'])) {
        return $query_vars;
    }

    $url_prefix = $query_vars['fa_service_prefix'];
    $slug = $query_vars['name'];
    $page_path = $url_prefix . '/' . $slug;

    // Check if a child page exists at this path (e.g. /mba/about-us/)
    $page = get_page_by_path($page_path);
    if ($page && $page->post_status === 'publish') {
        unset($query_vars['fa_service_prefix']);
        unset($query_vars['name']);
        $query_vars['pagename'] = $page_path;
        return $query_vars;
    }

    // It's a post — verify the service prefix is correct
    global $wpdb;
    $post_id = $wpdb->get_var($wpdb->prepare(
        "SELECT ID FROM $wpdb->posts WHERE post_name = %s AND post_type = 'post' AND post_status = 'publish' LIMIT 1",
        $slug
    ));

    if ($post_id) {
        $correct_prefix = fa_get_post_service_prefix($post_id);
        if ($correct_prefix !== $url_prefix) {
            // Wrong service prefix — force 404
            unset($query_vars['name']);
            unset($query_vars['fa_service_prefix']);
            $query_vars['error'] = '404';
        }
    }

    return $query_vars;
}
add_filter('request', 'fa_resolve_service_post_request');

/**
 * Helper: check if the current request URI already matches a service-prefixed path.
 */
function fa_is_service_prefixed_request() {
    $request_uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
    $prefixes = fa_get_service_url_prefixes();

    foreach ($prefixes as $url_prefix) {
        if (strpos($request_uri, $url_prefix . '/') === 0) {
            return true;
        }
    }

    return false;
}

/**
 * Prevent WordPress canonical redirect from overriding service-prefixed post URLs.
 * Priority 0 to run before Yoast SEO Premium (priority 1) and Redirection plugin (priority 10).
 */
function fa_prevent_canonical_redirect_for_service_posts($redirect_url, $requested_url) {
    if (fa_is_service_prefixed_request()) {
        return false;
    }
    return $redirect_url;
}
add_filter('redirect_canonical', 'fa_prevent_canonical_redirect_for_service_posts', 0, 2);

/**
 * Intercept wp_redirect to prevent redirect loops on service-prefixed URLs.
 * If we're already on the correct service-prefixed URL, block any redirect away from it.
 */
function fa_prevent_redirect_loop($location, $status) {
    if (is_admin()) {
        return $location;
    }

    if (!fa_is_service_prefixed_request()) {
        return $location;
    }

    // If the redirect target is the same service-prefixed URL, allow it (no loop).
    $current_path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
    $target_path = trim(parse_url($location, PHP_URL_PATH), '/');

    if ($current_path === $target_path) {
        return $location;
    }

    // We're on a service-prefixed URL and something wants to redirect us away — block it.
    return false;
}
add_filter('wp_redirect', 'fa_prevent_redirect_loop', 0, 2);

/**
 * Redirect old post URLs (without service prefix) to new service-prefixed URLs.
 */
function fa_redirect_old_post_urls() {
    if (is_admin() || !is_singular('post')) {
        return;
    }

    // Don't redirect if we're already on a service-prefixed URL.
    if (fa_is_service_prefixed_request()) {
        return;
    }

    $post = get_queried_object();
    if (!$post) {
        return;
    }

    $prefix = fa_get_post_service_prefix($post->ID);
    if (empty($prefix)) {
        return;
    }

    wp_redirect(home_url('/' . $prefix . '/' . $post->post_name . '/'), 301);
    exit;
}
add_action('template_redirect', 'fa_redirect_old_post_urls');

/**
 * Disable Hummingbird's "Delay JS Execution" on embed views.
 * Why: inside a self-embed <iframe>, wp-embed-template.min.js must run immediately
 * to postMessage its height/ready to the parent. If Hummingbird wraps it in
 * <script type="wphb-delay-type">, it never runs (no user interaction inside the
 * sandboxed iframe), so the parent never reveals the iframe and only the
 * blockquote fallback (bare title link) is visible.
 */
add_filter('wphb_should_delay_js', function ($should_delay) {
    if (is_embed()) {
        return false;
    }
    return $should_delay;
});

/* add aria label for college, mba and law link */
add_filter('render_block', function($block_content) {

    if (
        strpos($block_content, '/college') === false &&
        strpos($block_content, '/mba') === false &&
        strpos($block_content, '/law') === false
    ) {
        return $block_content;
    }

    $replacements = [
        'href="/college">' => 'href="/college" aria-label="Go to College page">',
        
        'href="/mba">' => 'href="/mba" aria-label="Go to MBA page">',
        
        'href="/law">' => 'href="/law" aria-label="Go to Law School page">',
        
        'href="/college/free-consultation">' => 'href="/college/free-consultation" aria-label="Book your college free consultation">',
        
        'href="/mba/free-consultation">' => 'href="/mba/free-consultation" aria-label="Book your MBA free consultation">',
  
        'href="/law/free-consultation/">' => 'href="/law/free-consultation" aria-label="Book your Law free consultation">',
  
  		'href="/mba/about-us/mba-team/">' => 'href="/mba/about-us/mba-team/" aria-label="Meet the Experts">',
  
  		
    ];

    return str_replace(
        array_keys($replacements),
        array_values($replacements),
        $block_content
    );

}, 10, 1);

add_shortcode('law_free_consultation_form', 'law_free_consultation_form_shortcode');

function law_free_consultation_form_shortcode() {

    ob_start();

    $form_file = get_stylesheet_directory() . '/inc/forms/law-free-consultation-form.php';

    if ( file_exists( $form_file ) ) {
        require $form_file;
    }

    return ob_get_clean();
}

add_shortcode('ug_free_consultation_form', 'ug_free_consultation_form_shortcode');

function ug_free_consultation_form_shortcode() {

    ob_start();

    $form_file = get_stylesheet_directory() . '/inc/forms/ug-free-consultation-form.php';

    if ( file_exists( $form_file ) ) {
        require $form_file;
    }

    return ob_get_clean();
}

add_shortcode('mim_free_consultation_form', 'mim_free_consultation_form_shortcode');

function mim_free_consultation_form_shortcode() {

    ob_start();

    $form_file = get_stylesheet_directory() . '/inc/forms/mim-free-consultation-form.php';

    if ( file_exists( $form_file ) ) {
        require $form_file;
    }

    return ob_get_clean();
}

add_action('admin_post_nopriv_law_consultation_submit', 'law_consultation_submit');
add_action('admin_post_law_consultation_submit', 'law_consultation_submit');

/**
 * Load Google reCAPTCHA v3
 */
function fa_enqueue_recaptcha() {

    wp_enqueue_script(
        'google-recaptcha',
        'https://www.google.com/recaptcha/api.js?render=6LevnXYtAAAAAMJD8mj2aeDja_yK6R20db50KgpD',
        array(),
        null,
        true
    );

}

add_action('wp_enqueue_scripts', 'fa_enqueue_recaptcha');

function law_consultation_submit() {

    if ( ! isset($_POST['law_consultation_submit']) ) {
        return;
    }

    /**
     * Nonce Verification
     */
    if (
        ! isset($_POST['law_consultation_nonce_field']) ||
        ! wp_verify_nonce(
            $_POST['law_consultation_nonce_field'],
            'law_consultation_nonce'
        )
    ) {
        wp_die('Security verification failed.');
    }

    /**
     * Honeypot Spam Check
     */
    if ( ! empty($_POST['website']) ) {
        wp_die('Spam detected.');
    }

    /**
     * Google reCAPTCHA Verification
     */
    $recaptcha_secret = '6LevnXYtAAAAABkAIW1joZ1sy2Bw_ieBD6V2Ide4';

    $recaptcha_response = isset($_POST['g-recaptcha-response'])
        ? sanitize_text_field($_POST['g-recaptcha-response'])
        : '';

    $verify = wp_remote_post(
        'https://www.google.com/recaptcha/api/siteverify',
        array(
            'body' => array(
                'secret'   => $recaptcha_secret,
                'response' => $recaptcha_response,
                'remoteip' => $_SERVER['REMOTE_ADDR'],
            ),
            'timeout' => 30,
        )
    );

    if ( is_wp_error( $verify ) ) {
        wp_die('Unable to verify reCAPTCHA.');
    }

    $result = json_decode(
        wp_remote_retrieve_body($verify),
        true
    );

    if (
        empty($result['success']) ||
        $result['score'] < 0.5 ||
        $result['action'] !== 'law_consultation'
    ) {
        wp_die('reCAPTCHA verification failed.');
    }

    /**
     * Sanitize Form Data
     */
    $data = array(
        'first_name'             => sanitize_text_field($_POST['first_name']),
        'last_name'              => sanitize_text_field($_POST['last_name']),
        'email'                  => sanitize_email($_POST['email']),
        'linkedin_url'           => esc_url_raw($_POST['linkedin_url']),
        'hear_about_us'          => sanitize_text_field($_POST['hear_about_us']),
        'additional_information' => sanitize_textarea_field($_POST['additional_information']),
  		'code'                   => sanitize_text_field($_POST['code']),
    );

    /**
     * Save to WordPress (backup layer)
     */
    $submission_id = fa_save_form_submission( 'law', $data );

    /**
     * Send to Podio
     */
    $response = wp_remote_post(
        'https://workflow-automation.podio.com/catch/7223n63kj799c0f',
        array(
            'method'  => 'POST',
            'body'    => $data,
            'timeout' => 30,
        )
    );

    if ( $submission_id ) {
        if ( is_wp_error( $response ) ) {
            fa_update_podio_status( $submission_id, 'failed', $response->get_error_message() );
            error_log( 'Law Consultation: Podio webhook error: ' . $response->get_error_message() );
        } else {
            $code = wp_remote_retrieve_response_code( $response );
            if ( $code >= 200 && $code < 300 ) {
                fa_update_podio_status( $submission_id, 'success' );
            } else {
                fa_update_podio_status( $submission_id, 'failed', 'HTTP ' . $code );
                error_log( 'Law Consultation: Podio webhook HTTP ' . $code );
            }
        }
    }

    /**
     * Redirect
     */
    wp_safe_redirect(home_url('/law/free-consultation-thank-you/'));

    exit;
}

add_action('admin_post_nopriv_ug_consultation_submit', 'ug_consultation_submit');
add_action('admin_post_ug_consultation_submit', 'ug_consultation_submit');

function ug_consultation_submit() {

    if ( ! isset($_POST['ug_consultation_submit']) ) {
        return;
    }

    /**
     * Nonce Verification
     */
    if (
        ! isset($_POST['ug_consultation_nonce_field']) ||
        ! wp_verify_nonce(
            $_POST['ug_consultation_nonce_field'],
            'ug_consultation_nonce'
        )
    ) {
        error_log('UG Consultation: Nonce verification failed. IP: ' . $_SERVER['REMOTE_ADDR']);
        wp_safe_redirect(home_url('/college/free-consultation/?error=security'));
        exit;
    }

    /**
     * Honeypot Spam Check
     */
    if ( ! empty($_POST['website']) ) {
        wp_safe_redirect(home_url('/college/free-consultation/?error=spam'));
        exit;
    }

    /**
     * Google reCAPTCHA Verification
     */
    $recaptcha_secret = '6LevnXYtAAAAABkAIW1joZ1sy2Bw_ieBD6V2Ide4';

    $recaptcha_response = isset($_POST['g-recaptcha-response'])
        ? sanitize_text_field($_POST['g-recaptcha-response'])
        : '';

    $verify = wp_remote_post(
        'https://www.google.com/recaptcha/api/siteverify',
        array(
            'body' => array(
                'secret'   => $recaptcha_secret,
                'response' => $recaptcha_response,
                'remoteip' => $_SERVER['REMOTE_ADDR'],
            ),
            'timeout' => 30,
        )
    );

    if ( is_wp_error( $verify ) ) {
        error_log('UG Consultation: reCAPTCHA API error: ' . $verify->get_error_message() . ' IP: ' . $_SERVER['REMOTE_ADDR']);
        wp_safe_redirect(home_url('/college/free-consultation/?error=recaptcha'));
        exit;
    }

    $result = json_decode(
        wp_remote_retrieve_body($verify),
        true
    );

    if (
        empty($result['success']) ||
        $result['score'] < 0.5 ||
        $result['action'] !== 'ug_consultation'
    ) {
        error_log(
            'UG Consultation: reCAPTCHA failed. IP: ' . $_SERVER['REMOTE_ADDR'] .
            ' | success: ' . ( isset($result['success']) ? var_export($result['success'], true) : 'n/a' ) .
            ' | score: ' . ( isset($result['score']) ? $result['score'] : 'n/a' ) .
            ' | action: ' . ( isset($result['action']) ? $result['action'] : 'n/a' ) .
            ' | error-codes: ' . ( isset($result['error-codes']) ? implode(',', $result['error-codes']) : 'none' )
        );
        wp_safe_redirect(home_url('/college/free-consultation/?error=recaptcha'));
        exit;
    }

    /**
     * Sanitize Form Data
     * JSON field names follow the mapping in the Podio field spec sheet.
     */
    $qualifications = isset($_POST['high_school_qualifications']) && is_array($_POST['high_school_qualifications'])
        ? array_map('sanitize_text_field', $_POST['high_school_qualifications'])
        : array();

    $discovery = isset($_POST['fortuna_discovery']) && is_array($_POST['fortuna_discovery'])
        ? array_map('sanitize_text_field', $_POST['fortuna_discovery'])
        : array();

    $data = array(
        'IAmA'                             => sanitize_text_field($_POST['i_am_a']),
        'student_first'                    => sanitize_text_field($_POST['student_first_name']),
        'student_last'                     => sanitize_text_field($_POST['student_last_name']),
        'student_email'                    => sanitize_email($_POST['student_email']),
        'student_phone'                    => sanitize_text_field($_POST['student_phone']),
        'student_sms'                      => isset($_POST['student_sms']) ? 'yes' : 'no',
        'parent_first'                     => sanitize_text_field($_POST['parent_first_name']),
        'parent_last'                      => sanitize_text_field($_POST['parent_last_name']),
        'parent_email'                     => sanitize_email($_POST['parent_email']),
        'parent_phone'                     => sanitize_text_field($_POST['parent_phone']),
        'parent_sms'                       => isset($_POST['parent_sms']) ? 'yes' : 'no',
        'country_of_residence'             => sanitize_text_field($_POST['country_of_residence']),
        'high_school'                      => sanitize_text_field($_POST['high_school_name']),
        'high_school_location'             => sanitize_text_field($_POST['high_school_location']),
        'high_school_grad'                 => sanitize_text_field($_POST['high_school_graduation_year']),
        'high_school_qualifications'       => implode(', ', $qualifications),
        'how_did_you_hear'                 => implode(', ', $discovery),
        'code'                             => sanitize_text_field($_POST['code']),
    );

    /**
     * Save to WordPress (backup layer)
     */
    $submission_id = fa_save_form_submission( 'ug', $data );

    /**
     * Send to Podio
     */
    $response = wp_remote_post(
        'https://workflow-automation.podio.com/catch/9f1tz0gd5c4d791',
        array(
            'method'  => 'POST',
            'body'    => $data,
            'timeout' => 30,
        )
    );

    if ( $submission_id ) {
        if ( is_wp_error( $response ) ) {
            fa_update_podio_status( $submission_id, 'failed', $response->get_error_message() );
            error_log( 'UG Consultation: Podio webhook error: ' . $response->get_error_message() );
        } else {
            $podio_code = wp_remote_retrieve_response_code( $response );
            if ( $podio_code >= 200 && $podio_code < 300 ) {
                fa_update_podio_status( $submission_id, 'success' );
            } else {
                fa_update_podio_status( $submission_id, 'failed', 'HTTP ' . $podio_code );
                error_log( 'UG Consultation: Podio webhook HTTP ' . $podio_code . ' | body: ' . wp_remote_retrieve_body( $response ) );
            }
        }
    }

    /**
     * Redirect
     */
    wp_safe_redirect(home_url('/college/free-consultation-thank-you/'));
    exit;
}

add_action(
    'wp_ajax_nopriv_mim_consultation_submit',
    'mim_consultation_submit'
);

add_action(
    'wp_ajax_mim_consultation_submit',
    'mim_consultation_submit'
);

function mim_consultation_submit() {

    if (
        ! isset($_POST['mim_consultation_nonce_field']) ||
        ! wp_verify_nonce(
            sanitize_text_field(
                wp_unslash($_POST['mim_consultation_nonce_field'])
            ),
            'mim_consultation_nonce'
        )
    ) {
        wp_send_json_error(
            array(
                'message' => 'Security verification failed. Please refresh the page and try again.',
            ),
            403
        );
    }

    if ( ! empty($_POST['website']) ) {
        wp_send_json_error(
            array(
                'message' => 'Spam submission detected.',
            ),
            400
        );
    }

    $first_name = isset($_POST['first_name'])
        ? sanitize_text_field(wp_unslash($_POST['first_name']))
        : '';

    $last_name = isset($_POST['last_name'])
        ? sanitize_text_field(wp_unslash($_POST['last_name']))
        : '';

    $email = isset($_POST['email'])
        ? sanitize_email(wp_unslash($_POST['email']))
        : '';

    $phone = isset($_POST['phone'])
        ? sanitize_text_field(wp_unslash($_POST['phone']))
        : '';

    $sms_consent = isset($_POST['sms_consent'])
        ? sanitize_text_field(wp_unslash($_POST['sms_consent']))
        : '';

    $hear_about_us = isset($_POST['hear_about_us'])
        ? sanitize_text_field(wp_unslash($_POST['hear_about_us']))
        : '';

    $linkedin_url = isset($_POST['linkedin_url'])
        ? esc_url_raw(wp_unslash($_POST['linkedin_url']))
        : '';

    $additional_information = isset($_POST['additional_information'])
        ? sanitize_textarea_field(
            wp_unslash($_POST['additional_information'])
        )
        : '';
  $code = isset($_POST['code'])
    ? sanitize_text_field(wp_unslash($_POST['code']))
    : '';

    if (
        empty($first_name) ||
        empty($last_name) ||
        empty($email) ||
        empty($hear_about_us) ||
        empty($linkedin_url) ||
  		empty($code)
    ) {
        wp_send_json_error(
            array(
                'message' => 'Please complete all required fields.',
            ),
            400
        );
    }

    if ( ! is_email($email) ) {
        wp_send_json_error(
            array(
                'message' => 'Please enter a valid email address.',
            ),
            400
        );
    }

    $resume_url = '';

    if (
        isset($_FILES['resume']) &&
        ! empty($_FILES['resume']['name'])
    ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';

        $upload_overrides = array(
            'test_form' => false,
            'mimes' => array(
                'pdf'  => 'application/pdf',
                'doc'  => 'application/msword',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ),
        );

        $uploaded_file = wp_handle_upload(
            $_FILES['resume'],
            $upload_overrides
        );

        if ( isset($uploaded_file['error']) ) {
            wp_send_json_error(
                array(
                    'message' => 'Resume upload failed. Please try again.',
                ),
                400
            );
        }

        if ( ! empty($uploaded_file['url']) ) {
            $resume_url = esc_url_raw($uploaded_file['url']);
        }
    }

    $payload = array(
        'code'                   => $code,
        'first_name'             => $first_name,
        'last_name'              => $last_name,
        'email'                  => $email,
        'phone'                  => $phone,
        'sms_consent'            => $sms_consent,
        'hear_about_us'          => $hear_about_us,
        'linkedin_url'           => $linkedin_url,
        'resume_url'             => $resume_url,
        'additional_information' => $additional_information,
    );

    /**
     * Save to WordPress first (backup layer) — data is safe regardless of Podio outcome.
     */
    $submission_id = fa_save_form_submission( 'mim', $payload );

    /**
     * Send to Podio
     */
    $webhook_response = wp_remote_post(
        'https://workflow-automation.podio.com/catch/9v0j9mmpvn0r3k9',
        array(
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ),
            'body' => wp_json_encode( $payload ),
        )
    );

    if ( $submission_id ) {
        if ( is_wp_error( $webhook_response ) ) {
            fa_update_podio_status( $submission_id, 'failed', $webhook_response->get_error_message() );
            error_log( 'MiM Consultation Webhook Error: ' . $webhook_response->get_error_message() );
        } else {
            $response_code = wp_remote_retrieve_response_code( $webhook_response );
            if ( $response_code >= 200 && $response_code < 300 ) {
                fa_update_podio_status( $submission_id, 'success' );
            } else {
                fa_update_podio_status( $submission_id, 'failed', 'HTTP ' . $response_code );
                error_log( 'MiM Consultation Webhook HTTP Error: ' . $response_code );
                error_log( 'MiM Consultation Webhook Response: ' . wp_remote_retrieve_body( $webhook_response ) );
            }
        }
    }

    // Always return success — data is captured in WordPress even if Podio was unreachable.
    wp_send_json_success(
        array(
            'message' => 'Thanks for sharing this very helpful background information, which will be invaluable for our call together. We will be in touch soon.',
        )
    );
}