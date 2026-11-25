<?php
/**
 * International phone field (country code picker) for consultation forms.
 *
 * - Loads intl-tel-input from jsDelivr (pinned version).
 * - Exposes an AJAX endpoint that returns the visitor's country from CDN / host
 *   geo headers (Cloudflare, CloudFront, etc.). This is done via AJAX rather
 *   than inline in the page because full-page caching (Hummingbird) would
 *   otherwise bake one visitor's country into the HTML for everyone.
 * - Shows UK ("gb") immediately; switches to the detected country only if the
 *   lookup returns a valid code. If the lookup fails or times out, UK stays.
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

    var DEFAULT_COUNTRY = "gb";

    function isValidCountry(code) {
        if (!code || typeof code !== "string") return false;
        code = code.toLowerCase();
        var list = (window.intlTelInput && window.intlTelInput.getCountryData) ? window.intlTelInput.getCountryData() : [];
        for (var i = 0; i < list.length; i++) {
            if (list[i].iso2 === code) return true;
        }
        return false;
    }

    // Look up the visitor's country via our AJAX endpoint. Calls done(code)
    // with a valid ISO2 code, or done("") if the lookup fails / times out.
    function lookupCountry(ajaxUrl, done) {
        var finished = false;
        function finish(code) {
            if (finished) return;
            finished = true;
            done(isValidCountry(code) ? code.toLowerCase() : "");
        }

        try {
            var cached = sessionStorage.getItem(STORAGE_KEY);
            if (isValidCountry(cached)) return finish(cached);
        } catch (e) {}

        if (!ajaxUrl || typeof fetch !== "function") return finish("");

        // Never wait on the lookup for long.
        var timer = setTimeout(function () { finish(""); }, 2500);

        fetch(ajaxUrl + (ajaxUrl.indexOf("?") === -1 ? "?" : "&") + "action=fa_geo_country", {
            credentials: "same-origin"
        }).then(function (res) {
            return res.json();
        }).then(function (res) {
            clearTimeout(timer);
            var code = res && res.success && res.data && res.data.country;
            if (isValidCountry(code)) {
                try { sessionStorage.setItem(STORAGE_KEY, code.toLowerCase()); } catch (e) {}
            }
            finish(code);
        }).catch(function () {
            clearTimeout(timer);
            finish("");
        });
    }

    function init(opts) {
        var input = opts.input;
        var hidden = opts.hidden;
        var countrySelect = opts.countrySelect;
        var iti = null;
        var userChoseCountry = false;

        if (input && typeof window.intlTelInput === "function") {
            try {
                // Start with UK straight away so the flag is never blank,
                // then switch to the detected country if the lookup succeeds.
                iti = window.intlTelInput(input, {
                    initialCountry: DEFAULT_COUNTRY,
                    separateDialCode: true,
                    countrySearch: true,
                    countryOrder: ["us", "gb", "ca", "cn", "in"],
                    loadUtils: function () { return import(UTILS_URL); }
                });
            } catch (e) {
                iti = null;
            }
        }

        if (iti) {
            // Don't override a country the visitor picked themselves.
            input.addEventListener("open:countrydropdown", function () { userChoseCountry = true; });

            lookupCountry(opts.ajaxUrl, function (code) {
                if (!code || userChoseCountry || input.value.trim()) return;
                try { iti.setCountry(code); } catch (e) {}
            });
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
                        userChoseCountry = true;
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
