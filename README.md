# 🏥 QECH Digital Queue Management System

A comprehensive digital queue management system for Queen Elizabeth Central Hospital (QECH) in Blantyre, Malawi.

![License](https://img.shields.io/badge/license-MIT-blue.svg)
![PHP](https://img.shields.io/badge/PHP-7.4+-blue.svg)
![MySQL](https://img.shields.io/badge/MySQL-5.7+-orange.svg)
![JavaScript](https://img.shields.io/badge/JavaScript-ES6+-yellow.svg)

## 📋 Overview

This system provides a complete solution for managing patient queues across multiple hospital departments. It features:

- **Patient Self-Registration**: Patients can join queues without staff assistance
- **Real-Time Queue Management**: Staff can call, manage, and track patients efficiently
- **Role-Based Access Control**: Separate interfaces for patients, staff, and administrators
- **Token System**: Unique queue numbers for each patient
- **Priority Handling**: Emergency, elderly, pregnant, and disabled patients get priority
- **Multi-Department Support**: OPD, Maternity, Emergency, and Pediatrics
- **Multilingual Support**: English and Chichewa (Chinyanja)

## ✨ Features

### For Patients
- ✅ Register and join queue without login
- ✅ Receive unique token number with queue position
- ✅ Check queue status anytime
- ✅ Print or copy token number
- ✅ Priority service for eligible cases

### For Staff
- ✅ View real-time queue for their department
- ✅ Call next patient automatically (priority-first)
- ✅ Mark patients as attended
- ✅ Reassign patients to different departments
- ✅ Pause/resume queue operations
- ✅ Auto-refresh queue display

### For Administrators
- ✅ Access all department queues
- ✅ View system statistics
- ✅ Manage user accounts
- ✅ Reset queues
- ✅ Generate reports

## 🚀 Installation

### Prerequisites
- **XAMPP** (Apache + MySQL + PHP 7.4+)
- **Web Browser** (Chrome, Firefox, Edge)
- **Git** (for cloning)

### Step 1: Clone the Repository
```bash
git clone https://github.com/Cyber-prince-k/queue-management-system.git
cd queue-management-system
```

### Step 2: Set Up Database
1. Start XAMPP (Apache + MySQL)
2. Open phpMyAdmin: `http://localhost/phpmyadmin`
3. Create database: `queue_management_db`
4. Import schema: `database/schema.sql`
5. (Optional) Import sample data: `database/sample_data.sql`

### Step 3: Configure Database Connection
Edit `php/config.php`:
```php
$host = 'localhost';
$dbname = 'queue_management_db';
$username = 'root';
$password = ''; // Your MySQL password
```

### Step 4: Move to Web Directory
Copy the project folder to XAMPP's htdocs:
```bash
# Windows
xcopy /E /I "queue-management-system" "C:\xampp\htdocs\queue system"

# Or manually copy to: C:\xampp\htdocs\
```

### Step 5: Access the System
Open your browser and navigate to:
```
http://localhost/queue%20system/index.html
```

## 📁 Project Structure

```
queue-management-system/
├── css/
│   └── style.css              # Main stylesheet
├── js/
│   ├── auth.js                # Authentication functions
│   ├── queue.js               # Queue management functions
│   └── style.js               # UI and navigation
├── html/
│   ├── patient.html           # Patient registration & status
│   ├── staff.html             # Staff queue management
│   ├── admin.html             # Admin dashboard
│   ├── login.html             # Login page
│   ├── register.html          # User registration
│   ├── profile.html           # User profile
│   ├── display.html           # Public queue display
│   └── queues.html            # All queues overview
├── php/
│   ├── config.php             # Database configuration
│   └── api/
│       ├── auth.php           # Authentication API
│       └── queue.php          # Queue management API
├── database/
│   ├── schema.sql             # Database schema
│   └── sample_data.sql        # Sample data (optional)
├── images/                    # Image assets
├── tests/                     # Test files
├── docs/                      # Documentation
│   ├── QUEUE_TOKEN_SYSTEM.md
│   ├── ACCESS_CONTROL.md
│   └── SESSION_FIX.md
├── index.html                 # Landing page
└── README.md                  # This file
```

## 🔐 Default Login Credentials

### Admin Account
- **Username**: `admin`
- **Password**: `admin123`

### Staff Account
- **Username**: `staff1`
- **Password**: `staff123`

### Patient Account
- **Username**: `patient1`
- **Password**: `patient123`

**⚠️ Important**: Change these passwords in production!

## 🎯 Usage

### Patient Registration Flow
1. Navigate to **Patient Portal**
2. Fill in registration form:
   - Full Name
   - Age
   - Phone Number
   - ID Number
   - Department
   - Priority (if applicable)
3. Click **Submit & Join Queue**
4. Receive token number (e.g., `OPD-20251022-0001`)
5. Save token for status checking

### Staff Queue Management
1. Login with staff credentials
2. Navigate to **Staff Portal**
3. Select department
4. View waiting patients
5. Click **Call Next** to serve next patient
6. Mark patient as **Attended** when done

### Admin Operations
1. Login with admin credentials
2. Navigate to **Admin Portal**
3. View all department statistics
4. Manage queues and users
5. Generate reports

## 🛠️ API Endpoints

### Authentication
- `POST /php/api/auth.php?action=login` - User login
- `POST /php/api/auth.php?action=register` - User registration
- `POST /php/api/auth.php?action=logout` - User logout
- `GET /php/api/auth.php?action=check` - Check session

### Queue Management
- `POST /php/api/queue.php?action=create` - Create queue token
- `GET /php/api/queue.php?action=status` - Get queue status
- `POST /php/api/queue.php?action=call_next` - Call next patient
- `POST /php/api/queue.php?action=complete` - Complete token
- `POST /php/api/queue.php?action=pause_queue` - Pause queue
- `POST /php/api/queue.php?action=resume_queue` - Resume queue
- `POST /php/api/queue.php?action=reassign` - Reassign patient
- `POST /php/api/queue.php?action=mark_attended` - Mark as attended

## 🔒 Security Features

- ✅ Role-based access control (RBAC)
- ✅ Session management with localStorage
- ✅ SQL injection prevention (prepared statements)
- ✅ XSS protection
- ✅ CSRF token validation
- ✅ Password hashing (bcrypt)
- ✅ Input validation and sanitization

## 🌐 Multilingual Support

The system supports:
- **English** (Default)
- **Chichewa/Chinyanja** (Local language)

Change language using the selector in the header.

## 📱 Responsive Design

The system is fully responsive and works on:
- 💻 Desktop computers
- 📱 Tablets
- 📱 Mobile phones

## 🧪 Testing

Test files are available in the `tests/` directory:
- `test_api.html` - API endpoint testing
- `test_complete_flow.html` - End-to-end flow testing
- `debug_patient.html` - Patient registration debugging

## 📝 Documentation

Additional documentation available in `docs/`:
- **QUEUE_TOKEN_SYSTEM.md** - Token generation system
- **ACCESS_CONTROL.md** - Role-based access control
- **SESSION_FIX.md** - Session management fixes
- **FIX_APPLIED.md** - Recent bug fixes

## 🤝 Contributing

Contributions are welcome! Please:
1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Open a Pull Request

## 📄 License

This project is licensed under the MIT License.

## 👨‍💻 Author

**Prince Adams Kamanga**
- GitHub: [@Cyber-prince-k](https://github.com/Cyber-prince-k)
- Email: princekamnga1@gmail.com

## 🙏 Acknowledgments

- Queen Elizabeth Central Hospital (QECH), Blantyre, Malawi
- NACIT Advanced Diploma in Computing Program
- All contributors and testers

## 📞 Support

For issues, questions, or suggestions:
- Open an issue on GitHub
- Email: princekamnga1@gmail.com

---

**Made with ❤️ for better healthcare in Malawi**
