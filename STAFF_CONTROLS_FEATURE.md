# Staff Controls Feature Documentation

## Overview
The staff portal now includes comprehensive queue management controls that allow hospital staff to efficiently manage patient queues. All actions are logged in the database for audit trails and system consistency.

## Features Implemented

### 1. **Pause Queue**
- **Functionality**: Staff can pause the queue for their department
- **Database Action**: Sets `is_active = 0` in the departments table
- **UI Behavior**: 
  - Disables "Call Next Patient" button
  - Changes button to "Queue Paused" with warning styling
  - Stops auto-refresh of queue display
- **Logging**: Records action in `queue_history` table with action type `queue_paused`

### 2. **Resume Queue**
- **Functionality**: Staff can resume a paused queue
- **Database Action**: Sets `is_active = 1` in the departments table
- **UI Behavior**:
  - Re-enables "Call Next Patient" button
  - Restores normal button styling
  - Resumes auto-refresh of queue display
- **Logging**: Records action in `queue_history` table with action type `queue_resumed`

### 3. **Reassign Patient**
- **Functionality**: Staff can move a patient to a different department
- **Database Action**: 
  - Updates patient's `department_id`
  - Recalculates `queue_position` in new department
  - Resets status to `waiting`
- **UI Behavior**: 
  - Prompts staff to select new department
  - Shows available departments (excluding current)
  - Validates department code input
- **Logging**: Records action in `queue_history` table with action type `reassigned` and notes about new department

### 4. **Mark Patient as Attended**
- **Functionality**: Staff can mark a patient as completed/attended
- **Database Action**: 
  - Sets patient status to `completed`
  - Records `completed_at` timestamp
- **UI Behavior**: 
  - Confirmation dialog before marking
  - Removes patient from active queue display
  - Shows success notification
- **Logging**: Records action in `queue_history` table with action type `attended`

## Technical Implementation

### Backend API Endpoints (queue.php)
```
POST /queue.php?action=pause_queue&department={code}
POST /queue.php?action=resume_queue&department={code}
POST /queue.php?action=reassign (with JSON body: {token_id, new_department})
POST /queue.php?action=mark_attended&token_id={id}
```

### Frontend Functions (queue.js)
- `pauseQueue(departmentCode)` - Pauses queue for department
- `resumeQueue(departmentCode)` - Resumes queue for department
- `reassignPatient(tokenId, newDepartmentCode)` - Reassigns patient
- `markPatientAttended(tokenId)` - Marks patient as attended

### UI Components (staff.html)
- **Info Banner**: Explains staff control capabilities
- **Queue Controls**: Pause/Resume buttons with state management
- **Patient Actions**: Per-patient action buttons (Attended, Reassign)
- **Visual Feedback**: Button states, disabled states, color coding

## User Experience Flow

### Pausing a Queue
1. Staff clicks "Pause Queue" button
2. System sends pause request to backend
3. Database updates department status
4. UI disables call button and shows "Queue Paused"
5. Auto-refresh stops
6. Success notification displayed

### Resuming a Queue
1. Staff clicks "Resume Queue" button
2. System sends resume request to backend
3. Database updates department status
4. UI re-enables call button and restores normal state
5. Auto-refresh resumes
6. Success notification displayed

### Reassigning a Patient
1. Staff clicks "Reassign" button on patient row
2. Prompt shows available departments
3. Staff enters department code
4. System validates and processes reassignment
5. Patient moved to new department queue
6. Current queue refreshes to show updated list

### Marking Patient as Attended
1. Staff clicks "Attended" button on patient row
2. Confirmation dialog appears
3. Upon confirmation, patient marked as completed
4. Patient removed from queue display
5. Success notification displayed

## Database Logging

All staff actions are logged in the `queue_history` table with:
- `token_id`: Patient token (or NULL for queue-level actions)
- `action`: Type of action performed
- `performed_by`: Staff user ID from session
- `notes`: Additional context (e.g., department name, reassignment details)
- `created_at`: Timestamp of action

### Action Types Logged
- `queue_paused` - Queue was paused
- `queue_resumed` - Queue was resumed
- `reassigned` - Patient reassigned to new department
- `attended` - Patient marked as attended/completed
- `called` - Patient called (existing)
- `completed` - Patient completed (existing)
- `created` - Token created (existing)

## Security & Validation

- All endpoints require staff or admin role (via `requireRole(['staff', 'admin'])`)
- Department codes validated against database
- Token IDs validated before operations
- SQL injection prevented via prepared statements
- Session-based authentication for user tracking

## Benefits

1. **Efficiency**: Staff can quickly manage patient flow
2. **Flexibility**: Patients can be reassigned as needed
3. **Transparency**: All actions logged for accountability
4. **Real-time Updates**: Database always reflects current state
5. **User-Friendly**: Clear visual feedback and confirmations
6. **Audit Trail**: Complete history of all queue management actions

## Future Enhancements

Potential improvements:
- Bulk operations (reassign multiple patients)
- Queue analytics dashboard
- Automated queue balancing suggestions
- SMS notifications for reassigned patients
- Staff performance metrics based on logged actions
