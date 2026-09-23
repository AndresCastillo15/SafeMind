<?php

session_start();

/*
|--------------------------------------------------------------------------
| Protección de acceso
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['rol']) ||
    $_SESSION['rol'] !== 'profesor'
) {
    header("Location: ../login.php?rol=profesor");
    exit();
}


/*
|--------------------------------------------------------------------------
| Datos de sesión
|--------------------------------------------------------------------------
*/

$nombre = $_SESSION['nombre'] ?? 'Docente';
$curso = $_SESSION['curso'] ?? 'Sin curso asignado';

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Panel del docente - SafeMind</title>

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
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
            --paper-dark: #EFEAE0;
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
        ========================================================= */

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

            background: var(--sage-soft);

            display: flex;

            align-items: center;

            justify-content: center;

            color: var(--sage);

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


        .orientacion a {

            text-decoration: none;

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
        ========================================================= */

        .main {

            margin-left: 263px;

            width: calc(100% - 263px);

            padding: 30px 38px 40px;

        }


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


        /* =========================================================
           TARJETAS
        ========================================================= */

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

            min-height: 135px;

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


        .icono.oro {

            background: var(--gold-soft);

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


        .enlace {

            display: block;

            margin-top: 15px;

            color: var(--gold);

            font-size: 12px;

            text-decoration: none;

            font-weight: 600;

        }


        /* =========================================================
           CONTENIDO INFERIOR
        ========================================================= */

        .contenido {

            display: grid;

            grid-template-columns: 1.6fr 1fr;

            gap: 18px;

        }


        .panel {

            background: #fff;

            border: 1px solid var(--line);

            border-radius: 15px;

            padding: 22px;

            min-height: 230px;

        }


        .panel-header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 25px;

        }


        .panel-header h2 {

            font-family: 'Fraunces', serif;

            font-size: 19px;

            color: var(--deep-dark);

        }


        .panel-header a {

            color: var(--deep);

            text-decoration: none;

            font-size: 12px;

            font-weight: 700;

        }


        .vacio {

            height: 135px;

            display: flex;

            align-items: center;

            justify-content: center;

            color: var(--ink-faint);

            font-size: 14px;

            text-align: center;

        }


        /* =========================================================
           RESPONSIVE
        ========================================================= */

        @media (max-width: 1100px) {

            .estadisticas {

                grid-template-columns: repeat(2, 1fr);

            }

            .contenido {

                grid-template-columns: 1fr;

            }

        }


        @media (max-width: 750px) {

            .sidebar {

                position: static;

                width: 100%;

                min-height: auto;

            }

            .app {

                flex-direction: column;

            }

            .main {

                margin-left: 0;

                width: 100%;

                padding: 25px 20px;

            }

            .estadisticas {

                grid-template-columns: 1fr;

            }

            .topbar {

                flex-direction: column;

                gap: 15px;

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

            <span>SafeMind</span>

        </div>


        <div class="perfil">

            <div class="avatar">

                <?php
                echo strtoupper(
                    substr($nombre, 0, 2)
                );
                ?>

            </div>

            <div class="perfil-info">

                <strong>

                    <?php
                    echo htmlspecialchars($nombre);
                    ?>

                </strong>

                <span>Profesor</span>

            </div>

        </div>


        <nav class="menu">

            <a
                href="#"
                class="activo"
            >
                🏠 &nbsp; Inicio
            </a>

            <a href="#">
                🔔 &nbsp; Estadísticas
            </a>

            <a href="#">
                👤 &nbsp; Mis estudiantes
            </a>

        </nav>


        <div class="orientacion">

            <strong>
                Equipo de orientación
            </strong>

            <p>
                Si detectas una situación que requiera
                atención, comunícala al equipo de orientación.
            </p>

            <a href="#">
                Contactar orientación
            </a>

        </div>


        <a
            href="../logout.php"
            class="cerrar"
        >
            ↪ &nbsp; Cerrar sesión
        </a>

    </aside>


    <!-- =========================================================
         CONTENIDO PRINCIPAL
    ========================================================== -->

    <main class="main">


        <!-- Encabezado -->

        <div class="topbar">

            <div class="titulo">

                <h1>
                    Panel del docente
                </h1>

                <p>
                    Acompañamiento y seguimiento de tus estudiantes
                </p>

            </div>


            <div class="fecha">

                22 de septiembre de 2026

                <span>
                    Curso:
                    <?php
                    echo htmlspecialchars($curso);
                    ?>
                </span>

            </div>

        </div>


        <!-- =====================================================
             ESTADÍSTICAS
        ====================================================== -->

        <section class="estadisticas">


            <div class="card">

                <div class="card-top">

                    <div class="icono rojo">
                        ⚠
                    </div>

                    <h3>
                        Alertas activas
                    </h3>

                </div>

                <div class="numero">
                    0
                </div>

                <div class="descripcion">
                    Requieren atención
                </div>

                <a
                    href="#"
                    class="enlace"
                >
                    Ver estadísticas →
                </a>

            </div>


            <div class="card">

                <div class="card-top">

                    <div class="icono oro">
                        ♙
                    </div>

                    <h3>
                        Estudiantes
                    </h3>

                </div>

                <div class="numero">
                    0
                </div>

                <div class="descripcion">
                    Con evaluaciones
                </div>

                <a
                    href="#"
                    class="enlace"
                >
                    Ver todos →
                </a>

            </div>


            <div class="card">

                <div class="card-top">

                    <div class="icono verde">
                        🌿
                    </div>

                    <h3>
                        Evaluaciones
                    </h3>

                </div>

                <div class="numero">
                    0
                </div>

                <div class="descripcion">
                    Realizadas
                </div>

            </div>


            <div class="card">

                <div class="card-top">

                    <div class="icono azul">
                        ✓
                    </div>

                    <h3>
                        Seguimientos
                    </h3>

                </div>

                <div class="numero">
                    0
                </div>

                <div class="descripcion">
                    Este mes
                </div>

            </div>


        </section>


        <!-- =====================================================
             PANELES
        ====================================================== -->

        <section class="contenido">


            <div class="panel">

                <div class="panel-header">

                    <h2>
                        Estado de mi curso
                    </h2>

                    <span>
                        <?php
                        echo htmlspecialchars($curso);
                        ?>
                    </span>

                </div>


                <div class="vacio">

                    Las estadísticas de tu curso aparecerán aquí.

                </div>

            </div>


            <div class="panel">

                <div class="panel-header">

                    <h2>
                        Actividad reciente
                    </h2>

                </div>


                <div class="vacio">

                    No hay actividad reciente.

                </div>

            </div>


        </section>


    </main>

</div>

</body>

</html>