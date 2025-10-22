<?php
require_once '../config.php';

$method = $_SERVER['REQUEST_METHOD'];

// Create new queue token
if ($method === 'POST' && isset($_GET['action']) && $_GET['action'] === 'create') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $patient_name = $data['patient_name'] ?? '';
    $patient_age = $data['patient_age'] ?? null;
    $patient_phone = $data['patient_phone'] ?? '';
    $patient_id_number = $data['patient_id_number'] ?? '';
    $patient_address = $data['patient_address'] ?? '';
    $service_type = $data['service_type'] ?? '';
    $department_code = $data['department'] ?? '';
    $priority_type = $data['priority_type'] ?? 'no';
    
    if (empty($patient_name) || empty($department_code)) {
        echo json_encode(['success' => false, 'message' => 'Patient name and department required']);
        exit();
    }
    
    $conn = getDBConnection();
    
    // Get department ID
    $stmt = $conn->prepare("SELECT id FROM departments WHERE code = ?");
    $stmt->bind_param("s", $department_code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid department']);
        exit();
    }
    
    $department_id = $result->fetch_assoc()['id'];
    
    // Generate token number
    $date_prefix = date('Ymd');
    $dept_prefix = strtoupper(substr($department_code, 0, 3));
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM queue_tokens WHERE DATE(created_at) = CURDATE() AND department_id = ?");
    $stmt->bind_param("i", $department_id);
    $stmt->execute();
    $count = $stmt->get_result()->fetch_assoc()['count'] + 1;
    $token_number = $dept_prefix . '-' . $date_prefix . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    
    // Get queue position
    $stmt = $conn->prepare("SELECT MAX(queue_position) as max_pos FROM queue_tokens WHERE department_id = ? AND status = 'waiting'");
    $stmt->bind_param("i", $department_id);
    $stmt->execute();
    $max_pos = $stmt->get_result()->fetch_assoc()['max_pos'] ?? 0;
    $queue_position = $max_pos + 1;
    
    // Insert token
    $patient_id = $_SESSION['user_id'] ?? null;
    $stmt = $conn->prepare("INSERT INTO queue_tokens (token_number, patient_id, patient_name, patient_age, patient_phone, patient_id_number, patient_address, service_type, department_id, priority_type, queue_position) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sisissssisi", $token_number, $patient_id, $patient_name, $patient_age, $patient_phone, $patient_id_number, $patient_address, $service_type, $department_id, $priority_type, $queue_position);
    
    if ($stmt->execute()) {
        $token_id = $conn->insert_id;
        
        // Log history
        $stmt = $conn->prepare("INSERT INTO queue_history (token_id, action, performed_by) VALUES (?, 'created', ?)");
        $stmt->bind_param("ii", $token_id, $patient_id);
        $stmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Token created successfully',
            'token' => [
                'id' => $token_id,
                'token_number' => $token_number,
                'queue_position' => $queue_position
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create token: ' . $stmt->error]);
    }
    
    $stmt->close();
    $conn->close();
}

// Get queue status
elseif ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'status') {
    $department_code = $_GET['department'] ?? '';
    
    $conn = getDBConnection();
    
    if ($department_code) {
        $stmt = $conn->prepare("
            SELECT qt.*, d.name as department_name, d.code as department_code
            FROM queue_tokens qt
            JOIN departments d ON qt.department_id = d.id
            WHERE d.code = ? AND qt.status IN ('waiting', 'serving')
            ORDER BY qt.priority_type DESC, qt.queue_position ASC
        ");
        $stmt->bind_param("s", $department_code);
    } else {
        $stmt = $conn->prepare("
            SELECT qt.*, d.name as department_name, d.code as department_code
            FROM queue_tokens qt
            JOIN departments d ON qt.department_id = d.id
            WHERE qt.status IN ('waiting', 'serving')
            ORDER BY d.code, qt.priority_type DESC, qt.queue_position ASC
        ");
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $tokens = [];
    
    while ($row = $result->fetch_assoc()) {
        $tokens[] = $row;
    }
    
    echo json_encode(['success' => true, 'tokens' => $tokens]);
    
    $stmt->close();
    $conn->close();
}

// Call next patient
elseif ($method === 'POST' && isset($_GET['action']) && $_GET['action'] === 'call_next') {
    $department_code = $_GET['department'] ?? '';
    
    if (empty($department_code)) {
        echo json_encode(['success' => false, 'message' => 'Department required']);
        exit();
    }
    
    $conn = getDBConnection();
    
    // Get department ID
    $stmt = $conn->prepare("SELECT id FROM departments WHERE code = ?");
    $stmt->bind_param("s", $department_code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid department']);
        exit();
    }
    
    $department_id = $result->fetch_assoc()['id'];
    
    // Get next token (priority first)
    $stmt = $conn->prepare("
        SELECT id, token_number FROM queue_tokens 
        WHERE department_id = ? AND status = 'waiting'
        ORDER BY priority_type DESC, queue_position ASC
        LIMIT 1
    ");
    $stmt->bind_param("i", $department_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'No patients in queue']);
        exit();
    }
    
    $token = $result->fetch_assoc();
    $token_id = $token['id'];
    
    // Update token status
    $stmt = $conn->prepare("UPDATE queue_tokens SET status = 'serving', called_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $token_id);
    $stmt->execute();
    
    // Log history
    $staff_id = $_SESSION['user_id'] ?? null;
    $stmt = $conn->prepare("INSERT INTO queue_history (token_id, action, performed_by) VALUES (?, 'called', ?)");
    $stmt->bind_param("ii", $token_id, $staff_id);
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'message' => 'Patient called',
        'token' => $token
    ]);
    
    $stmt->close();
    $conn->close();
}

// Complete token
elseif ($method === 'POST' && isset($_GET['action']) && $_GET['action'] === 'complete') {
    $token_id = $_GET['token_id'] ?? 0;
    
    if (empty($token_id)) {
        echo json_encode(['success' => false, 'message' => 'Token ID required']);
        exit();
    }
    
    $conn = getDBConnection();
    
    $stmt = $conn->prepare("UPDATE queue_tokens SET status = 'completed', completed_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $token_id);
    $stmt->execute();
    
    // Log history
    $staff_id = $_SESSION['user_id'] ?? null;
    $stmt = $conn->prepare("INSERT INTO queue_history (token_id, action, performed_by) VALUES (?, 'completed', ?)");
    $stmt->bind_param("ii", $token_id, $staff_id);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Token completed']);
    
    $stmt->close();
    $conn->close();
}

// Pause queue for a department
elseif ($method === 'POST' && isset($_GET['action']) && $_GET['action'] === 'pause_queue') {
    $department_code = $_GET['department'] ?? '';
    
    if (empty($department_code)) {
        echo json_encode(['success' => false, 'message' => 'Department required']);
        exit();
    }
    
    $conn = getDBConnection();
    
    // Get department ID
    $stmt = $conn->prepare("SELECT id FROM departments WHERE code = ?");
    $stmt->bind_param("s", $department_code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid department']);
        exit();
    }
    
    $department_id = $result->fetch_assoc()['id'];
    
    // Update department status to paused
    $stmt = $conn->prepare("UPDATE departments SET is_active = 0 WHERE id = ?");
    $stmt->bind_param("i", $department_id);
    $stmt->execute();
    
    // Log action
    $staff_id = $_SESSION['user_id'] ?? null;
    $stmt = $conn->prepare("INSERT INTO queue_history (token_id, action, performed_by, notes) VALUES (NULL, 'queue_paused', ?, ?)");
    $notes = "Department: $department_code";
    $stmt->bind_param("is", $staff_id, $notes);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Queue paused successfully']);
    
    $stmt->close();
    $conn->close();
}

// Resume queue for a department
elseif ($method === 'POST' && isset($_GET['action']) && $_GET['action'] === 'resume_queue') {
    $department_code = $_GET['department'] ?? '';
    
    if (empty($department_code)) {
        echo json_encode(['success' => false, 'message' => 'Department required']);
        exit();
    }
    
    $conn = getDBConnection();
    
    // Get department ID
    $stmt = $conn->prepare("SELECT id FROM departments WHERE code = ?");
    $stmt->bind_param("s", $department_code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid department']);
        exit();
    }
    
    $department_id = $result->fetch_assoc()['id'];
    
    // Update department status to active
    $stmt = $conn->prepare("UPDATE departments SET is_active = 1 WHERE id = ?");
    $stmt->bind_param("i", $department_id);
    $stmt->execute();
    
    // Log action
    $staff_id = $_SESSION['user_id'] ?? null;
    $stmt = $conn->prepare("INSERT INTO queue_history (token_id, action, performed_by, notes) VALUES (NULL, 'queue_resumed', ?, ?)");
    $notes = "Department: $department_code";
    $stmt->bind_param("is", $staff_id, $notes);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Queue resumed successfully']);
    
    $stmt->close();
    $conn->close();
}

// Reassign patient to another department
elseif ($method === 'POST' && isset($_GET['action']) && $_GET['action'] === 'reassign') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $token_id = $data['token_id'] ?? 0;
    $new_department_code = $data['new_department'] ?? '';
    
    if (empty($token_id) || empty($new_department_code)) {
        echo json_encode(['success' => false, 'message' => 'Token ID and new department required']);
        exit();
    }
    
    $conn = getDBConnection();
    
    // Get new department ID
    $stmt = $conn->prepare("SELECT id FROM departments WHERE code = ?");
    $stmt->bind_param("s", $new_department_code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid department']);
        exit();
    }
    
    $new_department_id = $result->fetch_assoc()['id'];
    
    // Get current max queue position in new department
    $stmt = $conn->prepare("SELECT MAX(queue_position) as max_pos FROM queue_tokens WHERE department_id = ? AND status = 'waiting'");
    $stmt->bind_param("i", $new_department_id);
    $stmt->execute();
    $max_pos = $stmt->get_result()->fetch_assoc()['max_pos'] ?? 0;
    $new_queue_position = $max_pos + 1;
    
    // Update token with new department and queue position
    $stmt = $conn->prepare("UPDATE queue_tokens SET department_id = ?, queue_position = ?, status = 'waiting' WHERE id = ?");
    $stmt->bind_param("iii", $new_department_id, $new_queue_position, $token_id);
    $stmt->execute();
    
    // Log history
    $staff_id = $_SESSION['user_id'] ?? null;
    $stmt = $conn->prepare("INSERT INTO queue_history (token_id, action, performed_by, notes) VALUES (?, 'reassigned', ?, ?)");
    $notes = "Reassigned to department: $new_department_code";
    $stmt->bind_param("iis", $token_id, $staff_id, $notes);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Patient reassigned successfully']);
    
    $stmt->close();
    $conn->close();
}

// Mark patient as attended (complete)
elseif ($method === 'POST' && isset($_GET['action']) && $_GET['action'] === 'mark_attended') {
    $token_id = $_GET['token_id'] ?? 0;
    
    if (empty($token_id)) {
        echo json_encode(['success' => false, 'message' => 'Token ID required']);
        exit();
    }
    
    $conn = getDBConnection();
    
    $stmt = $conn->prepare("UPDATE queue_tokens SET status = 'completed', completed_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $token_id);
    $stmt->execute();
    
    // Log history
    $staff_id = $_SESSION['user_id'] ?? null;
    $stmt = $conn->prepare("INSERT INTO queue_history (token_id, action, performed_by, notes) VALUES (?, 'attended', ?, 'Marked as attended by staff')");
    $stmt->bind_param("ii", $token_id, $staff_id);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Patient marked as attended']);
    
    $stmt->close();
    $conn->close();
}

else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
