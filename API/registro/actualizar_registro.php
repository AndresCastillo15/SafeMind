<?php

header('Content-Type: application/json; charset=utf-8');

require_once '../config/conexion.php';

$conexion = conectar();


/*
|--------------------------------------------------------------------------
| Verificar parámetros
|--------------------------------------------------------------------------
*/

if (
    !isset($_POST['chat_id']) ||
    empty($_POST['chat_id']) ||
    !isset($_POST['valor']) ||
    trim($_POST['valor']) === ''
) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'Faltan datos: chat_id o valor'
    ]);

    exit;
}

$chat_id = trim($_POST['chat_id']);
$valor = trim($_POST['valor']);


/*
|--------------------------------------------------------------------------
| Buscar el registro temporal
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT paso
    FROM registro_temporal
    WHERE chat_id_telegram = ?
";

$stmt = mysqli_prepare($conexion, $sql);

if (!$stmt) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'Error al preparar la consulta'
    ]);

    exit;
}

mysqli_stmt_bind_param(
    $stmt,
    "s",
    $chat_id
);

mysqli_stmt_execute($stmt);

$resultado = mysqli_stmt_get_result($stmt);


/*
|--------------------------------------------------------------------------
| Verificar si existe el registro
|--------------------------------------------------------------------------
*/

if (mysqli_num_rows($resultado) === 0) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'No existe un registro temporal para este usuario'
    ]);

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    exit;
}

$registro = mysqli_fetch_assoc($resultado);
$paso_actual = $registro['paso'];

mysqli_stmt_close($stmt);


/*
|--------------------------------------------------------------------------
| Definir el siguiente paso
|--------------------------------------------------------------------------
*/

$pasos = [
    'nombre' => 'apellido',
    'apellido' => 'correo',
    'correo' => 'telefono',
    'telefono' => 'curso',
    'curso' => 'completado'
];


/*
|--------------------------------------------------------------------------
| Verificar que el paso sea válido
|--------------------------------------------------------------------------
*/

if (!array_key_exists($paso_actual, $pasos)) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'El paso actual no es válido',
        'paso' => $paso_actual
    ]);

    mysqli_close($conexion);

    exit;
}

$siguiente_paso = $pasos[$paso_actual];


/*
|--------------------------------------------------------------------------
| Actualizar registro
|--------------------------------------------------------------------------
|
| IMPORTANTE:
| $paso_actual solo puede ser uno de los valores
| definidos internamente en $pasos.
|
*/

$sql_actualizar = "
    UPDATE registro_temporal
    SET $paso_actual = ?,
        paso = ?
    WHERE chat_id_telegram = ?
";

$stmt_actualizar = mysqli_prepare(
    $conexion,
    $sql_actualizar
);

if (!$stmt_actualizar) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'Error al preparar la actualización'
    ]);

    mysqli_close($conexion);

    exit;
}

mysqli_stmt_bind_param(
    $stmt_actualizar,
    "sss",
    $valor,
    $siguiente_paso,
    $chat_id
);


/*
|--------------------------------------------------------------------------
| Ejecutar actualización
|--------------------------------------------------------------------------
*/

if (mysqli_stmt_execute($stmt_actualizar)) {

    echo json_encode([
        'success' => true,
        'mensaje' => 'Dato guardado correctamente',
        'dato_guardado' => $paso_actual,
        'valor' => $valor,
        'siguiente_paso' => $siguiente_paso
    ]);

} else {

    echo json_encode([
        'success' => false,
        'mensaje' => 'No se pudo actualizar el registro'
    ]);
}


/*
|--------------------------------------------------------------------------
| Cerrar conexión
|--------------------------------------------------------------------------
*/

mysqli_stmt_close($stmt_actualizar);
mysqli_close($conexion);

?>