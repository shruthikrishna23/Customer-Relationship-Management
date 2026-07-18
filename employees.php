<?php
require_once 'config.php';
require_login();

$active_page = 'employees';
$page_title = 'Employee Management';
$message = '';

function next_employee_code($conn) {
    $res = $conn->query("SELECT employee_code FROM employees ORDER BY id DESC LIMIT 1");
    if ($res && $row = $res->fetch_assoc()) {
        $num = (int)substr($row['employee_code'], 1) + 1;
    } else {
        $num = 1;
    }
    return 'E' . str_pad($num, 3, '0', STR_PAD_LEFT);
}

// ---------- ADD (Registration) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $name        = trim($_POST['name']);
    $email       = trim($_POST['email']);
    $phone       = trim($_POST['phone']);
    $designation = trim($_POST['designation']);
    $department  = trim($_POST['department']);
    $salary      = (float)$_POST['salary'];
    $joining     = $_POST['joining_date'];
    $status      = $_POST['status'];
    $code        = next_employee_code($conn);

    $stmt = $conn->prepare("INSERT INTO employees (employee_code, name, email, phone, designation, department, salary, joining_date, status) VALUES (?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param("ssssssdss", $code, $name, $email, $phone, $designation, $department, $salary, $joining, $status);
    if ($stmt->execute()) {
        log_activity($conn, "New employee \"$name\" registered", "Employee");
        $message = "Employee registered successfully.";
    }
    $stmt->close();
}

// ---------- EDIT ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {
    $id          = (int)$_POST['id'];
    $name        = trim($_POST['name']);
    $email       = trim($_POST['email']);
    $phone       = trim($_POST['phone']);
    $designation = trim($_POST['designation']);
    $department  = trim($_POST['department']);
    $salary      = (float)$_POST['salary'];
    $joining     = $_POST['joining_date'];
    $status      = $_POST['status'];

    $stmt = $conn->prepare("UPDATE employees SET name=?, email=?, phone=?, designation=?, department=?, salary=?, joining_date=?, status=? WHERE id=?");
    $stmt->bind_param("sssssdssi", $name, $email, $phone, $designation, $department, $salary, $joining, $status, $id);
    if ($stmt->execute()) {
        log_activity($conn, "Employee \"$name\" details updated", "Employee");
        $message = "Employee updated successfully.";
    }
    $stmt->close();
}

// ---------- DELETE ----------
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM employees WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: employees.php?deleted=1");
    exit;
}
if (isset($_GET['deleted'])) $message = "Employee deleted successfully.";

$employees = $conn->query("SELECT * FROM employees ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Employee Management - CRM System</title>
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
        <h3>Employee List</h3>
        <button class="btn btn-success btn-sm" onclick="document.getElementById('addModal').classList.add('show')">+ Register Employee</button>
      </div>

      <table class="data-table">
        <thead>
          <tr><th>Emp ID</th><th>Name</th><th>Designation</th><th>Department</th><th>Salary</th><th>Status</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php if ($employees->num_rows === 0): ?>
          <tr><td colspan="7">No employees found.</td></tr>
        <?php else: while ($e = $employees->fetch_assoc()): ?>
          <tr>
            <td><?= h($e['employee_code']) ?></td>
            <td><?= h($e['name']) ?></td>
            <td><?= h($e['designation']) ?></td>
            <td><?= h($e['department']) ?></td>
            <td>₹<?= number_format($e['salary'], 2) ?></td>
            <td><span class="badge badge-<?= strtolower($e['status']) ?>"><?= h($e['status']) ?></span></td>
            <td class="action-icons">
              <button class="btn btn-primary btn-sm" onclick='openEdit(<?= json_encode($e) ?>)'>Edit</button>
              <a class="btn btn-danger btn-sm" href="employees.php?delete=<?= $e['id'] ?>" onclick="return confirm('Delete this employee?')">Delete</a>
            </td>
          </tr>
        <?php endwhile; endif; ?>
        </tbody>
      </table>
    </div>
  </main>
</div>

<!-- Add Employee Modal -->
<div class="modal-overlay" id="addModal">
  <div class="modal-box">
    <h3>Employee Registration</h3>
    <form method="POST" action="employees.php">
      <input type="hidden" name="action" value="add">
      <div class="form-group"><label>Name</label><input type="text" name="name" required></div>
      <div class="form-group"><label>Email</label><input type="email" name="email"></div>
      <div class="form-group"><label>Phone</label><input type="text" name="phone"></div>
      <div class="grid-2">
        <div class="form-group"><label>Designation</label><input type="text" name="designation"></div>
        <div class="form-group"><label>Department</label><input type="text" name="department"></div>
      </div>
      <div class="grid-2">
        <div class="form-group"><label>Salary</label><input type="number" step="0.01" name="salary" required></div>
        <div class="form-group"><label>Joining Date</label><input type="date" name="joining_date" required></div>
      </div>
      <div class="form-group">
        <label>Status</label>
        <select name="status"><option value="Active">Active</option><option value="Inactive">Inactive</option></select>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn" onclick="document.getElementById('addModal').classList.remove('show')">Cancel</button>
        <button type="submit" class="btn btn-success">Save Employee</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Employee Modal -->
<div class="modal-overlay" id="editModal">
  <div class="modal-box">
    <h3>Edit Employee</h3>
    <form method="POST" action="employees.php">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" id="edit_id">
      <div class="form-group"><label>Name</label><input type="text" name="name" id="edit_name" required></div>
      <div class="form-group"><label>Email</label><input type="email" name="email" id="edit_email"></div>
      <div class="form-group"><label>Phone</label><input type="text" name="phone" id="edit_phone"></div>
      <div class="grid-2">
        <div class="form-group"><label>Designation</label><input type="text" name="designation" id="edit_designation"></div>
        <div class="form-group"><label>Department</label><input type="text" name="department" id="edit_department"></div>
      </div>
      <div class="grid-2">
        <div class="form-group"><label>Salary</label><input type="number" step="0.01" name="salary" id="edit_salary" required></div>
        <div class="form-group"><label>Joining Date</label><input type="date" name="joining_date" id="edit_joining_date" required></div>
      </div>
      <div class="form-group">
        <label>Status</label>
        <select name="status" id="edit_status"><option value="Active">Active</option><option value="Inactive">Inactive</option></select>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn" onclick="document.getElementById('editModal').classList.remove('show')">Cancel</button>
        <button type="submit" class="btn btn-primary">Update Employee</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEdit(e) {
  document.getElementById('edit_id').value = e.id;
  document.getElementById('edit_name').value = e.name;
  document.getElementById('edit_email').value = e.email || '';
  document.getElementById('edit_phone').value = e.phone || '';
  document.getElementById('edit_designation').value = e.designation || '';
  document.getElementById('edit_department').value = e.department || '';
  document.getElementById('edit_salary').value = e.salary;
  document.getElementById('edit_joining_date').value = e.joining_date;
  document.getElementById('edit_status').value = e.status;
  document.getElementById('editModal').classList.add('show');
}
</script>
</body>
</html>
