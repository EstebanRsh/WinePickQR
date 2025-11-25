<?php
require_once __DIR__ . "/config.php";

header("Content-Type: application/json; charset=utf-8");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["error" => "Método no permitido"]);
    exit;
}

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

$username = trim($data["username"] ?? "");
$password = trim($data["password"] ?? "");

if ($username === "" || $password === "") {
    http_response_code(400);
    echo json_encode(["error" => "Faltan usuario o contraseña"]);
    exit;
}

try {
    $pdo = getPDO();

    $stmt = $pdo->prepare(
        "SELECT id, username
         FROM users
         WHERE username = :u
           AND password_hash = SHA2(:p, 256)
           AND active = 1"
    );
    $stmt->execute([
        ":u" => $username,
        ":p" => $password,
    ]);

    $user = $stmt->fetch();

    if ($user) {
        http_response_code(200);
        echo json_encode([
            "success"  => true,
            "user_id"  => $user["id"],
            "username" => $user["username"],
        ]);
    } else {
        http_response_code(401);
        echo json_encode(["error" => "Credenciales inválidas"]);
    }

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error interno", "detail" => $e->getMessage()]);
}
