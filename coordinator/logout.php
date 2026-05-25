<?php
// Initialize the session.
session_start();

// Unset all of the session variables for the coordinator.
$_SESSION = array();

// Destroy the session from the server.
session_destroy();

// Redirect to the coordinator login page after logout.
header("location: ../index.php");
exit;
?>