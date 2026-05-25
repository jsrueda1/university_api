<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

$conn = new mysqli("localhost", "root", "123456789", "university", "3306");

$piso       = isset($_GET['piso'])       ? intval($_GET['piso'])       : 0;
$dia        = isset($_GET['dia'])        ? intval($_GET['dia'])        : 1;
$hora_actual = isset($_GET['hora'])      ? $_GET['hora']               : '00:00:00';

$result = $conn->query("SELECT * FROM salones WHERE piso = $piso");
$salones = [];

while ($salon = $result->fetch_assoc()) {
    $sid = $salon['id'];

    // Buscar si hay clase activa ahora
    $q = "SELECT materia, docente, hora_fin FROM horarios
          WHERE salon_id = '$sid'
          AND dia_semana = $dia
          AND hora_inicio <= '$hora_actual'
          AND hora_fin > '$hora_actual'
          LIMIT 1";

    $hr = $conn->query($q)->fetch_assoc();

    // Buscar próxima clase del día
    $q2 = "SELECT materia, hora_inicio FROM horarios
           WHERE salon_id = '$sid'
           AND dia_semana = $dia
           AND hora_inicio > '$hora_actual'
           ORDER BY hora_inicio ASC
           LIMIT 1";

    $proxima = $conn->query($q2)->fetch_assoc();

    if ($hr) {
        // Hay clase en este momento
        $salon['estado']       = 'ocupado';
        $salon['docente']      = $hr['docente'];
        $salon['proxima_clase']= 'Termina ' . substr($hr['hora_fin'], 0, 5);
    } else {
        // Libre — mostrar próxima clase si existe
        $tipo = $salon['tipo'];
        if (strpos(strtolower($tipo), 'laboratorio') !== false) {
            $salon['estado'] = 'laboratorio';
        } else {
            $salon['estado'] = 'libre';
        }

        if ($proxima) {
            $salon['proxima_clase'] = $proxima['materia'] . ' — ' 
                . substr($proxima['hora_inicio'], 0, 5);
        } else {
            $salon['proxima_clase'] = 'Sin más clases hoy';
        }
    }

    $salones[] = $salon;
}

echo json_encode($salones);
?>