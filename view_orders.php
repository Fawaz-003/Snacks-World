<?php
require_once 'db.php';
session_start();
// Authentication: Only allow 'admin' users
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin.php");
    exit;
}

$status_message = "";

// On status update: move to correct table and delete from order table
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_status'], $_POST['order_id'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = $_POST['update_status'];
    $allowed_statuses = ["ordered", "dispatched", "delivered", "cancelled"];
    if (in_array($new_status, $allowed_statuses)) {
        // Fetch the order to move
        $stmt = $conn->prepare("SELECT * FROM `order` WHERE id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $order_res = $stmt->get_result();
        if ($order_row = $order_res->fetch_assoc()) {
            if ($new_status == "delivered") {
                // Insert into delivered_order
                $insert_stmt = $conn->prepare("INSERT INTO delivered_order (order_id, customer_id, item_id, quantity, status, created_at, delivered_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $insert_stmt->bind_param(
                    "iiiiss",
                    $order_row['id'],
                    $order_row['customer_id'],
                    $order_row['item_id'],
                    $order_row['quantity'],
                    $new_status,
                    $order_row['created_at']
                );
                if ($insert_stmt->execute()) {
                    $delete_stmt = $conn->prepare("DELETE FROM `order` WHERE id = ?");
                    $delete_stmt->bind_param("i", $order_id);
                    $delete_stmt->execute();
                    $delete_stmt->close();
                    $status_message = "<div class='alert alert-success'>Order marked as delivered and moved to delivered_order table.</div>";
                } else {
                    $status_message = "<div class='alert alert-danger'>Failed to move order to delivered_order.</div>";
                }
                $insert_stmt->close();
            } elseif ($new_status == "dispatched") {
                // Insert into dispatched_order
                $insert_stmt = $conn->prepare("INSERT INTO dispatched_order (order_id, customer_id, item_id, quantity, status, created_at, dispatched_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $insert_stmt->bind_param(
                    "iiiiss",
                    $order_row['id'],
                    $order_row['customer_id'],
                    $order_row['item_id'],
                    $order_row['quantity'],
                    $new_status,
                    $order_row['created_at']
                );
                if ($insert_stmt->execute()) {
                    $delete_stmt = $conn->prepare("DELETE FROM `order` WHERE id = ?");
                    $delete_stmt->bind_param("i", $order_id);
                    $delete_stmt->execute();
                    $delete_stmt->close();
                    $status_message = "<div class='alert alert-success'>Order marked as dispatched and moved to dispatched_order table.</div>";
                } else {
                    $status_message = "<div class='alert alert-danger'>Failed to move order to dispatched_order.</div>";
                }
                $insert_stmt->close();
            } elseif ($new_status == "cancelled") {
                // Insert into cancelled_order
                $insert_stmt = $conn->prepare("INSERT INTO cancelled_order (order_id, customer_id, item_id, quantity, status, created_at, cancelled_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $insert_stmt->bind_param(
                    "iiiiss",
                    $order_row['id'],
                    $order_row['customer_id'],
                    $order_row['item_id'],
                    $order_row['quantity'],
                    $new_status,
                    $order_row['created_at']
                );
                if ($insert_stmt->execute()) {
                    $delete_stmt = $conn->prepare("DELETE FROM `order` WHERE id = ?");
                    $delete_stmt->bind_param("i", $order_id);
                    $delete_stmt->execute();
                    $delete_stmt->close();
                    $status_message = "<div class='alert alert-success'>Order marked as cancelled and moved to cancelled_order table.</div>";
                } else {
                    $status_message = "<div class='alert alert-danger'>Failed to move order to cancelled_order.</div>";
                }
                $insert_stmt->close();
            } else {
                // Else just update status
                $stmt2 = $conn->prepare("UPDATE `order` SET status = ? WHERE id = ?");
                $stmt2->bind_param("si", $new_status, $order_id);
                if ($stmt2->execute()) {
                    $status_message = "<div class='alert alert-success'>Order status updated.</div>";
                } else {
                    $status_message = "<div class='alert alert-danger'>Failed to update status.</div>";
                }
                $stmt2->close();
            }
        }
        $stmt->close();
    }
}

// Fetch all orders, joined with customer and item (only those not delivered, cancelled, or dispatched)
$order_sql = "SELECT o.*, 
                     c.name AS customer_name, c.mobile AS customer_mobile, c.address AS customer_address,
                     i.name AS item_name, i.image AS item_image, i.price AS item_price
              FROM `order` o
              LEFT JOIN customer c ON o.customer_id = c.id
              LEFT JOIN item i ON o.item_id = i.id
              ORDER BY o.id DESC";
$order_result = $conn->query($order_sql);
$order_list = [];
while ($row = $order_result->fetch_assoc()) {
    $order_list[] = $row;
}

// Dispatched orders
$dispatched_sql = "SELECT d.*, 
                         c.name AS customer_name, c.mobile AS customer_mobile, c.address AS customer_address,
                         i.name AS item_name, i.image AS item_image, i.price AS item_price
                  FROM dispatched_order d
                  LEFT JOIN customer c ON d.customer_id = c.id
                  LEFT JOIN item i ON d.item_id = i.id
                  ORDER BY d.id DESC";
$dispatched_result = $conn->query($dispatched_sql);
$dispatched_list = [];
while ($row = $dispatched_result->fetch_assoc()) {
    $dispatched_list[] = $row;
}

// Delivered orders
$delivered_sql = "SELECT d.*, 
                         c.name AS customer_name, c.mobile AS customer_mobile, c.address AS customer_address,
                         i.name AS item_name, i.image AS item_image, i.price AS item_price
                  FROM delivered_order d
                  LEFT JOIN customer c ON d.customer_id = c.id
                  LEFT JOIN item i ON d.item_id = i.id
                  ORDER BY d.id DESC";
$delivered_result = $conn->query($delivered_sql);
$delivered_list = [];
while ($row = $delivered_result->fetch_assoc()) {
    $delivered_list[] = $row;
}

// Cancelled orders
$cancelled_sql = "SELECT d.*, 
                         c.name AS customer_name, c.mobile AS customer_mobile, c.address AS customer_address,
                         i.name AS item_name, i.image AS item_image, i.price AS item_price
                  FROM cancelled_order d
                  LEFT JOIN customer c ON d.customer_id = c.id
                  LEFT JOIN item i ON d.item_id = i.id
                  ORDER BY d.id DESC";
$cancelled_result = $conn->query($cancelled_sql);
$cancelled_list = [];
while ($row = $cancelled_result->fetch_assoc()) {
    $cancelled_list[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>All Orders | Snack World Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"/>
    <style>
        body { background: #101326; color: #fff; font-family: 'Roboto', Arial, sans-serif; }
        .navbar { background: #0a0e22 !important; }
        .navbar-brand { font-family: 'Montserrat', sans-serif; font-weight: 700; font-size: 2rem; color: #ffb200 !important; }
        .nav-link { color: #fff !important; font-family: 'Montserrat', sans-serif; font-weight: 700; }
        .nav-link.active, .nav-link:hover { color: #ffb200 !important; }
        .card { background: #171b32; border: none; border-radius: 16px; color: #fff; box-shadow: 0 4px 24px rgba(0,0,0,0.08);}
        .order-table th, .order-table td { vertical-align:middle; }
        .order-table th { color: #ffb200; }
        .order-status-ordered { color: #ffc107; font-weight: 600;}
        .order-status-cancelled { color: #dc3545; font-weight: 600;}
        .order-status-dispatched { color: #0dcaf0; font-weight: 600;}
        .order-status-delivered { color: #28a745; font-weight: 600;}
        .order-status-other { color: #adb5bd; font-weight: 600;}
        .admin-action-form select { min-width: 120px; }
        .admin-action-form button { margin-left: 8px; }
    </style>
</head>
<body>
<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="admin_dashboard.php"><i class="fa-solid fa-utensils"></i>Snack World <span class="d-none d-md-inline">Admin</span></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav" 
      aria-controls="adminNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon" style="color: #ffb200"></span>
    </button>
    <div class="collapse navbar-collapse" id="adminNav">
      <ul class="navbar-nav ms-auto align-items-center">
        <li class="nav-item">
          <a class="nav-link<?php if(basename($_SERVER['PHP_SELF']) == 'add_item.php') echo ' active'; ?>" href="add_item.php">
            <i class="fa-solid fa-plus"></i> Add Item
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link<?php if(basename($_SERVER['PHP_SELF']) == 'view_orders.php') echo ' active'; ?>" href="view_orders.php">
            <i class="fa-solid fa-list"></i> View Orders
          </a>
        </li>
        <li class="nav-item ms-2">
          <a class="btn btn-danger" href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<div class="container py-4">

    <!-- All Orders Table (current, not dispatched, not delivered, not cancelled) -->
    <div class="card p-4 mb-4">
        <h4 class="mb-3" style="color:#ffb200;"><i class="fa-solid fa-list"></i> All Orders (Pending/Ordered)</h4>
        <?php if ($status_message) echo $status_message; ?>
        <div class="table-responsive">
            <table class="table table-dark table-hover order-table align-middle">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Customer Name</th>
                    <th>Mobile</th>
                    <th>Address</th>
                    <th>Item</th>
                    <th>Image</th>
                    <th>Quantity</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Placed At</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($order_list)): ?>
                    <tr>
                        <td colspan="11" class="text-center text-muted">No orders found.</td>
                    </tr>
                <?php else:
                    foreach ($order_list as $i => $order): ?>
                        <tr>
                            <td><?php echo $i + 1; ?></td>
                            <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                            <td><?php echo htmlspecialchars($order['customer_mobile']); ?></td>
                            <td style="max-width:180px;white-space:pre-wrap;"><?php echo htmlspecialchars($order['customer_address']); ?></td>
                            <td><?php echo htmlspecialchars($order['item_name']); ?></td>
                            <td>
                                <?php if ($order['item_image']): ?>
                                    <img src="<?php echo htmlspecialchars($order['item_image']); ?>" alt="item" width="60" style="border-radius:8px;">
                                <?php else: ?>
                                    <span class="text-muted">No image</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo intval($order['quantity']); ?></td>
                            <td>
                                <?php
                                    $total = isset($order['item_price']) ? ($order['item_price'] * $order['quantity']) : 0;
                                    echo "₹" . number_format($total);
                                ?>
                            </td>
                            <td>
                                <?php
                                $status = strtolower($order['status']);
                                $status_class = "order-status-other";
                                if ($status == "ordered" || $status == "order") $status_class = "order-status-ordered";
                                else if ($status == "cancel" || $status == "cancelled") $status_class = "order-status-cancelled";
                                else if ($status == "dispatched") $status_class = "order-status-dispatched";
                                else if ($status == "delivered") $status_class = "order-status-delivered";
                                ?>
                                <span class="<?php echo $status_class; ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('d-M-Y h:i A', strtotime($order['created_at'])); ?></td>
                            <td>
                                <form method="post" class="admin-action-form d-flex align-items-center">
                                    <input type="hidden" name="order_id" value="<?php echo intval($order['id']); ?>">
                                    <select name="update_status" class="form-select form-select-sm">
                                        <option value="ordered" <?php if($status=="ordered"||$status=="order") echo "selected"; ?>>Ordered</option>
                                        <option value="dispatched" <?php if($status=="dispatched") echo "selected"; ?>>Dispatched</option>
                                        <option value="delivered" <?php if($status=="delivered") echo "selected"; ?>>Delivered</option>
                                        <option value="cancelled" <?php if($status=="cancelled"||$status=="cancel") echo "selected"; ?>>Cancelled</option>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-primary ms-2"><i class="fa-solid fa-arrows-rotate"></i> Update</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach;
                endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Dispatched Orders Table -->
    <div class="card p-4 mb-4">
        <h4 class="mb-3" style="color:#0dcaf0;"><i class="fa-solid fa-truck-fast"></i> Dispatched Orders</h4>
        <div class="table-responsive">
            <table class="table table-dark table-hover order-table align-middle">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Customer Name</th>
                    <th>Mobile</th>
                    <th>Address</th>
                    <th>Item</th>
                    <th>Image</th>
                    <th>Quantity</th>
                    <th>Total</th>
                    <th>Placed At</th>
                    <th>Dispatched At</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($dispatched_list)): ?>
                    <tr>
                        <td colspan="10" class="text-center text-muted">No dispatched orders yet.</td>
                    </tr>
                <?php else:
                    foreach ($dispatched_list as $i => $order): ?>
                        <tr>
                            <td><?php echo $i + 1; ?></td>
                            <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                            <td><?php echo htmlspecialchars($order['customer_mobile']); ?></td>
                            <td style="max-width:180px;white-space:pre-wrap;"><?php echo htmlspecialchars($order['customer_address']); ?></td>
                            <td><?php echo htmlspecialchars($order['item_name']); ?></td>
                            <td>
                                <?php if ($order['item_image']): ?>
                                    <img src="<?php echo htmlspecialchars($order['item_image']); ?>" alt="item" width="60" style="border-radius:8px;">
                                <?php else: ?>
                                    <span class="text-muted">No image</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo intval($order['quantity']); ?></td>
                            <td>
                                <?php
                                    $total = isset($order['item_price']) ? ($order['item_price'] * $order['quantity']) : 0;
                                    echo "₹" . number_format($total);
                                ?>
                            </td>
                            <td><?php echo date('d-M-Y h:i A', strtotime($order['created_at'])); ?></td>
                            <td><?php echo date('d-M-Y h:i A', strtotime($order['dispatched_at'])); ?></td>
                        </tr>
                    <?php endforeach;
                endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Delivered Orders Table -->
    <div class="card p-4 mb-4">
        <h4 class="mb-3" style="color:#28a745;"><i class="fa-solid fa-truck"></i> Delivered Orders</h4>
        <div class="table-responsive">
            <table class="table table-dark table-hover order-table align-middle">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Customer Name</th>
                    <th>Mobile</th>
                    <th>Address</th>
                    <th>Item</th>
                    <th>Image</th>
                    <th>Quantity</th>
                    <th>Total</th>
                    <th>Placed At</th>
                    <th>Delivered At</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($delivered_list)): ?>
                    <tr>
                        <td colspan="10" class="text-center text-muted">No delivered orders yet.</td>
                    </tr>
                <?php else:
                    foreach ($delivered_list as $i => $order): ?>
                        <tr>
                            <td><?php echo $i + 1; ?></td>
                            <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                            <td><?php echo htmlspecialchars($order['customer_mobile']); ?></td>
                            <td style="max-width:180px;white-space:pre-wrap;"><?php echo htmlspecialchars($order['customer_address']); ?></td>
                            <td><?php echo htmlspecialchars($order['item_name']); ?></td>
                            <td>
                                <?php if ($order['item_image']): ?>
                                    <img src="<?php echo htmlspecialchars($order['item_image']); ?>" alt="item" width="60" style="border-radius:8px;">
                                <?php else: ?>
                                    <span class="text-muted">No image</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo intval($order['quantity']); ?></td>
                            <td>
                                <?php
                                    $total = isset($order['item_price']) ? ($order['item_price'] * $order['quantity']) : 0;
                                    echo "₹" . number_format($total);
                                ?>
                            </td>
                            <td><?php echo date('d-M-Y h:i A', strtotime($order['created_at'])); ?></td>
                            <td><?php echo date('d-M-Y h:i A', strtotime($order['delivered_at'])); ?></td>
                        </tr>
                    <?php endforeach;
                endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Cancelled Orders Table -->
    <div class="card p-4 mb-4">
        <h4 class="mb-3" style="color:#dc3545;"><i class="fa-solid fa-ban"></i> Cancelled Orders</h4>
        <div class="table-responsive">
            <table class="table table-dark table-hover order-table align-middle">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Customer Name</th>
                    <th>Mobile</th>
                    <th>Address</th>
                    <th>Item</th>
                    <th>Image</th>
                    <th>Quantity</th>
                    <th>Total</th>
                    <th>Placed At</th>
                    <th>Cancelled At</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($cancelled_list)): ?>
                    <tr>
                        <td colspan="10" class="text-center text-muted">No cancelled orders yet.</td>
                    </tr>
                <?php else:
                    foreach ($cancelled_list as $i => $order): ?>
                        <tr>
                            <td><?php echo $i + 1; ?></td>
                            <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                            <td><?php echo htmlspecialchars($order['customer_mobile']); ?></td>
                            <td style="max-width:180px;white-space:pre-wrap;"><?php echo htmlspecialchars($order['customer_address']); ?></td>
                            <td><?php echo htmlspecialchars($order['item_name']); ?></td>
                            <td>
                                <?php if ($order['item_image']): ?>
                                    <img src="<?php echo htmlspecialchars($order['item_image']); ?>" alt="item" width="60" style="border-radius:8px;">
                                <?php else: ?>
                                    <span class="text-muted">No image</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo intval($order['quantity']); ?></td>
                            <td>
                                <?php
                                    $total = isset($order['item_price']) ? ($order['item_price'] * $order['quantity']) : 0;
                                    echo "₹" . number_format($total);
                                ?>
                            </td>
                            <td><?php echo date('d-M-Y h:i A', strtotime($order['created_at'])); ?></td>
                            <td><?php echo date('d-M-Y h:i A', strtotime($order['cancelled_at'])); ?></td>
                        </tr>
                    <?php endforeach;
                endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>