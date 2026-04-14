<?php

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Firebase\JWT\JWT;

$data = json_decode(file_get_contents("php://input"), true);

$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

$stmt = $conn->prepare("SELECT * FROM usuarios WHERE email=:e LIMIT 1");
$stmt->execute(['e'=>$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user ||!password_verify($password, $user['password'])) {
    echo json_encode(["error"=>"Credenciales inválidas"]);
    exit;
}

$payload = [
    "id" => $user['id'],
    "rol" => $user['rol'], // 🔥 dinámico
    "exp" => time() + (60*60*24)
];

$token = JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');

echo json_encode([
    "success" => true,
    "token" => $token,
    "usuario" => $payload
]);
?>