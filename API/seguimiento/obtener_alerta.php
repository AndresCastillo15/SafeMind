<?php

/*
|--------------------------------------------------------------------------
| SafeMind - Obtener detalle de una alerta
|--------------------------------------------------------------------------
| Endpoint privado: solo un psicólogo autenticado puede consultar
| datos personales, análisis y mensajes de un caso.
|--------------------------------------------------------------------------
*/

session_start();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

ini_set('display_errors', '0');
ini_set('log_errors', '1');

function responder_json(int $codigo_http, array $datos): void
{
    http_response_code($codigo_http);
    echo json_encode(
        $datos,
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );
    exit;
}

/*
|--------------------------------------------------------------------------
| 1. Validar sesión y rol
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['rol'], $_SESSION['id_usuario']) ||
    $_SESSION['rol'] !== 'psicologo' ||
    (int) $_SESSION['id_usuario'] <= 0
) {
    responder_json(403, [
        'success' => false,
        'mensaje' => 'No autorizado.'
    ]);
}

$id_psicologo = (int) $_SESSION['id_usuario'];

/*
|--------------------------------------------------------------------------
| 2. Conectar y verificar que la cuenta siga activa
|--------------------------------------------------------------------------
*/

require_once '../config/conexion.php';

try {
    $conexion = conectar();

    if (!$conexion) {
        throw new RuntimeException('No fue posible conectar con la base de datos.');
    }

    mysqli_set_charset($conexion, 'utf8mb4');

    $sql_psicologo = "
        SELECT id_psicologo
        FROM psicologo
        WHERE id_psicologo = ?
          AND estado = 'Activo'
        LIMIT 1
    ";

    $stmt_psicologo = mysqli_prepare($conexion, $sql_psicologo);
    mysqli_stmt_bind_param($stmt_psicologo, 'i', $id_psicologo);
    mysqli_stmt_execute($stmt_psicologo);

    $resultado_psicologo = mysqli_stmt_get_result($stmt_psicologo);
    $psicologo_activo = mysqli_num_rows($resultado_psicologo) === 1;

    mysqli_stmt_close($stmt_psicologo);

    if (!$psicologo_activo) {
        mysqli_close($conexion);
        responder_json(403, [
            'success' => false,
            'mensaje' => 'La cuenta no está autorizada para consultar casos.'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 3. Validar el identificador de la alerta
    |--------------------------------------------------------------------------
    */

    $id_alerta = filter_input(
        INPUT_GET,
        'id_alerta',
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if (!$id_alerta) {
        mysqli_close($conexion);
        responder_json(400, [
            'success' => false,
            'mensaje' => 'ID de alerta no válido.'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 4. Obtener información de la alerta y del estudiante
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
        est.correo,
        est.telefono

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
    mysqli_stmt_bind_param($stmt, 'i', $id_alerta);
    mysqli_stmt_execute($stmt);

    $resultado = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($resultado) !== 1) {
        mysqli_stmt_close($stmt);
        mysqli_close($conexion);

        responder_json(404, [
            'success' => false,
            'mensaje' => 'La alerta no existe.'
        ]);
    }

    $fila = mysqli_fetch_assoc($resultado);
    mysqli_stmt_close($stmt);

    /*
    |--------------------------------------------------------------------------
    | 5. Obtener mensajes de la evaluación
    |--------------------------------------------------------------------------
    */

    $sql_mensajes = "
        SELECT id_mensaje, remitente, contenido, fecha_hora
        FROM mensaje
        WHERE id_evaluacion = ?
        ORDER BY fecha_hora ASC, id_mensaje ASC
    ";

    $stmt_mensajes = mysqli_prepare($conexion, $sql_mensajes);
    mysqli_stmt_bind_param($stmt_mensajes, 'i', $fila['id_evaluacion']);
    mysqli_stmt_execute($stmt_mensajes);

    $resultado_mensajes = mysqli_stmt_get_result($stmt_mensajes);
    $mensajes = [];

    while ($mensaje = mysqli_fetch_assoc($resultado_mensajes)) {
        $mensajes[] = [
            'id' => (int) $mensaje['id_mensaje'],
            'remitente' => $mensaje['remitente'],
            'contenido' => $mensaje['contenido'],
            'fecha' => $mensaje['fecha_hora']
        ];
    }

    mysqli_stmt_close($stmt_mensajes);
    mysqli_close($conexion);

    /*
    |--------------------------------------------------------------------------
    | 6. Respuesta JSON
    |--------------------------------------------------------------------------
    */

    responder_json(200, [
        'success' => true,

        'alerta' => [
            'id_alerta' => (int) $fila['id_alerta'],
            'estado' => $fila['estado_alerta'],
            'fecha' => $fila['fecha_alerta']
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
            'id' => (int) $fila['id_evaluacion'],
            'fecha_inicio' => $fila['fecha_inicio'],
            'fecha_fin' => $fila['fecha_fin'],
            'estado' => $fila['estado_evaluacion']
        ],

        'riesgo' => [
            'id_nivel' => (int) $fila['id_nivel'],
            'nombre' => $fila['nivel_riesgo'],
            'color' => $fila['color_riesgo']
        ],

        'analisis' => [
            'id' => (int) $fila['id_analisis'],
            'emocion' => $fila['emocion_detectada'],
            'confianza' => (float) $fila['porcentaje_confianza'],
            'resumen' => $fila['resumen']
        ],

        'mensajes' => $mensajes
    ]);

} catch (Throwable $e) {
    error_log('[SafeMind obtener_alerta] ' . $e->getMessage());

    responder_json(500, [
        'success' => false,
        'mensaje' => 'Ocurrió un error interno al consultar el caso.'
    ]);
}

?>
