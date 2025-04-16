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

// Get the logged-in lecturer's username
$lecturer_username = $_SESSION['uname'];

// Verify the lecturer exists in the lecturers table
$stmt = $con->prepare("SELECT username FROM lecturers WHERE username = ?");
$stmt->bind_param("s", $lecturer_username);
$stmt->execute();
$lecturer_result = $stmt->get_result();
if ($lecturer_result->num_rows == 0) {
    // Lecturer not found, log them out
    session_destroy();
    header("Location: login.php");
    exit();
}
$stmt->close();

// Fetch the total number of users
$total_user = $con->query("SELECT COUNT(*) AS total FROM user")->fetch_assoc()['total'] ?? 0;

// Fetch the total number of courses for the lecturer
$stmt = $con->prepare("SELECT COUNT(*) AS total FROM lecturer_courses WHERE lecturer_username = ?");
$stmt->bind_param("s", $lecturer_username);
$stmt->execute();
$total_courses = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Fetch the lecturer's courses
$stmt = $con->prepare("SELECT course_name, subject_code_name FROM lecturer_courses WHERE lecturer_username = ?");
$stmt->bind_param("s", $lecturer_username);
$stmt->execute();
$lecturer_courses = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Lecturer Dashboard - Campus Entrepreneurship Manager</title>
    <link href="style.css" rel="stylesheet">
    <link href="css/style-header.css" rel="stylesheet">
    <link href="css/style-body.css" rel="stylesheet">
    <style>
        h1 {
            text-align: center;
        }
        .cards-container {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .card {
            background: #fff;
            border: 1px solid #ccc;
            border-radius: 5px;
            padding: 20px;
            margin: 10px;
            text-align: center;
            width: 200px;
        }
        .card a {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            margin-top: 10px;
        }
        .card a:hover {
            background-color: #0056b3;
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
                <li class="active">
                    <a href="../lecturers/lecturer_dashboard.php">Dashboard</a>
                </li>
                <li>
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
<h1>Lecturer Dashboard</h1>
<br>
<!-- Total Users and Courses Cards -->
<div class="cards-container">
    <div class="card">
        <h3>Total of Students</h3>
        <p><?php echo $total_user; ?></p>
    </div>
    <div class="card">
        <h3>Total of Courses</h3>
        <p><?php echo $total_courses; ?></p>
    </div>
</div>

<!-- Lecturer Courses Cards -->
<div class="cards-container">
    <?php while ($course = $lecturer_courses->fetch_assoc()): ?>
        <div class="card">
            <h3><?php echo htmlspecialchars($course['course_name']); ?></h3>
            <p><?php echo htmlspecialchars($course['subject_code_name']); ?></p>
            <a href="course_students.php?course=<?php echo urlencode($course['course_name']); ?>&subject=<?php echo urlencode($course['subject_code_name']); ?>">View Students</a>
        </div>
    <?php endwhile; ?>
</div>

<script src="plugins/bootstrap.min.js"></script>
</body>
</html>