<?php

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

// Database connection details
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "diss&miss";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Query to retrieve best sellers (grouped by code or name)
$sql = "SELECT p.code, p.name, MAX(p.stock) AS max_stock, SUM(c.quantity) AS total_sold
        FROM products p
        LEFT JOIN shoppingcart c ON p.product_id = c.product_id
        GROUP BY p.code, p.name
        ORDER BY total_sold DESC";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $groupedProducts = array();

    while ($row = $result->fetch_assoc()) {
        $code = $row["code"];
        $name = $row["name"];
        $maxStock = $row["max_stock"];
        $totalSold = $row["total_sold"];

        $groupedProducts[] = array(
            'code' => $code,
            'name' => $name,
            'max_stock' => $maxStock,
            'total_sold' => $totalSold
        );
    }

    // Output JSON response
    header('Content-Type: application/json');
    echo json_encode($groupedProducts);
} else {
    echo "No products found";
}

$conn->close();
?>
