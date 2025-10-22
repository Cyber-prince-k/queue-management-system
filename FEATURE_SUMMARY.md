# QECH Queue Management System - Feature Summary

## 🎯 Complete System Overview

### Patient Experience
```
┌─────────────────────────────────────────────────────────────┐
│                    PATIENT JOURNEY                          │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  1. LOGIN → Patient Portal                                  │
│                                                             │
│  2. FILL REGISTRATION FORM                                  │
│     ✓ Full Name (Required)                                  │
│     ✓ Age (Required) - Auto-priority if 65+                 │
│     ✓ Phone Number (Required)                               │
│     ✓ ID Number (Required)                                  │
│     ○ Address (Optional)                                    │
│     ✓ Department to Visit (Required)                        │
│     ○ Service Required (Optional)                           │
│     ○ Priority Case (Optional)                              │
│                                                             │
│  3. SUBMIT & JOIN QUEUE                                     │
│     → Receive Token Number (e.g., OPD-20231014-0001)        │
│     → See Queue Position                                    │
│                                                             │
│  4. CHECK STATUS ANYTIME                                    │
│     → Enter token number                                    │
│     → See current position & status                         │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### Staff Experience
```
┌─────────────────────────────────────────────────────────────┐
│                    STAFF DASHBOARD                          │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  📊 QUEUE STATISTICS                                        │
│  ┌─────────────┬─────────────┬─────────────┐               │
│  │  Waiting    │  Serving    │  Priority   │               │
│  │     15      │      2      │      3      │               │
│  └─────────────┴─────────────┴─────────────┘               │
│                                                             │
│  🎛️ QUEUE CONTROLS                                          │
│  [Call Next] [Pause Queue] [Resume Queue]                  │
│                                                             │
│  📋 PATIENT LIST (Current Queue: 17)                        │
│  ┌───────────────────────────────────────────────────────┐ │
│  │ OPD-20231014-0001 - John Doe        [SERVING]         │ │
│  │ Age: 45 | Phone: +265 999 123 456                     │ │
│  │ Service: General Consultation                         │ │
│  │ [✓ Attended] [↔ Reassign]                             │ │
│  ├───────────────────────────────────────────────────────┤ │
│  │ OPD-20231014-0002 - Jane Smith      Position: 1       │ │
│  │ Age: 72 | Phone: +265 888 456 789   Priority: elderly │ │
│  │ Service: Follow-up                                    │ │
│  │ [✓ Attended] [↔ Reassign]                             │ │
│  └───────────────────────────────────────────────────────┘ │
│                                                             │
│  ⚡ Auto-refresh every 10 seconds                           │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

## 🔧 Staff Control Actions

### 1. Pause Queue
- **Action:** Temporarily stop accepting new patients
- **Database:** Sets `departments.is_active = 0`
- **UI Effect:** Disables "Call Next" button
- **Logged:** Yes, in queue_history

### 2. Resume Queue
- **Action:** Reactivate queue operations
- **Database:** Sets `departments.is_active = 1`
- **UI Effect:** Re-enables "Call Next" button
- **Logged:** Yes, in queue_history

### 3. Reassign Patient
- **Action:** Move patient to different department
- **Database:** Updates `department_id`, recalculates `queue_position`
- **UI Effect:** Patient removed from current queue
- **Logged:** Yes, with new department in notes

### 4. Mark as Attended
- **Action:** Complete patient service
- **Database:** Sets `status = 'completed'`, records `completed_at`
- **UI Effect:** Patient removed from active queue
- **Logged:** Yes, in queue_history

### 5. Call Next Patient
- **Action:** Call next patient in line (priority first)
- **Database:** Sets `status = 'serving'`, records `called_at`
- **UI Effect:** Patient marked as "SERVING"
- **Logged:** Yes, in queue_history

## 📊 Data Flow

```
PATIENT SUBMITS FORM
        ↓
   API: queue.php
        ↓
  DATABASE: Insert into queue_tokens
        ↓
  Return Token Number
        ↓
  PATIENT RECEIVES TOKEN
        ↓
  STAFF DASHBOARD AUTO-REFRESHES
        ↓
  PATIENT APPEARS IN QUEUE
        ↓
  STAFF TAKES ACTION
        ↓
  DATABASE UPDATED
        ↓
  QUEUE_HISTORY LOGGED
        ↓
  UI UPDATES FOR ALL USERS
```

## 🗄️ Database Structure

### queue_tokens Table
```
id                  INT (Primary Key)
token_number        VARCHAR(20) UNIQUE
patient_id          INT (Foreign Key → users.id)
patient_name        VARCHAR(100) ✓ NEW ENHANCED
patient_age         INT ✨ NEW
patient_phone       VARCHAR(20) ✓ NEW ENHANCED
patient_id_number   VARCHAR(50) ✓ NEW ENHANCED
patient_address     TEXT ✨ NEW
service_type        VARCHAR(100) ✨ NEW
department_id       INT (Foreign Key → departments.id)
priority_type       ENUM('no', 'emergency', 'elderly', 'pregnant', 'disability')
status              ENUM('waiting', 'serving', 'completed', 'cancelled')
queue_position      INT
created_at          TIMESTAMP
called_at           TIMESTAMP
completed_at        TIMESTAMP
```

### queue_history Table
```
id              INT (Primary Key)
token_id        INT (Foreign Key → queue_tokens.id) - NOW NULLABLE
action          ENUM('created', 'called', 'completed', 'cancelled', 
                     'queue_paused', 'queue_resumed', 'reassigned', 'attended') ✨ UPDATED
performed_by    INT (Foreign Key → users.id)
created_at      TIMESTAMP
notes           TEXT
```

## 🎨 UI Components

### Patient Portal
- ✅ Info banner explaining process
- ✅ Comprehensive registration form
- ✅ Field validation
- ✅ Required field indicators (*)
- ✅ Placeholder text for guidance
- ✅ Warning about keeping token safe
- ✅ Token display after submission
- ✅ Queue status checker

### Staff Portal
- ✅ Info banner explaining staff controls
- ✅ Department selector
- ✅ Queue statistics dashboard
- ✅ Queue management buttons
- ✅ Patient list with details
- ✅ Action buttons per patient
- ✅ Real-time auto-refresh
- ✅ Visual status indicators

## 🔐 Security Features

- ✅ Role-based access control (patient/staff/admin)
- ✅ Session-based authentication
- ✅ SQL injection protection (prepared statements)
- ✅ Input validation on client and server
- ✅ Audit trail of all actions
- ✅ User ID tracking for accountability

## 📈 Key Metrics Tracked

### Real-time Statistics
1. **Patients Waiting** - Count of status='waiting'
2. **Currently Serving** - Count of status='serving'
3. **Priority Cases** - Count of priority_type != 'no'
4. **Total in Queue** - Total active patients

### Historical Data (via queue_history)
- Patient registration times
- Average wait times
- Staff actions performed
- Queue pause/resume events
- Patient reassignments
- Completion rates

## 🚀 Performance Features

- ✅ Auto-refresh every 10 seconds (configurable)
- ✅ Efficient SQL queries with indexes
- ✅ Minimal data transfer (JSON API)
- ✅ Client-side rendering for speed
- ✅ Optimized database structure

## 📱 Responsive Design

- ✅ Mobile-friendly forms
- ✅ Touch-friendly buttons
- ✅ Responsive statistics dashboard
- ✅ Adaptive layouts
- ✅ Clear typography

## 🎯 Business Benefits

### For Patients
1. **Transparency** - Know exact queue position
2. **Convenience** - Register from anywhere
3. **Fair System** - FIFO with priority handling
4. **Time Saving** - No physical queue standing
5. **Information** - Clear communication

### For Staff
1. **Efficiency** - Quick patient processing
2. **Information** - Full patient context
3. **Control** - Manage queue flow
4. **Flexibility** - Reassign as needed
5. **Accountability** - All actions logged

### For Hospital
1. **Data** - Comprehensive patient records
2. **Analytics** - Queue performance metrics
3. **Compliance** - Audit trail maintained
4. **Scalability** - Handles multiple departments
5. **Modernization** - Digital transformation

## 📋 Complete Feature List

### Patient Features
- [x] User authentication (login required)
- [x] Comprehensive registration form
- [x] Age-based auto-priority
- [x] Department selection
- [x] Service type specification
- [x] Priority case selection
- [x] Token generation
- [x] Queue status checking
- [x] Position tracking

### Staff Features
- [x] Department-specific queues
- [x] Real-time queue statistics
- [x] Patient detail visibility
- [x] Call next patient
- [x] Pause queue
- [x] Resume queue
- [x] Reassign patients
- [x] Mark as attended
- [x] Auto-refresh
- [x] Action confirmations

### System Features
- [x] Database persistence
- [x] Action logging
- [x] User tracking
- [x] Priority queue management
- [x] Multi-department support
- [x] Session management
- [x] Error handling
- [x] Input validation
- [x] API endpoints
- [x] Responsive design

## 🎓 Training Points

### For Patients
1. How to register and join queue
2. Understanding token numbers
3. Checking queue status
4. Priority eligibility

### For Staff
1. Selecting department
2. Reading queue statistics
3. Calling next patient
4. Using pause/resume
5. Reassigning patients
6. Marking patients attended
7. Understanding priority system

## ✅ System Requirements

### Server
- Apache/Nginx web server
- PHP 7.4 or higher
- MySQL 5.7 or higher
- XAMPP (for development)

### Client
- Modern web browser (Chrome, Firefox, Edge, Safari)
- JavaScript enabled
- Internet connection
- Screen resolution: 320px+ (mobile supported)

## 🎉 Success Indicators

Your system is working correctly when:
- ✅ Patients can register and receive tokens
- ✅ Staff can see all patient details
- ✅ Statistics update in real-time
- ✅ All controls work without errors
- ✅ Database logs all actions
- ✅ Auto-refresh works smoothly
- ✅ No console errors
- ✅ Mobile devices work properly

---

**System Status:** ✅ Fully Operational
**Last Updated:** October 2023
**Version:** 2.0 (Enhanced Patient Registration & Staff Controls)
