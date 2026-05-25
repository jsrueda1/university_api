<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ── CONEXIÓN ──────────────────────────────────────────────
$conn = new mysqli("localhost", "root", "123456789", "university");
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    echo json_encode(["success" => false, "mensaje" => "Error de conexión"]);
    exit();
}

// ── DATOS DEL REQUEST ─────────────────────────────────────
$data     = json_decode(file_get_contents("php://input"));
$codigo   = $data->usuario   ?? '';
$password = $data->password  ?? '';

if (empty($codigo) || empty($password)) {
    echo json_encode(["success" => false, "mensaje" => "Campos requeridos"]);
    exit();
}

// ── CONSULTA SEGURA CON MD5 ───────────────────────────────
$stmt = $conn->prepare(
    "SELECT id, codigo, nombre, apellido, programa 
     FROM estudiantes 
     WHERE codigo = ? AND password = MD5(?) 
     LIMIT 1"
);
$stmt->bind_param("ss", $codigo, $password);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $estudiante = $result->fetch_assoc();
    echo json_encode([
        "success"  => true,
        "mensaje"  => "Login exitoso",
        "codigo"   => $estudiante['codigo'],
        "nombre"   => $estudiante['nombre'],
        "apellido" => $estudiante['apellido'],
        "programa" => $estudiante['programa'],
    ]);
} else {
    echo json_encode([
        "success" => false,
        "mensaje" => "Código o contraseña incorrectos"
    ]);
}

$stmt->close();
$conn->close();
?>