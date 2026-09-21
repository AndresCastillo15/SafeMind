<?php

session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'profesor') {
    header('Location: ../login.php?rol=profesor');
    exit();
}

require_once '../API/config/conexion.php';

$conexion = conectar();
$nombre_usuario = $_SESSION['nombre'] ?? 'Profesor';

/*
--------------------------------------------------------------------
LIMITACIONES ACTUALES DEL ESQUEMA (leer antes de tocar esto):

1. No existe todavía una tabla `profesor` ni una relación
   profesor -> estudiante/curso en Safemind.sql. Por eso esta
   consulta trae TODOS los estudiantes activos, no solo los del
   profesor que inició sesión. En cuanto exista esa relación
   (por ejemplo una columna `curso` en `profesor`, o una tabla
   `profesor_curso`), se agrega aquí:
       WHERE e.estado = 'Activo' AND e.curso = ?
   con el curso del profesor en sesión.

2. No existe tabla de reuniones, así que la tarjeta que en la
   referencia decía "Reuniones de hoy" se reemplazó por
   "Recursos de apoyo disponibles" (tabla `recurso_apoyo`),
   que sí existe.

3. Por rol, al profesor NO se le muestra `resumen`,
   `emocion_detectada` (de `analisis_ia`) ni `observacion`
   (de `seguimiento`): esos son el detalle clínico que maneja
   el psicólogo/orientación. El profesor solo ve el nivel de
   riesgo y el estado general del caso.
--------------------------------------------------------------------
*/

// ------------------------------------------------------------------
// Última evaluación + análisis + nivel de riesgo + alerta, por estudiante
// ------------------------------------------------------------------

$sql_estudiantes = "
    SELECT
        e.id_estudiante, e.nombre, e.apellido, e.curso,
        ev.id_evaluacion, ev.fecha_inicio, ev.estado AS evaluacion_estado,
        ai.id_analisis, ai.fecha AS fecha_analisis,
        nr.id_nivel, nr.nombre AS nivel_nombre,
        al.id_alerta, al.estado AS alerta_estado, al.fecha AS fecha_alerta
    FROM estudiante e
    INNER JOIN (
        SELECT id_estudiante, MAX(fecha_inicio) AS ultima_fecha
        FROM evaluacion
        GROUP BY id_estudiante
    ) ue ON ue.id_estudiante = e.id_estudiante
    INNER JOIN evaluacion ev
        ON ev.id_estudiante = ue.id_estudiante AND ev.fecha_inicio = ue.ultima_fecha
    LEFT JOIN analisis_ia ai ON ai.id_evaluacion = ev.id_evaluacion
    LEFT JOIN nivel_riesgo nr ON nr.id_nivel = ai.id_nivel
    LEFT JOIN alerta al ON al.id_analisis = ai.id_analisis
    WHERE e.estado = 'Activo'
    ORDER BY (nr.id_nivel IS NULL) ASC, nr.id_nivel DESC, ev.fecha_inicio DESC
";

$estudiantes = [];
$resultado = mysqli_query($conexion, $sql_estudiantes);
if ($resultado) {
    while ($fila = mysqli_fetch_assoc($resultado)) {
        $estudiantes[] = $fila;
    }
}

// ------------------------------------------------------------------
// Último seguimiento (solo estado, sin el detalle clínico) por alerta
// ------------------------------------------------------------------

$seguimientos_por_alerta = [];
$sql_seguimiento = "
    SELECT s.id_alerta, s.estado, s.fecha
    FROM seguimiento s
    INNER JOIN (
        SELECT id_alerta, MAX(fecha) AS ultima_fecha
        FROM seguimiento
        GROUP BY id_alerta
    ) us ON us.id_alerta = s.id_alerta AND us.ultima_fecha = s.fecha
";
$resultado_seg = mysqli_query($conexion, $sql_seguimiento);
if ($resultado_seg) {
    while ($fila = mysqli_fetch_assoc($resultado_seg)) {
        $seguimientos_por_alerta[$fila['id_alerta']] = $fila;
    }
}

// ------------------------------------------------------------------
// Actividad reciente: alertas nuevas + seguimientos, sin contenido de chat
// ------------------------------------------------------------------

$sql_actividad = "
    (SELECT 'alerta' AS tipo, al.fecha AS fecha,
            e.nombre AS estudiante_nombre, e.apellido AS estudiante_apellido,
            nr.nombre AS etiqueta
     FROM alerta al
     JOIN analisis_ia ai ON ai.id_analisis = al.id_analisis
     JOIN evaluacion ev ON ev.id_evaluacion = ai.id_evaluacion
     JOIN estudiante e ON e.id_estudiante = ev.id_estudiante
     LEFT JOIN nivel_riesgo nr ON nr.id_nivel = ai.id_nivel)
    UNION ALL
    (SELECT 'seguimiento' AS tipo, s.fecha AS fecha,
            e.nombre AS estudiante_nombre, e.apellido AS estudiante_apellido,
            s.estado AS etiqueta
     FROM seguimiento s
     JOIN alerta al ON al.id_alerta = s.id_alerta
     JOIN analisis_ia ai ON ai.id_analisis = al.id_analisis
     JOIN evaluacion ev ON ev.id_evaluacion = ai.id_evaluacion
     JOIN estudiante e ON e.id_estudiante = ev.id_estudiante)
    ORDER BY fecha DESC
    LIMIT 6
";

$actividad = [];
$resultado_act = mysqli_query($conexion, $sql_actividad);
if ($resultado_act) {
    while ($fila = mysqli_fetch_assoc($resultado_act)) {
        $actividad[] = $fila;
    }
}

// ------------------------------------------------------------------
// Tarjetas de estado
// ------------------------------------------------------------------

$total_seguimiento = count($estudiantes);

$alertas_activas = 0;
foreach ($estudiantes as $est) {
    if (!empty($est['id_alerta']) && stripos((string) $est['alerta_estado'], 'resuel') === false) {
        $alertas_activas++;
    }
}

$sql_resueltos = "
    SELECT COUNT(*) AS total FROM alerta
    WHERE estado LIKE '%resuel%'
    AND MONTH(fecha) = MONTH(CURDATE()) AND YEAR(fecha) = YEAR(CURDATE())
";
$res_resueltos = mysqli_query($conexion, $sql_resueltos);
$casos_resueltos_mes = 0;
if ($res_resueltos) {
    $fila = mysqli_fetch_assoc($res_resueltos);
    $casos_resueltos_mes = (int) $fila['total'];
}

$sql_recursos = "SELECT COUNT(*) AS total FROM recurso_apoyo";
$res_recursos = mysqli_query($conexion, $sql_recursos);
$total_recursos = 0;
if ($res_recursos) {
    $fila = mysqli_fetch_assoc($res_recursos);
    $total_recursos = (int) $fila['total'];
}

mysqli_close($conexion);

// ------------------------------------------------------------------
// Funciones auxiliares de presentación (compatibles con PHP 7.0)
// ------------------------------------------------------------------

function iniciales_es($nombre) {
    $partes = preg_split('/\s+/', trim($nombre));
    $ini = mb_substr($partes[0], 0, 1);
    if (isset($partes[1])) {
        $ini .= mb_substr($partes[1], 0, 1);
    }
    return mb_strtoupper($ini);
}

function nivel_a_prioridad($id_nivel) {
    if ($id_nivel === null) {
        return null;
    }
    if ($id_nivel >= 3) {
        return 'alta';
    }
    if ($id_nivel == 2) {
        return 'media';
    }
    return 'baja';
}

function color_estado($texto) {
    $t = mb_strtolower((string) $texto, 'UTF-8');
    if ($t === '') {
        return 'gris';
    }
    if (strpos($t, 'crisis') !== false || strpos($t, 'urgen') !== false || strpos($t, 'pendient') !== false) {
        return 'rojo';
    }
    if (strpos($t, 'atenc') !== false || strpos($t, 'alerta') !== false || strpos($t, 'riesgo') !== false || strpos($t, 'seguimiento') !== false) {
        return 'gold';
    }
    if (strpos($t, 'resuel') !== false || strpos($t, 'estable') !== false || strpos($t, 'activ') !== false || strpos($t, 'bien') !== false) {
        return 'sage';
    }
    return 'gris';
}

function tiempo_relativo($fecha_str) {
    if (empty($fecha_str)) {
        return '—';
    }
    $fecha = new DateTime($fecha_str);
    $ahora = new DateTime();
    $diff = $ahora->diff($fecha);
    if ($diff->y > 0 || $diff->m > 0 || $diff->d >= 2) {
        return $fecha->format('d/m/Y');
    }
    if ($diff->d == 1) {
        return 'Ayer';
    }
    if ($diff->h >= 1) {
        return 'Hace ' . $diff->h . ($diff->h == 1 ? ' hora' : ' horas');
    }
    if ($diff->i >= 1) {
        return 'Hace ' . $diff->i . ($diff->i == 1 ? ' minuto' : ' minutos');
    }
    return 'Hace un momento';
}

function fecha_es() {
    $dias = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
    $meses = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio',
              'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    $dia = $dias[date('N') - 1];
    $mes = $meses[(int) date('n')];
    return "{$dia}, " . date('j') . " de {$mes} de " . date('Y');
}

function hora_es() {
    $hora = strtolower(date('g:i'));
    $sufijo = date('a') === 'am' ? 'a. m.' : 'p. m.';
    return "{$hora} {$sufijo}";
}

$acentos = ['deep', 'sage', 'gold'];
$caso_principal = isset($estudiantes[0]) ? $estudiantes[0] : null;

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Panel del docente - SafeMind</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,450;9..144,560;9..144,650&family=Karla:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {

            --paper: #F7F4ED;
            --paper-line: #E7E1D4;
            --paper-card: #FFFFFF;

            --ink: #1E2B2F;
            --ink-soft: #5B6B6F;
            --ink-faint: #9AA6A9;

            --deep: #17455A;
            --deep-dark: #0E2E3D;
            --deep-light: #3A7691;
            --deep-soft: #E3EBEE;

            --sage: #4F7F62;
            --sage-soft: #DCE9DF;

            --gold: #C8922F;
            --gold-soft: #F6E7C9;

            --red: #B54B3F;
            --red-soft: #FBE7E4;

            --gris: #9AA6A9;
            --gris-soft: #ECEEEC;

            --radius: 16px;
            --radius-sm: 10px;
            --shadow: 0 10px 26px rgba(14, 46, 61, 0.08);
        }

        body {
            font-family: 'Karla', sans-serif;
            color: var(--ink);
            background: var(--paper);
        }

        h1, h2, h3 {
            font-family: 'Fraunces', serif;
        }

        a {
            color: inherit;
        }

        /* ===================== ESTRUCTURA ===================== */

        .app {
            display: flex;
            min-height: 100vh;
        }

        /* ===================== BARRA LATERAL ===================== */

        .sidebar {
            width: 264px;
            flex-shrink: 0;
            background: #fff;
            border-right: 1px solid var(--paper-line);

            display: flex;
            flex-direction: column;
            padding: 26px 20px;
        }

        .marca {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 28px;
            padding: 0 6px;
        }

        .marca img {
            width: 34px;
            height: 34px;
            border-radius: 9px;
            object-fit: contain;
            flex-shrink: 0;
        }

        .marca span {
            font-family: 'Fraunces', serif;
            font-size: 19px;
            font-weight: 650;
            color: var(--deep-dark);
        }

        .perfil {
            display: flex;
            align-items: center;
            gap: 12px;

            padding: 10px;
            border-radius: var(--radius-sm);
            margin-bottom: 20px;
        }

        .perfil-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--deep-soft);
            color: var(--deep);

            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
            flex-shrink: 0;
        }

        .perfil-nombre {
            font-size: 14.5px;
            font-weight: 700;
            line-height: 1.3;
        }

        .perfil-rol {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 12.5px;
            color: var(--ink-soft);
        }

        .punto-verde {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--sage);
            flex-shrink: 0;
        }

        .nav {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;

            padding: 10px 12px;
            border-radius: var(--radius-sm);

            font-size: 14.5px;
            font-weight: 600;
            color: var(--ink-soft);
            text-decoration: none;

            transition: background 0.15s ease, color 0.15s ease;
        }

        .nav-item svg {
            width: 18px;
            height: 18px;
            stroke-width: 1.7;
            flex-shrink: 0;
        }

        .nav-item:hover {
            background: var(--paper);
            color: var(--ink);
        }

        .nav-item.activo {
            background: var(--deep-soft);
            color: var(--deep);
        }

        .nav-badge {
            margin-left: auto;
            background: var(--red);
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            padding: 1px 7px;
            border-radius: 20px;
        }

        .sidebar-pie {
            margin-top: auto;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .apoyo-card {
            background: var(--sage-soft);
            border-radius: var(--radius-sm);
            padding: 14px;
        }

        .apoyo-card-titulo {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 700;
            color: var(--deep-dark);
            margin-bottom: 6px;
        }

        .apoyo-card-titulo svg {
            width: 15px;
            height: 15px;
            stroke: var(--sage);
        }

        .apoyo-card p {
            font-size: 12.5px;
            color: var(--ink-soft);
            line-height: 1.5;
            margin-bottom: 8px;
        }

        .apoyo-card a {
            font-size: 12.5px;
            font-weight: 700;
            color: var(--sage);
            text-decoration: none;
        }

        .apoyo-card a:hover {
            text-decoration: underline;
        }

        .salir {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13.5px;
            font-weight: 600;
            color: var(--ink-faint);
            text-decoration: none;
            padding: 4px 12px;
        }

        .salir svg {
            width: 16px;
            height: 16px;
        }

        .salir:hover {
            color: var(--red);
        }

        /* ===================== CONTENIDO ===================== */

        .contenido {
            flex: 1;
            padding: 30px 38px 50px;
            max-width: 1360px;
        }

        .dash-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 26px;
            flex-wrap: wrap;
            gap: 14px;
        }

        .dash-header h1 {
            font-size: 27px;
            font-weight: 620;
            color: var(--deep-dark);
            margin-bottom: 4px;
        }

        .dash-header p {
            color: var(--ink-soft);
            font-size: 14.5px;
        }

        .dash-header-derecha {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .campana {
            position: relative;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 1px solid var(--paper-line);
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .campana svg {
            width: 18px;
            height: 18px;
            stroke: var(--ink-soft);
        }

        .punto-noti {
            position: absolute;
            top: -4px;
            right: -4px;
            background: var(--red);
            color: #fff;
            font-size: 10.5px;
            font-weight: 700;
            width: 17px;
            height: 17px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .fecha {
            text-align: right;
            font-size: 13px;
            line-height: 1.4;
        }

        .fecha strong {
            display: block;
            color: var(--ink);
        }

        .fecha span {
            color: var(--ink-faint);
        }

        /* ===================== TARJETAS DE ESTADO ===================== */

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: var(--paper-card);
            border: 1px solid var(--paper-line);
            border-radius: var(--radius);
            padding: 18px;

            opacity: 0;
            animation: aparecer 0.5s ease forwards;
        }

        .stats-grid .stat-card:nth-child(1) { animation-delay: 0.05s; }
        .stats-grid .stat-card:nth-child(2) { animation-delay: 0.1s; }
        .stats-grid .stat-card:nth-child(3) { animation-delay: 0.15s; }
        .stats-grid .stat-card:nth-child(4) { animation-delay: 0.2s; }

        .stat-top {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }

        .stat-icono {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .stat-icono svg {
            width: 18px;
            height: 18px;
            stroke-width: 1.8;
        }

        .stat-icono.red   { background: var(--red-soft);  color: var(--red); }
        .stat-icono.gold  { background: var(--gold-soft); color: var(--gold); }
        .stat-icono.sage  { background: var(--sage-soft); color: var(--sage); }
        .stat-icono.deep  { background: var(--deep-soft); color: var(--deep); }

        .stat-titulo {
            font-size: 13.5px;
            font-weight: 700;
            color: var(--ink);
        }

        .stat-valor {
            font-family: 'Fraunces', serif;
            font-size: 32px;
            font-weight: 620;
            color: var(--deep-dark);
            margin-bottom: 4px;
        }

        .stat-desc {
            font-size: 12.5px;
            color: var(--ink-soft);
            margin-bottom: 10px;
        }

        .stat-link {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 12.5px;
            font-weight: 700;
            text-decoration: none;
        }

        .stat-link svg {
            width: 13px;
            height: 13px;
        }

        .stat-link.red  { color: var(--red); }
        .stat-link.gold { color: var(--gold); }
        .stat-link.sage { color: var(--sage); }
        .stat-link.deep { color: var(--deep); }

        /* ===================== PANELES MEDIOS ===================== */

        .paneles-medios {
            display: grid;
            grid-template-columns: 1.75fr 1fr;
            gap: 18px;
            margin-bottom: 24px;
            align-items: start;
        }

        .tarjeta {
            background: var(--paper-card);
            border: 1px solid var(--paper-line);
            border-radius: var(--radius);
            padding: 22px;

            opacity: 0;
            animation: aparecer 0.5s ease 0.25s forwards;
        }

        .tarjeta-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 4px;
        }

        .tarjeta-header h2 {
            font-size: 17px;
            font-weight: 620;
            color: var(--deep-dark);
        }

        .boton-fantasma {
            font-size: 12.5px;
            font-weight: 700;
            color: var(--deep);
            background: none;
            border: none;
            cursor: pointer;
            text-decoration: none;
        }

        .boton-fantasma:hover {
            text-decoration: underline;
        }

        .nota-privacidad {
            display: flex;
            align-items: center;
            gap: 7px;
            font-size: 12.5px;
            color: var(--ink-faint);
            margin: 4px 0 16px;
        }

        .nota-privacidad svg {
            width: 13px;
            height: 13px;
            stroke: var(--ink-faint);
            flex-shrink: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead th {
            text-align: left;
            font-size: 11.5px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: var(--ink-faint);
            font-weight: 700;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--paper-line);
        }

        tbody td {
            padding: 13px 6px;
            border-bottom: 1px solid var(--paper-line);
            font-size: 14px;
            vertical-align: middle;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        .vacio {
            text-align: center;
            padding: 34px 10px;
            color: var(--ink-faint);
            font-size: 13.5px;
        }

        .prioridad-punto {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            font-weight: 600;
        }

        .prioridad-punto::before {
            content: "";
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .prioridad-punto.alta::before  { background: var(--red); }
        .prioridad-punto.media::before { background: var(--gold); }
        .prioridad-punto.baja::before  { background: var(--sage); }
        .prioridad-punto.gris::before  { background: var(--gris); }

        .prioridad-punto.alta  { color: var(--red); }
        .prioridad-punto.media { color: var(--gold); }
        .prioridad-punto.baja  { color: var(--sage); }
        .prioridad-punto.gris  { color: var(--gris); }

        .celda-estudiante {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
        }

        .avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11.5px;
            font-weight: 700;
            color: #fff;
            flex-shrink: 0;
        }

        .avatar.deep { background: var(--deep-light); }
        .avatar.sage { background: var(--sage); }
        .avatar.gold { background: var(--gold); }

        .pill {
            display: inline-block;
            padding: 4px 11px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        .pill.rojo { background: var(--red-soft);  color: var(--red); }
        .pill.gold { background: var(--gold-soft); color: var(--gold); }
        .pill.sage { background: var(--sage-soft); color: var(--sage); }
        .pill.gris { background: var(--gris-soft); color: var(--gris); }

        .ver-btn {
            background: none;
            border: none;
            color: var(--deep);
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
        }

        .ver-btn:hover {
            text-decoration: underline;
        }

        /* ===================== CASO SELECCIONADO ===================== */

        .caso-tag {
            font-size: 11.5px;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 20px;
        }

        .caso-perfil {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 16px 0;
        }

        .caso-avatar {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            background: var(--deep-light);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }

        .caso-perfil-nombre {
            font-weight: 700;
            font-size: 15px;
        }

        .caso-perfil-meta {
            font-size: 12.5px;
            color: var(--ink-soft);
        }

        .caso-info {
            background: var(--paper);
            border-radius: var(--radius-sm);
            padding: 14px;
            font-size: 13px;
            color: var(--ink-soft);
            line-height: 1.5;
            margin-bottom: 18px;
        }

        .caso-info strong {
            display: block;
            color: var(--ink);
            font-size: 12.5px;
            margin-bottom: 4px;
        }

        .caso-acciones {
            display: flex;
            flex-direction: column;
            gap: 9px;
            margin-bottom: 18px;
        }

        .accion-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 11px 14px;
            border-radius: var(--radius-sm);
            font-size: 13.5px;
            font-weight: 700;
            cursor: pointer;
            border: 1px solid var(--paper-line);
            background: #fff;
            color: var(--ink);
        }

        .accion-btn svg {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
        }

        .accion-btn.principal {
            background: var(--deep);
            border-color: var(--deep);
            color: #fff;
        }

        .accion-btn.principal:hover {
            background: var(--deep-dark);
        }

        .accion-btn:not(.principal):hover {
            border-color: var(--deep);
        }

        .caso-historial {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .caso-historial-item {
            display: flex;
            gap: 10px;
            font-size: 12.5px;
        }

        .caso-historial-item .punto {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            margin-top: 5px;
            flex-shrink: 0;
        }

        .caso-historial-item .punto.rojo { background: var(--red); }
        .caso-historial-item .punto.gold { background: var(--gold); }
        .caso-historial-item .punto.sage { background: var(--sage); }
        .caso-historial-item .punto.gris { background: var(--gris); }

        .caso-historial-item span.hora {
            color: var(--ink-faint);
            display: block;
            font-size: 11.5px;
        }

        .caso-vacio {
            text-align: center;
            padding: 30px 10px;
            color: var(--ink-faint);
            font-size: 13.5px;
        }

        /* ===================== ACTIVIDAD RECIENTE ===================== */

        .actividad-lista {
            display: flex;
            flex-direction: column;
        }

        .actividad-item {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            padding: 14px 0;
            border-bottom: 1px solid var(--paper-line);
        }

        .actividad-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .actividad-icono {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .actividad-icono svg {
            width: 16px;
            height: 16px;
        }

        .actividad-icono.rojo { background: var(--red-soft);  color: var(--red); }
        .actividad-icono.sage { background: var(--sage-soft); color: var(--sage); }
        .actividad-icono.gold { background: var(--gold-soft); color: var(--gold); }

        .actividad-texto {
            flex: 1;
        }

        .actividad-texto p.principal {
            font-size: 14px;
            font-weight: 600;
        }

        .actividad-texto p.detalle {
            font-size: 12.5px;
            color: var(--ink-soft);
        }

        .actividad-tiempo {
            font-size: 12px;
            color: var(--ink-faint);
            white-space: nowrap;
        }

        /* ===================== ANIMACIÓN ===================== */

        @keyframes aparecer {
            from { opacity: 0; transform: translateY(10px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        @media (prefers-reduced-motion: reduce) {
            .stat-card, .tarjeta {
                animation: none;
                opacity: 1;
            }
        }

        /* ===================== RESPONSIVE ===================== */

        @media (max-width: 1100px) {
            .paneles-medios {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 900px) {
            .app {
                flex-direction: column;
            }
            .sidebar {
                width: 100%;
                flex-direction: row;
                flex-wrap: wrap;
                align-items: center;
            }
            .nav {
                flex-direction: row;
                flex-wrap: wrap;
            }
            .sidebar-pie {
                display: none;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 560px) {
            .contenido {
                padding: 22px 16px 40px;
            }
            .stats-grid {
                grid-template-columns: 1fr;
            }
            table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
        }

    </style>

</head>

<body>

<div class="app">

    <!-- ===================== BARRA LATERAL ===================== -->

    <aside class="sidebar">

        <div class="marca">
            <img src="../img/logo.png" alt="Logo SafeMind">
            <span>SafeMind</span>
        </div>

        <div class="perfil">
            <div class="perfil-avatar"><?php echo iniciales_es($nombre_usuario); ?></div>
            <div>
                <div class="perfil-nombre"><?php echo htmlspecialchars($nombre_usuario); ?></div>
                <div class="perfil-rol">
                    <span class="punto-verde"></span>
                    Profesor
                </div>
            </div>
        </div>

        <nav class="nav">

            <a href="#" class="nav-item activo">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 11l8-7 8 7"/><path d="M6 10v9h12v-9"/>
                </svg>
                Inicio
            </a>

            <a href="#" class="nav-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 4a5 5 0 00-5 5v3l-2 4h14l-2-4V9a5 5 0 00-5-5z"/><path d="M10 20a2 2 0 004 0"/>
                </svg>
                Alertas
                <?php if ($alertas_activas > 0): ?>
                    <span class="nav-badge"><?php echo $alertas_activas; ?></span>
                <?php endif; ?>
            </a>

            <a href="#" class="nav-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 20v-2a4 4 0 00-4-4H7a4 4 0 00-4 4v2"/><circle cx="10" cy="7" r="3.2"/>
                    <path d="M22 20v-2a4 4 0 00-3-3.9"/><path d="M15.5 3.6a4 4 0 010 7.7"/>
                </svg>
                Mis estudiantes
            </a>

            <a href="#" class="nav-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3.5" y="5" width="17" height="15.5" rx="2.5"/><path d="M3.5 9.5h17"/>
                    <path d="M8 3v3.5"/><path d="M16 3v3.5"/>
                </svg>
                Reuniones
            </a>

            <a href="#" class="nav-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 5.5c2-1 5-1.3 7 0v13c-2-1.3-5-1-7 0v-13z"/>
                    <path d="M20 5.5c-2-1-5-1.3-7 0v13c2-1.3 5-1 7 0v-13z"/>
                </svg>
                Recursos
            </a>

            <a href="#" class="nav-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="3"/>
                    <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 11-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 11-4 0v-.09a1.65 1.65 0 00-1.08-1.51 1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 11-2.83-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 110-4h.09a1.65 1.65 0 001.51-1.08 1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 112.83-2.83l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 114 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 112.83 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 110 4h-.09a1.65 1.65 0 00-1.51 1z"/>
                </svg>
                Configuración
            </a>

        </nav>

        <div class="sidebar-pie">

            <div class="apoyo-card">
                <div class="apoyo-card-titulo">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 20v-2a4 4 0 00-4-4H7a4 4 0 00-4 4v2"/><circle cx="10" cy="7" r="3.2"/>
                    </svg>
                    Equipo de orientación
                </div>
                <p>
                    Si detectas una situación de riesgo, repórtala
                    y el equipo de orientación se coordina de inmediato.
                </p>
                <a href="#">Contactar orientación</a>
            </div>

            <a href="../logout.php" class="salir">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
                    <path d="M16 17l5-5-5-5"/><path d="M21 12H9"/>
                </svg>
                Cerrar sesión
            </a>

        </div>

    </aside>


    <!-- ===================== CONTENIDO ===================== -->

    <main class="contenido">

        <header class="dash-header">

            <div>
                <h1>Panel del docente</h1>
                <p>Acompañamiento y seguimiento de tus estudiantes</p>
            </div>

            <div class="dash-header-derecha">

                <button class="campana" aria-label="Notificaciones">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 4a5 5 0 00-5 5v3l-2 4h14l-2-4V9a5 5 0 00-5-5z"/><path d="M10 20a2 2 0 004 0"/>
                    </svg>
                    <?php if ($alertas_activas > 0): ?>
                        <span class="punto-noti"><?php echo $alertas_activas; ?></span>
                    <?php endif; ?>
                </button>

                <div class="fecha">
                    <strong><?php echo fecha_es(); ?></strong>
                    <span><?php echo hora_es(); ?></span>
                </div>

            </div>

        </header>

        <!-- TARJETAS DE ESTADO -->

        <section class="stats-grid">

            <div class="stat-card">
                <div class="stat-top">
                    <span class="stat-icono red">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 9v4"/><path d="M12 17h.01"/>
                            <path d="M10.3 4.3L2.6 18a1.5 1.5 0 001.3 2.2h16.2a1.5 1.5 0 001.3-2.2L13.7 4.3a1.6 1.6 0 00-2.8 0z"/>
                        </svg>
                    </span>
                    <span class="stat-titulo">Alertas activas</span>
                </div>
                <div class="stat-valor"><?php echo $alertas_activas; ?></div>
                <div class="stat-desc">Requieren tu atención</div>
                <a href="#" class="stat-link red">
                    Ver todas
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
                </a>
            </div>

            <div class="stat-card">
                <div class="stat-top">
                    <span class="stat-icono gold">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 20v-2a4 4 0 00-4-4H7a4 4 0 00-4 4v2"/><circle cx="10" cy="7" r="3.2"/>
                        </svg>
                    </span>
                    <span class="stat-titulo">Estudiantes en seguimiento</span>
                </div>
                <div class="stat-valor"><?php echo $total_seguimiento; ?></div>
                <div class="stat-desc">Con al menos una evaluación</div>
                <a href="#" class="stat-link gold">
                    Ver todos
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
                </a>
            </div>

            <div class="stat-card">
                <div class="stat-top">
                    <span class="stat-icono sage">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 5.5c2-1 5-1.3 7 0v13c-2-1.3-5-1-7 0v-13z"/>
                            <path d="M20 5.5c-2-1-5-1.3-7 0v13c2-1.3 5-1 7 0v-13z"/>
                        </svg>
                    </span>
                    <span class="stat-titulo">Recursos de apoyo</span>
                </div>
                <div class="stat-valor"><?php echo $total_recursos; ?></div>
                <div class="stat-desc">Disponibles para compartir</div>
                <a href="#" class="stat-link sage">
                    Ver recursos
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
                </a>
            </div>

            <div class="stat-card">
                <div class="stat-top">
                    <span class="stat-icono deep">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="9"/>
                        </svg>
                    </span>
                    <span class="stat-titulo">Casos resueltos</span>
                </div>
                <div class="stat-valor"><?php echo $casos_resueltos_mes; ?></div>
                <div class="stat-desc">Este mes</div>
                <a href="#" class="stat-link deep">
                    Ver historial
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
                </a>
            </div>

        </section>

        <!-- TABLA + CASO SELECCIONADO -->

        <section class="paneles-medios">

            <div class="tarjeta">

                <div class="tarjeta-header">
                    <h2>Estudiantes con seguimiento</h2>
                    <button class="boton-fantasma">Ver todos</button>
                </div>

                <p class="nota-privacidad">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="4.5" y="10.5" width="15" height="9.5" rx="2"/><path d="M8 10.5V7a4 4 0 018 0v3.5"/>
                    </svg>
                    Solo se muestra el estado general. El detalle de cada caso lo gestiona el equipo de orientación.
                </p>

                <?php if (empty($estudiantes)): ?>

                    <p class="vacio">
                        Todavía no hay estudiantes con evaluaciones registradas.
                    </p>

                <?php else: ?>

                    <table>
                        <thead>
                            <tr>
                                <th>Prioridad</th>
                                <th>Estudiante</th>
                                <th>Curso</th>
                                <th>Estado</th>
                                <th>Actualizado</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($estudiantes as $i => $e):
                                $prioridad = nivel_a_prioridad($e['id_nivel']);
                                $estado_texto = !empty($e['alerta_estado']) ? $e['alerta_estado'] : ($e['evaluacion_estado'] ?: 'Sin alertas');
                                $estado_color = color_estado($estado_texto);
                                $fecha_mostrar = !empty($e['fecha_analisis']) ? $e['fecha_analisis'] : $e['fecha_inicio'];
                            ?>
                            <tr>
                                <td>
                                    <span class="prioridad-punto <?php echo $prioridad ?: 'gris'; ?>">
                                        <?php echo $prioridad ? ucfirst($prioridad) : 'Sin evaluar'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="celda-estudiante">
                                        <span class="avatar <?php echo $acentos[$i % 3]; ?>">
                                            <?php echo iniciales_es($e['nombre'] . ' ' . $e['apellido']); ?>
                                        </span>
                                        <?php echo htmlspecialchars($e['nombre'] . ' ' . $e['apellido']); ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($e['curso'] ?: '—'); ?></td>
                                <td>
                                    <span class="pill <?php echo $estado_color; ?>">
                                        <?php echo htmlspecialchars($estado_texto); ?>
                                    </span>
                                </td>
                                <td><?php echo tiempo_relativo($fecha_mostrar); ?></td>
                                <td><button class="ver-btn">Ver</button></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                <?php endif; ?>

            </div>

            <div class="tarjeta">

                <?php if ($caso_principal === null): ?>

                    <div class="tarjeta-header">
                        <h3>Caso seleccionado</h3>
                    </div>
                    <p class="caso-vacio">
                        No hay ningún caso activo por ahora.
                    </p>

                <?php else:
                    $prioridad_caso = nivel_a_prioridad($caso_principal['id_nivel']);
                    $seguimiento = isset($seguimientos_por_alerta[$caso_principal['id_alerta']])
                        ? $seguimientos_por_alerta[$caso_principal['id_alerta']]
                        : null;
                ?>

                    <div class="tarjeta-header">
                        <h3><?php echo htmlspecialchars($caso_principal['nombre']); ?></h3>
                        <?php if ($prioridad_caso): ?>
                            <span class="caso-tag pill <?php echo color_estado($caso_principal['nivel_nombre']); ?>">
                                <?php echo htmlspecialchars($caso_principal['nivel_nombre']); ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="caso-perfil">
                        <span class="caso-avatar"><?php echo iniciales_es($caso_principal['nombre'] . ' ' . $caso_principal['apellido']); ?></span>
                        <div>
                            <div class="caso-perfil-nombre">
                                <?php echo htmlspecialchars($caso_principal['nombre'] . ' ' . $caso_principal['apellido']); ?>
                            </div>
                            <div class="caso-perfil-meta">
                                Curso <?php echo htmlspecialchars($caso_principal['curso'] ?: '—'); ?>
                                · Última evaluación <?php echo tiempo_relativo($caso_principal['fecha_inicio']); ?>
                            </div>
                        </div>
                    </div>

                    <div class="caso-info">
                        <strong>Resumen para el docente</strong>
                        <?php if ($caso_principal['nivel_nombre']): ?>
                            El nivel de riesgo detectado es
                            "<?php echo htmlspecialchars($caso_principal['nivel_nombre']); ?>".
                            El equipo de orientación tiene el detalle completo del caso;
                            contáctalos si necesitas más contexto.
                        <?php else: ?>
                            Este estudiante tiene una evaluación registrada, pero aún
                            no hay un análisis de riesgo asociado.
                        <?php endif; ?>
                    </div>

                    <div class="caso-acciones">
                        <button class="accion-btn principal">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 20l1.4-4.2A8 8 0 1112 20a8 8 0 01-4-1.1z"/>
                            </svg>
                            Registrar observación
                        </button>
                        <button class="accion-btn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3.5" y="5" width="17" height="15.5" rx="2.5"/><path d="M3.5 9.5h17"/>
                            </svg>
                            Programar reunión
                        </button>
                        <button class="accion-btn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17 20v-2a4 4 0 00-4-4H7a4 4 0 00-4 4v2"/><circle cx="10" cy="7" r="3.2"/>
                            </svg>
                            Contactar orientación
                        </button>
                    </div>

                    <div class="caso-historial">
                        <?php if (!empty($caso_principal['id_alerta'])): ?>
                            <div class="caso-historial-item">
                                <span class="punto rojo"></span>
                                <div>
                                    Alerta generada por SafeMind
                                    <span class="hora"><?php echo tiempo_relativo($caso_principal['fecha_alerta']); ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if ($seguimiento): ?>
                            <div class="caso-historial-item">
                                <span class="punto <?php echo color_estado($seguimiento['estado']); ?>"></span>
                                <div>
                                    <?php echo htmlspecialchars($seguimiento['estado']); ?>
                                    <span class="hora"><?php echo tiempo_relativo($seguimiento['fecha']); ?></span>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="caso-historial-item">
                                <span class="punto gris"></span>
                                <div>Aún no hay seguimiento registrado por orientación</div>
                            </div>
                        <?php endif; ?>
                    </div>

                <?php endif; ?>

            </div>

        </section>

        <!-- ACTIVIDAD RECIENTE -->

        <section class="tarjeta">

            <div class="tarjeta-header">
                <h2>Actividad reciente</h2>
                <button class="boton-fantasma">Ver toda la actividad</button>
            </div>

            <?php if (empty($actividad)): ?>

                <p class="vacio">Sin actividad reciente.</p>

            <?php else: ?>

                <div class="actividad-lista">

                    <?php foreach ($actividad as $a):
                        $es_alerta = $a['tipo'] === 'alerta';
                        $clase_icono = $es_alerta ? 'rojo' : color_estado($a['etiqueta']);
                        $nombre_completo = htmlspecialchars($a['estudiante_nombre'] . ' ' . $a['estudiante_apellido']);
                    ?>
                    <div class="actividad-item">
                        <span class="actividad-icono <?php echo $clase_icono; ?>">
                            <?php if ($es_alerta): ?>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 9v4"/><path d="M12 17h.01"/>
                                    <path d="M10.3 4.3L2.6 18a1.5 1.5 0 001.3 2.2h16.2a1.5 1.5 0 001.3-2.2L13.7 4.3a1.6 1.6 0 00-2.8 0z"/>
                                </svg>
                            <?php else: ?>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="9"/>
                                </svg>
                            <?php endif; ?>
                        </span>
                        <div class="actividad-texto">
                            <p class="principal">
                                <?php echo $es_alerta ? 'Nueva alerta de ' . $nombre_completo : 'Seguimiento actualizado para ' . $nombre_completo; ?>
                            </p>
                            <p class="detalle">
                                <?php echo $a['etiqueta'] ? htmlspecialchars($a['etiqueta']) : 'Sin detalle adicional'; ?>
                            </p>
                        </div>
                        <div class="actividad-tiempo"><?php echo tiempo_relativo($a['fecha']); ?></div>
                    </div>
                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </section>

    </main>

</div>

</body>

</html>