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
| CONEXIÓN
|--------------------------------------------------------------------------
*/

$conexion = conectar();


/*
|--------------------------------------------------------------------------
| OBTENER RECURSOS
|--------------------------------------------------------------------------
|
| Además de los datos del recurso, obtenemos cuántas veces
| ha sido relacionado con un análisis de SafeMind.
|
|--------------------------------------------------------------------------
*/

$sql = "

    SELECT

        r.id_recurso,
        r.titulo,
        r.descripcion,
        r.tipo,

        COUNT(ar.id_analisis) AS veces_recomendado

    FROM recurso_apoyo r

    LEFT JOIN analisis_recurso ar
        ON r.id_recurso = ar.id_recurso

    GROUP BY
        r.id_recurso,
        r.titulo,
        r.descripcion,
        r.tipo

    ORDER BY
        veces_recomendado DESC,
        r.titulo ASC

";


$resultado = mysqli_query(
    $conexion,
    $sql
);


/*
|--------------------------------------------------------------------------
| ERROR
|--------------------------------------------------------------------------
*/

if (!$resultado) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'No se pudieron obtener los recursos',
        'error' => mysqli_error($conexion)
    ], JSON_UNESCAPED_UNICODE);

    mysqli_close($conexion);

    exit;
}


/*
|--------------------------------------------------------------------------
| CONSTRUIR RESPUESTA
|--------------------------------------------------------------------------
*/

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
            $fila['tipo'],

        'veces_recomendado' =>
            intval(
                $fila['veces_recomendado']
            )

    ];

}


/*
|--------------------------------------------------------------------------
| CERRAR
|--------------------------------------------------------------------------
*/

mysqli_close($conexion);


/*
|--------------------------------------------------------------------------
| RESPUESTA
|--------------------------------------------------------------------------
*/

echo json_encode([

    'success' => true,

    'total' =>
        count($recursos),

    'recursos' =>
        $recursos

], JSON_UNESCAPED_UNICODE);

?>