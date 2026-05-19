<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

$conn = new mysqli("localhost", "root", "123456789", "university", "3306");

if ($conn->connect_error) {
    die(json_encode(["error" => "Conexión fallida"]));
}

$piso = isset($_GET['piso']) ? intval($_GET['piso']) : 0;

$result = $conn->query("SELECT * FROM salones WHERE piso = $piso");

$salones = [];
while ($row = $result->fetch_assoc()) {
    $salones[] = $row;
}

echo json_encode($salones);
?>