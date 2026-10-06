<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');

require_once('../config/conexion.php');

$conexion = conectar();

/*
--------------------------------------
Verificar parámetro
--------------------------------------
*/

if (!isset($_POST['chat_id']))
{
    echo json_encode([
        "success" => false,
        "mensaje" => "No se recibió el chat_id"
    ]);

    exit();
}

$chat_id = trim($_POST['chat_id']);

/*
--------------------------------------
Consultar estudiante
--------------------------------------
*/

$sql = "SELECT
            id_estudiante,
            chat_id_telegram,
            nombre,
            apellido,
            curso,
            estado
        FROM estudiante
        WHERE chat_id_telegram = ?";

$stmt = mysqli_prepare($conexion, $sql);

if (!$stmt)
{
    echo json_encode([
        "success" => false,
        "mensaje" => "Error al preparar la consulta",
        "error" => mysqli_error($conexion)
    ]);

    exit();
}

mysqli_stmt_bind_param($stmt, "s", $chat_id);

mysqli_stmt_execute($stmt);

$resultado = mysqli_stmt_get_result($stmt);

/*
--------------------------------------
Respuesta
--------------------------------------
*/

if (mysqli_num_rows($resultado) > 0)
{
    $estudiante = mysqli_fetch_assoc($resultado);

    echo json_encode([
        "success" => true,
        "existe" => true,
        "estudiante" => $estudiante
    ]);
}
else
{
    echo json_encode([
        "success" => true,
        "existe" => false,
        "mensaje" => "Estudiante no encontrado"
    ]);
}

mysqli_stmt_close($stmt);
mysqli_close($conexion);

?>