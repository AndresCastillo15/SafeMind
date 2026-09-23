<?php

header('Content-Type: application/json; charset=utf-8');

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../config/conexion.php';

$conexion = conectar();


/*
|--------------------------------------------------------------------------
| Validar ID
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
| Obtener información de la alerta
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT

        a.id_alerta,
        a.estado AS estado_alerta,
        a.fecha AS fecha_alerta,

        ai.id_analisis,
        ai.emocion_detectada,
        ai.porcentaje_confianza,
        ai.resumen,

        nr.id_nivel,
        nr.nombre AS nivel_riesgo,
        nr.color AS color_riesgo,

        e.id_evaluacion,
        e.fecha_inicio,
        e.fecha_fin,
        e.estado AS estado_evaluacion,

        est.id_estudiante,
        est.nombre,
        est.apellido,
        est.curso,
        est.correo

    FROM alerta a

    INNER JOIN analisis_ia ai
        ON a.id_analisis = ai.id_analisis

    INNER JOIN nivel_riesgo nr
        ON ai.id_nivel = nr.id_nivel

    INNER JOIN evaluacion e
        ON ai.id_evaluacion = e.id_evaluacion

    INNER JOIN estudiante est
        ON e.id_estudiante = est.id_estudiante

    WHERE a.id_alerta = ?

    LIMIT 1
";


$stmt = mysqli_prepare($conexion, $sql);

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

$resultado = mysqli_stmt_get_result($stmt);


if (mysqli_num_rows($resultado) === 0) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'La alerta no existe'
    ], JSON_UNESCAPED_UNICODE);

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    exit;
}


$fila = mysqli_fetch_assoc($resultado);


/*
|--------------------------------------------------------------------------
| Obtener mensajes de la evaluación
|--------------------------------------------------------------------------
*/

$sql_mensajes = "
    SELECT
        id_mensaje,
        remitente,
        contenido,
        fecha_hora

    FROM mensaje

    WHERE id_evaluacion = ?

    ORDER BY fecha_hora ASC
";


$stmt_mensajes = mysqli_prepare(
    $conexion,
    $sql_mensajes
);


$mensajes = [];


if ($stmt_mensajes) {

    mysqli_stmt_bind_param(
        $stmt_mensajes,
        "i",
        $fila['id_evaluacion']
    );

    mysqli_stmt_execute($stmt_mensajes);

    $resultado_mensajes =
        mysqli_stmt_get_result($stmt_mensajes);


    while (
        $mensaje = mysqli_fetch_assoc(
            $resultado_mensajes
        )
    ) {

        $mensajes[] = [

            'id' => intval(
                $mensaje['id_mensaje']
            ),

            'remitente' =>
                $mensaje['remitente'],

            'contenido' =>
                $mensaje['contenido'],

            'fecha' =>
                $mensaje['fecha_hora']

        ];

    }

}


/*
|--------------------------------------------------------------------------
| Construir respuesta
|--------------------------------------------------------------------------
*/

echo json_encode([

    'success' => true,

    'alerta' => [

        'id_alerta' =>
            intval($fila['id_alerta']),

        'estado' =>
            $fila['estado_alerta'],

        'fecha' =>
            $fila['fecha_alerta']

    ],

    'estudiante' => [

        'id' =>
            intval($fila['id_estudiante']),

        'nombre' =>
            $fila['nombre'],

        'apellido' =>
            $fila['apellido'],

        'curso' =>
            $fila['curso'],

        'correo' =>
            $fila['correo']

    ],

    'evaluacion' => [

        'id' =>
            intval($fila['id_evaluacion']),

        'fecha_inicio' =>
            $fila['fecha_inicio'],

        'fecha_fin' =>
            $fila['fecha_fin'],

        'estado' =>
            $fila['estado_evaluacion']

    ],

    'riesgo' => [

        'id_nivel' =>
            intval($fila['id_nivel']),

        'nombre' =>
            $fila['nivel_riesgo'],

        'color' =>
            $fila['color_riesgo']

    ],

    'analisis' => [

        'id' =>
            intval($fila['id_analisis']),

        'emocion' =>
            $fila['emocion_detectada'],

        'confianza' =>
            floatval(
                $fila['porcentaje_confianza']
            ),

        'resumen' =>
            $fila['resumen']

    ],

    'mensajes' =>
        $mensajes

], JSON_UNESCAPED_UNICODE);


/*
|--------------------------------------------------------------------------
| Cerrar
|--------------------------------------------------------------------------
*/

if ($stmt_mensajes) {
    mysqli_stmt_close($stmt_mensajes);
}

mysqli_stmt_close($stmt);

mysqli_close($conexion);

?>