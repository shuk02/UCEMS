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

// Handle Delete Action
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    // Ensure the record belongs to the lecturer
    $stmt = $con->prepare("DELETE FROM lecturer_courses WHERE id = ? AND lecturer_username = ?");
    $stmt->bind_param("is", $delete_id, $lecturer_username);
    $stmt->execute();
    $stmt->close();
    header("Location: lecturer_courses.php");
    exit();
}

// Handle Edit Action (fetch specific record to edit)
$edit_course = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $stmt = $con->prepare("SELECT * FROM lecturer_courses WHERE id = ? AND lecturer_username = ?");
    $stmt->bind_param("is", $edit_id, $lecturer_username);
    $stmt->execute();
    $edit_course = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $course_name = $con->real_escape_string($_POST['course_name']);
    $subject_code_name = $con->real_escape_string($_POST['subject_code_name']);

    if (isset($_POST['edit_id']) && is_numeric($_POST['edit_id'])) {
        // Update existing course
        $edit_id = intval($_POST['edit_id']);
        $stmt = $con->prepare("UPDATE lecturer_courses SET course_name = ?, subject_code_name = ? WHERE id = ? AND lecturer_username = ?");
        $stmt->bind_param("ssis", $course_name, $subject_code_name, $edit_id, $lecturer_username);
        if (!$stmt->execute()) {
            die("Error updating course: " . $stmt->error);
        }
    } else {
        // Insert new course
        $stmt = $con->prepare("INSERT INTO lecturer_courses (lecturer_username, course_name, subject_code_name) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $lecturer_username, $course_name, $subject_code_name);
        if (!$stmt->execute()) {
            die("Error inserting course: " . $stmt->error);
        }
    }
    $stmt->close();

    // Redirect to refresh the page
    header("Location: lecturer_courses.php");
    exit();
}

// Fetch all courses for the lecturer
$stmt = $con->prepare("SELECT * FROM lecturer_courses WHERE lecturer_username = ?");
$stmt->bind_param("s", $lecturer_username);
$stmt->execute();
$courses = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Manage Courses - Campus Entrepreneurship Manager</title>
    <link href="style.css" rel="stylesheet">
    <link href="css/style-header.css" rel="stylesheet">
    <link href="css/style-body.css" rel="stylesheet">
    <style>
        h1 {
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
        .form-container input {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
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
                <li>
                    <a href="../lecturers/students.php">Students</a>
                </li>
                <li class="active">
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
<h1>Manage Courses</h1>
<br>

<!-- Course Form -->
<div class="form-container">
    <h2><?php echo $edit_course ? 'Edit Course' : 'Add New Course'; ?></h2>
    <form action="lecturer_courses.php" method="POST">
        <?php if ($edit_course): ?>
            <input type="hidden" name="edit_id" value="<?php echo $edit_course['id']; ?>">
        <?php endif; ?>
        <label for="course_name">Course Name:</label>
        <input type="text" name="course_name" id="course_name" value="<?php echo $edit_course ? htmlspecialchars($edit_course['course_name']) : ''; ?>" required>

        <label for="subject_code_name">Subject Code and Name:</label>
        <input type="text" name="subject_code_name" id="subject_code_name" value="<?php echo $edit_course ? htmlspecialchars($edit_course['subject_code_name']) : ''; ?>" required>

        <button type="submit"><?php echo $edit_course ? 'Update Course' : 'Add Course'; ?></button>
    </form>
</div>

<!-- Course List -->
<div class="table-container">
    <h2>List of Courses</h2>
    <table>
        <thead>
            <tr>
                <th>Course Name</th>
                <th>Subject Code and Name</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($courses->num_rows > 0): ?>
                <?php while ($course = $courses->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                        <td><?php echo htmlspecialchars($course['subject_code_name']); ?></td>
                        <td>
                            <a href="lecturer_courses.php?edit=<?php echo $course['id']; ?>">Edit</a> |
                            <a href="lecturer_courses.php?delete=<?php echo $course['id']; ?>" onclick="return confirm('Are you sure you want to delete this course?')">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3">No courses found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script src="plugins/bootstrap.min.js"></script>
</body>
</html>