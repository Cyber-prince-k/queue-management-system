# Testing Token Display - Troubleshooting Guide

## What I Fixed

### 1. **Added Detailed Console Logging**
The system now logs every step of the token creation process:
- When `createQueueToken()` is called
- Response status from server
- Token data received
- When `displayTokenDetails()` is called
- When token display element is found/not found
- When token is displayed and scrolled into view

### 2. **Added Copy & Print Buttons**
- **Print Token**: Prints only the token card
- **Copy Number**: Copies token number to clipboard

### 3. **Improved Error Handling**
- Better error messages if token creation fails
- Alerts if token display element is missing
- Fallback copy method for older browsers

### 4. **Better Visual Feedback**
- Success toast/alert when token is created
- Smooth scrolling to token display
- Token card is more visible

---

## How to Test

### Step 1: Open Browser Console
1. Open patient.html in your browser
2. Press **F12** to open Developer Tools
3. Go to **Console** tab

### Step 2: Fill Out the Form
Fill in the patient registration form:
- **Name**: Test Patient
- **Age**: 30
- **Phone**: +265 999 123 456
- **ID Number**: 12345678
- **Department**: Select any (e.g., OPD)
- Leave other fields optional

### Step 3: Submit and Watch Console
Click **"Submit & Join Queue"** and watch the console output.

You should see:
```
=== FORM SUBMITTED ===
Form submitted - starting patient registration...
Patient data: {patient_name: "Test Patient", ...}
Calling createQueueToken...
createQueueToken called with: {patient_name: "Test Patient", ...}
Response status: 200
Response data: {success: true, token: {...}}
Token data: {id: 1, token_number: "OPD-20251022-0001", queue_position: 1}
✓ Token created, calling displayTokenDetails...
displayTokenDetails called with: {id: 1, token_number: "OPD-20251022-0001", ...}
✓ token-display element found
✓ Token display updated and made visible
✓ displayTokenDetails called
Token created successfully: {id: 1, token_number: "OPD-20251022-0001", ...}
✓ Scrolled to token display
```

### Step 4: Check for Token Display
After submission, you should see a **purple gradient card** with:
- 🎫 Icon
- "Token Created Successfully!" heading
- **Token Number** in large blue text (e.g., `OPD-20251022-0001`)
- **Queue Position** (e.g., `#1`)
- Warning message to save the token
- **Print Token** button (white)
- **Copy Number** button (transparent with white border)

---

## Common Issues & Solutions

### Issue 1: "❌ token-display element not found!"
**Cause**: The `<div id="token-display">` is missing from the HTML

**Solution**: Check that line 123 in patient.html has:
```html
<div id="token-display" style="margin-top: 1.5rem;"></div>
```

### Issue 2: "❌ Token creation failed or no token in response"
**Cause**: Backend API is not returning the token properly

**Check**:
1. Is XAMPP running?
2. Is Apache started?
3. Is MySQL started?
4. Check the console for the actual response data

**Solution**: 
- Start XAMPP
- Check `php/api/queue.php` is accessible
- Check database connection in `php/config.php`

### Issue 3: Token displays but doesn't scroll into view
**Cause**: Timing issue with scrolling

**Solution**: Already fixed with `setTimeout()` - should work now

### Issue 4: Form submits but nothing happens
**Cause**: JavaScript error or API connection issue

**Check Console For**:
- `Failed to fetch` → XAMPP not running
- `HTTP error! status: 404` → API file not found
- `HTTP error! status: 500` → PHP/Database error

---

## Expected Console Output (Success)

```
=== PAGE LOAD DEBUG ===
createQueueToken: function
showToast: function
requireRole: undefined
✓ Form found, attaching handler...
✓ Inline queue functions loaded

[User fills form and clicks submit]

=== FORM SUBMITTED ===
Form submitted - starting patient registration...
Patient data: {
  patient_name: "John Doe",
  patient_age: 30,
  patient_phone: "+265 999 123 456",
  patient_id_number: "12345678",
  patient_address: "",
  service_type: "",
  department: "opd",
  priority_type: "no"
}
Calling createQueueToken...
createQueueToken called with: {patient_name: "John Doe", ...}
Response status: 200
Response data: {
  success: true,
  message: "Token created successfully",
  token: {
    id: 1,
    token_number: "OPD-20251022-0001",
    queue_position: 1
  }
}
Token data: {id: 1, token_number: "OPD-20251022-0001", queue_position: 1}
✓ Token created, calling displayTokenDetails...
displayTokenDetails called with: {id: 1, token_number: "OPD-20251022-0001", queue_position: 1}
✓ token-display element found
✓ Token display updated and made visible
✓ displayTokenDetails called
Token created successfully: {id: 1, token_number: "OPD-20251022-0001", queue_position: 1}
Scrolling to token display
✓ Scrolled to token display
Re-enabling submit button
```

---

## Expected Console Output (Error - XAMPP Not Running)

```
=== FORM SUBMITTED ===
Form submitted - starting patient registration...
Patient data: {...}
Calling createQueueToken...
createQueueToken called with: {...}
Create token error: TypeError: Failed to fetch
Result from createQueueToken: {success: false, message: "Failed to fetch"}
Token creation failed: {success: false, message: "Failed to fetch"}
An error occurred: Failed to fetch. Please check the console for details.
Re-enabling submit button
```

---

## Visual Checklist

After submitting the form, you should see:

✅ Form clears (all fields reset)
✅ Button changes from "⏳ Creating Token..." back to "📝 Submit & Join Queue"
✅ Success toast/alert appears
✅ Purple gradient card appears below the form
✅ Token number is displayed in large blue text
✅ Queue position is shown (e.g., "#1")
✅ Two buttons appear: "🖨️ Print Token" and "📋 Copy Number"
✅ Page scrolls smoothly to show the token card

---

## Quick Test Checklist

1. ✅ XAMPP is running (Apache + MySQL)
2. ✅ Browser console is open (F12)
3. ✅ Navigate to: `http://localhost/queue%20system/html/patient.html`
4. ✅ Fill out the form with test data
5. ✅ Click "Submit & Join Queue"
6. ✅ Watch console for logs
7. ✅ Look for purple token card below form
8. ✅ Verify token number is displayed
9. ✅ Try clicking "Copy Number" button
10. ✅ Try clicking "Print Token" button

---

## What to Report Back

If it's still not working, please share:

1. **Console output** (copy all the logs)
2. **Any error messages** (red text in console)
3. **Screenshot** of what you see after submitting
4. **XAMPP status** (is it running?)
5. **Browser** you're using (Chrome, Firefox, etc.)

This will help me identify exactly where the issue is!
