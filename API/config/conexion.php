<?php

class Conexion {

    private $host = "sql302.infinityfree.com";
    private $database = "if0_42519781_safemind_bd";
    private $user = "if0_42519781";
    private $password = "TU_CONTRASEÑA";

    public function conectar() {

        $conexion = new mysqli(
            $this->host,
            $this->user,
            $this->password,
            $this->database
        );

        if ($conexion->connect_error) {

            http_response_code(500);

            die(json_encode([
                "success" => false,
                "message" => "Error de conexión a la base de datos."
            ]));

        }

        $conexion->set_charset("utf8mb4");

        return $conexion;

    }

}