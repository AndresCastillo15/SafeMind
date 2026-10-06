<?php

header('Content-Type: application/json; charset=utf-8');

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../config/conexion.php';

$conexion = conectar();

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

        est.id_estudiante,
        est.nombre,
        est.apellido,
        est.curso

    FROM alerta a

    INNER JOIN analisis_ia ai
        ON a.id_analisis = ai.id_analisis

    INNER JOIN nivel_riesgo nr
        ON ai.id_nivel = nr.id_nivel

    INNER JOIN evaluacion e
        ON ai.id_evaluacion = e.id_evaluacion

    INNER JOIN estudiante est
        ON e.id_estudiante = est.id_estudiante

    WHERE a.estado = 'Pendiente'

    ORDER BY a.fecha DESC
";

$resultado = mysqli_query($conexion, $sql);

if (!$resultado) {
    echo json_encode([
        'success' => false,
        'mensaje' => 'Error SQL',
        'error' => mysqli_error($conexion)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$alertas = [];

while ($fila = mysqli_fetch_assoc($resultado)) {

    $alertas[] = [

        'id_alerta' => intval($fila['id_alerta']),

        'estado' => $fila['estado_alerta'],

        'fecha' => $fila['fecha_alerta'],


        'id_analisis' => intval($fila['id_analisis']),

        'id_evaluacion' => intval($fila['id_evaluacion']),


        'estudiante' => [

            'id' => intval($fila['id_estudiante']),

            'nombre' => $fila['nombre'],

            'apellido' => $fila['apellido'],

            'curso' => $fila['curso']

        ],


        'riesgo' => [

            'id_nivel' => intval($fila['id_nivel']),

            'nombre' => $fila['nivel_riesgo'],

            'color' => $fila['color_riesgo']

        ],


        'analisis' => [

            'emocion' => $fila['emocion_detectada'],

            'confianza' => floatval(
                $fila['porcentaje_confianza']
            ),

            'resumen' => $fila['resumen']

        ]

    ];

}

echo json_encode([
    'success' => true,
    'total' => count($alertas),
    'alertas' => $alertas
], JSON_UNESCAPED_UNICODE);

mysqli_close($conexion);
?>