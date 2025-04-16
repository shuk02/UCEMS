<?php
session_start();
ob_start();

// Include database connection
include '../db.php';

if (!isset($_GET['token']) || empty($_GET['token'])) {
    $_SESSION['error'] = "Invalid or missing token.";
    header('Location: ../log_in1/login.html');
    exit;
}

$token = $_GET['token'];

// Check if the token is valid
$stmt = $con->prepare("SELECT username, email, token_expiry, token_status FROM lecturers WHERE token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $_SESSION['error'] = "Invalid token.";
    header('Location: ../log_in1/login.html');
    exit;
}

$row = $result->fetch_assoc();
$current_time = date('Y-m-d H:i:s');

if ($row['token_status'] == 1) {
    $_SESSION['error'] = "This token has already been used.";
    header('Location: ../log_in1/login.html');
    exit;
}

if ($current_time > $row['token_expiry']) {
    $_SESSION['error'] = "This token has expired.";
    header('Location: ../log_in1/login.html');
    exit;
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Validate passwords
    if (empty($password) || empty($confirm_password)) {
        $_SESSION['error'] = "Both password fields are required.";
        header('Location: reset_password.php?token=' . $token);
        exit;
    }

    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match.";
        header('Location: reset_password.php?token=' . $token);
        exit;
    }

    if (strlen($password) < 8) {
        $_SESSION['error'] = "Password must be at least 8 characters long.";
        header('Location: reset_password.php?token=' . $token);
        exit;
    }

    // Hash the new password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Update the password and mark the token as used
    $stmt = $con->prepare("UPDATE lecturers SET password = ?, token = NULL, token_expiry = NULL, token_status = 1 WHERE token = ?");
    $stmt->bind_param("ss", $hashed_password, $token);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Your password has been reset successfully. Please log in.";
        header('Location: ../log_in1/login.html');
        exit;
    } else {
        $_SESSION['error'] = "Failed to reset password. Please try again.";
        header('Location: reset_password.php?token=' . $token);
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">
    <link rel="stylesheet" href="../css/style-footer.css">
    <link href="css/style1.css" rel="stylesheet">
    <link href="css/style-body.css" rel="stylesheet" type="text/css" media="all"/>
    <link rel="stylesheet" href="http://maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css">
    <link href="css/font-awesome.min.css" rel="stylesheet" type="text/css" media="all">
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
                        <img src="../images/logo/logo-uptm.png" alt="UPTM Logo" style="max-width: 100px; height: auto">
                    </li>
                    <li class="active">
                        <a href="../index.html">Home</a>
                    </li>
                    <li>
                        <a href="../about.html">About Us</a>
                    </li>
                    <li>
                        <a href="../contact.html">Contact Us</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <!-- End Main Header -->

    <!-- Reset Password Form -->
    <div class="signupform">
        <div class="container">
            <div class="agile_info">
                <div class="login_form">
                    <div class="left_grid_info">
                        <h1>Manage Your Account</h1>
                        <p>This system provides high security to your account through the graphical password.</p><br>
                        <img class="im1" src="../images/cover1.jpg" height="270" width="370">
                    </div>
                </div>
                <div class="login_info">
                    <h2>Reset Password</h2>
                    <p>Enter your new password below.</p>
                    <?php if (isset($_SESSION['success'])): ?>
                        <div style="text-align: center; margin: 10px 0; padding: 10px; border-radius: 5px; background-color: #d4edda; color: #155724;">
                            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['error'])): ?>
                        <div style="text-align: center; margin: 10px 0; padding: 10px; border-radius: 5px; background-color: #f8d7da; color: #721c24;">
                            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>
                    <form action="reset_password.php?token=<?php echo htmlspecialchars($token); ?>" method="post">
                        <label>New Password</label>
                        <div class="input-group">
                            <span class="fa fa-lock"></span>
                            <input type="password" name="password" placeholder="Enter New Password" required>
                        </div>
                        <label>Confirm Password</label>
                        <div class="input-group">
                            <span class="fa fa-lock"></span>
                            <input type="password" name="confirm_password" placeholder="Confirm New Password" required>
                        </div>
                        <button class="btn btn-danger" type="submit">Reset Password</button>
                    </form>
                    <p class="account">Back to <a href="../log_in1/login.html">Login</a></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../plugins/jquery.js"></script>
    <script src="../plugins/bootstrap.min.js"></script>
    <script src="../plugins/bootstrap-select.min.js"></script>
    <script src="../plugins/validate.js"></script>
    <script src="../plugins/wow.js"></script>
    <script src="../plugins/jquery-ui.js"></script>
    <script src="../js/script.js"></script>
</body>
</html>