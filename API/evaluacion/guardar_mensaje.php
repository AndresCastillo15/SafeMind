<?php

header('Content-Type: application/json; charset=utf-8');

require_once '../config/conexion.php';

$conexion = conectar();

/*
|--------------------------------------------------------------------------
| VERIFICAR PARÁMETROS
|--------------------------------------------------------------------------
*/

if (
    !isset($_POST['chat_id']) ||
    empty($_POST['chat_id']) ||
    !isset($_POST['contenido']) ||
    empty(trim($_POST['contenido']))
) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'Faltan parámetros: chat_id y contenido'
    ]);

    exit;
}

$chat_id = trim($_POST['chat_id']);
$contenido = trim($_POST['contenido']);


/*
|--------------------------------------------------------------------------
| BUSCAR ESTUDIANTE
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT id_estudiante
    FROM estudiante
    WHERE chat_id_telegram = ?
    AND estado = 'Activo'
    LIMIT 1
";

$stmt = mysqli_prepare($conexion, $sql);

if (!$stmt) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'Error al preparar la consulta del estudiante'
    ]);

    exit;
}

mysqli_stmt_bind_param($stmt, "s", $chat_id);
mysqli_stmt_execute($stmt);

$resultado = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($resultado) === 0) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'No existe un estudiante registrado con este chat_id'
    ]);

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    exit;
}

$estudiante = mysqli_fetch_assoc($resultado);
$id_estudiante = $estudiante['id_estudiante'];

mysqli_stmt_close($stmt);


/*
|--------------------------------------------------------------------------
| BUSCAR EVALUACIÓN ACTIVA
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT id_evaluacion
    FROM evaluacion
    WHERE id_estudiante = ?
    AND estado = 'Activa'
    AND estado_animo IS NOT NULL
    ORDER BY id_evaluacion DESC
    LIMIT 1
";

$stmt = mysqli_prepare($conexion, $sql);

if (!$stmt) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'Error al preparar la consulta de evaluación'
    ]);

    mysqli_close($conexion);

    exit;
}

mysqli_stmt_bind_param($stmt, "i", $id_estudiante);
mysqli_stmt_execute($stmt);

$resultado = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($resultado) === 0) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'No existe una evaluación activa con estado de ánimo registrado'
    ]);

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    exit;
}

$evaluacion = mysqli_fetch_assoc($resultado);
$id_evaluacion = $evaluacion['id_evaluacion'];

mysqli_stmt_close($stmt);


/*
|--------------------------------------------------------------------------
| GUARDAR MENSAJE
|--------------------------------------------------------------------------
*/

$sql = "
    INSERT INTO mensaje (
        id_evaluacion,
        remitente,
        contenido
    )
    VALUES (?, 'estudiante', ?)
";

$stmt = mysqli_prepare($conexion, $sql);

if (!$stmt) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'Error al preparar el mensaje'
    ]);

    mysqli_close($conexion);

    exit;
}

mysqli_stmt_bind_param(
    $stmt,
    "is",
    $id_evaluacion,
    $contenido
);


/*
|--------------------------------------------------------------------------
| EJECUTAR
|--------------------------------------------------------------------------
*/

if (mysqli_stmt_execute($stmt)) {

    $id_mensaje = mysqli_insert_id($conexion);

    echo json_encode([
        'success' => true,
        'mensaje' => 'Mensaje guardado correctamente',
        'id_mensaje' => $id_mensaje,
        'id_evaluacion' => $id_evaluacion,
        'id_estudiante' => $id_estudiante,
        'contenido' => $contenido
    ]);

} else {

    echo json_encode([
        'success' => false,
        'mensaje' => 'No se pudo guardar el mensaje'
    ]);
}


/*
|--------------------------------------------------------------------------
| CERRAR
|--------------------------------------------------------------------------
*/

mysqli_stmt_close($stmt);
mysqli_close($conexion);

?>