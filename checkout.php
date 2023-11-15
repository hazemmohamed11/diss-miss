<?php
// checkout.php

header('Content-Type: application/json');

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
if (!isset($data['user_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid data']);
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

    // If all checks pass, proceed with the checkout logic
    // ...

    // Update product quantities in the database after successful checkout
    foreach ($cartItems as $item) {
        $newStock = $item['stock'] - $item['quantity'];
        $updateSql = "UPDATE products SET stock = :new_stock WHERE product_id = :product_id";
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->bindParam(':new_stock', $newStock);
        $updateStmt->bindParam(':product_id', $item['product_id']);
        $updateStmt->execute();
    }

    // Create a record in the orders table
    $orderSql = "INSERT INTO orders (user_id, total_cost) VALUES (:user_id, :total_cost)";
    $orderStmt = $pdo->prepare($orderSql);
    $orderStmt->bindParam(':user_id', $data['user_id']);

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

    $orderStmt->bindParam(':total_cost', $totalCost);
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
