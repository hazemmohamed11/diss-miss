<?php

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

// /products_by_code_or_name.php?param=YourProductName
///products_by_code_or_name.php?param=YourProductCodeOrID
//http://localhost/diss&miss/detailsproduct.php?param=ch1
//http://localhost/diss&miss/detailsproduct.php?param=chemise
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
$sql = "SELECT * FROM products WHERE code = ? OR name = ? OR product_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssi", $param, $param, $param);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $productsByCodeOrName = array();

    while ($row = $result->fetch_assoc()) {
        $productsByCodeOrName[] = $row;
    }

    // Output JSON response
    header('Content-Type: application/json');
    echo json_encode($productsByCodeOrName);
} else {
    echo "No products found";
}

$stmt->close();
$conn->close();
?>
