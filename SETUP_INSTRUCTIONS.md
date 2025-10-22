# QECH Queue Management System - Setup Instructions

## Prerequisites
- XAMPP (Apache + MySQL + PHP)
- Web browser (Chrome, Firefox, Edge)

## Installation Steps

### 1. Install XAMPP
1. Download XAMPP from https://www.apachefriends.org/
2. Install XAMPP to `C:\xampp` (default location)
3. Start Apache and MySQL from XAMPP Control Panel

### 2. Setup Database
1. Open phpMyAdmin: http://localhost/phpmyadmin
2. Click "Import" tab
3. Choose file: `database/schema.sql`
4. Click "Go" to import the database

**Default Admin Credentials:**
- Username: `admin`
- Password: `admin123`

### 3. Configure Project
1. Copy the entire `queue system` folder to `C:\xampp\htdocs\`
2. The project should be at: `C:\xampp\htdocs\queue system\`

### 4. Access the Application
Open your browser and go to:
- **Main Site:** http://localhost/queue%20system/index.html
- **Patient Portal:** http://localhost/queue%20system/html/patient.html
- **Staff Portal:** http://localhost/queue%20system/html/staff.html
- **Admin Portal:** http://localhost/queue%20system/html/admin.html

## Database Structure

### Tables Created:
- **users** - User accounts (patients, staff, admin)
- **departments** - Hospital departments (OPD, Maternity, Emergency, Pediatrics)
- **queue_tokens** - Queue tickets/tokens
- **staff_assignments** - Staff-to-department assignments
- **queue_history** - Audit trail of queue actions

### Default Departments:
- OPD (Outpatient Department)
- Maternity
- Emergency
- Pediatrics

## API Endpoints

### Authentication (`php/api/auth.php`)
- **POST** `?action=login` - User login
- **POST** `?action=register` - User registration
- **POST** `?action=logout` - User logout
- **GET** `?action=check` - Check session status

### Queue Management (`php/api/queue.php`)
- **POST** `?action=create` - Create new queue token
- **GET** `?action=status&department={code}` - Get queue status
- **POST** `?action=call_next&department={code}` - Call next patient
- **POST** `?action=complete&token_id={id}` - Complete token

## Troubleshooting

### Database Connection Error
- Make sure MySQL is running in XAMPP
- Check database name is `qech_queue_system`
- Verify credentials in `php/config.php`

### 404 Not Found
- Ensure project is in `C:\xampp\htdocs\queue system\`
- Check Apache is running in XAMPP
- Use correct URL with `%20` for spaces

### CORS Errors
- Already configured in `php/config.php`
- If issues persist, check browser console

## Next Steps
1. Update JavaScript files to use PHP API endpoints
2. Test authentication flow
3. Test queue creation and management
4. Customize as needed

## Security Notes
- Change default admin password after first login
- In production, use environment variables for database credentials
- Enable HTTPS for production deployment
- Implement rate limiting for API endpoints
