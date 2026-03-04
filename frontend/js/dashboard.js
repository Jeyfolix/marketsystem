// API Base URL - Same as register.js
const API_BASE_URL = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1'
    ? 'http://localhost/marketSystem/backend/api'
    : 'https://marketsystem-api.onrender.com/api';

document.addEventListener('DOMContentLoaded', function() {
    console.log('Dashboard page loaded');
    
    // Check if user is logged in
    const userData = JSON.parse(sessionStorage.getItem('user') || '{}');
    
    if (!userData || !userData.id) {
        console.log('No user found, redirecting to login');
        window.location.href = 'index.html';
        return;
    }
    
    console.log('User logged in:', userData);
    
    // Display user name
    document.getElementById('userName').textContent = userData.name || userData.username;
    
    // Load dashboard data
    loadDashboardData(userData.id);
    
    // Setup event listeners
    setupEventListeners();
});

async function loadDashboardData(userId) {
    console.log('Loading dashboard data for user ID:', userId);
    
    try {
        const response = await fetch(`${API_BASE_URL}/dashboard_data.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ user_id: userId })
        });
        
        console.log('Response status:', response.status);
        const data = await response.json();
        console.log('Dashboard data:', data);
        
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

function updateDashboardUI(data) {
    const user = data.user;
    const stats = data.stats;
    const referrals = data.recent_referrals;
    
    // Update user info
    document.getElementById('userName').textContent = user.name || user.username;
    
    // Update profile image with user initials
    const initials = (user.name || user.username).charAt(0).toUpperCase();
    document.getElementById('profileImage').src = `https://ui-avatars.com/api/?name=${encodeURIComponent(user.name || user.username)}&background=2563eb&color=fff&size=128`;
    
    // Update welcome message
    updateWelcomeMessage(user.name, stats.referrals_today);
    
    // Update referral code
    updateReferralCode(user.referral_code);
    
    // Update stats
    updateStats(stats);
    
    // Update recent referrals
    updateRecentReferrals(referrals);
    
    // Update rank color
    updateRankColor(stats.rank_color);
}

function updateWelcomeMessage(userName, referralsToday) {
    const hour = new Date().getHours();
    let greeting = 'Good evening';
    if (hour < 12) greeting = 'Good morning';
    else if (hour < 17) greeting = 'Good afternoon';
    
    const firstName = userName.split(' ')[0];
    document.getElementById('welcomeMessage').textContent = `${greeting}, ${firstName}! 🚀`;
    
    if (referralsToday > 0) {
        document.getElementById('welcomeSubtext').textContent = `You have ${referralsToday} new referral${referralsToday > 1 ? 's' : ''} today! Keep up the great work!`;
    } else {
        document.getElementById('welcomeSubtext').textContent = 'Share your referral code and start earning today!';
    }
}

function updateReferralCode(referralCode) {
    document.getElementById('referralCodeDisplay').textContent = referralCode;
    
    // Generate full referral link
    const baseUrl = window.location.origin + '/marketSystem/frontend/register.html';
    const referralLink = `${baseUrl}?ref=${referralCode}`;
    document.getElementById('referralLink').value = referralLink;
}

function updateStats(stats) {
    const statsGrid = document.getElementById('statsContainer');
    statsGrid.innerHTML = `
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-value">${stats.total_referrals}</div>
            <div class="stat-label">Total Referrals</div>
            <small>${stats.referrals_this_month} this month</small>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-coins"></i></div>
            <div class="stat-value">KES ${stats.total_earnings.toLocaleString()}</div>
            <div class="stat-label">Total Earnings</div>
            <small>KES ${stats.earnings_this_month.toLocaleString()} this month</small>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-wallet"></i></div>
            <div class="stat-value">KES ${stats.available_balance.toLocaleString()}</div>
            <div class="stat-label">Available Balance</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-trophy"></i></div>
            <div class="stat-value" id="rankValue">${stats.rank}</div>
            <div class="stat-label">Your Rank</div>
        </div>
    `;
}

function updateRecentReferrals(referrals) {
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

function updateRankColor(color) {
    const rankElement = document.getElementById('rankValue');
    if (rankElement) {
        rankElement.style.color = color;
    }
}

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

function showError(message) {
    const statsGrid = document.getElementById('statsContainer');
    statsGrid.innerHTML = `
        <div class="error-message" style="grid-column: 1/-1; text-align: center; padding: 50px;">
            <i class="fas fa-exclamation-circle" style="font-size: 48px; color: #ef4444;"></i>
            <p style="margin-top: 20px;">${message}</p>
            <button class="copy-btn" onclick="location.reload()" style="margin-top: 20px;">Retry</button>
        </div>
    `;
}

function setupEventListeners() {
    // Logout button
    document.getElementById('logoutBtn').addEventListener('click', function() {
        if (confirm('Are you sure you want to logout?')) {
            sessionStorage.removeItem('user');
            window.location.href = 'index.html';
        }
    });
    
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
    
    // Toggle referral link
    window.toggleReferralLink = function() {
        document.getElementById('referralLinkBox').classList.toggle('show');
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
    
    // Withdrawal
    window.processWithdrawal = function() {
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
    };
}
