<?php
// Set the default timezone for all date/time functions
date_default_timezone_set('UTC');

// Turn on error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- DATABASE CREDENTIALS (Vercel & Localhost) ---
// Vercel populates $_SERVER and $_ENV, which are sometimes more reliable than getenv()
$db_server = $_SERVER['DB_SERVER'] ?? $_ENV['DB_SERVER'] ?? getenv('DB_SERVER') ?: '127.0.0.1';
$db_username = $_SERVER['DB_USERNAME'] ?? $_ENV['DB_USERNAME'] ?? getenv('DB_USERNAME') ?: 'root';
$db_password = $_SERVER['DB_PASSWORD'] ?? $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: '';
$db_name = $_SERVER['DB_NAME'] ?? $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'intercollege_meet_app';
$db_port = $_SERVER['DB_PORT'] ?? $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: 3306;

define('DB_SERVER', $db_server); 
define('DB_USERNAME', $db_username);
define('DB_PASSWORD', $db_password); 
define('DB_NAME', $db_name);
define('DB_PORT', $db_port);

// --- Establish Connection (with SSL support for TiDB) ---
$conn = mysqli_init();
$flags = (DB_SERVER !== '127.0.0.1') ? MYSQLI_CLIENT_SSL : 0;
$conn->real_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME, DB_PORT, NULL, $flags);

// Check connection
if($conn->connect_error){
    die("ERROR: Database connection failed. " . $conn->connect_error . " - Please make sure MySQL is running in your XAMPP control panel.");
}

// --- Start Session ---
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- "Remember Me" Auto-Login Logic ---
// Check for user cookie
if (!isset($_SESSION["user_loggedin"]) && isset($_COOKIE['remember_me_token'])) {
    $token_from_cookie = $_COOKIE['remember_me_token'];
    $token_hash = hash('sha256', $token_from_cookie);
    $sql = "SELECT student_id, full_name, user_type FROM users WHERE remember_token_hash = ? AND remember_token_expires_at > NOW()";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $token_hash);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows == 1) {
            $stmt->bind_result($id, $name, $user_type);
            $stmt->fetch();
            session_regenerate_id();
            $_SESSION["user_loggedin"] = true;
            $_SESSION["user_id"] = $id;
            $_SESSION["user_name"] = $name;
            $_SESSION["user_type"] = $user_type;
        }
        $stmt->close();
    }
}

// Check for admin cookie
if (!isset($_SESSION["admin_loggedin"]) && isset($_COOKIE['remember_me_admin_token'])) {
    $token_from_cookie = $_COOKIE['remember_me_admin_token'];
    $token_hash = hash('sha256', $token_from_cookie);
    $sql = "SELECT admin_id, username FROM admins WHERE remember_token_hash = ? AND remember_token_expires_at > NOW()";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $token_hash);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows == 1) {
            $stmt->bind_result($id, $username);
            $stmt->fetch();
            session_regenerate_id();
            $_SESSION["admin_loggedin"] = true;
            $_SESSION["admin_id"] = $id;
            $_SESSION["admin_username"] = $username;
        }
        $stmt->close();
    }
}
?>