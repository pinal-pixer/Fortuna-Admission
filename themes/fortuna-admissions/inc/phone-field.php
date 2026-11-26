<?php
/**
 * International phone field (country code picker) for consultation forms.
 *
 * - Loads intl-tel-input from jsDelivr (pinned version).
 * - Exposes an AJAX endpoint that returns the visitor's country from CDN / host
 *   geo headers (Cloudflare, CloudFront, etc.). This is done via AJAX rather
 *   than inline in the page because full-page caching (Hummingbird) would
 *   otherwise bake one visitor's country into the HTML for everyone.
 * - Country lookup order: CDN geo headers via our AJAX endpoint, then
 *   api.country.is, then get.geojs.io (visitor IP is sent to these two).
 * - Lookup starts as soon as the script loads; the result is cached in
 *   localStorage for 30 days so returning visitors see their flag instantly.
 * - First visit: neutral placeholder until the lookup answers (no UK -> India
 *   flicker). If every source fails or it takes over 4s, no country is
 *   selected (neutral globe) and the visitor picks one from the dropdown.
 *
 * Usage inside a form template:
 *   <?php fa_phone_field_assets(); ?>
 *   ...
 *   const phone = window.faPhoneField.init({ input, hidden, countrySelect, ajaxUrl });
 *   phone.sync(); // before reading the hidden "phone" field on submit
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'FA_ITI_VERSION', '25.15.1' );

/**
 * Return the visitor's ISO country code (lowercase) from geo headers, or ''.
 */
function fa_geo_country_from_headers() {
    $headers = array(
        'HTTP_CF_IPCOUNTRY',               // Cloudflare
        'HTTP_CLOUDFRONT_VIEWER_COUNTRY',  // AWS CloudFront
        'HTTP_X_COUNTRY_CODE',             // Various hosts / proxies
        'HTTP_X_GEO_COUNTRY',
        'HTTP_GEOIP_COUNTRY_CODE',
        'GEOIP_COUNTRY_CODE',              // Apache/nginx GeoIP module
    );

    foreach ( $headers as $header ) {
        if ( empty( $_SERVER[ $header ] ) ) {
            continue;
        }
        $code = strtoupper( sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) ) );
        // XX = unknown, T1 = Tor (Cloudflare).
        if ( preg_match( '/^[A-Z]{2}$/', $code ) && ! in_array( $code, array( 'XX', 'T1' ), true ) ) {
            return strtolower( $code );
        }
    }

    return '';
}

function fa_ajax_geo_country() {
    nocache_headers();
    wp_send_json_success( array( 'country' => fa_geo_country_from_headers() ) );
}
add_action( 'wp_ajax_fa_geo_country', 'fa_ajax_geo_country' );
add_action( 'wp_ajax_nopriv_fa_geo_country', 'fa_ajax_geo_country' );

/**
 * Print the intl-tel-input assets and the shared init helper (once per page).
 */
function fa_phone_field_assets() {
    static $printed = false;
    if ( $printed ) {
        return;
    }
    $printed = true;

    $base = 'https://cdn.jsdelivr.net/npm/intl-tel-input@' . FA_ITI_VERSION . '/build/';
    ?>
<link rel="stylesheet" href="<?php echo esc_url( $base . 'css/intlTelInput.min.css' ); ?>">
<style>
.iti { width: 100%; display: block; }
.iti__country-container { z-index: 2; }
.iti--fullscreen-popup.iti--container { z-index: 100000; }

/* Country search box inside the dropdown (theme input styles override it). */
.iti .iti__search-input-wrapper { position: relative; }
.iti .iti__search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    pointer-events: none;
}
.iti input.iti__search-input,
.iti input.iti__search-input[type="search"] {
    width: 100%;
    height: auto;
    margin: 0;
    padding: 10px 36px 10px 38px !important;
    border: none;
    border-bottom: 1px solid #ddd;
    border-radius: 0;
    background: #fff;
    box-shadow: none;
    outline: none;
    font-size: 15px;
    line-height: 1.4;
    box-sizing: border-box;
}
.iti input.iti__search-input:focus {
    outline: none;
    box-shadow: none;
    border-bottom-color: #bbb;
}
.iti .iti__search-clear {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    padding: 4px;
    background: transparent !important;
    border: none;
}
</style>
<script src="<?php echo esc_url( $base . 'js/intlTelInput.min.js' ); ?>"></script>
<script>
(function () {
    if (window.faPhoneField) return;

    var UTILS_URL = <?php echo wp_json_encode( $base . 'js/utils.js' ); ?>;
    var STORAGE_KEY = "fa_geo_country_v2";
    var CACHE_DAYS = 30;
    var AJAX_URL = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;

    function isValidCountry(code) {
        if (!code || typeof code !== "string") return false;
        code = code.toLowerCase();
        var list = (window.intlTelInput && window.intlTelInput.getCountryData) ? window.intlTelInput.getCountryData() : [];
        for (var i = 0; i < list.length; i++) {
            if (list[i].iso2 === code) return true;
        }
        return false;
    }

    // Country remembered from an earlier visit (localStorage, 30 days), or "".
    function cachedCountry() {
        try {
            var saved = JSON.parse(localStorage.getItem(STORAGE_KEY) || "null");
            if (saved && isValidCountry(saved.c) && (Date.now() - saved.t) < CACHE_DAYS * 864e5) {
                return saved.c;
            }
        } catch (e) {}
        return "";
    }

    function saveCountry(code) {
        try { localStorage.setItem(STORAGE_KEY, JSON.stringify({ c: code, t: Date.now() })); } catch (e) {}
    }

    // Fetch JSON with its own timeout; resolves null on any failure.
    function getJSON(url, ms, opts) {
        return new Promise(function (resolve) {
            var timer = setTimeout(function () { resolve(null); }, ms);
            fetch(url, opts || {})
                .then(function (res) { return res.ok ? res.json() : null; })
                .then(function (data) { clearTimeout(timer); resolve(data); })
                .catch(function () { clearTimeout(timer); resolve(null); });
        });
    }

    // Where to look up the visitor's country, in order. Each resolves a code or "".
    //  1. Our own server: CDN geo headers (Cloudflare etc.). Empty locally.
    //  2. api.country.is, 3. get.geojs.io: free IP-to-country services that see
    //     the visitor's real public IP (works on local sites too).
    var SOURCES = [
        function () {
            return getJSON(AJAX_URL + "?action=fa_geo_country", 1500, { credentials: "same-origin" })
                .then(function (r) { return (r && r.success && r.data && r.data.country) || ""; });
        },
        function () {
            return getJSON("https://api.country.is/", 2000)
                .then(function (r) { return (r && r.country) || ""; });
        },
        function () {
            return getJSON("https://get.geojs.io/v1/ip/country.json", 2000)
                .then(function (r) { return (r && r.country) || ""; });
        }
    ];

    // Start the lookup as soon as this script runs (not on DOMContentLoaded).
    // Resolves to a valid ISO2 code, or "" if every source fails.
    var lookupPromise = (function () {
        var cached = cachedCountry();
        if (cached) return Promise.resolve(cached);
        if (typeof fetch !== "function" || typeof Promise === "undefined") return Promise.resolve("");

        function trySource(i) {
            if (i >= SOURCES.length) return Promise.resolve("");
            return SOURCES[i]().then(function (code) {
                if (isValidCountry(code)) {
                    code = code.toLowerCase();
                    saveCountry(code);
                    return code;
                }
                return trySource(i + 1);
            }, function () { return trySource(i + 1); });
        }

        // Hard cap so the field never waits too long; no country after that.
        return Promise.race([
            trySource(0),
            new Promise(function (resolve) { setTimeout(function () { resolve(""); }, 4000); })
        ]);
    })();

    function init(opts) {
        var input = opts.input;
        var hidden = opts.hidden;
        var countrySelect = opts.countrySelect;
        var iti = null;

        if (input && typeof window.intlTelInput === "function") {
            // Returning visitor: country already known, show it instantly.
            // First visit: "auto" shows a neutral placeholder (no wrong flag)
            // until the lookup answers, then the real country. If it fails,
            // no country is selected and the visitor can pick one.
            var known = cachedCountry();
            try {
                iti = window.intlTelInput(input, {
                    initialCountry: known || "auto",
                    geoIpLookup: function (success, failure) {
                        lookupPromise.then(function (code) {
                            // No country found: leave the field without a
                            // country (neutral globe) instead of guessing.
                            if (code) {
                                success(code);
                            } else if (typeof failure === "function") {
                                failure();
                            }
                        });
                    },
                    separateDialCode: true,
                    countrySearch: true,
                    countryOrder: ["us", "gb", "ca", "cn", "in"],
                    loadUtils: function () { return import(UTILS_URL); }
                });
            } catch (e) {
                iti = null;
            }
        }

        // If the visitor picks a Country of Residence before typing a number,
        // switch the dial code to match.
        if (iti && countrySelect) {
            countrySelect.addEventListener("change", function () {
                if (input.value.trim()) return;
                var name = countrySelect.value;
                var list = window.intlTelInput.getCountryData ? window.intlTelInput.getCountryData() : [];
                for (var i = 0; i < list.length; i++) {
                    if (list[i].name === name) {
                        iti.setCountry(list[i].iso2);
                        break;
                    }
                }
            });
        }

        // Write the full international number into the hidden "phone" field.
        // Deliberately lenient: phone is optional, so we never block submission
        // on format — we just normalise what the visitor typed.
        function sync() {
            if (!hidden) return "";
            var raw = input ? input.value.trim() : "";

            if (!raw) {
                hidden.value = "";
                return "";
            }

            var number = "";
            if (iti) {
                try { number = iti.getNumber() || ""; } catch (e) { number = ""; }

                if (!number) {
                    var digits = raw.replace(/\D/g, "");
                    var data = iti.getSelectedCountryData() || {};
                    if (raw.charAt(0) === "+") {
                        number = "+" + digits;
                    } else if (data.dialCode) {
                        number = "+" + data.dialCode + digits.replace(/^0+/, "");
                    }
                }
            }

            hidden.value = number || raw;
            return hidden.value;
        }

        return { iti: iti, sync: sync };
    }

    window.faPhoneField = { init: init };
})();
</script>
    <?php
}
