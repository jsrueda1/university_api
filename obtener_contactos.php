<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$conn = new mysqli('localhost', 'root', '123456789', 'university');
$conn->set_charset('utf8mb4');

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión']);
    exit;
}

$result = $conn->query('SELECT * FROM contactos ORDER BY id ASC');

$contactos = [];
while ($row = $result->fetch_assoc()) {
    $contactos[] = $row;
}

echo json_encode($contactos);
$conn->close();
?>