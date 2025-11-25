<?php
// api/public_product.php
// Endpoints públicos (sin login) para:
// - Ver detalle de producto por PID (para QR / web pública).
// - Buscar productos activos.

header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
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
if ($method !== "GET") {
    http_response_code(405);
    echo json_encode([
        "error"   => "Método no permitido",
        "allowed" => ["GET"]
    ]);
    exit;
}

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

/* =========================================================
 * HELPERS
 * =======================================================*/

function cleanString(?string $value): ?string
{
    if ($value === null) return null;
    $value = trim($value);
    return $value === "" ? null : $value;
}

/**
 * Registra un evento de vista (view_events).
 *
 * @param PDO    $pdo
 * @param int    $productId
 * @param string $channel   'QR' o 'SEARCH'
 * @param string|null $qrCode
 */
function registerViewEvent(PDO $pdo, int $productId, string $channel = "QR", ?string $qrCode = null): void
{
    if (!in_array($channel, ["QR", "SEARCH"], true)) {
        $channel = "QR";
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO view_events (product_id, channel, qr_code)
            VALUES (:product_id, :channel, :qr_code)
        ");
        $stmt->execute([
            ":product_id" => $productId,
            ":channel"    => $channel,
            ":qr_code"    => $qrCode
        ]);
    } catch (PDOException $e) {
        // En público NO tiramos error si falla la métrica.
        // A lo sumo podrías loguearlo a un archivo.
    }
}

/* =========================================================
 * HANDLERS
 * =======================================================*/

/**
 * GET /public_product.php?action=detail&pid=...&channel=QR
 *
 * Devuelve:
 * {
 *   "product": { ... },
 *   "promotion": { ... } | null,
 *   "combos": [ ... ]
 * }
 */
function handlePublicDetail(PDO $pdo): void
{
    $pid = cleanString($_GET["pid"] ?? null);

    if ($pid === null) {
        http_response_code(400);
        echo json_encode([
            "error"   => "Parámetro inválido",
            "details" => "Debes enviar 'pid'."
        ]);
        return;
    }

    // 1) Buscar producto activo por pid
    $stmt = $pdo->prepare("
        SELECT
            id,
            pid,
            name,
            producer,
            varietal,
            origin,
            year,
            short_description,
            list_price,
            stock_status,
            main_image
        FROM products
        WHERE pid = :pid
          AND active = 1
        LIMIT 1
    ");
    $stmt->execute([":pid" => $pid]);
    $product = $stmt->fetch();

    if (!$product) {
        http_response_code(404);
        echo json_encode([
            "error" => "Producto no encontrado o inactivo"
        ]);
        return;
    }

    $productId = (int)$product["id"];
    $today     = date("Y-m-d");

    // 2) Promo individual vigente (si existe)
    $stmtPromo = $pdo->prepare("
        SELECT
            id,
            product_id,
            percent,
            pack_size,
            pack_price,
            start_date,
            end_date,
            note
        FROM product_promotions
        WHERE product_id = :product_id
          AND active = 1
          AND :today BETWEEN start_date AND end_date
        ORDER BY start_date DESC, id DESC
        LIMIT 1
    ");
    $stmtPromo->execute([
        ":product_id" => $productId,
        ":today"      => $today
    ]);
    $promotion = $stmtPromo->fetch() ?: null;

    // 3) Combos vigentes donde participa el producto
    $stmtCombo = $pdo->prepare("
        SELECT
            cp.id,
            cp.name,
            cp.product1_id,
            cp.product2_id,
            p1.name AS product1_name,
            p2.name AS product2_name,
            cp.combo_price,
            cp.start_date,
            cp.end_date,
            cp.note
        FROM combo_promotions cp
        JOIN products p1 ON p1.id = cp.product1_id
        JOIN products p2 ON p2.id = cp.product2_id
        WHERE cp.active = 1
          AND :today BETWEEN cp.start_date AND cp.end_date
          AND (cp.product1_id = :product_id OR cp.product2_id = :product_id)
        ORDER BY cp.start_date DESC, cp.id DESC
    ");
    $stmtCombo->execute([
        ":today"      => $today,
        ":product_id" => $productId
    ]);
    $combos = $stmtCombo->fetchAll();

    // 4) Registrar view_event
    $channel = strtoupper(cleanString($_GET["channel"] ?? "QR"));
    $qrCode  = cleanString($_GET["qr_code"] ?? $pid);
    registerViewEvent($pdo, $productId, $channel, $qrCode);

    echo json_encode([
        "product"   => $product,
        "promotion" => $promotion,
        "combos"    => $combos
    ]);
}

/**
 * GET /public_product.php?action=search&q=texto&page=1&per_page=10
 *
 * Devuelve productos activos que coinciden con el texto.
 */
function handlePublicSearch(PDO $pdo): void
{
    $page    = max(1, (int)($_GET["page"]     ?? 1));
    $perPage = (int)($_GET["per_page"] ?? 10);
    if ($perPage < 1)  $perPage = 10;
    if ($perPage > 50) $perPage = 50;

    $offset = ($page - 1) * $perPage;

    $q = cleanString($_GET["q"] ?? null);

    $where  = ["p.active = 1"];
    $params = [];

    if ($q !== null) {
        $where[]          = "(p.name LIKE :q OR p.varietal LIKE :q OR p.origin LIKE :q OR p.producer LIKE :q OR p.pid LIKE :q)";
        $params[":q"]     = "%" . $q . "%";
    }

    $whereSql = "WHERE " . implode(" AND ", $where);

    // Total
    $sqlCount = "SELECT COUNT(*) FROM products p $whereSql";
    $stmt     = $pdo->prepare($sqlCount);
    $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();

    // Datos
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
            p.main_image
        FROM products p
        $whereSql
        ORDER BY p.name ASC
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
        "filters" => [
            "q" => $q
        ]
    ]);
}

/* =========================================================
 * ROUTER
 * =======================================================*/

$action = $_GET["action"] ?? null;

if ($action === null) {
    http_response_code(400);
    echo json_encode([
        "error"   => "Acción no especificada",
        "details" => "Acciones válidas: detail, search."
    ]);
    exit;
}

switch ($action) {
    case "detail":
        handlePublicDetail($pdo);
        break;

    case "search":
        handlePublicSearch($pdo);
        break;

    default:
        http_response_code(400);
        echo json_encode([
            "error"   => "Acción inválida",
            "details" => "Acciones válidas: detail, search."
        ]);
        break;
}
