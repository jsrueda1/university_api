<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

$conn = new mysqli("localhost", "root", "123456789", "university", "3306");

$usuario = $_GET['usuario'] ?? '';

$sql = "SELECT r.*, s.nombre as salon_nombre 
        FROM reservas r
        JOIN salones s ON r.salon_id = s.id
        WHERE r.usuario = '$usuario'
        ORDER BY r.fecha DESC, r.hora_inicio DESC";

$result = $conn->query($sql);
$reservas = [];

while ($row = $result->fetch_assoc()) {
    $reservas[] = $row;
}

echo json_encode($reservas);
?>