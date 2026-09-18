<style>
.mba-simple-consultation-form {
    max-width: 100%;
}

.mba-simple-consultation-form .form-row {
    display: flex;
    gap: 25px;
    margin-bottom: 20px;
}

.mba-simple-consultation-form .form-group {
    flex: 1;
}

.mba-simple-consultation-form .form-group label {
    display: block;
    margin-bottom: 6px;
    font-weight: 600;
    font-size: 14px;
    line-height: 20px;
}

.mba-simple-consultation-form .form-group input[type="text"],
.mba-simple-consultation-form .form-group input[type="email"],
.mba-simple-consultation-form .form-group input[type="tel"],
.mba-simple-consultation-form .form-group input[type="url"],
.mba-simple-consultation-form .form-group input[type="file"],
.mba-simple-consultation-form .form-group textarea {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-sizing: border-box;
}

.mba-simple-consultation-form .form-group textarea {
    resize: vertical;
}

.mba-simple-consultation-form button {
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

.mba-simple-consultation-form button:hover {
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

.mba-simple-consultation-form select {
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

.mba-simple-consultation-form .form-group select:focus {
    outline: none;
    border-color: #5b9dd9;
}

.mba-simple-consultation-form .intro-text {
    margin: 0 0 20px;
    font-style: italic;
    line-height: 20px;
}

@media (max-width: 768px) {
    .mba-simple-consultation-form .form-row {
        flex-direction: column;
        gap: 15px;
    }

    .last-name-label {
        display: none !important;
    }
}
</style>

<form class="mba-simple-consultation-form" action="#" method="post" enctype="multipart/form-data">

    <input type="hidden" name="g-recaptcha-response" value="">

    <!-- Name -->
    <div class="form-row">
        <div class="form-group">
            <label for="first-name">
                Name <span class="asterisk">*</span>
            </label>
            <input
                type="text"
                id="first-name"
                name="first_name"
                placeholder="First"
                required
            >
        </div>

        <div class="form-group">
            <label for="last-name" class="last-name-label">Name</label>
            <input
                type="text"
                id="last-name"
                name="last_name"
                placeholder="Last"
            >
        </div>
    </div>

    <!-- Email / Phone -->
    <div class="form-row">
        <div class="form-group">
            <label for="email">
                Email <span class="asterisk">*</span>
            </label>
            <input
                type="email"
                id="email"
                name="email"
                required
            >
        </div>

        <div class="form-group">
            <label for="phone">Phone</label>
            <input
                type="tel"
                id="phone"
                name="phone"
            >
        </div>
    </div>

    <!-- Intro text -->
    <p class="intro-text">
        To help make your free consultation as beneficial as possible, please provide either your Resume or LinkedIn profile.
    </p>

    <!-- Resume / LinkedIn -->
    <div class="form-row">
        <div class="form-group">
            <label for="resume">Resume</label>
            <input
                type="file"
                id="resume"
                name="resume"
                accept=".pdf,.doc,.docx"
            >
        </div>

        <div class="form-group">
            <label for="linkedin">LinkedIn Profile</label>
            <input
                type="url"
                id="linkedin"
                name="linkedin"
            >
        </div>
    </div>

    <!-- Country -->
    <div class="form-group bottom-margin">
        <label for="country">
            Country of Residence <span class="asterisk">*</span>
        </label>
        <select id="country" name="country" required>

            <option value="">Select Country</option>

            <option value="Afghanistan">Afghanistan</option>
            <option value="Albania">Albania</option>
            <option value="Algeria">Algeria</option>
            <option value="Andorra">Andorra</option>
            <option value="Angola">Angola</option>
            <option value="Argentina">Argentina</option>
            <option value="Armenia">Armenia</option>
            <option value="Australia">Australia</option>
            <option value="Austria">Austria</option>
            <option value="Azerbaijan">Azerbaijan</option>

            <option value="Bahrain">Bahrain</option>
            <option value="Bangladesh">Bangladesh</option>
            <option value="Belgium">Belgium</option>
            <option value="Brazil">Brazil</option>
            <option value="Bulgaria">Bulgaria</option>

            <option value="Canada">Canada</option>
            <option value="Chile">Chile</option>
            <option value="China">China</option>
            <option value="Colombia">Colombia</option>
            <option value="Costa Rica">Costa Rica</option>
            <option value="Croatia">Croatia</option>
            <option value="Cyprus">Cyprus</option>
            <option value="Czech Republic">Czech Republic</option>

            <option value="Denmark">Denmark</option>
            <option value="Dominican Republic">Dominican Republic</option>

            <option value="Ecuador">Ecuador</option>
            <option value="Egypt">Egypt</option>
            <option value="Estonia">Estonia</option>
            <option value="Ethiopia">Ethiopia</option>

            <option value="Finland">Finland</option>
            <option value="France">France</option>

            <option value="Georgia">Georgia</option>
            <option value="Germany">Germany</option>
            <option value="Ghana">Ghana</option>
            <option value="Greece">Greece</option>
            <option value="Guatemala">Guatemala</option>

            <option value="Hong Kong">Hong Kong</option>
            <option value="Hungary">Hungary</option>

            <option value="Iceland">Iceland</option>
            <option value="India">India</option>
            <option value="Indonesia">Indonesia</option>
            <option value="Ireland">Ireland</option>
            <option value="Israel">Israel</option>
            <option value="Italy">Italy</option>

            <option value="Japan">Japan</option>
            <option value="Jordan">Jordan</option>

            <option value="Kenya">Kenya</option>
            <option value="Kuwait">Kuwait</option>

            <option value="Latvia">Latvia</option>
            <option value="Lebanon">Lebanon</option>
            <option value="Lithuania">Lithuania</option>
            <option value="Luxembourg">Luxembourg</option>

            <option value="Malaysia">Malaysia</option>
            <option value="Malta">Malta</option>
            <option value="Mexico">Mexico</option>
            <option value="Monaco">Monaco</option>
            <option value="Morocco">Morocco</option>

            <option value="Netherlands">Netherlands</option>
            <option value="New Zealand">New Zealand</option>
            <option value="Nigeria">Nigeria</option>
            <option value="Norway">Norway</option>

            <option value="Pakistan">Pakistan</option>
            <option value="Panama">Panama</option>
            <option value="Peru">Peru</option>
            <option value="Philippines">Philippines</option>
            <option value="Poland">Poland</option>
            <option value="Portugal">Portugal</option>

            <option value="Qatar">Qatar</option>

            <option value="Romania">Romania</option>
            <option value="Russia">Russia</option>

            <option value="Saudi Arabia">Saudi Arabia</option>
            <option value="Singapore">Singapore</option>
            <option value="Slovakia">Slovakia</option>
            <option value="Slovenia">Slovenia</option>
            <option value="South Africa">South Africa</option>
            <option value="South Korea">South Korea</option>
            <option value="Spain">Spain</option>
            <option value="Sri Lanka">Sri Lanka</option>
            <option value="Sweden">Sweden</option>
            <option value="Switzerland">Switzerland</option>

            <option value="Taiwan">Taiwan</option>
            <option value="Thailand">Thailand</option>
            <option value="Turkey">Turkey</option>

            <option value="Ukraine">Ukraine</option>
            <option value="United Arab Emirates">United Arab Emirates</option>
            <option value="United Kingdom">United Kingdom</option>
            <option value="United States">United States</option>
            <option value="Uruguay">Uruguay</option>

            <option value="Venezuela">Venezuela</option>
            <option value="Vietnam">Vietnam</option>

            <option value="Other">Other</option>
        </select>
    </div>

    <!-- How did you hear -->
    <div class="form-row">
    <div class="form-group">

        <label for="hear-about">
            How did you hear about Fortuna? <span class="asterisk">*</span>
        </label>

        <select id="hear-about" name="hear_about_us" required>

            <option value="">Select an option</option>

            <option value="Google Ad">Google Ad</option>

            <option value="ChatGPT or other AI">ChatGPT or other AI</option>

            <option value="Poets&Quants">Poets&Quants</option>

            <option value="GMAT Club">GMAT Club</option>

            <option value="YouTube">YouTube</option>

            <option value="LinkedIn">LinkedIn</option>

            <option value="Instagram">Instagram</option>

            <option value="Facebook">Facebook</option>

            <option value="Reddit">Reddit</option>

            <option value="Centre Court MBA Festival">
                Centre Court MBA Festival
            </option>

            <option value="Beat The GMAT">
                Beat The GMAT
            </option>

        </select>

    </div>
</div>

    <!-- Additional information -->
    <div class="form-group bottom-margin">
        <label for="additional-info">
            Please provide any further information that will be helpful context ahead of our free consultation call
        </label>

        <textarea
            id="additional-info"
            name="additional_information"
            rows="5"
        ></textarea>
    </div>

    <!-- Submit -->
    <button type="submit">Next</button>

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