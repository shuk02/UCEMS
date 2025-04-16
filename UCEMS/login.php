<!-- Login Form (save as login.php) -->
<!DOCTYPE html>
<html>
<head>
    <title>UPTM ENTREPRENEURSHIP MANAGER SYSTEM</title>
    <title>Login</title>
    <style>
        .form-container {
            max-width: 400px;
            margin: 0 auto;
            padding: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        input {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
        }
        .error {
            color: red;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="form-container">
    <h1>UPTM ENTREPRENEURSHIP MANAGER SYSTEM</h1>
        <h2>Login</h2>
        <?php if (isset($error)) echo "<div class='error'>$error</div>"; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Username:</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label>Password:</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" name="login">Login</button>
        </form>
        <p>Don't have an account? <a href="connect.php">Sign up here</a></p>
    </div>
</body>
</html>