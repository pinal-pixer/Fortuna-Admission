<style>
.mba-full-consultation-form {
    max-width: 100%;
}

.mba-full-consultation-form .form-row {
    display: flex;
    gap: 25px;
    margin-bottom: 20px;
}

.mba-full-consultation-form .form-row.form-row-stacked {
    flex-direction: column;
    gap: 20px;
}

.mba-full-consultation-form .form-group {
    flex: 1;
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

.mba-full-consultation-form .field-note {
    display: block;
    margin: 0;
    margin-bottom: 6px;
    font-size: 12px;
    line-height: 16px;
    color: #555;
    font-weight: 400;
}

.mba-full-consultation-form .consent-group {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    margin: -14px 0 20px;
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

.mba-full-consultation-form .mba-full-consultation-success {
    padding: 20px 0;
}

.mba-full-consultation-form .mba-full-consultation-success p {
    margin: 0;
    font-size: 18px;
    line-height: 28px;
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


<form
    method="post"
    action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
    class="mba-full-consultation-form"
    enctype="multipart/form-data"
>

    <input type="hidden" name="action" value="mba_full_consultation_submit">

    <input type="hidden" name="mba_full_consultation_submit" value="1">

    <?php wp_nonce_field( 'mba_full_consultation_nonce', 'mba_full_consultation_nonce_field' ); ?>


    <div class="form-row">

        <div class="form-group">
            <label>
                Name <span class="asterisk">*</span>
            </label>

            <input
                type="text"
                name="first_name"
                placeholder="First"
                required
            >
        </div>


        <div class="form-group">
            <label class="last-name-label">Name</label>

            <input
                type="text"
                name="last_name"
                placeholder="Last"
                required
            >
        </div>

    </div>


    <div class="form-row form-row-stacked">

        <div class="form-group">
            <label>
                Email <span class="asterisk">*</span>
            </label>

            <input
                type="email"
                name="email"
                required
            >
        </div>


        <div class="form-group">
            <label>
                Phone
            </label>

            <input
                type="tel"
                name="phone"
                placeholder="+1 555 123 4567"
            >
        </div>

    </div>


    <div class="consent-group">

        <input
            type="checkbox"
            name="sms_consent"
            id="mba_full_sms_consent"
            value="Yes"
        >

        <label for="mba_full_sms_consent">
            I agree to receive promotional messages from Fortuna Admissions at the phone number provided.
        </label>

    </div>


    <div class="form-group bottom-margin">

        <label>
            How did you hear about Fortuna? <span class="asterisk">*</span>
        </label>

        <select name="hear_about_us" required>

            <option value="">Please Select</option>
            <option value="Personal Recommendation">Personal Recommendation</option>
            <option value="Search Engine">Search Engine</option>
            <option value="ChatGPT or other AI">ChatGPT or other AI</option>
            <option value="Google Ad">Google Ad</option>
            <option value="YouTube">YouTube</option>
            <option value="LinkedIn">LinkedIn</option>
            <option value="Instagram">Instagram</option>
            <option value="Facebook">Facebook</option>
            <option value="Reddit">Reddit</option>
            <option value="Career Services at My University">Career Services at My University</option>
            <option value="Other">Other</option>

        </select>

    </div>


    <div class="form-group bottom-margin">
        <p class="field-note">To help us understand your background, please provide us with your LinkedIn profile or Resume.</p>
        <label>
            LinkedIn Profile URL <span class="asterisk">*</span>
        </label>

        <input
            type="url"
            name="linkedin_url"
            placeholder="https://www.linkedin.com/in/your-profile"
            required
        >

    </div>


    <div class="form-group bottom-margin">
        <label>
            Upload your Resume
        </label>

        <input
            type="file"
            name="resume"
            accept=".pdf,.doc,.docx"
        >

    </div>


    <div class="form-group bottom-margin">

        <label>
            Please provide any further information that will be helpful context ahead of our free consultation call
        </label>

        <textarea
            name="additional_information"
            rows="5"
        ></textarea>

    </div>


    <input type="hidden" name="code" value="MBAFull">

    <input
        type="hidden"
        id="g-recaptcha-response"
        name="g-recaptcha-response"
    >


    <!-- Honeypot -->
    <div style="display:none;">

        <input
            type="text"
            name="website"
            autocomplete="off"
            tabindex="-1"
        >

    </div>


    <button type="submit">
        Submit
    </button>


    <script>
document.addEventListener("DOMContentLoaded", function () {

    window.dataLayer = window.dataLayer || [];

    const form = document.querySelector(".mba-full-consultation-form");

    if (!form) {
        return;
    }

    const tokenField = form.querySelector('input[name="g-recaptcha-response"]');
    const ajaxUrl = "<?php echo esc_url(admin_url('admin-ajax.php')); ?>";

    const phoneInput = form.querySelector('input[name="phone"]');
    const consentCb = document.getElementById("mba_full_sms_consent");
    const submitButton = form.querySelector('button[type="submit"]');

    if (phoneInput && consentCb) {

        phoneInput.addEventListener("input", function () {

            if (phoneInput.value.trim().length > 0) {
                consentCb.checked = true;
            }

        });

    }


    form.addEventListener("submit", function (event) {

        event.preventDefault();


        if (!form.checkValidity()) {

            form.reportValidity();

            return;
        }


        if (submitButton) {

            submitButton.disabled = true;
            submitButton.textContent = "Submitting...";

        }


        function submitForm(token) {

            if (tokenField) {
                tokenField.value = token || "";
            }

            const formData = new FormData(form);


            fetch(ajaxUrl, {
                method: "POST",
                body: formData
            })

            .then(function (response) {

                if (!response.ok) {
                    throw new Error("HTTP error: " + response.status);
                }

                return response.json();

            })

            .then(function (result) {

                if (result && result.success) {

                    window.dataLayer.push({
                        event: "MBAFullFormSubmit",
                        form_id: "mba_full_consultation_form",
                        form_name: "MBA Full Consultation Form",
                        form_status: "success"
                    });


                    form.innerHTML = `
                        <div class="mba-full-consultation-success">
                            <p>
                                Thanks for sharing this very helpful
                                background information, which will be
                                invaluable for our call together.
                                We will be in touch soon.
                            </p>
                        </div>
                    `;


                } else {

                    if (submitButton) {

                        submitButton.disabled = false;
                        submitButton.textContent = "Submit";

                    }


                    alert(
                        result &&
                        result.data &&
                        result.data.message
                            ? result.data.message
                            : "Something went wrong. Please try again."
                    );

                }

            })

            .catch(function (error) {

                console.error("Form submission error:", error);


                if (submitButton) {

                    submitButton.disabled = false;
                    submitButton.textContent = "Submit";

                }


                alert(
                    "Something went wrong while submitting the form. Please try again."
                );

            });

        }


        if (
            typeof grecaptcha === "undefined" ||
            !tokenField
        ) {

            submitForm("");

            return;

        }


        let completed = false;


        const fallback = setTimeout(function () {

            if (!completed) {

                completed = true;

                submitForm("");

            }

        }, 4000);


        grecaptcha.ready(function () {

            grecaptcha.execute(
                "6LevnXYtAAAAAMJD8mj2aeDja_yK6R20db50KgpD",
                {
                    action: "mba_full_consultation"
                }
            )

            .then(function (token) {

                if (completed) {
                    return;
                }

                completed = true;

                clearTimeout(fallback);

                submitForm(token);

            })

            .catch(function (error) {

                if (completed) {
                    return;
                }

                completed = true;

                clearTimeout(fallback);

                console.warn(
                    "reCAPTCHA failed:",
                    error
                );

                submitForm("");

            });

        });

    });

});
</script>
</form>
