<?php
require_once 'config.php';
require_login();

$active_page = 'search';
$page_title = 'Search';

$type = $_GET['type'] ?? 'customer';
$q = trim($_GET['q'] ?? '');
$results = null;

if ($q !== '') {
    if ($type === 'customer') {
        $stmt = $conn->prepare("SELECT * FROM customers WHERE name LIKE ? OR customer_code LIKE ? ORDER BY name");
        $like = "%$q%";
        $stmt->bind_param("ss", $like, $like);
        $stmt->execute();
        $results = $stmt->get_result();
    } elseif ($type === 'order') {
        $stmt = $conn->prepare("SELECT o.*, c.name AS customer_name FROM orders o JOIN customers c ON o.customer_id=c.id WHERE o.order_code LIKE ? ORDER BY o.id DESC");
        $like = "%$q%";
        $stmt->bind_param("s", $like);
        $stmt->execute();
        $results = $stmt->get_result();
    } elseif ($type === 'product') {
        $stmt = $conn->prepare("SELECT * FROM products WHERE name LIKE ? OR product_code LIKE ? ORDER BY name");
        $like = "%$q%";
        $stmt->bind_param("ss", $like, $like);
        $stmt->execute();
        $results = $stmt->get_result();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Search - CRM System</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-shell">
  <?php include 'includes/sidebar.php'; ?>
  <main class="main">
    <?php include 'includes/topbar.php'; ?>

    <div class="card">
      <div class="card-header"><h3>Search</h3></div>
      <form method="GET" action="search.php" class="search-form" style="flex-wrap:wrap;">
        <select name="type">
          <option value="customer" <?= $type === 'customer' ? 'selected' : '' ?>>Customer by Name</option>
          <option value="order" <?= $type === 'order' ? 'selected' : '' ?>>Order by ID/Code</option>
          <option value="product" <?= $type === 'product' ? 'selected' : '' ?>>Product by Name</option>
        </select>
        <input type="text" name="q" placeholder="Enter search term..." value="<?= h($q) ?>" required>
        <button type="submit" class="btn btn-primary">Search</button>
      </form>
    </div>

    <?php if ($q !== ''): ?>
    <div class="card">
      <div class="card-header"><h3>Results</h3></div>

      <?php if ($type === 'customer'): ?>
        <table class="data-table">
          <thead><tr><th>Customer ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Status</th></tr></thead>
          <tbody>
          <?php if (!$results || $results->num_rows === 0): ?>
            <tr><td colspan="5">No matching customers found.</td></tr>
          <?php else: while ($r = $results->fetch_assoc()): ?>
            <tr>
              <td><?= h($r['customer_code']) ?></td>
              <td><?= h($r['name']) ?></td>
              <td><?= h($r['email']) ?></td>
              <td><?= h($r['phone']) ?></td>
              <td><span class="badge badge-<?= strtolower($r['status']) ?>"><?= h($r['status']) ?></span></td>
            </tr>
          <?php endwhile; endif; ?>
          </tbody>
        </table>

      <?php elseif ($type === 'order'): ?>
        <table class="data-table">
          <thead><tr><th>Order Code</th><th>Customer</th><th>Date</th><th>Total</th><th>Status</th><th>Invoice</th></tr></thead>
          <tbody>
          <?php if (!$results || $results->num_rows === 0): ?>
            <tr><td colspan="6">No matching orders found.</td></tr>
          <?php else: while ($r = $results->fetch_assoc()): ?>
            <tr>
              <td><?= h($r['order_code']) ?></td>
              <td><?= h($r['customer_name']) ?></td>
              <td><?= h($r['order_date']) ?></td>
              <td>₹<?= number_format($r['total_amount'], 2) ?></td>
              <td><span class="badge badge-<?= strtolower($r['status']) ?>"><?= h($r['status']) ?></span></td>
              <td><a class="btn btn-sm" href="orders.php?invoice=<?= $r['id'] ?>">View</a></td>
            </tr>
          <?php endwhile; endif; ?>
          </tbody>
        </table>

      <?php elseif ($type === 'product'): ?>
        <table class="data-table">
          <thead><tr><th>Product Code</th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th></tr></thead>
          <tbody>
          <?php if (!$results || $results->num_rows === 0): ?>
            <tr><td colspan="5">No matching products found.</td></tr>
          <?php else: while ($r = $results->fetch_assoc()): ?>
            <tr>
              <td><?= h($r['product_code']) ?></td>
              <td><?= h($r['name']) ?></td>
              <td><?= h($r['category']) ?></td>
              <td>₹<?= number_format($r['price'], 2) ?></td>
              <td><?= (int)$r['stock'] ?></td>
            </tr>
          <?php endwhile; endif; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </main>
</div>
</body>
</html>
