<?php

header('Content-Type: application/json; charset=utf-8');

require_once '../config/conexion.php';

$conexion = conectar();


/*
|--------------------------------------------------------------------------
| VALIDAR DATOS
|--------------------------------------------------------------------------
*/

if (
    !isset($_POST['chat_id']) ||
    empty($_POST['chat_id']) ||
    !isset($_POST['id_evaluacion']) ||
    empty($_POST['id_evaluacion']) ||
    !isset($_POST['id_nivel']) ||
    $_POST['id_nivel'] === '' ||
    !isset($_POST['emocion']) ||
    trim($_POST['emocion']) === '' ||
    !isset($_POST['confianza']) ||
    $_POST['confianza'] === '' ||
    !isset($_POST['resumen']) ||
    trim($_POST['resumen']) === ''
) {
    echo json_encode([
        'success' => false,
        'mensaje' => 'Faltan datos requeridos para guardar el análisis'
    ], JSON_UNESCAPED_UNICODE);

    mysqli_close($conexion);
    exit;
}


$chat_id = trim($_POST['chat_id']);
$id_evaluacion = intval($_POST['id_evaluacion']);
$id_nivel = intval($_POST['id_nivel']);
$emocion = trim($_POST['emocion']);
$confianza = floatval($_POST['confianza']);
$resumen = trim($_POST['resumen']);


/*
|--------------------------------------------------------------------------
| VALIDAR NIVEL DE RIESGO
|--------------------------------------------------------------------------
*/

if ($id_nivel < 1 || $id_nivel > 4) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'El nivel de riesgo debe estar entre 1 y 4'
    ], JSON_UNESCAPED_UNICODE);

    mysqli_close($conexion);
    exit;
}


/*
|--------------------------------------------------------------------------
| VALIDAR CONFIANZA
|--------------------------------------------------------------------------
*/

if ($confianza < 0 || $confianza > 100) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'La confianza debe estar entre 0 y 100'
    ], JSON_UNESCAPED_UNICODE);

    mysqli_close($conexion);
    exit;
}


/*
|--------------------------------------------------------------------------
| VERIFICAR QUE LA EVALUACIÓN PERTENEZCA AL CHAT
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
        'mensaje' => 'Error al preparar la validación de la evaluación'
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
        'mensaje' => 'Error al verificar la evaluación'
    ], JSON_UNESCAPED_UNICODE);

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    exit;
}

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
| VERIFICAR QUE EL NIVEL DE RIESGO EXISTA
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT id_nivel, nombre
    FROM nivel_riesgo
    WHERE id_nivel = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conexion, $sql);

if (!$stmt) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'Error al preparar validación del nivel'
    ], JSON_UNESCAPED_UNICODE);

    mysqli_close($conexion);
    exit;
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id_nivel
);

mysqli_stmt_execute($stmt);

mysqli_stmt_bind_result(
    $stmt,
    $nivel_id,
    $nivel_nombre
);

if (!mysqli_stmt_fetch($stmt)) {

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    echo json_encode([
        'success' => false,
        'mensaje' => 'El nivel de riesgo no existe'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

mysqli_stmt_close($stmt);


/*
|--------------------------------------------------------------------------
| VERIFICAR SI YA EXISTE UN ANÁLISIS
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT id_analisis
    FROM analisis_ia
    WHERE id_evaluacion = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conexion, $sql);

if (!$stmt) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'Error al verificar análisis existente'
    ], JSON_UNESCAPED_UNICODE);

    mysqli_close($conexion);
    exit;
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id_evaluacion
);

mysqli_stmt_execute($stmt);

mysqli_stmt_bind_result(
    $stmt,
    $id_analisis_existente
);

if (mysqli_stmt_fetch($stmt)) {

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    echo json_encode([
        'success' => false,
        'mensaje' => 'Esta evaluación ya tiene un análisis registrado',
        'id_analisis' => intval($id_analisis_existente)
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

mysqli_stmt_close($stmt);


/*
|--------------------------------------------------------------------------
| INSERTAR ANÁLISIS
|--------------------------------------------------------------------------
*/

$sql = "
    INSERT INTO analisis_ia
    (
        id_evaluacion,
        id_nivel,
        emocion_detectada,
        porcentaje_confianza,
        resumen
    )
    VALUES (?, ?, ?, ?, ?)
";

$stmt = mysqli_prepare($conexion, $sql);

if (!$stmt) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'Error al preparar el registro del análisis'
    ], JSON_UNESCAPED_UNICODE);

    mysqli_close($conexion);
    exit;
}

mysqli_stmt_bind_param(
    $stmt,
    "iisds",
    $id_evaluacion,
    $id_nivel,
    $emocion,
    $confianza,
    $resumen
);

if (!mysqli_stmt_execute($stmt)) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'No se pudo guardar el análisis',
        'error' => mysqli_stmt_error($stmt)
    ], JSON_UNESCAPED_UNICODE);

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    exit;
}

$id_analisis = mysqli_insert_id($conexion);

mysqli_stmt_close($stmt);
mysqli_close($conexion);


/*
|--------------------------------------------------------------------------
| RESPUESTA EXITOSA
|--------------------------------------------------------------------------
*/

echo json_encode([
    'success' => true,
    'mensaje' => 'Análisis guardado correctamente',
    'id_analisis' => $id_analisis,
    'id_evaluacion' => $id_evaluacion,
    'id_nivel' => $id_nivel,
    'nivel' => $nivel_nombre,
    'emocion' => $emocion,
    'confianza' => $confianza,
    'resumen' => $resumen
], JSON_UNESCAPED_UNICODE);

?>