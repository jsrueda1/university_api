<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once 'conexion.php';

$usuario = $_GET['usuario'] ?? '';

$stmt = $conn->prepare("SELECT r.*, s.nombre as salon_nombre 
    FROM reservas r JOIN salones s ON r.salon_id = s.id
    WHERE r.usuario = ?
    ORDER BY r.fecha DESC, r.hora_inicio DESC");
$stmt->bind_param("s", $usuario);
$stmt->execute();
$result   = $stmt->get_result();
$reservas = [];

while ($row = $result->fetch_assoc()) {
    $reservas[] = $row;
}

echo json_encode($reservas);
$conn->close();
?>