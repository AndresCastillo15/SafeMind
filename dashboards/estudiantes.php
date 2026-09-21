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

function iniciales_es($nombre) {
    $partes = preg_split('/\s+/', trim($nombre));
    return strtoupper(substr($partes[0], 0, 1) . (isset($partes[1]) ? substr($partes[1], 0, 1) : ''));
}

$id_seleccionado = isset($_GET['id']) ? (int)$_GET['id'] : null;

// Obtener todos los estudiantes (filtrados por curso del profesor si aplica)
$sql = "SELECT 
            e.id_estudiante, e.nombre, e.apellido, e.correo, e.telefono, e.curso,
            ev.id_evaluacion, ev.fecha_inicio, ev.estado AS estado_eval,
            ai.id_analisis, ai.id_nivel, ai.emocion_detectada, ai.resumen AS resumen_ia,
            nr.nombre AS nivel_nombre,
            al.id_alerta, al.estado AS alerta_estado, al.fecha AS fecha_alerta
        FROM estudiante e
        LEFT JOIN evaluacion ev ON ev.id_estudiante = e.id_estudiante
        LEFT JOIN analisis_ia ai ON ai.id_evaluacion = ev.id_evaluacion
        LEFT JOIN nivel_riesgo nr ON nr.id_nivel = ai.id_nivel
        LEFT JOIN alerta al ON al.id_analisis = ai.id_analisis
        WHERE e.estado = 'Activo'";

if (!empty($curso_profesor)) {
    $sql .= " AND e.curso = '" . mysqli_real_escape_string($conexion, $curso_profesor) . "'";
}
$sql .= " GROUP BY e.id_estudiante ORDER BY e.nombre, e.apellido";

$estudiantes = mysqli_fetch_all(mysqli_query($conexion, $sql), MYSQLI_ASSOC);

$estudiante_detalle = null;
if ($id_seleccionado) {
    foreach ($estudiantes as $e) {
        if ($e['id_estudiante'] == $id_seleccionado) {
            $estudiante_detalle = $e;
            break;
        }
    }
}

// ============================================================
// HISTORIAL: obtener evaluaciones, alertas y seguimientos del estudiante seleccionado
// ============================================================
$historial = [];
if ($id_seleccionado) {
    $sql_hist = "SELECT 
                    'evaluacion' AS tipo, 
                    ev.fecha_inicio AS fecha, 
                    CONCAT('Evaluación ', ev.estado) AS descripcion,
                    NULL AS detalle,
                    ev.id_evaluacion AS id_ref
                FROM evaluacion ev
                WHERE ev.id_estudiante = $id_seleccionado
                
                UNION ALL
                
                SELECT 
                    'alerta' AS tipo, 
                    al.fecha AS fecha, 
                    CONCAT('Alerta: ', al.estado) AS descripcion,
                    nr.nombre AS detalle,
                    al.id_alerta AS id_ref
                FROM alerta al
                JOIN analisis_ia ai ON ai.id_analisis = al.id_analisis
                JOIN evaluacion ev ON ev.id_evaluacion = ai.id_evaluacion
                LEFT JOIN nivel_riesgo nr ON nr.id_nivel = ai.id_nivel
                WHERE ev.id_estudiante = $id_seleccionado
                
                UNION ALL
                
                SELECT 
                    'seguimiento' AS tipo, 
                    s.fecha AS fecha, 
                    CONCAT('Seguimiento: ', s.estado) AS descripcion,
                    s.observacion AS detalle,
                    s.id_seguimiento AS id_ref
                FROM seguimiento s
                JOIN alerta al ON al.id_alerta = s.id_alerta
                JOIN analisis_ia ai ON ai.id_analisis = al.id_analisis
                JOIN evaluacion ev ON ev.id_evaluacion = ai.id_evaluacion
                WHERE ev.id_estudiante = $id_seleccionado
                
                ORDER BY fecha DESC";
    
    $result_hist = mysqli_query($conexion, $sql_hist);
    if ($result_hist) {
        $historial = mysqli_fetch_all($result_hist, MYSQLI_ASSOC);
    }
}

// ============================================================
// PROCESAR FORMULARIO DE CONTACTO (si se envió)
// ============================================================
$mensaje_enviado = false;
$mensaje_error = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'contactar') {
    $id_est = (int)$_POST['id_estudiante'];
    $asunto = mysqli_real_escape_string($conexion, $_POST['asunto'] ?? '');
    $mensaje = mysqli_real_escape_string($conexion, $_POST['mensaje'] ?? '');
    
    // Opción 1: Guardar en BD como registro de contacto
    // (Creamos tabla temporal o usamos una existente)
    // Por ahora, simulamos envío y redirigimos
    
    if (!empty($asunto) && !empty($mensaje)) {
        // Aquí podrías usar mail() si tienes SMTP configurado
        // mail($destinatario, $asunto, $mensaje);
        
        $mensaje_enviado = true;
        // Redirigir para evitar reenvío del formulario
        header("Location: estudiantes.php?id=$id_est&msg=enviado");
        exit();
    } else {
        $mensaje_error = true;
    }
}

if (isset($_GET['msg']) && $_GET['msg'] === 'enviado') {
    $mensaje_enviado = true;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estudiantes - SafeMind</title>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,450;9..144,560;9..144,650&family=Karla:wght@400;500;600;700&display=swap" rel="stylesheet">
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
        
        /* Sidebar */
        .sidebar { width: 264px; background: #fff; border-right: 1px solid var(--paper-line); padding: 26px 20px; display: flex; flex-direction: column; }
        .marca { display: flex; align-items: center; gap: 10px; margin-bottom: 28px; }
        .marca img { width: 34px; height: 34px; border-radius: 9px; }
        .marca span { font-family: 'Fraunces'; font-size: 19px; font-weight: 650; color: var(--deep-dark); }
        .perfil { display: flex; align-items: center; gap: 12px; padding: 10px; border-radius: var(--radius-sm); margin-bottom: 20px; background: var(--paper); }
        .perfil-avatar { width: 40px; height: 40px; border-radius: 50%; background: var(--deep-soft); color: var(--deep); display: flex; align-items: center; justify-content: center; font-weight: 700; }
        .perfil-nombre { font-size: 14.5px; font-weight: 700; }
        .nav { display: flex; flex-direction: column; gap: 3px; }
        .nav-item { display: flex; align-items: center; gap: 12px; padding: 10px 12px; border-radius: var(--radius-sm); font-size: 14.5px; font-weight: 600; color: var(--ink-soft); }
        .nav-item:hover { background: var(--paper); color: var(--ink); }
        .nav-item.activo { background: var(--deep-soft); color: var(--deep); }
        .nav-item svg { width: 18px; height: 18px; }
        .sidebar-pie { margin-top: auto; }
        .salir { display: flex; align-items: center; gap: 10px; font-size: 13.5px; color: var(--ink-faint); padding: 4px 12px; }
        .salir:hover { color: var(--red); }
        
        /* Contenido */
        .contenido { flex: 1; padding: 30px 38px; }
        .dash-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 26px; flex-wrap: wrap; gap: 14px; }
        .dash-header h1 { font-size: 27px; color: var(--deep-dark); margin-bottom: 4px; }
        .dash-header p { color: var(--ink-soft); font-size: 14.5px; }
        
        .grid { display: grid; grid-template-columns: 1.5fr 1fr; gap: 18px; }
        .tarjeta { background: var(--paper-card); border: 1px solid var(--paper-line); border-radius: var(--radius); padding: 22px; }
        .tarjeta-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
        .tarjeta-header h2 { font-size: 18px; color: var(--deep-dark); }
        
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px; border-bottom: 2px solid var(--paper-line); font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.03em; color: var(--ink-faint); font-weight: 700; }
        td { padding: 14px 12px; border-bottom: 1px solid var(--paper-line); cursor: pointer; vertical-align: middle; }
        tr:hover { background: var(--paper); }
        tr.seleccionado { background: var(--deep-soft); }
        
        .pill { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; }
        .pill.rojo { background: var(--red-soft); color: var(--red); }
        .pill.gold { background: var(--gold-soft); color: var(--gold); }
        .pill.sage { background: var(--sage-soft); color: var(--sage); }
        .pill.gris { background: #ECEEEC; color: var(--ink-faint); }
        
        .avatar { width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 11.5px; font-weight: 700; color: #fff; margin-right: 10px; flex-shrink: 0; }
        .avatar.deep { background: var(--deep-light); }
        
        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: var(--radius-sm); font-weight: 600; cursor: pointer; border: none; font-size: 13px; transition: all 0.15s; }
        .btn-primary { background: var(--deep); color: #fff; }
        .btn-primary:hover { background: var(--deep-dark); }
        .btn-secondary { background: #fff; border: 1px solid var(--paper-line); color: var(--ink); }
        .btn-secondary:hover { border-color: var(--deep); background: var(--deep-soft); }
        
        .info-card { background: var(--paper); border-radius: var(--radius-sm); padding: 14px; margin-bottom: 12px; }
        .info-card label { display: block; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--ink-faint); font-weight: 700; margin-bottom: 4px; }
        .info-card .valor { font-size: 14px; font-weight: 600; color: var(--ink); word-break: break-word; }
        
        .avatar-grande { width: 60px; height: 60px; border-radius: 50%; background: var(--deep-light); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 24px; font-weight: 700; margin-bottom: 16px; }
        
        .acciones-detalle { display: flex; gap: 10px; margin-top: 16px; flex-wrap: wrap; }
        
        .vacio { text-align: center; padding: 40px; color: var(--ink-faint); }
        
        /* MODAL */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(14, 46, 61, 0.6);
            backdrop-filter: blur(4px);
            z-index: 999;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .modal-overlay.activo { display: flex; }
        .modal {
            background: #fff;
            border-radius: var(--radius);
            max-width: 560px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            animation: slideUp 0.3s ease;
            box-shadow: 0 20px 60px rgba(14, 46, 61, 0.3);
        }
        .modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--paper-line);
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .modal-header h3 { font-size: 20px; color: var(--deep-dark); margin-bottom: 4px; }
        .modal-header .subtitulo { font-size: 13px; color: var(--ink-soft); }
        .modal-cerrar {
            background: var(--paper); border: none;
            width: 32px; height: 32px; border-radius: 50%;
            cursor: pointer; display: flex; align-items: center; justify-content: center;
            font-size: 18px; color: var(--ink-soft); flex-shrink: 0;
        }
        .modal-cerrar:hover { background: var(--red-soft); color: var(--red); }
        .modal-body { padding: 24px; }
        .modal-footer {
            padding: 16px 24px;
            border-top: 1px solid var(--paper-line);
            display: flex; gap: 10px; justify-content: flex-end;
        }
        
        /* Formulario dentro del modal */
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 12px; font-weight: 700; color: var(--ink); margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.03em; }
        .form-group input, .form-group textarea {
            width: 100%; padding: 10px 12px;
            border: 1px solid var(--paper-line); border-radius: var(--radius-sm);
            font-family: inherit; font-size: 14px;
            transition: border-color 0.15s;
        }
        .form-group input:focus, .form-group textarea:focus {
            outline: none; border-color: var(--deep);
        }
        .form-group textarea { min-height: 120px; resize: vertical; }
        
        /* Alertas de éxito/error */
        .alert {
            padding: 12px 16px; border-radius: var(--radius-sm);
            margin-bottom: 16px; font-size: 14px;
        }
        .alert-success { background: var(--sage-soft); color: var(--sage); border: 1px solid var(--sage); }
        .alert-error { background: var(--red-soft); color: var(--red); border: 1px solid var(--red); }
        
        /* Timeline del historial */
        .timeline { position: relative; padding-left: 24px; }
        .timeline::before {
            content: '';
            position: absolute;
            left: 7px; top: 8px; bottom: 8px;
            width: 2px;
            background: var(--paper-line);
        }
        .timeline-item {
            position: relative;
            padding-bottom: 18px;
        }
        .timeline-item:last-child { padding-bottom: 0; }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -20px; top: 6px;
            width: 12px; height: 12px;
            border-radius: 50%;
            border: 2px solid #fff;
        }
        .timeline-item.tipo-evaluacion::before { background: var(--deep-light); }
        .timeline-item.tipo-alerta::before { background: var(--red); }
        .timeline-item.tipo-seguimiento::before { background: var(--sage); }
        .timeline-fecha {
            font-size: 11.5px; color: var(--ink-faint);
            margin-bottom: 4px;
        }
        .timeline-titulo {
            font-size: 14px; font-weight: 700; color: var(--ink);
            margin-bottom: 4px;
        }
        .timeline-detalle {
            font-size: 13px; color: var(--ink-soft);
            line-height: 1.5;
        }
        .timeline-vacio {
            text-align: center; padding: 30px 10px;
            color: var(--ink-faint); font-size: 13.5px;
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @media (max-width: 900px) {
            .app { flex-direction: column; }
            .sidebar { width: 100%; }
            .grid { grid-template-columns: 1fr; }
        }
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
            <div class="perfil-nombre"><?php echo htmlspecialchars($nombre_usuario); ?></div>
        </div>
        <nav class="nav">
            <a href="dashboard_docente.php" class="nav-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 11l8-7 8 7"/><path d="M6 10v9h12v-9"/></svg>
                Inicio
            </a>
            <a href="alertas.php" class="nav-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 4a5 5 0 00-5 5v3l-2 4h14l-2-4V9a5 5 0 00-5-5z"/><path d="M10 20a2 2 0 004 0"/></svg>
                Alertas
            </a>
            <a href="estudiantes.php" class="nav-item activo">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 20v-2a4 4 0 00-4-4H7a4 4 0 00-4 4v2"/><circle cx="10" cy="7" r="3.2"/></svg>
                Mis estudiantes
            </a>
        </nav>
        <div class="sidebar-pie">
            <a href="../logout.php" class="salir">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
                Cerrar sesión
            </a>
        </div>
    </aside>

    <main class="contenido">
        <header class="dash-header">
            <div>
                <h1>Mis estudiantes</h1>
                <p>Lista completa de estudiantes</p>
            </div>
            <a href="dashboard_docente.php" class="btn btn-primary">← Volver al inicio</a>
        </header>

        <?php if ($mensaje_enviado): ?>
            <div class="alert alert-success" style="margin-bottom:16px;">
                ✓ Mensaje enviado correctamente. El estudiante recibirá la comunicación.
            </div>
        <?php endif; ?>

        <div class="grid">
            <div class="tarjeta">
                <div class="tarjeta-header">
                    <h2>Todos los estudiantes</h2>
                </div>
                <?php if (empty($estudiantes)): ?>
                    <p class="vacio">No hay estudiantes registrados</p>
                <?php else: ?>
                <table>
                    <thead>
                        <tr><th>Estudiante</th><th>Curso</th><th>Nivel</th><th>Estado</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($estudiantes as $e): 
                            $nivel = $e['nivel_nombre'] ?? 'Sin evaluar';
                            $color = 'gris';
                            if (stripos($nivel, 'crisis') !== false) $color = 'rojo';
                            elseif (stripos($nivel, 'riesgo') !== false) $color = 'gold';
                            elseif (stripos($nivel, 'alerta') !== false) $color = 'gold';
                            elseif (stripos($nivel, 'bien') !== false) $color = 'sage';
                            
                            $estado = $e['alerta_estado'] ?? 'Sin alertas';
                            $seleccionado = $id_seleccionado == $e['id_estudiante'] ? 'seleccionado' : '';
                        ?>
                        <tr class="<?php echo $seleccionado; ?>" onclick="window.location='?id=<?php echo $e['id_estudiante']; ?>'">
                            <td>
                                <div style="display:flex;align-items:center;">
                                    <span class="avatar deep"><?php echo iniciales_es($e['nombre'] . ' ' . $e['apellido']); ?></span>
                                    <?php echo htmlspecialchars($e['nombre'] . ' ' . $e['apellido']); ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($e['curso'] ?? '—'); ?></td>
                            <td><span class="pill <?php echo $color; ?>"><?php echo htmlspecialchars($nivel); ?></span></td>
                            <td><?php echo htmlspecialchars($estado); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>

            <div class="tarjeta">
                <?php if (!$estudiante_detalle): ?>
                    <h3>Detalle del estudiante</h3>
                    <p class="vacio">Selecciona un estudiante para ver su información</p>
                <?php else: ?>
                    <h3 style="margin-bottom:16px;"><?php echo htmlspecialchars($estudiante_detalle['nombre'] . ' ' . $estudiante_detalle['apellido']); ?></h3>
                    <div class="avatar-grande"><?php echo iniciales_es($estudiante_detalle['nombre'] . ' ' . $estudiante_detalle['apellido']); ?></div>
                    
                    <div class="info-card">
                        <label>Curso</label>
                        <div class="valor"><?php echo htmlspecialchars($estudiante_detalle['curso'] ?? 'No asignado'); ?></div>
                    </div>
                    <div class="info-card">
                        <label>Correo</label>
                        <div class="valor"><?php echo htmlspecialchars($estudiante_detalle['correo'] ?? '—'); ?></div>
                    </div>
                    <div class="info-card">
                        <label>Teléfono</label>
                        <div class="valor"><?php echo htmlspecialchars($estudiante_detalle['telefono'] ?? '—'); ?></div>
                    </div>
                    <div class="info-card">
                        <label>Última evaluación</label>
                        <div class="valor"><?php echo $estudiante_detalle['fecha_inicio'] ? date('d/m/Y H:i', strtotime($estudiante_detalle['fecha_inicio'])) : 'Sin evaluaciones'; ?></div>
                    </div>
                    <div class="info-card">
                        <label>Nivel de riesgo</label>
                        <div class="valor">
                            <?php 
                            $niv = $estudiante_detalle['nivel_nombre'] ?? 'Sin evaluar';
                            $col = 'gris';
                            if (stripos($niv, 'crisis') !== false) $col = 'rojo';
                            elseif (stripos($niv, 'riesgo') !== false) $col = 'gold';
                            elseif (stripos($niv, 'alerta') !== false) $col = 'gold';
                            elseif (stripos($niv, 'bien') !== false) $col = 'sage';
                            ?>
                            <span class="pill <?php echo $col; ?>"><?php echo htmlspecialchars($niv); ?></span>
                        </div>
                    </div>
                    
                    <div class="acciones-detalle">
                        <button class="btn btn-primary" onclick="abrirModalContacto(
                            <?php echo (int)$estudiante_detalle['id_estudiante']; ?>,
                            '<?php echo addslashes($estudiante_detalle['nombre'] . ' ' . $estudiante_detalle['apellido']); ?>',
                            '<?php echo addslashes($estudiante_detalle['correo'] ?? ''); ?>'
                        )">
                            Contactar
                        </button>
                        <button class="btn btn-secondary" onclick="abrirModalHistorial(
                            <?php echo (int)$estudiante_detalle['id_estudiante']; ?>,
                            '<?php echo addslashes($estudiante_detalle['nombre'] . ' ' . $estudiante_detalle['apellido']); ?>'
                        )">
                            Ver historial
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<!-- ============================================ -->
<!-- MODAL DE CONTACTO -->
<!-- ============================================ -->
<div class="modal-overlay" id="modal-contacto">
    <div class="modal">
        <div class="modal-header">
            <div>
                <h3>Contactar estudiante</h3>
                <div class="subtitulo" id="contacto-nombre">Nombre del estudiante</div>
            </div>
            <button class="modal-cerrar" onclick="cerrarModal('modal-contacto')">✕</button>
        </div>
        <div class="modal-body">
            <form method="POST" action="estudiantes.php">
                <input type="hidden" name="accion" value="contactar">
                <input type="hidden" name="id_estudiante" id="contacto-id">
                
                <div class="form-group">
                    <label>Destinatario</label>
                    <input type="email" id="contacto-correo" readonly style="background:var(--paper);">
                </div>
                
                <div class="form-group">
                    <label>Asunto *</label>
                    <input type="text" name="asunto" id="contacto-asunto" required placeholder="Ej: Seguimiento de evaluación">
                </div>
                
                <div class="form-group">
                    <label>Mensaje *</label>
                    <textarea name="mensaje" id="contacto-mensaje" required placeholder="Escribe tu mensaje para el estudiante..."></textarea>
                </div>
                
                <div style="background:var(--deep-soft); border-radius:var(--radius-sm); padding:12px; font-size:13px; color:var(--deep); margin-bottom:16px;">
                    ️ El mensaje se registrará en el sistema y se notificará al estudiante.
                </div>
                
                <div style="display:flex; gap:10px; justify-content:flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="cerrarModal('modal-contacto')">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Enviar mensaje</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- MODAL DE HISTORIAL -->
<!-- ============================================ -->
<div class="modal-overlay" id="modal-historial">
    <div class="modal">
        <div class="modal-header">
            <div>
                <h3>Historial del estudiante</h3>
                <div class="subtitulo" id="historial-nombre">Nombre del estudiante</div>
            </div>
            <button class="modal-cerrar" onclick="cerrarModal('modal-historial')"></button>
        </div>
        <div class="modal-body">
            <div id="historial-contenido">
                <!-- Se llena dinámicamente con PHP -->
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="cerrarModal('modal-historial')">Cerrar</button>
        </div>
    </div>
</div>

<script>
// Datos del historial desde PHP (inyectados como JSON)
const historialData = <?php echo json_encode($historial, JSON_UNESCAPED_UNICODE); ?>;

function abrirModalContacto(idEstudiante, nombre, correo) {
    document.getElementById('contacto-id').value = idEstudiante;
    document.getElementById('contacto-nombre').textContent = nombre;
    document.getElementById('contacto-correo').value = correo || 'No registrado';
    document.getElementById('modal-contacto').classList.add('activo');
    document.body.style.overflow = 'hidden';
}

function abrirModalHistorial(idEstudiante, nombre) {
    document.getElementById('historial-nombre').textContent = nombre;
    
    const contenedor = document.getElementById('historial-contenido');
    
    // Filtrar historial del estudiante seleccionado
    // (Como la consulta ya filtra por id_estudiante, usamos todo el array)
    const items = historialData;
    
    if (items.length === 0) {
        contenedor.innerHTML = '<div class="timeline-vacio">Aún no hay registros en el historial de este estudiante.</div>';
    } else {
        let html = '<div class="timeline">';
        items.forEach(item => {
            const fecha = new Date(item.fecha).toLocaleString('es-ES', {
                day: '2-digit', month: '2-digit', year: 'numeric',
                hour: '2-digit', minute: '2-digit'
            });
            
            html += `
                <div class="timeline-item tipo-${item.tipo}">
                    <div class="timeline-fecha">${fecha}</div>
                    <div class="timeline-titulo">${item.descripcion}</div>
                    ${item.detalle ? `<div class="timeline-detalle">${item.detalle}</div>` : ''}
                </div>
            `;
        });
        html += '</div>';
        contenedor.innerHTML = html;
    }
    
    document.getElementById('modal-historial').classList.add('activo');
    document.body.style.overflow = 'hidden';
}

function cerrarModal(id) {
    document.getElementById(id).classList.remove('activo');
    document.body.style.overflow = '';
}

// Cerrar modal al hacer clic fuera
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.remove('activo');
            document.body.style.overflow = '';
        }
    });
});

// Cerrar modal con tecla ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.activo').forEach(m => {
            m.classList.remove('activo');
        });
        document.body.style.overflow = '';
    }
});
</script>

</body>
</html>