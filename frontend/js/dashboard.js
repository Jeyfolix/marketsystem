// API Base URL
const API_BASE_URL = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1'
    ? 'http://localhost/marketSystem/backend/api'
    : 'https://marketsystem-api.onrender.com/api';

// Initialize AOS
AOS.init({
    duration: 800,
    once: true,
    offset: 50
});

// Get user data from session
const userData = JSON.parse(sessionStorage.getItem('user') || '{}');

if (!userData || !userData.id) {
    window.location.href = 'index.html';
}

// Display user name
document.getElementById('userName').textContent = userData.name || userData.username;
document.getElementById('profileImage').textContent = (userData.name || userData.username).charAt(0).toUpperCase();

// Load welcome banner data
async function loadWelcomeData() {
    try {
        const response = await fetch(`${API_BASE_URL}/welcome.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ user_id: userData.id })
        });

        const data = await response.json();

        if (data.success) {
            // Update welcome banner
            document.getElementById('welcomeMessage').textContent = data.welcome.message;
            document.getElementById('welcomeSubtext').textContent = data.welcome.subtext;
        }
    } catch (error) {
        console.error('Error loading welcome data:', error);
    }
}

// Load dashboard data
async function loadDashboardData() {
    try {
        const response = await fetch(`${API_BASE_URL}/dashboard.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ user_id: userData.id })
        });

        const data = await response.json();

        if (data.success) {
            updateDashboardUI(data);
        } else {
            showError(data.message || 'Failed to load dashboard data');
        }
    } catch (error) {
        console.error('Error loading dashboard:', error);
        showError('Connection error! Please try again.');
    }
}

// Update dashboard UI
function updateDashboardUI(data) {
    const user = data.user;
    const stats = data.stats;
    const referrals = data.recent_referrals;

    // Update stats
    document.getElementById('totalReferrals').textContent = stats.total_referrals;
    document.getElementById('monthlyReferrals').textContent = `${stats.referrals_this_month} this month`;
    document.getElementById('totalEarnings').textContent = `KES ${stats.total_earnings.toLocaleString()}`;
    document.getElementById('monthlyEarnings').textContent = `KES ${stats.earnings_this_month.toLocaleString()} this month`;
    document.getElementById('availableBalance').textContent = `KES ${stats.available_balance.toLocaleString()}`;
    document.getElementById('rank').textContent = stats.rank;

    // Update referral code
    document.getElementById('referralCodeDisplay').textContent = user.referral_code;
    const baseUrl = 'https://jeyfolix.github.io/marketsystem/frontend/register.html';
    const referralLink = `${baseUrl}?ref=${user.referral_code}`;
    document.getElementById('referralLink').value = referralLink;

    // Update recent referrals
    updateRecentReferrals(referrals);
}

// Update recent referrals
function updateRecentReferrals(referrals) {
    const activityList = document.getElementById('recentActivity');
    
    if (!referrals || referrals.length === 0) {
        activityList.innerHTML = `
            <div class="activity-item">
                <div class="activity-avatar"><i class="fas fa-user-plus"></i></div>
                <div>
                    <div><strong>No referrals yet</strong></div>
                    <small>Start sharing your code!</small>
                </div>
            </div>
        `;
    } else {
        activityList.innerHTML = referrals.map(ref => {
            const date = new Date(ref.created_at);
            const timeAgo = getTimeAgo(date);
            const initials = ref.name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
            
            return `
                <div class="activity-item">
                    <div class="activity-avatar">${initials}</div>
                    <div>
                        <div><strong>${ref.name}</strong></div>
                        <small>${timeAgo}</small>
                    </div>
                    <div class="activity-amount">+KES 150</div>
                </div>
            `;
        }).join('');
    }
}

// Get time ago
function getTimeAgo(date) {
    const seconds = Math.floor((new Date() - date) / 1000);
    
    const intervals = {
        year: 31536000,
        month: 2592000,
        week: 604800,
        day: 86400,
        hour: 3600,
        minute: 60
    };
    
    for (const [unit, secondsInUnit] of Object.entries(intervals)) {
        const interval = Math.floor(seconds / secondsInUnit);
        if (interval >= 1) {
            return `${interval} ${unit}${interval > 1 ? 's' : ''} ago`;
        }
    }
    return 'just now';
}

// Show error
function showError(message) {
    const mainContent = document.getElementById('mainContent');
    mainContent.innerHTML = `
        <div style="text-align: center; padding: 50px; background: white; border-radius: 20px;">
            <i class="fas fa-exclamation-circle" style="font-size: 48px; color: #dc2626;"></i>
            <p style="margin-top: 20px;">${message}</p>
            <button onclick="location.reload()" style="margin-top: 20px; padding: 10px 20px; background: #2563eb; color: white; border: none; border-radius: 5px; cursor: pointer;">Retry</button>
        </div>
    `;
}

// Show different sections
window.showSection = function(section) {
    const mainContent = document.getElementById('mainContent');
    
    // Update active menu
    document.querySelectorAll('.sidebar-menu li').forEach(li => {
        li.classList.remove('active');
    });
    event.currentTarget.classList.add('active');
    
    if (section === 'dashboard') {
        showDashboard();
    } else if (section === 'payments') {
        showPayments();
    } else if (section === 'referrals') {
        showReferrals();
    } else if (section === 'earnings') {
        showEarnings();
    } else if (section === 'withdraw') {
        showWithdraw();
    } else if (section === 'settings') {
        showSettings();
    }
};

// Show dashboard
function showDashboard() {
    const mainContent = document.getElementById('mainContent');
    mainContent.innerHTML = `
        <!-- Welcome Banner -->
        <div class="welcome-banner">
            <h1 id="welcomeMessage">Welcome back! 🚀</h1>
            <p id="welcomeSubtext">Share your referral code and start earning today!</p>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid" id="statsContainer">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-value" id="totalReferrals">0</div>
                <div class="stat-label">TOTAL REFERRALS</div>
                <small id="monthlyReferrals">0 this month</small>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-coins"></i></div>
                <div class="stat-value" id="totalEarnings">KES 0</div>
                <div class="stat-label">TOTAL EARNINGS</div>
                <small id="monthlyEarnings">KES 0 this month</small>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-wallet"></i></div>
                <div class="stat-value" id="availableBalance">KES 0</div>
                <div class="stat-label">AVAILABLE BALANCE</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-trophy"></i></div>
                <div class="stat-value" id="rank">Starter</div>
                <div class="stat-label">YOUR RANK</div>
            </div>
        </div>

        <!-- Referral Section -->
        <div class="referral-section">
            <div class="referral-header">
                <h2>Your Referral Code</h2>
                <p>Share this code and earn KES 150 per referral!</p>
            </div>
            
            <div class="referral-code-box" onclick="toggleReferralLink()">
                <div class="referral-code-text" id="referralCodeDisplay">REFXXXXXX</div>
                <div><i class="fas fa-hand-pointer"></i> Tap to reveal sharing options</div>
            </div>
            
            <div class="referral-link-box" id="referralLinkBox">
                <div class="link-input-group">
                    <input type="text" id="referralLink" readonly>
                    <button class="copy-btn" onclick="copyReferralLink(event)"><i class="fas fa-copy"></i> Copy Link</button>
                </div>
                
                <div class="social-share">
                    <a href="#" class="social-btn whatsapp" onclick="shareOnWhatsApp(event)"><i class="fab fa-whatsapp"></i></a>
                    <a href="#" class="social-btn facebook" onclick="shareOnFacebook(event)"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-btn twitter" onclick="shareOnTwitter(event)"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="social-btn telegram" onclick="shareOnTelegram(event)"><i class="fab fa-telegram-plane"></i></a>
                </div>
                <p style="margin-top: 15px; color: var(--gray); font-size: 0.9rem;">
                    <i class="fas fa-info-circle"></i> When someone registers with this link, your referral code will be auto-filled
                </p>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="recent-activity">
            <div class="activity-header">
                <h3><i class="fas fa-history"></i> Recent Referrals</h3>
                <span class="view-all" onclick="viewAllReferrals()">View All →</span>
            </div>
            <div id="recentActivity"></div>
        </div>
    `;
    
    // Load welcome banner data first
    loadWelcomeData();
    
    // Then load dashboard data
    loadDashboardData();
}

// Show payments section
function showPayments() {
    const mainContent = document.getElementById('mainContent');
    mainContent.innerHTML = `
        <!-- Payment Status Card -->
        <div class="payment-status-card pending" id="paymentStatusCard">
            <h3><i class="fas fa-clock"></i> Account Status</h3>
            <div id="paymentStatus" class="payment-status status-pending">Pending Verification</div>
            <p style="margin-top: 15px; color: var(--gray);">Complete your payment to start earning commissions</p>
        </div>

        <!-- Payment Section -->
        <div class="payment-section">
            <div class="payment-header">
                <h2>🔐 Verify Your Account</h2>
                <p>Pay KES 300 to activate your account and start earning</p>
            </div>

            <div class="mpesa-details">
                <i class="fas fa-mobile-alt" style="font-size: 3rem; color: var(--primary); margin-bottom: 15px;"></i>
                <h3>Send to M-PESA</h3>
                <div class="mpesa-number" id="mpesaNumber">0701603497</div>
                <button class="copy-btn" onclick="copyMpesaNumber()">
                    <i class="fas fa-copy"></i> Copy Number
                </button>
                <div class="amount-badge">Amount: KES 300</div>
            </div>

            <form id="paymentForm">
                <div class="form-group">
                    <label for="phone">Your M-PESA Phone Number</label>
                    <input type="tel" id="phone" placeholder="e.g., 0701603497" required>
                </div>

                <div class="form-group">
                    <label for="mpesaCode">M-PESA Transaction Code</label>
                    <input type="text" id="mpesaCode" placeholder="e.g., PPI8J3K4L5" required>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" placeholder="your@email.com" required>
                </div>

                <button type="submit" class="verify-btn" id="verifyBtn">
                    <i class="fas fa-check-circle"></i> Submit Payment
                </button>
            </form>

            <div id="paymentMessage" class="message"></div>
        </div>

        <!-- Payment History -->
        <div class="transactions-section">
            <h2><i class="fas fa-history"></i> Payment History</h2>
            <table class="transactions-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Phone</th>
                        <th>M-PESA Code</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="paymentHistory">
                    <tr>
                        <td colspan="5" style="text-align: center;">Loading...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    `;
    
    // Load payment data
    loadPaymentData();
    setupPaymentForm();
}

// Show referrals section
function showReferrals() {
    const mainContent = document.getElementById('mainContent');
    mainContent.innerHTML = `
        <div class="transactions-section">
            <h2><i class="fas fa-users"></i> My Referrals</h2>
            <table class="transactions-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Date Joined</th>
                    </tr>
                </thead>
                <tbody id="referralsList">
                    <tr>
                        <td colspan="4" style="text-align: center;">Loading...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    `;
    
    loadReferrals();
}

// Show earnings section
function showEarnings() {
    const mainContent = document.getElementById('mainContent');
    mainContent.innerHTML = `
        <div class="transactions-section">
            <h2><i class="fas fa-chart-line"></i> Earnings History</h2>
            <div style="background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                <h3 style="color: white;">Total Earned: <span id="totalEarned">KES 0</span></h3>
            </div>
            <table class="transactions-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>From</th>
                        <th>Commission</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="earningsList">
                    <tr>
                        <td colspan="4" style="text-align: center;">Loading...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    `;
    
    loadEarnings();
}

// Show withdraw section
function showWithdraw() {
    const mainContent = document.getElementById('mainContent');
    mainContent.innerHTML = `
        <div class="withdrawal-section">
            <div class="withdrawal-header">
                <h3><i class="fas fa-hand-holding-usd"></i> Withdraw Your Earnings</h3>
                <span class="min-amount">Min: KES 100</span>
            </div>

            <div class="withdrawal-methods">
                <div class="method-card selected" onclick="selectMethod('mpesa')">
                    <div class="method-icon"><i class="fas fa-mobile-alt"></i></div>
                    <div>
                        <h4>M-PESA</h4>
                        <p>Instant to phone</p>
                    </div>
                </div>
                <div class="method-card" onclick="selectMethod('bank')">
                    <div class="method-icon"><i class="fas fa-university"></i></div>
                    <div>
                        <h4>Bank Transfer</h4>
                        <p>1-2 business days</p>
                    </div>
                </div>
            </div>

            <div class="withdraw-input">
                <label>Amount (KES)</label>
                <div class="input-group">
                    <span>KES</span>
                    <input type="number" id="withdrawAmount" min="100" step="50" value="100">
                </div>
            </div>

            <div class="form-group">
                <label for="withdrawPhone">M-PESA Phone Number</label>
                <input type="tel" id="withdrawPhone" placeholder="e.g., 0701603497" required>
            </div>

            <button class="withdraw-btn" onclick="processWithdrawal()">
                <i class="fas fa-arrow-right"></i> Withdraw Funds
            </button>
            <div id="withdrawMessage" class="message"></div>
        </div>
    `;
}

// Show settings section
function showSettings() {
    const mainContent = document.getElementById('mainContent');
    mainContent.innerHTML = `
        <div class="transactions-section">
            <h2><i class="fas fa-cog"></i> Account Settings</h2>
            <div style="text-align: center; padding: 30px;">
                <p><i class="fas fa-user-circle" style="font-size: 80px; color: var(--primary);"></i></p>
                <h3>${userData.name || userData.username}</h3>
                <p>Email: ${userData.email || 'N/A'}</p>
                <p>Member since: ${new Date(userData.created_at).toLocaleDateString()}</p>
                <button class="copy-btn" style="margin-top: 20px;" onclick="alert('Profile update coming soon!')">Edit Profile</button>
            </div>
        </div>
    `;
}

// Load payment data
async function loadPaymentData() {
    try {
        const response = await fetch(`${API_BASE_URL}/get_user_payments.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ user_id: userData.id })
        });

        const data = await response.json();

        if (data.success) {
            updatePaymentUI(data);
        }
    } catch (error) {
        console.error('Error loading payment data:', error);
    }
}

// Update payment UI
function updatePaymentUI(data) {
    const payments = data.payments;
    const currentStatus = data.current_status;
    
    // Update payment status
    const statusCard = document.getElementById('paymentStatusCard');
    const statusBadge = document.getElementById('paymentStatus');
    
    if (statusCard && statusBadge) {
        if (currentStatus === 'verified') {
            statusCard.className = 'payment-status-card verified';
            statusBadge.className = 'payment-status status-verified';
            statusBadge.innerHTML = '<i class="fas fa-check-circle"></i> Verified';
        } else if (currentStatus === 'pending') {
            statusCard.className = 'payment-status-card pending';
            statusBadge.className = 'payment-status status-pending';
            statusBadge.innerHTML = '<i class="fas fa-clock"></i> Pending Verification';
        } else {
            statusCard.className = 'payment-status-card pending';
            statusBadge.className = 'payment-status status-pending';
            statusBadge.innerHTML = '<i class="fas fa-exclamation-circle"></i> Not Paid';
        }
    }
    
    // Update payment history
    const tbody = document.getElementById('paymentHistory');
    if (!tbody) return;
    
    if (!payments || payments.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center;">No payment history</td></tr>';
    } else {
        tbody.innerHTML = payments.map(p => `
            <tr>
                <td>${new Date(p.created_at).toLocaleString()}</td>
                <td>${p.phone}</td>
                <td><strong>${p.mpesa_code}</strong></td>
                <td>KES ${p.amount}</td>
                <td><span class="${p.status === 'verified' ? 'badge-verified' : 'badge-pending'}">${p.status}</span></td>
            </tr>
        `).join('');
    }
}

// Load referrals
async function loadReferrals() {
    try {
        const response = await fetch(`${API_BASE_URL}/get_user_payments.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ user_id: userData.id })
        });

        const data = await response.json();

        if (data.success && data.referrals) {
            const tbody = document.getElementById('referralsList');
            if (data.referrals.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" style="text-align: center;">No referrals yet. Start sharing your code!</td></tr>';
            } else {
                tbody.innerHTML = data.referrals.map(ref => {
                    const date = new Date(ref.created_at).toLocaleDateString();
                    return `
                        <tr>
                            <td><strong>${ref.name}</strong></td>
                            <td>${ref.phone || 'N/A'}</td>
                            <td>${ref.email}</td>
                            <td>${date}</td>
                        </tr>
                    `;
                }).join('');
            }
        }
    } catch (error) {
        console.error('Error loading referrals:', error);
    }
}

// Load earnings
async function loadEarnings() {
    try {
        const response = await fetch(`${API_BASE_URL}/get_earnings.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ user_id: userData.id })
        });

        const data = await response.json();
        const tbody = document.getElementById('earningsList');
        const totalSpan = document.getElementById('totalEarned');

        if (data.success) {
            totalSpan.textContent = `KES ${data.total.toLocaleString()}`;
            
            if (!data.earnings || data.earnings.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" style="text-align: center;">No earnings yet</td></tr>';
            } else {
                tbody.innerHTML = data.earnings.map(e => {
                    const date = new Date(e.created_at).toLocaleDateString();
                    return `
                        <tr>
                            <td>${date}</td>
                            <td>${e.referred_name}</td>
                            <td>KES ${e.commission}</td>
                            <td><span class="badge-verified">Verified</span></td>
                        </tr>
                    `;
                }).join('');
            }
        }
    } catch (error) {
        console.error('Error loading earnings:', error);
    }
}

// Setup payment form
function setupPaymentForm() {
    const form = document.getElementById('paymentForm');
    if (!form) return;
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const phone = document.getElementById('phone').value;
        const mpesaCode = document.getElementById('mpesaCode').value;
        const email = document.getElementById('email').value;
        const verifyBtn = document.getElementById('verifyBtn');
        
        verifyBtn.disabled = true;
        verifyBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
        
        try {
            const response = await fetch(`${API_BASE_URL}/verify_payment.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    user_id: userData.id,
                    phone: phone,
                    email: email,
                    mpesa_code: mpesaCode
                })
            });
            
            const data = await response.json();
            const messageDiv = document.getElementById('paymentMessage');
            
            if (data.success) {
                messageDiv.className = 'message success';
                messageDiv.innerHTML = '<i class="fas fa-check-circle"></i> ' + data.message;
                form.reset();
                loadPaymentData(); // Reload payment data
            } else {
                messageDiv.className = 'message error';
                messageDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + data.message;
            }
            
            verifyBtn.disabled = false;
            verifyBtn.innerHTML = '<i class="fas fa-check-circle"></i> Submit Payment';
            
        } catch (error) {
            console.error('Error:', error);
            const messageDiv = document.getElementById('paymentMessage');
            messageDiv.className = 'message error';
            messageDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> Connection error. Please try again.';
            
            verifyBtn.disabled = false;
            verifyBtn.innerHTML = '<i class="fas fa-check-circle"></i> Submit Payment';
        }
    });
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

// Select withdrawal method
window.selectMethod = function(method) {
    document.querySelectorAll('.method-card').forEach(card => {
        card.classList.remove('selected');
    });
    event.currentTarget.classList.add('selected');
};

// Process withdrawal
window.processWithdrawal = async function() {
    const amount = document.getElementById('withdrawAmount').value;
    const phone = document.getElementById('withdrawPhone').value;
    const method = document.querySelector('.method-card.selected h4').textContent === 'M-PESA' ? 'mpesa' : 'bank';
    const withdrawBtn = document.querySelector('.withdraw-btn');
    const messageDiv = document.getElementById('withdrawMessage');
    
    if (!phone) {
        messageDiv.className = 'message error';
        messageDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> Please enter your phone number';
        return;
    }
    
    if (amount < 100) {
        messageDiv.className = 'message error';
        messageDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> Minimum withdrawal amount is KES 100';
        return;
    }
    
    const balanceEl = document.getElementById('availableBalance');
    const balance = parseInt(balanceEl ? balanceEl.textContent.replace('KES ', '').replace(',', '') : 0);
    
    if (amount > balance) {
        messageDiv.className = 'message error';
        messageDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> Insufficient balance';
        return;
    }
    
    withdrawBtn.disabled = true;
    withdrawBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    
    try {
        const response = await fetch(`${API_BASE_URL}/process_withdrawal.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                user_id: userData.id,
                amount: amount,
                method: method,
                phone: phone
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            messageDiv.className = 'message success';
            messageDiv.innerHTML = '<i class="fas fa-check-circle"></i> ' + data.message;
            document.getElementById('withdrawAmount').value = '100';
            document.getElementById('withdrawPhone').value = '';
            
            // Reload dashboard data to update balance
            loadDashboardData();
        } else {
            messageDiv.className = 'message error';
            messageDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + data.message;
        }
    } catch (error) {
        console.error('Withdrawal error:', error);
        messageDiv.className = 'message error';
        messageDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> Connection error. Please try again.';
    }
    
    withdrawBtn.disabled = false;
    withdrawBtn.innerHTML = '<i class="fas fa-arrow-right"></i> Withdraw Funds';
};

// Toggle referral link
window.toggleReferralLink = function() {
    const linkBox = document.getElementById('referralLinkBox');
    if (linkBox.style.display === 'none' || linkBox.style.display === '') {
        linkBox.style.display = 'block';
    } else {
        linkBox.style.display = 'none';
    }
};

// Copy referral link
window.copyReferralLink = function(event) {
    event.stopPropagation();
    const linkInput = document.getElementById('referralLink');
    linkInput.select();
    document.execCommand('copy');
    
    const copyBtn = event.currentTarget;
    const originalText = copyBtn.innerHTML;
    copyBtn.innerHTML = '<i class="fas fa-check"></i> Copied!';
    setTimeout(() => {
        copyBtn.innerHTML = originalText;
    }, 2000);
};

// Social share functions
window.shareOnWhatsApp = function(event) {
    event.preventDefault();
    event.stopPropagation();
    const referralLink = document.getElementById('referralLink').value;
    const text = `Join marketSystem and start earning! Use my referral link: ${referralLink}`;
    window.open(`https://wa.me/?text=${encodeURIComponent(text)}`, '_blank');
};

window.shareOnFacebook = function(event) {
    event.preventDefault();
    event.stopPropagation();
    const referralLink = document.getElementById('referralLink').value;
    window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(referralLink)}`, '_blank');
};

window.shareOnTwitter = function(event) {
    event.preventDefault();
    event.stopPropagation();
    const referralLink = document.getElementById('referralLink').value;
    const text = 'Join marketSystem and start earning!';
    window.open(`https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}&url=${encodeURIComponent(referralLink)}`, '_blank');
};

window.shareOnTelegram = function(event) {
    event.preventDefault();
    event.stopPropagation();
    const referralLink = document.getElementById('referralLink').value;
    const text = `Join marketSystem and start earning! ${referralLink}`;
    window.open(`https://t.me/share/url?url=${encodeURIComponent(referralLink)}&text=${encodeURIComponent(text)}`, '_blank');
};

// View all referrals
window.viewAllReferrals = function() {
    showSection('referrals');
};

// Logout
document.getElementById('logoutBtn').addEventListener('click', function() {
    if (confirm('Are you sure you want to logout?')) {
        sessionStorage.removeItem('user');
        window.location.href = '../index.html';
    }
});

// Show dashboard by default
document.addEventListener('DOMContentLoaded', showDashboard);
