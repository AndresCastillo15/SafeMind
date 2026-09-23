<?php

header('Content-Type: application/json; charset=utf-8');

session_start();

require_once '../config/conexion.php';


/*
|--------------------------------------------------------------------------
| Verificar sesión de psicólogo
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
| Obtener psicólogo desde la sesión
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
| Datos enviados
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
| Validar datos
|--------------------------------------------------------------------------
*/

if ($id_alerta <= 0) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'ID de alerta no válido'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


if ($observacion === '') {

    echo json_encode([
        'success' => false,
        'mensaje' => 'La observación es obligatoria'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


/*
|--------------------------------------------------------------------------
| Conexión
|--------------------------------------------------------------------------
*/

$conexion = conectar();


/*
|--------------------------------------------------------------------------
| Verificar que la alerta exista
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
| Registrar seguimiento
|--------------------------------------------------------------------------
*/

$estado_seguimiento = 'En seguimiento';


$sql_insertar = "
    INSERT INTO seguimiento
    (
        id_alerta,
        id_psicologo,
        observacion,
        estado
    )
    VALUES (?, ?, ?, ?)
";


$stmt_insertar = mysqli_prepare(
    $conexion,
    $sql_insertar
);


if (!$stmt_insertar) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'No se pudo preparar el seguimiento',
        'error' => mysqli_error($conexion)
    ], JSON_UNESCAPED_UNICODE);

    mysqli_close($conexion);

    exit;
}


mysqli_stmt_bind_param(
    $stmt_insertar,
    "iiss",
    $id_alerta,
    $id_psicologo,
    $observacion,
    $estado_seguimiento
);


if (
    !mysqli_stmt_execute(
        $stmt_insertar
    )
) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'No se pudo registrar el seguimiento',
        'error' => mysqli_stmt_error($stmt_insertar)
    ], JSON_UNESCAPED_UNICODE);

    mysqli_stmt_close($stmt_insertar);
    mysqli_close($conexion);

    exit;
}


$id_seguimiento =
    mysqli_insert_id($conexion);


mysqli_stmt_close($stmt_insertar);


/*
|--------------------------------------------------------------------------
| Cambiar estado de la alerta
|--------------------------------------------------------------------------
*/

$estado_alerta = 'En seguimiento';


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
        'mensaje' => 'El seguimiento fue creado, pero no se pudo actualizar la alerta'
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
        'mensaje' => 'El seguimiento fue creado, pero no se pudo actualizar el estado de la alerta'
    ], JSON_UNESCAPED_UNICODE);

    mysqli_stmt_close($stmt_actualizar);
    mysqli_close($conexion);

    exit;
}


mysqli_stmt_close($stmt_actualizar);

mysqli_close($conexion);


/*
|--------------------------------------------------------------------------
| Respuesta
|--------------------------------------------------------------------------
*/

echo json_encode([

    'success' => true,

    'mensaje' =>
        'Seguimiento registrado correctamente',

    'id_seguimiento' =>
        $id_seguimiento,

    'id_alerta' =>
        $id_alerta,

    'estado_alerta' =>
        $estado_alerta,

    'estado_seguimiento' =>
        $estado_seguimiento

], JSON_UNESCAPED_UNICODE);

?>