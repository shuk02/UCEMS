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

// Fetch user products
$stmt = $con->prepare("SELECT * FROM products WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$products = $stmt->get_result();

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

// Calculate total sales for the user
$stmt = $con->prepare("SELECT SUM(amount) AS total_sales FROM transactions WHERE username = ? AND type = 'Sale'");
$stmt->bind_param("s", $username);
$stmt->execute();
$total_sales = $stmt->get_result()->fetch_assoc()['total_sales'] ?? 0;

// Calculate total expenses for the user
$stmt = $con->prepare("SELECT SUM(amount) AS total_expenses FROM transactions WHERE type='Expense' AND username=?");
$stmt->bind_param("s", $username);
$stmt->execute();
$total_expenses = $stmt->get_result()->fetch_assoc()['total_expenses'] ?? 0;

// Calculate total profit
$total_profit = $total_sales - $total_expenses;

// Fetch comments for the user
$stmt = $con->prepare("SELECT lecturer_username, comment, created_at FROM comments WHERE username = ? ORDER BY created_at DESC");
$stmt->bind_param("s", $username);
$stmt->execute();
$comments = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Report - <?php echo htmlspecialchars($user['name']); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            margin: 0;
            padding: 20px;
            line-height: 1.6;
            box-sizing: border-box;
        }
        h1, h2 {
            text-align: center;
            width: 100%;
            margin: 10px 0;
        }
        h1 {
            color: #333;
        }
        h2 {
            color: #007bff;
            border-bottom: 2px solid #007bff;
            padding-bottom: 5px;
        }
        table {
            width: 100%;
            max-width: 1200px;
            border-collapse: collapse;
            margin: 20px auto;
            overflow-x: auto;
            display: block;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: center; /* Center table content */
        }
        th {
            background-color: #007bff;
            color: white;
        }
        .totals {
            text-align: center;
            margin: 20px 0;
            width: 100%;
        }
        .totals p {
            margin: 5px 0;
        }
        .instructions {
            background-color: #f8f9fa;
            padding: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            margin: 20px auto;
            text-align: center;
            max-width: 1200px;
            width: 90%;
        }
        @media print {
            .instructions {
                display: none;
            }
            body {
                margin: 0;
                padding: 10px;
            }
            table {
                width: 100%;
                max-width: 100%;
            }
        }
        @media (max-width: 768px) {
            table {
                width: 100%;
            }
            th, td {
                min-width: 80px;
            }
            .instructions {
                width: 95%;
            }
        }
    </style>
</head>
<body>
    <div class="instructions">
        <p><strong>Instructions:</strong> To save this report as a PDF, press <strong>Ctrl+P</strong> (or <strong>Cmd+P</strong> on Mac), then select "Save as PDF" in the print dialog.</p>
    </div>

    <h1>Student Report: <?php echo htmlspecialchars($user['name']); ?></h1>

    <!-- Student Details -->
    <h2>Student Details</h2>
    <table>
        <tr>
            <th>Name</th>
            <td><?php echo htmlspecialchars($user['name']); ?></td>
        </tr>
        <tr>
            <th>Email</th>
            <td><?php echo htmlspecialchars($user['email']); ?></td>
        </tr>
        <tr>
            <th>Phone</th>
            <td>+60<?php echo htmlspecialchars($user['phone']); ?></td>
        </tr>
    </table>

    <!-- Business Information -->
    <h2>Business Information</h2>
    <table>
        <tr>
            <th>Business Name</th>
            <td><?php echo $business_info ? htmlspecialchars($business_info['business_name']) : 'N/A'; ?></td>
        </tr>
        <tr>
            <th>Business Description</th>
            <td><?php echo $business_info ? htmlspecialchars($business_info['business_description']) : 'N/A'; ?></td>
        </tr>
        <tr>
            <th>Course Name</th>
            <td><?php echo $business_info ? htmlspecialchars($business_info['course_name']) : 'N/A'; ?></td>
        </tr>
        <tr>
            <th>Subject Code and Name</th>
            <td><?php echo $business_info ? htmlspecialchars($business_info['subject_code_name']) : 'N/A'; ?></td>
        </tr>
        <tr>
            <th>Study Level</th>
            <td><?php echo $business_info ? htmlspecialchars($business_info['study_level']) : 'N/A'; ?></td>
        </tr>
        <tr>
            <th>Class Section</th>
            <td><?php echo $business_info ? htmlspecialchars($business_info['class_section']) : 'N/A'; ?></td>
        </tr>
        <tr>
            <th>Facebook Link</th>
            <td><?php echo $business_info && $business_info['facebook_link'] ? htmlspecialchars($business_info['facebook_link']) : 'N/A'; ?></td>
        </tr>
        <tr>
            <th>Instagram Link</th>
            <td><?php echo $business_info && $business_info['instagram_link'] ? htmlspecialchars($business_info['instagram_link']) : 'N/A'; ?></td>
        </tr>
        <tr>
            <th>Twitter Link</th>
            <td><?php echo $business_info && $business_info['twitter_link'] ? htmlspecialchars($business_info['twitter_link']) : 'N/A'; ?></td>
        </tr>
        <tr>
            <th>Business Category</th>
            <td><?php echo $business_info ? htmlspecialchars($business_info['business_category'] ?? 'N/A') : 'N/A'; ?></td>
        </tr>
    </table>

    <!-- Transactions -->
    <h2>Transactions</h2>
    <div class="totals">
        <p><strong>Total Sales:</strong> RM <?php echo number_format($total_sales, 2); ?></p>
        <p><strong>Total Expenses:</strong> RM <?php echo number_format($total_expenses, 2); ?></p>
        <p><strong>Total Profit:</strong> RM <?php echo number_format($total_profit, 2); ?></p>
    </div>
    <table>
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
            <?php if ($transactions->num_rows > 0): ?>
                <?php while ($row = $transactions->fetch_assoc()): ?>
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
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8">No transactions recorded for this student.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Products -->
    <h2>Products</h2>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Quantity</th>
                <th>Price (RM)</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php $products->data_seek(0); ?>
            <?php while ($row = $products->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo htmlspecialchars($row['quantity']); ?></td>
                    <td><?php echo number_format($row['price'], 2); ?></td>
                    <td><?php echo htmlspecialchars($row['status']); ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <!-- Files -->
    <h2>Files</h2>
    <table>
        <thead>
            <tr>
                <th>File Name</th>
            </tr>
        </thead>
        <tbody>
            <?php $files->data_seek(0); ?>
            <?php while ($row = $files->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['file_name']); ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <!-- Comments -->
    <h2>Comments</h2>
    <table>
        <thead>
            <tr>
                <th>Lecturer</th>
                <th>Comment</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($comments->num_rows > 0): ?>
                <?php while ($row = $comments->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['lecturer_username']); ?></td>
                        <td><?php echo htmlspecialchars($row['comment']); ?></td>
                        <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3">No comments available for this student.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>