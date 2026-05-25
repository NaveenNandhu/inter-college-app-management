<?php
require_once '../config/db_connect.php';

// Security Check
if(!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true){
    header("location: ../auth.php?action=login");
    exit;
}

// Get the event ID from the URL
$event_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($event_id) {
    // Prepare a delete statement
    $sql = "DELETE FROM events WHERE event_id = ?";
    
    if($stmt = $conn->prepare($sql)){
        $stmt->bind_param("i", $event_id);
        
        // Thanks to "ON DELETE CASCADE", deleting an event automatically
        // deletes its registrations and coordinator assignments.
        $stmt->execute();
        
        $stmt->close();
    }
}

// Redirect back to the manage events page after deletion
header("location: manage_events.php");
exit;
?>