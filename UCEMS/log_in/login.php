<?php
session_start();
ob_start();
include("db.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = mysqli_real_escape_string($con, trim($_POST['name']));
    $pw = trim($_POST['password']);

    // Validate input
    if (empty($name) || empty($pw)) {
        $_SESSION['error'] = "Username and password are required!";
        header('Location: invalid_textpw.html');
        exit;
    }

    // Prepare query to get user details
    $stmt = $con->prepare("SELECT username, password FROM user WHERE username = ?");
    if (!$stmt) {
        $_SESSION['error'] = "Database error: " . $con->error;
        header('Location: invalid_textpw.html');
        exit;
    }

    $stmt->bind_param("s", $name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        // Verify hashed password
        if (password_verify($pw, $row['password'])) {
            // Store username in session
            $_SESSION['username'] = $row['username']; 
            $_SESSION['uname'] = $row['username'];
            $stmt->close();
            ob_end_clean();
            header('Location: log_img1.php');
            exit;
        } else {
            $_SESSION['error'] = "Invalid password!";
            $stmt->close();
            ob_end_clean();
            header('Location: invalid_textpw.html');
            exit;
        }
    } else {
        $_SESSION['error'] = "Username not found!";
        $stmt->close();
        ob_end_clean();
        header('Location: invalid_textpw.html');
        exit;
    }
}

// Close the database connection
$con->close();
?>