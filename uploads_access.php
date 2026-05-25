<?php
// ── uploads_access.php ────────────────────────────────────
// Coloca este archivo en: C:/xampp/htdocs/university_api/uploads_access.php
// Úsalo para servir imágenes desde la carpeta externa de forma segura.
//
// Uso: GET /university_api/uploads_access.php?foto=2020150023.jpg
//
header('Access-Control-Allow-Origin: *');

$carpeta = 'C:/xampp/uploads/fotos_estudiantes/';
$archivo = isset($_GET['foto']) ? basename($_GET['foto']) : '';

if (empty($archivo)) {
    http_response_code(400);
    echo 'Parámetro foto requerido';
    exit;
}

$ruta = $carpeta . $archivo;

if (!file_exists($ruta)) {
    http_response_code(404);
    echo 'Imagen no encontrada';
    exit;
}

// Detectar tipo
$tipo = mime_content_type($ruta);
header('Content-Type: ' . $tipo);
header('Content-Length: ' . filesize($ruta));
readfile($ruta);
?>