# User Information Display - Implementation Guide

## ✅ User Info Display Across All Pages

The system now displays logged-in user information **on every page** in the header navigation.

---

## 📍 Where User Info Appears

### Location: **Header Navigation (Top Right)**

The user info is displayed in the navigation bar on:
- ✅ Home page (index.html)
- ✅ Login page (login.html)
- ✅ Registration page (register.html)
- ✅ Patient Portal (patient.html)
- ✅ Staff Portal (staff.html)
- ✅ Admin Portal (admin.html)
- ✅ Public Display (display.html)
- ✅ Queues page (queues.html)
- ✅ Profile page (profile.html)

---

## 🎨 Visual Display

### When User is Logged In:
```
┌─────────────────────────────────────────────────────────┐
│  QECH Logo  Queen Elizabeth Central Hospital           │
│             Digital Queue Management System             │
│                                                         │
│  [Home] [Patient] [Staff] [Admin]  👤 John Doe (Patient) [Logout] │
└─────────────────────────────────────────────────────────┘
```

### Components:
1. **User Icon** (👤) - SVG person icon
2. **User Name** - Full name in bold
3. **User Role** - Role in parentheses (Patient/Staff/Admin)
4. **Logout Button** - Red button to logout

---

## 🔧 Technical Implementation

### JavaScript Function: `displayUserInfo()`

**Location:** `js/auth.js`

**Features:**
- ✅ Automatically creates user-info element if not present
- ✅ Displays user icon, name, and role
- ✅ Shows logout button
- ✅ Updates on every page load
- ✅ Capitalizes role for better display
- ✅ Styled to match header design

### Code Flow:
```javascript
1. Page loads → DOMContentLoaded event fires
2. Check if user is logged in (checkSession())
3. If logged in → Get user from localStorage
4. Call displayUserInfo()
5. Create/update user-info element in navigation
6. Display: Icon + Name + Role + Logout button
```

---

## 🎯 User Experience

### Scenario 1: Not Logged In
- **Display:** Navigation shows only page links
- **Behavior:** No user info shown

### Scenario 2: Logged In as Patient
- **Display:** "👤 John Doe (Patient) [Logout]"
- **Behavior:** User can see their name and role everywhere

### Scenario 3: Logged In as Staff
- **Display:** "👤 Mary Smith (Staff) [Logout]"
- **Behavior:** User info persists across all pages

### Scenario 4: Logged In as Admin
- **Display:** "👤 Admin User (Admin) [Logout]"
- **Behavior:** Full access with user info visible

---

## 🔄 Session Persistence

### How It Works:
1. **Login** → User data stored in:
   - PHP Session (server-side)
   - localStorage (client-side)

2. **Navigate** → Every page:
   - Checks PHP session via API
   - Falls back to localStorage if server unreachable
   - Displays user info automatically

3. **Logout** → Clears:
   - PHP session
   - localStorage
   - Redirects to home

---

## 🎨 Styling Details

### User Info Container:
```css
- Display: flex
- Align items: center
- Gap: 0.5rem
- Margin-left: auto (pushes to right)
- Color: white
- Font-size: 0.9rem
```

### User Icon:
```css
- SVG icon (20x20px)
- White stroke
- Person silhouette design
```

### User Name:
```css
- Bold font weight
- White color
```

### Role Badge:
```css
- Small font size
- Opacity: 0.8
- In parentheses
- Capitalized (Patient, Staff, Admin)
```

### Logout Button:
```css
- Red background (danger color)
- White text
- Padding: 0.4rem 1rem
- Font-size: 0.85rem
- Border-radius: pill shape
```

---

## 📱 Responsive Behavior

### Desktop (1200px+):
- User info on right side of navigation
- All elements visible
- Icon + Name + Role + Button

### Tablet (768px):
- User info wraps to new line if needed
- All elements still visible

### Mobile (< 768px):
- User info may show abbreviated
- Icon + Name + Button (role hidden if space limited)

---

## 🔐 Security Features

### Session Validation:
- ✅ Checks server session on every page
- ✅ Validates user still exists in database
- ✅ Verifies role hasn't changed
- ✅ Auto-logout if session expired

### Data Protection:
- ✅ User data encrypted in session
- ✅ localStorage as fallback only
- ✅ No sensitive data exposed
- ✅ Logout clears all data

---

## 🐛 Troubleshooting

### Issue 1: User Info Not Showing
**Cause:** JavaScript not loaded or user not logged in
**Solution:**
- Check browser console for errors
- Verify auth.js is loaded
- Confirm user is logged in
- Check localStorage has 'user' key

### Issue 2: Wrong User Displayed
**Cause:** Cached data not updated
**Solution:**
- Clear browser cache
- Logout and login again
- Check session is active

### Issue 3: User Info Disappears
**Cause:** Session expired or logout occurred
**Solution:**
- Login again
- Check XAMPP MySQL is running
- Verify session timeout settings

---

## 📊 User Info Data Structure

### localStorage Format:
```json
{
  "id": 1,
  "username": "johndoe",
  "full_name": "John Doe",
  "role": "patient"
}
```

### Display Format:
```
Icon: 👤 (SVG person icon)
Name: John Doe (bold)
Role: (Patient) (small, capitalized)
Button: [Logout] (red)
```

---

## ✨ Features Summary

1. ✅ **Universal Display** - Shows on all pages
2. ✅ **Auto-Creation** - Creates element if missing
3. ✅ **Session Sync** - Always shows current user
4. ✅ **Visual Icon** - Person icon for clarity
5. ✅ **Role Display** - Shows user type clearly
6. ✅ **Quick Logout** - One-click logout button
7. ✅ **Responsive** - Works on all screen sizes
8. ✅ **Secure** - Validates session constantly

---

## 🎯 Testing Checklist

- [ ] Login as patient → See name and "Patient" role
- [ ] Navigate to different pages → User info persists
- [ ] Login as staff → See name and "Staff" role
- [ ] Login as admin → See name and "Admin" role
- [ ] Click logout → User info disappears
- [ ] Refresh page while logged in → User info reappears
- [ ] Open new tab → User info shows automatically
- [ ] Close browser and reopen → Session may expire (expected)

---

## 📝 Code Reference

### Main Function Location:
**File:** `js/auth.js`
**Function:** `displayUserInfo()`
**Lines:** 243-278

### Initialization:
**File:** `js/auth.js`
**Event:** `DOMContentLoaded`
**Lines:** 303-325

### All Pages Include:
```html
<script src="../js/auth.js"></script>
```

---

## 🎉 Result

Users now see their information **everywhere they navigate** in the system:
- ✅ Always know who is logged in
- ✅ See their role at a glance
- ✅ Quick access to logout
- ✅ Consistent experience across all pages
- ✅ Professional, modern UI
