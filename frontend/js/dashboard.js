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
    document.getElementById('profileImage').textContent = initials;
    
    // Update welcome message
    const hour = new Date().getHours();
    let greeting = 'Good evening';
    if (hour < 12) greeting = 'Good morning';
    else if (hour < 17) greeting = 'Good afternoon';
    
    const firstName = user.name.split(' ')[0];
    document.getElementById('welcomeMessage').textContent = `${greeting}, ${firstName}! 🚀`;
    
    // Update stats
    document.getElementById('totalReferrals').textContent = stats.total_referrals;
    document.getElementById('monthlyReferrals').textContent = `${stats.referrals_this_month} this month`;
    document.getElementById('totalEarnings').textContent = `KES ${stats.total_earnings.toLocaleString()}`;
    document.getElementById('monthlyEarnings').textContent = `KES ${stats.earnings_this_month.toLocaleString()} this month`;
    document.getElementById('availableBalance').textContent = `KES ${stats.available_balance.toLocaleString()}`;
    document.getElementById('rank').textContent = stats.rank;
    
    // Update referral code and generate link
    const referralCode = user.referral_code;
    document.getElementById('referralCodeDisplay').textContent = referralCode;
    
    // Generate the referral link that auto-fills in registration
    const baseUrl = window.location.origin + '/marketSystem/frontend/register.html';
    const referralLink = `${baseUrl}?ref=${referralCode}`;
    document.getElementById('referralLink').value = referralLink;
    
    // Update recent referrals
    updateRecentReferrals(referrals);
}

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
        <div style="grid-column: 1/-1; text-align: center; padding: 50px;">
            <i class="fas fa-exclamation-circle" style="font-size: 48px; color: #ef4444;"></i>
            <p style="margin-top: 20px;">${message}</p>
            <button onclick="location.reload()" style="margin-top: 20px; padding: 10px 20px; background: #2563eb; color: white; border: none; border-radius: 5px; cursor: pointer;">Retry</button>
        </div>
    `;
}

// Toggle referral link visibility
function toggleReferralLink() {
    const linkBox = document.getElementById('referralLinkBox');
    if (linkBox.style.display === 'none' || linkBox.style.display === '') {
        linkBox.style.display = 'block';
    } else {
        linkBox.style.display = 'none';
    }
}

// Copy referral link to clipboard
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

// Social share functions - share the referral link
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

// Withdrawal method selection
function selectMethod(method) {
    document.querySelectorAll('.method-card').forEach(card => {
        card.classList.remove('selected');
    });
    event.currentTarget.classList.add('selected');
}

// Process withdrawal
function processWithdrawal() {
    const amount = document.getElementById('withdrawAmount').value;
    const balanceEl = document.getElementById('availableBalance');
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
    alert('View all referrals feature coming soon!');
}

// Logout
document.getElementById('logoutBtn').addEventListener('click', function() {
    if (confirm('Are you sure you want to logout?')) {
        sessionStorage.removeItem('user');
        window.location.href = '../index.html';
    }
});
