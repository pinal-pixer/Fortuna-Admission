<?php
/**
 * International phone field (country code picker) for consultation forms.
 *
 * - Loads intl-tel-input from jsDelivr (pinned version).
 * - Exposes an AJAX endpoint that returns the visitor's country from CDN / host
 *   geo headers (Cloudflare, CloudFront, etc.). This is done via AJAX rather
 *   than inline in the page because full-page caching (Hummingbird) would
 *   otherwise bake one visitor's country into the HTML for everyone.
 * - Falls back to the browser locale (e.g. en-GB -> gb), then "us".
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
    var STORAGE_KEY = "fa_geo_country";

    function localeCountry() {
        var langs = (navigator.languages && navigator.languages.length) ? navigator.languages : [navigator.language || ""];
        for (var i = 0; i < langs.length; i++) {
            var m = String(langs[i]).match(/[-_]([A-Za-z]{2})(?:$|[-_])/);
            if (m) return m[1].toLowerCase();
        }
        return "us";
    }

    function makeGeoLookup(ajaxUrl) {
        return function (success) {
            var done = false;
            function finish(code) {
                if (done) return;
                done = true;
                success(code);
            }

            try {
                var cached = sessionStorage.getItem(STORAGE_KEY);
                if (cached) return finish(cached);
            } catch (e) {}

            if (!ajaxUrl || typeof fetch !== "function") return finish(localeCountry());

            // Never let the lookup hold the field hostage.
            var timer = setTimeout(function () { finish(localeCountry()); }, 1500);

            fetch(ajaxUrl + (ajaxUrl.indexOf("?") === -1 ? "?" : "&") + "action=fa_geo_country", {
                credentials: "same-origin"
            }).then(function (res) {
                return res.json();
            }).then(function (res) {
                clearTimeout(timer);
                var code = res && res.success && res.data && res.data.country;
                if (code) {
                    try { sessionStorage.setItem(STORAGE_KEY, code); } catch (e) {}
                    finish(code);
                } else {
                    finish(localeCountry());
                }
            }).catch(function () {
                clearTimeout(timer);
                finish(localeCountry());
            });
        };
    }

    function init(opts) {
        var input = opts.input;
        var hidden = opts.hidden;
        var countrySelect = opts.countrySelect;
        var iti = null;

        if (input && typeof window.intlTelInput === "function") {
            try {
                iti = window.intlTelInput(input, {
                    initialCountry: "auto",
                    geoIpLookup: makeGeoLookup(opts.ajaxUrl),
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
