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
$display_username = $_SESSION['uname'] ?? 'Unknown';

// Handle File Upload
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!empty($_FILES['file']['name'])) {
        $target_dir = "../user/uploads/";
        // Create uploads directory if it doesn't exist
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        // Validate file type
        $allowed_types = ['application/pdf', 'image/jpeg', 'image/png', 'image/gif', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        $file_type = $_FILES['file']['type'];
        if (!in_array($file_type, $allowed_types)) {
            die("Error: Only PDF, JPEG, PNG, GIF, and Word documents are allowed.");
        }
        // Validate file size (e.g., 5MB limit)
        $max_size = 5 * 1024 * 1024; // 5MB
        if ($_FILES['file']['size'] > $max_size) {
            die("Error: File size exceeds 5MB limit.");
        }
        // Use the original filename
        $original_filename = $_FILES["file"]["name"];
        $file_path = $target_dir . $original_filename;
        $file_name = $_FILES["file"]["name"]; // Original file name

        // Move the uploaded file to the uploads directory
        if (move_uploaded_file($_FILES["file"]["tmp_name"], $file_path)) {
            // Insert file details into the database
            $stmt = $con->prepare("INSERT INTO files (username, file_name, file_path) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $file_name, $file_path);
            $stmt->execute();
            $stmt->close();

            // Redirect to refresh the page and show the updated file list
            header("Location: upload.php");
            exit();
        } else {
            die("Error: Failed to upload the file.");
        }
    } else {
        die("Error: No file selected.");
    }
}

// Fetch the user's uploaded files
$stmt = $con->prepare("SELECT * FROM files WHERE username = ? ORDER BY upload_time DESC");
$stmt->bind_param("s", $username);
$stmt->execute();
$files = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>File Upload - Campus Entrepreneurship Manager</title>
    <link href="style.css" rel="stylesheet">
    <link href="css/style-header.css" rel="stylesheet">
    <link href="css/style-body.css" rel="stylesheet">
    <style>
        .form-container, .table-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            margin-bottom: 20px;
            background: #fff;
        }
        .form-container label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-container input {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .form-container button {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .form-container button:hover {
            background-color: #0056b3;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table th, table td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ccc;
        }
        table th {
            background-color: #007bff;
            color: white;
        }
        table a {
            margin-right: 10px;
            text-decoration: none;
            color: #007bff;
        }
        table a:hover {
            text-decoration: underline;
        }
    </style>
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
                              <img src="images/logo-uptm.png" alt="UPTM Logo" style="max-width: 100px; height: auto">
                   </li>
                <li><a href="../user/dashboard.php">Dashboard</a></li>
                <li><a href="../user/product.php">Product Management</a></li>
                <li class="active"><a href="../user/upload.php">Upload</a></li>
                <li><a href="../user/business_info.php">Business Info</a></li>
                <li><a href="../user/user_profile.php">View Profile</a></li>
                <li><a href="../user/logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>
<!-- End Main Header -->
 <br>
 <br>
<h2>File Upload</h2>

<!-- File Upload Form -->
<div class="form-container">
    <h2>Upload a File</h2>
    <form action="upload.php" method="POST" enctype="multipart/form-data">
        <label for="file">File:</label>
        <input type="file" name="file" id="file" required>

        <button type="submit">Upload File</button>
    </form>
</div>

<!-- File List -->
<div class="table-container">
    <h2>Uploaded Files</h2>
    <table>
        <thead>
            <tr>
                <th>File Name</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $files->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['file_name']); ?></td>
                    <td>
                        <a href="<?php echo htmlspecialchars($row['file_path']); ?>" target="_blank">View</a> |
                        <a href="<?php echo htmlspecialchars($row['file_path']); ?>" download="<?php echo htmlspecialchars($row['file_name']); ?>">Download</a> |
                        <a href="delete_file.php?id=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure you want to delete this file?')">Delete</a> 
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script src="plugins/bootstrap.min.js"></script>
</body>
</html>