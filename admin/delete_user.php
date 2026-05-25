<?php
require_once '../config/db_connect.php';

// Security Check
if(!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true){
    header("location: ../auth.php?action=login");
    exit;
}

$user_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($user_id) {
    // To prevent an admin from deleting their own account
    if ($user_id == $_SESSION['admin_id']) {
        // You might want to add a feedback message here using sessions
        header("location: manage_users.php");
        exit;
    }

    $sql = "DELETE FROM users WHERE student_id = ?";
    
    if($stmt = $conn->prepare($sql)){
        $stmt->bind_param("i", $user_id);
        
        // ON DELETE CASCADE in the DB handles cleanup of related records.
        $stmt->execute();
        
        $stmt->close();
    }
}

// Redirect back to the user list
header("location: manage_users.php");
exit;
?>