<?php
header('Content-Type: application/json');
require_once '../config/db_connect.php';

// Security Check: Ensure an admin is logged in
if(!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true){
    echo json_encode(['error' => 'Authentication required.']);
    exit;
}

$event_id = filter_input(INPUT_GET, 'event_id', FILTER_VALIDATE_INT);

if (!$event_id) {
    echo json_encode(['assigned_user_ids' => []]);
    exit;
}

// Fetch user_ids for the given event_id
$sql = "SELECT user_id FROM event_assignments WHERE event_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();

$assigned_user_ids = [];
while ($row = $result->fetch_assoc()) {
    // We push the user_id as a string to match JavaScript's checkbox.value which is also a string
    $assigned_user_ids[] = (string)$row['user_id'];
}

$stmt->close();
$conn->close();

echo json_encode(['assigned_user_ids' => $assigned_user_ids]);
?>