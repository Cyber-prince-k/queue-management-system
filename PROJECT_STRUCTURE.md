# QECH Queue Management System - Project Structure

## 📁 Folder Organization

```
queue system/
│
├── index.html                          # Homepage (ONLY HTML file in root)
│
├── html/                               # All application pages
│   ├── admin.html                      # Admin dashboard
│   ├── display.html                    # Public display screen
│   ├── login.html                      # Login page
│   ├── patient.html                    # Patient registration & status
│   ├── profile.html                    # User profile page
│   ├── queues.html                     # Queue overview
│   ├── register.html                   # User registration
│   └── staff.html                      # Staff queue management
│
├── css/                                # Stylesheets
│   └── style.css                       # Main stylesheet
│
├── js/                                 # JavaScript files
│   ├── auth.js                         # Authentication (login, register, logout)
│   ├── queue.js                        # Queue operations (REAL database API)
│   └── style.js                        # UI/UX helpers (toast, modals, language)
│
├── php/                                # Backend PHP files
│   ├── api/                            # API endpoints
│   │   ├── auth.php                    # Authentication API
│   │   └── queue.php                   # Queue management API
│   └── config.php                      # Database configuration
│
├── database/                           # Database files
│   ├── schema.sql                      # Complete database setup
│   ├── migration_add_patient_fields.sql # Database migration
│   └── fix_database.sql                # Quick database fix
│
├── tests/                              # Test & debug tools
│   ├── test_complete_flow.html         # Complete system test
│   ├── test_api.html                   # API endpoint tester
│   ├── debug_patient.html              # Patient form debugger
│   └── README.md                       # Test files documentation
│
├── TROUBLESHOOTING_GUIDE.md            # Common issues & solutions
├── CHANGES_REAL_DATA_ONLY.md           # Real data implementation notes
└── PROJECT_STRUCTURE.md                # This file

```

---

## 📄 File Purposes

### Root Level
- **`index.html`** - Homepage/landing page (ONLY HTML file allowed in root)

### HTML Pages (`html/`)
All application pages are organized here:
- **Patient Portal:** `patient.html` - Register for queue, check status
- **Staff Portal:** `staff.html` - Manage queues, call patients
- **Admin Portal:** `admin.html` - View statistics, manage system
- **Authentication:** `login.html`, `register.html`, `profile.html`
- **Public Display:** `display.html` - TV screen showing current queue
- **Queue Overview:** `queues.html` - View all department queues

### JavaScript (`js/`)
- **`queue.js`** - All queue operations using REAL database
  - `createQueueToken()` - Create new token
  - `getQueueStatus()` - Get token status
  - `callNextPatient()` - Call next in queue
  - `refreshQueueDisplay()` - Update queue display
  - `pauseQueue()`, `resumeQueue()` - Queue control
  
- **`auth.js`** - User authentication
  - `login()`, `register()`, `logout()`
  - `getCurrentUser()`, `requireRole()`
  - Session management
  
- **`style.js`** - UI/UX helpers ONLY (no data)
  - `showToast()` - Show notifications
  - `showConfirm()` - Confirmation dialogs
  - Language/translation support
  - Tab switching, navigation

### PHP Backend (`php/`)
- **`config.php`** - Database connection settings
- **`api/auth.php`** - Authentication endpoints
  - POST `/login` - User login
  - POST `/register` - User registration
  - POST `/logout` - User logout
  - GET `/session` - Get current session
  
- **`api/queue.php`** - Queue management endpoints
  - POST `?action=create` - Create token
  - GET `?action=status` - Get queue status
  - POST `?action=call_next` - Call next patient
  - POST `?action=complete` - Mark patient complete
  - POST `?action=pause` - Pause queue
  - POST `?action=resume` - Resume queue

### Database (`database/`)
- **`schema.sql`** - Complete database setup (run this first)
- **`migration_add_patient_fields.sql`** - Add patient fields to existing DB
- **`fix_database.sql`** - Quick fix for missing columns

### Tests (`tests/`)
- **`test_complete_flow.html`** - Full system test
- **`test_api.html`** - API endpoint tester
- **`debug_patient.html`** - Patient form debugger
- **`README.md`** - Test documentation

---

## 🔄 Data Flow

### Patient Registration:
```
patient.html → createQueueToken() → php/api/queue.php → MySQL → Token returned
```

### Staff Queue View:
```
staff.html → refreshQueueDisplay() → php/api/queue.php → MySQL → Queue displayed
```

### Authentication:
```
login.html → login() → php/api/auth.php → MySQL → Session created
```

---

## 🎯 Key Principles

1. **Only `index.html` in root** - All other HTML files in `html/` folder
2. **No mock data** - All data from real MySQL database
3. **API-driven** - All operations go through PHP API
4. **Separation of concerns:**
   - `queue.js` = Data operations
   - `auth.js` = Authentication
   - `style.js` = UI/UX only
5. **Test files separate** - All test tools in `tests/` folder

---

## 🚀 Quick Start

1. **Setup Database:**
   ```sql
   -- In phpMyAdmin, run:
   database/schema.sql
   ```

2. **Configure Database:**
   ```php
   // Edit php/config.php with your credentials
   ```

3. **Start XAMPP:**
   - Start Apache
   - Start MySQL

4. **Access System:**
   - Homepage: `http://localhost/queue%20system/`
   - Patient: `http://localhost/queue%20system/html/patient.html`
   - Staff: `http://localhost/queue%20system/html/staff.html`
   - Tests: `http://localhost/queue%20system/tests/test_complete_flow.html`

---

## 📝 Naming Conventions

- **HTML files:** lowercase with hyphens (e.g., `patient.html`)
- **CSS files:** lowercase with hyphens (e.g., `style.css`)
- **JS files:** lowercase with hyphens (e.g., `queue.js`)
- **PHP files:** lowercase with hyphens (e.g., `queue.php`)
- **Functions:** camelCase (e.g., `createQueueToken()`)
- **Database tables:** snake_case (e.g., `queue_tokens`)

---

## 🔒 Security Notes

- All user inputs are sanitized in PHP
- SQL injection prevention using prepared statements
- Password hashing with `password_hash()`
- Session-based authentication
- Role-based access control (patient, staff, admin)

---

## 📚 Documentation Files

- **`TROUBLESHOOTING_GUIDE.md`** - Common problems and solutions
- **`CHANGES_REAL_DATA_ONLY.md`** - Explanation of real data implementation
- **`PROJECT_STRUCTURE.md`** - This file
- **`tests/README.md`** - Test files documentation
