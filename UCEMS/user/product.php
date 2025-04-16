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

// Handle Product Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_name = $con->real_escape_string($_POST['name']);
    $quantity = intval($_POST['quantity']);
    $price = floatval($_POST['price']);
    $status = $con->real_escape_string($_POST['status']);

    // Handle image upload
    $picture = "";
    if (!empty($_FILES['picture']['name'])) {
        $target_dir = "../user/images/products/";
        // Create the products directory if it doesn't exist
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        // Validate file type
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['picture']['type'];
        if (!in_array($file_type, $allowed_types)) {
            die("Error: Only JPEG, PNG, and GIF files are allowed.");
        }
        // Validate file size (e.g., 5MB limit)
        $max_size = 5 * 1024 * 1024; // 5MB
        if ($_FILES['picture']['size'] > $max_size) {
            die("Error: File size exceeds 5MB limit.");
        }
        // Use the original filename
        $original_filename = $_FILES["picture"]["name"];
        $picture = $target_dir . $original_filename;
        // Move the uploaded file to the products directory
        if (!move_uploaded_file($_FILES["picture"]["tmp_name"], $picture)) {
            die("Error: Failed to upload the image.");
        }
    }

    // Insert product into the database
    $stmt = $con->prepare("INSERT INTO products (username, image, name, quantity, price, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssiis", $username, $picture, $product_name, $quantity, $price, $status);
    $stmt->execute();
    $stmt->close();

    // Redirect to refresh the page and show the updated product list
    header("Location: product.php");
    exit();
}

// Fetch the user's products
$stmt = $con->prepare("SELECT * FROM products WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$products = $stmt->get_result();
$product_rows = [];
while ($row = $products->fetch_assoc()) {
    $product_rows[] = $row;
}
$total_products = count($product_rows);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Product Management - Campus Entrepreneurship Manager</title>
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
        .form-container input, .form-container select {
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
        table img {
            max-width: 50px;
            height: auto;
        }
        .low-stock {
            color: #dc3545;
            font-weight: bold;
        }
        .table-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .table-controls input[type="text"] {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            width: 200px;
        }
        .pagination {
            display: flex;
            gap: 5px;
        }
        .pagination button {
            padding: 5px 10px;
            border: 1px solid #ccc;
            background-color: #fff;
            cursor: pointer;
            border-radius: 4px;
        }
        .pagination button:disabled {
            background-color: #f0f0f0;
            cursor: not-allowed;
        }
        .pagination button:hover:not(:disabled) {
            background-color: #007bff;
            color: white;
        }
        .card {
            background: #fff;
            border: 1px solid #ccc;
            border-radius: 5px;
            padding: 20px;
            margin: 10px auto;
            text-align: center;
            width: 200px;
        }
        @media (max-width: 768px) {
            .table-controls {
                flex-direction: column;
                gap: 10px;
            }
            .table-controls input[type="text"] {
                width: 100%;
            }
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
                              <img src="images/logo-uptm.png" alt="UPTM Logo" style="max-width: 95px; height: auto">
                   </li>
                <li><a href="../user/dashboard.php">Dashboard</a></li>
                <li class="active"><a href="../user/product.php">Product Management</a></li>
                <li><a href="../user/upload.php">Upload</a></li>
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
<h2>Product Management</h2>

<!-- Total Products Card -->
<div class="card">
    <h3>Total Products</h3>
    <p><?php echo $total_products; ?></p>
</div>

<!-- Add New Product Form -->
<div class="form-container">
    <h2>Add New Product</h2>
    <form action="product.php" method="POST" enctype="multipart/form-data">
        <label for="picture">Picture:</label>
        <input type="file" name="picture" id="picture" accept="image/*">

        <label for="name">Product Name:</label>
        <input type="text" name="name" id="name" required>

        <label for="quantity">Quantity:</label>
        <input type="number" name="quantity" id="quantity" min="0" required>

        <label for="price">Price (RM):</label>
        <input type="number" name="price" id="price" step="0.01" min="0" required>

        <label for="status">Status:</label>
        <select name="status" id="status" required>
            <option value="Available">Available</option>
            <option value="Out of Stock">Out of Stock</option>
        </select>

        <button type="submit">Save Product</button>
    </form>
</div>

<!-- Product List -->
<div class="table-container">
    <h2>Product List</h2>
    <div class="table-controls">
        <div>
            <input type="text" id="productSearch" placeholder="Search products..." onkeyup="filterTable('productTable', 'productSearch')">
        </div>
        <div>
            <span id="productEntries">Showing 0 to 0 of 0 entries</span>
        </div>
    </div>
    <table id="productTable">
        <thead>
            <tr>
                <th>Image</th>
                <th>Name</th>
                <th>Quantity</th>
                <th>Price (RM)</th>
                <th>Status</th>
                <th>Actions</th>
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
                    <td class="<?php echo $row['quantity'] < 5 ? 'low-stock' : ''; ?>">
                        <?php echo htmlspecialchars($row['quantity']); ?>
                        <?php if ($row['quantity'] < 5): ?>
                            (Low Stock)
                        <?php endif; ?>
                    </td>
                    <td><?php echo number_format($row['price'], 2); ?></td>
                    <td><?php echo $row['status']; ?></td>
                    <td>
                        <a href="edit_product.php?id=<?php echo $row['id']; ?>">Edit</a> |
                        <a href="delete_product.php?id=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure you want to delete this product?')">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div class="table-controls">
        <div></div>
        <div class="pagination">
            <button onclick="firstPage('productTable')">First</button>
            <button onclick="previousPage('productTable')">Previous</button>
            <button onclick="nextPage('productTable')">Next</button>
            <button onclick="lastPage('productTable')">Last</button>
        </div>
    </div>
</div>

<script>
    // Table Search and Pagination (same as in dashboard.php)
    const rowsPerPage = 5;
    const tableStates = {};

    function initializeTable(tableId) {
        tableStates[tableId] = {
            currentPage: 1,
            searchText: ''
        };
        updateTable(tableId);
    }

    function filterTable(tableId, searchId) {
        const searchText = document.getElementById(searchId).value.toLowerCase();
        tableStates[tableId].searchText = searchText;
        tableStates[tableId].currentPage = 1;
        updateTable(tableId);
    }

    function updateTable(tableId) {
        const table = document.getElementById(tableId);
        const rows = Array.from(table.getElementsByTagName('tbody')[0].getElementsByTagName('tr'));
        const state = tableStates[tableId];
        const searchText = state.searchText.toLowerCase();

        // Filter rows based on search
        const filteredRows = rows.filter(row => {
            const cells = row.getElementsByTagName('td');
            for (let i = 0; i < cells.length - 1; i++) { // Exclude the last column (Action)
                if (cells[i].textContent.toLowerCase().includes(searchText)) {
                    return true;
                }
            }
            return false;
        });

        // Pagination
        const totalRows = filteredRows.length;
        const totalPages = Math.ceil(totalRows / rowsPerPage);
        state.currentPage = Math.min(state.currentPage, totalPages || 1);

        const start = (state.currentPage - 1) * rowsPerPage;
        const end = start + rowsPerPage;
        const paginatedRows = filteredRows.slice(start, end);

        // Show/hide rows
        rows.forEach(row => row.style.display = 'none');
        paginatedRows.forEach(row => row.style.display = '');

        // Update entries info
        const entriesInfo = document.getElementById(tableId === 'transactionTable' ? 'transactionEntries' : 'productEntries');
        const showingStart = totalRows === 0 ? 0 : start + 1;
        const showingEnd = Math.min(end, totalRows);
        entriesInfo.textContent = `Showing ${showingStart} to ${showingEnd} of ${totalRows} entries`;

        // Update pagination buttons
        const prevButton = document.querySelector(`#${tableId} + .table-controls .pagination button:nth-child(2)`);
        const nextButton = document.querySelector(`#${tableId} + .table-controls .pagination button:nth-child(3)`);
        prevButton.disabled = state.currentPage === 1;
        nextButton.disabled = state.currentPage === totalPages || totalPages === 0;
    }

    function firstPage(tableId) {
        tableStates[tableId].currentPage = 1;
        updateTable(tableId);
    }

    function previousPage(tableId) {
        if (tableStates[tableId].currentPage > 1) {
            tableStates[tableId].currentPage--;
            updateTable(tableId);
        }
    }

    function nextPage(tableId) {
        const table = document.getElementById(tableId);
        const rows = Array.from(table.getElementsByTagName('tbody')[0].getElementsByTagName('tr'));
        const filteredRows = rows.filter(row => {
            const cells = row.getElementsByTagName('td');
            for (let i = 0; i < cells.length - 1; i++) {
                if (cells[i].textContent.toLowerCase().includes(tableStates[tableId].searchText.toLowerCase())) {
                    return true;
                }
            }
            return false;
        });
        const totalPages = Math.ceil(filteredRows.length / rowsPerPage);
        if (tableStates[tableId].currentPage < totalPages) {
            tableStates[tableId].currentPage++;
            updateTable(tableId);
        }
    }

    function lastPage(tableId) {
        const table = document.getElementById(tableId);
        const rows = Array.from(table.getElementsByTagName('tbody')[0].getElementsByTagName('tr'));
        const filteredRows = rows.filter(row => {
            const cells = row.getElementsByTagName('td');
            for (let i = 0; i < cells.length - 1; i++) {
                if (cells[i].textContent.toLowerCase().includes(tableStates[tableId].searchText.toLowerCase())) {
                    return true;
                }
            }
            return false;
        });
        const totalPages = Math.ceil(filteredRows.length / rowsPerPage);
        tableStates[tableId].currentPage = totalPages || 1;
        updateTable(tableId);
    }

    // Initialize tables on page load
    document.addEventListener('DOMContentLoaded', () => {
        initializeTable('productTable');
    });
</script>

<script src="plugins/bootstrap.min.js"></script>
</body>
</html>