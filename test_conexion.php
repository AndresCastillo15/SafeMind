<?php

error_reporting(E_ALL);
ini_set('display_errors',1);

require_once 'API/config/conexion.php';

$conexion = conectar();

echo "Conexión exitosa";
