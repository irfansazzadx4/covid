<?php
// ============================================================
// config.php  –  Database connection (PDO)
// !! EDIT the four constants below to match your server !!
// ============================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database');   // ← change
define('DB_USER', 'root');            // ← change
define('DB_PASS', '');                // ← change

function get_pdo(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}
