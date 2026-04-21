<?php

// Cargar dotenv si aún no está cargado
if (!isset($_ENV['DB_HOST1'])) {
    require_once __DIR__ . '/../vendor/autoload.php';

    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

try {

    $host = $_ENV['DB_HOST1'];
    $port = $_ENV['DB_PORT1'];
    $db   = $_ENV['DB_NAME1'];
    $user = $_ENV['DB_USER1'];
    $pass = $_ENV['DB_PASS1'];

    // 🔥 PostgreSQL DSN
    $dsn = "pgsql:host=$host;port=$port;dbname=$db";

    $connHolos = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error conexión BD Holos (PostgreSQL)",
        "detalle" => $e->getMessage() // ⚠️ quitar en producción
    ]);
    exit;
}
?>