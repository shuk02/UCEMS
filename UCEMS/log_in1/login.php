<?php
session_start();
ob_start();
include("db.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = mysqli_real_escape_string($con, trim($_POST['name']));
    $pw = trim($_POST['password']);

    // Prepare query to get user details
    $stmt = $con->prepare("SELECT username, password FROM lecturers WHERE username = ?");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        // Verify hashed password
        if (password_verify($pw, $row['password'])) {
            $_SESSION['uname'] = $name;
            $stmt->close();
            header('Location: log_img1.php');
            exit;
        } else {
            $_SESSION['error'] = "Invalid password!";
            $stmt->close();
            header('Location: invalid_textpw.html'); // Redirect to invalid password page
            exit;
        }
    } else {
        $_SESSION['error'] = "Username not found!";
        $stmt->close();
        header('Location: invalid_textpw.html'); // Redirect back to login page
        exit;
    }
}
?>
