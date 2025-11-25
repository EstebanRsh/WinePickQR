<?php
// api/promotion.php
// API para manejar promociones:
// - Promociones individuales de producto (product_promotions)
// - Promociones combo (combo_promotions)

header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once __DIR__ . "/config.php";

/**
 * CORS preflight
 */
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(204);
    exit;
}

$method = $_SERVER["REQUEST_METHOD"];

try {
    $pdo = getPDO();
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "error"   => "Error de conexión a la base de datos",
        "details" => $e->getMessage()
    ]);
    exit;
}

/**
 * Lee el cuerpo de la petición:
 * - Intenta JSON (application/json)
 * - Si no, usa $_POST
 */
function getRequestData(): array
{
    $raw = file_get_contents("php://input");
    if (!empty($raw)) {
        $json = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
            return $json;
        }
    }
    if (!empty($_POST)) {
        return $_POST;
    }
    return [];
}

/**
 * Limpia string: trim y null si queda vacía.
 */
function cleanString(?string $value): ?string
{
    if ($value === null) return null;
    $value = trim($value);
    return $value === "" ? null : $value;
}

/* =========================================================
 * VALIDACIONES: PROMO INDIVIDUAL (product_promotions)
 * =======================================================*/

/**
 * Valida datos de ProductPromotion para CREATE/UPDATE.
 *
 * Estructura esperada:
 * - product_id (obligatorio en create)
 * - percent (opcional)
 * - pack_size (opcional)
 * - pack_price (opcional)
 * - start_date (YYYY-MM-DD obligatorio)
 * - end_date   (YYYY-MM-DD obligatorio)
 * - note (opcional)
 * - active (opcional)
 *
 * Regla: XOR entre percent y (pack_size + pack_price).
 */
function validateProductPromotionData(array $data, bool $isUpdate = false): array
{
    $errors = [];
    $promo  = [];

    if ($isUpdate) {
        $id = (int)($data["id"] ?? 0);
        if ($id <= 0) {
            $errors[] = "ID de promoción inválido.";
        } else {
            $promo["id"] = $id;
        }
    }

    // product_id
    if (!$isUpdate || array_key_exists("product_id", $data)) {
        if (!isset($data["product_id"]) || (int)$data["product_id"] <= 0) {
            $errors[] = "El campo 'product_id' es obligatorio y debe ser > 0.";
        } else {
            $promo["product_id"] = (int)$data["product_id"];
        }
    }

    // percent
    $percent = $data["percent"] ?? null;
    if ($percent !== null && $percent !== "") {
        $percent = (float)$percent;
        if ($percent <= 0 || $percent > 100) {
            $errors[] = "El 'percent' debe estar entre 0 y 100.";
        } else {
            $promo["percent"] = $percent;
        }
    } else {
        $promo["percent"] = null;
    }

    // pack_size y pack_price
    $packSize  = $data["pack_size"]  ?? null;
    $packPrice = $data["pack_price"] ?? null;

    if ($packSize !== null && $packSize !== "") {
        $packSize = (int)$packSize;
        if ($packSize < 2) {
            $errors[] = "El 'pack_size' debe ser al menos 2.";
        } else {
            $promo["pack_size"] = $packSize;
        }
    } else {
        $promo["pack_size"] = null;
    }

    if ($packPrice !== null && $packPrice !== "") {
        $packPrice = (float)$packPrice;
        if ($packPrice <= 0) {
            $errors[] = "El 'pack_price' debe ser mayor a 0.";
        } else {
            $promo["pack_price"] = $packPrice;
        }
    } else {
        $promo["pack_price"] = null;
    }

    // Regla XOR: o es percent, o es pack_size+pack_price
    $hasPercent = $promo["percent"] !== null;
    $hasPack    = $promo["pack_size"] !== null && $promo["pack_price"] !== null;

    if (!($hasPercent xor $hasPack)) {
        $errors[] = "Debes definir o 'percent' O 'pack_size'+'pack_price', pero no ambos ni ninguno.";
    }

    // Fechas
    $startDate = cleanString($data["start_date"] ?? null);
    $endDate   = cleanString($data["end_date"]   ?? null);

    if ($startDate === null || $endDate === null) {
        $errors[] = "Los campos 'start_date' y 'end_date' son obligatorios (YYYY-MM-DD).";
    } else {
        $promo["start_date"] = $startDate;
        $promo["end_date"]   = $endDate;

        if ($endDate < $startDate) {
            $errors[] = "La 'end_date' no puede ser anterior a 'start_date'.";
        }
    }

    // Note
    $promo["note"] = cleanString($data["note"] ?? null);

    // Active
    if (array_key_exists("active", $data)) {
        $promo["active"] = (int)((bool)$data["active"]);
    } else {
        if (!$isUpdate) {
            $promo["active"] = 1;
        }
    }

    return [
        "errors" => $errors,
        "promo"  => $errors ? null : $promo
    ];
}

/* =========================================================
 * VALIDACIONES: PROMO COMBO (combo_promotions)
 * =======================================================*/

/**
 * Valida datos de ComboPromotion.
 *
 * Estructura esperada:
 * - name (obligatorio)
 * - product1_id (obligatorio)
 * - product2_id (obligatorio, distinto a product1_id)
 * - combo_price (obligatorio > 0)
 * - start_date, end_date (obligatorio, YYYY-MM-DD)
 * - note (opcional)
 * - active (opcional)
 */
function validateComboPromotionData(array $data, bool $isUpdate = false): array
{
    $errors = [];
    $promo  = [];

    if ($isUpdate) {
        $id = (int)($data["id"] ?? 0);
        if ($id <= 0) {
            $errors[] = "ID de combo inválido.";
        } else {
            $promo["id"] = $id;
        }
    }

    $name = cleanString($data["name"] ?? null);
    if ($name === null) {
        $errors[] = "El campo 'name' es obligatorio.";
    } else {
        $promo["name"] = $name;
    }

    $product1 = (int)($data["product1_id"] ?? 0);
    $product2 = (int)($data["product2_id"] ?? 0);

    if ($product1 <= 0 || $product2 <= 0) {
        $errors[] = "Los campos 'product1_id' y 'product2_id' son obligatorios y deben ser > 0.";
    } elseif ($product1 === $product2) {
        $errors[] = "Los productos del combo deben ser distintos.";
    } else {
        // Ordenar para cumplir la convención product1_id < product2_id
        if ($product1 < $product2) {
            $promo["product1_id"] = $product1;
            $promo["product2_id"] = $product2;
        } else {
            $promo["product1_id"] = $product2;
            $promo["product2_id"] = $product1;
        }
    }

    // combo_price
    if (!isset($data["combo_price"]) || $data["combo_price"] === "") {
        $errors[] = "El campo 'combo_price' es obligatorio.";
    } else {
        $comboPrice = (float)$data["combo_price"];
        if ($comboPrice <= 0) {
            $errors[] = "El 'combo_price' debe ser mayor a 0.";
        } else {
            $promo["combo_price"] = $comboPrice;
        }
    }

    // Fechas
    $startDate = cleanString($data["start_date"] ?? null);
    $endDate   = cleanString($data["end_date"]   ?? null);

    if ($startDate === null || $endDate === null) {
        $errors[] = "Los campos 'start_date' y 'end_date' son obligatorios (YYYY-MM-DD).";
    } else {
        $promo["start_date"] = $startDate;
        $promo["end_date"]   = $endDate;

        if ($endDate < $startDate) {
            $errors[] = "La 'end_date' no puede ser anterior a 'start_date'.";
        }
    }

    // Note
    $promo["note"] = cleanString($data["note"] ?? null);

    // Active
    if (array_key_exists("active", $data)) {
        $promo["active"] = (int)((bool)$data["active"]);
    } else {
        if (!$isUpdate) {
            $promo["active"] = 1;
        }
    }

    return [
        "errors" => $errors,
        "promo"  => $errors ? null : $promo
    ];
}

/* =========================================================
 * LISTADOS
 * =======================================================*/

/**
 * Listado de promociones individuales de producto.
 *
 * GET promotion.php?action=list_product
 *      &product_id=...
 *      &active=1
 *      &page=1&per_page=10
 */
function handleListProductPromotions(PDO $pdo): void
{
    $page    = max(1, (int)($_GET["page"]     ?? 1));
    $perPage = (int)($_GET["per_page"] ?? 10);
    if ($perPage < 1)  $perPage = 10;
    if ($perPage > 50) $perPage = 50;

    $offset    = ($page - 1) * $perPage;
    $productId = (int)($_GET["product_id"] ?? 0);
    $active    = $_GET["active"] ?? null;

    $where  = [];
    $params = [];

    if ($productId > 0) {
        $where[]             = "pp.product_id = :product_id";
        $params[":product_id"] = $productId;
    }

    if ($active !== null && $active !== "") {
        $where[]          = "pp.active = :active";
        $params[":active"] = (int)((bool)$active);
    }

    $whereSql = $where ? "WHERE " . implode(" AND ", $where) : "";

    $sqlCount = "SELECT COUNT(*) FROM product_promotions pp $whereSql";
    $stmt     = $pdo->prepare($sqlCount);
    $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();

    $sql = "
        SELECT
            pp.id,
            pp.product_id,
            p.name AS product_name,
            pp.percent,
            pp.pack_size,
            pp.pack_price,
            pp.start_date,
            pp.end_date,
            pp.note,
            pp.active
        FROM product_promotions pp
        JOIN products p ON p.id = pp.product_id
        $whereSql
        ORDER BY pp.start_date DESC, pp.id DESC
        LIMIT :limit OFFSET :offset
    ";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(":limit",  $perPage, PDO::PARAM_INT);
    $stmt->bindValue(":offset", $offset,  PDO::PARAM_INT);
    $stmt->execute();

    $rows       = $stmt->fetchAll();
    $totalPages = $perPage > 0 ? (int)ceil($total / $perPage) : 1;

    echo json_encode([
        "data"       => $rows,
        "pagination" => [
            "page"        => $page,
            "per_page"    => $perPage,
            "total"       => $total,
            "total_pages" => $totalPages
        ],
        "filters"    => [
            "product_id" => $productId,
            "active"     => $active
        ]
    ]);
}

/**
 * Listado de promociones combo.
 *
 * GET promotion.php?action=list_combo
 *      &product_id=...   (opcional, combos que contengan ese producto)
 *      &active=1
 *      &page=1&per_page=10
 */
function handleListComboPromotions(PDO $pdo): void
{
    $page    = max(1, (int)($_GET["page"]     ?? 1));
    $perPage = (int)($_GET["per_page"] ?? 10);
    if ($perPage < 1)  $perPage = 10;
    if ($perPage > 50) $perPage = 50;

    $offset    = ($page - 1) * $perPage;
    $productId = (int)($_GET["product_id"] ?? 0);
    $active    = $_GET["active"] ?? null;

    $where  = [];
    $params = [];

    if ($productId > 0) {
        $where[]             = "(cp.product1_id = :product_id OR cp.product2_id = :product_id)";
        $params[":product_id"] = $productId;
    }

    if ($active !== null && $active !== "") {
        $where[]          = "cp.active = :active";
        $params[":active"] = (int)((bool)$active);
    }

    $whereSql = $where ? "WHERE " . implode(" AND ", $where) : "";

    $sqlCount = "SELECT COUNT(*) FROM combo_promotions cp $whereSql";
    $stmt     = $pdo->prepare($sqlCount);
    $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();

    $sql = "
        SELECT
            cp.id,
            cp.name,
            cp.product1_id,
            p1.name AS product1_name,
            cp.product2_id,
            p2.name AS product2_name,
            cp.combo_price,
            cp.start_date,
            cp.end_date,
            cp.note,
            cp.active
        FROM combo_promotions cp
        JOIN products p1 ON p1.id = cp.product1_id
        JOIN products p2 ON p2.id = cp.product2_id
        $whereSql
        ORDER BY cp.start_date DESC, cp.id DESC
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(":limit",  $perPage, PDO::PARAM_INT);
    $stmt->bindValue(":offset", $offset,  PDO::PARAM_INT);
    $stmt->execute();

    $rows       = $stmt->fetchAll();
    $totalPages = $perPage > 0 ? (int)ceil($total / $perPage) : 1;

    echo json_encode([
        "data"       => $rows,
        "pagination" => [
            "page"        => $page,
            "per_page"    => $perPage,
            "total"       => $total,
            "total_pages" => $totalPages
        ],
        "filters"    => [
            "product_id" => $productId,
            "active"     => $active
        ]
    ]);
}

/* =========================================================
 * DETALLES
 * =======================================================*/

function handleDetailProductPromotion(PDO $pdo): void
{
    $id = (int)($_GET["id"] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(["error" => "ID inválido"]);
        return;
    }

    $sql = "
        SELECT
            pp.*,
            p.name AS product_name
        FROM product_promotions pp
        JOIN products p ON p.id = pp.product_id
        WHERE pp.id = :id
        LIMIT 1
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([":id" => $id]);
    $promo = $stmt->fetch();

    if (!$promo) {
        http_response_code(404);
        echo json_encode(["error" => "Promoción de producto no encontrada"]);
        return;
    }

    echo json_encode(["data" => $promo]);
}

function handleDetailComboPromotion(PDO $pdo): void
{
    $id = (int)($_GET["id"] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(["error" => "ID inválido"]);
        return;
    }

    $sql = "
        SELECT
            cp.*,
            p1.name AS product1_name,
            p2.name AS product2_name
        FROM combo_promotions cp
        JOIN products p1 ON p1.id = cp.product1_id
        JOIN products p2 ON p2.id = cp.product2_id
        WHERE cp.id = :id
        LIMIT 1
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([":id" => $id]);
    $promo = $stmt->fetch();

    if (!$promo) {
        http_response_code(404);
        echo json_encode(["error" => "Promoción combo no encontrada"]);
        return;
    }

    echo json_encode(["data" => $promo]);
}

/* =========================================================
 * CREATE / UPDATE / DELETE
 * =======================================================*/

function handleCreateProductPromotion(PDO $pdo): void
{
    $data = getRequestData();
    $validation = validateProductPromotionData($data, false);

    if (!empty($validation["errors"])) {
        http_response_code(400);
        echo json_encode([
            "error"   => "Datos inválidos",
            "details" => $validation["errors"]
        ]);
        return;
    }

    $p = $validation["promo"];

    try {
        $sql = "
            INSERT INTO product_promotions (
                product_id,
                percent,
                pack_size,
                pack_price,
                start_date,
                end_date,
                note,
                active
            ) VALUES (
                :product_id,
                :percent,
                :pack_size,
                :pack_price,
                :start_date,
                :end_date,
                :note,
                :active
            )
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ":product_id" => $p["product_id"],
            ":percent"    => $p["percent"],
            ":pack_size"  => $p["pack_size"],
            ":pack_price" => $p["pack_price"],
            ":start_date" => $p["start_date"],
            ":end_date"   => $p["end_date"],
            ":note"       => $p["note"],
            ":active"     => $p["active"]
        ]);

        $newId = (int)$pdo->lastInsertId();

        http_response_code(201);
        echo json_encode([
            "success" => true,
            "id"      => $newId
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            "error"   => "Error al crear la promoción de producto",
            "details" => $e->getMessage()
        ]);
    }
}

function handleUpdateProductPromotion(PDO $pdo): void
{
    $data = getRequestData();
    $validation = validateProductPromotionData($data, true);

    if (!empty($validation["errors"])) {
        http_response_code(400);
        echo json_encode([
            "error"   => "Datos inválidos",
            "details" => $validation["errors"]
        ]);
        return;
    }

    $p  = $validation["promo"];
    $id = $p["id"];

    // Verificar existencia
    $stmtCheck = $pdo->prepare("SELECT id FROM product_promotions WHERE id = :id");
    $stmtCheck->execute([":id" => $id]);
    if (!$stmtCheck->fetch()) {
        http_response_code(404);
        echo json_encode(["error" => "Promoción de producto no encontrada"]);
        return;
    }

    try {
        $sql = "
            UPDATE product_promotions
            SET
                product_id = :product_id,
                percent    = :percent,
                pack_size  = :pack_size,
                pack_price = :pack_price,
                start_date = :start_date,
                end_date   = :end_date,
                note       = :note,
                active     = :active
            WHERE id = :id
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ":product_id" => $p["product_id"],
            ":percent"    => $p["percent"],
            ":pack_size"  => $p["pack_size"],
            ":pack_price" => $p["pack_price"],
            ":start_date" => $p["start_date"],
            ":end_date"   => $p["end_date"],
            ":note"       => $p["note"],
            ":active"     => $p["active"],
            ":id"         => $id
        ]);

        echo json_encode(["success" => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            "error"   => "Error al actualizar la promoción de producto",
            "details" => $e->getMessage()
        ]);
    }
}

function handleDeleteProductPromotion(PDO $pdo): void
{
    $data = getRequestData();
    $id   = (int)($data["id"] ?? 0);

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(["error" => "ID inválido"]);
        return;
    }

    $stmtCheck = $pdo->prepare("SELECT id FROM product_promotions WHERE id = :id");
    $stmtCheck->execute([":id" => $id]);
    if (!$stmtCheck->fetch()) {
        http_response_code(404);
        echo json_encode(["error" => "Promoción de producto no encontrada"]);
        return;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM product_promotions WHERE id = :id");
        $stmt->execute([":id" => $id]);

        echo json_encode(["success" => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            "error"   => "Error al eliminar la promoción de producto",
            "details" => $e->getMessage()
        ]);
    }
}

function handleCreateComboPromotion(PDO $pdo): void
{
    $data = getRequestData();
    $validation = validateComboPromotionData($data, false);

    if (!empty($validation["errors"])) {
        http_response_code(400);
        echo json_encode([
            "error"   => "Datos inválidos",
            "details" => $validation["errors"]
        ]);
        return;
    }

    $p = $validation["promo"];

    try {
        $sql = "
            INSERT INTO combo_promotions (
                name,
                product1_id,
                product2_id,
                combo_price,
                start_date,
                end_date,
                note,
                active
            ) VALUES (
                :name,
                :product1_id,
                :product2_id,
                :combo_price,
                :start_date,
                :end_date,
                :note,
                :active
            )
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ":name"        => $p["name"],
            ":product1_id" => $p["product1_id"],
            ":product2_id" => $p["product2_id"],
            ":combo_price" => $p["combo_price"],
            ":start_date"  => $p["start_date"],
            ":end_date"    => $p["end_date"],
            ":note"        => $p["note"],
            ":active"      => $p["active"]
        ]);

        $newId = (int)$pdo->lastInsertId();

        http_response_code(201);
        echo json_encode([
            "success" => true,
            "id"      => $newId
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            "error"   => "Error al crear la promoción combo",
            "details" => $e->getMessage()
        ]);
    }
}

function handleUpdateComboPromotion(PDO $pdo): void
{
    $data = getRequestData();
    $validation = validateComboPromotionData($data, true);

    if (!empty($validation["errors"])) {
        http_response_code(400);
        echo json_encode([
            "error"   => "Datos inválidos",
            "details" => $validation["errors"]
        ]);
        return;
    }

    $p  = $validation["promo"];
    $id = $p["id"];

    // Verificar existencia
    $stmtCheck = $pdo->prepare("SELECT id FROM combo_promotions WHERE id = :id");
    $stmtCheck->execute([":id" => $id]);
    if (!$stmtCheck->fetch()) {
        http_response_code(404);
        echo json_encode(["error" => "Promoción combo no encontrada"]);
        return;
    }

    try {
        $sql = "
            UPDATE combo_promotions
            SET
                name        = :name,
                product1_id = :product1_id,
                product2_id = :product2_id,
                combo_price = :combo_price,
                start_date  = :start_date,
                end_date    = :end_date,
                note        = :note,
                active      = :active
            WHERE id = :id
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ":name"        => $p["name"],
            ":product1_id" => $p["product1_id"],
            ":product2_id" => $p["product2_id"],
            ":combo_price" => $p["combo_price"],
            ":start_date"  => $p["start_date"],
            ":end_date"    => $p["end_date"],
            ":note"        => $p["note"],
            ":active"      => $p["active"],
            ":id"          => $id
        ]);

        echo json_encode(["success" => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            "error"   => "Error al actualizar la promoción combo",
            "details" => $e->getMessage()
        ]);
    }
}

function handleDeleteComboPromotion(PDO $pdo): void
{
    $data = getRequestData();
    $id   = (int)($data["id"] ?? 0);

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(["error" => "ID inválido"]);
        return;
    }

    $stmtCheck = $pdo->prepare("SELECT id FROM combo_promotions WHERE id = :id");
    $stmtCheck->execute([":id" => $id]);
    if (!$stmtCheck->fetch()) {
        http_response_code(404);
        echo json_encode(["error" => "Promoción combo no encontrada"]);
        return;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM combo_promotions WHERE id = :id");
        $stmt->execute([":id" => $id]);

        echo json_encode(["success" => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            "error"   => "Error al eliminar la promoción combo",
            "details" => $e->getMessage()
        ]);
    }
}

/* =========================================================
 * ROUTER POR ACTION
 * =======================================================*/

$action = $_GET["action"] ?? $_POST["action"] ?? null;

if ($action === null) {
    http_response_code(400);
    echo json_encode([
        "error"   => "Acción no especificada",
        "details" => "Acciones válidas: list_product, detail_product, create_product, update_product, delete_product, list_combo, detail_combo, create_combo, update_combo, delete_combo."
    ]);
    exit;
}

switch ($action) {
    // PRODUCT PROMOTIONS
    case "list_product":
        if ($method !== "GET") {
            http_response_code(405);
            echo json_encode(["error" => "Usa GET para 'list_product'."]);
            exit;
        }
        handleListProductPromotions($pdo);
        break;

    case "detail_product":
        if ($method !== "GET") {
            http_response_code(405);
            echo json_encode(["error" => "Usa GET para 'detail_product'."]);
            exit;
        }
        handleDetailProductPromotion($pdo);
        break;

    case "create_product":
        if ($method !== "POST") {
            http_response_code(405);
            echo json_encode(["error" => "Usa POST para 'create_product'."]);
            exit;
        }
        handleCreateProductPromotion($pdo);
        break;

    case "update_product":
        if ($method !== "POST") {
            http_response_code(405);
            echo json_encode(["error" => "Usa POST para 'update_product'."]);
            exit;
        }
        handleUpdateProductPromotion($pdo);
        break;

    case "delete_product":
        if ($method !== "POST") {
            http_response_code(405);
            echo json_encode(["error" => "Usa POST para 'delete_product'."]);
            exit;
        }
        handleDeleteProductPromotion($pdo);
        break;

    // COMBO PROMOTIONS
    case "list_combo":
        if ($method !== "GET") {
            http_response_code(405);
            echo json_encode(["error" => "Usa GET para 'list_combo'."]);
            exit;
        }
        handleListComboPromotions($pdo);
        break;

    case "detail_combo":
        if ($method !== "GET") {
            http_response_code(405);
            echo json_encode(["error" => "Usa GET para 'detail_combo'."]);
            exit;
        }
        handleDetailComboPromotion($pdo);
        break;

    case "create_combo":
        if ($method !== "POST") {
            http_response_code(405);
            echo json_encode(["error" => "Usa POST para 'create_combo'."]);
            exit;
        }
        handleCreateComboPromotion($pdo);
        break;

    case "update_combo":
        if ($method !== "POST") {
            http_response_code(405);
            echo json_encode(["error" => "Usa POST para 'update_combo'."]);
            exit;
        }
        handleUpdateComboPromotion($pdo);
        break;

    case "delete_combo":
        if ($method !== "POST") {
            http_response_code(405);
            echo json_encode(["error" => "Usa POST para 'delete_combo'."]);
            exit;
        }
        handleDeleteComboPromotion($pdo);
        break;

    default:
        http_response_code(400);
        echo json_encode([
            "error"   => "Acción inválida",
            "details" => "Acciones válidas: list_product, detail_product, create_product, update_product, delete_product, list_combo, detail_combo, create_combo, update_combo, delete_combo."
        ]);
        break;
}
