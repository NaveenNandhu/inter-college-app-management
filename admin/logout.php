<?php
// Initialize the session.
session_start();

// Unset all of the session variables.
// $_SESSION = array(); is a simple way to clear all session data.
$_SESSION = array();

// Destroy the session.
// This will remove the session data from the server.
session_destroy();

// Redirect to the main landing page after logout.
// The '../' is important because it navigates one directory up from the 'admin' folder.
header("location: ../index.php");
exit;
?>