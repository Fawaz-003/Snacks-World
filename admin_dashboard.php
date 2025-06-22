<?php
require_once 'db.php';
session_start();
// Authentication: Only allow 'admin' users
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin.php");
    exit;
}

// Handle add user form submission
$user_message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $mobilenumber = trim($_POST['mobilenumber']);
    $role = $_POST['role'];

    // Only allow 'admin' and 'delivery partner' roles via the form
    $allowed_roles = ['admin', 'delivery partner'];

    if (!$name || !$username || !$password || !$mobilenumber || !in_array($role, $allowed_roles)) {
        $user_message = '<div class="alert alert-danger">Please fill all required fields with a valid role.</div>';
    } else {
        // Check if username already exists
        $check_stmt = $conn->prepare("SELECT id FROM user WHERE username = ?");
        $check_stmt->bind_param("s", $username);
        $check_stmt->execute();
        $check_stmt->store_result();
        if ($check_stmt->num_rows > 0) {
            $user_message = '<div class="alert alert-warning">Username already exists. Please choose another.</div>';
        } else {
            // Hash the password using SHA-256
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

// Fetch all users except 'company' role
$user_sql = "SELECT id, name, username, mobilenumber, role FROM user WHERE role != 'company' ORDER BY id DESC";
$user_result = $conn->query($user_sql);
$users = [];
while ($row = $user_result->fetch_assoc()) {
    $users[] = $row;
}

// For dropdown options (only admin, delivery partner)
$role_options = [
    'admin' => 'Admin',
    'delivery partner' => 'Delivery Partner'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Snack World Admin Dashboard</title>
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
    .dashboard-content { min-height: 80vh; display: flex; align-items: center; justify-content: center; }
    .welcome-box {
      background: #171b32;
      border-radius: 18px;
      box-shadow: 0 4px 24px rgba(0,0,0,0.08);
      padding: 40px 30px;
      text-align: center;
      max-width: 700px;
      width: 100%;
    }
    .welcome-box h2 { color: #ffb200; font-weight: 700; }
    /* User Table Styles */
    .user-table th { color: #ffb200; }
    .user-table td, .user-table th { vertical-align: middle; }
    .add-user-form { background: #23243a; border-radius: 10px; padding: 24px 18px; margin-bottom: 32px; }
    .add-user-form label { color: #ffb200; }
    .add-user-form .form-control, .add-user-form .form-select { background: #181b2c; border: 1px solid #44465e; color: #fff; }
  </style>
</head>
<body>
<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="#"><i class="fa-solid fa-utensils"></i>Snack World <span class="d-none d-md-inline">Admin</span></a>
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

<!-- DASHBOARD CONTENT -->
<div class="dashboard-content">
  <div class="welcome-box">
    <h2>Welcome to Admin Dashboard</h2>
    <p class="mt-3 mb-0">Use the navigation bar to add new menu items or view recent orders.</p>
    <div class="mt-4">
      <a href="add_item.php" class="btn btn-warning me-2"><i class="fa-solid fa-plus"></i> Add Item</a>
      <a href="view_orders.php" class="btn btn-primary"><i class="fa-solid fa-list"></i> View Orders</a>
    </div>
    <!-- Add User Form -->
    <div class="mt-5 mb-4 text-start">
      <h5 style="color:#ffb200;"><i class="fa-solid fa-user-plus"></i> Add User (Admin or Delivery Partner)</h5>
      <?php if ($user_message) echo $user_message; ?>
      <form method="post" class="add-user-form">
        <div class="row g-3">
          <div class="col-md-6">
            <label for="name" class="form-label">Name *</label>
            <input type="text" id="name" name="name" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label for="username" class="form-label">Username *</label>
            <input type="text" id="username" name="username" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label for="mobilenumber" class="form-label">Mobile Number *</label>
            <input type="text" id="mobilenumber" name="mobilenumber" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label for="role" class="form-label">Role *</label>
            <select id="role" name="role" class="form-select" required>
              <?php foreach($role_options as $value => $label): ?>
                <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label for="password" class="form-label">Password *</label>
            <input type="password" id="password" name="password" class="form-control" required>
            <small class="text-secondary">Password will be stored as SHA-256 hash.</small>
          </div>
        </div>
        <button type="submit" name="add_user" class="btn btn-success mt-3 px-4"><i class="fa fa-user-plus"></i> Add User</button>
      </form>
    </div>
    <!-- User Table -->
    <div class="mt-5">
      <h5 style="color:#ffb200;"><i class="fa-solid fa-users"></i> Users (Admin & Delivery Partner)</h5>
      <div class="table-responsive mt-3">
        <table class="table table-dark table-hover user-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Name</th>
              <th>Username</th>
              <th>Mobile Number</th>
              <th>Role</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($users)): ?>
              <tr>
                <td colspan="5" class="text-center text-muted">No users found.</td>
              </tr>
            <?php else:  
              foreach ($users as $i => $user): ?>
              <tr>
                <td><?php echo $i+1; ?></td>
                <td><?php echo htmlspecialchars($user['name']); ?></td>
                <td><?php echo htmlspecialchars($user['username']); ?></td>
                <td><?php echo htmlspecialchars($user['mobilenumber']); ?></td>
                <td>
                  <?php 
                    echo isset($role_options[$user['role']]) ? $role_options[$user['role']] : ucfirst($user['role']);
                  ?>
                </td>
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