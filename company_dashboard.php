<?php
require_once 'db.php';
session_start();

// Authentication: Only allow 'company' users
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'company') {
    header("Location: admin.php");
    exit;
}

$company_name = $_SESSION['name'] ?? "";
$user_message = "";

// Handle delete user
if (isset($_GET['delete_user']) && is_numeric($_GET['delete_user'])) {
    $delete_id = intval($_GET['delete_user']);
    if ($delete_id != $_SESSION['user_id']) {
        $del_stmt = $conn->prepare("DELETE FROM user WHERE id = ?");
        $del_stmt->bind_param("i", $delete_id);
        if ($del_stmt->execute()) {
            $user_message = '<div class="alert alert-success">User deleted successfully.</div>';
        } else {
            $user_message = '<div class="alert alert-danger">Failed to delete user.</div>';
        }
        $del_stmt->close();
    } else {
        $user_message = '<div class="alert alert-warning">You cannot delete yourself.</div>';
    }
}

// Handle edit user form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $edit_id = intval($_POST['edit_user_id']);
    $name = trim($_POST['edit_name']);
    $username = trim($_POST['edit_username']);
    $mobilenumber = trim($_POST['edit_mobilenumber']);
    $role = $_POST['edit_role'];
    $allowed_roles = ['company', 'admin', 'delivery partner'];

    if (!$name || !$username || !$mobilenumber || !in_array($role, $allowed_roles)) {
        $user_message = '<div class="alert alert-danger">Please fill all required fields with a valid role.</div>';
    } else {
        $update_sql = "UPDATE user SET name = ?, username = ?, mobilenumber = ?, role = ?";
        $params = [$name, $username, $mobilenumber, $role];
        $types = "ssss";
        if (!empty($_POST['edit_password'])) {
            $update_sql .= ", password = ?";
            $params[] = hash('sha256', trim($_POST['edit_password']));
            $types .= "s";
        }
        $update_sql .= " WHERE id = ?";
        $params[] = $edit_id;
        $types .= "i";
        $edit_stmt = $conn->prepare($update_sql);
        $edit_stmt->bind_param($types, ...$params);
        if ($edit_stmt->execute()) {
            $user_message = '<div class="alert alert-success">User updated successfully.</div>';
        } else {
            $user_message = '<div class="alert alert-danger">Failed to update user.</div>';
        }
        $edit_stmt->close();
    }
}

// Handle add user form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $mobilenumber = trim($_POST['mobilenumber']);
    $role = $_POST['role'];
    $allowed_roles = ['company', 'admin', 'delivery partner'];

    if (!$name || !$username || !$password || !$mobilenumber || !in_array($role, $allowed_roles)) {
        $user_message = '<div class="alert alert-danger">Please fill all required fields with a valid role.</div>';
    } else {
        $check_stmt = $conn->prepare("SELECT id FROM user WHERE username = ?");
        $check_stmt->bind_param("s", $username);
        $check_stmt->execute();
        $check_stmt->store_result();
        if ($check_stmt->num_rows > 0) {
            $user_message = '<div class="alert alert-warning">Username already exists. Please choose another.</div>';
        } else {
            $hashed_password = hash('sha256', $password);
            $insert_stmt = $conn->prepare("INSERT INTO user (name, username, password, mobilenumber, role) VALUES (?, ?, ?, ?, ?)");
            $insert_stmt->bind_param("sssss", $name, $username, $hashed_password, $mobilenumber, $role);
            if ($insert_stmt->execute()) {
                $user_message = '<div class="alert alert-success">User added successfully.</div>';
            } else {
                $user_message = '<div class="alert alert-danger">Failed to add user. Please try again.</div>';
            }
            $insert_stmt->close();
        }
        $check_stmt->close();
    }
}

// Fetch all users
$user_sql = "SELECT id, name, username, mobilenumber, role FROM user ORDER BY id DESC";
$user_result = $conn->query($user_sql);
$users = [];
while ($row = $user_result->fetch_assoc()) {
    $users[] = $row;
}
$role_options = [
    'company' => 'Company',
    'admin' => 'Admin',
    'delivery partner' => 'Delivery Partner'
];

// Stats
$total_customers = $conn->query("SELECT COUNT(*) AS cnt FROM customer")->fetch_assoc()['cnt'] ?? 0;
$total_items = $conn->query("SELECT COUNT(*) AS cnt FROM item")->fetch_assoc()['cnt'] ?? 0;
$total_orders = $conn->query("SELECT COUNT(*) AS cnt FROM `order`")->fetch_assoc()['cnt'] ?? 0;
$total_dispatched = $conn->query("SELECT COUNT(*) AS cnt FROM dispatched_order")->fetch_assoc()['cnt'] ?? 0;
$total_delivered = $conn->query("SELECT COUNT(*) AS cnt FROM delivered_order")->fetch_assoc()['cnt'] ?? 0;

// Revenue stats
$month_revenue = $conn->query("SELECT SUM(i.price * d.quantity) AS total FROM delivered_order d LEFT JOIN item i ON d.item_id = i.id WHERE YEAR(d.delivered_at) = YEAR(CURDATE()) AND MONTH(d.delivered_at) = MONTH(CURDATE())")->fetch_assoc()['total'] ?? 0;
$year_revenue = $conn->query("SELECT SUM(i.price * d.quantity) AS total FROM delivered_order d LEFT JOIN item i ON d.item_id = i.id WHERE YEAR(d.delivered_at) = YEAR(CURDATE())")->fetch_assoc()['total'] ?? 0;

// Daily revenue: last 14 days
$daily_amounts = [];
$daily_sql = "SELECT DATE(delivered_at) AS date, SUM(i.price * d.quantity) AS total_amount
    FROM delivered_order d
    LEFT JOIN item i ON d.item_id = i.id
    WHERE delivered_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
    GROUP BY DATE(delivered_at)
    ORDER BY DATE(delivered_at) DESC";
$daily_result = $conn->query($daily_sql);
while ($row = $daily_result->fetch_assoc()) {
    $daily_amounts[] = $row;
}

// Recent orders (last 10, from all sources)
$orders = [];
$order_sqls = [
    "SELECT o.id, c.name AS customer_name, i.name AS item_name, o.quantity, o.status, o.created_at AS order_time, '-' AS status_time
     FROM `order` o
     LEFT JOIN customer c ON o.customer_id = c.id
     LEFT JOIN item i ON o.item_id = i.id
     ORDER BY o.created_at DESC LIMIT 10",
    "SELECT d.id, c.name AS customer_name, i.name AS item_name, d.quantity, d.status, d.created_at AS order_time, d.dispatched_at AS status_time
     FROM dispatched_order d
     LEFT JOIN customer c ON d.customer_id = c.id
     LEFT JOIN item i ON d.item_id = i.id
     ORDER BY d.dispatched_at DESC LIMIT 10",
    "SELECT d.id, c.name AS customer_name, i.name AS item_name, d.quantity, d.status, d.created_at AS order_time, d.delivered_at AS status_time
     FROM delivered_order d
     LEFT JOIN customer c ON d.customer_id = c.id
     LEFT JOIN item i ON d.item_id = i.id
     ORDER BY d.delivered_at DESC LIMIT 10",
    "SELECT c.id, cu.name AS customer_name, i.name AS item_name, c.quantity, c.status, c.created_at AS order_time, c.cancelled_at AS status_time
     FROM cancelled_order c
     LEFT JOIN customer cu ON c.customer_id = cu.id
     LEFT JOIN item i ON c.item_id = i.id
     ORDER BY c.cancelled_at DESC LIMIT 10"
];
foreach ($order_sqls as $sql) {
    $res = $conn->query($sql);
    while ($row = $res->fetch_assoc()) $orders[] = $row;
}
usort($orders, function($a, $b) {
    $a_time = strtotime($a['order_time']);
    $b_time = strtotime($b['order_time']);
    return $b_time <=> $a_time;
});
$orders = array_slice($orders, 0, 10);

// For edit modal, get user by ID if requested
$edit_user = null;
if (isset($_GET['edit_user']) && is_numeric($_GET['edit_user'])) {
    $edit_id = intval($_GET['edit_user']);
    foreach ($users as $u) {
        if ($u['id'] == $edit_id) {
            $edit_user = $u;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Company Dashboard | Snack World</title>
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
    .stats-card { background: #171b32; border-radius: 13px; box-shadow: 0 4px 24px rgba(0,0,0,0.10); min-width: 160px; min-height: 140px; display: flex; flex-direction: column; align-items: center; justify-content: center;}
    .stats-card .stat-label { color: #adb5bd; font-size: 1rem; }
    .stats-card .stat-value { font-size: 2rem; font-weight: bold; color: #ffb200;}
    .stats-card .stat-subvalue { color:#adb5bd; font-size: 1.1rem; margin-top: 8px; }
    .add-user-form { background: #23243a; border-radius: 10px; padding: 24px 18px; margin-bottom: 32px; }
    .add-user-form label { color: #ffb200; }
    .add-user-form .form-control, .add-user-form .form-select { background: #181b2c; border: 1px solid #44465e; color: #fff; }
    .action-btns .btn { min-width: 70px; }
    @media (max-width: 991.98px) {
      .stats-card { margin-bottom: 20px; }
    }
    @media (max-width: 767.98px) {
      .stats-card { min-width: unset; }
    }
    .row-cards {
      margin-left: 0!important;
      margin-right: 0!important;
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      align-items: stretch;
      gap: 2rem;
    }
    .row-cards > div {
      padding-left: 0!important;
      padding-right: 0!important;
    }
  </style>
</head>
<body>
<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="#"><i class="fa-solid fa-utensils"></i>Snack World <span class="d-none d-md-inline">Company</span></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#companyNav" 
      aria-controls="companyNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon" style="color: #ffb200"></span>
    </button>
    <div class="collapse navbar-collapse" id="companyNav">
      <ul class="navbar-nav ms-auto align-items-center">
        <li class="nav-item">
          <span class="nav-link">Welcome, <span style="color:#ffb200;"><?php echo htmlspecialchars($company_name); ?></span></span>
        </li>
        <li class="nav-item ms-2">
          <a class="btn btn-danger" href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<!-- Top Stats Cards -->
<div class="container-fluid dashboard-content py-5" style="max-width:100vw;">
  <div class="row row-cards g-4 mb-5">
    <div class="col-12 col-sm-6 col-md-6 col-lg-2 d-flex">
      <div class="stats-card text-center flex-fill w-100 h-100">
        <div class="stat-label"><i class="fa fa-users"></i> Customers</div>
        <div class="stat-value"><?php echo $total_customers; ?></div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-md-6 col-lg-2 d-flex">
      <div class="stats-card text-center flex-fill w-100 h-100">
        <div class="stat-label"><i class="fa fa-burger"></i> Items</div>
        <div class="stat-value"><?php echo $total_items; ?></div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-md-6 col-lg-2 d-flex">
      <div class="stats-card text-center flex-fill w-100 h-100">
        <div class="stat-label"><i class="fa fa-cart-shopping"></i> Orders</div>
        <div class="stat-value"><?php echo $total_orders; ?></div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-md-6 col-lg-2 d-flex">
      <div class="stats-card text-center flex-fill w-100 h-100">
        <div class="stat-label"><i class="fa fa-truck-fast"></i> Dispatched</div>
        <div class="stat-value"><?php echo $total_dispatched; ?></div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-md-6 col-lg-2 d-flex">
      <div class="stats-card text-center flex-fill w-100 h-100">
        <div class="stat-label"><i class="fa fa-truck"></i> Delivered</div>
        <div class="stat-value"><?php echo $total_delivered; ?></div>
        <div class="stat-subvalue" style="margin-top:8px;">
            <span style="display:block;">Month Revenue: <span style="color:#fff;">₹<?php echo number_format($month_revenue); ?></span></span>
            <span style="display:block;">Year Revenue: <span style="color:#fff;">₹<?php echo number_format($year_revenue); ?></span></span>
        </div>
      </div>
    </div>
  </div>

  <!-- Daily Revenue Table -->
  <div class="card p-4 mb-4">
    <h4 class="mb-3" style="color:#ffb200;"><i class="fa fa-calendar-day"></i> Daily Revenue (Last 14 Days)</h4>
    <div class="table-responsive">
      <table class="table table-dark table-hover align-middle">
        <thead>
          <tr>
            <th>#</th>
            <th>Date</th>
            <th>Total Amount</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($daily_amounts)): ?>
            <tr>
              <td colspan="3" class="text-center text-muted">No data.</td>
            </tr>
          <?php else: foreach ($daily_amounts as $i => $day): ?>
            <tr>
              <td><?php echo $i+1; ?></td>
              <td><?php echo date("d-M-Y", strtotime($day['date'])); ?></td>
              <td>₹<?php echo number_format($day['total_amount']); ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Add User Form -->
  <div class="card add-user-form mb-4">
    <h5 style="color:#ffb200;"><i class="fa-solid fa-user-plus"></i> Add User (All Roles)</h5>
    <?php if ($user_message) echo $user_message; ?>
    <form method="post">
      <div class="row g-3">
        <div class="col-md-4">
          <label for="name" class="form-label">Name *</label>
          <input type="text" id="name" name="name" class="form-control" required>
        </div>
        <div class="col-md-4">
          <label for="username" class="form-label">Username *</label>
          <input type="text" id="username" name="username" class="form-control" required>
        </div>
        <div class="col-md-4">
          <label for="mobilenumber" class="form-label">Mobile Number *</label>
          <input type="text" id="mobilenumber" name="mobilenumber" class="form-control" required>
        </div>
        <div class="col-md-4">
          <label for="role" class="form-label">Role *</label>
          <select id="role" name="role" class="form-select" required>
            <?php foreach($role_options as $value => $label): ?>
              <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label for="password" class="form-label">Password *</label>
          <input type="password" id="password" name="password" class="form-control" required>
          <small class="text-secondary">Password will be stored as SHA-256 hash.</small>
        </div>
      </div>
      <button type="submit" name="add_user" class="btn btn-success mt-3 px-4"><i class="fa fa-user-plus"></i> Add User</button>
    </form>
  </div>

  <!-- Edit User Modal -->
  <?php if ($edit_user): ?>
    <div class="modal show d-block" tabindex="-1" id="editUserModal" style="background:rgba(0,0,0,0.6);">
      <div class="modal-dialog">
        <div class="modal-content" style="background:#23243a;color:#fff;">
          <form method="post">
            <div class="modal-header border-0">
              <h5 class="modal-title" style="color:#ffb200;">Edit User</h5>
              <a href="company_dashboard.php" class="btn-close btn-close-white" aria-label="Close"></a>
            </div>
            <div class="modal-body">
              <input type="hidden" name="edit_user_id" value="<?php echo $edit_user['id']; ?>">
              <div class="mb-3">
                <label class="form-label">Name *</label>
                <input type="text" name="edit_name" class="form-control" value="<?php echo htmlspecialchars($edit_user['name']); ?>" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Username *</label>
                <input type="text" name="edit_username" class="form-control" value="<?php echo htmlspecialchars($edit_user['username']); ?>" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Mobile Number *</label>
                <input type="text" name="edit_mobilenumber" class="form-control" value="<?php echo htmlspecialchars($edit_user['mobilenumber']); ?>" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Role *</label>
                <select name="edit_role" class="form-select" required>
                  <?php foreach($role_options as $value => $label): ?>
                    <option value="<?php echo $value; ?>" <?php if($edit_user['role']==$value) echo "selected"; ?>><?php echo $label; ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label">Password (leave blank to keep unchanged)</label>
                <input type="password" name="edit_password" class="form-control">
              </div>
            </div>
            <div class="modal-footer border-0">
              <button type="submit" name="edit_user" class="btn btn-success px-4">Save Changes</button>
              <a href="company_dashboard.php" class="btn btn-secondary">Cancel</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <!-- User Table -->
  <div class="card p-4 mb-4">
    <h5 style="color:#ffb200;"><i class="fa-solid fa-users"></i> All Users</h5>
    <div class="table-responsive mt-3">
      <table class="table table-dark table-hover user-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Name</th>
            <th>Username</th>
            <th>Mobile Number</th>
            <th>Role</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($users)): ?>
            <tr>
              <td colspan="6" class="text-center text-muted">No users found.</td>
            </tr>
          <?php else: 
            foreach ($users as $i => $user): ?>
            <tr>
              <td><?php echo $i+1; ?></td>
              <td><?php echo htmlspecialchars($user['name']); ?></td>
              <td><?php echo htmlspecialchars($user['username']); ?></td>
              <td><?php echo htmlspecialchars($user['mobilenumber']); ?></td>
              <td><?php echo isset($role_options[$user['role']]) ? $role_options[$user['role']] : ucfirst($user['role']); ?></td>
              <td class="action-btns">
                <?php if ($user['role'] !== 'company'): ?>
                  <a href="company_dashboard.php?edit_user=<?php echo $user['id']; ?>" class="btn btn-sm btn-warning mb-1"><i class="fa fa-edit"></i> Edit</a>
                  <?php if ($user['id'] != $_SESSION['user_id']): ?>
                    <a href="company_dashboard.php?delete_user=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger mb-1" onclick="return confirm('Are you sure you want to delete this user?');"><i class="fa fa-trash"></i> Delete</a>
                  <?php endif; ?>
                <?php else: ?>
                  <span class="badge bg-secondary">No Action</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
    <small class="text-secondary d-block mt-2">* User passwords are stored as SHA-256 hashes for security.</small>
  </div>

  <!-- Recent Orders Table -->
  <div class="card p-4 mb-4">
    <h4 class="mb-3" style="color:#ffb200;"><i class="fa fa-clock"></i> Recent Orders (All Statuses)</h4>
    <div class="table-responsive">
      <table class="table table-dark table-hover align-middle">
        <thead>
          <tr>
            <th>#</th>
            <th>Customer</th>
            <th>Item</th>
            <th>Quantity</th>
            <th>Status</th>
            <th>Ordered At</th>
            <th>Status Time</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($orders)): ?>
            <tr>
              <td colspan="7" class="text-center text-muted">No recent orders.</td>
            </tr>
          <?php else: foreach ($orders as $i => $order): ?>
            <tr>
              <td><?php echo $i+1; ?></td>
              <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
              <td><?php echo htmlspecialchars($order['item_name']); ?></td>
              <td><?php echo intval($order['quantity']); ?></td>
              <td>
                <?php
                  $status_class = "text-secondary";
                  if ($order['status'] == "ordered") $status_class = "text-warning";
                  elseif ($order['status'] == "dispatched") $status_class = "text-info";
                  elseif ($order['status'] == "delivered") $status_class = "text-success";
                  elseif ($order['status'] == "cancelled") $status_class = "text-danger";
                ?>
                <span class="<?php echo $status_class; ?>"><?php echo ucfirst($order['status']); ?></span>
              </td>
              <td><?php echo date('d-M-Y h:i A', strtotime($order['order_time'])); ?></td>
              <td>
                <?php
                  echo ($order['status_time'] === '-' || !$order['status_time'])
                    ? '-' : date('d-M-Y h:i A', strtotime($order['status_time']));
                ?>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>