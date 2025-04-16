<?php
session_start();
ob_start();
include("db.php");

// Check if the lecturer is logged in
if (!isset($_SESSION['uname']) || empty($_SESSION['uname'])) {
    header("Location: login.php");
    exit();
}

// Get the lecturer's username from the session
$username = $_SESSION['uname'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate inputs
    $old_password = trim($_POST['old'] ?? '');
    $new_password = trim($_POST['new'] ?? '');
    $confirm_password = trim($_POST['re-new'] ?? ''); // Changed from 'confirm' to 're-new' to match the front-end form

    if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
        header("Location: change_failed.html");
        exit();
    } elseif ($new_password !== $confirm_password) {
        header("Location: change_failed.html");
        exit();
    } elseif (strlen($new_password) < 8) {
        header("Location: change_failed.html");
        exit();
    }

    // Verify the old password
    $stmt = $con->prepare("SELECT password FROM lecturers WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $stored_password = $row['password'];

        // Verify the old password
        if (password_verify($old_password, $stored_password)) {
            // Hash the new password
            $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            // Update the password in the database
            $stmt2 = $con->prepare("UPDATE lecturers SET password = ? WHERE username = ?");
            $stmt2->bind_param("ss", $new_hashed_password, $username);
            if ($stmt2->execute()) {
                $stmt2->close();
                $stmt->close();
                header("Location: change_success.html");
                exit();
            } else {
                $stmt2->close();
                header("Location: change_failed.html");
                exit();
            }
        } else {
            header("Location: change_failed.html");
            exit();
        }
    } else {
        header("Location: change_failed.html");
        exit();
    }
    
}
?>