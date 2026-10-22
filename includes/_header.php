<?php
/** includes/_header.php — shared public storefront header. Expects functions.php already loaded. */
$config = app_config();
?>
<header class="site-header">
    <div class="container">
        <a href="<?= h(url('index.php')) ?>" class="brand">
            <i class="fa-solid fa-couch"></i>
            <?= h($config['app']['name']) ?>
        </a>
        <button class="nav-toggle" aria-label="Toggle navigation">
            <i class="fa-solid fa-bars"></i>
        </button>
        <nav class="nav-links">
            <a href="<?= h(url('index.php')) ?>">Catalog</a>
            <a href="<?= h(url('admin/login.php')) ?>" class="btn-admin">Admin Login</a>
        </nav>
    </div>
</header>
