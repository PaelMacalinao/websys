function addValidationIcons() {
    const inputs = document.querySelectorAll('.input-group input');
    inputs.forEach(input => {
        if (!input.nextElementSibling || !input.nextElementSibling.classList.contains('validation-icon')) {
            const icon = document.createElement('i');
            icon.classList.add('fas', 'validation-icon');
            input.parentNode.appendChild(icon);
        }
    });
}

// Initialize validation when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    addValidationIcons();
    
    const inputs = document.querySelectorAll('.input-group input');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateField(this.id);
        });
        
        // Also validate on input for immediate feedback
        input.addEventListener('input', function() {
            validateField(this.id);
            
            if (this.id === 'password') {
                validatePassword();
                // Also validate confirm password when password changes
                if (document.getElementById('confirm_password').value.length > 0) {
                    validateConfirmPassword();
                }
            } else if (this.id === 'confirm_password') {
                validateConfirmPassword();
            }
        });
    });
});

function validateField(fieldId) {
    switch(fieldId) {
        case 'first_name':
            return validateFirstName();
        case 'middle_name':
            return validateMiddleName();
        case 'last_name':
            return validateLastName();
        case 'email':
            return validateEmail();
        case 'mobile':
            return validateMobile();
        case 'password':
            return validatePassword();
        case 'confirm_password':
            return validateConfirmPassword();
        default:
            return true;
    }
}

// Function to validate first name
function validateFirstName() {
    const firstName = document.getElementById('first_name').value.trim();
    const errorElement = document.getElementById('first_name_error');
    const icon = document.querySelector('#first_name').parentNode.querySelector('.validation-icon');
    
    if (firstName === '') {
        errorElement.textContent = 'First name is required.';
        icon.className = 'fas fa-times-circle validation-icon invalid';
        return false;
    } else if (!/^[a-zA-Z\s]+$/.test(firstName)) {
        errorElement.textContent = 'First name can only contain letters and spaces.';
        icon.className = 'fas fa-times-circle validation-icon invalid';
        return false;
    } else {
        errorElement.textContent = '';
        icon.className = 'fas fa-check-circle validation-icon valid';
        return true;
    }
}

// Function to validate middle name
function validateMiddleName() {
    const middleName = document.getElementById('middle_name').value.trim();
    const errorElement = document.getElementById('middle_name_error');
    const icon = document.querySelector('#middle_name').parentNode.querySelector('.validation-icon');
    
    if (middleName && !/^[a-zA-Z\s]+$/.test(middleName)) {
        errorElement.textContent = 'Middle name can only contain letters and spaces.';
        icon.className = 'fas fa-times-circle validation-icon invalid';
        return false;
    } else {
        errorElement.textContent = '';
        if (middleName) {
            icon.className = 'fas fa-check-circle validation-icon valid';
        } else {
            icon.className = 'fas validation-icon';
        }
        return true;
    }
}

// Function to validate last name
function validateLastName() {
    const lastName = document.getElementById('last_name').value.trim();
    const errorElement = document.getElementById('last_name_error');
    const icon = document.querySelector('#last_name').parentNode.querySelector('.validation-icon');
    
    if (lastName === '') {
        errorElement.textContent = 'Last name is required.';
        icon.className = 'fas fa-times-circle validation-icon invalid';
        return false;
    } else if (!/^[a-zA-Z\s]+$/.test(lastName)) {
        errorElement.textContent = 'Last name can only contain letters and spaces.';
        icon.className = 'fas fa-times-circle validation-icon invalid';
        return false;
    } else {
        errorElement.textContent = '';
        icon.className = 'fas fa-check-circle validation-icon valid';
        return true;
    }
}

// Function to validate email
function validateEmail() {
    const email = document.getElementById('email').value.trim();
    const errorElement = document.getElementById('email_error');
    const icon = document.querySelector('#email').parentNode.querySelector('.validation-icon');
    
    if (email === '') {
        errorElement.textContent = 'Email is required.';
        icon.className = 'fas fa-times-circle validation-icon invalid';
        return false;
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        errorElement.textContent = 'Please enter a valid email address.';
        icon.className = 'fas fa-times-circle validation-icon invalid';
        return false;
    } else {
        errorElement.textContent = '';
        icon.className = 'fas fa-check-circle validation-icon valid';
        return true;
    }
}

// Function to validate mobile
function validateMobile() {
    const mobile = document.getElementById('mobile').value.trim();
    const errorElement = document.getElementById('mobile_error');
    const icon = document.querySelector('#mobile').parentNode.querySelector('.validation-icon');
    
    if (mobile === '') {
        errorElement.textContent = 'Mobile number is required.';
        icon.className = 'fas fa-times-circle validation-icon invalid';
        return false;
    } else if (!/^0\d{0,10}$/.test(mobile)) {
        errorElement.textContent = 'Must start with 0 and contain only numbers.';
        icon.className = 'fas fa-times-circle validation-icon invalid';
        return false;
    } else if (mobile.length !== 11) {
        errorElement.textContent = 'Mobile number must be 11 digits long.';
        icon.className = 'fas fa-times-circle validation-icon invalid';
        return false;
    } else {
        errorElement.textContent = '';
        icon.className = 'fas fa-check-circle validation-icon valid';
        return true;
    }
}

// Function to validate password
function validatePassword() {
    const password = document.getElementById('password').value;
    const errorElement = document.getElementById('password_error');
    const icon = document.querySelector('#password').parentNode.querySelector('.validation-icon');
    const strengthMeter = document.getElementById('password_strength') || createPasswordStrengthMeter();
    
    errorElement.textContent = '';
    
    if (password.length === 0) {
        errorElement.textContent = '';
        icon.className = 'fas validation-icon';
        strengthMeter.querySelector('span').className = 'strength-0';
        return false;
    }
    
    let strength = 0;
    if (password.length >= 8) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/[a-z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^a-zA-Z0-9]/.test(password)) strength++;
    
    strengthMeter.querySelector('span').className = `strength-${strength}`;
    
    const hasMinLength = password.length >= 8;
    const hasUpperCase = /[A-Z]/.test(password);
    const hasLowerCase = /[a-z]/.test(password);
    const hasNumber = /[0-9]/.test(password);
    const hasSpecialChar = /[^a-zA-Z0-9]/.test(password);
    
    if (!hasMinLength || !hasUpperCase || !hasLowerCase || !hasNumber || !hasSpecialChar) {
        const missingRequirements = [];
        if (!hasMinLength) missingRequirements.push('8 characters');
        if (!hasUpperCase) missingRequirements.push('uppercase');
        if (!hasLowerCase) missingRequirements.push('lowercase');
        if (!hasNumber) missingRequirements.push('number');
        if (!hasSpecialChar) missingRequirements.push('special character');
        
        errorElement.textContent = `Needs: ${missingRequirements.join(', ')}`;
        icon.className = 'fas fa-times-circle validation-icon invalid';
        return false;
    }
    
    errorElement.textContent = 'Strong password';
    errorElement.className = 'success-message';
    icon.className = 'fas fa-check-circle validation-icon valid';
    return true;
}


function createPasswordStrengthMeter() {
    const passwordGroup = document.querySelector('#password').parentNode.parentNode;
    const strengthMeter = document.createElement('div');
    strengthMeter.id = 'password_strength';
    strengthMeter.className = 'password-strength';
    strengthMeter.innerHTML = '<span class="strength-0"></span>';
    passwordGroup.appendChild(strengthMeter);
    return strengthMeter;
}

// Function to validate confirm password
function validateConfirmPassword() {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const errorElement = document.getElementById('confirm_password_error');
    const icon = document.querySelector('#confirm_password').parentNode.querySelector('.validation-icon');
    
    if (confirmPassword.length === 0) {
        errorElement.textContent = '';
        errorElement.className = 'error-message';
        icon.className = 'fas validation-icon';
        return false;
    } else if (password !== confirmPassword) {
        errorElement.textContent = 'Passwords do not match.';
        errorElement.className = 'error-message';
        icon.className = 'fas fa-times-circle validation-icon invalid';
        return false;
    } else {
        errorElement.textContent = 'Passwords match';
        errorElement.className = 'success-message';
        icon.className = 'fas fa-check-circle validation-icon valid';
        return true;
    }
}

// Form submission validation
document.getElementById('registerForm').addEventListener('submit', function(event) {
    let isValid = true;
    isValid = validateFirstName() && isValid;
    isValid = validateMiddleName() && isValid;
    isValid = validateLastName() && isValid;
    isValid = validateEmail() && isValid;
    isValid = validateMobile() && isValid;
    isValid = validatePassword() && isValid;
    isValid = validateConfirmPassword() && isValid;
    
    if (!isValid) {
        event.preventDefault();
        
        const firstError = document.querySelector('.error-message:not(:empty)');
        if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
});