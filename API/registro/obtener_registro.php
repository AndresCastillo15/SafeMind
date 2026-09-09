<?php

header('Content-Type: application/json; charset=utf-8');

require_once '../config/conexion.php';

$conexion = conectar();


/*
|--------------------------------------------------------------------------
| VERIFICAR CHAT_ID
|--------------------------------------------------------------------------
*/

if (!isset($_POST['chat_id']) || empty($_POST['chat_id'])) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'No se recibió el chat_id'
    ]);

    exit;
}

$chat_id = trim($_POST['chat_id']);


/*
|--------------------------------------------------------------------------
| 1. BUSCAR EN REGISTRO TEMPORAL
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        chat_id_telegram,
        paso,
        nombre,
        apellido,
        correo,
        telefono,
        curso
    FROM registro_temporal
    WHERE chat_id_telegram = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conexion, $sql);

if (!$stmt) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'Error al preparar la consulta del registro temporal'
    ]);

    mysqli_close($conexion);
    exit;
}

mysqli_stmt_bind_param($stmt, "s", $chat_id);
mysqli_stmt_execute($stmt);

$resultado = mysqli_stmt_get_result($stmt);


/*
|--------------------------------------------------------------------------
| SI EXISTE REGISTRO TEMPORAL
|--------------------------------------------------------------------------
*/

if (mysqli_num_rows($resultado) > 0) {

    $registro = mysqli_fetch_assoc($resultado);

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    echo json_encode([
        'success' => true,
        'tipo_usuario' => 'registro_temporal',
        'existe_registro' => true,
        'registrado' => false,
        'evaluacion_pendiente' => false,
        'paso' => $registro['paso'],
        'datos' => [
            'nombre' => $registro['nombre'],
            'apellido' => $registro['apellido'],
            'correo' => $registro['correo'],
            'telefono' => $registro['telefono'],
            'curso' => $registro['curso']
        ]
    ]);

    exit;
}

mysqli_stmt_close($stmt);


/*
|--------------------------------------------------------------------------
| 2. BUSCAR ESTUDIANTE DEFINITIVO
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        id_estudiante,
        nombre,
        apellido,
        correo,
        telefono,
        curso,
        estado
    FROM estudiante
    WHERE chat_id_telegram = ?
    AND estado = 'Activo'
    LIMIT 1
";

$stmt = mysqli_prepare($conexion, $sql);

if (!$stmt) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'Error al preparar la consulta del estudiante'
    ]);

    mysqli_close($conexion);
    exit;
}

mysqli_stmt_bind_param($stmt, "s", $chat_id);
mysqli_stmt_execute($stmt);

$resultado = mysqli_stmt_get_result($stmt);


/*
|--------------------------------------------------------------------------
| SI EL ESTUDIANTE EXISTE
|--------------------------------------------------------------------------
*/

if (mysqli_num_rows($resultado) > 0) {

    $estudiante = mysqli_fetch_assoc($resultado);

    mysqli_stmt_close($stmt);

    $id_estudiante = $estudiante['id_estudiante'];


    /*
    |--------------------------------------------------------------------------
    | 3. BUSCAR EVALUACIÓN ACTIVA PENDIENTE DE DESCRIPCIÓN
    |--------------------------------------------------------------------------
    |
    | La evaluación debe:
    | - pertenecer al estudiante
    | - estar activa
    | - tener estado_animo
    | - todavía no tener mensaje del estudiante
    |
    */

    $sql = "
        SELECT
            e.id_evaluacion,
            e.estado_animo,
            e.fecha_inicio
        FROM evaluacion e
        LEFT JOIN mensaje m
            ON m.id_evaluacion = e.id_evaluacion
            AND m.remitente = 'estudiante'
        WHERE e.id_estudiante = ?
        AND e.estado = 'Activa'
        AND e.estado_animo IS NOT NULL
        AND m.id_mensaje IS NULL
        ORDER BY e.id_evaluacion DESC
        LIMIT 1
    ";

    $stmt = mysqli_prepare($conexion, $sql);

    if (!$stmt) {

        echo json_encode([
            'success' => false,
            'mensaje' => 'Error al buscar la evaluación pendiente'
        ]);

        mysqli_close($conexion);
        exit;
    }

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $id_estudiante
    );

    mysqli_stmt_execute($stmt);

    $resultado_evaluacion = mysqli_stmt_get_result($stmt);


    /*
    |--------------------------------------------------------------------------
    | EXISTE EVALUACIÓN PENDIENTE DE DESCRIPCIÓN
    |--------------------------------------------------------------------------
    */

    if (mysqli_num_rows($resultado_evaluacion) > 0) {

        $evaluacion = mysqli_fetch_assoc(
            $resultado_evaluacion
        );

        mysqli_stmt_close($stmt);
        mysqli_close($conexion);

        echo json_encode([
            'success' => true,
            'tipo_usuario' => 'estudiante',
            'existe_registro' => true,
            'registrado' => true,
            'evaluacion_pendiente' => true,
            'id_estudiante' => $id_estudiante,
            'id_evaluacion' => $evaluacion['id_evaluacion'],
            'estado_animo' => $evaluacion['estado_animo'],
            'paso' => 'pendiente_descripcion',
            'datos' => [
                'nombre' => $estudiante['nombre'],
                'apellido' => $estudiante['apellido'],
                'correo' => $estudiante['correo'],
                'telefono' => $estudiante['telefono'],
                'curso' => $estudiante['curso']
            ]
        ]);

        exit;
    }

    mysqli_stmt_close($stmt);


    /*
    |--------------------------------------------------------------------------
    | 4. ESTUDIANTE REGISTRADO SIN DESCRIPCIÓN PENDIENTE
    |--------------------------------------------------------------------------
    */

    mysqli_close($conexion);

    echo json_encode([
        'success' => true,
        'tipo_usuario' => 'estudiante',
        'existe_registro' => true,
        'registrado' => true,
        'evaluacion_pendiente' => false,
        'id_estudiante' => $id_estudiante,
        'paso' => 'estudiante_registrado',
        'datos' => [
            'nombre' => $estudiante['nombre'],
            'apellido' => $estudiante['apellido'],
            'correo' => $estudiante['correo'],
            'telefono' => $estudiante['telefono'],
            'curso' => $estudiante['curso']
        ]
    ]);

    exit;
}

mysqli_stmt_close($stmt);


/*
|--------------------------------------------------------------------------
| 5. USUARIO COMPLETAMENTE NUEVO
|--------------------------------------------------------------------------
*/

mysqli_close($conexion);

echo json_encode([
    'success' => true,
    'tipo_usuario' => 'nuevo',
    'existe_registro' => false,
    'registrado' => false,
    'evaluacion_pendiente' => false,
    'paso' => null,
    'mensaje' => 'No existe un registro para este chat_id'
]);

?>