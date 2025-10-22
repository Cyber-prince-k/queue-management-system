# Patient Registration & Queue Request Feature

## Overview
Patients can now register with comprehensive details and request to join the queue for their desired department. All patient requests appear on the staff dashboard with full visibility of patient information.

## Features Implemented

### 1. **Enhanced Patient Registration Form**
Patients must fill in the following information:
- **Full Name** (Required)
- **Age** (Required) - Auto-detects elderly priority (65+)
- **Phone Number** (Required)
- **ID Number** (Required) - National ID or Passport
- **Address** (Optional)
- **Department to Visit** (Required) - OPD, Maternity, Emergency, Pediatrics
- **Service Required** (Optional) - e.g., Consultation, Check-up, Follow-up
- **Priority Case** (Optional) - Emergency, Elderly, Pregnant, Disability

### 2. **Queue Request Workflow**

#### Patient Side:
1. Patient logs into the system
2. Fills out registration form with all details
3. Selects department they want to visit
4. Submits form to join queue
5. Receives unique token number
6. Token number can be used to check queue status

#### Staff Side:
1. Staff views queue for their department
2. Sees all patient requests with full details:
   - Token number
   - Patient name
   - Age
   - Phone number
   - Service requested
   - Priority status
   - Queue position
3. Can manage patients (call next, mark attended, reassign)

### 3. **Queue Statistics Dashboard**
Staff dashboard now shows:
- **Total Patients Waiting** - Number of patients in waiting status
- **Currently Serving** - Number of patients being served
- **Priority Cases** - Number of priority patients
- **Queue Count Badge** - Total patients in queue

### 4. **Auto-Priority Detection**
- Patients aged 65+ automatically get "elderly" priority if no other priority selected
- Helps ensure elderly patients receive appropriate priority service

## Database Schema Updates

### New Fields in `queue_tokens` Table:
```sql
patient_age INT                 -- Patient's age
patient_address TEXT            -- Residential address
service_type VARCHAR(100)       -- Type of service requested
```

### Updated `queue_history` Table:
- `token_id` now allows NULL for queue-level actions
- New action types: `queue_paused`, `queue_resumed`, `reassigned`, `attended`
- Renamed `action_time` to `created_at` for consistency

## User Experience Flow

### Patient Registration Flow
```
1. Login → 2. Patient Portal → 3. Fill Form → 4. Submit → 5. Receive Token
```

**Form Validation:**
- All required fields must be filled
- Age must be between 0-150
- Department must be selected
- Phone and ID number required for contact/identification

**Success Response:**
- Token number displayed prominently
- Queue position shown
- Department confirmation
- Warning to keep token number safe

### Staff Queue Management Flow
```
1. Login → 2. Staff Portal → 3. Select Department → 4. View Queue → 5. Manage Patients
```

**Queue Display:**
- Patients listed in order (priority first, then FIFO)
- Full patient details visible
- Action buttons for each patient
- Real-time statistics at top
- Auto-refresh every 10 seconds

## Technical Implementation

### Frontend Changes

#### `patient.html`
- Enhanced form with new fields (age, address, service type)
- Informational banners explaining the process
- Better visual design with placeholders
- Required field indicators (*)
- Auto-scroll to token display after submission

#### `staff.html`
- Queue statistics dashboard added
- Queue count badge on header
- Enhanced patient information display

#### `queue.js`
- `updateQueueStatistics()` function added
- Enhanced `refreshQueueDisplay()` to show patient details
- Statistics update on every queue refresh

### Backend Changes

#### `queue.php`
- Accept new patient fields (age, address, service_type)
- Store all fields in database
- Return comprehensive patient data in API responses

#### Database Migration
- `migration_add_patient_fields.sql` created for existing databases
- Adds new columns without data loss
- Updates enum types for new action types

## API Endpoints

### Create Queue Token
**Endpoint:** `POST /queue.php?action=create`

**Request Body:**
```json
{
  "patient_name": "John Doe",
  "patient_age": 45,
  "patient_phone": "+265 999 123 456",
  "patient_id_number": "MWI123456",
  "patient_address": "123 Main St, Blantyre",
  "service_type": "General Consultation",
  "department": "opd",
  "priority_type": "no"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Token created successfully",
  "token": {
    "id": 1,
    "token_number": "OPD-20231014-0001",
    "queue_position": 5
  }
}
```

### Get Queue Status
**Endpoint:** `GET /queue.php?action=status&department=opd`

**Response:**
```json
{
  "success": true,
  "tokens": [
    {
      "id": 1,
      "token_number": "OPD-20231014-0001",
      "patient_name": "John Doe",
      "patient_age": 45,
      "patient_phone": "+265 999 123 456",
      "service_type": "General Consultation",
      "department_name": "Outpatient Department (OPD)",
      "priority_type": "no",
      "status": "waiting",
      "queue_position": 1
    }
  ]
}
```

## Benefits

### For Patients:
1. **Complete Information Capture** - All relevant details collected upfront
2. **Clear Process** - Step-by-step guidance through registration
3. **Token System** - Easy to track queue status
4. **Priority Recognition** - Automatic priority for eligible patients
5. **Transparency** - Can check queue position anytime

### For Staff:
1. **Full Patient Context** - All information visible before calling patient
2. **Better Planning** - Can see service types and prepare accordingly
3. **Contact Information** - Can reach patients if needed
4. **Priority Management** - Clear visibility of priority cases
5. **Real-time Statistics** - Quick overview of queue status

### For Hospital:
1. **Data Collection** - Comprehensive patient data for records
2. **Efficiency** - Streamlined patient flow
3. **Accountability** - All actions logged in database
4. **Analytics Ready** - Data structured for reporting
5. **Scalability** - System handles multiple departments

## Security & Privacy

### Data Protection:
- Patient data only visible to logged-in staff
- Role-based access control enforced
- Session-based authentication
- SQL injection protection via prepared statements

### Privacy Considerations:
- Phone numbers and addresses stored securely
- Only relevant staff can view patient details
- Audit trail of all data access via queue_history

## Installation & Setup

### For New Installations:
1. Run `database/schema.sql` to create database
2. System includes all new fields

### For Existing Installations:
1. Backup your database first
2. Run `database/migration_add_patient_fields.sql`
3. Verify migration completed successfully
4. Clear browser cache and reload

### Verification:
```sql
-- Check if new columns exist
DESCRIBE queue_tokens;

-- Should show: patient_age, patient_address, service_type
```

## Testing Checklist

- [ ] Patient can register with all fields
- [ ] Age validation works (0-150)
- [ ] Required fields enforced
- [ ] Token generated successfully
- [ ] Patient appears in staff queue
- [ ] Staff can see all patient details
- [ ] Statistics update correctly
- [ ] Auto-priority for 65+ works
- [ ] Queue position calculated correctly
- [ ] Database stores all fields

## Future Enhancements

1. **SMS Notifications** - Send token number via SMS
2. **Email Confirmation** - Email queue details to patient
3. **Appointment Scheduling** - Pre-book time slots
4. **Medical History** - Link to patient medical records
5. **Queue Time Estimates** - Show estimated wait time
6. **Multi-language Support** - Support local languages
7. **Patient Photos** - Optional photo upload for identification
8. **Insurance Information** - Capture insurance details

## Support & Troubleshooting

### Common Issues:

**Issue:** Form submission fails
- Check all required fields are filled
- Verify department is selected
- Check browser console for errors

**Issue:** Patient details not showing on staff dashboard
- Verify database migration ran successfully
- Check API response includes new fields
- Clear browser cache

**Issue:** Statistics not updating
- Check JavaScript console for errors
- Verify `updateQueueStatistics()` function is called
- Ensure element IDs match in HTML

## Summary

The patient registration system now provides a comprehensive solution for queue management:
- ✅ Patients fill detailed forms before joining queue
- ✅ Staff see all patient information on dashboard
- ✅ Real-time statistics show queue status
- ✅ All data stored in database for audit trail
- ✅ Auto-priority detection for elderly patients
- ✅ Seamless integration with existing queue system

This ensures efficient patient handling and better service delivery at QECH.
