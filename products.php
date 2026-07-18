<?php
require_once 'config.php';
require_login();

$active_page = 'products';
$page_title = 'Product & Service Management';
$message = '';

function next_product_code($conn) {
    $res = $conn->query("SELECT product_code FROM products ORDER BY id DESC LIMIT 1");
    if ($res && $row = $res->fetch_assoc()) {
        $num = (int)substr($row['product_code'], 1) + 1;
    } else {
        $num = 1;
    }
    return 'P' . str_pad($num, 3, '0', STR_PAD_LEFT);
}

// ---------- ADD ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $name     = trim($_POST['name']);
    $category = trim($_POST['category']);
    $price    = (float)$_POST['price'];
    $stock    = (int)$_POST['stock'];
    $code     = next_product_code($conn);

    $stmt = $conn->prepare("INSERT INTO products (product_code, name, category, price, stock) VALUES (?,?,?,?,?)");
    $stmt->bind_param("sssdi", $code, $name, $category, $price, $stock);
    if ($stmt->execute()) {
        log_activity($conn, "Product \"$name\" added", "Product");
        $message = "Product added successfully.";
    }
    $stmt->close();
}

// ---------- EDIT ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {
    $id       = (int)$_POST['id'];
    $name     = trim($_POST['name']);
    $category = trim($_POST['category']);
    $price    = (float)$_POST['price'];
    $stock    = (int)$_POST['stock'];

    $stmt = $conn->prepare("UPDATE products SET name=?, category=?, price=?, stock=? WHERE id=?");
    $stmt->bind_param("ssdii", $name, $category, $price, $stock, $id);
    if ($stmt->execute()) {
        log_activity($conn, "Product \"$name\" stock/details updated", "Product");
        $message = "Product updated successfully.";
    }
    $stmt->close();
}

// ---------- DELETE ----------
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM products WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: products.php?deleted=1");
    exit;
}
if (isset($_GET['deleted'])) $message = "Product deleted successfully.";

$products = $conn->query("SELECT * FROM products ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Product Management - CRM System</title>
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
        <h3>Product / Service List</h3>
        <button class="btn btn-success btn-sm" onclick="document.getElementById('addModal').classList.add('show')">+ Add Product</button>
      </div>

      <table class="data-table">
        <thead><tr><th>Code</th><th>Product</th><th>Category</th><th>Price</th><th>Stock</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if ($products->num_rows === 0): ?>
          <tr><td colspan="6">No products found.</td></tr>
        <?php else: while ($p = $products->fetch_assoc()): ?>
          <tr>
            <td><?= h($p['product_code']) ?></td>
            <td><?= h($p['name']) ?></td>
            <td><?= h($p['category']) ?></td>
            <td>₹<?= number_format($p['price'], 2) ?></td>
            <td><?= (int)$p['stock'] ?><?php if ($p['stock'] < 10): ?> <span class="badge badge-cancelled">Low</span><?php endif; ?></td>
            <td class="action-icons">
              <button class="btn btn-primary btn-sm" onclick='openEdit(<?= json_encode($p) ?>)'>Edit</button>
              <a class="btn btn-danger btn-sm" href="products.php?delete=<?= $p['id'] ?>" onclick="return confirm('Delete this product?')">Delete</a>
            </td>
          </tr>
        <?php endwhile; endif; ?>
        </tbody>
      </table>
    </div>
  </main>
</div>

<!-- Add Product Modal -->
<div class="modal-overlay" id="addModal">
  <div class="modal-box">
    <h3>Add Product</h3>
    <form method="POST" action="products.php">
      <input type="hidden" name="action" value="add">
      <div class="form-group"><label>Product Name</label><input type="text" name="name" required></div>
      <div class="form-group"><label>Category</label><input type="text" name="category"></div>
      <div class="grid-2">
        <div class="form-group"><label>Price (₹)</label><input type="number" step="0.01" name="price" required></div>
        <div class="form-group"><label>Stock</label><input type="number" name="stock" required></div>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn" onclick="document.getElementById('addModal').classList.remove('show')">Cancel</button>
        <button type="submit" class="btn btn-success">Save Product</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Product Modal -->
<div class="modal-overlay" id="editModal">
  <div class="modal-box">
    <h3>Edit Product</h3>
    <form method="POST" action="products.php">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" id="edit_id">
      <div class="form-group"><label>Product Name</label><input type="text" name="name" id="edit_name" required></div>
      <div class="form-group"><label>Category</label><input type="text" name="category" id="edit_category"></div>
      <div class="grid-2">
        <div class="form-group"><label>Price (₹)</label><input type="number" step="0.01" name="price" id="edit_price" required></div>
        <div class="form-group"><label>Stock</label><input type="number" name="stock" id="edit_stock" required></div>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn" onclick="document.getElementById('editModal').classList.remove('show')">Cancel</button>
        <button type="submit" class="btn btn-primary">Update Product</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEdit(p) {
  document.getElementById('edit_id').value = p.id;
  document.getElementById('edit_name').value = p.name;
  document.getElementById('edit_category').value = p.category || '';
  document.getElementById('edit_price').value = p.price;
  document.getElementById('edit_stock').value = p.stock;
  document.getElementById('editModal').classList.add('show');
}
</script>
</body>
</html>
