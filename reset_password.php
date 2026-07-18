<?php
// =========================================================
// ONE-TIME UTILITY: Run this once in your browser after
// importing database.sql to correctly set the admin
// password hash for 'admin123' (bcrypt hashes are unique
// per-generation, so the SQL file's placeholder must be
// refreshed using PHP's own password_hash()).
//
// After running it once, DELETE this file for security.
// =========================================================
require_once 'config.php';

$new_password = 'admin123';
$hash = password_hash($new_password, PASSWORD_BCRYPT);

$stmt = $conn->prepare("UPDATE admin_users SET password = ? WHERE username = 'admin'");
$stmt->bind_param("s", $hash);

if ($stmt->execute()) {
    echo "Password reset successful. You can now log in with username: <b>admin</b> and password: <b>admin123</b>.<br>";
    echo "Please delete reset_password.php now for security.";
} else {
    echo "Error: " . $conn->error;
}
$stmt->close();
