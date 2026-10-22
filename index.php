<?php
/**
 * index.php
 * Public storefront: hero section + category filter + product grid.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/categories.php';
require_once __DIR__ . '/includes/products.php';

$config = app_config();
date_default_timezone_set($config['app']['timezone'] ?? 'UTC');

$activeCategorySlug = isset($_GET['category']) ? (string) $_GET['category'] : 'all';
$categories = categories_get_active();
$products = products_get_active($activeCategorySlug === 'all' ? null : $activeCategorySlug);

$pageTitle = $config['app']['name'] . ' — See it before you buy';
$currentUrl = url('index.php');
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($pageTitle) ?></title>
<meta name="description" content="Browse our furniture catalog and preview every piece in your own home using augmented reality before you buy.">
<link rel="stylesheet" href="<?= h(url('css/styles.css')) ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

<?php include __DIR__ . '/includes/_header.php'; ?>

<section class="hero">
    <div class="container">
        <div class="hero-copy">
            <span class="hero-eyebrow">Augmented Reality Furniture</span>
            <h1>See it before you buy.</h1>
            <p class="lead">
                Preview every piece of furniture in your own space using your phone's
                camera. Rotate it, walk around it, and make sure it truly fits &mdash;
                before it ever leaves the warehouse.
            </p>
            <div class="hero-actions">
                <a href="#catalog" class="btn btn-primary"><i class="fa-solid fa-couch"></i> Browse the catalog</a>
                <a href="admin/login.php" class="btn btn-outline"><i class="fa-solid fa-lock"></i> Admin</a>
            </div>
        </div>
        <div class="hero-qr" data-qr-block>
            <div data-qr-container data-qr-target="<?= h($currentUrl) ?>">
                <noscript>Enable JavaScript to see the QR code.</noscript>
            </div>
            <small>Scan with your phone to open this page and try AR</small>
        </div>
    </div>
</section>

<main class="container section" id="catalog">
    <h2>Our Catalog</h2>

    <nav class="category-bar" aria-label="Filter by category">
        <a href="<?= h(url('index.php')) ?>"
           class="category-pill <?= $activeCategorySlug === 'all' ? 'active' : '' ?>">All</a>
        <?php foreach ($categories as $cat): ?>
            <a href="<?= h(url('index.php?category=' . urlencode($cat['slug']))) ?>"
               class="category-pill <?= $activeCategorySlug === $cat['slug'] ? 'active' : '' ?>">
                <?= h($cat['name']) ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <?php if (empty($products)): ?>
        <div class="empty-state">
            <i class="fa-solid fa-box-open" style="font-size:2.2rem;margin-bottom:10px;"></i>
            <p>No products found in this category yet.</p>
        </div>
    <?php else: ?>
        <div class="product-grid">
            <?php foreach ($products as $p): ?>
                <a class="product-card" href="<?= h(url('product.php?slug=' . urlencode($p['slug']))) ?>">
                    <div class="product-thumb">
                        <?php if (!empty($p['thumb_path'])): ?>
                            <img src="<?= h(asset_url($p['thumb_path'])) ?>" alt="<?= h($p['name']) ?>" loading="lazy">
                        <?php else: ?>
                            <i class="fa-solid fa-couch placeholder"></i>
                        <?php endif; ?>
                    </div>
                    <div class="product-body">
                        <span class="product-category"><?= h($p['category_name'] ?? 'Uncategorized') ?></span>
                        <div class="product-name"><?= h($p['name']) ?></div>
                        <div class="product-price"><?= format_price((float) $p['price'], $p['currency']) ?></div>
                        <span class="product-cta">View in your space <i class="fa-solid fa-arrow-right"></i></span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/includes/_footer.php'; ?>

<script src="<?= h(url('js/qr.js')) ?>"></script>
<script src="<?= h(url('js/app.js')) ?>"></script>
</body>
</html>
