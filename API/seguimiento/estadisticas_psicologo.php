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
| ALERTAS POR ESTADO
|--------------------------------------------------------------------------
*/

$sql_estados = "
    SELECT
        estado,
        COUNT(*) AS total
    FROM alerta
    GROUP BY estado
";

$resultado_estados =
    mysqli_query(
        $conexion,
        $sql_estados
    );


if (!$resultado_estados) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'No se pudieron obtener los estados de las alertas',
        'error' => mysqli_error($conexion)
    ], JSON_UNESCAPED_UNICODE);

    mysqli_close($conexion);

    exit;
}


$pendientes = 0;
$en_seguimiento = 0;
$cerradas = 0;


while (
    $fila = mysqli_fetch_assoc(
        $resultado_estados
    )
) {

    $estado = strtolower(
        trim(
            $fila['estado']
        )
    );

    $total = intval(
        $fila['total']
    );


    if ($estado === 'pendiente') {

        $pendientes = $total;

    } elseif (
        $estado === 'en seguimiento'
    ) {

        $en_seguimiento = $total;

    } elseif (
        $estado === 'cerrada'
    ) {

        $cerradas = $total;

    }

}


/*
|--------------------------------------------------------------------------
| ESTUDIANTES CON EVALUACIONES
|--------------------------------------------------------------------------
*/

$sql_estudiantes = "
    SELECT
        COUNT(DISTINCT id_estudiante) AS total
    FROM evaluacion
";

$resultado_estudiantes =
    mysqli_query(
        $conexion,
        $sql_estudiantes
    );


if (!$resultado_estudiantes) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'No se pudieron obtener los estudiantes evaluados',
        'error' => mysqli_error($conexion)
    ], JSON_UNESCAPED_UNICODE);

    mysqli_close($conexion);

    exit;
}


$fila_estudiantes =
    mysqli_fetch_assoc(
        $resultado_estudiantes
    );


$estudiantes_evaluados =
    intval(
        $fila_estudiantes['total']
    );


/*
|--------------------------------------------------------------------------
| TOTAL DE EVALUACIONES
|--------------------------------------------------------------------------
*/

$sql_evaluaciones = "
    SELECT
        COUNT(*) AS total
    FROM evaluacion
";

$resultado_evaluaciones =
    mysqli_query(
        $conexion,
        $sql_evaluaciones
    );


$total_evaluaciones = 0;


if ($resultado_evaluaciones) {

    $fila_evaluaciones =
        mysqli_fetch_assoc(
            $resultado_evaluaciones
        );

    $total_evaluaciones =
        intval(
            $fila_evaluaciones['total']
        );

}


/*
|--------------------------------------------------------------------------
| NIVELES DE RIESGO
|--------------------------------------------------------------------------
*/

$sql_riesgos = "
    SELECT
        id_nivel,
        COUNT(*) AS total
    FROM analisis_ia
    GROUP BY id_nivel
";

$resultado_riesgos =
    mysqli_query(
        $conexion,
        $sql_riesgos
    );


if (!$resultado_riesgos) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'No se pudieron obtener los niveles de riesgo',
        'error' => mysqli_error($conexion)
    ], JSON_UNESCAPED_UNICODE);

    mysqli_close($conexion);

    exit;
}


$nivel_1 = 0;
$nivel_2 = 0;
$nivel_3 = 0;
$nivel_4 = 0;


while (
    $fila = mysqli_fetch_assoc(
        $resultado_riesgos
    )
) {

    $nivel =
        intval(
            $fila['id_nivel']
        );

    $total =
        intval(
            $fila['total']
        );


    switch ($nivel) {

        case 1:
            $nivel_1 = $total;
            break;

        case 2:
            $nivel_2 = $total;
            break;

        case 3:
            $nivel_3 = $total;
            break;

        case 4:
            $nivel_4 = $total;
            break;

    }

}


/*
|--------------------------------------------------------------------------
| TOTAL DE ALERTAS
|--------------------------------------------------------------------------
*/

$total_alertas =
    $pendientes
    + $en_seguimiento
    + $cerradas;


/*
|--------------------------------------------------------------------------
| CERRAR CONEXIÓN
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

    'estadisticas' => [

        'alertas' => [

            'pendientes' =>
                $pendientes,

            'en_seguimiento' =>
                $en_seguimiento,

            'cerradas' =>
                $cerradas,

            'total' =>
                $total_alertas

        ],

        'estudiantes' => [

            'evaluados' =>
                $estudiantes_evaluados

        ],

        'evaluaciones' => [

            'total' =>
                $total_evaluaciones

        ],

        'riesgo' => [

            'nivel_1' =>
                $nivel_1,

            'nivel_2' =>
                $nivel_2,

            'nivel_3' =>
                $nivel_3,

            'nivel_4' =>
                $nivel_4

        ]

    ]

], JSON_UNESCAPED_UNICODE);

?>