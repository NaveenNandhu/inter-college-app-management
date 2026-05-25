<?php
header('Content-Type: application/json');
require_once '../config/db_connect.php';

// --- CORRECTED SECURITY CHECK ---
// It now checks for the unified user login session.
if(!isset($_SESSION["user_loggedin"]) || $_SESSION["user_loggedin"] !== true){
    echo json_encode(['success' => false, 'message' => 'Authentication required. Please log in.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$event_id = $data['event_id'] ?? null;
// Use the correct session variable for the user ID
$student_id = $_SESSION['user_id'] ?? null;

if(!$event_id || !$student_id){
    echo json_encode(['success' => false, 'message' => 'Invalid data provided.']);
    exit;
}

// Check for duplicate registration
$sql_check = "SELECT registration_id FROM registrations WHERE student_id = ? AND event_id = ?";
if($stmt_check = $conn->prepare($sql_check)){
    $stmt_check->bind_param("ii", $student_id, $event_id);
    $stmt_check->execute();
    $stmt_check->store_result();
    if($stmt_check->num_rows > 0){
        echo json_encode(['success' => false, 'message' => 'You are already registered for this event.']);
        $stmt_check->close();
        exit;
    }
    $stmt_check->close();
}

// Create unique QR code data
$qr_code_data = "EVENT_ID=" . $event_id . ";SID=" . $student_id;

$sql = "INSERT INTO registrations (student_id, event_id, qr_code_data) VALUES (?, ?, ?)";
if($stmt = $conn->prepare($sql)){
    $stmt->bind_param("iis", $student_id, $event_id, $qr_code_data);
    if($stmt->execute()){
        echo json_encode(['success' => true, 'message' => 'Successfully registered for the event! Check your dashboard for the QR code.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error during registration.']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare the database statement.']);
}
$conn->close();
?>