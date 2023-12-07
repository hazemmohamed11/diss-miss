<?php
// deletecart.php

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

if (!isset($data['user_id'], $data['token'], $data['product_id'])) {
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
$product_id = $data['product_id'];

// Delete item from the shopping cart
$stmt = $pdo->prepare("DELETE FROM shoppingcart WHERE user_id = :user_id AND product_id = :product_id");
$stmt->bindParam(':user_id', $user_id);
$stmt->bindParam(':product_id', $product_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Item deleted from the cart']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to delete item from the cart']);
}
?>
