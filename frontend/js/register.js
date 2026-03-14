document.addEventListener('DOMContentLoaded', () => {
    // ValidationUtils is available globally from validation-utils.js
    const registerForm = document.getElementById('register-form');
    const errorMessage = document.getElementById('error-message');
    const submitBtn = document.getElementById('submit-btn');

    const firstNameInput = document.getElementById('first-name');
    const lastNameInput = document.getElementById('last-name');
    const emailInput = document.getElementById('email');
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm-password');

    // Toggle password visibility
    const togglePasswordBtn = document.getElementById('toggle-password');
    const toggleConfirmPasswordBtn = document.getElementById('toggle-confirm-password');

    if (togglePasswordBtn) {
        togglePasswordBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const type = passwordInput.type === 'password' ? 'text' : 'password';
            passwordInput.type = type;
            togglePasswordBtn.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        });
    }

    if (toggleConfirmPasswordBtn) {
        toggleConfirmPasswordBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const type = confirmPasswordInput.type === 'password' ? 'text' : 'password';
            confirmPasswordInput.type = type;
            toggleConfirmPasswordBtn.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        });
    }

    // First Name Validation
    firstNameInput.addEventListener('blur', () => {
        const value = firstNameInput.value.trim();
        const feedback = document.getElementById('first-name-feedback');
        const namePattern = /^[A-Za-z\s'\-]+$/;

        if (!value) {
            showFeedback(feedback, 'First name is required', 'invalid');
            return;
        }

        if (!namePattern.test(value)) {
            showFeedback(feedback, 'First name should only contain letters, spaces, hyphens and apostrophes', 'invalid');
            return;
        }

        showFeedback(feedback, '✓ First name is valid', 'valid');
        checkFormValidity();
    });

    // Last Name Validation
    lastNameInput.addEventListener('blur', () => {
        const value = lastNameInput.value.trim();
        const feedback = document.getElementById('last-name-feedback');
        const namePattern = /^[A-Za-z\s'\-]+$/;

        if (!value) {
            showFeedback(feedback, 'Last name is required', 'invalid');
            return;
        }

        if (!namePattern.test(value)) {
            showFeedback(feedback, 'Last name should only contain letters, spaces, hyphens and apostrophes', 'invalid');
            return;
        }

        showFeedback(feedback, '✓ Last name is valid', 'valid');
        checkFormValidity();
    });

    // Email Validation with availability check
    emailInput.addEventListener('blur', async () => {
        const value = emailInput.value.trim();
        const feedback = document.getElementById('email-feedback');
        const verificationDiv = document.getElementById('email-verification');

        if (!value) {
            showFeedback(feedback, 'Email is required', 'invalid');
            verificationDiv.style.display = 'none';
            return;
        }

        if (!window.ValidationUtils.validateEmailFormat(value)) {
            showFeedback(feedback, 'Please enter a valid email address', 'invalid');
            verificationDiv.style.display = 'none';
            return;
        }

        showFeedback(feedback, 'Checking email availability...', 'warning');
        const result = await window.ValidationUtils.validateEmail(value);

        if (result.available) {
            showFeedback(feedback, '✓ Email is available', 'valid');
            verificationDiv.style.display = 'flex';
            document.getElementById('verify-email-btn').disabled = false;
        } else if (result.available === false) {
            showFeedback(feedback, '✗ ' + result.message, 'invalid');
            verificationDiv.style.display = 'none';
        } else {
            showFeedback(feedback, '✓ Email format is valid', 'valid');
            verificationDiv.style.display = 'flex';
        }

        checkFormValidity();
    });

    // Email Verification Button
    const verifyEmailBtn = document.getElementById('verify-email-btn');
    if (verifyEmailBtn) {
        verifyEmailBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            const email = emailInput.value.trim();
            const statusSpan = document.getElementById('email-status');

            statusSpan.textContent = 'Sending verification email...';
            const result = await ValidationUtils.sendVerificationEmail(email);

            if (result.success) {
                statusSpan.textContent = '✓ Verification email sent to ' + email;
                statusSpan.style.color = '#28a745';
            } else {
                statusSpan.textContent = '✗ ' + result.message;
                statusSpan.style.color = '#dc3545';
            }
        });
    }

    // Username Validation with suggestions
    usernameInput.addEventListener('input', () => {
        const value = usernameInput.value.trim();
        const feedback = document.getElementById('username-feedback');
        const suggestionsDiv = document.getElementById('username-suggestions');

        // Generate suggestions based on first and last name
        const firstName = firstNameInput.value.trim();
        const lastName = lastNameInput.value.trim();

        if (firstName && lastName) {
            const suggestions = window.ValidationUtils.generateUsernameSuggestions(firstName, lastName);
            showSuggestions(suggestions);
        }

        if (!value) {
            showFeedback(feedback, 'Username is required', 'invalid');
            return;
        }

        if (value.length < 3) {
            showFeedback(feedback, 'Username must be at least 3 characters', 'warning');
            return;
        }

        if (value.length > 20) {
            showFeedback(feedback, 'Username must be at most 20 characters', 'invalid');
            return;
        }

        if (!/^[a-zA-Z0-9_\-\.]+$/.test(value)) {
            showFeedback(feedback, 'Username can only contain letters, numbers, underscores, hyphens, and periods', 'invalid');
            return;
        }

        showFeedback(feedback, 'Checking username availability...', 'warning');
        checkUsernameAvailability(value);
    });

    // Check username availability asynchronously
    let usernameCheckTimeout;
    async function checkUsernameAvailability(username) {
        clearTimeout(usernameCheckTimeout);
        usernameCheckTimeout = setTimeout(async () => {
            const feedback = document.getElementById('username-feedback');
            const result = await window.ValidationUtils.validateUsername(username);

            if (result.available) {
                showFeedback(feedback, '✓ Username is available', 'valid');
            } else if (result.available === false) {
                showFeedback(feedback, '✗ ' + result.message, 'invalid');
            } else {
                showFeedback(feedback, '✓ Username format is valid', 'valid');
            }

            checkFormValidity();
        }, 500);
    }

    // Password Validation with strength indicator
    function validatePasswordInput() {
        const value = passwordInput.value;
        const feedback = document.getElementById('password-feedback');
        const strengthDiv = document.getElementById('password-strength');
        const toggleBtn = document.getElementById('toggle-password');

        if (!value) {
            showFeedback(feedback, 'Password is required', 'invalid');
            strengthDiv.style.display = 'none';
            toggleBtn.style.display = 'none';
            return;
        }

        toggleBtn.style.display = 'block';
        strengthDiv.style.display = 'block';

        const strength = window.ValidationUtils.validatePasswordStrength(value);
        updatePasswordStrengthIndicator(strength);

        if (strength.isValid) {
            showFeedback(feedback, '✓ Password meets all requirements', 'valid');
        } else {
            showFeedback(feedback, `${strength.metRequirements}/${strength.totalRequirements} requirements met`, 'warning');
        }

        // Check confirm password match
        if (confirmPasswordInput.value && confirmPasswordInput.value !== value) {
            const confirmFeedback = document.getElementById('confirm-password-feedback');
            showFeedback(confirmFeedback, '✗ Passwords do not match', 'invalid');
        } else if (confirmPasswordInput.value) {
            const confirmFeedback = document.getElementById('confirm-password-feedback');
            showFeedback(confirmFeedback, '✓ Passwords match', 'valid');
        }

        checkFormValidity();
    }

    passwordInput.addEventListener('input', validatePasswordInput);
    passwordInput.addEventListener('paste', () => {
        setTimeout(validatePasswordInput, 10);
    });

    // Confirm Password Validation
    confirmPasswordInput.addEventListener('input', () => {
        const password = passwordInput.value;
        const confirmPassword = confirmPasswordInput.value;
        const feedback = document.getElementById('confirm-password-feedback');

        if (!confirmPassword) {
            showFeedback(feedback, 'Please confirm your password', 'invalid');
            checkFormValidity();
            return;
        }

        if (password !== confirmPassword) {
            showFeedback(feedback, '✗ Passwords do not match', 'invalid');
        } else {
            showFeedback(feedback, '✓ Passwords match', 'valid');
        }

        checkFormValidity();
    });

    // Suggest Strong Password Button
    const suggestPasswordBtn = document.getElementById('suggest-password-btn');
    if (suggestPasswordBtn) {
        suggestPasswordBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const suggestedPassword = window.ValidationUtils.generateStrongPassword();
            passwordInput.value = suggestedPassword;
            confirmPasswordInput.value = suggestedPassword;
            passwordInput.type = 'text'; // Show the password
            confirmPasswordInput.type = 'text';

            if (togglePasswordBtn) togglePasswordBtn.innerHTML = '<i class="fas fa-eye-slash"></i>';
            if (toggleConfirmPasswordBtn) toggleConfirmPasswordBtn.innerHTML = '<i class="fas fa-eye-slash"></i>';

            // Trigger validation
            validatePasswordInput();
            confirmPasswordInput.dispatchEvent(new Event('input'));

            showFeedback(document.getElementById('password-feedback'), '✓ Strong password generated', 'valid');
        });
    }

    // Form Submission
    registerForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        errorMessage.textContent = '';

        // Trigger validation on all fields to show errors
        const firstName = firstNameInput.value.trim();
        const lastName = lastNameInput.value.trim();
        const email = emailInput.value.trim();
        const username = usernameInput.value.trim();
        const password = passwordInput.value;
        const confirmPassword = confirmPasswordInput.value;

        // Validate each field and show specific errors
        let hasErrors = false;

        const namePattern = /^[A-Za-z\s'\-]+$/;
        if (!firstName) {
            showFeedback(document.getElementById('first-name-feedback'), 'First name is required', 'invalid');
            hasErrors = true;
        } else if (!namePattern.test(firstName)) {
            showFeedback(document.getElementById('first-name-feedback'), 'First name should only contain letters, spaces, hyphens and apostrophes', 'invalid');
            hasErrors = true;
        }

        if (!lastName) {
            showFeedback(document.getElementById('last-name-feedback'), 'Last name is required', 'invalid');
            hasErrors = true;
        } else if (!namePattern.test(lastName)) {
            showFeedback(document.getElementById('last-name-feedback'), 'Last name should only contain letters, spaces, hyphens and apostrophes', 'invalid');
            hasErrors = true;
        }

        if (!email) {
            showFeedback(document.getElementById('email-feedback'), 'Email is required', 'invalid');
            hasErrors = true;
        } else if (!window.ValidationUtils.validateEmailFormat(email)) {
            showFeedback(document.getElementById('email-feedback'), 'Please enter a valid email address', 'invalid');
            hasErrors = true;
        }

        if (!username) {
            showFeedback(document.getElementById('username-feedback'), 'Username is required', 'invalid');
            hasErrors = true;
        } else if (username.length < 3 || username.length > 20) {
            showFeedback(document.getElementById('username-feedback'), 'Username must be between 3 and 20 characters', 'invalid');
            hasErrors = true;
        } else if (!/^[a-zA-Z0-9_\-\.]+$/.test(username)) {
            showFeedback(document.getElementById('username-feedback'), 'Username can only contain letters, numbers, underscores, hyphens, and periods', 'invalid');
            hasErrors = true;
        }

        if (!password) {
            showFeedback(document.getElementById('password-feedback'), 'Password is required', 'invalid');
            hasErrors = true;
        } else {
            const strength = window.ValidationUtils.validatePasswordStrength(password);
            if (!strength.isValid) {
                showFeedback(document.getElementById('password-feedback'), `Password needs: ${getMissingRequirements(strength)}`, 'invalid');
                document.getElementById('password-strength').style.display = 'block';
                updatePasswordStrengthIndicator(strength);
                hasErrors = true;
            }
        }

        if (!confirmPassword) {
            showFeedback(document.getElementById('confirm-password-feedback'), 'Please confirm your password', 'invalid');
            hasErrors = true;
        } else if (password !== confirmPassword) {
            showFeedback(document.getElementById('confirm-password-feedback'), '✗ Passwords do not match', 'invalid');
            hasErrors = true;
        }

        if (hasErrors) {
            errorMessage.textContent = 'Please fix the errors above before registering.';
            errorMessage.style.color = '#dc3545';
            // Scroll to first error
            const firstError = registerForm.querySelector('.validation-feedback.invalid.show');
            if (firstError) firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        try {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Registering...';

            const apiUrl = window.API_URL || 'http://localhost/mombasahamlets_web/backend/api';
            const response = await fetch(`${apiUrl}/users.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'register',
                    first_name: firstName,
                    last_name: lastName,
                    email,
                    username,
                    password
                }),
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'Registration failed');
            }

            // Success - redirect to login
            window.location.href = 'login.php?registered=true';

        } catch (error) {
            errorMessage.textContent = error.message;
            errorMessage.style.color = '#dc3545';
            submitBtn.disabled = false;
            submitBtn.textContent = 'Register';
        }
    });

    // Helper: get missing password requirements as a readable string
    function getMissingRequirements(strength) {
        const missing = [];
        if (!strength.requirements.length) missing.push('8+ characters');
        if (!strength.requirements.uppercase) missing.push('uppercase letter');
        if (!strength.requirements.lowercase) missing.push('lowercase letter');
        if (!strength.requirements.number) missing.push('number');
        if (!strength.requirements.special) missing.push('special character (!@#$%)');
        return missing.join(', ');
    }

    // Helper Functions
    function showFeedback(element, message, type) {
        element.textContent = message;
        element.className = `validation-feedback show ${type}`;
    }

    function updatePasswordStrengthIndicator(strength) {
        const fillEl = document.getElementById('strength-fill');
        const textEl = document.getElementById('strength-text');

        fillEl.className = `strength-fill ${strength.strength}`;
        textEl.className = `strength-text ${strength.strength}`;
        textEl.textContent = `Password Strength: ${strength.strength.charAt(0).toUpperCase() + strength.strength.slice(1)}`;

        // Update requirements
        updateRequirementUI('req-length', strength.requirements.length);
        updateRequirementUI('req-upper', strength.requirements.uppercase);
        updateRequirementUI('req-lower', strength.requirements.lowercase);
        updateRequirementUI('req-number', strength.requirements.number);
        updateRequirementUI('req-special', strength.requirements.special);
    }

    function updateRequirementUI(id, met) {
        const el = document.getElementById(id);
        if (met) {
            el.classList.add('met');
        } else {
            el.classList.remove('met');
        }
    }

    function showSuggestions(suggestions) {
        const suggestionsList = document.getElementById('suggestions-list');
        const suggestionsDiv = document.getElementById('username-suggestions');

        suggestionsList.innerHTML = '';
        suggestions.forEach(suggestion => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'suggestion-btn';
            btn.textContent = suggestion;
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                usernameInput.value = suggestion;
                usernameInput.dispatchEvent(new Event('input'));
                suggestionsDiv.style.display = 'none';
            });
            suggestionsList.appendChild(btn);
        });

        if (suggestions.length > 0) {
            suggestionsDiv.style.display = 'block';
        }
    }

    function validateAllFields() {
        const firstName = firstNameInput.value.trim();
        const lastName = lastNameInput.value.trim();
        const email = emailInput.value.trim();
        const username = usernameInput.value.trim();
        const password = passwordInput.value;
        const confirmPassword = confirmPasswordInput.value;

        if (!firstName || !lastName || !email || !username || !password || !confirmPassword) {
            return false;
        }

        const namePattern = /^[A-Za-z\s'\-]+$/;
        if (!namePattern.test(firstName) || !namePattern.test(lastName)) {
            return false;
        }

        if (!window.ValidationUtils.validateEmailFormat(email)) {
            return false;
        }

        if (!/^[a-zA-Z0-9_\-\.]+$/.test(username) || username.length < 3 || username.length > 20) {
            return false;
        }

        const strength = window.ValidationUtils.validatePasswordStrength(password);
        if (!strength.isValid) {
            return false;
        }

        if (password !== confirmPassword) {
            return false;
        }

        return true;
    }

    function checkFormValidity() {
        const isValid = validateAllFields();
        submitBtn.disabled = !isValid;
    }
});

