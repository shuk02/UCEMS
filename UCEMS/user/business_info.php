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

// Handle Delete Action
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    // Ensure the record belongs to the user
    $stmt = $con->prepare("DELETE FROM business_info WHERE id = ? AND username = ?");
    $stmt->bind_param("is", $delete_id, $username);
    $stmt->execute();
    $stmt->close();
    header("Location: business_info.php");
    exit();
}

// Fetch the user's business information (if it exists)
$stmt = $con->prepare("SELECT * FROM business_info WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$business_info_result = $stmt->get_result();
$business_info = $business_info_result->fetch_assoc();
$stmt->close();

// Handle Business Info Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $business_name = $con->real_escape_string($_POST['business_name']);
    $business_description = $con->real_escape_string($_POST['business_description']);
    $course_name = $con->real_escape_string($_POST['course_name']);
    $subject_code_name = $con->real_escape_string($_POST['subject_code_name']);
    $study_level = $con->real_escape_string($_POST['study_level']);
    $class_section = $con->real_escape_string($_POST['class_section']);
    $facebook_link = $con->real_escape_string($_POST['facebook_link']);
    $instagram_link = $con->real_escape_string($_POST['instagram_link']);
    $twitter_link = $con->real_escape_string($_POST['twitter_link']);
    $business_category = $con->real_escape_string($_POST['business_category']);

    // Validate study_level
    if (!in_array($study_level, ['Diploma', 'Degree'])) {
        die("Error: Invalid study level.");
    }

    // Validate business_category
    $valid_categories = [
        'Apparel, Health & Beauty',
        'Electronics, IT & Telecommunications',
        'Food & Beverage',
        'Household, Hobbies & Lifestyle',
        'Supplies & Services'
    ];
    if (!in_array($business_category, $valid_categories)) {
        die("Error: Invalid business category.");
    }

    if ($business_info) {
        // Update existing business info
        $stmt = $con->prepare("UPDATE business_info SET business_name = ?, business_description = ?, course_name = ?, subject_code_name = ?, study_level = ?, class_section = ?, facebook_link = ?, instagram_link = ?, twitter_link = ?, business_category = ? WHERE username = ?");
        $stmt->bind_param("sssssssssss", $business_name, $business_description, $course_name, $subject_code_name, $study_level, $class_section, $facebook_link, $instagram_link, $twitter_link, $business_category, $username);
        if (!$stmt->execute()) {
            die("Error updating business info: " . $stmt->error);
        }
    } else {
        // Insert new business info
        $stmt = $con->prepare("INSERT INTO business_info (username, business_name, business_description, course_name, subject_code_name, study_level, class_section, facebook_link, instagram_link, twitter_link, business_category) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssssss", $username, $business_name, $business_description, $course_name, $subject_code_name, $study_level, $class_section, $facebook_link, $instagram_link, $twitter_link, $business_category);
        if (!$stmt->execute()) {
            die("Error inserting business info: " . $stmt->error);
        }
    }
    $stmt->close();

    // Redirect to refresh the page
    header("Location: business_info.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Business Information - Campus Entrepreneurship Manager</title>
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
        .form-container input, .form-container textarea, .form-container select {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .form-container textarea {
            height: 100px;
            resize: vertical;
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
            overflow-x: auto;
            display: block;
        }
        table th, table td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ccc;
            min-width: 100px;
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
        .social-link {
            color: #007bff;
            text-decoration: none;
        }
        .social-link:hover {
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
                <li><a href="../user/upload.php">Upload</a></li>
                <li class="active"><a href="../user/business_info.php" class="active">Business Info</a></li>
                <li><a href="../user/user_profile.php">View Profile</a></li>
                <li><a href="../user/logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>
<!-- End Main Header -->

<br>
<br>
<h2>Business Information</h2>

<!-- Business Info Form -->
<div class="form-container">
    <h2><?php echo $business_info ? 'Edit Business Information' : 'Add Business Information'; ?></h2>
    <form action="business_info.php" method="POST">
        <label for="business_name">Business Name:</label>
        <input type="text" name="business_name" id="business_name" value="<?php echo $business_info ? htmlspecialchars($business_info['business_name']) : ''; ?>" required>

        <label for="business_description">Business Description:</label>
        <textarea name="business_description" id="business_description" required><?php echo $business_info ? htmlspecialchars($business_info['business_description']) : ''; ?></textarea>

        <label for="course_name">Course Name:</label>
        <input type="text" name="course_name" id="course_name" value="<?php echo $business_info ? htmlspecialchars($business_info['course_name']) : ''; ?>" required>

        <label for="subject_code_name">Subject Code and Name:</label>
        <input type="text" name="subject_code_name" id="subject_code_name" value="<?php echo $business_info ? htmlspecialchars($business_info['subject_code_name']) : ''; ?>" required>

        <label for="study_level">Study Level:</label>
        <select name="study_level" id="study_level" required>
            <option value="Diploma" <?php echo $business_info && $business_info['study_level'] == 'Diploma' ? 'selected' : ''; ?>>Diploma</option>
            <option value="Degree" <?php echo $business_info && $business_info['study_level'] == 'Degree' ? 'selected' : ''; ?>>Degree</option>
        </select>

        <label for="class_section">Class Section:</label>
        <input type="text" name="class_section" id="class_section" value="<?php echo $business_info ? htmlspecialchars($business_info['class_section']) : ''; ?>" required>

        <label for="facebook_link">Facebook Link (optional):</label>
        <input type="url" name="facebook_link" id="facebook_link" value="<?php echo $business_info ? htmlspecialchars($business_info['facebook_link']) : ''; ?>" placeholder="https://facebook.com/yourpage">

        <label for="instagram_link">Instagram Link (optional):</label>
        <input type="url" name="instagram_link" id="instagram_link" value="<?php echo $business_info ? htmlspecialchars($business_info['instagram_link']) : ''; ?>" placeholder="https://instagram.com/yourpage">

        <label for="twitter_link">Twitter Link (optional):</label>
        <input type="url" name="twitter_link" id="twitter_link" value="<?php echo $business_info ? htmlspecialchars($business_info['twitter_link']) : ''; ?>" placeholder="https://twitter.com/yourpage">

        <label for="business_category">Business Category:</label>
        <select name="business_category" id="business_category" required>
            <option value="Apparel, Health & Beauty" <?php echo $business_info && $business_info['business_category'] == 'Apparel, Health & Beauty' ? 'selected' : ''; ?>>Apparel, Health & Beauty</option>
            <option value="Electronics, IT & Telecommunications" <?php echo $business_info && $business_info['business_category'] == 'Electronics, IT & Telecommunications' ? 'selected' : ''; ?>>Electronics, IT & Telecommunications</option>
            <option value="Food & Beverage" <?php echo $business_info && $business_info['business_category'] == 'Food & Beverage' ? 'selected' : ''; ?>>Food & Beverage</option>
            <option value="Household, Hobbies & Lifestyle" <?php echo $business_info && $business_info['business_category'] == 'Household, Hobbies & Lifestyle' ? 'selected' : ''; ?>>Household, Hobbies & Lifestyle</option>
            <option value="Supplies & Services" <?php echo $business_info && $business_info['business_category'] == 'Supplies & Services' ? 'selected' : ''; ?>>Supplies & Services</option>
        </select>

        <button type="submit"><?php echo $business_info ? 'Update Business Info' : 'Save Business Info'; ?></button>
    </form>
</div>

<!-- Business Info Table -->
<div class="table-container">
    <h2>Business Information List</h2>
    <table>
        <thead>
            <tr>
                <th>Username</th>
                <th>Business Name</th>
                <th>Business Description</th>
                <th>Course Name</th>
                <th>Subject Code and Name</th>
                <th>Study Level</th>
                <th>Class Section</th>
                <th>Facebook Link</th>
                <th>Instagram Link</th>
                <th>Twitter Link</th>
                <th>Business Category</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($business_info): ?>
                <tr>
                    <td><?php echo htmlspecialchars($business_info['username']); ?></td>
                    <td><?php echo htmlspecialchars($business_info['business_name']); ?></td>
                    <td><?php echo htmlspecialchars($business_info['business_description']); ?></td>
                    <td><?php echo htmlspecialchars($business_info['course_name']); ?></td>
                    <td><?php echo htmlspecialchars($business_info['subject_code_name']); ?></td>
                    <td><?php echo htmlspecialchars($business_info['study_level']); ?></td>
                    <td><?php echo htmlspecialchars($business_info['class_section']); ?></td>
                    <td>
                        <?php if ($business_info['facebook_link']): ?>
                            <a href="<?php echo htmlspecialchars($business_info['facebook_link']); ?>" class="social-link" target="_blank">Facebook</a>
                        <?php else: ?>
                            N/A
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($business_info['instagram_link']): ?>
                            <a href="<?php echo htmlspecialchars($business_info['instagram_link']); ?>" class="social-link" target="_blank">Instagram</a>
                        <?php else: ?>
                            N/A
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($business_info['twitter_link']): ?>
                            <a href="<?php echo htmlspecialchars($business_info['twitter_link']); ?>" class="social-link" target="_blank">Twitter</a>
                        <?php else: ?>
                            N/A
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($business_info['business_category'] ?? 'N/A'); ?></td>
                    <td>
                        <a href="business_info.php?edit=1">Edit</a> |
                        <a href="business_info.php?delete=<?php echo $business_info['id']; ?>" onclick="return confirm('Are you sure you want to delete this business information?')">Delete</a>
                    </td>
                </tr>
            <?php else: ?>
                <tr>
                    <td colspan="12">No business information found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script src="plugins/bootstrap.min.js"></script>
</body>
</html>