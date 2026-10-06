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
| ID DEL ANÁLISIS
|--------------------------------------------------------------------------
*/

$id_analisis = isset($_GET['id_analisis'])
    ? intval($_GET['id_analisis'])
    : 0;


if ($id_analisis <= 0) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'ID de análisis no válido'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


/*
|--------------------------------------------------------------------------
| CONEXIÓN
|--------------------------------------------------------------------------
*/

$conexion = conectar();


/*
|--------------------------------------------------------------------------
| OBTENER RECURSOS
|--------------------------------------------------------------------------
*/

$sql = "

    SELECT

        r.id_recurso,
        r.titulo,
        r.descripcion,
        r.tipo

    FROM analisis_recurso ar

    INNER JOIN recurso_apoyo r
        ON ar.id_recurso = r.id_recurso

    WHERE ar.id_analisis = ?

    ORDER BY r.id_recurso ASC

";


$stmt = mysqli_prepare(
    $conexion,
    $sql
);


if (!$stmt) {

    echo json_encode([
        'success' => false,
        'mensaje' =>
            'No se pudo preparar la consulta',
        'error' =>
            mysqli_error($conexion)
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


$resultado =
    mysqli_stmt_get_result($stmt);


$recursos = [];


while (
    $fila = mysqli_fetch_assoc($resultado)
) {

    $recursos[] = [

        'id' =>
            intval(
                $fila['id_recurso']
            ),

        'titulo' =>
            $fila['titulo'],

        'descripcion' =>
            $fila['descripcion'],

        'tipo' =>
            $fila['tipo']

    ];

}


mysqli_stmt_close($stmt);

mysqli_close($conexion);


/*
|--------------------------------------------------------------------------
| RESPUESTA
|--------------------------------------------------------------------------
*/

echo json_encode([

    'success' => true,

    'id_analisis' =>
        $id_analisis,

    'total' =>
        count($recursos),

    'recursos' =>
        $recursos

], JSON_UNESCAPED_UNICODE);

?>