<?php
// Los datos de conexión vienen de variables de entorno (configuradas en Railway).
// Si no existen, usa valores para XAMPP local.
$host     = getenv('DB_HOST')     ?: '127.0.0.1';
$port     = (int)(getenv('DB_PORT') ?: 3306);
$user     = getenv('DB_USER')     ?: 'root';
$password = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : '';
$database = getenv('DB_NAME')     ?: 'university';

try {
    $conn = new mysqli($host, $user, $password, $database, $port);
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Error de conexión: ' . $e->getMessage()]);
    exit;
}

$conn->set_charset('utf8mb4');
?>