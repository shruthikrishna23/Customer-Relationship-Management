<?php
// =========================================================
// Database Configuration
// =========================================================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');          // set your MySQL password here
define('DB_NAME', 'crm_db');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// Start session on every page that includes this file
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper: redirect to login if not authenticated
function require_login() {
    if (!isset($_SESSION['admin_id'])) {
        header("Location: login.php");
        exit;
    }
}

// Helper: escape output
function h($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// Helper: log an activity
function log_activity($conn, $desc, $type = 'General') {
    $stmt = $conn->prepare("INSERT INTO activity_log (activity_desc, activity_type) VALUES (?, ?)");
    $stmt->bind_param("ss", $desc, $type);
    $stmt->execute();
    $stmt->close();
}
