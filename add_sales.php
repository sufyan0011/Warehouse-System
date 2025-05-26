<?php
session_start();
require_once __DIR__ . '/include/db.php';  // This file should set $pdo_east and $pdo_west

// Redirect if not logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

// Determine branch from GET parameter (default to "Walmart East")
$branch = isset($_GET['branch']) ? trim($_GET['branch']) : "Walmart East";

// Choose the appropriate PDO connection based on branch
$pdo_current = ($branch === "Walmart West") ? $pdo_west : $pdo_east;

$error = $success = "";

// Fetch available products from the selected branch
try {
    $stmt = $pdo_current->query("SELECT * FROM products");
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Error fetching products: " . $e->getMessage();
    $products = [];
}

// Process sale form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_sale'])) {
    $customer_name   = trim($_POST['customer_name']);
    $phone_number    = trim($_POST['phone_number']);
    $branch_form     = trim($_POST['branch']); // Should match $branch
    $product_id      = trim($_POST['product_id']);
    $quantity_to_buy = trim($_POST['quantity']);

    if (empty($customer_name) || empty($phone_number) || empty($branch_form) || empty($product_id) || empty($quantity_to_buy)) {
        $error = "All fields are required.";
    } else {
        // Use the same connection as determined by branch
        $pdo_branch = $pdo_current;
        try {
            // Start transaction
            $pdo_branch->beginTransaction();
            
            // Fetch the selected product and verify sufficient stock
            $stmt = $pdo_branch->prepare("SELECT * FROM products WHERE id = ? AND total_quantity >= ?");
            $stmt->execute([$product_id, $quantity_to_buy]);
            $product = $stmt->fetch();
            
            if (!$product) {
                throw new Exception("Product not found or insufficient stock.");
            }
            
            // Calculate total bill (sell_price * quantity)
            $total_bill = $product['sell_price'] * $quantity_to_buy;
            
            // Update product stock
            $stmt = $pdo_branch->prepare("UPDATE products SET total_quantity = total_quantity - ? WHERE id = ?");
            $stmt->execute([$quantity_to_buy, $product_id]);
            
            // Insert sale record (assumes sales table exists with sale_date defaulting to CURRENT_TIMESTAMP)
            $stmt = $pdo_branch->prepare("INSERT INTO sales (customer_name, phone_number, product_id, quantity, total_bill) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$customer_name, $phone_number, $product_id, $quantity_to_buy, $total_bill]);
            
            $pdo_branch->commit();
            $success = "Sale added successfully. Total Bill: $" . number_format($total_bill, 2);
        } catch (Exception $e) {
            $pdo_branch->rollBack();
            $error = "Failed to record sale: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add New Sale</title>
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
            max-width: 600px;
            margin: 0 auto;
        }
        .form-box h2 {
            margin-top: 0;
        }
        .form-box label {
            font-weight: bold;
        }
        .form-box input[type="text"],
        .form-box input[type="tel"],
        .form-box input[type="number"],
        .form-box select {
            width: 100%;
            padding: 10px;
            margin: 10px 0 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .form-box input[readonly] {
            background-color: #e9ecef;
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
        .close-container {
            margin-top: 20px;
            text-align: right;
        }
        .close-btn {
            background-color: #007bff;
            color: #fff;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .close-btn:hover {
            background-color: #0056b3;
        }
    </style>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var branchSelect = document.getElementById('branch');
            // When branch changes, reload the page with the new branch parameter
            branchSelect.addEventListener("change", function() {
                var selectedBranch = branchSelect.value;
                window.location.href = "add_sales.php?branch=" + encodeURIComponent(selectedBranch);
            });
            
            // Populate products dropdown
            var productSelect = document.getElementById('product_id');
            // Clear any existing options
            productSelect.innerHTML = "";
            // Add a placeholder option
            var placeholder = document.createElement("option");
            placeholder.value = "";
            placeholder.text = "Select Product";
            productSelect.appendChild(placeholder);
            // Use products fetched from PHP (which come from the selected branch's database)
            var products = <?php echo json_encode($products); ?>;
            products.forEach(function(prod) {
                var option = document.createElement("option");
                option.value = prod.id;
                option.setAttribute("data-sell-price", prod.sell_price);
                option.text = prod.name + " (Available: " + prod.total_quantity + ")";
                productSelect.appendChild(option);
            });
            
            // Total Bill Calculation
            var quantityInput = document.getElementById('quantity');
            var totalBillInput = document.getElementById('total_bill');
            function updateTotalBill() {
                var selectedOption = productSelect.options[productSelect.selectedIndex];
                var sellPrice = selectedOption ? parseFloat(selectedOption.getAttribute("data-sell-price")) : 0;
                var quantity = parseFloat(quantityInput.value) || 0;
                var total = sellPrice * quantity;
                totalBillInput.value = total.toFixed(2);
            }
            productSelect.addEventListener("change", updateTotalBill);
            quantityInput.addEventListener("input", updateTotalBill);
        });
    </script>
</head>
<body>
    <?php include 'include/navbar.php'; ?>
    <div class="container">
        <?php include 'include/sidebar.php'; ?>
        <div class="main-content">
            <div class="form-box">
                <h2>Add New Sale (<?php echo htmlspecialchars($branch); ?>)</h2>
                <?php 
                    if (!empty($error)) { echo '<p class="error">'.$error.'</p>'; }
                    if (!empty($success)) { echo '<p class="message">'.$success.'</p>'; }
                ?>
                <form action="add_sales.php?branch=<?php echo urlencode($branch); ?>" method="post">
                    <label for="customer_name">Customer Name:</label>
                    <input type="text" id="customer_name" name="customer_name" required>
                    
                    <label for="phone_number">Phone Number:</label>
                    <input type="tel" id="phone_number" name="phone_number" required>
                    
                    <label for="branch">Branch:</label>
                    <select id="branch" name="branch" required>
                        <option value="Walmart East" <?php if($branch=="Walmart East") echo "selected"; ?>>Walmart East</option>
                        <option value="Walmart West" <?php if($branch=="Walmart West") echo "selected"; ?>>Walmart West</option>
                    </select>
                    
                    <label for="product_id">Product:</label>
                    <select id="product_id" name="product_id" required>
                        <option value="">Select Product</option>
                        <!-- Options populated dynamically -->
                    </select>
                    
                    <label for="quantity">Quantity to Buy:</label>
                    <input type="number" id="quantity" name="quantity" required>
                    
                    <label for="total_bill">Total Bill ($):</label>
                    <input type="text" id="total_bill" name="total_bill" readonly>
                    
                    <button type="submit" name="submit_sale">Submit Sale</button>
                </form>
            </div>
            <div class="close-container">
                <form action="show_sales.php" method="get">
                    <button type="submit" class="close-btn">Close</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
