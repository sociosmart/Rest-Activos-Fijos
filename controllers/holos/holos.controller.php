<?php
require_once __DIR__ . '/../../config/conexionholos.php';
require_once __DIR__ . '/../middlewares/auth.middleware.php';

header('Content-Type: application/json');

$request = $_SERVER['REQUEST_URI'];
$method  = $_SERVER['REQUEST_METHOD'];

$base = '/api_activos_v2/public';
$path = str_replace($base, '', $request);
$path = trim(parse_url($path, PHP_URL_PATH), '/');

switch ($method) {

    case 'POST':

        validarJWT('usuario');

        // 🔥 Mapa de rutas → tablas
        $routes = [
            'holos/categorias' => 'account_asset_category',
            'holos/analitica'  => 'account_analytic_account',
            'holos/companias'  => 'res_company'
        ];

        if (!isset($routes[$path])) {
            http_response_code(400);
            echo json_encode([
                "status" => "error",
                "message" => "Ruta POST no válida",
                "path" => $path
            ]);
            exit;
        }

        try {

            $tabla = $routes[$path];
            $stmt = $connHolos->prepare("SELECT id, name FROM {$tabla}");
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                "status" => "success",
                "total" => count($data),
                "data" => $data
            ]);

        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                "status" => "error",
                "message" => "Error BD",
                "detalle" => $e->getMessage()
            ]);
        }

        exit;

    break;
}

// ❌ NO ENCONTRADO
http_response_code(404);
echo json_encode([
    "error" => "Ruta no encontrada desde controller",
    "path" => $path
]);
exit;