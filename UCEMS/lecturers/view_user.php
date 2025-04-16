<?php
session_start();
ob_start();

// Check if the lecturer is logged in
if (!isset($_SESSION['uname'])) {
    header("Location: login.php");
    exit();
}

// Database Connection
include 'db.php';

// Get the lecturer's username
$lecturer_username = $_SESSION['uname'];

// Verify the lecturer exists in the lecturers table
$stmt = $con->prepare("SELECT username FROM lecturers WHERE username = ?");
$stmt->bind_param("s", $lecturer_username);
$stmt->execute();
$lecturer_result = $stmt->get_result();
if ($lecturer_result->num_rows == 0) {
    session_destroy();
    header("Location: login.php");
    exit();
}
$stmt->close();

// Check if the username is provided
if (!isset($_GET['username']) || empty($_GET['username'])) {
    header("Location: lecturer_dashboard.php");
    exit();
}

$username = $_GET['username'];

// Fetch user details
$stmt = $con->prepare("SELECT * FROM user WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
if (!$user) {
    header("Location: lecturer_dashboard.php");
    exit();
}

// Fetch business info for the user
$stmt = $con->prepare("SELECT business_name, business_description, course_name, subject_code_name, study_level, class_section, facebook_link, instagram_link, twitter_link, business_category 
                       FROM business_info WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$business_info = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Handle user details update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_user'])) {
    $name = $con->real_escape_string($_POST['name']);
    $email = $con->real_escape_string($_POST['email']);
    $phone = $con->real_escape_string($_POST['phone']);

    $stmt = $con->prepare("UPDATE user SET name = ?, email = ?, phone = ? WHERE username = ?");
    $stmt->bind_param("ssss", $name, $email, $phone, $username);
    $stmt->execute();
    $stmt->close();

    // Refresh user data
    header("Location: view_user.php?username=" . urlencode($username));
    exit();
}

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_comment'])) {
    $comment = $con->real_escape_string($_POST['comment']);
    $stmt = $con->prepare("INSERT INTO comments (username, lecturer_username, comment) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $lecturer_username, $comment);
    $stmt->execute();
    $stmt->close();

    // Refresh the page
    header("Location: view_user.php?username=" . urlencode($username));
    exit();
}

// Fetch user products
$stmt = $con->prepare("SELECT * FROM products WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$products = $stmt->get_result();
$product_rows = [];
while ($row = $products->fetch_assoc()) {
    $product_rows[] = $row;
}

// Fetch user files
$stmt = $con->prepare("SELECT * FROM files WHERE username = ? ORDER BY upload_time DESC");
$stmt->bind_param("s", $username);
$stmt->execute();
$files = $stmt->get_result();

// Fetch user transactions with product name
$stmt = $con->prepare("
    SELECT t.*, p.name AS product_name 
    FROM transactions t 
    LEFT JOIN products p ON t.product_id = p.id 
    WHERE t.username = ? 
    ORDER BY t.created_at DESC
");
$stmt->bind_param("s", $username);
$stmt->execute();
$transactions = $stmt->get_result();
$transaction_rows = [];
while ($row = $transactions->fetch_assoc()) {
    $transaction_rows[] = $row;
}

// Calculate total sales for the user
$stmt = $con->prepare("SELECT SUM(amount) AS total_sales FROM transactions WHERE username = ? AND type = 'Sale'");
$stmt->bind_param("s", $username);
$stmt->execute();
$total_sales = $stmt->get_result()->fetch_assoc()['total_sales'] ?? 0;
$stmt->close();

// Calculate total expenses for the user
$stmt = $con->prepare("SELECT SUM(amount) AS total_expenses FROM transactions WHERE type='Expense' AND username=?");
$stmt->bind_param("s", $username);
$stmt->execute();
$total_expenses = $stmt->get_result()->fetch_assoc()['total_expenses'] ?? 0;
$stmt->close();

// Calculate total profit
$total_profit = $total_sales - $total_expenses;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>View Students - Campus Entrepreneurship Manager</title>
    <link href="style.css" rel="stylesheet">
    <link href="css/style-header.css" rel="stylesheet">
    <link href="css/style-body.css" rel="stylesheet">
    <style>
        h1, h2 {
            text-align: center;
        }
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
        .form-container input, .form-container textarea {
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
            overflow-x: auto;
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
        table img {
            max-width: 50px;
            height: auto;
        }
        table a {
            margin-right: 10px;
            text-decoration: none;
            color: #007bff;
        }
        table a:hover {
            text-decoration: underline;
        }
        .generate-report {
            text-align: center;
            margin-bottom: 20px;
        }
        .generate-report a {
            display: inline-block;
            background-color: #28a745;
            color: white;
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
        }
        .generate-report a:hover {
            background-color: #218838;
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

<!--Main Header-->
<nav class="navbar navbar-default">
    <div class="container">
        <!-- Brand and toggle get grouped for better mobile display -->
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1"
                    aria-expanded="false">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
        </div>
        <!-- Collect the nav links, forms, and other content for toggling -->
        <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
            <ul class="nav navbar-nav">
            <li>
                              <img src="../images/logo/logo-uptm.png" alt="UPTM Logo" style="max-width: 100px; height: auto">
              </li>
                <li>
                    <a href="../lecturers/lecturer_dashboard.php">Dashboard</a>
                </li>
                <li class="active">
                    <a href="../lecturers/students.php">Students</a>
                </li>
                <li>
                    <a href="../lecturers/lecturer_courses.php">Manage Courses</a>
                </li>
                <li>
                    <a href="../lecturers/user_profile.php">View Profile</a>
                </li>
                <li>
                    <a href="../lecturers/logout.php">Logout</a>
                </li>                                               
            </ul>
        </div>
        <!-- /.navbar-collapse -->
    </div>
    <!-- /.container-fluid -->
</nav>
<!--End Main Header -->
<br>
<h1>View Student: <?php echo htmlspecialchars($user['name']); ?></h1>
<br>
<!-- Generate Report Button -->
<div class="generate-report">
    <a href="generate_student_report_html.php?username=<?php echo urlencode($username); ?>" target="_blank">Generate Report</a>
</div>

<!-- Edit User Details -->
<div class="form-container">
    <h2>Edit Student Details</h2>
    <form action="view_user.php?username=<?php echo urlencode($username); ?>" method="POST">
        <input type="hidden" name="update_user" value="1">
        <label for="name">Name:</label>
        <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>

        <label for="email">Email:</label>
        <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>

        <label for="phone">Phone: +60</label>
        <input type="text" name="phone" id="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required>

        <button type="submit">Update Student</button>
    </form>
</div>

<!-- Business Information -->
<div class="table-container">
    <h2>Business Information</h2>
    <table>
        <thead>
            <tr>
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
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><?php echo $business_info ? htmlspecialchars($business_info['business_name']) : 'N/A'; ?></td>
                <td><?php echo $business_info ? htmlspecialchars($business_info['business_description']) : 'N/A'; ?></td>
                <td><?php echo $business_info ? htmlspecialchars($business_info['course_name']) : 'N/A'; ?></td>
                <td><?php echo $business_info ? htmlspecialchars($business_info['subject_code_name']) : 'N/A'; ?></td>
                <td><?php echo $business_info ? htmlspecialchars($business_info['study_level']) : 'N/A'; ?></td>
                <td><?php echo $business_info ? htmlspecialchars($business_info['class_section']) : 'N/A'; ?></td>
                <td>
                    <?php if ($business_info && $business_info['facebook_link']): ?>
                        <a href="<?php echo htmlspecialchars($business_info['facebook_link']); ?>" class="social-link" target="_blank">Facebook</a>
                    <?php else: ?>
                        N/A
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($business_info && $business_info['instagram_link']): ?>
                        <a href="<?php echo htmlspecialchars($business_info['instagram_link']); ?>" class="social-link" target="_blank">Instagram</a>
                    <?php else: ?>
                        N/A
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($business_info && $business_info['twitter_link']): ?>
                        <a href="<?php echo htmlspecialchars($business_info['twitter_link']); ?>" class="social-link" target="_blank">Twitter</a>
                    <?php else: ?>
                        N/A
                    <?php endif; ?>
                </td>
                <td><?php echo $business_info ? htmlspecialchars($business_info['business_category'] ?? 'N/A') : 'N/A'; ?></td>
            </tr>
        </tbody>
    </table>
</div>

<!-- User Transactions (Sales and Expenses) -->
<div class="table-container">
    <h2>Student Transactions (Total Sales: RM <?php echo number_format($total_sales, 2); ?>)</h2> 
    <br>
    <h2>Student Transactions (Total Expenses: RM <?php echo number_format($total_expenses, 2); ?>)</h2>
    <br>
    <h2>Student Transactions (Total Profit: RM <?php echo number_format($total_profit, 2); ?>)</h2>
    <br>
    <table id="transactionTable">
        <thead>
            <tr>
                <th>Transaction ID</th>
                <th>Type</th>
                <th>Product</th>
                <th>Quantity</th>
                <th>Amount (RM)</th>
                <th>Payment Method</th>
                <th>Date</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($transaction_rows as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                    <td><?php echo htmlspecialchars($row['type']); ?></td>
                    <td><?php echo $row['product_name'] ? htmlspecialchars($row['product_name']) : 'N/A'; ?></td>
                    <td><?php echo $row['quantity_sold'] !== NULL ? htmlspecialchars($row['quantity_sold']) : 'N/A'; ?></td>
                    <td><?php echo number_format($row['amount'], 2); ?></td>
                    <td><?php echo $row['payment_method'] ? htmlspecialchars($row['payment_method']) : 'N/A'; ?></td>
                    <td><?php echo htmlspecialchars($row['date']); ?></td>
                    <td><?php echo htmlspecialchars($row['description']); ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($transaction_rows)): ?>
                <tr>
                    <td colspan="8">No transactions recorded for this Student.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- User Products -->
<div class="table-container">
    <h2>Student Products</h2>
    <table id="productTable">
        <thead>
            <tr>
                <th>Image</th>
                <th>Name</th>
                <th>Quantity</th>
                <th>Price (RM)</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($product_rows as $row): ?>
                <tr>
                    <td>
                        <?php if (!empty($row['image'])): ?>
                            <img src="<?php echo htmlspecialchars($row['image']); ?>" alt="Product Image">
                        <?php else: ?>
                            No Image
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo htmlspecialchars($row['quantity']); ?></td>
                    <td><?php echo number_format($row['price'], 2); ?></td>
                    <td><?php echo htmlspecialchars($row['status']); ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($product_rows)): ?>
                <tr>
                    <td colspan="5">No products recorded for this Student.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- User Files -->
<div class="table-container">
    <h2>Student Files</h2>
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
                        <a href="<?php echo htmlspecialchars($row['file_path']); ?>" download="<?php echo htmlspecialchars($row['file_name']); ?>">Download</a>
                    </td>
                </tr>
            <?php endwhile; ?>
            <?php if ($files->num_rows == 0): ?>
                <tr>
                    <td colspan="2">No files uploaded by this Student.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Add Comment -->
<div class="form-container">
    <h2>Add Comment</h2>
    <form action="view_user.php?username=<?php echo urlencode($username); ?>" method="POST">
        <input type="hidden" name="add_comment" value="1">
        <label for="comment">Comment:</label>
        <textarea name="comment" id="comment" rows="5" required></textarea>

        <button type="submit">Add Comment</button>
    </form>
</div>

<script src="plugins/bootstrap.min.js"></script>
</body>
</html>