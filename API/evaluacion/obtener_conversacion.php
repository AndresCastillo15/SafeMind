<?php

header('Content-Type: application/json; charset=utf-8');

error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once '../config/conexion.php';

try {

    $conexion = conectar();

    if (!$conexion) {
        throw new Exception('No se pudo establecer la conexión con la base de datos');
    }

    if (!isset($_POST['chat_id']) || empty($_POST['chat_id'])) {
        throw new Exception('Falta el parámetro chat_id');
    }

    if (!isset($_POST['id_evaluacion']) || empty($_POST['id_evaluacion'])) {
        throw new Exception('Falta el parámetro id_evaluacion');
    }

    $chat_id = trim($_POST['chat_id']);
    $id_evaluacion = intval($_POST['id_evaluacion']);


    /*
    |--------------------------------------------------------------------------
    | COMPROBAR EVALUACIÓN
    |--------------------------------------------------------------------------
    */

    $sql = "
        SELECT e.id_evaluacion
        FROM evaluacion e
        INNER JOIN estudiante s
            ON e.id_estudiante = s.id_estudiante
        WHERE e.id_evaluacion = ?
          AND s.chat_id_telegram = ?
        LIMIT 1
    ";

    $stmt = mysqli_prepare($conexion, $sql);

    if (!$stmt) {
        throw new Exception(
            'Error al preparar consulta de evaluación: ' .
            mysqli_error($conexion)
        );
    }

    if (!mysqli_stmt_bind_param($stmt, "is", $id_evaluacion, $chat_id)) {
        throw new Exception(
            'Error en bind_param de evaluación: ' .
            mysqli_stmt_error($stmt)
        );
    }

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception(
            'Error ejecutando consulta de evaluación: ' .
            mysqli_stmt_error($stmt)
        );
    }

    mysqli_stmt_store_result($stmt);

    $existe_evaluacion = mysqli_stmt_num_rows($stmt);

    mysqli_stmt_close($stmt);

    if ($existe_evaluacion === 0) {

        mysqli_close($conexion);

        echo json_encode([
            'success' => false,
            'mensaje' => 'La evaluación no pertenece al estudiante',
            'debug' => [
                'chat_id' => $chat_id,
                'id_evaluacion' => $id_evaluacion
            ]
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | OBTENER MENSAJES
    |--------------------------------------------------------------------------
    */

    $sql = "
        SELECT
            id_mensaje,
            remitente,
            contenido,
            fecha_hora
        FROM mensaje
        WHERE id_evaluacion = ?
        ORDER BY id_mensaje ASC
    ";

    $stmt = mysqli_prepare($conexion, $sql);

    if (!$stmt) {
        throw new Exception(
            'Error al preparar consulta de mensajes: ' .
            mysqli_error($conexion)
        );
    }

    if (!mysqli_stmt_bind_param($stmt, "i", $id_evaluacion)) {
        throw new Exception(
            'Error en bind_param de mensajes: ' .
            mysqli_stmt_error($stmt)
        );
    }

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception(
            'Error ejecutando consulta de mensajes: ' .
            mysqli_stmt_error($stmt)
        );
    }

    mysqli_stmt_bind_result(
        $stmt,
        $id_mensaje,
        $remitente,
        $contenido,
        $fecha_hora
    );

    $mensajes = [];

    while (mysqli_stmt_fetch($stmt)) {

        $mensajes[] = [
            'id_mensaje' => (int) $id_mensaje,
            'remitente' => $remitente,
            'contenido' => $contenido,
            'fecha_hora' => $fecha_hora
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
        'id_evaluacion' => $id_evaluacion,
        'cantidad_mensajes' => count($mensajes),
        'mensajes' => $mensajes
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {

    http_response_code(200);

    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

?>