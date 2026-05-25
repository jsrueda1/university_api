<?php
// ── obtener_estudiante.php ────────────────────────────────
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// ── CONEXIÓN ──────────────────────────────────────────────
$host   = 'localhost';
$db     = 'university';
$user   = 'root';
$pass   = '123456789';

$conn = new mysqli($host, $user, $pass, $db);
$conn->set_charset('utf8mb4');

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión: ' . $conn->connect_error]);
    exit;
}

// ── PARÁMETRO ─────────────────────────────────────────────
$codigo = isset($_GET['codigo']) ? trim($_GET['codigo']) : '';

if (empty($codigo)) {
    http_response_code(400);
    echo json_encode(['error' => 'Parámetro codigo requerido']);
    exit;
}

// ── CONSULTA ──────────────────────────────────────────────
$stmt = $conn->prepare('SELECT * FROM estudiantes WHERE codigo = ? LIMIT 1');
$stmt->bind_param('s', $codigo);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Estudiante no encontrado']);
    exit;
}

$row = $result->fetch_assoc();

// ── URL DE LA FOTO ────────────────────────────────────────
// Si tiene foto, construye la URL; si no, devuelve null
$foto_url = null;
if (!empty($row['foto'])) {
    $protocolo = (!empty($_SERVER['HTTPS'])) ? 'https' : 'http';
    $host_url  = $_SERVER['HTTP_HOST'];
    $foto_url  = "$protocolo://$host_url/uploads/fotos_estudiantes/" . $row['foto'];
}

// ── RESPUESTA ─────────────────────────────────────────────
echo json_encode([
    'id'          => $row['id'],
    'codigo'      => $row['codigo'],
    'nombre'      => $row['nombre'],
    'apellido'    => $row['apellido'],
    'programa'    => $row['programa'],
    'facultad'    => $row['facultad'],
    'semestre'    => $row['semestre'],
    'tipo_sangre' => $row['tipo_sangre'],
    'eps'         => $row['eps'],
    'vigencia'    => $row['vigencia'],
    'foto'        => $foto_url,
]);

$stmt->close();
$conn->close();
?>