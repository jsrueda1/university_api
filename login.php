<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { http_response_code(200); exit(); }

require_once 'conexion.php';

$data     = json_decode(file_get_contents("php://input"));
$codigo   = $data->usuario  ?? '';
$password = $data->password ?? '';

if (empty($codigo) || empty($password)) {
    echo json_encode(["success" => false, "mensaje" => "Campos requeridos"]);
    exit();
}

// Ya NO comparamos con MD5 en el SQL, solo buscamos por código
$stmt = $conn->prepare(
    "SELECT id, codigo, nombre, apellido, programa,
            facultad, semestre, tipo_sangre, eps, vigencia, foto, password
     FROM estudiantes
     WHERE codigo = ?
     LIMIT 1"
);
$stmt->bind_param("s", $codigo);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $e = $result->fetch_assoc();

    // Verificamos la contraseña con password_verify (compatible con bcrypt)
    if (password_verify($password, $e['password'])) {
        echo json_encode([
            "success"    => true,
            "mensaje"    => "Login exitoso",
            "id"         => (int)$e['id'],
            "codigo"     => $e['codigo'],
            "nombre"     => $e['nombre'],
            "apellido"   => $e['apellido'],
            "programa"   => $e['programa'],
            "facultad"   => $e['facultad']    ?? '',
            "semestre"   => $e['semestre']    ?? '',
            "tipo_sangre"=> $e['tipo_sangre'] ?? '',
            "eps"        => $e['eps']         ?? '',
            "vigencia"   => $e['vigencia']    ?? '',
            "foto"       => $e['foto']        ?? '',
        ]);
    } else {
        echo json_encode(["success" => false, "mensaje" => "Código o contraseña incorrectos"]);
    }
} else {
    echo json_encode(["success" => false, "mensaje" => "Código o contraseña incorrectos"]);
}

$stmt->close();
$conn->close();
?>