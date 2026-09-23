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
| ID DE LA ALERTA
|--------------------------------------------------------------------------
*/

$id_alerta = isset($_GET['id_alerta'])
    ? intval($_GET['id_alerta'])
    : 0;

if ($id_alerta <= 0) {
    header("Location: dashboard_psicologo.php");
    exit();
}


/*
|--------------------------------------------------------------------------
| DATOS DEL PSICÓLOGO
|--------------------------------------------------------------------------
*/

$nombre_psicologo = $_SESSION['nombre'] ?? 'Psicólogo';

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Detalle del caso - SafeMind</title>


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


        .volver:hover {

            text-decoration: underline;

        }


        .encabezado {

            display: flex;

            justify-content: space-between;

            align-items: flex-start;

            gap: 20px;

            margin-bottom: 24px;

        }


        .encabezado h1 {

            font-family: 'Fraunces', serif;

            font-size: 31px;

            color: var(--deep-dark);

            margin-bottom: 5px;

        }


        .encabezado p {

            color: var(--ink-soft);

            font-size: 15px;

        }


        .estado-alerta {

            padding: 7px 12px;

            border-radius: 20px;

            background: var(--orange-soft);

            color: #A75223;

            font-size: 12px;

            font-weight: 700;

        }


        .estado-alerta.cerrada {

            background: var(--sage-soft);

            color: var(--sage);

        }


        /* =========================================================
           INFORMACIÓN
        ========================================================== */

        .grid-info {

            display: grid;

            grid-template-columns: 1fr 1fr 1fr;

            gap: 16px;

            margin-bottom: 18px;

        }


        .info-card {

            background: #fff;

            border: 1px solid var(--line);

            border-radius: 15px;

            padding: 20px;

        }


        .info-card h3 {

            font-family: 'Fraunces', serif;

            font-size: 17px;

            color: var(--deep-dark);

            margin-bottom: 14px;

        }


        .dato {

            margin-bottom: 9px;

            font-size: 13px;

        }


        .dato strong {

            color: var(--ink);

        }


        .dato span {

            color: var(--ink-soft);

        }


        .badge-riesgo {

            display: inline-block;

            padding: 6px 10px;

            border-radius: 20px;

            background: var(--orange-soft);

            color: #A75223;

            font-size: 12px;

            font-weight: 700;

        }


        /* =========================================================
           BLOQUES
        ========================================================== */

        .bloque {

            background: #fff;

            border: 1px solid var(--line);

            border-radius: 15px;

            padding: 22px;

            margin-bottom: 18px;

        }


        .bloque h2 {

            font-family: 'Fraunces', serif;

            font-size: 20px;

            color: var(--deep-dark);

            margin-bottom: 16px;

        }


        /* =========================================================
           RESUMEN
        ========================================================== */

        .resumen {

            color: var(--ink-soft);

            font-size: 14px;

            line-height: 1.7;

            background: var(--paper);

            padding: 16px;

            border-radius: 10px;

        }


        /* =========================================================
           RECURSO RECOMENDADO
        ========================================================== */

        .recurso-contenedor {

            display: flex;

            flex-direction: column;

            gap: 12px;

        }


        .recurso-card {

            background: var(--paper);

            border: 1px solid var(--line);

            border-radius: 12px;

            padding: 17px;

        }


        .recurso-titulo {

            font-family: 'Fraunces', serif;

            font-size: 17px;

            color: var(--deep-dark);

            margin-bottom: 7px;

        }


        .recurso-descripcion {

            color: var(--ink-soft);

            font-size: 13px;

            line-height: 1.6;

            margin-bottom: 10px;

        }


        .recurso-tipo {

            display: inline-block;

            padding: 5px 9px;

            border-radius: 20px;

            background: var(--sage-soft);

            color: var(--sage);

            font-size: 11px;

            font-weight: 700;

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


        .recurso-vacio {

            color: var(--ink-faint);

            font-size: 13px;

            background: var(--paper);

            border-radius: 10px;

            padding: 15px;

        }


        .recurso-cargando {

            color: var(--ink-faint);

            font-size: 13px;

        }


        .recurso-error {

            color: var(--red);

            font-size: 13px;

        }


        /* =========================================================
           CONVERSACIÓN
        ========================================================== */

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


        .mensaje-remitente {

            font-size: 11px;

            font-weight: 700;

            margin-bottom: 5px;

            color: var(--ink-faint);

        }


        .mensaje-fecha {

            margin-top: 6px;

            font-size: 10px;

            color: var(--ink-faint);

        }


        /* =========================================================
           SEGUIMIENTO
        ========================================================== */

        .texto-ayuda {

            color: var(--ink-soft);

            font-size: 13px;

            line-height: 1.5;

            margin-bottom: 14px;

        }


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


        .campo-observacion::placeholder {

            color: var(--ink-faint);

        }


        .campo-observacion:focus {

            outline: none;

            border-color: var(--deep);

            box-shadow:
                0 0 0 3px
                rgba(23, 69, 90, 0.10);

        }


        .campo-observacion:disabled {

            background: #F0EEE9;

            cursor: not-allowed;

        }


        .acciones-seguimiento {

            display: flex;

            align-items: center;

            gap: 12px;

            margin-top: 14px;

            flex-wrap: wrap;

        }


        #btn-seguimiento {

            border: none;

            border-radius: 10px;

            background: var(--deep);

            color: #fff;

            padding: 11px 18px;

            font-family: 'Karla', sans-serif;

            font-size: 13px;

            font-weight: 700;

            cursor: pointer;

        }


        #btn-seguimiento:hover {

            background: var(--deep-dark);

        }


        #btn-seguimiento:disabled {

            opacity: 0.65;

            cursor: default;

        }


        #btn-cerrar-caso {

            border: 1px solid var(--red);

            border-radius: 10px;

            background: transparent;

            color: var(--red);

            padding: 10px 18px;

            font-family: 'Karla', sans-serif;

            font-size: 13px;

            font-weight: 700;

            cursor: pointer;

        }


        #btn-cerrar-caso:hover {

            background: var(--red-soft);

        }


        #btn-cerrar-caso:disabled {

            opacity: 0.6;

            cursor: default;

        }


        .mensaje-seguimiento,
        .mensaje-cierre {

            font-size: 13px;

        }


        .mensaje-seguimiento.exito,
        .mensaje-cierre.exito {

            color: var(--sage);

        }


        .mensaje-seguimiento.error,
        .mensaje-cierre.error {

            color: var(--red);

        }


        /* =========================================================
           HISTORIAL
        ========================================================== */

        .historial-seguimientos {

            display: flex;

            flex-direction: column;

            gap: 12px;

        }


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


        .seguimiento-psicologo {

            font-size: 13px;

            font-weight: 700;

            color: var(--deep-dark);

        }


        .seguimiento-fecha {

            font-size: 11px;

            color: var(--ink-faint);

        }


        .seguimiento-observacion {

            background: var(--paper);

            border-radius: 9px;

            padding: 12px;

            color: var(--ink-soft);

            font-size: 13px;

            line-height: 1.6;

            margin-bottom: 10px;

        }


        .seguimiento-estado {

            display: inline-block;

            padding: 5px 9px;

            border-radius: 20px;

            background: var(--sage-soft);

            color: var(--sage);

            font-size: 11px;

            font-weight: 700;

        }


        .seguimiento-estado.cerrado {

            background: var(--blue-soft);

            color: var(--deep);

        }


        .historial-vacio {

            min-height: 100px;

            display: flex;

            align-items: center;

            justify-content: center;

            color: var(--ink-faint);

            font-size: 13px;

            text-align: center;

        }


        /* =========================================================
           CARGA / ERROR
        ========================================================== */

        .cargando {

            min-height: 300px;

            display: flex;

            align-items: center;

            justify-content: center;

            color: var(--ink-faint);

            font-size: 14px;

        }


        .error {

            min-height: 250px;

            display: flex;

            align-items: center;

            justify-content: center;

            text-align: center;

            color: var(--red);

            font-size: 14px;

        }


        /* =========================================================
           RESPONSIVE
        ========================================================== */

        @media (max-width: 1050px) {

            .grid-info {

                grid-template-columns: 1fr 1fr;

            }

        }


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


            .encabezado {

                flex-direction: column;

            }

        }


        @media (max-width: 600px) {

            .grid-info {

                grid-template-columns: 1fr;

            }


            .mensaje {

                max-width: 90%;

            }


            .seguimiento-cabecera {

                flex-direction: column;

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
                            $nombre_psicologo,
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
                        $nombre_psicologo
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


            <a
                href="dashboard_psicologo.php#alertas"
            >
                🔔 &nbsp; Alertas
            </a>


            <a href="estudiantes_psicologo.php">
                👥 &nbsp; Estudiantes
            </a>


            <a href="seguimientos_psicologo.php">
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
                Información privada destinada al
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
         CONTENIDO
    ========================================================== -->

    <main class="main">


        <a
            href="dashboard_psicologo.php"
            class="volver"
        >
            ← Volver a alertas
        </a>


        <div id="contenido-caso">

            <div class="cargando">
                Cargando información del caso...
            </div>

        </div>


    </main>

</div>


<script>

/*
|--------------------------------------------------------------------------
| ID DE LA ALERTA
|--------------------------------------------------------------------------
*/

const ID_ALERTA =
    <?php echo $id_alerta; ?>;


/*
|--------------------------------------------------------------------------
| APIs
|--------------------------------------------------------------------------
*/

const API_CASO =
    `../API/seguimiento/obtener_alerta.php?id_alerta=${ID_ALERTA}`;

const API_SEGUIMIENTO =
    '../API/seguimiento/registrar_seguimiento.php';

const API_HISTORIAL =
    '../API/seguimiento/obtener_seguimientos.php';

const API_CERRAR_CASO =
    '../API/seguimiento/cerrar_caso.php';

const API_RECURSOS =
    '../API/seguimiento/obtener_recursos_analisis.php';


/*
|--------------------------------------------------------------------------
| CONTENEDOR
|--------------------------------------------------------------------------
*/

const contenido =
    document.getElementById(
        'contenido-caso'
    );


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
| CARGAR CASO
|--------------------------------------------------------------------------
*/

async function cargarCaso() {

    try {

        const respuesta =
            await fetch(
                API_CASO,
                {
                    method: 'GET',
                    cache: 'no-store',
                    credentials: 'same-origin'
                }
            );


        if (!respuesta.ok) {

            throw new Error(
                'No se pudo consultar el caso.'
            );

        }


        const datos =
            await respuesta.json();


        if (!datos.success) {

            throw new Error(
                datos.mensaje ||
                'No se pudo cargar el caso.'
            );

        }


        const alerta =
            datos.alerta || {};

        const estudiante =
            datos.estudiante || {};

        const evaluacion =
            datos.evaluacion || {};

        const riesgo =
            datos.riesgo || {};

        const analisis =
            datos.analisis || {};

        const mensajes =
            Array.isArray(datos.mensajes)
                ? datos.mensajes
                : [];


        /*
        |--------------------------------------------------------------------------
        | CONVERSACIÓN
        |--------------------------------------------------------------------------
        */

        let conversacionHTML = '';


        if (mensajes.length === 0) {

            conversacionHTML = `

                <div class="historial-vacio">

                    No hay mensajes registrados.

                </div>

            `;

        } else {

            conversacionHTML =
                mensajes.map(
                    mensaje => {

                        const clase =
                            mensaje.remitente === 'estudiante'
                                ? 'estudiante'
                                : 'ia';


                        const nombreRemitente =
                            mensaje.remitente === 'estudiante'
                                ? 'Estudiante'
                                : 'SafeMind';


                        return `

                            <div
                                class="mensaje ${clase}"
                            >

                                <div
                                    class="mensaje-remitente"
                                >

                                    ${escapar(
                                        nombreRemitente
                                    )}

                                </div>


                                <div>

                                    ${escapar(
                                        mensaje.contenido
                                    )}

                                </div>


                                <div
                                    class="mensaje-fecha"
                                >

                                    ${escapar(
                                        formatearFecha(
                                            mensaje.fecha
                                        )
                                    )}

                                </div>

                            </div>

                        `;

                    }
                ).join('');

        }


        /*
        |--------------------------------------------------------------------------
        | ESTADO
        |--------------------------------------------------------------------------
        */

        const alertaCerrada =
            String(alerta.estado)
                .toLowerCase() === 'cerrada';


        const claseEstado =
            alertaCerrada
                ? 'estado-alerta cerrada'
                : 'estado-alerta';


        const textoEstado =
            alerta.estado ||
            'Pendiente';


        /*
        |--------------------------------------------------------------------------
        | RENDERIZAR CASO
        |--------------------------------------------------------------------------
        */

        contenido.innerHTML = `

            <div class="encabezado">

                <div>

                    <h1>
                        Caso de seguimiento
                    </h1>


                    <p>

                        ${escapar(
                            estudiante.nombre || ''
                        )}

                        ${escapar(
                            estudiante.apellido || ''
                        )}

                        · Curso

                        ${escapar(
                            estudiante.curso || ''
                        )}

                    </p>

                </div>


                <div
                    class="${claseEstado}"
                    id="estado-alerta"
                >

                    ${escapar(
                        textoEstado
                    )}

                </div>

            </div>


            <!-- =================================================
                 INFORMACIÓN
            ================================================== -->

            <section class="grid-info">


                <div class="info-card">

                    <h3>
                        Estudiante
                    </h3>


                    <div class="dato">

                        <strong>
                            Nombre:
                        </strong>

                        <span>

                            ${escapar(
                                estudiante.nombre || ''
                            )}

                            ${escapar(
                                estudiante.apellido || ''
                            )}

                        </span>

                    </div>


                    <div class="dato">

                        <strong>
                            Curso:
                        </strong>

                        <span>

                            ${escapar(
                                estudiante.curso ||
                                'Sin curso'
                            )}

                        </span>

                    </div>

                </div>


                <div class="info-card">

                    <h3>
                        Evaluación
                    </h3>


                    <div class="dato">

                        <strong>
                            Inicio:
                        </strong>

                        <span>

                            ${escapar(
                                formatearFecha(
                                    evaluacion.fecha_inicio
                                )
                            )}

                        </span>

                    </div>


                    <div class="dato">

                        <strong>
                            Estado:
                        </strong>

                        <span>

                            ${escapar(
                                evaluacion.estado ||
                                'Sin estado'
                            )}

                        </span>

                    </div>

                </div>


                <div class="info-card">

                    <h3>
                        Análisis
                    </h3>


                    <div class="dato">

                        <strong>
                            Riesgo:
                        </strong>

                        <span>

                            <span class="badge-riesgo">

                                ${escapar(
                                    riesgo.nombre ||
                                    'Sin nivel'
                                )}

                            </span>

                        </span>

                    </div>


                    <div class="dato">

                        <strong>
                            Emoción:
                        </strong>

                        <span>

                            ${escapar(
                                analisis.emocion ||
                                'Sin emoción'
                            )}

                        </span>

                    </div>


                    <div class="dato">

                        <strong>
                            Confianza:
                        </strong>

                        <span>

                            ${escapar(
                                analisis.confianza ?? 0
                            )}%

                        </span>

                    </div>

                </div>


            </section>


            <!-- =================================================
                 RESUMEN
            ================================================== -->

            <section class="bloque">

                <h2>
                    Resumen del análisis
                </h2>


                <div class="resumen">

                    ${escapar(
                        analisis.resumen ||
                        'No hay resumen disponible.'
                    )}

                </div>

            </section>


            <!-- =================================================
                 RECURSO RECOMENDADO
            ================================================== -->

            <section class="bloque">

                <h2>
                    Recurso recomendado por SafeMind
                </h2>


                <div
                    id="recurso-recomendado"
                    class="recurso-contenedor"
                >

                    <div class="recurso-cargando">

                        Cargando recurso recomendado...

                    </div>

                </div>

            </section>


            <!-- =================================================
                 CONVERSACIÓN
            ================================================== -->

            <section class="bloque">

                <h2>
                    Conversación con SafeMind
                </h2>


                <div class="conversacion">

                    ${conversacionHTML}

                </div>

            </section>


            <!-- =================================================
                 SEGUIMIENTO
            ================================================== -->

            <section class="bloque">

                <h2>
                    Seguimiento profesional
                </h2>


                <p class="texto-ayuda">

                    ${
                        alertaCerrada
                            ? 'Este caso ya está cerrado.'
                            : 'Registra aquí la observación correspondiente al seguimiento de este caso.'
                    }

                </p>


                <textarea
                    id="observacion-seguimiento"
                    class="campo-observacion"
                    placeholder="${
                        alertaCerrada
                            ? 'El caso está cerrado.'
                            : 'Escribe la observación del seguimiento...'
                    }"
                    rows="5"
                    ${alertaCerrada ? 'disabled' : ''}
                ></textarea>


                <div class="acciones-seguimiento">


                    <button
                        type="button"
                        id="btn-seguimiento"
                        onclick="registrarSeguimiento()"
                        ${alertaCerrada ? 'disabled' : ''}
                    >

                        ${
                            alertaCerrada
                                ? 'Caso cerrado'
                                : 'Registrar seguimiento'
                        }

                    </button>


                    <button
                        type="button"
                        id="btn-cerrar-caso"
                        onclick="cerrarCaso()"
                        ${alertaCerrada ? 'disabled' : ''}
                    >

                        ${
                            alertaCerrada
                                ? 'Caso cerrado'
                                : 'Cerrar caso'
                        }

                    </button>


                    <span
                        id="mensaje-seguimiento"
                        class="mensaje-seguimiento"
                    ></span>


                    <span
                        id="mensaje-cierre"
                        class="mensaje-cierre"
                    ></span>

                </div>

            </section>


            <!-- =================================================
                 HISTORIAL
            ================================================== -->

            <section class="bloque">

                <h2>
                    Historial de seguimientos
                </h2>


                <div
                    id="historial-seguimientos"
                    class="historial-seguimientos"
                >

                    <div class="historial-vacio">

                        Cargando historial...

                    </div>

                </div>

            </section>

        `;


        /*
        |--------------------------------------------------------------------------
        | CARGAR RECURSOS
        |--------------------------------------------------------------------------
        */

        cargarRecursos(
            analisis.id
        );

    } catch (error) {

        console.error(
            'Error al cargar caso:',
            error
        );


        contenido.innerHTML = `

            <div class="error">

                No se pudo cargar la información
                del caso.

            </div>

        `;

        throw error;

    }

}


/*
|--------------------------------------------------------------------------
| CARGAR RECURSOS
|--------------------------------------------------------------------------
*/

async function cargarRecursos(id_analisis) {

    const contenedor =
        document.getElementById(
            'recurso-recomendado'
        );


    if (!contenedor) {

        return;

    }


    if (!id_analisis || Number(id_analisis) <= 0) {

        contenedor.innerHTML = `

            <div class="recurso-vacio">

                No hay un análisis asociado a este caso.

            </div>

        `;

        return;

    }


    try {

        const respuesta =
            await fetch(
                `${API_RECURSOS}?id_analisis=${Number(id_analisis)}`,
                {
                    method: 'GET',
                    cache: 'no-store',
                    credentials: 'same-origin'
                }
            );


        if (!respuesta.ok) {

            throw new Error(
                'No se pudo consultar el recurso.'
            );

        }


        const datos =
            await respuesta.json();


        if (!datos.success) {

            throw new Error(
                datos.mensaje ||
                'No se pudo cargar el recurso.'
            );

        }


        const recursos =
            Array.isArray(
                datos.recursos
            )
                ? datos.recursos
                : [];


        if (recursos.length === 0) {

            contenedor.innerHTML = `

                <div class="recurso-vacio">

                    SafeMind no recomendó un recurso
                    específico para este caso.

                </div>

            `;

            return;

        }


        contenedor.innerHTML =
            recursos.map(
                recurso => {

                    return `

                        <article
                            class="recurso-card"
                        >

                            <div
                                class="recurso-icono"
                            >
                                🌿
                            </div>


                            <div
                                class="recurso-titulo"
                            >

                                ${escapar(
                                    recurso.titulo ||
                                    'Recurso de apoyo'
                                )}

                            </div>


                            <div
                                class="recurso-descripcion"
                            >

                                ${escapar(
                                    recurso.descripcion ||
                                    'Sin descripción disponible.'
                                )}

                            </div>


                            <span
                                class="recurso-tipo"
                            >

                                ${escapar(
                                    recurso.tipo ||
                                    'Apoyo'
                                )}

                            </span>

                        </article>

                    `;

                }
            ).join('');


    } catch (error) {

        console.error(
            'Error al cargar recurso:',
            error
        );


        contenedor.innerHTML = `

            <div class="recurso-error">

                No se pudo cargar el recurso recomendado.

            </div>

        `;

    }

}


/*
|--------------------------------------------------------------------------
| CARGAR HISTORIAL
|--------------------------------------------------------------------------
*/

async function cargarSeguimientos() {

    const contenedor =
        document.getElementById(
            'historial-seguimientos'
        );


    if (!contenedor) {

        return;

    }


    try {

        contenedor.innerHTML = `

            <div class="historial-vacio">

                Cargando historial...

            </div>

        `;


        const respuesta =
            await fetch(
                `${API_HISTORIAL}?id_alerta=${ID_ALERTA}`,
                {
                    method: 'GET',
                    cache: 'no-store',
                    credentials: 'same-origin'
                }
            );


        if (!respuesta.ok) {

            throw new Error(
                'No se pudo consultar el historial.'
            );

        }


        const datos =
            await respuesta.json();


        if (!datos.success) {

            throw new Error(
                datos.mensaje ||
                'No se pudo cargar el historial.'
            );

        }


        const seguimientos =
            Array.isArray(
                datos.seguimientos
            )
                ? datos.seguimientos
                : [];


        if (seguimientos.length === 0) {

            contenedor.innerHTML = `

                <div class="historial-vacio">

                    Todavía no hay seguimientos registrados.

                </div>

            `;

            return;

        }


        contenedor.innerHTML =
            seguimientos.map(
                seguimiento => {

                    const nombrePsicologo =
                        seguimiento.psicologo?.nombre ||
                        'Psicólogo';


                    const fecha =
                        formatearFecha(
                            seguimiento.fecha
                        );


                    const observacion =
                        seguimiento.observacion ||
                        'Sin observación';


                    const estado =
                        seguimiento.estado ||
                        'Sin estado';


                    const claseEstado =
                        String(estado)
                            .toLowerCase() === 'cerrado'
                            ? 'seguimiento-estado cerrado'
                            : 'seguimiento-estado';


                    return `

                        <article
                            class="seguimiento-item"
                        >

                            <div
                                class="seguimiento-cabecera"
                            >

                                <div
                                    class="seguimiento-psicologo"
                                >

                                    ${escapar(
                                        nombrePsicologo
                                    )}

                                </div>


                                <div
                                    class="seguimiento-fecha"
                                >

                                    ${escapar(
                                        fecha
                                    )}

                                </div>

                            </div>


                            <div
                                class="seguimiento-observacion"
                            >

                                ${escapar(
                                    observacion
                                )}

                            </div>


                            <span
                                class="${claseEstado}"
                            >

                                ${escapar(
                                    estado
                                )}

                            </span>

                        </article>

                    `;

                }
            ).join('');


    } catch (error) {

        console.error(
            'Error al cargar seguimientos:',
            error
        );


        contenedor.innerHTML = `

            <div class="historial-vacio">

                No se pudo cargar el historial
                de seguimientos.

            </div>

        `;

    }

}


/*
|--------------------------------------------------------------------------
| REGISTRAR SEGUIMIENTO
|--------------------------------------------------------------------------
*/

async function registrarSeguimiento() {

    const campo =
        document.getElementById(
            'observacion-seguimiento'
        );


    const boton =
        document.getElementById(
            'btn-seguimiento'
        );


    const mensaje =
        document.getElementById(
            'mensaje-seguimiento'
        );


    if (!campo || !boton || !mensaje) {

        return;

    }


    const observacion =
        campo.value.trim();


    if (observacion === '') {

        mensaje.textContent =
            'Escribe una observación antes de continuar.';

        mensaje.className =
            'mensaje-seguimiento error';

        campo.focus();

        return;

    }


    boton.disabled = true;

    boton.textContent =
        'Registrando...';

    mensaje.textContent =
        '';

    mensaje.className =
        'mensaje-seguimiento';


    try {

        const datos =
            new FormData();


        datos.append(
            'id_alerta',
            String(ID_ALERTA)
        );


        datos.append(
            'observacion',
            observacion
        );


        const respuesta =
            await fetch(
                API_SEGUIMIENTO,
                {
                    method: 'POST',
                    body: datos,
                    credentials: 'same-origin'
                }
            );


        const resultado =
            await respuesta.json();


        if (!resultado.success) {

            throw new Error(
                resultado.mensaje ||
                'No se pudo registrar el seguimiento.'
            );

        }


        mensaje.textContent =
            'Seguimiento registrado correctamente.';

        mensaje.className =
            'mensaje-seguimiento exito';


        campo.value = '';


        const estado =
            document.getElementById(
                'estado-alerta'
            );


        if (estado) {

            estado.textContent =
                resultado.estado_alerta ||
                'En seguimiento';

            estado.className =
                'estado-alerta';

        }


        boton.disabled = false;

        boton.textContent =
            'Registrar seguimiento';


        const botonCerrar =
            document.getElementById(
                'btn-cerrar-caso'
            );


        if (botonCerrar) {

            botonCerrar.disabled = false;

        }


        await cargarSeguimientos();


    } catch (error) {

        console.error(
            'Error al registrar seguimiento:',
            error
        );


        mensaje.textContent =
            error.message ||
            'Ocurrió un error al registrar el seguimiento.';


        mensaje.className =
            'mensaje-seguimiento error';


        boton.disabled = false;

        boton.textContent =
            'Registrar seguimiento';

    }

}


/*
|--------------------------------------------------------------------------
| CERRAR CASO
|--------------------------------------------------------------------------
*/

async function cerrarCaso() {

    const boton =
        document.getElementById(
            'btn-cerrar-caso'
        );


    const mensaje =
        document.getElementById(
            'mensaje-cierre'
        );


    if (!boton || !mensaje) {

        return;

    }


    const confirmar =
        confirm(
            '¿Estás seguro de que deseas cerrar este caso?\n\n' +
            'Una vez cerrado, dejará de aparecer entre las alertas pendientes.'
        );


    if (!confirmar) {

        return;

    }


    boton.disabled = true;

    boton.textContent =
        'Cerrando...';

    mensaje.textContent =
        '';

    mensaje.className =
        'mensaje-cierre';


    try {

        const datos =
            new FormData();


        datos.append(
            'id_alerta',
            String(ID_ALERTA)
        );


        datos.append(
            'observacion',
            'Caso cerrado por seguimiento profesional.'
        );


        const respuesta =
            await fetch(
                API_CERRAR_CASO,
                {
                    method: 'POST',
                    body: datos,
                    credentials: 'same-origin'
                }
            );


        const resultado =
            await respuesta.json();


        if (!resultado.success) {

            throw new Error(
                resultado.mensaje ||
                'No se pudo cerrar el caso.'
            );

        }


        mensaje.textContent =
            'Caso cerrado correctamente.';

        mensaje.className =
            'mensaje-cierre exito';


        const estado =
            document.getElementById(
                'estado-alerta'
            );


        if (estado) {

            estado.textContent =
                'Cerrada';

            estado.className =
                'estado-alerta cerrada';

        }


        const campo =
            document.getElementById(
                'observacion-seguimiento'
            );


        const botonSeguimiento =
            document.getElementById(
                'btn-seguimiento'
            );


        if (campo) {

            campo.disabled = true;

            campo.placeholder =
                'El caso está cerrado.';

        }


        if (botonSeguimiento) {

            botonSeguimiento.disabled =
                true;

            botonSeguimiento.textContent =
                'Caso cerrado';

        }


        boton.disabled = true;

        boton.textContent =
            'Caso cerrado';


        await cargarSeguimientos();


    } catch (error) {

        console.error(
            'Error al cerrar caso:',
            error
        );


        mensaje.textContent =
            error.message ||
            'Ocurrió un error al cerrar el caso.';


        mensaje.className =
            'mensaje-cierre error';


        boton.disabled = false;

        boton.textContent =
            'Cerrar caso';

    }

}


/*
|--------------------------------------------------------------------------
| INICIAR
|--------------------------------------------------------------------------
*/

async function iniciarPagina() {

    try {

        await cargarCaso();

        await cargarSeguimientos();

    } catch (error) {

        console.error(
            'Error al iniciar la página:',
            error
        );

    }

}


iniciarPagina();

</script>

</body>

</html>