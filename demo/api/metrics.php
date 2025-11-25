<?php
// api/metrics.php
// Endpoints de solo lectura para métricas basadas en view_events.

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

/**
 * Limpia string: trim y null si queda vacía.
 */
function cleanString(?string $value): ?string
{
    if ($value === null) return null;
    $value = trim($value);
    return $value === "" ? null : $value;
}

/**
 * Intenta interpretar un parámetro de fecha como YYYY-MM-DD.
 * Si no coincide con el formato básico, devuelve null.
 */
function parseDateParam(?string $value): ?string
{
    $value = cleanString($value);
    if ($value === null) {
        return null;
    }

    // Validación rápida del formato YYYY-MM-DD
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        return null;
    }

    return $value;
}

/**
 * Construye el fragmento de WHERE para filtros de fecha sobre view_events.
 * Usa DATE(viewed_at) para simplicidad.
 *
 * Devuelve [whereSql, params].
 */
function buildDateFilterWhere(string $tableAlias = "ve"): array
{
    $conditions = [];
    $params     = [];

    $startDate = parseDateParam($_GET["start_date"] ?? null);
    $endDate   = parseDateParam($_GET["end_date"]   ?? null);

    if ($startDate !== null && $endDate !== null) {
        $conditions[]            = "DATE($tableAlias.viewed_at) BETWEEN :start_date AND :end_date";
        $params[":start_date"]   = $startDate;
        $params[":end_date"]     = $endDate;
    } elseif ($startDate !== null) {
        $conditions[]          = "DATE($tableAlias.viewed_at) >= :start_date";
        $params[":start_date"] = $startDate;
    } elseif ($endDate !== null) {
        $conditions[]        = "DATE($tableAlias.viewed_at) <= :end_date";
        $params[":end_date"] = $endDate;
    }

    $whereSql = $conditions ? ("WHERE " . implode(" AND ", $conditions)) : "";

    return [$whereSql, $params];
}

/* =========================================================
 * HANDLERS
 * =======================================================*/

/**
 * Resumen general de vistas.
 *
 * GET metrics.php?action=views_summary
 *      &start_date=YYYY-MM-DD
 *      &end_date=YYYY-MM-DD
 *
 * Respuesta:
 * {
 *   "total_views": 123,
 *   "views_by_channel": [
 *      { "channel": "QR", "views": 80 },
 *      { "channel": "SEARCH", "views": 43 }
 *   ],
 *   "date_filter": { "start_date": "...", "end_date": "..." }
 * }
 */
function handleViewsSummary(PDO $pdo): void
{
    list($whereSql, $params) = buildDateFilterWhere("ve");

    // Total de vistas
    $sqlTotal = "SELECT COUNT(*) AS total_views FROM view_events ve $whereSql";
    $stmt     = $pdo->prepare($sqlTotal);
    $stmt->execute($params);
    $totalViews = (int)$stmt->fetchColumn();

    // Vistas por canal
    $sqlChannel = "
        SELECT channel, COUNT(*) AS views
        FROM view_events ve
        $whereSql
        GROUP BY channel
    ";
    $stmt = $pdo->prepare($sqlChannel);
    $stmt->execute($params);
    $byChannel = $stmt->fetchAll();

    echo json_encode([
        "total_views"      => $totalViews,
        "views_by_channel" => $byChannel,
        "date_filter"      => [
            "start_date" => parseDateParam($_GET["start_date"] ?? null),
            "end_date"   => parseDateParam($_GET["end_date"]   ?? null),
        ]
    ]);
}

/**
 * Vistas agrupadas por producto con paginación.
 *
 * GET metrics.php?action=views_by_product
 *      &start_date=YYYY-MM-DD
 *      &end_date=YYYY-MM-DD
 *      &page=1
 *      &per_page=10
 *
 * Respuesta:
 * {
 *   "data": [
 *     {
 *       "product_id": 1,
 *       "pid": "MALBEC-QR-001",
 *       "name": "Malbec...",
 *       "views_total": 50,
 *       "views_qr": 30,
 *       "views_search": 20
 *     },
 *     ...
 *   ],
 *   "pagination": { ... },
 *   "date_filter": { ... }
 * }
 */
function handleViewsByProduct(PDO $pdo): void
{
    $page    = max(1, (int)($_GET["page"]     ?? 1));
    $perPage = (int)($_GET["per_page"] ?? 10);
    if ($perPage < 1)  $perPage = 10;
    if ($perPage > 100) $perPage = 100;

    $offset = ($page - 1) * $perPage;

    list($whereSql, $params) = buildDateFilterWhere("ve");

    // Contar cantidad de grupos (productos con al menos una vista)
    $sqlCount = "
        SELECT COUNT(*) AS total
        FROM (
            SELECT ve.product_id
            FROM view_events ve
            $whereSql
            GROUP BY ve.product_id
        ) t
    ";
    $stmt = $pdo->prepare($sqlCount);
    $stmt->execute($params);
    $totalGroups = (int)$stmt->fetchColumn();

    // Datos agrupados con join a products
    $sql = "
        SELECT
            p.id   AS product_id,
            p.pid,
            p.name,
            COUNT(*) AS views_total,
            SUM(CASE WHEN ve.channel = 'QR'     THEN 1 ELSE 0 END) AS views_qr,
            SUM(CASE WHEN ve.channel = 'SEARCH' THEN 1 ELSE 0 END) AS views_search
        FROM view_events ve
        JOIN products p ON p.id = ve.product_id
        $whereSql
        GROUP BY p.id, p.pid, p.name
        ORDER BY views_total DESC
        LIMIT :limit OFFSET :offset
    ";
    $stmt = $pdo->prepare($sql);

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(":limit",  $perPage, PDO::PARAM_INT);
    $stmt->bindValue(":offset", $offset,  PDO::PARAM_INT);

    $stmt->execute();
    $rows = $stmt->fetchAll();

    $totalPages = $perPage > 0 ? (int)ceil($totalGroups / $perPage) : 1;

    echo json_encode([
        "data"       => $rows,
        "pagination" => [
            "page"        => $page,
            "per_page"    => $perPage,
            "total"       => $totalGroups,
            "total_pages" => $totalPages
        ],
        "date_filter" => [
            "start_date" => parseDateParam($_GET["start_date"] ?? null),
            "end_date"   => parseDateParam($_GET["end_date"]   ?? null),
        ]
    ]);
}

/**
 * Serie temporal de vistas (por día), opcionalmente filtrada por producto.
 *
 * GET metrics.php?action=views_timeline
 *      &product_id=1       (opcional, si falta devuelve todas)
 *      &start_date=YYYY-MM-DD
 *      &end_date=YYYY-MM-DD
 *
 * Respuesta:
 * {
 *   "data": [
 *     { "date": "2025-11-10", "views_total": 10, "views_qr": 7, "views_search": 3 },
 *     ...
 *   ],
 *   "filters": {
 *     "product_id": 1,
 *     "start_date": "...",
 *     "end_date": "..."
 *   }
 * }
 */
function handleViewsTimeline(PDO $pdo): void
{
    $productId = (int)($_GET["product_id"] ?? 0);

    list($whereDateSql, $dateParams) = buildDateFilterWhere("ve");

    $conditions = [];
    $params     = $dateParams;

    if ($productId > 0) {
        $conditions[]          = "ve.product_id = :product_id";
        $params[":product_id"] = $productId;
    }

    $whereSql = "";

    if (!empty($conditions) && $whereDateSql) {
        // ya hay condiciones de fecha
        $whereSql = $whereDateSql . " AND " . implode(" AND ", $conditions);
    } elseif (!empty($conditions)) {
        $whereSql = "WHERE " . implode(" AND ", $conditions);
    } else {
        $whereSql = $whereDateSql;
    }

    $sql = "
        SELECT
            DATE(ve.viewed_at) AS date,
            COUNT(*) AS views_total,
            SUM(CASE WHEN ve.channel = 'QR'     THEN 1 ELSE 0 END) AS views_qr,
            SUM(CASE WHEN ve.channel = 'SEARCH' THEN 1 ELSE 0 END) AS views_search
        FROM view_events ve
        $whereSql
        GROUP BY DATE(ve.viewed_at)
        ORDER BY date ASC
    ";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $rows = $stmt->fetchAll();

    echo json_encode([
        "data" => $rows,
        "filters" => [
            "product_id" => $productId ?: null,
            "start_date" => parseDateParam($_GET["start_date"] ?? null),
            "end_date"   => parseDateParam($_GET["end_date"]   ?? null),
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
        "details" => "Acciones válidas: views_summary, views_by_product, views_timeline."
    ]);
    exit;
}

switch ($action) {
    case "views_summary":
        handleViewsSummary($pdo);
        break;

    case "views_by_product":
        handleViewsByProduct($pdo);
        break;

    case "views_timeline":
        handleViewsTimeline($pdo);
        break;

    default:
        http_response_code(400);
        echo json_encode([
            "error"   => "Acción inválida",
            "details" => "Acciones válidas: views_summary, views_by_product, views_timeline."
        ]);
        break;
}
