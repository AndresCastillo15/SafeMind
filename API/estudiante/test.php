<?php

// Indicar que la respuesta será en formato JSON
header('Content-Type: application/json');

// Evitar caché
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Respuesta de prueba
$response = [
    "success" => true,
    "mensaje" => "API funcionando correctamente",
    "version" => "1.0",
    "fecha" => date("Y-m-d H:i:s")
];

// Enviar respuesta
echo json_encode($response, JSON_PRETTY_PRINT);