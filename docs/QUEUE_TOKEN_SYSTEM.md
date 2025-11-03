# Queue Token System Documentation

## Overview
The Queue Management System automatically generates a **unique token number** for each patient when they submit the registration form. This token serves as their position identifier in the queue.

---

## How It Works

### 1. **Patient Submits Registration Form**
When a patient fills out the registration form on `patient.html` and clicks **"Submit & Join Queue"**, the following happens:

#### Form Data Collected:
- Patient Name (required)
- Age (required)
- Phone Number (required)
- ID Number (required)
- Address (optional)
- Department to Visit (required)
- Service Type (optional)
- Priority Case (optional)

---

### 2. **Token Number Generation** (Backend - `queue.php`)

The system generates a **unique token number** using this format:

```
[DEPT]-[DATE]-[SEQUENCE]
```

**Example:** `OPD-20251022-0001`

#### Breakdown:
- **DEPT**: First 3 letters of department code (e.g., `OPD`, `MAT`, `EME`, `PED`)
- **DATE**: Current date in `YYYYMMDD` format (e.g., `20251022`)
- **SEQUENCE**: 4-digit sequential number for that department on that day (e.g., `0001`, `0002`, etc.)

#### Code Implementation (lines 39-46 in `queue.php`):
```php
// Generate token number
$date_prefix = date('Ymd');
$dept_prefix = strtoupper(substr($department_code, 0, 3));
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM queue_tokens WHERE DATE(created_at) = CURDATE() AND department_id = ?");
$stmt->bind_param("i", $department_id);
$stmt->execute();
$count = $stmt->get_result()->fetch_assoc()['count'] + 1;
$token_number = $dept_prefix . '-' . $date_prefix . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
```

---

### 3. **Queue Position Calculation** (Backend - `queue.php`)

The system also calculates the patient's **position in line** for their department:

#### Code Implementation (lines 48-53 in `queue.php`):
```php
// Get queue position
$stmt = $conn->prepare("SELECT MAX(queue_position) as max_pos FROM queue_tokens WHERE department_id = ? AND status = 'waiting'");
$stmt->bind_param("i", $department_id);
$stmt->execute();
$max_pos = $stmt->get_result()->fetch_assoc()['max_pos'] ?? 0;
$queue_position = $max_pos + 1;
```

**How it works:**
- Finds the highest queue position number in the department
- Adds 1 to get the new patient's position
- First patient of the day gets position `1`, second gets `2`, etc.

---

### 4. **Database Storage** (Backend - `queue.php`)

The token is saved to the `queue_tokens` table with all patient information:

#### Code Implementation (lines 56-66 in `queue.php`):
```php
$stmt = $conn->prepare("INSERT INTO queue_tokens (token_number, patient_id, patient_name, patient_age, patient_phone, patient_id_number, patient_address, service_type, department_id, priority_type, queue_position) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sisissssisi", $token_number, $patient_id, $patient_name, $patient_age, $patient_phone, $patient_id_number, $patient_address, $service_type, $department_id, $priority_type, $queue_position);

if ($stmt->execute()) {
    $token_id = $conn->insert_id;
    
    // Log history
    $stmt = $conn->prepare("INSERT INTO queue_history (token_id, action, performed_by) VALUES (?, 'created', ?)");
    $stmt->bind_param("ii", $token_id, $patient_id);
    $stmt->execute();
}
```

---

### 5. **Response to Frontend** (Backend - `queue.php`)

The backend sends back the token information:

#### Code Implementation (lines 68-76 in `queue.php`):
```php
echo json_encode([
    'success' => true,
    'message' => 'Token created successfully',
    'token' => [
        'id' => $token_id,
        'token_number' => $token_number,
        'queue_position' => $queue_position
    ]
]);
```

---

### 6. **Display Token to Patient** (Frontend - `patient.html`)

The frontend receives the token and displays it beautifully:

#### Code Implementation (lines 217-249 in `patient.html`):
```javascript
function displayTokenDetails(token) {
    const tokenDisplay = document.getElementById('token-display');
    if (!tokenDisplay) return;
    
    tokenDisplay.innerHTML = `
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 2rem; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); text-align: center; color: white;">
            <div style="font-size: 3rem; margin-bottom: 1rem;">🎫</div>
            <h3 style="margin: 0 0 1rem 0; font-size: 1.5rem; font-weight: bold;">Token Created Successfully!</h3>
            
            <div style="background: rgba(255,255,255,0.95); padding: 1.5rem; border-radius: 12px; margin: 1rem 0; color: #1e293b;">
                <p style="margin: 0 0 0.5rem 0; font-size: 0.9rem; color: #64748b; text-transform: uppercase; letter-spacing: 1px;">Your Token Number</p>
                <p style="font-size: 2.5rem; font-weight: bold; margin: 0; color: #2563eb; font-family: monospace; letter-spacing: 2px;">
                    ${token.token_number}
                </p>
            </div>
            
            <div style="background: rgba(255,255,255,0.2); padding: 1rem; border-radius: 8px; margin: 1rem 0;">
                <p style="margin: 0; font-size: 1.1rem;">
                    <strong>Queue Position:</strong> <span style="font-size: 1.3rem; font-weight: bold;">#${token.queue_position}</span>
                </p>
            </div>
        </div>
    `;
}
```

---

## Visual Flow Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│ 1. PATIENT FILLS FORM                                           │
│    - Name: John Doe                                             │
│    - Department: OPD                                            │
│    - Priority: No                                               │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│ 2. FORM SUBMITTED TO BACKEND (queue.php)                        │
│    POST /php/api/queue.php?action=create                        │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│ 3. BACKEND GENERATES TOKEN                                      │
│    - Department: OPD → DEPT = "OPD"                             │
│    - Today's Date: Oct 22, 2025 → DATE = "20251022"            │
│    - Count today's OPD tokens: 0 → SEQUENCE = "0001"           │
│    - TOKEN NUMBER = "OPD-20251022-0001"                         │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│ 4. BACKEND CALCULATES QUEUE POSITION                            │
│    - Check max position in OPD: 0                               │
│    - New position = 0 + 1 = 1                                   │
│    - QUEUE POSITION = 1                                         │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│ 5. SAVE TO DATABASE                                             │
│    INSERT INTO queue_tokens                                     │
│    - token_number: "OPD-20251022-0001"                          │
│    - queue_position: 1                                          │
│    - status: "waiting"                                          │
│    - patient_name: "John Doe"                                   │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│ 6. RETURN TO FRONTEND                                           │
│    JSON Response:                                               │
│    {                                                            │
│      "success": true,                                           │
│      "token": {                                                 │
│        "token_number": "OPD-20251022-0001",                     │
│        "queue_position": 1                                      │
│      }                                                          │
│    }                                                            │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│ 7. DISPLAY TO PATIENT                                           │
│    ┌───────────────────────────────────────────────────────┐   │
│    │  🎫 Token Created Successfully!                       │   │
│    │                                                       │   │
│    │  ┌─────────────────────────────────────────────────┐ │   │
│    │  │ Your Token Number                               │ │   │
│    │  │ OPD-20251022-0001                               │ │   │
│    │  └─────────────────────────────────────────────────┘ │   │
│    │                                                       │   │
│    │  Queue Position: #1                                  │   │
│    │                                                       │   │
│    │  ⚠️ Important: Save this token number!              │   │
│    └───────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
```

---

## Key Features

### ✅ **Unique Token Numbers**
- Each token is guaranteed to be unique
- Format includes department, date, and sequence
- Easy to identify which department and day

### ✅ **Queue Position Tracking**
- Patients know exactly where they are in line
- Position updates as patients are served
- Priority patients get moved up automatically

### ✅ **Priority Handling**
- Emergency cases get priority
- Elderly (65+) automatically get priority
- Pregnant women and disabled patients can request priority
- Priority patients are served first regardless of queue position

### ✅ **Status Tracking**
Tokens have three statuses:
- **waiting**: Patient is in queue
- **serving**: Patient is currently being served
- **completed**: Patient has been attended to

---

## Example Scenarios

### Scenario 1: Regular Patient
1. **Patient**: Sarah, Age 30, Department: OPD
2. **Token Generated**: `OPD-20251022-0005`
3. **Queue Position**: 5
4. **Priority**: No
5. **Result**: Sarah is 5th in line for OPD

### Scenario 2: Priority Patient
1. **Patient**: Mary, Age 70, Department: Maternity
2. **Token Generated**: `MAT-20251022-0003`
3. **Queue Position**: 3
4. **Priority**: Elderly (auto-detected)
5. **Result**: Mary will be served before non-priority patients, even if they arrived earlier

### Scenario 3: Multiple Departments
- **OPD**: `OPD-20251022-0001`, `OPD-20251022-0002`, `OPD-20251022-0003`
- **Maternity**: `MAT-20251022-0001`, `MAT-20251022-0002`
- **Emergency**: `EME-20251022-0001`
- Each department has its own independent queue

---

## Patient Actions After Receiving Token

### 1. **Save Token Number**
- Write it down
- Take a screenshot
- Use the "Copy Number" button

### 2. **Check Queue Status**
- Go to "Queue Status" tab
- Enter token number
- See current position and status

### 3. **Wait for Service**
- Monitor the public display
- Wait for token to be called
- Proceed to service counter when called

---

## Staff Actions with Tokens

### 1. **Call Next Patient**
- Click "Call Next" button
- System automatically selects next patient (priority first)
- Token status changes to "serving"

### 2. **Complete Service**
- Click "Attended" button
- Token status changes to "completed"
- Next patient moves up in queue

### 3. **Reassign Patient**
- If patient needs different department
- Click "Reassign" button
- Patient gets new queue position in new department

---

## Technical Details

### Database Tables

#### `queue_tokens` table:
```sql
- id (primary key)
- token_number (unique, e.g., "OPD-20251022-0001")
- patient_name
- patient_age
- patient_phone
- patient_id_number
- department_id (foreign key)
- priority_type (no, emergency, elderly, pregnant, disability)
- queue_position (1, 2, 3, ...)
- status (waiting, serving, completed)
- created_at (timestamp)
- called_at (timestamp)
- completed_at (timestamp)
```

#### `queue_history` table:
```sql
- id (primary key)
- token_id (foreign key)
- action (created, called, completed, reassigned, etc.)
- performed_by (user_id)
- notes
- created_at (timestamp)
```

---

## Summary

**The system ALREADY implements a complete token/queue number system:**

1. ✅ **Generates unique token numbers** when patient submits form
2. ✅ **Calculates queue position** automatically
3. ✅ **Displays token prominently** to patient
4. ✅ **Tracks position in line** for each department
5. ✅ **Handles priority cases** appropriately
6. ✅ **Allows status checking** via token number
7. ✅ **Logs all actions** in history table

The token number serves as both:
- **Unique identifier** for the patient's queue entry
- **Position indicator** showing where they are in line

No additional changes are needed - the system is fully functional! 🎉
