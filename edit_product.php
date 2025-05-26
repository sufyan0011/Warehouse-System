<?php
session_start();
require_once __DIR__ . '/include/db.php'; // Must set $pdo_east and $pdo_west

// Redirect if not logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

// Ensure both product id and branch are provided
if (!isset($_GET['id']) || !isset($_GET['branch'])) {
    echo "Product ID and branch must be provided.";
    exit;
}

$productId = intval($_GET['id']);
$branchParam = trim($_GET['branch']);

// Validate branch and choose connection
if ($branchParam === "Walmart East") {
    $pdo_used = $pdo_east;
} elseif ($branchParam === "Walmart West") {
    $pdo_used = $pdo_west;
} else {
    echo "Invalid branch specified.";
    exit;
}
$branchUsed = $branchParam;

// Fetch the product from the selected database
$stmt = $pdo_used->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    echo "Product not found in $branchUsed.";
    exit;
}

// Fetch categories from the same database for this branch
$stmt = $pdo_used->prepare("SELECT id, name FROM categories ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll();

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_edit'])) {
    $product_name     = trim($_POST['product_name']);
    // We use the branch from the GET parameter; you can also allow editing if needed.
    $branch           = $branchUsed;
    $product_category = trim($_POST['product_category']); // expected category id
    $buy_price        = trim($_POST['buy_price']);
    $sell_price       = trim($_POST['sell_price']);
    $total_quantity   = trim($_POST['total_quantity']);
    
    if (empty($product_name) || empty($product_category) || empty($buy_price) || empty($sell_price) || empty($total_quantity)) {
        $error = "All fields are required.";
    } else {
        // Update the product in the selected database
        $stmt = $pdo_used->prepare("UPDATE products SET name = ?, branch = ?, category_id = ?, buy_price = ?, sell_price = ?, total_quantity = ? WHERE id = ?");
        $stmt->execute([$product_name, $branch, $product_category, $buy_price, $sell_price, $total_quantity, $productId]);
        $success = "Product updated successfully.";
        
        // Optionally, redirect after a successful update:
        header("Location: show_product.php?branch=" . urlencode($branch));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Product</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f8f9fa; margin: 0; }
        .container { display: flex; }
        .main-content { flex: 1; padding: 20px; }
        .form-box { background-color: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 8px; max-width: 500px; margin: 0 auto; }
        .form-box h2 { margin-top: 0; }
        .form-box label { font-weight: bold; }
        .form-box input[type="text"],
        .form-box input[type="number"],
        .form-box select { width: 100%; padding: 10px; margin: 10px 0 15px; border: 1px solid #ccc; border-radius: 4px; }
        .form-box input[readonly] { background-color: #e9ecef; }
        .form-box button { padding: 10px 20px; background-color: #5cb85c; border: none; color: #fff; border-radius: 4px; cursor: pointer; }
        .form-box button:hover { background-color: #4cae4c; }
        .error { color: red; }
        .message { color: green; }
    </style>
</head>
<body>
    <?php include 'include/navbar.php'; ?>
    <div class="container">
        <?php include 'include/sidebar.php'; ?>
        <div class="main-content">
            <div class="form-box">
                <h2>Edit Product (<?php echo htmlspecialchars($branchUsed); ?>)</h2>
                <?php 
                    if (!empty($error)) echo '<p class="error">' . $error . '</p>';
                    if (!empty($success)) echo '<p class="message">' . $success . '</p>';
                ?>
                <form action="edit_product.php?id=<?php echo $productId; ?>&branch=<?php echo urlencode($branchUsed); ?>" method="post">
                    <label for="product_name">Product Name:</label>
                    <input type="text" id="product_name" name="product_name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                    
                    <label for="branch">Branch:</label>
                    <!-- Display branch as a read-only dropdown -->
                    <select id="branch" name="branch" required disabled>
                        <option value="Walmart East" <?php if($branchUsed=="Walmart East") echo "selected"; ?>>Walmart East</option>
                        <option value="Walmart West" <?php if($branchUsed=="Walmart West") echo "selected"; ?>>Walmart West</option>
                    </select>
                    <!-- Also include the branch in a hidden field so it gets submitted -->
                    <input type="hidden" name="branch" value="<?php echo htmlspecialchars($branchUsed); ?>">
                    
                    <label for="product_category">Product Category:</label>
                    <select id="product_category" name="product_category" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php if($cat['id'] == $product['category_id']) echo "selected"; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <label for="buy_price">Buy Price:</label>
                    <input type="number" step="0.01" id="buy_price" name="buy_price" value="<?php echo htmlspecialchars($product['buy_price']); ?>" required>
                    
                    <label for="sell_price">Sell Price:</label>
                    <input type="number" step="0.01" id="sell_price" name="sell_price" value="<?php echo htmlspecialchars($product['sell_price']); ?>" required>
                    
                    <label for="total_quantity">Total Quantity:</label>
                    <input type="number" id="total_quantity" name="total_quantity" value="<?php echo htmlspecialchars($product['total_quantity']); ?>" required>
                    
                    <button type="submit" name="submit_edit">Update Product</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
