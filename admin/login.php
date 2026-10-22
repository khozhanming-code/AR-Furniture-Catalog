<?php
/**
 * admin/login.php
 * Administrator login form + handler.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (auth_check()) {
    safe_redirect('admin/index.php');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'Your session expired. Please try again.';
    } else {
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            $error = 'Please enter both username and password.';
        } elseif (auth_login($username, $password)) {
            safe_redirect('admin/index.php');
        } else {
            $error = 'Invalid username or password.';
        }
    }
}

$config = app_config();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Login — <?= h($config['app']['name']) ?></title>
<link rel="stylesheet" href="<?= h(url('css/styles.css')) ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
<div class="login-shell">
    <div class="login-card">
        <h1><i class="fa-solid fa-couch"></i> <?= h($config['app']['name']) ?></h1>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= h($error) ?></div>
        <?php endif; ?>
        <form method="post" action="<?= h(url('admin/login.php')) ?>" novalidate>
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autofocus autocomplete="username">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn btn-primary btn-block">
                <i class="fa-solid fa-right-to-bracket"></i> Log in
            </button>
        </form>
        <p style="text-align:center;margin-top:16px;">
            <a href="<?= h(url('index.php')) ?>" style="font-size:0.85rem;text-decoration:underline;color:#6b6560;">
                &larr; Back to storefront
            </a>
        </p>
    </div>
</div>
</body>
</html>
