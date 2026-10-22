<?php
/**
 * includes/db.php
 * Central PDO database connection.
 *
 * Usage:
 *   require_once __DIR__ . '/db.php';
 *   $pdo = get_db();
 */

declare(strict_types=1);

/**
 * Returns a shared PDO instance (created once per request).
 */
function get_db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = require __DIR__ . '/../config/config.php';
    $db = $config['db'];

    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        $db['host'],
        $db['name'],
        $db['charset'] ?? 'utf8mb4'
    );

    try {
        $pdo = new PDO($dsn, $db['user'], $db['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        // Never leak raw DB errors (credentials, schema, etc.) to visitors.
        error_log('[AR Furniture] DB connection failed: ' . $e->getMessage());
        http_response_code(500);
        // A minimal, dependency-free fallback page (includes/* may not be
        // safe to use yet since the DB itself is unavailable).
        echo '<!doctype html><html><head><meta charset="utf-8">'
           . '<title>Service unavailable</title></head><body style="font-family:sans-serif;'
           . 'max-width:600px;margin:80px auto;text-align:center;color:#333;">'
           . '<h1>Database unavailable</h1>'
           . '<p>Please check the database configuration and try again shortly.</p>'
           . '</body></html>';
        exit;
    }

    return $pdo;
}
