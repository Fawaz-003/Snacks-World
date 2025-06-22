<?php
require_once 'db.php';
session_start();
// Authentication: Only allow 'admin' users
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin.php");
    exit;
}

// Handle item deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    $conn->query("DELETE FROM item WHERE id=$del_id");
    header("Location: add_item.php");
    exit;
}

// Handle add item
$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_item"])) {
    $name = trim($_POST["name"]);
    $price = trim($_POST["price"]);
    $imageUrl = trim($_POST["image_url"]);

    if ($name && is_numeric($price) && $imageUrl) {
        $stmt = $conn->prepare("INSERT INTO item (name, image, price) VALUES (?, ?, ?)");
        $stmt->bind_param("ssd", $name, $imageUrl, $price);
        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>Item added successfully!</div>";
        } else {
            $message = "<div class='alert alert-danger'>Database error: " . htmlspecialchars($stmt->error) . "</div>";
        }
        $stmt->close();
    } else {
        $message = "<div class='alert alert-danger'>Please fill all fields correctly.</div>";
    }
}

// Fetch all items
$result = $conn->query("SELECT * FROM item ORDER BY id DESC");
$items = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Add Item | Admin - Snack World</title>
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
    .table thead {background: #0a0e22;}
    .table th, .table td { vertical-align: middle; }
    .table img { border-radius: 8px; }
    .action-btns .btn { margin-right: 3px; }
    @media (max-width: 600px) {
      .table-responsive { font-size: 0.92em; }
      .navbar-brand { font-size: 1.2rem;}
    }
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
        <li class="nav-item"><a class="nav-link active" href="add_item.php"><i class="fa-solid fa-plus"></i> Add Item</a></li>
        <li class="nav-item"><a class="nav-link" href="view_orders.php"><i class="fa-solid fa-list"></i> View Orders</a></li>
        <li class="nav-item ms-2"><a class="btn btn-danger" href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
      </ul>
    </div>
  </div>
</nav>
<div class="container py-5">
  <div class="row justify-content-center mb-4">
    <div class="col-md-7 col-lg-5">
      <div class="card p-4">
        <h3 class="mb-4 text-center" style="color:#ffb200;"><i class="fa-solid fa-plus"></i> Add New Snack Item</h3>
        <?php if($message) echo $message; ?>
        <form method="post" action="" autocomplete="off">
          <input type="hidden" name="add_item" value="1">
          <div class="mb-3">
            <label class="form-label">Item Name</label>
            <input type="text" name="name" class="form-control" required maxlength="128" placeholder="e.g. Veg Sandwich">
          </div>
          <div class="mb-3">
            <label class="form-label">Price (₹)</label>
            <input type="number" name="price" class="form-control" required min="1" step="0.01" placeholder="e.g. 25">
          </div>
          <div class="mb-3">
            <label class="form-label">Image URL</label>
            <input type="url" name="image_url" class="form-control" required placeholder="https://example.com/image.jpg">
          </div>
          <button type="submit" class="btn btn-order w-100 mt-2"><i class="fa-solid fa-plus"></i> Add Item</button>
        </form>
      </div>
    </div>
  </div>
  
  <!-- List of Items -->
  <div class="row justify-content-center">
    <div class="col-lg-10">
      <div class="card p-3">
        <h4 class="mb-3" style="color:#ffb200;"><i class="fa-solid fa-list"></i> Items List</h4>
        <div class="table-responsive">
          <table class="table table-dark table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>#</th>
                <th>Image</th>
                <th>Name</th>
                <th>Price (₹)</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if(empty($items)): ?>
                <tr>
                  <td colspan="5" class="text-center text-muted">No items added yet.</td>
                </tr>
              <?php else: foreach($items as $i => $row): ?>
                <tr>
                  <td><?php echo $i+1; ?></td>
                  <td>
                    <?php if($row['image']): ?>
                      <img src="<?php echo htmlspecialchars($row['image']); ?>" alt="img" width="60" height="45">
                    <?php else: ?>
                      <span class="text-muted">No Image</span>
                    <?php endif; ?>
                  </td>
                  <td><?php echo htmlspecialchars($row['name']); ?></td>
                  <td>₹<?php echo number_format($row['price'],2); ?></td>
                  <td class="action-btns">
                    <a href="edit_item.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning" title="Edit"><i class="fa-solid fa-pen-to-square"></i></a>
                    <a href="add_item.php?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this item?');" title="Delete"><i class="fa-solid fa-trash"></i></a>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>