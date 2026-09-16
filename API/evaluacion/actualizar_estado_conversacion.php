<?php

header('Content-Type: application/json; charset=utf-8');

require_once '../config/conexion.php';

$conexion = conectar();


/*
|--------------------------------------------------------------------------
| VALIDAR DATOS
|--------------------------------------------------------------------------
*/

if (!isset($_POST['chat_id']) || empty($_POST['chat_id'])) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'Se requiere chat_id'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

if (!isset($_POST['id_evaluacion']) || empty($_POST['id_evaluacion'])) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'Se requiere id_evaluacion'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

if (!isset($_POST['estado_conversacion']) || empty($_POST['estado_conversacion'])) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'Se requiere estado_conversacion'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$chat_id = trim($_POST['chat_id']);
$id_evaluacion = intval($_POST['id_evaluacion']);
$estado_conversacion = trim($_POST['estado_conversacion']);


/*
|--------------------------------------------------------------------------
| VALIDAR ESTADOS PERMITIDOS
|--------------------------------------------------------------------------
*/

$estados_permitidos = [
    'esperando_descripcion',
    'en_conversacion',
    'finalizada'
];

if (!in_array($estado_conversacion, $estados_permitidos, true)) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'Estado de conversación no válido'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


/*
|--------------------------------------------------------------------------
| ACTUALIZAR SOLO SI LA EVALUACIÓN PERTENECE AL CHAT
|--------------------------------------------------------------------------
*/

$sql = "
    UPDATE evaluacion e
    INNER JOIN estudiante s
        ON e.id_estudiante = s.id_estudiante
    SET e.estado_conversacion = ?
    WHERE e.id_evaluacion = ?
      AND s.chat_id_telegram = ?
";

$stmt = mysqli_prepare($conexion, $sql);

if (!$stmt) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'Error al preparar la actualización'
    ], JSON_UNESCAPED_UNICODE);

    mysqli_close($conexion);
    exit;
}

mysqli_stmt_bind_param(
    $stmt,
    "sis",
    $estado_conversacion,
    $id_evaluacion,
    $chat_id
);

if (!mysqli_stmt_execute($stmt)) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'Error al actualizar el estado de conversación'
    ], JSON_UNESCAPED_UNICODE);

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    exit;
}

$filas_afectadas = mysqli_stmt_affected_rows($stmt);

mysqli_stmt_close($stmt);
mysqli_close($conexion);


/*
|--------------------------------------------------------------------------
| RESPUESTA
|--------------------------------------------------------------------------
*/

echo json_encode([
    'success' => true,
    'mensaje' => 'Estado de conversación actualizado correctamente',
    'id_evaluacion' => $id_evaluacion,
    'estado_conversacion' => $estado_conversacion,
    'filas_afectadas' => $filas_afectadas
], JSON_UNESCAPED_UNICODE);

?>