<?php

header('Content-Type: application/json; charset=utf-8');

require_once '../config/conexion.php';

$conexion = conectar();

/*
|--------------------------------------------------------------------------
| Verificar chat_id
|--------------------------------------------------------------------------
*/

if (!isset($_POST['chat_id']) || empty($_POST['chat_id'])) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'No se recibió el chat_id'
    ]);

    exit;
}

$chat_id = trim($_POST['chat_id']);


/*
|--------------------------------------------------------------------------
| Buscar registro temporal
|--------------------------------------------------------------------------
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

if (!$stmt) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'Error al preparar la consulta'
    ]);

    exit;
}

mysqli_stmt_bind_param($stmt, "s", $chat_id);

mysqli_stmt_execute($stmt);

$resultado = mysqli_stmt_get_result($stmt);


/*
|--------------------------------------------------------------------------
| Verificar si existe
|--------------------------------------------------------------------------
*/

if (mysqli_num_rows($resultado) > 0) {

    $registro = mysqli_fetch_assoc($resultado);

    echo json_encode([
        'success' => true,
        'existe_registro' => true,
        'paso' => $registro['paso'],
        'datos' => [
            'nombre' => $registro['nombre'],
            'apellido' => $registro['apellido'],
            'correo' => $registro['correo'],
            'telefono' => $registro['telefono'],
            'curso' => $registro['curso']
        ]
    ]);

} else {

    echo json_encode([
        'success' => true,
        'existe_registro' => false,
        'mensaje' => 'No existe un registro temporal'
    ]);
}


/*
|--------------------------------------------------------------------------
| Cerrar conexión
|--------------------------------------------------------------------------
*/

mysqli_stmt_close($stmt);
mysqli_close($conexion);

?>