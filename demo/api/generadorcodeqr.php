<?php
// api/generadorcodeqr.php
// Genera y guarda un código QR para un producto y devuelve la URL del PNG.

header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(204);
    exit;
}

require_once __DIR__ . "/config.php";
require_once __DIR__ . "/phpqrcode/qrlib.php";

try {
    // Leer datos (POST JSON o GET)
    $input = [];
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $raw = file_get_contents("php://input");
        if ($raw !== false && strlen($raw) > 0) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $input = $decoded;
            }
        }
    }

    if (empty($input)) {
        $input = $_GET;
    }

    $productId = isset($input["product_id"]) ? (int) $input["product_id"] : 0;
    $pidParam  = isset($input["pid"]) ? trim((string) $input["pid"]) : "";

    if ($productId <= 0 && $pidParam === "") {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "error"   => "Parámetros inválidos",
            "details" => "Debes enviar 'product_id' o 'pid'."
        ]);
        exit;
    }

    $pdo = getPDO();

    // Buscar el producto por ID o PID
    if ($productId > 0) {
        $stmt = $pdo->prepare("SELECT id, pid, name FROM products WHERE id = :id");
        $stmt->execute([":id" => $productId]);
    } else {
        $stmt = $pdo->prepare("SELECT id, pid, name FROM products WHERE pid = :pid");
        $stmt->execute([":pid" => $pidParam]);
    }

    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "error"   => "Producto no encontrado"
        ]);
        exit;
    }

    $productId = (int) $product["id"];
    $pid       = trim((string) $product["pid"]);

    if ($pid === "") {
        // Si por alguna razón no tiene PID, usamos el ID
        $pid = (string) $productId;
    }

    // Contenido a codificar en el QR: PID directo (compatible con tu lector)
    $qrContent = $pid;

    // Carpeta destino
    $qrDir = __DIR__ . "/../public/uploads/qr/";
    if (!is_dir($qrDir)) {
        if (!mkdir($qrDir, 0775, true) && !is_dir($qrDir)) {
            throw new RuntimeException("No se pudo crear el directorio de QR: " . $qrDir);
        }
    }

    // Nombre de archivo (ya seguías esta convención)
    $fileName = "product_" . $productId . ".png";
    $filePath = $qrDir . $fileName;

    // Generar el PNG (usa la librería phpqrcode)
    // Nivel de corrección: L, tamaño: 6, margen: 2 (ajustable)
    QRcode::png($qrContent, $filePath, QR_ECLEVEL_L, 6, 2);

    // Construir URL pública al archivo
    $scheme = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ? "https" : "http";
    $host   = $_SERVER["HTTP_HOST"] ?? "localhost";

    // script_name típicamente: /demo/api/generadorcodeqr.php
    $scriptDir = rtrim(dirname($_SERVER["SCRIPT_NAME"] ?? ""), "/"); // /demo/api
    // Reemplazar /api por /public para apuntar a la carpeta pública
    $publicBase = preg_replace("#/api$#i", "/public", $scriptDir);

    $qrUrl = $scheme . "://" . $host . $publicBase . "/uploads/qr/" . $fileName;

    echo json_encode([
        "success"     => true,
        "product_id"  => $productId,
        "pid"         => $pid,
        "qr_url"      => $qrUrl,
        "file_name"   => $fileName,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error"   => "Error interno al generar el código QR",
        "details" => $e->getMessage()
    ]);
}
