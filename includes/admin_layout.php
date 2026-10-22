<?php
/**
 * includes/admin_layout.php
 *
 * Shared admin chrome. Usage in any protected admin page:
 *
 *   $activeNav = 'products'; // 'dashboard' | 'products' | 'categories'
 *   $pageTitle = 'Manage Products';
 *   require __DIR__ . '/../includes/admin_layout.php';
 *   // ... page content ...
 *   require __DIR__ . '/../includes/admin_layout_end.php';
 */

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

auth_require();

$config = app_config();
$activeNav = $activeNav ?? '';
$pageTitle = $pageTitle ?? 'Admin';
$flash = flash_get();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($pageTitle) ?> — <?= h($config['app']['name']) ?> Admin</title>
<link rel="stylesheet" href="<?= h(url('css/styles.css')) ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="admin-body">
<div class="admin-shell">
    <aside class="admin-sidebar">
        <div class="brand"><i class="fa-solid fa-couch"></i> <?= h($config['app']['name']) ?></div>
        <nav>
            <a href="<?= h(url('admin/index.php')) ?>" class="<?= $activeNav === 'dashboard' ? 'active' : '' ?>">
                <i class="fa-solid fa-gauge"></i> Dashboard
            </a>
            <a href="<?= h(url('admin/index.php')) ?>" class="<?= $activeNav === 'products' ? 'active' : '' ?>">
                <i class="fa-solid fa-couch"></i> Products
            </a>
            <a href="<?= h(url('admin/categories.php')) ?>" class="<?= $activeNav === 'categories' ? 'active' : '' ?>">
                <i class="fa-solid fa-tags"></i> Categories
            </a>
            <a href="<?= h(url('index.php')) ?>" target="_blank">
                <i class="fa-solid fa-arrow-up-right-from-square"></i> Storefront
            </a>
            <a href="<?= h(url('admin/logout.php')) ?>">
                <i class="fa-solid fa-right-from-bracket"></i> Logout
            </a>
        </nav>
    </aside>

    <main class="admin-main">
        <h1><?= h($pageTitle) ?></h1>

        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] === 'error' ? 'error' : 'success' ?>">
                <?= h($flash['message']) ?>
            </div>
        <?php endif; ?>
