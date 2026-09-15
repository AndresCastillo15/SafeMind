<?php

session_start();

require_once 'API/config/conexion.php';

$mensaje = "";

$rol = $_GET['rol'] ?? 'estudiante';

$roles = [
    'admin' => 'Administrador',
    'profesor' => 'Profesor',
    'estudiante' => 'Estudiante',
];

if (!isset($roles[$rol])) {
    $rol = 'estudiante';
}

$tablas_login = [
    'profesor' => [
        'tabla'       => 'profesor',
        'campo_id'    => 'id_profesor',
        'redirigir_a' => 'dashboards/profesor/index.php',
    ],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($tablas_login[$rol])) {

        $mensaje = "El inicio de sesión para este rol todavía no está disponible.";

    } else {

        $correo = trim($_POST['correo'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($correo === '' || $password === '') {

            $mensaje = "Completa correo y contraseña.";

        } else {

            $config = $tablas_login[$rol];
            $conexion = conectar();

            $sql = "SELECT * FROM `{$config['tabla']}`
                    WHERE correo = ?
                    AND estado = 'Activo'";

            $stmt = mysqli_prepare($conexion, $sql);

            if (!$stmt) {

                $mensaje = "Error interno al iniciar sesión. Intenta más tarde.";

            } else {

                mysqli_stmt_bind_param($stmt, "s", $correo);
                mysqli_stmt_execute($stmt);

                $resultado = mysqli_stmt_get_result($stmt);

                if (mysqli_num_rows($resultado) === 1) {

                    $usuario = mysqli_fetch_assoc($resultado);

                    if (
                        !empty($usuario['password']) &&
                        password_verify($password, $usuario['password'])
                    ) {

                        $_SESSION['id_usuario'] = $usuario[$config['campo_id']];
                        $_SESSION['nombre'] = $usuario['nombre'];
                        $_SESSION['correo'] = $usuario['correo'];
                        $_SESSION['rol'] = $rol;

                        mysqli_stmt_close($stmt);
                        mysqli_close($conexion);

                        header("Location: " . $config['redirigir_a']);
                        exit();

                    } else {

                        $mensaje = "Contraseña incorrecta.";

                    }

                } else {

                    $mensaje = "El usuario no existe, no está activo, o no pertenece a este rol.";

                }

                mysqli_stmt_close($stmt);
            }

            mysqli_close($conexion);
        }
    }
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Iniciar sesión - SafeMind</title>

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
            --gold: #C8922F;

            --radius: 16px;
            --shadow: 0 10px 28px rgba(14, 46, 61, 0.14);
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            min-height: 100vh;
            font-family: 'Karla', sans-serif;
            background: var(--paper);
            color: var(--ink);
        }

        h1,
        h2,
        .titulo {
            font-family: 'Fraunces', serif;
        }

        a {
            font-family: inherit;
        }

        .contenedor {
            display: flex;
            min-height: 100vh;
        }

        /* ================= PANEL IZQUIERDO ================= */

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
            width: 62px;
            height: 62px;
            object-fit: contain;
            border-radius: 12px;

            transition: transform 0.3s ease;
        }

        .logo:hover {
            transform: scale(1.06) rotate(-2deg);
        }

        .titulo {
            font-size: 28px;
            font-weight: 560;
            color: var(--deep-dark);
            line-height: 1.1;
        }

        .subtitulo {
            font-size: 15px;
            color: var(--ink-soft);
            line-height: 1.55;

            margin: 18px 0 34px;
            max-width: 34ch;

            opacity: 0;
            animation: aparecer 0.6s ease 0.1s forwards;
        }

        .etiqueta {
            display: inline-block;
            width: fit-content;

            margin-bottom: 12px;
            padding: 6px 11px;

            border-radius: 30px;
            background: #E8EFEA;
            color: var(--sage);

            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.4px;

            opacity: 0;
            animation: aparecer 0.6s ease 0.2s forwards;
        }

        /* ================= FORMULARIO ================= */

        .login {
            width: 100%;
            max-width: 420px;

            opacity: 0;
            animation: aparecer 0.7s ease 0.25s forwards;
        }

        .login h2 {
            color: var(--deep-dark);
            font-size: 31px;
            font-weight: 560;
            line-height: 1.15;
            margin-bottom: 9px;
        }

        .descripcion-login {
            color: var(--ink-soft);
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 25px;
        }

        .campo {
            margin-bottom: 17px;
        }

        .campo label {
            display: block;
            margin-bottom: 7px;

            color: var(--deep-dark);
            font-size: 13px;
            font-weight: 700;
        }

        .campo input {
            width: 100%;

            padding: 14px 15px;

            border: 1px solid #D8D8CE;
            border-radius: 11px;

            background: #FFFFFF;
            color: var(--ink);

            font-family: inherit;
            font-size: 15px;

            outline: none;

            transition:
                border-color 0.25s ease,
                box-shadow 0.25s ease,
                transform 0.25s ease;
        }

        .campo input::placeholder {
            color: #9AA6A9;
        }

        .campo input:focus {
            border-color: var(--deep-light);
            box-shadow: 0 0 0 4px rgba(58, 118, 145, 0.13);
            transform: translateY(-1px);
        }

        .boton {
            width: 100%;

            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;

            padding: 14px 18px;

            border: none;
            border-radius: 11px;

            background: var(--deep);
            color: #FFFFFF;

            font-family: inherit;
            font-size: 15px;
            font-weight: 700;

            cursor: pointer;

            transition:
                background 0.25s ease,
                transform 0.25s ease,
                box-shadow 0.25s ease;
        }

        .boton svg {
            width: 18px;
            height: 18px;
            transition: transform 0.25s ease;
        }

        .boton:hover {
            background: var(--deep-dark);
            transform: translateY(-3px);
            box-shadow: var(--shadow);
        }

        .boton:hover svg {
            transform: translateX(4px);
        }

        .boton:active {
            transform: translateY(0);
        }

        .error {
            display: flex;
            align-items: flex-start;
            gap: 9px;

            padding: 12px 13px;
            margin-bottom: 18px;

            border: 1px solid #E8BDB5;
            border-radius: 11px;

            background: #FFF0EC;
            color: #9B3D31;

            font-size: 13px;
            line-height: 1.4;

            animation: aparecer 0.4s ease;
        }

        .error svg {
            width: 17px;
            height: 17px;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .volver {
            display: inline-flex;
            align-items: center;
            gap: 7px;

            margin-top: 20px;

            color: var(--deep-light);
            text-decoration: none;

            font-size: 14px;
            font-weight: 700;

            transition:
                color 0.25s ease,
                transform 0.25s ease;
        }

        .volver svg {
            width: 16px;
            height: 16px;
            transition: transform 0.25s ease;
        }

        .volver:hover {
            color: var(--deep-dark);
            transform: translateX(-3px);
        }

        .volver:hover svg {
            transform: translateX(-3px);
        }

        /* ================= PIE ================= */

        .pie {
            margin-top: auto;
            padding-top: 30px;
        }

        .pie-nota {
            display: flex;
            align-items: center;
            gap: 8px;

            margin-bottom: 10px;

            color: var(--ink-soft);
            font-size: 12.5px;
        }

        .pie-nota svg {
            width: 15px;
            height: 15px;
            stroke: var(--sage);
            flex-shrink: 0;
        }

        .copy {
            color: #9AA6A9;
            font-size: 12px;
            line-height: 1.4;
        }

        /* ================= PARTE DERECHA ================= */

        .principal {
            position: relative;
            flex: 1;
            min-height: 100vh;
            overflow: hidden;

            display: flex;
            align-items: center;
            justify-content: center;

            background:
                radial-gradient(
                    120% 140% at 15% 15%,
                    var(--deep-light) 0%,
                    transparent 55%
                ),
                linear-gradient(
                    165deg,
                    var(--deep) 0%,
                    var(--deep-dark) 70%
                );
        }

        .mancha {
            position: absolute;
            border-radius: 50%;
            filter: blur(2px);
            opacity: 0.5;
            pointer-events: none;
        }

        .mancha.sage {
            width: 340px;
            height: 340px;

            top: -80px;
            right: -60px;

            background:
                radial-gradient(
                    circle at 35% 35%,
                    rgba(79, 127, 98, 0.55),
                    transparent 70%
                );

            animation: flotar 9s ease-in-out infinite;
        }

        .mancha.gold {
            width: 260px;
            height: 260px;

            bottom: -60px;
            left: -40px;

            background:
                radial-gradient(
                    circle at 40% 40%,
                    rgba(200, 146, 47, 0.35),
                    transparent 70%
                );

            animation: flotar 11s ease-in-out infinite reverse;
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

        .respirar circle:nth-child(1) {
            animation-delay: 0s;
        }

        .respirar circle:nth-child(2) {
            animation-delay: 0.5s;
            stroke: rgba(255, 255, 255, 0.3);
        }

        .respirar circle:nth-child(3) {
            animation-delay: 1s;
            stroke: rgba(255, 255, 255, 0.18);
        }

        .contenido {
            position: relative;
            z-index: 2;

            max-width: 620px;
            padding: 40px;

            color: #FFFFFF;
            text-align: center;

            opacity: 0;
            animation: aparecer 0.8s ease 0.4s forwards;
        }

        .contenido .pequeno {
            margin-bottom: 18px;

            color: rgba(255, 255, 255, 0.65);
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .contenido h1 {
            margin-bottom: 22px;

            color: #FFFFFF;
            font-size: 48px;
            font-weight: 560;
            line-height: 1.18;
        }

        .contenido p {
            max-width: 52ch;
            margin: 0 auto;

            color: rgba(255, 255, 255, 0.82);
            font-size: 17.5px;
            line-height: 1.65;
        }

        /* ================= ANIMACIONES ================= */

        @keyframes aparecer {
            from {
                opacity: 0;
                transform: translateY(18px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes respirar {
            0%,
            100% {
                transform: scale(0.85);
                opacity: 0.35;
            }

            50% {
                transform: scale(1.05);
                opacity: 0.85;
            }
        }

        @keyframes flotar {
            0%,
            100% {
                transform: translate(0, 0);
            }

            50% {
                transform: translate(15px, 20px);
            }
        }

        /* ================= RESPONSIVE ================= */

        @media (max-width: 900px) {

            .contenedor {
                flex-direction: column;
            }

            .panel {
                width: 100%;
                min-height: auto;

                padding: 35px 30px;

                border-right: none;
                border-bottom: 1px solid var(--paper-line);
            }

            .login {
                max-width: 520px;
            }

            .principal {
                min-height: 430px;
                padding: 35px 20px;
            }

            .contenido h1 {
                font-size: 36px;
            }

            .respirar {
                width: 310px;
                height: 310px;
            }

            .pie {
                margin-top: 35px;
            }
        }

        @media (max-width: 480px) {

            .panel {
                padding: 30px 22px 26px;
            }

            .marca {
                gap: 11px;
            }

            .logo {
                width: 54px;
                height: 54px;
            }

            .titulo {
                font-size: 25px;
            }

            .login h2 {
                font-size: 28px;
            }

            .principal {
                min-height: 390px;
            }

            .contenido {
                padding: 25px 15px;
            }

            .contenido h1 {
                font-size: 31px;
            }

            .contenido p {
                font-size: 15px;
            }
        }

        @media (prefers-reduced-motion: reduce) {

            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }

    </style>

</head>

<body>

    <div class="contenedor">

        <!-- PANEL IZQUIERDO -->

        <aside class="panel">

            <div class="marca">

                <img
                    src="img/logo.png"
                    class="logo"
                    alt="Logo SafeMind"
                >

                <h1 class="titulo">SafeMind</h1>

            </div>

            <p class="subtitulo">
                Acompañamos el bienestar emocional de cada
                estudiante, con apoyo profesional disponible
                cuando más se necesita.
            </p>

            <span class="etiqueta">
                ACCESO SEGURO
            </span>

            <section class="login">

                <h2>
                    Bienvenido de nuevo
                </h2>

                <p class="descripcion-login">
                    Inicia sesión para continuar en tu espacio de SafeMind.
                </p>

                <?php if ($mensaje != ""): ?>

                    <div class="error">

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>

                        <span>
                            <?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?>
                        </span>

                    </div>

                <?php endif; ?>

                <form method="POST">

                    <div class="campo">

                        <label for="correo">
                            Correo electrónico
                        </label>

                        <input
                            type="email"
                            id="correo"
                            name="correo"
                            placeholder="ejemplo@correo.com"
                            autocomplete="email"
                            required
                        >

                    </div>

                    <div class="campo">

                        <label for="password">
                            Contraseña
                        </label>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Escribe tu contraseña"
                            autocomplete="current-password"
                            required
                        >

                    </div>

                    <button type="submit" class="boton">

                        Ingresar

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>

                    </button>

                </form>

                <a href="index.php" class="volver">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>

                    Volver al inicio

                </a>

            </section>

            <footer class="pie">

                <p class="pie-nota">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="M12 3l7 3v5c0 5-3.3 8.4-7 10-3.7-1.6-7-5-7-10V6l7-3z"/>
                    </svg>

                    Tus datos y conversaciones son confidenciales

                </p>

                <p class="copy">
                    SafeMind · Sistema de acompañamiento al bienestar estudiantil
                </p>

            </footer>

        </aside>

        <!-- PARTE DERECHA -->

        <main class="principal">

            <div class="mancha sage"></div>
            <div class="mancha gold"></div>

            <svg
                class="respirar"
                viewBox="0 0 300 300"
                aria-hidden="true"
            >
                <circle cx="150" cy="150" r="60"></circle>
                <circle cx="150" cy="150" r="95"></circle>
                <circle cx="150" cy="150" r="130"></circle>
            </svg>

            <div class="contenido">

                <p class="pequeno">
                    Un espacio para ti
                </p>

                <h1>
                    Aquí, cada estudiante puede ser escuchado
                </h1>

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