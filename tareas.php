<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'conexion.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    // ── Listar tareas del estudiante ─────────────────────────────────────────
    case 'GET':
        if (isset($_GET['usuario_id'])) {
            listarTareas($conn, $_GET['usuario_id']);
        } else {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'usuario_id requerido']);
        }
        break;

    // ── Crear tarea (profesor) / Marcar completada (estudiante) ─────────────
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $action = $data['action'] ?? '';

        if ($action === 'crear') {
            crearTarea($conn, $data);
        } elseif ($action === 'completar') {
            completarTarea($conn, $data);
        } elseif ($action === 'asignar') {
            asignarTarea($conn, $data);
        } else {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Acción no reconocida']);
        }
        break;

    // ── Eliminar tarea ───────────────────────────────────────────────────────
    case 'DELETE':
        $data = json_decode(file_get_contents('php://input'), true);
        eliminarTarea($conn, $data);
        break;

    default:
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
}

// =============================================================================
// LISTAR TAREAS DEL ESTUDIANTE
// =============================================================================
function listarTareas($conn, $usuario_id) {
    $sql = "
        SELECT
            t.id,
            t.titulo,
            t.descripcion,
            t.materia,
            t.tipo,
            t.fecha_limite,
            t.creado_por,
            t.created_at,
            COALESCE(te.completada, 0)    AS completada,
            te.completed_at
        FROM tareas t
        LEFT JOIN tareas_estudiante te
            ON te.tarea_id = t.id AND te.usuario_id = ?
        ORDER BY t.fecha_limite ASC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $tareas = [];
    while ($row = $result->fetch_assoc()) {
        $tareas[] = [
            'id'           => (int)$row['id'],
            'titulo'       => $row['titulo'],
            'descripcion'  => $row['descripcion'] ?? '',
            'materia'      => $row['materia']      ?? '',
            'tipo'         => $row['tipo'],
            'fecha_limite' => $row['fecha_limite'],
            'creado_por'   => $row['creado_por']   ?? '',
            'completada'   => (bool)$row['completada'],
            'completed_at' => $row['completed_at'],
            'created_at'   => $row['created_at'],
        ];
    }

    $stmt->close();
    $conn->close();

    echo json_encode([
        'status' => 'success',
        'tareas' => $tareas,
        'total'  => count($tareas),
    ], JSON_UNESCAPED_UNICODE);
}

// =============================================================================
// CREAR TAREA (profesor)
// =============================================================================
function crearTarea($conn, $data) {
    $titulo       = $data['titulo']       ?? '';
    $descripcion  = $data['descripcion']  ?? '';
    $materia      = $data['materia']      ?? '';
    $tipo         = $data['tipo']         ?? 'entrega';
    $fecha_limite = $data['fecha_limite'] ?? '';
    $creado_por   = $data['creado_por']   ?? '';
    $clase_id     = isset($data['clase_id']) ? (int)$data['clase_id'] : null;

    if (empty($titulo) || empty($fecha_limite)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'titulo y fecha_limite requeridos']);
        return;
    }

    if ($clase_id) {
        $stmt = $conn->prepare(
            "INSERT INTO tareas (titulo, descripcion, materia, tipo, fecha_limite, clase_id, creado_por)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('sssssss', $titulo, $descripcion, $materia, $tipo, $fecha_limite, $clase_id, $creado_por);
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO tareas (titulo, descripcion, materia, tipo, fecha_limite, creado_por)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('ssssss', $titulo, $descripcion, $materia, $tipo, $fecha_limite, $creado_por);
    }

    $ok = $stmt->execute();
    $new_id = $conn->insert_id;
    $stmt->close();

    if ($ok && isset($data['usuarios']) && is_array($data['usuarios'])) {
        asignarAUsuarios($conn, $new_id, $data['usuarios']);
    }

    $conn->close();

    echo json_encode([
        'status'  => $ok ? 'success' : 'error',
        'message' => $ok ? 'Tarea creada' : 'Error al crear',
        'id'      => $ok ? $new_id : null,
    ], JSON_UNESCAPED_UNICODE);
}

// =============================================================================
// ASIGNAR TAREA A ESTUDIANTES
// =============================================================================
function asignarAUsuarios($conn, $tarea_id, $usuarios) {
    $stmt = $conn->prepare(
        "INSERT IGNORE INTO tareas_estudiante (tarea_id, usuario_id) VALUES (?, ?)"
    );
    foreach ($usuarios as $uid) {
        $stmt->bind_param('is', $tarea_id, $uid);
        $stmt->execute();
    }
    $stmt->close();
}

function asignarTarea($conn, $data) {
    $tarea_id  = (int)($data['tarea_id']  ?? 0);
    $usuario_id = $data['usuario_id'] ?? '';

    if (!$tarea_id || !$usuario_id) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'tarea_id y usuario_id requeridos']);
        return;
    }

    $stmt = $conn->prepare(
        "INSERT IGNORE INTO tareas_estudiante (tarea_id, usuario_id) VALUES (?, ?)"
    );
    $stmt->bind_param('is', $tarea_id, $usuario_id);
    $ok = $stmt->execute();
    $stmt->close();
    $conn->close();

    echo json_encode([
        'status'  => $ok ? 'success' : 'error',
        'message' => $ok ? 'Tarea asignada' : 'Error al asignar',
    ], JSON_UNESCAPED_UNICODE);
}

// =============================================================================
// MARCAR TAREA COMO COMPLETADA
// =============================================================================
function completarTarea($conn, $data) {
    $tarea_id   = (int)($data['tarea_id']   ?? 0);
    $usuario_id = $data['usuario_id'] ?? '';

    if (!$tarea_id || !$usuario_id) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'tarea_id y usuario_id requeridos']);
        return;
    }

    // Upsert: si no existe la fila la crea, si existe actualiza
    $stmt = $conn->prepare("
        INSERT INTO tareas_estudiante (tarea_id, usuario_id, completada, completed_at)
        VALUES (?, ?, 1, NOW())
        ON DUPLICATE KEY UPDATE completada = 1, completed_at = NOW()
    ");
    $stmt->bind_param('is', $tarea_id, $usuario_id);
    $ok = $stmt->execute();
    $stmt->close();
    $conn->close();

    echo json_encode([
        'status'  => $ok ? 'success' : 'error',
        'message' => $ok ? 'Tarea completada' : 'Error',
    ], JSON_UNESCAPED_UNICODE);
}

// =============================================================================
// ELIMINAR TAREA
// =============================================================================
function eliminarTarea($conn, $data) {
    $tarea_id = (int)($data['tarea_id'] ?? 0);

    if (!$tarea_id) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'tarea_id requerido']);
        return;
    }

    $conn->prepare("DELETE FROM tareas_estudiante WHERE tarea_id = ?")->execute() ;
    $stmt = $conn->prepare("DELETE FROM tareas WHERE id = ?");
    $stmt->bind_param('i', $tarea_id);
    $ok = $stmt->execute();
    $stmt->close();
    $conn->close();

    echo json_encode([
        'status'  => $ok ? 'success' : 'error',
        'message' => $ok ? 'Tarea eliminada' : 'Error al eliminar',
    ], JSON_UNESCAPED_UNICODE);
}
?>