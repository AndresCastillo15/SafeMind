<?php

/*
====================================================================
⚠️  MODO DE PRUEBA: LOGIN SOLO CON CORREO, SIN CONTRASEÑA ⚠️

Esta versión deja entrar a cualquiera que escriba un correo que
exista y esté 'Activo' en la tabla del rol — no se valida ninguna
contraseña. Es solo para ahorrar tiempo mientras desarrollas.

ANTES DE ENTREGAR EL PROYECTO O SUBIRLO A ALGO QUE otras personas
puedan ver, hay que devolver la validación de contraseña real.
Avísame cuando llegue ese momento y te la reactivo.
====================================================================
*/

// TEMPORAL: quitar estas dos líneas cuando el login ya funcione bien.
// Sirven para ver el error real en pantalla en vez del 500 en blanco.
ini_set('display_errors', 1);
error_reporting(E_ALL);

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

/*
--------------------------------------------------------------------
Solo presentación: ícono de cada rol para la tarjeta de login.
No afecta la lógica de autenticación de abajo.
--------------------------------------------------------------------
*/

$iconos_rol = [
    'admin' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7 3v5c0 5-3.3 8.4-7 10-3.7-1.6-7-5-7-10V6l7-3z"/><path d="M9 12l2 2 4-4"/></svg>',
    'profesor' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6c2-1.2 5-1.4 7 0v12.5c-2-1.4-5-1.2-7 0V6z"/><path d="M21 6c-2-1.2-5-1.4-7 0v12.5c2-1.4 5-1.2 7 0V6z"/></svg>',
    'estudiante' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21v-8.5"/><path d="M12 13c-4.2 0-7.5-2.8-7.5-7 4.2 0 7.5 2.1 7.5 5.2"/><path d="M12 11.4c0-3.4 3.1-5.9 7.5-5.9 0 4.3-3.3 7.4-7.5 6.5"/></svg>',
];

/*
--------------------------------------------------------------------
Mapa de roles habilitados para iniciar sesión con correo/contraseña.

Solo 'profesor' tiene tabla real con columna de contraseña por ahora
(tabla `profesor`, columna `contraseña` — ver Safemind.sql). Los
demás roles no tienen tabla de autenticación todavía, así que en
vez de lanzar un error 500 al intentar consultarlos, se les muestra
un mensaje claro.

'campo_password' existe porque no todas las tablas van a llamar
igual a su columna de contraseña necesariamente; así queda explícito
por rol en vez de asumirlo en el código de abajo.

Cuando agregues las tablas que faltan (admin, un login propio de
estudiante), solo hay que añadir su entrada aquí con:
    'rol_login' => [
        'tabla'          => 'nombre_tabla',
        'campo_id'       => 'columna_id',
        'campo_password' => 'columna_contraseña',
        'redirigir_a'    => 'ruta/a/su/dashboard.php',
    ]
--------------------------------------------------------------------
*/

$tablas_login = [
    'profesor' => [
        'tabla'          => 'profesor',
        'campo_id'       => 'id_profesor',
        'campo_password' => 'password',
        'redirigir_a'    => 'dashboards/dashboard_docente.php',
    ],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($tablas_login[$rol])) {

        $mensaje = "El inicio de sesión para este rol todavía no está disponible.";

    } else {

        $correo = trim($_POST['correo'] ?? '');

        if ($correo === '') {

            $mensaje = "Escribe un correo.";

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

                    $_SESSION['id_usuario'] = $usuario[$config['campo_id']];
                    $_SESSION['nombre'] = trim($usuario['nombre'] . ' ' . $usuario['apellido']);
                    $_SESSION['correo'] = $usuario['correo'];
                    $_SESSION['rol'] = $rol;

                    mysqli_stmt_close($stmt);
                    mysqli_close($conexion);

                    header("Location: " . $config['redirigir_a']);
                    exit();

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

    <title>Login - SafeMind</title>

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
            --ink-faint: #9AA6A9;

            --deep: #17455A;
            --deep-dark: #0E2E3D;
            --deep-light: #3A7691;

            --sage: #4F7F62;

            --gold: #C8922F;

            --radius: 16px;
            --shadow: 0 18px 40px rgba(14, 46, 61, 0.22);
        }

        body {
            font-family: 'Karla', sans-serif;
            color: var(--ink);
            min-height: 100vh;

            position: relative;
            overflow: hidden;

            background: radial-gradient(120% 140% at 15% 10%, var(--deep-light) 0%, transparent 55%),
                        linear-gradient(165deg, var(--deep) 0%, var(--deep-dark) 75%);

            display: flex;
            justify-content: center;
            align-items: center;

            padding: 24px;
        }

        h1 {
            font-family: 'Fraunces', serif;
        }

        /* fondo decorativo, igual al de la página principal, ahora con flotación lenta */

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
            top: -90px;
            right: -60px;
            animation: flotar 17s ease-in-out infinite;
        }

        .mancha.gold {
            width: 260px;
            height: 260px;
            background: radial-gradient(circle at 40% 40%, rgba(200, 146, 47, 0.35), transparent 70%);
            bottom: -60px;
            left: -50px;
            animation: flotar 21s ease-in-out infinite reverse;
        }

        .respirar {
            position: absolute;
            width: 480px;
            height: 480px;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            pointer-events: none;
        }

        .respirar circle {
            fill: none;
            stroke: rgba(255, 255, 255, 0.35);
            stroke-width: 1;
            transform-origin: center;
            animation: respirar 5.5s ease-in-out infinite;
        }

        .respirar circle:nth-child(1) { animation-delay: 0s; }
        .respirar circle:nth-child(2) { animation-delay: 0.5s; stroke: rgba(255,255,255,0.22); }
        .respirar circle:nth-child(3) { animation-delay: 1s; stroke: rgba(255,255,255,0.12); }

        /* tarjeta de login */

        .login {
            position: relative;
            z-index: 2;

            width: 100%;
            max-width: 380px;

            background: #fff;
            padding: 40px 34px 32px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);

            text-align: center;

            opacity: 0;
            animation: aparecer 0.5s ease forwards;
        }

        .login-icono {
            width: 52px;
            height: 52px;
            margin: 0 auto 16px;
            border-radius: 14px;

            display: flex;
            align-items: center;
            justify-content: center;

            background: var(--accent);
            color: #fff;

            opacity: 0;
            animation: aparecer-rebote 0.6s cubic-bezier(.34, 1.56, .64, 1) 0.15s forwards;
        }

        .login-icono svg {
            width: 24px;
            height: 24px;
            stroke-width: 1.7;
        }

        .login h1 {
            font-size: 24px;
            font-weight: 560;
            color: var(--deep-dark);
            margin-bottom: 6px;

            opacity: 0;
            animation: aparecer 0.5s ease 0.22s forwards;
        }

        .rol {
            color: var(--ink-soft);
            font-size: 14.5px;
            margin-bottom: 26px;

            opacity: 0;
            animation: aparecer 0.5s ease 0.28s forwards;
        }

        .error {
            display: flex;
            align-items: center;
            gap: 8px;
            text-align: left;

            background: #FBE7E4;
            color: #963B2A;
            font-size: 13.5px;
            line-height: 1.4;

            padding: 12px 14px;
            border-radius: 10px;
            margin-bottom: 18px;

            animation: aparecer 0.35s ease forwards, sacudir 0.5s ease 0.35s;
        }

        .error svg {
            width: 17px;
            height: 17px;
            flex-shrink: 0;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 13px;

            opacity: 0;
            animation: aparecer 0.5s ease 0.34s forwards;
        }

        .campo {
            position: relative;
        }

        .campo svg {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            width: 17px;
            height: 17px;
            stroke: var(--ink-faint);
            pointer-events: none;
            transition: stroke 0.2s ease;
        }

        .campo:focus-within svg {
            stroke: var(--accent);
        }

        input {
            width: 100%;
            padding: 13px 14px 13px 42px;

            border: 1px solid var(--paper-line);
            border-radius: 10px;

            font-family: 'Karla', sans-serif;
            font-size: 15px;
            color: var(--ink);

            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        input::placeholder {
            color: #9AA6A9;
        }

        input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-soft);
        }

        button {
            width: 100%;
            padding: 13px;
            margin-top: 5px;

            border: none;
            border-radius: 10px;

            background: var(--accent);
            color: #fff;

            display: flex;
            align-items: center;
            justify-content: center;
            gap: 9px;

            font-family: 'Karla', sans-serif;
            font-size: 15.5px;
            font-weight: 700;

            cursor: pointer;
            transition: background 0.2s ease, transform 0.15s ease;
        }

        button:hover {
            background: var(--accent-dark);
            transform: translateY(-1px);
        }

        button:active {
            transform: scale(0.98);
        }

        button:disabled {
            cursor: default;
            opacity: 0.85;
            transform: none;
        }

        .spinner {
            width: 15px;
            height: 15px;
            border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, 0.4);
            border-top-color: #fff;
            display: inline-block;
            animation: girar 0.6s linear infinite;
        }

        button:focus-visible,
        a:focus-visible {
            outline: 2px solid var(--accent);
            outline-offset: 2px;
        }

        .volver {
            display: inline-block;
            margin-top: 22px;

            color: var(--accent);
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;

            opacity: 0;
            animation: aparecer 0.5s ease 0.42s forwards;
        }

        .volver:hover {
            text-decoration: underline;
        }

        .nota {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;

            margin-top: 20px;
            color: #9AA6A9;
            font-size: 12px;

            opacity: 0;
            animation: aparecer 0.5s ease 0.48s forwards;
        }

        .nota svg {
            width: 14px;
            height: 14px;
            stroke: var(--sage);
            flex-shrink: 0;
        }

        /* acentos por rol */

        .login.admin {
            --accent: var(--deep);
            --accent-dark: var(--deep-dark);
            --accent-soft: rgba(23, 69, 90, 0.14);
        }

        .login.profesor {
            --accent: var(--sage);
            --accent-dark: #365F44;
            --accent-soft: rgba(79, 127, 98, 0.16);
        }

        .login.estudiante {
            --accent: var(--gold);
            --accent-dark: #A2751F;
            --accent-soft: rgba(200, 146, 47, 0.18);
        }

        @keyframes aparecer {
            from {
                opacity: 0;
                transform: translateY(14px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes aparecer-rebote {
            0% {
                opacity: 0;
                transform: scale(0.4) rotate(-8deg);
            }
            60% {
                opacity: 1;
                transform: scale(1.12) rotate(3deg);
            }
            100% {
                opacity: 1;
                transform: scale(1) rotate(0deg);
            }
        }

        @keyframes sacudir {
            10%, 90% { transform: translateX(-1px); }
            20%, 80% { transform: translateX(2px); }
            30%, 50%, 70% { transform: translateX(-4px); }
            40%, 60% { transform: translateX(4px); }
        }

        @keyframes respirar {
            0%, 100% {
                transform: scale(0.85);
                opacity: 0.3;
            }
            50% {
                transform: scale(1.05);
                opacity: 0.75;
            }
        }

        @keyframes flotar {
            0%, 100% {
                transform: translate(0, 0) scale(1);
            }
            50% {
                transform: translate(16px, -12px) scale(1.05);
            }
        }

        @keyframes girar {
            to { transform: rotate(360deg); }
        }

        @media (prefers-reduced-motion: reduce) {
            .login,
            .login-icono,
            .login h1,
            .rol,
            form,
            .volver,
            .nota,
            .error {
                animation: none;
                opacity: 1;
            }
            .respirar circle,
            .mancha,
            .spinner {
                animation: none;
                opacity: 0.3;
            }
        }

        @media (max-width: 480px) {
            .login {
                padding: 32px 24px 26px;
            }
        }

    </style>

</head>

<body>

    <div class="mancha sage"></div>
    <div class="mancha gold"></div>

    <svg class="respirar" viewBox="0 0 300 300" aria-hidden="true">
        <circle cx="150" cy="150" r="60"></circle>
        <circle cx="150" cy="150" r="95"></circle>
        <circle cx="150" cy="150" r="130"></circle>
    </svg>

    <div class="login <?php echo $rol; ?>">

        <div class="login-icono">
            <?php echo $iconos_rol[$rol]; ?>
        </div>

        <h1>SafeMind</h1>

        <p class="rol">
            Estás iniciando sesión como
            <?php echo strtolower($roles[$rol]); ?>
        </p>

        <?php if ($mensaje != ""): ?>

            <div class="error">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="9"/>
                    <path d="M12 8v5"/>
                    <path d="M12 16h.01"/>
                </svg>
                <span><?php echo $mensaje; ?></span>
            </div>

        <?php endif; ?>

        <form method="POST" id="form-login">

            <div class="campo">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="5" width="18" height="14" rx="2.5"/>
                    <path d="M3.5 6.5l8.5 6 8.5-6"/>
                </svg>
                <input
                    type="email"
                    name="correo"
                    placeholder="Correo electrónico"
                    required
                >
            </div>

            <button type="submit" id="btn-login">
                Ingresar
            </button>

        </form>

        <a href="index.php" class="volver">
            Volver al inicio
        </a>

        <p class="nota">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 3l7 3v5c0 5-3.3 8.4-7 10-3.7-1.6-7-5-7-10V6l7-3z"/>
            </svg>
            Conexión protegida
        </p>

    </div>

    <script>
        document.getElementById('form-login').addEventListener('submit', function () {
            var boton = document.getElementById('btn-login');
            boton.disabled = true;
            boton.innerHTML = '<span class="spinner"></span> Ingresando...';
        });
    </script>

</body>

</html>
