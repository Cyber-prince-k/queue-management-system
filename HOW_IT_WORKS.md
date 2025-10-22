# How the QECH Queue System Works

## Complete Flow Explanation

---

## 1️⃣ PATIENT REGISTERS - Will They Get a Token?

### ✅ YES! Here's exactly what happens:

### Step-by-Step Process:

1. **Patient fills the registration form** (`patient.html`):
   - Full Name
   - Age (auto-detects elderly for priority)
   - Phone Number
   - ID Number
   - Address
   - Department (OPD, Maternity, Emergency, Pediatrics)
   - Service Type
   - Priority Case (Emergency, Elderly, Pregnant, Disability)

2. **Patient clicks "Submit & Join Queue"**

3. **System creates token** (via `createQueueToken()` in `queue.js`):
   ```javascript
   // Sends data to: php/api/queue.php?action=create
   // Saves to MySQL database: queue_tokens table
   ```

4. **Token is generated** with format: `DEPT-YYYYMMDD-####`
   - Example: `OPD-20251022-0001`
   - Unique for each patient
   - Includes department code and date

5. **Patient IMMEDIATELY sees token display**:
   ```
   🎫 Token Created Successfully!
   
   ┌─────────────────────────┐
   │  YOUR TOKEN NUMBER      │
   │  OPD-20251022-0001     │
   │  (Large, bold display)  │
   └─────────────────────────┘
   
   Queue Position: #3
   
   ⚠️ Important: Please save this token number
   
   [🖨️ Print Token]  [📋 Copy Number]
   ```

6. **Token is saved in database** permanently:
   - Stored in `queue_tokens` table
   - Includes all patient information
   - Status: 'waiting'
   - Queue position assigned
   - Timestamp recorded

### What Patient Can Do With Token:

✅ **Print it** - Click "Print Token" button  
✅ **Copy it** - Click "Copy Number" button  
✅ **Check status** - Enter token in "Check Queue Status" section  
✅ **Show to staff** - Present token when called  

---

## 2️⃣ STAFF SEES APPLICATION - Will They See It?

### ✅ YES! Staff sees it IMMEDIATELY (or within 10 seconds)

### How Staff Sees Patient Applications:

1. **Staff opens staff portal** (`staff.html`)

2. **Selects department** from dropdown:
   - OPD
   - Maternity
   - Emergency
   - Pediatrics

3. **Queue automatically loads** showing ALL patients:

   ```
   ┌─────────────────────────────────────────────┐
   │ Current Queue [3]                           │
   ├─────────────────────────────────────────────┤
   │                                             │
   │ OPD-20251022-0001 - John Doe       Pos: 1  │
   │ ├─ Age: 45  Phone: +265 999 123 456        │
   │ ├─ Service: General Consultation           │
   │ └─ [✓ Attended] [↔ Reassign]               │
   │                                             │
   │ OPD-20251022-0002 - Jane Smith     Pos: 2  │
   │ ⚠️ Priority: elderly                        │
   │ ├─ Age: 70  Phone: +265 999 654 321        │
   │ ├─ Service: Follow-up                      │
   │ └─ [✓ Attended] [↔ Reassign]               │
   │                                             │
   │ OPD-20251022-0003 - Mary Johnson   Pos: 3  │
   │ ├─ Age: 30  Phone: +265 999 111 222        │
   │ ├─ Service: New Patient                    │
   │ └─ [✓ Attended] [↔ Reassign]               │
   │                                             │
   └─────────────────────────────────────────────┘
   ```

4. **Auto-refresh every 10 seconds**:
   - Staff doesn't need to refresh page
   - New patients appear automatically
   - Queue updates in real-time

5. **Statistics displayed at top**:
   ```
   ┌──────────────┬──────────────┬──────────────┐
   │      3       │      0       │      1       │
   │ Patients     │ Currently    │ Priority     │
   │ Waiting      │ Serving      │ Cases        │
   └──────────────┴──────────────┴──────────────┘
   ```

### What Staff Can See:

✅ **Token number** - Full token ID  
✅ **Patient name** - From registration  
✅ **Age** - Patient's age  
✅ **Phone number** - Contact information  
✅ **Service type** - What they need  
✅ **Priority status** - If emergency/elderly/etc  
✅ **Queue position** - Their place in line  
✅ **Time registered** - When they joined  

---

## 3️⃣ STAFF MANAGES QUEUE - Can They Manage It?

### ✅ YES! Staff has FULL queue management capabilities

### Staff Management Actions:

### A. **Call Next Patient**
```
[📢 Call Next Patient] button
```
- Moves first patient to "serving" status
- Updates queue positions automatically
- Shows patient details to staff
- Patient can be notified (if SMS enabled)

### B. **Mark Patient as Attended**
```
[✓ Attended] button for each patient
```
- Marks patient as completed
- Removes from active queue
- Records in history
- Updates statistics

### C. **Reassign Patient**
```
[↔ Reassign] button for each patient
```
- Move patient to different department
- Example: OPD → Emergency
- Maintains patient data
- Updates queue positions

### D. **Pause Queue**
```
[⏸ Pause Queue] button
```
- Stops calling new patients
- Keeps current patients in queue
- Useful for breaks/emergencies
- Records pause in database

### E. **Resume Queue**
```
[▶ Resume Queue] button
```
- Resumes normal operations
- Continues calling patients
- Records resume in database

### F. **View Different Departments**
```
[Department Selector Dropdown]
```
- Switch between departments
- See all department queues
- Manage multiple areas

---

## Complete Flow Diagram

```
PATIENT SIDE                    DATABASE                    STAFF SIDE
─────────────                   ────────                    ──────────

1. Fill Form
   ↓
2. Submit                    →  Save to MySQL
   ↓                            queue_tokens table
3. Receive Token                     ↓
   OPD-20251022-0001                 ↓
   ↓                                 ↓
4. Token Displayed              Token stored                    
   - Large number               - patient_name              1. Staff opens
   - Queue position             - patient_age                  staff.html
   - Print/Copy                 - patient_phone                 ↓
                                - department_id             2. Select dept
                                - priority_type                 ↓
                                - status: waiting           3. Auto-load
                                - queue_position         ←     queue
                                                                ↓
                                                            4. See patient:
                                                               - Token
                                                               - Name
                                                               - Details
                                                                ↓
                                                            5. Manage:
                                                               - Call Next
                                Update status         ←      - Mark Attended
                                - serving/completed          - Reassign
                                - queue_position             - Pause/Resume
                                     ↓
                                History saved
                                queue_history table
```

---

## Real-Time Features

### ✅ Auto-Refresh (Staff)
- Queue updates every **10 seconds**
- No manual refresh needed
- New patients appear automatically

### ✅ Persistent Data
- Tokens saved in database
- Survives page refresh
- Survives browser close
- Available across devices

### ✅ Multi-User Support
- Multiple staff can view same queue
- Changes visible to all users
- Real-time synchronization

### ✅ Priority Handling
- Priority patients highlighted in **RED**
- Moved to front of queue automatically
- Clear visual indicators

---

## Testing the Complete Flow

### Test 1: Patient Registration
1. Open: `http://localhost/queue%20system/html/patient.html`
2. Fill form with test data
3. Click "Submit & Join Queue"
4. **Expected:** Large token display appears
5. **Expected:** Token number shown (e.g., OPD-20251022-0001)
6. **Expected:** Queue position shown (e.g., #3)

### Test 2: Staff Sees Patient
1. Open: `http://localhost/queue%20system/html/staff.html`
2. Select department (e.g., OPD)
3. **Expected:** Patient appears in queue list
4. **Expected:** All details visible (name, age, phone, service)
5. **Expected:** Action buttons available

### Test 3: Staff Manages Queue
1. Click "Call Next Patient"
2. **Expected:** First patient status changes to "SERVING"
3. Click "✓ Attended" on a patient
4. **Expected:** Patient removed from queue
5. **Expected:** Statistics update automatically

### Test 4: Real-Time Updates
1. Open staff page in Browser 1
2. Open patient page in Browser 2
3. Register patient in Browser 2
4. Wait 10 seconds
5. **Expected:** Patient appears in Browser 1 automatically

---

## Summary

| Question | Answer | Details |
|----------|--------|---------|
| **Will patient get token?** | ✅ YES | Immediately after registration, large display with print/copy options |
| **Will staff see application?** | ✅ YES | Within 10 seconds, with full patient details and queue position |
| **Can staff manage queue?** | ✅ YES | Call next, mark attended, reassign, pause/resume, full control |

---

## Requirements for System to Work

✅ **XAMPP running** - Apache + MySQL must be active  
✅ **Database exists** - `qech_queue_system` with proper tables  
✅ **PHP API working** - Files in `php/api/` accessible  
✅ **Browser JavaScript enabled** - For real-time updates  

---

## Troubleshooting

### Patient doesn't get token:
1. Check browser console (F12) for errors
2. Verify XAMPP is running
3. Check database connection in `php/config.php`
4. Test with: `tests/test_complete_flow.html`

### Staff doesn't see patients:
1. Check if tokens exist in database (phpMyAdmin)
2. Verify department matches patient's department
3. Check browser console for API errors
4. Wait 10 seconds for auto-refresh

### Queue management not working:
1. Verify staff is logged in with correct role
2. Check browser console for errors
3. Verify API endpoints are accessible
4. Check database for proper permissions

---

**The system is fully functional and production-ready!** 🎉
