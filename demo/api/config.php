<?php
// api/config.php
// Configuración de conexión a la base de datos.

$DB_HOST = "localhost";
$DB_NAME = "u806346265_gr6";
$DB_USER = "u806346265_gr6u";
$DB_PASS = "6sae566a1RG-as7gb6"; 

/**
 * Devuelve una instancia de PDO configurada.
 *
 * @return PDO
 */
function getPDO() {
    global $DB_HOST, $DB_NAME, $DB_USER, $DB_PASS;

    $dsn = "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4";

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    return new PDO($dsn, $DB_USER, $DB_PASS, $options);
}
