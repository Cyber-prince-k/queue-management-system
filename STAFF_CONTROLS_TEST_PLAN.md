# Staff Controls Testing Guide

## Prerequisites
1. XAMPP running (Apache + MySQL)
2. Database initialized with queue system schema
3. User logged in with staff or admin role
4. At least one patient in queue for testing

## Test Cases

### Test 1: Pause Queue Functionality
**Steps:**
1. Navigate to Staff Portal (`staff.html`)
2. Select a department from dropdown
3. Verify queue displays with patients
4. Click "Pause Queue" button

**Expected Results:**
- ✓ Toast notification: "Queue paused successfully"
- ✓ Button changes to "Queue Paused" with warning (orange) styling
- ✓ Button becomes disabled
- ✓ "Resume Queue" button appears
- ✓ "Call Next Patient" button becomes disabled
- ✓ Auto-refresh stops
- ✓ Database: `departments.is_active = 0`
- ✓ Database: New entry in `queue_history` with action `queue_paused`

---

### Test 2: Resume Queue Functionality
**Steps:**
1. With queue paused (from Test 1)
2. Click "Resume Queue" button

**Expected Results:**
- ✓ Toast notification: "Queue resumed successfully"
- ✓ "Pause Queue" button restored to normal styling
- ✓ "Pause Queue" button becomes enabled
- ✓ "Resume Queue" button disappears
- ✓ "Call Next Patient" button becomes enabled
- ✓ Auto-refresh resumes (queue updates every 10 seconds)
- ✓ Database: `departments.is_active = 1`
- ✓ Database: New entry in `queue_history` with action `queue_resumed`

---

### Test 3: Mark Patient as Attended
**Steps:**
1. Navigate to Staff Portal with active queue
2. Locate a patient in the queue list
3. Click "✓ Attended" button on patient row
4. Confirm in the dialog

**Expected Results:**
- ✓ Confirmation dialog appears
- ✓ Toast notification: "Patient marked as attended"
- ✓ Patient removed from queue display
- ✓ Queue refreshes automatically
- ✓ Database: `queue_tokens.status = 'completed'`
- ✓ Database: `queue_tokens.completed_at` timestamp set
- ✓ Database: New entry in `queue_history` with action `attended`

**Cancel Test:**
- Click "✓ Attended" but cancel the confirmation
- Patient should remain in queue

---

### Test 4: Reassign Patient to Another Department
**Steps:**
1. Navigate to Staff Portal (e.g., OPD department)
2. Locate a patient in the queue list
3. Click "↔ Reassign" button on patient row
4. In the prompt, enter a valid department code (e.g., "maternity")
5. Submit

**Expected Results:**
- ✓ Prompt shows available departments (excluding current)
- ✓ Toast notification: "Patient reassigned successfully"
- ✓ Patient removed from current department queue
- ✓ Queue refreshes automatically
- ✓ Database: `queue_tokens.department_id` updated to new department
- ✓ Database: `queue_tokens.queue_position` recalculated for new department
- ✓ Database: `queue_tokens.status = 'waiting'`
- ✓ Database: New entry in `queue_history` with action `reassigned`
- ✓ Verify patient appears in new department queue

**Invalid Input Test:**
- Enter invalid department code (e.g., "xyz")
- Should show error: "Invalid department code"

**Cancel Test:**
- Click "↔ Reassign" but cancel the prompt
- Patient should remain in current queue

---

### Test 5: Department Switching
**Steps:**
1. Navigate to Staff Portal
2. Select "OPD" department
3. Perform some actions (pause, view queue)
4. Switch to "Maternity" department

**Expected Results:**
- ✓ Toast notification: "Switched to Maternity"
- ✓ Queue display updates to show Maternity patients
- ✓ Previous auto-refresh stopped
- ✓ New auto-refresh started for Maternity
- ✓ Queue state (paused/resumed) resets for new department

---

### Test 6: Call Next Patient (Existing Feature)
**Steps:**
1. Navigate to Staff Portal with active queue
2. Click "Call Next Patient" button

**Expected Results:**
- ✓ Toast notification: "Now serving: [TOKEN_NUMBER]"
- ✓ Next patient in queue marked as "SERVING"
- ✓ Queue display refreshes
- ✓ Notification sound plays (if supported)
- ✓ Database: Patient status changed to `serving`
- ✓ Database: `called_at` timestamp set

---

### Test 7: Auto-Refresh Behavior
**Steps:**
1. Navigate to Staff Portal
2. Open another browser tab/window
3. In second tab, add a new patient to queue
4. Wait 10 seconds
5. Check first tab

**Expected Results:**
- ✓ Queue display automatically updates with new patient
- ✓ No manual refresh needed
- ✓ Position numbers recalculated

---

### Test 8: Multiple Staff Members
**Steps:**
1. Open Staff Portal in two different browsers (or incognito)
2. Log in as different staff members
3. Both select same department
4. Staff A pauses queue
5. Check Staff B's view

**Expected Results:**
- ✓ Both staff see same queue state
- ✓ When Staff A pauses, Staff B should see paused state after auto-refresh
- ✓ Actions by one staff member reflected for all

---

### Test 9: Database Logging Verification
**Steps:**
1. Perform various actions (pause, resume, reassign, mark attended)
2. Query database: `SELECT * FROM queue_history ORDER BY created_at DESC LIMIT 10`

**Expected Results:**
- ✓ All actions logged with correct action type
- ✓ `performed_by` field contains staff user ID
- ✓ `token_id` set for patient-specific actions (or NULL for queue-level)
- ✓ `notes` field contains relevant details
- ✓ Timestamps accurate

---

### Test 10: Error Handling
**Test 10a: Server Offline**
- Stop XAMPP
- Try to pause queue
- Expected: "Cannot connect to server" error

**Test 10b: Invalid Token ID**
- Manually call `markPatientAttended(99999)` in console
- Expected: Error message displayed

**Test 10c: Empty Queue**
- Clear all patients from queue
- Click "Call Next Patient"
- Expected: "No patients in queue" message

---

## Browser Compatibility Testing
Test on:
- ✓ Chrome/Edge (Chromium)
- ✓ Firefox
- ✓ Safari (if available)

## Mobile Responsiveness
- ✓ Test on mobile viewport (DevTools)
- ✓ Buttons should be tappable
- ✓ Prompts should work on mobile

## Performance Testing
- ✓ Test with 50+ patients in queue
- ✓ Auto-refresh should not cause lag
- ✓ Action buttons should respond quickly

## Security Testing
- ✓ Try accessing staff.html without login → Should redirect
- ✓ Try accessing with patient role → Should be denied
- ✓ Verify SQL injection protection (try malicious input in reassign prompt)

---

## Quick Verification Checklist

After implementation, verify:
- [ ] Info banner displays on staff.html
- [ ] Pause/Resume buttons work correctly
- [ ] Patient action buttons (Attended, Reassign) appear on each queue item
- [ ] All actions show toast notifications
- [ ] Database updates correctly for all actions
- [ ] Queue history logs all actions
- [ ] Auto-refresh works as expected
- [ ] UI states (disabled buttons, color changes) work correctly
- [ ] No console errors
- [ ] Responsive design works on mobile

---

## SQL Queries for Verification

**Check department status:**
```sql
SELECT code, name, is_active FROM departments;
```

**Check recent queue history:**
```sql
SELECT qh.*, u.username, qt.token_number 
FROM queue_history qh
LEFT JOIN users u ON qh.performed_by = u.id
LEFT JOIN queue_tokens qt ON qh.token_id = qt.id
ORDER BY qh.created_at DESC
LIMIT 20;
```

**Check patient reassignments:**
```sql
SELECT * FROM queue_history 
WHERE action = 'reassigned' 
ORDER BY created_at DESC;
```

**Check queue status by department:**
```sql
SELECT d.code, d.name, COUNT(qt.id) as patient_count
FROM departments d
LEFT JOIN queue_tokens qt ON d.id = qt.department_id 
  AND qt.status IN ('waiting', 'serving')
GROUP BY d.id;
```
