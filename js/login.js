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
    
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const loginForm = document.getElementById('loginForm');
    
    // Add event listeners
    emailInput.addEventListener('blur', validateEmail);
    passwordInput.addEventListener('blur', validatePassword);
    
    emailInput.addEventListener('input', validateEmail);
    passwordInput.addEventListener('input', validatePassword);
    
    // Form submission handler
    loginForm.addEventListener('submit', function(event) {
        const isEmailValid = validateEmail();
        const isPasswordValid = validatePassword();
        
        if (!isEmailValid || !isPasswordValid) {
            event.preventDefault();
            
            // Scroll to first error
            const firstError = document.querySelector('.error-message:not(:empty)');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    });
});

// Email validation
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

// Password validation
function validatePassword() {
    const password = document.getElementById('password').value;
    const errorElement = document.getElementById('password_error');
    const icon = document.querySelector('#password').parentNode.querySelector('.validation-icon');
    
    if (password === '') {
        errorElement.textContent = 'Password is required.';
        icon.className = 'fas fa-times-circle validation-icon invalid';
        return false;
    } else if (password.length < 8) {
        errorElement.textContent = 'Password must be at least 8 characters.';
        icon.className = 'fas fa-times-circle validation-icon invalid';
        return false;
    } else {
        errorElement.textContent = '';
        icon.className = 'fas fa-check-circle validation-icon valid';
        return true;
    }
}