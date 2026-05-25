<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once 'conexion.php';

$salon_id    = $_GET['salon_id']    ?? '';
$fecha       = $_GET['fecha']       ?? '';
$hora_inicio = $_GET['hora_inicio'] ?? '';
$hora_fin    = $_GET['hora_fin']    ?? '';

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM reservas 
    WHERE salon_id = ? AND fecha = ? AND estado = 'activa'
    AND (hora_inicio < ? AND hora_fin > ?)");
$stmt->bind_param("ssss", $salon_id, $fecha, $hora_fin, $hora_inicio);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

echo json_encode(["disponible" => $row['total'] == 0]);
$conn->close();
?>