<?php
//http://localhost/diss&miss/getProduct.php?productId=0
$host = 'localhost';
$dbname = 'diss&miss';
$user = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (isset($_GET['productId'])) {
        $productId = $_GET['productId'];

        $query = "SELECT * FROM products WHERE product_id = :productId";
        $statement = $pdo->prepare($query);
        $statement->bindParam(':productId', $productId, PDO::PARAM_INT);
        $statement->execute();

        $result = $statement->fetch(PDO::FETCH_ASSOC);

        echo json_encode($result);
    } else {
        echo json_encode(['error' => 'Product ID not provided']);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database connection error']);
}
?>


