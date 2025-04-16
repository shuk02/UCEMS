<?php
session_start();
ob_start();
include("db.php");

// Sanitize and validate input
$name = mysqli_real_escape_string($con, trim($_POST['name']));
$pw = trim($_POST['password']);
$email = mysqli_real_escape_string($con, trim($_POST['email']));
$phone = mysqli_real_escape_string($con, trim($_POST['phone']));
$realname = mysqli_real_escape_string($con, trim($_POST['realname']));

// Validate email format (Only @student.uptm.edu.my allowed)
if (!preg_match("/^[a-zA-Z0-9._%+-]+@uptm\.edu\.my$/", $email)) {
    header('Location: email_failed.html');
    exit;
}

// Hash the password securely
$password = password_hash($pw, PASSWORD_DEFAULT);

// Check if username exists
$stmt = $con->prepare("SELECT * FROM lecturers WHERE username = ?");
$stmt->bind_param("s", $name);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    header('Location: username_failed.html');
    exit;
}
$stmt->close();

// Check if email exists
$stmt = $con->prepare("SELECT * FROM lecturers WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    header('Location: email_failed.html');
    exit;
}
$stmt->close();

// Check if phone exists
$stmt = $con->prepare("SELECT * FROM lecturers WHERE phone = ?");
$stmt->bind_param("s", $phone);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    header('Location: phone_failed.html');
    exit;
}
$stmt->close();

// Store user session data
$_SESSION['a'] = [$name, $password, $realname, $email, $phone];
header('Location: registration_img1.php');
exit;
?>
