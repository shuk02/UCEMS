<?php
session_start();
ob_start();

// Check if the lecturer is logged in
if (!isset($_SESSION['uname'])) {
    header("Location: login.php");
    exit();
}

// Database Connection
include 'db.php';

// Verify the lecturer exists in the lecturers table
$lecturer_username = $_SESSION['uname'];
$stmt = $con->prepare("SELECT username FROM lecturers WHERE username = ?");
$stmt->bind_param("s", $lecturer_username);
$stmt->execute();
$lecturer_result = $stmt->get_result();
if ($lecturer_result->num_rows == 0) {
    session_destroy();
    header("Location: login.php");
    exit();
}
$stmt->close();

// Check if the username is provided
if (!isset($_GET['username']) || empty($_GET['username'])) {
    header("Location: lecturer_dashboard.php");
    exit();
}

$username = $_GET['username'];

// Delete the user's files from the uploads directory and database
$stmt = $con->prepare("SELECT file_path FROM files WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$files = $stmt->get_result();
while ($file = $files->fetch_assoc()) {
    if (!empty($file['file_path']) && file_exists($file['file_path'])) {
        unlink($file['file_path']);
    }
}
$stmt = $con->prepare("DELETE FROM files WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();

// Delete the user's products
$stmt = $con->prepare("DELETE FROM products WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();

// Delete the user's comments
$stmt = $con->prepare("DELETE FROM comments WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();

// Delete the user's business info
$stmt = $con->prepare("DELETE FROM business_info WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();

// Delete the user's transactions
$stmt = $con->prepare("DELETE FROM transactions WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();

// Delete the user
$stmt = $con->prepare("DELETE FROM user WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->close();

// Redirect back to lecturer_dashboard.php
header("Location: lecturer_dashboard.php");
exit();
?>