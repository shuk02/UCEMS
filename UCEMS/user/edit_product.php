<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Database Connection
include 'db.php';

// Get the logged-in user's username from the session
$username = $_SESSION['username'];
$display_username = $_SESSION['uname'] ?? 'Unknown';

// Check if the product ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: product.php");
    exit();
}

$product_id = intval($_GET['id']);

// Fetch the product details (only if it belongs to the logged-in user)
$stmt = $con->prepare("SELECT * FROM products WHERE id = ? AND username = ?");
$stmt->bind_param("is", $product_id, $username);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    // Product not found or doesn't belong to the user
    header("Location: product.php");
    exit();
}

// Handle Product Update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_name = $con->real_escape_string($_POST['name']);
    $quantity = intval($_POST['quantity']);
    $price = floatval($_POST['price']);
    $status = $con->real_escape_string($_POST['status']);
    $current_image = $product['image']; // Existing image path

    // Debug: Check the submitted status value
    error_log("Submitted status: " . $status);

    // Handle image upload
    $picture = $current_image; // Keep the current image by default
    if (!empty($_FILES['picture']['name'])) {
        $target_dir = "../user/images/products/";
        // Create the products directory if it doesn't exist
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        // Validate file type
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['picture']['type'];
        if (!in_array($file_type, $allowed_types)) {
            die("Error: Only JPEG, PNG, and GIF files are allowed.");
        }
        // Validate file size (e.g., 5MB limit)
        $max_size = 5 * 1024 * 1024; // 5MB
        if ($_FILES['picture']['size'] > $max_size) {
            die("Error: File size exceeds 5MB limit.");
        }
        // Use the original filename
        $original_filename = $_FILES["picture"]["name"];
        $picture = $target_dir . $original_filename;
        // Move the uploaded file to the products directory
        if (move_uploaded_file($_FILES["picture"]["tmp_name"], $picture)) {
            // Delete the old image file if a new one is uploaded
            if (!empty($current_image) && file_exists($current_image) && $current_image !== $picture) {
                unlink($current_image);
            }
        } else {
            die("Error: Failed to upload the image.");
        }
    }

    // Update the product in the database
    $stmt = $con->prepare("UPDATE products SET image = ?, name = ?, quantity = ?, price = ?, status = ? WHERE id = ? AND username = ?");
    $stmt->bind_param("ssidsis", $picture, $product_name, $quantity, $price, $status, $product_id, $username);
    if (!$stmt->execute()) {
        die("Error updating product: " . $stmt->error);
    }
    $stmt->close();

    // Redirect back to product.php
    header("Location: product.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Edit Product - Campus Entrepreneurship Manager</title>
    <link href="style.css" rel="stylesheet">
    <link href="css/style-header.css" rel="stylesheet">
    <link href="css/style-body.css" rel="stylesheet">
    <style>
        .form-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            margin-bottom: 20px;
            background: #fff;
        }
        .form-container label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-container input, .form-container select {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .form-container button {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .form-container button:hover {
            background-color: #0056b3;
        }
        .form-container img {
            max-width: 100px;
            height: auto;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

<!-- Main Header -->
<nav class="navbar navbar-default">
    <div class="container">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
        </div>
        <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
            <ul class="nav navbar-nav">
            <li>
                              <img src="images/logo-uptm.png" alt="UPTM Logo" style="max-width: 100px; height: auto">
                   </li>
                <li><a href="../user/dashboard.php">Dashboard</a></li>
                <li class="active"><a href="../user/product.php">Product Management</a></li>
                <li><a href="../user/upload.php">Upload</a></li>
                <li><a href="../user/business_info.php">Business Info</a></li>
                <li><a href="../user/user_profile.php">View Profile</a></li>
                <li><a href="../user/logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>
<!-- End Main Header -->
<br>
<br>
<!-- Edit Product Form -->
<div class="form-container">
    <h2>Edit Product</h2>
    <div>
        <h3>Current Product Image</h3>
        <?php if (!empty($product['image'])): ?>
            <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="Product Image">
        <?php else: ?>
            <p>No Image</p>
        <?php endif; ?>
    </div>
    <form action="edit_product.php?id=<?php echo $product_id; ?>" method="POST" enctype="multipart/form-data">
        <label for="picture">Picture (leave blank to keep current image):</label>
        <input type="file" name="picture" id="picture" accept="image/*">

        <label for="name">Product Name:</label>
        <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>

        <label for="quantity">Quantity:</label>
        <input type="number" name="quantity" id="quantity" min="0" value="<?php echo htmlspecialchars($product['quantity']); ?>" required>

        <label for="price">Price (RM):</label>
        <input type="number" name="price" id="price" step="0.01" min="0" value="<?php echo htmlspecialchars($product['price']); ?>" required>

        <label for="status">Status:</label>
        <select name="status" id="status" required>
            <option value="Available" <?php echo $product['status'] == 'Available' ? 'selected' : ''; ?>>Available</option>
            <option value="Out of Stock" <?php echo $product['status'] == 'Out of Stock' ? 'selected' : ''; ?>>Out of Stock</option>
        </select>

        <button type="submit">Update Product</button>
    </form>
</div>

<script src="plugins/bootstrap.min.js"></script>
</body>
</html>