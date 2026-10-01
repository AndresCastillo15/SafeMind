<?php

session_start();
header('Content-Type: application/json; charset=utf-8');

if (
    !isset($_SESSION['rol']) ||
    $_SESSION['rol'] !== 'profesor' ||
    !isset($_SESSION['id_usuario'])
) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'mensaje' => 'No autorizado.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once '../config/conexion.php';
$conexion = conectar();
$id_profesor = (int) $_SESSION['id_usuario'];

try {

    $sql = "
        SELECT nombre, apellido, curso
        FROM profesor
        WHERE id_profesor = ?
          AND estado = 'Activo'
        LIMIT 1
    ";

    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) throw new Exception('No se pudo preparar la consulta del profesor.');

    mysqli_stmt_bind_param($stmt, 'i', $id_profesor);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($resultado) !== 1) {
        throw new Exception('No se encontró el profesor activo.');
    }

    $profesor = mysqli_fetch_assoc($resultado);
    mysqli_stmt_close($stmt);

    $curso = trim((string) ($profesor['curso'] ?? ''));

    if ($curso === '') {
        throw new Exception('El profesor no tiene un curso asignado.');
    }

    $sql = "
        SELECT COUNT(DISTINCT ev.id_estudiante) AS total
        FROM evaluacion ev
        INNER JOIN estudiante est ON est.id_estudiante = ev.id_estudiante
        WHERE est.curso = ?
    ";
    $stmt = mysqli_prepare($conexion, $sql);
    mysqli_stmt_bind_param($stmt, 's', $curso);
    mysqli_stmt_execute($stmt);
    $estudiantes_evaluados = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'];
    mysqli_stmt_close($stmt);

    $sql = "
        SELECT COUNT(*) AS total
        FROM evaluacion ev
        INNER JOIN estudiante est ON est.id_estudiante = ev.id_estudiante
        WHERE est.curso = ?
    ";
    $stmt = mysqli_prepare($conexion, $sql);
    mysqli_stmt_bind_param($stmt, 's', $curso);
    mysqli_stmt_execute($stmt);
    $total_evaluaciones = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'];
    mysqli_stmt_close($stmt);

    $sql = "
        SELECT COUNT(*) AS total
        FROM alerta a
        INNER JOIN analisis_ia ai ON ai.id_analisis = a.id_analisis
        INNER JOIN evaluacion ev ON ev.id_evaluacion = ai.id_evaluacion
        INNER JOIN estudiante est ON est.id_estudiante = ev.id_estudiante
        WHERE est.curso = ?
          AND a.estado IN ('Pendiente', 'En seguimiento')
    ";
    $stmt = mysqli_prepare($conexion, $sql);
    mysqli_stmt_bind_param($stmt, 's', $curso);
    mysqli_stmt_execute($stmt);
    $alertas_activas = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'];
    mysqli_stmt_close($stmt);

    $sql = "
    SELECT COUNT(DISTINCT s.id_alerta) AS total
    FROM seguimiento s
    INNER JOIN alerta a ON a.id_alerta = s.id_alerta
    INNER JOIN analisis_ia ai ON ai.id_analisis = a.id_analisis
    INNER JOIN evaluacion ev ON ev.id_evaluacion = ai.id_evaluacion
    INNER JOIN estudiante est ON est.id_estudiante = ev.id_estudiante
    WHERE est.curso = ?
      AND YEAR(s.fecha) = YEAR(CURDATE())
      AND MONTH(s.fecha) = MONTH(CURDATE())
    ";
    
    $stmt = mysqli_prepare($conexion, $sql);
    mysqli_stmt_bind_param($stmt, 's', $curso);
    mysqli_stmt_execute($stmt);
    $seguimientos_mes = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'];
    mysqli_stmt_close($stmt);

    $riesgo = [
        'nivel_1' => 0,
        'nivel_2' => 0,
        'nivel_3' => 0,
        'nivel_4' => 0
    ];

    $sql = "
        SELECT ai.id_nivel, COUNT(*) AS cantidad
        FROM analisis_ia ai
        INNER JOIN (
            SELECT ev.id_estudiante, MAX(ai2.id_analisis) AS id_ultimo_analisis
            FROM analisis_ia ai2
            INNER JOIN evaluacion ev ON ev.id_evaluacion = ai2.id_evaluacion
            INNER JOIN estudiante est ON est.id_estudiante = ev.id_estudiante
            WHERE est.curso = ?
            GROUP BY ev.id_estudiante
        ) ultimos ON ultimos.id_ultimo_analisis = ai.id_analisis
        GROUP BY ai.id_nivel
    ";
    $stmt = mysqli_prepare($conexion, $sql);
    mysqli_stmt_bind_param($stmt, 's', $curso);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);

    while ($fila = mysqli_fetch_assoc($resultado)) {
        $nivel = (int) $fila['id_nivel'];
        if ($nivel >= 1 && $nivel <= 4) {
            $riesgo["nivel_$nivel"] = (int) $fila['cantidad'];
        }
    }
    mysqli_stmt_close($stmt);

    $sql = "
        SELECT DATE_FORMAT(ev.fecha_inicio, '%Y-%m') AS periodo,
               COUNT(*) AS cantidad
        FROM evaluacion ev
        INNER JOIN estudiante est ON est.id_estudiante = ev.id_estudiante
        WHERE est.curso = ?
          AND ev.fecha_inicio >= DATE_SUB(
                DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 5 MONTH
          )
        GROUP BY DATE_FORMAT(ev.fecha_inicio, '%Y-%m')
        ORDER BY periodo ASC
    ";
    $stmt = mysqli_prepare($conexion, $sql);
    mysqli_stmt_bind_param($stmt, 's', $curso);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);

    $tendencia = [];
    while ($fila = mysqli_fetch_assoc($resultado)) {
        $tendencia[] = [
            'periodo' => $fila['periodo'],
            'cantidad' => (int) $fila['cantidad']
        ];
    }
    mysqli_stmt_close($stmt);

    echo json_encode([
        'success' => true,
        'profesor' => [
            'nombre' => trim(($profesor['nombre'] ?? '') . ' ' . ($profesor['apellido'] ?? ''))
        ],
        'curso' => $curso,
        'estadisticas' => [
            'alertas_activas' => $alertas_activas,
            'estudiantes_evaluados' => $estudiantes_evaluados,
            'total_evaluaciones' => $total_evaluaciones,
            'seguimientos_mes' => $seguimientos_mes
        ],
        'riesgo' => $riesgo,
        'tendencia' => $tendencia
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'mensaje' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

mysqli_close($conexion);
?>
