<?php

session_start();
date_default_timezone_set('America/Bogota');

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'psicologo') {
    header("Location: ../login.php?rol=psicologo");
    exit();
}

$id_alerta = intval($_GET['id_alerta'] ?? 0);

if ($id_alerta <= 0) {
    header("Location: dashboard_psicologo.php");
    exit();
}

// Token CSRF: se crea una sola vez por sesión y se envía en cada POST
if (
    !isset($_SESSION['csrf_token']) ||
    !is_string($_SESSION['csrf_token']) ||
    !preg_match('/\A[a-f0-9]{64}\z/', $_SESSION['csrf_token'])
) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$nombre_psicologo = $_SESSION['nombre'] ?? 'Psicólogo';
$iniciales = mb_strtoupper(mb_substr($nombre_psicologo, 0, 2));

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle del caso - SafeMind</title>
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

        .app { min-height: 100vh; display: flex; }

        /* Barra lateral */
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

        .logo { display: flex; align-items: center; gap: 10px; padding: 0 8px; margin-bottom: 30px; }
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

        .cerrar { margin-top: 17px; padding: 10px 13px; color: var(--ink-soft); text-decoration: none; font-size: 13px; }
        .cerrar:hover { color: var(--red); }

        /* Contenido principal */
        .main {
            margin-left: 263px;
            width: calc(100% - 263px);
            padding: 30px 38px 40px;
        }

        .volver {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--deep);
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 18px;
        }
        .volver:hover { text-decoration: underline; }

        .encabezado {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 24px;
        }
        .encabezado h1 { font-family: 'Fraunces', serif; font-size: 31px; color: var(--deep-dark); margin-bottom: 5px; }
        .encabezado p { color: var(--ink-soft); font-size: 15px; }

        /* Píldoras de estado */
        .estado-alerta,
        .badge-riesgo,
        .recurso-tipo,
        .seguimiento-estado {
            display: inline-block;
            border-radius: 20px;
            font-weight: 700;
            background: var(--bg, var(--sage-soft));
            color: var(--fg, var(--sage));
        }
        .estado-alerta { padding: 7px 12px; font-size: 12px; --bg: var(--orange-soft); --fg: #A75223; }
        .estado-alerta.cerrada,
        .seguimiento-estado.cerrado { --bg: var(--blue-soft); --fg: var(--deep); }
        .badge-riesgo { padding: 6px 10px; font-size: 12px; --bg: var(--orange-soft); --fg: #A75223; }
        .recurso-tipo { padding: 5px 9px; font-size: 11px; }
        .seguimiento-estado { padding: 5px 9px; font-size: 11px; }

        /* Tarjetas de información */
        .grid-info {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 16px;
            margin-bottom: 18px;
        }

        .bloque,
        .info-card {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 15px;
            padding: 22px;
        }
        .info-card { padding: 20px; }
        .bloque { margin-bottom: 18px; }

        .info-card h3,
        .bloque h2 {
            font-family: 'Fraunces', serif;
            color: var(--deep-dark);
            margin-bottom: 14px;
        }
        .info-card h3 { font-size: 17px; }
        .bloque h2 { font-size: 20px; margin-bottom: 16px; }

        .dato { margin-bottom: 9px; font-size: 13px; }
        .dato strong { color: var(--ink); }
        .dato span { color: var(--ink-soft); }

        .contacto-dato { display: flex; align-items: center; gap: 7px; flex-wrap: wrap; }
        .contacto-dato a { color: var(--deep); text-decoration: none; word-break: break-word; }
        .contacto-dato a:hover { text-decoration: underline; }

        .resumen {
            color: var(--ink-soft);
            font-size: 14px;
            line-height: 1.7;
            background: var(--paper);
            padding: 16px;
            border-radius: 10px;
        }

        /* Recurso recomendado */
        .recurso-contenedor { display: flex; flex-direction: column; gap: 12px; }

        .recurso-card {
            background: var(--paper);
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 17px;
        }
        .recurso-icono {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: var(--sage-soft);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
            font-size: 18px;
        }
        .recurso-titulo { font-family: 'Fraunces', serif; font-size: 17px; color: var(--deep-dark); margin-bottom: 7px; }
        .recurso-descripcion { color: var(--ink-soft); font-size: 13px; line-height: 1.6; margin-bottom: 10px; }

        .recurso-vacio {
            color: var(--ink-faint);
            font-size: 13px;
            background: var(--paper);
            border-radius: 10px;
            padding: 15px;
        }
        .recurso-cargando { color: var(--ink-faint); font-size: 13px; }
        .recurso-error { color: var(--red); font-size: 13px; }

        /* Conversación */
        .conversacion {
            display: flex;
            flex-direction: column;
            gap: 12px;
            max-height: 480px;
            overflow-y: auto;
            padding-right: 5px;
        }
        .mensaje {
            max-width: 78%;
            padding: 12px 14px;
            border-radius: 12px;
            font-size: 13px;
            line-height: 1.5;
        }
        .mensaje.estudiante {
            align-self: flex-start;
            background: var(--paper);
            color: var(--ink);
            border-bottom-left-radius: 4px;
        }
        .mensaje.ia {
            align-self: flex-end;
            background: var(--blue-soft);
            color: var(--deep-dark);
            border-bottom-right-radius: 4px;
        }
        .mensaje-remitente { font-size: 11px; font-weight: 700; margin-bottom: 5px; color: var(--ink-faint); }
        .mensaje-fecha { margin-top: 6px; font-size: 10px; color: var(--ink-faint); }

        /* Seguimiento */
        .texto-ayuda { color: var(--ink-soft); font-size: 13px; line-height: 1.5; margin-bottom: 14px; }

        .campo-observacion {
            width: 100%;
            min-height: 130px;
            resize: vertical;
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 14px;
            font-family: 'Karla', sans-serif;
            font-size: 14px;
            line-height: 1.5;
            color: var(--ink);
            background: #fff;
        }
        .campo-observacion::placeholder { color: var(--ink-faint); }
        .campo-observacion:focus {
            outline: none;
            border-color: var(--deep);
            box-shadow: 0 0 0 3px rgba(23, 69, 90, 0.10);
        }
        .campo-observacion:disabled { background: #F0EEE9; cursor: not-allowed; }

        .acciones-seguimiento {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 14px;
            flex-wrap: wrap;
        }

        .boton {
            font-family: 'Karla', sans-serif;
            font-size: 13px;
            font-weight: 700;
            border-radius: 10px;
            padding: 11px 18px;
            cursor: pointer;
        }
        .boton:disabled { opacity: 0.65; cursor: default; }

        #btn-seguimiento { border: none; background: var(--deep); color: #fff; }
        #btn-seguimiento:hover:not(:disabled) { background: var(--deep-dark); }

        #btn-cerrar-caso { border: 1px solid var(--red); background: transparent; color: var(--red); padding: 10px 18px; }
        #btn-cerrar-caso:hover:not(:disabled) { background: var(--red-soft); }

        .mensaje-seguimiento,
        .mensaje-cierre { font-size: 13px; }
        .exito { color: var(--sage); }
        .error { color: var(--red); }

        /* Historial */
        .historial-seguimientos { display: flex; flex-direction: column; gap: 12px; }
        .seguimiento-item {
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 16px;
            background: #fff;
        }
        .seguimiento-cabecera {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 15px;
            margin-bottom: 10px;
        }
        .seguimiento-psicologo { font-size: 13px; font-weight: 700; color: var(--deep-dark); }
        .seguimiento-fecha { font-size: 11px; color: var(--ink-faint); }
        .seguimiento-observacion {
            background: var(--paper);
            border-radius: 9px;
            padding: 12px;
            color: var(--ink-soft);
            font-size: 13px;
            line-height: 1.6;
            margin-bottom: 10px;
        }

        .historial-vacio,
        .cargando {
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: var(--ink-faint);
            font-size: 13px;
        }
        .historial-vacio { min-height: 100px; }
        .cargando { min-height: 300px; font-size: 14px; }

        .error-caso {
            min-height: 250px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: var(--red);
            font-size: 14px;
        }

        /* Responsive */
        @media (max-width: 1050px) {
            .grid-info { grid-template-columns: 1fr 1fr; }
        }

        @media (max-width: 800px) {
            .sidebar { position: static; width: 100%; }
            .app { flex-direction: column; }
            .main { margin-left: 0; width: 100%; padding: 25px 20px; }
            .encabezado { flex-direction: column; }
        }

        @media (max-width: 600px) {
            .grid-info { grid-template-columns: 1fr; }
            .mensaje { max-width: 90%; }
            .seguimiento-cabecera { flex-direction: column; }
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
                <strong><?php echo htmlspecialchars($nombre_psicologo); ?></strong>
                <span>Psicólogo / Orientador</span>
            </div>
        </div>

        <nav class="menu">
            <a href="dashboard_psicologo.php">🏠 &nbsp; Inicio</a>
            <a href="dashboard_psicologo.php#alertas">🔔 &nbsp; Alertas</a>
            <a href="estudiantes_psicologo.php">👥 &nbsp; Estudiantes</a>
            <a href="seguimientos_psicologo.php">📋 &nbsp; Seguimientos</a>
            <a href="recursos_psicologo.php">🌿 &nbsp; Recursos</a>
        </nav>

        <div class="orientacion">
            <strong>SafeMind</strong>
            <p>Información privada destinada al seguimiento profesional.</p>
            <span>Panel protegido</span>
        </div>

        <a href="../logout.php" class="cerrar">↪ &nbsp; Cerrar sesión</a>

    </aside>

    <main class="main">

        <a href="dashboard_psicologo.php" class="volver">← Volver a alertas</a>

        <div id="contenido-caso">
            <div class="cargando">Cargando información del caso...</div>
        </div>

    </main>

</div>

<script>
const ID_ALERTA = <?php echo $id_alerta; ?>;

// JSON_HEX_* evita que el token rompa el <script>
const CSRF_TOKEN = <?php echo json_encode($_SESSION['csrf_token'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

const API = {
    caso: `../API/seguimiento/obtener_alerta.php?id_alerta=${ID_ALERTA}`,
    historial: `../API/seguimiento/obtener_seguimientos.php?id_alerta=${ID_ALERTA}`,
    recursos: '../API/seguimiento/obtener_recursos_analisis.php',
    seguimiento: '../API/seguimiento/registrar_seguimiento.php',
    cerrar: '../API/seguimiento/cerrar_caso.php'
};

const $ = id => document.getElementById(id);
const contenido = $('contenido-caso');

// Pide datos a la API y lanza error si la respuesta no es válida
async function pedir(url) {
    const respuesta = await fetch(url, { cache: 'no-store', credentials: 'same-origin' });
    if (!respuesta.ok) throw new Error(`HTTP ${respuesta.status} en ${url}`);

    const datos = await respuesta.json();
    if (!datos.success) throw new Error(datos.mensaje || 'La API devolvió un error.');

    return datos;
}

// Envía un formulario POST con el token CSRF
async function enviar(url, campos, mensajeError) {
    const datos = new FormData();
    for (const [clave, valor] of Object.entries(campos)) datos.append(clave, valor);
    datos.append('csrf_token', CSRF_TOKEN);

    const respuesta = await fetch(url, { method: 'POST', body: datos, credentials: 'same-origin' });
    const resultado = await respuesta.json();

    if (!resultado.success) throw new Error(resultado.mensaje || mensajeError);

    return resultado;
}

// Evita inyectar HTML desde los datos de la API
function escapar(texto) {
    const div = document.createElement('div');
    div.textContent = texto ?? '';
    return div.innerHTML;
}

// '2026-10-27 06:51:00' -> '27/10/2026 06:51:00'
function formatearFecha(fecha) {
    if (!fecha) return 'Sin fecha';

    const [dia, hora] = String(fecha).split(' ');
    const partes = dia.split('-');

    return hora && partes.length === 3
        ? `${partes[2]}/${partes[1]}/${partes[0]} ${hora}`
        : String(fecha);
}

// Mensaje de la conversación entre el estudiante y SafeMind
function mensajeHTML(mensaje) {
    const esEstudiante = mensaje.remitente === 'estudiante';

    return `
        <div class="mensaje ${esEstudiante ? 'estudiante' : 'ia'}">
            <div class="mensaje-remitente">${esEstudiante ? 'Estudiante' : 'SafeMind'}</div>
            <div>${escapar(mensaje.contenido)}</div>
            <div class="mensaje-fecha">${escapar(formatearFecha(mensaje.fecha))}</div>
        </div>`;
}

// Teléfono y correo: el texto va escapado y el enlace va codificado
function contactoHTML(etiqueta, valor, esquema, sinValor) {
    const contenidoDato = valor
        ? `<a href="${esquema}${encodeURIComponent(valor)}">${escapar(valor)}</a>`
        : `<span>${sinValor}</span>`;

    return `<div class="dato contacto-dato"><strong>${etiqueta}</strong>${contenidoDato}</div>`;
}

function renderCaso(datos) {
    const alerta = datos.alerta || {};
    const estudiante = datos.estudiante || {};
    const evaluacion = datos.evaluacion || {};
    const riesgo = datos.riesgo || {};
    const analisis = datos.analisis || {};
    const mensajes = Array.isArray(datos.mensajes) ? datos.mensajes : [];

    const cerrada = String(alerta.estado).toLowerCase() === 'cerrada';
    const textoEstado = alerta.estado || 'Pendiente';
    const deshabilitado = cerrada ? 'disabled' : '';

    const conversacion = mensajes.length
        ? mensajes.map(mensajeHTML).join('')
        : '<div class="historial-vacio">No hay mensajes registrados.</div>';

    contenido.innerHTML = `
        <div class="encabezado">
            <div>
                <h1>Caso de seguimiento</h1>
                <p>${escapar(estudiante.nombre)} ${escapar(estudiante.apellido)} · Curso ${escapar(estudiante.curso)}</p>
            </div>
            <div class="estado-alerta ${cerrada ? 'cerrada' : ''}" id="estado-alerta">${escapar(textoEstado)}</div>
        </div>

        <section class="grid-info">
            <div class="info-card">
                <h3>Estudiante</h3>
                <div class="dato"><strong>Nombre:</strong> <span>${escapar(estudiante.nombre)} ${escapar(estudiante.apellido)}</span></div>
                <div class="dato"><strong>Curso:</strong> <span>${escapar(estudiante.curso || 'Sin curso')}</span></div>
                ${contactoHTML('Teléfono:', estudiante.telefono, 'tel:', 'Sin teléfono')}
                ${contactoHTML('Correo:', estudiante.correo, 'mailto:', 'Sin correo')}
            </div>

            <div class="info-card">
                <h3>Evaluación</h3>
                <div class="dato"><strong>Inicio:</strong> <span>${escapar(formatearFecha(evaluacion.fecha_inicio))}</span></div>
                <div class="dato"><strong>Estado:</strong> <span>${escapar(evaluacion.estado || 'Sin estado')}</span></div>
            </div>

            <div class="info-card">
                <h3>Análisis</h3>
                <div class="dato"><strong>Riesgo:</strong> <span class="badge-riesgo">${escapar(riesgo.nombre || 'Sin nivel')}</span></div>
                <div class="dato"><strong>Emoción:</strong> <span>${escapar(analisis.emocion || 'Sin emoción')}</span></div>
                <div class="dato"><strong>Confianza:</strong> <span>${escapar(analisis.confianza ?? 0)}%</span></div>
            </div>
        </section>

        <section class="bloque">
            <h2>Resumen del análisis</h2>
            <div class="resumen">${escapar(analisis.resumen || 'No hay resumen disponible.')}</div>
        </section>

        <section class="bloque">
            <h2>Recurso recomendado por SafeMind</h2>
            <div id="recurso-recomendado" class="recurso-contenedor">
                <div class="recurso-cargando">Cargando recurso recomendado...</div>
            </div>
        </section>

        <section class="bloque">
            <h2>Conversación con SafeMind</h2>
            <div class="conversacion">${conversacion}</div>
        </section>

        <section class="bloque">
            <h2>Seguimiento profesional</h2>
            <p class="texto-ayuda">
                ${cerrada
                    ? 'Este caso ya está cerrado.'
                    : 'Registra aquí la observación correspondiente al seguimiento de este caso.'}
            </p>

            <textarea id="observacion-seguimiento" class="campo-observacion" rows="5"
                placeholder="${cerrada ? 'El caso está cerrado.' : 'Escribe la observación del seguimiento...'}"
                ${deshabilitado}></textarea>

            <div class="acciones-seguimiento">
                <button type="button" id="btn-seguimiento" class="boton" onclick="registrarSeguimiento()" ${deshabilitado}>
                    ${cerrada ? 'Caso cerrado' : 'Registrar seguimiento'}
                </button>
                <button type="button" id="btn-cerrar-caso" class="boton" onclick="cerrarCaso()" ${deshabilitado}>
                    ${cerrada ? 'Caso cerrado' : 'Cerrar caso'}
                </button>
                <span id="mensaje-seguimiento" class="mensaje-seguimiento"></span>
                <span id="mensaje-cierre" class="mensaje-cierre"></span>
            </div>
        </section>

        <section class="bloque">
            <h2>Historial de seguimientos</h2>
            <div id="historial-seguimientos" class="historial-seguimientos">
                <div class="historial-vacio">Cargando historial...</div>
            </div>
        </section>`;

    cargarRecursos(analisis.id);
}

async function cargarCaso() {
    try {
        renderCaso(await pedir(API.caso));
    } catch (error) {
        console.error('Error al cargar caso:', error);
        contenido.innerHTML = '<div class="error-caso">No se pudo cargar la información del caso.</div>';
    }
}

async function cargarRecursos(idAnalisis) {
    const contenedor = $('recurso-recomendado');

    if (!idAnalisis || Number(idAnalisis) <= 0) {
        contenedor.innerHTML = '<div class="recurso-vacio">No hay un análisis asociado a este caso.</div>';
        return;
    }

    try {
        const { recursos } = await pedir(`${API.recursos}?id_analisis=${Number(idAnalisis)}`);
        const lista = Array.isArray(recursos) ? recursos : [];

        contenedor.innerHTML = lista.length
            ? lista.map(r => `
                <article class="recurso-card">
                    <div class="recurso-icono">🌿</div>
                    <div class="recurso-titulo">${escapar(r.titulo || 'Recurso de apoyo')}</div>
                    <div class="recurso-descripcion">${escapar(r.descripcion || 'Sin descripción disponible.')}</div>
                    <span class="recurso-tipo">${escapar(r.tipo || 'Apoyo')}</span>
                </article>`).join('')
            : '<div class="recurso-vacio">SafeMind no recomendó un recurso específico para este caso.</div>';

    } catch (error) {
        console.error('Error al cargar recurso:', error);
        contenedor.innerHTML = '<div class="recurso-error">No se pudo cargar el recurso recomendado.</div>';
    }
}

async function cargarSeguimientos() {
    const contenedor = $('historial-seguimientos');
    if (!contenedor) return;

    try {
        const { seguimientos } = await pedir(API.historial);
        const lista = Array.isArray(seguimientos) ? seguimientos : [];

        contenedor.innerHTML = lista.length
            ? lista.map(s => {
                const cerrado = String(s.estado).toLowerCase() === 'cerrado';

                return `
                    <article class="seguimiento-item">
                        <div class="seguimiento-cabecera">
                            <div class="seguimiento-psicologo">${escapar(s.psicologo?.nombre || 'Psicólogo')}</div>
                            <div class="seguimiento-fecha">${escapar(formatearFecha(s.fecha))}</div>
                        </div>
                        <div class="seguimiento-observacion">${escapar(s.observacion || 'Sin observación')}</div>
                        <span class="seguimiento-estado ${cerrado ? 'cerrado' : ''}">${escapar(s.estado || 'Sin estado')}</span>
                    </article>`;
            }).join('')
            : '<div class="historial-vacio">Todavía no hay seguimientos registrados.</div>';

    } catch (error) {
        console.error('Error al cargar seguimientos:', error);
        contenedor.innerHTML = '<div class="historial-vacio">No se pudo cargar el historial de seguimientos.</div>';
    }
}

async function registrarSeguimiento() {
    const campo = $('observacion-seguimiento');
    const boton = $('btn-seguimiento');
    const mensaje = $('mensaje-seguimiento');

    const observacion = campo.value.trim();

    if (observacion === '') {
        mensaje.textContent = 'Escribe una observación antes de continuar.';
        mensaje.className = 'mensaje-seguimiento error';
        campo.focus();
        return;
    }

    boton.disabled = true;
    boton.textContent = 'Registrando...';
    mensaje.textContent = '';
    mensaje.className = 'mensaje-seguimiento';

    try {
        const resultado = await enviar(
            API.seguimiento,
            { id_alerta: ID_ALERTA, observacion },
            'No se pudo registrar el seguimiento.'
        );

        mensaje.textContent = 'Seguimiento registrado correctamente.';
        mensaje.className = 'mensaje-seguimiento exito';
        campo.value = '';

        const estado = $('estado-alerta');
        estado.textContent = resultado.estado_alerta || 'En seguimiento';
        estado.className = 'estado-alerta';

        $('btn-cerrar-caso').disabled = false;
        boton.textContent = 'Registrar seguimiento';
        boton.disabled = false;

        await cargarSeguimientos();

    } catch (error) {
        console.error('Error al registrar seguimiento:', error);
        mensaje.textContent = error.message || 'Ocurrió un error al registrar el seguimiento.';
        mensaje.className = 'mensaje-seguimiento error';
        boton.textContent = 'Registrar seguimiento';
        boton.disabled = false;
    }
}

async function cerrarCaso() {
    const boton = $('btn-cerrar-caso');
    const mensaje = $('mensaje-cierre');

    const confirmar = confirm(
        '¿Estás seguro de que deseas cerrar este caso?\n\n' +
        'Una vez cerrado, dejará de aparecer entre las alertas pendientes.'
    );

    if (!confirmar) return;

    boton.disabled = true;
    boton.textContent = 'Cerrando...';
    mensaje.textContent = '';
    mensaje.className = 'mensaje-cierre';

    try {
        await enviar(
            API.cerrar,
            { id_alerta: ID_ALERTA, observacion: 'Caso cerrado por seguimiento profesional.' },
            'No se pudo cerrar el caso.'
        );

        mensaje.textContent = 'Caso cerrado correctamente.';
        mensaje.className = 'mensaje-cierre exito';

        const estado = $('estado-alerta');
        estado.textContent = 'Cerrada';
        estado.className = 'estado-alerta cerrada';

        const campo = $('observacion-seguimiento');
        campo.disabled = true;
        campo.placeholder = 'El caso está cerrado.';

        const botonSeguimiento = $('btn-seguimiento');
        botonSeguimiento.disabled = true;
        botonSeguimiento.textContent = 'Caso cerrado';

        boton.disabled = true;
        boton.textContent = 'Caso cerrado';

        await cargarSeguimientos();

    } catch (error) {
        console.error('Error al cerrar caso:', error);
        mensaje.textContent = error.message || 'Ocurrió un error al cerrar el caso.';
        mensaje.className = 'mensaje-cierre error';
        boton.disabled = false;
        boton.textContent = 'Cerrar caso';
    }
}

// El historial solo existe si el caso cargó; si no, cargarSeguimientos no hace nada
cargarCaso().then(cargarSeguimientos);
</script>

</body>
</html>