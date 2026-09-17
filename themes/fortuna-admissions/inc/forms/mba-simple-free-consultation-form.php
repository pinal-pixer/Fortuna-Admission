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
}

.mba-simple-consultation-form .form-group label {
    display: block;
    margin-bottom: 6px;
    font-weight: 600;
    font-size: 14px;
    line-height: 20px;
}

.mba-simple-consultation-form .form-group input,
.mba-simple-consultation-form .form-group textarea {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-sizing: border-box;
    font-size: 13px;
    font-family: inherit;
    min-height: 40px;
}

.mba-simple-consultation-form .form-group textarea {
    resize: vertical;
    min-height: 80px;
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
    box-sizing: border-box;
    min-height: 40px;

    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;

    background: #fff
        url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='18' height='18' viewBox='0 0 24 24' fill='none' stroke='%23333' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E")
        no-repeat right 8px center;
    background-size: 18px;
}

.mba-simple-consultation-form .form-group select:focus,
.mba-simple-consultation-form .form-group input:focus,
.mba-simple-consultation-form .form-group textarea:focus {
    outline: none;
    border-color: #5b9dd9;
}

.mba-simple-consultation-form .intro-text {
    margin: 0 0 20px;
    font-style: italic;
    line-height: 20px;
}

.mba-simple-consultation-form .file-input {
    padding: 8px 10px;
    min-height: 46px;
    display: flex;
    align-items: center;
}

.mba-simple-consultation-form .file-input::file-selector-button {
    margin-right: 8px;
    padding: 5px 10px;
    border: 1px solid #777;
    border-radius: 3px;
    background: #fff;
    cursor: pointer;
}

.mba-simple-consultation-form .submit-row {
    margin-top: 5px;
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
</head>

<body>

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
            <label for="last-name" class="last-name-label">Last Name</label>
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
                class="file-input"
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
    <div class="form-row">
        <div class="form-group">
            <label for="country">
                Country of Residence <span class="asterisk">*</span>
            </label>

            <select id="country" name="country" required>
                <option value="" selected disabled></option>
                <option value="United States">United States</option>
                <option value="United Kingdom">United Kingdom</option>
                <option value="Canada">Canada</option>
                <option value="India">India</option>
                <option value="Australia">Australia</option>
                <option value="Singapore">Singapore</option>
                <option value="Other">Other</option>
            </select>
        </div>
    </div>

    <!-- How did you hear -->
    <div class="form-row">
        <div class="form-group">
            <label for="hear-about">
                How did you hear about Fortuna? <span class="asterisk">*</span>
            </label>

            <input
                type="text"
                id="hear-about"
                name="hear_about_us"
                required
            >
        </div>
    </div>

    <!-- Additional information -->
    <div class="form-row">
        <div class="form-group">
            <label for="additional-info">
                Please provide any further information that will be helpful context ahead of our free consultation call:
            </label>

            <textarea
                id="additional-info"
                name="additional_information"
                rows="3"
            ></textarea>
        </div>
    </div>

    <!-- Submit -->
    <div class="submit-row">
        <button type="submit">Next</button>
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

        // Google Tag Manager / dataLayer event
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({
            event: "MBASimpleFormSubmit"
        });

        function submitForm(token) {
            tokenField.value = token || "";
            form.submit();
        }

        // Fallback if reCAPTCHA is not loaded
        if (typeof grecaptcha === "undefined") {
            submitForm("");
            return;
        }

        // Fallback after 4 seconds
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