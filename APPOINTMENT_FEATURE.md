# Appointment Booking Feature

## Overview
The appointment booking feature allows patients to schedule future visits to the hospital by selecting their preferred date, time, and department. This complements the existing queue registration system which is for immediate/same-day visits.

## Features Added

### 1. Database Schema
**File:** `database/migration_add_appointments.sql`

Created two new tables:
- **appointments** - Stores all appointment bookings with details like patient info, department, date/time, status, etc.
- **appointment_history** - Tracks all actions performed on appointments (created, confirmed, rescheduled, completed, cancelled)

**To set up the database:**
```sql
-- Run this in phpMyAdmin or MySQL command line
source database/migration_add_appointments.sql;
```

### 2. Backend API
**File:** `php/api/appointments.php`

Provides the following endpoints:

#### Create Appointment
- **Action:** `create`
- **Method:** POST
- **Description:** Books a new appointment
- **Validates:** Future dates, time slot availability (max 3 per slot)
- **Returns:** Appointment confirmation with unique appointment number

#### List Appointments
- **Action:** `list`
- **Method:** GET
- **Parameters:** department, date, status (optional filters)
- **Description:** Retrieves all appointments with optional filtering

#### Get Appointment
- **Action:** `get`
- **Method:** GET
- **Parameters:** appointment_number
- **Description:** Retrieves a specific appointment by its number

#### Update Appointment
- **Action:** `update`
- **Method:** POST
- **Description:** Updates appointment status or notes

#### Cancel Appointment
- **Action:** `cancel`
- **Method:** POST
- **Description:** Cancels an appointment

#### Get Available Slots
- **Action:** `available-slots`
- **Method:** GET
- **Parameters:** department, date
- **Description:** Returns available time slots for a specific date and department
- **Time Range:** 8:00 AM to 4:00 PM (30-minute intervals)
- **Capacity:** Maximum 3 appointments per time slot

#### Get My Appointments
- **Action:** `my-appointments`
- **Method:** GET
- **Description:** Retrieves all appointments for the logged-in patient

### 3. Frontend UI
**File:** `html/patient.html`

Added a new "Book Appointment" tab to the patient dashboard with:

#### Appointment Booking Form
- Patient information (name, age, phone, ID, email)
- Department selection
- Date picker (restricted to future dates only)
- Dynamic time slot selection (loads available slots based on department and date)
- Service type and reason for visit
- Priority case selection
- Real-time validation

#### Features:
- **Smart Time Slot Loading:** Automatically loads available time slots when department and date are selected
- **Slot Availability Indicator:** Shows which time slots are available or fully booked
- **Minimum Date Validation:** Prevents booking appointments in the past
- **Auto-priority:** Automatically sets priority to "elderly" for patients 65+
- **Beautiful Confirmation Display:** Shows appointment details in an attractive card with print and copy options

#### Appointment Status Checker
- Check appointment status by entering appointment number
- Displays full appointment details including date, time, department, and current status
- Color-coded status indicators (pending, confirmed, completed, cancelled)

### 4. JavaScript Functions
**File:** `html/patient.html` (inline script)

Key functions added:
- `createAppointment()` - Creates a new appointment via API
- `getAvailableSlots()` - Fetches available time slots for a date/department
- `getAppointmentByNumber()` - Retrieves appointment details
- `displayAppointmentConfirmation()` - Shows success confirmation with appointment details
- `copyAppointmentNumber()` - Copies appointment number to clipboard
- `loadTimeSlots()` - Dynamically loads time slots based on selection

## How to Use

### For Patients:

1. **Book an Appointment:**
   - Navigate to the Patient Portal
   - Click on the "Book Appointment" tab
   - Fill in your personal details
   - Select the department you want to visit
   - Choose your preferred date (must be a future date)
   - Select an available time slot
   - Optionally add service type and reason for visit
   - Click "Book Appointment"
   - Save your appointment number for future reference

2. **Check Appointment Status:**
   - Scroll down to the "Check Appointment Status" section
   - Enter your appointment number
   - Click "Check Appointment"
   - View your appointment details and current status

### For Staff (Future Enhancement):
The API is ready for staff to:
- View all appointments for their department
- Confirm pending appointments
- Mark appointments as completed
- Cancel appointments if needed

## Appointment Number Format
Appointment numbers follow this pattern: `{DEPT}{YYYYMMDD}{SEQUENCE}`

Example: `OPD202410270001`
- `OPD` - Department code (first 3 letters)
- `20241027` - Date created (YYYYMMDD)
- `0001` - Sequence number for that day

## Status Flow
1. **Pending** - Initial status when appointment is created
2. **Confirmed** - Staff confirms the appointment
3. **Completed** - Patient attended and service was provided
4. **Cancelled** - Appointment was cancelled by patient or staff

## Time Slot Management
- **Operating Hours:** 8:00 AM to 4:00 PM
- **Slot Duration:** 30 minutes
- **Capacity:** Maximum 3 patients per time slot
- **Availability:** Real-time checking prevents overbooking

## Differences: Queue vs Appointment

### Queue Registration (Existing)
- For immediate/same-day service
- Walk-in patients
- First-come, first-served (with priority cases)
- Token-based system
- Managed in real-time by staff

### Appointment Booking (New)
- For future visits
- Scheduled in advance
- Time-slot based
- Appointment number system
- Reduces wait times and crowding

## Integration Points

### Database Tables:
- `appointments` - Main appointment data
- `appointment_history` - Audit trail
- `departments` - Shared with queue system
- `users` - Links to patient accounts (optional)

### Shared Components:
- Authentication system
- Department management
- Toast notifications
- UI styling and layout

## Future Enhancements

1. **Staff Dashboard Integration:**
   - View today's appointments
   - Confirm/reschedule appointments
   - Send appointment reminders

2. **Email/SMS Notifications:**
   - Appointment confirmation
   - Reminder 24 hours before
   - Status change notifications

3. **Calendar View:**
   - Visual calendar for selecting dates
   - Department availability overview

4. **Appointment Rescheduling:**
   - Allow patients to reschedule
   - Automatic slot availability checking

5. **Recurring Appointments:**
   - For patients with regular check-ups
   - Automatic booking of follow-ups

## Testing

### Test the Feature:
1. Ensure XAMPP is running
2. Run the database migration
3. Navigate to `http://localhost/queue%20system/html/patient.html`
4. Click "Book Appointment" tab
5. Try booking an appointment for tomorrow
6. Check the appointment status using the generated number

### API Testing:
```javascript
// Test available slots
fetch('http://localhost/queue%20system/php/api/appointments.php?action=available-slots&department=opd&date=2024-10-28')
  .then(r => r.json())
  .then(console.log);

// Test create appointment
fetch('http://localhost/queue%20system/php/api/appointments.php?action=create', {
  method: 'POST',
  headers: {'Content-Type': 'application/json'},
  body: JSON.stringify({
    patient_name: 'Test Patient',
    patient_phone: '+265999123456',
    department: 'opd',
    appointment_date: '2024-10-28',
    appointment_time: '09:00:00'
  })
}).then(r => r.json()).then(console.log);
```

## Files Modified/Created

### Created:
- `database/migration_add_appointments.sql` - Database schema
- `php/api/appointments.php` - Backend API
- `APPOINTMENT_FEATURE.md` - This documentation

### Modified:
- `html/patient.html` - Added appointment booking tab and functionality

## Notes
- The appointment system is independent of the queue system
- Patients can use both systems as needed
- No authentication required for booking (walk-in friendly)
- Optional user account linking for logged-in patients
- All appointment actions are logged in the history table for auditing
