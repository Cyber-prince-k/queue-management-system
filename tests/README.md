# Test Files

This folder contains test and debugging tools for the QECH Queue Management System.

## Files:

### 1. `test_complete_flow.html`
**Purpose:** Complete end-to-end system test  
**URL:** `http://localhost/queue%20system/tests/test_complete_flow.html`  
**Features:**
- System status check (Server, Database, API)
- Create test tokens
- View queue (staff view)
- View all departments
- Real-time console logging

**Use this to:**
- Verify the entire system is working
- Test token creation and queue display
- Debug API connectivity issues

---

### 2. `test_api.html`
**Purpose:** API endpoint testing  
**URL:** `http://localhost/queue%20system/tests/test_api.html`  
**Features:**
- Test server connection
- Test database connection
- Create test token
- View all tokens

**Use this to:**
- Check if API endpoints are responding
- Verify database connectivity
- Test individual API calls

---

### 3. `debug_patient.html`
**Purpose:** Patient form debugging  
**URL:** `http://localhost/queue%20system/tests/debug_patient.html`  
**Features:**
- Patient registration form with pre-filled test data
- Real-time console logging
- Shows exact API requests and responses
- Displays raw JSON responses

**Use this to:**
- Debug patient registration issues
- See exact API request/response data
- Test form validation
- Identify specific error messages

---

## How to Use:

1. **Make sure XAMPP is running** (Apache + MySQL)
2. **Open any test file** in your browser
3. **Follow the on-screen instructions**
4. **Check the console logs** for detailed information

## Important Notes:

- These are **test files only** - not part of the production system
- They use the same API as the real system
- Data created here will appear in the real database
- Use these for debugging and testing only

## Troubleshooting:

If tests fail:
1. Check XAMPP is running
2. Verify database exists (`qech_queue_system`)
3. Check browser console (F12) for errors
4. Refer to `TROUBLESHOOTING_GUIDE.md` in the root folder
