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

// Fetch all products for the user (to display in the sales form)
$stmt = $con->prepare("SELECT id, name, quantity FROM products WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$products = $stmt->get_result();

// Handle Sales and Expenses Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $type = $con->real_escape_string($_POST['type']);
    $amount = floatval($_POST['amount']);
    $description = $con->real_escape_string($_POST['description']);
    $payment_method = ($type === 'Sale' && isset($_POST['payment_method'])) ? $con->real_escape_string($_POST['payment_method']) : NULL;
    $product_id = ($type === 'Sale' && isset($_POST['product_id'])) ? intval($_POST['product_id']) : NULL;
    $quantity_sold = ($type === 'Sale' && isset($_POST['quantity_sold'])) ? intval($_POST['quantity_sold']) : NULL;

    // If it's a sale, handle product quantity
    if ($type === 'Sale' && $product_id && $quantity_sold) {
        // Fetch the product's current quantity and price
        $stmt = $con->prepare("SELECT quantity, price FROM products WHERE id = ? AND username = ?");
        $stmt->bind_param("is", $product_id, $username);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($product) {
            $current_quantity = $product['quantity'];
            $product_price = $product['price'];

            // Validate quantity
            if ($quantity_sold <= 0) {
                die("Error: Quantity sold must be greater than 0.");
            }
            if ($quantity_sold > $current_quantity) {
                die("Error: Quantity sold ($quantity_sold) exceeds available stock ($current_quantity).");
            }

            // Update product quantity
            $new_quantity = $current_quantity - $quantity_sold;
            $stmt = $con->prepare("UPDATE products SET quantity = ? WHERE id = ? AND username = ?");
            $stmt->bind_param("iis", $new_quantity, $product_id, $username);
            $stmt->execute();
            $stmt->close();

            // Calculate amount based on quantity sold and product price
            $amount = $quantity_sold * $product_price;
        } else {
            die("Error: Invalid product selected.");
        }
    }

    // Insert the transaction
    $stmt = $con->prepare("INSERT INTO transactions (username, type, amount, description, payment_method, product_id, quantity_sold) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdssii", $username, $type, $amount, $description, $payment_method, $product_id, $quantity_sold);
    $stmt->execute();
    $stmt->close();

    header("Location: dashboard.php");
    exit();
}

// Handle Month and Year Filtering
$selected_month = isset($_GET['month']) ? intval($_GET['month']) : date('m');
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Build date filters
$today = date('Y-m-d');
$thisMonth = "$selected_year-" . str_pad($selected_month, 2, "0", STR_PAD_LEFT);
$thisMonthPattern = "$thisMonth%";

// Sales Today (only if the selected month and year match the current date)
$salesToday = 0;
if ($selected_year == date('Y') && $selected_month == date('m')) {
    $stmt = $con->prepare("SELECT SUM(amount) AS total FROM transactions WHERE type='Sale' AND date=? AND username=?");
    $stmt->bind_param("ss", $today, $username);
    $stmt->execute();
    $salesToday = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    $stmt->close();
}

// Sales This Month (for the selected month and year)
$stmt = $con->prepare("SELECT SUM(amount) AS total FROM transactions WHERE type='Sale' AND date LIKE ? AND username=?");
$stmt->bind_param("ss", $thisMonthPattern, $username);
$stmt->execute();
$salesThisMonth = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Total Sales (for the selected year, or all time if no year selected)
if ($selected_year) {
    $yearPattern = "$selected_year%";
    $stmt = $con->prepare("SELECT SUM(amount) AS total FROM transactions WHERE type='Sale' AND date LIKE ? AND username=?");
    $stmt->bind_param("ss", $yearPattern, $username);
} else {
    $stmt = $con->prepare("SELECT SUM(amount) AS total FROM transactions WHERE type='Sale' AND username=?");
    $stmt->bind_param("s", $username);
}
$stmt->execute();
$totalSales = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Total Expenses (for the selected year, or all time if no year selected)
if ($selected_year) {
    $stmt = $con->prepare("SELECT SUM(amount) AS total FROM transactions WHERE type='Expense' AND date LIKE ? AND username=?");
    $stmt->bind_param("ss", $yearPattern, $username);
} else {
    $stmt = $con->prepare("SELECT SUM(amount) AS total FROM transactions WHERE type='Expense' AND username=?");
    $stmt->bind_param("s", $username);
}
$stmt->execute();
$totalExpenses = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Calculate Total Profit
$totalProfit = $totalSales - $totalExpenses;

// Fetch Transactions with Product Name (filtered by selected month and year)
if ($selected_year && $selected_month) {
    $stmt = $con->prepare("
        SELECT t.*, p.name AS product_name 
        FROM transactions t 
        LEFT JOIN products p ON t.product_id = p.id 
        WHERE t.username = ? AND t.date LIKE ? 
        ORDER BY t.created_at DESC
    ");
    $stmt->bind_param("ss", $username, $thisMonthPattern);
} else {
    $stmt = $con->prepare("
        SELECT t.*, p.name AS product_name 
        FROM transactions t 
        LEFT JOIN products p ON t.product_id = p.id 
        WHERE t.username = ? 
        ORDER BY t.created_at DESC
    ");
    $stmt->bind_param("s", $username);
}
$stmt->execute();
$transactions = $stmt->get_result();
$transaction_rows = [];
while ($row = $transactions->fetch_assoc()) {
    $transaction_rows[] = $row;
}

// Fetch Lecturer Comments
$stmt = $con->prepare("SELECT * FROM comments WHERE username = ? ORDER BY created_at DESC");
$stmt->bind_param("s", $username);
$stmt->execute();
$comments = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Dashboard - Campus Entrepreneurship Manager</title>
    <link href="style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="css/style-header.css" rel="stylesheet">
    <link href="css/style-body.css" rel="stylesheet">
    <style>
        h2, h3, p {
            text-align: center;
        }
        .cards-container {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
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
        .grid-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .section {
            background: #fff;
            border: 1fr solid #ccc;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table th, table td {
            padding: 10px;
            text-align: left;
            border: 1fr solid #ccc;
        }
        table th {
            background-color: #007bff;
            color: white;
        }
        table a {
            color: #dc3545;
            text-decoration: none;
        }
        table a:hover {
            text-decoration: underline;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 8px;
            border: 1fr solid #ccc;
            border-radius: 4px;
        }
        .btn-success {
            background-color: #28a745;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-success:hover {
            background-color: #218838;
        }
        .comment {
            border-bottom: 1fr solid #ccc;
            padding: 10px 0;
        }
        .comment p {
            margin: 5px 0;
            text-align: left;
        }
        .filter-form {
            text-align: center;
            margin-bottom: 20px;
        }
        .filter-form form {
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        .filter-form select {
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
            background-color: #fff;
            font-size: 14px;
            cursor: pointer;
            min-width: 120px;
        }
        .filter-form select:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.3);
        }
        .filter-form button {
            background-color: #007bff;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        .filter-form button:hover {
            background-color: #0056b3;
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
        @media (max-width: 768px) {
            .grid-container {
                grid-template-columns: 1fr;
            }
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
                              <img src="images/logo-uptm.png" alt="UPTM Logo" style="max-width: 100px; height: auto">
                   </li>
                <li class="active"><a href="../user/dashboard.php">Dashboard</a></li>
                <li><a href="../user/product.php">Product Management</a></li>
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
<h2>Campus Entrepreneurship Manager Dashboard</h2>
<br>
<p>Welcome, <?php echo htmlspecialchars($display_username); ?>!</p>
<br>

<!-- Month and Year Filter -->
<div class="filter-form">
    <form method="GET" action="dashboard.php">
        <select name="month" id="month">
            <option value="">Month</option>
            <?php for ($m = 1; $m <= 12; $m++): ?>
                <option value="<?php echo $m; ?>" <?php echo $selected_month == $m ? 'selected' : ''; ?>>
                    <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                </option>
            <?php endfor; ?>
        </select>

        <select name="year" id="year">
            <option value="">Year</option>
            <?php
            $current_year = date('Y');
            for ($y = $current_year; $y >= $current_year - 5; $y--):
            ?>
                <option value="<?php echo $y; ?>" <?php echo $selected_year == $y ? 'selected' : ''; ?>>
                    <?php echo $y; ?>
                </option>
            <?php endfor; ?>
        </select>

        <button type="submit">Filter</button>
    </form>
</div>

<!-- Earnings Overview -->
<div class="cards-container">
    <div class="card">
        <h3>Today's Earnings</h3>
        <p>RM <?php echo number_format($salesToday, 2); ?></p>
    </div>
    <div class="card">
        <h3>This Month's Earnings</h3>
        <p>RM <?php echo number_format($salesThisMonth, 2); ?></p>
    </div>
    <div class="card">
        <h3>Total Earnings</h3>
        <p>RM <?php echo number_format($totalSales, 2); ?></p>
    </div>
    <div class="card">
        <h3>Total Expenses</h3>
        <p>RM <?php echo number_format($totalExpenses, 2); ?></p>
    </div>
    <div class="card">
        <h3>Total Profit</h3>
        <p>RM <?php echo number_format($totalProfit, 2); ?></p>
    </div>
</div>

<!-- Main Content Grid -->
<div class="grid-container">
    <!-- Grid Item 1: Pie Chart -->
    <div class="section">
        <h3>Sales vs Expenses</h3>
        <canvas id="pieChart"></canvas>
    </div>

    <!-- Grid Item 2: Add Sales/Expenses -->
    <div class="section">
        <h3>Add Sales/Expenses</h3>
        <form action="dashboard.php" method="POST">
            <div class="form-group">
                <label>Type:</label>
                <select name="type" id="transactionType" required onchange="toggleSaleFields()">
                    <option value="Sale">Sale</option>
                    <option value="Expense">Expense</option>
                </select>
            </div>
            <div class="form-group sale-field">
                <label>Product:</label>
                <select name="product_id" id="productId">
                    <option value="">Select a product</option>
                    <?php while ($product = $products->fetch_assoc()): ?>
                        <option value="<?php echo $product['id']; ?>" data-quantity="<?php echo $product['quantity']; ?>">
                            <?php echo htmlspecialchars($product['name']); ?> (Stock: <?php echo $product['quantity']; ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group sale-field">
                <label>Quantity Sold:</label>
                <input type="number" name="quantity_sold" id="quantitySold" min="1">
            </div>
            <div class="form-group sale-field">
                <label>Payment Method:</label>
                <select name="payment_method" id="paymentMethod">
                    <option value="Cash">Cash</option>
                    <option value="QR Payment">QR Payment</option>
                    <option value="Online Transfer">Online Transfer</option>
                    <option value="E-Wallet">E-Wallet</option>
                </select>
            </div>
            <div class="form-group expense-field">
                <label>Amount (RM):</label>
                <input type="number" name="amount" id="amount" step="0.01">
            </div>
            <div class="form-group">
                <label>Description:</label>
                <input type="text" name="description">
            </div>
            <button class="btn btn-success" type="submit">Submit</button>
        </form>
    </div>

    <!-- Grid Item 3: Transaction History -->
    <div class="section">
        <h3>Transaction History</h3>
        <div class="table-controls">
            <div>
                <input type="text" id="transactionSearch" placeholder="Search transactions..." onkeyup="filterTable('transactionTable', 'transactionSearch')">
            </div>
            <div>
                <span id="transactionEntries">Showing 0 to 0 of 0 entries</span>
            </div>
        </div>
        <table id="transactionTable">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Amount (RM)</th>
                    <th>Payment Method</th>
                    <th>Description</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transaction_rows as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['type']); ?></td>
                        <td><?php echo $row['product_name'] ? htmlspecialchars($row['product_name']) : 'N/A'; ?></td>
                        <td><?php echo $row['quantity_sold'] !== NULL ? htmlspecialchars($row['quantity_sold']) : 'N/A'; ?></td>
                        <td><?php echo number_format($row['amount'], 2); ?></td>
                        <td><?php echo $row['payment_method'] ? htmlspecialchars($row['payment_method']) : 'N/A'; ?></td>
                        <td><?php echo htmlspecialchars($row['description']); ?></td>
                        <td><?php echo htmlspecialchars($row['date']); ?></td>
                        <td>
                            <a href="delete_transaction.php?id=<?php echo htmlspecialchars($row['id']); ?>" onclick="return confirm('Are you sure you want to delete this transaction?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="table-controls">
            <div></div>
            <div class="pagination">
                <button onclick="firstPage('transactionTable')">First</button>
                <button onclick="previousPage('transactionTable')">Previous</button>
                <button onclick="nextPage('transactionTable')">Next</button>
                <button onclick="lastPage('transactionTable')">Last</button>
            </div>
        </div>
    </div>

    <!-- Grid Item 4: Lecturer Comments -->
    <div class="section">
        <h3>Lecturer Comments</h3>
        <?php if ($comments->num_rows > 0): ?>
            <?php while ($comment = $comments->fetch_assoc()): ?>
                <div class="comment">
                    <p><strong>From Lecturer: <?php echo htmlspecialchars($comment['lecturer_username']); ?></strong></p>
                    <p><strong>Comments Lecturer: <?php echo htmlspecialchars($comment['comment']); ?></strong></p>
                    <p><em><?php echo htmlspecialchars($comment['created_at']); ?></em></p>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No comments yet.</p>
        <?php endif; ?>
    </div>
</div>

<script>
    // Toggle sale-specific fields based on transaction type
    function toggleSaleFields() {
        const type = document.getElementById('transactionType').value;
        const saleFields = document.getElementsByClassName('sale-field');
        const expenseFields = document.getElementsByClassName('expense-field');
        if (type === 'Sale') {
            for (let field of saleFields) {
                field.style.display = 'block';
            }
            for (let field of expenseFields) {
                field.style.display = 'none';
            }
            document.getElementById('amount').removeAttribute('required');
            document.getElementById('productId').setAttribute('required', 'required');
            document.getElementById('quantitySold').setAttribute('required', 'required');
            document.getElementById('paymentMethod').setAttribute('required', 'required');
        } else {
            for (let field of saleFields) {
                field.style.display = 'none';
            }
            for (let field of expenseFields) {
                field.style.display = 'block';
            }
            document.getElementById('amount').setAttribute('required', 'required');
            document.getElementById('productId').removeAttribute('required');
            document.getElementById('quantitySold').removeAttribute('required');
            document.getElementById('paymentMethod').removeAttribute('required');
        }
    }

    // Initialize form on page load
    document.addEventListener('DOMContentLoaded', toggleSaleFields);

    // Pie Chart
    new Chart(document.getElementById('pieChart'), {
        type: 'pie',
        data: {
            labels: ['Sales', 'Expenses'],
            datasets: [{
                data: [<?php echo $totalSales; ?>, <?php echo $totalExpenses; ?>],
                backgroundColor: ['#4CAF50', '#FF5733']
            }]
        }
    });

    // Table Search and Pagination
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
        initializeTable('transactionTable');
    });
</script>

<script src="plugins/bootstrap.min.js"></script>
</body>
</html>