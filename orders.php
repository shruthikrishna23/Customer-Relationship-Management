<?php
require_once 'config.php';
require_login();

$active_page = 'orders';
$page_title = 'Order Management';
$message = '';

function next_order_code($conn) {
    $res = $conn->query("SELECT order_code FROM orders ORDER BY id DESC LIMIT 1");
    if ($res && $row = $res->fetch_assoc()) {
        $num = (int)substr($row['order_code'], 3) + 1;
    } else {
        $num = 1;
    }
    return 'ORD' . str_pad($num, 3, '0', STR_PAD_LEFT);
}

// ---------- PLACE ORDER ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'place_order') {
    $customer_id = (int)$_POST['customer_id'];
    $order_date  = $_POST['order_date'];
    $product_ids = $_POST['product_id'] ?? [];
    $quantities  = $_POST['quantity'] ?? [];

    if ($customer_id && !empty($product_ids)) {
        $conn->begin_transaction();
        try {
            $code = next_order_code($conn);
            $stmt = $conn->prepare("INSERT INTO orders (order_code, customer_id, order_date, status, total_amount) VALUES (?,?,?, 'Pending', 0)");
            $stmt->bind_param("sis", $code, $customer_id, $order_date);
            $stmt->execute();
            $order_id = $stmt->insert_id;
            $stmt->close();

            $total = 0;
            foreach ($product_ids as $i => $pid) {
                $pid = (int)$pid;
                $qty = max(1, (int)$quantities[$i]);
                $pres = $conn->query("SELECT price, stock, name FROM products WHERE id=$pid");
                $prod = $pres->fetch_assoc();
                if (!$prod) continue;
                $subtotal = $prod['price'] * $qty;
                $total += $subtotal;

                $item = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal) VALUES (?,?,?,?,?)");
                $item->bind_param("iiidd", $order_id, $pid, $qty, $prod['price'], $subtotal);
                $item->execute();
                $item->close();

                // reduce stock
                $conn->query("UPDATE products SET stock = stock - $qty WHERE id=$pid AND stock >= $qty");
            }

            $upd = $conn->prepare("UPDATE orders SET total_amount=? WHERE id=?");
            $upd->bind_param("di", $total, $order_id);
            $upd->execute();
            $upd->close();

            $conn->commit();
            log_activity($conn, "Order $code placed (₹" . number_format($total, 2) . ")", "Order");
            $message = "Order $code placed successfully.";
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Error placing order: " . $e->getMessage();
        }
    } else {
        $message = "Please select a customer and at least one product.";
    }
}

// ---------- UPDATE ORDER STATUS ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
    $id = (int)$_POST['id'];
    $status = $_POST['status'];
    $stmt = $conn->prepare("UPDATE orders SET status=? WHERE id=?");
    $stmt->bind_param("si", $status, $id);
    if ($stmt->execute()) {
        log_activity($conn, "Order status updated to \"$status\"", "Order");
        $message = "Order status updated.";
    }
    $stmt->close();
}

$customers = $conn->query("SELECT id, customer_code, name FROM customers WHERE status='Active' ORDER BY name");
$products_list = $conn->query("SELECT id, product_code, name, price, stock FROM products ORDER BY name");

// Order history with customer name
$orders = $conn->query("SELECT o.*, c.name AS customer_name, c.customer_code FROM orders o
                         JOIN customers c ON o.customer_id = c.id
                         ORDER BY o.id DESC");

// If invoice requested
$invoice = null;
if (isset($_GET['invoice'])) {
    $oid = (int)$_GET['invoice'];
    $ostmt = $conn->prepare("SELECT o.*, c.name AS customer_name, c.email, c.phone, c.address FROM orders o JOIN customers c ON o.customer_id=c.id WHERE o.id=?");
    $ostmt->bind_param("i", $oid);
    $ostmt->execute();
    $invoice = $ostmt->get_result()->fetch_assoc();
    $ostmt->close();

    $items_stmt = $conn->prepare("SELECT oi.*, p.name AS product_name, p.product_code FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id=?");
    $items_stmt->bind_param("i", $oid);
    $items_stmt->execute();
    $invoice_items = $items_stmt->get_result();
    $items_stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Order Management - CRM System</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-shell">
  <?php include 'includes/sidebar.php'; ?>
  <main class="main">
    <?php include 'includes/topbar.php'; ?>

    <?php if ($message): ?><div class="alert alert-success"><?= h($message) ?></div><?php endif; ?>

    <?php if ($invoice): ?>
    <!-- ===================== INVOICE VIEW ===================== -->
    <div class="card">
      <div class="card-header">
        <h3>Invoice - <?= h($invoice['order_code']) ?></h3>
        <a href="orders.php" class="btn btn-sm">← Back to Orders</a>
      </div>
      <p><strong>Customer:</strong> <?= h($invoice['customer_name']) ?><br>
         <strong>Email:</strong> <?= h($invoice['email']) ?> | <strong>Phone:</strong> <?= h($invoice['phone']) ?><br>
         <strong>Address:</strong> <?= h($invoice['address']) ?><br>
         <strong>Order Date:</strong> <?= h($invoice['order_date']) ?> |
         <strong>Status:</strong> <span class="badge badge-<?= strtolower($invoice['status']) ?>"><?= h($invoice['status']) ?></span>
      </p>
      <table class="data-table">
        <thead><tr><th>Product Code</th><th>Product</th><th>Qty</th><th>Unit Price</th><th>Subtotal</th></tr></thead>
        <tbody>
        <?php while ($it = $invoice_items->fetch_assoc()): ?>
          <tr>
            <td><?= h($it['product_code']) ?></td>
            <td><?= h($it['product_name']) ?></td>
            <td><?= (int)$it['quantity'] ?></td>
            <td>₹<?= number_format($it['unit_price'], 2) ?></td>
            <td>₹<?= number_format($it['subtotal'], 2) ?></td>
          </tr>
        <?php endwhile; ?>
        </tbody>
        <tfoot>
          <tr><td colspan="4" style="text-align:right;"><strong>Grand Total</strong></td><td><strong>₹<?= number_format($invoice['total_amount'], 2) ?></strong></td></tr>
        </tfoot>
      </table>
      <button class="btn btn-primary" onclick="window.print()" style="margin-top:14px;">🖨 Print Invoice</button>
    </div>
    <?php else: ?>

    <!-- ===================== PLACE ORDER ===================== -->
    <div class="card">
      <div class="card-header"><h3>Place New Order</h3></div>
      <form method="POST" action="orders.php" id="orderForm">
        <input type="hidden" name="action" value="place_order">
        <div class="grid-2">
          <div class="form-group">
            <label>Customer</label>
            <select name="customer_id" required>
              <option value="">-- Select Customer --</option>
              <?php mysqli_data_seek($customers, 0); while ($c = $customers->fetch_assoc()): ?>
                <option value="<?= $c['id'] ?>"><?= h($c['customer_code'] . ' - ' . $c['name']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Order Date</label>
            <input type="date" name="order_date" value="<?= date('Y-m-d') ?>" required>
          </div>
        </div>

        <div id="itemsWrap">
          <div class="grid-2 item-row">
            <div class="form-group">
              <label>Product</label>
              <select name="product_id[]" required>
                <option value="">-- Select Product --</option>
                <?php mysqli_data_seek($products_list, 0); while ($p = $products_list->fetch_assoc()): ?>
                  <option value="<?= $p['id'] ?>">
                    <?= h($p['product_code'] . ' - ' . $p['name'] . ' (₹' . number_format($p['price'],2) . ', stock: ' . $p['stock'] . ')') ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Quantity</label>
              <input type="number" name="quantity[]" min="1" value="1" required>
            </div>
          </div>
        </div>
        <button type="button" class="btn btn-sm" onclick="addItemRow()">+ Add Another Product</button>
        <br><br>
        <button type="submit" class="btn btn-success">Place Order</button>
      </form>
    </div>

    <!-- ===================== ORDER HISTORY ===================== -->
    <div class="card">
      <div class="card-header"><h3>Order History</h3></div>
      <table class="data-table">
        <thead><tr><th>Order Code</th><th>Customer</th><th>Date</th><th>Total</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if ($orders->num_rows === 0): ?>
          <tr><td colspan="6">No orders placed yet.</td></tr>
        <?php else: while ($o = $orders->fetch_assoc()): ?>
          <tr>
            <td><?= h($o['order_code']) ?></td>
            <td><?= h($o['customer_name']) ?> (<?= h($o['customer_code']) ?>)</td>
            <td><?= h($o['order_date']) ?></td>
            <td>₹<?= number_format($o['total_amount'], 2) ?></td>
            <td><span class="badge badge-<?= strtolower($o['status']) ?>"><?= h($o['status']) ?></span></td>
            <td class="action-icons">
              <a class="btn btn-sm" href="orders.php?invoice=<?= $o['id'] ?>">Invoice</a>
              <button class="btn btn-warning btn-sm" onclick='openStatus(<?= json_encode(["id"=>$o['id'], "status"=>$o['status']]) ?>)'>Update Status</button>
            </td>
          </tr>
        <?php endwhile; endif; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </main>
</div>

<!-- Update Status Modal -->
<div class="modal-overlay" id="statusModal">
  <div class="modal-box">
    <h3>Update Order Status</h3>
    <form method="POST" action="orders.php">
      <input type="hidden" name="action" value="update_status">
      <input type="hidden" name="id" id="status_id">
      <div class="form-group">
        <label>Status</label>
        <select name="status" id="status_value">
          <option value="Pending">Pending</option>
          <option value="Processing">Processing</option>
          <option value="Shipped">Shipped</option>
          <option value="Delivered">Delivered</option>
          <option value="Cancelled">Cancelled</option>
        </select>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn" onclick="document.getElementById('statusModal').classList.remove('show')">Cancel</button>
        <button type="submit" class="btn btn-primary">Update</button>
      </div>
    </form>
  </div>
</div>

<script>
function openStatus(o) {
  document.getElementById('status_id').value = o.id;
  document.getElementById('status_value').value = o.status;
  document.getElementById('statusModal').classList.add('show');
}
function addItemRow() {
  const wrap = document.getElementById('itemsWrap');
  const row = wrap.querySelector('.item-row').cloneNode(true);
  row.querySelectorAll('select, input').forEach(el => { if (el.name === 'quantity[]') el.value = 1; });
  wrap.appendChild(row);
}
</script>
</body>
</html>
