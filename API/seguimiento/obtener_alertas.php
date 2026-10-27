<?php
/**
 * SafeMind - Consulta de alertas pendientes.
 * Acceso exclusivo para psicólogos autenticados y activos.
 * Conserva la estructura JSON consumida por el panel.
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
 * Responde en JSON y termina la petición.
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

// Este endpoint es de solo lectura y acepta únicamente GET.
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    header('Allow: GET');

    responder_json(405, [
        'success' => false,
        'mensaje' => 'Método no permitido.'
    ]);
}

// Validar sesión y rol.
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

$conexion = null;

try {
    require_once __DIR__ . '/../config/conexion.php';

    $conexion = conectar();

    if (!$conexion instanceof mysqli) {
        throw new RuntimeException(
            'La conexión a la base de datos no es válida.'
        );
    }

    // Verificar que la cuenta del psicólogo siga activa.
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
            'No se pudo preparar la validación del psicólogo.'
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
            'No se pudo validar el estado del psicólogo.'
        );
    }

    mysqli_stmt_store_result($stmtPermiso);

    $psicologoActivo =
        mysqli_stmt_num_rows($stmtPermiso) === 1;

    mysqli_stmt_close($stmtPermiso);

    if (!$psicologoActivo) {
        responder_json(403, [
            'success' => false,
            'mensaje' => 'No autorizado.'
        ]);
    }

    // Consulta de alertas pendientes.
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

            est.id_estudiante,
            est.nombre,
            est.apellido,
            est.curso

        FROM alerta a

        INNER JOIN analisis_ia ai
            ON a.id_analisis = ai.id_analisis

        INNER JOIN nivel_riesgo nr
            ON ai.id_nivel = nr.id_nivel

        INNER JOIN evaluacion e
            ON ai.id_evaluacion = e.id_evaluacion

        INNER JOIN estudiante est
            ON e.id_estudiante = est.id_estudiante

        WHERE a.estado = 'Pendiente'

        ORDER BY a.fecha DESC
    ";

    $resultado = mysqli_query(
        $conexion,
        $sql
    );

    if (!$resultado) {
        throw new RuntimeException(
            'La consulta de alertas no pudo completarse.'
        );
    }

    $alertas = [];

    while ($fila = mysqli_fetch_assoc($resultado)) {
        $alertas[] = [
            'id_alerta' => (int) $fila['id_alerta'],

            'estado' => $fila['estado_alerta'],

            'fecha' => $fila['fecha_alerta'],

            'id_analisis' => (int) $fila['id_analisis'],

            'id_evaluacion' => (int) $fila['id_evaluacion'],

            'estudiante' => [
                'id' => (int) $fila['id_estudiante'],

                'nombre' => $fila['nombre'],

                'apellido' => $fila['apellido'],

                'curso' => $fila['curso']
            ],

            'riesgo' => [
                'id_nivel' => (int) $fila['id_nivel'],

                'nombre' => $fila['nivel_riesgo'],

                'color' => $fila['color_riesgo']
            ],

            'analisis' => [
                'emocion' => $fila['emocion_detectada'],

                'confianza' => (float)
                    $fila['porcentaje_confianza'],

                'resumen' => $fila['resumen']
            ]
        ];
    }

    mysqli_free_result($resultado);

    mysqli_close($conexion);

    $conexion = null;

    // Mantener el formato esperado por el dashboard.
    responder_json(200, [
        'success' => true,

        'total' => count($alertas),

        'alertas' => $alertas
    ]);

} catch (Throwable $e) {
    // Registrar detalles internos solo en el servidor.
    error_log(
        'SafeMind obtener_alertas.php: ' .
        $e->getMessage()
    );

    if ($conexion instanceof mysqli) {
        mysqli_close($conexion);
    }

    responder_json(500, [
        'success' => false,

        'mensaje' =>
            'No se pudieron cargar las alertas en este momento.'
    ]);
}