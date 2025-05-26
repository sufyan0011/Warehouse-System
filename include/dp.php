<?php
// db.php

$host = 'localhost';
$user = 'root';          // Your MySQL username
$pass = '';              // Your MySQL password
$charset = 'utf8mb4';

// DSNs for each database
$dsn_east = "mysql:host=$host;dbname=walmart_east;charset=$charset";
$dsn_west = "mysql:host=$host;dbname=walmart_west;charset=$charset";
$dsn_dw   = "mysql:host=$host;dbname=walmart_datawarehouse;charset=$charset";

// Common PDO options
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // Create three separate PDO connections
    $pdo_east = new PDO($dsn_east, $user, $pass, $options);
    $pdo_west = new PDO($dsn_west, $user, $pass, $options);
    $pdo_dw   = new PDO($dsn_dw, $user, $pass, $options);
} catch (PDOException $e) {
    // In production, log the error and display a generic message
    die("Database connection failed: " . $e->getMessage());
}
?>
