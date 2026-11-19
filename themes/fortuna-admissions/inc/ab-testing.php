<?php
/**
 * ============================================================================
 * MBA Consultation A/B Test — helpers
 * ----------------------------------------------------------------------------
 * Variation A (control): existing flow — redirect to the thank-you page after
 *                        Form 2.
 * Variation B (test):    after Form 2 success, show the relevant coach's
 *                        Appointlet calendar so the visitor can book a slot
 *                        directly without leaving the page.
 *
 * Only Non-US / Non-LATAM visitors participate in Variation B. Everyone else
 * stays on the control flow regardless of bucket assignment, so the control
 * event still fires but nothing visible changes for them.
 *
 * Variation is assigned client-side (cache-friendly: no Set-Cookie header) and
 * persisted in a cookie so Form 1 and Form 2 see the same bucket. The server
 * trusts the submitted variation only to the extent of validating it's 'A' or
 * 'B' — this is experiment bucketing, not a security boundary.
 * ============================================================================
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'FA_AB_COOKIE_NAME' ) ) {
    define( 'FA_AB_COOKIE_NAME', 'fa_mba_ab' );
}

if ( ! defined( 'FA_AB_COOKIE_TTL_DAYS' ) ) {
    define( 'FA_AB_COOKIE_TTL_DAYS', 30 );
}

/**
 * Countries excluded from the A/B test (test only runs for Non-US / Non-LATAM).
 * Labels must match the values in the MBA country <select> exactly.
 *
 * Filter: fa_ab_excluded_countries — Matt's final list drops in here without
 * touching this file.
 */
function fa_ab_excluded_countries() {

    $defaults = array(
        // United States
        'United States',
        'Puerto Rico',

        // LATAM
        'Argentina',
        'Bolivia',
        'Brazil',
        'Chile',
        'Colombia',
        'Costa Rica',
        'Cuba',
        'Dominican Republic',
        'Ecuador',
        'El Salvador',
        'Guatemala',
        'Guyana',
        'Haiti',
        'Honduras',
        'Mexico',
        'Nicaragua',
        'Panama',
        'Paraguay',
        'Peru',
        'Suriname',
        'Uruguay',
        'Venezuela',
    );

    return apply_filters( 'fa_ab_excluded_countries', $defaults );
}

function fa_ab_is_country_eligible( $country ) {

    if ( empty( $country ) ) {
        return false;
    }

    $excluded = array_map( 'strtolower', fa_ab_excluded_countries() );

    return ! in_array( strtolower( trim( $country ) ), $excluded, true );
}

/**
 * Coaches available for Variation B.
 *
 * Filter: fa_ab_coach_catalog — swap the appointlet URLs or add coaches here.
 */
function fa_ab_coach_catalog() {

    $defaults = array(
        'leonie' => array(
            'name'           => 'Leonie',
            'appointlet_url' => 'https://appt.link/meet-with-leonie/30-minute-free-consultation',
        ),
        'matt'   => array(
            'name'           => 'Matt Symonds',
            'appointlet_url' => 'https://appt.link/meet-with-matt-symonds/mba',
        ),
    );

    return apply_filters( 'fa_ab_coach_catalog', $defaults );
}

/**
 * Resolve a coach for the given country.
 *
 * Final mapping (from Matt) should be injected via the
 * `fa_ab_country_coach_map` filter, e.g.
 *
 *   add_filter( 'fa_ab_country_coach_map', function () {
 *       return array(
 *           'United Kingdom' => 'leonie',
 *           'India'          => 'matt',
 *           // ...
 *       );
 *   } );
 *
 * Any country not explicitly mapped falls back to a deterministic crc32
 * assignment so staging gives a stable (same country → same coach) result.
 */
function fa_ab_get_coach_for_country( $country ) {

    $catalog = fa_ab_coach_catalog();

    if ( empty( $catalog ) ) {
        return null;
    }

    $key_lc = strtolower( trim( (string) $country ) );

    $map = apply_filters( 'fa_ab_country_coach_map', array() );

    foreach ( $map as $mapped_country => $coach_key ) {
        if ( strtolower( trim( (string) $mapped_country ) ) === $key_lc && isset( $catalog[ $coach_key ] ) ) {
            return $catalog[ $coach_key ];
        }
    }

    $coach_keys = array_keys( $catalog );
    $idx        = abs( crc32( $key_lc ) ) % count( $coach_keys );

    return $catalog[ $coach_keys[ $idx ] ];
}

/**
 * Normalise a variation value coming from the client. Anything other than
 * 'A' / 'B' falls back to 'A' (control).
 */
function fa_ab_sanitize_variation( $value ) {

    $value = is_string( $value ) ? strtoupper( trim( $value ) ) : '';

    if ( $value === 'A' || $value === 'B' ) {
        return $value;
    }

    return 'A';
}

/**
 * Append Appointlet prefill params so the visitor doesn't retype their name /
 * email in the booking widget.
 */
function fa_ab_build_booking_url( $base_url, $first_name = '', $last_name = '', $email = '' ) {

    $args = array();

    if ( ! empty( $first_name ) ) {
        $args['firstName'] = $first_name;
    }
    if ( ! empty( $last_name ) ) {
        $args['lastName'] = $last_name;
    }
    if ( ! empty( $email ) ) {
        $args['email'] = $email;
    }

    if ( empty( $args ) ) {
        return $base_url;
    }

    return add_query_arg( $args, $base_url );
}

/**
 * Shared JS snippet that reads/sets the variation cookie on the client.
 * Loaded once per page where it's needed; safe to call multiple times.
 */
function fa_ab_print_variation_bootstrap() {

    static $printed = false;
    if ( $printed ) {
        return;
    }
    $printed = true;

    $cookie_name = FA_AB_COOKIE_NAME;
    $ttl_days    = (int) FA_AB_COOKIE_TTL_DAYS;
    ?>
    <script>
    (function () {
        if (window.faAbResolveVariation) return;

        var COOKIE = <?php echo wp_json_encode( $cookie_name ); ?>;
        var TTL    = <?php echo (int) $ttl_days; ?> * 24 * 60 * 60;

        function readCookie() {
            var m = document.cookie.match(new RegExp('(?:^|;\\s*)' + COOKIE + '=([AB])'));
            return m ? m[1] : null;
        }

        function writeCookie(v) {
            var secure = window.location.protocol === 'https:' ? '; secure' : '';
            document.cookie = COOKIE + '=' + v + '; path=/; max-age=' + TTL + '; samesite=Lax' + secure;
        }

        window.faAbResolveVariation = function () {
            var v = readCookie();
            if (v === 'A' || v === 'B') return v;
            v = Math.random() < 0.5 ? 'A' : 'B';
            writeCookie(v);
            // First-time assignment — fire a dataLayer event so analytics can
            // record the bucket split without waiting for form submission.
            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push({
                event: 'MBAFormVariationAssigned',
                mba_ab_variation: v
            });
            return v;
        };
    })();
    </script>
    <?php
}
