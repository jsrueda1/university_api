<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

$conn = new mysqli("localhost", "root", "123456789", "university", "3306");

$salon_id    = $_GET['salon_id'] ?? '';
$fecha       = $_GET['fecha'] ?? '';
$hora_inicio = $_GET['hora_inicio'] ?? '';
$hora_fin    = $_GET['hora_fin'] ?? '';

$sql = "SELECT COUNT(*) as total FROM reservas 
        WHERE salon_id = '$salon_id' 
        AND fecha = '$fecha' 
        AND estado = 'activa'
        AND (
            (hora_inicio < '$hora_fin' AND hora_fin > '$hora_inicio')
        )";

$result = $conn->query($sql);
$row = $result->fetch_assoc();

echo json_encode([
    "disponible" => $row['total'] == 0
]);
?>