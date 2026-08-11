<?php
    // script para crear una conexión con la BD

    require_once 'constantes.php';

    function conectar()
    {
        $conexion = mysqli_connect(HOST, USER, PW, BD);

        if(!$conexion)
          {
        die("Error: ".mysqli_connect_error());
          }

        mysqli_set_charset($conexion,"utf8mb4");

        return $conexion;
    }

    //Probar conexion a BD
    //echo '<br>Probando conexión a la BD ...';
    //$con = conectar(); 
?>