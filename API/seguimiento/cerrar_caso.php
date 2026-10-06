<?php

header('Content-Type: application/json; charset=utf-8');

session_start();

require_once '../config/conexion.php';


/*
|--------------------------------------------------------------------------
| VERIFICAR SESIÓN
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['rol']) ||
    $_SESSION['rol'] !== 'psicologo'
) {
    echo json_encode([
        'success' => false,
        'mensaje' => 'No autorizado'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


/*
|--------------------------------------------------------------------------
| OBTENER ID DEL PSICÓLOGO
|--------------------------------------------------------------------------
*/

$id_psicologo = isset($_SESSION['id_usuario'])
    ? intval($_SESSION['id_usuario'])
    : 0;


if ($id_psicologo <= 0) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'Sesión de psicólogo no válida'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


/*
|--------------------------------------------------------------------------
| OBTENER DATOS RECIBIDOS
|--------------------------------------------------------------------------
*/

$id_alerta = isset($_POST['id_alerta'])
    ? intval($_POST['id_alerta'])
    : 0;

$observacion = trim(
    $_POST['observacion'] ?? ''
);


/*
|--------------------------------------------------------------------------
| VALIDAR ID
|--------------------------------------------------------------------------
*/

if ($id_alerta <= 0) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'ID de alerta no válido'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


/*
|--------------------------------------------------------------------------
| OBSERVACIÓN
|--------------------------------------------------------------------------
|
| La observación final es opcional.
|--------------------------------------------------------------------------
*/

if ($observacion === '') {

    $observacion =
        'Caso cerrado por seguimiento profesional.';

}


/*
|--------------------------------------------------------------------------
| CONEXIÓN
|--------------------------------------------------------------------------
*/

$conexion = conectar();


/*
|--------------------------------------------------------------------------
| VERIFICAR ALERTA
|--------------------------------------------------------------------------
*/

$sql_alerta = "
    SELECT
        id_alerta,
        estado

    FROM alerta

    WHERE id_alerta = ?

    LIMIT 1
";


$stmt_alerta = mysqli_prepare(
    $conexion,
    $sql_alerta
);


if (!$stmt_alerta) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'No se pudo preparar la consulta'
    ], JSON_UNESCAPED_UNICODE);

    mysqli_close($conexion);

    exit;
}


mysqli_stmt_bind_param(
    $stmt_alerta,
    "i",
    $id_alerta
);


mysqli_stmt_execute(
    $stmt_alerta
);


$resultado_alerta =
    mysqli_stmt_get_result(
        $stmt_alerta
    );


if (
    mysqli_num_rows(
        $resultado_alerta
    ) === 0
) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'La alerta no existe'
    ], JSON_UNESCAPED_UNICODE);

    mysqli_stmt_close($stmt_alerta);
    mysqli_close($conexion);

    exit;
}


$fila_alerta =
    mysqli_fetch_assoc(
        $resultado_alerta
    );


mysqli_stmt_close($stmt_alerta);


/*
|--------------------------------------------------------------------------
| COMPROBAR SI YA ESTÁ CERRADA
|--------------------------------------------------------------------------
*/

if ($fila_alerta['estado'] === 'Cerrada') {

    echo json_encode([
        'success' => false,
        'mensaje' => 'Esta alerta ya está cerrada'
    ], JSON_UNESCAPED_UNICODE);

    mysqli_close($conexion);

    exit;
}


/*
|--------------------------------------------------------------------------
| ACTUALIZAR ALERTA
|--------------------------------------------------------------------------
*/

$estado_alerta = 'Cerrada';


$sql_actualizar = "
    UPDATE alerta

    SET estado = ?

    WHERE id_alerta = ?
";


$stmt_actualizar = mysqli_prepare(
    $conexion,
    $sql_actualizar
);


if (!$stmt_actualizar) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'No se pudo preparar la actualización'
    ], JSON_UNESCAPED_UNICODE);

    mysqli_close($conexion);

    exit;
}


mysqli_stmt_bind_param(
    $stmt_actualizar,
    "si",
    $estado_alerta,
    $id_alerta
);


if (
    !mysqli_stmt_execute(
        $stmt_actualizar
    )
) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'No se pudo cerrar la alerta',
        'error' => mysqli_stmt_error($stmt_actualizar)
    ], JSON_UNESCAPED_UNICODE);

    mysqli_stmt_close($stmt_actualizar);
    mysqli_close($conexion);

    exit;
}


mysqli_stmt_close($stmt_actualizar);


/*
|--------------------------------------------------------------------------
| REGISTRAR CIERRE EN EL HISTORIAL
|--------------------------------------------------------------------------
|
| De esta manera el cierre queda registrado como un seguimiento más.
|--------------------------------------------------------------------------
*/

$estado_seguimiento = 'Cerrado';


$sql_seguimiento = "
    INSERT INTO seguimiento
    (
        id_alerta,
        id_psicologo,
        observacion,
        estado
    )
    VALUES (?, ?, ?, ?)
";


$stmt_seguimiento = mysqli_prepare(
    $conexion,
    $sql_seguimiento
);


if (!$stmt_seguimiento) {

    echo json_encode([
        'success' => false,
        'mensaje' =>
            'La alerta fue cerrada, pero no se pudo registrar el cierre'
    ], JSON_UNESCAPED_UNICODE);

    mysqli_close($conexion);

    exit;
}


mysqli_stmt_bind_param(
    $stmt_seguimiento,
    "iiss",
    $id_alerta,
    $id_psicologo,
    $observacion,
    $estado_seguimiento
);


if (
    !mysqli_stmt_execute(
        $stmt_seguimiento
    )
) {

    echo json_encode([
        'success' => false,
        'mensaje' =>
            'La alerta fue cerrada, pero no se pudo registrar el historial',
        'error' =>
            mysqli_stmt_error($stmt_seguimiento)
    ], JSON_UNESCAPED_UNICODE);

    mysqli_stmt_close($stmt_seguimiento);
    mysqli_close($conexion);

    exit;
}


$id_seguimiento =
    mysqli_insert_id($conexion);


mysqli_stmt_close($stmt_seguimiento);

mysqli_close($conexion);


/*
|--------------------------------------------------------------------------
| RESPUESTA
|--------------------------------------------------------------------------
*/

echo json_encode([

    'success' => true,

    'mensaje' =>
        'Caso cerrado correctamente',

    'id_alerta' =>
        $id_alerta,

    'id_seguimiento' =>
        $id_seguimiento,

    'estado_alerta' =>
        'Cerrada',

    'estado_seguimiento' =>
        'Cerrado'

], JSON_UNESCAPED_UNICODE);

?>