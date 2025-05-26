<?php
session_start();
require_once __DIR__ . '/include/db.php';  // Sets $pdo_east, $pdo_west, and $pdo_dw

/**
 * Ensure that a given date (YYYY-MM-DD) exists in dim_date.
 */
function ensureDateExists($pdo_dw, $dateStr) {
    $checkStmt = $pdo_dw->prepare("SELECT date_key FROM dim_date WHERE date_key = ?");
    $checkStmt->execute([$dateStr]);
    if (!$checkStmt->fetch()) {
        $timestamp = strtotime($dateStr);
        $day       = date("d", $timestamp);
        $month     = date("m", $timestamp);
        $year      = date("Y", $timestamp);
        $quarter   = ceil(date("n", $timestamp) / 3);
        $day_name  = date("l", $timestamp);
        $month_name= date("F", $timestamp);
        $insertStmt = $pdo_dw->prepare("
            INSERT INTO dim_date (date_key, day, month, year, quarter, day_name, month_name)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $insertStmt->execute([$dateStr, $day, $month, $year, $quarter, $day_name, $month_name]);
        error_log("ETL: Inserted dim_date record for $dateStr");
    }
}

/**
 * Upsert a product into dim_product using a composite key (product_key, branch).
 * Fetches product details from the operational DB ($pdo_operational) and then
 * either updates the record if it exists or inserts a new one.
 */
function ensureOrUpdateProduct($pdo_dw, $pdo_operational, $productKey, $branchName) {
    // Check if record exists in dim_product for this product and branch.
    $checkStmt = $pdo_dw->prepare("
        SELECT product_key 
        FROM dim_product 
        WHERE product_key = ? AND branch = ?
    ");
    $checkStmt->execute([$productKey, $branchName]);
    $existing = $checkStmt->fetch();

    // Fetch product details from operational DB.
    $stmt = $pdo_operational->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$productKey]);
    $prod = $stmt->fetch();

    if ($prod) {
        $category = isset($prod['category']) && !empty($prod['category']) ? $prod['category'] : 'Uncategorized';
        if ($existing) {
            $updateStmt = $pdo_dw->prepare("
                UPDATE dim_product 
                SET product_name = ?, category = ?, buy_price = ?, sell_price = ? 
                WHERE product_key = ? AND branch = ?
            ");
            $updateStmt->execute([$prod['name'], $category, $prod['buy_price'], $prod['sell_price'], $productKey, $branchName]);
            error_log("ETL: Updated dim_product for product_id=$productKey, branch=$branchName");
        } else {
            $insertStmt = $pdo_dw->prepare("
                INSERT INTO dim_product (product_key, product_name, branch, category, buy_price, sell_price)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $insertStmt->execute([$productKey, $prod['name'], $branchName, $category, $prod['buy_price'], $prod['sell_price']]);
            error_log("ETL: Inserted dim_product for product_id=$productKey, branch=$branchName");
        }
    } else {
        error_log("ETL: Operational product not found for product_id=$productKey");
    }
}

/**
 * Run the ETL process:
 * - Extract sales from both operational databases.
 * - For each sale, ensure the sale date exists in dim_date.
 * - Upsert the corresponding product in dim_product using (product_key, branch).
 * - Truncate fact_sales and insert the sales data.
 */
function runETL() {
    global $pdo_east, $pdo_west, $pdo_dw;
    
    error_log("ETL: Starting ETL process.");
    $sales = [];

    // Extract sales from Walmart East.
    try {
        $stmt = $pdo_east->query("SELECT * FROM sales");
        $salesEast = $stmt->fetchAll();
        error_log("ETL: Extracted " . count($salesEast) . " sales from East.");
    } catch (PDOException $e) {
        error_log("ETL East Error: " . $e->getMessage());
        $salesEast = [];
    }
    foreach ($salesEast as $sale) {
        $sale['branch'] = "Walmart East";
        $sales[] = $sale;
    }

    // Extract sales from Walmart West.
    try {
        $stmt = $pdo_west->query("SELECT * FROM sales");
        $salesWest = $stmt->fetchAll();
        error_log("ETL: Extracted " . count($salesWest) . " sales from West.");
    } catch (PDOException $e) {
        error_log("ETL West Error: " . $e->getMessage());
        $salesWest = [];
    }
    foreach ($salesWest as $sale) {
        $sale['branch'] = "Walmart West";
        $sales[] = $sale;
    }

    // Mapping for branch names to branch_key.
    $branchMapping = [
        "Walmart East" => 1,
        "Walmart West" => 2
    ];

    // Truncate fact_sales for a full refresh.
    try {
        $pdo_dw->exec("TRUNCATE TABLE fact_sales");
        error_log("ETL: Truncated fact_sales.");
    } catch (PDOException $e) {
        error_log("ETL Truncate Error: " . $e->getMessage());
        die("Error truncating fact_sales: " . $e->getMessage());
    }

    $insertStmt = $pdo_dw->prepare("
        INSERT INTO fact_sales (date_key, product_key, branch_key, quantity_sold, total_revenue, sale_date)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $inserted = 0;
    foreach ($sales as $sale) {
        // Convert sale_date to YYYY-MM-DD.
        $dateKey = date("Y-m-d", strtotime($sale['sale_date']));
        ensureDateExists($pdo_dw, $dateKey);

        // Choose the appropriate operational DB.
        $pdo_operational = ($sale['branch'] === "Walmart East") ? $pdo_east : $pdo_west;
        $productKey = $sale['product_id'];
        // Upsert product record using composite key.
        ensureOrUpdateProduct($pdo_dw, $pdo_operational, $productKey, $sale['branch']);

        $branchKey = isset($branchMapping[$sale['branch']]) ? $branchMapping[$sale['branch']] : null;
        if ($branchKey === null) continue;
        $quantity = $sale['quantity'];
        $totalRevenue = $sale['total_bill'];
        
        error_log("ETL: Inserting sale - dateKey: $dateKey, productKey: $productKey, branch: " . $sale['branch'] . ", qty: $quantity, totalRevenue: $totalRevenue");
        try {
            $insertStmt->execute([$dateKey, $productKey, $branchKey, $quantity, $totalRevenue, $sale['sale_date']]);
            $inserted++;
        } catch (PDOException $e) {
            error_log("ETL Insert Error: " . $e->getMessage());
            continue;
        }
    }
    error_log("ETL: Inserted $inserted records into fact_sales.");
    error_log("ETL: Finished ETL process.");
}

// If GET parameter action=etl is set, run ETL and exit.
if (isset($_GET['action']) && $_GET['action'] === 'etl') {
    runETL();
    echo "ETL process completed.";
    exit;
}

// --- Built-in Queries for the Front End ---
$queries = [
    1 => [
      'english' => 'Show total daily revenue for each branch.',
      'sql' => "SELECT b.branch_name, d.date_key AS sale_date, SUM(f.total_revenue) AS daily_revenue
FROM fact_sales f
JOIN dim_branch b ON f.branch_key = b.branch_key
JOIN dim_date d ON f.date_key = d.date_key
GROUP BY b.branch_name, d.date_key
ORDER BY d.date_key;"
    ],
    2 => [
      'english' => 'List top 5 products by total quantity sold.',
      'sql' => "SELECT p.product_name, SUM(f.quantity_sold) AS total_sold
FROM fact_sales f
JOIN dim_branch b ON f.branch_key = b.branch_key
JOIN dim_product p ON f.product_key = p.product_key AND p.branch = b.branch_name
GROUP BY p.product_name
ORDER BY total_sold DESC
LIMIT 5;"
    ],
    3 => [
      'english' => 'Find monthly revenue for each branch.',
      'sql' => "SELECT b.branch_name, d.month, d.year, SUM(f.total_revenue) AS monthly_revenue
FROM fact_sales f
JOIN dim_branch b ON f.branch_key = b.branch_key
JOIN dim_date d ON f.date_key = d.date_key
GROUP BY b.branch_name, d.year, d.month
ORDER BY d.year, d.month;"
    ],
    4 => [
      'english' => 'List the top 5 products by total revenue.',
      'sql' => "SELECT p.product_name, SUM(f.total_revenue) AS total_revenue
FROM fact_sales f
JOIN dim_branch b ON f.branch_key = b.branch_key
JOIN dim_product p ON f.product_key = p.product_key AND p.branch = b.branch_name
GROUP BY p.product_name
ORDER BY total_revenue DESC
LIMIT 5;"
    ],
    5 => [
      'english' => 'Find the total number of transactions per day.',
      'sql' => "SELECT d.date_key AS sale_date, COUNT(f.sale_id) AS num_transactions
FROM fact_sales f
JOIN dim_date d ON f.date_key = d.date_key
GROUP BY d.date_key
ORDER BY d.date_key;"
    ],
    6 => [
      'english' => 'Calculate average revenue per sale for each branch.',
      'sql' => "SELECT b.branch_name, AVG(f.total_revenue) AS avg_revenue_per_sale
FROM fact_sales f
JOIN dim_branch b ON f.branch_key = b.branch_key
GROUP BY b.branch_name;"
    ],
    7 => [
      'english' => 'Show daily revenue and quantity sold for each branch.',
      'sql' => "SELECT b.branch_name, d.date_key AS sale_date, SUM(f.total_revenue) AS daily_revenue, SUM(f.quantity_sold) AS daily_quantity
FROM fact_sales f
JOIN dim_branch b ON f.branch_key = b.branch_key
JOIN dim_date d ON f.date_key = d.date_key
GROUP BY b.branch_name, d.date_key
ORDER BY d.date_key;"
    ],
    8 => [
      'english' => 'Find total quantity sold for each product over its lifetime.',
      'sql' => "SELECT p.product_name, SUM(f.quantity_sold) AS lifetime_quantity
FROM fact_sales f
JOIN dim_branch b ON f.branch_key = b.branch_key
JOIN dim_product p ON f.product_key = p.product_key AND p.branch = b.branch_name
GROUP BY p.product_name
ORDER BY lifetime_quantity DESC;"
    ],
    9 => [
      'english' => 'Show quarterly revenue by branch.',
      'sql' => "SELECT b.branch_name, d.year, d.quarter, SUM(f.total_revenue) AS quarterly_revenue
FROM fact_sales f
JOIN dim_branch b ON f.branch_key = b.branch_key
JOIN dim_date d ON f.date_key = d.date_key
GROUP BY b.branch_name, d.year, d.quarter
ORDER BY d.year, d.quarter;"
    ],
    10 => [
      'english' => 'Show total revenue by product for each branch.',
      'sql' => "SELECT b.branch_name, p.product_name, SUM(f.total_revenue) AS total_revenue
FROM fact_sales f
JOIN dim_branch b ON f.branch_key = b.branch_key
JOIN dim_product p ON f.product_key = p.product_key AND p.branch = b.branch_name
GROUP BY b.branch_name, p.product_name
ORDER BY b.branch_name, total_revenue DESC;"
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Data Warehouse Queries</title>
  <style>
    body {
      margin: 0;
      padding: 0;
      font-family: Arial, sans-serif;
      background-color: #f2f2f2;
    }
    .wrapper {
      display: flex;
      min-height: 100vh;
      flex-direction: column;
    }
    .navbar { width: 100%; }
    .main-area { flex: 1; display: flex; }
    .sidebar { width: 250px; background-color: #333; color: #fff; padding: 20px; }
    .main-content { flex: 1; padding: 20px; background-color: #f2f2f2; }
    .top-buttons { margin-bottom: 20px; }
    .refresh-btn {
      padding: 8px 16px;
      background-color: #28a745;
      color: #fff;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      margin-right: 10px;
    }
    .refresh-btn:hover { background-color: #218838; }
    .query-box {
      background-color: #fff;
      border: 1px solid #ccc;
      border-radius: 6px;
      padding: 20px;
      margin-bottom: 20px;
    }
    .query-box h2 { margin-top: 0; font-size: 18px; }
    .query-row {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
    }
    .query-sql, .query-output {
      flex: 1;
      min-width: 300px;
      margin-top: 10px;
    }
    .query-sql pre {
      background-color: #e9ecef;
      padding: 10px;
      border-radius: 4px;
      margin: 0;
      white-space: pre-wrap;
      word-break: break-word;
    }
    .query-output {
      background-color: #f8f9fa;
      border: 1px solid #ccc;
      border-radius: 4px;
      padding: 10px;
      min-height: 80px;
    }
    .run-btn {
      display: inline-block;
      padding: 6px 12px;
      background-color: #007bff;
      color: #fff;
      border-radius: 4px;
      text-decoration: none;
      cursor: pointer;
      margin-top: 10px;
    }
    .run-btn:hover { background-color: #0056b3; }
    /* Custom query section */
    .custom-query-box {
      background-color: #fff;
      border: 1px solid #ccc;
      border-radius: 6px;
      padding: 20px;
      margin-top: 40px;
    }
    .custom-query-box h2 { margin-top: 0; font-size: 18px; }
    .custom-query-box textarea {
      width: 100%;
      height: 150px;
      padding: 10px;
      font-family: monospace;
      font-size: 14px;
      border: 1px solid #ccc;
      border-radius: 4px;
      resize: vertical;
    }
    .custom-query-output {
      margin-top: 20px;
      background-color: #f8f9fa;
      border: 1px solid #ccc;
      border-radius: 4px;
      padding: 10px;
      min-height: 80px;
    }
    .custom-run-btn {
      padding: 8px 16px;
      background-color: #007bff;
      color: #fff;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      margin-top: 10px;
    }
    .custom-run-btn:hover { background-color: #0056b3; }
  </style>
  <script>
    function refreshWarehouse() {
      const refreshBtn = document.getElementById('refreshBtn');
      refreshBtn.disabled = true;
      refreshBtn.innerText = "Refreshing...";
      fetch('datawarehouse_frontend.php?action=etl&t=' + new Date().getTime())
        .then(response => response.text())
        .then(data => {
          alert(data);
          location.reload();
        })
        .catch(error => {
          alert("ETL Error: " + error);
          refreshBtn.disabled = false;
          refreshBtn.innerText = "Refresh Warehouse";
        });
    }
    function runQuery(queryId) {
      const outputDiv = document.getElementById('output-' + queryId);
      outputDiv.innerHTML = "Running query...";
      fetch('execute_query.php?query_id=' + queryId + '&t=' + new Date().getTime())
        .then(response => response.text())
        .then(data => {
          outputDiv.innerHTML = data;
        })
        .catch(error => {
          outputDiv.innerHTML = "Error: " + error;
        });
    }
    function runCustomQuery() {
      const customQuery = document.getElementById('customQuery').value;
      const outputDiv = document.getElementById('customQueryOutput');
      if (!customQuery.trim()) {
        alert("Please enter a SQL query.");
        return;
      }
      outputDiv.innerHTML = "Running custom query...";
      fetch('execute_custom.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'custom_query=' + encodeURIComponent(customQuery)
      })
        .then(response => response.text())
        .then(data => {
          outputDiv.innerHTML = data;
        })
        .catch(error => {
          outputDiv.innerHTML = "Error: " + error;
        });
    }
  </script>
</head>
<body>
  <!-- Navbar -->
  <div class="navbar">
    <?php include 'include/navbar.php'; ?>
  </div>
  
  <!-- Main Area: Sidebar + Main Content -->
  <div class="main-area">
    <!-- Sidebar -->
    <div class="sidebar">
      <?php include 'include/sidebar.php'; ?>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
      <h1>Data Warehouse Queries</h1>
      <div class="top-buttons">
        <button class="refresh-btn" id="refreshBtn" onclick="refreshWarehouse()">Refresh Warehouse (ETL)</button>
      </div>
      <?php
      // Output built-in query boxes.
      foreach ($queries as $id => $query) {
          echo '<div class="query-box">';
          echo '<h2>' . $id . '. English: "' . htmlspecialchars($query['english']) . '"</h2>';
          echo '<div class="query-row">';
          echo '<div class="query-sql"><strong>SQL Query:</strong><pre id="sql' . $id . '">' . htmlspecialchars($query['sql']) . '</pre>';
          echo '<button class="run-btn" onclick="runQuery(' . $id . ')">Run</button></div>';
          echo '<div class="query-output" id="output-' . $id . '"></div>';
          echo '</div>';
          echo '</div>';
      }
      ?>
      
      <!-- Custom SQL Query Section -->
      <div class="custom-query-box">
        <h2>Custom SQL Query</h2>
        <textarea id="customQuery" placeholder="Enter your SQL query here..."></textarea>
        <br>
        <button class="custom-run-btn" onclick="runCustomQuery()">Run Custom Query</button>
        <div class="custom-query-output" id="customQueryOutput"></div>
      </div>
    </div>
  </div>
</body>
</html>
