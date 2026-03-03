// API Base URL - Same as register.js
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
        showError('Connection error! Please check your internet connection and try again.');
    }
}

// Update UI with real data
function updateDashboardUI(data) {
    const user = data.user;
    const stats = data.stats;
    const referrals = data.recent_referrals;

    // Update user info
    document.getElementById('userName').textContent = user.name || user.username;
    document.getElementById('profileImage').src = `https://ui-avatars.com/api/?name=${encodeURIComponent(user.name || user.username)}&background=2563eb&color=fff&size=128`;
    
    // Update welcome message
    const hour = new Date().getHours();
    let greeting = 'Good evening';
    if (hour < 12) greeting = 'Good morning';
    else if (hour < 17) greeting = 'Good afternoon';
    
    document.getElementById('welcomeMessage').textContent = `${greeting}, ${user.name.split(' ')[0]}! 🚀`;
    
    if (stats.referrals_today > 0) {
        document.getElementById('welcomeSubtext').textContent = `You have ${stats.referrals_today} new referral${stats.referrals_today > 1 ? 's' : ''} today! Keep up the great work!`;
    }

    // Update referral code
    document.getElementById('referralCodeDisplay').textContent = user.referral_code;
    const baseUrl = window.location.origin + '/marketSystem/frontend/register.html';
    const referralLink = `${baseUrl}?ref=${user.referral_code}`;
    document.getElementById('referralLink').value = referralLink;

    // Update stats
    const statsGrid = document.getElementById('statsContainer');
    statsGrid.innerHTML = `
        <div class="stat-card" data-aos="fade-up" data-aos-delay="100">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-value">${stats.total_referrals}</div>
            <div class="stat-label">Total Referrals</div>
            <small>${stats.referrals_this_month} this month</small>
        </div>
        <div class="stat-card" data-aos="fade-up" data-aos-delay="200">
            <div class="stat-icon"><i class="fas fa-coins"></i></div>
            <div class="stat-value">KES ${stats.total_earnings.toLocaleString()}</div>
            <div class="stat-label">Total Earnings</div>
            <small>KES ${stats.earnings_this_month.toLocaleString()} this month</small>
        </div>
        <div class="stat-card" data-aos="fade-up" data-aos-delay="300">
            <div class="stat-icon"><i class="fas fa-wallet"></i></div>
            <div class="stat-value">KES ${stats.available_balance.toLocaleString()}</div>
            <div class="stat-label">Available Balance</div>
        </div>
        <div class="stat-card" data-aos="fade-up" data-aos-delay="400">
            <div class="stat-icon"><i class="fas fa-trophy"></i></div>
            <div class="stat-value" style="color: ${stats.rank_color || '#f59e0b'};">${stats.rank || 'Bronze'}</div>
            <div class="stat-label">Your Rank</div>
        </div>
    `;

    // Update recent referrals
    const activityList = document.getElementById('recentActivity');
    
    if (!referrals || referrals.length === 0) {
        activityList.innerHTML = `
            <div class="activity-item">
                <div class="activity-avatar"><i class="fas fa-user-plus"></i></div>
                <div class="activity-details">
                    <div class="activity-user">No referrals yet</div>
                    <div class="activity-time">Start sharing your code!</div>
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
                    <div class="activity-details">
                        <div class="activity-user">${ref.name}</div>
                        <div class="activity-time"><i class="far fa-clock"></i> ${timeAgo}</div>
                    </div>
                    <div class="activity-amount">+KES 150</div>
                </div>
            `;
        }).join('');
    }
}

// Helper function for time ago
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

// Show error message
function showError(message) {
    const statsGrid = document.getElementById('statsContainer');
    statsGrid.innerHTML = `
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i>
            <p>${message}</p>
            <button class="retry-btn" onclick="loadDashboardData()">Retry</button>
        </div>
    `;
}

// Navigation
function navigateTo(page) {
    alert(`${page} page coming soon!`);
}

// Toggle referral link
function toggleReferralLink() {
    document.getElementById('referralLinkBox').classList.toggle('show');
}

// Copy referral link
function copyReferralLink(event) {
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
}

// Social share functions
function shareOnWhatsApp(event) {
    event.preventDefault();
    event.stopPropagation();
    const referralLink = document.getElementById('referralLink').value;
    const text = `Join marketSystem and start earning! Use my referral link: ${referralLink}`;
    window.open(`https://wa.me/?text=${encodeURIComponent(text)}`, '_blank');
}

function shareOnFacebook(event) {
    event.preventDefault();
    event.stopPropagation();
    const referralLink = document.getElementById('referralLink').value;
    window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(referralLink)}`, '_blank');
}

function shareOnTwitter(event) {
    event.preventDefault();
    event.stopPropagation();
    const referralLink = document.getElementById('referralLink').value;
    const text = 'Join marketSystem and start earning!';
    window.open(`https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}&url=${encodeURIComponent(referralLink)}`, '_blank');
}

function shareOnTelegram(event) {
    event.preventDefault();
    event.stopPropagation();
    const referralLink = document.getElementById('referralLink').value;
    const text = `Join marketSystem and start earning! ${referralLink}`;
    window.open(`https://t.me/share/url?url=${encodeURIComponent(referralLink)}&text=${encodeURIComponent(text)}`, '_blank');
}

// Withdrawal methods
function selectMethod(method) {
    document.querySelectorAll('.method-card').forEach(card => {
        card.classList.remove('selected');
    });
    event.currentTarget.classList.add('selected');
}

// Process withdrawal
function processWithdrawal() {
    const amount = document.getElementById('withdrawAmount').value;
    const balanceEl = document.querySelector('.stat-card:nth-child(3) .stat-value');
    const balance = parseInt(balanceEl.textContent.replace('KES ', '').replace(',', ''));
    
    if (amount < 100) {
        alert('Minimum withdrawal amount is KES 100');
        return;
    }
    
    if (amount > balance) {
        alert('Insufficient balance');
        return;
    }
    
    if (confirm(`Withdraw KES ${amount} to your M-PESA?`)) {
        alert('Withdrawal request submitted! You will receive payment within 24 hours.');
    }
}

// View all referrals
function viewAllReferrals() {
    navigateTo('referrals');
}

// Logout
document.getElementById('logoutBtn').addEventListener('click', function() {
    if (confirm('Are you sure you want to logout?')) {
        sessionStorage.removeItem('user');
        window.location.href = '../index.html';
    }
});

// Load data when page loads
document.addEventListener('DOMContentLoaded', loadDashboardData);
