document.addEventListener("DOMContentLoaded", function () {
    const registerForm = document.getElementById("registerForm") || document.querySelector("form[action='register.html']");
    if (!registerForm) return;

    const nameInput = document.getElementById("fullname") || document.getElementById("name");
    const emailInput = document.getElementById("email");
    const mobileInput = document.getElementById("phone") || document.getElementById("mobile");
    const courseInput = document.getElementById("department") || document.getElementById("course");
    const yearInput = document.getElementById("semester") || document.getElementById("year");
    const passwordInput = document.getElementById("password");
    const confirmInput = document.getElementById("confirm_password") || document.getElementById("confirm");
    const termsInput = document.getElementById("terms_agree") || document.getElementById("terms");

    const passwordStrengthSpan = document.getElementById("passwordStrength");

    const namePattern = /^[A-Za-z ]{3,}$/;
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const mobilePattern = /^[6-9]\d{9}$/;
    const passwordPattern = /^(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/;

    function getErrorElement(fieldId, fallbackContainer) {
        let errEl = document.getElementById(fieldId + "Error");
        if (!errEl && fallbackContainer) {
            errEl = document.createElement("p");
            errEl.id = fieldId + "Error";
            errEl.className = "error-message";
            fallbackContainer.appendChild(errEl);
        }
        return errEl;
    }

    function clearErrors() {
        document.querySelectorAll(".error-message, p[id$='Error']").forEach(function (el) {
            el.textContent = "";
        });
        document.querySelectorAll(".input-error").forEach(function (input) {
            input.classList.remove("input-error");
        });
    }

    if (passwordInput) {
        passwordInput.addEventListener("input", function () {
            const val = passwordInput.value;
            if (!passwordStrengthSpan) return;

            if (val.length === 0) {
                passwordStrengthSpan.textContent = "";
                passwordStrengthSpan.className = "strength-meter";
            } else if (val.length < 8) {
                passwordStrengthSpan.textContent = "Weak Password (Min 8 chars required)";
                passwordStrengthSpan.className = "strength-meter weak";
            } else if (passwordPattern.test(val)) {
                passwordStrengthSpan.textContent = "Strong Password ✓";
                passwordStrengthSpan.className = "strength-meter strong";
            } else {
                passwordStrengthSpan.textContent = "Medium Password (Add uppercase, digit & special character)";
                passwordStrengthSpan.className = "strength-meter medium";
            }
        });
    }

    registerForm.addEventListener("submit", function (event) {
        clearErrors();
        let isValid = true;

        const nameVal = nameInput ? nameInput.value.trim() : "";
        if (!namePattern.test(nameVal)) {
            const err = getErrorElement("name", nameInput ? nameInput.parentElement : registerForm);
            if (err) err.textContent = "Please enter a valid full name (at least 3 alphabetic characters).";
            if (nameInput) nameInput.classList.add("input-error");
            isValid = false;
        }

        const emailVal = emailInput ? emailInput.value.trim() : "";
        if (!emailPattern.test(emailVal)) {
            const err = getErrorElement("email", emailInput ? emailInput.parentElement : registerForm);
            if (err) err.textContent = "Please enter a valid email address (e.g. student@charusat.edu.in).";
            if (emailInput) emailInput.classList.add("input-error");
            isValid = false;
        }

        const mobileVal = mobileInput ? mobileInput.value.trim() : "";
        if (!mobilePattern.test(mobileVal)) {
            const err = getErrorElement("mobile", mobileInput ? mobileInput.parentElement : registerForm);
            if (err) err.textContent = "Please enter a valid 10-digit mobile number starting with 6-9.";
            if (mobileInput) mobileInput.classList.add("input-error");
            isValid = false;
        }

        const genderChecked = document.querySelector('input[name="gender"]:checked');
        if (!genderChecked) {
            const err = getErrorElement("gender", registerForm);
            if (err) err.textContent = "Please select your gender.";
            isValid = false;
        }

        const courseVal = courseInput ? courseInput.value.trim() : "";
        if (courseVal === "") {
            const err = getErrorElement("course", courseInput ? courseInput.parentElement : registerForm);
            if (err) err.textContent = "Please select your department / course.";
            if (courseInput) courseInput.classList.add("input-error");
            isValid = false;
        }

        const yearVal = yearInput ? yearInput.value.trim() : "";
        if (yearVal === "") {
            const err = getErrorElement("year", yearInput ? yearInput.parentElement : registerForm);
            if (err) err.textContent = "Please select your year / semester.";
            if (yearInput) yearInput.classList.add("input-error");
            isValid = false;
        }

        const passVal = passwordInput ? passwordInput.value : "";
        if (!passwordPattern.test(passVal)) {
            const err = getErrorElement("password", passwordInput ? passwordInput.parentElement : registerForm);
            if (err) err.textContent = "Password must be at least 8 characters long, contain an uppercase letter, a digit, and a special character.";
            if (passwordInput) passwordInput.classList.add("input-error");
            isValid = false;
        }

        const confirmVal = confirmInput ? confirmInput.value : "";
        if (passVal !== confirmVal) {
            const err = getErrorElement("confirm", confirmInput ? confirmInput.parentElement : registerForm);
            if (err) err.textContent = "Passwords do not match. Please re-enter.";
            if (confirmInput) confirmInput.classList.add("input-error");
            isValid = false;
        }

        if (termsInput && !termsInput.checked) {
            const err = getErrorElement("terms", termsInput.parentElement);
            if (err) err.textContent = "You must accept the terms and conditions to proceed.";
            isValid = false;
        }

        if (!isValid) {
            event.preventDefault();
            const firstError = document.querySelector(".input-error");
            if (firstError) firstError.focus();
        } else {
            event.preventDefault();
            alert("Registration successful! Your StudentHub account registration has been validated and recorded.");
            registerForm.reset();
            if (passwordStrengthSpan) passwordStrengthSpan.textContent = "";
        }
    });
});
