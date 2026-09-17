<style>
.mba-simple-consultation-form {
    max-width: 100%;
    font-family: Arial, sans-serif;
    font-size: 13px;
}

.mba-simple-consultation-form .form-row {
    display: flex;
    gap: 25px;
    margin-bottom: 20px;
}

.mba-simple-consultation-form .form-group {
    flex: 1;
    min-width: 0;
}

.mba-simple-consultation-form .form-group label {
    display: block;
    margin-bottom: 6px;
    font-weight: 600;
    font-size: 14px;
    line-height: 20px;
}

.mba-simple-consultation-form .form-group input,
.mba-simple-consultation-form .form-group textarea,
.mba-simple-consultation-form .form-group select {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-sizing: border-box;
    font-size: 13px;
    font-family: inherit;
    min-height: 40px;
}

.mba-simple-consultation-form .form-group select {
    padding: 8px 45px 8px 12px;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    background: #fff
        url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='18' height='18' viewBox='0 0 24 24' fill='none' stroke='%23333' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E")
        no-repeat right 8px center;
    background-size: 18px;
}

.mba-simple-consultation-form .form-group input:focus,
.mba-simple-consultation-form .form-group select:focus,
.mba-simple-consultation-form .form-group textarea:focus {
    outline: none;
    border-color: #5b9dd9;
}

.mba-simple-consultation-form button {
    padding: 14px 30px;
    background: linear-gradient(
        90deg,
        var(--ast-global-color-0, #666) 0%,
        var(--ast-global-color-1, #888) 100%
    );
    color: #fff;
    border: none;
    cursor: pointer;
    text-transform: uppercase;
    border-radius: 3px;
}

.mba-simple-consultation-form button:hover {
    background: var(--ast-global-color-0, #666);
}

.asterisk {
    color: #CC2A24;
}

.form-title {
    margin: 0 0 28px;
    color: #f47721;
    font-size: 22px;
    line-height: 28px;
    font-weight: 700;
    text-transform: uppercase;
}

.file-input {
    padding: 8px 10px !important;
    min-height: 46px !important;
    display: flex;
    align-items: center;
}

.file-input::file-selector-button {
    margin-right: 8px;
    padding: 5px 10px;
    border: 1px solid #777;
    border-radius: 3px;
    background: #fff;
    cursor: pointer;
}

.full-width {
    width: 100%;
}

.checkbox-section {
    margin-bottom: 20px;
}

.checkbox-section > label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    font-size: 14px;
    line-height: 20px;
}

.checkbox-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    column-gap: 45px;
    row-gap: 9px;
}

.checkbox-item {
    display: flex;
    align-items: center;
    gap: 7px;
    min-width: 0;
}

.checkbox-item input[type="checkbox"] {
    width: 13px;
    height: 13px;
    min-height: auto;
    margin: 0;
    flex: 0 0 auto;
}

.checkbox-item label {
    margin: 0;
    font-size: 13px;
    line-height: 18px;
    font-weight: 400;
}

.other-input {
    width: 100% !important;
    min-height: 28px !important;
    padding: 4px 10px !important;
}

.radio-group {
    display: flex;
    gap: 12px;
    align-items: center;
    padding-top: 4px;
}

.radio-item {
    display: flex;
    align-items: center;
    gap: 5px;
}

.radio-item input[type="radio"] {
    width: 14px;
    height: 14px;
    min-height: auto;
    margin: 0;
}

.radio-item label {
    margin: 0 !important;
    font-size: 13px !important;
    font-weight: 400 !important;
}

@media (max-width: 768px) {
    .mba-simple-consultation-form .form-row {
        flex-direction: column;
        gap: 15px;
    }

    .checkbox-grid {
        grid-template-columns: 1fr;
        row-gap: 9px;
    }

    .form-title {
        font-size: 20px;
    }
}
</style>
</head>

<body>

<form class="mba-simple-consultation-form"
      action="#"
      method="post"
      enctype="multipart/form-data">

    <input type="hidden" name="g-recaptcha-response" value="">

    <!-- Name -->
    <div class="form-row">
        <div class="form-group">
            <label for="first-name">
                Name <span class="asterisk">*</span>
            </label>
            <input type="text" id="first-name" name="first_name" placeholder="First" required>
        </div>

        <div class="form-group">
            <label for="last-name">&nbsp;</label>
            <input type="text" id="last-name" name="last_name" placeholder="Last">
        </div>
    </div>

    <!-- Email / Phone -->
    <div class="form-row">
        <div class="form-group">
            <label for="email">
                Email <span class="asterisk">*</span>
            </label>
            <input type="email" id="email" name="email" required>
        </div>

        <div class="form-group">
            <label for="phone">Phone</label>
            <input type="tel" id="phone" name="phone">
        </div>
    </div>

    <!-- Resume -->
    <div class="form-row">
        <div class="form-group">
            <label for="resume">Resume</label>
            <input
                class="file-input"
                type="file"
                id="resume"
                name="resume"
                accept=".pdf,.doc,.docx"
            >
        </div>

        <div class="form-group"></div>
    </div>

    <!-- City -->
    <div class="form-row">
        <div class="form-group">
            <label for="city">City of Residence</label>
            <input type="text" id="city" name="city_of_residence">
        </div>
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
    <div class="form-row">
        <div class="form-group">
            <label for="industry">Current Industry</label>
            <select id="industry" name="current_industry">
                <option value="" selected></option>
                <option value="Consulting">Consulting</option>
                <option value="Finance">Finance</option>
                <option value="Technology">Technology</option>
                <option value="Healthcare">Healthcare</option>
                <option value="Marketing">Marketing</option>
                <option value="Manufacturing">Manufacturing</option>
                <option value="Education">Education</option>
                <option value="Government">Government</option>
                <option value="Other">Other</option>
            </select>
        </div>

        <div class="form-group"></div>
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
    <div class="form-row">
        <div class="form-group">
            <label for="tests">
                Which tests have you taken (GMAT/<br>GRE)? <span class="asterisk">*</span>
            </label>
            <select id="tests" name="tests_taken" required>
                <option value="" selected></option>
                <option value="GMAT">GMAT</option>
                <option value="GRE">GRE</option>
                <option value="Both">Both</option>
                <option value="Neither">Neither</option>
            </select>
        </div>

        <div class="form-group"></div>
    </div>

    <!-- Business Schools -->
    <div class="checkbox-section">
        <label>
            Which Business Schools are you considering?<span class="asterisk">*</span>
        </label>

        <div class="checkbox-grid">

            <div class="checkbox-item">
                <input type="checkbox" id="harvard" name="business_schools[]" value="Harvard Business School">
                <label for="harvard">Harvard Business School</label>
            </div>

            <div class="checkbox-item">
                <input type="checkbox" id="nyu" name="business_schools[]" value="NYU Stern">
                <label for="nyu">NYU Stern</label>
            </div>

            <div class="checkbox-item">
                <input type="checkbox" id="london" name="business_schools[]" value="London Business School">
                <label for="london">London Business School</label>
            </div>

            <div class="checkbox-item">
                <input type="checkbox" id="stanford" name="business_schools[]" value="Stanford GSB">
                <label for="stanford">Stanford GSB</label>
            </div>

            <div class="checkbox-item">
                <input type="checkbox" id="yale" name="business_schools[]" value="Yale SOM">
                <label for="yale">Yale SOM</label>
            </div>

            <div class="checkbox-item">
                <input type="checkbox" id="cambridge" name="business_schools[]" value="Cambridge Judge">
                <label for="cambridge">Cambridge Judge</label>
            </div>

            <div class="checkbox-item">
                <input type="checkbox" id="wharton" name="business_schools[]" value="Wharton">
                <label for="wharton">Wharton</label>
            </div>

            <div class="checkbox-item">
                <input type="checkbox" id="tuck" name="business_schools[]" value="Dartmouth Tuck">
                <label for="tuck">Dartmouth Tuck</label>
            </div>

            <div class="checkbox-item">
                <input type="checkbox" id="oxford" name="business_schools[]" value="Oxford Said">
                <label for="oxford">Oxford Said</label>
            </div>

            <div class="checkbox-item">
                <input type="checkbox" id="columbia" name="business_schools[]" value="Columbia Business School">
                <label for="columbia">Columbia Business School</label>
            </div>

            <div class="checkbox-item">
                <input type="checkbox" id="duke" name="business_schools[]" value="Duke Fuqua">
                <label for="duke">Duke Fuqua</label>
            </div>

            <div class="checkbox-item">
                <input type="checkbox" id="hec" name="business_schools[]" value="HEC Paris">
                <label for="hec">HEC Paris</label>
            </div>

            <div class="checkbox-item">
                <input type="checkbox" id="mit" name="business_schools[]" value="MIT Sloan">
                <label for="mit">MIT Sloan</label>
            </div>

            <div class="checkbox-item">
                <input type="checkbox" id="ucla" name="business_schools[]" value="UCLA Anderson">
                <label for="ucla">UCLA Anderson</label>
            </div>

            <div class="checkbox-item">
                <input type="checkbox" id="imd" name="business_schools[]" value="IMD">
                <label for="imd">IMD</label>
            </div>

            <div class="checkbox-item">
                <input type="checkbox" id="chicago" name="business_schools[]" value="Chicago Booth">
                <label for="chicago">Chicago Booth</label>
            </div>

            <div class="checkbox-item">
                <input type="checkbox" id="cornell" name="business_schools[]" value="Cornell Johnson">
                <label for="cornell">Cornell Johnson</label>
            </div>

            <div class="checkbox-item">
                <input type="checkbox" id="iese" name="business_schools[]" value="IESE">
                <label for="iese">IESE</label>
            </div>

            <div class="checkbox-item">
                <input type="checkbox" id="northwestern" name="business_schools[]" value="Northwestern Kellogg">
                <label for="northwestern">Northwestern Kellogg</label>
            </div>

            <div class="checkbox-item">
                <input type="checkbox" id="uva" name="business_schools[]" value="UVA Darden">
                <label for="uva">UVA Darden</label>
            </div>

            <div class="checkbox-item">
                <input type="checkbox" id="ie" name="business_schools[]" value="IE">
                <label for="ie">IE</label>
            </div>

            <div class="checkbox-item">
                <input type="checkbox" id="berkeley" name="business_schools[]" value="UC Berkeley Haas">
                <label for="berkeley">UC Berkeley Haas</label>
            </div>

            <div class="checkbox-item">
                <input type="checkbox" id="insead" name="business_schools[]" value="INSEAD">
                <label for="insead">INSEAD</label>
            </div>

            <div class="checkbox-item">
                <input type="checkbox" id="other" name="business_schools[]" value="Other">
                <label for="other">Other</label>
                <input class="other-input" type="text" name="business_school_other">
            </div>

        </div>
    </div>

    <!-- Start MBA / Previous MBA -->
    <div class="form-row">
        <div class="form-group">
            <label for="start-date">When do you hope to start your MBA / Masters?</label>
            <input type="text" id="start-date" name="mba_start_date">
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
    <div class="form-row">
        <div class="form-group">
            <label for="service">
                Which Fortuna Admissions service are you interested in?
            </label>
            <input type="text" id="service" name="fortuna_service">
        </div>

        <div class="form-group"></div>
    </div>

    <!-- Submit -->
    <div class="form-row" style="margin-bottom: 0;">
        <div class="form-group">
            <button type="submit">Submit</button>
        </div>
    </div>

</form>

<script>
document.addEventListener("DOMContentLoaded", function () {

    const form       = document.querySelector(".mba-simple-consultation-form");
    const tokenField = form ? form.querySelector('input[name="g-recaptcha-response"]') : null;

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
        window.dataLayer.push({
            event: "MBASimpleFormSubmit"
        });

        function submitForm(token) {
            tokenField.value = token || "";
            form.submit();
        }

        if (typeof grecaptcha === "undefined") {
            submitForm("");
            return;
        }

        var fallback = setTimeout(function () {
            submitForm("");
        }, 4000);

        grecaptcha.ready(function () {
            grecaptcha.execute(
                "6LevnXYtAAAAAMJD8mj2aeDja_yK6R20db50KgpD",
                {
                    action: "mba_simple_consultation"
                }
            ).then(function (token) {
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