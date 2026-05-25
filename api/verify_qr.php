<?php
header('Content-Type: application/json');
require_once '../config/db_connect.php';

// --- Function to send a JSON response and terminate the script ---
function send_response($success, $message) {
    echo json_encode(['success' => $success, 'message' => $message]);
    exit; // Stop the script immediately after sending the response
}

// Security check
if (!isset($_SESSION["user_loggedin"]) || $_SESSION["user_loggedin"] !== true || $_SESSION['user_type'] !== 'coordinator') {
    send_response(false, 'Coordinator not authenticated.');
}
$coordinator_id = $_SESSION['user_id'];

$data = json_decode(file_get_contents('php://input'), true);
$qr_data = $data['qr_data'] ?? null;

if (!$qr_data) {
    send_response(false, 'No QR data received.');
}

parse_str(str_replace(';', '&', $qr_data), $parsed_data);

// --- LUNCH TOKEN LOGIC ---
if (isset($parsed_data['LUNCH_TOKEN'])) {
    $student_id = $parsed_data['SID'] ?? null;
    $student_name = rawurldecode($parsed_data['NAME'] ?? 'Unknown Student');

    if (!$student_id) {
        send_response(false, 'Invalid Lunch Token format.');
    }

    // Check if lunch has already been redeemed
    $check_sql = "SELECT redemption_id FROM lunch_redemptions WHERE user_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $student_id);
    $check_stmt->execute();
    $check_stmt->store_result();
    
    if ($check_stmt->num_rows > 0) {
        $check_stmt->close();
        send_response(false, 'LUNCH ALREADY REDEEMED for ' . htmlspecialchars($student_name));
    }
    $check_stmt->close();

    // Insert a new record into the lunch_redemptions table
    $insert_sql = "INSERT INTO lunch_redemptions (user_id, scanned_by_coordinator_id) VALUES (?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("ii", $student_id, $coordinator_id);
    if ($insert_stmt->execute()) {
        send_response(true, "Lunch Token VALID for " . htmlspecialchars($student_name));
    } else {
        send_response(false, "Database error redeeming lunch.");
    }
    $insert_stmt->close();
}

// --- EVENT TOKEN LOGIC ---
elseif (isset($parsed_data['EVENT_ID'])) {
    $event_id = $parsed_data['EVENT_ID'] ?? null;
    $student_id = $parsed_data['SID'] ?? null;

    if (!$event_id || !$student_id) {
        send_response(false, 'Invalid Event Pass format.');
    }

    $sql = "SELECT r.attendance_marked, u.full_name, e.event_name 
            FROM registrations r 
            JOIN users u ON r.student_id = u.student_id
            JOIN events e ON r.event_id = e.event_id
            WHERE r.student_id = ? AND r.event_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $student_id, $event_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($attendance_marked, $student_name, $event_name);
        $stmt->fetch();
        $stmt->close();

        if ($attendance_marked) {
            send_response(false, "ALREADY CHECKED IN for " . htmlspecialchars($event_name));
        } else {
            // Update the attendance
            $update_sql = "UPDATE registrations SET attendance_marked = 1 WHERE student_id = ? AND event_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ii", $student_id, $event_id);
            $update_stmt->execute();
            $update_stmt->close();
            send_response(true, "Check-in successful for " . htmlspecialchars($student_name) . " to " . htmlspecialchars($event_name));
        }
    } else {
        $stmt->close();
        send_response(false, 'Registration NOT FOUND. Invalid Pass.');
    }
}

// Fallback for unknown QR codes
send_response(false, 'UNKNOWN QR CODE FORMAT.');
?>