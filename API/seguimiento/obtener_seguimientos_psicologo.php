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
| OBTENER CASOS ACTUALMENTE EN SEGUIMIENTO
|--------------------------------------------------------------------------
|
| Reglas:
|
| 1. La alerta debe estar actualmente "En seguimiento".
| 2. El seguimiento debe estar "En seguimiento".
| 3. Solo se muestra UN registro por estudiante.
| 4. Si un estudiante tiene varios casos activos,
|    se muestra el seguimiento más reciente.
|
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
        p.apellido AS apellido_psicologo,

        est.id_estudiante,
        est.nombre AS nombre_estudiante,
        est.apellido AS apellido_estudiante,
        est.curso,

        a.estado AS estado_alerta,

        ai.id_nivel,

        nr.nombre AS nivel_riesgo,
        nr.color AS color_riesgo


    FROM seguimiento s


    INNER JOIN psicologo p
        ON s.id_psicologo = p.id_psicologo


    INNER JOIN alerta a
        ON s.id_alerta = a.id_alerta


    INNER JOIN analisis_ia ai
        ON a.id_analisis = ai.id_analisis


    INNER JOIN nivel_riesgo nr
        ON ai.id_nivel = nr.id_nivel


    INNER JOIN evaluacion e
        ON ai.id_evaluacion = e.id_evaluacion


    INNER JOIN estudiante est
        ON e.id_estudiante = est.id_estudiante


    WHERE

        s.estado = 'En seguimiento'

        AND

        a.estado = 'En seguimiento'


        /*
        --------------------------------------------------------------
        Solo el seguimiento más reciente por estudiante
        --------------------------------------------------------------
        */

        AND NOT EXISTS (

            SELECT 1

            FROM seguimiento s2


            INNER JOIN alerta a2
                ON s2.id_alerta = a2.id_alerta


            INNER JOIN analisis_ia ai2
                ON a2.id_analisis = ai2.id_analisis


            INNER JOIN evaluacion e2
                ON ai2.id_evaluacion = e2.id_evaluacion


            WHERE

                s2.estado = 'En seguimiento'

                AND

                a2.estado = 'En seguimiento'

                AND

                e2.id_estudiante =
                    e.id_estudiante


                AND (

                    s2.fecha > s.fecha

                    OR

                    (
                        s2.fecha = s.fecha
                        AND
                        s2.id_seguimiento >
                            s.id_seguimiento
                    )

                )

        )


    ORDER BY
        s.fecha DESC

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

        'mensaje' =>
            'No se pudieron obtener los casos en seguimiento',

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

$seguimientos = [];


while (
    $fila = mysqli_fetch_assoc($resultado)
) {

    $seguimientos[] = [

        'id_seguimiento' =>
            intval(
                $fila['id_seguimiento']
            ),

        'id_alerta' =>
            intval(
                $fila['id_alerta']
            ),

        'fecha' =>
            $fila['fecha'],

        'observacion' =>
            $fila['observacion'],

        'estado' =>
            $fila['estado'],


        'psicologo' => [

            'id' =>
                intval(
                    $fila['id_psicologo']
                ),

            'nombre' =>
                trim(
                    $fila['nombre_psicologo']
                    . ' '
                    .
                    $fila['apellido_psicologo']
                )

        ],


        'estudiante' => [

            'id' =>
                intval(
                    $fila['id_estudiante']
                ),

            'nombre' =>
                $fila['nombre_estudiante'],

            'apellido' =>
                $fila['apellido_estudiante'],

            'curso' =>
                $fila['curso']

        ],


        'riesgo' => [

            'id_nivel' =>
                intval(
                    $fila['id_nivel']
                ),

            'nombre' =>
                $fila['nivel_riesgo'],

            'color' =>
                $fila['color_riesgo']

        ]

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
        count($seguimientos),

    'casos_en_seguimiento' =>
        $seguimientos

], JSON_UNESCAPED_UNICODE);

?>