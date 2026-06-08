<?php
// services/calificaciones_service.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { http_response_code(200); exit(); }

require_once 'conexion.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'obtener':  obtenerCalificaciones($conn); break;
    case 'guardar':  guardarNota($conn);           break;
    case 'resumen':  getResumen($conn);            break;
    default:
        echo json_encode(["success" => false, "mensaje" => "Acción no válida"]);
}

// =============================================
// GET ?action=obtener&estudiante_id=1&periodo=2026-1
// =============================================
function obtenerCalificaciones($conn) {
    $estudiante_id = $_GET['estudiante_id'] ?? '';
    $periodo       = $_GET['periodo']       ?? '2026-1';

    if (!$estudiante_id) {
        echo json_encode(["success" => false, "mensaje" => "estudiante_id es requerido"]);
        exit();
    }

    $stmt = $conn->prepare("
        SELECT 
            id, materia, periodo,
            parcial1, parcial2, parcial3,
            taller1, taller2, taller3, taller4,
            quiz1, quiz2, quiz3, quiz4,
            peso_parciales, peso_talleres, peso_quizes,
            promedio_parciales, promedio_talleres, promedio_quizes,
            nota_definitiva, nota_aprobacion, observaciones
        FROM calificaciones
        WHERE estudiante_id = ? AND periodo = ?
        ORDER BY materia ASC
    ");
    $stmt->bind_param("ss", $estudiante_id, $periodo);
    $stmt->execute();
    $resultado = $stmt->get_result();

    $data = [];
    while ($row = $resultado->fetch_assoc()) {
        $data[] = calcularCamposExtra($row);
    }

    echo json_encode(["success" => true, "data" => $data]);
    $stmt->close();
}

// =============================================
// POST ?action=guardar
// Body JSON: { estudiante_id, materia, periodo,
//              parcial1..3, taller1..4, quiz1..4,
//              peso_parciales, peso_talleres, peso_quizes,
//              nota_aprobacion, observaciones }
// =============================================
function guardarNota($conn) {
    $data = json_decode(file_get_contents("php://input"));

    $estudiante_id  = $data->estudiante_id  ?? '';
    $materia        = $data->materia        ?? '';
    $periodo        = $data->periodo        ?? '2026-1';

    if (!$estudiante_id || !$materia || !$periodo) {
        echo json_encode(["success" => false, "mensaje" => "estudiante_id, materia y periodo son requeridos"]);
        exit();
    }

    // Notas (pueden ser null si no están ingresadas aún)
    $parcial1 = isset($data->parcial1) && $data->parcial1 !== '' ? (float)$data->parcial1 : null;
    $parcial2 = isset($data->parcial2) && $data->parcial2 !== '' ? (float)$data->parcial2 : null;
    $parcial3 = isset($data->parcial3) && $data->parcial3 !== '' ? (float)$data->parcial3 : null;
    $taller1  = isset($data->taller1)  && $data->taller1  !== '' ? (float)$data->taller1  : null;
    $taller2  = isset($data->taller2)  && $data->taller2  !== '' ? (float)$data->taller2  : null;
    $taller3  = isset($data->taller3)  && $data->taller3  !== '' ? (float)$data->taller3  : null;
    $taller4  = isset($data->taller4)  && $data->taller4  !== '' ? (float)$data->taller4  : null;
    $quiz1    = isset($data->quiz1)    && $data->quiz1    !== '' ? (float)$data->quiz1    : null;
    $quiz2    = isset($data->quiz2)    && $data->quiz2    !== '' ? (float)$data->quiz2    : null;
    $quiz3    = isset($data->quiz3)    && $data->quiz3    !== '' ? (float)$data->quiz3    : null;
    $quiz4    = isset($data->quiz4)    && $data->quiz4    !== '' ? (float)$data->quiz4    : null;

    // Pesos
    $peso_parciales = (float)($data->peso_parciales ?? 60);
    $peso_talleres  = (float)($data->peso_talleres  ?? 20);
    $peso_quizes    = (float)($data->peso_quizes    ?? 20);

    // Nota mínima para aprobar
    $nota_aprobacion = (float)($data->nota_aprobacion ?? 3.0);
    $observaciones   = $data->observaciones ?? null;

    // Calcular promedios y definitiva en PHP
    $calc = calcularDefinitiva(
        $parcial1, $parcial2, $parcial3,
        $taller1,  $taller2,  $taller3, $taller4,
        $quiz1,    $quiz2,    $quiz3,   $quiz4,
        $peso_parciales, $peso_talleres, $peso_quizes
    );

    $prom_parciales = $calc['promedio_parciales'];
    $prom_talleres  = $calc['promedio_talleres'];
    $prom_quizes    = $calc['promedio_quizes'];
    $nota_definitiva = $calc['nota_definitiva'];

    // INSERT o UPDATE si ya existe el registro (mismo estudiante + materia + periodo)
    $stmt = $conn->prepare("
        INSERT INTO calificaciones
            (estudiante_id, materia, periodo,
             parcial1, parcial2, parcial3,
             taller1, taller2, taller3, taller4,
             quiz1, quiz2, quiz3, quiz4,
             peso_parciales, peso_talleres, peso_quizes,
             promedio_parciales, promedio_talleres, promedio_quizes,
             nota_definitiva, nota_aprobacion, observaciones)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            parcial1 = VALUES(parcial1), parcial2 = VALUES(parcial2), parcial3 = VALUES(parcial3),
            taller1  = VALUES(taller1),  taller2  = VALUES(taller2),
            taller3  = VALUES(taller3),  taller4  = VALUES(taller4),
            quiz1    = VALUES(quiz1),    quiz2    = VALUES(quiz2),
            quiz3    = VALUES(quiz3),    quiz4    = VALUES(quiz4),
            peso_parciales     = VALUES(peso_parciales),
            peso_talleres      = VALUES(peso_talleres),
            peso_quizes        = VALUES(peso_quizes),
            promedio_parciales = VALUES(promedio_parciales),
            promedio_talleres  = VALUES(promedio_talleres),
            promedio_quizes    = VALUES(promedio_quizes),
            nota_definitiva    = VALUES(nota_definitiva),
            nota_aprobacion    = VALUES(nota_aprobacion),
            observaciones      = VALUES(observaciones),
            actualizado_en     = CURRENT_TIMESTAMP
    ");

    $stmt->bind_param(
        "sssddddddddddddddddddds",
        $estudiante_id, $materia, $periodo,
        $parcial1, $parcial2, $parcial3,
        $taller1, $taller2, $taller3, $taller4,
        $quiz1, $quiz2, $quiz3, $quiz4,
        $peso_parciales, $peso_talleres, $peso_quizes,
        $prom_parciales, $prom_talleres, $prom_quizes,
        $nota_definitiva, $nota_aprobacion, $observaciones
    );

    $ok = $stmt->execute();

    echo json_encode([
        "success"         => $ok,
        "mensaje"         => $ok ? "Calificación guardada correctamente" : "Error: " . $conn->error,
        "nota_definitiva" => $nota_definitiva,
        "estado"          => $nota_definitiva !== null
            ? ($nota_definitiva >= $nota_aprobacion ? "aprobado" : "en_riesgo")
            : "en_curso"
    ]);

    $stmt->close();
}

// =============================================
// GET ?action=resumen&estudiante_id=1&periodo=2026-1
// =============================================
function getResumen($conn) {
    $estudiante_id = $_GET['estudiante_id'] ?? '';
    $periodo       = $_GET['periodo']       ?? '2026-1';

    $stmt = $conn->prepare("
        SELECT materia, nota_definitiva, nota_aprobacion
        FROM calificaciones
        WHERE estudiante_id = ? AND periodo = ?
    ");
    $stmt->bind_param("ss", $estudiante_id, $periodo);
    $stmt->execute();
    $resultado = $stmt->get_result();

    $total = 0; $aprobadas = 0; $en_riesgo = 0; $suma = 0.0;

    while ($row = $resultado->fetch_assoc()) {
        $total++;
        $def = (float)($row['nota_definitiva'] ?? 0);
        $apr = (float)($row['nota_aprobacion'] ?? 3.0);
        $suma += $def;
        if ($def >= $apr) $aprobadas++;
        elseif ($def > 0) $en_riesgo++;
    }

    echo json_encode([
        "success" => true,
        "data" => [
            "total_materias"   => $total,
            "aprobadas"        => $aprobadas,
            "en_riesgo"        => $en_riesgo,
            "promedio_general" => $total > 0 ? round($suma / $total, 2) : 0
        ]
    ]);
    $stmt->close();
}

// =============================================
// HELPERS
// =============================================

/**
 * Calcula promedio ignorando nulls
 */
function promedio(array $notas): ?float {
    $validas = array_filter($notas, fn($n) => $n !== null);
    if (empty($validas)) return null;
    return round(array_sum($validas) / count($validas), 2);
}

/**
 * Calcula promedios por categoría y nota definitiva ponderada
 */
function calcularDefinitiva(
    $p1, $p2, $p3,
    $t1, $t2, $t3, $t4,
    $q1, $q2, $q3, $q4,
    $peso_p, $peso_t, $peso_q
): array {
    $prom_p = promedio([$p1, $p2, $p3]);
    $prom_t = promedio([$t1, $t2, $t3, $t4]);
    $prom_q = promedio([$q1, $q2, $q3, $q4]);

    $definitiva = null;
    if ($prom_p !== null) {
        $definitiva = round(
            ($prom_p * $peso_p / 100) +
            (($prom_t ?? 0) * $peso_t / 100) +
            (($prom_q ?? 0) * $peso_q / 100),
            2
        );
    }

    return [
        'promedio_parciales' => $prom_p,
        'promedio_talleres'  => $prom_t,
        'promedio_quizes'    => $prom_q,
        'nota_definitiva'    => $definitiva,
    ];
}

/**
 * Agrega nota_requerida y estado a un row de DB
 */
function calcularCamposExtra(array $row): array {
    $nota_aprobacion = (float)($row['nota_aprobacion'] ?? 3.0);
    $nota_definitiva = $row['nota_definitiva'] !== null ? (float)$row['nota_definitiva'] : null;

    // Estado
    if ($nota_definitiva === null) {
        $row['estado'] = 'en_curso';
    } elseif ($nota_definitiva >= $nota_aprobacion) {
        $row['estado'] = 'aprobado';
    } else {
        $row['estado'] = 'en_riesgo';
    }

    // Nota requerida en parcial3 para aprobar (solo si parcial3 aún no tiene nota)
    $row['nota_requerida_aprobar'] = null;
    if ($row['parcial3'] === null && $row['parcial1'] !== null) {
        $peso_p = (float)($row['peso_parciales'] ?? 60) / 100;
        $peso_t = (float)($row['peso_talleres']  ?? 20) / 100;
        $peso_q = (float)($row['peso_quizes']    ?? 20) / 100;

        $prom_t = (float)($row['promedio_talleres'] ?? 0);
        $prom_q = (float)($row['promedio_quizes']   ?? 0);

        $suma_parciales = (float)$row['parcial1']
            + ($row['parcial2'] !== null ? (float)$row['parcial2'] : 0);

        // Despejar x: ((suma_parciales + x) / 3) * peso_p + resto = nota_aprobacion
        $resto = ($prom_t * $peso_t) + ($prom_q * $peso_q);
        if ($peso_p > 0) {
            $x = (($nota_aprobacion - $resto) * 3 / $peso_p) - $suma_parciales;
            $row['nota_requerida_aprobar'] = round(max(0, min(5, $x)), 2);
        }
    }

    return $row;
}

$conn->close();
?>