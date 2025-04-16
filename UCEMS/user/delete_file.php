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

// Check if the file ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: upload.php");
    exit();
}

$file_id = intval($_GET['id']);

// Fetch the file to get the file path (only if it belongs to the user)
$stmt = $con->prepare("SELECT file_path FROM files WHERE id = ? AND username = ?");
$stmt->bind_param("is", $file_id, $username);
$stmt->execute();
$file = $stmt->get_result()->fetch_assoc();

if ($file) {
    // Delete the file from the uploads directory
    if (!empty($file['file_path']) && file_exists($file['file_path'])) {
        unlink($file['file_path']);
    }

    // Delete the file record from the database
    $stmt = $con->prepare("DELETE FROM files WHERE id = ? AND username = ?");
    $stmt->bind_param("is", $file_id, $username);
    $stmt->execute();
    $stmt->close();
}

// Redirect back to upload.php
header("Location: upload.php");
exit();
?>