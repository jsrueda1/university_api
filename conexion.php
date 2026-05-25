<?php


$host     = 'zephyr.proxy.rlwy.net';
$port     = 27327;
$user     = 'root';
$password = 'WSPjexvOBDbxjEMWEFyDrPLiLeXVAKDC';
$database = 'railway';

$conn = new mysqli($host, $user, $password, $database, $port);
$conn->set_charset('utf8mb4');

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión: ' . $conn->connect_error]);
    exit;
}
?>