<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'profesor') {
    header('Location: ../login.php?rol=profesor');
    exit();
}

require_once '../API/config/conexion.php';
$conexion = conectar();
$nombre_usuario = $_SESSION['nombre'] ?? 'Profesor';
$curso_profesor = $_SESSION['curso'] ?? null;

// Funciones auxiliares
function iniciales_es($nombre) {
    $partes = preg_split('/\s+/', trim($nombre));
    $ini = substr($partes[0], 0, 1);
    if (isset($partes[1])) $ini .= substr($partes[1], 0, 1);
    return strtoupper($ini);
}

function tiempo_relativo($fecha_str) {
    if (empty($fecha_str)) return '—';
    $fecha = new DateTime($fecha_str);
    $ahora = new DateTime();
    $diff = $ahora->diff($fecha);
    if ($diff->d >= 2) return $fecha->format('d/m/Y');
    if ($diff->d == 1) return 'Ayer';
    if ($diff->h >= 1) return 'Hace ' . $diff->h . 'h';
    return 'Hace ' . $diff->i . 'm';
}

function color_estado($texto) {
    $t = strtolower((string) $texto);
    if (strpos($t, 'crisis') !== false || strpos($t, 'urgen') !== false || strpos($t, 'pendient') !== false) return 'rojo';
    if (strpos($t, 'atenc') !== false || strpos($t, 'alerta') !== false || strpos($t, 'riesgo') !== false) return 'gold';
    if (strpos($t, 'resuel') !== false || strpos($t, 'estable') !== false) return 'sage';
    return 'gris';
}

// Obtener estadísticas
$sql_alertas = "SELECT COUNT(*) as total FROM alerta al 
                JOIN analisis_ia ai ON ai.id_analisis = al.id_analisis 
                JOIN evaluacion ev ON ev.id_evaluacion = ai.id_evaluacion 
                JOIN estudiante e ON e.id_estudiante = ev.id_estudiante 
                WHERE al.estado NOT LIKE '%resuelto%'";
if (!empty($curso_profesor)) $sql_alertas .= " AND e.curso = '" . mysqli_real_escape_string($conexion, $curso_profesor) . "'";
$alertas_activas = mysqli_fetch_assoc(mysqli_query($conexion, $sql_alertas))['total'];

$sql_est = "SELECT COUNT(DISTINCT e.id_estudiante) as total FROM estudiante e 
            JOIN evaluacion ev ON ev.id_estudiante = e.id_estudiante WHERE e.estado = 'Activo'";
if (!empty($curso_profesor)) $sql_est .= " AND e.curso = '" . mysqli_real_escape_string($conexion, $curso_profesor) . "'";
$total_estudiantes = mysqli_fetch_assoc(mysqli_query($conexion, $sql_est))['total'];

$total_recursos = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) as total FROM recurso_apoyo"))['total'];

// Últimos estudiantes
$sql_ultimos = "SELECT e.*, ev.fecha_inicio, ai.id_nivel, nr.nombre as nivel_nombre, al.estado as alerta_estado, al.id_alerta
                FROM estudiante e
                JOIN evaluacion ev ON ev.id_estudiante = e.id_estudiante
                LEFT JOIN analisis_ia ai ON ai.id_evaluacion = ev.id_evaluacion
                LEFT JOIN nivel_riesgo nr ON nr.id_nivel = ai.id_nivel
                LEFT JOIN alerta al ON al.id_analisis = ai.id_analisis
                WHERE e.estado = 'Activo'";
if (!empty($curso_profesor)) $sql_ultimos .= " AND e.curso = '" . mysqli_real_escape_string($conexion, $curso_profesor) . "'";
$sql_ultimos .= " ORDER BY ev.fecha_inicio DESC LIMIT 5";
$estudiantes = mysqli_fetch_all(mysqli_query($conexion, $sql_ultimos), MYSQLI_ASSOC);

// Actividad reciente
$sql_act = "(SELECT 'alerta' as tipo, al.fecha, e.nombre, e.apellido, nr.nombre as etiqueta
            FROM alerta al JOIN analisis_ia ai ON ai.id_analisis = al.id_analisis 
            JOIN evaluacion ev ON ev.id_evaluacion = ai.id_evaluacion 
            JOIN estudiante e ON e.id_estudiante = ev.id_estudiante
            LEFT JOIN nivel_riesgo nr ON nr.id_nivel = ai.id_nivel";
if (!empty($curso_profesor)) $sql_act .= " WHERE e.curso = '" . mysqli_real_escape_string($conexion, $curso_profesor) . "'";
$sql_act .= " ORDER BY al.fecha DESC LIMIT 3)";
$actividad = mysqli_fetch_all(mysqli_query($conexion, $sql_act), MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel del docente - SafeMind</title>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,450;9..144,560&family=Karla:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --paper: #F7F4ED; --paper-line: #E7E1D4; --paper-card: #FFFFFF;
            --ink: #1E2B2F; --ink-soft: #5B6B6F; --ink-faint: #9AA6A9;
            --deep: #17455A; --deep-dark: #0E2E3D; --deep-light: #3A7691; --deep-soft: #E3EBEE;
            --sage: #4F7F62; --sage-soft: #DCE9DF;
            --gold: #C8922F; --gold-soft: #F6E7C9;
            --red: #B54B3F; --red-soft: #FBE7E4;
            --radius: 16px; --radius-sm: 10px;
        }
        body { font-family: 'Karla', sans-serif; color: var(--ink); background: var(--paper); }
        h1, h2, h3 { font-family: 'Fraunces', serif; }
        a { color: inherit; text-decoration: none; }
        .app { display: flex; min-height: 100vh; }
        .sidebar { width: 264px; background: #fff; border-right: 1px solid var(--paper-line); padding: 26px 20px; }
        .marca { display: flex; align-items: center; gap: 10px; margin-bottom: 28px; }
        .marca img { width: 34px; height: 34px; border-radius: 9px; }
        .marca span { font-family: 'Fraunces'; font-size: 19px; font-weight: 650; color: var(--deep-dark); }
        .perfil { display: flex; align-items: center; gap: 12px; padding: 10px; border-radius: var(--radius-sm); margin-bottom: 20px; background: var(--paper); }
        .perfil-avatar { width: 40px; height: 40px; border-radius: 50%; background: var(--deep-soft); color: var(--deep); display: flex; align-items: center; justify-content: center; font-weight: 700; }
        .perfil-nombre { font-size: 14.5px; font-weight: 700; }
        .perfil-rol { font-size: 12.5px; color: var(--ink-soft); }
        .nav { display: flex; flex-direction: column; gap: 3px; }
        .nav-item { display: flex; align-items: center; gap: 12px; padding: 10px 12px; border-radius: var(--radius-sm); font-size: 14.5px; font-weight: 600; color: var(--ink-soft); }
        .nav-item:hover { background: var(--paper); color: var(--ink); }
        .nav-item.activo { background: var(--deep-soft); color: var(--deep); }
        .nav-item svg { width: 18px; height: 18px; }
        .nav-badge { margin-left: auto; background: var(--red); color: #fff; font-size: 11px; padding: 1px 7px; border-radius: 20px; }
        .sidebar-pie { margin-top: auto; }
        .apoyo-card { background: var(--sage-soft); border-radius: var(--radius-sm); padding: 14px; margin-bottom: 14px; }
        .apoyo-card-titulo { font-size: 13px; font-weight: 700; color: var(--deep-dark); margin-bottom: 6px; }
        .apoyo-card p { font-size: 12.5px; color: var(--ink-soft); margin-bottom: 8px; line-height: 1.5; }
        .apoyo-card a { font-size: 12.5px; font-weight: 700; color: var(--sage); }
        .salir { display: flex; align-items: center; gap: 10px; font-size: 13.5px; color: var(--ink-faint); }
        .salir:hover { color: var(--red); }
        .contenido { flex: 1; padding: 30px 38px; }
        .dash-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 26px; }
        .dash-header h1 { font-size: 27px; color: var(--deep-dark); }
        .dash-header p { color: var(--ink-soft); }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
        .stat-card { background: var(--paper-card); border: 1px solid var(--paper-line); border-radius: var(--radius); padding: 18px; }
        .stat-top { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }
        .stat-icono { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; }
        .stat-icono svg { width: 18px; height: 18px; }
        .stat-icono.red { background: var(--red-soft); color: var(--red); }
        .stat-icono.gold { background: var(--gold-soft); color: var(--gold); }
        .stat-icono.sage { background: var(--sage-soft); color: var(--sage); }
        .stat-titulo { font-size: 13.5px; font-weight: 700; }
        .stat-valor { font-family: 'Fraunces'; font-size: 32px; font-weight: 620; color: var(--deep-dark); margin-bottom: 4px; }
        .stat-desc { font-size: 12.5px; color: var(--ink-soft); margin-bottom: 10px; }
        .stat-link { font-size: 12.5px; font-weight: 700; }
        .stat-link.red { color: var(--red); }
        .stat-link.gold { color: var(--gold); }
        .stat-link.sage { color: var(--sage); }
        .paneles { display: grid; grid-template-columns: 1.75fr 1fr; gap: 18px; margin-bottom: 24px; }
        .tarjeta { background: var(--paper-card); border: 1px solid var(--paper-line); border-radius: var(--radius); padding: 22px; }
        .tarjeta-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
        .tarjeta-header h2 { font-size: 17px; color: var(--deep-dark); }
        .boton-fantasma { font-size: 12.5px; font-weight: 700; color: var(--deep); background: none; border: none; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px; border-bottom: 2px solid var(--paper-line); font-size: 12px; text-transform: uppercase; color: var(--ink-faint); }
        td { padding: 14px 12px; border-bottom: 1px solid var(--paper-line); }
        .pill { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; }
        .pill.rojo { background: var(--red-soft); color: var(--red); }
        .pill.gold { background: var(--gold-soft); color: var(--gold); }
        .pill.sage { background: var(--sage-soft); color: var(--sage); }
        .pill.gris { background: var(--gris-soft); color: var(--gris); }
        .prioridad { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; }
        .prioridad::before { content: ""; width: 8px; height: 8px; border-radius: 50%; }
        .prioridad.alta::before { background: var(--red); }
        .prioridad.media::before { background: var(--gold); }
        .prioridad.baja::before { background: var(--sage); }
        .avatar { width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 11.5px; font-weight: 700; color: #fff; margin-right: 10px; }
        .avatar.deep { background: var(--deep-light); }
        .avatar.sage { background: var(--sage); }
        .avatar.gold { background: var(--gold); }
        .ver-btn { background: none; border: none; color: var(--deep); font-weight: 700; cursor: pointer; font-size: 13px; }
        .ver-btn:hover { text-decoration: underline; }
        .vacio { text-align: center; padding: 40px; color: var(--ink-faint); }
        .actividad-item { display: flex; align-items: flex-start; gap: 14px; padding: 14px 0; border-bottom: 1px solid var(--paper-line); }
        .actividad-item:last-child { border-bottom: none; }
        .actividad-icono { width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .actividad-icono svg { width: 16px; height: 16px; }
        .actividad-icono.rojo { background: var(--red-soft); color: var(--red); }
        .actividad-texto { flex: 1; }
        .actividad-texto p { font-size: 14px; font-weight: 600; margin-bottom: 4px; }
        .actividad-texto span { font-size: 12.5px; color: var(--ink-soft); }
        .actividad-tiempo { font-size: 12px; color: var(--ink-faint); }
    </style>
</head>
<body>
<div class="app">
    <aside class="sidebar">
        <div class="marca">
            <img src="../img/logo.png" alt="SafeMind">
            <span>SafeMind</span>
        </div>
        <div class="perfil">
            <div class="perfil-avatar"><?php echo iniciales_es($nombre_usuario); ?></div>
            <div>
                <div class="perfil-nombre"><?php echo htmlspecialchars($nombre_usuario); ?></div>
                <div class="perfil-rol">Profesor</div>
            </div>
        </div>
        <nav class="nav">
            <a href="dashboard_docente.php" class="nav-item activo">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 11l8-7 8 7"/><path d="M6 10v9h12v-9"/></svg>
                Inicio
            </a>
            <a href="alertas.php" class="nav-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 4a5 5 0 00-5 5v3l-2 4h14l-2-4V9a5 5 0 00-5-5z"/><path d="M10 20a2 2 0 004 0"/></svg>
                Alertas
                <?php if ($alertas_activas > 0): ?><span class="nav-badge"><?php echo $alertas_activas; ?></span><?php endif; ?>
            </a>
            <a href="estudiantes.php" class="nav-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 20v-2a4 4 0 00-4-4H7a4 4 0 00-4 4v2"/><circle cx="10" cy="7" r="3.2"/></svg>
                Mis estudiantes
            </a>
        </nav>
        <div class="sidebar-pie">
            <div class="apoyo-card">
                <div class="apoyo-card-titulo">Equipo de orientación</div>
                <p>Si detectas riesgo, repórtalo inmediatamente.</p>
                <a href="#">Contactar orientación</a>
            </div>
            <a href="../logout.php" class="salir">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
                Cerrar sesión
            </a>
        </div>
    </aside>

    <main class="contenido">
        <header class="dash-header">
            <div>
                <h1>Panel del docente</h1>
                <p>Acompañamiento y seguimiento de tus estudiantes</p>
            </div>
            <div style="text-align:right;font-size:13px;">
                <strong><?php echo date('d \d\e F \d\e Y'); ?></strong><br>
                <span style="color:var(--ink-faint)"><?php echo date('h:i a'); ?></span>
            </div>
        </header>

        <section class="stats-grid">
            <div class="stat-card">
                <div class="stat-top">
                    <span class="stat-icono red">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.3 4.3L2.6 18a1.5 1.5 0 001.3 2.2h16.2a1.5 1.5 0 001.3-2.2L13.7 4.3a1.6 1.6 0 00-2.8 0z"/></svg>
                    </span>
                    <span class="stat-titulo">Alertas activas</span>
                </div>
                <div class="stat-valor"><?php echo $alertas_activas; ?></div>
                <div class="stat-desc">Requieren tu atención</div>
                <a href="alertas.php" class="stat-link red">Ver todas →</a>
            </div>
            <div class="stat-card">
                <div class="stat-top">
                    <span class="stat-icono gold">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 20v-2a4 4 0 00-4-4H7a4 4 0 00-4 4v2"/><circle cx="10" cy="7" r="3.2"/></svg>
                    </span>
                    <span class="stat-titulo">Estudiantes</span>
                </div>
                <div class="stat-valor"><?php echo $total_estudiantes; ?></div>
                <div class="stat-desc">Con evaluaciones</div>
                <a href="estudiantes.php" class="stat-link gold">Ver todos →</a>
            </div>
            <div class="stat-card">
                <div class="stat-top">
                    <span class="stat-icono sage">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 5.5c2-1 5-1.3 7 0v13"/><path d="M20 5.5c-2-1-5-1.3-7 0v13"/></svg>
                    </span>
                    <span class="stat-titulo">Recursos</span>
                </div>
                <div class="stat-valor"><?php echo $total_recursos; ?></div>
                <div class="stat-desc">Disponibles</div>
            </div>
            <div class="stat-card">
                <div class="stat-top">
                    <span class="stat-icono" style="background:var(--deep-soft);color:var(--deep)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="9"/></svg>
                    </span>
                    <span class="stat-titulo">Casos resueltos</span>
                </div>
                <div class="stat-valor">0</div>
                <div class="stat-desc">Este mes</div>
            </div>
        </section>

        <section class="paneles">
            <div class="tarjeta">
                <div class="tarjeta-header">
                    <h2>Estudiantes con seguimiento</h2>
                    <a href="estudiantes.php" class="boton-fantasma">Ver todos</a>
                </div>
                <?php if (empty($estudiantes)): ?>
                    <p class="vacio">No hay estudiantes con evaluaciones</p>
                <?php else: ?>
                <table>
                    <thead>
                        <tr><th>Prioridad</th><th>Estudiante</th><th>Estado</th><th>Actualizado</th><th></th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($estudiantes as $e): 
                            $prioridad = $e['id_nivel'] >= 3 ? 'alta' : ($e['id_nivel'] == 2 ? 'media' : 'baja');
                            $estado = $e['alerta_estado'] ?? 'Sin alertas';
                            $color = color_estado($estado);
                        ?>
                        <tr>
                            <td><span class="prioridad <?php echo $prioridad; ?>"><?php echo ucfirst($prioridad); ?></span></td>
                            <td>
                                <div style="display:flex;align-items:center;">
                                    <span class="avatar <?php echo $prioridad; ?>"><?php echo iniciales_es($e['nombre']); ?></span>
                                    <?php echo htmlspecialchars($e['nombre'] . ' ' . $e['apellido']); ?>
                                </div>
                            </td>
                            <td><span class="pill <?php echo $color; ?>"><?php echo htmlspecialchars($estado); ?></span></td>
                            <td><?php echo tiempo_relativo($e['fecha_inicio']); ?></td>
                            <td><a href="estudiantes.php?id=<?php echo $e['id_estudiante']; ?>" class="ver-btn">Ver</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>

            <div class="tarjeta">
                <div class="tarjeta-header"><h2>Actividad reciente</h2></div>
                <?php if (empty($actividad)): ?>
                    <p class="vacio">Sin actividad reciente</p>
                <?php else: ?>
                <div>
                    <?php foreach ($actividad as $a): ?>
                    <div class="actividad-item">
                        <span class="actividad-icono rojo">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                        </span>
                        <div class="actividad-texto">
                            <p><?php echo htmlspecialchars($a['nombre'] . ' ' . $a['apellido']); ?></p>
                            <span><?php echo htmlspecialchars($a['etiqueta'] ?? 'Alerta'); ?></span>
                        </div>
                        <div class="actividad-tiempo"><?php echo tiempo_relativo($a['fecha']); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
</div>
</body>
</html>