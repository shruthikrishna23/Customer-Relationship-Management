<?php
require_once 'config.php';
require_login();

$active_page = 'customers';
$page_title = 'Customer Management';
$message = '';

// ---------- Generate next customer code ----------
function next_customer_code($conn) {
    $res = $conn->query("SELECT customer_code FROM customers ORDER BY id DESC LIMIT 1");
    if ($res && $row = $res->fetch_assoc()) {
        $num = (int)substr($row['customer_code'], 1) + 1;
    } else {
        $num = 1;
    }
    return 'C' . str_pad($num, 3, '0', STR_PAD_LEFT);
}

// ---------- ADD CUSTOMER ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name    = trim($_POST['name']);
    $email   = trim($_POST['email']);
    $phone   = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $status  = $_POST['status'];
    $code    = next_customer_code($conn);

    $stmt = $conn->prepare("INSERT INTO customers (customer_code, name, email, phone, address, status) VALUES (?,?,?,?,?,?)");
    $stmt->bind_param("ssssss", $code, $name, $email, $phone, $address, $status);
    if ($stmt->execute()) {
        log_activity($conn, "New customer \"$name\" added", "Customer");
        $message = "Customer added successfully.";
    }
    $stmt->close();
}

// ---------- EDIT CUSTOMER ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id      = (int)$_POST['id'];
    $name    = trim($_POST['name']);
    $email   = trim($_POST['email']);
    $phone   = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $status  = $_POST['status'];

    $stmt = $conn->prepare("UPDATE customers SET name=?, email=?, phone=?, address=?, status=? WHERE id=?");
    $stmt->bind_param("sssssi", $name, $email, $phone, $address, $status, $id);
    if ($stmt->execute()) {
        log_activity($conn, "Customer \"$name\" updated", "Customer");
        $message = "Customer updated successfully.";
    }
    $stmt->close();
}

// ---------- DELETE CUSTOMER ----------
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM customers WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: customers.php?deleted=1");
    exit;
}
if (isset($_GET['deleted'])) $message = "Customer deleted successfully.";

// ---------- SEARCH + LIST ----------
$search = trim($_GET['q'] ?? '');
if ($search !== '') {
    $stmt = $conn->prepare("SELECT * FROM customers WHERE name LIKE ? OR customer_code LIKE ? OR email LIKE ? ORDER BY id DESC");
    $like = "%$search%";
    $stmt->bind_param("sss", $like, $like, $like);
    $stmt->execute();
    $customers = $stmt->get_result();
} else {
    $customers = $conn->query("SELECT * FROM customers ORDER BY id DESC");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Customer Management - CRM System</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-shell">
  <?php include 'includes/sidebar.php'; ?>
  <main class="main">
    <?php include 'includes/topbar.php'; ?>

    <?php if ($message): ?><div class="alert alert-success"><?= h($message) ?></div><?php endif; ?>

    <div class="card">
      <div class="card-header">
        <h3>Customer List</h3>
        <div style="display:flex; gap:10px;">
          <form class="search-form" method="GET" action="customers.php">
            <input type="text" name="q" placeholder="Search by name, code, email" value="<?= h($search) ?>">
            <button class="btn btn-primary btn-sm" type="submit">Search</button>
          </form>
          <button class="btn btn-success btn-sm" onclick="document.getElementById('addModal').classList.add('show')">+ Add Customer</button>
        </div>
      </div>

      <table class="data-table">
        <thead>
          <tr>
            <th>Customer ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Status</th><th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php if ($customers->num_rows === 0): ?>
          <tr><td colspan="6">No customers found.</td></tr>
        <?php else: while ($c = $customers->fetch_assoc()): ?>
          <tr>
            <td><?= h($c['customer_code']) ?></td>
            <td><?= h($c['name']) ?></td>
            <td><?= h($c['email']) ?></td>
            <td><?= h($c['phone']) ?></td>
            <td><span class="badge badge-<?= strtolower($c['status']) ?>"><?= h($c['status']) ?></span></td>
            <td class="action-icons">
              <button class="btn btn-primary btn-sm" onclick='openEdit(<?= json_encode($c) ?>)'>Edit</button>
              <a class="btn btn-danger btn-sm" href="customers.php?delete=<?= $c['id'] ?>" onclick="return confirm('Delete this customer?')">Delete</a>
            </td>
          </tr>
        <?php endwhile; endif; ?>
        </tbody>
      </table>
    </div>
  </main>
</div>

<!-- Add Customer Modal -->
<div class="modal-overlay" id="addModal">
  <div class="modal-box">
    <h3>Add Customer</h3>
    <form method="POST" action="customers.php">
      <input type="hidden" name="action" value="add">
      <div class="form-group"><label>Name</label><input type="text" name="name" required></div>
      <div class="form-group"><label>Email</label><input type="email" name="email"></div>
      <div class="form-group"><label>Phone</label><input type="text" name="phone"></div>
      <div class="form-group"><label>Address</label><input type="text" name="address"></div>
      <div class="form-group">
        <label>Status</label>
        <select name="status"><option value="Active">Active</option><option value="Inactive">Inactive</option></select>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn" onclick="document.getElementById('addModal').classList.remove('show')">Cancel</button>
        <button type="submit" class="btn btn-success">Save Customer</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Customer Modal -->
<div class="modal-overlay" id="editModal">
  <div class="modal-box">
    <h3>Edit Customer</h3>
    <form method="POST" action="customers.php">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" id="edit_id">
      <div class="form-group"><label>Name</label><input type="text" name="name" id="edit_name" required></div>
      <div class="form-group"><label>Email</label><input type="email" name="email" id="edit_email"></div>
      <div class="form-group"><label>Phone</label><input type="text" name="phone" id="edit_phone"></div>
      <div class="form-group"><label>Address</label><input type="text" name="address" id="edit_address"></div>
      <div class="form-group">
        <label>Status</label>
        <select name="status" id="edit_status"><option value="Active">Active</option><option value="Inactive">Inactive</option></select>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn" onclick="document.getElementById('editModal').classList.remove('show')">Cancel</button>
        <button type="submit" class="btn btn-primary">Update Customer</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEdit(c) {
  document.getElementById('edit_id').value = c.id;
  document.getElementById('edit_name').value = c.name;
  document.getElementById('edit_email').value = c.email || '';
  document.getElementById('edit_phone').value = c.phone || '';
  document.getElementById('edit_address').value = c.address || '';
  document.getElementById('edit_status').value = c.status;
  document.getElementById('editModal').classList.add('show');
}
</script>
</body>
</html>
