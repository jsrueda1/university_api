<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'conexion.php';

$codigo = isset($_GET['codigo']) ? trim($_GET['codigo']) : '';

if (empty($codigo)) {
    http_response_code(400);
    echo json_encode(['error' => 'Parámetro codigo requerido']);
    exit;
}

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

$foto_url = null;
if (!empty($row['foto'])) {
    $protocolo = (!empty($_SERVER['HTTPS'])) ? 'https' : 'http';
    $host_url  = $_SERVER['HTTP_HOST'];
    $foto_url  = "$protocolo://$host_url/uploads/fotos_estudiantes/" . $row['foto'];
}

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