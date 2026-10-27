<?php
/**
 * SafeMind - Obtener recursos recomendados para un análisis.
 * Acceso exclusivo para psicólogos autenticados y activos.
 * Conserva la estructura JSON que consume detalle_alerta.php.
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
 * Enviar una respuesta JSON y terminar la petición.
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

/*
|--------------------------------------------------------------------------
| 1. Validar método HTTP
|--------------------------------------------------------------------------
*/

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    header('Allow: GET');

    responder_json(405, [
        'success' => false,
        'mensaje' => 'Método no permitido.'
    ]);
}

/*
|--------------------------------------------------------------------------
| 2. Validar sesión, rol e identificador del psicólogo
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
| 3. Validar el identificador del análisis
|--------------------------------------------------------------------------
*/

$id_analisis = filter_var(
    $_GET['id_analisis'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if ($id_analisis === false || $id_analisis === null) {
    responder_json(400, [
        'success' => false,
        'mensaje' => 'ID de análisis no válido.'
    ]);
}

$conexion = null;

try {

    /*
    |--------------------------------------------------------------------------
    | 4. Conexión
    |--------------------------------------------------------------------------
    */

    require_once __DIR__ . '/../config/conexion.php';

    $conexion = conectar();

    if (!$conexion instanceof mysqli) {
        throw new RuntimeException(
            'La conexión a la base de datos no es válida.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | 5. Verificar que el psicólogo continúe activo
    |--------------------------------------------------------------------------
    */

    $sql_permiso = "
        SELECT id_psicologo
        FROM psicologo
        WHERE id_psicologo = ?
          AND estado = 'Activo'
        LIMIT 1
    ";

    $stmt_permiso = mysqli_prepare(
        $conexion,
        $sql_permiso
    );

    if (!$stmt_permiso) {
        throw new RuntimeException(
            'No se pudo preparar la validación del psicólogo.'
        );
    }

    mysqli_stmt_bind_param(
        $stmt_permiso,
        'i',
        $id_psicologo
    );

    if (!mysqli_stmt_execute($stmt_permiso)) {
        mysqli_stmt_close($stmt_permiso);

        throw new RuntimeException(
            'No se pudo validar la cuenta del psicólogo.'
        );
    }

    $resultado_permiso =
        mysqli_stmt_get_result($stmt_permiso);

    if ($resultado_permiso === false) {
        mysqli_stmt_close($stmt_permiso);

        throw new RuntimeException(
            'No se pudo verificar el estado de la cuenta.'
        );
    }

    $psicologo_activo = is_array(
        mysqli_fetch_assoc($resultado_permiso)
    );

    mysqli_free_result($resultado_permiso);
    mysqli_stmt_close($stmt_permiso);

    if (!$psicologo_activo) {
        mysqli_close($conexion);
        $conexion = null;

        responder_json(403, [
            'success' => false,
            'mensaje' => 'No autorizado.'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 6. Verificar que el análisis exista
    |--------------------------------------------------------------------------
    */

    $sql_analisis = "
        SELECT id_analisis
        FROM analisis_ia
        WHERE id_analisis = ?
        LIMIT 1
    ";

    $stmt_analisis = mysqli_prepare(
        $conexion,
        $sql_analisis
    );

    if (!$stmt_analisis) {
        throw new RuntimeException(
            'No se pudo preparar la validación del análisis.'
        );
    }

    mysqli_stmt_bind_param(
        $stmt_analisis,
        'i',
        $id_analisis
    );

    if (!mysqli_stmt_execute($stmt_analisis)) {
        mysqli_stmt_close($stmt_analisis);

        throw new RuntimeException(
            'No se pudo consultar el análisis.'
        );
    }

    $resultado_analisis =
        mysqli_stmt_get_result($stmt_analisis);

    if ($resultado_analisis === false) {
        mysqli_stmt_close($stmt_analisis);

        throw new RuntimeException(
            'No se pudo obtener el resultado del análisis.'
        );
    }

    $analisis_existe = is_array(
        mysqli_fetch_assoc($resultado_analisis)
    );

    mysqli_free_result($resultado_analisis);
    mysqli_stmt_close($stmt_analisis);

    if (!$analisis_existe) {
        mysqli_close($conexion);
        $conexion = null;

        responder_json(404, [
            'success' => false,
            'mensaje' => 'El análisis solicitado no existe.'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 7. Obtener los recursos recomendados
    |--------------------------------------------------------------------------
    */

    $sql_recursos = "
        SELECT
            r.id_recurso,
            r.titulo,
            r.descripcion,
            r.tipo

        FROM analisis_recurso ar

        INNER JOIN recurso_apoyo r
            ON ar.id_recurso = r.id_recurso

        WHERE ar.id_analisis = ?

        ORDER BY r.id_recurso ASC
    ";

    $stmt_recursos = mysqli_prepare(
        $conexion,
        $sql_recursos
    );

    if (!$stmt_recursos) {
        throw new RuntimeException(
            'No se pudo preparar la consulta de recursos.'
        );
    }

    mysqli_stmt_bind_param(
        $stmt_recursos,
        'i',
        $id_analisis
    );

    if (!mysqli_stmt_execute($stmt_recursos)) {
        mysqli_stmt_close($stmt_recursos);

        throw new RuntimeException(
            'No se pudo ejecutar la consulta de recursos.'
        );
    }

    $resultado_recursos =
        mysqli_stmt_get_result($stmt_recursos);

    if ($resultado_recursos === false) {
        mysqli_stmt_close($stmt_recursos);

        throw new RuntimeException(
            'No se pudo obtener el resultado de los recursos.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | 8. Construir la respuesta
    |--------------------------------------------------------------------------
    */

    $recursos = [];

    while ($fila = mysqli_fetch_assoc($resultado_recursos)) {
        $recursos[] = [
            'id' => (int) $fila['id_recurso'],
            'titulo' => $fila['titulo'],
            'descripcion' => $fila['descripcion'],
            'tipo' => $fila['tipo']
        ];
    }

    mysqli_free_result($resultado_recursos);
    mysqli_stmt_close($stmt_recursos);

    mysqli_close($conexion);
    $conexion = null;

    /*
    |--------------------------------------------------------------------------
    | 9. Respuesta compatible con el frontend
    |--------------------------------------------------------------------------
    */

    responder_json(200, [
        'success' => true,
        'id_analisis' => (int) $id_analisis,
        'total' => count($recursos),
        'recursos' => $recursos
    ]);

} catch (Throwable $e) {

    // Registrar los detalles técnicos solo en el servidor.
    error_log(
        'SafeMind obtener_recursos_analisis.php: ' .
        $e->getMessage()
    );

    if ($conexion instanceof mysqli) {
        mysqli_close($conexion);
    }

    responder_json(500, [
        'success' => false,
        'mensaje' =>
            'No se pudieron cargar los recursos en este momento.'
    ]);
}
?>