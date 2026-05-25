<?php
// ── subir_foto.php ────────────────────────────────────────
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

// ── CARPETA FUERA DEL PROYECTO ────────────────────────────
// Ajusta esta ruta si tu XAMPP está en otra ubicación
$carpeta = 'C:/xampp/uploads/fotos_estudiantes/';

// ── CONEXIÓN ──────────────────────────────────────────────
$conn = new mysqli('localhost', 'root', '123456789', 'university');
$conn->set_charset('utf8mb4');

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión']);
    exit;
}

// ── VALIDAR PARÁMETROS ────────────────────────────────────
$codigo = isset($_POST['codigo']) ? trim($_POST['codigo']) : '';

if (empty($codigo)) {
    http_response_code(400);
    echo json_encode(['error' => 'Parámetro codigo requerido']);
    exit;
}

if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'No se recibió ninguna foto']);
    exit;
}

// ── VALIDAR TIPO Y TAMAÑO ─────────────────────────────────
$tipos_permitidos = ['image/jpeg', 'image/png', 'image/webp'];
$tipo             = mime_content_type($_FILES['foto']['tmp_name']);
$tamano_max       = 5 * 1024 * 1024; // 5 MB

if (!in_array($tipo, $tipos_permitidos)) {
    http_response_code(400);
    echo json_encode(['error' => 'Solo se permiten imágenes JPG, PNG o WEBP']);
    exit;
}

if ($_FILES['foto']['size'] > $tamano_max) {
    http_response_code(400);
    echo json_encode(['error' => 'La imagen no puede superar 5 MB']);
    exit;
}

// ── CREAR CARPETA SI NO EXISTE ────────────────────────────
if (!is_dir($carpeta)) {
    mkdir($carpeta, 0755, true);
}

// ── GUARDAR ARCHIVO ───────────────────────────────────────
$extension  = ($tipo === 'image/png') ? 'png' : (($tipo === 'image/webp') ? 'webp' : 'jpg');
$nombre_archivo = $codigo . '.' . $extension;
$ruta_destino   = $carpeta . $nombre_archivo;

if (!move_uploaded_file($_FILES['foto']['tmp_name'], $ruta_destino)) {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo guardar la imagen']);
    exit;
}

// ── ACTUALIZAR BD ─────────────────────────────────────────
$stmt = $conn->prepare('UPDATE estudiantes SET foto = ? WHERE codigo = ?');
$stmt->bind_param('ss', $nombre_archivo, $codigo);
$stmt->execute();

if ($stmt->affected_rows === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Estudiante no encontrado en la BD']);
    exit;
}

// ── RESPUESTA ─────────────────────────────────────────────
echo json_encode([
    'mensaje'  => 'Foto subida correctamente',
    'archivo'  => $nombre_archivo,
]);

$stmt->close();
$conn->close();
?>