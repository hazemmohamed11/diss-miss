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

// Query to retrieve best sellers (grouped by name)
$sql = "SELECT
            p.name,
            p.price,
            p.category,
            p.description,
            p.media_url AS image,
            MAX(p.stock) AS max_stock,
            SUM(c.quantity) AS total_sold
        FROM
            products p
        LEFT JOIN
            shoppingcart c ON p.product_id = c.product_id
        GROUP BY
            p.name
        ORDER BY
            total_sold DESC";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $groupedProducts = array();

    while ($row = $result->fetch_assoc()) {
        $name = $row["name"];
        $price = $row["price"];
        $category = $row["category"];
        $description = $row["description"];
        $image = $row["image"];
        $maxStock = $row["max_stock"];
        $totalSold = $row["total_sold"];

        $groupedProducts[] = array(
            'name' => $name,
            'price' => $price,
            'category' => $category,
            'description' => $description,
            'image' => $image,
            'max_stock' => $maxStock,
            'total_sold' => $totalSold
        );
    }

    // Output JSON response
    echo json_encode($groupedProducts);
} else {
    echo "No products found";
}

$conn->close();
?>
