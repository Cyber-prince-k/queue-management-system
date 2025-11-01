<?php
// Appointments API for QECH Queue System
// NOTE: Headers and session are initialized in ../config.php
require_once '../config.php';

// Initialize a global DB connection for all functions in this file
$conn = getDBConnection();

// Get action from query string
$action = $_GET['action'] ?? '';

// Handle different actions
switch ($action) {
    case 'create':
        createAppointment();
        break;
    case 'list':
        listAppointments();
        break;
    case 'get':
        getAppointment();
        break;
    case 'update':
        updateAppointment();
        break;
    case 'cancel':
        cancelAppointment();
        break;
    case 'available-slots':
        getAvailableSlots();
        break;
    case 'my-appointments':
        getMyAppointments();
        break;
    case 'promote-due':
        promoteDueAppointments();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

// Create new appointment
function createAppointment() {
    global $conn;
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $required = ['patient_name', 'patient_phone', 'department', 'appointment_date', 'appointment_time'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
            return;
        }
    }
    
    // Validate appointment date is in the future
    $appointmentDateTime = $data['appointment_date'] . ' ' . $data['appointment_time'];
    if (strtotime($appointmentDateTime) < time()) {
        echo json_encode(['success' => false, 'message' => 'Appointment date and time must be in the future']);
        return;
    }
    
    // Get department ID
    $dept_code = $data['department'];
    $dept_query = "SELECT id FROM departments WHERE code = ?";
    $stmt = $conn->prepare($dept_query);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'DB error (dept lookup): ' . $conn->error]);
        return;
    }
    $stmt->bind_param('s', $dept_code);
    $stmt->execute();
    $dept_result = $stmt->get_result();
    
    if ($dept_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid department']);
        return;
    }
    
    $department_id = $dept_result->fetch_assoc()['id'];
    
    // Check if slot is available
    $slot_check = "SELECT COUNT(*) as count FROM appointments 
                   WHERE department_id = ? 
                   AND appointment_date = ? 
                   AND appointment_time = ? 
                   AND status != 'cancelled'";
    $stmt = $conn->prepare($slot_check);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'DB error (slot check): ' . $conn->error]);
        return;
    }
    $stmt->bind_param('iss', $department_id, $data['appointment_date'], $data['appointment_time']);
    $stmt->execute();
    $slot_result = $stmt->get_result()->fetch_assoc();
    
    if ($slot_result['count'] >= 3) { // Max 3 appointments per slot
        echo json_encode(['success' => false, 'message' => 'This time slot is fully booked. Please choose another time.']);
        return;
    }
    
    // Generate appointment number
    $appointment_number = generateAppointmentNumber($dept_code);
    
    // Get patient ID if logged in
    $patient_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    
    // Insert appointment
    $query = "INSERT INTO appointments 
              (appointment_number, patient_id, patient_name, patient_age, patient_phone, 
               patient_id_number, patient_email, department_id, appointment_date, 
               appointment_time, service_type, reason, priority_type, created_at, updated_at) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'DB error (insert appointment): ' . $conn->error]);
        return;
    }
    // Prepare variables for bind_param (must be variables, not expressions)
    $bp_patient_name = $data['patient_name'];
    $bp_patient_age = isset($data['patient_age']) && $data['patient_age'] !== '' ? (int)$data['patient_age'] : null;
    $bp_patient_phone = $data['patient_phone'];
    $bp_patient_id_number = $data['patient_id_number'] ?? null;
    $bp_patient_email = $data['patient_email'] ?? null;
    $bp_appt_date = $data['appointment_date'];
    $bp_appt_time = $data['appointment_time'];
    $bp_service_type = $data['service_type'] ?? null;
    $bp_reason = $data['reason'] ?? null;
    $bp_priority = $data['priority_type'] ?? 'no';

    $stmt->bind_param(
        'sisisssisssss',
        $appointment_number,   // s
        $patient_id,           // i
        $bp_patient_name,      // s
        $bp_patient_age,       // i
        $bp_patient_phone,     // s
        $bp_patient_id_number, // s
        $bp_patient_email,     // s
        $department_id,        // i
        $bp_appt_date,         // s
        $bp_appt_time,         // s
        $bp_service_type,      // s
        $bp_reason,            // s
        $bp_priority           // s
    );
    
    if ($stmt->execute()) {
        $appointment_id = $conn->insert_id;
        
        // Log to history (best-effort)
        $history_query = "INSERT INTO appointment_history (appointment_id, action, performed_by) 
                         VALUES (?, 'created', ?)";
        if ($history_stmt = $conn->prepare($history_query)) {
            $history_stmt->bind_param('ii', $appointment_id, $patient_id);
            @$history_stmt->execute();
        }
        
        // Get full appointment details
        $appointment = getAppointmentById($appointment_id);
        
        echo json_encode([
            'success' => true,
            'message' => 'Appointment created successfully',
            'appointment' => $appointment
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create appointment: ' . ($stmt->error ?: $conn->error)]);
    }
}

// Generate unique appointment number
function generateAppointmentNumber($dept_code) {
    global $conn;
    
    $prefix = strtoupper(substr($dept_code, 0, 3));
    $date = date('Ymd');
    
    // Get count of appointments already created today for this department
    // Count purely by the number prefix to avoid relying on a created_at column
    $query = "SELECT COUNT(*) as count FROM appointments 
              WHERE appointment_number LIKE ?";
    $pattern = $prefix . $date . '%';
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $pattern);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    $sequence = str_pad($result['count'] + 1, 4, '0', STR_PAD_LEFT);
    
    return $prefix . $date . $sequence;
}

// List all appointments (with filters)
function listAppointments() {
    global $conn;
    
    $department = $_GET['department'] ?? null;
    $date = $_GET['date'] ?? null;
    $status = $_GET['status'] ?? null;
    
    $query = "SELECT a.*, d.name as department_name, d.code as department_code 
              FROM appointments a 
              JOIN departments d ON a.department_id = d.id 
              WHERE 1=1";
    
    $params = [];
    $types = '';
    
    if ($department) {
        $query .= " AND d.code = ?";
        $params[] = $department;
        $types .= 's';
    }
    
    if ($date) {
        $query .= " AND a.appointment_date = ?";
        $params[] = $date;
        $types .= 's';
    }
    
    if ($status) {
        $query .= " AND a.status = ?";
        $params[] = $status;
        $types .= 's';
    }
    
    $query .= " ORDER BY a.appointment_date ASC, a.appointment_time ASC";
    
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $appointments = [];
    while ($row = $result->fetch_assoc()) {
        $appointments[] = $row;
    }
    
    echo json_encode(['success' => true, 'appointments' => $appointments]);
}

// Promote due appointments into the live queue as tokens
function promoteDueAppointments() {
    global $conn;
    
    // Create connection if not present (fallback for environments where $conn isn't global)
    if (!isset($conn) || !$conn) {
        if (function_exists('getDBConnection')) {
            $conn = getDBConnection();
        }
    }
    
    $department = $_GET['department'] ?? null;
    
    // Build base query: appointments due now or in next 15 minutes, not cancelled/completed, and not already queued
    $query = "SELECT a.*, d.id AS department_id, d.code AS department_code
              FROM appointments a
              JOIN departments d ON a.department_id = d.id
              WHERE a.status IN ('pending','confirmed')
                AND CONCAT(a.appointment_date, ' ', a.appointment_time) <= DATE_ADD(NOW(), INTERVAL 15 MINUTE)
                AND a.appointment_date <= CURDATE()";
    $types = '';
    $params = [];
    if (!empty($department)) {
        $query .= " AND d.code = ?";
        $types .= 's';
        $params[] = $department;
    }
    
    // Exclude cancelled
    $query .= " AND a.status != 'cancelled'";
    
    // Order for deterministic processing
    $query .= " ORDER BY a.appointment_date ASC, a.appointment_time ASC, a.id ASC";
    
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $promoted = 0;
    $errors = [];
    
    while ($appt = $result->fetch_assoc()) {
        // Safety: skip if already marked queued
        if ($appt['status'] === 'queued') {
            continue;
        }
        
        $department_id = (int)$appt['department_id'];
        $department_code = $appt['department_code'];
        $patient_phone = $appt['patient_phone'];
        $patient_name = $appt['patient_name'];
        $patient_age = !empty($appt['patient_age']) ? (int)$appt['patient_age'] : null;
        $patient_id_number = $appt['patient_id_number'] ?? null;
        $service_type = $appt['service_type'] ?? null;
        $priority_type = $appt['priority_type'] ?? 'no';
        $patient_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        
        // Prevent duplicates: check if a token already exists today for same phone & department
        $dupStmt = $conn->prepare("SELECT qt.id FROM queue_tokens qt
                                   WHERE qt.patient_phone = ? AND qt.department_id = ?
                                     AND DATE(qt.created_at) = CURDATE()
                                     AND qt.status IN ('waiting','serving')");
        $dupStmt->bind_param('si', $patient_phone, $department_id);
        $dupStmt->execute();
        $dupRes = $dupStmt->get_result();
        if ($dupRes->num_rows > 0) {
            // Mark appointment as queued (linked implicitly) and continue
            $upd = $conn->prepare("UPDATE appointments SET status = 'queued' WHERE id = ?");
            $upd->bind_param('i', $appt['id']);
            $upd->execute();
            // History
            $hist = $conn->prepare("INSERT INTO appointment_history (appointment_id, action, performed_by, notes) VALUES (?, 'queued', ?, 'Linked to existing queue token')");
            $hist->bind_param('ii', $appt['id'], $patient_id);
            $hist->execute();
            $promoted++;
            continue;
        }
        
        // Generate token number like queue.php
        $date_prefix = date('Ymd');
        $dept_prefix = strtoupper(substr($department_code, 0, 3));
        $countStmt = $conn->prepare("SELECT COUNT(*) as count FROM queue_tokens WHERE DATE(created_at) = CURDATE() AND department_id = ?");
        $countStmt->bind_param('i', $department_id);
        $countStmt->execute();
        $countRow = $countStmt->get_result()->fetch_assoc();
        $seq = str_pad(((int)$countRow['count']) + 1, 4, '0', STR_PAD_LEFT);
        $token_number = $dept_prefix . '-' . $date_prefix . '-' . $seq;
        
        // Determine queue position
        $posStmt = $conn->prepare("SELECT COALESCE(MAX(queue_position), 0) AS max_pos FROM queue_tokens WHERE department_id = ? AND status = 'waiting'");
        $posStmt->bind_param('i', $department_id);
        $posStmt->execute();
        $posRow = $posStmt->get_result()->fetch_assoc();
        $queue_position = ((int)$posRow['max_pos']) + 1;
        
        // Insert queue token
        $ins = $conn->prepare("INSERT INTO queue_tokens (token_number, patient_id, patient_name, patient_age, patient_phone, patient_id_number, patient_address, service_type, department_id, priority_type, queue_position)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $patient_address = null;
        $ins->bind_param(
            'sisisssssii',
            $token_number,
            $patient_id,
            $patient_name,
            $patient_age,
            $patient_phone,
            $patient_id_number,
            $patient_address,
            $service_type,
            $department_id,
            $priority_type,
            $queue_position
        );
        
        if ($ins->execute()) {
            $token_id = $conn->insert_id;
            // Log queue history
            $qh = $conn->prepare("INSERT INTO queue_history (token_id, action, performed_by, notes) VALUES (?, 'created', ?, ?)");
            $notes = 'Promoted from appointment ' . $appt['appointment_number'];
            $qh->bind_param('iis', $token_id, $patient_id, $notes);
            $qh->execute();
            
            // Update appointment status to queued
            $upd = $conn->prepare("UPDATE appointments SET status = 'queued' WHERE id = ?");
            $upd->bind_param('i', $appt['id']);
            $upd->execute();
            
            // Log appointment history
            $hist = $conn->prepare("INSERT INTO appointment_history (appointment_id, action, performed_by, notes) VALUES (?, 'queued', ?, ?)");
            $hist->bind_param('iis', $appt['id'], $patient_id, $notes);
            $hist->execute();
            
            $promoted++;
        } else {
            $errors[] = 'Failed to create token for appointment ' . $appt['appointment_number'];
        }
    }
    
    echo json_encode([
        'success' => true,
        'promoted' => $promoted,
        'errors' => $errors
    ]);
}

// Get single appointment
function getAppointment() {
    $appointment_number = $_GET['appointment_number'] ?? null;
    
    if (!$appointment_number) {
        echo json_encode(['success' => false, 'message' => 'Appointment number required']);
        return;
    }
    
    $appointment = getAppointmentByNumber($appointment_number);
    
    if ($appointment) {
        echo json_encode(['success' => true, 'appointment' => $appointment]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Appointment not found']);
    }
}

// Get appointment by ID
function getAppointmentById($id) {
    global $conn;
    
    $query = "SELECT a.*, d.name as department_name, d.code as department_code 
              FROM appointments a 
              JOIN departments d ON a.department_id = d.id 
              WHERE a.id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

// Get appointment by number
function getAppointmentByNumber($number) {
    global $conn;
    
    $query = "SELECT a.*, d.name as department_name, d.code as department_code 
              FROM appointments a 
              JOIN departments d ON a.department_id = d.id 
              WHERE a.appointment_number = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $number);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

// Update appointment
function updateAppointment() {
    global $conn;
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['appointment_number'])) {
        echo json_encode(['success' => false, 'message' => 'Appointment number required']);
        return;
    }
    
    $appointment = getAppointmentByNumber($data['appointment_number']);
    
    if (!$appointment) {
        echo json_encode(['success' => false, 'message' => 'Appointment not found']);
        return;
    }
    
    // Build update query
    $updates = [];
    $params = [];
    $types = '';
    
    if (isset($data['status'])) {
        $updates[] = "status = ?";
        $params[] = $data['status'];
        $types .= 's';
    }
    
    if (isset($data['notes'])) {
        $updates[] = "notes = ?";
        $params[] = $data['notes'];
        $types .= 's';
    }
    
    if (empty($updates)) {
        echo json_encode(['success' => false, 'message' => 'No fields to update']);
        return;
    }
    
    $query = "UPDATE appointments SET " . implode(', ', $updates) . " WHERE appointment_number = ?";
    $params[] = $data['appointment_number'];
    $types .= 's';
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        // Log to history
        $action = $data['status'] ?? 'updated';
        $user_id = $_SESSION['user_id'] ?? null;
        $history_query = "INSERT INTO appointment_history (appointment_id, action, performed_by, notes) 
                         VALUES (?, ?, ?, ?)";
        if ($history_stmt = $conn->prepare($history_query)) {
            $notes = $data['notes'] ?? null;
            $history_stmt->bind_param('isis', $appointment['id'], $action, $user_id, $notes);
            @$history_stmt->execute();
        }
        
        $updated_appointment = getAppointmentByNumber($data['appointment_number']);
        echo json_encode(['success' => true, 'message' => 'Appointment updated', 'appointment' => $updated_appointment]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update appointment']);
    }
}

// Cancel appointment
function cancelAppointment() {
    global $conn;
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['appointment_number'])) {
        echo json_encode(['success' => false, 'message' => 'Appointment number required']);
        return;
    }
    
    $appointment = getAppointmentByNumber($data['appointment_number']);
    
    if (!$appointment) {
        echo json_encode(['success' => false, 'message' => 'Appointment not found']);
        return;
    }
    
    $query = "UPDATE appointments SET status = 'cancelled' WHERE appointment_number = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $data['appointment_number']);
    
    if ($stmt->execute()) {
        // Log to history
        $user_id = $_SESSION['user_id'] ?? null;
        $history_query = "INSERT INTO appointment_history (appointment_id, action, performed_by, notes) 
                         VALUES (?, 'cancelled', ?, ?)";
        if ($history_stmt = $conn->prepare($history_query)) {
            $notes = $data['reason'] ?? 'Cancelled by patient';
            $history_stmt->bind_param('iis', $appointment['id'], $user_id, $notes);
            @$history_stmt->execute();
        }
        
        echo json_encode(['success' => true, 'message' => 'Appointment cancelled successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to cancel appointment']);
    }
}

// Get available time slots for a date and department
function getAvailableSlots() {
    global $conn;
    // Ensure DB connection exists
    if (!isset($conn) || !$conn) {
        if (function_exists('getDBConnection')) {
            $conn = getDBConnection();
        }
    }
    
    $department = $_GET['department'] ?? null;
    $date = $_GET['date'] ?? null;
    
    if (!$department || !$date) {
        echo json_encode(['success' => false, 'message' => 'Department and date required']);
        return;
    }
    
    // Get department ID
    $dept_query = "SELECT id FROM departments WHERE code = ?";
    $stmt = $conn->prepare($dept_query);
    if (!$stmt) {
        // If departments table is missing, return default slots
        $available_slots = [];
        $start_hour = 8; $end_hour = 16;
        for ($hour = $start_hour; $hour < $end_hour; $hour++) {
            for ($minute = 0; $minute < 60; $minute += 30) {
                $time = sprintf('%02d:%02d:00', $hour, $minute);
                $available_slots[] = [
                    'time' => $time,
                    'available' => true,
                    'booked_count' => 0,
                    'max_capacity' => 3
                ];
            }
        }
        echo json_encode(['success' => true, 'slots' => $available_slots, 'note' => 'Departments table not available; returning default slots']);
        return;
    }
    $stmt->bind_param('s', $department);
    $stmt->execute();
    $dept_result = $stmt->get_result();
    
    if ($dept_result->num_rows === 0) {
        // If department codes not seeded yet, return default slots so UI still works
        $available_slots = [];
        // Define default slots (8:00 to 16:00, every 30 minutes)
        $start_hour = 8; $end_hour = 16;
        for ($hour = $start_hour; $hour < $end_hour; $hour++) {
            for ($minute = 0; $minute < 60; $minute += 30) {
                $time = sprintf('%02d:%02d:00', $hour, $minute);
                $available_slots[] = [
                    'time' => $time,
                    'available' => true,
                    'booked_count' => 0,
                    'max_capacity' => 3
                ];
            }
        }
        echo json_encode(['success' => true, 'slots' => $available_slots, 'note' => 'Department not found; returning default slots']);
        return;
    }
    
    $department_id = $dept_result->fetch_assoc()['id'];
    
    // Define time slots (8:00 AM to 4:00 PM, every 30 minutes)
    $slots = [];
    $start_hour = 8;
    $end_hour = 16;
    
    for ($hour = $start_hour; $hour < $end_hour; $hour++) {
        for ($minute = 0; $minute < 60; $minute += 30) {
            $time = sprintf('%02d:%02d:00', $hour, $minute);
            $slots[] = $time;
        }
    }
    
    // Get booked slots
    $query = "SELECT appointment_time, COUNT(*) as count 
              FROM appointments 
              WHERE department_id = ? 
              AND appointment_date = ? 
              AND status != 'cancelled' 
              GROUP BY appointment_time";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        // If appointments table not available, return default slots
        $available_slots = [];
        foreach ($slots as $slot) {
            $available_slots[] = [
                'time' => $slot,
                'available' => true,
                'booked_count' => 0,
                'max_capacity' => 3
            ];
        }
        echo json_encode(['success' => true, 'slots' => $available_slots, 'note' => 'Appointments table not available; returning default slots']);
        return;
    }
    $stmt->bind_param('is', $department_id, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $booked = [];
    while ($row = $result->fetch_assoc()) {
        $booked[$row['appointment_time']] = $row['count'];
    }
    
    // Build available slots array
    $available_slots = [];
    foreach ($slots as $slot) {
        $count = $booked[$slot] ?? 0;
        $available_slots[] = [
            'time' => $slot,
            'available' => $count < 3,
            'booked_count' => $count,
            'max_capacity' => 3
        ];
    }
    
    echo json_encode(['success' => true, 'slots' => $available_slots]);
}

// Get appointments for logged-in patient
function getMyAppointments() {
    global $conn;
    
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        return;
    }
    
    $user_id = $_SESSION['user_id'];
    
    $query = "SELECT a.*, d.name as department_name, d.code as department_code 
              FROM appointments a 
              JOIN departments d ON a.department_id = d.id 
              WHERE a.patient_id = ? 
              ORDER BY a.appointment_date DESC, a.appointment_time DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $appointments = [];
    while ($row = $result->fetch_assoc()) {
        $appointments[] = $row;
    }
    
    echo json_encode(['success' => true, 'appointments' => $appointments]);
}
