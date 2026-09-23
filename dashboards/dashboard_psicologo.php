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
| DATOS DE SESIÓN
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

    <title>Panel del orientador - SafeMind</title>

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


        /*
        |--------------------------------------------------------------------------
        | BODY
        |--------------------------------------------------------------------------
        */

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


        /*
        |--------------------------------------------------------------------------
        | SIDEBAR
        |--------------------------------------------------------------------------
        */

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


        /*
        |--------------------------------------------------------------------------
        | MENÚ
        |--------------------------------------------------------------------------
        */

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


        /*
        |--------------------------------------------------------------------------
        | BLOQUE INFORMATIVO
        |--------------------------------------------------------------------------
        */

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


        /*
        |--------------------------------------------------------------------------
        | CONTENIDO PRINCIPAL
        |--------------------------------------------------------------------------
        */

        .main {

            margin-left: 263px;

            width: calc(100% - 263px);

            padding: 30px 38px 45px;

        }


        /*
        |--------------------------------------------------------------------------
        | TOPBAR
        |--------------------------------------------------------------------------
        */

        .topbar {

            display: flex;

            justify-content: space-between;

            align-items: flex-start;

            margin-bottom: 28px;

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


        /*
        |--------------------------------------------------------------------------
        | TARJETAS DE ESTADÍSTICAS
        |--------------------------------------------------------------------------
        */

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


        .card-top {

            display: flex;

            align-items: center;

            gap: 10px;

            margin-bottom: 17px;

        }


        .icono {

            width: 38px;

            height: 38px;

            border-radius: 10px;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 18px;

        }


        .icono.rojo {

            background: var(--red-soft);

        }


        .icono.naranja {

            background: var(--orange-soft);

        }


        .icono.verde {

            background: var(--sage-soft);

        }


        .icono.azul {

            background: var(--blue-soft);

        }


        .card h3 {

            font-size: 14px;

            font-weight: 600;

        }


        .numero {

            font-family: 'Fraunces', serif;

            font-size: 31px;

            color: var(--deep-dark);

            margin-bottom: 4px;

        }


        .descripcion {

            color: var(--ink-faint);

            font-size: 12px;

        }


        /*
        |--------------------------------------------------------------------------
        | GRID CENTRAL
        |--------------------------------------------------------------------------
        */

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


        .panel-header h2 {

            font-family: 'Fraunces', serif;

            font-size: 20px;

            color: var(--deep-dark);

        }


        .panel-header span {

            color: var(--ink-faint);

            font-size: 12px;

        }


        /*
        |--------------------------------------------------------------------------
        | DISTRIBUCIÓN DE RIESGO
        |--------------------------------------------------------------------------
        */

        .riesgo-lista {

            display: flex;

            flex-direction: column;

            gap: 15px;

        }


        .riesgo-item {

            display: grid;

            grid-template-columns: 100px 1fr 35px;

            align-items: center;

            gap: 12px;

        }


        .riesgo-nombre {

            font-size: 13px;

            color: var(--ink-soft);

        }


        .barra {

            height: 9px;

            background: #EEEAE2;

            border-radius: 20px;

            overflow: hidden;

        }


        .barra-progreso {

            height: 100%;

            width: 0%;

            border-radius: 20px;

            transition: width 0.5s ease;

        }


        .barra-verde {

            background: var(--sage);

        }


        .barra-amarilla {

            background: var(--gold);

        }


        .barra-naranja {

            background: var(--orange);

        }


        .barra-roja {

            background: var(--red);

        }


        .riesgo-total {

            text-align: right;

            font-size: 13px;

            font-weight: 700;

            color: var(--deep-dark);

        }


        /*
        |--------------------------------------------------------------------------
        | ESTADO DEL SISTEMA
        |--------------------------------------------------------------------------
        */

        .estado-sistema {

            display: flex;

            flex-direction: column;

            gap: 14px;

        }


        .estado-item {

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding: 12px 0;

            border-bottom: 1px solid var(--line);

        }


        .estado-item:last-child {

            border-bottom: none;

        }


        .estado-item span {

            font-size: 13px;

            color: var(--ink-soft);

        }


        .estado-ok {

            color: var(--sage);

            font-size: 12px;

            font-weight: 700;

        }


        /*
        |--------------------------------------------------------------------------
        | ALERTAS
        |--------------------------------------------------------------------------
        */

        #alertas-container {

            display: flex;

            flex-direction: column;

            gap: 12px;

        }


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


        .alerta:hover {

            border-color: #D5CDC0;

            transform: translateY(-1px);

        }


        .alerta-info {

            display: flex;

            align-items: center;

            gap: 14px;

            min-width: 0;

        }


        .riesgo {

            width: 11px;

            height: 50px;

            border-radius: 10px;

            flex-shrink: 0;

        }


        .riesgo.naranja {

            background: #D87A3D;

        }


        .riesgo.rojo {

            background: var(--red);

        }


        .alerta-texto {

            min-width: 0;

        }


        .alerta-texto h3 {

            font-size: 15px;

            margin-bottom: 4px;

        }


        .alerta-texto p {

            font-size: 12px;

            color: var(--ink-soft);

            margin-bottom: 4px;

        }


        .alerta-texto small {

            display: block;

            font-size: 11px;

            color: var(--ink-faint);

            line-height: 1.5;

        }


        .alerta-meta {

            text-align: right;

            flex-shrink: 0;

        }


        .badge {

            display: inline-block;

            padding: 5px 9px;

            border-radius: 20px;

            font-size: 11px;

            font-weight: 700;

            margin-bottom: 7px;

        }


        .badge.naranja {

            background: var(--orange-soft);

            color: #A75223;

        }


        .badge.rojo {

            background: var(--red-soft);

            color: #963B2A;

        }


        .ver-caso {

            border: none;

            background: transparent;

            color: var(--deep);

            cursor: pointer;

            font-family: 'Karla', sans-serif;

            font-size: 12px;

            font-weight: 700;

        }


        .ver-caso:hover {

            text-decoration: underline;

        }


        /*
        |--------------------------------------------------------------------------
        | ESTADOS
        |--------------------------------------------------------------------------
        */

        .estado {

            min-height: 130px;

            display: flex;

            align-items: center;

            justify-content: center;

            text-align: center;

            color: var(--ink-faint);

            font-size: 14px;

        }


        .estado.error {

            color: var(--red);

        }


        /*
        |--------------------------------------------------------------------------
        | RESPONSIVE
        |--------------------------------------------------------------------------
        */

        @media (max-width: 1150px) {

            .estadisticas {

                grid-template-columns: repeat(2, 1fr);

            }


            .contenido-grid {

                grid-template-columns: 1fr;

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


            .topbar {

                flex-direction: column;

                gap: 15px;

            }


            .fecha {

                text-align: left;

            }

        }


        @media (max-width: 600px) {

            .estadisticas {

                grid-template-columns: 1fr;

            }


            .alerta {

                flex-direction: column;

                align-items: flex-start;

            }


            .alerta-meta {

                text-align: left;

            }


            .riesgo-item {

                grid-template-columns: 85px 1fr 25px;

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

            <a
                href="#"
                class="activo"
            >
                🏠 &nbsp; Inicio
            </a>


            <a href="#alertas">

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
                Panel profesional para el seguimiento
                y atención de situaciones detectadas.
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


        <!-- =====================================================
             ENCABEZADO
        ====================================================== -->

        <div class="topbar">

            <div class="titulo">

                <h1>
                    Panel del orientador
                </h1>


                <p>
                    Seguimiento y atención de situaciones detectadas
                </p>

            </div>


            <div class="fecha">

                <?php

                echo date('d/m/Y');

                ?>


                <span>
                    Información actualizada
                </span>

            </div>

        </div>


        <!-- =====================================================
             ESTADÍSTICAS
        ====================================================== -->

        <section class="estadisticas">


            <!-- Alertas pendientes -->

            <div class="card">

                <div class="card-top">

                    <div class="icono rojo">
                        ⚠
                    </div>


                    <h3>
                        Alertas pendientes
                    </h3>

                </div>


                <div
                    class="numero"
                    id="total-pendientes"
                >
                    —
                </div>


                <div class="descripcion">
                    Requieren revisión
                </div>

            </div>


            <!-- En seguimiento -->

            <div class="card">

                <div class="card-top">

                    <div class="icono naranja">
                        ●
                    </div>


                    <h3>
                        En seguimiento
                    </h3>

                </div>


                <div
                    class="numero"
                    id="total-seguimiento"
                >
                    —
                </div>


                <div class="descripcion">
                    Casos activos
                </div>

            </div>


            <!-- Casos cerrados -->

            <div class="card">

                <div class="card-top">

                    <div class="icono verde">
                        ✓
                    </div>


                    <h3>
                        Casos cerrados
                    </h3>

                </div>


                <div
                    class="numero"
                    id="total-cerradas"
                >
                    —
                </div>


                <div class="descripcion">
                    Casos finalizados
                </div>

            </div>


            <!-- Estudiantes -->

            <div class="card">

                <div class="card-top">

                    <div class="icono azul">
                        ♙
                    </div>


                    <h3>
                        Estudiantes evaluados
                    </h3>

                </div>


                <div
                    class="numero"
                    id="total-estudiantes"
                >
                    —
                </div>


                <div class="descripcion">
                    Con evaluaciones registradas
                </div>

            </div>


        </section>


        <!-- =====================================================
             DISTRIBUCIÓN Y ESTADO
        ====================================================== -->

        <section class="contenido-grid">


            <!-- Distribución -->

            <div class="panel">

                <div class="panel-header">

                    <h2>
                        Distribución por nivel de riesgo
                    </h2>


                    <span id="total-evaluaciones">
                        — evaluaciones
                    </span>

                </div>


                <div class="riesgo-lista">


                    <!-- Nivel 1 -->

                    <div class="riesgo-item">

                        <div class="riesgo-nombre">
                            Nivel 1 · Bien
                        </div>


                        <div class="barra">

                            <div
                                id="barra-nivel-1"
                                class="barra-progreso barra-verde"
                            ></div>

                        </div>


                        <div
                            id="nivel-1"
                            class="riesgo-total"
                        >
                            0
                        </div>

                    </div>


                    <!-- Nivel 2 -->

                    <div class="riesgo-item">

                        <div class="riesgo-nombre">
                            Nivel 2 · Alerta
                        </div>


                        <div class="barra">

                            <div
                                id="barra-nivel-2"
                                class="barra-progreso barra-amarilla"
                            ></div>

                        </div>


                        <div
                            id="nivel-2"
                            class="riesgo-total"
                        >
                            0
                        </div>

                    </div>


                    <!-- Nivel 3 -->

                    <div class="riesgo-item">

                        <div class="riesgo-nombre">
                            Nivel 3 · Riesgo
                        </div>


                        <div class="barra">

                            <div
                                id="barra-nivel-3"
                                class="barra-progreso barra-naranja"
                            ></div>

                        </div>


                        <div
                            id="nivel-3"
                            class="riesgo-total"
                        >
                            0
                        </div>

                    </div>


                    <!-- Nivel 4 -->

                    <div class="riesgo-item">

                        <div class="riesgo-nombre">
                            Nivel 4 · Crisis
                        </div>


                        <div class="barra">

                            <div
                                id="barra-nivel-4"
                                class="barra-progreso barra-roja"
                            ></div>

                        </div>


                        <div
                            id="nivel-4"
                            class="riesgo-total"
                        >
                            0
                        </div>

                    </div>


                </div>

            </div>


            <!-- Estado del sistema -->

            <div class="panel">

                <div class="panel-header">

                    <h2>
                        Resumen del sistema
                    </h2>

                </div>


                <div class="estado-sistema">


                    <div class="estado-item">

                        <span>
                            Sistema SafeMind
                        </span>

                        <strong
                            class="estado-ok"
                        >
                            Activo
                        </strong>

                    </div>


                    <div class="estado-item">

                        <span>
                            Evaluaciones
                        </span>

                        <strong
                            class="estado-ok"
                            id="estado-evaluaciones"
                        >
                            —
                        </strong>

                    </div>


                    <div class="estado-item">

                        <span>
                            Seguimientos activos
                        </span>

                        <strong
                            class="estado-ok"
                            id="estado-seguimientos"
                        >
                            —
                        </strong>

                    </div>


                    <div class="estado-item">

                        <span>
                            Casos cerrados
                        </span>

                        <strong
                            class="estado-ok"
                            id="estado-cerrados"
                        >
                            —
                        </strong>

                    </div>


                </div>

            </div>


        </section>


        <!-- =====================================================
             ALERTAS PENDIENTES
        ====================================================== -->

        <section
            id="alertas"
            class="panel"
        >

            <div class="panel-header">

                <h2>
                    Alertas pendientes
                </h2>


                <span id="ultima-actualizacion">
                    Cargando...
                </span>

            </div>


            <div id="alertas-container">

                <div class="estado">

                    Cargando alertas...

                </div>

            </div>

        </section>


    </main>

</div>


<script>

/*
|--------------------------------------------------------------------------
| APIs
|--------------------------------------------------------------------------
*/

const API_ESTADISTICAS =
    '../API/seguimiento/estadisticas_psicologo.php';

const API_ALERTAS =
    '../API/seguimiento/obtener_alertas.php';


/*
|--------------------------------------------------------------------------
| ELEMENTOS
|--------------------------------------------------------------------------
*/

const totalPendientes =
    document.getElementById(
        'total-pendientes'
    );

const totalSeguimiento =
    document.getElementById(
        'total-seguimiento'
    );

const totalCerradas =
    document.getElementById(
        'total-cerradas'
    );

const totalEstudiantes =
    document.getElementById(
        'total-estudiantes'
    );

const totalEvaluaciones =
    document.getElementById(
        'total-evaluaciones'
    );

const estadoEvaluaciones =
    document.getElementById(
        'estado-evaluaciones'
    );

const estadoSeguimientos =
    document.getElementById(
        'estado-seguimientos'
    );

const estadoCerrados =
    document.getElementById(
        'estado-cerrados'
    );

const ultimaActualizacion =
    document.getElementById(
        'ultima-actualizacion'
    );

const alertasContainer =
    document.getElementById(
        'alertas-container'
    );


/*
|--------------------------------------------------------------------------
| SEGURIDAD PARA HTML
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

        return '';

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
| CARGAR ESTADÍSTICAS
|--------------------------------------------------------------------------
*/

async function cargarEstadisticas() {

    try {

        const respuesta =
            await fetch(
                API_ESTADISTICAS,
                {
                    method: 'GET',
                    cache: 'no-store',
                    credentials: 'same-origin'
                }
            );


        if (!respuesta.ok) {

            throw new Error(
                'No se pudieron obtener las estadísticas.'
            );

        }


        const datos =
            await respuesta.json();


        if (!datos.success) {

            throw new Error(
                datos.mensaje ||
                'La API de estadísticas devolvió un error.'
            );

        }


        const estadisticas =
            datos.estadisticas;


        const alertas =
            estadisticas.alertas;


        const estudiantes =
            estadisticas.estudiantes;


        const evaluaciones =
            estadisticas.evaluaciones;


        const riesgo =
            estadisticas.riesgo;


        /*
        |--------------------------------------------------------------------------
        | TARJETAS
        |--------------------------------------------------------------------------
        */

        totalPendientes.textContent =
            alertas.pendientes;


        totalSeguimiento.textContent =
            alertas.en_seguimiento;


        totalCerradas.textContent =
            alertas.cerradas;


        totalEstudiantes.textContent =
            estudiantes.evaluados;


        /*
        |--------------------------------------------------------------------------
        | EVALUACIONES
        |--------------------------------------------------------------------------
        */

        totalEvaluaciones.textContent =
            `${evaluaciones.total} evaluaciones`;


        estadoEvaluaciones.textContent =
            evaluaciones.total;


        estadoSeguimientos.textContent =
            alertas.en_seguimiento;


        estadoCerrados.textContent =
            alertas.cerradas;


        /*
        |--------------------------------------------------------------------------
        | DISTRIBUCIÓN DE RIESGO
        |--------------------------------------------------------------------------
        */

        const total =
            evaluaciones.total > 0
                ? evaluaciones.total
                : 1;


        const porcentaje1 =
            (riesgo.nivel_1 / total) * 100;


        const porcentaje2 =
            (riesgo.nivel_2 / total) * 100;


        const porcentaje3 =
            (riesgo.nivel_3 / total) * 100;


        const porcentaje4 =
            (riesgo.nivel_4 / total) * 100;


        document.getElementById(
            'nivel-1'
        ).textContent =
            riesgo.nivel_1;


        document.getElementById(
            'nivel-2'
        ).textContent =
            riesgo.nivel_2;


        document.getElementById(
            'nivel-3'
        ).textContent =
            riesgo.nivel_3;


        document.getElementById(
            'nivel-4'
        ).textContent =
            riesgo.nivel_4;


        document.getElementById(
            'barra-nivel-1'
        ).style.width =
            `${porcentaje1}%`;


        document.getElementById(
            'barra-nivel-2'
        ).style.width =
            `${porcentaje2}%`;


        document.getElementById(
            'barra-nivel-3'
        ).style.width =
            `${porcentaje3}%`;


        document.getElementById(
            'barra-nivel-4'
        ).style.width =
            `${porcentaje4}%`;

    } catch (error) {

        console.error(
            'Error de estadísticas:',
            error
        );


        totalPendientes.textContent =
            '—';

        totalSeguimiento.textContent =
            '—';

        totalCerradas.textContent =
            '—';

        totalEstudiantes.textContent =
            '—';

    }

}


/*
|--------------------------------------------------------------------------
| CARGAR ALERTAS
|--------------------------------------------------------------------------
*/

async function cargarAlertas() {

    try {

        alertasContainer.innerHTML = `

            <div class="estado">

                Cargando alertas...

            </div>

        `;


        const respuesta =
            await fetch(
                API_ALERTAS,
                {
                    method: 'GET',
                    cache: 'no-store',
                    credentials: 'same-origin'
                }
            );


        if (!respuesta.ok) {

            throw new Error(
                'No se pudo consultar la API de alertas.'
            );

        }


        const datos =
            await respuesta.json();


        if (!datos.success) {

            throw new Error(
                datos.mensaje ||
                'La API de alertas devolvió un error.'
            );

        }


        const alertas =
            Array.isArray(
                datos.alertas
            )
                ? datos.alertas
                : [];


        /*
        |--------------------------------------------------------------------------
        | ACTUALIZACIÓN
        |--------------------------------------------------------------------------
        */

        const ahora =
            new Date();


        ultimaActualizacion.textContent =
            `Actualizado ${ahora.toLocaleTimeString(
                'es-CO',
                {
                    hour: '2-digit',
                    minute: '2-digit'
                }
            )}`;


        /*
        |--------------------------------------------------------------------------
        | SIN ALERTAS
        |--------------------------------------------------------------------------
        */

        if (alertas.length === 0) {

            alertasContainer.innerHTML = `

                <div class="estado">

                    No hay alertas pendientes
                    en este momento.

                </div>

            `;

            return;

        }


        /*
        |--------------------------------------------------------------------------
        | RENDERIZAR ALERTAS
        |--------------------------------------------------------------------------
        */

        alertasContainer.innerHTML =
            alertas.map(
                alerta => {

                    const nivel =
                        Number(
                            alerta.riesgo?.id_nivel
                        );


                    const claseRiesgo =
                        nivel === 4
                            ? 'rojo'
                            : 'naranja';


                    const nombre =
                        `${alerta.estudiante?.nombre ?? ''}
                        ${alerta.estudiante?.apellido ?? ''}`
                        .trim();


                    const curso =
                        alerta.estudiante?.curso ||
                        'Sin curso';


                    const emocion =
                        alerta.analisis?.emocion ||
                        'Sin emoción registrada';


                    const resumen =
                        alerta.analisis?.resumen ||
                        'Sin resumen disponible';


                    const nivelNombre =
                        alerta.riesgo?.nombre ||
                        'Riesgo';


                    const fecha =
                        formatearFecha(
                            alerta.fecha
                        );


                    return `

                        <article
                            class="alerta"
                        >


                            <div
                                class="alerta-info"
                            >

                                <div
                                    class="riesgo ${claseRiesgo}"
                                ></div>


                                <div
                                    class="alerta-texto"
                                >

                                    <h3>

                                        ${escapar(
                                            nombre
                                        )}

                                    </h3>


                                    <p>

                                        Curso:
                                        ${escapar(
                                            curso
                                        )}

                                        ·

                                        ${escapar(
                                            emocion
                                        )}

                                    </p>


                                    <small>

                                        ${escapar(
                                            resumen
                                        )}

                                    </small>

                                </div>

                            </div>


                            <div
                                class="alerta-meta"
                            >

                                <div
                                    class="badge ${claseRiesgo}"
                                >

                                    ${escapar(
                                        nivelNombre
                                    )}

                                </div>


                                <div>

                                    <small>

                                        ${escapar(
                                            fecha
                                        )}

                                    </small>

                                </div>


                                <div>

                                    <button
                                        type="button"
                                        class="ver-caso"
                                        onclick="verCaso(${Number(
                                            alerta.id_alerta
                                        )})"
                                    >

                                        Ver caso →

                                    </button>

                                </div>

                            </div>


                        </article>

                    `;

                }
            ).join('');


    } catch (error) {

        console.error(
            'Error de alertas:',
            error
        );


        alertasContainer.innerHTML = `

            <div class="estado error">

                No se pudieron cargar
                las alertas.

            </div>

        `;

    }

}


/*
|--------------------------------------------------------------------------
| VER CASO
|--------------------------------------------------------------------------
*/

function verCaso(id_alerta) {

    window.location.href =
        `detalle_alerta.php?id_alerta=${id_alerta}`;

}


/*
|--------------------------------------------------------------------------
| INICIALIZAR DASHBOARD
|--------------------------------------------------------------------------
*/

async function iniciarDashboard() {

    await Promise.all([
        cargarEstadisticas(),
        cargarAlertas()
    ]);

}


iniciarDashboard();

</script>


</body>

</html>