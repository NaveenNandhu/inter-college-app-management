<?php
header('Content-Type: application/json');
require_once '../config/db_connect.php';

// Security Check: Ensure an admin is logged in
if(!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true){
    echo json_encode(['error' => 'Authentication required.']);
    exit;
}

// Get the meet_id from the request
$meet_id = filter_input(INPUT_GET, 'meet_id', FILTER_VALIDATE_INT);

if (!$meet_id) {
    echo json_encode([]); // Return an empty array if no meet_id is provided
    exit;
}

// Fetch events for the given meet_id
$sql = "SELECT event_id, event_name FROM events WHERE meet_id = ? ORDER BY event_name";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $meet_id);
$stmt->execute();
$result = $stmt->get_result();

$events = [];
while ($row = $result->fetch_assoc()) {
    $events[] = $row;
}

$stmt->close();
$conn->close();

// Return the events as a JSON object
echo json_encode($events);
?>