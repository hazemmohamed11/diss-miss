<?php
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $data = json_decode(file_get_contents("php://input"));
    $username = $data->username;
    $email = $data->email;
    $password = $data->password;

    $conn = new mysqli("localhost", "root", "", "diss&miss");

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("SELECT user_id FROM Users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(["success" => false, "message" => "Username or email already exists"]);
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO Users (username, email, password, registration_date) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("sss", $username, $email, $hashedPassword);
        $stmt->execute();

        echo json_encode(["success" => true, "message" => "Registration successful"]);
    }

    $stmt->close();
    $conn->close();
}
?>
