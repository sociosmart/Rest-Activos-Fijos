<?php
/*hola */
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../middlewares/auth.middleware.php';

$request = $_SERVER['REQUEST_URI'];
$method  = $_SERVER['REQUEST_METHOD'];

// limpiar ruta
$base = '/api_activos_v2/public';
$path = str_replace($base, '', $request);
$path = trim(parse_url($path, PHP_URL_PATH), '/');

// ===============================
// 🖼️ GET /activo/{id}/imagen
// ===============================
if ($method === 'GET' && preg_match('/^activos?\/(\d+)\/imagen$/', $path, $matches)) {

    validarJWT(); // usuario o admin

    $id = (int)$matches[1];

    $stmt = $conn->prepare("SELECT fotografia FROM activo_fijo WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $img = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$img || empty($img['fotografia'])) {
        http_response_code(404);
        echo json_encode(["error" => "Imagen no encontrada"]);
        exit;
    }

    // 🔒 validar ruta segura
    if (strpos($img['fotografia'], 'uploads/') !== 0) {
        http_response_code(400);
        echo json_encode(["error" => "Ruta de imagen inválida"]);
        exit;
    }

    $ruta = __DIR__ . '/../../' . $img['fotografia'];

    if (!file_exists($ruta)) {
        http_response_code(404);
        echo json_encode(["error" => "Archivo no existe"]);
        exit;
    }

    if (ob_get_length()) ob_clean();

    header("Content-Type: " . mime_content_type($ruta));
    header("Content-Length: " . filesize($ruta));

    readfile($ruta);
    exit;
}

// ===============================
// 📦 REST DE ACTIVOS
// ===============================
switch ($method) {

    // ===============================
    // GET
    // ===============================
    case 'GET':

        validarJWT(); // cualquier usuario

        // 🔹 GET /activo/{id}
        if (preg_match('/^activos?\/(\d+)$/', $path, $matches)) {

            $id = (int)$matches[1];

            $stmt = $conn->prepare("SELECT * FROM activo_fijo WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $activo = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$activo) {
                http_response_code(404);
                echo json_encode(["error" => "Activo no encontrado"]);
                exit;
            }

            echo json_encode([$activo]);
            exit;
        }

        // 🔹 GET /activos
        if ($path === 'activos') {

            $stmt = $conn->query("SELECT * FROM activo_fijo");
            $activos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode($activos);
            exit;
        }

        break;

    // ===============================
    // POST (solo admin)
    // ===============================
    case 'POST':

    validarJWT('usuario');

    // ===============================
    // 🔹 VALIDAR CAMPOS
    // ===============================
    if (!isset($_POST['nombre'])) {
        http_response_code(400);
        echo json_encode(["error" => "Nombre requerido"]);
        exit;
    }

    // ===============================
    // 🔹 VALIDAR IMAGEN
    // ===============================
    if (!isset($_FILES['fotografia'])) {
        http_response_code(400);
        echo json_encode(["error" => "Imagen requerida"]);
        exit;
    }

    $archivo = $_FILES['fotografia'];

    // tipos permitidos
    $tiposPermitidos = ['image/jpeg', 'image/png', 'image/jpg'];

    if (!in_array($archivo['type'], $tiposPermitidos)) {
        http_response_code(400);
        echo json_encode(["error" => "Tipo de imagen no permitido"]);
        exit;
    }

    // tamaño máximo (2MB)
    if ($archivo['size'] > 2 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(["error" => "Imagen demasiado grande"]);
        exit;
    }

    // validar que sea imagen real
    if (!getimagesize($archivo['tmp_name'])) {
        http_response_code(400);
        echo json_encode(["error" => "Archivo no es imagen válida"]);
        exit;
    }

    // ===============================
    // 🔥 GENERAR NOMBRE SEGURO
    // ===============================
    $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
    $nombreSeguro = uniqid() . '.' . $extension;

    $rutaRelativa = 'uploads/' . $nombreSeguro;
    $rutaFisica = __DIR__ . '/../../' . $rutaRelativa;

    // ===============================
    // 🚀 GUARDAR ARCHIVO
    // ===============================
    if (!move_uploaded_file($archivo['tmp_name'], $rutaFisica)) {
        http_response_code(500);
        echo json_encode(["error" => "Error al guardar imagen"]);
        exit;
    }

    // ===============================
    // 💾 INSERTAR EN BD
    // ===============================
    $stmt = $conn->prepare("
        INSERT INTO activo_fijo 
        (nombre, ubicacion, fecha, fotografia, compania, latitud, longitud)
        VALUES 
        (:nombre, :ubicacion, :fecha, :fotografia, :compania, :latitud, :longitud)
    ");

    $stmt->execute([
        'nombre' => $_POST['nombre'] ?? null,
        'ubicacion' => $_POST['ubicacion'] ?? null,
        'fecha' => $_POST['fecha'] ?? null,
        'fotografia' => $rutaRelativa, // 🔥 SIEMPRE ESTA
        'compania' => $_POST['compania'] ?? null,
        'latitud' => $_POST['latitud'] ?? null,
        'longitud' => $_POST['longitud'] ?? null
    ]);
    $idInsertado = $conn->lastInsertId();

    echo json_encode([
        "mensaje" => "Activo creado",
        "imagen" => $rutaRelativa,
         "id" => $idInsertado
    ]);
    exit;
    case 'PUT':

    validarJWT('admin');

    if (preg_match('/^activos?\/(\d+)$/', $path, $matches)) {

        $id = (int)$matches[1];

        // ===============================
        // 🔹 OBTENER ACTUAL
        // ===============================
        $stmt = $conn->prepare("SELECT fotografia FROM activo_fijo WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $actual = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$actual) {
            http_response_code(404);
            echo json_encode(["error" => "Activo no encontrado"]);
            exit;
        }

        $rutaImagen = $actual['fotografia']; // imagen actual

        // ===============================
        // 🔹 SI VIENE NUEVA IMAGEN
        // ===============================
        if (isset($_FILES['fotografia']) && $_FILES['fotografia']['tmp_name']) {

            $archivo = $_FILES['fotografia'];

            $tiposPermitidos = ['image/jpeg', 'image/png', 'image/jpg'];

            if (!in_array($archivo['type'], $tiposPermitidos)) {
                http_response_code(400);
                echo json_encode(["error" => "Tipo de imagen no permitido"]);
                exit;
            }

            if (!getimagesize($archivo['tmp_name'])) {
                http_response_code(400);
                echo json_encode(["error" => "Archivo no es imagen válida"]);
                exit;
            }

            // 🔥 generar nuevo nombre
            $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
            $nombreSeguro = uniqid() . '.' . $extension;

            $nuevaRutaRelativa = 'uploads/' . $nombreSeguro;
            $nuevaRutaFisica = __DIR__ . '/../../' . $nuevaRutaRelativa;

            // guardar nueva imagen
            if (!move_uploaded_file($archivo['tmp_name'], $nuevaRutaFisica)) {
                http_response_code(500);
                echo json_encode(["error" => "Error al guardar nueva imagen"]);
                exit;
            }

            // 🔥 eliminar imagen anterior
            if (!empty($rutaImagen) && strpos($rutaImagen, 'uploads/') === 0) {
                $rutaAnterior = __DIR__ . '/../../' . $rutaImagen;

                if (file_exists($rutaAnterior)) {
                    unlink($rutaAnterior);
                }
            }

            // actualizar ruta
            $rutaImagen = $nuevaRutaRelativa;
        }

        // ===============================
        // 🔹 CAMPOS (tipo PATCH)
        // ===============================
        $camposPermitidos = [
            'nombre',
            'ubicacion',
            'fecha',
            'compania',
            'latitud',
            'longitud'
        ];

        $set = [];
        $params = [];

        foreach ($_POST as $campo => $valor) {
            if (in_array($campo, $camposPermitidos)) {
                $set[] = "$campo = :$campo";
                $params[$campo] = $valor;
            }
        }

        // 🔥 siempre actualizar imagen si cambió
        if ($rutaImagen) {
            $set[] = "fotografia = :fotografia";
            $params['fotografia'] = $rutaImagen;
        }

        if (empty($set)) {
            http_response_code(400);
            echo json_encode(["error" => "No hay datos para actualizar"]);
            exit;
        }

        $params['id'] = $id;

        $sql = "UPDATE activo_fijo SET " . implode(', ', $set) . " WHERE id = :id";

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);

        echo json_encode([
            "mensaje" => "Activo actualizado",
            "imagen_actual" => $rutaImagen
        ]);
        exit;
    }

    break;

    // ===============================
    // DELETE (solo admin + elimina imagen)
    // ===============================
    case 'DELETE':

        validarJWT('admin');

        if (preg_match('/^activos?\/(\d+)$/', $path, $matches)) {

            $id = (int)$matches[1];

            // 1. obtener imagen
            $stmt = $conn->prepare("SELECT fotografia FROM activo_fijo WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $activo = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$activo) {
                http_response_code(404);
                echo json_encode(["error" => "Activo no encontrado"]);
                exit;
            }

            // 2. eliminar archivo seguro
            if (!empty($activo['fotografia']) && strpos($activo['fotografia'], 'uploads/') === 0) {

                $ruta = __DIR__ . '/../../' . $activo['fotografia'];

                if (file_exists($ruta)) {
                    unlink($ruta);
                }
            }

            // 3. eliminar BD
            $stmt = $conn->prepare("DELETE FROM activo_fijo WHERE id = :id");
            $stmt->execute(['id' => $id]);

            echo json_encode([
                "mensaje" => "Activo eliminado",
                "imagen_eliminada" => !empty($activo['fotografia'])
            ]);
            exit;
        }

        break;
}

// ===============================
// ❌ NO ENCONTRADO
// ===============================
http_response_code(404);
echo json_encode([
    "error" => "Ruta no encontrada desde controller",
    "path" => $path
]);
exit;