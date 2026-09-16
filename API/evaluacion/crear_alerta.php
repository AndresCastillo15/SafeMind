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

$chat_id = trim($_POST['chat_id']);
$id_analisis = intval($_POST['id_analisis']);


/*
|--------------------------------------------------------------------------
| VERIFICAR QUE EL ANÁLISIS PERTENEZCA AL CHAT
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        ai.id_analisis,
        ai.id_nivel,
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

if (!mysqli_stmt_execute($stmt)) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'Error al verificar el análisis'
    ], JSON_UNESCAPED_UNICODE);

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    exit;
}

mysqli_stmt_bind_result(
    $stmt,
    $analisis_id,
    $id_nivel,
    $id_evaluacion
);

if (!mysqli_stmt_fetch($stmt)) {

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
| SOLO CREAR ALERTA PARA NIVELES 3 Y 4
|--------------------------------------------------------------------------
*/

if ($id_nivel < 3) {

    mysqli_close($conexion);

    echo json_encode([
        'success' => true,
        'alerta_creada' => false,
        'mensaje' => 'El nivel de riesgo no requiere una alerta automática',
        'id_analisis' => $id_analisis,
        'id_evaluacion' => $id_evaluacion,
        'id_nivel' => $id_nivel
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


/*
|--------------------------------------------------------------------------
| VERIFICAR SI YA EXISTE UNA ALERTA
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT id_alerta, estado, fecha
    FROM alerta
    WHERE id_analisis = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conexion, $sql);

if (!$stmt) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'Error al verificar alerta existente'
    ], JSON_UNESCAPED_UNICODE);

    mysqli_close($conexion);
    exit;
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id_analisis
);

mysqli_stmt_execute($stmt);

mysqli_stmt_bind_result(
    $stmt,
    $id_alerta_existente,
    $estado_existente,
    $fecha_existente
);

if (mysqli_stmt_fetch($stmt)) {

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    echo json_encode([
        'success' => true,
        'alerta_creada' => false,
        'alerta_existente' => true,
        'mensaje' => 'Ya existe una alerta para este análisis',
        'id_alerta' => intval($id_alerta_existente),
        'id_analisis' => $id_analisis,
        'estado' => $estado_existente,
        'fecha' => $fecha_existente
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

mysqli_stmt_close($stmt);


/*
|--------------------------------------------------------------------------
| CREAR ALERTA
|--------------------------------------------------------------------------
*/

$estado = 'Pendiente';

$sql = "
    INSERT INTO alerta
    (
        id_analisis,
        estado
    )
    VALUES (?, ?)
";

$stmt = mysqli_prepare($conexion, $sql);

if (!$stmt) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'Error al preparar la creación de la alerta'
    ], JSON_UNESCAPED_UNICODE);

    mysqli_close($conexion);
    exit;
}

mysqli_stmt_bind_param(
    $stmt,
    "is",
    $id_analisis,
    $estado
);

if (!mysqli_stmt_execute($stmt)) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'No se pudo crear la alerta',
        'error' => mysqli_stmt_error($stmt)
    ], JSON_UNESCAPED_UNICODE);

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    exit;
}

$id_alerta = mysqli_insert_id($conexion);

mysqli_stmt_close($stmt);
mysqli_close($conexion);


/*
|--------------------------------------------------------------------------
| RESPUESTA
|--------------------------------------------------------------------------
*/

echo json_encode([
    'success' => true,
    'alerta_creada' => true,
    'id_alerta' => $id_alerta,
    'id_analisis' => $id_analisis,
    'id_evaluacion' => $id_evaluacion,
    'id_nivel' => $id_nivel,
    'estado' => $estado
], JSON_UNESCAPED_UNICODE);

?>