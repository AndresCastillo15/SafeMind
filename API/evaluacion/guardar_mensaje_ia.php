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

if (!isset($_POST['contenido']) || trim($_POST['contenido']) === '') {
    echo json_encode([
        'success' => false,
        'mensaje' => 'Se requiere contenido'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$chat_id = trim($_POST['chat_id']);
$id_evaluacion = intval($_POST['id_evaluacion']);
$contenido = trim($_POST['contenido']);


/*
|--------------------------------------------------------------------------
| VERIFICAR QUE LA EVALUACIÓN PERTENEZCA AL ESTUDIANTE
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT e.id_evaluacion
    FROM evaluacion e
    INNER JOIN estudiante s
        ON e.id_estudiante = s.id_estudiante
    WHERE e.id_evaluacion = ?
      AND s.chat_id_telegram = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conexion, $sql);

if (!$stmt) {
    echo json_encode([
        'success' => false,
        'mensaje' => 'Error al preparar la consulta'
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

mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);

if (mysqli_stmt_num_rows($stmt) === 0) {

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    echo json_encode([
        'success' => false,
        'mensaje' => 'La evaluación no pertenece al estudiante'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

mysqli_stmt_close($stmt);


/*
|--------------------------------------------------------------------------
| GUARDAR RESPUESTA DE LA IA
|--------------------------------------------------------------------------
*/

$remitente = 'ia';

$sql = "
    INSERT INTO mensaje
    (
        id_evaluacion,
        remitente,
        contenido
    )
    VALUES (?, ?, ?)
";

$stmt = mysqli_prepare($conexion, $sql);

if (!$stmt) {
    echo json_encode([
        'success' => false,
        'mensaje' => 'Error al preparar el registro del mensaje'
    ], JSON_UNESCAPED_UNICODE);

    mysqli_close($conexion);
    exit;
}

mysqli_stmt_bind_param(
    $stmt,
    "iss",
    $id_evaluacion,
    $remitente,
    $contenido
);

if (!mysqli_stmt_execute($stmt)) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'No se pudo guardar el mensaje de la IA'
    ], JSON_UNESCAPED_UNICODE);

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    exit;
}

$id_mensaje = mysqli_insert_id($conexion);

mysqli_stmt_close($stmt);
mysqli_close($conexion);


/*
|--------------------------------------------------------------------------
| RESPUESTA
|--------------------------------------------------------------------------
*/

echo json_encode([
    'success' => true,
    'mensaje' => 'Respuesta de IA guardada correctamente',
    'id_mensaje' => $id_mensaje,
    'id_evaluacion' => $id_evaluacion,
    'remitente' => 'ia',
    'contenido' => $contenido
], JSON_UNESCAPED_UNICODE);

?>