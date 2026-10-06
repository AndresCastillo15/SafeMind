<?php

header('Content-Type: application/json; charset=utf-8');

require_once '../config/conexion.php';

$conexion = conectar();

/*
|--------------------------------------------------------------------------
| Verificar chat_id
|--------------------------------------------------------------------------
*/

if (!isset($_POST['chat_id']) || empty($_POST['chat_id'])) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'No se recibió el chat_id'
    ]);

    exit;
}

$chat_id = trim($_POST['chat_id']);


/*
|--------------------------------------------------------------------------
| Verificar si ya existe un registro temporal
|--------------------------------------------------------------------------
*/

$sql_verificar = "
    SELECT chat_id_telegram
    FROM registro_temporal
    WHERE chat_id_telegram = ?
";

$stmt_verificar = mysqli_prepare($conexion, $sql_verificar);

if (!$stmt_verificar) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'Error al preparar la consulta'
    ]);

    exit;
}

mysqli_stmt_bind_param(
    $stmt_verificar,
    "s",
    $chat_id
);

mysqli_stmt_execute($stmt_verificar);

$resultado = mysqli_stmt_get_result($stmt_verificar);


/*
|--------------------------------------------------------------------------
| Si ya existe
|--------------------------------------------------------------------------
*/

if (mysqli_num_rows($resultado) > 0) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'Ya existe un registro temporal para este usuario'
    ]);

    mysqli_stmt_close($stmt_verificar);
    mysqli_close($conexion);

    exit;
}

mysqli_stmt_close($stmt_verificar);


/*
|--------------------------------------------------------------------------
| Crear registro temporal
|--------------------------------------------------------------------------
*/

$sql_insertar = "
    INSERT INTO registro_temporal
    (
        chat_id_telegram,
        paso
    )
    VALUES
    (
        ?,
        'nombre'
    )
";

$stmt_insertar = mysqli_prepare($conexion, $sql_insertar);

if (!$stmt_insertar) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'Error al preparar el registro'
    ]);

    exit;
}

mysqli_stmt_bind_param(
    $stmt_insertar,
    "s",
    $chat_id
);

if (mysqli_stmt_execute($stmt_insertar)) {

    echo json_encode([
        'success' => true,
        'mensaje' => 'Registro temporal creado correctamente',
        'chat_id' => $chat_id,
        'paso' => 'nombre'
    ]);

} else {

    echo json_encode([
        'success' => false,
        'mensaje' => 'No se pudo crear el registro temporal'
    ]);
}


/*
|--------------------------------------------------------------------------
| Cerrar conexión
|--------------------------------------------------------------------------
*/

mysqli_stmt_close($stmt_insertar);
mysqli_close($conexion);

?>