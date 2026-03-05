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

// Load dashboard data
async function loadDashboardData() {
    try {
        console.log('Loading dashboard data for user ID:', userData.id);
        
        const response = await fetch(`${API_BASE_URL}/dashboard.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ user_id: userData.id })
        });

        const data = await response.json();
        console.log('Dashboard data received:', data);

        if (data.success) {
            updateDashboardUI(data);
            // Also update welcome banner with user data
            updateWelcomeBanner(data.user.name);
        } else {
            showError(data.message || 'Failed to load dashboard data');
        }
    } catch (error) {
        console.error('Error loading dashboard:', error);
        showError('Connection error! Please check your internet and try again.');
    }
}

// Update welcome banner
function updateWelcomeBanner(userName) {
    const hour = new Date().getHours();
    let greeting = 'Good evening';
    if (hour < 12) greeting = 'Good morning';
    else if (hour < 17) greeting = 'Good afternoon';
    
    const firstName = userName.split(' ')[0];
    document.getElementById('welcomeMessage').textContent = `${greeting}, ${firstName}! 🚀`;
}

// Update dashboard UI
function updateDashboardUI(data) {
    const user = data.user;
    const stats = data.stats;
    const referrals = data.recent_referrals || [];

    // Update stats
    document.getElementById('totalReferrals').textContent = stats.total_referrals || 0;
    document.getElementById('monthlyReferrals').textContent = `${stats.referrals_this_month || 0} this month`;
    document.getElementById('totalEarnings').textContent = `KES ${(stats.total_earnings || 0).toLocaleString()}`;
    document.getElementById('monthlyEarnings').textContent = `KES ${(stats.earnings_this_month || 0).toLocaleString()} this month`;
    document.getElementById('availableBalance').textContent = `KES ${(stats.available_balance || 0).toLocaleString()}`;
    
    // Update rank with color
    const rankElement = document.getElementById('rank');
    rankElement.textContent = stats.rank || 'Starter';
    if (stats.rank_color) {
        rankElement.style.color = stats.rank_color;
    }

    // Update referral code
    document.getElementById('referralCodeDisplay').textContent = user.referral_code || 'REFXXXXXX';
    const baseUrl = 'https://jeyfolix.github.io/marketsystem/frontend/register.html';
    const referralLink = `${baseUrl}?ref=${user.referral_code || ''}`;
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
            const initials = ref.name ? ref.name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase() : '??';
            
            return `
                <div class="activity-item">
                    <div class="activity-avatar">${initials}</div>
                    <div>
                        <div><strong>${ref.name || 'Unknown'}</strong></div>
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
        <div style="text-align: center; padding: 50px; background: white; border-radius: 20px; margin: 20px;">
            <i class="fas fa-exclamation-circle" style="font-size: 48px; color: #dc2626;"></i>
            <p style="margin-top: 20px; color: #333;">${message}</p>
            <button onclick="location.reload()" style="margin-top: 20px; padding: 12px 30px; background: #2563eb; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 16px;">Retry</button>
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
    } else {
        // Show placeholder for other sections
        mainContent.innerHTML = `
            <div style="text-align: center; padding: 100px; background: white; border-radius: 20px;">
                <i class="fas fa-tools" style="font-size: 48px; color: #2563eb;"></i>
                <h3 style="margin-top: 20px;">${section.charAt(0).toUpperCase() + section.slice(1)} section coming soon!</h3>
            </div>
        `;
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
    
    // Load dashboard data
    loadDashboardData();
}

// View all referrals
window.viewAllReferrals = function() {
    alert('View all referrals feature coming soon!');
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

// Logout
document.getElementById('logoutBtn').addEventListener('click', function() {
    if (confirm('Are you sure you want to logout?')) {
        sessionStorage.removeItem('user');
        window.location.href = '../index.html';
    }
});

// Show dashboard by default
document.addEventListener('DOMContentLoaded', showDashboard);
