<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Dotenv\Dotenv;

// cargar .env
$dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

function validarJWT($rolRequerido = null) {

    $headers = function_exists('getallheaders') ? getallheaders() : [];

    if (!isset($headers['Authorization']) && isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $headers['Authorization'] = $_SERVER['HTTP_AUTHORIZATION'];
    }

    if (!isset($headers['Authorization'])) {
        http_response_code(401);
        echo json_encode(["error" => "Token requerido"]);
        exit;
    }

    $token = str_replace('Bearer ', '', $headers['Authorization']);
    $secret = $_ENV['JWT_SECRET'] ?? null;

    if (!$secret) {
        http_response_code(500);
        echo json_encode(["error" => "JWT_SECRET no definido"]);
        exit;
    }

    try {
        $decoded = JWT::decode($token, new Key($secret, 'HS256'));

        // 🔥 VALIDAR ROL
        if ($rolRequerido && $decoded->rol !== $rolRequerido) {
            http_response_code(403);
            echo json_encode([
                "error" => "Acceso denegado",
                "rol_requerido" => $rolRequerido,
                "rol_actual" => $decoded->rol
            ]);
            exit;
        }

        return $decoded;

    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode([
            "error" => "Token inválido",
            "detalle" => $e->getMessage()
        ]);
        exit;
    }
}