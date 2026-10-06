<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');

require_once('../config/conexion.php');

$conexion = conectar();

/*
--------------------------------------
1. Verificar parámetros
--------------------------------------
*/

if (
    !isset($_POST['chat_id']) ||
    !isset($_POST['nombre']) ||
    !isset($_POST['apellido']) ||
    !isset($_POST['correo'])
) {
    echo json_encode([
        "success" => false,
        "mensaje" => "Faltan parámetros: chat_id, nombre, apellido y correo"
    ]);

    exit();
}

$chat_id  = trim($_POST['chat_id']);
$nombre   = trim($_POST['nombre']);
$apellido = trim($_POST['apellido']);
$correo   = trim($_POST['correo']);

/*
--------------------------------------
2. Validar que no estén vacíos
--------------------------------------
*/

if ($chat_id === '' || $nombre === '' || $apellido === '' || $correo === '') {
    echo json_encode([
        "success" => false,
        "mensaje" => "Los datos no pueden estar vacíos"
    ]);

    exit();
}

/*
--------------------------------------
3. Validar formato de chat_id (numérico) y correo
--------------------------------------
*/

if (!ctype_digit($chat_id)) {
    echo json_encode([
        "success" => false,
        "mensaje" => "chat_id debe ser numérico"
    ]);

    exit();
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        "success" => false,
        "mensaje" => "El correo no tiene un formato válido"
    ]);

    exit();
}

// chat_id_telegram es BIGINT en la BD
$chat_id = (int) $chat_id;

/*
--------------------------------------
4. Verificar si ya existe (por chat_id o por correo)
--------------------------------------
*/

$sql = "SELECT id_estudiante
        FROM estudiante
        WHERE chat_id_telegram = ? OR correo = ?";

$stmt = mysqli_prepare($conexion, $sql);

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "mensaje" => "Error al preparar consulta",
        "error" => mysqli_error($conexion)
    ]);
    exit();
}

mysqli_stmt_bind_param($stmt, "is", $chat_id, $correo);
mysqli_stmt_execute($stmt);

$resultado = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($resultado) > 0) {
    $estudiante = mysqli_fetch_assoc($resultado);

    echo json_encode([
        "success" => false,
        "registrado" => false,
        "mensaje" => "El estudiante ya está registrado (chat_id o correo duplicado)",
        "id_estudiante" => $estudiante['id_estudiante']
    ]);

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    exit();
}

mysqli_stmt_close($stmt);

/*
--------------------------------------
5. Registrar estudiante
--------------------------------------
*/

$sql = "INSERT INTO estudiante
        (
            nombre,
            apellido,
            correo,
            chat_id_telegram,
            estado
        )
        VALUES (?, ?, ?, ?, 'Activo')";

$stmt = mysqli_prepare($conexion, $sql);

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "mensaje" => "Error al preparar inserción",
        "error" => mysqli_error($conexion)
    ]);
    exit();
}

mysqli_stmt_bind_param(
    $stmt,
    "sssi",
    $nombre,
    $apellido,
    $correo,
    $chat_id
);

/*
--------------------------------------
6. Ejecutar registro
--------------------------------------
*/

if (mysqli_stmt_execute($stmt)) {

    $id_estudiante = mysqli_insert_id($conexion);

    echo json_encode([
        "success" => true,
        "registrado" => true,
        "mensaje" => "Estudiante registrado correctamente",
        "estudiante" => [
            "id_estudiante" => $id_estudiante,
            "nombre" => $nombre,
            "apellido" => $apellido,
            "correo" => $correo,
            "chat_id_telegram" => $chat_id,
            "estado" => "Activo"
        ]
    ]);

} else {

    echo json_encode([
        "success" => false,
        "registrado" => false,
        "mensaje" => "Error al registrar estudiante",
        "error" => mysqli_error($conexion)
    ]);
}

mysqli_stmt_close($stmt);
mysqli_close($conexion);