# QECH Queue Management System - Complete Testing Guide

## Prerequisites Checklist
- [ ] XAMPP installed
- [ ] Apache running
- [ ] MySQL running
- [ ] Database imported
- [ ] Project in htdocs folder

## Test Scenarios

### 1. User Registration & Login

#### Test Case 1.1: Register New Patient
**Steps:**
1. Go to http://localhost/queue%20system/html/register.html
2. Fill in:
   - Full Name: "John Doe"
   - Username: "johndoe"
   - User Type: "Patient"
   - Password: "password123"
   - Confirm Password: "password123"
3. Click "Create Account"

**Expected Result:**
- ✓ Success message: "Account created successfully!"
- ✓ Redirect to login page after 2 seconds
- ✓ User inserted in database

#### Test Case 1.2: Login as Patient
**Steps:**
1. Go to login page
2. Enter username: "johndoe"
3. Enter password: "password123"
4. Click "Login"

**Expected Result:**
- ✓ Success message: "Welcome back, John Doe!"
- ✓ Redirect to patient.html
- ✓ User info displayed in header
- ✓ Logout button visible

#### Test Case 1.3: Login as Admin
**Steps:**
1. Username: "admin"
2. Password: "admin123"
3. Click "Login"

**Expected Result:**
- ✓ Redirect to admin.html
- ✓ Full access to all features

### 2. Queue Token Creation (Patient)

#### Test Case 2.1: Create Queue Token
**Steps:**
1. Login as patient
2. Go to Patient Portal
3. Fill registration form:
   - Full Name: "Jane Smith"
   - Phone: "+265 999 123 456"
   - ID Number: "12345678"
   - Department: "OPD"
   - Priority: "No"
4. Click "Register & Join Queue"

**Expected Result:**
- ✓ Success message with token number
- ✓ Token displayed (e.g., "OPD-20250108-0001")
- ✓ Queue position shown
- ✓ Form cleared
- ✓ Token saved in database

#### Test Case 2.2: Check Token Status
**Steps:**
1. Copy token number from previous test
2. Switch to "Queue Status" tab
3. Enter token number
4. Click "Check Status"

**Expected Result:**
- ✓ Token details displayed
- ✓ Shows: Token number, patient name, department, status, position
- ✓ Status shows "WAITING"

### 3. Staff Queue Management

#### Test Case 3.1: Call Next Patient
**Steps:**
1. Login as staff/admin
2. Go to Staff Portal
3. Select department: "OPD"
4. Click "Call Next Patient"

**Expected Result:**
- ✓ Success message: "Now serving: [token]"
- ✓ Queue list updated
- ✓ Token status changed to "SERVING"
- ✓ Notification sound plays
- ✓ Public display updated

#### Test Case 3.2: View Queue List
**Steps:**
1. Staff portal with department selected
2. Observe queue list

**Expected Result:**
- ✓ All waiting patients displayed
- ✓ Priority patients highlighted
- ✓ Currently serving patient shown
- ✓ Queue positions accurate
- ✓ Auto-refresh every 10 seconds

### 4. Public Display

#### Test Case 4.1: View Public Display
**Steps:**
1. Go to http://localhost/queue%20system/html/display.html
2. Observe all departments

**Expected Result:**
- ✓ Shows current serving token for each department
- ✓ Shows next waiting token
- ✓ Updates every 5 seconds
- ✓ All 4 departments visible

### 5. Error Handling Tests

#### Test Case 5.1: Invalid Login
**Steps:**
1. Enter wrong username/password
2. Click Login

**Expected Result:**
- ✓ Error message: "Invalid credentials"
- ✓ No redirect
- ✓ Form not cleared

#### Test Case 5.2: Duplicate Username
**Steps:**
1. Try to register with existing username
2. Click Create Account

**Expected Result:**
- ✓ Error: "Username already taken"
- ✓ No database insertion

#### Test Case 5.3: Server Offline
**Steps:**
1. Stop Apache in XAMPP
2. Try to login

**Expected Result:**
- ✓ Error: "Cannot connect to server. Please ensure XAMPP is running"

### 6. Role-Based Access Control

#### Test Case 6.1: Patient Access
**Steps:**
1. Login as patient
2. Try to access: http://localhost/queue%20system/html/staff.html

**Expected Result:**
- ✓ Redirect to patient.html
- ✓ Error message: "Access denied"

#### Test Case 6.2: Staff Access
**Steps:**
1. Login as staff
2. Try to access: http://localhost/queue%20system/html/admin.html

**Expected Result:**
- ✓ Redirect to staff.html
- ✓ Error message: "Access denied"

#### Test Case 6.3: Admin Access
**Steps:**
1. Login as admin
2. Access all pages

**Expected Result:**
- ✓ Can access patient.html
- ✓ Can access staff.html
- ✓ Can access admin.html
- ✓ Full permissions

### 7. Session Management

#### Test Case 7.1: Session Persistence
**Steps:**
1. Login
2. Refresh page
3. Navigate to different pages

**Expected Result:**
- ✓ User stays logged in
- ✓ User info persists
- ✓ No re-login required

#### Test Case 7.2: Logout
**Steps:**
1. Click logout button
2. Try to access protected page

**Expected Result:**
- ✓ Success message: "Logged out successfully"
- ✓ Redirect to home
- ✓ Session cleared
- ✓ Redirect to login when accessing protected pages

## Database Verification

### Check Users Table
```sql
SELECT * FROM users;
```
**Expected:**
- Admin user exists
- New registered users appear
- Passwords are hashed

### Check Queue Tokens Table
```sql
SELECT * FROM queue_tokens ORDER BY created_at DESC LIMIT 10;
```
**Expected:**
- New tokens appear
- Token numbers are unique
- Status updates correctly

### Check Queue History
```sql
SELECT * FROM queue_history ORDER BY action_time DESC LIMIT 10;
```
**Expected:**
- Actions logged (created, called, completed)
- Performed_by shows user ID

## Performance Tests

### Test Case P1: Auto-Refresh
**Steps:**
1. Open staff portal
2. Open public display
3. Create new token from patient portal
4. Observe updates

**Expected:**
- ✓ Staff portal updates within 10 seconds
- ✓ Public display updates within 5 seconds
- ✓ No page freezing

### Test Case P2: Multiple Users
**Steps:**
1. Open 3 browser tabs
2. Login different users in each
3. Perform actions simultaneously

**Expected:**
- ✓ No conflicts
- ✓ All sessions independent
- ✓ Data consistency maintained

## Browser Compatibility

Test on:
- [ ] Google Chrome
- [ ] Mozilla Firefox
- [ ] Microsoft Edge
- [ ] Safari (if available)

## Mobile Responsiveness

Test on:
- [ ] Mobile phone (portrait)
- [ ] Mobile phone (landscape)
- [ ] Tablet
- [ ] Desktop

## Common Issues & Solutions

### Issue 1: "Cannot connect to server"
**Solution:**
- Check XAMPP Apache is running
- Verify URL has correct port
- Check firewall settings

### Issue 2: "Database connection failed"
**Solution:**
- Check MySQL is running
- Verify database exists
- Check config.php credentials

### Issue 3: "Token not created"
**Solution:**
- Check all form fields filled
- Verify department exists
- Check database permissions

### Issue 4: "Session not persisting"
**Solution:**
- Check browser cookies enabled
- Verify session_start() in PHP
- Check localStorage not blocked

## Success Criteria

System is ready for deployment when:
- ✅ All test cases pass
- ✅ No console errors
- ✅ Database operations work
- ✅ Role-based access enforced
- ✅ Error handling works
- ✅ Auto-refresh functions properly
- ✅ Mobile responsive
- ✅ Cross-browser compatible

## Next Steps After Testing

1. **Security Hardening**
   - Change default admin password
   - Add HTTPS in production
   - Implement rate limiting
   - Add CSRF protection

2. **Performance Optimization**
   - Add database indexes
   - Implement caching
   - Optimize queries
   - Minify JS/CSS

3. **Feature Enhancements**
   - SMS notifications
   - Email alerts
   - Print token receipts
   - Advanced analytics

4. **Deployment**
   - Move to production server
   - Configure domain
   - Setup SSL certificate
   - Configure backups
