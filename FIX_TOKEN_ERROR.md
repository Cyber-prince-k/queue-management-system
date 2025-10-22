# Fix: "createQueueToken is not defined" Error

## Problem
When patient submits the registration form, error appears:
```
An error occurred: createQueueToken is not defined. 
Please check the console for details.
```

## Root Cause
The form submission handler was trying to call `createQueueToken()` before the `queue.js` script finished loading.

## Solution Applied

### Changed in `patient.html`:

**Before:**
```javascript
document.addEventListener('DOMContentLoaded', function() {
  // Form handler here
});
```

**After:**
```javascript
window.addEventListener('load', function() {
  // Wait for ALL scripts to load first
  
  // Check if createQueueToken is available
  if (typeof createQueueToken !== 'function') {
    console.error('createQueueToken function not found!');
    alert('System error: Please refresh the page.');
    return;
  }
  
  // Form handler here
});
```

### Key Changes:

1. ✅ Changed from `DOMContentLoaded` to `window.load` event
   - `DOMContentLoaded` fires when HTML is parsed
   - `window.load` fires when ALL resources (including scripts) are loaded

2. ✅ Added function availability check
   - Verifies `createQueueToken` exists before using it
   - Shows clear error if function is missing

3. ✅ Added script error handlers
   - `<script src="../js/queue.js" onerror="console.error('Failed to load queue.js')"></script>`
   - Logs errors if scripts fail to load

4. ✅ Added detailed console logging
   - Shows which functions are available
   - Helps diagnose loading issues

## How to Test

### Option 1: Use Diagnostic Page
```
http://localhost/queue%20system/tests/diagnose_scripts.html
```
This will show:
- ✓ Which scripts loaded successfully
- ✓ Which functions are available
- ✓ Test token creation button

### Option 2: Test Patient Page
1. Open: `http://localhost/queue%20system/html/patient.html`
2. Open browser console (F12)
3. Look for these messages:
   ```
   Page fully loaded
   createQueueToken available: function
   showToast available: function
   ✓ All required functions loaded successfully
   ```
4. Fill form and submit
5. Should work without errors

### Option 3: Manual Console Check
1. Open patient page
2. Open console (F12)
3. Type: `typeof createQueueToken`
4. Should show: `"function"`
5. If shows `"undefined"`, queue.js didn't load

## Common Issues & Solutions

### Issue 1: Scripts still not loading
**Symptoms:** Console shows "Failed to load queue.js"  
**Solutions:**
- Check file exists at: `c:\Users\princ\Music\queue system\js\queue.js`
- Check XAMPP is running
- Clear browser cache (Ctrl+Shift+Delete)
- Try different browser

### Issue 2: Function exists but still errors
**Symptoms:** Console shows `createQueueToken available: function` but form still fails  
**Solutions:**
- Check for JavaScript errors in queue.js
- Open queue.js and look for syntax errors
- Check browser console for other errors

### Issue 3: API connection fails
**Symptoms:** Token creation starts but fails with network error  
**Solutions:**
- Verify XAMPP Apache is running
- Check: `http://localhost/queue%20system/php/api/queue.php?action=status`
- Verify database exists and is accessible

## Verification Steps

After applying the fix:

1. ✅ **Refresh patient page** (Ctrl+F5 to clear cache)

2. ✅ **Open console** (F12) and check for:
   ```
   Page fully loaded
   createQueueToken available: function
   ✓ All required functions loaded successfully
   ```

3. ✅ **Fill form** with test data:
   - Name: Test Patient
   - Age: 30
   - Phone: +265 999 123 456
   - ID: TEST123
   - Department: OPD

4. ✅ **Submit form** and check for:
   - No "createQueueToken is not defined" error
   - Token display appears
   - Large token number shown

5. ✅ **Check database** (phpMyAdmin):
   - Open `qech_queue_system` database
   - Check `queue_tokens` table
   - New row should exist with patient data

## Files Modified

- ✅ `html/patient.html` - Updated script loading and form handler
- ✅ `tests/diagnose_scripts.html` - Created diagnostic tool

## Prevention

To prevent this issue in future pages:

1. Always use `window.addEventListener('load', ...)` when calling functions from external scripts
2. Always check if function exists before calling: `if (typeof funcName === 'function')`
3. Add `onerror` handlers to script tags
4. Add console logging to track script loading

## Still Having Issues?

1. Run diagnostic page: `tests/diagnose_scripts.html`
2. Check browser console for specific errors
3. Verify all files exist in correct locations
4. Clear browser cache completely
5. Try in incognito/private mode
6. Try different browser

## Success Indicators

✅ No console errors  
✅ "All required functions loaded successfully" message  
✅ Form submits without errors  
✅ Token display appears  
✅ Token saved in database  
✅ Staff can see the token  

---

**The fix has been applied. Please refresh the patient page and try again!**
