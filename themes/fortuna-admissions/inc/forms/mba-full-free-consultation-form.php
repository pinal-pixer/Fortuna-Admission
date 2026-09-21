<?php
// Prefill shared fields from URL query params (passed by Form 1 on redirect).
// Rendering server-side means values are in the HTML on first paint — no JS delay.
$fa_prefill = array(
    'first_name'  => isset($_GET['first_name'])  ? sanitize_text_field( wp_unslash($_GET['first_name']) )  : '',
    'last_name'   => isset($_GET['last_name'])   ? sanitize_text_field( wp_unslash($_GET['last_name']) )   : '',
    'email'       => isset($_GET['email'])       ? sanitize_email( wp_unslash($_GET['email']) )            : '',
    'phone'       => isset($_GET['phone'])       ? sanitize_text_field( wp_unslash($_GET['phone']) )       : '',
    'country'     => isset($_GET['country'])     ? sanitize_text_field( wp_unslash($_GET['country']) )     : '',
    'sms_consent' => isset($_GET['sms_consent']) ? sanitize_text_field( wp_unslash($_GET['sms_consent']) ) : '',
);

$fa_country_top = array(
    'United States','United Kingdom','Canada','China','India',
);

$fa_country_options = array(
    'Afghanistan','Albania','Algeria','Andorra','Angola','Argentina','Armenia','Australia','Austria','Azerbaijan',
    'Bahrain','Bangladesh','Belgium','Brazil','Bulgaria',
    'Chile','Colombia','Costa Rica','Croatia','Cyprus','Czech Republic',
    'Denmark','Dominican Republic',
    'Ecuador','Egypt','Estonia','Ethiopia',
    'Finland','France',
    'Georgia','Germany','Ghana','Greece','Guatemala',
    'Hong Kong','Hungary',
    'Iceland','Indonesia','Ireland','Israel','Italy',
    'Japan','Jordan',
    'Kenya','Kuwait',
    'Latvia','Lebanon','Lithuania','Luxembourg',
    'Malaysia','Malta','Mexico','Monaco','Morocco',
    'Netherlands','New Zealand','Nigeria','Norway',
    'Pakistan','Panama','Peru','Philippines','Poland','Portugal',
    'Qatar',
    'Romania','Russia',
    'Saudi Arabia','Singapore','Slovakia','Slovenia','South Africa','South Korea','Spain','Sri Lanka','Sweden','Switzerland',
    'Taiwan','Thailand','Turkey',
    'Ukraine','United Arab Emirates','Uruguay',
    'Venezuela','Vietnam',
    'Other',
);
?>
<style>
.mba-full-consultation-form {
    max-width: 100%;
}

.mba-full-consultation-form .form-row {
    display: flex;
    gap: 25px;
    margin-bottom: 20px;
}

.mba-full-consultation-form .form-group {
    flex: 1;
    min-width: 0;
}

.mba-full-consultation-form .form-group label {
    display: block;
    margin-bottom: 6px;
    font-weight: 600;
    font-size: 14px;
    line-height: 20px;
}

.mba-full-consultation-form .form-group input[type="text"],
.mba-full-consultation-form .form-group input[type="email"],
.mba-full-consultation-form .form-group input[type="tel"],
.mba-full-consultation-form .form-group input[type="url"],
.mba-full-consultation-form .form-group input[type="file"],
.mba-full-consultation-form .form-group textarea {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-sizing: border-box;
}

.mba-full-consultation-form .form-group textarea {
    resize: vertical;
}

.mba-full-consultation-form select {
    width: 100%;
    padding: 8px 45px 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;

    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;

    background: #fff
        url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='18' height='18' viewBox='0 0 24 24' fill='none' stroke='%23333' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E")
        no-repeat right 8px center;
    background-size: 18px;
}

.mba-full-consultation-form .form-group select:focus {
    outline: none;
    border-color: #5b9dd9;
}

.mba-full-consultation-form button {
    padding: 14px 30px;
    background: linear-gradient(
        90deg,
        var(--ast-global-color-0) 0%,
        var(--ast-global-color-1) 100%
    );
    color: #fff;
    border: none;
    cursor: pointer;
    text-transform: uppercase;
}

.mba-full-consultation-form button:hover {
    background: var(--ast-global-color-0);
}

.bottom-margin {
    margin-bottom: 20px;
}

.asterisk {
    color: #CC2A24;
}

.last-name-label {
    visibility: hidden;
}

.mba-full-consultation-form .consent-group {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    margin: -6px 0 20px;
}

.mba-full-consultation-form .consent-group input[type="checkbox"] {
    margin-top: 3px;
    flex-shrink: 0;
    width: 16px;
    height: 16px;
}

.mba-full-consultation-form .consent-group label {
    margin-bottom: 0;
    font-weight: 400;
    font-size: 13px;
    line-height: 18px;
    color: #555;
    cursor: pointer;
}

.mba-full-consultation-form .checkbox-group {
    display: flex;
    flex-wrap: wrap;
    gap: 10px 20px;
}

.mba-full-consultation-form .checkbox-group label {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 0;
    font-weight: 400;
    cursor: pointer;
}

.mba-full-consultation-form .checkbox-group input[type="checkbox"] {
    width: auto;
    margin: 0;
}

.mba-full-consultation-form .radio-group {
    display: flex;
    gap: 20px;
    align-items: center;
    padding-top: 4px;
}

.mba-full-consultation-form .radio-item {
    display: flex;
    align-items: center;
    gap: 6px;
}

.mba-full-consultation-form .radio-item input[type="radio"] {
    width: auto;
    margin: 0;
}

.mba-full-consultation-form .radio-item label {
    margin: 0;
    font-weight: 400;
}

@media (max-width: 768px) {
    .mba-full-consultation-form .form-row {
        flex-direction: column;
        gap: 15px;
    }

    .last-name-label {
        display: none !important;
    }
}
</style>

<form class="mba-full-consultation-form"
      action="#"
      method="post"
      enctype="multipart/form-data">

    <input type="hidden" name="g-recaptcha-response" value="">
    <input type="hidden" name="action" value="mba_full_consultation_submit">
    <?php wp_nonce_field('mba_full_consultation_nonce', 'mba_full_consultation_nonce_field'); ?>

    <!-- Name -->
    <div class="form-row">
        <div class="form-group">
            <label for="first-name">
                Name <span class="asterisk">*</span>
            </label>
            <input type="text" id="first-name" name="first_name" placeholder="First" required value="<?php echo esc_attr($fa_prefill['first_name']); ?>">
        </div>

        <div class="form-group">
            <label for="last-name" class="last-name-label">Name</label>
            <input type="text" id="last-name" name="last_name" placeholder="Last" value="<?php echo esc_attr($fa_prefill['last_name']); ?>">
        </div>
    </div>

    <!-- Email / Phone -->
    <div class="form-row">
        <div class="form-group">
            <label for="email">
                Email <span class="asterisk">*</span>
            </label>
            <input type="email" id="email" name="email" required value="<?php echo esc_attr($fa_prefill['email']); ?>">
        </div>

        <div class="form-group">
            <label for="phone">Phone</label>
            <input type="tel" id="phone" name="phone" value="<?php echo esc_attr($fa_prefill['phone']); ?>">
        </div>
    </div>

    <?php
    // Show consent up-front when Form 1 pre-filled a phone number; JS keeps it in sync afterwards.
    $fa_show_consent    = ! empty($fa_prefill['phone']);
    $fa_consent_checked = ($fa_prefill['sms_consent'] === 'Yes') || ($fa_show_consent && $fa_prefill['sms_consent'] === '');
    ?>
    <div class="consent-group" id="mba_full_sms_consent_group" style="<?php echo $fa_show_consent ? '' : 'display: none;'; ?>">
        <input
            type="checkbox"
            name="sms_consent"
            id="mba_full_sms_consent"
            value="Yes"
            <?php checked($fa_consent_checked); ?>
        >
        <label for="mba_full_sms_consent">
            I agree to receive promotional messages from Fortuna Admissions at the phone number provided.
        </label>
    </div>

    <!-- Resume -->
    <div class="form-group bottom-margin">
        <label for="resume">Resume</label>
        <input
            type="file"
            id="resume"
            name="resume"
            accept=".pdf,.doc,.docx"
        >
    </div>

    <!-- Country -->
    <div class="form-group bottom-margin">
        <label for="country">
            Country of Residence <span class="asterisk">*</span>
        </label>
        <select id="country" name="country" required>
            <option value="">Select Country</option>
            <?php foreach ($fa_country_top as $fa_country_option) : ?>
                <option value="<?php echo esc_attr($fa_country_option); ?>" <?php selected($fa_prefill['country'], $fa_country_option); ?>>
                    <?php echo esc_html($fa_country_option); ?>
                </option>
            <?php endforeach; ?>
            <option disabled>-----------------------------------</option>
            <?php foreach ($fa_country_options as $fa_country_option) : ?>
                <option value="<?php echo esc_attr($fa_country_option); ?>" <?php selected($fa_prefill['country'], $fa_country_option); ?>>
                    <?php echo esc_html($fa_country_option); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Employer / Job -->
    <div class="form-row">
        <div class="form-group">
            <label for="employer">Current Employer</label>
            <input type="text" id="employer" name="current_employer">
        </div>

        <div class="form-group">
            <label for="job-title">Job Title / Function</label>
            <input type="text" id="job-title" name="job_title_function">
        </div>
    </div>

    <!-- Industry -->
    <div class="form-group bottom-margin">
      <label for="industry">Current Industry</label>
      <select id="industry" name="current_industry">
          <option value="">Please Select</option>
          <option value="Consulting">Consulting</option>
          <option value="Government / Military / Non Profit">Government / Military / Non Profit</option>
          <option value="Investment Banking">Investment Banking</option>
          <option value="Private Equity / Venture Capital">Private Equity / Venture Capital</option>
          <option value="Investment Management">Investment Management</option>
          <option value="Other Financial Services">Other Financial Services</option>
          <option value="Technology / Internet / Ecommerce">Technology / Internet / Ecommerce</option>
          <option value="CPG / Retail / Healthcare">CPG / Retail / Healthcare</option>
          <option value="Advertising">Advertising</option>
          <option value="Heavy Industry / Manufacturing">Heavy Industry / Manufacturing</option>
          <option value="Media & Entertainment">Media & Entertainment</option>
          <option value="Energy / Oil & Gas">Energy / Oil & Gas</option>
          <option value="Real Estate">Real Estate</option>
          <option value="Other">Other</option>
      </select>
	</div>

    <!-- Undergraduate Education -->
    <div class="form-row">
        <div class="form-group">
            <label for="university">Undergrad University</label>
            <input type="text" id="university" name="undergrad_university">
        </div>

        <div class="form-group">
            <label for="degree">Degree and Major</label>
            <input type="text" id="degree" name="degree_major">
        </div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="gpa">GPA</label>
            <input type="text" id="gpa" name="gpa">
        </div>

        <div class="form-group">
            <label for="graduation-year">Graduation Year</label>
            <input type="text" id="graduation-year" name="graduation_year">
        </div>
    </div>

    <!-- Tests -->
    <div class="form-group bottom-margin">
    <label for="tests">Which tests have you taken (GMAT/GRE)? <span class="asterisk">*</span></label>
    <select id="tests" name="tests_taken" required>
        <option value="">Please Select</option>
        <option value="GMAT">GMAT</option>
        <option value="GRE">GRE</option>
        <option value="Both">Both GMAT and GRE</option>
        <option value="Neither">Neither GMAT nor GRE</option>
    </select>
</div>

<div id="gmat-fields" class="form-row test-score-fields" style="display:none;">
    <div class="form-group">
        <label for="gmat-score">Best GMAT Score <span class="asterisk">*</span></label>
        <input type="text" id="gmat-score" name="gmat_score">
    </div>
    <div class="form-group">
        <label for="gmat-quant">GMAT Quant</label>
        <input type="text" id="gmat-quant" name="gmat_quant">
    </div>
    <div class="form-group">
        <label for="gmat-verbal">GMAT Verbal</label>
        <input type="text" id="gmat-verbal" name="gmat_verbal">
    </div>
</div>

<div id="gre-fields" class="form-row test-score-fields" style="display:none;">
    <div class="form-group">
        <label for="gre-score">Best GRE Score <span class="asterisk">*</span></label>
        <input type="text" id="gre-score" name="gre_score">
    </div>
    <div class="form-group">
        <label for="gre-quant">GRE Quant</label>
        <input type="text" id="gre-quant" name="gre_quant">
    </div>
    <div class="form-group">
        <label for="gre-verbal">GRE Verbal</label>
        <input type="text" id="gre-verbal" name="gre_verbal">
    </div>
</div>

    <!-- Business Schools -->
    <div class="form-group bottom-margin">
        <label>
            Which Business Schools are you considering? <span class="asterisk">*</span>
        </label>

        <div class="checkbox-group">
            <label>
                <input type="checkbox" name="business_schools[]" value="Harvard Business School">
                Harvard Business School
            </label>
            <label>
                <input type="checkbox" name="business_schools[]" value="NYU Stern">
                NYU Stern
            </label>
            <label>
                <input type="checkbox" name="business_schools[]" value="London Business School">
                London Business School
            </label>
            <label>
                <input type="checkbox" name="business_schools[]" value="Stanford GSB">
                Stanford GSB
            </label>
            <label>
                <input type="checkbox" name="business_schools[]" value="Yale SOM">
                Yale SOM
            </label>
            <label>
                <input type="checkbox" name="business_schools[]" value="Cambridge Judge">
                Cambridge Judge
            </label>
            <label>
                <input type="checkbox" name="business_schools[]" value="Wharton">
                Wharton
            </label>
            <label>
                <input type="checkbox" name="business_schools[]" value="Dartmouth Tuck">
                Dartmouth Tuck
            </label>
            <label>
                <input type="checkbox" name="business_schools[]" value="Oxford Said">
                Oxford Said
            </label>
            <label>
                <input type="checkbox" name="business_schools[]" value="Columbia Business School">
                Columbia Business School
            </label>
            <label>
                <input type="checkbox" name="business_schools[]" value="Duke Fuqua">
                Duke Fuqua
            </label>
            <label>
                <input type="checkbox" name="business_schools[]" value="HEC Paris">
                HEC Paris
            </label>
            <label>
                <input type="checkbox" name="business_schools[]" value="MIT Sloan">
                MIT Sloan
            </label>
            <label>
                <input type="checkbox" name="business_schools[]" value="UCLA Anderson">
                UCLA Anderson
            </label>
            <label>
                <input type="checkbox" name="business_schools[]" value="IMD">
                IMD
            </label>
            <label>
                <input type="checkbox" name="business_schools[]" value="Chicago Booth">
                Chicago Booth
            </label>
            <label>
                <input type="checkbox" name="business_schools[]" value="Cornell Johnson">
                Cornell Johnson
            </label>
            <label>
                <input type="checkbox" name="business_schools[]" value="IESE">
                IESE
            </label>
            <label>
                <input type="checkbox" name="business_schools[]" value="Northwestern Kellogg">
                Northwestern Kellogg
            </label>
            <label>
                <input type="checkbox" name="business_schools[]" value="UVA Darden">
                UVA Darden
            </label>
            <label>
                <input type="checkbox" name="business_schools[]" value="IE">
                IE
            </label>
            <label>
                <input type="checkbox" name="business_schools[]" value="UC Berkeley Haas">
                UC Berkeley Haas
            </label>
            <label>
                <input type="checkbox" name="business_schools[]" value="INSEAD">
                INSEAD
            </label>
            <label>
                <input type="checkbox" name="business_schools[]" value="Other">
                Other
            </label>
        </div>
    </div>

    <!-- Start MBA / Previous MBA -->
    <div class="form-row">
        <div class="form-group">
          <label for="start-date">When do you hope to start your MBA / Masters?</label>
          <select id="start-date" name="mba_start_date">
              <option value="">Please Select</option>
              <option value="September 2027">September 2027</option>
              <option value="January 2028">January 2028</option>
              <option value="September 2028">September 2028</option>
              <option value="2029 or later">2029 or later</option>
          </select>
      </div>

        <div class="form-group">
            <label>Have you ever applied previously for an MBA?</label>
            <div class="radio-group">
                <div class="radio-item">
                    <input type="radio" id="applied-yes" name="previously_applied_mba" value="Yes">
                    <label for="applied-yes">Yes</label>
                </div>

                <div class="radio-item">
                    <input type="radio" id="applied-no" name="previously_applied_mba" value="No" checked>
                    <label for="applied-no">No</label>
                </div>
            </div>
        </div>
    </div>

    <!-- Service -->
    <div class="form-group bottom-margin">
        <label for="service">Which Fortuna Admissions service are you interested in?</label>
        <select id="service" name="fortuna_service">
            <option value="">Please Select</option>
            <option value="All-Inclusive Package">All-Inclusive Package</option>
            <option value="Hourly Consulting">Hourly Consulting</option>
            <option value="MBA Interview Prep">MBA Interview Prep</option>
            <option value="Other">Other</option>
        </select>
    </div>

    <!-- Submit -->
    <button type="submit">Submit</button>

</form>

<script>
(function () {
    // Shared text fields (first_name, last_name, email, phone) are prefilled server-side
    // from URL query params — no JS needed there. We only need to:
    //   1. Restore resume file from sessionStorage (files can't travel via URL).
    //   2. Strip the query string from the address bar so PII doesn't linger.
    const form = document.querySelector(".mba-full-consultation-form");
    if (!form) return;

    try {
        const resumeInput = form.querySelector('input[name="resume"]');
        const raw = sessionStorage.getItem("mba_resume");

        if (resumeInput && raw && typeof DataTransfer !== "undefined") {
            const meta = JSON.parse(raw);
            const dataUrl = meta.data || "";
            const commaIdx = dataUrl.indexOf(",");
            const base64 = commaIdx >= 0 ? dataUrl.slice(commaIdx + 1) : "";
            const byteChars = atob(base64);
            const bytes = new Uint8Array(byteChars.length);

            for (let i = 0; i < byteChars.length; i++) {
                bytes[i] = byteChars.charCodeAt(i);
            }

            const file = new File(
                [bytes],
                meta.name || "resume",
                { type: meta.type || "application/octet-stream" }
            );

            const dt = new DataTransfer();
            dt.items.add(file);
            resumeInput.files = dt.files;
        }
    } catch (err) {
        // If restoration fails, the user will re-attach the file.
    }

    // Clean prefill params from URL so email/phone don't sit in the address bar or history.
    if (window.location.search && window.history && window.history.replaceState) {
        const url = new URL(window.location.href);
        ["first_name", "last_name", "email", "phone", "country", "sms_consent"].forEach(function (k) {
            url.searchParams.delete(k);
        });
        window.history.replaceState({}, "", url.pathname + (url.search ? url.search : "") + url.hash);
    }
})();
</script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const form = document.querySelector(".mba-full-consultation-form");
    if (!form) return;

    const tokenField = form.querySelector('input[name="g-recaptcha-response"]');
    const tests = form.querySelector("#tests");
    const gmatFields = form.querySelector("#gmat-fields");
    const greFields = form.querySelector("#gre-fields");
    const ajaxUrl = "<?php echo esc_url(admin_url('admin-ajax.php')); ?>";
    const thankYouUrl = "/mba/free-consultation-thank-you/";

    // Show SMS consent when a phone number is entered; auto-check on first entry.
    const phoneInput   = form.querySelector('input[name="phone"]');
    const consentCb    = document.getElementById("mba_full_sms_consent");
    const consentGroup = document.getElementById("mba_full_sms_consent_group");

    if (phoneInput && consentCb && consentGroup) {
        phoneInput.addEventListener("input", function () {
            if (phoneInput.value.trim().length > 0) {
                consentGroup.style.display = "";
                if (!consentCb.dataset.userTouched) {
                    consentCb.checked = true;
                }
            } else {
                consentGroup.style.display = "none";
                consentCb.checked = false;
                delete consentCb.dataset.userTouched;
            }
        });

        consentCb.addEventListener("change", function () {
            consentCb.dataset.userTouched = "1";
        });
    }

    function updateTestFields() {
        if (!tests) return;

        const value = tests.value;

        if (gmatFields) {
            gmatFields.style.display = (value === "GMAT" || value === "Both") ? "flex" : "none";
        }

        if (greFields) {
            greFields.style.display = (value === "GRE" || value === "Both") ? "flex" : "none";
        }
    }

    if (tests) {
        tests.addEventListener("change", updateTestFields);
        updateTestFields();
    }

    form.addEventListener("submit", function (e) {
        e.preventDefault();

        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const btn = form.querySelector('button[type="submit"]');

        if (btn) {
            btn.disabled = true;
            btn.textContent = "Submitting...";
        }

        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({
            event: "MBAFullFormSubmit"
        });

        function submitForm(token) {
            if (tokenField) tokenField.value = token || "";

            const formData = new FormData(form);

            fetch(ajaxUrl, {
                method: "POST",
                body: formData
            })
            .then(function (response) {
                return response.json().catch(function () { return null; });
            })
            .then(function (result) {
                if (result && result.success) {
                    // Clean up any transferred state from Form 1
                    try { sessionStorage.removeItem("mba_resume"); } catch (err) {}
                    window.location.href = thankYouUrl;
                    return;
                }

                if (btn) {
                    btn.disabled = false;
                    btn.textContent = "Submit";
                }

                const message = (result && result.data && result.data.message)
                    || "Sorry, something went wrong. Please try again.";
                alert(message);
            })
            .catch(function () {
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = "Submit";
                }
                alert("Sorry, something went wrong. Please try again.");
            });
        }

        if (typeof grecaptcha === "undefined") {
            submitForm("");
            return;
        }

        var fallback = setTimeout(function () {
            submitForm("");
        }, 4000);

        grecaptcha.ready(function () {
            grecaptcha.execute("6LevnXYtAAAAAMJD8mj2aeDja_yK6R20db50KgpD", {
                action: "mba_full_consultation"
            }).then(function (token) {
                clearTimeout(fallback);
                submitForm(token);
            }).catch(function () {
                clearTimeout(fallback);
                submitForm("");
            });
        });
    });
});
</script>