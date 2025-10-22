# Access Control System ✅

## Role-Based Access Control (RBAC)

The system now has **TWO LAYERS** of protection:

### Layer 1: `style.js` - `guardRoleProtectedPages()`
Runs automatically on every page load via `DOMContentLoaded` event.

### Layer 2: `auth.js` - `requireRole()`
Called explicitly in each page's inline script.

---

## Access Control Matrix

| Page | Patient | Staff | Admin | Public |
|------|---------|-------|-------|--------|
| **index.html** | ✅ | ✅ | ✅ | ✅ |
| **patient.html** | ✅ | ✅ | ✅ | ✅ |
| **staff.html** | ❌ | ✅ | ✅ | ❌ |
| **admin.html** | ❌ | ❌ | ✅ | ❌ |
| **profile.html** | ✅ | ✅ | ✅ | ❌ |
| **login.html** | ✅ | ✅ | ✅ | ✅ |
| **register.html** | ✅ | ✅ | ✅ | ✅ |
| **display.html** | ✅ | ✅ | ✅ | ✅ |
| **queues.html** | ✅ | ✅ | ✅ | ✅ |

---

## Detailed Access Rules

### 🏥 **patient.html** (Public)
- **Who can access**: Everyone (no login required)
- **Purpose**: Patient registration and queue joining
- **Protection**: None
- **Reason**: Patients need to register without having an account

### 👨‍⚕️ **staff.html** (Staff + Admin)
- **Who can access**: Staff OR Admin (must be logged in)
- **Protection**: 
  - `guardRoleProtectedPages()` checks for `['staff', 'admin']`
  - `requireRole(['staff', 'admin'])` in inline script
- **What happens if patient tries to access**:
  1. System detects patient role
  2. Shows: "Access denied. You do not have permission to access this page."
  3. Redirects to `patient.html` after 1.5 seconds

### 🔐 **admin.html** (Admin Only)
- **Who can access**: Admin ONLY (must be logged in)
- **Protection**:
  - `guardRoleProtectedPages()` checks for `['admin']`
  - `requireRole(['admin'])` in inline script
- **What happens if staff/patient tries to access**:
  1. System detects non-admin role
  2. Shows: "Access denied. You do not have permission to access this page."
  3. Redirects to their dashboard:
     - Staff → `staff.html`
     - Patient → `patient.html`

### 👤 **profile.html** (Any Logged-In User)
- **Who can access**: Any logged-in user (patient, staff, or admin)
- **Protection**:
  - `guardRoleProtectedPages()` checks if user is logged in
  - `requireRole(['patient', 'staff', 'admin'])` in inline script
- **What happens if not logged in**:
  1. Shows: "Please login to access this page."
  2. Redirects to `login.html`

---

## How It Works

### Example 1: Patient tries to access staff.html
```
1. Patient logs in with role: "patient"
2. Session stored: { role: "patient", username: "john_doe" }
3. Patient navigates to staff.html
4. guardRoleProtectedPages() runs:
   - Page: staff.html
   - Required roles: ['staff', 'admin']
   - User role: 'patient'
   - Result: NOT in allowed list ❌
5. System shows error: "Access denied..."
6. Redirects to patient.html (patient's dashboard)
```

### Example 2: Staff tries to access admin.html
```
1. Staff logs in with role: "staff"
2. Session stored: { role: "staff", username: "nurse_mary" }
3. Staff navigates to admin.html
4. guardRoleProtectedPages() runs:
   - Page: admin.html
   - Required roles: ['admin']
   - User role: 'staff'
   - Result: NOT in allowed list ❌
5. System shows error: "Access denied..."
6. Redirects to staff.html (staff's dashboard)
```

### Example 3: Admin accesses any page
```
1. Admin logs in with role: "admin"
2. Session stored: { role: "admin", username: "dr_smith" }
3. Admin can navigate to:
   ✅ admin.html (admin only)
   ✅ staff.html (staff or admin)
   ✅ patient.html (public)
   ✅ profile.html (any logged-in user)
4. All access granted!
```

---

## Code Implementation

### In `style.js` (lines 94-124):
```javascript
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
```

### In each page's inline script:
```javascript
// staff.html
requireRole(['staff', 'admin']);

// admin.html
requireRole(['admin']);

// profile.html
requireRole(['patient', 'staff', 'admin']);
```

---

## Security Features

✅ **Double Protection**: Both automatic (guardRoleProtectedPages) and manual (requireRole)
✅ **Role Validation**: Checks user role against allowed roles
✅ **Session Verification**: Ensures user is logged in
✅ **Automatic Redirect**: Sends users to appropriate dashboard if access denied
✅ **User Feedback**: Shows clear error messages
✅ **Public Access**: Patient registration doesn't require login

---

## Testing Access Control

### Test 1: Patient Access
1. Login as patient
2. Try to access `staff.html` → Should be blocked ❌
3. Try to access `admin.html` → Should be blocked ❌
4. Try to access `patient.html` → Should work ✅
5. Try to access `profile.html` → Should work ✅

### Test 2: Staff Access
1. Login as staff
2. Try to access `admin.html` → Should be blocked ❌
3. Try to access `staff.html` → Should work ✅
4. Try to access `patient.html` → Should work ✅
5. Try to access `profile.html` → Should work ✅

### Test 3: Admin Access
1. Login as admin
2. Try to access `admin.html` → Should work ✅
3. Try to access `staff.html` → Should work ✅
4. Try to access `patient.html` → Should work ✅
5. Try to access `profile.html` → Should work ✅

---

## Summary

✅ **Patient** → Can only access patient.html and profile.html
✅ **Staff** → Can access staff.html, patient.html, and profile.html
✅ **Admin** → Can access ALL pages (admin, staff, patient, profile)
✅ **Public** → Can access patient.html without login

**Access control is properly enforced!** 🔒
