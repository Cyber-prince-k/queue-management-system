// Authentication JavaScript for QECH Queue System
// Define API_BASE_URL only if not already defined
if (typeof API_BASE_URL === 'undefined') {
    var API_BASE_URL = 'http://localhost/queue%20system/php/api';
}

// Check if user is already logged in
async function checkSession() {
    try {
        const response = await fetch(`${API_BASE_URL}/auth.php?action=check`, {
            method: 'GET',
            credentials: 'include'
        });
        
        if (!response.ok) {
            console.error('Session check failed:', response.status);
            return null;
        }
        
        const data = await response.json();
        
        if (data.success && data.logged_in) {
            return data.user;
        }
        return null;
    } catch (error) {
        console.error('Session check error:', error);
        
        // If server is unreachable, check localStorage as fallback
        const userStr = localStorage.getItem('session_user');
        if (userStr) {
            console.warn('Using cached user data (server unreachable)');
            return JSON.parse(userStr);
        }
        
        return null;
    }
}

// Login function
async function login(username, password) {
    // Validate inputs
    if (!username || !password) {
        showToast('Please enter both username and password', 'error');
        return { success: false, message: 'Missing credentials' };
    }

    try {
        showToast('Logging in...', 'info');
        
        const response = await fetch(`${API_BASE_URL}/auth.php?action=login`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify({ username, password })
        });
        
        // Check if response is ok
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success) {
            // Store user info in localStorage (use 'session_user' to match style.js)
            localStorage.setItem('session_user', JSON.stringify(data.user));
            
            showToast(`Welcome back, ${data.user.full_name}!`, 'success');
            
            // Redirect after short delay to show success message
            setTimeout(() => {
                redirectToDashboard(data.user.role);
            }, 1000);
        } else {
            showToast(data.message || 'Login failed. Please check your credentials.', 'error');
        }
        
        return data;
    } catch (error) {
        console.error('Login error:', error);
        
        if (error.message.includes('Failed to fetch')) {
            showToast('Cannot connect to server. Please ensure XAMPP is running.', 'error');
        } else if (error.message.includes('HTTP error')) {
            showToast('Server error. Please try again later.', 'error');
        } else {
            showToast('Login failed. Please try again.', 'error');
        }
        
        return { success: false, message: error.message };
    }
}

// Register function
async function register(username, password, fullName, role) {
    // Validate inputs
    if (!username || !password || !fullName || !role) {
        showToast('Please fill in all required fields', 'error');
        return { success: false, message: 'Missing required fields' };
    }

    // Validate username length
    if (username.length < 3) {
        showToast('Username must be at least 3 characters long', 'error');
        return { success: false, message: 'Username too short' };
    }

    // Validate password length
    if (password.length < 6) {
        showToast('Password must be at least 6 characters long', 'error');
        return { success: false, message: 'Password too short' };
    }

    try {
        showToast('Creating account...', 'info');
        
        const response = await fetch(`${API_BASE_URL}/auth.php?action=register`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify({
                username,
                password,
                full_name: fullName,
                role
            })
        });
        
        // Check if response is ok
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success) {
            showToast('✓ Account created successfully! Redirecting to login...', 'success');
            
            // Redirect to login page after 2 seconds
            setTimeout(() => {
                window.location.href = 'login.html';
            }, 2000);
        } else {
            showToast(data.message || 'Registration failed. Please try again.', 'error');
        }
        
        return data;
    } catch (error) {
        console.error('Registration error:', error);
        
        if (error.message.includes('Failed to fetch')) {
            showToast('Cannot connect to server. Please ensure XAMPP is running.', 'error');
        } else if (error.message.includes('HTTP error')) {
            showToast('Server error. Please try again later.', 'error');
        } else {
            showToast('Registration failed. Please try again.', 'error');
        }
        
        return { success: false, message: error.message };
    }
}

// Logout function
async function logout() {
    try {
        showToast('Logging out...', 'info');
        
        const response = await fetch(`${API_BASE_URL}/auth.php?action=logout`, {
            method: 'POST',
            credentials: 'include'
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success) {
            localStorage.removeItem('session_user');
            showToast('Logged out successfully', 'success');
            
            setTimeout(() => {
                window.location.href = '../index.html';
            }, 500);
        } else {
            showToast('Logout failed. Please try again.', 'error');
        }
    } catch (error) {
        console.error('Logout error:', error);
        
        // Even if server request fails, clear local data
        localStorage.removeItem('session_user');
        showToast('Logged out (offline)', 'info');
        
        setTimeout(() => {
            window.location.href = '../index.html';
        }, 500);
    }
}

// Redirect to appropriate dashboard based on role
function redirectToDashboard(role) {
    const dashboards = {
        'patient': '../html/patient.html',
        'staff': '../html/staff.html',
        'admin': '../html/admin.html'
    };
    
    const dashboard = dashboards[role] || '../index.html';
    window.location.href = dashboard;
}

// Get current user from localStorage
function getCurrentUser() {
    // Use 'session_user' to match style.js
    const userStr = localStorage.getItem('session_user');
    return userStr ? JSON.parse(userStr) : null;
}

// Check if user has required role
function requireRole(allowedRoles) {
    const user = getCurrentUser();
    
    if (!user) {
        window.location.href = '../html/login.html';
        return false;
    }
    
    if (!allowedRoles.includes(user.role)) {
        showToast('Access denied. Insufficient permissions.', 'error');
        setTimeout(() => {
            redirectToDashboard(user.role);
        }, 1500);
        return false;
    }
    
    return true;
}

// Display user info in header
function displayUserInfo() {
    const user = getCurrentUser();
    
    if (user) {
        // Try to find user-info element
        let userInfoElement = document.getElementById('user-info');
        
        // If not found, create it in the navigation
        if (!userInfoElement) {
            const nav = document.querySelector('header nav ul');
            if (nav) {
                const li = document.createElement('li');
                li.id = 'user-info';
                li.style.cssText = 'display: flex; align-items: center; gap: 0.5rem; margin-left: auto;';
                nav.appendChild(li);
                userInfoElement = li;
            }
        }
        
        if (userInfoElement) {
            // Capitalize role for display
            const roleDisplay = user.role.charAt(0).toUpperCase() + user.role.slice(1);
            
            userInfoElement.innerHTML = `
                <span style="color: white; font-size: 0.9rem; display: flex; align-items: center; gap: 0.5rem;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    <span><strong>${user.full_name}</strong> <small style="opacity: 0.8;">(${roleDisplay})</small></span>
                </span>
                <button onclick="logout()" class="btn btn-danger" style="padding: 0.4rem 1rem; font-size: 0.85rem; margin-left: 0.5rem;">Logout</button>
            `;
        }
    }
}

// Toast notification function
function showToast(message, type = 'info') {
    // Create toast container if it doesn't exist
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }
    
    // Create toast element
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;
    
    container.appendChild(toast);
    
    // Remove toast after 4.5 seconds
    setTimeout(() => {
        toast.remove();
    }, 4500);
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', async () => {
    // Check session on protected pages
    const protectedPages = ['patient.html', 'staff.html', 'admin.html', 'profile.html', 'queues.html'];
    const currentPage = window.location.pathname.split('/').pop();
    
    if (protectedPages.includes(currentPage)) {
        const user = await checkSession();
        if (!user) {
            window.location.href = '../html/login.html';
            return;
        } else {
            localStorage.setItem('session_user', JSON.stringify(user));
        }
    }
    
    // Always display user info if logged in (on any page)
    const user = await checkSession();
    if (user) {
        localStorage.setItem('session_user', JSON.stringify(user));
        displayUserInfo();
    }
});
