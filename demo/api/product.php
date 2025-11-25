<?php
// api/product.php
// API para CRUD de productos, con paginación, filtros y búsqueda.

header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once __DIR__ . "/config.php";

/**
 * Manejo de preflight (CORS).
 */
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(204);
    exit;
}

/**
 * Helper para obtener el método HTTP.
 */
$method = $_SERVER["REQUEST_METHOD"] ?? "GET";

/**
 * Limpia strings básicos (trim, etc.).
 *
 * @param mixed $value
 * @return string|null
 */
function cleanString($value): ?string
{
    if ($value === null) {
        return null;
    }
    $value = trim((string) $value);
    if ($value === "") {
        return null;
    }
    return $value;
}

/**
 * Obtiene el cuerpo de la request.
 *
 * - Si es JSON, lo decodifica y devuelve array.
 * - Si es form-data / x-www-form-urlencoded, usa $_POST.
 *
 * @return array
 */
function getRequestData(): array
{
    $contentType = $_SERVER["CONTENT_TYPE"] ?? "";

    if (stripos($contentType, "application/json") !== false) {
        $raw = file_get_contents("php://input");
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return [];
        }
        return $data;
    }

    // Por defecto, asumimos form-data o x-www-form-urlencoded
    return $_POST;
}

/**
 * Valida y normaliza los datos de producto de create/update.
 *
 * @param array $data
 * @param bool  $isUpdate
 * @return array [errors => string[], product => array|null]
 */
function validateProductData(array $data, bool $isUpdate = false): array
{
    $errors = [];
    $product = [];

    // Campos obligatorios en CREATE. En UPDATE, id también.
    if ($isUpdate) {
        $id = (int) ($data["id"] ?? 0);
        if ($id <= 0) {
            $errors[] = "ID de producto inválido.";
        } else {
            $product["id"] = $id;
        }
    }

    $pid = cleanString($data["pid"] ?? null);
    $name = cleanString($data["name"] ?? null);

    if (!$pid) {
        $errors[] = "El campo 'pid' es obligatorio.";
    } else {
        $product["pid"] = $pid;
    }

    if (!$name) {
        $errors[] = "El campo 'name' es obligatorio.";
    } else {
        $product["name"] = $name;
    }

    // Campos opcionales
    $product["producer"] = cleanString($data["producer"] ?? null);
    $product["varietal"] = cleanString($data["varietal"] ?? null);
    $product["origin"] = cleanString($data["origin"] ?? null);
    $product["short_description"] = cleanString($data["short_description"] ?? null);

    // Año puede ser 0 o null, lo dejamos opcional
    $year = $data["year"] ?? null;
    if ($year !== null && $year !== "") {
        $year = (int) $year;
        $product["year"] = $year;
    } else {
        $product["year"] = null;
    }

    // Precio de lista
    if (!$isUpdate || array_key_exists("list_price", $data)) {
        if (!isset($data["list_price"]) || $data["list_price"] === "") {
            $errors[] = "El campo 'list_price' es obligatorio.";
        } else {
            $price = (float) $data["list_price"];
            if ($price < 0) {
                $errors[] = "El 'list_price' no puede ser negativo.";
            } else {
                $product["list_price"] = $price;
            }
        }
    }

    // Stock status
    $validStockStatuses = ["AVAILABLE", "LOW", "OUT"];
    if (!$isUpdate || array_key_exists("stock_status", $data)) {
        $stockStatusRaw = $data["stock_status"] ?? null;
        $stockStatus = $stockStatusRaw !== null ? strtoupper(trim((string) $stockStatusRaw)) : null;
        if (!$stockStatus || !in_array($stockStatus, $validStockStatuses, true)) {
            $errors[] = "El campo 'stock_status' es obligatorio y debe ser uno de: " . implode(", ", $validStockStatuses);
        } else {
            $product["stock_status"] = $stockStatus;
        }
    }


    // Active
    if (!$isUpdate || array_key_exists("active", $data)) {
        $active = $data["active"] ?? 1;
        $active = (int) $active;
        $product["active"] = $active ? 1 : 0;
    }

    return [
        "errors" => $errors,
        "product" => $errors ? null : $product
    ];
}

/**
 * Maneja listado de productos con paginación y filtros.
 *
 * GET /product.php?action=list&search=...&stock_status=...&page=1&per_page=10
 */
function handleList(PDO $pdo): void
{
    $page = max(1, (int) ($_GET["page"] ?? 1));
    $perPage = max(1, (int) ($_GET["per_page"] ?? 10));

    $offset = ($page - 1) * $perPage;

    // Filtros opcionales
    $search = cleanString($_GET["search"] ?? null);
    $stockStatus = cleanString($_GET["stock_status"] ?? null);
    $onlyActive = isset($_GET["only_active"]) ? (int) $_GET["only_active"] : null;
    $onlyPromo = isset($_GET["only_promo"]) ? (int) $_GET["only_promo"] : null;

    $where = [];
    $params = [];

    if ($search) {
        $where[] = "(p.name LIKE :search OR p.pid LIKE :search OR p.producer LIKE :search)";
        $params[":search"] = "%" . $search . "%";
    }

    if ($stockStatus) {
        $where[] = "p.stock_status = :stock_status";
        $params[":stock_status"] = $stockStatus;
    }

    if ($onlyActive !== null) {
        $where[] = "p.active = :active_filter";
        $params[":active_filter"] = $onlyActive ? 1 : 0;
    }

    if ($onlyPromo !== null) {
        $where[] = "EXISTS (SELECT 1 FROM promotions pr WHERE pr.product_id = p.id AND pr.active = 1)";
    }

    $whereSql = "";
    if (!empty($where)) {
        $whereSql = "WHERE " . implode(" AND ", $where);
    }

    // Conteo total para paginación
    $sqlCount = "
        SELECT COUNT(*) AS total
        FROM products p
        $whereSql
    ";
    $stmt = $pdo->prepare($sqlCount);
    $stmt->execute($params);
    $total = (int) $stmt->fetchColumn();

    // Listado paginado
    $sql = "
        SELECT
            p.id,
            p.pid,
            p.name,
            p.producer,
            p.varietal,
            p.origin,
            p.year,
            p.short_description,
            p.list_price,
            p.stock_status,
            p.main_image,
            p.active,
            p.created_at,
            p.updated_at
        FROM products p
        $whereSql
        ORDER BY p.created_at DESC
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $pdo->prepare($sql);

    // Bind de parámetros dinámicos
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(":limit", $perPage, PDO::PARAM_INT);
    $stmt->bindValue(":offset", $offset, PDO::PARAM_INT);

    $stmt->execute();
    $rows = $stmt->fetchAll();

    $totalPages = $perPage > 0 ? (int) ceil($total / $perPage) : 1;

    echo json_encode([
        "data" => $rows,
        "pagination" => [
            "page" => $page,
            "per_page" => $perPage,
            "total" => $total,
            "totalPages" => $totalPages
        ]
    ]);
}

/**
 * Detalle de producto por ID.
 *
 * GET /product.php?action=detail&id=123
 */
function handleDetail(PDO $pdo): void
{
    $id = (int) ($_GET["id"] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(["error" => "ID inválido."]);
        return;
    }

    $sql = "
        SELECT
            p.id,
            p.pid,
            p.name,
            p.producer,
            p.varietal,
            p.origin,
            p.year,
            p.short_description,
            p.list_price,
            p.stock_status,
            p.main_image,
            p.active,
            p.created_at,
            p.updated_at
        FROM products p
        WHERE p.id = :id
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([":id" => $id]);
    $row = $stmt->fetch();

    if (!$row) {
        http_response_code(404);
        echo json_encode(["error" => "Producto no encontrado"]);
        return;
    }

    echo json_encode($row);
}

/**
 * Creación de producto.
 *
 * POST /product.php?action=create
 * Body JSON o form con:
 *  - pid
 *  - name
 *  - list_price
 *  - stock_status
 *  - active (opcional, por defecto 1)
 */
function handleCreate(PDO $pdo): void
{
    $data = getRequestData();

    $validation = validateProductData($data, false);
    if (!empty($validation["errors"])) {
        http_response_code(400);
        echo json_encode([
            "error" => "Datos inválidos",
            "details" => $validation["errors"]
        ]);
        return;
    }

    $p = $validation["product"];

    try {
        $sql = "
            INSERT INTO products (
                pid,
                name,
                producer,
                varietal,
                origin,
                year,
                short_description,
                list_price,
                stock_status,
                active
            ) VALUES (
                :pid,
                :name,
                :producer,
                :varietal,
                :origin,
                :year,
                :short_description,
                :list_price,
                :stock_status,
                :active
            )
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ":pid" => $p["pid"],
            ":name" => $p["name"],
            ":producer" => $p["producer"],
            ":varietal" => $p["varietal"],
            ":origin" => $p["origin"],
            ":year" => $p["year"],
            ":short_description" => $p["short_description"],
            ":list_price" => $p["list_price"],
            ":stock_status" => $p["stock_status"],
            ":active" => $p["active"]
        ]);

        $newId = (int) $pdo->lastInsertId();

        http_response_code(201);
        echo json_encode([
            "success" => true,
            "id" => $newId
        ]);
    } catch (PDOException $e) {
        // Posible violación de UNIQUE en pid
        if ($e->getCode() === "23000") {
            http_response_code(409);
            echo json_encode([
                "error" => "Conflicto",
                "details" => "Ya existe un producto con el mismo 'pid'."
            ]);
            return;
        }

        http_response_code(500);
        echo json_encode([
            "error" => "Error al crear el producto",
            "details" => $e->getMessage()
        ]);
    }
}

/**
 * Actualización de producto.
 *
 * POST /product.php?action=update
 * Body con al menos:
 *  - id
 *  - pid
 *  - name
 *  - list_price
 *  - stock_status
 *  - active
 */
function handleUpdate(PDO $pdo): void
{
    $data = getRequestData();

    $validation = validateProductData($data, true);
    if (!empty($validation["errors"])) {
        http_response_code(400);
        echo json_encode([
            "error" => "Datos inválidos",
            "details" => $validation["errors"]
        ]);
        return;
    }

    $p = $validation["product"];
    $id = $p["id"];

    // Verificar que exista el producto
    $stmtCheck = $pdo->prepare("SELECT id FROM products WHERE id = :id");
    $stmtCheck->execute([":id" => $id]);
    if (!$stmtCheck->fetch()) {
        http_response_code(404);
        echo json_encode(["error" => "Producto no encontrado"]);
        return;
    }

    try {
        $sql = "
            UPDATE products
            SET
                pid              = :pid,
                name             = :name,
                producer         = :producer,
                varietal         = :varietal,
                origin           = :origin,
                year             = :year,
                short_description= :short_description,
                list_price       = :list_price,
                stock_status     = :stock_status,
                active           = :active
            WHERE id = :id
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ":pid" => $p["pid"],
            ":name" => $p["name"],
            ":producer" => $p["producer"],
            ":varietal" => $p["varietal"],
            ":origin" => $p["origin"],
            ":year" => $p["year"],
            ":short_description" => $p["short_description"],
            ":list_price" => $p["list_price"],
            ":stock_status" => $p["stock_status"],
            ":active" => $p["active"],
            ":id" => $id
        ]);

        echo json_encode([
            "success" => true
        ]);
    } catch (PDOException $e) {
        if ($e->getCode() === "23000") {
            http_response_code(409);
            echo json_encode([
                "error" => "Conflicto",
                "details" => "Ya existe otro producto con el mismo 'pid'."
            ]);
            return;
        }

        http_response_code(500);
        echo json_encode([
            "error" => "Error al actualizar el producto",
            "details" => $e->getMessage()
        ]);
    }
}

/**
 * Borrado de producto.
 *
 * POST /product.php?action=delete
 * Body:
 *  - id
 */
function handleDelete(PDO $pdo): void
{
    $data = getRequestData();
    $id = (int) ($data["id"] ?? 0);

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(["error" => "ID inválido para eliminar."]);
        return;
    }

    // Verificar existencia
    $stmtCheck = $pdo->prepare("SELECT id FROM products WHERE id = :id");
    $stmtCheck->execute([":id" => $id]);
    if (!$stmtCheck->fetch()) {
        http_response_code(404);
        echo json_encode(["error" => "Producto no encontrado."]);
        return;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = :id");
        $stmt->execute([":id" => $id]);

        echo json_encode(["success" => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            "error" => "Error al eliminar el producto",
            "details" => $e->getMessage()
        ]);
    }
}

// -----------------------------------------------------------------------------
// Enrutamiento por acción
// -----------------------------------------------------------------------------

$action = $_GET["action"] ?? "list";

try {
    // getPDO() viene desde config.php
    $pdo = getPDO();
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "Error de conexión a base de datos",
        "details" => $e->getMessage()
    ]);
    exit;
}

switch ($action) {
    case "list":
        if ($method !== "GET") {
            http_response_code(405);
            echo json_encode(["error" => "Método no permitido para 'list'. Usa GET."]);
            exit;
        }
        handleList($pdo);
        break;

    case "detail":
        if ($method !== "GET") {
            http_response_code(405);
            echo json_encode(["error" => "Método no permitido para 'detail'. Usa GET."]);
            exit;
        }
        handleDetail($pdo);
        break;

    case "create":
        if ($method !== "POST") {
            http_response_code(405);
            echo json_encode(["error" => "Método no permitido para 'create'. Usa POST."]);
            exit;
        }
        handleCreate($pdo);
        break;

    case "update":
        if ($method !== "POST") {
            http_response_code(405);
            echo json_encode(["error" => "Método no permitido para 'update'. Usa POST."]);
            exit;
        }
        handleUpdate($pdo);
        break;

    case "delete":
        if ($method !== "POST") {
            http_response_code(405);
            echo json_encode(["error" => "Método no permitido para 'delete'. Usa POST."]);
            exit;
        }
        handleDelete($pdo);
        break;

    default:
        http_response_code(400);
        echo json_encode([
            "error" => "Acción inválida",
            "details" => "Acciones válidas: list, detail, create, update, delete."
        ]);
        break;
}
