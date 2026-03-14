/**
 * Validation Utilities
 * Shared validation functions for registration and other forms
 */

const ValidationUtils = {
    /**
     * Validate email format and check with backend if it exists
     */
    async validateEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            return {
                valid: false,
                message: 'Please enter a valid email address',
                available: false
            };
        }

        // Check if email is available via backend
        try {
            const apiUrl = window.API_URL || 'http://localhost/mombasahamlets_web/backend/api';
            const response = await fetch(`${apiUrl}/users.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'check_email',
                    email: email
                })
            });

            const data = await response.json();
            if (data.available) {
                return {
                    valid: true,
                    message: 'Email is available',
                    available: true
                };
            } else {
                return {
                    valid: false,
                    message: 'Email is already registered',
                    available: false
                };
            }
        } catch (error) {
            return {
                valid: true,
                message: 'Email format is valid',
                available: null,
                error: 'Could not check availability'
            };
        }
    },

    /**
     * Validate password strength
     * Requirements:
     * - At least 8 characters
     * - At least one uppercase letter
     * - At least one lowercase letter
     * - At least one number
     * - At least one special character
     */
    validatePasswordStrength(password) {
        const requirements = {
            length: password.length >= 8,
            uppercase: /[A-Z]/.test(password),
            lowercase: /[a-z]/.test(password),
            number: /\d/.test(password),
            special: /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)
        };

        const metRequirements = Object.values(requirements).filter(Boolean).length;
        const totalRequirements = Object.keys(requirements).length;

        let strength = 'weak';
        if (metRequirements === totalRequirements) {
            strength = 'strong';
        } else if (metRequirements >= 4) {
            strength = 'good';
        } else if (metRequirements >= 3) {
            strength = 'fair';
        }

        return {
            strength,
            metRequirements,
            totalRequirements,
            requirements,
            isValid: metRequirements === totalRequirements
        };
    },

    /**
     * Generate a strong password
     */
    generateStrongPassword() {
        const lowercase = 'abcdefghijklmnopqrstuvwxyz';
        const uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        const numbers = '0123456789';
        const special = '!@#$%^&*()_+-=[]{}";:,.<>?';

        let password = '';
        password += uppercase[Math.floor(Math.random() * uppercase.length)];
        password += lowercase[Math.floor(Math.random() * lowercase.length)];
        password += numbers[Math.floor(Math.random() * numbers.length)];
        password += special[Math.floor(Math.random() * special.length)];

        const allChars = lowercase + uppercase + numbers + special;
        for (let i = password.length; i < 12; i++) {
            password += allChars[Math.floor(Math.random() * allChars.length)];
        }

        return password.split('').sort(() => Math.random() - 0.5).join('');
    },

    /**
     * Validate username
     */
    async validateUsername(username) {
        if (username.length < 3) {
            return {
                valid: false,
                message: 'Username must be at least 3 characters',
                available: false
            };
        }

        if (username.length > 20) {
            return {
                valid: false,
                message: 'Username must be at most 20 characters',
                available: false
            };
        }

        if (!/^[a-zA-Z0-9_\-\.]+$/.test(username)) {
            return {
                valid: false,
                message: 'Username can only contain letters, numbers, underscores, hyphens, and periods',
                available: false
            };
        }

        // Check if username is available via backend
        try {
            const apiUrl = window.API_URL || 'http://localhost/mombasahamlets_web/backend/api';
            const response = await fetch(`${apiUrl}/users.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'check_username',
                    username: username
                })
            });

            const data = await response.json();
            if (data.available) {
                return {
                    valid: true,
                    message: 'Username is available',
                    available: true
                };
            } else {
                return {
                    valid: false,
                    message: 'Username is already taken',
                    available: false
                };
            }
        } catch (error) {
            return {
                valid: true,
                message: 'Username format is valid',
                available: null,
                error: 'Could not check availability'
            };
        }
    },

    /**
     * Generate username suggestions based on first and last name
     */
    generateUsernameSuggestions(firstName, lastName) {
        const suggestions = [];
        const f = firstName.toLowerCase();
        const l = lastName.toLowerCase();

        suggestions.push(`${f}${l}`);
        suggestions.push(`${f}.${l}`);
        suggestions.push(`${f}_${l}`);
        suggestions.push(`${f}${l}${Math.floor(Math.random() * 100)}`);
        suggestions.push(`${f.charAt(0)}${l}`);
        suggestions.push(`${f}${l.charAt(0)}`);
        suggestions.push(`${f.charAt(0)}_${l}`);

        return [...new Set(suggestions)]; // Remove duplicates
    },

    /**
     * Validate email format
     */
    validateEmailFormat(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    },

    /**
     * Send verification email
     */
    async sendVerificationEmail(email) {
        try {
            const apiUrl = window.API_URL || 'http://localhost/mombasahamlets_web/backend/api';
            const response = await fetch(`${apiUrl}/users.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'send_verification',
                    email: email
                })
            });

            const data = await response.json();
            return {
                success: data.success || false,
                message: data.message || 'Verification email sent'
            };
        } catch (error) {
            return {
                success: false,
                message: 'Failed to send verification email'
            };
        }
    }
};

// Make ValidationUtils available globally
window.ValidationUtils = ValidationUtils;
