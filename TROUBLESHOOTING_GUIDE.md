# QECH Queue System - Troubleshooting Guide

## Problem: Token not showing to patient & staff not seeing queue

### Quick Fix Steps:

#### Step 1: Check XAMPP is Running
1. Open XAMPP Control Panel
2. Make sure **Apache** is running (green)
3. Make sure **MySQL** is running (green)
4. If not, click "Start" for both

#### Step 2: Verify Database Exists
1. Open browser: `http://localhost/phpmyadmin`
2. Check if database `qech_queue_system` exists
3. If NOT, run the setup SQL file

#### Step 3: Run Database Fix
1. Open phpMyAdmin
2. Select `qech_queue_system` database
3. Click "SQL" tab
4. Copy and paste the contents of `fix_database.sql`
5. Click "Go"

#### Step 4: Test the System
1. Open in browser: `http://localhost/queue%20system/test_complete_flow.html`
2. Click "Create Token"
3. Check if token is created successfully
4. Click "View Queue"
5. Verify the token appears in the queue

---

## Common Errors & Solutions

### Error: "Cannot connect to server"
**Cause:** XAMPP Apache is not running  
**Solution:** Start Apache in XAMPP Control Panel

### Error: "Database connection failed"
**Cause:** MySQL is not running OR database doesn't exist  
**Solution:** 
1. Start MySQL in XAMPP
2. Create database using phpMyAdmin or run setup SQL

### Error: "Prepare failed: Table doesn't exist"
**Cause:** Database tables not created  
**Solution:** Run the database setup SQL file in phpMyAdmin

### Error: "Prepare failed: Unknown column"
**Cause:** Database schema is outdated  
**Solution:** Run `fix_database.sql` or `migration_add_patient_fields.sql`

### Error: Token created but not showing
**Cause:** JavaScript error or display issue  
**Solution:** 
1. Open browser console (F12)
2. Check for JavaScript errors
3. Refresh the page
4. Clear browser cache (Ctrl+Shift+Delete)

### Error: Staff can't see queue
**Cause:** Auto-refresh not working or API error  
**Solution:**
1. Check browser console for errors
2. Manually click "Refresh" or change department
3. Verify tokens exist in database (check phpMyAdmin)

---

## Testing Checklist

Use this checklist to verify everything is working:

- [ ] XAMPP Apache is running
- [ ] XAMPP MySQL is running
- [ ] Database `qech_queue_system` exists
- [ ] All tables exist (departments, queue_tokens, queue_history, users)
- [ ] Can access: `http://localhost/queue%20system/`
- [ ] Can create token from patient page
- [ ] Token displays with large number after creation
- [ ] Can view token in staff dashboard
- [ ] Can call next patient
- [ ] Queue statistics update correctly

---

## File Locations

- **Test Page:** `http://localhost/queue%20system/test_complete_flow.html`
- **Debug Page:** `http://localhost/queue%20system/debug_patient.html`
- **Patient Page:** `http://localhost/queue%20system/html/patient.html`
- **Staff Page:** `http://localhost/queue%20system/html/staff.html`
- **API Endpoint:** `http://localhost/queue%20system/php/api/queue.php`

---

## Database Schema Check

Run this SQL to verify your tables are correct:

```sql
USE qech_queue_system;

-- Check queue_tokens table structure
DESCRIBE queue_tokens;

-- Expected columns:
-- id, token_number, patient_id, patient_name, patient_age, patient_phone,
-- patient_id_number, patient_address, service_type, department_id,
-- priority_type, queue_position, status, created_at, called_at, completed_at
```

---

## Still Not Working?

1. **Check Browser Console (F12):**
   - Look for red error messages
   - Note the exact error text

2. **Check Apache Error Log:**
   - XAMPP Control Panel → Apache → Logs → Error Log
   - Look for PHP errors

3. **Test API Directly:**
   - Open: `http://localhost/queue%20system/php/api/queue.php?action=status`
   - Should return JSON with tokens array

4. **Verify File Permissions:**
   - Make sure PHP files are readable
   - Check that `php/api/` folder exists

5. **Clear Everything:**
   - Clear browser cache (Ctrl+Shift+Delete)
   - Restart Apache in XAMPP
   - Refresh the page (Ctrl+F5)

---

## Contact for Help

If you're still stuck, provide:
1. Screenshot of browser console (F12)
2. Screenshot of XAMPP Control Panel
3. Error message from Apache error log
4. Result of visiting the test page
