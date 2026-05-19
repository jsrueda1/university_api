<?php

header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json');

$conexion = new mysqli(
    "localhost",
    "root",
    "123456789",
    "university",
    "3306"
);

if ($conexion->connect_error) {
    die("Error de conexión");
}

$sql = "SELECT * FROM noticias";

$resultado = $conexion->query($sql);

$datos = [];

while($fila = $resultado->fetch_assoc()) {
    $datos[] = $fila;
}

echo json_encode($datos);

?>