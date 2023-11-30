<?php
// http://localhost/diss&miss/submitReview.php
// {
//    "product_id": 0,
//    "user_id": 0,
//    "rating": 5,
//    "review_text": "Great product!"
// }

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

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['product_id'], $data['user_id'], $data['rating'], $data['review_text'], $data['token'])) {
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

$product_id = (int)$data['product_id'];

$sql = "INSERT INTO productreview (product_id, user_id, rating, review_text) VALUES (:product_id, :user_id, :rating, :review_text)";
$stmt = $pdo->prepare($sql);

$stmt->bindParam(':product_id', $product_id);
$stmt->bindParam(':user_id', $data['user_id']);
$stmt->bindParam(':rating', $data['rating']);
$stmt->bindParam(':review_text', $data['review_text']);

if ($stmt->execute()) {
    echo json_encode(['success' => 'Review submitted successfully']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to submit review']);
}
?>
