<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once 'conexion.php';

$piso        = isset($_GET['piso']) ? intval($_GET['piso']) : 0;
$dia         = isset($_GET['dia'])  ? intval($_GET['dia'])  : 1;
$hora_actual = isset($_GET['hora']) ? $_GET['hora']         : '00:00:00';

$result  = $conn->query("SELECT * FROM salones WHERE piso = $piso");
$salones = [];

while ($salon = $result->fetch_assoc()) {
    $sid = $salon['id'];

    $hr = $conn->query("SELECT materia, docente, hora_fin FROM horarios
        WHERE salon_id = '$sid' AND dia_semana = $dia
        AND hora_inicio <= '$hora_actual' AND hora_fin > '$hora_actual'
        LIMIT 1")->fetch_assoc();

    $proxima = $conn->query("SELECT materia, hora_inicio FROM horarios
        WHERE salon_id = '$sid' AND dia_semana = $dia
        AND hora_inicio > '$hora_actual'
        ORDER BY hora_inicio ASC LIMIT 1")->fetch_assoc();

    if ($hr) {
        $salon['estado']        = 'ocupado';
        $salon['docente']       = $hr['docente'];
        $salon['proxima_clase'] = 'Termina ' . substr($hr['hora_fin'], 0, 5);
    } else {
        $salon['estado'] = strpos(strtolower($salon['tipo']), 'laboratorio') !== false
            ? 'laboratorio' : 'libre';
        $salon['proxima_clase'] = $proxima
            ? $proxima['materia'] . ' — ' . substr($proxima['hora_inicio'], 0, 5)
            : 'Sin más clases hoy';
    }

    $salones[] = $salon;
}

echo json_encode($salones);
$conn->close();
?>