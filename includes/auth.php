<?php
/**
 * includes/auth.php
 * Administrator authentication helpers.
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';

function auth_start_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

/**
 * Attempts to log in an administrator. Returns true on success.
 */
function auth_login(string $username, string $password): bool
{
    auth_start_session();

    $pdo = get_db();
    $stmt = $pdo->prepare('SELECT id, username, password_hash FROM admins WHERE username = :u LIMIT 1');
    $stmt->execute(['u' => $username]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($password, $admin['password_hash'])) {
        return false;
    }

    // Prevent session fixation.
    session_regenerate_id(true);

    $_SESSION['admin_id']       = (int) $admin['id'];
    $_SESSION['admin_username'] = $admin['username'];
    $_SESSION['login_time']     = time();

    return true;
}

function auth_logout(): void
{
    auth_start_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function auth_check(): bool
{
    auth_start_session();
    return !empty($_SESSION['admin_id']);
}

function auth_username(): ?string
{
    auth_start_session();
    return $_SESSION['admin_username'] ?? null;
}

/**
 * Call at the top of every protected admin page.
 */
function auth_require(): void
{
    if (!auth_check()) {
        require_once __DIR__ . '/functions.php';
        safe_redirect('admin/login.php');
    }
}
