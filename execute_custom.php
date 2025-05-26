<?php
// execute_custom.php
require_once __DIR__ . '/include/db.php'; // Ensure $pdo_dw is set for your data warehouse

// Optionally, check user authentication here.

if (!isset($_POST['custom_query'])) {
    die("No SQL query provided.");
}

$customQuery = trim($_POST['custom_query']);

// WARNING: Executing arbitrary SQL can be very dangerous.
// This example is for testing in a controlled environment only.
try {
    $stmt = $pdo_dw->query($customQuery);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if(empty($results)) {
        echo "No results found.";
    } else {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        // Table headers
        echo "<tr>";
        foreach(array_keys($results[0]) as $col) {
            echo "<th>" . htmlspecialchars($col) . "</th>";
        }
        echo "</tr>";
        // Table rows
        foreach($results as $row) {
            echo "<tr>";
            foreach($row as $value) {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (PDOException $e) {
    echo "Error executing query: " . $e->getMessage();
}
?>
