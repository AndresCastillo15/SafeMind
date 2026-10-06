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

if (!isset($_POST['id_analisis']) || empty($_POST['id_analisis'])) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'Se requiere id_analisis'
    ], JSON_UNESCAPED_UNICODE);

    mysqli_close($conexion);
    exit;
}

if (!isset($_POST['id_recurso']) || empty($_POST['id_recurso'])) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'Se requiere id_recurso'
    ], JSON_UNESCAPED_UNICODE);

    mysqli_close($conexion);
    exit;
}

$chat_id = trim($_POST['chat_id']);
$id_analisis = intval($_POST['id_analisis']);
$id_recurso = intval($_POST['id_recurso']);


/*
|--------------------------------------------------------------------------
| VERIFICAR QUE EL ANÁLISIS PERTENEZCA AL CHAT
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        ai.id_analisis,
        ai.id_evaluacion
    FROM analisis_ia ai
    INNER JOIN evaluacion e
        ON ai.id_evaluacion = e.id_evaluacion
    INNER JOIN estudiante s
        ON e.id_estudiante = s.id_estudiante
    WHERE ai.id_analisis = ?
      AND s.chat_id_telegram = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conexion, $sql);

if (!$stmt) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'Error al preparar la validación del análisis'
    ], JSON_UNESCAPED_UNICODE);

    mysqli_close($conexion);
    exit;
}

mysqli_stmt_bind_param(
    $stmt,
    "is",
    $id_analisis,
    $chat_id
);

mysqli_stmt_execute($stmt);

mysqli_stmt_store_result($stmt);

if (mysqli_stmt_num_rows($stmt) === 0) {

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    echo json_encode([
        'success' => false,
        'mensaje' => 'El análisis no pertenece al estudiante'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

mysqli_stmt_close($stmt);


/*
|--------------------------------------------------------------------------
| VERIFICAR QUE EL RECURSO EXISTA
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        id_recurso,
        titulo
    FROM recurso_apoyo
    WHERE id_recurso = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conexion, $sql);

if (!$stmt) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'Error al preparar la validación del recurso'
    ], JSON_UNESCAPED_UNICODE);

    mysqli_close($conexion);
    exit;
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id_recurso
);

mysqli_stmt_execute($stmt);

mysqli_stmt_bind_result(
    $stmt,
    $recurso_id,
    $titulo_recurso
);

if (!mysqli_stmt_fetch($stmt)) {

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    echo json_encode([
        'success' => false,
        'mensaje' => 'El recurso no existe'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

mysqli_stmt_close($stmt);


/*
|--------------------------------------------------------------------------
| VERIFICAR SI YA EXISTE LA RELACIÓN
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT id_analisis
    FROM analisis_recurso
    WHERE id_analisis = ?
      AND id_recurso = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conexion, $sql);

if (!$stmt) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'Error al verificar la relación existente'
    ], JSON_UNESCAPED_UNICODE);

    mysqli_close($conexion);
    exit;
}

mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $id_analisis,
    $id_recurso
);

mysqli_stmt_execute($stmt);

mysqli_stmt_store_result($stmt);

if (mysqli_stmt_num_rows($stmt) > 0) {

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    echo json_encode([
        'success' => true,
        'relacion_creada' => false,
        'relacion_existente' => true,
        'mensaje' => 'El recurso ya está relacionado con este análisis',
        'id_analisis' => $id_analisis,
        'id_recurso' => $id_recurso,
        'titulo_recurso' => $titulo_recurso
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

mysqli_stmt_close($stmt);


/*
|--------------------------------------------------------------------------
| CREAR RELACIÓN
|--------------------------------------------------------------------------
*/

$sql = "
    INSERT INTO analisis_recurso
    (
        id_analisis,
        id_recurso
    )
    VALUES (?, ?)
";

$stmt = mysqli_prepare($conexion, $sql);

if (!$stmt) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'Error al preparar la relación del recurso'
    ], JSON_UNESCAPED_UNICODE);

    mysqli_close($conexion);
    exit;
}

mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $id_analisis,
    $id_recurso
);

if (!mysqli_stmt_execute($stmt)) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'No se pudo guardar la relación',
        'error' => mysqli_stmt_error($stmt)
    ], JSON_UNESCAPED_UNICODE);

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    exit;
}

mysqli_stmt_close($stmt);
mysqli_close($conexion);


/*
|--------------------------------------------------------------------------
| RESPUESTA
|--------------------------------------------------------------------------
*/

echo json_encode([
    'success' => true,
    'relacion_creada' => true,
    'id_analisis' => $id_analisis,
    'id_recurso' => $id_recurso,
    'titulo_recurso' => $titulo_recurso,
    'mensaje' => 'Recurso relacionado con el análisis correctamente'
], JSON_UNESCAPED_UNICODE);

?>