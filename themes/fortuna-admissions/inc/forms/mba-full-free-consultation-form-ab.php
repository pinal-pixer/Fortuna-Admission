<?php
// A/B STAGING COPY of mba-full-free-consultation-form.php.
// Identical UI to the live form, with unique action / nonce / reCAPTCHA names
// and a success path that — for Variation B + eligible countries — shows the
// coach's Appointlet calendar instead of redirecting to the thank-you page.
// Only the A/B test runs through this file — live visitors still hit the
// original shortcode and handler.

$fa_prefill = array(
    'first_name'    => isset($_GET['first_name'])    ? sanitize_text_field( wp_unslash($_GET['first_name']) )    : '',
    'last_name'     => isset($_GET['last_name'])     ? sanitize_text_field( wp_unslash($_GET['last_name']) )     : '',
    'email'         => isset($_GET['email'])         ? sanitize_email( wp_unslash($_GET['email']) )              : '',
    'phone'         => isset($_GET['phone'])         ? sanitize_text_field( wp_unslash($_GET['phone']) )         : '',
    'country'       => isset($_GET['country'])       ? sanitize_text_field( wp_unslash($_GET['country']) )       : '',
    'sms_consent'   => isset($_GET['sms_consent'])   ? sanitize_text_field( wp_unslash($_GET['sms_consent']) )   : '',
    'hear_about_us' => isset($_GET['hear_about_us']) ? sanitize_text_field( wp_unslash($_GET['hear_about_us']) ) : '',
);

$fa_ab_variation = isset($_GET['ab_variation'])
    ? fa_ab_sanitize_variation( wp_unslash($_GET['ab_variation']) )
    : '';
?>
<style>
.mba-full-consultation-form-ab {
    max-width: 100%;
}

.mba-full-consultation-form-ab .form-row {
    display: flex;
    gap: 25px;
    margin-bottom: 20px;
}

.mba-full-consultation-form-ab .form-group {
    flex: 1;
    min-width: 0;
}

.mba-full-consultation-form-ab .form-group label {
    display: block;
    margin-bottom: 6px;
    font-weight: 600;
    font-size: 14px;
    line-height: 20px;
}

.mba-full-consultation-form-ab .form-group input[type="text"],
.mba-full-consultation-form-ab .form-group input[type="email"],
.mba-full-consultation-form-ab .form-group input[type="tel"],
.mba-full-consultation-form-ab .form-group input[type="url"],
.mba-full-consultation-form-ab .form-group input[type="file"],
.mba-full-consultation-form-ab .form-group textarea {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-sizing: border-box;
}

.mba-full-consultation-form-ab .form-group textarea {
    resize: vertical;
}

.mba-full-consultation-form-ab select {
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

.mba-full-consultation-form-ab .form-group select:focus {
    outline: none;
    border-color: #5b9dd9;
}

.mba-full-consultation-form-ab button {
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

.mba-full-consultation-form-ab button:hover {
    background: var(--ast-global-color-0);
}

.mba-full-consultation-form-ab .bottom-margin {
    margin-bottom: 20px;
}

.mba-full-consultation-form-ab .asterisk {
    color: #CC2A24;
}

.mba-full-consultation-form-ab .last-name-label {
    visibility: hidden;
}

.mba-full-consultation-form-ab .consent-group {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    margin: -6px 0 20px;
}

.mba-full-consultation-form-ab .consent-group input[type="checkbox"] {
    margin-top: 3px;
    flex-shrink: 0;
    width: 16px;
    height: 16px;
}

.mba-full-consultation-form-ab .consent-group label {
    margin-bottom: 0;
    font-weight: 400;
    font-size: 13px;
    line-height: 18px;
    color: #555;
    cursor: pointer;
}

.mba-full-consultation-form-ab .checkbox-group {
    display: flex;
    flex-wrap: wrap;
    gap: 10px 20px;
}

.mba-full-consultation-form-ab .checkbox-group label {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 0;
    font-weight: 400;
    cursor: pointer;
}

.mba-full-consultation-form-ab .checkbox-group input[type="checkbox"] {
    width: auto;
    margin: 0;
}

.mba-full-consultation-form-ab .radio-group {
    display: flex;
    gap: 20px;
    align-items: center;
    padding-top: 4px;
}

.mba-full-consultation-form-ab .radio-item {
    display: flex;
    align-items: center;
    gap: 6px;
}

.mba-full-consultation-form-ab .radio-item input[type="radio"] {
    width: auto;
    margin: 0;
}

.mba-full-consultation-form-ab .radio-item label {
    margin: 0;
    font-weight: 400;
}

@media (max-width: 768px) {
    .mba-full-consultation-form-ab .form-row {
        flex-direction: column;
        gap: 15px;
    }

    .mba-full-consultation-form-ab .last-name-label {
        display: none !important;
    }
}

.mba-booking-success-ab {
    padding: 24px;
    border: 1px solid #e3e3e3;
    border-radius: 6px;
    background: #fafafa;
}

.mba-booking-success-ab h3 {
    margin: 0 0 8px;
    font-size: 20px;
    line-height: 1.3;
}

.mba-booking-success-ab p {
    margin: 0 0 16px;
    color: #444;
    font-size: 14px;
    line-height: 1.5;
}

.mba-booking-success-ab iframe {
    width: 100%;
    min-height: 720px;
    border: 0;
    border-radius: 4px;
    background: #fff;
}

.mba-booking-success-ab .mba-booking-fallback {
    margin-top: 10px;
    font-size: 13px;
    color: #555;
}

.mba-booking-success-ab .mba-booking-fallback a {
    color: var(--ast-global-color-0);
    text-decoration: underline;
}
</style>

<?php fa_ab_print_variation_bootstrap(); ?>

<form class="mba-full-consultation-form-ab"
      action="#"
      method="post"
      enctype="multipart/form-data">

    <input type="hidden" name="g-recaptcha-response" value="">
    <input type="hidden" name="action" value="mba_full_consultation_submit_ab">
    <input type="hidden" name="completed_full_form" value="True">
    <input type="hidden" name="code" value="MBAMain">
    <input type="hidden" name="ab_variation" value="<?php echo esc_attr($fa_ab_variation); ?>">
    <?php wp_nonce_field('mba_full_consultation_nonce_ab', 'mba_full_consultation_nonce_field_ab'); ?>

    <!-- Prefilled from Form 1 — passed through as hidden fields so the Podio webhook still receives them -->
    <input type="hidden" name="first_name"  value="<?php echo esc_attr($fa_prefill['first_name']); ?>">
    <input type="hidden" name="last_name"   value="<?php echo esc_attr($fa_prefill['last_name']); ?>">
    <input type="hidden" name="email"       value="<?php echo esc_attr($fa_prefill['email']); ?>">
    <input type="hidden" name="phone"       value="<?php echo esc_attr($fa_prefill['phone']); ?>">
    <input type="hidden" name="sms_consent" value="<?php echo esc_attr($fa_prefill['sms_consent']); ?>">
    <input type="hidden" name="country"     value="<?php echo esc_attr($fa_prefill['country']); ?>">
    <input type="hidden" name="hear_about_us" value="<?php echo esc_attr($fa_prefill['hear_about_us']); ?>">

    <!-- Resume: input kept in DOM (hidden) so the sessionStorage restore from Form 1 still populates it on submit -->
    <input
        type="file"
        id="resume-ab-2"
        name="resume"
        accept=".pdf,.doc,.docx"
        style="display: none;"
        tabindex="-1"
        aria-hidden="true"
    >

    <!-- Employer / Job -->
    <div class="form-row">
        <div class="form-group">
            <label for="employer-ab">Current Employer</label>
            <input type="text" id="employer-ab" name="current_employer">
        </div>

        <div class="form-group">
            <label for="job-title-ab">Job Title / Function</label>
            <input type="text" id="job-title-ab" name="job_title_function">
        </div>
    </div>

    <!-- Industry -->
    <div class="form-group bottom-margin">
      <label for="industry-ab">Current Industry</label>
      <select id="industry-ab" name="current_industry">
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
            <label for="university-ab">Undergrad University</label>
            <input type="text" id="university-ab" name="undergrad_university">
        </div>

        <div class="form-group">
            <label for="degree-ab">Degree and Major</label>
            <input type="text" id="degree-ab" name="degree_major">
        </div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="gpa-ab">GPA</label>
            <input type="text" id="gpa-ab" name="gpa">
        </div>

        <div class="form-group">
            <label for="graduation-year-ab">Graduation Year</label>
            <input type="text" id="graduation-year-ab" name="graduation_year">
        </div>
    </div>

    <!-- Tests -->
    <div class="form-group bottom-margin">
    <label for="tests-ab">Which tests have you taken (GMAT/GRE)? <span class="asterisk">*</span></label>
    <select id="tests-ab" name="tests_taken" required>
        <option value="">Please Select</option>
        <option value="GMAT">GMAT</option>
        <option value="GRE">GRE</option>
        <option value="Neither GMAT nor GRE">Neither GMAT nor GRE</option>
        <option value="Both GMAT and GRE">Both GMAT and GRE</option>
    </select>
</div>

<div id="gmat-fields-ab" class="form-row test-score-fields" style="display:none;">
    <div class="form-group">
        <label for="gmat-score-ab">Best GMAT Score <span class="asterisk">*</span></label>
        <input type="text" id="gmat-score-ab" name="gmat_score">
    </div>
    <div class="form-group">
        <label for="gmat-quant-ab">GMAT Quant</label>
        <input type="text" id="gmat-quant-ab" name="gmat_quant">
    </div>
    <div class="form-group">
        <label for="gmat-verbal-ab">GMAT Verbal</label>
        <input type="text" id="gmat-verbal-ab" name="gmat_verbal">
    </div>
</div>

<div id="gre-fields-ab" class="form-row test-score-fields" style="display:none;">
    <div class="form-group">
        <label for="gre-score-ab">Best GRE Score <span class="asterisk">*</span></label>
        <input type="text" id="gre-score-ab" name="gre_score">
    </div>
    <div class="form-group">
        <label for="gre-quant-ab">GRE Quant</label>
        <input type="text" id="gre-quant-ab" name="gre_quant">
    </div>
    <div class="form-group">
        <label for="gre-verbal-ab">GRE Verbal</label>
        <input type="text" id="gre-verbal-ab" name="gre_verbal">
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
                <input type="checkbox" name="business_schools[]" value="Oxford Saïd">
                Oxford Saïd
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
          <label for="start-date-ab">When do you hope to start your MBA / Masters?</label>
          <select id="start-date-ab" name="mba_start_date">
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
                    <input type="radio" id="applied-yes-ab" name="previously_applied_mba" value="Yes">
                    <label for="applied-yes-ab">Yes</label>
                </div>

                <div class="radio-item">
                    <input type="radio" id="applied-no-ab" name="previously_applied_mba" value="No" checked>
                    <label for="applied-no-ab">No</label>
                </div>
            </div>
        </div>
    </div>

    <!-- Service -->
    <div class="form-group bottom-margin">
        <label for="service-ab">Which Fortuna Admissions service are you interested in?</label>
        <select id="service-ab" name="fortuna_service">
            <option value="">Please Select</option>
            <option value="All-Inclusive Package">All-Inclusive Package</option>
            <option value="Hourly Consulting">Hourly Consulting</option>
            <option value="MBA Interview Prep">MBA Interview Prep</option>
            <option value="Other">Other</option>
        </select>
    </div>

    <!-- Referred by -->
    <div class="form-group bottom-margin">
        <label for="referred-by-ab">If you were referred by someone, please enter their name:</label>
        <input type="text" id="referred-by-ab" name="referred_by_name">
    </div>

    <!-- Submit -->
    <button type="submit">Submit</button>

</form>

<script>
(function () {
    const form = document.querySelector(".mba-full-consultation-form-ab");
    if (!form) return;

    try {
        const resumeInput = form.querySelector('input[name="resume"]');
        const raw = sessionStorage.getItem("mba_resume_ab");

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
        ["first_name", "last_name", "email", "phone", "country", "sms_consent", "hear_about_us", "ab_variation"].forEach(function (k) {
            url.searchParams.delete(k);
        });
        window.history.replaceState({}, "", url.pathname + (url.search ? url.search : "") + url.hash);
    }

    // Ensure the hidden ab_variation input is populated: Form 1 passes it in via
    // the query string, but a visitor landing here directly (bookmark, back
    // button, etc.) relies on the cookie bootstrap to resolve a bucket.
    const abField = form.querySelector('input[name="ab_variation"]');
    if (abField && !abField.value && typeof window.faAbResolveVariation === "function") {
        abField.value = window.faAbResolveVariation();
    }
})();
</script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const form = document.querySelector(".mba-full-consultation-form-ab");
    if (!form) return;

    const tokenField = form.querySelector('input[name="g-recaptcha-response"]');
    const tests = form.querySelector("#tests-ab");
    const gmatFields = form.querySelector("#gmat-fields-ab");
    const greFields = form.querySelector("#gre-fields-ab");
    const ajaxUrl = "<?php echo esc_url(admin_url('admin-ajax.php')); ?>";
    const thankYouUrl = "/mba/free-consultation-thank-you/";
    const abField = form.querySelector('input[name="ab_variation"]');
    const firstNameField = form.querySelector('input[name="first_name"]');

    function renderBookingCalendar(calendarUrl, coachName) {
        const safeName = (coachName || "").replace(/[<>"']/g, "");
        const firstName = (firstNameField && firstNameField.value) || "";
        const greeting = firstName
            ? ("Thanks, " + firstName.replace(/[<>"']/g, "") + "!")
            : "Thanks!";
        const coachLine = safeName
            ? ("Pick a convenient time below to meet with your dedicated coach, " + safeName + ".")
            : "Pick a convenient time below for your free consultation.";

        const wrapper = document.createElement("div");
        wrapper.className = "mba-booking-success-ab";
        wrapper.innerHTML =
            "<h3>" + greeting + " Your consultation form has been received.</h3>" +
            "<p>" + coachLine + "</p>" +
            "<iframe src=\"" + calendarUrl + "\" title=\"Book your free consultation\" loading=\"lazy\" allow=\"clipboard-write; fullscreen\"></iframe>" +
            "<p class=\"mba-booking-fallback\">Having trouble with the calendar above? " +
            "<a href=\"" + calendarUrl + "\" target=\"_blank\" rel=\"noopener\">Open the booking page in a new tab.</a></p>";

        form.parentNode.replaceChild(wrapper, form);

        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({
            event: "MBABookingCalendarShown",
            mba_ab_variation: "B",
            mba_coach: safeName
        });
    }

    function updateTestFields() {
        if (!tests) return;

        const value = tests.value;

        if (gmatFields) {
            gmatFields.style.display = (value === "GMAT" || value === "Both GMAT and GRE") ? "flex" : "none";
        }

        if (greFields) {
            greFields.style.display = (value === "GRE" || value === "Both GMAT and GRE") ? "flex" : "none";
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
            event: "MBAFullFormSubmitAB",
            mba_ab_variation: (abField && abField.value) || "A"
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
                    try { sessionStorage.removeItem("mba_resume_ab"); } catch (err) {}

                    // Variation B — server returns the Appointlet URL for the
                    // visitor's country. Render the calendar in place of the
                    // form instead of redirecting to the thank-you page.
                    const calendarUrl = result.data && result.data.calendar_url;
                    if (calendarUrl) {
                        renderBookingCalendar(calendarUrl, result.data && result.data.coach_name);
                        return;
                    }

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
        }, 1500);

        grecaptcha.ready(function () {
            grecaptcha.execute("6LevnXYtAAAAAMJD8mj2aeDja_yK6R20db50KgpD", {
                action: "mba_full_consultation_ab"
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
