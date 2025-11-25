<?php
// demo/db/dev_seed_fake_data.php
// ⚠️ SOLO PARA DESARROLLO / PRUEBAS.
// Este seed SOLO se asegura de que exista el usuario admin/admin123.

require_once __DIR__ . '/../api/config.php';

header('Content-Type: text/plain; charset=utf-8');

// Mismo prefijo que en dev_migrate.php (por defecto sin prefijo)
$TABLE_PREFIX = '';
$TABLE_USERS  = $TABLE_PREFIX . 'users';

date_default_timezone_set('America/Argentina/Buenos_Aires');

try {
    $pdo = getPDO();
    $pdo->exec("SET NAMES utf8mb4");

    echo "============================================\n";
    echo " Seed – Usuario administrador\n";
    echo "============================================\n\n";
    echo "Tabla usada:\n";
    echo "  - {$TABLE_USERS}\n\n";

    // ¿Ya existe admin?
    $username = 'admin';
    $plainPassword = 'admin123';

    $sql = "SELECT id FROM `{$TABLE_USERS}` WHERE username = :u LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':u' => $username]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        echo "• Usuario '{$username}' ya existe en {$TABLE_USERS}. No se modificó la clave.\n";
        exit(0);
    }

    $passwordHash = hash('sha256', $plainPassword);

    $insertSql = "INSERT INTO `{$TABLE_USERS}` (username, password_hash, active)
                  VALUES (:u, :h, 1)";
    $insert = $pdo->prepare($insertSql);
    $insert->execute([
        ':u' => $username,
        ':h' => $passwordHash,
    ]);

    echo "✔ Usuario admin creado en {$TABLE_USERS} (username: {$username} / password: {$plainPassword})\n";

} catch (Throwable $e) {
    http_response_code(500);
    echo "ERROR en dev_seed_fake_data: " . $e->getMessage() . "\n";
    exit(1);
}
