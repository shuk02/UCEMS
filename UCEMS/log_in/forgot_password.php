<?php
session_start();
ob_start();

// Include database connection
include '../db.php';

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Adjust the path to autoload.php to point to the root directory
require '../vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($con, trim($_POST['email']));

    // Validate email
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Please enter a valid email address.";
        header('Location: forgot_password.php');
        exit;
    }

    // Check if email exists in the user table
    $stmt = $con->prepare("SELECT id, email FROM user WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Email exists, generate a token
        $token = bin2hex(random_bytes(32)); // Generate a secure random token
        $token_expiry = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token expires in 1 hour
        $token_status = 0; // Unused token

        // Update the user table with the token, expiry, and status
        $stmt = $con->prepare("UPDATE user SET token = ?, token_expiry = ?, token_status = ? WHERE email = ?");
        $stmt->bind_param("ssis", $token, $token_expiry, $token_status, $email);
        if ($stmt->execute()) {
            // Send email with reset link
            $reset_link = "http://localhost/UCEMS/log_in/reset_password.php?token=" . $token;

            $mail = new PHPMailer(true);
            try {
                // SMTP settings for Gmail
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'lusvitoon@gmail.com'; // Replace with your Gmail address
                $mail->Password = 'qyehiwnrezlfphcf'; // Use the app password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                // Email settings
                $mail->setFrom('lusvitoon@gmail.com', 'UPTM Campus Entrepreneurship Manager');
                $mail->addAddress($email);
                $mail->isHTML(true);
                $mail->Subject = 'Password Reset Request';
                $mail->Body = "
                    <h2>Password Reset Request</h2>
                    <p>You have requested to reset your password for the UPTM Campus Entrepreneurship Manager system.</p>
                    <p>Please click the link below to reset your password:</p>
                    <p><a href='$reset_link'>Reset Password</a></p>
                    <p>This link will expire in 1 hour.</p>
                    <p>If you did not request a password reset, please ignore this email.</p>
                ";
                $mail->AltBody = "You have requested to reset your password. Please visit this link to reset your password: $reset_link. This link will expire in 1 hour.";

                $mail->send();
                $_SESSION['success'] = "A password reset link has been sent to your email. Check at your SPAM email";
                header('Location: forgot_password.php');
                exit;
            } catch (Exception $e) {
                $_SESSION['error'] = "Failed to send email. Error: {$mail->ErrorInfo}";
                header('Location: forgot_password.php');
                exit;
            }
        } else {
            $_SESSION['error'] = "Failed to generate reset token.";
            header('Location: forgot_password.php');
            exit;
        }
    } else {
        // Email not found
        $_SESSION['error'] = "No account found with that email address.";
        header('Location: forgot_password.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">
    <link rel="stylesheet" href="../css/style-footer.css">
    <link href="css/style1.css" rel="stylesheet">
    <link href="css/style-body.css" rel="stylesheet" type="text/css" media="all"/>
    <link rel="stylesheet" href="../css/demo.css">
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

    <!-- Forgot Password Form -->
    <div class="signupform">
        <div class="container">
            <div class="agile_info">
                <div class="login_form">
                    <div class="left_grid_info">
                        <h1>Manage Your User Account</h1>
                        <p>This system provides high security to your account through the graphical password.</p><br>
                        <img class="im1" src="../images/cover1.jpg" height="270" width="370">
                    </div>
                </div>
                <div class="login_info">
                    <h2>Forgot Password</h2>
                    <p>Enter your email address to receive a password reset link.</p>
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
                    <form action="forgot_password.php" method="post">
                        <label>Email</label>
                        <div class="input-group">
                            <span class="fa fa-envelope"></span>
                            <input type="email" name="email" placeholder="Enter Your Student Email" required>
                        </div>
                        <button class="btn btn-danger" type="submit">Send Reset Link</button>
                    </form>
                    <p class="account">Back to <a href="../log_in/login.html">Login</a></p>
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