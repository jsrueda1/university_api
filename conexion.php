<?php

$host     = 'tokaido.proxy.rlwy.net';
$port     = 39816;
$user     = 'root';
$password = 'bdIAkuAwvIbcPKPTxoZPsKzsfAHMoIQG';
$database = 'railway';

$conn = new mysqli($host, $user, $password, $database, $port);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Error de conexión: ' . $conn->connect_error
    ]);
    exit;
}

$conn->set_charset('utf8mb4');

?>