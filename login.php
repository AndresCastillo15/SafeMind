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

/*
--------------------------------------------------------------------
Mapa de roles habilitados para iniciar sesión con correo/contraseña.

Solo 'profesor' tiene tabla real con columna password por ahora
(usa la tabla `profesor`, ver Safemind.sql). Los demás roles no
tienen tabla de autenticación todavía, así que en vez de lanzar un
error 500 al intentar consultarlos, se les muestra un mensaje claro.

Cuando agregues las tablas que faltan (admin, un login propio de
estudiante), solo hay que añadir su entrada aquí con:
    'rol_login' => [
        'tabla'          => 'nombre_tabla',
        'campo_id'       => 'columna_id',
        'redirigir_a'    => 'ruta/a/su/dashboard.php',
    ]
--------------------------------------------------------------------
*/

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

                    if (!empty($usuario['password']) && password_verify($password, $usuario['password'])) {

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

    <title>Login - SafeMind</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #eef3f7;

            display: flex;
            justify-content: center;
            align-items: center;

            min-height: 100vh;
        }

        .login {
            width: 380px;
            background: white;

            padding: 35px;

            border-radius: 15px;

            box-shadow: 0 5px 20px rgba(0,0,0,0.15);

            text-align: center;
        }

        h1 {
            color: #173b63;
            margin-bottom: 10px;
        }

        .rol {
            color: #666;
            margin-bottom: 25px;
        }

        input {
            width: 100%;

            padding: 13px;

            margin-bottom: 15px;

            border: 1px solid #ccc;

            border-radius: 7px;

            font-size: 15px;
        }

        button {
            width: 100%;

            padding: 13px;

            border: none;

            border-radius: 7px;

            background: #2878bd;

            color: white;

            font-size: 16px;

            cursor: pointer;
        }

        button:hover {
            background: #1c609b;
        }

        .error {
            background: #ffe5e5;

            color: #a00000;

            padding: 10px;

            border-radius: 7px;

            margin-bottom: 15px;
        }

        .volver {
            display: block;

            margin-top: 20px;

            color: #2878bd;

            text-decoration: none;
        }

    </style>

</head>

<body>

    <div class="login">

        <h1>SafeMind</h1>

        <p class="rol">
            Inicio de sesión -
            <?php echo $roles[$rol]; ?>
        </p>

        <?php if ($mensaje != ""): ?>

            <div class="error">
                <?php echo $mensaje; ?>
            </div>

        <?php endif; ?>

        <form method="POST">

            <input
                type="email"
                name="correo"
                placeholder="Correo electrónico"
                required
            >

            <input
                type="password"
                name="password"
                placeholder="Contraseña"
                required
            >

            <button type="submit">
                Ingresar
            </button>

        </form>

        <a href="index.php" class="volver">
            Volver al inicio
        </a>

    </div>

</body>

</html>