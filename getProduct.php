<?php
//http://localhost/diss&miss/getProduct.php?productId=0
//http://localhost/diss&miss/getProduct.php

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

$host = 'localhost';
$db = 'diss&miss';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Check if a specific product ID is provided in the URL
if (isset($_GET['product_id'])) {
    $productId = $_GET['product_id'];

    $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = :product_id");
    $stmt->bindParam(':product_id', $productId);

    if ($stmt->execute()) {
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode($product);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch product']);
    }
} else {
    // Fetch all products
    $stmt = $pdo->query("SELECT * FROM products");

    if ($stmt) {
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($products);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch products']);
    }
}
?>
