<?php

header('Content-Type: application/json; charset=utf-8');

require_once '../config/conexion.php';

$conexion = conectar();

/*
|--------------------------------------------------------------------------
| 1. Verificar chat_id
|--------------------------------------------------------------------------
*/

if (!isset($_POST['chat_id']) || trim($_POST['chat_id']) === '') {

    echo json_encode([
        'success' => false,
        'mensaje' => 'No se recibió el chat_id'
    ]);

    exit;
}

$chat_id = trim($_POST['chat_id']);


/*
|--------------------------------------------------------------------------
| 2. Buscar estudiante
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT id_estudiante, nombre, apellido
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


/*
|--------------------------------------------------------------------------
| 3. Verificar que el estudiante exista
|--------------------------------------------------------------------------
*/

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
$nombre = $estudiante['nombre'];
$apellido = $estudiante['apellido'];

mysqli_stmt_close($stmt);


/*
|--------------------------------------------------------------------------
| 4. Crear nueva evaluación
|--------------------------------------------------------------------------
*/

$sql = "
    INSERT INTO evaluacion (
        id_estudiante,
        estado
    )
    VALUES (?, 'Activa')
";

$stmt = mysqli_prepare($conexion, $sql);

if (!$stmt) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'Error al preparar la evaluación'
    ]);

    mysqli_close($conexion);
    exit;
}

mysqli_stmt_bind_param($stmt, "i", $id_estudiante);


/*
|--------------------------------------------------------------------------
| 5. Guardar evaluación
|--------------------------------------------------------------------------
*/

if (mysqli_stmt_execute($stmt)) {

    $id_evaluacion = mysqli_insert_id($conexion);

    echo json_encode([
        'success' => true,
        'mensaje' => 'Evaluación creada correctamente',
        'id_evaluacion' => $id_evaluacion,
        'id_estudiante' => $id_estudiante,
        'nombre' => $nombre,
        'apellido' => $apellido
    ]);

} else {

    echo json_encode([
        'success' => false,
        'mensaje' => 'No se pudo crear la evaluación',
        'error' => mysqli_stmt_error($stmt)
    ]);
}

mysqli_stmt_close($stmt);
mysqli_close($conexion);

?>