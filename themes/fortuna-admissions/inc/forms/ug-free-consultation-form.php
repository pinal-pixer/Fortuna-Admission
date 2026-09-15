<style>
.ug-consultation-form {
    max-width: 100%;
}

.ug-consultation-form .form-row {
    display: flex;
    gap: 25px;
    margin-bottom: 20px;
}

.ug-consultation-form .form-group {
    flex: 1;
}

.ug-consultation-form .form-group label {
    display: flex;
  	gap: 2px;
    margin-bottom: 6px;
    font-weight: 600;
    font-size: 14px;
    line-height: 20px;
}

.ug-consultation-form .form-group input[type="text"],
.ug-consultation-form .form-group input[type="email"],
.ug-consultation-form .form-group input[type="tel"],
.ug-consultation-form .form-group input[type="url"],
.ug-consultation-form .form-group textarea {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-sizing: border-box;
}

.ug-consultation-form .form-group textarea {
    resize: vertical;
}

.ug-consultation-form button {
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

.ug-consultation-form button:hover {
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

.ug-consultation-form select {
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

.ug-consultation-form .form-group select:focus {
    outline: none;
    border-color: #5b9dd9;
}

.ug-consultation-form .i-am-a-label {
    font-weight: 700;
    margin-bottom: 12px;
}

.ug-consultation-form .radio-group {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    align-items: center;
}

.ug-consultation-form .radio-label {
    display: flex;
    align-items: center;
    gap: 6px !important;
    margin-bottom: 0 !important;
    font-weight: 400 !important;
    cursor: pointer;
    font-size: 18px;
}

.ug-consultation-form .radio-label input[type="radio"] {
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    width: 16px;
    height: 16px;
    border: 1px solid #808285;
    border-radius: 50%;
    background: #fff;
    margin: 0;
    cursor: pointer;
    position: relative;
    flex-shrink: 0;
    transition: border-color 0.15s ease-in-out;
}

.ug-consultation-form .radio-label input[type="radio"]:checked {
    border-color: #1e3ad6;
}

.ug-consultation-form .radio-label input[type="radio"]:checked::after {
    content: "";
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #1e3ad6;
}

.ug-consultation-form .radio-label input[type="radio"]:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(30, 58, 214, 0.15);
}

.ug-consultation-form .checkbox-group {
    display: flex;
    flex-wrap: wrap;
    gap: 10px 20px;
}

.ug-consultation-form .checkbox-group label {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 0;
    font-weight: 400;
    cursor: pointer;
}

.ug-consultation-form .checkbox-group input[type="checkbox"] {
    width: auto;
    margin: 0;
}

.ug-consultation-form .parent-section,
.ug-consultation-form .student-section {
    display: block;
}

@media (max-width: 768px) {
    .ug-consultation-form .form-row {
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
    class="ug-consultation-form"
>

    <input type="hidden" name="action" value="ug_consultation_submit">

    <input type="hidden" name="ug_consultation_submit" value="1">

    <?php wp_nonce_field( 'ug_consultation_nonce', 'ug_consultation_nonce_field' ); ?>

    <div class="form-group bottom-margin i-am-a-group">
        <label class="i-am-a-label">
            I am a: <span class="asterisk">*</span>
        </label>

        <div class="radio-group">
            <label class="radio-label">
                <input type="radio" name="i_am_a" value="Student" required>
                <span>Student</span>
            </label>
            <label class="radio-label">
                <input type="radio" name="i_am_a" value="Parent" required>
                <span>Parent</span>
            </label>
        </div>
    </div>

    <div class="student-section">
        <div class="form-row">
            <div class="form-group">
                <label>
                    Student Name <span class="asterisk">*</span>
                </label>
                <input
                    type="text"
                    name="student_first_name"
                    placeholder="First"
                    required
                >
            </div>

            <div class="form-group">
                <label class="last-name-label">Student Name</label>
                <input
                    type="text"
                    name="student_last_name"
                    placeholder="Last"
                    required
                >
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Student Email</label>
                <input
                    type="email"
                    name="student_email"
                >
            </div>

            <div class="form-group">
                <label>Student Phone</label>
                <input
                    type="tel"
                    name="student_phone"
                >
            </div>
        </div>

        <div class="form-group bottom-margin">
            <label class="radio-label" style="font-weight:400;font-size:14px;align-items: flex-start;">
                <input type="checkbox" name="student_sms" value="yes" style="margin-top: 4px;">
                I consent to receive SMS messages from Fortuna Admissions at the student phone number provided.
            </label>
        </div>
    </div>

    <div class="parent-section">
        <div class="form-row">
            <div class="form-group">
                <label>
                    Parent Name <span class="asterisk">*</span>
                </label>
                <input
                    type="text"
                    name="parent_first_name"
                    placeholder="First"
                    required
                >
            </div>

            <div class="form-group">
                <label class="last-name-label">Parent Name</label>
                <input
                    type="text"
                    name="parent_last_name"
                    placeholder="Last"
                    required
                >
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>
                    Parent Email <span class="asterisk">*</span>
                </label>
                <input
                    type="email"
                    name="parent_email"
                    required
                >
            </div>

            <div class="form-group">
                <label>Parent Phone</label>
                <input
                    type="tel"
                    name="parent_phone"
                >
            </div>
        </div>

        <div class="form-group bottom-margin">
            <label class="radio-label" style="font-weight:400;font-size:14px;align-items: flex-start;">
                <input type="checkbox" name="parent_sms" value="yes" style="margin-top: 4px;">
                I consent to receive SMS messages from Fortuna Admissions at the parent phone number provided.
            </label>
        </div>
    </div>

    <div class="form-group bottom-margin">
        <label>
            Country of Residence <span class="asterisk">*</span>
        </label>

        <select name="country_of_residence" required>
            <option value="">Please Select</option>
            <option value="United States">United States</option>
            <option value="United Kingdom">United Kingdom</option>
            <option value="Canada">Canada</option>
            <option value="Australia">Australia</option>
            <option value="India">India</option>
            <option value="China">China</option>
            <option value="Singapore">Singapore</option>
            <option value="Hong Kong">Hong Kong</option>
            <option value="United Arab Emirates">United Arab Emirates</option>
            <option value="Saudi Arabia">Saudi Arabia</option>
            <option value="Germany">Germany</option>
            <option value="France">France</option>
            <option value="Italy">Italy</option>
            <option value="Spain">Spain</option>
            <option value="Netherlands">Netherlands</option>
            <option value="Switzerland">Switzerland</option>
            <option value="Sweden">Sweden</option>
            <option value="Norway">Norway</option>
            <option value="Denmark">Denmark</option>
            <option value="Finland">Finland</option>
            <option value="Ireland">Ireland</option>
            <option value="Belgium">Belgium</option>
            <option value="Austria">Austria</option>
            <option value="Portugal">Portugal</option>
            <option value="Greece">Greece</option>
            <option value="Poland">Poland</option>
            <option value="Russia">Russia</option>
            <option value="Turkey">Turkey</option>
            <option value="Israel">Israel</option>
            <option value="Qatar">Qatar</option>
            <option value="Kuwait">Kuwait</option>
            <option value="Bahrain">Bahrain</option>
            <option value="Oman">Oman</option>
            <option value="Egypt">Egypt</option>
            <option value="South Africa">South Africa</option>
            <option value="Nigeria">Nigeria</option>
            <option value="Kenya">Kenya</option>
            <option value="Ghana">Ghana</option>
            <option value="Morocco">Morocco</option>
            <option value="Japan">Japan</option>
            <option value="South Korea">South Korea</option>
            <option value="Taiwan">Taiwan</option>
            <option value="Thailand">Thailand</option>
            <option value="Vietnam">Vietnam</option>
            <option value="Malaysia">Malaysia</option>
            <option value="Indonesia">Indonesia</option>
            <option value="Philippines">Philippines</option>
            <option value="Pakistan">Pakistan</option>
            <option value="Bangladesh">Bangladesh</option>
            <option value="Sri Lanka">Sri Lanka</option>
            <option value="Nepal">Nepal</option>
            <option value="Mexico">Mexico</option>
            <option value="Brazil">Brazil</option>
            <option value="Argentina">Argentina</option>
            <option value="Chile">Chile</option>
            <option value="Colombia">Colombia</option>
            <option value="Peru">Peru</option>
            <option value="Venezuela">Venezuela</option>
            <option value="New Zealand">New Zealand</option>
            <option value="Other">Other</option>
        </select>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label>High School Name</label>
            <input
                type="text"
                name="high_school_name"
            >
        </div>

        <div class="form-group">
            <label>High School Location</label>
            <select name="high_school_location">
                <option value="">Please Select</option>
                <option value="United States">United States</option>
                <option value="United Kingdom">United Kingdom</option>
                <option value="Canada">Canada</option>
                <option value="Australia">Australia</option>
                <option value="India">India</option>
                <option value="China">China</option>
                <option value="Singapore">Singapore</option>
                <option value="Hong Kong">Hong Kong</option>
                <option value="United Arab Emirates">United Arab Emirates</option>
                <option value="Saudi Arabia">Saudi Arabia</option>
                <option value="Germany">Germany</option>
                <option value="France">France</option>
                <option value="Switzerland">Switzerland</option>
                <option value="Netherlands">Netherlands</option>
                <option value="Ireland">Ireland</option>
                <option value="Japan">Japan</option>
                <option value="South Korea">South Korea</option>
                <option value="Malaysia">Malaysia</option>
                <option value="Thailand">Thailand</option>
                <option value="Indonesia">Indonesia</option>
                <option value="Philippines">Philippines</option>
                <option value="Pakistan">Pakistan</option>
                <option value="Mexico">Mexico</option>
                <option value="Brazil">Brazil</option>
                <option value="New Zealand">New Zealand</option>
                <option value="Other">Other</option>
            </select>
        </div>
    </div>

    <div class="form-group bottom-margin">
        <label>
            High School Graduation Year <span class="asterisk">*</span>
        </label>

        <select name="high_school_graduation_year" required>
            <option value="">Please Select</option>
            <?php
            $current_year = (int) date( 'Y' );
            for ( $y = $current_year; $y <= $current_year + 8; $y++ ) {
                echo '<option value="' . esc_attr( $y ) . '">' . esc_html( $y ) . '</option>';
            }
            ?>
            <option value="Already Graduated">Already Graduated</option>
        </select>
    </div>

    <div class="form-group bottom-margin">
        <label>
            High School Qualifications <span class="asterisk">*</span>
        </label>

        <div class="checkbox-group">
            <label>
                <input type="checkbox" name="high_school_qualifications[]" value="US High School Diploma">
                US High School Diploma
            </label>
            <label>
                <input type="checkbox" name="high_school_qualifications[]" value="AP Credits">
                AP Credits
            </label>
            <label>
                <input type="checkbox" name="high_school_qualifications[]" value="A Levels">
                A Levels
            </label>
            <label>
                <input type="checkbox" name="high_school_qualifications[]" value="IB">
                IB
            </label>
            <label>
                <input type="checkbox" name="high_school_qualifications[]" value="Other" id="qualifications_other">
                Other
            </label>
        </div>
    </div>

    <div class="form-group bottom-margin">
        <label>
            How did you hear about Fortuna Admissions? <span class="asterisk">*</span>
        </label>

        <div class="checkbox-group">
            <label>
                <input type="checkbox" name="fortuna_discovery[]" value="Personal Recommendation">
                Personal Recommendation
            </label>
            <label>
                <input type="checkbox" name="fortuna_discovery[]" value="Search Engine">
                Search Engine
            </label>
            <label>
                <input type="checkbox" name="fortuna_discovery[]" value="ChatGPT or other AI">
                ChatGPT or other AI
            </label>
            <label>
                <input type="checkbox" name="fortuna_discovery[]" value="Google Ad">
                Google Ad
            </label>
            <label>
                <input type="checkbox" name="fortuna_discovery[]" value="YouTube">
                YouTube
            </label>
            <label>
                <input type="checkbox" name="fortuna_discovery[]" value="LinkedIn">
                LinkedIn
            </label>
            <label>
                <input type="checkbox" name="fortuna_discovery[]" value="Instagram">
                Instagram
            </label>
            <label>
                <input type="checkbox" name="fortuna_discovery[]" value="Facebook">
                Facebook
            </label>
            <label>
                <input type="checkbox" name="fortuna_discovery[]" value="Reddit">
                Reddit
            </label>
            <label>
                <input type="checkbox" name="fortuna_discovery[]" value="School Counselor">
                School Counselor
            </label>
            <label>
                <input type="checkbox" name="fortuna_discovery[]" value="Other" id="discovery_other">
                Other
            </label>
        </div>
    </div>

    <input type="hidden" name="code" value="UGMain">
    <input type="hidden" id="g-recaptcha-response" name="g-recaptcha-response">

    <!-- Honeypot -->
    <div style="display:none;">
        <input type="text" name="website" autocomplete="off" tabindex="-1">
    </div>

    <button type="submit">
        Submit
    </button>

    <script>
        document.addEventListener("DOMContentLoaded", function () {

            function bindOtherToggle(checkboxId, inputWrapperId) {
                const cb = document.getElementById(checkboxId);
                const wrap = document.getElementById(inputWrapperId);

                if (!cb || !wrap) return;

                cb.addEventListener("change", function () {
                    if (cb.checked) {
                        wrap.classList.add("active");
                    } else {
                        wrap.classList.remove("active");
                    }
                });
            }

            bindOtherToggle("qualifications_other", "qualifications_other_input");
            bindOtherToggle("discovery_other", "discovery_other_input");

            var studentPhone  = document.querySelector('input[name="student_phone"]');
            var studentSms    = document.querySelector('input[name="student_sms"]');
            var parentPhone   = document.querySelector('input[name="parent_phone"]');
            var parentSms     = document.querySelector('input[name="parent_sms"]');

            if (studentPhone && studentSms) {
                studentPhone.addEventListener("input", function () {
                    if (studentPhone.value.trim().length > 0) {
                        studentSms.checked = true;
                    }
                });
            }

            if (parentPhone && parentSms) {
                parentPhone.addEventListener("input", function () {
                    if (parentPhone.value.trim().length > 0) {
                        parentSms.checked = true;
                    }
                });
            }

        });
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function () {

            const form       = document.querySelector(".ug-consultation-form");
            const tokenField = document.getElementById("g-recaptcha-response");

            if (!form || !tokenField) return;

            form.addEventListener("submit", function (e) {
                e.preventDefault();

                if (!form.checkValidity()) {
                    form.reportValidity();
                    return;
                }

                const btn = form.querySelector('button[type="submit"]');
                if (btn) {
                    btn.disabled    = true;
                    btn.textContent = "Submitting...";
                }

                window.dataLayer = window.dataLayer || [];
                window.dataLayer.push({ event: "UGFormSubmit" });

                function submitForm(token) {
                    tokenField.value = token || "";
                    form.submit();
                }

                if (typeof grecaptcha === "undefined") {
                    submitForm("");
                    return;
                }

                var fallback = setTimeout(function () { submitForm(""); }, 4000);

                grecaptcha.ready(function () {
                    grecaptcha.execute("6LevnXYtAAAAAMJD8mj2aeDja_yK6R20db50KgpD", {
                        action: "ug_consultation"
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
</form>