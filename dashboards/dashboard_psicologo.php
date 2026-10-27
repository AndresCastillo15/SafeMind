<?php

session_start();
date_default_timezone_set('America/Bogota');

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'psicologo') {
    header("Location: ../login.php?rol=psicologo");
    exit();
}

$nombre = $_SESSION['nombre'] ?? 'Psicólogo';
$iniciales = mb_strtoupper(mb_substr($nombre, 0, 2));

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel del orientador - SafeMind</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,450;9..144,560;9..144,650&family=Karla:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --paper: #F7F4ED;
            --line: #E5DED0;
            --ink: #1E2B2F;
            --ink-soft: #68777B;
            --ink-faint: #9AA6A9;
            --deep: #17455A;
            --deep-dark: #0E2E3D;
            --sage: #4F7F62;
            --sage-soft: #DCE9DF;
            --gold: #C8922F;
            --orange: #C96D34;
            --orange-soft: #F5E1D2;
            --red: #B94A43;
            --red-soft: #F7E0DD;
            --blue-soft: #E2EDF1;
        }

        body {
            font-family: 'Karla', sans-serif;
            background: var(--paper);
            color: var(--ink);
            min-height: 100vh;
        }

        /* Barra lateral */
        .app { min-height: 100vh; display: flex; }

        .sidebar {
            width: 263px;
            background: #fff;
            border-right: 1px solid var(--line);
            padding: 28px 20px;
            display: flex;
            flex-direction: column;
            position: fixed;
            inset: 0 auto 0 0;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0 8px;
            margin-bottom: 30px;
        }
        .logo span { font-family: 'Fraunces', serif; font-size: 23px; color: var(--deep-dark); }

        .logo-mark,
        .avatar {
            background: var(--blue-soft);
            color: var(--deep);
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .logo-mark { width: 34px; height: 34px; border-radius: 10px; }
        .avatar { width: 40px; height: 40px; border-radius: 50%; }

        .perfil {
            background: var(--paper);
            border-radius: 12px;
            padding: 13px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .perfil-info strong { display: block; font-size: 14px; }
        .perfil-info span { font-size: 12px; color: var(--ink-soft); }

        .menu { display: flex; flex-direction: column; gap: 6px; }
        .menu a {
            text-decoration: none;
            color: var(--ink-soft);
            padding: 11px 13px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            transition: 0.2s ease;
        }
        .menu a:hover { background: var(--paper); color: var(--deep); }
        .menu a.activo { background: var(--blue-soft); color: var(--deep); font-weight: 600; }

        .orientacion {
            margin-top: auto;
            background: var(--sage-soft);
            border-radius: 12px;
            padding: 15px;
        }
        .orientacion strong { display: block; font-size: 14px; margin-bottom: 5px; }
        .orientacion p { font-size: 12px; line-height: 1.5; color: var(--ink-soft); margin-bottom: 10px; }
        .orientacion span { color: var(--sage); font-size: 12px; font-weight: 700; }

        .cerrar {
            margin-top: 17px;
            padding: 10px 13px;
            color: var(--ink-soft);
            text-decoration: none;
            font-size: 13px;
        }
        .cerrar:hover { color: var(--red); }

        /* Contenido principal */
        .main {
            margin-left: 263px;
            width: calc(100% - 263px);
            padding: 30px 38px 45px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 28px;
        }
        .titulo h1 { font-family: 'Fraunces', serif; font-size: 31px; color: var(--deep-dark); margin-bottom: 5px; }
        .titulo p { color: var(--ink-soft); font-size: 15px; }
        .fecha { text-align: right; font-size: 13px; color: var(--ink); }
        .fecha span { display: block; color: var(--ink-faint); margin-top: 3px; }

        /* Tarjetas de estadísticas */
        .estadisticas {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 22px;
        }
        .card {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 15px;
            padding: 18px;
            min-height: 140px;
        }
        .card-top { display: flex; align-items: center; gap: 10px; margin-bottom: 17px; }
        .card h3 { font-size: 14px; font-weight: 600; }
        .icono {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            background: var(--c);
        }
        .icono.rojo { --c: var(--red-soft); }
        .icono.naranja { --c: var(--orange-soft); }
        .icono.verde { --c: var(--sage-soft); }
        .icono.azul { --c: var(--blue-soft); }
        .numero { font-family: 'Fraunces', serif; font-size: 31px; color: var(--deep-dark); margin-bottom: 4px; }
        .descripcion { color: var(--ink-faint); font-size: 12px; }

        /* Paneles */
        .contenido-grid {
            display: grid;
            grid-template-columns: 1.35fr 1fr;
            gap: 18px;
            margin-bottom: 18px;
        }
        .panel {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 15px;
            padding: 22px;
        }
        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .panel-header h2 { font-family: 'Fraunces', serif; font-size: 20px; color: var(--deep-dark); }
        .panel-header span { color: var(--ink-faint); font-size: 12px; }

        /* Distribución de riesgo */
        .riesgo-lista { display: flex; flex-direction: column; gap: 15px; }
        .riesgo-item {
            display: grid;
            grid-template-columns: 100px 1fr 35px;
            align-items: center;
            gap: 12px;
        }
        .riesgo-nombre { font-size: 13px; color: var(--ink-soft); }
        .barra { height: 9px; background: #EEEAE2; border-radius: 20px; overflow: hidden; }
        .barra-progreso {
            height: 100%;
            width: 0%;
            border-radius: 20px;
            background: var(--c);
            transition: width 0.5s ease;
        }
        .barra-verde { --c: var(--sage); }
        .barra-amarilla { --c: var(--gold); }
        .barra-naranja { --c: var(--orange); }
        .barra-roja { --c: var(--red); }
        .riesgo-total { text-align: right; font-size: 13px; font-weight: 700; color: var(--deep-dark); }

        /* Estado del sistema */
        .estado-sistema { display: flex; flex-direction: column; gap: 14px; }
        .estado-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid var(--line);
        }
        .estado-item:last-child { border-bottom: none; }
        .estado-item span { font-size: 13px; color: var(--ink-soft); }
        .estado-ok { color: var(--sage); font-size: 12px; font-weight: 700; }

        /* Alertas */
        #alertas-container { display: flex; flex-direction: column; gap: 12px; }
        .alerta {
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            transition: 0.2s ease;
        }
        .alerta:hover { border-color: #D5CDC0; transform: translateY(-1px); }
        .alerta-info { display: flex; align-items: center; gap: 14px; min-width: 0; }
        .riesgo {
            width: 11px;
            height: 50px;
            border-radius: 10px;
            flex-shrink: 0;
            background: var(--c);
        }
        .riesgo.naranja { --c: #D87A3D; }
        .riesgo.rojo { --c: var(--red); }
        .alerta-texto { min-width: 0; }
        .alerta-texto h3 { font-size: 15px; margin-bottom: 4px; }
        .alerta-texto p { font-size: 12px; color: var(--ink-soft); margin-bottom: 4px; }
        .alerta-texto small { display: block; font-size: 11px; color: var(--ink-faint); line-height: 1.5; }
        .alerta-meta { text-align: right; flex-shrink: 0; }
        .badge {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 7px;
            background: var(--bg);
            color: var(--fg);
        }
        .badge.naranja { --bg: var(--orange-soft); --fg: #A75223; }
        .badge.rojo { --bg: var(--red-soft); --fg: #963B2A; }
        .ver-caso {
            border: none;
            background: transparent;
            color: var(--deep);
            cursor: pointer;
            font-family: 'Karla', sans-serif;
            font-size: 12px;
            font-weight: 700;
        }
        .ver-caso:hover { text-decoration: underline; }

        /* Estados de carga y error */
        .estado {
            min-height: 130px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: var(--ink-faint);
            font-size: 14px;
        }
        .estado.error { color: var(--red); }

        /* Responsive */
        @media (max-width: 1150px) {
            .estadisticas { grid-template-columns: repeat(2, 1fr); }
            .contenido-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 800px) {
            .sidebar { position: static; width: 100%; }
            .app { flex-direction: column; }
            .main { margin-left: 0; width: 100%; padding: 25px 20px; }
            .topbar { flex-direction: column; gap: 15px; }
            .fecha { text-align: left; }
        }

        @media (max-width: 600px) {
            .estadisticas { grid-template-columns: 1fr; }
            .alerta { flex-direction: column; align-items: flex-start; }
            .alerta-meta { text-align: left; }
            .riesgo-item { grid-template-columns: 85px 1fr 25px; }
        }
    </style>
</head>
<body>

<div class="app">

    <aside class="sidebar">

        <div class="logo">
            <div class="logo-mark">SM</div>
            <span>SafeMind</span>
        </div>

        <div class="perfil">
            <div class="avatar"><?php echo htmlspecialchars($iniciales); ?></div>
            <div class="perfil-info">
                <strong><?php echo htmlspecialchars($nombre); ?></strong>
                <span>Psicólogo / Orientador</span>
            </div>
        </div>

        <nav class="menu">
            <a href="#" class="activo">🏠 &nbsp; Inicio</a>
            <a href="#alertas">🔔 &nbsp; Alertas</a>
            <a href="estudiantes_psicologo.php">👥 &nbsp; Estudiantes</a>
            <a href="seguimientos_psicologo.php">📋 &nbsp; Seguimientos</a>
            <a href="recursos_psicologo.php">🌿 &nbsp; Recursos</a>
        </nav>

        <div class="orientacion">
            <strong>SafeMind</strong>
            <p>Panel profesional para el seguimiento y atención de situaciones detectadas.</p>
            <span>Panel protegido</span>
        </div>

        <a href="../logout.php" class="cerrar">↪ &nbsp; Cerrar sesión</a>

    </aside>

    <main class="main">

        <div class="topbar">
            <div class="titulo">
                <h1>Panel del orientador</h1>
                <p>Seguimiento y atención de situaciones detectadas</p>
            </div>
            <div class="fecha">
                <?php echo date('d/m/Y'); ?>
                <span>Información actualizada</span>
            </div>
        </div>

        <section class="estadisticas">
            <div class="card">
                <div class="card-top">
                    <div class="icono rojo">⚠</div>
                    <h3>Alertas pendientes</h3>
                </div>
                <div class="numero" id="total-pendientes">—</div>
                <div class="descripcion">Requieren revisión</div>
            </div>

            <div class="card">
                <div class="card-top">
                    <div class="icono naranja">●</div>
                    <h3>En seguimiento</h3>
                </div>
                <div class="numero" id="total-seguimiento">—</div>
                <div class="descripcion">Casos activos</div>
            </div>

            <div class="card">
                <div class="card-top">
                    <div class="icono verde">✓</div>
                    <h3>Casos cerrados</h3>
                </div>
                <div class="numero" id="total-cerradas">—</div>
                <div class="descripcion">Casos finalizados</div>
            </div>

            <div class="card">
                <div class="card-top">
                    <div class="icono azul">♙</div>
                    <h3>Estudiantes evaluados</h3>
                </div>
                <div class="numero" id="total-estudiantes">—</div>
                <div class="descripcion">Con evaluaciones registradas</div>
            </div>
        </section>

        <section class="contenido-grid">

            <div class="panel">
                <div class="panel-header">
                    <h2>Distribución por nivel de riesgo</h2>
                    <span id="total-evaluaciones">— evaluaciones</span>
                </div>

                <div class="riesgo-lista">
                    <div class="riesgo-item">
                        <div class="riesgo-nombre">Nivel 1 · Bien</div>
                        <div class="barra"><div id="barra-nivel-1" class="barra-progreso barra-verde"></div></div>
                        <div id="nivel-1" class="riesgo-total">0</div>
                    </div>

                    <div class="riesgo-item">
                        <div class="riesgo-nombre">Nivel 2 · Alerta</div>
                        <div class="barra"><div id="barra-nivel-2" class="barra-progreso barra-amarilla"></div></div>
                        <div id="nivel-2" class="riesgo-total">0</div>
                    </div>

                    <div class="riesgo-item">
                        <div class="riesgo-nombre">Nivel 3 · Riesgo</div>
                        <div class="barra"><div id="barra-nivel-3" class="barra-progreso barra-naranja"></div></div>
                        <div id="nivel-3" class="riesgo-total">0</div>
                    </div>

                    <div class="riesgo-item">
                        <div class="riesgo-nombre">Nivel 4 · Crisis</div>
                        <div class="barra"><div id="barra-nivel-4" class="barra-progreso barra-roja"></div></div>
                        <div id="nivel-4" class="riesgo-total">0</div>
                    </div>
                </div>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <h2>Resumen del sistema</h2>
                </div>

                <div class="estado-sistema">
                    <div class="estado-item">
                        <span>Sistema SafeMind</span>
                        <strong class="estado-ok">Activo</strong>
                    </div>
                    <div class="estado-item">
                        <span>Evaluaciones</span>
                        <strong class="estado-ok" id="estado-evaluaciones">—</strong>
                    </div>
                    <div class="estado-item">
                        <span>Seguimientos activos</span>
                        <strong class="estado-ok" id="estado-seguimientos">—</strong>
                    </div>
                    <div class="estado-item">
                        <span>Casos cerrados</span>
                        <strong class="estado-ok" id="estado-cerrados">—</strong>
                    </div>
                </div>
            </div>

        </section>

        <section id="alertas" class="panel">
            <div class="panel-header">
                <h2>Alertas pendientes</h2>
                <span id="ultima-actualizacion">Cargando...</span>
            </div>
            <div id="alertas-container">
                <div class="estado">Cargando alertas...</div>
            </div>
        </section>

    </main>

</div>

<script>
const API_ESTADISTICAS = '../API/seguimiento/estadisticas_psicologo.php';
const API_ALERTAS = '../API/seguimiento/obtener_alertas.php';

const $ = id => document.getElementById(id);
const alertasContainer = $('alertas-container');
const ultimaActualizacion = $('ultima-actualizacion');

// Pide una API y lanza error si la respuesta no es válida
async function pedir(url) {
    const respuesta = await fetch(url, { cache: 'no-store', credentials: 'same-origin' });
    if (!respuesta.ok) throw new Error(`HTTP ${respuesta.status} en ${url}`);

    const datos = await respuesta.json();
    if (!datos.success) throw new Error(datos.mensaje || 'La API devolvió un error.');

    return datos;
}

// Evita inyectar HTML desde los datos de la API
function escapar(texto) {
    const div = document.createElement('div');
    div.textContent = texto ?? '';
    return div.innerHTML;
}

// '2026-10-27 06:51:00' -> '27/10/2026 06:51:00'
function formatearFecha(fecha) {
    if (!fecha) return '';

    const [dia, hora] = String(fecha).split(' ');
    const partes = dia?.split('-') ?? [];

    return hora && partes.length === 3
        ? `${partes[2]}/${partes[1]}/${partes[0]} ${hora}`
        : String(fecha);
}

async function cargarEstadisticas() {
    try {
        const { estadisticas: e } = await pedir(API_ESTADISTICAS);
        const { alertas: a, estudiantes, evaluaciones: ev, riesgo: r } = e;

        $('total-pendientes').textContent = a.pendientes;
        $('total-seguimiento').textContent = a.en_seguimiento;
        $('total-cerradas').textContent = a.cerradas;
        $('total-estudiantes').textContent = estudiantes.evaluados;

        $('total-evaluaciones').textContent = `${ev.total} evaluaciones`;
        $('estado-evaluaciones').textContent = ev.total;
        $('estado-seguimientos').textContent = a.en_seguimiento;
        $('estado-cerrados').textContent = a.cerradas;

        // Evita dividir entre cero cuando no hay evaluaciones
        const total = ev.total > 0 ? ev.total : 1;

        [1, 2, 3, 4].forEach(nivel => {
            const cantidad = r[`nivel_${nivel}`];
            $(`nivel-${nivel}`).textContent = cantidad;
            $(`barra-nivel-${nivel}`).style.width = `${(cantidad / total) * 100}%`;
        });

    } catch (error) {
        console.error('Error de estadísticas:', error);

        ['total-pendientes', 'total-seguimiento', 'total-cerradas', 'total-estudiantes']
            .forEach(id => $(id).textContent = '—');
    }
}

// Una tarjeta de alerta, con todo el texto escapado
function tarjeta(alerta) {
    const clase = Number(alerta.riesgo?.id_nivel) === 4 ? 'rojo' : 'naranja';
    const nombre = [alerta.estudiante?.nombre, alerta.estudiante?.apellido].filter(Boolean).join(' ');
    const curso = alerta.estudiante?.curso || 'Sin curso';
    const emocion = alerta.analisis?.emocion || 'Sin emoción registrada';
    const resumen = alerta.analisis?.resumen || 'Sin resumen disponible';
    const nivelNombre = alerta.riesgo?.nombre || 'Riesgo';

    return `
        <article class="alerta">
            <div class="alerta-info">
                <div class="riesgo ${clase}"></div>
                <div class="alerta-texto">
                    <h3>${escapar(nombre)}</h3>
                    <p>Curso: ${escapar(curso)} · ${escapar(emocion)}</p>
                    <small>${escapar(resumen)}</small>
                </div>
            </div>
            <div class="alerta-meta">
                <div class="badge ${clase}">${escapar(nivelNombre)}</div>
                <div><small>${escapar(formatearFecha(alerta.fecha))}</small></div>
                <div>
                    <button type="button" class="ver-caso" onclick="verCaso(${Number(alerta.id_alerta)})">Ver caso →</button>
                </div>
            </div>
        </article>`;
}

async function cargarAlertas() {
    try {
        alertasContainer.innerHTML = '<div class="estado">Cargando alertas...</div>';

        const datos = await pedir(API_ALERTAS);
        const alertas = Array.isArray(datos.alertas) ? datos.alertas : [];

        ultimaActualizacion.textContent =
            `Actualizado ${new Date().toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit' })}`;

        alertasContainer.innerHTML = alertas.length
            ? alertas.map(tarjeta).join('')
            : '<div class="estado">No hay alertas pendientes en este momento.</div>';

    } catch (error) {
        console.error('Error de alertas:', error);
        alertasContainer.innerHTML = '<div class="estado error">No se pudieron cargar las alertas.</div>';
    }
}

function verCaso(id_alerta) {
    window.location.href = `detalle_alerta.php?id_alerta=${id_alerta}`;
}

Promise.all([cargarEstadisticas(), cargarAlertas()]);
</script>

</body>
</html>