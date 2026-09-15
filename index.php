<?php
?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>SafeMind - Bienestar estudiantil</title>

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
            --paper-line: #E7E1D4;

            --ink: #1E2B2F;
            --ink-soft: #5B6B6F;

            --deep: #17455A;
            --deep-dark: #0E2E3D;
            --deep-light: #3A7691;

            --sage: #4F7F62;
            --sage-light: #DCE9DF;

            --gold: #C8922F;
            --gold-light: #F3E4C4;

            --radius: 16px;
            --shadow: 0 10px 28px rgba(14, 46, 61, 0.14);
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Karla', sans-serif;
            min-height: 100vh;
            background: var(--paper);
            color: var(--ink);
        }

        h1, h2, .titulo {
            font-family: 'Fraunces', serif;
        }

        a {
            font-family: inherit;
        }

        .contenedor {
            display: flex;
            min-height: 100vh;
        }

        /* ===================== PANEL IZQUIERDO ===================== */

        .panel {
            width: 400px;
            flex-shrink: 0;
            background: var(--paper);
            padding: 44px 38px 32px;

            display: flex;
            flex-direction: column;

            border-right: 1px solid var(--paper-line);
        }

        .marca {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 6px;

            opacity: 0;
            animation: aparecer 0.6s ease forwards;
        }

        .logo {
            width: 52px;
            height: 52px;
            object-fit: contain;
            border-radius: 12px;
            flex-shrink: 0;
        }

        .titulo {
            font-size: 26px;
            font-weight: 560;
            color: var(--deep-dark);
            line-height: 1.1;
        }

        .subtitulo {
            font-size: 15px;
            color: var(--ink-soft);
            line-height: 1.55;
            margin: 18px 0 36px;
            max-width: 34ch;

            opacity: 0;
            animation: aparecer 0.6s ease 0.1s forwards;
        }

        /* ===================== TARJETAS DE ROL ===================== */

        .roles {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .rol-card {
            display: flex;
            align-items: flex-start;
            gap: 14px;

            text-decoration: none;
            color: var(--ink);

            padding: 16px 18px;
            border-radius: var(--radius);
            border: 1px solid var(--paper-line);
            background: #fff;

            opacity: 0;
            animation: aparecer 0.6s ease forwards;

            transition: border-color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
        }

        .rol-card:nth-child(1) { animation-delay: 0.2s; }
        .rol-card:nth-child(2) { animation-delay: 0.3s; }
        .rol-card:nth-child(3) { animation-delay: 0.4s; }

        .rol-card:hover,
        .rol-card:focus-visible {
            transform: translateY(-3px);
            box-shadow: var(--shadow);
        }

        .rol-card:focus-visible {
            outline: 2px solid var(--deep-light);
            outline-offset: 2px;
        }

        .rol-icono {
            width: 40px;
            height: 40px;
            flex-shrink: 0;
            border-radius: 10px;

            display: flex;
            align-items: center;
            justify-content: center;

            transition: transform 0.25s ease;
        }

        .rol-icono svg {
            width: 20px;
            height: 20px;
            stroke-width: 1.7;
        }

        .rol-card:hover .rol-icono {
            transform: scale(1.08) rotate(-4deg);
        }

        .rol-card.admin .rol-icono {
            background: var(--deep);
            color: #fff;
        }
        .rol-card.admin:hover {
            border-color: var(--deep);
        }

        .rol-card.profesor .rol-icono {
            background: var(--sage);
            color: #fff;
        }
        .rol-card.profesor:hover {
            border-color: var(--sage);
        }

        .rol-card.estudiante .rol-icono {
            background: var(--gold);
            color: #fff;
        }
        .rol-card.estudiante:hover {
            border-color: var(--gold);
        }

        .rol-nombre {
            font-size: 15.5px;
            font-weight: 700;
            margin-bottom: 2px;
        }

        .rol-desc {
            font-size: 13px;
            color: var(--ink-soft);
            line-height: 1.4;
        }

        /* ===================== PIE ===================== */

        .pie {
            margin-top: auto;
            padding-top: 30px;
        }

        .pie-nota {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12.5px;
            color: var(--ink-soft);
            margin-bottom: 10px;
        }

        .pie-nota svg {
            width: 15px;
            height: 15px;
            stroke: var(--sage);
            flex-shrink: 0;
        }

        .pie p.copy {
            font-size: 12px;
            color: #9AA6A9;
        }

        /* ===================== PARTE DERECHA ===================== */

        .principal {
            flex: 1;
            min-height: 100vh;
            position: relative;
            overflow: hidden;

            background: radial-gradient(120% 140% at 15% 15%, var(--deep-light) 0%, transparent 55%),
                        linear-gradient(165deg, var(--deep) 0%, var(--deep-dark) 70%);

            display: flex;
            justify-content: center;
            align-items: center;
        }

        .mancha {
            position: absolute;
            border-radius: 50%;
            filter: blur(2px);
            opacity: 0.5;
        }

        .mancha.sage {
            width: 340px;
            height: 340px;
            background: radial-gradient(circle at 35% 35%, rgba(79, 127, 98, 0.55), transparent 70%);
            top: -80px;
            right: -60px;
        }

        .mancha.gold {
            width: 260px;
            height: 260px;
            background: radial-gradient(circle at 40% 40%, rgba(200, 146, 47, 0.35), transparent 70%);
            bottom: -60px;
            left: -40px;
        }

        .respirar {
            position: absolute;
            width: 420px;
            height: 420px;
            left: 50%;
            top: 46%;
            transform: translate(-50%, -50%);
            pointer-events: none;
        }

        .respirar circle {
            fill: none;
            stroke: rgba(255, 255, 255, 0.45);
            stroke-width: 1;
            transform-origin: center;
            animation: respirar 5.5s ease-in-out infinite;
        }

        .respirar circle:nth-child(1) { animation-delay: 0s; }
        .respirar circle:nth-child(2) { animation-delay: 0.5s; stroke: rgba(255,255,255,0.3); }
        .respirar circle:nth-child(3) { animation-delay: 1s; stroke: rgba(255,255,255,0.18); }

        .contenido {
            position: relative;
            z-index: 2;
            max-width: 620px;
            padding: 40px;
            color: #fff;
            text-align: center;

            opacity: 0;
            animation: aparecer 0.7s ease 0.3s forwards;
        }

        .contenido h1 {
            font-size: 48px;
            font-weight: 560;
            line-height: 1.18;
            margin-bottom: 22px;
        }

        .contenido p {
            font-size: 17.5px;
            line-height: 1.65;
            color: rgba(255, 255, 255, 0.82);
            max-width: 52ch;
            margin: 0 auto;
        }

        /* ===================== ANIMACIONES ===================== */

        @keyframes aparecer {
            from {
                opacity: 0;
                transform: translateY(12px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes respirar {
            0%, 100% {
                transform: scale(0.85);
                opacity: 0.35;
            }
            50% {
                transform: scale(1.05);
                opacity: 0.85;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .marca, .subtitulo, .rol-card, .contenido {
                animation: none;
                opacity: 1;
            }
            .respirar circle {
                animation: none;
                opacity: 0.4;
            }
        }

        /* ===================== RESPONSIVE ===================== */

        @media (max-width: 900px) {

            .contenedor {
                flex-direction: column;
            }

            .panel {
                width: 100%;
                border-right: none;
                border-bottom: 1px solid var(--paper-line);
            }

            .principal {
                min-height: 420px;
                padding: 40px 20px;
            }

            .contenido h1 {
                font-size: 34px;
            }

            .respirar {
                width: 300px;
                height: 300px;
            }
        }

        @media (max-width: 480px) {

            .panel {
                padding: 32px 22px 26px;
            }

            .rol-card {
                padding: 14px;
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

        <div class="marca">

            <img
                src="img/logo.png"
                class="logo"
                alt="Logo SafeMind"
                style="width: 100px; height: auto;"
            >

            <h1 class="titulo">SafeMind</h1>

        </div>

        <p class="subtitulo">
            Acompañamos el bienestar emocional de cada
            estudiante, con apoyo profesional disponible
            cuando más se necesita.
        </p>


        <!-- ACCESO POR ROL -->

        <nav class="roles">

            <a href="login.php?rol=admin" class="rol-card admin">
                <span class="rol-icono">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 3l7 3v5c0 5-3.3 8.4-7 10-3.7-1.6-7-5-7-10V6l7-3z"/>
                        <path d="M9 12l2 2 4-4"/>
                    </svg>
                </span>
                <span class="rol-texto">
                    <span class="rol-nombre">Administrador</span>
                    <span class="rol-desc">Gestiona usuarios, reportes y configuración</span>
                </span>
            </a>

            <a href="login.php?rol=profesor" class="rol-card profesor">
                <span class="rol-icono">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 6c2-1.2 5-1.4 7 0v12.5c-2-1.4-5-1.2-7 0V6z"/>
                        <path d="M21 6c-2-1.2-5-1.4-7 0v12.5c2-1.4 5-1.2 7 0V6z"/>
                    </svg>
                </span>
                <span class="rol-texto">
                    <span class="rol-nombre">Profesor</span>
                    <span class="rol-desc">Da seguimiento al bienestar de tus estudiantes</span>
                </span>
            </a>

            <a href="login.php?rol=estudiante" class="rol-card estudiante">
                <span class="rol-icono">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 21v-8.5"/>
                        <path d="M12 13c-4.2 0-7.5-2.8-7.5-7 4.2 0 7.5 2.1 7.5 5.2"/>
                        <path d="M12 11.4c0-3.4 3.1-5.9 7.5-5.9 0 4.3-3.3 7.4-7.5 6.5"/>
                    </svg>
                </span>
                <span class="rol-texto">
                    <span class="rol-nombre">Estudiante</span>
                    <span class="rol-desc">Encuentra apoyo, recursos y un espacio para hablar</span>
                </span>
            </a>

        </nav>


        <!-- PIE -->

        <div class="pie">

            <p class="pie-nota">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 3l7 3v5c0 5-3.3 8.4-7 10-3.7-1.6-7-5-7-10V6l7-3z"/>
                </svg>
                Tus datos y conversaciones son confidenciales
            </p>

            <p class="copy">
                SafeMind · Sistema de acompañamiento al bienestar estudiantil
            </p>

        </div>


    </aside>


    <!-- PARTE DERECHA -->

    <main class="principal">

        <div class="mancha sage"></div>
        <div class="mancha gold"></div>

        <svg class="respirar" viewBox="0 0 300 300" aria-hidden="true">
            <circle cx="150" cy="150" r="60"></circle>
            <circle cx="150" cy="150" r="95"></circle>
            <circle cx="150" cy="150" r="130"></circle>
        </svg>

        <div class="contenido">

            <h1>Aquí, cada estudiante puede ser escuchado</h1>

            <p>
                Un espacio seguro donde emociones, dudas y momentos
                difíciles encuentran acompañamiento profesional,
                antes de que se conviertan en una crisis.
            </p>

        </div>


    </main>


</div>

</body>

</html>