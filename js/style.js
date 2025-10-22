// Digital Queue Management System JavaScript

// ===== Path Resolution Helper =====
// Resolves page paths based on current location (root vs html/ folder)
function resolvePath(page) {
    const currentPath = location.pathname;
    const isInHtmlFolder = currentPath.includes('/html/');
    
    // Special cases: index.html always at root
    if (page === 'index.html') {
        return isInHtmlFolder ? '../index.html' : 'index.html';
    }
    
    // All other pages are in html/ folder
    if (isInHtmlFolder) {
        return page; // Already in html/, use relative path
    } else {
        return `html/${page}`; // From root, prefix with html/
    }
}

// ===== Multilingual (i18n) Support =====
const translations = {
    en: {
        hospital_name: 'Queen Elizabeth Central Hospital',
        system_name: 'Digital Queue Management System',
        nav_home: 'Home',
        nav_patient: 'Patient',
        nav_staff: 'Staff',
        nav_admin: 'Admin',
        nav_display: 'Public Display',
        nav_queues: 'Queues',
        system_access_title: 'System Access',
        patient_access: 'Patient Access',
        patient_access_desc: 'Register or check your queue status',
        staff_access: 'Staff Access',
        staff_access_desc: 'Manage queues and patient flow',
        admin_access: 'Admin Access',
        admin_access_desc: 'System administration and reports',
        public_display_title: 'Current Queue Status',
        dept_opd: 'Outpatient Department (OPD)',
        dept_maternity: 'Maternity',
        dept_emergency: 'Emergency',
        dept_pediatrics: 'Pediatrics',
        footer_line1: '© 2023 Queen Elizabeth Central Hospital - Digital Queue Management System',
        footer_line2: 'Developed as part of NACIT Advanced Diploma in Computing Project',
    },
    ny: {
        hospital_name: 'Chipatala cha Mfumukazi Elizabeth',
        system_name: 'Dongosolo Laukadaulo la Mzere',
        nav_home: 'Poyambira',
        nav_patient: 'Odwala',
        nav_staff: 'Ogwira Ntchito',
        nav_admin: 'Oyendetsa',
        nav_display: 'Chiwonetsero Pagulu',
        nav_queues: 'Mizere',
        system_access_title: 'Kulowa mu Dongosolo',
        patient_access: 'Khomo la Odwala',
        patient_access_desc: 'Lembani kapena onani momwe muli pa mzere',
        staff_access: 'Khomo la Ogwira Ntchito',
        staff_access_desc: 'Samalirani mizere ndi mayendedwe a odwala',
        admin_access: 'Khomo la Oyendetsa',
        admin_access_desc: 'Kasamalidwe ka dongosolo ndi malipoti',
        public_display_title: 'Zomwe zikuchitika pa Mizere',
        dept_opd: 'Chipatala cha OPD',
        dept_maternity: 'Maternity',
        dept_emergency: 'Emergency',
        dept_pediatrics: 'Pediatrics',
        footer_line1: '© 2023 Chipatala cha Mfumukazi Elizabeth - Dongosolo Laukadaulo la Mzere',
        footer_line2: 'Zapangidwa monga gawo la polojekiti ya NACIT Advanced Diploma in Computing',
    }
};

// ===== Auth: session, forms, guards, nav =====
function setSessionUser(user) {
    localStorage.setItem('session_user', JSON.stringify(user || null));
}
function getSessionUser() {
    try {
        const raw = localStorage.getItem('session_user');
        return raw ? JSON.parse(raw) : null;
    } catch {
        return null;
    }
}
function clearSession() { localStorage.removeItem('session_user'); }

function roleToDashboard(role) {
    if (role === 'admin') return resolvePath('admin.html');
    if (role === 'staff') return resolvePath('staff.html');
    return resolvePath('patient.html');
}

function guardRoleProtectedPages() {
    const page = location.pathname.split('/').pop() || 'index.html';
    
    // Define which roles can access which pages
    const pageRoles = {
        'admin.html': ['admin'],                      // Only admin
        'staff.html': ['staff', 'admin'],             // Staff or admin
        'profile.html': ['patient', 'staff', 'admin'] // Any logged-in user
        // patient.html is public - no restriction
    };
    
    if (pageRoles[page]) {
        const user = getSessionUser();
        
        // Check if user is logged in
        if (!user) {
            showToast('error', 'Please login to access this page.');
            location.href = resolvePath('login.html');
            return;
        }
        
        // Check if user has the required role
        if (!pageRoles[page].includes(user.role)) {
            showToast('error', 'Access denied. You do not have permission to access this page.');
            // Redirect to their appropriate dashboard
            setTimeout(() => {
                location.href = roleToDashboard(user.role);
            }, 1500);
        }
    }
}

function renderAuthNav() {
    const navList = document.querySelector('header nav ul');
    if (!navList) return;
    let slot = document.getElementById('auth-slot');
    if (!slot) {
        slot = document.createElement('li');
        slot.id = 'auth-slot';
        navList.appendChild(slot);
    }
    const user = getSessionUser();
    if (user) {
        const dashboard = roleToDashboard(user.role);
        slot.innerHTML = `
            <a class="btn btn-secondary" href="${dashboard}">Dashboard</a>
            <a class="btn btn-secondary" href="${resolvePath('profile.html')}" style="margin-left:.5rem;">Profile</a>
            <span style="color:#fff;margin-left:.5rem;">${user.name || user.username} (${user.role})</span>
            <button type="button" class="btn btn-warning" id="logout-btn" style="margin-left:.5rem;">Logout</button>
        `;
        const btn = slot.querySelector('#logout-btn');
        btn.addEventListener('click', () => {
            clearSession();
            showToast('info', 'Logged out');
            setTimeout(() => location.href = resolvePath('index.html'), 300);
        });
    } else {
        slot.innerHTML = `
            <a class="btn btn-secondary" href="${resolvePath('register.html')}">Create Account</a>
            <a class="btn btn-primary" href="${resolvePath('login.html')}" style="margin-left:.5rem;">Login</a>
        `;
    }
}

function initAuthFlows() {
    const page = location.pathname.split('/').pop() || 'index.html';
    const existing = getSessionUser();
    if (page === 'login.html' && existing) {
        location.href = roleToDashboard(existing.role);
        return;
    }
    // Login form
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const username = document.getElementById('login-username').value.trim();
            const password = document.getElementById('login-password').value;
            try {
                if (!USE_API) await initApiMode();
                const res = await fetch(`${API_BASE}/auth/login`, {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ username, password })
                });
                if (!res.ok) throw new Error('Login failed');
                const data = await res.json();
                setSessionUser(data.user);
                showToast('success', `Welcome ${data.user.name || data.user.username}!`);
                setTimeout(() => { location.href = roleToDashboard(data.user.role); }, 500);
            } catch (err) {
                showToast('error', 'Invalid username or password');
            }
        });
    }
    // Register form
    const registerForm = document.getElementById('register-form');
    if (registerForm) {
        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const username = document.getElementById('reg-username').value.trim();
            const name = document.getElementById('reg-name').value.trim();
            const password = document.getElementById('reg-password').value;
            const confirmPwd = document.getElementById('reg-confirm').value;
            const role = document.getElementById('reg-role').value;
            if (!username || !password || !role) { showToast('error', 'Please fill all required fields'); return; }
            if (password !== confirmPwd) { showToast('error', 'Passwords do not match'); return; }
            try {
                if (!USE_API) await initApiMode();
                const res = await fetch(`${API_BASE}/auth/register`, {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ username, password, role, name })
                });
                if (!res.ok) {
                    const errText = await res.text();
                    throw new Error(errText || 'Registration failed');
                }
                const data = await res.json();
                setSessionUser(data.user);
                showToast('success', 'Account created successfully');
                setTimeout(() => { location.href = roleToDashboard(data.user.role); }, 600);
            } catch (err) {
                showToast('error', 'Registration failed. Try a different username.');
            }
        });
    }

    // Profile page handlers
    const pageProfile = (page === 'profile.html');
    if (pageProfile) {
        const user = getSessionUser();
        if (user) {
            // Populate current profile
            const nameEl = document.getElementById('prof-name');
            const usernameEl = document.getElementById('prof-username');
            if (usernameEl) usernameEl.value = user.username;
            
            // Async IIFE to handle await
            (async () => {
                try {
                    if (!USE_API) await initApiMode();
                    const res = await fetch(`${API_BASE}/auth/me?username=${encodeURIComponent(user.username)}`);
                    if (res.ok) {
                        const data = await res.json();
                        if (nameEl) nameEl.value = data.name || '';
                    }
                } catch {}
            })();
        }
        const form = document.getElementById('profile-form');
        if (form) {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const username = document.getElementById('prof-username').value;
                const name = document.getElementById('prof-name').value;
                const currentPassword = document.getElementById('prof-current').value;
                const newPassword = document.getElementById('prof-new').value;
                try {
                    if (!USE_API) await initApiMode();
                    const res = await fetch(`${API_BASE}/auth/update`, {
                        method: 'POST', headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ username, name, currentPassword, newPassword })
                    });
                    if (!res.ok) throw new Error('Update failed');
                    const data = await res.json();
                    setSessionUser(data.user);
                    showToast('success', 'Profile updated');
                    renderAuthNav();
                    (document.getElementById('prof-current').value = ''), (document.getElementById('prof-new').value = '');
                } catch (err) {
                    showToast('error', 'Failed to update profile');
                }
            });
        }
    }
}

function applyTranslations() {
    const lang = localStorage.getItem('app_lang') || 'en';
    const dict = translations[lang] || translations.en;
    document.querySelectorAll('[data-i18n]').forEach(el => {
        const key = el.getAttribute('data-i18n');
        if (dict[key]) {
            el.textContent = dict[key];
        }
    });
    const languageSelect = document.getElementById('language-select');
    if (languageSelect && languageSelect.value !== lang) languageSelect.value = lang;
}

function setLanguage(lang) {
    if (!translations[lang]) return;
    localStorage.setItem('app_lang', lang);
    applyTranslations();
}

document.addEventListener('DOMContentLoaded', async () => {
    // Initialize language
    const saved = localStorage.getItem('app_lang') || 'en';
    localStorage.setItem('app_lang', saved);
    const languageSelect = document.getElementById('language-select');
    if (languageSelect) {
        languageSelect.value = saved;
        languageSelect.addEventListener('change', (e) => setLanguage(e.target.value));
    }
    applyTranslations();

    // Highlight active nav link
    const navLinks = document.querySelectorAll('header nav a[href]');
    const here = location.pathname.split('/').pop() || 'index.html';
    navLinks.forEach(a => {
        const href = a.getAttribute('href');
        if (href === here) {
            a.classList.add('active');
            a.setAttribute('aria-current', 'page');
        }
    });

    // REMOVED: Mock data initialization
    // Each page now handles its own data loading using real API from queue.js
    // Patient page: Uses createQueueToken() and getQueueStatus()
    // Staff page: Uses refreshQueueDisplay() with auto-refresh
    // Admin page: Should implement real database statistics queries

    // Initialize auth flows and guards, and render auth-aware nav
    initAuthFlows();
    guardRoleProtectedPages();
    renderAuthNav();

    // Initialize toast container and confirm modal
    initUIEnhancements();
});

// ===== UI Enhancements: Toasts and Confirm Modal =====
let toastContainerEl = null;
let confirmBackdropEl = null;
function initUIEnhancements() {
    // Toast container
    toastContainerEl = document.createElement('div');
    toastContainerEl.className = 'toast-container';
    document.body.appendChild(toastContainerEl);

    // Confirm modal
    confirmBackdropEl = document.createElement('div');
    confirmBackdropEl.className = 'modal-backdrop';
    confirmBackdropEl.innerHTML = `
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="confirm-title">
            <header id="confirm-title">Confirm Action</header>
            <p id="confirm-message">Are you sure?</p>
            <div class="actions">
                <button type="button" class="btn btn-secondary" data-action="cancel">Cancel</button>
                <button type="button" class="btn btn-danger" data-action="confirm">Confirm</button>
            </div>
        </div>`;
    document.body.appendChild(confirmBackdropEl);
}

function showToast(type, message, duration = 4500) {
    if (!toastContainerEl) return alert(message);
    const el = document.createElement('div');
    el.className = `toast ${type || 'info'}`;
    el.textContent = message;
    toastContainerEl.appendChild(el);
    setTimeout(() => {
        el.addEventListener('animationend', () => el.remove(), { once: true });
        el.style.animationPlayState = 'running';
    }, Math.max(500, duration - 300));
    // Fallback removal after duration + buffer
    setTimeout(() => { if (el.parentNode) el.remove(); }, duration + 1500);
}

function showConfirm(message, { confirmText = 'Confirm', cancelText = 'Cancel' } = {}) {
    if (!confirmBackdropEl) return Promise.resolve(confirm(message));
    return new Promise(resolve => {
        confirmBackdropEl.classList.add('show');
        const msg = confirmBackdropEl.querySelector('#confirm-message');
        const btnCancel = confirmBackdropEl.querySelector('[data-action="cancel"]');
        const btnConfirm = confirmBackdropEl.querySelector('[data-action="confirm"]');
        msg.textContent = message;
        btnConfirm.textContent = confirmText;
        btnCancel.textContent = cancelText;
        const cleanup = (result) => {
            confirmBackdropEl.classList.remove('show');
            btnCancel.removeEventListener('click', onCancel);
            btnConfirm.removeEventListener('click', onConfirm);
            confirmBackdropEl.removeEventListener('click', onBackdrop);
            resolve(result);
        };
        const onCancel = () => cleanup(false);
        const onConfirm = () => cleanup(true);
        const onBackdrop = (e) => { if (e.target === confirmBackdropEl) cleanup(false); };
        btnCancel.addEventListener('click', onCancel);
        btnConfirm.addEventListener('click', onConfirm);
        confirmBackdropEl.addEventListener('click', onBackdrop);
    });
}

// ===== Backend API integration =====
let USE_API = false;
let API_BASE = localStorage.getItem('api_base') || 'http://localhost:3000/api';

async function initApiMode() {
    try {
        const res = await fetch(`${API_BASE}/health`, { cache: 'no-store' });
        const data = await res.json();
        USE_API = data && data.status === 'ok';
        return USE_API;
    } catch (e) {
        USE_API = false;
        return false;
    }
}

async function apiRegisterPatient(payload) {
    const res = await fetch(`${API_BASE}/patients/register`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    });
    if (!res.ok) throw new Error('Registration failed');
    return res.json();
}

async function apiGetStatus(token) {
    const res = await fetch(`${API_BASE}/status/${encodeURIComponent(token)}`);
    if (!res.ok) throw new Error('Not found');
    return res.json();
}

async function apiGetPublic() {
    const res = await fetch(`${API_BASE}/public`, { cache: 'no-store' });
    if (!res.ok) throw new Error('Failed to load public');
    return res.json();
}

async function apiCallNext(department) {
    const res = await fetch(`${API_BASE}/staff/call-next`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ department })
    });
    if (!res.ok) throw new Error('No patients or invalid department');
    return res.json();
}

async function apiResetQueues() {
    const res = await fetch(`${API_BASE}/admin/reset`, { method: 'POST' });
    if (!res.ok) throw new Error('Reset failed');
    return res.json();
}

async function apiGetStats() {
    const res = await fetch(`${API_BASE}/admin/stats`, { cache: 'no-store' });
    if (!res.ok) throw new Error('Stats failed');
    return res.json();
}

async function apiGetQueues(department) {
    const url = department ? `${API_BASE}/queues?department=${encodeURIComponent(department)}` : `${API_BASE}/queues`;
    const res = await fetch(url, { cache: 'no-store' });
    if (!res.ok) throw new Error('Queues failed');
    return res.json();
}

// REMOVED: Mock data - System now uses REAL database data only
// All data comes from the PHP API backend

// Note: The queue.js file handles all queue operations with the real database
// This file (style.js) only handles UI/UX and authentication

// DOM elements (may be null on pages that don't include these sections)
const loginSection = document.getElementById('login-section');
const patientSection = document.getElementById('patient-section');
const staffSection = document.getElementById('staff-section');
const adminSection = document.getElementById('admin-section');

// Login buttons
const patientLoginBtn = document.getElementById('patient-login-btn');
if (patientLoginBtn && patientSection) {
    patientLoginBtn.addEventListener('click', () => {
        if (loginSection && patientSection) showSection(patientSection);
    });
}

const staffLoginBtn = document.getElementById('staff-login-btn');
if (staffLoginBtn && staffSection) {
    staffLoginBtn.addEventListener('click', () => {
        if (loginSection && staffSection) showSection(staffSection);
        loadQueueForStaff();
    });
}

const adminLoginBtn = document.getElementById('admin-login-btn');
if (adminLoginBtn && adminSection) {
    adminLoginBtn.addEventListener('click', () => {
        if (loginSection && adminSection) showSection(adminSection);
        loadAdminStats();
    });
}

// Back buttons
const patientBackBtn = document.getElementById('patient-back-btn');
if (patientBackBtn && loginSection) {
    patientBackBtn.addEventListener('click', () => {
        showSection(loginSection);
    });
}

const staffBackBtn = document.getElementById('staff-back-btn');
if (staffBackBtn && loginSection) {
    staffBackBtn.addEventListener('click', () => {
        showSection(loginSection);
    });
}

const adminBackBtn = document.getElementById('admin-back-btn');
if (adminBackBtn && loginSection) {
    adminBackBtn.addEventListener('click', () => {
        showSection(loginSection);
    });
}

// Tab functionality
const tabBtns = document.querySelectorAll('.tab-btn');
tabBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        const tabId = btn.getAttribute('data-tab');
        
        // Remove active class from all tabs and contents
        document.querySelectorAll('.tab-btn').forEach(tb => tb.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(tc => tc.classList.remove('active'));
        
        // Add active class to current tab and content
        btn.classList.add('active');
        document.getElementById(tabId).classList.add('active');
    });
});

// Patient registration form - HANDLED BY patient.html inline script
// This uses the createQueueToken() function from queue.js which connects to real database
// DO NOT add duplicate handlers here

// Queue status form - HANDLED BY patient.html inline script
// This uses the getQueueStatus() function from queue.js which connects to real database
// DO NOT add duplicate handlers here

// Staff functionality - HANDLED BY staff.html inline script
// Uses real API functions from queue.js: callNextPatient(), pauseQueue(), resumeQueue()
// DO NOT add duplicate handlers here

// Admin functionality
const dailyReportBtn = document.getElementById('daily-report-btn');
if (dailyReportBtn) {
    dailyReportBtn.addEventListener('click', () => {
        generateReport('daily');
    });
}

const weeklyReportBtn = document.getElementById('weekly-report-btn');
if (weeklyReportBtn) {
    weeklyReportBtn.addEventListener('click', () => {
        generateReport('weekly');
    });
}

// Admin reset queues - HANDLED BY admin.html inline script
// Uses real API functions from queue.js
// DO NOT add duplicate handlers here

const backupDataBtn = document.getElementById('backup-data-btn');
if (backupDataBtn) {
    backupDataBtn.addEventListener('click', () => {
        showToast('info', 'Data backup initiated (simulation).');
    });
}

// Helper functions
function showSection(section) {
    // Hide all sections if they exist
    if (loginSection) loginSection.classList.add('hidden');
    if (patientSection) patientSection.classList.add('hidden');
    if (staffSection) staffSection.classList.add('hidden');
    if (adminSection) adminSection.classList.add('hidden');
    // Show selected section
    if (section) section.classList.remove('hidden');
}

// REMOVED: loadQueueForStaff() and callNextPatient()
// These functions are now in queue.js and use REAL database data
// The refreshQueueDisplay() function in queue.js handles staff queue display

// REMOVED: updatePublicDisplay(), updateQueuesOverview(), loadAdminStats()
// These functions used mock data. Now using real database data from queue.js
// The updatePublicDisplay() function in queue.js handles public display updates

// REMOVED: generateReport() - Used mock data
// Admin reports should query real database statistics

// Helper function for department names
function getDepartmentName(code) {
    const departments = {
        opd: 'Outpatient Department (OPD)',
        maternity: 'Maternity',
        emergency: 'Emergency',
        pediatrics: 'Pediatrics'
    };
    return departments[code] || code;
}

// REMOVED: simulateSMSNotification() - Not needed for basic system
// Real SMS integration would use Twilio or similar service

// REMOVED: updatePublicDisplay() initialization
// Public display now uses real-time data from queue.js