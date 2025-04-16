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

// Get the course and subject from the query parameters
$course_filter = isset($_GET['course']) ? $con->real_escape_string($_GET['course']) : null;
$subject_filter = isset($_GET['subject']) ? $con->real_escape_string($_GET['subject']) : null;

// Validate that course and subject are provided
if (!$course_filter || !$subject_filter) {
    header("Location: lecturer_dashboard.php");
    exit();
}

// Fetch the total number of students for this course and subject
$stmt = $con->prepare("SELECT COUNT(*) AS total 
                       FROM user u 
                       INNER JOIN business_info bi ON u.username = bi.username 
                       WHERE bi.course_name = ? AND bi.subject_code_name = ?");
$stmt->bind_param("ss", $course_filter, $subject_filter);
$stmt->execute();
$total_students = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Fetch students who take this course and subject
$query = "SELECT u.username, u.name, u.email, u.phone, u.userimage, bi.study_level, bi.class_section 
          FROM user u 
          INNER JOIN business_info bi ON u.username = bi.username 
          WHERE bi.course_name = ? AND bi.subject_code_name = ?";
$stmt = $con->prepare($query);
$stmt->bind_param("ss", $course_filter, $subject_filter);
$stmt->execute();
$students = $stmt->get_result();

// Fetch the number of files for each user
$user_files = [];
$file_stmt = $con->prepare("SELECT username, COUNT(*) AS file_count FROM files GROUP BY username");
$file_stmt->execute();
$files_result = $file_stmt->get_result();
while ($row = $files_result->fetch_assoc()) {
    $user_files[$row['username']] = $row['file_count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Students for <?php echo htmlspecialchars($course_filter . ' - ' . $subject_filter); ?> - Campus Entrepreneurship Manager</title>
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
            margin-bottom: 20px;
        }
        .card {
            background: #fff;
            border: 1px solid #ccc;
            border-radius: 5px;
            padding: 20px;
            margin: 0 10px;
            text-align: center;
            width: 200px;
        }
        .table-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            background: #fff;
            margin-bottom: 20px;
        }
        .search-container {
            margin-bottom: 20px;
            text-align: right;
        }
        .search-container input {
            padding: 8px;
            width: 300px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
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
        .pagination-container {
            margin-top: 20px;
            text-align: right;
        }
        .pagination-container button {
            padding: 8px 12px;
            margin: 0 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
            background-color: #f8f9fa;
            cursor: pointer;
        }
        .pagination-container button:disabled {
            background-color: #e9ecef;
            cursor: not-allowed;
        }
        .pagination-container .showing-entries {
            float: left;
            line-height: 36px;
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
<h1>Students for <?php echo htmlspecialchars($course_filter . ' - ' . $subject_filter); ?></h1>
<br>
<!-- Total Students Card -->
<div class="cards-container">
    <div class="card">
        <h3>Total Students</h3>
        <p><?php echo $total_students; ?></p>
    </div>
</div>

<!-- Student List -->
<div class="table-container">
    <h2>List of Students</h2>
    <div class="search-container">
        <input type="text" id="searchInput" placeholder="Search students..." onkeyup="searchTable()">
    </div>
    <table id="userTable">
        <thead>
            <tr>
                <th>Students Image</th>
                <th>Username</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Study Level</th>
                <th>Class Section</th>
                <th>Files</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $students->fetch_assoc()): ?>
                <tr>
                    <td>
                        <?php if (!empty($row['userimage'])): ?>
                            <img src="<?php echo htmlspecialchars($row['userimage']); ?>" alt="User Image">
                        <?php else: ?>
                            No Image
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td> +60<?php echo htmlspecialchars($row['phone']); ?></td>
                    <td><?php echo $row['study_level'] ? htmlspecialchars($row['study_level']) : 'N/A'; ?></td>
                    <td><?php echo $row['class_section'] ? htmlspecialchars($row['class_section']) : 'N/A'; ?></td>
                    <td><?php echo $user_files[$row['username']] ?? 0; ?></td>
                    <td>
                        <a href="view_user.php?username=<?php echo htmlspecialchars($row['username']); ?>">View</a> | 
                        <a href="delete_users.php?username=<?php echo htmlspecialchars($row['username']); ?>" onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <div class="pagination-container">
        <span class="showing-entries" id="showingEntries">Showing 0 to 0 of 0 entries</span>
        <button onclick="firstPage()">First</button>
        <button onclick="previousPage()">Previous</button>
        <button onclick="nextPage()">Next</button>
        <button onclick="lastPage()">Last</button>
    </div>
</div>

<script>
var currentPage = 1;
var rowsPerPage = 15;
var allRows = [];
var filteredRows = [];

document.addEventListener("DOMContentLoaded", function() {
    // Get all table rows and store them
    var table = document.getElementById("userTable");
    allRows = Array.from(table.getElementsByTagName("tbody")[0].getElementsByTagName("tr"));
    filteredRows = allRows.slice();
    updateTable();
});

function searchTable() {
    var input = document.getElementById("searchInput");
    var filter = input.value.toLowerCase();
    var table = document.getElementById("userTable");
    var tr = table.getElementsByTagName("tr");

    filteredRows = [];
    for (var i = 1; i < tr.length; i++) {
        var tdUsername = tr[i].getElementsByTagName("td")[1];
        var tdName = tr[i].getElementsByTagName("td")[2];
        var tdEmail = tr[i].getElementsByTagName("td")[3];
        var tdPhone = tr[i].getElementsByTagName("td")[4];
        var tdStudyLevel = tr[i].getElementsByTagName("td")[5];
        var tdSection = tr[i].getElementsByTagName("td")[6];

        if (tdUsername || tdName || tdEmail || tdPhone || tdStudyLevel || tdSection) {
            var username = tdUsername.textContent || tdUsername.innerText;
            var name = tdName.textContent || tdName.innerText;
            var email = tdEmail.textContent || tdEmail.innerText;
            var phone = tdPhone.textContent || tdPhone.innerText;
            var studyLevel = tdStudyLevel.textContent || tdStudyLevel.innerText;
            var section = tdSection.textContent || tdSection.innerText;

            if (username.toLowerCase().indexOf(filter) > -1 ||
                name.toLowerCase().indexOf(filter) > -1 ||
                email.toLowerCase().indexOf(filter) > -1 ||
                phone.toLowerCase().indexOf(filter) > -1 ||
                studyLevel.toLowerCase().indexOf(filter) > -1 ||
                section.toLowerCase().indexOf(filter) > -1) {
                filteredRows.push(tr[i]);
            }
        }
    }
    currentPage = 1; // Reset to first page after search
    updateTable();
}

function updateTable() {
    var startIndex = (currentPage - 1) * rowsPerPage;
    var endIndex = startIndex + rowsPerPage;
    var totalEntries = filteredRows.length;

    // Hide all rows
    allRows.forEach(row => row.style.display = "none");

    // Show only the rows for the current page
    for (var i = startIndex; i < endIndex && i < totalEntries; i++) {
        filteredRows[i].style.display = "";
    }

    // Update "Showing X to Y of Z entries"
    var showingStart = startIndex + 1;
    var showingEnd = Math.min(endIndex, totalEntries);
    document.getElementById("showingEntries").textContent = 
        "Showing " + showingStart + " to " + showingEnd + " of " + totalEntries + " entries";

    // Update pagination buttons
    document.querySelector("button[onclick='previousPage()']").disabled = currentPage === 1;
    document.querySelector("button[onclick='firstPage()']").disabled = currentPage === 1;
    document.querySelector("button[onclick='nextPage()']").disabled = endIndex >= totalEntries;
    document.querySelector("button[onclick='lastPage()']").disabled = endIndex >= totalEntries;
}

function firstPage() {
    currentPage = 1;
    updateTable();
}

function previousPage() {
    if (currentPage > 1) {
        currentPage--;
        updateTable();
    }
}

function nextPage() {
    var totalEntries = filteredRows.length;
    var totalPages = Math.ceil(totalEntries / rowsPerPage);
    if (currentPage < totalPages) {
        currentPage++;
        updateTable();
    }
}

function lastPage() {
    var totalEntries = filteredRows.length;
    currentPage = Math.ceil(totalEntries / rowsPerPage);
    updateTable();
}
</script>

<script src="plugins/bootstrap.min.js"></script>
</body>
</html>