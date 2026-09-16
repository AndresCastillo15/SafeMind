<?php

header('Content-Type: application/json; charset=utf-8');

require_once '../config/conexion.php';

$conexion = conectar();


/*
|--------------------------------------------------------------------------
| VALIDAR CHAT_ID
|--------------------------------------------------------------------------
*/

if (!isset($_POST['chat_id']) || empty($_POST['chat_id'])) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'No se recibió el chat_id'
    ], JSON_UNESCAPED_UNICODE);

    mysqli_close($conexion);
    exit;
}

$chat_id = trim($_POST['chat_id']);


/*
|--------------------------------------------------------------------------
| 1. BUSCAR REGISTRO TEMPORAL
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
        'mensaje' => 'Error al preparar consulta de registro temporal'
    ], JSON_UNESCAPED_UNICODE);

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
        'estado_conversacion' => null,
        'id_evaluacion' => null,
        'paso' => $registro['paso'],
        'datos' => [
            'nombre' => $registro['nombre'],
            'apellido' => $registro['apellido'],
            'correo' => $registro['correo'],
            'telefono' => $registro['telefono'],
            'curso' => $registro['curso']
        ]
    ], JSON_UNESCAPED_UNICODE);

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
        'mensaje' => 'Error al preparar consulta del estudiante'
    ], JSON_UNESCAPED_UNICODE);

    mysqli_close($conexion);
    exit;
}

mysqli_stmt_bind_param($stmt, "s", $chat_id);
mysqli_stmt_execute($stmt);

$resultado = mysqli_stmt_get_result($stmt);


/*
|--------------------------------------------------------------------------
| SI NO EXISTE EL ESTUDIANTE
|--------------------------------------------------------------------------
*/

if (mysqli_num_rows($resultado) === 0) {

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    echo json_encode([
        'success' => true,
        'tipo_usuario' => 'nuevo',
        'existe_registro' => false,
        'registrado' => false,
        'evaluacion_pendiente' => false,
        'estado_conversacion' => null,
        'id_evaluacion' => null,
        'paso' => null,
        'mensaje' => 'No existe un registro para este chat_id'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$estudiante = mysqli_fetch_assoc($resultado);

mysqli_stmt_close($stmt);

$id_estudiante = intval($estudiante['id_estudiante']);


/*
|--------------------------------------------------------------------------
| 3. BUSCAR EVALUACIÓN ACTIVA EN CONVERSACIÓN
|--------------------------------------------------------------------------
|
| Este caso tiene prioridad.
|
*/

$sql = "
    SELECT
        id_evaluacion,
        estado_animo,
        estado_conversacion,
        fecha_inicio
    FROM evaluacion
    WHERE id_estudiante = ?
      AND estado = 'Activa'
      AND estado_conversacion = 'en_conversacion'
    ORDER BY id_evaluacion DESC
    LIMIT 1
";

$stmt = mysqli_prepare($conexion, $sql);

if (!$stmt) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'Error al buscar conversación activa'
    ], JSON_UNESCAPED_UNICODE);

    mysqli_close($conexion);
    exit;
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id_estudiante
);

mysqli_stmt_execute($stmt);

$resultado_conversacion = mysqli_stmt_get_result($stmt);


/*
|--------------------------------------------------------------------------
| EXISTE CONVERSACIÓN ACTIVA
|--------------------------------------------------------------------------
*/

if (mysqli_num_rows($resultado_conversacion) > 0) {

    $evaluacion = mysqli_fetch_assoc($resultado_conversacion);

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    echo json_encode([
        'success' => true,
        'tipo_usuario' => 'estudiante_en_conversacion',
        'existe_registro' => true,
        'registrado' => true,
        'evaluacion_pendiente' => false,
        'estado_conversacion' => 'en_conversacion',
        'id_estudiante' => $id_estudiante,
        'id_evaluacion' => intval($evaluacion['id_evaluacion']),
        'estado_animo' => $evaluacion['estado_animo'],
        'paso' => 'en_conversacion',
        'datos' => [
            'nombre' => $estudiante['nombre'],
            'apellido' => $estudiante['apellido'],
            'correo' => $estudiante['correo'],
            'telefono' => $estudiante['telefono'],
            'curso' => $estudiante['curso']
        ]
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

mysqli_stmt_close($stmt);


/*
|--------------------------------------------------------------------------
| 4. BUSCAR EVALUACIÓN ESPERANDO DESCRIPCIÓN
|--------------------------------------------------------------------------
|
| Debe:
| - estar activa
| - estar esperando_descripcion
| - tener estado_animo
|
*/

$sql = "
    SELECT
        id_evaluacion,
        estado_animo,
        estado_conversacion,
        fecha_inicio
    FROM evaluacion
    WHERE id_estudiante = ?
      AND estado = 'Activa'
      AND estado_conversacion = 'esperando_descripcion'
      AND estado_animo IS NOT NULL
    ORDER BY id_evaluacion DESC
    LIMIT 1
";

$stmt = mysqli_prepare($conexion, $sql);

if (!$stmt) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'Error al buscar evaluación pendiente'
    ], JSON_UNESCAPED_UNICODE);

    mysqli_close($conexion);
    exit;
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id_estudiante
);

mysqli_stmt_execute($stmt);

$resultado_pendiente = mysqli_stmt_get_result($stmt);


/*
|--------------------------------------------------------------------------
| EVALUACIÓN PENDIENTE
|--------------------------------------------------------------------------
*/

if (mysqli_num_rows($resultado_pendiente) > 0) {

    $evaluacion = mysqli_fetch_assoc($resultado_pendiente);

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    echo json_encode([
        'success' => true,
        'tipo_usuario' => 'estudiante',
        'existe_registro' => true,
        'registrado' => true,
        'evaluacion_pendiente' => true,
        'estado_conversacion' => 'esperando_descripcion',
        'id_estudiante' => $id_estudiante,
        'id_evaluacion' => intval($evaluacion['id_evaluacion']),
        'estado_animo' => $evaluacion['estado_animo'],
        'paso' => 'pendiente_descripcion',
        'datos' => [
            'nombre' => $estudiante['nombre'],
            'apellido' => $estudiante['apellido'],
            'correo' => $estudiante['correo'],
            'telefono' => $estudiante['telefono'],
            'curso' => $estudiante['curso']
        ]
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

mysqli_stmt_close($stmt);


/*
|--------------------------------------------------------------------------
| 5. ESTUDIANTE REGISTRADO SIN CONVERSACIÓN ACTIVA
|--------------------------------------------------------------------------
*/

mysqli_close($conexion);

echo json_encode([
    'success' => true,
    'tipo_usuario' => 'estudiante',
    'existe_registro' => true,
    'registrado' => true,
    'evaluacion_pendiente' => false,
    'estado_conversacion' => null,
    'id_estudiante' => $id_estudiante,
    'id_evaluacion' => null,
    'estado_animo' => null,
    'paso' => 'estudiante_registrado',
    'datos' => [
        'nombre' => $estudiante['nombre'],
        'apellido' => $estudiante['apellido'],
        'correo' => $estudiante['correo'],
        'telefono' => $estudiante['telefono'],
        'curso' => $estudiante['curso']
    ]
], JSON_UNESCAPED_UNICODE);

?>