<?php
/**
 * SafeMind - Historial de seguimientos de una alerta.
 * Acceso exclusivo para psicólogos autenticados y activos.
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

/**
 * Envía una respuesta JSON y finaliza la petición.
 */
function responder_json(int $codigo_http, array $datos): void
{
    http_response_code($codigo_http);

    echo json_encode(
        $datos,
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );

    exit;
}

// 1. Aceptar únicamente solicitudes GET.
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    header('Allow: GET');

    responder_json(405, [
        'success' => false,
        'mensaje' => 'Método no permitido.'
    ]);
}

// 2. Validar la sesión y el rol.
$rol = $_SESSION['rol'] ?? '';

$idUsuario = filter_var(
    $_SESSION['id_usuario'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if (
    $rol !== 'psicologo' ||
    $idUsuario === false ||
    $idUsuario === null
) {
    responder_json(403, [
        'success' => false,
        'mensaje' => 'No autorizado.'
    ]);
}

// 3. Validar el identificador de la alerta.
$id_alerta = filter_input(
    INPUT_GET,
    'id_alerta',
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if ($id_alerta === false || $id_alerta === null) {
    responder_json(400, [
        'success' => false,
        'mensaje' => 'ID de alerta no válido.'
    ]);
}

$conexion = null;

try {
    // 4. Conectar a la base de datos.
    require_once __DIR__ . '/../config/conexion.php';

    $conexion = conectar();

    if (!$conexion instanceof mysqli) {
        throw new RuntimeException(
            'La conexión a la base de datos no es válida.'
        );
    }

    // 5. Verificar que el psicólogo continúe activo.
    $sqlPermiso = "
        SELECT id_psicologo
        FROM psicologo
        WHERE id_psicologo = ?
          AND estado = 'Activo'
        LIMIT 1
    ";

    $stmtPermiso = mysqli_prepare(
        $conexion,
        $sqlPermiso
    );

    if (!$stmtPermiso) {
        throw new RuntimeException(
            'No se pudo preparar la validación de la cuenta.'
        );
    }

    mysqli_stmt_bind_param(
        $stmtPermiso,
        'i',
        $idUsuario
    );

    if (!mysqli_stmt_execute($stmtPermiso)) {
        mysqli_stmt_close($stmtPermiso);

        throw new RuntimeException(
            'No se pudo validar la cuenta del psicólogo.'
        );
    }

    mysqli_stmt_store_result($stmtPermiso);

    $psicologoActivo =
        mysqli_stmt_num_rows($stmtPermiso) === 1;

    mysqli_stmt_close($stmtPermiso);

    if (!$psicologoActivo) {
        mysqli_close($conexion);
        $conexion = null;

        responder_json(403, [
            'success' => false,
            'mensaje' => 'No autorizado.'
        ]);
    }

    // 6. Consultar los seguimientos asociados a la alerta.
    $sql = "
        SELECT
            s.id_seguimiento,
            s.id_alerta,
            s.id_psicologo,
            s.fecha,
            s.observacion,
            s.estado,

            p.nombre AS nombre_psicologo,
            p.apellido AS apellido_psicologo

        FROM seguimiento s

        INNER JOIN psicologo p
            ON s.id_psicologo = p.id_psicologo

        WHERE s.id_alerta = ?

        ORDER BY s.fecha DESC
    ";

    $stmt = mysqli_prepare(
        $conexion,
        $sql
    );

    if (!$stmt) {
        throw new RuntimeException(
            'No se pudo preparar la consulta de seguimientos.'
        );
    }

    mysqli_stmt_bind_param(
        $stmt,
        'i',
        $id_alerta
    );

    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);

        throw new RuntimeException(
            'No se pudo ejecutar la consulta de seguimientos.'
        );
    }

    $resultado = mysqli_stmt_get_result($stmt);

    if ($resultado === false) {
        mysqli_stmt_close($stmt);

        throw new RuntimeException(
            'No se pudo obtener el resultado de la consulta.'
        );
    }

    // 7. Construir la respuesta.
    $seguimientos = [];

    while ($fila = mysqli_fetch_assoc($resultado)) {
        $seguimientos[] = [
            'id_seguimiento' =>
                (int) $fila['id_seguimiento'],

            'id_alerta' =>
                (int) $fila['id_alerta'],

            'psicologo' => [
                'id' =>
                    (int) $fila['id_psicologo'],

                'nombre' => trim(
                    $fila['nombre_psicologo'] . ' ' .
                    $fila['apellido_psicologo']
                )
            ],

            'fecha' =>
                $fila['fecha'],

            'observacion' =>
                $fila['observacion'],

            'estado' =>
                $fila['estado']
        ];
    }

    mysqli_free_result($resultado);
    mysqli_stmt_close($stmt);

    mysqli_close($conexion);
    $conexion = null;

    // 8. Mantener el formato que consume el dashboard.
    responder_json(200, [
        'success' => true,
        'total' => count($seguimientos),
        'id_alerta' => (int) $id_alerta,
        'seguimientos' => $seguimientos
    ]);

} catch (Throwable $e) {
    // Registrar el error técnico solo en el servidor.
    error_log(
        'SafeMind obtener_seguimientos.php: ' .
        $e->getMessage()
    );

    if ($conexion instanceof mysqli) {
        mysqli_close($conexion);
    }

    responder_json(500, [
        'success' => false,
        'mensaje' =>
            'No se pudo cargar el historial de seguimientos en este momento.'
    ]);
}
?>