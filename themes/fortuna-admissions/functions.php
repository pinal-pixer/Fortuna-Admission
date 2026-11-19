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
require_once('inc/ab-testing.php');
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

add_shortcode('mba_simple_free_consultation_form', 'mba_simple_free_consultation_form_shortcode');

function mba_simple_free_consultation_form_shortcode() {

    ob_start();

    $form_file = get_stylesheet_directory() . '/inc/forms/mba-simple-free-consultation-form.php';

    if ( file_exists( $form_file ) ) {
        require $form_file;
    }

    return ob_get_clean();
}

add_shortcode('mba_full_free_consultation_form', 'mba_full_free_consultation_form_shortcode');

function mba_full_free_consultation_form_shortcode() {

    ob_start();

    $form_file = get_stylesheet_directory() . '/inc/forms/mba-full-free-consultation-form.php';

    if ( file_exists( $form_file ) ) {
        require $form_file;
    }

    return ob_get_clean();
}

/*
 * ============================================================================
 * MBA A/B STAGING SHORTCODES
 * ----------------------------------------------------------------------------
 * Parallel to the live shortcodes above, served from *-ab.php files so the
 * live flow stays untouched. Use these on the staging page only:
 *   [mba_simple_free_consultation_form_ab]   ← Step 1 (staging)
 *   [mba_full_free_consultation_form_ab]     ← Step 2 (staging)
 * ============================================================================
 */

add_shortcode('mba_simple_free_consultation_form_ab', 'mba_simple_free_consultation_form_ab_shortcode');

function mba_simple_free_consultation_form_ab_shortcode() {

    ob_start();

    $form_file = get_stylesheet_directory() . '/inc/forms/mba-simple-free-consultation-form-ab.php';

    if ( file_exists( $form_file ) ) {
        require $form_file;
    }

    return ob_get_clean();
}

add_shortcode('mba_full_free_consultation_form_ab', 'mba_full_free_consultation_form_ab_shortcode');

function mba_full_free_consultation_form_ab_shortcode() {

    ob_start();

    $form_file = get_stylesheet_directory() . '/inc/forms/mba-full-free-consultation-form-ab.php';

    if ( file_exists( $form_file ) ) {
        require $form_file;
    }

    return ob_get_clean();
}

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

/**
 * Output the Ortto fortuna capture code in <head> on every front-end page.
 *
 * The script must run immediately on page load (not on user interaction) so
 * Ortto's tracked-form check can detect it. The data-no-defer / data-no-minify
 * / data-no-optimize attributes, together with the wphb_delay_js_exclusions
 * filter below, keep WP Hummingbird's Delay JS feature from rewriting the tag
 * to type="wphb-delay-type".
 */
function fa_ortto_capture_head() {

    if ( is_admin() ) {
        return;
    }
    ?>
    <!-- Ortto fortuna capture code -->
    <script id="ortto-ap3c-init" type="text/javascript" data-no-defer="1" data-no-minify="1" data-no-optimize="1" data-cfasync="false">
    window.ap3c = window.ap3c || {};
    var ap3c = window.ap3c;
    ap3c.cmd = ap3c.cmd || [];
    ap3c.cmd.push(function() {
        ap3c.init('Y5mSy2h04G7O6FQAZm9ydHVuYQ', 'https://capture-api-us.ortto.app/');
        ap3c.track({v: 0});
    });
    ap3c.activity = function(act) { ap3c.act = (ap3c.act || []); ap3c.act.push(act); };
    var s, t; s = document.createElement('script'); s.type = 'text/javascript'; s.src = "https://cdn3l.ink/app.js"; s.setAttribute('data-no-defer', '1'); s.setAttribute('data-cfasync', 'false');
    t = document.getElementsByTagName('script')[0]; t.parentNode.insertBefore(s, t);
    </script>
    <?php
}
add_action('wp_head', 'fa_ortto_capture_head', 1);

/**
 * Exclude the Ortto capture scripts from WP Hummingbird's Delay JS feature.
 * Without this, Hummingbird rewrites the script type to "wphb-delay-type",
 * which blocks execution until the first user interaction and causes Ortto
 * to report the tracking script as missing.
 */
function fa_ortto_hummingbird_delay_js_exclusions( $exclusions ) {

    $ortto_exclusions = array(
        'ap3c',
        'cdn3l.ink',
        'capture-api-us.ortto.app',
        'ortto-ap3c-init',
    );

    if ( is_array( $exclusions ) ) {
        return array_merge( $exclusions, $ortto_exclusions );
    }

    return $ortto_exclusions;
}
add_filter( 'wphb_delay_js_exclusions', 'fa_ortto_hummingbird_delay_js_exclusions' );
add_filter( 'wphb_minify_resource_exclusions', 'fa_ortto_hummingbird_delay_js_exclusions' );

add_action('admin_post_nopriv_law_consultation_submit', 'law_consultation_submit');
add_action('admin_post_law_consultation_submit', 'law_consultation_submit');

function law_consultation_submit() {

    if ( ! isset($_POST['law_consultation_submit']) ) {
        return;
    }

    $raw_email = isset($_POST['email']) ? sanitize_email( wp_unslash($_POST['email']) ) : '';
    $raw_first = isset($_POST['first_name']) ? sanitize_text_field( wp_unslash($_POST['first_name']) ) : '';

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
        fa_log_form_error(
            'law', 'single', 'nonce_failed',
            'Nonce verification failed — likely a cached page or expired session.',
            array( 'first_name' => $raw_first, 'email' => $raw_email )
        );
        wp_die('Security verification failed.');
    }

    /**
     * Honeypot Spam Check
     */
    if ( ! empty($_POST['website']) ) {
        fa_log_form_error(
            'law', 'single', 'honeypot_triggered',
            'Honeypot field "website" was filled — likely bot submission.',
            array( 'first_name' => $raw_first, 'email' => $raw_email )
        );
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
        fa_log_form_error(
            'law', 'single', 'recaptcha_unreachable',
            'Unable to reach Google reCAPTCHA: ' . $verify->get_error_message(),
            array( 'first_name' => $raw_first, 'email' => $raw_email )
        );
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
        fa_log_form_error(
            'law', 'single', 'recaptcha_failed',
            'reCAPTCHA failed — score=' . ($result['score'] ?? 'n/a') . ' action=' . ($result['action'] ?? 'n/a'),
            array( 'first_name' => $raw_first, 'email' => $raw_email )
        );
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
    if ( $submission_id ) {
        update_post_meta( $submission_id, '_fa_form_stage', 'single' );
    }

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

    $raw_email = isset($_POST['student_email']) ? sanitize_email( wp_unslash($_POST['student_email']) ) : '';
    $raw_first = isset($_POST['student_first_name']) ? sanitize_text_field( wp_unslash($_POST['student_first_name']) ) : '';

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
        fa_log_form_error(
            'ug', 'single', 'nonce_failed',
            'Nonce verification failed — likely a cached page or expired session.',
            array( 'student_first' => $raw_first, 'student_email' => $raw_email )
        );
        wp_safe_redirect(home_url('/college/free-consultation/?error=security'));
        exit;
    }

    /**
     * Honeypot Spam Check
     */
    if ( ! empty($_POST['website']) ) {
        fa_log_form_error(
            'ug', 'single', 'honeypot_triggered',
            'Honeypot field "website" was filled — likely bot submission.',
            array( 'student_first' => $raw_first, 'student_email' => $raw_email )
        );
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
        fa_log_form_error(
            'ug', 'single', 'recaptcha_unreachable',
            'Unable to reach Google reCAPTCHA: ' . $verify->get_error_message(),
            array( 'student_first' => $raw_first, 'student_email' => $raw_email )
        );
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
        fa_log_form_error(
            'ug', 'single', 'recaptcha_failed',
            'reCAPTCHA failed — score=' . ($result['score'] ?? 'n/a') . ' action=' . ($result['action'] ?? 'n/a') . ' codes=' . ( isset($result['error-codes']) ? implode(',', $result['error-codes']) : 'none' ),
            array( 'student_first' => $raw_first, 'student_email' => $raw_email )
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
        'student_sms'                      => isset($_POST['student_sms']) ? 'Yes' : 'No',
        'parent_first'                     => sanitize_text_field($_POST['parent_first_name']),
        'parent_last'                      => sanitize_text_field($_POST['parent_last_name']),
        'parent_email'                     => sanitize_email($_POST['parent_email']),
        'parent_phone'                     => sanitize_text_field($_POST['parent_phone']),
        'parent_sms'                       => isset($_POST['parent_sms']) ? 'Yes' : 'No',
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
    if ( $submission_id ) {
        update_post_meta( $submission_id, '_fa_form_stage', 'single' );
    }

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
    'admin_post_nopriv_mim_consultation_submit',
    'mim_consultation_submit'
);

add_action(
    'admin_post_mim_consultation_submit',
    'mim_consultation_submit'
);

function mim_consultation_submit() {

    if ( ! isset($_POST['mim_consultation_submit']) ) {
        return;
    }

    $raw_email = isset($_POST['email']) ? sanitize_email( wp_unslash($_POST['email']) ) : '';
    $raw_first = isset($_POST['first_name']) ? sanitize_text_field( wp_unslash($_POST['first_name']) ) : '';

    /**
     * ============================================
     * NONCE VERIFICATION
     * ============================================
     */
    if (
        ! isset($_POST['mim_consultation_nonce_field']) ||
        ! wp_verify_nonce(
            sanitize_text_field(
                wp_unslash($_POST['mim_consultation_nonce_field'])
            ),
            'mim_consultation_nonce'
        )
    ) {
        fa_log_form_error(
            'mim', 'single', 'nonce_failed',
            'Nonce verification failed — likely a cached page or expired session.',
            array( 'first_name' => $raw_first, 'email' => $raw_email )
        );
        wp_die('Security verification failed.');
    }

    /**
     * ============================================
     * HONEYPOT / SPAM CHECK
     * ============================================
     */
    if ( ! empty($_POST['website']) ) {
        fa_log_form_error(
            'mim', 'single', 'honeypot_triggered',
            'Honeypot field "website" was filled — likely bot submission.',
            array( 'first_name' => $raw_first, 'email' => $raw_email )
        );
        wp_die('Spam detected.');
    }

    /**
     * ============================================
     * GET FORM VALUES
     * ============================================
     */

    $first_name = isset($_POST['first_name'])
        ? sanitize_text_field(
            wp_unslash($_POST['first_name'])
        )
        : '';

    $last_name = isset($_POST['last_name'])
        ? sanitize_text_field(
            wp_unslash($_POST['last_name'])
        )
        : '';

    $email = isset($_POST['email'])
        ? sanitize_email(
            wp_unslash($_POST['email'])
        )
        : '';

    $phone = isset($_POST['phone'])
        ? sanitize_text_field(
            wp_unslash($_POST['phone'])
        )
        : '';

    $sms_consent = isset($_POST['sms_consent'])
        ? sanitize_text_field(
            wp_unslash($_POST['sms_consent'])
        )
        : '';

    $hear_about_us = isset($_POST['hear_about_us'])
        ? sanitize_text_field(
            wp_unslash($_POST['hear_about_us'])
        )
        : '';

    $linkedin_url = isset($_POST['linkedin_url'])
        ? esc_url_raw(
            wp_unslash($_POST['linkedin_url'])
        )
        : '';

    $additional_information = isset($_POST['additional_information'])
        ? sanitize_textarea_field(
            wp_unslash($_POST['additional_information'])
        )
        : '';

    $code = isset($_POST['code'])
        ? sanitize_text_field(
            wp_unslash($_POST['code'])
        )
        : '';

    /**
     * ============================================
     * REQUIRED FIELD VALIDATION
     * ============================================
     */
    if (
        empty($first_name) ||
        empty($last_name) ||
        empty($email) ||
        empty($hear_about_us) ||
        empty($linkedin_url) ||
        empty($code)
    ) {
        fa_log_form_error(
            'mim', 'single', 'missing_required',
            'Required field(s) missing: ' . implode(', ', array_filter(array(
                empty($first_name) ? 'first_name' : null,
                empty($last_name) ? 'last_name' : null,
                empty($email) ? 'email' : null,
                empty($hear_about_us) ? 'hear_about_us' : null,
                empty($linkedin_url) ? 'linkedin_url' : null,
                empty($code) ? 'code' : null,
            ))),
            array( 'first_name' => $first_name, 'last_name' => $last_name, 'email' => $email, 'linkedin_url' => $linkedin_url )
        );
        wp_die('Please complete all required fields.');
    }

    /**
     * ============================================
     * EMAIL VALIDATION
     * ============================================
     */
    if ( ! is_email($email) ) {
        fa_log_form_error(
            'mim', 'single', 'invalid_email',
            'Submitted email failed is_email() validation.',
            array( 'first_name' => $first_name, 'email' => $email )
        );
        wp_die('Please enter a valid email address.');
    }

    /**
     * ============================================
     * RESUME UPLOAD
     * ============================================
     */
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
            fa_log_form_error(
                'mim', 'single', 'resume_upload_failed',
                'Resume upload failed: ' . (string) $uploaded_file['error'],
                array( 'first_name' => $first_name, 'email' => $email )
            );
            wp_die('Resume upload failed. Please try again.');
        }

        if ( ! empty($uploaded_file['url']) ) {
            $resume_url = esc_url_raw(
                $uploaded_file['url']
            );
        }
    }

    /**
     * ============================================
     * PODIO / BACKUP PAYLOAD
     * ============================================
     */
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
     * ============================================
     * SAVE TO WORDPRESS FIRST
     * ============================================
     */
    $submission_id = fa_save_form_submission(
        'mim',
        $payload
    );
    if ( $submission_id ) {
        update_post_meta( $submission_id, '_fa_form_stage', 'single' );
    }

    /**
     * ============================================
     * SEND TO PODIO WEBHOOK
     * ============================================
     */
    $webhook_response = wp_remote_post(
        'https://workflow-automation.podio.com/catch/9v0j9mmpvn0r3k9',
        array(
            'method'  => 'POST',
            'body'    => $payload,
            'timeout' => 30,
        )
    );

    /**
     * ============================================
     * PODIO RESPONSE HANDLING
     * ============================================
     */
    if ( $submission_id ) {

        if ( is_wp_error($webhook_response) ) {

            $error_message = $webhook_response->get_error_message();

            fa_update_podio_status(
                $submission_id,
                'failed',
                $error_message
            );

            error_log(
                'MiM Consultation Webhook Error: ' .
                $error_message
            );

        } else {

            $response_code = wp_remote_retrieve_response_code(
                $webhook_response
            );

            $response_body = wp_remote_retrieve_body(
                $webhook_response
            );

            if (
                $response_code >= 200 &&
                $response_code < 300
            ) {

                fa_update_podio_status(
                    $submission_id,
                    'success'
                );

                /**
                 * Debug logging
                 */
                error_log(
                    'MiM Consultation Webhook SUCCESS. HTTP: ' .
                    $response_code
                );

                error_log(
                    'MiM Consultation Event: ' .
                    $event_name
                );

            } else {

                fa_update_podio_status(
                    $submission_id,
                    'failed',
                    'HTTP ' . $response_code
                );

                error_log(
                    'MiM Consultation Webhook HTTP Error: ' .
                    $response_code
                );

                error_log(
                    'MiM Consultation Webhook Response: ' .
                    $response_body
                );
            }
        }
    }

    /**
     * ============================================
     * REDIRECT TO THANK YOU PAGE
     * ============================================
     */
    wp_safe_redirect(home_url('/free-consultation-thank-you'));
    exit;
}

/**
 * Send a JSON response to the browser and close the connection, so any work
 * that follows (e.g. the Podio webhook call) runs without the user waiting.
 * On PHP-FPM this uses fastcgi_finish_request(); other SAPIs get a best-effort
 * flush and still benefit from PHP finishing the request in the background.
 */
if ( ! function_exists( 'fa_send_json_and_continue' ) ) {
    function fa_send_json_and_continue( $success, $payload = array() ) {

        $body = array(
            'success' => (bool) $success,
            'data'    => $payload,
        );
        $json = wp_json_encode( $body );

        // Drop any existing output buffers so the browser sees the JSON now.
        while ( ob_get_level() > 0 ) {
            @ob_end_clean();
        }

        nocache_headers();
        header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
        header( 'Content-Length: ' . strlen( $json ) );
        header( 'Connection: close' );
        echo $json;

        if ( function_exists( 'fastcgi_finish_request' ) ) {
            fastcgi_finish_request();
        } elseif ( function_exists( 'litespeed_finish_request' ) ) {
            litespeed_finish_request();
        } else {
            if ( function_exists( 'session_write_close' ) ) {
                @session_write_close();
            }
            @flush();
        }

        // Keep processing beyond the client disconnect so the webhook completes.
        @ignore_user_abort( true );
    }
}

/**
 * ============================================================================
 * MBA — SIMPLE Free Consultation Form (Form 1 / partial submission)
 * ----------------------------------------------------------------------------
 * Sent via AJAX when the user submits Form 1, before they are redirected to
 * Form 2. Fires the Podio webhook so we capture the lead even if the user
 * abandons Form 2. Form 2 will fire another webhook call with the fuller
 * payload when it is submitted.
 * ============================================================================
 */
add_action('wp_ajax_nopriv_mba_simple_consultation_submit', 'mba_simple_consultation_submit');
add_action('wp_ajax_mba_simple_consultation_submit', 'mba_simple_consultation_submit');

function mba_simple_consultation_submit() {

    $raw_email = isset($_POST['email']) ? sanitize_email( wp_unslash($_POST['email']) ) : '';
    $raw_first = isset($_POST['first_name']) ? sanitize_text_field( wp_unslash($_POST['first_name']) ) : '';

    if (
        ! isset($_POST['mba_simple_consultation_nonce_field']) ||
        ! wp_verify_nonce(
            sanitize_text_field( wp_unslash($_POST['mba_simple_consultation_nonce_field']) ),
            'mba_simple_consultation_nonce'
        )
    ) {
        fa_log_form_error(
            'mba', 'step1', 'nonce_failed',
            'Nonce verification failed — likely a cached page or expired session.',
            array( 'first_name' => $raw_first, 'email' => $raw_email )
        );
        wp_send_json_error(
            array( 'message' => 'Security verification failed.' ),
            403
        );
    }

    $first_name = $raw_first;

    $last_name = isset($_POST['last_name'])
        ? sanitize_text_field( wp_unslash($_POST['last_name']) )
        : '';

    $email = $raw_email;

    if ( empty($first_name) || empty($email) || ! is_email($email) ) {
        fa_log_form_error(
            'mba', 'step1', 'missing_required',
            'Step 1 submitted without required first_name or valid email.',
            array( 'first_name' => $first_name, 'email' => $email, 'last_name' => $last_name )
        );
        wp_send_json_error(
            array( 'message' => 'Missing required fields.' ),
            400
        );
    }

    $phone = isset($_POST['phone'])
        ? sanitize_text_field( wp_unslash($_POST['phone']) )
        : '';

    $sms_consent = isset($_POST['sms_consent']) && $_POST['sms_consent'] === 'Yes'
        ? 'Yes'
        : 'No';

    $country = isset($_POST['country'])
        ? sanitize_text_field( wp_unslash($_POST['country']) )
        : '';

    $linkedin_url = isset($_POST['linkedin'])
        ? esc_url_raw( wp_unslash($_POST['linkedin']) )
        : '';

    $hear_about_us = isset($_POST['hear_about_us'])
        ? sanitize_text_field( wp_unslash($_POST['hear_about_us']) )
        : '';

    $additional_information = isset($_POST['additional_information'])
        ? sanitize_textarea_field( wp_unslash($_POST['additional_information']) )
        : '';
  
  	$code = isset($_POST['code'])
        ? sanitize_text_field( wp_unslash($_POST['code']) )
        : '';

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

        if ( ! empty($uploaded_file['error']) ) {
            fa_log_form_error(
                'mba', 'step1', 'resume_upload_failed',
                'Resume upload failed during Step 1: ' . (string) $uploaded_file['error'],
                array( 'first_name' => $first_name, 'email' => $email )
            );
        } elseif ( ! empty($uploaded_file['url']) ) {
            $resume_url = esc_url_raw( $uploaded_file['url'] );
        }
    }

    $data = array(
        'first_name'             => $first_name,
        'last_name'              => $last_name,
        'email'                  => $email,
        'phone'                  => $phone,
        'sms_consent'            => $sms_consent,
        'country'                => $country,
        'linkedin_url'           => $linkedin_url,
        'hear_about_us'          => $hear_about_us,
        'additional_information' => $additional_information,
        'resume_url'             => $resume_url,
        'form_stage'             => 'partial',
  		'code'                   => $code,
    );

    $submission_id = fa_save_form_submission( 'mba', $data );
    if ( $submission_id ) {
        update_post_meta( $submission_id, '_fa_form_stage', 'step1' );
    }

    // Respond to the browser first so the redirect to Form 2 isn't blocked
    // on the Podio round-trip. The webhook call below runs after the client
    // is disconnected.
    fa_send_json_and_continue( true, array( 'message' => 'Partial submission received.' ) );

    /**
     * Send to Podio (runs after response is sent to the browser)
     */
    $response = wp_remote_post(
        'https://workflow-automation.podio.com/catch/y73oog24iwh3c7g',
        array(
            'method'  => 'POST',
            'body'    => $data,
            'timeout' => 30,
        )
    );

    if ( $submission_id ) {
        if ( is_wp_error( $response ) ) {
            fa_update_podio_status( $submission_id, 'failed', $response->get_error_message() );
            error_log( 'MBA Simple Consultation: Podio webhook error: ' . $response->get_error_message() );
        } else {
            $podio_code = wp_remote_retrieve_response_code( $response );
            if ( $podio_code >= 200 && $podio_code < 300 ) {
                fa_update_podio_status( $submission_id, 'success' );
            } else {
                fa_update_podio_status( $submission_id, 'failed', 'HTTP ' . $podio_code );
                error_log( 'MBA Simple Consultation: Podio webhook HTTP ' . $podio_code . ' | body: ' . wp_remote_retrieve_body( $response ) );
            }
        }
    }

    exit;
}

/**
 * ============================================================================
 * MBA — FULL Free Consultation Form
 * ----------------------------------------------------------------------------
 * Longer form (Name, Email, Phone, SMS consent, How heard, LinkedIn,
 * Resume upload, Additional info). Uses AJAX because of the resume file
 * upload — returns JSON success/error to the front-end.
 * ============================================================================
 */
add_action('wp_ajax_nopriv_mba_full_consultation_submit', 'mba_full_consultation_submit');
add_action('wp_ajax_mba_full_consultation_submit', 'mba_full_consultation_submit');

function mba_full_consultation_submit() {

    $raw_email = isset($_POST['email']) ? sanitize_email( wp_unslash($_POST['email']) ) : '';
    $raw_first = isset($_POST['first_name']) ? sanitize_text_field( wp_unslash($_POST['first_name']) ) : '';

    if (
        ! isset($_POST['mba_full_consultation_nonce_field']) ||
        ! wp_verify_nonce(
            sanitize_text_field( wp_unslash($_POST['mba_full_consultation_nonce_field']) ),
            'mba_full_consultation_nonce'
        )
    ) {
        fa_log_form_error(
            'mba', 'step2', 'nonce_failed',
            'Nonce verification failed on Step 2 — likely a cached page or expired session.',
            array( 'first_name' => $raw_first, 'email' => $raw_email )
        );
        wp_send_json_error(
            array( 'message' => 'Security verification failed. Please refresh the page and try again.' ),
            403
        );
    }

    $first_name = $raw_first;

    $last_name = isset($_POST['last_name'])
        ? sanitize_text_field( wp_unslash($_POST['last_name']) )
        : '';

    $email = $raw_email;

    $phone = isset($_POST['phone'])
        ? sanitize_text_field( wp_unslash($_POST['phone']) )
        : '';

  	$hear_about_us = isset($_POST['hear_about_us'])
        ? sanitize_text_field( wp_unslash($_POST['hear_about_us']) )
        : '';

    if ( empty($first_name) || empty($email) ) {
        fa_log_form_error(
            'mba', 'step2', 'missing_step1_data',
            'Step 2 submitted without first_name/email from Step 1 — likely a refresh or direct-navigation case.',
            array( 'first_name' => $first_name, 'email' => $email )
        );
        wp_send_json_error(
            array( 'message' => 'Your contact details from Step 1 are missing. Please return to the first page and start again.' ),
            400
        );
    }

    if ( ! is_email($email) ) {
        fa_log_form_error(
            'mba', 'step2', 'invalid_email',
            'Step 2 submitted with an invalid email address.',
            array( 'first_name' => $first_name, 'email' => $email )
        );
        wp_send_json_error(
            array( 'message' => 'Please enter a valid email address.' ),
            400
        );
    }

    $sms_consent = isset($_POST['sms_consent']) && $_POST['sms_consent'] === 'Yes'
        ? 'Yes'
        : 'No';

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
            fa_log_form_error(
                'mba', 'step2', 'resume_upload_failed',
                'Resume upload failed during Step 2: ' . (string) $uploaded_file['error'],
                array( 'first_name' => $first_name, 'email' => $email )
            );
            wp_send_json_error(
                array( 'message' => 'Resume upload failed. Please try again.' ),
                400
            );
        }

        if ( ! empty($uploaded_file['url']) ) {
            $resume_url = esc_url_raw( $uploaded_file['url'] );
        }
    }

    $country = isset($_POST['country'])
        ? sanitize_text_field( wp_unslash($_POST['country']) )
        : '';

    $referred_by_name = isset($_POST['referred_by_name'])
        ? sanitize_text_field( wp_unslash($_POST['referred_by_name']) )
        : '';

    $current_employer = isset($_POST['current_employer'])
        ? sanitize_text_field( wp_unslash($_POST['current_employer']) )
        : '';

    $job_title_function = isset($_POST['job_title_function'])
        ? sanitize_text_field( wp_unslash($_POST['job_title_function']) )
        : '';

    $current_industry = isset($_POST['current_industry'])
        ? sanitize_text_field( wp_unslash($_POST['current_industry']) )
        : '';

    $undergrad_university = isset($_POST['undergrad_university'])
        ? sanitize_text_field( wp_unslash($_POST['undergrad_university']) )
        : '';

    $degree_major = isset($_POST['degree_major'])
        ? sanitize_text_field( wp_unslash($_POST['degree_major']) )
        : '';

    $gpa = isset($_POST['gpa'])
        ? sanitize_text_field( wp_unslash($_POST['gpa']) )
        : '';

    $graduation_year = isset($_POST['graduation_year'])
        ? sanitize_text_field( wp_unslash($_POST['graduation_year']) )
        : '';

    $tests_taken = isset($_POST['tests_taken'])
        ? sanitize_text_field( wp_unslash($_POST['tests_taken']) )
        : '';

    $gmat_score = isset($_POST['gmat_score'])
        ? sanitize_text_field( wp_unslash($_POST['gmat_score']) )
        : '';

    $gmat_quant = isset($_POST['gmat_quant'])
        ? sanitize_text_field( wp_unslash($_POST['gmat_quant']) )
        : '';

    $gmat_verbal = isset($_POST['gmat_verbal'])
        ? sanitize_text_field( wp_unslash($_POST['gmat_verbal']) )
        : '';

    $gre_score = isset($_POST['gre_score'])
        ? sanitize_text_field( wp_unslash($_POST['gre_score']) )
        : '';

    $gre_quant = isset($_POST['gre_quant'])
        ? sanitize_text_field( wp_unslash($_POST['gre_quant']) )
        : '';

    $gre_verbal = isset($_POST['gre_verbal'])
        ? sanitize_text_field( wp_unslash($_POST['gre_verbal']) )
        : '';

    $business_schools = isset($_POST['business_schools']) && is_array($_POST['business_schools'])
        ? array_map( 'sanitize_text_field', wp_unslash($_POST['business_schools']) )
        : array();

    $mba_start_date = isset($_POST['mba_start_date'])
        ? sanitize_text_field( wp_unslash($_POST['mba_start_date']) )
        : '';

    $previously_applied_mba = isset($_POST['previously_applied_mba'])
        ? sanitize_text_field( wp_unslash($_POST['previously_applied_mba']) )
        : '';

    $fortuna_service = isset($_POST['fortuna_service'])
        ? sanitize_text_field( wp_unslash($_POST['fortuna_service']) )
        : '';
  	
  	$code = isset($_POST['code'])
        ? sanitize_text_field( wp_unslash($_POST['code']) )
        : '';

    $data = array(
        'first_name'             => $first_name,
        'last_name'              => $last_name,
        'email'                  => $email,
        'phone'                  => $phone,
        'sms_consent'            => $sms_consent,
        'resume_url'             => $resume_url,
        'country'                => $country,
  		'hear_about_us'          => $hear_about_us,
        'referred_by_name'       => $referred_by_name,
        'current_employer'       => $current_employer,
        'job_title_function'     => $job_title_function,
        'current_industry'       => $current_industry,
        'undergrad_university'   => $undergrad_university,
        'degree_major'           => $degree_major,
        'gpa'                    => $gpa,
        'graduation_year'        => $graduation_year,
        'tests_taken'            => $tests_taken,
        'gmat_score'             => $gmat_score,
        'gmat_quant'             => $gmat_quant,
        'gmat_verbal'            => $gmat_verbal,
        'gre_score'              => $gre_score,
        'gre_quant'              => $gre_quant,
        'gre_verbal'             => $gre_verbal,
        'business_schools'       => implode(', ', $business_schools),
        'mba_start_date'         => $mba_start_date,
        'previously_applied_mba' => $previously_applied_mba,
        'fortuna_service'        => $fortuna_service,
  		'code'                   => $code,
    );

    $submission_id = fa_save_form_submission( 'mba', $data );
    if ( $submission_id ) {
        update_post_meta( $submission_id, '_fa_form_stage', 'step2' );
    }

    // Respond to the browser first so the redirect to the thank-you page
    // isn't blocked on the Podio round-trip. The webhook call below runs
    // after the client is disconnected.
    fa_send_json_and_continue( true, array(
        'message' => 'Thanks for sharing this very helpful background information, which will be invaluable for our call together. We will be in touch soon.',
    ) );

    /**
     * Send to Podio (runs after response is sent to the browser)
     */
    $response = wp_remote_post(
        'https://workflow-automation.podio.com/catch/y73oog24iwh3c7g',
        array(
            'method'  => 'POST',
            'body'    => $data,
            'timeout' => 30,
        )
    );

    if ( $submission_id ) {
        if ( is_wp_error( $response ) ) {
            fa_update_podio_status( $submission_id, 'failed', $response->get_error_message() );
            error_log( 'MBA Full Consultation: Podio webhook error: ' . $response->get_error_message() );
        } else {
            $podio_code = wp_remote_retrieve_response_code( $response );
            if ( $podio_code >= 200 && $podio_code < 300 ) {
                fa_update_podio_status( $submission_id, 'success' );
            } else {
                fa_update_podio_status( $submission_id, 'failed', 'HTTP ' . $podio_code );
                error_log( 'MBA Full Consultation: Podio webhook HTTP ' . $podio_code . ' | body: ' . wp_remote_retrieve_body( $response ) );
            }
        }
    }

    exit;
}

/*
 * ============================================================================
 * MBA A/B STAGING — Form 1 & Form 2 handlers
 * ----------------------------------------------------------------------------
 * Parallel to the live handlers above. Only requests from the *-ab shortcodes
 * reach here. The live handlers are not touched. Podio webhook URL is
 * filterable (`fa_ab_podio_webhook_url`) in case staging should be isolated
 * from the production Podio pipeline.
 * ============================================================================
 */

add_action('wp_ajax_nopriv_mba_simple_consultation_submit_ab', 'mba_simple_consultation_submit_ab');
add_action('wp_ajax_mba_simple_consultation_submit_ab', 'mba_simple_consultation_submit_ab');

function mba_simple_consultation_submit_ab() {

    $raw_email = isset($_POST['email']) ? sanitize_email( wp_unslash($_POST['email']) ) : '';
    $raw_first = isset($_POST['first_name']) ? sanitize_text_field( wp_unslash($_POST['first_name']) ) : '';

    if (
        ! isset($_POST['mba_simple_consultation_nonce_field_ab']) ||
        ! wp_verify_nonce(
            sanitize_text_field( wp_unslash($_POST['mba_simple_consultation_nonce_field_ab']) ),
            'mba_simple_consultation_nonce_ab'
        )
    ) {
        fa_log_form_error(
            'mba', 'step1-ab', 'nonce_failed',
            'AB Step 1 nonce verification failed — likely a cached page or expired session.',
            array( 'first_name' => $raw_first, 'email' => $raw_email )
        );
        wp_send_json_error(
            array( 'message' => 'Security verification failed.' ),
            403
        );
    }

    $first_name = $raw_first;

    $last_name = isset($_POST['last_name'])
        ? sanitize_text_field( wp_unslash($_POST['last_name']) )
        : '';

    $email = $raw_email;

    if ( empty($first_name) || empty($email) || ! is_email($email) ) {
        fa_log_form_error(
            'mba', 'step1-ab', 'missing_required',
            'AB Step 1 submitted without required first_name or valid email.',
            array( 'first_name' => $first_name, 'email' => $email, 'last_name' => $last_name )
        );
        wp_send_json_error(
            array( 'message' => 'Missing required fields.' ),
            400
        );
    }

    $phone = isset($_POST['phone'])
        ? sanitize_text_field( wp_unslash($_POST['phone']) )
        : '';

    $sms_consent = isset($_POST['sms_consent']) && $_POST['sms_consent'] === 'Yes'
        ? 'Yes'
        : 'No';

    $country = isset($_POST['country'])
        ? sanitize_text_field( wp_unslash($_POST['country']) )
        : '';

    $linkedin_url = isset($_POST['linkedin'])
        ? esc_url_raw( wp_unslash($_POST['linkedin']) )
        : '';

    $hear_about_us = isset($_POST['hear_about_us'])
        ? sanitize_text_field( wp_unslash($_POST['hear_about_us']) )
        : '';

    $additional_information = isset($_POST['additional_information'])
        ? sanitize_textarea_field( wp_unslash($_POST['additional_information']) )
        : '';

    $code = isset($_POST['code'])
        ? sanitize_text_field( wp_unslash($_POST['code']) )
        : '';

    $ab_variation = isset($_POST['ab_variation'])
        ? fa_ab_sanitize_variation( wp_unslash($_POST['ab_variation']) )
        : 'A';

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

        if ( ! empty($uploaded_file['error']) ) {
            fa_log_form_error(
                'mba', 'step1-ab', 'resume_upload_failed',
                'AB Step 1 resume upload failed: ' . (string) $uploaded_file['error'],
                array( 'first_name' => $first_name, 'email' => $email )
            );
        } elseif ( ! empty($uploaded_file['url']) ) {
            $resume_url = esc_url_raw( $uploaded_file['url'] );
        }
    }

    $data = array(
        'first_name'             => $first_name,
        'last_name'              => $last_name,
        'email'                  => $email,
        'phone'                  => $phone,
        'sms_consent'            => $sms_consent,
        'country'                => $country,
        'linkedin_url'           => $linkedin_url,
        'hear_about_us'          => $hear_about_us,
        'additional_information' => $additional_information,
        'resume_url'             => $resume_url,
        'form_stage'             => 'partial',
        'code'                   => $code,
        'ab_variation'           => $ab_variation,
    );

    $submission_id = fa_save_form_submission( 'mba', $data );
    if ( $submission_id ) {
        update_post_meta( $submission_id, '_fa_form_stage', 'step1-ab' );
    }

    fa_send_json_and_continue( true, array( 'message' => 'Partial submission received.' ) );

    $podio_url = apply_filters(
        'fa_ab_podio_webhook_url',
        'https://workflow-automation.podio.com/catch/y73oog24iwh3c7g',
        'step1',
        $data
    );

    $response = wp_remote_post(
        $podio_url,
        array(
            'method'  => 'POST',
            'body'    => $data,
            'timeout' => 30,
        )
    );

    if ( $submission_id ) {
        if ( is_wp_error( $response ) ) {
            fa_update_podio_status( $submission_id, 'failed', $response->get_error_message() );
            error_log( 'MBA Simple Consultation (AB): Podio webhook error: ' . $response->get_error_message() );
        } else {
            $podio_code = wp_remote_retrieve_response_code( $response );
            if ( $podio_code >= 200 && $podio_code < 300 ) {
                fa_update_podio_status( $submission_id, 'success' );
            } else {
                fa_update_podio_status( $submission_id, 'failed', 'HTTP ' . $podio_code );
                error_log( 'MBA Simple Consultation (AB): Podio webhook HTTP ' . $podio_code . ' | body: ' . wp_remote_retrieve_body( $response ) );
            }
        }
    }

    exit;
}

add_action('wp_ajax_nopriv_mba_full_consultation_submit_ab', 'mba_full_consultation_submit_ab');
add_action('wp_ajax_mba_full_consultation_submit_ab', 'mba_full_consultation_submit_ab');

function mba_full_consultation_submit_ab() {

    $raw_email = isset($_POST['email']) ? sanitize_email( wp_unslash($_POST['email']) ) : '';
    $raw_first = isset($_POST['first_name']) ? sanitize_text_field( wp_unslash($_POST['first_name']) ) : '';

    if (
        ! isset($_POST['mba_full_consultation_nonce_field_ab']) ||
        ! wp_verify_nonce(
            sanitize_text_field( wp_unslash($_POST['mba_full_consultation_nonce_field_ab']) ),
            'mba_full_consultation_nonce_ab'
        )
    ) {
        fa_log_form_error(
            'mba', 'step2-ab', 'nonce_failed',
            'AB Step 2 nonce verification failed — likely a cached page or expired session.',
            array( 'first_name' => $raw_first, 'email' => $raw_email )
        );
        wp_send_json_error(
            array( 'message' => 'Security verification failed. Please refresh the page and try again.' ),
            403
        );
    }

    $first_name = $raw_first;

    $last_name = isset($_POST['last_name'])
        ? sanitize_text_field( wp_unslash($_POST['last_name']) )
        : '';

    $email = $raw_email;

    $phone = isset($_POST['phone'])
        ? sanitize_text_field( wp_unslash($_POST['phone']) )
        : '';

    $hear_about_us = isset($_POST['hear_about_us'])
        ? sanitize_text_field( wp_unslash($_POST['hear_about_us']) )
        : '';

    if ( empty($first_name) || empty($email) ) {
        fa_log_form_error(
            'mba', 'step2-ab', 'missing_step1_data',
            'AB Step 2 submitted without first_name/email from Step 1 — likely a refresh or direct-navigation case.',
            array( 'first_name' => $first_name, 'email' => $email )
        );
        wp_send_json_error(
            array( 'message' => 'Your contact details from Step 1 are missing. Please return to the first page and start again.' ),
            400
        );
    }

    if ( ! is_email($email) ) {
        fa_log_form_error(
            'mba', 'step2-ab', 'invalid_email',
            'AB Step 2 submitted with an invalid email address.',
            array( 'first_name' => $first_name, 'email' => $email )
        );
        wp_send_json_error(
            array( 'message' => 'Please enter a valid email address.' ),
            400
        );
    }

    $sms_consent = isset($_POST['sms_consent']) && $_POST['sms_consent'] === 'Yes'
        ? 'Yes'
        : 'No';

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
            fa_log_form_error(
                'mba', 'step2-ab', 'resume_upload_failed',
                'AB Step 2 resume upload failed: ' . (string) $uploaded_file['error'],
                array( 'first_name' => $first_name, 'email' => $email )
            );
            wp_send_json_error(
                array( 'message' => 'Resume upload failed. Please try again.' ),
                400
            );
        }

        if ( ! empty($uploaded_file['url']) ) {
            $resume_url = esc_url_raw( $uploaded_file['url'] );
        }
    }

    $country = isset($_POST['country'])
        ? sanitize_text_field( wp_unslash($_POST['country']) )
        : '';

    $referred_by_name = isset($_POST['referred_by_name'])
        ? sanitize_text_field( wp_unslash($_POST['referred_by_name']) )
        : '';

    $current_employer = isset($_POST['current_employer'])
        ? sanitize_text_field( wp_unslash($_POST['current_employer']) )
        : '';

    $job_title_function = isset($_POST['job_title_function'])
        ? sanitize_text_field( wp_unslash($_POST['job_title_function']) )
        : '';

    $current_industry = isset($_POST['current_industry'])
        ? sanitize_text_field( wp_unslash($_POST['current_industry']) )
        : '';

    $undergrad_university = isset($_POST['undergrad_university'])
        ? sanitize_text_field( wp_unslash($_POST['undergrad_university']) )
        : '';

    $degree_major = isset($_POST['degree_major'])
        ? sanitize_text_field( wp_unslash($_POST['degree_major']) )
        : '';

    $gpa = isset($_POST['gpa'])
        ? sanitize_text_field( wp_unslash($_POST['gpa']) )
        : '';

    $graduation_year = isset($_POST['graduation_year'])
        ? sanitize_text_field( wp_unslash($_POST['graduation_year']) )
        : '';

    $tests_taken = isset($_POST['tests_taken'])
        ? sanitize_text_field( wp_unslash($_POST['tests_taken']) )
        : '';

    $gmat_score = isset($_POST['gmat_score'])
        ? sanitize_text_field( wp_unslash($_POST['gmat_score']) )
        : '';

    $gmat_quant = isset($_POST['gmat_quant'])
        ? sanitize_text_field( wp_unslash($_POST['gmat_quant']) )
        : '';

    $gmat_verbal = isset($_POST['gmat_verbal'])
        ? sanitize_text_field( wp_unslash($_POST['gmat_verbal']) )
        : '';

    $gre_score = isset($_POST['gre_score'])
        ? sanitize_text_field( wp_unslash($_POST['gre_score']) )
        : '';

    $gre_quant = isset($_POST['gre_quant'])
        ? sanitize_text_field( wp_unslash($_POST['gre_quant']) )
        : '';

    $gre_verbal = isset($_POST['gre_verbal'])
        ? sanitize_text_field( wp_unslash($_POST['gre_verbal']) )
        : '';

    $business_schools = isset($_POST['business_schools']) && is_array($_POST['business_schools'])
        ? array_map( 'sanitize_text_field', wp_unslash($_POST['business_schools']) )
        : array();

    $mba_start_date = isset($_POST['mba_start_date'])
        ? sanitize_text_field( wp_unslash($_POST['mba_start_date']) )
        : '';

    $previously_applied_mba = isset($_POST['previously_applied_mba'])
        ? sanitize_text_field( wp_unslash($_POST['previously_applied_mba']) )
        : '';

    $fortuna_service = isset($_POST['fortuna_service'])
        ? sanitize_text_field( wp_unslash($_POST['fortuna_service']) )
        : '';

    $code = isset($_POST['code'])
        ? sanitize_text_field( wp_unslash($_POST['code']) )
        : '';

    $ab_variation = isset($_POST['ab_variation'])
        ? fa_ab_sanitize_variation( wp_unslash($_POST['ab_variation']) )
        : 'A';

    // Variation B: if the visitor's country is eligible, resolve the coach's
    // Appointlet URL so Podio sees who was routed and the front-end can
    // render the calendar in place of the standard thank-you redirect.
    // Everyone else falls through to the control flow (no calendar_url in the
    // response → JS redirects as before).
    $ab_coach_name   = '';
    $ab_calendar_url = '';

    if ( $ab_variation === 'B' && fa_ab_is_country_eligible( $country ) ) {
        $coach = fa_ab_get_coach_for_country( $country );
        if ( $coach && ! empty( $coach['appointlet_url'] ) ) {
            $ab_coach_name   = $coach['name'];
            $ab_calendar_url = fa_ab_build_booking_url(
                $coach['appointlet_url'],
                $first_name,
                $last_name,
                $email
            );
        }
    }

    $data = array(
        'first_name'             => $first_name,
        'last_name'              => $last_name,
        'email'                  => $email,
        'phone'                  => $phone,
        'sms_consent'            => $sms_consent,
        'resume_url'             => $resume_url,
        'country'                => $country,
        'hear_about_us'          => $hear_about_us,
        'referred_by_name'       => $referred_by_name,
        'current_employer'       => $current_employer,
        'job_title_function'     => $job_title_function,
        'current_industry'       => $current_industry,
        'undergrad_university'   => $undergrad_university,
        'degree_major'           => $degree_major,
        'gpa'                    => $gpa,
        'graduation_year'        => $graduation_year,
        'tests_taken'            => $tests_taken,
        'gmat_score'             => $gmat_score,
        'gmat_quant'             => $gmat_quant,
        'gmat_verbal'            => $gmat_verbal,
        'gre_score'              => $gre_score,
        'gre_quant'              => $gre_quant,
        'gre_verbal'             => $gre_verbal,
        'business_schools'       => implode(', ', $business_schools),
        'mba_start_date'         => $mba_start_date,
        'previously_applied_mba' => $previously_applied_mba,
        'fortuna_service'        => $fortuna_service,
        'code'                   => $code,
        'ab_variation'           => $ab_variation,
        'ab_coach'               => $ab_coach_name,
    );

    $submission_id = fa_save_form_submission( 'mba', $data );
    if ( $submission_id ) {
        update_post_meta( $submission_id, '_fa_form_stage', 'step2-ab' );
    }

    $response_payload = array(
        'message'   => 'Thanks for sharing this very helpful background information, which will be invaluable for our call together. We will be in touch soon.',
        'variation' => $ab_variation,
    );

    if ( ! empty( $ab_calendar_url ) ) {
        $response_payload['calendar_url'] = $ab_calendar_url;
        $response_payload['coach_name']   = $ab_coach_name;
    }

    fa_send_json_and_continue( true, $response_payload );

    $podio_url = apply_filters(
        'fa_ab_podio_webhook_url',
        'https://workflow-automation.podio.com/catch/y73oog24iwh3c7g',
        'step2',
        $data
    );

    $response = wp_remote_post(
        $podio_url,
        array(
            'method'  => 'POST',
            'body'    => $data,
            'timeout' => 30,
        )
    );

    if ( $submission_id ) {
        if ( is_wp_error( $response ) ) {
            fa_update_podio_status( $submission_id, 'failed', $response->get_error_message() );
            error_log( 'MBA Full Consultation (AB): Podio webhook error: ' . $response->get_error_message() );
        } else {
            $podio_code = wp_remote_retrieve_response_code( $response );
            if ( $podio_code >= 200 && $podio_code < 300 ) {
                fa_update_podio_status( $submission_id, 'success' );
            } else {
                fa_update_podio_status( $submission_id, 'failed', 'HTTP ' . $podio_code );
                error_log( 'MBA Full Consultation (AB): Podio webhook HTTP ' . $podio_code . ' | body: ' . wp_remote_retrieve_body( $response ) );
            }
        }
    }

    exit;
}


add_action('wp_footer', function () {
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
          
          // Do not run the custom accordion in the WordPress admin.
          if (
              document.body.classList.contains('wp-admin') ||
              document.body.classList.contains('block-editor-page')
          ) {
              return;
          }
          
            const faqs = document.querySelectorAll('.fortuna-faq .schema-faq-section');

            faqs.forEach(function (faq, index) {
                const question = faq.querySelector('.schema-faq-question');
                const answer = faq.querySelector('.schema-faq-answer');

                if (!question || !answer) {
                    return;
                }

                question.setAttribute('role', 'button');
                question.setAttribute('tabindex', '0');
                question.setAttribute('aria-expanded', index === 0 ? 'true' : 'false');
                answer.setAttribute('aria-hidden', index === 0 ? 'false' : 'true');

                if (index === 0) {
                    faq.classList.add('is-open');
                }

                function toggleFaq() {
                    const isOpen = faq.classList.contains('is-open');

                    faqs.forEach(function (item) {
                        item.classList.remove('is-open');

                        const itemQuestion = item.querySelector('.schema-faq-question');
                        const itemAnswer = item.querySelector('.schema-faq-answer');

                        if (itemQuestion) {
                            itemQuestion.setAttribute('aria-expanded', 'false');
                        }

                        if (itemAnswer) {
                            itemAnswer.setAttribute('aria-hidden', 'true');
                        }
                    });

                    if (!isOpen) {
                        faq.classList.add('is-open');
                        question.setAttribute('aria-expanded', 'true');
                        answer.setAttribute('aria-hidden', 'false');
                    }
                }

                question.addEventListener('click', toggleFaq);

                question.addEventListener('keydown', function (event) {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        toggleFaq();
                    }
                });
            });
        });
    </script>
    <?php
});



/**
 * Automatically preload the first Spectra Container background image.
 * Works across pages without manually specifying page IDs or image URLs.
 */

function fortuna_find_hero_background_image( $blocks ) {

    $is_mobile = wp_is_mobile();

    foreach ( $blocks as $block ) {

        $attrs = isset( $block['attrs'] ) ? $block['attrs'] : array();
        $name  = isset( $block['blockName'] ) ? $block['blockName'] : '';

        // Check Spectra Container blocks only.
        if ( strpos( $name, 'container' ) !== false ) {

            // Common Spectra background image attributes.
            $image_keys = $is_mobile
                ? array(
                    'backgroundImageMobile',
                    'backgroundImageTablet',
                    'backgroundImageDesktop',
                    'backgroundImage'
                )
                : array(
                    'backgroundImageDesktop',
                    'backgroundImage',
                    'backgroundImageTablet'
                );

            foreach ( $image_keys as $key ) {

                if ( ! empty( $attrs[$key]['url'] ) ) {
                    return esc_url_raw( $attrs[$key]['url'] );
                }
            }

            // Newer Spectra background structure.
            $background_paths = array(
                array( 'background', 'media', 'url' ),
                array( 'responsiveControls', 'lg', 'background', 'media', 'url' ),
                array( 'responsiveControls', 'sm', 'background', 'media', 'url' )
            );

            foreach ( $background_paths as $path ) {

                $value = $attrs;

                foreach ( $path as $key ) {
                    $value = isset( $value[$key] ) ? $value[$key] : null;
                }

                if ( is_string( $value ) && $value !== '' ) {
                    return esc_url_raw( $value );
                }
            }

            // WordPress native background image structure.
            if ( ! empty( $attrs['style']['background']['backgroundImage']['url'] ) ) {
                return esc_url_raw(
                    $attrs['style']['background']['backgroundImage']['url']
                );
            }
        }

        // Check nested containers.
        if ( ! empty( $block['innerBlocks'] ) ) {
            $image = fortuna_find_hero_background_image( $block['innerBlocks'] );

            if ( $image ) {
                return $image;
            }
        }
    }

    return '';
}


function fortuna_preload_spectra_hero_image() {

    if ( is_admin() || is_feed() ) {
        return;
    }

    $post_id = get_queried_object_id();

    if ( ! $post_id ) {
        return;
    }

    $post = get_post( $post_id );

    if ( ! $post ) {
        return;
    }

    $blocks = parse_blocks( $post->post_content );

    $image_url = fortuna_find_hero_background_image( $blocks );

    if ( $image_url ) {
        printf(
            '<link rel="preload" as="image" href="%s" fetchpriority="high">' . "\n",
            esc_url( $image_url )
        );
    }
}

add_action( 'wp_head', 'fortuna_preload_spectra_hero_image', 1 );

