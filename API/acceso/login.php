<?php

session_start();

header('Content-Type: application/json; charset=utf-8');

require_once '../config/conexion.php';

$conexion = conectar();

$usuario = trim($_POST['usuario'] ?? '');
$contraseña = $_POST['contraseña'] ?? '';

if ($usuario === '' || $contraseña === '') {
    echo json_encode([
        'success' => false,
        'mensaje' => 'Usuario y contraseña son obligatorios'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/*
|--------------------------------------------------------------------------
| BUSCAR EN PSICÓLOGOS
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        id_psicologo,
        usuario,
        nombre,
        apellido,
        correo,
        estado,
        contraseña
    FROM psicologo
    WHERE usuario = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conexion, $sql);

mysqli_stmt_bind_param($stmt, "s", $usuario);
mysqli_stmt_execute($stmt);

$resultado = mysqli_stmt_get_result($stmt);

if ($fila = mysqli_fetch_assoc($resultado)) {

    if ($fila['estado'] !== 'Activo') {
        echo json_encode([
            'success' => false,
            'mensaje' => 'La cuenta se encuentra inactiva'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (password_verify($contraseña, $fila['contraseña'])) {

        $_SESSION['usuario_id'] = $fila['id_psicologo'];
        $_SESSION['usuario'] = $fila['usuario'];
        $_SESSION['nombre'] = $fila['nombre'];
        $_SESSION['apellido'] = $fila['apellido'];
        $_SESSION['rol'] = 'psicologo';

        echo json_encode([
            'success' => true,
            'mensaje' => 'Inicio de sesión correcto',
            'rol' => 'psicologo',
            'usuario' => [
                'id' => intval($fila['id_psicologo']),
                'nombre' => $fila['nombre'],
                'apellido' => $fila['apellido'],
                'correo' => $fila['correo']
            ]
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }
}

/*
|--------------------------------------------------------------------------
| BUSCAR EN PROFESORES
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        id_profesor,
        usuario,
        nombre,
        apellido,
        correo,
        curso,
        estado,
        contraseña
    FROM profesor
    WHERE usuario = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conexion, $sql);

mysqli_stmt_bind_param($stmt, "s", $usuario);
mysqli_stmt_execute($stmt);

$resultado = mysqli_stmt_get_result($stmt);

if ($fila = mysqli_fetch_assoc($resultado)) {

    if ($fila['estado'] !== 'Activo') {
        echo json_encode([
            'success' => false,
            'mensaje' => 'La cuenta se encuentra inactiva'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (password_verify($contraseña, $fila['contraseña'])) {

        $_SESSION['usuario_id'] = $fila['id_profesor'];
        $_SESSION['usuario'] = $fila['usuario'];
        $_SESSION['nombre'] = $fila['nombre'];
        $_SESSION['apellido'] = $fila['apellido'];
        $_SESSION['rol'] = 'profesor';
        $_SESSION['curso'] = $fila['curso'];

        echo json_encode([
            'success' => true,
            'mensaje' => 'Inicio de sesión correcto',
            'rol' => 'profesor',
            'usuario' => [
                'id' => intval($fila['id_profesor']),
                'nombre' => $fila['nombre'],
                'apellido' => $fila['apellido'],
                'correo' => $fila['correo'],
                'curso' => $fila['curso']
            ]
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }
}

/*
|--------------------------------------------------------------------------
| CREDENCIALES INCORRECTAS
|--------------------------------------------------------------------------
*/

echo json_encode([
    'success' => false,
    'mensaje' => 'Usuario o contraseña incorrectos'
], JSON_UNESCAPED_UNICODE);

mysqli_close($conexion);

?>