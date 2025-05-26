<?php
session_start();
require_once __DIR__ . '/include/db.php'; // This should set $pdo_east and $pdo_west

// Redirect if the user is not logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

// Ensure category type and id are provided
if (!isset($_GET['type']) || !isset($_GET['id'])) {
    header("Location: add_category.php");
    exit;
}

$type = $_GET['type']; // Expected values: 'east' or 'west'
$id = intval($_GET['id']);

// Choose the appropriate PDO connection
$pdo_current = ($type === 'west') ? $pdo_west : $pdo_east;

// Fetch the category record from the database
$stmt = $pdo_current->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->execute([$id]);
$category = $stmt->fetch();

if (!$category) {
    echo "Category not found.";
    exit;
}

$error = "";
$success = "";

// Process form submission to update the category name
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_edit'])) {
    $new_name = trim($_POST['category_name']);
    if (empty($new_name)) {
        $error = "Category name cannot be empty.";
    } else {
        $stmt = $pdo_current->prepare("UPDATE categories SET name = ? WHERE id = ?");
        $stmt->execute([$new_name, $id]);
        $success = "Category updated successfully.";
        // Refresh the category data
        $stmt = $pdo_current->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        $category = $stmt->fetch();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Category</title>
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
        a {
            text-decoration: none;
            color: #007bff;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <?php include 'include/navbar.php'; ?>
    <div class="container">
        <?php include 'include/sidebar.php'; ?>
        <div class="main-content">
            <div class="form-box">
                <h2>Edit Category - <?php echo ucfirst($type); ?> Branch</h2>
                <?php 
                if (!empty($error)) echo '<p class="error">'.$error.'</p>';
                if (!empty($success)) echo '<p class="message">'.$success.'</p>';
                ?>
                <form action="edit_category.php?type=<?php echo $type; ?>&id=<?php echo $id; ?>" method="post">
                    <label for="category_name">Category Name:</label>
                    <input type="text" id="category_name" name="category_name" value="<?php echo htmlspecialchars($category['name']); ?>" required>
                    <button type="submit" name="submit_edit">Update Category</button>
                </form>
                <p><a href="add_category.php">Back to Categories</a></p>
            </div>
        </div>
    </div>
</body>
</html>
