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

const ESTADOS_ALERTA_EDITABLES = ['Pendiente', 'En seguimiento'];
const ESTADO_SEGUIMIENTO = 'En seguimiento';
const MAX_OBSERVACION = 5000;

function responder_json(int $codigo_http, array $datos): void
{
    http_response_code($codigo_http);

    echo json_encode(
        $datos,
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );

    exit;
}

/**
 * Deshace la transacción, cierra la conexión y responde con un error.
 */
function cortar(?mysqli $conexion, int $codigo_http, string $mensaje): void
{
    if ($conexion instanceof mysqli) {
        mysqli_rollback($conexion);
        mysqli_close($conexion);
    }

    responder_json($codigo_http, [
        'success' => false,
        'mensaje' => $mensaje
    ]);
}

/**
 * Prepara, enlaza y ejecuta una consulta. Devuelve el statement (ya ejecutado).
 */
function preparar_ejecutar(mysqli $conexion, string $sql, string $tipos = '', array $valores = []): mysqli_stmt
{
    $stmt = mysqli_prepare($conexion, $sql);

    if ($stmt === false) {
        throw new RuntimeException(
            'No se pudo preparar la consulta: ' . mysqli_error($conexion)
        );
    }

    if ($tipos !== '') {
        $referencias = [];

        foreach ($valores as $clave => $_) {
            $referencias[$clave] = &$valores[$clave];
        }

        call_user_func_array(
            [$stmt, 'bind_param'],
            array_merge([$tipos], $referencias)
        );
    }

    if (!mysqli_stmt_execute($stmt)) {
        $error = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);

        throw new RuntimeException('No se pudo ejecutar la consulta: ' . $error);
    }

    return $stmt;
}

/**
 * Ejecuta una consulta SELECT y devuelve la primera fila, o null si no hay.
 */
function fila_unica(mysqli $conexion, string $sql, string $tipos, array $valores): ?array
{
    $stmt = preparar_ejecutar($conexion, $sql, $tipos, $valores);

    $resultado = mysqli_stmt_get_result($stmt);
    $fila = $resultado ? mysqli_fetch_assoc($resultado) : null;

    if ($resultado instanceof mysqli_result) {
        mysqli_free_result($resultado);
    }

    mysqli_stmt_close($stmt);

    return is_array($fila) ? $fila : null;
}


/*
|--------------------------------------------------------------------------
| 1. Método HTTP
|--------------------------------------------------------------------------
*/

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    responder_json(405, ['success' => false, 'mensaje' => 'Método no permitido.']);
}


/*
|--------------------------------------------------------------------------
| 2. Sesión y rol
|--------------------------------------------------------------------------
*/

$id_psicologo = filter_var(
    $_SESSION['id_usuario'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if (($_SESSION['rol'] ?? '') !== 'psicologo' || $id_psicologo === false) {
    responder_json(403, ['success' => false, 'mensaje' => 'No autorizado.']);
}


/*
|--------------------------------------------------------------------------
| 3. Token CSRF
|--------------------------------------------------------------------------
| El formato se revisa antes que la comparación: así un token vacío
| en sesión nunca coincide con un token vacío enviado.
*/

$csrf_sesion = $_SESSION['csrf_token'] ?? '';
$csrf_recibido = $_POST['csrf_token'] ?? '';

if (
    !is_string($csrf_sesion) ||
    !preg_match('/\A[a-f0-9]{64}\z/', $csrf_sesion) ||
    !is_string($csrf_recibido) ||
    !hash_equals($csrf_sesion, $csrf_recibido)
) {
    responder_json(403, [
        'success' => false,
        'mensaje' => 'Solicitud no autorizada. Actualiza la página e inténtalo de nuevo.'
    ]);
}


/*
|--------------------------------------------------------------------------
| 4. Datos recibidos
|--------------------------------------------------------------------------
*/

$id_alerta = filter_var(
    $_POST['id_alerta'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

$observacion = is_string($_POST['observacion'] ?? null)
    ? trim($_POST['observacion'])
    : '';

if ($id_alerta === false) {
    responder_json(400, ['success' => false, 'mensaje' => 'ID de alerta no válido.']);
}

if ($observacion === '') {
    responder_json(400, ['success' => false, 'mensaje' => 'La observación es obligatoria.']);
}

if (mb_strlen($observacion, 'UTF-8') > MAX_OBSERVACION) {
    responder_json(400, [
        'success' => false,
        'mensaje' => 'La observación no puede superar los 5000 caracteres.'
    ]);
}


/*
|--------------------------------------------------------------------------
| 5. Transacción
|--------------------------------------------------------------------------
*/

$conexion = null;

try {
    require_once __DIR__ . '/../config/conexion.php';

    $conexion = conectar();

    if (!$conexion instanceof mysqli) {
        throw new RuntimeException('La conexión a la base de datos no es válida.');
    }

    if (!mysqli_begin_transaction($conexion)) {
        throw new RuntimeException('No se pudo iniciar la transacción.');
    }

    // Bloquea al psicólogo: si lo desactivan a mitad de la operación, falla.
    $psicologo = fila_unica(
        $conexion,
        "SELECT id_psicologo FROM psicologo
         WHERE id_psicologo = ? AND estado = 'Activo'
         LIMIT 1 FOR UPDATE",
        'i',
        [$id_psicologo]
    );

    if ($psicologo === null) {
        cortar($conexion, 403, 'No autorizado.');
    }

    // Bloquea la alerta: dos psicólogos no pueden cambiarla a la vez.
    $alerta = fila_unica(
        $conexion,
        'SELECT id_alerta, estado FROM alerta WHERE id_alerta = ? LIMIT 1 FOR UPDATE',
        'i',
        [$id_alerta]
    );

    if ($alerta === null) {
        cortar($conexion, 404, 'La alerta no existe.');
    }

    if (!in_array(trim((string) $alerta['estado']), ESTADOS_ALERTA_EDITABLES, true)) {
        cortar($conexion, 409, 'Esta alerta está cerrada o no admite nuevos seguimientos.');
    }

    $stmt = preparar_ejecutar(
        $conexion,
        'INSERT INTO seguimiento (id_alerta, id_psicologo, observacion, estado)
         VALUES (?, ?, ?, ?)',
        'iiss',
        [$id_alerta, $id_psicologo, $observacion, ESTADO_SEGUIMIENTO]
    );
    $id_seguimiento = mysqli_insert_id($conexion);
    mysqli_stmt_close($stmt);

    $stmt = preparar_ejecutar(
        $conexion,
        'UPDATE alerta SET estado = ? WHERE id_alerta = ?',
        'si',
        [ESTADO_SEGUIMIENTO, $id_alerta]
    );
    mysqli_stmt_close($stmt);

    if (!mysqli_commit($conexion)) {
        throw new RuntimeException('No se pudo confirmar la transacción.');
    }

    mysqli_close($conexion);

    responder_json(200, [
        'success' => true,
        'mensaje' => 'Seguimiento registrado correctamente.',
        'id_seguimiento' => (int) $id_seguimiento,
        'id_alerta' => $id_alerta,
        'estado_alerta' => ESTADO_SEGUIMIENTO,
        'estado_seguimiento' => ESTADO_SEGUIMIENTO
    ]);

} catch (Throwable $e) {
    if ($conexion instanceof mysqli) {
        mysqli_rollback($conexion);
        mysqli_close($conexion);
    }

    error_log('SafeMind registrar_seguimiento.php: ' . $e->getMessage());

    responder_json(500, [
        'success' => false,
        'mensaje' => 'No se pudo registrar el seguimiento en este momento.'
    ]);
}