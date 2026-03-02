// API Base URL
const API_BASE_URL = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1'
    ? 'http://localhost/marketSystem/backend/api'
    : 'https://marketsystem-api.onrender.com/api';

document.addEventListener('DOMContentLoaded', function() {
    console.log('Register page loaded');
    
    // Check for referral code in URL
    const urlParams = new URLSearchParams(window.location.search);
    const refCode = urlParams.get('ref');
    
    if (refCode) {
        console.log('Referral code found:', refCode);
        document.getElementById('referral_code').value = refCode;
        showReferralInfo(refCode);
    }

    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        console.log('Register form found');
        registerForm.addEventListener('submit', handleRegister);
    } else {
        console.error('Register form not found!');
    }
});

async function showReferralInfo(refCode) {
    try {
        const response = await fetch(`${API_BASE_URL}/get_referrer.php?code=${refCode}`);
        const data = await response.json();
        
        if (data.success) {
            const referralInfo = document.getElementById('referralInfo');
            document.getElementById('referrerName').textContent = data.referrer;
            referralInfo.style.display = 'block';
        }
    } catch (error) {
        console.error('Error checking referral:', error);
    }
}

async function handleRegister(e) {
    e.preventDefault();
    console.log('Register handler called');
    
    // Get form values
    const name = document.getElementById('name').value;
    const email = document.getElementById('email').value;
    const phone = document.getElementById('phone').value;
    const username = document.getElementById('username').value;
    const country = document.getElementById('country').value;
    const referral_code = document.getElementById('referral_code').value;
    const password = document.getElementById('password').value;
    const confirm_password = document.getElementById('confirm_password').value;
    
    console.log('Form data:', { name, email, phone, username, country, referral_code });
    
    // Validation
    if (password !== confirm_password) {
        showMessage('Passwords do not match!', 'error');
        return;
    }
    
    if (password.length < 6) {
        showMessage('Password must be at least 6 characters!', 'error');
        return;
    }
    
    if (!validateEmail(email)) {
        showMessage('Please enter a valid email address!', 'error');
        return;
    }
    
    if (!validatePhone(phone)) {
        showMessage('Please enter a valid phone number!', 'error');
        return;
    }
    
    // Prepare data for API
    const requestData = {
        name: name,
        username: username,
        email: email,
        phone: phone,
        country: country,
        password: password
    };
    
    // Add referral code if provided
    if (referral_code && referral_code.trim() !== '') {
        requestData.referral_code = referral_code.trim();
    }
    
    console.log('Sending to API:', requestData);
    
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registering...';
    submitBtn.disabled = true;
    
    try {
        const response = await fetch(`${API_BASE_URL}/register.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(requestData)
        });
        
        console.log('Response status:', response.status);
        const data = await response.json();
        console.log('Response data:', data);
        
        if (data.success) {
            showMessage('Registration successful! Your referral code: ' + data.referral_code, 'success');
            e.target.reset();
            
            setTimeout(() => {
                window.location.href = 'index.html';
            }, 3000);
        } else {
            showMessage(data.message || 'Registration failed!', 'error');
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    } catch (error) {
        console.error('Error:', error);
        showMessage('Connection error! Please try again.', 'error');
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }
}

function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function validatePhone(phone) {
    const re = /^[\d\s\+\-\(\)]{10,20}$/;
    return re.test(phone);
}

function showMessage(message, type) {
    const messageDiv = document.getElementById('message');
    if (messageDiv) {
        messageDiv.textContent = message;
        messageDiv.className = `message ${type}`;
        messageDiv.style.display = 'block';
        
        setTimeout(() => {
            messageDiv.style.display = 'none';
        }, 5000);
    } else {
        alert(message);
    }
}
