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
$stmt = $con->prepare("SELECT business_name, business_description, course_name, subject_code_name, study_level, class_section 
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

// Fetch user transactions (sales and expenses)
$stmt = $con->prepare("SELECT id, type, amount, date, description, created_at FROM transactions WHERE username = ? ORDER BY created_at DESC");
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

// Fetch comments for the user
$stmt = $con->prepare("SELECT lecturer_username, comment, created_at FROM comments WHERE username = ? ORDER BY created_at DESC");
$stmt->bind_param("s", $username);
$stmt->execute();
$comments = $stmt->get_result();
$stmt->close();

// Build the document content
$content = "Student Report: " . $user['name'] . "\n";
$content .= "========================================\n\n";

// Student Details
$content .= "Student Details\n";
$content .= "---------------\n";
$content .= "Name: " . $user['name'] . "\n";
$content .= "Email: " . $user['email'] . "\n";
$content .= "Phone: +60" . $user['phone'] . "\n\n";

// Business Information
$content .= "Business Information\n";
$content .= "--------------------\n";
$content .= "Business Name: " . ($business_info ? $business_info['business_name'] : 'N/A') . "\n";
$content .= "Business Description: " . ($business_info ? $business_info['business_description'] : 'N/A') . "\n";
$content .= "Course Name: " . ($business_info ? $business_info['course_name'] : 'N/A') . "\n";
$content .= "Subject Code and Name: " . ($business_info ? $business_info['subject_code_name'] : 'N/A') . "\n";
$content .= "Study Level: " . ($business_info ? $business_info['study_level'] : 'N/A') . "\n";
$content .= "Class Section: " . ($business_info ? $business_info['class_section'] : 'N/A') . "\n\n";

// Transactions
$content .= "Transactions\n";
$content .= "------------\n";
$content .= "Total Sales: RM " . number_format($total_sales, 2) . "\n";
$content .= "Total Expenses: RM " . number_format($total_expenses, 2) . "\n\n";
if ($transactions->num_rows > 0) {
    $content .= "Transaction ID | Type | Amount (RM) | Date | Description\n";
    $content .= "-------------------------------------------------------\n";
    while ($row = $transactions->fetch_assoc()) {
        $content .= $row['id'] . " | " . $row['type'] . " | " . number_format($row['amount'], 2) . " | " . $row['date'] . " | " . $row['description'] . "\n";
    }
} else {
    $content .= "No transactions recorded for this student.\n";
}
$content .= "\n";

// Products
$content .= "Products\n";
$content .= "--------\n";
if ($products->num_rows > 0) {
    $content .= "Name | Quantity | Price (RM) | Status\n";
    $content .= "-------------------------------------\n";
    $products->data_seek(0);
    while ($row = $products->fetch_assoc()) {
        $content .= $row['name'] . " | " . $row['quantity'] . " | " . number_format($row['price'], 2) . " | " . $row['status'] . "\n";
    }
} else {
    $content .= "No products recorded for this student.\n";
}
$content .= "\n";

// Files
$content .= "Files\n";
$content .= "-----\n";
if ($files->num_rows > 0) {
    $content .= "File Name\n";
    $content .= "---------\n";
    $files->data_seek(0);
    while ($row = $files->fetch_assoc()) {
        $content .= $row['file_name'] . "\n";
    }
} else {
    $content .= "No files recorded for this student.\n";
}
$content .= "\n";

// Comments
$content .= "Comments\n";
$content .= "--------\n";
if ($comments->num_rows > 0) {
    $content .= "Lecturer | Comment | Date\n";
    $content .= "--------------------------\n";
    while ($row = $comments->fetch_assoc()) {
        $content .= $row['lecturer_username'] . " | " . $row['comment'] . " | " . $row['created_at'] . "\n";
    }
} else {
    $content .= "No comments available for this student.\n";
}

// Set headers to force download
header('Content-Type: application/msword');
header('Content-Disposition: attachment; filename="Student_Report_' . $username . '.doc"');
header('Content-Length: ' . strlen($content));

// Output the content
echo $content;
exit();
?>