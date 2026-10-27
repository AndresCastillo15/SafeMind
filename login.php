<?php

/*
LOGIN DE SAFEMIND

Usuario y contraseña se consultan directamente en las tablas existentes
(profesor, psicologo). No se crea ninguna tabla adicional.
Las contraseñas se guardan con password_hash() y se verifican con password_verify().
*/

// TEMPORAL: quitar antes de publicar; muestra errores de PHP en pantalla.
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/API/config/conexion.php';

// Un solo lugar para todo lo que cambia por rol.
// 'disponible' => false: el Administrador aún no tiene tabla de usuarios.
$roles = [
    'admin' => [
        'nombre' => 'Administrador',
        'disponible' => false,
        'icono' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7 3v5c0 5-3.3 8.4-7 10-3.7-1.6-7-5-7-10V6l7-3z"/><path d="M9 12l2 2 4-4"/></svg>',
    ],
    'profesor' => [
        'nombre' => 'Docente',
        'tabla' => 'profesor',
        'campo_id' => 'id_profesor',
        'redirigir_a' => 'dashboards/dashboard_profesor.php',
        'icono' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6c2-1.2 5-1.4 7 0v12.5c-2-1.4-5-1.2-7 0V6z"/><path d="M21 6c-2-1.2-5-1.4-7 0v12.5c2-1.4 5-1.2 7 0V6z"/></svg>',
    ],
    'psicologo' => [
        'nombre' => 'Orientador / Psicólogo',
        'tabla' => 'psicologo',
        'campo_id' => 'id_psicologo',
        'redirigir_a' => 'dashboards/dashboard_psicologo.php',
        'icono' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3"/><path d="M5 21c.6-4 3-6 7-6s6.4 2 7 6"/><path d="M19 3v4"/><path d="M17 5h4"/></svg>',
    ],
];

// Sin rol válido, volver al inicio
$rol = $_GET['rol'] ?? '';
if (!is_string($rol) || !isset($roles[$rol])) {
    header('Location: index.php');
    exit();
}

$config = $roles[$rol];
$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($usuario === '' || $password === '') {
        $mensaje = 'Escribe tu usuario y contraseña.';

    } elseif (!($config['disponible'] ?? true)) {
        $mensaje = 'El acceso de Administrador aún no está conectado a una tabla de usuarios.';

    } else {
        $conexion = conectar();

        // La tabla y las columnas salen de la lista fija de roles, nunca de la petición
        $columnas = "`{$config['campo_id']}`, usuario, `contraseña`, nombre, apellido, correo, estado"
                  . ($rol === 'profesor' ? ', curso' : '');

        $sql = "SELECT $columnas FROM `{$config['tabla']}`
                WHERE usuario = ? AND estado = 'Activo'
                LIMIT 1";

        $stmt = mysqli_prepare($conexion, $sql);
        $datos = null;

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $usuario);
            mysqli_stmt_execute($stmt);
            $datos = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            mysqli_stmt_close($stmt);
        }

        mysqli_close($conexion);

        if (!$stmt) {
            $mensaje = 'Error interno al iniciar sesión. Intenta más tarde.';

        } elseif ($datos && password_verify($password, (string) $datos['contraseña'])) {

            session_regenerate_id(true);

            $_SESSION['id_usuario'] = (int) $datos[$config['campo_id']];
            $_SESSION['nombre'] = trim(($datos['nombre'] ?? '') . ' ' . ($datos['apellido'] ?? ''));
            $_SESSION['correo'] = $datos['correo'] ?? null;
            $_SESSION['usuario'] = $datos['usuario'];
            $_SESSION['rol'] = $rol;

            if ($rol === 'profesor') {
                $_SESSION['curso'] = $datos['curso'] ?? null;
            } else {
                unset($_SESSION['curso']);
            }

            header('Location: ' . $config['redirigir_a']);
            exit();

        } else {
            // Mismo mensaje para usuario inexistente y contraseña incorrecta
            $mensaje = 'Usuario o contraseña incorrectos.';
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
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,450;9..144,560;9..144,650&family=Karla:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
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

        /* El scroll vive en body; el fondo animado es fijo y no lo corta */
        body {
            font-family: 'Karla', sans-serif;
            color: var(--ink);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 24px;
            background: radial-gradient(120% 140% at 15% 10%, var(--deep-light) 0%, transparent 55%),
                        linear-gradient(165deg, var(--deep) 0%, var(--deep-dark) 75%);
        }

        h1 { font-family: 'Fraunces', serif; }

        .fondo {
            position: fixed;
            inset: 0;
            overflow: hidden;
            pointer-events: none;
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
            top: -90px;
            right: -60px;
            background: radial-gradient(circle at 35% 35%, rgba(79, 127, 98, 0.55), transparent 70%);
            animation: flotar 17s ease-in-out infinite;
        }
        .mancha.gold {
            width: 260px;
            height: 260px;
            bottom: -60px;
            left: -50px;
            background: radial-gradient(circle at 40% 40%, rgba(200, 146, 47, 0.35), transparent 70%);
            animation: flotar 21s ease-in-out infinite reverse;
        }

        .respirar {
            position: absolute;
            width: 480px;
            height: 480px;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
        }
        .respirar circle {
            fill: none;
            stroke: rgba(255, 255, 255, 0.35);
            stroke-width: 1;
            transform-origin: center;
            animation: respirar 5.5s ease-in-out infinite;
        }
        .respirar circle:nth-child(2) { animation-delay: 0.5s; stroke: rgba(255, 255, 255, 0.22); }
        .respirar circle:nth-child(3) { animation-delay: 1s; stroke: rgba(255, 255, 255, 0.12); }

        /* Tarjeta de login */
        .login {
            --accent: var(--deep);
            --accent-dark: var(--deep-dark);
            --accent-soft: rgba(23, 69, 90, 0.14);
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 380px;
            background: #fff;
            padding: 40px 34px 32px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            text-align: center;
        }
        .login.profesor {
            --accent: var(--sage);
            --accent-dark: #365F44;
            --accent-soft: rgba(79, 127, 98, 0.16);
        }
        .login.psicologo {
            --accent: var(--deep-light);
            --accent-dark: var(--deep);
            --accent-soft: rgba(58, 118, 145, 0.16);
        }

        /* Entrada escalonada: cada bloque usa --d como retraso */
        .entra {
            opacity: 0;
            animation: aparecer 0.5s ease var(--d, 0s) forwards;
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
            animation: rebote 0.6s cubic-bezier(.34, 1.56, .64, 1) 0.15s forwards;
        }
        .login-icono svg { width: 24px; height: 24px; stroke-width: 1.7; }

        .login h1 { --d: 0.22s; font-size: 24px; font-weight: 560; color: var(--deep-dark); margin-bottom: 6px; }
        .rol { --d: 0.28s; color: var(--ink-soft); font-size: 14.5px; margin-bottom: 26px; }

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
        .error svg { width: 17px; height: 17px; flex-shrink: 0; }

        form { --d: 0.34s; display: flex; flex-direction: column; gap: 13px; }

        .campo { position: relative; }
        .campo > svg {
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
        .campo:focus-within > svg { stroke: var(--accent); }

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
        /* Espacio para el botón del ojo, para que el texto no quede debajo */
        input[type="password"] { padding-right: 46px; }
        input::placeholder { color: var(--ink-faint); }
        input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-soft);
        }

        .mostrar-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            width: 30px;
            height: 30px;
            margin: 0;
            padding: 0;
            border: none;
            border-radius: 8px;
            background: transparent;
            color: var(--ink-faint);
            cursor: pointer;
        }
        .mostrar-password:hover { background: transparent; color: var(--accent); transform: translateY(-50%); }
        .mostrar-password svg { width: 17px; height: 17px; }

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
        button:hover { background: var(--accent-dark); transform: translateY(-1px); }
        button:active { transform: scale(0.98); }
        button:disabled { cursor: default; opacity: 0.85; transform: none; }
        button:focus-visible,
        a:focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; }

        .spinner {
            width: 15px;
            height: 15px;
            border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, 0.4);
            border-top-color: #fff;
            display: inline-block;
            animation: girar 0.6s linear infinite;
        }

        .volver {
            --d: 0.42s;
            display: inline-block;
            margin-top: 22px;
            color: var(--accent);
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
        }
        .volver:hover { text-decoration: underline; }

        .nota {
            --d: 0.48s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            margin-top: 20px;
            color: var(--ink-faint);
            font-size: 12px;
        }
        .nota svg { width: 14px; height: 14px; stroke: var(--sage); flex-shrink: 0; }

        @keyframes aparecer {
            from { opacity: 0; transform: translateY(14px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes rebote {
            0% { opacity: 0; transform: scale(0.4) rotate(-8deg); }
            60% { opacity: 1; transform: scale(1.12) rotate(3deg); }
            100% { opacity: 1; transform: scale(1) rotate(0deg); }
        }
        @keyframes sacudir {
            10%, 90% { transform: translateX(-1px); }
            20%, 80% { transform: translateX(2px); }
            30%, 50%, 70% { transform: translateX(-4px); }
            40%, 60% { transform: translateX(4px); }
        }
        @keyframes respirar {
            0%, 100% { transform: scale(0.85); opacity: 0.3; }
            50% { transform: scale(1.05); opacity: 0.75; }
        }
        @keyframes flotar {
            0%, 100% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(16px, -12px) scale(1.05); }
        }
        @keyframes girar {
            to { transform: rotate(360deg); }
        }

        @media (prefers-reduced-motion: reduce) {
            .entra, .login-icono, .error { animation: none; opacity: 1; }
            /* Las manchas usan .sage/.gold, así que su selector debe ser igual de específico */
            .mancha.sage, .mancha.gold, .respirar circle, .spinner { animation: none; }
            .respirar circle { opacity: 0.3; }
            .spinner { opacity: 0.3; }
        }

        @media (max-width: 480px) {
            .login { padding: 32px 24px 26px; }
        }
    </style>
</head>
<body>

    <div class="fondo" aria-hidden="true">
        <div class="mancha sage"></div>
        <div class="mancha gold"></div>
        <svg class="respirar" viewBox="0 0 300 300">
            <circle cx="150" cy="150" r="60"></circle>
            <circle cx="150" cy="150" r="95"></circle>
            <circle cx="150" cy="150" r="130"></circle>
        </svg>
    </div>

    <div class="login entra <?php echo htmlspecialchars($rol); ?>">

        <div class="login-icono">
            <?php echo $config['icono']; ?>
        </div>

        <h1 class="entra">SafeMind</h1>

        <p class="rol entra">
            Estás iniciando sesión como <?php echo mb_strtolower(htmlspecialchars($config['nombre'])); ?>
        </p>

        <?php if ($mensaje !== ''): ?>
            <div class="error" role="alert">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="9"/>
                    <path d="M12 8v5"/>
                    <path d="M12 16h.01"/>
                </svg>
                <span><?php echo htmlspecialchars($mensaje); ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" id="form-login" class="entra">

            <div class="campo">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="8" r="3"/>
                    <path d="M5 21c.6-4 3-6 7-6s6.4 2 7 6"/>
                </svg>
                <input type="text" name="usuario" placeholder="Usuario" autocomplete="username" required>
            </div>

            <div class="campo">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="5" y="10" width="14" height="10" rx="2"/>
                    <path d="M8 10V7a4 4 0 0 1 8 0v3"/>
                </svg>
                <input type="password" name="password" id="password" placeholder="Contraseña" autocomplete="current-password" required>
                <button type="button" class="mostrar-password" id="mostrar-password" aria-label="Mostrar contraseña">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6z"/>
                        <circle cx="12" cy="12" r="2.5"/>
                    </svg>
                </button>
            </div>

            <button type="submit" id="btn-login">Ingresar</button>

        </form>

        <a href="index.php" class="volver entra">Volver al inicio</a>

        <p class="nota entra">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 3l7 3v5c0 5-3.3 8.4-7 10-3.7-1.6-7-5-7-10V6l7-3z"/>
            </svg>
            Conexión protegida
        </p>

    </div>

    <script>
        const campoPassword = document.getElementById('password');
        const botonOjo = document.getElementById('mostrar-password');
        const botonLogin = document.getElementById('btn-login');

        botonOjo.addEventListener('click', () => {
            const visible = campoPassword.type === 'text';
            campoPassword.type = visible ? 'password' : 'text';
            botonOjo.setAttribute('aria-label', visible ? 'Mostrar contraseña' : 'Ocultar contraseña');
        });

        document.getElementById('form-login').addEventListener('submit', () => {
            botonLogin.disabled = true;
            botonLogin.innerHTML = '<span class="spinner"></span> Ingresando...';
        });

        // Al volver con el botón de atrás, la página puede restaurarse desde caché con el botón bloqueado
        window.addEventListener('pageshow', () => {
            botonLogin.disabled = false;
            botonLogin.textContent = 'Ingresar';
        });
    </script>

</body>
</html>