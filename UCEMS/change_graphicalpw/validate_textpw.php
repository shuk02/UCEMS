<?php
session_start();
ob_start();

include("db.php");

// Check if the user is logged in
if (!isset($_SESSION['uname']) || empty($_SESSION['uname'])) {
    header('Location: login.php');
    exit();
}

// Get the user's username from the session
$name = $_SESSION['uname'];

// Check if the password is provided
if (!isset($_POST['password']) || empty($_POST['password'])) {
    header('Location: invalid_textpw.html');
    exit();
}

$pw = $_POST['password'];

// Fetch the user's hashed password from the database
$stmt = $con->prepare("SELECT password FROM user WHERE username = ?");
$stmt->bind_param("s", $name);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if ($user) {
    // Verify the password (assuming the stored password is hashed with password_hash)
    $stored_password = $user['password'];
    if (password_verify($pw, $stored_password)) {
        // Password is correct, proceed to change_img1.php
        header('Location: change_img1.php');
        exit();
    } else {
        // Invalid password
        header('Location: invalid_textpw.html');
        exit();
    }
} else {
    // Lecturer not found
    header('Location: invalid_textpw.html');
    exit();
}
?>