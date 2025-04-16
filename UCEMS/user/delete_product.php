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

// Check if the product ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: product.php");
    exit();
}

$product_id = intval($_GET['id']);

// Fetch the product to get the image path (only if it belongs to the user)
$stmt = $con->prepare("SELECT image FROM products WHERE id = ? AND username = ?");
$stmt->bind_param("is", $product_id, $username);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if ($product) {
    // Delete the image file if it exists
    if (!empty($product['image']) && file_exists($product['image'])) {
        unlink($product['image']);
    }

    // Delete the product from the database
    $stmt = $con->prepare("DELETE FROM products WHERE id = ? AND username = ?");
    $stmt->bind_param("is", $product_id, $username);
    $stmt->execute();
    $stmt->close();
}

// Redirect back to product.php
header("Location: product.php");
exit();
?>