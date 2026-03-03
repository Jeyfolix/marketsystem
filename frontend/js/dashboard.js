// API Base URL
const API_BASE_URL = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1'
    ? 'http://localhost/marketSystem/backend/api'
    : 'https://marketsystem-api.onrender.com/api';

console.log('API Base URL:', API_BASE_URL);

// Initialize AOS
AOS.init({
    duration: 800,
    once: true,
    offset: 50
});

// Get user data from session
const userData = JSON.parse(sessionStorage.getItem('user') || '{}');
console.log('User data from session:', userData);

if (!userData || !userData.id) {
    console.log('No user found, redirecting to login');
    window.location.href = 'index.html';
}

// Load dashboard data
async function loadDashboardData() {
    console.log('Loading dashboard data for user ID:', userData.id);
    
    try {
        const response = await fetch(`${API_BASE_URL}/dashboard.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ user_id: userData.id })
        });

        console.log('Response status:', response.status);
        
        const data = await response.json();
        console.log('Response data:', data);

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

// Rest of your functions remain the same...
// [Keep all your existing functions here]

// Load data when page loads
document.addEventListener('DOMContentLoaded', loadDashboardData);
