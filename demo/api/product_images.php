<?php
// api/product_images.php
// API para manejar imágenes de productos (tabla product_images)
// VERSIÓN CORREGIDA PARA XAMPP - 19/11/2025

header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once __DIR__ . "/config.php";

// ============================================================================
// CONFIGURACIÓN DE RUTAS - AJUSTADO PARA XAMPP
// ============================================================================
// Ruta física donde se guardan las imágenes
define("PRODUCT_IMAGES_DIR", __DIR__ . "/../uploads/products");

// Ruta base URL para acceder a las imágenes desde el navegador
// ⚠️ AJUSTAR SEGÚN TU ESTRUCTURA:
// - Si tu app está en http://localhost/demo/, usa: "/demo/uploads/products"
// - Si demo/ es tu document root, usa: "/uploads/products"
define("PRODUCT_IMAGES_BASE_URL", "/demo/uploads/products");

/**
 * CORS preflight
 */
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(204);
    exit;
}

$method = $_SERVER["REQUEST_METHOD"] ?? "GET";

/**
 * Limpieza básica de string.
 */
function cleanString($value): ?string
{
    if ($value === null) {
        return null;
    }
    $value = trim((string)$value);
    if ($value === "") {
        return null;
    }
    return $value;
}

/**
 * Obtiene datos del body (JSON o form-data).
 */
function getRequestData(): array
{
    $contentType = $_SERVER["CONTENT_TYPE"] ?? "";

    if (stripos($contentType, "application/json") !== false) {
        $raw  = file_get_contents("php://input");
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return [];
        }
        return $data;
    }

    // form-data / x-www-form-urlencoded
    return $_POST;
}

/**
 * Helper para responder error JSON.
 */
function respondError(int $code, string $message, ?string $details = null): void
{
    http_response_code($code);
    $payload = ["error" => $message];
    if ($details !== null) {
        $payload["details"] = $details;
    }
    echo json_encode($payload);
    exit;
}

/**
 * Verifica que exista el producto.
 */
function productExists(PDO $pdo, int $productId): bool
{
    $stmt = $pdo->prepare("SELECT id FROM products WHERE id = :id");
    $stmt->execute([":id" => $productId]);
    return (bool)$stmt->fetchColumn();
}

// ============================================================================
// ENDPOINT: LIST - Lista imágenes de un producto
// ============================================================================
/**
 * GET /product_images.php?action=list&product_id=123
 */
function handleListImages(PDO $pdo): void
{
    $productId = (int)($_GET["product_id"] ?? 0);
    if ($productId <= 0) {
        respondError(400, "Parámetro 'product_id' inválido.");
    }

    if (!productExists($pdo, $productId)) {
        respondError(404, "Producto no encontrado.");
    }

    $sql = "
        SELECT
            id,
            product_id,
            path,
            is_main,
            sort_order,
            created_at
        FROM product_images
        WHERE product_id = :product_id
        ORDER BY sort_order ASC, id ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([":product_id" => $productId]);
    $images = $stmt->fetchAll();

    echo json_encode([
        "product_id" => $productId,
        "images"     => $images
    ]);
}

// ============================================================================
// ENDPOINT: UPLOAD - Sube una imagen para un producto
// ============================================================================
/**
 * POST /product_images.php?action=upload
 *  - form-data:
 *      - product_id (int)
 *      - image (file)
 */
function handleUploadImage(PDO $pdo): void
{
    // Para uploads, usamos directamente $_POST / $_FILES.
    $productId = (int)($_POST["product_id"] ?? 0);
    if ($productId <= 0) {
        respondError(400, "Parámetro 'product_id' inválido.");
    }

    if (!productExists($pdo, $productId)) {
        respondError(404, "Producto no encontrado.");
    }

    if (!isset($_FILES["image"])) {
        respondError(400, "No se encontró el archivo 'image' en la petición.");
    }

    $file = $_FILES["image"];

    if (!is_array($file) || $file["error"] !== UPLOAD_ERR_OK) {
        respondError(400, "Error al subir el archivo.", "Código de error: " . ($file["error"] ?? "desconocido"));
    }

    // Validaciones básicas
    $maxSize = 5 * 1024 * 1024; // 5 MB
    if ($file["size"] > $maxSize) {
        respondError(400, "La imagen supera el tamaño máximo permitido (5MB).");
    }

    $originalName = $file["name"] ?? "image";
    $ext          = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    $allowedExts = ["jpg", "jpeg", "png", "webp"];
    if (!in_array($ext, $allowedExts, true)) {
        respondError(400, "Formato de imagen no permitido. Extensiones válidas: jpg, jpeg, png, webp.");
    }

    // Verificar que es una imagen real (seguridad)
    $imageInfo = getimagesize($file["tmp_name"]);
    if ($imageInfo === false) {
        respondError(400, "El archivo no es una imagen válida.");
    }

    // Directorio destino: /uploads/products/{product_id}
    $productDir = rtrim(PRODUCT_IMAGES_DIR, "/") . "/" . $productId;
    if (!is_dir($productDir)) {
        if (!mkdir($productDir, 0775, true) && !is_dir($productDir)) {
            respondError(500, "No se pudo crear el directorio de destino para las imágenes.");
        }
    }

    // Nombre de archivo único
    $filename = "p{$productId}_" . bin2hex(random_bytes(8)) . "." . $ext;
    $destPath = $productDir . "/" . $filename;

    if (!move_uploaded_file($file["tmp_name"], $destPath)) {
        respondError(500, "No se pudo mover el archivo subido.");
    }

    // Path relativo que se almacenará en DB
    $relativePath = rtrim(PRODUCT_IMAGES_BASE_URL, "/") . "/" . $productId . "/" . $filename;

    try {
        $pdo->beginTransaction();

        // ¿Ya tiene principal?
        $stmtHasMain = $pdo->prepare("
            SELECT COUNT(*) FROM product_images
            WHERE product_id = :product_id AND is_main = 1
        ");
        $stmtHasMain->execute([":product_id" => $productId]);
        $hasMain = (int)$stmtHasMain->fetchColumn() > 0;

        // sort_order siguiente
        $stmtSort = $pdo->prepare("
            SELECT COALESCE(MAX(sort_order), 0) + 1 AS next_sort
            FROM product_images
            WHERE product_id = :product_id
        ");
        $stmtSort->execute([":product_id" => $productId]);
        $nextSort = (int)$stmtSort->fetchColumn();

        $isMain = $hasMain ? 0 : 1;

        // Insertar registro en product_images
        $stmtInsert = $pdo->prepare("
            INSERT INTO product_images (product_id, path, is_main, sort_order, created_at)
            VALUES (:product_id, :path, :is_main, :sort_order, NOW())
        ");
        $stmtInsert->execute([
            ":product_id" => $productId,
            ":path"       => $relativePath,
            ":is_main"    => $isMain,
            ":sort_order" => $nextSort
        ]);

        $imageId = (int)$pdo->lastInsertId();

        // Si es principal, sincronizar products.main_image
        if ($isMain === 1) {
            $stmtUpdateProduct = $pdo->prepare("
                UPDATE products
                SET main_image = :path
                WHERE id = :id
            ");
            $stmtUpdateProduct->execute([
                ":path" => $relativePath,
                ":id"   => $productId
            ]);
        }

        $pdo->commit();

        http_response_code(201);
        echo json_encode([
            "success" => true,
            "image"   => [
                "id"         => $imageId,
                "product_id" => $productId,
                "path"       => $relativePath,
                "is_main"    => $isMain,
                "sort_order" => $nextSort
            ]
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        // Si algo falló, intentamos borrar el archivo físico para no dejar basura
        if (file_exists($destPath)) {
            @unlink($destPath);
        }
        respondError(500, "Error al guardar la imagen en la base de datos.", $e->getMessage());
    }
}

// ============================================================================
// ENDPOINT: SET_MAIN - Marca una imagen como principal
// ============================================================================
/**
 * POST /product_images.php?action=set_main
 *  - JSON o form-data:
 *      - id (id de la imagen en product_images)
 */
function handleSetMain(PDO $pdo): void
{
    $data = getRequestData();
    $id   = (int)($data["id"] ?? 0);

    if ($id <= 0) {
        respondError(400, "Parámetro 'id' inválido.");
    }

    $stmtGet = $pdo->prepare("
        SELECT id, product_id, path
        FROM product_images
        WHERE id = :id
    ");
    $stmtGet->execute([":id" => $id]);
    $image = $stmtGet->fetch();

    if (!$image) {
        respondError(404, "Imagen no encontrada.");
    }

    $productId = (int)$image["product_id"];
    $path      = $image["path"];

    try {
        $pdo->beginTransaction();

        // Poner todas en no principal
        $stmtReset = $pdo->prepare("
            UPDATE product_images
            SET is_main = 0
            WHERE product_id = :product_id
        ");
        $stmtReset->execute([":product_id" => $productId]);

        // Marcar esta como principal
        $stmtSet = $pdo->prepare("
            UPDATE product_images
            SET is_main = 1
            WHERE id = :id
        ");
        $stmtSet->execute([":id" => $id]);

        // Sincronizar products.main_image
        $stmtUpdateProduct = $pdo->prepare("
            UPDATE products
            SET main_image = :path
            WHERE id = :id
        ");
        $stmtUpdateProduct->execute([
            ":path" => $path,
            ":id"   => $productId
        ]);

        $pdo->commit();

        echo json_encode([
            "success" => true
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        respondError(500, "Error al marcar la imagen como principal.", $e->getMessage());
    }
}

// ============================================================================
// ENDPOINT: DELETE - Elimina una imagen
// ============================================================================
/**
 * POST /product_images.php?action=delete
 *  - JSON o form-data:
 *      - id (id de la imagen en product_images)
 */
function handleDeleteImage(PDO $pdo): void
{
    $data = getRequestData();
    $id   = (int)($data["id"] ?? 0);

    if ($id <= 0) {
        respondError(400, "Parámetro 'id' inválido.");
    }

    $stmtGet = $pdo->prepare("
        SELECT id, product_id, path, is_main
        FROM product_images
        WHERE id = :id
    ");
    $stmtGet->execute([":id" => $id]);
    $image = $stmtGet->fetch();

    if (!$image) {
        respondError(404, "Imagen no encontrada.");
    }

    $productId = (int)$image["product_id"];
    $path      = $image["path"];
    $isMain    = (int)$image["is_main"];

    // Resolver ruta física a partir del path guardado
    $fullPath = null;
    $base = rtrim(PRODUCT_IMAGES_BASE_URL, "/") . "/";
    if (strpos($path, $base) === 0) {
        // path es algo como /demo/uploads/products/123/archivo.jpg
        $relativeFile = substr($path, strlen($base)); // "123/archivo.jpg"
        $fullPath = rtrim(PRODUCT_IMAGES_DIR, "/") . "/" . $relativeFile;
    }

    // Primero intentamos borrar archivo físico (si existe).
    if ($fullPath && file_exists($fullPath)) {
        @unlink($fullPath);
    }

    try {
        $pdo->beginTransaction();

        // Borrar registro
        $stmtDelete = $pdo->prepare("
            DELETE FROM product_images
            WHERE id = :id
        ");
        $stmtDelete->execute([":id" => $id]);

        if ($isMain === 1) {
            // Buscar otra imagen para dejar como principal (si existe)
            $stmtNext = $pdo->prepare("
                SELECT id, path
                FROM product_images
                WHERE product_id = :product_id
                ORDER BY sort_order ASC, id ASC
                LIMIT 1
            ");
            $stmtNext->execute([":product_id" => $productId]);
            $next = $stmtNext->fetch();

            if ($next) {
                // Marcarla principal
                $stmtSetMain = $pdo->prepare("
                    UPDATE product_images
                    SET is_main = 1
                    WHERE id = :id
                ");
                $stmtSetMain->execute([":id" => $next["id"]]);

                // Sincronizar products.main_image
                $stmtUpdateProduct = $pdo->prepare("
                    UPDATE products
                    SET main_image = :path
                    WHERE id = :id
                ");
                $stmtUpdateProduct->execute([
                    ":path" => $next["path"],
                    ":id"   => $productId
                ]);
            } else {
                // No quedan imágenes → main_image = NULL
                $stmtClear = $pdo->prepare("
                    UPDATE products
                    SET main_image = NULL
                    WHERE id = :id
                ");
                $stmtClear->execute([":id" => $productId]);
            }
        }

        $pdo->commit();

        echo json_encode([
            "success" => true
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        respondError(500, "Error al eliminar la imagen.", $e->getMessage());
    }
}

// ============================================================================
// ENRUTAMIENTO
// ============================================================================

$action = $_GET["action"] ?? null;

try {
    $pdo = getPDO();
} catch (PDOException $e) {
    respondError(500, "Error de conexión a base de datos", $e->getMessage());
}

switch ($action) {
    case "list":
        if ($method !== "GET") {
            respondError(405, "Método no permitido para 'list'. Usa GET.");
        }
        handleListImages($pdo);
        break;

    case "upload":
        if ($method !== "POST") {
            respondError(405, "Método no permitido para 'upload'. Usa POST.");
        }
        handleUploadImage($pdo);
        break;

    case "set_main":
        if ($method !== "POST") {
            respondError(405, "Método no permitido para 'set_main'. Usa POST.");
        }
        handleSetMain($pdo);
        break;

    case "delete":
        if ($method !== "POST") {
            respondError(405, "Método no permitido para 'delete'. Usa POST.");
        }
        handleDeleteImage($pdo);
        break;

    default:
        respondError(400, "Acción inválida. Acciones válidas: list, upload, set_main, delete.");
        break;
}
