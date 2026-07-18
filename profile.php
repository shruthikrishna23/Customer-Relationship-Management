<?php
require_once 'config.php';
require_login();

$active_page = 'profile';
$page_title = 'Profile';
$message = '';
$error = '';

$admin_id = $_SESSION['admin_id'];
$stmt = $conn->prepare("SELECT * FROM admin_users WHERE id=?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
$stmt->close();

// ---------- UPDATE PROFILE INFO ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_profile') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);

    $stmt = $conn->prepare("UPDATE admin_users SET full_name=?, email=? WHERE id=?");
    $stmt->bind_param("ssi", $full_name, $email, $admin_id);
    if ($stmt->execute()) {
        $_SESSION['admin_name'] = $full_name;
        $message = "Profile updated successfully.";
        $admin['full_name'] = $full_name;
        $admin['email'] = $email;
    }
    $stmt->close();
}

// ---------- UPDATE PASSWORD ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_password') {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if (!password_verify($current, $admin['password'])) {
        $error = "Current password is incorrect.";
    } elseif ($new !== $confirm) {
        $error = "New password and confirmation do not match.";
    } elseif (strlen($new) < 6) {
        $error = "New password must be at least 6 characters.";
    } else {
        $hash = password_hash($new, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE admin_users SET password=? WHERE id=?");
        $stmt->bind_param("si", $hash, $admin_id);
        if ($stmt->execute()) {
            $message = "Password updated successfully.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Profile - CRM System</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-shell">
  <?php include 'includes/sidebar.php'; ?>
  <main class="main">
    <?php include 'includes/topbar.php'; ?>

    <?php if ($message): ?><div class="alert alert-success"><?= h($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= h($error) ?></div><?php endif; ?>

    <div class="grid-2">
      <div class="card">
        <div class="card-header"><h3>Admin Profile</h3></div>
        <form method="POST" action="profile.php">
          <input type="hidden" name="action" value="update_profile">
          <div class="form-group">
            <label>Username</label>
            <input type="text" value="<?= h($admin['username']) ?>" disabled>
          </div>
          <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="full_name" value="<?= h($admin['full_name']) ?>" required>
          </div>
          <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" value="<?= h($admin['email']) ?>">
          </div>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
      </div>

      <div class="card">
        <div class="card-header"><h3>Update Password</h3></div>
        <form method="POST" action="profile.php">
          <input type="hidden" name="action" value="update_password">
          <div class="form-group">
            <label>Current Password</label>
            <input type="password" name="current_password" required>
          </div>
          <div class="form-group">
            <label>New Password</label>
            <input type="password" name="new_password" required minlength="6">
          </div>
          <div class="form-group">
            <label>Confirm New Password</label>
            <input type="password" name="confirm_password" required minlength="6">
          </div>
          <button type="submit" class="btn btn-warning">Update Password</button>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h3>Session</h3></div>
      <p class="text-muted">Signed in as <strong><?= h($admin['full_name']) ?></strong>.</p>
      <a href="logout.php" class="btn btn-danger">Logout</a>
    </div>
  </main>
</div>
</body>
</html>
