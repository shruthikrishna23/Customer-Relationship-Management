<?php
require_once 'config.php';
require_login();

$active_page = 'reports';
$page_title = 'Reports';

// ---------- Monthly Sales Report (last 6 months) ----------
$monthly_sales = $conn->query("
    SELECT DATE_FORMAT(order_date, '%Y-%m') AS ym, DATE_FORMAT(order_date, '%b %Y') AS label,
           COUNT(*) AS order_count, COALESCE(SUM(total_amount),0) AS total
    FROM orders
    WHERE status != 'Cancelled'
    GROUP BY ym, label
    ORDER BY ym DESC
    LIMIT 6
");

// ---------- Customer Growth Report (customers added per month, last 6 months) ----------
$customer_growth = $conn->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, DATE_FORMAT(created_at, '%b %Y') AS label,
           COUNT(*) AS new_customers
    FROM customers
    GROUP BY ym, label
    ORDER BY ym DESC
    LIMIT 6
");

// ---------- Product Sales Report (top selling products) ----------
$product_sales = $conn->query("
    SELECT p.product_code, p.name, SUM(oi.quantity) AS units_sold, SUM(oi.subtotal) AS revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status != 'Cancelled'
    GROUP BY p.id, p.product_code, p.name
    ORDER BY revenue DESC
");

// ---------- Revenue Summary ----------
$revenue_summary = $conn->query("
    SELECT
        COALESCE(SUM(total_amount),0) AS total_revenue,
        COALESCE(SUM(CASE WHEN status='Delivered' THEN total_amount ELSE 0 END),0) AS delivered_revenue,
        COALESCE(SUM(CASE WHEN status='Pending' THEN total_amount ELSE 0 END),0) AS pending_revenue,
        COALESCE(SUM(CASE WHEN status='Cancelled' THEN total_amount ELSE 0 END),0) AS cancelled_revenue,
        COUNT(*) AS total_orders
    FROM orders
")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Reports - CRM System</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-shell">
  <?php include 'includes/sidebar.php'; ?>
  <main class="main">
    <?php include 'includes/topbar.php'; ?>

    <!-- Revenue Summary -->
    <div class="stats-grid">
      <div class="stat-card revenue">
        <h3>Total Revenue</h3>
        <div class="value">₹<?= number_format($revenue_summary['total_revenue'], 2) ?></div>
      </div>
      <div class="stat-card">
        <h3>Delivered Revenue</h3>
        <div class="value">₹<?= number_format($revenue_summary['delivered_revenue'], 2) ?></div>
      </div>
      <div class="stat-card orders">
        <h3>Pending Revenue</h3>
        <div class="value">₹<?= number_format($revenue_summary['pending_revenue'], 2) ?></div>
      </div>
      <div class="stat-card">
        <h3>Cancelled Revenue</h3>
        <div class="value">₹<?= number_format($revenue_summary['cancelled_revenue'], 2) ?></div>
      </div>
    </div>

    <!-- Monthly Sales Report -->
    <div class="card">
      <div class="card-header"><h3>Monthly Sales Report</h3></div>
      <table class="data-table">
        <thead><tr><th>Month</th><th>Orders</th><th>Revenue</th></tr></thead>
        <tbody>
        <?php if ($monthly_sales->num_rows === 0): ?>
          <tr><td colspan="3">No sales data available.</td></tr>
        <?php else: while ($m = $monthly_sales->fetch_assoc()): ?>
          <tr>
            <td><?= h($m['label']) ?></td>
            <td><?= (int)$m['order_count'] ?></td>
            <td>₹<?= number_format($m['total'], 2) ?></td>
          </tr>
        <?php endwhile; endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Customer Growth Report -->
    <div class="card">
      <div class="card-header"><h3>Customer Growth Report</h3></div>
      <table class="data-table">
        <thead><tr><th>Month</th><th>New Customers</th></tr></thead>
        <tbody>
        <?php if ($customer_growth->num_rows === 0): ?>
          <tr><td colspan="2">No customer growth data available.</td></tr>
        <?php else: while ($g = $customer_growth->fetch_assoc()): ?>
          <tr>
            <td><?= h($g['label']) ?></td>
            <td><?= (int)$g['new_customers'] ?></td>
          </tr>
        <?php endwhile; endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Product Sales Report -->
    <div class="card">
      <div class="card-header"><h3>Product Sales Report</h3></div>
      <table class="data-table">
        <thead><tr><th>Product Code</th><th>Product</th><th>Units Sold</th><th>Revenue</th></tr></thead>
        <tbody>
        <?php if ($product_sales->num_rows === 0): ?>
          <tr><td colspan="4">No product sales data available.</td></tr>
        <?php else: while ($p = $product_sales->fetch_assoc()): ?>
          <tr>
            <td><?= h($p['product_code']) ?></td>
            <td><?= h($p['name']) ?></td>
            <td><?= (int)$p['units_sold'] ?></td>
            <td>₹<?= number_format($p['revenue'], 2) ?></td>
          </tr>
        <?php endwhile; endif; ?>
        </tbody>
      </table>
    </div>
  </main>
</div>
</body>
</html>
