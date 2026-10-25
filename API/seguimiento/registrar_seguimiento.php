<?php
/**
 * SafeMind - Registro de seguimiento profesional.
 * Solo para psicólogos autenticados y activos.
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

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
| 1. Validar método HTTP
|--------------------------------------------------------------------------
*/

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');

    responder_json(405, [
        'success' => false,
        'mensaje' => 'Método no permitido.'
    ]);
}

/*
|--------------------------------------------------------------------------
| 2. Validar sesión y rol
|--------------------------------------------------------------------------
*/

$rol = $_SESSION['rol'] ?? '';

$id_psicologo = filter_var(
    $_SESSION['id_usuario'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if (
    $rol !== 'psicologo' ||
    $id_psicologo === false ||
    $id_psicologo === null
) {
    responder_json(403, [
        'success' => false,
        'mensaje' => 'No autorizado.'
    ]);
}

/*
|--------------------------------------------------------------------------
| 3. Validar token CSRF
|--------------------------------------------------------------------------
*/

$csrf_sesion = $_SESSION['csrf_token'] ?? null;
$csrf_recibido = $_POST['csrf_token'] ?? null;

if (
    !is_string($csrf_sesion) ||
    !preg_match('/\A[a-f0-9]{64}\z/', $csrf_sesion) ||
    !is_string($csrf_recibido) ||
    !hash_equals($csrf_sesion, $csrf_recibido)
) {
    responder_json(403, [
        'success' => false,
        'mensaje' =>
            'Solicitud no autorizada. Actualiza la página e inténtalo de nuevo.'
    ]);
}

/*
|--------------------------------------------------------------------------
| 4. Validar datos recibidos
|--------------------------------------------------------------------------
*/

$id_alerta = filter_var(
    $_POST['id_alerta'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

$observacion = trim(
    (string) ($_POST['observacion'] ?? '')
);

$longitud_observacion = function_exists('mb_strlen')
    ? mb_strlen($observacion, 'UTF-8')
    : strlen($observacion);

if ($id_alerta === false || $id_alerta === null) {
    responder_json(400, [
        'success' => false,
        'mensaje' => 'ID de alerta no válido.'
    ]);
}

if ($observacion === '') {
    responder_json(400, [
        'success' => false,
        'mensaje' => 'La observación es obligatoria.'
    ]);
}

if ($longitud_observacion > 5000) {
    responder_json(400, [
        'success' => false,
        'mensaje' =>
            'La observación no puede superar los 5000 caracteres.'
    ]);
}

/*
|--------------------------------------------------------------------------
| 5. Conexión y transacción
|--------------------------------------------------------------------------
*/

$conexion = null;
$transaccion_iniciada = false;

try {
    require_once __DIR__ . '/../config/conexion.php';

    $conexion = conectar();

    if (!$conexion instanceof mysqli) {
        throw new RuntimeException(
            'La conexión a la base de datos no es válida.'
        );
    }

    if (!mysqli_begin_transaction($conexion)) {
        throw new RuntimeException(
            'No se pudo iniciar la transacción.'
        );
    }

    $transaccion_iniciada = true;

    /*
    |--------------------------------------------------------------------------
    | 6. Confirmar que el psicólogo siga activo
    |--------------------------------------------------------------------------
    */

    $sql_psicologo = "
        SELECT id_psicologo
        FROM psicologo
        WHERE id_psicologo = ?
          AND estado = 'Activo'
        LIMIT 1
        FOR UPDATE
    ";

    $stmt_psicologo = mysqli_prepare(
        $conexion,
        $sql_psicologo
    );

    if (!$stmt_psicologo) {
        throw new RuntimeException(
            'No se pudo preparar la validación del psicólogo.'
        );
    }

    mysqli_stmt_bind_param(
        $stmt_psicologo,
        'i',
        $id_psicologo
    );

    if (!mysqli_stmt_execute($stmt_psicologo)) {
        mysqli_stmt_close($stmt_psicologo);

        throw new RuntimeException(
            'No se pudo validar el estado de la cuenta.'
        );
    }

    $resultado_psicologo =
        mysqli_stmt_get_result($stmt_psicologo);

    $fila_psicologo = $resultado_psicologo !== false
        ? mysqli_fetch_assoc($resultado_psicologo)
        : false;

    $psicologo_activo = is_array($fila_psicologo);

    if ($resultado_psicologo instanceof mysqli_result) {
        mysqli_free_result($resultado_psicologo);
    }

    mysqli_stmt_close($stmt_psicologo);

    if (!$psicologo_activo) {
        mysqli_rollback($conexion);
        $transaccion_iniciada = false;

        mysqli_close($conexion);
        $conexion = null;

        responder_json(403, [
            'success' => false,
            'mensaje' => 'No autorizado.'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 7. Consultar y bloquear la alerta
    |--------------------------------------------------------------------------
    */

    $sql_alerta = "
        SELECT id_alerta, estado
        FROM alerta
        WHERE id_alerta = ?
        LIMIT 1
        FOR UPDATE
    ";

    $stmt_alerta = mysqli_prepare(
        $conexion,
        $sql_alerta
    );

    if (!$stmt_alerta) {
        throw new RuntimeException(
            'No se pudo preparar la consulta de la alerta.'
        );
    }

    mysqli_stmt_bind_param(
        $stmt_alerta,
        'i',
        $id_alerta
    );

    if (!mysqli_stmt_execute($stmt_alerta)) {
        mysqli_stmt_close($stmt_alerta);

        throw new RuntimeException(
            'No se pudo consultar la alerta.'
        );
    }

    $resultado_alerta =
        mysqli_stmt_get_result($stmt_alerta);

    if ($resultado_alerta === false) {
        mysqli_stmt_close($stmt_alerta);

        throw new RuntimeException(
            'No se pudo obtener el resultado de la alerta.'
        );
    }

    $fila_alerta = mysqli_fetch_assoc($resultado_alerta);

    mysqli_free_result($resultado_alerta);
    mysqli_stmt_close($stmt_alerta);

    if (!$fila_alerta) {
        mysqli_rollback($conexion);
        $transaccion_iniciada = false;

        mysqli_close($conexion);
        $conexion = null;

        responder_json(404, [
            'success' => false,
            'mensaje' => 'La alerta no existe.'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 8. Impedir cambios en alertas cerradas
    |--------------------------------------------------------------------------
    */

    $estado_actual = trim(
        (string) $fila_alerta['estado']
    );

    $estados_permitidos = [
        'Pendiente',
        'En seguimiento'
    ];

    if (!in_array($estado_actual, $estados_permitidos, true)) {
        mysqli_rollback($conexion);
        $transaccion_iniciada = false;

        mysqli_close($conexion);
        $conexion = null;

        responder_json(409, [
            'success' => false,
            'mensaje' =>
                'Esta alerta está cerrada o no admite nuevos seguimientos.'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 9. Registrar el seguimiento
    |--------------------------------------------------------------------------
    */

    $estado_seguimiento = 'En seguimiento';
    $estado_alerta = 'En seguimiento';

    $sql_insertar = "
        INSERT INTO seguimiento (
            id_alerta,
            id_psicologo,
            observacion,
            estado
        )
        VALUES (?, ?, ?, ?)
    ";

    $stmt_insertar = mysqli_prepare(
        $conexion,
        $sql_insertar
    );

    if (!$stmt_insertar) {
        throw new RuntimeException(
            'No se pudo preparar la inserción del seguimiento.'
        );
    }

    mysqli_stmt_bind_param(
        $stmt_insertar,
        'iiss',
        $id_alerta,
        $id_psicologo,
        $observacion,
        $estado_seguimiento
    );

    if (!mysqli_stmt_execute($stmt_insertar)) {
        mysqli_stmt_close($stmt_insertar);

        throw new RuntimeException(
            'No se pudo insertar el seguimiento.'
        );
    }

    $id_seguimiento = mysqli_insert_id($conexion);

    mysqli_stmt_close($stmt_insertar);

    /*
    |--------------------------------------------------------------------------
    | 10. Actualizar el estado de la alerta
    |--------------------------------------------------------------------------
    */

    $sql_actualizar = "
        UPDATE alerta
        SET estado = ?
        WHERE id_alerta = ?
    ";

    $stmt_actualizar = mysqli_prepare(
        $conexion,
        $sql_actualizar
    );

    if (!$stmt_actualizar) {
        throw new RuntimeException(
            'No se pudo preparar la actualización de la alerta.'
        );
    }

    mysqli_stmt_bind_param(
        $stmt_actualizar,
        'si',
        $estado_alerta,
        $id_alerta
    );

    if (!mysqli_stmt_execute($stmt_actualizar)) {
        mysqli_stmt_close($stmt_actualizar);

        throw new RuntimeException(
            'No se pudo actualizar el estado de la alerta.'
        );
    }

    mysqli_stmt_close($stmt_actualizar);

    /*
    |--------------------------------------------------------------------------
    | 11. Confirmar ambas operaciones
    |--------------------------------------------------------------------------
    */

    if (!mysqli_commit($conexion)) {
        throw new RuntimeException(
            'No se pudo confirmar la transacción.'
        );
    }

    $transaccion_iniciada = false;

    mysqli_close($conexion);
    $conexion = null;

    responder_json(200, [
        'success' => true,
        'mensaje' => 'Seguimiento registrado correctamente.',
        'id_seguimiento' => (int) $id_seguimiento,
        'id_alerta' => (int) $id_alerta,
        'estado_alerta' => $estado_alerta,
        'estado_seguimiento' => $estado_seguimiento
    ]);

} catch (Throwable $e) {
    if ($conexion instanceof mysqli) {
        if ($transaccion_iniciada) {
            mysqli_rollback($conexion);
        }

        mysqli_close($conexion);
    }

    error_log(
        'SafeMind registrar_seguimiento.php: ' .
        $e->getMessage()
    );

    responder_json(500, [
        'success' => false,
        'mensaje' =>
            'No se pudo registrar el seguimiento en este momento.'
    ]);
}
?>