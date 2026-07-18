<?php
require_once 'config.php';
require_login();

$active_page = 'dashboard';
$page_title = 'Dashboard';

// ---- Key statistics ----
$total_customers = $conn->query("SELECT COUNT(*) AS c FROM customers")->fetch_assoc()['c'];
$total_employees  = $conn->query("SELECT COUNT(*) AS c FROM employees")->fetch_assoc()['c'];
$total_products   = $conn->query("SELECT COUNT(*) AS c FROM products")->fetch_assoc()['c'];
$total_orders     = $conn->query("SELECT COUNT(*) AS c FROM orders")->fetch_assoc()['c'];
$total_revenue    = $conn->query("SELECT COALESCE(SUM(total_amount),0) AS r FROM orders WHERE status != 'Cancelled'")->fetch_assoc()['r'];

// ---- Recent activities ----
$activities = $conn->query("SELECT activity_desc, activity_type, created_at FROM activity_log ORDER BY created_at DESC LIMIT 8");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Dashboard - CRM System</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-shell">
  <?php include 'includes/sidebar.php'; ?>

  <main class="main">
    <?php include 'includes/topbar.php'; ?>

    <div class="stats-grid">
      <div class="stat-card">
        <h3>Total Customers</h3>
        <div class="value"><?= (int)$total_customers ?></div>
      </div>
      <div class="stat-card">
        <h3>Total Employees</h3>
        <div class="value"><?= (int)$total_employees ?></div>
      </div>
      <div class="stat-card">
        <h3>Total Products</h3>
        <div class="value"><?= (int)$total_products ?></div>
      </div>
      <div class="stat-card orders">
        <h3>Total Orders</h3>
        <div class="value"><?= (int)$total_orders ?></div>
      </div>
      <div class="stat-card revenue">
        <h3>Total Revenue</h3>
        <div class="value">₹<?= number_format($total_revenue, 2) ?></div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h3>Recent Activities</h3>
      </div>
      <ul class="activity-list">
        <?php if ($activities->num_rows === 0): ?>
          <li>No recent activity.</li>
        <?php else: while ($a = $activities->fetch_assoc()): ?>
          <li>
            <span>
              <strong><?= h($a['activity_type']) ?>:</strong> <?= h($a['activity_desc']) ?>
            </span>
            <span class="text-muted"><?= date('d M Y, h:i A', strtotime($a['created_at'])) ?></span>
          </li>
        <?php endwhile; endif; ?>
      </ul>
    </div>
  </main>
</div>
</body>
</html>
