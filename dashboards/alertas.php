<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'profesor') {
    header('Location: ../login.php?rol=profesor');
    exit();
}

require_once '../API/config/conexion.php';
$conexion = conectar();
$nombre_usuario = $_SESSION['nombre'] ?? 'Profesor';

function iniciales_es($nombre) {
    $partes = preg_split('/\s+/', trim($nombre));
    return strtoupper(substr($partes[0], 0, 1) . (isset($partes[1]) ? substr($partes[1], 0, 1) : ''));
}

// Consulta completa: alerta + análisis + nivel + estudiante + evaluación
$sql = "SELECT 
            al.id_alerta, 
            al.estado AS alerta_estado, 
            al.fecha AS fecha_alerta,
            ai.id_analisis,
            ai.emocion_detectada, 
            ai.porcentaje_confianza, 
            ai.resumen AS resumen_ia,
            ai.fecha AS fecha_analisis,
            nr.nombre AS nivel_nombre,
            nr.descripcion AS nivel_descripcion,
            nr.color AS nivel_color,
            e.id_estudiante,
            e.nombre, 
            e.apellido, 
            e.correo,
            e.telefono,
            e.curso,
            ev.fecha_inicio AS fecha_evaluacion,
            ev.estado AS estado_evaluacion
        FROM alerta al
        JOIN analisis_ia ai ON ai.id_analisis = al.id_analisis
        JOIN evaluacion ev ON ev.id_evaluacion = ai.id_evaluacion
        JOIN estudiante e ON e.id_estudiante = ev.id_estudiante
        LEFT JOIN nivel_riesgo nr ON nr.id_nivel = ai.id_nivel
        ORDER BY al.fecha DESC";

$alertas = mysqli_fetch_all(mysqli_query($conexion, $sql), MYSQLI_ASSOC);

// Contar alertas por estado
$pendientes = 0;
$resueltas = 0;
foreach ($alertas as $a) {
    if (stripos($a['alerta_estado'], 'pendient') !== false || stripos($a['alerta_estado'], 'activ') !== false) {
        $pendientes++;
    } else {
        $resueltas++;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alertas - SafeMind</title>
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
        .nav-badge { margin-left: auto; background: var(--red); color: #fff; font-size: 11px; padding: 1px 7px; border-radius: 20px; }
        .sidebar-pie { margin-top: auto; }
        .apoyo-card { background: var(--sage-soft); border-radius: var(--radius-sm); padding: 14px; margin-bottom: 14px; }
        .apoyo-card-titulo { font-size: 13px; font-weight: 700; color: var(--deep-dark); margin-bottom: 6px; }
        .apoyo-card p { font-size: 12.5px; color: var(--ink-soft); margin-bottom: 8px; line-height: 1.5; }
        .apoyo-card a { font-size: 12.5px; font-weight: 700; color: var(--sage); }
        .salir { display: flex; align-items: center; gap: 10px; font-size: 13.5px; color: var(--ink-faint); padding: 4px 12px; }
        .salir:hover { color: var(--red); }
        
        /* Contenido */
        .contenido { flex: 1; padding: 30px 38px; }
        .dash-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 26px; flex-wrap: wrap; gap: 14px; }
        .dash-header h1 { font-size: 27px; color: var(--deep-dark); margin-bottom: 4px; }
        .dash-header p { color: var(--ink-soft); font-size: 14.5px; }
        
        /* Stats rápidas */
        .stats-rapidas { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px; }
        .stat-mini { background: var(--paper-card); border: 1px solid var(--paper-line); border-radius: var(--radius); padding: 18px; }
        .stat-mini-numero { font-family: 'Fraunces'; font-size: 28px; font-weight: 620; color: var(--deep-dark); }
        .stat-mini-label { font-size: 13px; color: var(--ink-soft); margin-top: 4px; }
        
        /* Tarjeta */
        .tarjeta { background: var(--paper-card); border: 1px solid var(--paper-line); border-radius: var(--radius); padding: 22px; }
        .tarjeta-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
        .tarjeta-header h2 { font-size: 18px; color: var(--deep-dark); }
        
        /* Tabla */
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px; border-bottom: 2px solid var(--paper-line); font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.03em; color: var(--ink-faint); font-weight: 700; }
        td { padding: 14px 12px; border-bottom: 1px solid var(--paper-line); vertical-align: middle; }
        tr:hover { background: var(--paper); }
        
        /* Pills */
        .pill { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; }
        .pill.rojo { background: var(--red-soft); color: var(--red); }
        .pill.gold { background: var(--gold-soft); color: var(--gold); }
        .pill.sage { background: var(--sage-soft); color: var(--sage); }
        .pill.gris { background: var(--gris-soft); color: var(--gris); }
        
        /* Botones */
        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: var(--radius-sm); font-weight: 600; cursor: pointer; border: none; font-size: 13px; transition: all 0.15s; }
        .btn-primary { background: var(--deep); color: #fff; }
        .btn-primary:hover { background: var(--deep-dark); }
        .btn-secondary { background: #fff; border: 1px solid var(--paper-line); color: var(--ink); }
        .btn-secondary:hover { border-color: var(--deep); }
        .btn-small { padding: 6px 12px; font-size: 12px; }
        
        .vacio { text-align: center; padding: 40px; color: var(--ink-faint); }
        
        /* MODAL */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(14, 46, 61, 0.6);
            backdrop-filter: blur(4px);
            z-index: 999;
            align-items: center;
            justify-content: center;
            padding: 20px;
            animation: fadeIn 0.2s ease;
        }
        .modal-overlay.activo {
            display: flex;
        }
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
        .modal-header h3 {
            font-size: 20px;
            color: var(--deep-dark);
            margin-bottom: 4px;
        }
        .modal-header .subtitulo {
            font-size: 13px;
            color: var(--ink-soft);
        }
        .modal-cerrar {
            background: var(--paper);
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: var(--ink-soft);
            flex-shrink: 0;
        }
        .modal-cerrar:hover {
            background: var(--red-soft);
            color: var(--red);
        }
        .modal-body {
            padding: 24px;
        }
        .modal-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 20px;
        }
        .modal-info-item {
            background: var(--paper);
            border-radius: var(--radius-sm);
            padding: 12px;
        }
        .modal-info-item label {
            display: block;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--ink-faint);
            font-weight: 700;
            margin-bottom: 4px;
        }
        .modal-info-item .valor {
            font-size: 14px;
            font-weight: 600;
            color: var(--ink);
        }
        .modal-info-item.full {
            grid-column: 1 / -1;
        }
        .modal-resumen {
            background: var(--deep-soft);
            border-left: 3px solid var(--deep);
            padding: 14px 16px;
            border-radius: 0 var(--radius-sm) var(--radius-sm) 0;
            margin-bottom: 20px;
        }
        .modal-resumen label {
            display: block;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--deep);
            font-weight: 700;
            margin-bottom: 6px;
        }
        .modal-resumen p {
            font-size: 14px;
            line-height: 1.6;
            color: var(--ink);
        }
        .modal-footer {
            padding: 16px 24px;
            border-top: 1px solid var(--paper-line);
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @media (max-width: 768px) {
            .app { flex-direction: column; }
            .sidebar { width: 100%; }
            .stats-rapidas { grid-template-columns: 1fr; }
            .modal-info-grid { grid-template-columns: 1fr; }
            table { display: block; overflow-x: auto; }
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
            <div>
                <div class="perfil-nombre"><?php echo htmlspecialchars($nombre_usuario); ?></div>
                <div style="font-size:12.5px;color:var(--ink-soft);">Profesor</div>
            </div>
        </div>
        <nav class="nav">
            <a href="dashboard_docente.php" class="nav-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 11l8-7 8 7"/><path d="M6 10v9h12v-9"/></svg>
                Inicio
            </a>
            <a href="alertas.php" class="nav-item activo">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 4a5 5 0 00-5 5v3l-2 4h14l-2-4V9a5 5 0 00-5-5z"/><path d="M10 20a2 2 0 004 0"/></svg>
                Alertas
                <?php if ($pendientes > 0): ?><span class="nav-badge"><?php echo $pendientes; ?></span><?php endif; ?>
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
                <h1>Alertas</h1>
                <p>Gestión y seguimiento de alertas de estudiantes</p>
            </div>
            <a href="dashboard_docente.php" class="btn btn-primary">← Volver al inicio</a>
        </header>

        <!-- Stats rápidas -->
        <section class="stats-rapidas">
            <div class="stat-mini">
                <div class="stat-mini-numero"><?php echo count($alertas); ?></div>
                <div class="stat-mini-label">Total de alertas</div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-numero" style="color:var(--red);"><?php echo $pendientes; ?></div>
                <div class="stat-mini-label">Pendientes de atención</div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-numero" style="color:var(--sage);"><?php echo $resueltas; ?></div>
                <div class="stat-mini-label">Resueltas</div>
            </div>
        </section>

        <div class="tarjeta">
            <div class="tarjeta-header">
                <h2>Todas las alertas</h2>
            </div>
            <?php if (empty($alertas)): ?>
                <p class="vacio">No hay alertas registradas</p>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Estudiante</th>
                        <th>Curso</th>
                        <th>Nivel</th>
                        <th>Emoción</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($alertas as $a): 
                        // Determinar color del nivel
                        $nivel_lower = strtolower($a['nivel_nombre'] ?? '');
                        if (strpos($nivel_lower, 'crisis') !== false) $nivel_color = 'rojo';
                        elseif (strpos($nivel_lower, 'riesgo') !== false) $nivel_color = 'gold';
                        elseif (strpos($nivel_lower, 'alerta') !== false) $nivel_color = 'gold';
                        else $nivel_color = 'sage';
                        
                        // Determinar color del estado
                        $estado_lower = strtolower($a['alerta_estado'] ?? '');
                        if (strpos($estado_lower, 'pendient') !== false || strpos($estado_lower, 'activ') !== false) $estado_color = 'rojo';
                        elseif (strpos($estado_lower, 'resuel') !== false) $estado_color = 'sage';
                        else $estado_color = 'gold';
                    ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($a['nombre'] . ' ' . $a['apellido']); ?></strong></td>
                        <td><?php echo htmlspecialchars($a['curso'] ?? '—'); ?></td>
                        <td><span class="pill <?php echo $nivel_color; ?>"><?php echo htmlspecialchars($a['nivel_nombre'] ?? 'Sin nivel'); ?></span></td>
                        <td><?php echo htmlspecialchars($a['emocion_detectada'] ?? '—'); ?></td>
                        <td><span class="pill <?php echo $estado_color; ?>"><?php echo htmlspecialchars($a['alerta_estado']); ?></span></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($a['fecha_alerta'])); ?></td>
                        <td>
                            <button class="btn btn-primary btn-small" 
                                    onclick="abrirModal(
                                        <?php echo (int)$a['id_estudiante']; ?>,
                                        '<?php echo addslashes($a['nombre'] . ' ' . $a['apellido']); ?>',
                                        '<?php echo addslashes($a['curso'] ?? 'No asignado'); ?>',
                                        '<?php echo addslashes($a['correo'] ?? ''); ?>',
                                        '<?php echo addslashes($a['telefono'] ?? ''); ?>',
                                        '<?php echo addslashes($a['nivel_nombre'] ?? 'Sin nivel'); ?>',
                                        '<?php echo addslashes($a['nivel_descripcion'] ?? ''); ?>',
                                        '<?php echo addslashes($a['emocion_detectada'] ?? 'No detectada'); ?>',
                                        <?php echo (float)($a['porcentaje_confianza'] ?? 0); ?>,
                                        '<?php echo addslashes($a['resumen_ia'] ?? 'Sin resumen disponible'); ?>',
                                        '<?php echo addslashes($a['alerta_estado']); ?>',
                                        '<?php echo date('d/m/Y H:i', strtotime($a['fecha_alerta'])); ?>',
                                        '<?php echo date('d/m/Y H:i', strtotime($a['fecha_evaluacion'] ?? $a['fecha_alerta'])); ?>'
                                    )">
                                Ver detalle
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- MODAL DE DETALLE -->
<div class="modal-overlay" id="modal-detalle">
    <div class="modal">
        <div class="modal-header">
            <div>
                <h3 id="modal-nombre">Nombre del estudiante</h3>
                <div class="subtitulo" id="modal-curso">Curso</div>
            </div>
            <button class="modal-cerrar" onclick="cerrarModal()" aria-label="Cerrar">✕</button>
        </div>
        <div class="modal-body">
            <!-- Información del estudiante -->
            <div class="modal-info-grid">
                <div class="modal-info-item">
                    <label>Correo</label>
                    <div class="valor" id="modal-correo">—</div>
                </div>
                <div class="modal-info-item">
                    <label>Teléfono</label>
                    <div class="valor" id="modal-telefono">—</div>
                </div>
                <div class="modal-info-item">
                    <label>Nivel de riesgo</label>
                    <div class="valor"><span id="modal-nivel-pill" class="pill">—</span></div>
                </div>
                <div class="modal-info-item">
                    <label>Emoción detectada</label>
                    <div class="valor" id="modal-emocion">—</div>
                </div>
                <div class="modal-info-item">
                    <label>Confianza IA</label>
                    <div class="valor" id="modal-confianza">—</div>
                </div>
                <div class="modal-info-item">
                    <label>Estado alerta</label>
                    <div class="valor" id="modal-estado">—</div>
                </div>
                <div class="modal-info-item">
                    <label>Fecha evaluación</label>
                    <div class="valor" id="modal-fecha-eval">—</div>
                </div>
                <div class="modal-info-item">
                    <label>Fecha alerta</label>
                    <div class="valor" id="modal-fecha-alerta">—</div>
                </div>
            </div>

            <!-- Resumen del análisis IA -->
            <div class="modal-resumen">
                <label>Análisis de la IA</label>
                <p id="modal-resumen">Sin resumen disponible.</p>
            </div>

            <!-- Descripción del nivel -->
            <div class="modal-info-item full" style="background:var(--gold-soft);">
                <label style="color:var(--gold);">Descripción del nivel</label>
                <div class="valor" id="modal-nivel-desc">—</div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="cerrarModal()">Cerrar</button>
            <a href="#" id="modal-link-estudiante" class="btn btn-primary">Ver perfil completo →</a>
        </div>
    </div>
</div>

<script>
function abrirModal(idEstudiante, nombre, curso, correo, telefono, nivel, nivelDesc, emocion, confianza, resumen, estado, fechaAlerta, fechaEval) {
    // Llenar datos del modal
    document.getElementById('modal-nombre').textContent = nombre;
    document.getElementById('modal-curso').textContent = 'Curso ' + curso;
    document.getElementById('modal-correo').textContent = correo || 'No registrado';
    document.getElementById('modal-telefono').textContent = telefono || 'No registrado';
    document.getElementById('modal-emocion').textContent = emocion;
    document.getElementById('modal-confianza').textContent = confianza + '%';
    document.getElementById('modal-estado').textContent = estado;
    document.getElementById('modal-fecha-alerta').textContent = fechaAlerta;
    document.getElementById('modal-fecha-eval').textContent = fechaEval;
    document.getElementById('modal-resumen').textContent = resumen;
    document.getElementById('modal-nivel-desc').textContent = nivelDesc || 'Sin descripción';
    
    // Actualizar link del botón "Ver perfil completo"
    document.getElementById('modal-link-estudiante').href = 'estudiantes.php?id=' + idEstudiante;
    
    // Actualizar pill del nivel con color dinámico
    const nivelPill = document.getElementById('modal-nivel-pill');
    nivelPill.textContent = nivel;
    const nivelLower = nivel.toLowerCase();
    nivelPill.className = 'pill';
    if (nivelLower.includes('crisis')) nivelPill.classList.add('rojo');
    else if (nivelLower.includes('riesgo') || nivelLower.includes('alerta')) nivelPill.classList.add('gold');
    else nivelPill.classList.add('sage');
    
    // Mostrar modal
    document.getElementById('modal-detalle').classList.add('activo');
    document.body.style.overflow = 'hidden'; // Evitar scroll del body
}

function cerrarModal() {
    document.getElementById('modal-detalle').classList.remove('activo');
    document.body.style.overflow = ''; // Restaurar scroll
}

// Cerrar modal al hacer clic fuera
document.getElementById('modal-detalle').addEventListener('click', function(e) {
    if (e.target === this) {
        cerrarModal();
    }
});

// Cerrar modal con tecla ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        cerrarModal();
    }
});
</script>

</body>
</html>