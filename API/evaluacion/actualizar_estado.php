<?php

header('Content-Type: application/json; charset=utf-8');

require_once '../config/conexion.php';

$conexion = conectar();

/*
|--------------------------------------------------------------------------
| 1. Verificar parámetros
|--------------------------------------------------------------------------
*/

if (
    !isset($_POST['chat_id']) ||
    !isset($_POST['estado_animo']) ||
    trim($_POST['chat_id']) === '' ||
    trim($_POST['estado_animo']) === ''
) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'Faltan parámetros: chat_id y estado_animo'
    ]);

    mysqli_close($conexion);
    exit;
}

$chat_id = trim($_POST['chat_id']);
$estado_recibido = trim($_POST['estado_animo']);


/*
|--------------------------------------------------------------------------
| 2. Normalizar el estado de ánimo
|--------------------------------------------------------------------------
*/

$estados = [
    '🟢 Bien' => 'Bien',
    '🟡 No tan bien' => 'No tan bien',
    '🟠 Mal' => 'Mal',
    '🔴 Malísimo' => 'Malísimo'
];

if (!isset($estados[$estado_recibido])) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'Estado de ánimo no válido'
    ]);

    mysqli_close($conexion);
    exit;
}

$estado_animo = $estados[$estado_recibido];


/*
|--------------------------------------------------------------------------
| 3. Buscar estudiante
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

    mysqli_close($conexion);
    exit;
}

mysqli_stmt_bind_param($stmt, "s", $chat_id);
mysqli_stmt_execute($stmt);

$resultado = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($resultado) === 0) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'No se encontró un estudiante registrado con este chat_id'
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
| 4. Buscar la evaluación activa más reciente
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT id_evaluacion
    FROM evaluacion
    WHERE id_estudiante = ?
    AND estado = 'Activa'
    ORDER BY id_evaluacion DESC
    LIMIT 1
";

$stmt = mysqli_prepare($conexion, $sql);

if (!$stmt) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'Error al buscar la evaluación activa'
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
        'mensaje' => 'No existe una evaluación activa para este estudiante'
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
| 5. Actualizar estado de ánimo
|--------------------------------------------------------------------------
*/

$sql = "
    UPDATE evaluacion
    SET estado_animo = ?
    WHERE id_evaluacion = ?
";

$stmt = mysqli_prepare($conexion, $sql);

if (!$stmt) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'Error al preparar la actualización'
    ]);

    mysqli_close($conexion);
    exit;
}

mysqli_stmt_bind_param(
    $stmt,
    "si",
    $estado_animo,
    $id_evaluacion
);


/*
|--------------------------------------------------------------------------
| 6. Ejecutar actualización
|--------------------------------------------------------------------------
*/

if (mysqli_stmt_execute($stmt)) {

    echo json_encode([
        'success' => true,
        'mensaje' => 'Estado de ánimo guardado correctamente',
        'id_evaluacion' => $id_evaluacion,
        'id_estudiante' => $id_estudiante,
        'estado_animo' => $estado_animo
    ]);

} else {

    echo json_encode([
        'success' => false,
        'mensaje' => 'No se pudo actualizar el estado de ánimo',
        'error' => mysqli_stmt_error($stmt)
    ]);
}


/*
|--------------------------------------------------------------------------
| 7. Cerrar conexión
|--------------------------------------------------------------------------
*/

mysqli_stmt_close($stmt);
mysqli_close($conexion);

?>