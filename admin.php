<?php
require_once 'db.php';
session_start();

$error_message = "";

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username && $password) {
        // Hash the entered password using SHA-256
        $hashed_password = hash('sha256', $password);

        // Check user
        $stmt = $conn->prepare("SELECT * FROM user WHERE username = ? AND password = ?");
        $stmt->bind_param("ss", $username, $hashed_password);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name'];

            // Redirect based on user role
            if ($user['role'] === 'admin') {
                header("Location: admin_dashboard.php");
            } elseif ($user['role'] === 'company') {
                header("Location: company_dashboard.php");
            } elseif ($user['role'] === 'delivery partner') {
                header("Location: delivery_dashboard.php");
            } else {
                $error_message = "Unknown role.";
            }
            exit;
        } else {
            $error_message = "Invalid username or password.";
        }
    } else {
        $error_message = "Please enter both username and password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Admin Login | Snack World</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"/>
    <style>
        body { background: #101326; color: #fff; min-height: 100vh; font-family: 'Roboto', Arial, sans-serif; }
        .login-box { background: #171b32; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.08); padding: 36px; margin-top: 80px; }
        .form-label { color: #ffb200; font-weight: 700; }
        .btn-login { background: #ffb200; color: #171b32; font-weight: 700; border-radius: 8px; }
        .btn-login:hover, .btn-login:focus { background: #ffc107; color: #101326; }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center align-items-center" style="min-height:90vh;">
        <div class="col-md-6 col-lg-4">
            <div class="login-box">
                <h3 class="mb-4 text-center" style="color:#ffb200;"><i class="fa fa-user-shield"></i> Admin Login</h3>
                <?php if ($error_message): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>
                <form method="post" autocomplete="off">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-login w-100">Login</button>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Font Awesome (optional for icon) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"/>
</body>
</html>