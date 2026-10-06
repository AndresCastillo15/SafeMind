<?php

header('Content-Type: application/json; charset=utf-8');

require_once '../config/conexion.php';

$conexion = conectar();

try {

    /*
    -----------------------------------
    VERIFICAR QUE LLEGUE EL CHAT_ID
    -----------------------------------
    */

    if (!isset($_POST['chat_id']) || empty($_POST['chat_id'])) {

        echo json_encode([
            "success" => false,
            "mensaje" => "No se recibió el chat_id"
        ]);

        exit;
    }

    $chat_id = trim($_POST['chat_id']);


    /*
    -----------------------------------
    BUSCAR EL REGISTRO TEMPORAL
    -----------------------------------
    */

    $sql = "SELECT 
                chat_id_telegram,
                paso,
                nombre,
                apellido,
                correo,
                telefono,
                curso
            FROM registro_temporal
            WHERE chat_id_telegram = ?";

    $stmt = mysqli_prepare($conexion, $sql);

    mysqli_stmt_bind_param($stmt, "s", $chat_id);

    mysqli_stmt_execute($stmt);

    $resultado = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($resultado) === 0) {

        echo json_encode([
            "success" => false,
            "mensaje" => "No existe un registro temporal para este usuario"
        ]);

        exit;
    }

    $registro = mysqli_fetch_assoc($resultado);


    /*
    -----------------------------------
    VERIFICAR QUE EL REGISTRO
    ESTÉ COMPLETADO
    -----------------------------------
    */

    if ($registro['paso'] !== 'completado') {

        echo json_encode([
            "success" => false,
            "mensaje" => "El registro todavía no está completado",
            "paso_actual" => $registro['paso']
        ]);

        exit;
    }


    /*
    -----------------------------------
    VERIFICAR SI YA EXISTE
    EL ESTUDIANTE
    -----------------------------------
    */

    $sql = "SELECT id_estudiante
            FROM estudiante
            WHERE chat_id_telegram = ?";

    $stmt = mysqli_prepare($conexion, $sql);

    mysqli_stmt_bind_param($stmt, "s", $chat_id);

    mysqli_stmt_execute($stmt);

    $resultado = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($resultado) > 0) {

        echo json_encode([
            "success" => false,
            "mensaje" => "El estudiante ya existe en la base de datos"
        ]);

        exit;
    }


    /*
    -----------------------------------
    INICIAR TRANSACCIÓN
    -----------------------------------
    */

    mysqli_begin_transaction($conexion);


    /*
    -----------------------------------
    CREAR ESTUDIANTE DEFINITIVO
    -----------------------------------
    */

    $estado = "activo";

    $sql = "INSERT INTO estudiante
            (
                chat_id_telegram,
                nombre,
                apellido,
                correo,
                telefono,
                curso,
                estado
            )
            VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conexion, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "sssssss",
        $registro['chat_id_telegram'],
        $registro['nombre'],
        $registro['apellido'],
        $registro['correo'],
        $registro['telefono'],
        $registro['curso'],
        $estado
    );

    mysqli_stmt_execute($stmt);

    $id_estudiante = mysqli_insert_id($conexion);


    /*
    -----------------------------------
    ELIMINAR REGISTRO TEMPORAL
    -----------------------------------
    */

    $sql = "DELETE FROM registro_temporal
            WHERE chat_id_telegram = ?";

    $stmt = mysqli_prepare($conexion, $sql);

    mysqli_stmt_bind_param($stmt, "s", $chat_id);

    mysqli_stmt_execute($stmt);


    /*
    -----------------------------------
    CONFIRMAR TRANSACCIÓN
    -----------------------------------
    */

    mysqli_commit($conexion);


    /*
    -----------------------------------
    RESPUESTA EXITOSA
    -----------------------------------
    */

    echo json_encode([
        "success" => true,
        "mensaje" => "Registro finalizado correctamente",
        "id_estudiante" => $id_estudiante,
        "chat_id" => $chat_id
    ]);

} catch (Exception $e) {

    /*
    -----------------------------------
    DESHACER CAMBIOS SI HAY ERROR
    -----------------------------------
    */

    mysqli_rollback($conexion);

    echo json_encode([
        "success" => false,
        "mensaje" => "Error al finalizar el registro"
    ]);
}

mysqli_close($conexion);

?>