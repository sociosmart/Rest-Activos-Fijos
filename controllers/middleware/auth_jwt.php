<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require_once __DIR__ . '/../../vendor/autoload.php';

function validarToken() {

    $headers = getallheaders();

    if (!isset($headers['Authorization'])) return false;

    $token = str_replace('Bearer ', '', $headers['Authorization']);

    try {
        return (array) JWT::decode($token, new Key($_ENV['JWT_SECRET'], 'HS256'));
    } catch (Exception $e) {
        return false;
    }
}

function esAdmin($user){
    return isset($user['rol']) && $user['rol'] === 'admin';
}
?>