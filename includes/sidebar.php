<?php
// Expects $active_page to be set by the calling page (e.g. 'dashboard')
$active_page = $active_page ?? '';
function nav_active($page, $active) { return $page === $active ? 'active' : ''; }
?>
<aside class="sidebar">
  <div class="brand">🗂 CRM System</div>
  <nav>
    <a href="dashboard.php" class="<?= nav_active('dashboard', $active_page) ?>"><span class="icon">📊</span> Dashboard</a>
    <a href="customers.php" class="<?= nav_active('customers', $active_page) ?>"><span class="icon">👥</span> Customers</a>
    <a href="employees.php" class="<?= nav_active('employees', $active_page) ?>"><span class="icon">🧑‍💼</span> Employees</a>
    <a href="products.php" class="<?= nav_active('products', $active_page) ?>"><span class="icon">📦</span> Products</a>
    <a href="orders.php" class="<?= nav_active('orders', $active_page) ?>"><span class="icon">🧾</span> Orders</a>
    <a href="reports.php" class="<?= nav_active('reports', $active_page) ?>"><span class="icon">📈</span> Reports</a>
    <a href="search.php" class="<?= nav_active('search', $active_page) ?>"><span class="icon">🔍</span> Search</a>
    <a href="profile.php" class="<?= nav_active('profile', $active_page) ?>"><span class="icon">⚙️</span> Profile</a>
    <a href="logout.php"><span class="icon">🚪</span> Logout</a>
  </nav>
</aside>
