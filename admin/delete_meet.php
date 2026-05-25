<?php
require_once '../config/db_connect.php';

// Security Check
if(!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true){
    header("location: ../auth.php?action=login");
    exit;
}

// Get the meet ID from the URL
$meet_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($meet_id) {
    // Prepare a delete statement
    $sql = "DELETE FROM meets WHERE meet_id = ?";
    
    if($stmt = $conn->prepare($sql)){
        $stmt->bind_param("i", $meet_id);
        
        // Because of "ON DELETE CASCADE" in the database, 
        // deleting a meet will automatically delete all its associated events and their registrations.
        $stmt->execute();
        
        $stmt->close();
    }
}

// Redirect back to the manage meets page after deletion
header("location: manage_meets.php");
exit;
?>