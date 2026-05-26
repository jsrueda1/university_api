<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once 'conexion.php';

$codigo = $_GET['codigo'] ?? '';

if (empty($codigo)) {
    echo json_encode(["error" => "Falta el codigo"]);
    exit();
}

$sql = "SELECT c.id, c.nombre, c.docente, c.salon_id,
               c.dia_semana, c.hora_inicio, c.hora_fin
        FROM clases c
        INNER JOIN horario_estudiante he ON c.id = he.clase_id
        WHERE he.usuario_id = '$codigo'
        ORDER BY c.dia_semana, c.hora_inicio";

$result = $conn->query($sql);
$horario = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $horario[] = $row;
    }
}

echo json_encode($horario);
$conn->close();
?>