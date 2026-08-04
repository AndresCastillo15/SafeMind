<?php
require 'config.php';

// Guardar información en la sesión [10, 11]
$_SESSION['usuario'] = "admin";
$_SESSION['email'] = "admin@ejemplo.com";

// Es una buena práctica regenerar el ID al iniciar sesión para evitar fijación de sesión [12, 13]
session_regenerate_id(true);

echo "Sesión iniciada. <a href='perfil.php'>Ir al perfil</a>";