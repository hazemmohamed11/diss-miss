<?php
// viewcart.php

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

// Assuming you have a database connection
$host = 'localhost';
$db = 'diss&miss';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Sample data from frontend (you need to replace this with actual data from your frontend)
$user_id = $_GET['user_id'];

// Fetch cart items for the user
$stmt = $pdo->prepare("SELECT p.product_id, p.name, p.price, c.quantity FROM shoppingcart c JOIN products p ON c.product_id = p.product_id WHERE c.user_id = :user_id");
$stmt->bindParam(':user_id', $user_id);

if ($stmt->execute()) {
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($cartItems);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch cart items']);
}
?>
