<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $data = json_decode(file_get_contents("php://input"));

    $username = $data->username;
    $email = $data->email;
    $password = $data->password;

    $conn = new mysqli("sql207.infinityfree.com", "if0_35612830", "Jft20@hazem", "if0_35612830_diss_miss");

    if ($conn->connect_error) {
        die(json_encode(["success" => false, "message" => "Connection failed: " . $conn->connect_error]));
    }

    // Check if username or email already exists
    $stmt = $conn->prepare("SELECT user_id FROM Users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $stmt->close();
        $conn->close();
        echo json_encode(["success" => false, "message" => "Username or email already exists"]);
    } else {
        // Insert new user
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO Users (username, email, password, registration_date) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("sss", $username, $email, $hashedPassword);
        $stmt->execute();

        $stmt->close();
        $conn->close();
        echo json_encode(["success" => true, "message" => "Registration successful"]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid request method"]);
}
?>
