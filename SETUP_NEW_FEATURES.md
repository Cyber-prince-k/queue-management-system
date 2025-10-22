# Setup Guide for New Features

## Quick Start - Database Update

### Step 1: Backup Your Database
Before making any changes, backup your existing database:
```sql
-- In phpMyAdmin, select your database and click "Export"
-- Or use command line:
mysqldump -u root -p qech_queue_system > backup_before_update.sql
```

### Step 2: Run Database Migration
Open phpMyAdmin and run the migration script:

1. Select `qech_queue_system` database
2. Click on "SQL" tab
3. Copy and paste the contents of `database/migration_add_patient_fields.sql`
4. Click "Go" to execute

**OR** if you're starting fresh:
- Drop the existing database
- Run `database/schema.sql` (includes all new fields)

### Step 3: Verify Database Update
Run this query to verify:
```sql
DESCRIBE queue_tokens;
```

You should see these new columns:
- `patient_age` (INT)
- `patient_address` (TEXT)
- `service_type` (VARCHAR)

### Step 4: Clear Browser Cache
- Press `Ctrl + Shift + Delete` (Windows) or `Cmd + Shift + Delete` (Mac)
- Select "Cached images and files"
- Click "Clear data"

### Step 5: Test the System

#### Test Patient Registration:
1. Login as a patient (or create a patient account)
2. Navigate to Patient Portal
3. Fill out the registration form with:
   - Name, Age, Phone, ID Number
   - Select a department
   - Submit
4. Verify you receive a token number

#### Test Staff Dashboard:
1. Login as staff
2. Navigate to Staff Portal
3. Select the department where you registered the patient
4. Verify you can see:
   - Patient details (age, phone, service)
   - Queue statistics (waiting, serving, priority)
   - Action buttons (Attended, Reassign)

## Features Overview

### What's New for Patients:
✅ Enhanced registration form with age, address, service type
✅ Clear instructions and visual feedback
✅ Auto-priority for elderly (65+)
✅ Token display with queue position

### What's New for Staff:
✅ Queue statistics dashboard (waiting, serving, priority counts)
✅ Patient details visible in queue (age, phone, service)
✅ Pause/Resume queue functionality
✅ Reassign patients between departments
✅ Mark patients as attended
✅ All actions logged in database

## File Changes Summary

### Modified Files:
1. `html/patient.html` - Enhanced registration form
2. `html/staff.html` - Added statistics dashboard
3. `js/queue.js` - Added statistics update function
4. `php/api/queue.php` - Handle new patient fields
5. `css/style.css` - Added btn-sm class
6. `database/schema.sql` - Updated table structure

### New Files:
1. `database/migration_add_patient_fields.sql` - Migration script
2. `PATIENT_REGISTRATION_FEATURE.md` - Feature documentation
3. `STAFF_CONTROLS_FEATURE.md` - Staff controls documentation
4. `STAFF_CONTROLS_TEST_PLAN.md` - Testing guide
5. `IMPLEMENTATION_SUMMARY.md` - Implementation overview
6. `SETUP_NEW_FEATURES.md` - This file

## Troubleshooting

### Issue: "Column 'patient_age' doesn't exist"
**Solution:** Run the migration script in phpMyAdmin

### Issue: Patient form doesn't show new fields
**Solution:** Clear browser cache and hard refresh (Ctrl + F5)

### Issue: Statistics showing 0 even with patients in queue
**Solution:** 
- Check browser console for JavaScript errors
- Verify queue.js is loaded
- Check that element IDs match in staff.html

### Issue: Auto-refresh not working
**Solution:**
- Check browser console for errors
- Verify XAMPP/Apache is running
- Check API endpoint is accessible

### Issue: Can't pause/resume queue
**Solution:**
- Verify you're logged in as staff or admin
- Check database migration updated queue_history table
- Check browser console for API errors

## Testing Checklist

After setup, test these scenarios:

### Patient Flow:
- [ ] Can access patient portal after login
- [ ] Can fill all form fields
- [ ] Age field accepts numbers only
- [ ] Department dropdown works
- [ ] Form submits successfully
- [ ] Token number is displayed
- [ ] Can check queue status with token

### Staff Flow:
- [ ] Can access staff portal after login
- [ ] Can select department
- [ ] Queue displays with patient details
- [ ] Statistics show correct counts
- [ ] Can call next patient
- [ ] Can pause queue
- [ ] Can resume queue
- [ ] Can mark patient as attended
- [ ] Can reassign patient to another department
- [ ] Auto-refresh works (wait 10 seconds)

### Database Verification:
- [ ] New patient records include age, address, service_type
- [ ] Queue history logs all actions
- [ ] Staff actions include performed_by user ID
- [ ] Timestamps are recorded correctly

## Support

If you encounter issues:

1. **Check Browser Console** (F12) for JavaScript errors
2. **Check PHP Error Log** in XAMPP control panel
3. **Verify Database** structure matches schema
4. **Test API Endpoints** directly in browser or Postman
5. **Review Documentation** in the feature markdown files

## Next Steps

Once everything is working:

1. **Create Test Accounts**
   - Create patient accounts for testing
   - Create staff accounts for each department
   - Test with multiple concurrent users

2. **Configure Settings**
   - Adjust auto-refresh interval if needed (in queue.js)
   - Customize department names if needed
   - Add more priority types if required

3. **Train Users**
   - Show staff how to use new controls
   - Demonstrate patient registration process
   - Explain queue statistics

4. **Monitor Performance**
   - Check database query performance
   - Monitor auto-refresh impact
   - Verify system handles peak loads

## Success Criteria

Your system is ready when:
✅ Patients can register with full details
✅ Staff can see all patient information
✅ Queue statistics update in real-time
✅ All staff controls work (pause, resume, reassign, attend)
✅ Database logs all actions
✅ No JavaScript errors in console
✅ No PHP errors in logs

Congratulations! Your QECH Queue Management System is now fully operational with enhanced features! 🎉
