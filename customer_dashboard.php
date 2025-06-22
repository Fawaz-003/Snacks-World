<?php
require_once 'db.php';
session_start();

// WhatsApp Notification Function (UltraMsg)
function sendOrderToAdminWhatsapp($customer, $item, $quantity, $total, $admin_number) {
    $instance_id = 'instance121765'; // UltraMsg instance ID
    $token = 'gt4joytizy1fhpah'; // UltraMsg Token

    $message = "New Order!\n"
        . "Customer: {$customer['name']}\n"
        . "Mobile: {$customer['mobile']}\n" 
        . "Address: {$customer['address']}\n"
        . "Item: $item\n"
        . "Quantity: $quantity\n"
        . "Total: ₹$total";

    $url = "https://api.ultramsg.com/{$instance_id}/messages/chat";
    $data = [
        'token' => $token,
        'to' => $admin_number, // e.g. '91XXXXXXXXXX'
        'body' => $message,
    ];

    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
        ]
    ];
    @file_get_contents($url, false, stream_context_create($options));
}

function sendMultipleOrdersToAdminWhatsapp($customer, $orders, $admin_number) {
    $instance_id = 'instance121765'; // UltraMsg instance ID
    $token = 'gt4joytizy1fhpah'; // UltraMsg Token

    $message = "New Order!\n"
        . "Customer: {$customer['name']}\n"
        . "Mobile: {$customer['mobile']}\n" 
        . "Address: {$customer['address']}\n\n"
        . "Items:\n";

    $grand_total = 0;
    foreach ($orders as $order) {
        $message .= "- {$order['name']} x {$order['quantity']} = ₹{$order['subtotal']}\n";
        $grand_total += $order['subtotal'];
    }
    $message .= "\nTotal: ₹{$grand_total}";

    $url = "https://api.ultramsg.com/{$instance_id}/messages/chat";
    $data = [
        'token' => $token,
        'to' => $admin_number,
        'body' => $message,
    ];

    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
        ]
    ];
    @file_get_contents($url, false, stream_context_create($options));
}

// Check if customer is logged in
if (!isset($_SESSION['customer_mobile']) || empty($_SESSION['customer_mobile'])) {
    header("Location: order.php");
    exit;
}

// Fetch customer details
$mobile = $_SESSION['customer_mobile'];
$stmt = $conn->prepare("SELECT * FROM customer WHERE mobile = ?");
$stmt->bind_param("s", $mobile);
$stmt->execute();
$result = $stmt->get_result();
$customer = $result->fetch_assoc();
$stmt->close();

if (!$customer) {
    session_unset();
    session_destroy();
    header("Location: order.php");
    exit;
}

// Check if item is passed (from index.php: ?item=Item+Name)
$selected_item = null;
$show_order_item = false;
$preselected_item_id = null;
$preselected_item_qty = 1;
if (isset($_GET['item']) && !empty($_GET['item'])) {
    $item_name = $_GET['item'];
    $item_stmt = $conn->prepare("SELECT * FROM item WHERE name = ?");
    $item_stmt->bind_param("s", $item_name);
    $item_stmt->execute();
    $item_result = $item_stmt->get_result();
    $selected_item = $item_result->fetch_assoc();
    $item_stmt->close();
    $show_order_item = true;

    if (isset($_GET['qty']) && is_numeric($_GET['qty']) && intval($_GET['qty']) > 0) {
        $preselected_item_qty = intval($_GET['qty']);
    }
    if ($selected_item) {
        $preselected_item_id = $selected_item['id'];
    }
}

$order_message = "";

// Place order from the main "Order This Item"
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_order'])) {
    $item_id = intval($_POST['item_id']);
    $quantity = intval($_POST['quantity']);
    if ($item_id && $quantity > 0) {
        $stmt = $conn->prepare("INSERT INTO `order` (customer_id, item_id, quantity, status) VALUES (?, ?, ?, 'ordered')");
        $stmt->bind_param("iii", $customer['id'], $item_id, $quantity);
        if ($stmt->execute()) {
            // Fetch item details
            $item_stmt = $conn->prepare("SELECT name, price FROM item WHERE id = ?");
            $item_stmt->bind_param("i", $item_id);
            $item_stmt->execute();
            $item_row = $item_stmt->get_result()->fetch_assoc();
            $item_stmt->close();

            // WhatsApp notification
            $customer_data = [
                'name' => $customer['name'],
                'mobile' => $customer['mobile'],
                'address' => $customer['address']
            ];
            $total = $item_row['price'] * $quantity;
            $admin_number = '919894574006'; // Replace with admin's WhatsApp number

            sendOrderToAdminWhatsapp($customer_data, $item_row['name'], $quantity, $total, $admin_number);

            $order_message = "<div class='alert alert-success'>Order placed successfully!</div>";
            $show_order_item = false;
            echo "<script>if(window.history.replaceState){window.history.replaceState({},document.title,window.location.pathname);}</script>";
        } else {
            $order_message = "<div class='alert alert-danger'>Failed to place order. Please try again.</div>";
        }
        $stmt->close();
    } else {
        $order_message = "<div class='alert alert-danger'>Please enter a valid quantity.</div>";
    }
}

// Place order from the "Order More" modal (single)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_order_more']) && empty($_POST['multiple'])) {
    $item_id = intval($_POST['item_id']);
    $quantity = intval($_POST['quantity']);
    if ($item_id && $quantity > 0) {
        $stmt = $conn->prepare("INSERT INTO `order` (customer_id, item_id, quantity, status) VALUES (?, ?, ?, 'ordered')");
        $stmt->bind_param("iii", $customer['id'], $item_id, $quantity);
        if ($stmt->execute()) {
            // Fetch item details
            $item_stmt = $conn->prepare("SELECT name, price FROM item WHERE id = ?");
            $item_stmt->bind_param("i", $item_id);
            $item_stmt->execute();
            $item_row = $item_stmt->get_result()->fetch_assoc();
            $item_stmt->close();

            // WhatsApp notification
            $customer_data = [
                'name' => $customer['name'],
                'mobile' => $customer['mobile'],
                'address' => $customer['address']
            ];
            $total = $item_row['price'] * $quantity;
            $admin_number = '919894574006'; // Replace with admin's WhatsApp number

            sendOrderToAdminWhatsapp($customer_data, $item_row['name'], $quantity, $total, $admin_number);

            $order_message = "<div class='alert alert-success'>Order placed successfully!</div>";
        } else {
            $order_message = "<div class='alert alert-danger'>Failed to place order. Please try again.</div>";
        }
        $stmt->close();
    } else {
        $order_message = "<div class='alert alert-danger'>Please enter a valid quantity.</div>";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_order_more']) && isset($_POST['multiple']) && $_POST['multiple'] == "1") {
    $order_data = isset($_POST['multiple_order']) ? $_POST['multiple_order'] : [];
    $success = 0;
    $fail = 0;
    $orders_for_whatsapp = []; // <-- Collect order details for WhatsApp
    if (is_array($order_data) && count($order_data)) {
        foreach ($order_data as $order_item) {
            $item_id = intval($order_item['item_id']);
            $quantity = intval($order_item['quantity']);
            if ($item_id && $quantity > 0) {
                $stmt = $conn->prepare("INSERT INTO `order` (customer_id, item_id, quantity, status) VALUES (?, ?, ?, 'ordered')");
                $stmt->bind_param("iii", $customer['id'], $item_id, $quantity);
                if ($stmt->execute()) {
                    // Fetch item details
                    $item_stmt = $conn->prepare("SELECT name, price FROM item WHERE id = ?");
                    $item_stmt->bind_param("i", $item_id);
                    $item_stmt->execute();
                    $item_row = $item_stmt->get_result()->fetch_assoc();
                    $item_stmt->close();

                    // Collect for WhatsApp
                    $orders_for_whatsapp[] = [
                        'name' => $item_row['name'],
                        'quantity' => $quantity,
                        'subtotal' => $item_row['price'] * $quantity
                    ];

                    $success++;
                } else {
                    $fail++;
                }
                $stmt->close();
            } else {
                $fail++;
            }
        }
        if ($success > 0) {
            // WhatsApp notification for all items at once
            $customer_data = [
                'name' => $customer['name'],
                'mobile' => $customer['mobile'],
                'address' => $customer['address']
            ];
            $admin_number = '919894574006'; // Replace with admin's WhatsApp number
            sendMultipleOrdersToAdminWhatsapp($customer_data, $orders_for_whatsapp, $admin_number);

            $msg = "$success item(s) ordered successfully.";
            if ($fail > 0) $msg .= " $fail failed.";
            $order_message = "<div class='alert alert-success'>$msg</div>";
        } else {
            $order_message = "<div class='alert alert-danger'>Failed to place order. Please try again.</div>";
        }
    }
}

// Cancel order action (only for orders in 'order' table)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['cancel_order_id'])) {
    $cancel_order_id = intval($_POST['cancel_order_id']);
    $stmt = $conn->prepare("SELECT * FROM `order` WHERE id = ? AND customer_id = ? AND status NOT IN ('cancelled', 'delivered', 'dispatched')");
    $stmt->bind_param("ii", $cancel_order_id, $customer['id']);
    $stmt->execute();
    $cancel_result = $stmt->get_result();
    if ($cancel_result && $cancel_result->num_rows > 0) {
        $stmt_update = $conn->prepare("UPDATE `order` SET status = 'cancelled' WHERE id = ?");
        $stmt_update->bind_param("i", $cancel_order_id);
        $stmt_update->execute();
        $stmt_update->close();
        $order_message = "<div class='alert alert-success'>Order cancelled successfully!</div>";
    }
    $stmt->close();
}

// Fetch all items for "Order More"
$all_items = [];
$item_result = $conn->query("SELECT * FROM item ORDER BY id DESC");
while ($row = $item_result->fetch_assoc()) {
    $all_items[] = $row;
}

// Fetch all pending/ordered orders for this customer (from `order` table)
$order_sql = "SELECT o.*, i.name AS item_name, i.image AS item_image, i.price AS item_price, o.created_at AS table_created_at
              FROM `order` o
              LEFT JOIN item i ON o.item_id = i.id
              WHERE o.customer_id = ?
              ORDER BY o.id DESC";
$order_stmt = $conn->prepare($order_sql);
$order_stmt->bind_param("i", $customer['id']);
$order_stmt->execute();
$order_result = $order_stmt->get_result();
$order_list = [];
while ($row = $order_result->fetch_assoc()) {
    $row['source'] = 'order';
    $row['table_status'] = $row['status'];
    $row['status_date'] = $row['created_at'];
    $order_list[] = $row;
}
$order_stmt->close();

// Fetch all dispatched orders for this customer (from `dispatched_order`)
$dispatched_sql = "SELECT d.*, i.name AS item_name, i.image AS item_image, i.price AS item_price, d.dispatched_at AS status_date
                   FROM dispatched_order d
                   LEFT JOIN item i ON d.item_id = i.id
                   WHERE d.customer_id = ?
                   ORDER BY d.id DESC";
$dispatched_stmt = $conn->prepare($dispatched_sql);
$dispatched_stmt->bind_param("i", $customer['id']);
$dispatched_stmt->execute();
$dispatched_result = $dispatched_stmt->get_result();
while ($row = $dispatched_result->fetch_assoc()) {
    $row['source'] = 'dispatched';
    $row['table_status'] = 'dispatched';
    $order_list[] = $row;
}
$dispatched_stmt->close();

// Fetch all cancelled orders for this customer (from `cancelled_order`)
$cancelled_sql = "SELECT c.*, i.name AS item_name, i.image AS item_image, i.price AS item_price, c.cancelled_at AS status_date
                  FROM cancelled_order c
                  LEFT JOIN item i ON c.item_id = i.id
                  WHERE c.customer_id = ?
                  ORDER BY c.id DESC";
$cancelled_stmt = $conn->prepare($cancelled_sql);
$cancelled_stmt->bind_param("i", $customer['id']);
$cancelled_stmt->execute();
$cancelled_result = $cancelled_stmt->get_result();
while ($row = $cancelled_result->fetch_assoc()) {
    $row['source'] = 'cancelled';
    $row['table_status'] = 'cancelled';
    $order_list[] = $row;
}
$cancelled_stmt->close();

// Sort all orders by created_at/appropriate date DESC
usort($order_list, function($a, $b) {
    return strtotime($b['status_date']) <=> strtotime($a['status_date']);
});

// Fetch delivered orders for this customer (from `delivered_order`)
$delivered_sql = "SELECT d.*, i.name AS item_name, i.image AS item_image, i.price AS item_price
                  FROM delivered_order d
                  LEFT JOIN item i ON d.item_id = i.id
                  WHERE d.customer_id = ?
                  ORDER BY d.delivered_at DESC";
$delivered_stmt = $conn->prepare($delivered_sql);
$delivered_stmt->bind_param("i", $customer['id']);
$delivered_stmt->execute();
$delivered_result = $delivered_stmt->get_result();
$delivered_list = [];
while ($row = $delivered_result->fetch_assoc()) {
    $delivered_list[] = $row;
}
$delivered_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Customer Dashboard | Snack World</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"/>
    <style>
        body { background: #101326; color: #fff; font-family: 'Roboto', Arial, sans-serif; }
        .navbar { background: #0a0e22 !important; }
        .navbar-brand { font-family: 'Montserrat', sans-serif; font-weight: 700; font-size: 2rem; color: #ffb200 !important; }
        .nav-link, .navbar-text { color: #fff !important; font-family: 'Montserrat', sans-serif; font-weight: 700; }
        .navbar-text span { color: #ffb200; font-weight: 700; }
        .card { background: #171b32; border: none; border-radius: 16px; color: #fff; box-shadow: 0 4px 24px rgba(0,0,0,0.08);}
        .form-label { color: #ffb200; font-weight: 700; }
        .item-img { width: 100%; max-width: 260px; max-height: 200px; object-fit: cover; border-radius: 12px; margin: 0 auto 12px auto; display: block; background: #22253a;}
        .btn-order { background: #23243a; color: #ffb200; border-radius: 8px; font-family: 'Montserrat', sans-serif; font-weight: 700; border: 2px solid #ffb200; transition: 0.2s;}
        .btn-order:hover, .btn-order:focus { background: #ffb200; color: #171b32; }
        .modal-content { background: #171b32; color: #fff; }
        .modal-header, .modal-footer { border: 0; }
        .order-more-card { background: #23284a !important; }
        .order-more-img { width:100%; max-width:110px; max-height:80px; object-fit:cover; border-radius:8px; background: #191a25; }
        .order-more-title { color:#ffb200; font-weight:700;}
        .order-more-price { color:#ffb200;}
        .order-table th, .order-table td { vertical-align:middle; }
        .order-table th { color: #ffb200; }
        .order-status-ordered { color: #ffc107; font-weight: 600;}
        .order-status-cancelled { color: #dc3545; font-weight: 600;}
        .order-status-dispatched { color: #0dcaf0; font-weight: 600;}
        .order-status-delivered { color: #28a745; font-weight: 600;}
        .order-status-other { color: #adb5bd; font-weight: 600;}
        .multiple-order-btn { border:2px solid #ffb200; color:#ffb200; background:transparent; border-radius:8px; font-weight:600; }
        .multiple-order-btn.active, .multiple-order-btn:focus { background:#ffb200; color:#171b32; }
        .multiple-order-controls { display: flex; flex-direction: row; align-items: center; gap: 6px; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="#"><i class="fa-solid fa-utensils"></i>Snack World</a>
        <ul class="navbar-nav ms-auto align-items-center">
            <li class="nav-item">
                <span class="navbar-text ms-lg-3">
                    Welcome, <span><?php echo htmlspecialchars($customer['name']); ?></span>
                </span>
            </li>
            <li class="nav-item ms-3">
                <a href="logout.php" class="btn btn-danger"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
            </li>
        </ul>
    </div>
</nav>

<div class="container py-5">

    <?php if ($show_order_item && $selected_item): ?>
        <div class="row justify-content-center mb-5" id="order-this-item-section">
            <div class="col-lg-6">
                <div class="card p-4 mb-4 text-center">
                    <h4 class="mb-3" style="color:#ffb200;"><i class="fa-solid fa-burger"></i> Order This Item</h4>
                    <img src="<?php echo htmlspecialchars($selected_item['image']); ?>" class="item-img" alt="<?php echo htmlspecialchars($selected_item['name']); ?>">
                    <h5 style="color:#ffb200;"><?php echo htmlspecialchars($selected_item['name']); ?></h5>
                    <div class="mb-2" style="color:#ffb200;">₹<?php echo number_format($selected_item['price']); ?></div>
                    <form id="orderForm" method="post" class="d-flex flex-column align-items-center mt-2" onsubmit="return showOrderConfirmation(event)">
                        <input type="hidden" name="order_item" value="1">
                        <input type="hidden" name="item_id" id="item_id" value="<?php echo $selected_item['id']; ?>">
                        <div class="mb-2" style="width:100px;">
                            <input type="number" name="quantity" id="quantity" class="form-control" min="1" value="<?php echo $preselected_item_qty; ?>" required>
                        </div>
                        <button type="submit" class="btn btn-order w-100"><i class="fa-solid fa-cart-plus"></i> Place Order</button>
                    </form>
                </div>
            </div>
        </div>
        <!-- Order Confirmation Modal -->
        <div class="modal fade" id="orderConfirmModal" tabindex="-1" aria-labelledby="orderConfirmModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header border-0">
                <h5 class="modal-title" id="orderConfirmModalLabel" style="color:#ffb200;"><i class="fa-solid fa-cart-shopping"></i> Confirm Your Order</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="background:white;"></button>
              </div>
              <div class="modal-body">
                <div class="mb-3 text-center">
                    <img src="<?php echo htmlspecialchars($selected_item['image']); ?>" class="item-img" style="max-width:130px;max-height:100px;" alt="">
                </div>
                <div class="mb-2"><strong>Item:</strong> <span id="modalItemName"><?php echo htmlspecialchars($selected_item['name']); ?></span></div>
                <div class="mb-2"><strong>Quantity:</strong> <span id="modalQuantity"></span></div>
                <div class="mb-2"><strong>Total Price:</strong> ₹<span id="modalTotalPrice"></span></div>
              </div>
              <div class="modal-footer border-0 flex-column">
                <form id="confirmOrderForm" method="post" class="w-100 d-flex flex-column align-items-center">
                    <input type="hidden" name="confirm_order" value="1">
                    <input type="hidden" name="item_id" value="<?php echo $selected_item['id']; ?>">
                    <input type="hidden" name="quantity" id="modalQtyInput" value="<?php echo $preselected_item_qty; ?>">
                    <div class="d-flex w-100 justify-content-between">
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-order">Confirm Order</button>
                    </div>
                </form>
                <button class="btn btn-outline-warning mt-3" style="width:100%;" data-bs-toggle="modal" data-bs-target="#orderMoreModal" data-bs-dismiss="modal">
                    <i class="fa-solid fa-plus"></i> Order More Items
                </button>
              </div>
            </div>
          </div>
        </div>
    <?php endif; ?>

    <!-- Order List (All Statuses except Delivered) -->
    <div class="row justify-content-center mb-5">
        <div class="col-lg-9">
            <div class="card p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h4 style="color:#ffb200;"><i class="fa-solid fa-list"></i> Your Orders</h4>
                  <button class="btn btn-order" data-bs-toggle="modal" data-bs-target="#orderMoreModal">
                    <i class="fa-solid fa-plus"></i> Order More
                  </button>
                </div>
                <?php if ($order_message) echo $order_message; ?>
                <div class="table-responsive">
                    <table class="table table-dark table-hover order-table">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Item</th>
                            <th>Image</th>
                            <th>Quantity</th>
                            <th>Status</th>
                            <th>
                                <span id="orderStatusDateHeader">
                                    <?php echo "Status Date"; ?>
                                </span>
                            </th>
                            <th>Total</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($order_list)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted">No orders yet.</td>
                            </tr>
                        <?php else:
                            $i = 1;
                            foreach ($order_list as $order): ?>
                                <tr>
                                    <td><?php echo $i++; ?></td>
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
                                        $status = strtolower($order['table_status']);
                                        $status_class = "order-status-other";
                                        if ($status == "ordered" || $status == "order") $status_class = "order-status-ordered";
                                        else if ($status == "cancel" || $status == "cancelled") $status_class = "order-status-cancelled";
                                        else if ($status == "dispatched") $status_class = "order-status-dispatched";
                                        ?>
                                        <span class="<?php echo $status_class; ?>">
                                            <?php echo ucfirst($order['table_status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                            if (isset($order['status_date'])) {
                                                echo date('d-M-Y h:i A', strtotime($order['status_date']));
                                            }
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                            $total = isset($order['item_price']) ? ($order['item_price'] * $order['quantity']) : 0;
                                            echo "₹" . number_format($total);
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        if ($order['source'] === 'order' && in_array(strtolower($order['table_status']), ['ordered', 'order'])): ?>
                                            <form method="post" style="display:inline;">
                                                <input type="hidden" name="cancel_order_id" value="<?php echo intval($order['id']); ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to cancel this order?');"><i class="fa-solid fa-xmark"></i> Cancel</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach;
                        endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Delivered Orders List -->
    <div class="row justify-content-center mb-5">
        <div class="col-lg-9">
            <div class="card p-4 mb-4">
                <h4 class="mb-3" style="color:#28a745;"><i class="fa-solid fa-truck"></i> Delivered Orders</h4>
                <div class="table-responsive">
                    <table class="table table-dark table-hover order-table">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Item</th>
                            <th>Image</th>
                            <th>Quantity</th>
                            <th>Delivered At</th>
                            <th>Total</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($delivered_list)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">No delivered orders yet.</td>
                            </tr>
                        <?php else:
                            foreach ($delivered_list as $i => $order): ?>
                                <tr>
                                    <td><?php echo $i + 1; ?></td>
                                    <td><?php echo htmlspecialchars($order['item_name']); ?></td>
                                    <td>
                                        <?php if ($order['item_image']): ?>
                                            <img src="<?php echo htmlspecialchars($order['item_image']); ?>" alt="item" width="60" style="border-radius:8px;">
                                        <?php else: ?>
                                            <span class="text-muted">No image</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo intval($order['quantity']); ?></td>
                                    <td><?php echo date('d-M-Y h:i A', strtotime($order['delivered_at'])); ?></td>
                                    <td>
                                        <?php
                                            $total = isset($order['item_price']) ? ($order['item_price'] * $order['quantity']) : 0;
                                            echo "₹" . number_format($total);
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach;
                        endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Order More Modal -->
    <div class="modal fade" id="orderMoreModal" tabindex="-1" aria-labelledby="orderMoreModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header border-0">
            <h5 class="modal-title" id="orderMoreModalLabel" style="color:#ffb200;"><i class="fa-solid fa-list"></i> Order More Items</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="background:white;"></button>
          </div>
          <div class="modal-body">
            <div class="d-flex mb-2">
                <button id="multipleOrderModeBtn" class="multiple-order-btn" onclick="toggleMultipleOrderMode(this)">
                    <i class="fa-solid fa-layer-group"></i> Multiple Order Mode
                </button>
                <span class="ms-3" style="color:#adb5bd; font-size:13px;">Order multiple items in one go. Adjust quantities and click "Place All Orders".</span>
            </div>
            <form id="multipleOrderForm" method="post" class="d-none" onsubmit="return showMultipleOrderConfirmation(event)">
                <input type="hidden" name="confirm_order_more" value="1">
                <input type="hidden" name="multiple" value="1">
                <div class="row g-3">
                  <?php foreach($all_items as $item): ?>
                    <div class="col-md-4 col-sm-6">
                      <div class="card order-more-card p-2 h-100 text-center">
                        <img src="<?php echo htmlspecialchars($item['image']); ?>" class="order-more-img mt-2" alt="<?php echo htmlspecialchars($item['name']); ?>">
                        <div class="order-more-title mt-2"><?php echo htmlspecialchars($item['name']); ?></div>
                        <div class="order-more-price mb-2">₹<?php echo number_format($item['price']); ?></div>
                        <div class="multiple-order-controls">
                            <button type="button" class="btn btn-sm btn-secondary" onclick="changeQty(this, -1)">-</button>
                            <input type="number" name="multiple_order[<?php echo $item['id']; ?>][quantity]" class="form-control text-center" min="0" value="0" style="width:60px;" data-item-name="<?php echo htmlspecialchars($item['name']); ?>" data-item-price="<?php echo intval($item['price']); ?>">
                            <button type="button" class="btn btn-sm btn-secondary" onclick="changeQty(this, 1)">+</button>
                        </div>
                        <input type="hidden" name="multiple_order[<?php echo $item['id']; ?>][item_id]" value="<?php echo $item['id']; ?>">
                      </div>
                    </div>
                  <?php endforeach; ?>
                  <?php if(empty($all_items)): ?>
                    <div class="col-12 text-center text-muted">No items available.</div>
                  <?php endif; ?>
                </div>
                <div class="pt-3 d-flex justify-content-center">
                    <button type="submit" class="btn btn-order"><i class="fa-solid fa-check"></i> Place All Orders</button>
                </div>
            </form>
            <div id="orderMoreSingleList">
                <div class="row g-3">
                  <?php foreach($all_items as $item): ?>
                    <div class="col-md-4 col-sm-6">
                      <div class="card order-more-card p-2 h-100 text-center">
                        <img src="<?php echo htmlspecialchars($item['image']); ?>" class="order-more-img mt-2" alt="<?php echo htmlspecialchars($item['name']); ?>">
                        <div class="order-more-title mt-2"><?php echo htmlspecialchars($item['name']); ?></div>
                        <div class="order-more-price mb-2">₹<?php echo number_format($item['price']); ?></div>
                        <form class="d-flex flex-column align-items-center order-more-form" onsubmit="return showOrderMoreConfirmation(event, <?php echo $item['id']; ?>, '<?php echo htmlspecialchars(addslashes($item['name'])); ?>', <?php echo intval($item['price']); ?>, '<?php echo htmlspecialchars(addslashes($item['image'])); ?>', this)">
                          <div class="mb-2" style="width:70px;">
                            <input type="number" name="order_more_quantity" class="form-control" min="1" value="1" required>
                          </div>
                          <button type="submit" class="btn btn-order btn-sm mb-2"><i class="fa-solid fa-cart-plus"></i> Order</button>
                        </form>
                      </div>
                    </div>
                  <?php endforeach; ?>
                  <?php if(empty($all_items)): ?>
                    <div class="col-12 text-center text-muted">No items available.</div>
                  <?php endif; ?>
                </div>
            </div>
          </div>
          <div class="modal-footer border-0">
            <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>
    <!-- Order More Confirmation Modal -->
    <div class="modal fade" id="orderMoreConfirmModal" tabindex="-1" aria-labelledby="orderMoreConfirmModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header border-0">
            <h5 class="modal-title" id="orderMoreConfirmModalLabel" style="color:#ffb200;"><i class="fa-solid fa-cart-shopping"></i> Confirm Your Order</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="background:white;"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3 text-center">
                <img id="orderMoreModalImage" src="" class="item-img" style="max-width:130px;max-height:100px;" alt="">
            </div>
            <div class="mb-2"><strong>Item:</strong> <span id="orderMoreModalItemName"></span></div>
            <div class="mb-2"><strong>Quantity:</strong> <span id="orderMoreModalQuantity"></span></div>
            <div class="mb-2"><strong>Total Price:</strong> ₹<span id="orderMoreModalTotalPrice"></span></div>
          </div>
          <div class="modal-footer border-0 flex-column">
            <form id="orderMoreConfirmForm" method="post" class="w-100 d-flex flex-column align-items-center">
                <input type="hidden" name="confirm_order_more" value="1">
                <input type="hidden" name="item_id" id="orderMoreModalItemId" value="">
                <input type="hidden" name="quantity" id="orderMoreModalQtyInput" value="">
                <div class="d-flex w-100 justify-content-between">
                    <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-order">Confirm Order</button>
                </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Multiple Order Confirmation Modal -->
    <div class="modal fade" id="multipleOrderConfirmModal" tabindex="-1" aria-labelledby="multipleOrderConfirmModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
          <div class="modal-header border-0">
            <h5 class="modal-title" id="multipleOrderConfirmModalLabel" style="color:#ffb200;"><i class="fa-solid fa-cart-shopping"></i> Confirm Your Orders</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="background:white;"></button>
          </div>
          <div class="modal-body">
            <div id="multipleOrderSummary"></div>
          </div>
          <div class="modal-footer border-0 flex-column">
            <button type="button" class="btn btn-secondary me-2 w-100 mb-2" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-order w-100" onclick="document.getElementById('multipleOrderForm').submit();">Confirm All Orders</button>
          </div>
        </div>
      </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<?php if ($show_order_item && $selected_item): ?>
<script>
function showOrderConfirmation(e) {
    e.preventDefault();
    var qty = parseInt(document.getElementById('quantity').value, 10);
    if (isNaN(qty) || qty < 1) qty = 1;
    document.getElementById('modalQuantity').textContent = qty;
    document.getElementById('modalQtyInput').value = qty;
    var price = <?php echo intval($selected_item['price']); ?>;
    document.getElementById('modalTotalPrice').textContent = price * qty;
    var modal = new bootstrap.Modal(document.getElementById('orderConfirmModal'));
    modal.show();
    return false;
}
</script>
<?php endif; ?>
<script>
function showOrderMoreConfirmation(e, itemId, itemName, itemPrice, itemImg, formEl) {
    e.preventDefault();
    var qty = parseInt(formEl.querySelector('[name="order_more_quantity"]').value, 10);
    if (isNaN(qty) || qty < 1) qty = 1;
    document.getElementById('orderMoreModalImage').src = itemImg;
    document.getElementById('orderMoreModalItemName').textContent = itemName;
    document.getElementById('orderMoreModalQuantity').textContent = qty;
    document.getElementById('orderMoreModalTotalPrice').textContent = itemPrice * qty;
    document.getElementById('orderMoreModalItemId').value = itemId;
    document.getElementById('orderMoreModalQtyInput').value = qty;
    var modal = new bootstrap.Modal(document.getElementById('orderMoreConfirmModal'));
    modal.show();
    return false;
}

function toggleMultipleOrderMode(btn) {
    var singleList = document.getElementById('orderMoreSingleList');
    var multiForm = document.getElementById('multipleOrderForm');
    if (multiForm.classList.contains('d-none')) {
        singleList.style.display = "none";
        multiForm.classList.remove('d-none');
        btn.classList.add('active');
    } else {
        singleList.style.display = "";
        multiForm.classList.add('d-none');
        btn.classList.remove('active');
    }
}

function changeQty(btn, delta) {
    var input = btn.parentNode.querySelector('input[type="number"]');
    var value = parseInt(input.value, 10) || 0;
    value = Math.max(0, value + delta);
    input.value = value;
}

function showMultipleOrderConfirmation(e) {
    e.preventDefault();
    var form = document.getElementById('multipleOrderForm');
    var inputs = form.querySelectorAll('input[type="number"]');
    var total = 0;
    var summaryHtml = '<table class="table table-sm table-bordered text-white"><thead><tr><th>Item</th><th>Quantity</th><th>Price</th><th>Subtotal</th></tr></thead><tbody>';
    var orderCount = 0;
    inputs.forEach(function(input) {
        var qty = parseInt(input.value, 10) || 0;
        if (qty > 0) {
            var name = input.getAttribute('data-item-name');
            var price = parseInt(input.getAttribute('data-item-price'), 10) || 0;
            var subtotal = qty * price;
            total += subtotal;
            summaryHtml += '<tr><td>' + name + '</td><td>' + qty + '</td><td>₹' + price + '</td><td>₹' + subtotal + '</td></tr>';
            orderCount++;
        }
    });
    summaryHtml += '</tbody></table>';
    summaryHtml += '<div class="text-end fs-5 mt-2"><b>Total Amount: ₹' + total + '</b></div>';
    if (orderCount === 0) {
        alert("Please select at least one item to order.");
        return false;
    }
    document.getElementById('multipleOrderSummary').innerHTML = summaryHtml;
    var modal = new bootstrap.Modal(document.getElementById('multipleOrderConfirmModal'));
    modal.show();
    return false;
}
</script>
</body>
</html>