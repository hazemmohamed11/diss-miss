<?php

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

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

// Get the parameter from the request (code, name, or product_id)
$param = $_GET['param'];

// Query to retrieve products with the same code, name, or product_id
$sql = "SELECT * FROM products WHERE name = ? ";
$stmt = $conn->prepare($sql);

// Bind the parameter
$stmt->bind_param("s", $param);
$stmt->execute();
$result = $stmt->get_result();

if ($result !== false && $result->num_rows > 0) {
    $productsByCodeOrName = array();
    $productsByColor = array();

    while ($row = $result->fetch_assoc()) {
        $productsByCodeOrName[] = $row;

        $color = $row['color'];
        $size = $row['size'];
        $medium = $row['media_url'];

        
        if (!array_key_exists($color, $productsByColor)) {
            $productsByColor[$color] = array('sizes' => array(), 'media_url' => null);
        }

        if (!in_array($size, $productsByColor[$color]['sizes'])) {
            $productsByColor[$color]['sizes'][] = $size;
        }

        if ($productsByColor[$color]['media_url'] === null && $medium !== null) {
            $productsByColor[$color]['media_url'] = $medium;
        }
    }

    // Output JSON response
    $response = array(
        'productsByName' => $productsByCodeOrName,
        'productsByColor' => $productsByColor,
    );

    header('Content-Type: application/json');
    echo json_encode($response);
} else {
    echo "No products found";
}

$stmt->close();
$conn->close();
?>
