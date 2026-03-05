// Payment Module - Handles all payment related functionality

// API Base URL (same as dashboard)
const PAYMENT_API_URL = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1'
    ? 'http://localhost/marketSystem/backend/api'
    : 'https://marketsystem-api.onrender.com/api';

// Get user data from session
const userData = JSON.parse(sessionStorage.getItem('user') || '{}');

// Initialize payment section
function initPaymentSection() {
    console.log('Payment section initialized');
    loadPaymentData();
    setupPaymentForm();
}

// Load payment data from server
async function loadPaymentData() {
    try {
        const response = await fetch(`${PAYMENT_API_URL}/transactions.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ user_id: userData.id })
        });

        const data = await response.json();
        console.log('Payment data loaded:', data);

        if (data.success) {
            updatePaymentUI(data);
        } else {
            showPaymentError(data.message || 'Failed to load payment data');
        }
    } catch (error) {
        console.error('Error loading payment data:', error);
        showPaymentError('Connection error! Please try again.');
    }
}

// Update payment UI with data
function updatePaymentUI(data) {
    const payments = data.payments || [];
    const currentStatus = data.current_status || 'unpaid';
    
    // Update payment status card
    const statusCard = document.getElementById('paymentStatusCard');
    const statusBadge = document.getElementById('paymentStatus');
    
    if (statusCard && statusBadge) {
        // Remove all possible status classes
        statusCard.classList.remove('pending', 'verified');
        
        if (currentStatus === 'verified') {
            statusCard.classList.add('verified');
            statusBadge.className = 'payment-status status-verified';
            statusBadge.innerHTML = '<i class="fas fa-check-circle"></i> Verified';
        } else if (currentStatus === 'pending') {
            statusCard.classList.add('pending');
            statusBadge.className = 'payment-status status-pending';
            statusBadge.innerHTML = '<i class="fas fa-clock"></i> Pending Verification';
        } else {
            statusCard.classList.add('pending');
            statusBadge.className = 'payment-status status-pending';
            statusBadge.innerHTML = '<i class="fas fa-exclamation-circle"></i> Not Paid';
        }
    }
    
    // Update payment history table
    updatePaymentHistory(payments);
}

// Update payment history table
function updatePaymentHistory(payments) {
    const tbody = document.getElementById('paymentHistory');
    if (!tbody) return;
    
    if (!payments || payments.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 30px;">No payment history yet</td></tr>';
        return;
    }
    
    tbody.innerHTML = payments.map(p => {
        const date = new Date(p.created_at).toLocaleString();
        const statusClass = p.status === 'verified' ? 'badge-verified' : 'badge-pending';
        const statusText = p.status === 'verified' ? 'Verified' : 'Pending';
        
        return `
            <tr>
                <td>${date}</td>
                <td>${p.phone || 'N/A'}</td>
                <td><strong>${p.mpesa_code || 'N/A'}</strong></td>
                <td>KES ${p.amount || 300}</td>
                <td><span class="${statusClass}">${statusText}</span></td>
            </tr>
        `;
    }).join('');
}

// Setup payment form submission
function setupPaymentForm() {
    const form = document.getElementById('paymentForm');
    if (!form) return;
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const phone = document.getElementById('phone').value.trim();
        const mpesaCode = document.getElementById('mpesaCode').value.trim();
        const email = document.getElementById('email').value.trim();
        const verifyBtn = document.getElementById('verifyBtn');
        const messageDiv = document.getElementById('paymentMessage');
        
        // Validate inputs
        if (!phone || !mpesaCode || !email) {
            showPaymentMessage('Please fill in all fields', 'error', messageDiv);
            return;
        }
        
        if (phone.length < 10) {
            showPaymentMessage('Please enter a valid phone number', 'error', messageDiv);
            return;
        }
        
        if (mpesaCode.length < 5) {
            showPaymentMessage('Please enter a valid M-PESA code', 'error', messageDiv);
            return;
        }
        
        if (!email.includes('@') || !email.includes('.')) {
            showPaymentMessage('Please enter a valid email address', 'error', messageDiv);
            return;
        }
        
        // Disable button and show loading
        verifyBtn.disabled = true;
        verifyBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
        
        try {
            const response = await fetch(`${PAYMENT_API_URL}/verify_payment.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    user_id: userData.id,
                    phone: phone,
                    email: email,
                    mpesa_code: mpesaCode,
                    amount: 300
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                showPaymentMessage('Payment submitted successfully! Admin will verify within 24 hours.', 'success', messageDiv);
                form.reset();
                loadPaymentData(); // Reload payment data
            } else {
                showPaymentMessage(data.message || 'Payment submission failed', 'error', messageDiv);
            }
        } catch (error) {
            console.error('Payment error:', error);
            showPaymentMessage('Connection error. Please try again.', 'error', messageDiv);
        } finally {
            // Re-enable button
            verifyBtn.disabled = false;
            verifyBtn.innerHTML = '<i class="fas fa-check-circle"></i> Submit Payment';
        }
    });
}

// Show payment message
function showPaymentMessage(message, type, messageDiv) {
    if (!messageDiv) return;
    
    messageDiv.className = `message ${type}`;
    messageDiv.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${message}`;
    messageDiv.style.display = 'block';
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        messageDiv.style.display = 'none';
    }, 5000);
}

// Show payment error
function showPaymentError(message) {
    const tbody = document.getElementById('paymentHistory');
    if (tbody) {
        tbody.innerHTML = `<tr><td colspan="5" style="text-align: center; color: #dc2626; padding: 30px;">${message}</td></tr>`;
    }
}

// Copy M-PESA number
window.copyMpesaNumber = function() {
    const mpesaNumber = document.getElementById('mpesaNumber').textContent;
    navigator.clipboard.writeText(mpesaNumber).then(() => {
        const copyBtn = document.querySelector('.copy-btn');
        const originalText = copyBtn.innerHTML;
        copyBtn.innerHTML = '<i class="fas fa-check"></i> Copied!';
        setTimeout(() => {
            copyBtn.innerHTML = originalText;
        }, 2000);
    });
};

// Export functions for use in dashboard
window.initPaymentSection = initPaymentSection;
window.copyMpesaNumber = copyMpesaNumber;
