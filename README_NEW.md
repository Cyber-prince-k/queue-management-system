# QECH Queue Management System

A comprehensive digital queue management system for Queen Elizabeth Central Hospital (QECH) in Blantyre, Malawi.

## 🎯 Overview

This system provides a complete solution for managing patient queues across multiple hospital departments. Patients can register online with their details and join queues, while staff can efficiently manage patient flow with real-time visibility and control.

## ✨ Key Features

### For Patients
- **Online Registration** - Fill comprehensive form with age, contact details, and service needed
- **Queue Token System** - Receive unique token number for tracking
- **Real-time Status** - Check queue position anytime
- **Auto-Priority** - Automatic priority for elderly patients (65+)
- **Department Selection** - Choose specific department to visit

### For Staff
- **Queue Dashboard** - Real-time statistics (waiting, serving, priority counts)
- **Patient Details** - View age, phone, service type for each patient
- **Queue Controls** - Pause/resume queues, call next patient
- **Patient Management** - Mark as attended, reassign to other departments
- **Auto-refresh** - Queue updates every 10 seconds
- **Action Logging** - All actions recorded in database

### For Administrators
- **User Management** - Create and manage staff/patient accounts
- **Department Management** - Configure departments and services
- **Audit Trail** - Complete history of all queue actions
- **Analytics Ready** - Comprehensive data for reporting

## 🚀 Quick Start

### Prerequisites
- XAMPP (Apache + MySQL + PHP)
- Modern web browser
- Basic knowledge of PHP/MySQL

### Installation

1. **Install XAMPP**
   - Download from [apachefriends.org](https://www.apachefriends.org/)
   - Install and start Apache + MySQL

2. **Setup Database**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Create new database: `qech_queue_system`
   - Import: `database/schema.sql`

3. **Configure System**
   - Edit `php/config.php` with your database credentials
   - Default settings work with standard XAMPP installation

4. **Access System**
   - Open browser: http://localhost/queue%20system/
   - Login with default admin account

### Default Login Credentials

**Admin Account:**
- Username: `admin`
- Password: `admin123`

**Create Additional Accounts:**
- Use admin portal to create staff and patient accounts

## 📁 Project Structure

```
queue system/
├── css/
│   └── style.css                 # Main stylesheet
├── database/
│   ├── schema.sql                # Database structure
│   └── migration_add_patient_fields.sql  # Update script
├── html/
│   ├── admin.html                # Admin portal
│   ├── display.html              # Public display
│   ├── patient.html              # Patient registration
│   ├── queues.html               # Queue viewer
│   └── staff.html                # Staff dashboard
├── js/
│   ├── auth.js                   # Authentication
│   ├── queue.js                  # Queue management
│   └── style.js                  # UI interactions
├── php/
│   ├── api/
│   │   ├── auth.php              # Login/logout API
│   │   ├── queue.php             # Queue operations API
│   │   └── users.php             # User management API
│   └── config.php                # Database configuration
├── index.html                    # Landing page
└── Documentation files (.md)
```

## 📚 Documentation

- **[FEATURE_SUMMARY.md](FEATURE_SUMMARY.md)** - Complete feature overview
- **[PATIENT_REGISTRATION_FEATURE.md](PATIENT_REGISTRATION_FEATURE.md)** - Patient registration details
- **[STAFF_CONTROLS_FEATURE.md](STAFF_CONTROLS_FEATURE.md)** - Staff control features
- **[SETUP_NEW_FEATURES.md](SETUP_NEW_FEATURES.md)** - Setup guide
- **[SQL_TESTING_QUERIES.md](SQL_TESTING_QUERIES.md)** - Database queries
- **[STAFF_CONTROLS_TEST_PLAN.md](STAFF_CONTROLS_TEST_PLAN.md)** - Testing guide

## 🎓 User Roles

### Patient
- Register and join queues
- Check queue status
- View token information

### Staff
- View department queues
- Call next patient
- Pause/resume queues
- Reassign patients
- Mark patients as attended

### Admin
- All staff permissions
- Create/manage users
- System configuration
- View all departments

## 🔐 Security Features

- Session-based authentication
- Role-based access control
- SQL injection protection (prepared statements)
- Password hashing (bcrypt)
- Input validation (client & server)
- Audit trail logging

## 🧪 Testing

### Manual Testing
1. **Patient Flow**
   - Register as patient
   - Fill form and submit
   - Verify token received
   - Check queue status

2. **Staff Flow**
   - Login as staff
   - View queue statistics
   - Call next patient
   - Test pause/resume
   - Reassign patient
   - Mark as attended

## 🐛 Troubleshooting

### Common Issues

**Issue: Cannot connect to database**
- Solution: Check XAMPP MySQL is running
- Verify config.php credentials

**Issue: Form submission fails**
- Solution: Check browser console for errors
- Verify all required fields filled

**Issue: Queue not updating**
- Solution: Check auto-refresh is enabled
- Verify API endpoints accessible
- Clear browser cache

## 🔄 Updating from Previous Version

If you have an existing installation:

1. **Backup Database**
2. **Run Migration** - `database/migration_add_patient_fields.sql`
3. **Clear Browser Cache**
4. **Verify Update**

## 🎉 Version History

**Version 2.0** (Current)
- Enhanced patient registration with age, address, service type
- Staff queue statistics dashboard
- Pause/resume queue functionality
- Patient reassignment feature
- Mark as attended feature
- Comprehensive action logging

**Version 1.0**
- Basic queue management
- Patient registration
- Token system

---

**Developed for:** Queen Elizabeth Central Hospital  
**Location:** Blantyre, Malawi  
**System Status:** ✅ Production Ready
