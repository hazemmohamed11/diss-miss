<?php
// updatecart.php

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
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['user_id'], $data['token'], $data['cart_items'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid data']);
    exit;
}

$secret_key = "hazem"; 
$token = $data['token'];

function custom_jwt_decode($jwt, $key) {
    $parts = explode('.', $jwt);
    if (count($parts) !== 3) {
        return false;
    }

    list($header, $payload, $signature) = $parts;

    $verified_signature = hash_hmac('sha256', $header . '.' . $payload, $key, true);
    $verified_signature_base64 = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($verified_signature));

    if ($signature !== $verified_signature_base64) {
        return false;
    }

    return json_decode(base64_decode($payload), true);
}

$decoded = custom_jwt_decode($token, $secret_key);

if (!$decoded || $decoded['user_id'] !== $data['user_id']) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid token or user_id']);
    exit;
}

$user_id = $data['user_id'];
$cart_items = $data['cart_items'];

// Update items in the shopping cart
foreach ($cart_items as $item) {
    $product_id = $item['product_id'];
    $quantity = $item['quantity'];

    $stmt = $pdo->prepare("UPDATE shoppingcart SET quantity = :quantity WHERE user_id = :user_id AND product_id = :product_id");
    $stmt->bindParam(':quantity', $quantity);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':product_id', $product_id);

    $stmt->execute();
}

echo json_encode(['success' => true, 'message' => 'Cart updated successfully']);
?>
