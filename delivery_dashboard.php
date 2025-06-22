<?php
require_once 'db.php';
session_start();

// Optional: check if user is logged in and is delivery partner
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'delivery partner') {
    header("Location: admin.php");
    exit;
}

$delivery_partner_id = $_SESSION['user_id'];
$delivery_partner_name = $_SESSION['name'] ?? "";

// Message for actions
$status_message = "";

// Mark as delivered logic
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['mark_delivered'], $_POST['dispatched_id'])) {
    $dispatched_id = intval($_POST['dispatched_id']);
    // Fetch the dispatched order
    $stmt = $conn->prepare("SELECT * FROM dispatched_order WHERE id = ?");
    $stmt->bind_param("i", $dispatched_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        // Insert to delivered_order
        $insert_stmt = $conn->prepare("INSERT INTO delivered_order (order_id, customer_id, item_id, quantity, status, created_at, delivered_at)
            VALUES (?, ?, ?, ?, 'delivered', ?, NOW())");
        $insert_stmt->bind_param(
            "iiiis",
            $row['order_id'],
            $row['customer_id'],
            $row['item_id'],
            $row['quantity'],
            $row['created_at']
        );
        if ($insert_stmt->execute()) {
            // Remove from dispatched_order
            $delete_stmt = $conn->prepare("DELETE FROM dispatched_order WHERE id = ?");
            $delete_stmt->bind_param("i", $dispatched_id);
            $delete_stmt->execute();
            $delete_stmt->close();
            $status_message = "<div class='alert alert-success'>Order marked as delivered.</div>";
        } else {
            $status_message = "<div class='alert alert-danger'>Failed to mark as delivered.</div>";
        }
        $insert_stmt->close();
    } else {
        $status_message = "<div class='alert alert-danger'>Order not found.</div>";
    }
    $stmt->close();
}

// Fetch dispatched orders to deliver (all partners see all; if you want to assign, add a field to dispatched_order)
$dispatched_sql = "SELECT d.*, c.name AS customer_name, c.address AS customer_address, c.mobile AS customer_mobile,
                          i.name AS item_name, i.image AS item_image, i.price AS item_price
                   FROM dispatched_order d
                   LEFT JOIN customer c ON d.customer_id = c.id
                   LEFT JOIN item i ON d.item_id = i.id
                   ORDER BY d.dispatched_at DESC";
$dispatched_result = $conn->query($dispatched_sql);
$dispatched_list = [];
while ($row = $dispatched_result->fetch_assoc()) {
    $dispatched_list[] = $row;
}

// Fetch delivered orders by this delivery partner (if you want to associate delivery partner id, add a column to delivered_order)
$delivered_sql = "SELECT d.*, c.name AS customer_name, c.address AS customer_address, c.mobile AS customer_mobile,
                         i.name AS item_name, i.image AS item_image, i.price AS item_price
                  FROM delivered_order d
                  LEFT JOIN customer c ON d.customer_id = c.id
                  LEFT JOIN item i ON d.item_id = i.id
                  ORDER BY d.delivered_at DESC";
$delivered_result = $conn->query($delivered_sql);
$delivered_list = [];
while ($row = $delivered_result->fetch_assoc()) {
    $delivered_list[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Delivery Partner Dashboard | Snack World</title>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"/>
  <style>
    body { background: #101326; color: #fff; font-family: 'Roboto', Arial, sans-serif; }
    .navbar { background: #0a0e22 !important; }
    .navbar-brand { font-family: 'Montserrat', sans-serif; font-weight: 700; font-size: 2rem; color: #ffb200 !important; }
    .nav-link { color: #fff !important; font-family: 'Montserrat', sans-serif; font-weight: 700; }
    .nav-link.active, .nav-link:hover { color: #ffb200 !important; }
    .dashboard-content { min-height: 80vh; }
    .card { background: #171b32; border-radius: 16px; color: #fff; box-shadow: 0 4px 24px rgba(0,0,0,0.08);}
    .order-table th, .order-table td { vertical-align:middle; }
    .order-table th { color: #ffb200; }
    .order-status-dispatched { color: #0dcaf0; font-weight: 600;}
    .order-status-delivered { color: #28a745; font-weight: 600;}
    .btn-deliver { background: #28a745; color: #fff; font-weight: 700; }
    .btn-deliver:hover { background: #1e7e34; }
    .item-img { width: 60px; height: 45px; object-fit: cover; border-radius: 8px; background: #191a25; }
  </style>
</head>
<body>
<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="#"><i class="fa-solid fa-utensils"></i>Snack World <span class="d-none d-md-inline">Delivery</span></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navBar" 
      aria-controls="navBar" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon" style="color: #ffb200"></span>
    </button>
    <div class="collapse navbar-collapse" id="navBar">
      <ul class="navbar-nav ms-auto align-items-center">
        <li class="nav-item">
          <span class="nav-link">Welcome, <span style="color:#ffb200;"><?php echo htmlspecialchars($delivery_partner_name); ?></span></span>
        </li>
        <li class="nav-item ms-2">
          <a class="btn btn-danger" href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<div class="container dashboard-content py-5">
  <div class="row justify-content-center mb-4">
    <div class="col-lg-10">
      <div class="card p-4 mb-4">
        <h4 class="mb-3" style="color:#0dcaf0;"><i class="fa-solid fa-truck-fast"></i> Dispatched Orders</h4>
        <?php if ($status_message) echo $status_message; ?>
        <div class="table-responsive">
          <table class="table table-dark table-hover order-table align-middle">
            <thead>
              <tr>
                <th>#</th>
                <th>Customer</th>
                <th>Mobile</th>
                <th>Address</th>
                <th>Item</th>
                <th>Image</th>
                <th>Quantity</th>
                <th>Status</th>
                <th>Dispatched At</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($dispatched_list)): ?>
                <tr>
                  <td colspan="10" class="text-center text-muted">No dispatched orders.</td>
                </tr>
              <?php else: foreach ($dispatched_list as $i => $order): ?>
                <tr>
                  <td><?php echo $i+1; ?></td>
                  <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                  <td><?php echo htmlspecialchars($order['customer_mobile']); ?></td>
                  <td style="max-width:160px;white-space:pre-wrap;"><?php echo htmlspecialchars($order['customer_address']); ?></td>
                  <td><?php echo htmlspecialchars($order['item_name']); ?></td>
                  <td>
                    <?php if ($order['item_image']): ?>
                      <img src="<?php echo htmlspecialchars($order['item_image']); ?>" class="item-img" alt="item">
                    <?php else: ?>
                      <span class="text-muted">No image</span>
                    <?php endif; ?>
                  </td>
                  <td><?php echo intval($order['quantity']); ?></td>
                  <td><span class="order-status-dispatched">Dispatched</span></td>
                  <td><?php echo date('d-M-Y h:i A', strtotime($order['dispatched_at'])); ?></td>
                  <td>
                    <form method="post" style="display:inline;">
                      <input type="hidden" name="dispatched_id" value="<?php echo intval($order['id']); ?>">
                      <button type="submit" name="mark_delivered" class="btn btn-deliver btn-sm" onclick="return confirm('Mark this order as delivered?');">
                        <i class="fa fa-check"></i> Delivered
                      </button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <!-- Delivered Orders Table -->
  <div class="row justify-content-center mb-4">
    <div class="col-lg-10">
      <div class="card p-4 mb-4">
        <h4 class="mb-3" style="color:#28a745;"><i class="fa-solid fa-truck"></i> Delivered Orders</h4>
        <div class="table-responsive">
          <table class="table table-dark table-hover order-table align-middle">
            <thead>
              <tr>
                <th>#</th>
                <th>Customer</th>
                <th>Mobile</th>
                <th>Address</th>
                <th>Item</th>
                <th>Image</th>
                <th>Quantity</th>
                <th>Status</th>
                <th>Delivered At</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($delivered_list)): ?>
                <tr>
                  <td colspan="9" class="text-center text-muted">No delivered orders yet.</td>
                </tr>
              <?php else: foreach ($delivered_list as $i => $order): ?>
                <tr>
                  <td><?php echo $i+1; ?></td>
                  <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                  <td><?php echo htmlspecialchars($order['customer_mobile']); ?></td>
                  <td style="max-width:160px;white-space:pre-wrap;"><?php echo htmlspecialchars($order['customer_address']); ?></td>
                  <td><?php echo htmlspecialchars($order['item_name']); ?></td>
                  <td>
                    <?php if ($order['item_image']): ?>
                      <img src="<?php echo htmlspecialchars($order['item_image']); ?>" class="item-img" alt="item">
                    <?php else: ?>
                      <span class="text-muted">No image</span>
                    <?php endif; ?>
                  </td>
                  <td><?php echo intval($order['quantity']); ?></td>
                  <td><span class="order-status-delivered">Delivered</span></td>
                  <td><?php echo date('d-M-Y h:i A', strtotime($order['delivered_at'])); ?></td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>