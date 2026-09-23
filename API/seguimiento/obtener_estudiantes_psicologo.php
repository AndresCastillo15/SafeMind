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
| OBTENER ESTUDIANTES
|--------------------------------------------------------------------------
|
| Para cada estudiante obtenemos:
| - datos básicos
| - total de evaluaciones
| - última evaluación
| - última emoción
| - último riesgo
| - estado de la última alerta
| - ID de la última alerta
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT

        est.id_estudiante,
        est.nombre,
        est.apellido,
        est.curso,

        (
            SELECT COUNT(*)
            FROM evaluacion e
            WHERE e.id_estudiante = est.id_estudiante
        ) AS total_evaluaciones,


        (
            SELECT e.fecha_inicio
            FROM evaluacion e
            WHERE e.id_estudiante = est.id_estudiante
            ORDER BY e.fecha_inicio DESC
            LIMIT 1
        ) AS ultima_evaluacion,


        (
            SELECT ai.emocion_detectada

            FROM analisis_ia ai

            INNER JOIN evaluacion e
                ON ai.id_evaluacion = e.id_evaluacion

            WHERE e.id_estudiante = est.id_estudiante

            ORDER BY e.fecha_inicio DESC

            LIMIT 1
        ) AS ultima_emocion,


        (
            SELECT nr.nombre

            FROM analisis_ia ai

            INNER JOIN evaluacion e
                ON ai.id_evaluacion = e.id_evaluacion

            INNER JOIN nivel_riesgo nr
                ON ai.id_nivel = nr.id_nivel

            WHERE e.id_estudiante = est.id_estudiante

            ORDER BY e.fecha_inicio DESC

            LIMIT 1
        ) AS ultimo_riesgo,


        (
            SELECT a.estado

            FROM alerta a

            INNER JOIN analisis_ia ai
                ON a.id_analisis = ai.id_analisis

            INNER JOIN evaluacion e
                ON ai.id_evaluacion = e.id_evaluacion

            WHERE e.id_estudiante = est.id_estudiante

            ORDER BY a.fecha DESC

            LIMIT 1
        ) AS estado_alerta,


        (
            SELECT a.id_alerta

            FROM alerta a

            INNER JOIN analisis_ia ai
                ON a.id_analisis = ai.id_analisis

            INNER JOIN evaluacion e
                ON ai.id_evaluacion = e.id_evaluacion

            WHERE e.id_estudiante = est.id_estudiante

            ORDER BY a.fecha DESC

            LIMIT 1
        ) AS id_alerta


    FROM estudiante est

    WHERE est.estado = 'Activo'

    ORDER BY
        est.apellido ASC,
        est.nombre ASC
";


$resultado = mysqli_query(
    $conexion,
    $sql
);


/*
|--------------------------------------------------------------------------
| ERROR SQL
|--------------------------------------------------------------------------
*/

if (!$resultado) {

    echo json_encode([
        'success' => false,
        'mensaje' =>
            'No se pudieron obtener los estudiantes',
        'error' =>
            mysqli_error($conexion)
    ], JSON_UNESCAPED_UNICODE);

    mysqli_close($conexion);

    exit;
}


/*
|--------------------------------------------------------------------------
| CONSTRUIR RESPUESTA
|--------------------------------------------------------------------------
*/

$estudiantes = [];


while (
    $fila = mysqli_fetch_assoc($resultado)
) {

    $estudiantes[] = [

        'id' =>
            intval(
                $fila['id_estudiante']
            ),

        'nombre' =>
            $fila['nombre'],

        'apellido' =>
            $fila['apellido'],

        'curso' =>
            $fila['curso'],

        'total_evaluaciones' =>
            intval(
                $fila['total_evaluaciones']
            ),

        'ultima_evaluacion' =>
            $fila['ultima_evaluacion'],

        'ultima_emocion' =>
            $fila['ultima_emocion'],

        'ultimo_riesgo' =>
            $fila['ultimo_riesgo'],

        'estado_alerta' =>
            $fila['estado_alerta'],

        'id_alerta' =>
            $fila['id_alerta']
                !== null
                ? intval(
                    $fila['id_alerta']
                )
                : null

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
        count($estudiantes),

    'estudiantes' =>
        $estudiantes

], JSON_UNESCAPED_UNICODE);

?>