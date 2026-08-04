<?php

require_once 'config/conexion.php';

$db = new Conexion();

$conexion = $db->conectar();

echo "Conexión exitosa";