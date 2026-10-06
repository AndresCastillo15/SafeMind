<?php

date_default_timezone_set('America/Bogota');

// script para crear una conexión con la BD

require_once 'constantes.php';

function conectar()
{
    $conexion = mysqli_connect(HOST, USER, PW, BD);

    if (!$conexion) {
        die("Error: " . mysqli_connect_error());
    }

    mysqli_set_charset($conexion, "utf8mb4");

    mysqli_query($conexion, "SET time_zone = '-05:00'");

    return $conexion;
}