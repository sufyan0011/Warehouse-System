<?php
session_start();
require_once __DIR__ . '/include/db.php'; // Must set $pdo_east and $pdo_west

// Redirect if not logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

// Ensure product id and branch are provided
if (!isset($_GET['id']) || !isset($_GET['branch'])) {
    die("Product ID and branch are required.");
}

$productId = intval($_GET['id']);
$branch = trim($_GET['branch']);

if ($branch === "Walmart East") {
    $pdo_used = $pdo_east;
} elseif ($branch === "Walmart West") {
    $pdo_used = $pdo_west;
} else {
    die("Invalid branch specified.");
}

try {
    // Begin transaction
    $pdo_used->beginTransaction();

    // Delete related sales records first
    $stmt = $pdo_used->prepare("DELETE FROM sales WHERE product_id = ?");
    $stmt->execute([$productId]);

    // Delete the product record
    $stmt = $pdo_used->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$productId]);

    // Commit the transaction
    $pdo_used->commit();

    // Redirect back to the show_product page with the branch parameter
    header("Location: show_product.php?branch=" . urlencode($branch));
    exit;
} catch (PDOException $e) {
    $pdo_used->rollBack();
    die("Error deleting product: " . $e->getMessage());
}
?>
