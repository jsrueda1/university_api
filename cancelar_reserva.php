<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

$conn = new mysqli("localhost", "root", "123456789", "university", "3306");

$data = json_decode(file_get_contents("php://input"));
$id      = $data->id ?? 0;
$usuario = $data->usuario ?? '';

$sql = "UPDATE reservas SET estado = 'cancelada' 
        WHERE id = $id AND usuario = '$usuario'";

$ok = $conn->query($sql);

echo json_encode([
    "success" => $ok && $conn->affected_rows > 0
]);
?>