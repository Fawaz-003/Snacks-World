<?php
require_once 'db.php';
session_start();
// Authentication: Only allow 'admin' users
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin.php");
    exit;
}

// Validate ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: add_item.php");
    exit;
}
$id = intval($_GET['id']);

// Fetch existing item
$stmt = $conn->prepare("SELECT * FROM item WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$item = $result->fetch_assoc();
$stmt->close();

if (!$item) {
    echo "<div class='alert alert-danger'>Item not found.</div>";
    exit;
}

$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST["name"]);
    $price = trim($_POST["price"]);
    $imageUrl = trim($_POST["image_url"]);

    if ($name && is_numeric($price) && $imageUrl) {
        $stmt = $conn->prepare("UPDATE item SET name=?, image=?, price=? WHERE id=?");
        $stmt->bind_param("ssdi", $name, $imageUrl, $price, $id);
        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>Item updated successfully!</div>";
            // Refresh item data after update
            $item['name'] = $name;
            $item['image'] = $imageUrl;
            $item['price'] = $price;
        } else {
            $message = "<div class='alert alert-danger'>Database error: " . htmlspecialchars($stmt->error) . "</div>";
        }
        $stmt->close();
    } else {
        $message = "<div class='alert alert-danger'>Please fill all fields correctly.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Edit Item | Admin - Snack World</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"/>
  <style>
    body { background: #101326; color: #fff; font-family: 'Roboto', Arial, sans-serif; }
    .navbar { background: #0a0e22 !important; }
    .navbar-brand { font-family: 'Montserrat', sans-serif; font-weight: 700; font-size: 2rem; color: #ffb200 !important; }
    .nav-link { color: #fff !important; font-family: 'Montserrat', sans-serif; font-weight: 700; }
    .nav-link.active, .nav-link:hover { color: #ffb200 !important; }
    .form-label { color: #ffb200; font-weight: 700; }
    .btn-order { background: #ffb200; color: #181818; border-radius: 8px; font-family: 'Montserrat', sans-serif; font-weight: 700; border: none; }
    .btn-order:hover { background: #e4a000; color: #fff; }
    .card { background: #171b32; border: none; border-radius: 16px; color: #fff; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="admin_dashboard.php"><i class="fa-solid fa-utensils"></i>Snack World Admin</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav" aria-controls="adminNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon" style="color: #ffb200"></span>
    </button>
    <div class="collapse navbar-collapse" id="adminNav">
      <ul class="navbar-nav ms-auto align-items-center">
        <li class="nav-item"><a class="nav-link" href="add_item.php"><i class="fa-solid fa-plus"></i> Add Item</a></li>
        <li class="nav-item"><a class="nav-link" href="view_orders.php"><i class="fa-solid fa-list"></i> View Orders</a></li>
        <li class="nav-item ms-2"><a class="btn btn-danger" href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
      </ul>
    </div>
  </div>
</nav>
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-7 col-lg-5">
      <div class="card p-4">
        <h3 class="mb-4 text-center" style="color:#ffb200;"><i class="fa-solid fa-pen-to-square"></i> Edit Snack Item</h3>
        <?php if($message) echo $message; ?>
        <form method="post" action="" autocomplete="off">
          <div class="mb-3">
            <label class="form-label">Item Name</label>
            <input type="text" name="name" class="form-control" required maxlength="128" value="<?php echo htmlspecialchars($item['name']); ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Price (₹)</label>
            <input type="number" name="price" class="form-control" required min="1" step="0.01" value="<?php echo htmlspecialchars($item['price']); ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Image URL</label>
            <input type="url" name="image_url" class="form-control" required value="<?php echo htmlspecialchars($item['image']); ?>">
            <?php if ($item['image']): ?>
              <div class="text-center mt-2">
                <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="Item Image" style="max-width:150px; border-radius:8px;">
              </div>
            <?php endif; ?>
          </div>
          <div class="d-flex justify-content-between">
            <a href="add_item.php" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
            <button type="submit" class="btn btn-order"><i class="fa-solid fa-save"></i> Update Item</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>