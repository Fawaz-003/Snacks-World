<?php
require_once 'db.php';
session_start();

// --- Helper Functions ---
function redirect($url) {
    header("Location: $url");
    exit;
}

// Check if an item was passed in the URL (from index.php)
$selected_item_name = isset($_GET['item']) ? trim($_GET['item']) : '';
$selected_item = null;
if ($selected_item_name !== '') {
    $stmt = $conn->prepare("SELECT * FROM item WHERE name = ?");
    $stmt->bind_param("s", $selected_item_name);
    $stmt->execute();
    $result = $stmt->get_result();
    $selected_item = $result->fetch_assoc();
    $stmt->close();
}

// --- Check if already logged in ---
if (isset($_SESSION['customer_mobile']) && !empty($_SESSION['customer_mobile'])) {
    // Check if customer really exists
    $mobile = $_SESSION['customer_mobile'];
    $stmt = $conn->prepare("SELECT * FROM customer WHERE mobile = ?");
    $stmt->bind_param("s", $mobile);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        // Already logged in, go to customer dashboard, preserve item if present
        if ($selected_item) {
            redirect("customer_dashboard.php?item=" . urlencode($selected_item['name']));
        } else {
            redirect("customer_dashboard.php");
        }
    } else {
        // Session exists but customer not in DB, clear session
        session_unset();
        session_destroy();
    }
}

// --- Logic: Step 1: Ask for mobile number, Step 2: If not found, ask for full details ---
$step = 1;
$mobile = "";
$message = "";
$name = "";
$address = "";

// Step 1: User starts with mobile number only
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['step']) && $_POST['step'] == "1") {
    $mobile = trim($_POST['mobile']);
    $item_hidden = isset($_POST['item_hidden']) ? trim($_POST['item_hidden']) : '';
    if (!preg_match('/^[0-9]{10}$/', $mobile)) {
        $message = "<div class='alert alert-danger'>Please enter a valid 10-digit mobile number.</div>";
    } else {
        $stmt = $conn->prepare("SELECT * FROM customer WHERE mobile = ?");
        $stmt->bind_param("s", $mobile);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $_SESSION['customer_mobile'] = $mobile;
            // Redirect to dashboard, preserve item if present (from hidden input)
            if ($item_hidden !== '') {
                redirect("customer_dashboard.php?item=" . urlencode($item_hidden));
            } else {
                redirect("customer_dashboard.php");
            }
        } else {
            $step = 2;
            $selected_item_name = $item_hidden; // propagate item to next step
        }
    }
}

// Step 2: User provides all details
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['step']) && $_POST['step'] == "2") {
    $mobile = trim($_POST['mobile']);
    $name = trim($_POST['name']);
    $address = trim($_POST['address']);
    $item_hidden = isset($_POST['item_hidden']) ? trim($_POST['item_hidden']) : '';
    if (!$name || !$mobile || !$address) {
        $message = "<div class='alert alert-danger'>All fields are required.</div>";
        $step = 2;
        $selected_item_name = $item_hidden;
    } elseif (!preg_match('/^[0-9]{10}$/', $mobile)) {
        $message = "<div class='alert alert-danger'>Please enter a valid 10-digit mobile number.</div>";
        $step = 2;
        $selected_item_name = $item_hidden;
    } else {
        $stmt = $conn->prepare("SELECT * FROM customer WHERE mobile = ?");
        $stmt->bind_param("s", $mobile);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $_SESSION['customer_mobile'] = $mobile;
            if ($item_hidden !== '') {
                redirect("customer_dashboard.php?item=" . urlencode($item_hidden));
            } else {
                redirect("customer_dashboard.php");
            }
        } else {
            $stmt = $conn->prepare("INSERT INTO customer (name, mobile, address) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $mobile, $address);
            if ($stmt->execute()) {
                $_SESSION['customer_mobile'] = $mobile;
                if ($item_hidden !== '') {
                    redirect("customer_dashboard.php?item=" . urlencode($item_hidden));
                } else {
                    redirect("customer_dashboard.php");
                }
            } else {
                $message = "<div class='alert alert-danger'>Database error: ".htmlspecialchars($stmt->error)."</div>";
                $step = 2;
                $selected_item_name = $item_hidden;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Customer Login | Snack World</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"/>
    <style>
        body { background: #101326; color: #fff; font-family: 'Roboto', Arial, sans-serif; }
        .center-container { min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card { background: #171b32; border: none; border-radius: 16px; color: #fff; box-shadow: 0 4px 24px rgba(0,0,0,0.08);}
        .form-label { color: #ffb200; font-weight: 700; }
        .btn-order { background: #ffb200; color: #181818; border-radius: 8px; font-family: 'Montserrat', sans-serif; font-weight: 700; border: none; }
        .btn-order:hover { background: #e4a000; color: #fff; }
        .navbar { background: #0a0e22 !important; }
        .navbar-brand { font-family: 'Montserrat', sans-serif; font-weight: 700; font-size: 2rem; color: #ffb200 !important; }
        .snack-title { color: #ffb200; }
        .selected-item-preview { background: #23284a; border-radius: 12px; padding: 12px; margin-bottom: 18px; display: flex; align-items: center; gap: 16px;}
        .selected-item-preview img { width: 64px; height: 64px; object-fit: cover; border-radius: 8px;}
        .selected-item-preview .item-title { font-weight: 700; color: #ffb200;}
        .selected-item-preview .item-price { color: #ffb200;}
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="#"><i class="fa-solid fa-utensils"></i>Snack World</a>
    </div>
</nav>
<div class="center-container">
    <div class="card p-4" style="min-width:320px; max-width:400px; width:100%;">
        <h3 class="mb-4 text-center snack-title"><i class="fa-solid fa-user"></i> Customer Login</h3>
        <?php if ($message) echo $message; ?>

        <?php
        // Always keep the item hidden input if present
        $item_hidden_val = htmlspecialchars($selected_item_name ?? '');
        ?>
        <?php if ($selected_item && $item_hidden_val !== ''): ?>
            <div class="selected-item-preview">
                <img src="<?php echo htmlspecialchars($selected_item['image']); ?>" alt="<?php echo htmlspecialchars($selected_item['name']); ?>">
                <div>
                    <div class="item-title"><?php echo htmlspecialchars($selected_item['name']); ?></div>
                    <div class="item-price">₹<?php echo number_format($selected_item['price']); ?></div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($step == 1): ?>
            <form method="post" autocomplete="off">
                <input type="hidden" name="step" value="1">
                <?php if ($item_hidden_val !== ''): ?>
                    <input type="hidden" name="item_hidden" value="<?php echo $item_hidden_val; ?>">
                <?php endif; ?>
                <div class="mb-3">
                    <label class="form-label">Mobile Number</label>
                    <input type="tel" name="mobile" class="form-control" required maxlength="10" pattern="[0-9]{10}" placeholder="10-digit Mobile">
                </div>
                <button type="submit" class="btn btn-order w-100 mt-2">Continue</button>
            </form>
        <?php elseif ($step == 2): ?>
            <form method="post" autocomplete="off">
                <input type="hidden" name="step" value="2">
                <input type="hidden" name="mobile" value="<?php echo htmlspecialchars($mobile); ?>">
                <?php if ($item_hidden_val !== ''): ?>
                    <input type="hidden" name="item_hidden" value="<?php echo $item_hidden_val; ?>">
                <?php endif; ?>
                <div class="mb-3">
                    <label class="form-label">Customer Name</label>
                    <input type="text" name="name" class="form-control" required maxlength="128" placeholder="Your Name" value="<?php echo htmlspecialchars($name); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" required maxlength="255" placeholder="Delivery Address"><?php echo htmlspecialchars($address); ?></textarea>
                </div>
                <button type="submit" class="btn btn-order w-100 mt-2">Register & Continue</button>
            </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>