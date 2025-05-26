<?php
session_start();
require_once __DIR__ . '/include/db.php';  // This file should set $pdo_east and $pdo_west

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

$error = $success = "";

// Process product form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_product'])) {
    $product_name     = trim($_POST['product_name']);
    $branch           = trim($_POST['branch']);
    $category_id      = trim($_POST['product_category']); // Expecting a category ID
    $buy_price        = trim($_POST['buy_price']);
    $sell_price       = trim($_POST['sell_price']);
    $total_quantity   = trim($_POST['total_quantity']);

    if (empty($product_name) || empty($branch) || empty($category_id) || empty($buy_price) || empty($sell_price) || empty($total_quantity)) {
        $error = "All fields are required.";
    } else {
        if ($branch === "Walmart East") {
            $stmt = $pdo_east->prepare("INSERT INTO products (name, branch, category_id, buy_price, sell_price, total_quantity) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$product_name, $branch, $category_id, $buy_price, $sell_price, $total_quantity]);
        } else {
            $stmt = $pdo_west->prepare("INSERT INTO products (name, branch, category_id, buy_price, sell_price, total_quantity) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$product_name, $branch, $category_id, $buy_price, $sell_price, $total_quantity]);
        }
        $success = "Product added successfully.";
    }
}

// Fetch categories from both databases
$stmt = $pdo_east->query("SELECT id, name FROM categories");
$categories_east = $stmt->fetchAll();

$stmt = $pdo_west->query("SELECT id, name FROM categories");
$categories_west = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Product</title>
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
        .form-box {
            background-color: #fff;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            max-width: 500px;
            margin: 0 auto;
        }
        .form-box h2 {
            margin-top: 0;
        }
        .form-box label {
            font-weight: bold;
        }
        .form-box input[type="text"],
        .form-box input[type="number"],
        .form-box select {
            width: 100%;
            padding: 10px;
            margin: 10px 0 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .form-box button {
            padding: 10px 20px;
            background-color: #5cb85c;
            border: none;
            color: #fff;
            border-radius: 4px;
            cursor: pointer;
        }
        .form-box button:hover {
            background-color: #4cae4c;
        }
        .error {
            color: red;
        }
        .message {
            color: green;
        }
    </style>
    <script>
        // Populate category dropdown based on branch selection using data from the databases
        var categoriesEast = <?php echo json_encode($categories_east); ?>;
        var categoriesWest = <?php echo json_encode($categories_west); ?>;
        
        document.addEventListener("DOMContentLoaded", function() {
            var branchSelect = document.getElementById('branch');
            var categorySelect = document.getElementById('product_category');
            
            function updateCategories() {
                var branch = branchSelect.value;
                categorySelect.innerHTML = "";
                var categories = [];
                if (branch === "Walmart East") {
                    categories = categoriesEast;
                } else if (branch === "Walmart West") {
                    categories = categoriesWest;
                }
                // Add a placeholder option
                var placeholder = document.createElement("option");
                placeholder.value = "";
                placeholder.text = "Select Category";
                categorySelect.appendChild(placeholder);
                
                categories.forEach(function(cat) {
                    var option = document.createElement("option");
                    option.value = cat.id;  // Use category ID as the value
                    option.text = cat.name;
                    categorySelect.appendChild(option);
                });
            }
            
            branchSelect.addEventListener("change", updateCategories);
            updateCategories(); // initial population
        });
    </script>
</head>
<body>
    <?php include 'include/navbar.php'; ?>
    <div class="container">
        <?php include 'include/sidebar.php'; ?>
        <div class="main-content">
            <div class="form-box">
                <h2>Add New Product</h2>
                <?php 
                    if (!empty($error)) { echo '<p class="error">' . $error . '</p>'; }
                    if (!empty($success)) { echo '<p class="message">' . $success . '</p>'; }
                ?>
                <form action="add_product.php" method="post">
                    <label for="product_name">Product Name:</label>
                    <input type="text" id="product_name" name="product_name" required>
                    
                    <label for="branch">Branch:</label>
                    <select id="branch" name="branch" required>
                        <option value="Walmart East">Walmart East</option>
                        <option value="Walmart West">Walmart West</option>
                    </select>
                    
                    <label for="product_category">Product Category:</label>
                    <select id="product_category" name="product_category" required>
                        <option value="">Select Category</option>
                        <!-- Options will be populated dynamically -->
                    </select>
                    
                    <label for="buy_price">Buy Price:</label>
                    <input type="number" step="0.01" id="buy_price" name="buy_price" required>
                    
                    <label for="sell_price">Sell Price:</label>
                    <input type="number" step="0.01" id="sell_price" name="sell_price" required>
                    
                    <label for="total_quantity">Total Quantity:</label>
                    <input type="number" id="total_quantity" name="total_quantity" required>
                    
                    <button type="submit" name="submit_product">Add Product</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
