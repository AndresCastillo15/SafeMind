<?php

session_start();

/*
|--------------------------------------------------------------------------
| PROTECCIÓN DE ACCESO
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'psicologo') {
    header("Location: ../login.php?rol=psicologo");
    exit();
}

$nombre = $_SESSION['nombre'] ?? 'Psicólogo';

?>
<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seguimientos - SafeMind</title>

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
            --line: #E5DED0;

            --ink: #1E2B2F;
            --ink-soft: #68777B;
            --ink-faint: #9AA6A9;

            --deep: #17455A;
            --deep-dark: #0E2E3D;

            --sage: #4F7F62;
            --sage-soft: #DCE9DF;

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

        .app {
            min-height: 100vh;
            display: flex;
        }

        /* =========================================================
           SIDEBAR
        ========================================================== */

        .sidebar {
            width: 263px;
            background: #fff;
            border-right: 1px solid var(--line);
            padding: 28px 20px;
            display: flex;
            flex-direction: column;
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0 8px;
            margin-bottom: 30px;
        }

        .logo-mark {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: var(--blue-soft);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--deep);
            font-weight: 700;
        }

        .logo span {
            font-family: 'Fraunces', serif;
            font-size: 23px;
            color: var(--deep-dark);
        }

        .perfil {
            background: var(--paper);
            border-radius: 12px;
            padding: 13px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--blue-soft);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--deep);
            font-weight: 700;
        }

        .perfil-info strong {
            display: block;
            font-size: 14px;
        }

        .perfil-info span {
            font-size: 12px;
            color: var(--ink-soft);
        }

        .menu {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

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

        .menu a:hover {
            background: var(--paper);
            color: var(--deep);
        }

        .menu a.activo {
            background: var(--blue-soft);
            color: var(--deep);
            font-weight: 600;
        }

        .orientacion {
            margin-top: auto;
            background: var(--sage-soft);
            border-radius: 12px;
            padding: 15px;
        }

        .orientacion strong {
            display: block;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .orientacion p {
            font-size: 12px;
            line-height: 1.5;
            color: var(--ink-soft);
            margin-bottom: 10px;
        }

        .orientacion span {
            color: var(--sage);
            font-size: 12px;
            font-weight: 700;
        }

        .cerrar {
            margin-top: 17px;
            padding: 10px 13px;
            color: var(--ink-soft);
            text-decoration: none;
            font-size: 13px;
        }

        .cerrar:hover {
            color: var(--red);
        }

        /* =========================================================
           MAIN
        ========================================================== */

        .main {
            margin-left: 263px;
            width: calc(100% - 263px);
            padding: 30px 38px 45px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 25px;
        }

        .titulo h1 {
            font-family: 'Fraunces', serif;
            font-size: 31px;
            color: var(--deep-dark);
            margin-bottom: 5px;
        }

        .titulo p {
            color: var(--ink-soft);
            font-size: 15px;
        }

        .fecha {
            text-align: right;
            font-size: 13px;
            color: var(--ink);
        }

        .fecha span {
            display: block;
            color: var(--ink-faint);
            margin-top: 3px;
        }

        /* =========================================================
           PANEL
        ========================================================== */

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
            gap: 15px;
            margin-bottom: 20px;
        }

        .panel-header h2 {
            font-family: 'Fraunces', serif;
            font-size: 20px;
            color: var(--deep-dark);
        }

        .total {
            color: var(--ink-faint);
            font-size: 12px;
        }

        /* =========================================================
           BUSCADOR
        ========================================================== */

        .busqueda {
            margin-bottom: 20px;
        }

        .busqueda input {
            width: 100%;
            max-width: 420px;
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 12px 14px;
            font-family: 'Karla', sans-serif;
            font-size: 14px;
            color: var(--ink);
            background: #fff;
        }

        .busqueda input:focus {
            outline: none;
            border-color: var(--deep);
            box-shadow: 0 0 0 3px rgba(23, 69, 90, 0.10);
        }

        /* =========================================================
           TABLA
        ========================================================== */

        .tabla-contenedor {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 980px;
        }

        thead th {
            text-align: left;
            padding: 12px 14px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--ink-faint);
            border-bottom: 1px solid var(--line);
        }

        tbody td {
            padding: 15px 14px;
            font-size: 13px;
            border-bottom: 1px solid #EEEAE2;
            vertical-align: top;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        tbody tr:hover {
            background: #FCFBF8;
        }

        .estudiante {
            font-weight: 700;
            color: var(--deep-dark);
        }

        .psicologo,
        .fecha-seguimiento {
            color: var(--ink-soft);
        }

        .fecha-seguimiento {
            white-space: nowrap;
        }

        .observacion {
            color: var(--ink-soft);
            line-height: 1.5;
            max-width: 350px;
        }

        /* =========================================================
           BADGES
        ========================================================== */

        .badge {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
        }

        .badge.seguimiento {
            background: var(--orange-soft);
            color: #A75223;
        }

        .badge.cerrado {
            background: var(--sage-soft);
            color: var(--sage);
        }

        .badge.pendiente {
            background: var(--red-soft);
            color: #963B2A;
        }

        /* =========================================================
           ACCIÓN
        ========================================================== */

        .ver-caso {
            display: inline-block;
            background: var(--blue-soft);
            color: var(--deep);
            border-radius: 8px;
            padding: 8px 11px;
            font-size: 11px;
            font-weight: 700;
            text-decoration: none;
            white-space: nowrap;
            transition: 0.2s ease;
        }

        .ver-caso:hover {
            background: var(--deep);
            color: #fff;
        }

        .sin-caso {
            color: var(--ink-faint);
            font-size: 12px;
        }

        /* =========================================================
           ESTADOS DE LA TABLA (filas de mensaje)
        ========================================================== */

        .fila-estado {
            padding: 70px 0;
            text-align: center;
            color: var(--ink-faint);
            font-size: 14px;
        }

        .fila-estado.error {
            color: var(--red);
        }

        /* =========================================================
           RESPONSIVE
        ========================================================== */

        @media (max-width: 800px) {
            .sidebar {
                position: static;
                width: 100%;
            }

            .app {
                flex-direction: column;
            }

            .main {
                margin-left: 0;
                width: 100%;
                padding: 25px 20px;
            }

            .topbar {
                flex-direction: column;
            }

            .fecha {
                text-align: left;
            }
        }

    </style>

</head>

<body>

<div class="app">

    <!-- =========================================================
         SIDEBAR
    ========================================================== -->

    <aside class="sidebar">

        <div class="logo">
            <div class="logo-mark">SM</div>
            <span>SafeMind</span>
        </div>

        <div class="perfil">

            <div class="avatar">
                <?php echo htmlspecialchars(mb_strtoupper(mb_substr($nombre, 0, 2))); ?>
            </div>

            <div class="perfil-info">
                <strong><?php echo htmlspecialchars($nombre); ?></strong>
                <span>Psicólogo / Orientador</span>
            </div>

        </div>

        <nav class="menu">
            <a href="dashboard_psicologo.php">🏠 &nbsp; Inicio</a>
            <a href="dashboard_psicologo.php#alertas">🔔 &nbsp; Alertas</a>
            <a href="estudiantes_psicologo.php">👥 &nbsp; Estudiantes</a>
            <a href="seguimientos_psicologo.php" class="activo">📋 &nbsp; Seguimientos</a>
            <a href="recursos_psicologo.php">🌿 &nbsp; Recursos</a>
        </nav>

        <div class="orientacion">
            <strong>SafeMind</strong>
            <p>Historial global de atención y seguimiento profesional.</p>
            <span>Panel protegido</span>
        </div>

        <a href="../logout.php" class="cerrar">↪ &nbsp; Cerrar sesión</a>

    </aside>

    <!-- =========================================================
         MAIN
    ========================================================== -->

    <main class="main">

        <div class="topbar">

            <div class="titulo">
                <h1>Casos en seguimiento</h1>
                <p>Estudiantes que actualmente requieren seguimiento profesional</p>
            </div>

            <div class="fecha">
                <?php echo date('d/m/Y'); ?>
                <span>Información de SafeMind</span>
            </div>

        </div>

        <section class="panel">

            <div class="panel-header">
                <h2>Casos activos</h2>
                <span id="total-seguimientos" class="total">Cargando...</span>
            </div>

            <div class="busqueda">
                <input
                    type="text"
                    id="buscador"
                    placeholder="Buscar por estudiante, psicólogo o curso..."
                    autocomplete="off"
                >
            </div>

            <div class="tabla-contenedor">

                <table>

                    <thead>
                        <tr>
                            <th>Estudiante</th>
                            <th>Curso</th>
                            <th>Psicólogo</th>
                            <th>Fecha</th>
                            <th>Observación</th>
                            <th>Estado</th>
                            <th>Acción</th>
                        </tr>
                    </thead>

                    <tbody id="tabla-seguimientos">
                        <tr>
                            <td colspan="7" class="fila-estado">
                                Cargando casos en seguimiento...
                            </td>
                        </tr>
                    </tbody>

                </table>

            </div>

        </section>

    </main>

</div>

<script>

/*
|--------------------------------------------------------------------------
| API Y ELEMENTOS
|--------------------------------------------------------------------------
*/

const API_SEGUIMIENTOS = '../API/seguimiento/obtener_seguimientos_psicologo.php';

const tabla = document.getElementById('tabla-seguimientos');
const total = document.getElementById('total-seguimientos');
const buscador = document.getElementById('buscador');

let seguimientos = [];


/*
|--------------------------------------------------------------------------
| UTILIDADES
|--------------------------------------------------------------------------
*/

function escapar(texto) {
    const div = document.createElement('div');
    div.textContent = texto ?? '';
    return div.innerHTML;
}

// "2026-10-27 14:30:00" -> "27/10/2026 14:30:00"
function formatearFecha(fecha) {
    if (!fecha) return 'Sin fecha';
    return String(fecha).replace(/^(\d{4})-(\d{2})-(\d{2})/, '$3/$2/$1');
}

const CLASES_ESTADO = {
    'en seguimiento': 'seguimiento',
    cerrado: 'cerrado',
    pendiente: 'pendiente'
};

function claseEstado(estado) {
    return CLASES_ESTADO[String(estado ?? '').trim().toLowerCase()] || '';
}

function mostrarFila(mensaje, clase = '') {
    tabla.innerHTML = `
        <tr>
            <td colspan="7" class="fila-estado ${clase}">${mensaje}</td>
        </tr>
    `;
}


/*
|--------------------------------------------------------------------------
| FILTRO (texto del buscador)
|--------------------------------------------------------------------------
*/

function filtrarSeguimientos() {

    const texto = buscador.value.toLowerCase().trim();

    if (texto === '') return seguimientos;

    return seguimientos.filter(caso => {

        const estudiante = caso.estudiante || {};
        const psicologo = caso.psicologo || {};

        return [
            `${estudiante.nombre ?? ''} ${estudiante.apellido ?? ''}`,
            psicologo.nombre,
            estudiante.curso
        ].some(campo => String(campo ?? '').toLowerCase().includes(texto));

    });

}


/*
|--------------------------------------------------------------------------
| RENDERIZAR
|--------------------------------------------------------------------------
*/

function renderizarSeguimientos(lista) {

    if (lista.length === 0) {
        mostrarFila('No hay estudiantes actualmente en seguimiento.');
        return;
    }

    tabla.innerHTML = lista.map(caso => {

        const estudiante = caso.estudiante || {};
        const psicologo = caso.psicologo || {};

        const nombreEstudiante = `${estudiante.nombre ?? ''} ${estudiante.apellido ?? ''}`.trim();
        const idAlerta = Number(caso.id_alerta) || 0;
        const estado = caso.estado || 'Sin estado';

        const accion = idAlerta > 0
            ? `<a href="detalle_alerta.php?id_alerta=${idAlerta}" class="ver-caso">Ver caso →</a>`
            : '<span class="sin-caso">Sin caso</span>';

        return `
            <tr>
                <td><span class="estudiante">${escapar(nombreEstudiante)}</span></td>
                <td>${escapar(estudiante.curso || 'Sin curso')}</td>
                <td><span class="psicologo">${escapar(psicologo.nombre || 'Psicólogo')}</span></td>
                <td><span class="fecha-seguimiento">${escapar(formatearFecha(caso.fecha))}</span></td>
                <td><div class="observacion">${escapar(caso.observacion || 'Sin observación')}</div></td>
                <td><span class="badge ${claseEstado(estado)}">${escapar(estado)}</span></td>
                <td>${accion}</td>
            </tr>
        `;

    }).join('');

}


/*
|--------------------------------------------------------------------------
| CARGAR SEGUIMIENTOS
|--------------------------------------------------------------------------
*/

async function cargarSeguimientos() {

    try {

        const respuesta = await fetch(API_SEGUIMIENTOS, {
            method: 'GET',
            cache: 'no-store',
            credentials: 'same-origin'
        });

        if (!respuesta.ok) {
            throw new Error('No se pudo consultar la API.');
        }

        const datos = await respuesta.json();

        if (!datos.success) {
            throw new Error(datos.mensaje || 'No se pudieron cargar los seguimientos.');
        }

        seguimientos = Array.isArray(datos.casos_en_seguimiento)
            ? datos.casos_en_seguimiento
            : [];

        total.textContent = `${seguimientos.length} registro(s)`;

        renderizarSeguimientos(seguimientos);

    } catch (error) {

        console.error('Error al cargar seguimientos:', error);

        total.textContent = 'Error';

        mostrarFila('No se pudieron cargar los seguimientos.', 'error');

    }

}


/*
|--------------------------------------------------------------------------
| BUSCADOR
|--------------------------------------------------------------------------
*/

buscador.addEventListener('input', () => {
    renderizarSeguimientos(filtrarSeguimientos());
});


/*
|--------------------------------------------------------------------------
| INICIAR
|--------------------------------------------------------------------------
*/

cargarSeguimientos();

</script>

</body>
</html>