<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}
require_once __DIR__ . '/include/db.php';

// Get filter parameters from GET; default branch "Walmart East" and default date is today
$branch = isset($_GET['branch']) ? $_GET['branch'] : 'Walmart East';
$date   = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Choose the correct PDO connection based on the branch
$pdo_branch = ($branch === 'Walmart West') ? $pdo_west : $pdo_east;

// Fetch sales for the selected branch and date using a LEFT JOIN to get product name
try {
    $stmt = $pdo_branch->prepare("SELECT s.*, p.name AS product_name FROM sales s LEFT JOIN products p ON s.product_id = p.id WHERE DATE(sale_date) = ?");
    $stmt->execute([$date]);
    $sales = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error fetching sales: " . $e->getMessage());
}

// Calculate the grand total for the day
$grandTotal = 0;
foreach ($sales as $sale) {
    $grandTotal += $sale['total_bill'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales Summary</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
        }
        .container {
            display: flex;
        }
        .main-content {
            flex: 1;
            padding: 20px;
        }
        .table-box {
            background-color: #fff;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .table-box h2 {
            margin-top: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .total-row {
            font-weight: bold;
        }
        form.filter-form {
            margin-bottom: 20px;
        }
        form.filter-form label {
            font-weight: bold;
            margin-right: 10px;
        }
        form.filter-form select,
        form.filter-form input[type="date"] {
            padding: 5px;
            margin-right: 10px;
        }
        form.filter-form button {
            padding: 5px 10px;
            background-color: #5cb85c;
            border: none;
            color: #fff;
            border-radius: 4px;
            cursor: pointer;
        }
        form.filter-form button:hover {
            background-color: #4cae4c;
        }
    </style>
</head>
<body>
    <?php include 'include/navbar.php'; ?>
    <div class="container">
        <?php include 'include/sidebar.php'; ?>
        <div class="main-content">
            <!-- Filter Form -->
            <form method="get" action="show_sales.php" class="filter-form">
                <label for="branch">Branch:</label>
                <select id="branch" name="branch">
                    <option value="Walmart East" <?php if($branch=="Walmart East") echo "selected"; ?>>Walmart East</option>
                    <option value="Walmart West" <?php if($branch=="Walmart West") echo "selected"; ?>>Walmart West</option>
                </select>
                <label for="date">Date:</label>
                <input type="date" id="date" name="date" value="<?php echo $date; ?>">
                <button type="submit">Filter</button>
            </form>

            <div class="table-box">
                <h2>Sales List for <?php echo htmlspecialchars($branch); ?> on <?php echo htmlspecialchars($date); ?></h2>
                <?php if (!empty($sales)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Customer Name</th>
                                <th>Phone Number</th>
                                <th>Product Name</th>
                                <th>Quantity</th>
                                <th>Sell Price</th>
                                <th>Total Bill</th>
                                <th>Sale Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sales as $sale): ?>
                                <tr>
                                    <td><?php echo $sale['id']; ?></td>
                                    <td><?php echo htmlspecialchars($sale['customer_name']); ?></td>
                                    <td><?php echo htmlspecialchars($sale['phone_number']); ?></td>
                                    <td><?php echo htmlspecialchars($sale['product_name']); ?></td>
                                    <td><?php echo htmlspecialchars($sale['quantity']); ?></td>
                                    <td><?php echo number_format($sale['total_bill'] / $sale['quantity'], 2); ?></td>
                                    <td><?php echo number_format($sale['total_bill'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($sale['sale_date']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="total-row">
                                <td colspan="7">Grand Total for <?php echo htmlspecialchars($date); ?>:</td>
                                <td><?php echo number_format($grandTotal, 2); ?></td>
                            </tr>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No sales recorded for <?php echo htmlspecialchars($branch); ?> on <?php echo htmlspecialchars($date); ?>.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
