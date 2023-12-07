<?php
// addtocart.php

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");
header("Access-Control-Allow-Credentials: true");
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

// Sample data from React (you need to replace this with actual data from your React app)
$data = json_decode(file_get_contents('php://input'), true);

// Check if required data is present
if (!isset($data['user_id'], $data['product_id'], $data['quantity'], $data['token'])) {
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

if (!$decoded) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid token']);
    exit;
}

// Check product quantity before adding to the cart
$sqlCheckQuantity = "SELECT stock FROM products WHERE product_id = :product_id";
$stmtCheckQuantity = $pdo->prepare($sqlCheckQuantity);
$stmtCheckQuantity->bindParam(':product_id', $data['product_id']);
$stmtCheckQuantity->execute();

$product = $stmtCheckQuantity->fetch(PDO::FETCH_ASSOC);

if ($product['stock'] < $data['quantity']) {
    http_response_code(400);
    echo json_encode(['error' => 'Insufficient stock']);
    exit;
}

// Check if the product is already in the cart for the user
$sqlCheckCart = "SELECT * FROM shoppingcart WHERE user_id = :user_id AND product_id = :product_id";
$stmtCheckCart = $pdo->prepare($sqlCheckCart);
$stmtCheckCart->bindParam(':user_id', $data['user_id']);
$stmtCheckCart->bindParam(':product_id', $data['product_id']);
$stmtCheckCart->execute();

if ($stmtCheckCart->rowCount() > 0) {
    // Product is already in the cart, update the quantity
    $sqlUpdateCart = "UPDATE shoppingcart SET quantity = quantity + :quantity WHERE user_id = :user_id AND product_id = :product_id";
    $stmtUpdateCart = $pdo->prepare($sqlUpdateCart);
    $stmtUpdateCart->bindParam(':quantity', $data['quantity']);
    $stmtUpdateCart->bindParam(':user_id', $data['user_id']);
    $stmtUpdateCart->bindParam(':product_id', $data['product_id']);

    if ($stmtUpdateCart->execute()) {
        echo json_encode(['success' => 'Product quantity updated in the cart']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update product quantity in the cart']);
    }
} else {
    // Product is not in the cart, insert into the cart
    $sqlAddToCart = "INSERT INTO shoppingcart (user_id, product_id, quantity) VALUES (:user_id, :product_id, :quantity)";
    $stmtAddToCart = $pdo->prepare($sqlAddToCart);
    $stmtAddToCart->bindParam(':user_id', $data['user_id']);
    $stmtAddToCart->bindParam(':product_id', $data['product_id']);
    $stmtAddToCart->bindParam(':quantity', $data['quantity']);

    if ($stmtAddToCart->execute()) {
        echo json_encode(['success' => 'Product added to the cart']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to add product to the cart']);
    }
}
?>
