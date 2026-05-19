<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// RESPONDER PREFLIGHT (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

$conn = new mysqli(
    "localhost",
    "root",
    "123456789",
    "university",
    "3306"
);

$data = json_decode(file_get_contents("php://input"));

$usuario = $data->usuario ?? '';
$password = $data->password ?? '';

$sql = "SELECT * FROM usuarios 
        WHERE usuario='$usuario' 
        AND password='$password'";

$result = $conn->query($sql);

echo json_encode([
    "success" => $result && $result->num_rows > 0
]);

?>