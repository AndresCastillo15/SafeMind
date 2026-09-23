<?php

session_start();

/*
|--------------------------------------------------------------------------
| PROTECCIÓN DE ACCESO
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['rol']) ||
    $_SESSION['rol'] !== 'psicologo'
) {
    header("Location: ../login.php?rol=psicologo");
    exit();
}


/*
|--------------------------------------------------------------------------
| DATOS DEL PSICÓLOGO
|--------------------------------------------------------------------------
*/

$nombre = $_SESSION['nombre'] ?? 'Psicólogo';

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Seguimientos - SafeMind</title>


    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
        crossorigin
    >

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

            --gold: #C8922F;
            --gold-soft: #F5E7C8;

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
           FILTROS
        ========================================================== */

        .filtros {

            display: flex;

            gap: 12px;

            flex-wrap: wrap;

            margin-bottom: 20px;

        }


        .buscador {

            flex: 1;

            min-width: 260px;

        }


        .buscador input {

            width: 100%;

            border: 1px solid var(--line);

            border-radius: 10px;

            padding: 12px 14px;

            font-family: 'Karla', sans-serif;

            font-size: 14px;

            color: var(--ink);

            background: #fff;

        }


        .buscador input:focus {

            outline: none;

            border-color: var(--deep);

            box-shadow:
                0 0 0 3px
                rgba(23, 69, 90, 0.10);

        }


        .filtro-estado select {

            border: 1px solid var(--line);

            border-radius: 10px;

            padding: 12px 35px 12px 14px;

            font-family: 'Karla', sans-serif;

            font-size: 14px;

            color: var(--ink);

            background: #fff;

            cursor: pointer;

        }


        .filtro-estado select:focus {

            outline: none;

            border-color: var(--deep);

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


        .psicologo {

            color: var(--ink-soft);

        }


        .fecha-seguimiento {

            color: var(--ink-soft);

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

            border: none;

            background: var(--blue-soft);

            color: var(--deep);

            border-radius: 8px;

            padding: 8px 11px;

            font-family: 'Karla', sans-serif;

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


        /* =========================================================
           ESTADOS
        ========================================================== */

        .cargando,
        .vacio,
        .error {

            min-height: 180px;

            display: flex;

            justify-content: center;

            align-items: center;

            text-align: center;

            color: var(--ink-faint);

            font-size: 14px;

        }


        .error {

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

            <div class="logo-mark">
                SM
            </div>

            <span>
                SafeMind
            </span>

        </div>


        <div class="perfil">

            <div class="avatar">

                <?php

                echo htmlspecialchars(
                    strtoupper(
                        substr(
                            $nombre,
                            0,
                            2
                        )
                    )
                );

                ?>

            </div>


            <div class="perfil-info">

                <strong>

                    <?php

                    echo htmlspecialchars(
                        $nombre
                    );

                    ?>

                </strong>


                <span>
                    Psicólogo / Orientador
                </span>

            </div>

        </div>


        <nav class="menu">

            <a href="dashboard_psicologo.php">
                🏠 &nbsp; Inicio
            </a>


            <a href="dashboard_psicologo.php#alertas">
                🔔 &nbsp; Alertas
            </a>


            <a href="estudiantes_psicologo.php">
                👥 &nbsp; Estudiantes
            </a>


            <a
                href="seguimientos_psicologo.php"
                class="activo"
            >
                📋 &nbsp; Seguimientos
            </a>


            <a href="recursos_psicologo.php">
                🌿 &nbsp; Recursos
            </a>

        </nav>


        <div class="orientacion">

            <strong>
                SafeMind
            </strong>


            <p>
                Historial global de atención y
                seguimiento profesional.
            </p>


            <span>
                Panel protegido
            </span>

        </div>


        <a
            href="../logout.php"
            class="cerrar"
        >
            ↪ &nbsp; Cerrar sesión
        </a>

    </aside>


    <!-- =========================================================
         MAIN
    ========================================================== -->

    <main class="main">


        <div class="topbar">

            <div class="titulo">

                <h1>
                    Casos en seguimiento
                </h1>


                <p>
                    Estudiantes que actualmente requieren seguimiento profesional
                </p>

            </div>


            <div class="fecha">

                <?php

                echo date('d/m/Y');

                ?>


                <span>
                    Información de SafeMind
                </span>

            </div>

        </div>


        <!-- =====================================================
             PANEL
        ====================================================== -->

        <section class="panel">


            <div class="panel-header">

                <h2>
                    Casos activos
                </h2>


                <span
                    id="total-seguimientos"
                    class="total"
                >
                    Cargando...
                </span>

            </div>


            <!-- =================================================
                 FILTROS
            ================================================== -->

            <div class="filtros">


                <div class="buscador">

                    <input
                        type="text"
                        id="buscador"
                        placeholder="Buscar por estudiante, psicólogo o curso..."
                        autocomplete="off"
                    >

                </div>
            </div>


            <!-- =================================================
                 TABLA
            ================================================== -->

            <div class="tabla-contenedor">

                <table>

                    <thead>

                        <tr>

                            <th>
                                Estudiante
                            </th>

                            <th>
                                Curso
                            </th>

                            <th>
                                Psicólogo
                            </th>

                            <th>
                                Fecha
                            </th>

                            <th>
                                Observación
                            </th>

                            <th>
                                Estado
                            </th>

                            <th>
                                Acción
                            </th>

                        </tr>

                    </thead>


                    <tbody id="tabla-seguimientos">

                        <tr>

                            <td
                                colspan="7"
                                class="cargando"
                            >

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
| API
|--------------------------------------------------------------------------
*/

const API_SEGUIMIENTOS =
    '../API/seguimiento/obtener_seguimientos_psicologo.php';


/*
|--------------------------------------------------------------------------
| ELEMENTOS
|--------------------------------------------------------------------------
*/

const tabla =
    document.getElementById(
        'tabla-seguimientos'
    );


const total =
    document.getElementById(
        'total-seguimientos'
    );


const buscador =
    document.getElementById(
        'buscador'
    );



/*
|--------------------------------------------------------------------------
| DATOS
|--------------------------------------------------------------------------
*/

let seguimientos = [];


/*
|--------------------------------------------------------------------------
| ESCAPAR HTML
|--------------------------------------------------------------------------
*/

function escapar(texto) {

    const div =
        document.createElement('div');

    div.textContent =
        texto ?? '';

    return div.innerHTML;

}


/*
|--------------------------------------------------------------------------
| FORMATEAR FECHA
|--------------------------------------------------------------------------
*/

function formatearFecha(fecha) {

    if (!fecha) {

        return 'Sin fecha';

    }


    const partes =
        String(fecha).split(' ');


    if (partes.length < 2) {

        return String(fecha);

    }


    const fechaPartes =
        partes[0].split('-');


    if (fechaPartes.length !== 3) {

        return String(fecha);

    }


    return `${fechaPartes[2]}/${fechaPartes[1]}/${fechaPartes[0]} ${partes[1]}`;

}


/*
|--------------------------------------------------------------------------
| CLASE DEL ESTADO
|--------------------------------------------------------------------------
*/

function claseEstado(estado) {

    const valor =
        String(
            estado ?? ''
        ).toLowerCase();


    if (valor === 'cerrado') {

        return 'cerrado';

    }


    if (valor === 'en seguimiento') {

        return 'seguimiento';

    }


    if (valor === 'pendiente') {

        return 'pendiente';

    }


    return '';

}


/*
|--------------------------------------------------------------------------
| APLICAR FILTROS
|--------------------------------------------------------------------------
*/

function aplicarFiltros() {

    const texto =
        buscador.value
            .toLowerCase()
            .trim();


    const estadoSeleccionado =
        filtroEstado.value;


    const filtrados =
        seguimientos.filter(
            seguimiento => {

                const estudiante =
                    seguimiento.estudiante || {};


                const psicologo =
                    seguimiento.psicologo || {};


                const nombreEstudiante =
                    `${estudiante.nombre ?? ''}
                    ${estudiante.apellido ?? ''}`
                    .toLowerCase();


                const nombrePsicologo =
                    String(
                        psicologo.nombre ?? ''
                    ).toLowerCase();


                const curso =
                    String(
                        estudiante.curso ?? ''
                    ).toLowerCase();


                const coincideTexto =
                    texto === '' ||
                    nombreEstudiante.includes(texto) ||
                    nombrePsicologo.includes(texto) ||
                    curso.includes(texto);


                const coincideEstado =
                    estadoSeleccionado === '' ||
                    seguimiento.estado ===
                        estadoSeleccionado;


                return (
                    coincideTexto &&
                    coincideEstado
                );

            }
        );


    renderizarSeguimientos(
        filtrados
    );

}


/*
|--------------------------------------------------------------------------
| RENDERIZAR
|--------------------------------------------------------------------------
*/

function renderizarSeguimientos(lista) {

    if (!Array.isArray(lista)) {

        lista = [];

    }


    if (lista.length === 0) {

        tabla.innerHTML = `

            <tr>

                <td
                    colspan="7"
                    class="vacio"
                >

                    No hay estudiantes actualmente en seguimiento.

                </td>

            </tr>

        `;

        return;

    }


    tabla.innerHTML =
        lista.map(
            seguimiento => {

                const estudiante =
                    seguimiento.estudiante || {};


                const psicologo =
                    seguimiento.psicologo || {};


                const nombreEstudiante =
                    `${estudiante.nombre ?? ''}
                    ${estudiante.apellido ?? ''}`
                    .trim();


                const nombrePsicologo =
                    psicologo.nombre ||
                    'Psicólogo';


                const observacion =
                    seguimiento.observacion ||
                    'Sin observación';


                const estado =
                    seguimiento.estado ||
                    'Sin estado';


                const idAlerta =
                    Number(
                        seguimiento.id_alerta || 0
                    );


                let accion =
                    `<span class="sin-caso">
                        Sin caso
                     </span>`;


                if (idAlerta > 0) {

                    accion = `

                        <a
                            href="detalle_alerta.php?id_alerta=${idAlerta}"
                            class="ver-caso"
                        >

                            Ver caso →

                        </a>

                    `;

                }


                return `

                    <tr>


                        <td>

                            <span
                                class="estudiante"
                            >

                                ${escapar(
                                    nombreEstudiante
                                )}

                            </span>

                        </td>


                        <td>

                            ${escapar(
                                estudiante.curso ||
                                'Sin curso'
                            )}

                        </td>


                        <td>

                            <span
                                class="psicologo"
                            >

                                ${escapar(
                                    nombrePsicologo
                                )}

                            </span>

                        </td>


                        <td>

                            <span
                                class="fecha-seguimiento"
                            >

                                ${escapar(
                                    formatearFecha(
                                        seguimiento.fecha
                                    )
                                )}

                            </span>

                        </td>


                        <td>

                            <div
                                class="observacion"
                            >

                                ${escapar(
                                    observacion
                                )}

                            </div>

                        </td>


                        <td>

                            <span
                                class="badge ${claseEstado(
                                    estado
                                )}"
                            >

                                ${escapar(
                                    estado
                                )}

                            </span>

                        </td>


                        <td>

                            ${accion}

                        </td>


                    </tr>

                `;

            }
        ).join('');

}


/*
|--------------------------------------------------------------------------
| CARGAR SEGUIMIENTOS
|--------------------------------------------------------------------------
*/

async function cargarSeguimientos() {

    try {

        const respuesta =
            await fetch(
                API_SEGUIMIENTOS,
                {
                    method: 'GET',
                    cache: 'no-store',
                    credentials: 'same-origin'
                }
            );


        if (!respuesta.ok) {

            throw new Error(
                'No se pudo consultar la API.'
            );

        }


        const datos =
            await respuesta.json();


        if (!datos.success) {

            throw new Error(
                datos.mensaje ||
                'No se pudieron cargar los seguimientos.'
            );

        }


        seguimientos =
            Array.isArray(
                datos.casos_en_seguimiento
            )
                ? datos.casos_en_seguimiento
                : [];


        total.textContent =
            `${seguimientos.length} registro(s)`;


        renderizarSeguimientos(
            seguimientos
        );


    } catch (error) {

        console.error(
            'Error al cargar seguimientos:',
            error
        );


        total.textContent =
            'Error';


        tabla.innerHTML = `

            <tr>

                <td
                    colspan="7"
                    class="error"
                >

                    No se pudieron cargar
                    los seguimientos.

                </td>

            </tr>

        `;

    }

}


/*
|--------------------------------------------------------------------------
| EVENTOS DE FILTRO
|--------------------------------------------------------------------------
*/

buscador.addEventListener(
    'input',
    function () {

        const texto =
            this.value
                .toLowerCase()
                .trim();


        const filtrados =
            seguimientos.filter(
                caso => {

                    const estudiante =
                        caso.estudiante || {};

                    const psicologo =
                        caso.psicologo || {};


                    const nombreEstudiante =
                        `${estudiante.nombre ?? ''}
                        ${estudiante.apellido ?? ''}`
                        .toLowerCase();


                    const nombrePsicologo =
                        String(
                            psicologo.nombre ?? ''
                        ).toLowerCase();


                    const curso =
                        String(
                            estudiante.curso ?? ''
                        ).toLowerCase();


                    return (
                        texto === '' ||
                        nombreEstudiante.includes(texto) ||
                        nombrePsicologo.includes(texto) ||
                        curso.includes(texto)
                    );

                }
            );


        renderizarSeguimientos(
            filtrados
        );

    }
);


/*
|--------------------------------------------------------------------------
| INICIAR
|--------------------------------------------------------------------------
*/

cargarSeguimientos();

</script>


</body>

</html>