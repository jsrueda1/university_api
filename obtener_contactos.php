<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'conexion.php';

$result    = $conn->query('SELECT * FROM contactos ORDER BY id ASC');
$contactos = [];
while ($row = $result->fetch_assoc()) {
    $contactos[] = $row;
}

echo json_encode($contactos);
$conn->close();
?>