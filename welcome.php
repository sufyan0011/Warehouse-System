<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #e9ecef;
            margin: 0;
        }
        .container {
            display: flex;
            min-height: calc(100vh - 60px); /* subtract navbar height */
        }
        .main-content {
            flex: 1;
            padding: 40px;
        }
        .main-content h2 {
            font-size: 28px;
            margin-top: 0;
        }
        .main-content p {
            font-size: 18px;
        }
    </style>
</head>
<body>
    <?php include 'include/navbar.php'; ?>
    <div class="container">
        <?php include 'include/sidebar.php'; ?>
        <div class="main-content">
            <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
            <p>This is your main dashboard. Use the sidebar and navbar to navigate through the system.</p>
        </div>
    </div>
</body>
</html>
