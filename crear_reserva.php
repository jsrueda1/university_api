<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

$conn = new mysqli("localhost", "root", "123456789", "university", "3306");

$data = json_decode(file_get_contents("php://input"));

$salon_id    = $data->salon_id ?? '';
$usuario     = $data->usuario ?? '';
$fecha       = $data->fecha ?? '';
$hora_inicio = $data->hora_inicio ?? '';
$hora_fin    = $data->hora_fin ?? '';
$motivo      = $data->motivo ?? '';

// Verificar disponibilidad
$check = "SELECT COUNT(*) as total FROM reservas 
          WHERE salon_id = '$salon_id' 
          AND fecha = '$fecha' 
          AND estado = 'activa'
          AND (hora_inicio < '$hora_fin' AND hora_fin > '$hora_inicio')";

$result = $conn->query($check);
$row = $result->fetch_assoc();

if ($row['total'] > 0) {
    echo json_encode([
        "success" => false,
        "mensaje" => "El salón no está disponible en ese horario"
    ]);
    exit();
}

$sql = "INSERT INTO reservas 
        (salon_id, usuario, fecha, hora_inicio, hora_fin, motivo)
        VALUES ('$salon_id', '$usuario', '$fecha', '$hora_inicio', '$hora_fin', '$motivo')";

$ok = $conn->query($sql);

echo json_encode([
    "success" => $ok,
    "mensaje" => $ok ? "Reserva creada exitosamente" : "Error al crear reserva"
]);
?>