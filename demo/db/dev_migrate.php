<?php
// demo/db/dev_migrate.php
// ⚠️ SOLO PARA DESARROLLO / PRUEBAS.
// Crea tablas si no existen y se asegura de que exista el usuario admin/admin123.

/**
 * ===========================================================
 *  DEV MIGRATE – SCRIPT DE INICIALIZACIÓN DE BASE DE DATOS
 * ===========================================================
 *
 * ¿Qué hace este archivo?
 * -----------------------
 * - Crea automáticamente las tablas necesarias para la app
 *   usando un prefijo configurable (por defecto: SIN prefijo).
 * - Se asegura de que exista un usuario administrador por
 *   defecto: username: admin / password: admin123
 * - NO borra tablas ni datos existentes, solo crea lo que
 *   falte (tablas nuevas).
 *
 * Cómo usarlo
 * -----------
 * 1) Configurá la conexión a la base de datos en:
 *      api/config.php   (función getPDO())
 *
 * 2) Revisá y, si querés, cambiá el prefijo de tablas acá:
 *      $TABLE_PREFIX = '';
 *    Con prefijo vacío se usan las tablas:
 *      users, products, product_images, product_promotions,
 *      combo_promotions, view_events
 *    Si ponés, por ejemplo:
 *      $TABLE_PREFIX = 'wp_';
 *    vas a generar:
 *      wp_users, wp_products, wp_product_images, etc.
 *
 * 3) Subí este archivo al proyecto (por ejemplo en:
 *      demo/db/dev_migrate.php
 *
 * 4) Ejecutalo en un navegador o por CLI apuntando a este archivo:
 *      - Navegador: http://localhost/demo/db/dev_migrate.php
 *      - CLI:       php demo/db/dev_migrate.php
 *
 * 5) El script mostrará en pantalla qué hizo:
 *      - "Tabla 'xxx' creada"             → se creó una tabla nueva.
 *      - "Tabla 'xxx' ya existe"          → no se tocó esa tabla.
 *      - "Usuario 'admin' ya existe..."   → no se cambió su clave.
 *      - "Usuario admin creado..."        → se creó admin/admin123.
 *
 * Notas importantes
 * -----------------
 * - Este archivo está pensado SOLO para desarrollo / pruebas.
 *   No deberías dejarlo accesible públicamente en producción.
 *
 * - Podés ejecutarlo varias veces: es idempotente.
 *   Solo crea tablas/usuario si no existen.
 *
 * ===========================================================
 */

require_once __DIR__ . '/../api/config.php';

header('Content-Type: text/html; charset=utf-8');

// Prefijo de tablas (por defecto sin prefijo para coincidir con winepick_db.sql)
$TABLE_PREFIX  = '';
$TABLE_USERS   = $TABLE_PREFIX . 'users';
$TABLE_PRODUCTS = $TABLE_PREFIX . 'products';
$TABLE_PIMAGES  = $TABLE_PREFIX . 'product_images';
$TABLE_PPROMOS  = $TABLE_PREFIX . 'product_promotions';
$TABLE_CPROMOS  = $TABLE_PREFIX . 'combo_promotions';
$TABLE_VEVENTS  = $TABLE_PREFIX . 'view_events';

try {
    $pdo = getPDO();
    echo "<h1>Dev migrate – WinePick QR</h1>";
    echo "<p>Prefijo de tablas: <strong>" . htmlspecialchars($TABLE_PREFIX, ENT_QUOTES, 'UTF-8') . "</strong></p>";
    echo "<pre>";

    // Helpers --------------------------------------------------------------

    /**
     * Crea la tabla si no existe (usa information_schema).
     */
    function ensureTable(PDO $pdo, string $table, string $createSql): void
    {
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
              AND table_name   = :t
        ");
        $stmt->execute([":t" => $table]);
        $exists = (bool) $stmt->fetchColumn();

        if ($exists) {
            echo "• Tabla '$table' ya existe\n";
            return;
        }

        $pdo->exec($createSql);
        echo "✔ Tabla '$table' creada\n";
    }

    /**
     * Se asegura de que exista un usuario admin con password admin123
     * en la tabla indicada. Si ya existe el username 'admin', NO toca la clave.
     */
    function ensureAdminUser(PDO $pdo, string $usersTable, string $username = 'admin', string $plainPassword = 'admin123'): void
    {
        $sql = "SELECT id FROM `{$usersTable}` WHERE username = :u LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':u' => $username]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            echo "• Usuario '$username' ya existe en {$usersTable} (no se modificó la clave)\n";
            return;
        }

        // Mismo esquema de hash que tu dump: SHA-256 sin salt.
        $passwordHash = hash('sha256', $plainPassword);

        $insertSql = "INSERT INTO `{$usersTable}` (username, password_hash, active)
                      VALUES (:u, :h, 1)";
        $insert = $pdo->prepare($insertSql);
        $insert->execute([
            ':u' => $username,
            ':h' => $passwordHash,
        ]);

        echo "✔ Usuario admin creado en {$usersTable} (username: {$username} / password: {$plainPassword})\n";
    }

    // 1) Tabla users -------------------------------------------------------

    ensureTable($pdo, $TABLE_USERS, "
        CREATE TABLE IF NOT EXISTS `{$TABLE_USERS}` (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            username      VARCHAR(50)  NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            active        TINYINT(1)   NOT NULL DEFAULT 1,
            created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            last_login    DATETIME     DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY username (username)
        ) ENGINE=InnoDB
          DEFAULT CHARSET=utf8mb4
          COLLATE=utf8mb4_unicode_ci;
    ");

    // Asegurar usuario admin/admin123
    ensureAdminUser($pdo, $TABLE_USERS);

    // 2) Tabla products ----------------------------------------------------

    ensureTable($pdo, $TABLE_PRODUCTS, "
        CREATE TABLE IF NOT EXISTS `{$TABLE_PRODUCTS}` (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            pid               VARCHAR(50)  NOT NULL,
            name              VARCHAR(255) NOT NULL,
            producer          VARCHAR(255) DEFAULT NULL,
            varietal          VARCHAR(255) DEFAULT NULL,
            origin            VARCHAR(255) DEFAULT NULL,
            year              SMALLINT(6)  DEFAULT NULL,
            short_description TEXT         DEFAULT NULL,
            list_price        DECIMAL(10,2) NOT NULL,
            stock_status      ENUM('AVAILABLE','LOW','OUT') NOT NULL DEFAULT 'AVAILABLE',
            main_image        TEXT         DEFAULT NULL,
            active            TINYINT(1)   NOT NULL DEFAULT 1,
            created_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at        DATETIME     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            qr_path           VARCHAR(255) DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_products_pid (pid)
        ) ENGINE=InnoDB
          DEFAULT CHARSET=utf8mb4
          COLLATE=utf8mb4_unicode_ci;
    ");

    // 3) Tabla product_images ----------------------------------------------

    ensureTable($pdo, $TABLE_PIMAGES, "
        CREATE TABLE IF NOT EXISTS `{$TABLE_PIMAGES}` (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            product_id BIGINT(20) UNSIGNED NOT NULL,
            path       VARCHAR(255) NOT NULL,
            is_main    TINYINT(1)   NOT NULL DEFAULT 0,
            sort_order INT(11)      NOT NULL DEFAULT 0,
            created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY product_id (product_id),
            CONSTRAINT fk_product_images_products
              FOREIGN KEY (product_id)
              REFERENCES `{$TABLE_PRODUCTS}`(id)
              ON DELETE CASCADE
              ON UPDATE CASCADE
        ) ENGINE=InnoDB
          DEFAULT CHARSET=utf8mb4
          COLLATE=utf8mb4_unicode_ci;
    ");

    // 4) Tabla product_promotions -----------------------------------------

    ensureTable($pdo, $TABLE_PPROMOS, "
        CREATE TABLE IF NOT EXISTS `{$TABLE_PPROMOS}` (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            product_id BIGINT(20) UNSIGNED NOT NULL,
            percent    DECIMAL(5,2)   DEFAULT NULL,
            pack_size  INT(11)        DEFAULT NULL,
            pack_price DECIMAL(10,2)  DEFAULT NULL,
            start_date DATE           NOT NULL,
            end_date   DATE           NOT NULL,
            note       TEXT           DEFAULT NULL,
            active     TINYINT(1)     NOT NULL DEFAULT 1,
            PRIMARY KEY (id),
            KEY idx_pp_product        (product_id),
            KEY idx_pp_product_dates  (product_id, start_date, end_date),
            CONSTRAINT fk_pp_product
              FOREIGN KEY (product_id)
              REFERENCES `{$TABLE_PRODUCTS}`(id)
              ON DELETE CASCADE
              ON UPDATE CASCADE
        ) ENGINE=InnoDB
          DEFAULT CHARSET=utf8mb4
          COLLATE=utf8mb4_unicode_ci;
    ");

    // 5) Tabla combo_promotions -------------------------------------------

    ensureTable($pdo, $TABLE_CPROMOS, "
        CREATE TABLE IF NOT EXISTS `{$TABLE_CPROMOS}` (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name        VARCHAR(255) NOT NULL,
            product1_id BIGINT(20) UNSIGNED NOT NULL,
            product2_id BIGINT(20) UNSIGNED NOT NULL,
            combo_price DECIMAL(10,2) NOT NULL,
            start_date  DATE          NOT NULL,
            end_date    DATE          NOT NULL,
            note        TEXT          DEFAULT NULL,
            active      TINYINT(1)    NOT NULL DEFAULT 1,
            PRIMARY KEY (id),
            KEY idx_cp_products (product1_id, product2_id),
            KEY idx_cp_dates    (start_date, end_date),
            CONSTRAINT fk_cp_product1
              FOREIGN KEY (product1_id)
              REFERENCES `{$TABLE_PRODUCTS}`(id)
              ON DELETE CASCADE
              ON UPDATE CASCADE,
            CONSTRAINT fk_cp_product2
              FOREIGN KEY (product2_id)
              REFERENCES `{$TABLE_PRODUCTS}`(id)
              ON DELETE CASCADE
              ON UPDATE CASCADE
        ) ENGINE=InnoDB
          DEFAULT CHARSET=utf8mb4
          COLLATE=utf8mb4_unicode_ci;
    ");

    // 6) Tabla view_events -------------------------------------------------

    ensureTable($pdo, $TABLE_VEVENTS, "
        CREATE TABLE IF NOT EXISTS `{$TABLE_VEVENTS}` (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            product_id BIGINT(20) UNSIGNED NOT NULL,
            channel    ENUM('QR','SEARCH') NOT NULL,
            viewed_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            qr_code    TEXT     DEFAULT NULL,
            PRIMARY KEY (id),
            KEY idx_ve_product_date (product_id, viewed_at),
            CONSTRAINT fk_ve_product
              FOREIGN KEY (product_id)
              REFERENCES `{$TABLE_PRODUCTS}`(id)
              ON DELETE CASCADE
              ON UPDATE CASCADE
        ) ENGINE=InnoDB
          DEFAULT CHARSET=utf8mb4
          COLLATE=utf8mb4_unicode_ci;
    ");

    echo "\nFin de migración.\n";
    echo "</pre>";

} catch (Throwable $e) {
    http_response_code(500);
    echo "<h1>Error en dev_migrate</h1>";
    echo "<pre>" . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</pre>";
}
