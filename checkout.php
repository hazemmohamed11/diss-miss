<?php
// checkout.php

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
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Enable error reporting
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Sample data from React (you need to replace this with actual data from your React app)
$data = json_decode(file_get_contents('php://input'), true);

// Check if required data is present
if (!isset($data['user_id'], $data['token'], $data['address_id'], $data['voucher_code'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid data']);
    exit;
}

$secret_key = "hazem"; // Replace with your actual secret key
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
    echo json_encode(['error' => 'Invalid token']);
    exit;
}

try {
    // Check if the quantity in the cart exceeds the available stock
    $sql = "SELECT shoppingcart.product_id, shoppingcart.quantity, products.stock
            FROM shoppingcart
            JOIN products ON shoppingcart.product_id = products.product_id
            WHERE shoppingcart.user_id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':user_id', $data['user_id']);
    $stmt->execute();

    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($cartItems as $item) {
        if ($item['quantity'] > $item['stock']) {
            throw new Exception('Quantity exceeds available stock for product ' . $item['product_id']);
        }
    }

    foreach ($cartItems as $item) {
        $newStock = $item['stock'] - $item['quantity'];
        $updateSql = "UPDATE products SET stock = :new_stock WHERE product_id = :product_id";
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->bindParam(':new_stock', $newStock);
        $updateStmt->bindParam(':product_id', $item['product_id']);
        $updateStmt->execute();
    }

    // Calculate total cost based on the products in the cart
    $totalCost = 0;

    foreach ($cartItems as $item) {
        // Fetch the product price from the database based on the product_id
        $priceSql = "SELECT price FROM products WHERE product_id = :product_id";
        $priceStmt = $pdo->prepare($priceSql);
        $priceStmt->bindParam(':product_id', $item['product_id']);
        $priceStmt->execute();
        $productPrice = $priceStmt->fetchColumn();

        // Calculate the total cost for each item
        $totalCost += $item['quantity'] * $productPrice;
    }

    // Check and apply voucher discount
    if (!empty($data['voucher_code'])) {
        $voucherSql = "SELECT voucher_id, discount FROM vouchers WHERE voucher_code = :voucher_code AND status = 'active'";
        $voucherStmt = $pdo->prepare($voucherSql);
        $voucherStmt->bindParam(':voucher_code', $data['voucher_code']);
        $voucherStmt->execute();
        $voucherData = $voucherStmt->fetch(PDO::FETCH_ASSOC);

        if ($voucherData) {
            $voucherId = $voucherData['voucher_id'];
            $voucherDiscount = $voucherData['discount'];

            // Apply voucher discount to total cost
            $totalCost -= $voucherDiscount;

            // Update the voucher status to 'used'
            $updateVoucherSql = "UPDATE vouchers SET status = 'used' WHERE voucher_id = :voucher_id";
            $updateVoucherStmt = $pdo->prepare($updateVoucherSql);
            $updateVoucherStmt->bindParam(':voucher_id', $voucherId);
            $updateVoucherStmt->execute();
        } else {
            // Handle case where the voucher is not found or not active
            http_response_code(400);
            echo json_encode(['error' => 'Invalid voucher code or voucher is not active']);
            exit;
        }
    }

    // Create a record in the orders table
    $orderSql = "INSERT INTO orders (user_id, total_cost, address_id) VALUES (:user_id, :total_cost, :address_id)";
    $orderStmt = $pdo->prepare($orderSql);
    $orderStmt->bindParam(':user_id', $data['user_id']);
    $orderStmt->bindParam(':total_cost', $totalCost);
    $orderStmt->bindParam(':address_id', $data['address_id']);
    $orderStmt->execute();

    // Clear the shopping cart
    $clearCartStmt = $pdo->prepare("DELETE FROM shoppingcart WHERE user_id = :user_id");
    $clearCartStmt->bindParam(':user_id', $data['user_id']);

    if ($clearCartStmt->execute()) {
        echo json_encode(['success' => 'Checkout successful']);
    } else {
        throw new Exception('Failed to clear shopping cart');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
