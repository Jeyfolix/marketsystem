const API_BASE_URL = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1'
    ? 'http://localhost/marketSystem/backend/api'
    : 'https://marketsystem-api.onrender.com/api';

AOS.init({ duration: 800, once: true, offset: 50 });

const userData = JSON.parse(sessionStorage.getItem('user') || '{}');

if (!userData || !userData.id) {
    window.location.href = 'index.html';
}

document.getElementById('userName').textContent = userData.name || userData.username;
document.getElementById('profileImage').textContent = (userData.name || userData.username).charAt(0).toUpperCase();

window.showSection = function(section) {
    document.querySelectorAll('.sidebar-menu li').forEach(li => li.classList.remove('active'));
    event.currentTarget.classList.add('active');
    
    if (section === 'dashboard') showDashboard();
    else if (section === 'payments') showPayments();
    else if (section === 'referrals') showReferrals();
    else if (section === 'earnings') showEarnings();
    else if (section === 'withdraw') showWithdraw();
    else if (section === 'settings') showSettings();
};

function showDashboard() {
    document.getElementById('mainContent').innerHTML = `
        <div class="welcome-banner">
            <h1>Welcome back! 🚀</h1>
            <p>Share your referral code and start earning today!</p>
        </div>
        <div class="stats-grid" id="statsContainer">
            <div class="stat-card"><div class="stat-icon"><i class="fas fa-spinner fa-spin"></i></div><div class="stat-value">Loading...</div><div class="stat-label">Loading stats</div></div>
        </div>
        <div class="referral-section">
            <div class="referral-header"><h2>Your Referral Code</h2><p>Share this code and earn KES 150 per referral!</p></div>
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
                <p style="margin-top: 15px;"><i class="fas fa-info-circle"></i> When someone registers with this link, your referral code will be auto-filled</p>
            </div>
        </div>
        <div class="recent-activity">
            <div class="activity-header"><h3><i class="fas fa-history"></i> Recent Referrals</h3><span class="view-all" onclick="showSection('referrals')">View All →</span></div>
            <div id="recentActivity"></div>
        </div>
    `;
    loadDashboardData();
}

async function loadDashboardData() {
    try {
        const response = await fetch(`${API_BASE_URL}/dashboard_data.php`, {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userData.id })
        });
        const data = await response.json();
        if (data.success) {
            const u = data.user, s = data.stats, r = data.recent_referrals;
            document.getElementById('statsContainer').innerHTML = `
                <div class="stat-card"><div class="stat-icon"><i class="fas fa-users"></i></div><div class="stat-value">${s.total_referrals}</div><div class="stat-label">TOTAL REFERRALS</div><small>${s.referrals_this_month} this month</small></div>
                <div class="stat-card"><div class="stat-icon"><i class="fas fa-coins"></i></div><div class="stat-value">KES ${s.total_earnings.toLocaleString()}</div><div class="stat-label">TOTAL EARNINGS</div><small>KES ${s.earnings_this_month.toLocaleString()} this month</small></div>
                <div class="stat-card"><div class="stat-icon"><i class="fas fa-wallet"></i></div><div class="stat-value">KES ${s.available_balance.toLocaleString()}</div><div class="stat-label">AVAILABLE BALANCE</div></div>
                <div class="stat-card"><div class="stat-icon"><i class="fas fa-trophy"></i></div><div class="stat-value" style="color:${s.rank_color}">${s.rank}</div><div class="stat-label">YOUR RANK</div></div>
            `;
            document.getElementById('referralCodeDisplay').textContent = u.referral_code;
            document.getElementById('referralLink').value = `https://jeyfolix.github.io/marketsystem/frontend/register.html?ref=${u.referral_code}`;
            document.getElementById('recentActivity').innerHTML = !r || r.length === 0 
                ? '<div class="activity-item"><div class="activity-avatar"><i class="fas fa-user-plus"></i></div><div><div><strong>No referrals yet</strong></div><small>Start sharing your code!</small></div></div>'
                : r.map(ref => {
                    const timeAgo = getTimeAgo(new Date(ref.created_at));
                    const initials = ref.name.split(' ').map(n => n[0]).join('').substring(0,2).toUpperCase();
                    return `<div class="activity-item"><div class="activity-avatar">${initials}</div><div><div><strong>${ref.name}</strong></div><small>${timeAgo}</small></div><div class="activity-amount">+KES 150</div></div>`;
                }).join('');
        }
    } catch (error) { console.error('Error:', error); }
}

function getTimeAgo(date) {
    const seconds = Math.floor((new Date() - date) / 1000);
    const intervals = { year: 31536000, month: 2592000, week: 604800, day: 86400, hour: 3600, minute: 60 };
    for (const [unit, sec] of Object.entries(intervals)) {
        const interval = Math.floor(seconds / sec);
        if (interval >= 1) return `${interval} ${unit}${interval > 1 ? 's' : ''} ago`;
    }
    return 'just now';
}

function showPayments() {
    document.getElementById('mainContent').innerHTML = `
        <div class="payment-section">
            <div class="payment-header"><h2>🔐 Verify Your Account</h2><p>Pay KES 300 to activate your account and start earning</p></div>
            <div class="mpesa-details">
                <i class="fas fa-mobile-alt" style="font-size:3rem;color:var(--primary);margin-bottom:15px;"></i>
                <h3>Send to M-PESA</h3>
                <div class="mpesa-number" id="mpesaNumber">0701603497</div>
                <button class="copy-btn" onclick="copyMpesaNumber()"><i class="fas fa-copy"></i> Copy Number</button>
                <div class="amount-badge">Amount: KES 300</div>
            </div>
            <form id="paymentForm">
                <div class="form-group"><label>Your M-PESA Phone Number</label><input type="tel" id="phone" placeholder="e.g., 0701603497" required></div>
                <div class="form-group"><label>M-PESA Transaction Code</label><input type="text" id="mpesaCode" placeholder="e.g., PPI8J3K4L5" required></div>
                <div class="form-group"><label>Email Address</label><input type="email" id="email" placeholder="your@email.com" required></div>
                <button type="submit" class="verify-btn" id="verifyBtn"><i class="fas fa-check-circle"></i> Submit Payment</button>
            </form>
            <div id="paymentMessage" class="message"></div>
        </div>
        <div class="transactions-section">
            <h2><i class="fas fa-history"></i> Payment History</h2>
            <table class="transactions-table"><thead><tr><th>Date</th><th>Phone</th><th>M-PESA Code</th><th>Amount</th><th>Status</th></tr></thead><tbody id="paymentHistory"><tr><td colspan="5" style="text-align:center;">Loading...</td></tr></tbody></table>
        </div>
    `;
    loadPayments(); setupPaymentForm();
}

async function loadPayments() {
    try {
        const response = await fetch(`${API_BASE_URL}/get_user_payments.php`, {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userData.id })
        });
        const data = await response.json();
        if (data.success) {
            document.getElementById('paymentHistory').innerHTML = !data.payments || data.payments.length === 0
                ? '<tr><td colspan="5" style="text-align:center;">No payment history</td></tr>'
                : data.payments.map(p => `<tr><td>${new Date(p.created_at).toLocaleString()}</td><td>${p.phone}</td><td><strong>${p.mpesa_code}</strong></td><td>KES ${p.amount}</td><td><span class="${p.status === 'verified' ? 'badge-verified' : 'badge-pending'}">${p.status}</span></td></tr>`).join('');
        }
    } catch (error) { console.error('Error:', error); }
}

function setupPaymentForm() {
    document.getElementById('paymentForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('verifyBtn');
        btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
        try {
            const response = await fetch(`${API_BASE_URL}/verify_payment.php`, {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: userData.id, phone: document.getElementById('phone').value, email: document.getElementById('email').value, mpesa_code: document.getElementById('mpesaCode').value })
            });
            const data = await response.json();
            const msg = document.getElementById('paymentMessage');
            msg.className = `message ${data.success ? 'success' : 'error'}`;
            msg.innerHTML = `<i class="fas fa-${data.success ? 'check-circle' : 'exclamation-circle'}"></i> ${data.message}`;
            if (data.success) { document.getElementById('paymentForm').reset(); loadPayments(); }
        } catch (error) {
            document.getElementById('paymentMessage').className = 'message error';
            document.getElementById('paymentMessage').innerHTML = '<i class="fas fa-exclamation-circle"></i> Connection error';
        }
        btn.disabled = false; btn.innerHTML = '<i class="fas fa-check-circle"></i> Submit Payment';
    });
}

function showReferrals() {
    document.getElementById('mainContent').innerHTML = `
        <div class="transactions-section">
            <h2><i class="fas fa-users"></i> My Referrals</h2>
            <table class="transactions-table"><thead><tr><th>Name</th><th>Phone</th><th>Email</th><th>Date Joined</th></tr></thead><tbody id="referralsList"><tr><td colspan="4" style="text-align:center;">Loading...</td></tr></tbody></table>
        </div>
    `;
    loadReferrals();
}

async function loadReferrals() {
    try {
        const response = await fetch(`${API_BASE_URL}/get_user_payments.php`, {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userData.id })
        });
        const data = await response.json();
        if (data.success) {
            document.getElementById('referralsList').innerHTML = !data.referrals || data.referrals.length === 0
                ? '<tr><td colspan="4" style="text-align:center;">No referrals yet</td></tr>'
                : data.referrals.map(ref => `<tr><td><strong>${ref.name}</strong></td><td>${ref.phone || 'N/A'}</td><td>${ref.email}</td><td>${new Date(ref.created_at).toLocaleDateString()}</td></tr>`).join('');
        }
    } catch (error) { console.error('Error:', error); }
}

function showEarnings() {
    document.getElementById('mainContent').innerHTML = `
        <div class="transactions-section">
            <h2><i class="fas fa-chart-line"></i> Earnings History</h2>
            <div style="background:linear-gradient(135deg,var(--primary),var(--secondary));color:white;padding:20px;border-radius:10px;margin-bottom:20px;">
                <h3 style="color:white;">Total Earned: <span id="totalEarned">KES 0</span></h3>
            </div>
            <table class="transactions-table"><thead><tr><th>Date</th><th>From</th><th>Commission</th></tr></thead><tbody id="earningsList"><tr><td colspan="3" style="text-align:center;">Loading...</td></tr></tbody></table>
        </div>
    `;
    loadEarnings();
}

async function loadEarnings() {
    try {
        const response = await fetch(`${API_BASE_URL}/get_earnings.php`, {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userData.id })
        });
        const data = await response.json();
        if (data.success) {
            document.getElementById('totalEarned').textContent = `KES ${data.total.toLocaleString()}`;
            document.getElementById('earningsList').innerHTML = !data.earnings || data.earnings.length === 0
                ? '<tr><td colspan="3" style="text-align:center;">No earnings yet</td></tr>'
                : data.earnings.map(e => `<tr><td>${new Date(e.created_at).toLocaleDateString()}</td><td>${e.referred_name}</td><td>KES ${e.commission}</td></tr>`).join('');
        }
    } catch (error) { console.error('Error:', error); }
}

function showWithdraw() {
    document.getElementById('mainContent').innerHTML = `
        <div class="withdrawal-section">
            <div class="withdrawal-header"><h3><i class="fas fa-hand-holding-usd"></i> Withdraw Your Earnings</h3><span class="min-amount">Min: KES 100</span></div>
            <div class="withdrawal-methods">
                <div class="method-card selected" onclick="selectMethod('mpesa')"><div class="method-icon"><i class="fas fa-mobile-alt"></i></div><div><h4>M-PESA</h4><p>Instant to phone</p></div></div>
                <div class="method-card" onclick="selectMethod('bank')"><div class="method-icon"><i class="fas fa-university"></i></div><div><h4>Bank Transfer</h4><p>1-2 business days</p></div></div>
            </div>
            <div class="withdraw-input"><label>Amount (KES)</label><div class="input-group"><span>KES</span><input type="number" id="withdrawAmount" min="100" step="50" value="100"></div></div>
            <div class="form-group"><label>M-PESA Phone Number</label><input type="tel" id="withdrawPhone" placeholder="e.g., 0701603497" required></div>
            <button class="withdraw-btn" onclick="processWithdrawal()"><i class="fas fa-arrow-right"></i> Withdraw Funds</button>
            <div id="withdrawMessage" class="message"></div>
        </div>
    `;
}

window.processWithdrawal = async function() {
    const amount = document.getElementById('withdrawAmount').value;
    const phone = document.getElementById('withdrawPhone').value;
    const method = document.querySelector('.method-card.selected h4').textContent === 'M-PESA' ? 'mpesa' : 'bank';
    const btn = document.querySelector('.withdraw-btn');
    const msg = document.getElementById('withdrawMessage');
    
    if (!phone) { msg.className = 'message error'; msg.innerHTML = '<i class="fas fa-exclamation-circle"></i> Enter phone number'; return; }
    if (amount < 100) { msg.className = 'message error'; msg.innerHTML = '<i class="fas fa-exclamation-circle"></i> Minimum withdrawal is KES 100'; return; }
    
    btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    try {
        const response = await fetch(`${API_BASE_URL}/process_withdrawal.php`, {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userData.id, amount, method, phone })
        });
        const data = await response.json();
        msg.className = `message ${data.success ? 'success' : 'error'}`;
        msg.innerHTML = `<i class="fas fa-${data.success ? 'check-circle' : 'exclamation-circle'}"></i> ${data.message}`;
        if (data.success) { document.getElementById('withdrawAmount').value = '100'; document.getElementById('withdrawPhone').value = ''; }
    } catch (error) {
        msg.className = 'message error';
        msg.innerHTML = '<i class="fas fa-exclamation-circle"></i> Connection error';
    }
    btn.disabled = false; btn.innerHTML = '<i class="fas fa-arrow-right"></i> Withdraw Funds';
};

function showSettings() {
    document.getElementById('mainContent').innerHTML = `
        <div class="transactions-section">
            <h2><i class="fas fa-cog"></i> Account Settings</h2>
            <div style="text-align:center;padding:30px;">
                <p><i class="fas fa-user-circle" style="font-size:80px;color:var(--primary);"></i></p>
                <h3>${userData.name || userData.username}</h3>
                <p>Email: ${userData.email || 'N/A'}</p>
                <p>Member since: ${new Date(userData.created_at).toLocaleDateString()}</p>
                <button class="copy-btn" style="margin-top:20px;" onclick="alert('Profile update coming soon!')">Edit Profile</button>
            </div>
        </div>
    `;
}

window.copyMpesaNumber = function() {
    navigator.clipboard.writeText('0701603497').then(() => {
        const btn = document.querySelector('.copy-btn');
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
        setTimeout(() => btn.innerHTML = orig, 2000);
    });
};

window.selectMethod = function() {
    document.querySelectorAll('.method-card').forEach(c => c.classList.remove('selected'));
    event.currentTarget.classList.add('selected');
};

window.toggleReferralLink = function() {
    const box = document.getElementById('referralLinkBox');
    box.style.display = box.style.display === 'none' || box.style.display === '' ? 'block' : 'none';
};

window.copyReferralLink = function(e) {
    e.stopPropagation();
    document.getElementById('referralLink').select();
    document.execCommand('copy');
    const btn = e.currentTarget;
    const orig = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
    setTimeout(() => btn.innerHTML = orig, 2000);
};

window.shareOnWhatsApp = function(e) {
    e.preventDefault();
    window.open(`https://wa.me/?text=${encodeURIComponent('Join marketSystem! Use my link: ' + document.getElementById('referralLink').value)}`);
};

window.shareOnFacebook = function(e) {
    e.preventDefault();
    window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(document.getElementById('referralLink').value)}`);
};

window.shareOnTwitter = function(e) {
    e.preventDefault();
    window.open(`https://twitter.com/intent/tweet?text=Join%20marketSystem!&url=${encodeURIComponent(document.getElementById('referralLink').value)}`);
};

window.shareOnTelegram = function(e) {
    e.preventDefault();
    window.open(`https://t.me/share/url?url=${encodeURIComponent(document.getElementById('referralLink').value)}&text=Join%20marketSystem!`);
};

document.getElementById('logoutBtn').addEventListener('click', function() {
    if (confirm('Logout?')) {
        sessionStorage.removeItem('user');
        window.location.href = '../index.html';
    }
});

document.addEventListener('DOMContentLoaded', showDashboard);
