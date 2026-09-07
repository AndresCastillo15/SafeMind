<?php
?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>SafeMind</title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            min-height: 100vh;
            background: #f5f7fa;
        }

        .contenedor {
            display: flex;
            min-height: 100vh;
        }

        /* PANEL IZQUIERDO */

        .panel {
            width: 370px;
            background: white;
            padding: 35px 30px;

            text-align: center;

            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.15);

            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .logo {
            width: 150px;
            height: 150px;
            object-fit: contain;
            margin-bottom: 15px;
        }

        .titulo {
            font-size: 32px;
            color: #173b63;
            margin-bottom: 8px;
        }

        .subtitulo {
            font-size: 15px;
            color: #666;
            line-height: 1.4;
            margin-bottom: 30px;
        }

        /* BOTONES */

        .botones {
            width: 100%;

            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 15px;
        }

        .boton {
            text-decoration: none;

            color: white;

            min-height: 105px;

            border-radius: 10px;

            display: flex;

            flex-direction: column;

            justify-content: center;

            align-items: center;

            font-weight: bold;

            transition: 0.2s;
        }

        .boton:hover {
            transform: translateY(-4px);

            box-shadow: 0 5px 12px rgba(0, 0, 0, 0.20);
        }

        .icono {
            font-size: 32px;

            margin-bottom: 8px;
        }

        /* COLORES */

        .admin {
            background: #2878bd;
        }

        .profesor {
            background: #15965b;
        }

        .estudiante {
            background: #e7a91b;
        }

        .padres {
            background: #b5222d;
        }

        /* REGISTRO */

        .registro {
            margin-top: 25px;

            font-size: 14px;

            color: #555;
        }

        .registro a {
            color: #2878bd;

            font-weight: bold;

            text-decoration: none;
        }

        .registro a:hover {
            text-decoration: underline;
        }

        /* PIE */

        .pie {
            margin-top: auto;

            padding-top: 30px;

            color: #777;

            font-size: 12px;

            line-height: 1.5;
        }

        /* PARTE DERECHA */

        .principal {
            flex: 1;

            min-height: 100vh;

            background-image:
                linear-gradient(
                    rgba(20, 90, 130, 0.50),
                    rgba(20, 90, 130, 0.50)
                ),
                url("graficos/fondo.jpg");

            background-size: cover;

            background-position: center;

            display: flex;

            justify-content: center;

            align-items: center;
        }

        .contenido {
            max-width: 700px;

            padding: 40px;

            color: white;

            text-align: center;
        }

        .contenido h1 {
            font-size: 65px;

            margin-bottom: 20px;
        }

        .contenido p {
            font-size: 21px;

            line-height: 1.6;
        }

        /* RESPONSIVE */

        @media (max-width: 850px) {

            .contenedor {
                flex-direction: column;
            }

            .panel {
                width: 100%;
            }

            .principal {
                min-height: 500px;
            }

            .contenido h1 {
                font-size: 45px;
            }

        }

    </style>

</head>


<body>

<div class="contenedor">


    <!-- PANEL IZQUIERDO -->

    <aside class="panel">

        <!--
            IMPORTANTE:
            Si tu logo tiene otro nombre,
            cambia solamente esta ruta.
        -->

        <img
            src="graficos/logo.png"
            class="logo"
            alt="Logo SafeMind"
        >


        <h1 class="titulo">
            SafeMind
        </h1>


        <p class="subtitulo">

            Plataforma de acompañamiento
            y bienestar estudiantil

        </p>


        <!-- BOTONES DE ACCESO -->

        <div class="botones">


            <!-- ADMINISTRADOR -->

            <a
                href="login.php?rol=admin"
                class="boton admin"
            >

                <span class="icono">
                    ⚙
                </span>

                ADMIN

            </a>


            <!-- PROFESOR -->

            <a
                href="login.php?rol=profesor"
                class="boton profesor"
            >

                <span class="icono">
                    ▣
                </span>

                PROFESOR

            </a>


            <!-- ESTUDIANTE -->

            <a
                href="login.php?rol=estudiante"
                class="boton estudiante"
            >

                <span class="icono">
                    ●
                </span>

                ESTUDIANTE

            </a>


            <!-- PADRES -->

            <a
                href="login.php?rol=padres"
                class="boton padres"
            >

                <span class="icono">
                    ♙
                </span>

                PADRES

            </a>


        </div>


        <!-- REGISTRO DE ESTUDIANTE -->

        <div class="registro">

            ¿Eres estudiante y no tienes cuenta?

            <a href="registro.php">
                Regístrate aquí
            </a>

        </div>


        <!-- PIE -->

        <div class="pie">

            <p>
                SafeMind
            </p>

            <p>
                Sistema de acompañamiento
                al bienestar estudiantil
            </p>

        </div>


    </aside>


    <!-- PARTE DERECHA -->

    <main class="principal">


        <div class="contenido">

            <h1>
                SafeMind
            </h1>


            <p>

                Un espacio seguro para acompañar,
                escuchar y cuidar el bienestar
                de nuestra comunidad educativa.

            </p>

        </div>


    </main>


</div>

</body>

</html>