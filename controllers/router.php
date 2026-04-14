<?php

$request = $_SERVER['REQUEST_URI'];
$method  = $_SERVER['REQUEST_METHOD'];

// limpiar ruta base
$base = '/api_activos_v2/public';
$path = str_replace($base, '', $request);
$path = trim(parse_url($path, PHP_URL_PATH), '/');

// ================= ROUTES =================

// 🔥 AUTH
if ($path === 'auth' && $method === 'POST') {
    require __DIR__ . '/auth/login.php';
    exit;
}

// 🔥 ACTIVOS (incluye /activo/29)
if (
    $path === 'activos' ||
    strpos($path, 'activo/') === 0 ||
    strpos($path, 'activos') === 0
) {
    require __DIR__ . '/activos/activos.controller.php';
    exit;
}

// ❌ NO ENCONTRADO
http_response_code(404);
echo json_encode([
    "error" => "Ruta no encontrada",
    "path" => $path
]);
exit; // 🔥 CLAVE