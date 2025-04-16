<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Database Connection
include 'db.php';

// Get the logged-in user's username
$username = $_SESSION['username'];

// Check if the transaction ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$transaction_id = $_GET['id'];

// Verify the transaction belongs to the user
$stmt = $con->prepare("SELECT username FROM transactions WHERE id = ?");
$stmt->bind_param("i", $transaction_id);
$stmt->execute();
$transaction = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$transaction || $transaction['username'] !== $username) {
    header("Location: dashboard.php");
    exit();
}

// Delete the transaction
$stmt = $con->prepare("DELETE FROM transactions WHERE id = ?");
$stmt->bind_param("i", $transaction_id);
$stmt->execute();
$stmt->close();

// Redirect back to dashboard.php to refresh the page
header("Location: dashboard.php");
exit();
?>