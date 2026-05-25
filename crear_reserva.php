<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { http_response_code(200); exit(); }

require_once 'conexion.php';

$data        = json_decode(file_get_contents("php://input"));
$salon_id    = $data->salon_id    ?? '';
$usuario     = $data->usuario     ?? '';
$fecha       = $data->fecha       ?? '';
$hora_inicio = $data->hora_inicio ?? '';
$hora_fin    = $data->hora_fin    ?? '';
$motivo      = $data->motivo      ?? '';

// Verificar disponibilidad
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM reservas 
    WHERE salon_id = ? AND fecha = ? AND estado = 'activa'
    AND (hora_inicio < ? AND hora_fin > ?)");
$stmt->bind_param("ssss", $salon_id, $fecha, $hora_fin, $hora_inicio);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if ($row['total'] > 0) {
    echo json_encode(["success" => false, "mensaje" => "El salón no está disponible en ese horario"]);
    exit();
}

$stmt2 = $conn->prepare("INSERT INTO reservas (salon_id, usuario, fecha, hora_inicio, hora_fin, motivo)
    VALUES (?, ?, ?, ?, ?, ?)");
$stmt2->bind_param("ssssss", $salon_id, $usuario, $fecha, $hora_inicio, $hora_fin, $motivo);
$ok = $stmt2->execute();

echo json_encode([
    "success" => $ok,
    "mensaje" => $ok ? "Reserva creada exitosamente" : "Error al crear reserva"
]);

$conn->close();
?>