<?php

session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

/*
--------------------------------------------------------------------
NOTA DE INTEGRACIÓN

Este archivo asume que vive en la raíz del proyecto, al mismo nivel
que index.php / login.php / logout.php (según tu estructura de
carpetas). Por eso la ruta de conexión apunta a "API/config/conexion.php".

También asume que login.php, al autenticar a un profesor, guarda en
sesión:
    $_SESSION['rol']    = 'profesor'
    $_SESSION['nombre'] = 'Nombre del docente'

Si tu login.php usa otras llaves, ajusta esas dos líneas más abajo.

Como la BD (Safemind.sql) no tiene una tabla que vincule
profesor <-> curso, este dashboard deja elegir el curso por un
selector (usa la columna estudiante.curso) y lo recuerda en sesión.
Si en tu proyecto un profesor solo puede ver un curso fijo, reemplaza
el bloque "Curso seleccionado" por esa lógica.
--------------------------------------------------------------------
*/

require_once __DIR__ . '/../API/config/conexion.php';

// --- Control de acceso ---
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'profesor') {
    header('Location: login.php?rol=profesor');
    exit();
}

$nombre_docente = $_SESSION['nombre'] ?? 'Orientador Educativo';

$conexion = conectar();

// --- Curso seleccionado ---

$cursos_disponibles = [];
$res_cursos = mysqli_query(
    $conexion,
    "SELECT DISTINCT curso FROM estudiante WHERE curso IS NOT NULL AND curso <> '' ORDER BY curso ASC"
);

if ($res_cursos) {
    while ($fila = mysqli_fetch_assoc($res_cursos)) {
        $cursos_disponibles[] = $fila['curso'];
    }
}

if (isset($_GET['curso']) && in_array($_GET['curso'], $cursos_disponibles, true)) {
    $_SESSION['curso_actual'] = $_GET['curso'];
}

$curso_actual = $_SESSION['curso_actual'] ?? ($cursos_disponibles[0] ?? null);

// --- Datos del dashboard ---

$mapa_animo = [
    1 => ['etiqueta' => 'Positivo', 'color' => '#6EE7B7'],
    2 => ['etiqueta' => 'Neutral',  'color' => '#60A5FA'],
    3 => ['etiqueta' => 'Alerta',   'color' => '#FBBF24'],
    4 => ['etiqueta' => 'Crítico',  'color' => '#F87171'],
];

$animo                  = [1 => 0, 2 => 0, 3 => 0, 4 => 0];
$total_analisis_semana  = 0;
$tasa_checkin           = 0;
$total_estudiantes      = 0;
$estudiantes_registrados = 0;
$recursos_recomendados  = [];
$variacion_riesgo       = null;

if ($curso_actual !== null) {

    // 1) Estado de ánimo colectivo (últimos 7 días)
    $sql = "SELECT ai.id_nivel, COUNT(*) AS total
            FROM analisis_ia ai
            INNER JOIN evaluacion e ON ai.id_evaluacion = e.id_evaluacion
            INNER JOIN estudiante est ON e.id_estudiante = est.id_estudiante
            WHERE est.curso = ?
              AND ai.fecha >= (NOW() - INTERVAL 7 DAY)
            GROUP BY ai.id_nivel";

    $stmt = mysqli_prepare($conexion, $sql);
    mysqli_stmt_bind_param($stmt, "s", $curso_actual);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);

    while ($fila = mysqli_fetch_assoc($resultado)) {
        $nivel = (int) $fila['id_nivel'];
        if (isset($animo[$nivel])) {
            $animo[$nivel] = (int) $fila['total'];
        }
        $total_analisis_semana += (int) $fila['total'];
    }
    mysqli_stmt_close($stmt);

    // 2) Tasa de check-in
    $stmt = mysqli_prepare($conexion, "SELECT COUNT(*) AS total FROM estudiante WHERE curso = ? AND estado = 'Activo'");
    mysqli_stmt_bind_param($stmt, "s", $curso_actual);
    mysqli_stmt_execute($stmt);
    $total_estudiantes = (int) (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'] ?? 0);
    mysqli_stmt_close($stmt);

    $sql = "SELECT COUNT(DISTINCT e.id_estudiante) AS total
            FROM evaluacion e
            INNER JOIN estudiante est ON e.id_estudiante = est.id_estudiante
            WHERE est.curso = ?
              AND est.estado = 'Activo'
              AND e.fecha_inicio >= (NOW() - INTERVAL 7 DAY)";
    $stmt = mysqli_prepare($conexion, $sql);
    mysqli_stmt_bind_param($stmt, "s", $curso_actual);
    mysqli_stmt_execute($stmt);
    $estudiantes_registrados = (int) (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'] ?? 0);
    mysqli_stmt_close($stmt);

    $tasa_checkin = $total_estudiantes > 0
        ? (int) round(($estudiantes_registrados / $total_estudiantes) * 100)
        : 0;

    // 3) Recursos recomendados (últimos 30 días)
    $sql = "SELECT r.id_recurso, r.titulo, r.descripcion, r.tipo, r.url, COUNT(*) AS veces
            FROM analisis_recurso arec
            INNER JOIN analisis_ia ai ON arec.id_analisis = ai.id_analisis
            INNER JOIN evaluacion e ON ai.id_evaluacion = e.id_evaluacion
            INNER JOIN estudiante est ON e.id_estudiante = est.id_estudiante
            INNER JOIN recurso_apoyo r ON arec.id_recurso = r.id_recurso
            WHERE est.curso = ?
              AND ai.fecha >= (NOW() - INTERVAL 30 DAY)
            GROUP BY r.id_recurso
            ORDER BY veces DESC
            LIMIT 2";
    $stmt = mysqli_prepare($conexion, $sql);
    mysqli_stmt_bind_param($stmt, "s", $curso_actual);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    while ($fila = mysqli_fetch_assoc($resultado)) {
        $recursos_recomendados[] = $fila;
    }
    mysqli_stmt_close($stmt);

    // 4) Variación de riesgo (semana actual vs. anterior) para el texto de contexto
    $sql = "SELECT
                SUM(CASE WHEN ai.fecha >= (NOW() - INTERVAL 7 DAY) THEN 1 ELSE 0 END) AS total_actual,
                SUM(CASE WHEN ai.fecha >= (NOW() - INTERVAL 7 DAY) AND ai.id_nivel >= 3 THEN 1 ELSE 0 END) AS riesgo_actual,
                SUM(CASE WHEN ai.fecha < (NOW() - INTERVAL 7 DAY) THEN 1 ELSE 0 END) AS total_previo,
                SUM(CASE WHEN ai.fecha < (NOW() - INTERVAL 7 DAY) AND ai.id_nivel >= 3 THEN 1 ELSE 0 END) AS riesgo_previo
            FROM analisis_ia ai
            INNER JOIN evaluacion e ON ai.id_evaluacion = e.id_evaluacion
            INNER JOIN estudiante est ON e.id_estudiante = est.id_estudiante
            WHERE est.curso = ?
              AND ai.fecha >= (NOW() - INTERVAL 14 DAY)";
    $stmt = mysqli_prepare($conexion, $sql);
    mysqli_stmt_bind_param($stmt, "s", $curso_actual);
    mysqli_stmt_execute($stmt);
    $fila = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if ($fila && (int) $fila['total_actual'] > 0 && (int) $fila['total_previo'] > 0) {
        $pct_actual = ($fila['riesgo_actual'] / $fila['total_actual']) * 100;
        $pct_previo = ($fila['riesgo_previo'] / $fila['total_previo']) * 100;
        $variacion_riesgo = (int) round($pct_actual - $pct_previo);
    }
}

mysqli_close($conexion);

// Texto de contexto dinámico para la tarjeta de recursos
if ($variacion_riesgo === null) {
    $texto_insight = 'Aún no hay suficientes registros para comparar esta semana con la anterior.';
} elseif ($variacion_riesgo > 0) {
    $texto_insight = "Basado en el incremento de {$variacion_riesgo}% en niveles de alerta y crisis, sugerimos estas actividades grupales.";
} elseif ($variacion_riesgo < 0) {
    $texto_insight = 'El curso muestra una mejora respecto a la semana pasada. Estas actividades ayudan a mantener el buen clima.';
} else {
    $texto_insight = 'El clima emocional del curso se mantiene estable respecto a la semana pasada.';
}

$max_animo = max(1, ...array_values($animo));

function icono_recurso(string $tipo): string
{
    return match (strtolower(trim($tipo))) {
        'video' => '🎬',
        'audio' => '🎧',
        'articulo', 'artículo' => '📄',
        'actividad', 'dinamica', 'dinámica' => '🧩',
        default => '🌱',
    };
}

function e(?string $texto): string
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SafeMind · Dashboard docente</title>
<style>
    :root {
        --verde-positivo: #6EE7B7;
        --azul-neutral: #60A5FA;
        --amarillo-alerta: #FBBF24;
        --rojo-critico: #F87171;
        --texto-principal: #1F2937;
        --texto-secundario: #6B7280;
        --borde: #E5E7EB;
        --fondo: #F3F4F6;
        --superficie: #FFFFFF;
        --acento: #2563EB;
        --activo-fondo: #A7F3D0;
        --radio: 14px;
        --sombra: 0 1px 2px rgba(15, 23, 42, 0.06), 0 4px 12px rgba(15, 23, 42, 0.04);
    }

    * { box-sizing: border-box; }

    body {
        margin: 0;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        background: var(--fondo);
        color: var(--texto-principal);
    }

    a { color: inherit; text-decoration: none; }

    a:focus-visible,
    button:focus-visible,
    select:focus-visible {
        outline: 2px solid var(--acento);
        outline-offset: 2px;
    }

    .app {
        display: flex;
        min-height: 100vh;
    }

    /* --- Sidebar --- */

    .sidebar {
        width: 220px;
        flex-shrink: 0;
        background: var(--superficie);
        border-right: 1px solid var(--borde);
        padding: 24px 16px;
        display: flex;
        flex-direction: column;
        gap: 24px;
    }

    .perfil {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 0 4px;
    }

    .avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, #93C5FD, #6EE7B7);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        color: #ffffff;
        flex-shrink: 0;
    }

    .perfil-texto p { margin: 0; line-height: 1.3; }
    .perfil-nombre { font-weight: 600; font-size: 14px; color: var(--acento); }
    .perfil-rol { font-size: 12px; color: var(--texto-secundario); }

    nav.menu {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .menu-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        border-radius: 10px;
        font-size: 14px;
        color: var(--texto-secundario);
    }

    .menu-item:hover { background: #F3F4F6; }

    .menu-item.activo {
        background: var(--activo-fondo);
        color: #065F46;
        font-weight: 600;
    }

    .menu-item .icono { font-size: 16px; width: 20px; text-align: center; }

    /* --- Contenido principal --- */

    .contenido {
        flex: 1;
        padding: 32px 40px;
        max-width: 1100px;
    }

    .encabezado {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        flex-wrap: wrap;
        margin-bottom: 24px;
    }

    .encabezado h1 {
        margin: 0 0 4px;
        font-size: 24px;
        letter-spacing: -0.01em;
    }

    .encabezado p {
        margin: 0;
        color: var(--texto-secundario);
        font-size: 14px;
    }

    .selector-curso select {
        padding: 8px 12px;
        border-radius: 8px;
        border: 1px solid var(--borde);
        background: var(--superficie);
        font-size: 14px;
        color: var(--texto-principal);
    }

    .fila {
        display: grid;
        grid-template-columns: 1.6fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
    }

    .tarjeta {
        background: var(--superficie);
        border: 1px solid var(--borde);
        border-radius: var(--radio);
        box-shadow: var(--sombra);
        padding: 20px;
    }

    .tarjeta-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
    }

    .tarjeta-header h2 {
        margin: 0;
        font-size: 15px;
        font-weight: 600;
    }

    .tarjeta-header .link {
        font-size: 13px;
        color: var(--acento);
        font-weight: 500;
    }

    /* --- Gráfico de ánimo --- */

    .grafico-animo {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 16px;
        height: 150px;
    }

    .barra-grupo {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        height: 100%;
        justify-content: flex-end;
        gap: 8px;
    }

    .barra {
        width: 100%;
        border-radius: 8px 8px 4px 4px;
        min-height: 6px;
        transition: height 0.3s ease;
    }

    .barra-etiqueta {
        font-size: 12px;
        color: var(--texto-secundario);
    }

    .estado-vacio {
        color: var(--texto-secundario);
        font-size: 13px;
        text-align: center;
        padding: 40px 0;
    }

    /* --- Tarjeta check-in --- */

    .checkin-porcentaje {
        font-size: 36px;
        font-weight: 700;
        color: var(--acento);
        margin: 0 0 8px;
    }

    .checkin-texto {
        font-size: 13px;
        color: var(--texto-secundario);
        margin: 0 0 16px;
        line-height: 1.5;
    }

    .barra-progreso {
        position: relative;
        height: 6px;
        background: #E5E7EB;
        border-radius: 999px;
        margin-bottom: 6px;
    }

    .barra-progreso-relleno {
        height: 100%;
        background: var(--acento);
        border-radius: 999px;
    }

    .barra-progreso-meta {
        position: absolute;
        top: -3px;
        width: 2px;
        height: 12px;
        background: var(--texto-principal);
        opacity: 0.4;
    }

    .barra-progreso-labels {
        display: flex;
        justify-content: space-between;
        font-size: 11px;
        color: var(--texto-secundario);
    }

    /* --- Recursos --- */

    .tarjeta-recursos p.subtexto {
        margin: 0 0 16px;
        font-size: 13px;
        color: var(--texto-secundario);
        max-width: 70ch;
    }

    .grid-recursos {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
    }

    .recurso {
        border: 1px solid var(--borde);
        border-radius: 12px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    .recurso-ilustracion {
        height: 90px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        background: linear-gradient(135deg, #DBEAFE, #D1FAE5);
    }

    .recurso-cuerpo {
        padding: 12px 14px;
    }

    .recurso-cuerpo h3 {
        margin: 0 0 4px;
        font-size: 13px;
        font-weight: 600;
    }

    .recurso-cuerpo p {
        margin: 0;
        font-size: 12px;
        color: var(--texto-secundario);
        line-height: 1.4;
    }

    .recurso-catalogo {
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 20px 14px;
        gap: 8px;
        color: var(--texto-secundario);
    }

    .recurso-catalogo .circulo {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: #DBEAFE;
        color: var(--acento);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        margin: 0 auto 6px;
    }

    @media (max-width: 900px) {
        .app { flex-direction: column; }
        .sidebar {
            width: 100%;
            flex-direction: row;
            align-items: center;
            overflow-x: auto;
        }
        nav.menu { flex-direction: row; }
        .fila { grid-template-columns: 1fr; }
        .contenido { padding: 20px; }
    }
</style>
</head>
<body>

<div class="app">

    <aside class="sidebar">
        <div class="perfil">
            <div class="avatar"><?= e(mb_substr($nombre_docente, 0, 1)) ?></div>
            <div class="perfil-texto">
                <p class="perfil-nombre"><?= e($nombre_docente) ?></p>
                <p class="perfil-rol">SafeMind Guardián</p>
            </div>
        </div>

        <nav class="menu">
            <a class="menu-item activo" href="dashboard_docente.php">
                <span class="icono">▦</span> Dashboard
            </a>
            <a class="menu-item" href="alertas.php">
                <span class="icono">⚠</span> Alertas
            </a>
            <a class="menu-item" href="estudiantes.php">
                <span class="icono">👥</span> Estudiantes
            </a>
            <a class="menu-item" href="recursos.php">
                <span class="icono">📁</span> Recursos
            </a>
            <a class="menu-item" href="configuracion.php">
                <span class="icono">⚙</span> Configuración
            </a>
        </nav>
    </aside>

    <main class="contenido">

        <?php if ($curso_actual === null): ?>

            <div class="tarjeta">
                <h2>No hay estudiantes registrados todavía</h2>
                <p style="color: var(--texto-secundario); font-size: 14px;">
                    En cuanto haya estudiantes con un curso asignado en la base de datos,
                    aquí aparecerá el panel de bienestar.
                </p>
            </div>

        <?php else: ?>

            <div class="encabezado">
                <div>
                    <h1>Bienestar del Curso – <?= e($curso_actual) ?></h1>
                    <p>Visión general analítica y anónima del clima emocional y participación.</p>
                </div>

                <?php if (count($cursos_disponibles) > 1): ?>
                    <form class="selector-curso" method="get">
                        <select name="curso" onchange="this.form.submit()">
                            <?php foreach ($cursos_disponibles as $curso): ?>
                                <option value="<?= e($curso) ?>" <?= $curso === $curso_actual ? 'selected' : '' ?>>
                                    <?= e($curso) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                <?php endif; ?>
            </div>

            <div class="fila">

                <section class="tarjeta">
                    <div class="tarjeta-header">
                        <h2>Estado de Ánimo Colectivo (Últimos 7 días)</h2>
                        <span aria-hidden="true">🙂</span>
                    </div>

                    <?php if ($total_analisis_semana === 0): ?>
                        <p class="estado-vacio">Aún no hay análisis registrados esta semana.</p>
                    <?php else: ?>
                        <div class="grafico-animo">
                            <?php foreach ($mapa_animo as $nivel => $info): ?>
                                <?php $altura = max(6, (int) round(($animo[$nivel] / $max_animo) * 120)); ?>
                                <div class="barra-grupo">
                                    <div class="barra"
                                         style="height: <?= $altura ?>px; background: <?= e($info['color']) ?>;"
                                         title="<?= (int) $animo[$nivel] ?> registro(s)"></div>
                                    <span class="barra-etiqueta"><?= e($info['etiqueta']) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <section class="tarjeta">
                    <div class="tarjeta-header">
                        <h2>Tasa de Check-in</h2>
                        <span aria-hidden="true">📊</span>
                    </div>

                    <p class="checkin-porcentaje"><?= (int) $tasa_checkin ?>%</p>
                    <p class="checkin-texto">
                        <?= $estudiantes_registrados ?> de <?= $total_estudiantes ?> estudiantes activos
                        registraron al menos una evaluación esta semana.
                    </p>

                    <div class="barra-progreso">
                        <div class="barra-progreso-relleno" style="width: <?= min(100, $tasa_checkin) ?>%;"></div>
                        <div class="barra-progreso-meta" style="left: 80%;"></div>
                    </div>
                    <div class="barra-progreso-labels">
                        <span>0%</span>
                        <span>Meta 80%</span>
                        <span>100%</span>
                    </div>
                </section>

            </div>

            <section class="tarjeta tarjeta-recursos">
                <div class="tarjeta-header">
                    <h2>Recursos Recomendados para <?= e($curso_actual) ?></h2>
                    <a class="link" href="recursos.php">Ver todos →</a>
                </div>

                <p class="subtexto"><?= e($texto_insight) ?></p>

                <div class="grid-recursos">

                    <?php if (empty($recursos_recomendados)): ?>
                        <p class="estado-vacio">Todavía no hay recursos asociados a análisis recientes de este curso.</p>
                    <?php else: ?>
                        <?php foreach ($recursos_recomendados as $recurso): ?>
                            <a class="recurso" href="<?= e($recurso['url'] ?: '#') ?>" target="_blank" rel="noopener">
                                <div class="recurso-ilustracion" aria-hidden="true">
                                    <?= icono_recurso($recurso['tipo'] ?? '') ?>
                                </div>
                                <div class="recurso-cuerpo">
                                    <h3><?= e($recurso['titulo']) ?></h3>
                                    <p><?= e(mb_strimwidth($recurso['descripcion'] ?? '', 0, 90, '…')) ?></p>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <a class="recurso recurso-catalogo" href="recursos.php">
                        <div class="circulo" aria-hidden="true">+</div>
                        <h3>Explorar Catálogo</h3>
                        <p>Buscar recursos específicos.</p>
                    </a>

                </div>
            </section>

        <?php endif; ?>

    </main>

</div>

</body>
</html>
