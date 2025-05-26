<?php
// execute_query.php
require_once __DIR__ . '/include/db.php'; // This file should set $pdo_dw for your data warehouse

// Optionally, check login session here

if (!isset($_GET['query_id'])) {
    die("Query ID not provided.");
}

$query_id = intval($_GET['query_id']);

// Define the built-in queries. Note that we've updated queries that need to join
// dim_product on both product_key and branch.
$queries = [
    1 => "SELECT b.branch_name, d.date_key AS sale_date, SUM(f.total_revenue) AS daily_revenue
FROM fact_sales f
JOIN dim_branch b ON f.branch_key = b.branch_key
JOIN dim_date d ON f.date_key = d.date_key
GROUP BY b.branch_name, d.date_key
ORDER BY d.date_key;",
    
    2 => "SELECT p.product_name, SUM(f.quantity_sold) AS total_sold
FROM fact_sales f
JOIN dim_branch b ON f.branch_key = b.branch_key
JOIN dim_product p ON f.product_key = p.product_key AND p.branch = b.branch_name
GROUP BY p.product_name
ORDER BY total_sold DESC
LIMIT 5;",
    
    3 => "SELECT b.branch_name, d.month, d.year, SUM(f.total_revenue) AS monthly_revenue
FROM fact_sales f
JOIN dim_branch b ON f.branch_key = b.branch_key
JOIN dim_date d ON f.date_key = d.date_key
GROUP BY b.branch_name, d.year, d.month
ORDER BY d.year, d.month;",
    
    4 => "SELECT p.product_name, SUM(f.total_revenue) AS total_revenue
FROM fact_sales f
JOIN dim_branch b ON f.branch_key = b.branch_key
JOIN dim_product p ON f.product_key = p.product_key AND p.branch = b.branch_name
GROUP BY p.product_name
ORDER BY total_revenue DESC
LIMIT 5;",
    
    5 => "SELECT d.date_key AS sale_date, COUNT(f.sale_id) AS num_transactions
FROM fact_sales f
JOIN dim_date d ON f.date_key = d.date_key
GROUP BY d.date_key
ORDER BY d.date_key;",
    
    6 => "SELECT b.branch_name, AVG(f.total_revenue) AS avg_revenue_per_sale
FROM fact_sales f
JOIN dim_branch b ON f.branch_key = b.branch_key
GROUP BY b.branch_name;",
    
    7 => "SELECT b.branch_name, d.date_key AS sale_date, SUM(f.total_revenue) AS daily_revenue, SUM(f.quantity_sold) AS daily_quantity
FROM fact_sales f
JOIN dim_branch b ON f.branch_key = b.branch_key
JOIN dim_date d ON f.date_key = d.date_key
GROUP BY b.branch_name, d.date_key
ORDER BY d.date_key;",
    
    8 => "SELECT p.product_name, SUM(f.quantity_sold) AS lifetime_quantity
FROM fact_sales f
JOIN dim_branch b ON f.branch_key = b.branch_key
JOIN dim_product p ON f.product_key = p.product_key AND p.branch = b.branch_name
GROUP BY p.product_name
ORDER BY lifetime_quantity DESC;",
    
    9 => "SELECT b.branch_name, d.year, d.quarter, SUM(f.total_revenue) AS quarterly_revenue
FROM fact_sales f
JOIN dim_branch b ON f.branch_key = b.branch_key
JOIN dim_date d ON f.date_key = d.date_key
GROUP BY b.branch_name, d.year, d.quarter
ORDER BY d.year, d.quarter;",
    
    10 => "SELECT b.branch_name, p.product_name, SUM(f.total_revenue) AS total_revenue
FROM fact_sales f
JOIN dim_branch b ON f.branch_key = b.branch_key
JOIN dim_product p ON f.product_key = p.product_key AND p.branch = b.branch_name
GROUP BY b.branch_name, p.product_name
ORDER BY b.branch_name, total_revenue DESC;"
];

if (!isset($queries[$query_id])) {
    die("Invalid query ID.");
}

$sql = $queries[$query_id];

try {
    $stmt = $pdo_dw->query($sql);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if(empty($results)) {
        echo "No results found. (Debug: Query executed: <br><pre>" . htmlspecialchars($sql) . "</pre>)";
    } else {
        // Build an HTML table from results
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
