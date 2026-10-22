<?php
/**
 * includes/functions.php
 * General-purpose helper functions used across the whole application.
 */

declare(strict_types=1);

/**
 * Loads (and caches) the config array for the current request.
 */
function app_config(): array
{
    static $config = null;
    if ($config === null) {
        $config = require __DIR__ . '/../config/config.php';
    }
    return $config;
}

/**
 * Escapes a value for safe HTML output.
 */
function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Detects the application's base URL at runtime so the project works
 * unmodified on localhost/XAMPP, a cPanel subfolder, or a full domain.
 * Falls back to config['app']['base_url'] if explicitly set.
 */
function base_url(): string
{
    $config = app_config();
    if (!empty($config['app']['base_url'])) {
        return rtrim($config['app']['base_url'], '/');
    }

    $isHttps = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') ||
        (($_SERVER['SERVER_PORT'] ?? '') == 443)
    );
    $scheme = $isHttps ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';

    // Directory the application lives in (works whether it's at the
    // domain root or in a subfolder like /ar-furniture/).
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));

    // Normalize: if we are inside /admin or /api, go up one level.
    foreach (['/admin', '/api'] as $sub) {
        if (substr($scriptDir, -strlen($sub)) === $sub) {
            $scriptDir = substr($scriptDir, 0, -strlen($sub));
        }
    }
    $scriptDir = rtrim($scriptDir, '/');

    return $scheme . '://' . $host . $scriptDir;
}

/**
 * Builds an absolute URL to a project-relative path (e.g. "product.php").
 */
function url(string $path = ''): string
{
    $path = ltrim($path, '/');
    return base_url() . ($path !== '' ? '/' . $path : '');
}

/**
 * Formats a price with its currency code, e.g. "RM 899.00".
 */
function format_price(float $price, string $currency = 'RM'): string
{
    return h($currency) . ' ' . number_format($price, 2);
}

/**
 * Returns a public URL for an uploaded model/thumbnail path stored in
 * the database, or null if none is set. Paths are stored relative to
 * the project root (e.g. "models/abc123.glb").
 */
function asset_url(?string $relativePath): ?string
{
    if (!$relativePath) {
        return null;
    }
    return url($relativePath);
}

/**
 * Generates a cryptographically random filename while preserving the
 * original file extension. Never trust/reuse the original filename.
 */
function random_filename(string $originalName): string
{
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $ext = preg_replace('/[^a-z0-9]/', '', $ext);
    return bin2hex(random_bytes(8)) . ($ext ? '.' . $ext : '');
}

/**
 * Converts an arbitrary string into a URL-safe slug.
 */
function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    return trim($text, '-');
}

/**
 * Simple CSRF token helpers for admin forms.
 */
function csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">';
}

function csrf_verify(): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $sent = $_POST['csrf_token'] ?? '';
    return is_string($sent) && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $sent);
}

/**
 * Redirects safely to a project-relative path and stops execution.
 */
function safe_redirect(string $relativePath): never
{
    // Only allow relative, in-project redirects (never open redirects).
    $relativePath = ltrim($relativePath, '/');
    header('Location: ' . url($relativePath));
    exit;
}

/**
 * Flash message helpers (stored in session, shown once).
 */
function flash_set(string $type, string $message): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_get(): ?array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}
