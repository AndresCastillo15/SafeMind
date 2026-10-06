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
| Obtener ID de alerta
|--------------------------------------------------------------------------
*/

$id_alerta = isset($_GET['id_alerta'])
    ? intval($_GET['id_alerta'])
    : 0;


if ($id_alerta <= 0) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'ID de alerta no válido'
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
| Obtener seguimientos
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        s.id_seguimiento,
        s.id_alerta,
        s.id_psicologo,
        s.fecha,
        s.observacion,
        s.estado,

        p.nombre AS nombre_psicologo,
        p.apellido AS apellido_psicologo

    FROM seguimiento s

    INNER JOIN psicologo p
        ON s.id_psicologo = p.id_psicologo

    WHERE s.id_alerta = ?

    ORDER BY s.fecha DESC
";


$stmt = mysqli_prepare(
    $conexion,
    $sql
);


if (!$stmt) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'No se pudo preparar la consulta',
        'error' => mysqli_error($conexion)
    ], JSON_UNESCAPED_UNICODE);

    mysqli_close($conexion);

    exit;
}


mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id_alerta
);


mysqli_stmt_execute($stmt);


$resultado =
    mysqli_stmt_get_result($stmt);


$seguimientos = [];


while (
    $fila = mysqli_fetch_assoc($resultado)
) {

    $seguimientos[] = [

        'id_seguimiento' =>
            intval($fila['id_seguimiento']),

        'id_alerta' =>
            intval($fila['id_alerta']),

        'psicologo' => [

            'id' =>
                intval($fila['id_psicologo']),

            'nombre' =>
                trim(
                    $fila['nombre_psicologo']
                    . ' '
                    . $fila['apellido_psicologo']
                )

        ],

        'fecha' =>
            $fila['fecha'],

        'observacion' =>
            $fila['observacion'],

        'estado' =>
            $fila['estado']

    ];
}


mysqli_stmt_close($stmt);

mysqli_close($conexion);


/*
|--------------------------------------------------------------------------
| Respuesta
|--------------------------------------------------------------------------
*/

echo json_encode([

    'success' => true,

    'total' => count($seguimientos),

    'id_alerta' => $id_alerta,

    'seguimientos' => $seguimientos

], JSON_UNESCAPED_UNICODE);

?>