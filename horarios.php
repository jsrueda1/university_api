<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'conexion.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['usuario_id'])) {
            obtenerHorario($conn, (int)$_GET['usuario_id']);
        } else {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'usuario_id requerido']);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        if (isset($data['action']) && $data['action'] === 'inscribir') {
            inscribirHorario($conn, $data);
        } else {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Acción no reconocida']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
}

// =============================================================================
// OBTENER HORARIO DEL ESTUDIANTE agrupado por día
// =============================================================================
function obtenerHorario($conn, $usuario_id) {
    $sql = "
        SELECT
            h.id,
            h.materia,
            h.docente,
            h.dia_semana,
            h.hora_inicio,
            h.hora_fin,
            s.nombre    AS salon_nombre,
            s.ubicacion AS salon_ubicacion
        FROM horario_estudiante he
        JOIN horarios h ON he.clase_id = h.id
        LEFT JOIN salones s ON h.salon_id = s.id
        WHERE he.usuario_id = ?
        ORDER BY
            FIELD(h.dia_semana,
                'Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'),
            h.hora_inicio
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $agrupado = [];
    while ($row = $result->fetch_assoc()) {
        $dia = $row['dia_semana'];
        if (!isset($agrupado[$dia])) {
            $agrupado[$dia] = [];
        }
        $agrupado[$dia][] = [
            'id'              => (int)$row['id'],
            'materia'         => $row['materia'],
            'docente'         => $row['docente'],
            'dia_semana'      => $row['dia_semana'],
            'hora_inicio'     => $row['hora_inicio'],
            'hora_fin'        => $row['hora_fin'],
            'salon_nombre'    => $row['salon_nombre']    ?? 'Sin asignar',
            'salon_ubicacion' => $row['salon_ubicacion'] ?? '',
        ];
    }

    $stmt->close();
    $conn->close();

    echo json_encode([
        'status'  => 'success',
        'horario' => $agrupado,
        'total'   => array_sum(array_map('count', $agrupado)),
    ]);
}

// =============================================================================
// INSCRIBIR ESTUDIANTE A UNA CLASE
// =============================================================================
function inscribirHorario($conn, $data) {
    $usuario_id = (int)($data['usuario_id'] ?? 0);
    $clase_id   = (int)($data['clase_id']   ?? 0);

    if (!$usuario_id || !$clase_id) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'usuario_id y clase_id requeridos']);
        return;
    }

    // Verificar que no esté ya inscrito
    $check = $conn->prepare("SELECT 1 FROM horario_estudiante WHERE usuario_id = ? AND clase_id = ?");
    $check->bind_param('ii', $usuario_id, $clase_id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $check->close();
        echo json_encode(['status' => 'error', 'message' => 'Ya estás inscrito en esta clase']);
        return;
    }
    $check->close();

    $stmt = $conn->prepare("INSERT INTO horario_estudiante (usuario_id, clase_id) VALUES (?, ?)");
    $stmt->bind_param('ii', $usuario_id, $clase_id);
    $ok = $stmt->execute();
    $stmt->close();
    $conn->close();

    echo json_encode([
        'status'  => $ok ? 'success' : 'error',
        'message' => $ok ? 'Inscrito correctamente' : 'Error al inscribir',
    ]);
}
?>