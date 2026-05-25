<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json');

require_once 'conexion.php';

$resultado = $conn->query("SELECT * FROM noticias ORDER BY id DESC");
$datos = [];
while ($fila = $resultado->fetch_assoc()) {
    $datos[] = $fila;
}

echo json_encode($datos);
$conn->close();
?>