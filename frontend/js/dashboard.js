document.addEventListener('DOMContentLoaded', function() {
    const userData = sessionStorage.getItem('user');
    
    if (!userData) {
        window.location.href = 'index.html';
        return;
    }
    
    const user = JSON.parse(userData);
    
    document.getElementById('userName').textContent = user.name || user.username;
    
    displayUserInfo(user);
    
    document.getElementById('logoutBtn').addEventListener('click', handleLogout);
    
    updateLastLogin();
});

function displayUserInfo(user) {
    const userInfoDiv = document.getElementById('userInfo');
    
    const createdDate = user.created_at ? new Date(user.created_at).toLocaleDateString() : 'N/A';
    
    userInfoDiv.innerHTML = `
        <h3>Your Profile Information</h3>
        <div class="user-info-detail">
            <strong>Full Name:</strong> ${user.name || 'N/A'}
        </div>
        <div class="user-info-detail">
            <strong>Username:</strong> ${user.username}
        </div>
        <div class="user-info-detail">
            <strong>Email:</strong> ${user.email || 'N/A'}
        </div>
        <div class="user-info-detail">
            <strong>Member Since:</strong> ${createdDate}
        </div>
    `;
}

function updateLastLogin() {
    const lastLoginElement = document.getElementById('lastLogin');
    const now = new Date();
    const formattedTime = now.toLocaleString('en-US', { 
        hour: 'numeric', 
        minute: 'numeric', 
        hour12: true,
        month: 'short',
        day: 'numeric'
    });
    lastLoginElement.textContent = formattedTime;
}

function handleLogout() {
    sessionStorage.removeItem('user');
    window.location.href = 'index.html';
}
