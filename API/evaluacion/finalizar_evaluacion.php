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

    mysqli_close($conexion);
    exit;
}

if (!isset($_POST['id_evaluacion']) || empty($_POST['id_evaluacion'])) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'Se requiere id_evaluacion'
    ], JSON_UNESCAPED_UNICODE);

    mysqli_close($conexion);
    exit;
}

$chat_id = trim($_POST['chat_id']);
$id_evaluacion = intval($_POST['id_evaluacion']);


/*
|--------------------------------------------------------------------------
| FINALIZAR EVALUACIÓN
|--------------------------------------------------------------------------
|
| Solo se actualiza si:
| - la evaluación pertenece al chat
| - está activa
|
*/

$sql = "
    UPDATE evaluacion e
    INNER JOIN estudiante s
        ON e.id_estudiante = s.id_estudiante
    SET
        e.estado_conversacion = 'finalizada',
        e.fecha_fin = NOW(),
        e.estado = 'Finalizada'
    WHERE e.id_evaluacion = ?
      AND s.chat_id_telegram = ?
      AND e.estado = 'Activa'
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
    "is",
    $id_evaluacion,
    $chat_id
);


if (!mysqli_stmt_execute($stmt)) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'No se pudo finalizar la evaluación'
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
    'mensaje' => 'Evaluación finalizada correctamente',
    'id_evaluacion' => $id_evaluacion,
    'estado_conversacion' => 'finalizada',
    'estado' => 'Finalizada',
    'filas_afectadas' => $filas_afectadas
], JSON_UNESCAPED_UNICODE);

?>