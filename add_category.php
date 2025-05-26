<?php
session_start();


require_once __DIR__ . '/include/db.php';
// Make sure this file contains $pdo_east and $pdo_west connections

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

$error_east = $error_west = '';
$success_east = $success_west = '';

// Process Walmart East category form
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_east'])) {
    $category_name_east = trim($_POST['category_name_east']);
    if (empty($category_name_east)) {
        $error_east = "Category name is required for Walmart East.";
    } else {
        $stmt = $pdo_east->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmt->execute([$category_name_east]);
        $success_east = "Category added successfully for Walmart East.";
    }
}

// Process Walmart West category form
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_west'])) {
    $category_name_west = trim($_POST['category_name_west']);
    if (empty($category_name_west)) {
        $error_west = "Category name is required for Walmart West.";
    } else {
        $stmt = $pdo_west->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmt->execute([$category_name_west]);
        $success_west = "Category added successfully for Walmart West.";
    }
}

// Fetch all categories
$categories_east = $pdo_east->query("SELECT * FROM categories")->fetchAll();
$categories_west = $pdo_west->query("SELECT * FROM categories")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add New Category</title>
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
        .forms-row, .tables-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .form-box, .table-box {
            background-color: #fff;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            width: 48%;
        }
        .form-box h2, .table-box h2 {
            margin-top: 0;
        }
        .form-box label {
            font-weight: bold;
        }
        .form-box input[type="text"] {
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
        .edit-btn {
            background-color: #007bff;
            color: #fff;
            padding: 5px 10px;
            text-decoration: none;
            border-radius: 4px;
        }
        .edit-btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <?php include 'include/navbar.php'; ?>
    <div class="container">
        <?php include 'include/sidebar.php'; ?>
        <div class="main-content">
            <h2>Add New Category</h2>
            <div class="forms-row">
                <div class="form-box">
                    <h3>Walmart East</h3>
                    <?php if ($error_east) echo "<p class='error'>$error_east</p>"; ?>
                    <?php if ($success_east) echo "<p class='message'>$success_east</p>"; ?>
                    <form method="post" action="">
                        <label for="category_name_east">Category Name:</label>
                        <input type="text" id="category_name_east" name="category_name_east" required>
                        <button type="submit" name="submit_east">Add Category</button>
                    </form>
                </div>
                <div class="form-box">
                    <h3>Walmart West</h3>
                    <?php if ($error_west) echo "<p class='error'>$error_west</p>"; ?>
                    <?php if ($success_west) echo "<p class='message'>$success_west</p>"; ?>
                    <form method="post" action="">
                        <label for="category_name_west">Category Name:</label>
                        <input type="text" id="category_name_west" name="category_name_west" required>
                        <button type="submit" name="submit_west">Add Category</button>
                    </form>
                </div>
            </div>
            <div class="tables-row">
                <div class="table-box">
                    <h3>Categories List (Walmart East)</h3>
                    <?php if (!empty($categories_east)): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Category Name</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories_east as $cat): ?>
                                    <tr>
                                        <td><?php echo $cat['id']; ?></td>
                                        <td><?php echo htmlspecialchars($cat['name']); ?></td>
                                        <td><a class="edit-btn" href="edit_category.php?type=east&id=<?php echo $cat['id']; ?>">Edit</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No categories added for Walmart East yet.</p>
                    <?php endif; ?>
                </div>
                <div class="table-box">
                    <h3>Categories List (Walmart West)</h3>
                    <?php if (!empty($categories_west)): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Category Name</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories_west as $cat): ?>
                                    <tr>
                                        <td><?php echo $cat['id']; ?></td>
                                        <td><?php echo htmlspecialchars($cat['name']); ?></td>
                                        <td><a class="edit-btn" href="edit_category.php?type=west&id=<?php echo $cat['id']; ?>">Edit</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No categories added for Walmart West yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 