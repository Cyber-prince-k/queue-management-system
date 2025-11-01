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
        showToast('error', 'Please enter both username and password');
        return { success: false, message: 'Missing credentials' };
    }

    try {
        showToast('info', 'Logging in...');
        
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
            
            showToast('success', `Welcome back, ${data.user.full_name}!`);
            
            // Redirect after short delay to show success message
            setTimeout(() => {
                redirectToDashboard(data.user.role);
            }, 1000);
        } else {
            showToast('error', data.message || 'Login failed. Please check your credentials.');
        }
        
        return data;
    } catch (error) {
        console.error('Login error:', error);
        
        if (error.message.includes('Failed to fetch')) {
            showToast('error', 'Cannot connect to server. Please ensure XAMPP is running.');
        } else if (error.message.includes('HTTP error')) {
            showToast('error', 'Server error. Please try again later.');
        } else {
            showToast('error', 'Login failed. Please try again.');
        }
        
        return { success: false, message: error.message };
    }
}

// Register function
async function register(username, password, fullName, role) {
    // Validate inputs
    if (!username || !password || !fullName || !role) {
        showToast('error', 'Please fill in all required fields');
        return { success: false, message: 'Missing required fields' };
    }

    // Validate username length
    if (username.length < 3) {
        showToast('error', 'Username must be at least 3 characters long');
        return { success: false, message: 'Username too short' };
    }

    // Validate password length
    if (password.length < 6) {
        showToast('error', 'Password must be at least 6 characters long');
        return { success: false, message: 'Password too short' };
    }

    try {
        showToast('info', 'Creating account...');
        
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
            showToast('success', '✓ Account created successfully! Redirecting to login...');
            
            // Redirect to login page after 2 seconds
            setTimeout(() => {
                window.location.href = 'login.html';
            }, 2000);
        } else {
            showToast('error', data.message || 'Registration failed. Please try again.');
        }
        
        return data;
    } catch (error) {
        console.error('Registration error:', error);
        
        if (error.message.includes('Failed to fetch')) {
            showToast('error', 'Cannot connect to server. Please ensure XAMPP is running.');
        } else if (error.message.includes('HTTP error')) {
            showToast('error', 'Server error. Please try again later.');
        } else {
            showToast('error', 'Registration failed. Please try again.');
        }
        
        return { success: false, message: error.message };
    }
}

// Logout function - works across all tabs/windows
async function logout() {
    try {
        showToast('info', 'Logging out...');
        
        const response = await fetch(`${API_BASE_URL}/auth.php?action=logout`, {
            method: 'POST',
            credentials: 'include'
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success) {
            // Clear session and broadcast logout to all tabs
            performLogout('Logged out successfully');
        } else {
            showToast('error', 'Logout failed. Please try again.');
        }
    } catch (error) {
        console.error('Logout error:', error);
        
        // Even if server request fails, clear local data
        performLogout('Logged out (offline)');
    }
}

// Perform logout and broadcast to all tabs/windows
function performLogout(message) {
    // Clear session data
    localStorage.removeItem('session_user');
    
    // Broadcast logout event to all tabs using localStorage event
    // This triggers storage event in other tabs
    localStorage.setItem('logout_event', Date.now().toString());
    localStorage.removeItem('logout_event');
    
    showToast('success', message);
    
    // Redirect to home page
    setTimeout(() => {
        const currentPath = window.location.pathname;
        if (currentPath.includes('/html/')) {
            window.location.href = '../index.html';
        } else {
            window.location.href = 'index.html';
        }
    }, 500);
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
    console.log('requireRole called with:', allowedRoles);
    const user = getCurrentUser();
    console.log('getCurrentUser returned:', user);
    
    if (!user) {
        console.error('No user found in session, redirecting to login');
        // Inform the user before redirecting
        if (typeof showToast === 'function') {
            showToast('error', 'Please login to access this page.');
        }
        setTimeout(() => {
            window.location.href = '../html/login.html';
        }, 1200);
        return false;
    }
    
    console.log('User role:', user.role);
    console.log('Allowed roles:', allowedRoles);
    
    if (!allowedRoles.includes(user.role)) {
        console.error('User role not in allowed roles');
        showToast('error', 'Access denied. Insufficient permissions.');
        setTimeout(() => {
            redirectToDashboard(user.role);
        }, 1500);
        return false;
    }
    
    console.log('Access granted!');
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

// Toast notification function (matches style.js signature)
function showToast(type, message, duration = 4500) {
    // Create toast container if it doesn't exist
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }
    
    // Create toast element
    const toast = document.createElement('div');
    toast.className = `toast ${type || 'info'}`;
    toast.textContent = message;
    
    container.appendChild(toast);
    
    // Remove toast with animation
    setTimeout(() => {
        toast.style.animation = 'fadeOut 300ms ease forwards';
        setTimeout(() => toast.remove(), 300);
    }, duration);
}

// Listen for logout events from other tabs/windows
window.addEventListener('storage', (event) => {
    // Detect logout event from another tab
    if (event.key === 'logout_event') {
        console.log('Logout detected from another tab');
        showToast('info', 'You have been logged out');
        
        // Redirect to home page after short delay
        setTimeout(() => {
            const currentPath = window.location.pathname;
            if (currentPath.includes('/html/')) {
                window.location.href = '../index.html';
            } else {
                window.location.href = 'index.html';
            }
        }, 1000);
    }
    
    // Also detect if session_user was removed
    if (event.key === 'session_user' && event.newValue === null) {
        console.log('Session cleared from another tab');
        const currentPage = window.location.pathname.split('/').pop();
        
        // Only redirect if not on public pages
        const publicPages = ['index.html', 'login.html', 'register.html'];
        if (!publicPages.includes(currentPage)) {
            showToast('info', 'Session ended');
            setTimeout(() => {
                const currentPath = window.location.pathname;
                if (currentPath.includes('/html/')) {
                    window.location.href = '../index.html';
                } else {
                    window.location.href = 'index.html';
                }
            }, 1000);
        }
    }
});

// Initialize on page load
document.addEventListener('DOMContentLoaded', async () => {
    console.log('=== AUTH.JS DOMContentLoaded ===');
    const currentPage = window.location.pathname.split('/').pop();
    console.log('Current page:', currentPage);
    
    // Check session and store in localStorage
    const user = await checkSession();
    console.log('Session check result:', user);
    
    if (user) {
        // Store user in localStorage for requireRole() to access
        localStorage.setItem('session_user', JSON.stringify(user));
        console.log('✓ User stored in localStorage');
        
        // Note: User info display is handled by style.js renderAuthNav()
        // displayUserInfo(); // Disabled to prevent duplicate user info
    } else {
        console.log('✗ No active session');
    }
});
