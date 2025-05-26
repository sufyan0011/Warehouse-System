<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/include/db.php'; // Must set $pdo_east and $pdo_west

// Fetch products from Walmart East
try {
    $stmt = $pdo_east->query("SELECT * FROM products");
    $products_east = $stmt->fetchAll();
} catch (PDOException $e) {
    $products_east = [];
}

// Fetch products from Walmart West
try {
    $stmt = $pdo_west->query("SELECT * FROM products");
    $products_west = $stmt->fetchAll();
} catch (PDOException $e) {
    $products_west = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Show Products</title>
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
        .edit-btn, .delete-btn {
            background-color: #007bff;
            color: #fff;
            padding: 5px 10px;
            text-decoration: none;
            border-radius: 4px;
            margin-right: 5px;
        }
        .edit-btn:hover, .delete-btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <?php include 'include/navbar.php'; ?>
    <div class="container">
        <?php include 'include/sidebar.php'; ?>
        <div class="main-content">
            <div class="table-box">
                <h2>Products List (Walmart East)</h2>
                <?php if (!empty($products_east)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Product Name</th>
                                <th>Category ID</th>
                                <th>Buy Price</th>
                                <th>Sell Price</th>
                                <th>Quantity</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products_east as $prod): ?>
                                <tr>
                                    <td><?php echo $prod['id']; ?></td>
                                    <td><?php echo htmlspecialchars($prod['name']); ?></td>
                                    <td><?php echo htmlspecialchars($prod['category_id']); ?></td>
                                    <td><?php echo htmlspecialchars($prod['buy_price']); ?></td>
                                    <td><?php echo htmlspecialchars($prod['sell_price']); ?></td>
                                    <td><?php echo htmlspecialchars($prod['total_quantity']); ?></td>
                                    <td>
                                        <a class="edit-btn" href="edit_product.php?id=<?php echo $prod['id']; ?>&branch=Walmart%20East">Edit</a>
                                        <a class="delete-btn" href="delete_product.php?id=<?php echo $prod['id']; ?>&branch=Walmart%20East">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No products found for Walmart East.</p>
                <?php endif; ?>
            </div>

            <div class="table-box">
                <h2>Products List (Walmart West)</h2>
                <?php if (!empty($products_west)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Product Name</th>
                                <th>Category ID</th>
                                <th>Buy Price</th>
                                <th>Sell Price</th>
                                <th>Quantity</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products_west as $prod): ?>
                                <tr>
                                    <td><?php echo $prod['id']; ?></td>
                                    <td><?php echo htmlspecialchars($prod['name']); ?></td>
                                    <td><?php echo htmlspecialchars($prod['category_id']); ?></td>
                                    <td><?php echo htmlspecialchars($prod['buy_price']); ?></td>
                                    <td><?php echo htmlspecialchars($prod['sell_price']); ?></td>
                                    <td><?php echo htmlspecialchars($prod['total_quantity']); ?></td>
                                    <td>
                                        <a class="edit-btn" href="edit_product.php?id=<?php echo $prod['id']; ?>&branch=Walmart%20West">Edit</a>
                                        <a class="delete-btn" href="delete_product.php?id=<?php echo $prod['id']; ?>&branch=Walmart%20West">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No products found for Walmart West.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
