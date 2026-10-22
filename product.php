<?php
/**
 * product.php?slug=product-name
 * Product detail page: 3D model viewer + AR launch + product info.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/products.php';

$config = app_config();
date_default_timezone_set($config['app']['timezone'] ?? 'UTC');

$slug = isset($_GET['slug']) ? trim((string) $_GET['slug']) : '';
$product = $slug !== '' ? products_get_by_slug($slug) : null;

if (!$product) {
    http_response_code(404);
}

$pageTitle = $product
    ? $product['name'] . ' — ' . $config['app']['name']
    : 'Product not found — ' . $config['app']['name'];

$glbUrl  = $product ? asset_url($product['glb_path'])  : null;
$usdzUrl = $product ? asset_url($product['usdz_path']) : null;
$thumbUrl = $product ? asset_url($product['thumb_path']) : null;
$currentUrl = $product ? url('product.php?slug=' . rawurlencode($product['slug'])) : url('index.php');
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($pageTitle) ?></title>
<?php
$metaDescription = 'Product not found.';
if ($product) {
    $desc = (string) $product['description'];
    $metaDescription = strlen($desc) > 150 ? substr($desc, 0, 147) . '...' : $desc;
}
?>
<meta name="description" content="<?= h($metaDescription) ?>">
<link rel="stylesheet" href="<?= h(url('css/styles.css')) ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<?php if ($product): ?>
<script type="module" src="https://unpkg.com/@google/model-viewer/dist/model-viewer.min.js"></script>
<?php endif; ?>
</head>
<body>

<?php include __DIR__ . '/includes/_header.php'; ?>

<main class="container">

<?php if (!$product): ?>

    <div class="empty-state" style="padding:90px 20px;">
        <i class="fa-solid fa-triangle-exclamation" style="font-size:2.4rem;margin-bottom:14px;color:#b3261e;"></i>
        <h2>Product not found.</h2>
        <p>The product you're looking for doesn't exist or is no longer available.</p>
        <a href="<?= h(url('index.php')) ?>" class="btn btn-primary">Back to catalog</a>
    </div>

<?php else: ?>

    <div class="breadcrumb">
        <a href="<?= h(url('index.php')) ?>">Catalog</a>
        <?php if (!empty($product['category_slug'])): ?>
            / <a href="<?= h(url('index.php?category=' . urlencode($product['category_slug']))) ?>"><?= h($product['category_name']) ?></a>
        <?php endif; ?>
        / <?= h($product['name']) ?>
    </div>

    <div class="product-detail">

        <div class="viewer-wrap">
            <?php if ($glbUrl): ?>
                <div data-viewer-loading class="viewer-message" style="display:flex;align-items:center;justify-content:center;position:absolute;inset:0;background:#f1efeb;z-index:1;">
                    <span><i class="fa-solid fa-spinner fa-spin"></i> Loading 3D model&hellip;</span>
                </div>
                <div data-viewer-error class="viewer-message" style="display:none;">
                    <i class="fa-solid fa-triangle-exclamation" style="font-size:1.8rem;color:#b3261e;"></i>
                    <p>3D model failed to load. Please check the model file.</p>
                </div>
                <model-viewer
                    src="<?= h($glbUrl) ?>"
                    <?= $usdzUrl ? 'ios-src="' . h($usdzUrl) . '"' : '' ?>
                    alt="3D model of <?= h($product['name']) ?>, viewable and placeable via augmented reality"
                    camera-controls
                    ar
                    ar-modes="webxr scene-viewer quick-look"
                    ar-scale="fixed"
                    shadow-intensity="1"
                    environment-image="neutral"
                    poster="<?= $thumbUrl ? h($thumbUrl) : '' ?>"
                    exposure="1"
                    interaction-prompt="none"
                    loading="eager">
                    <button slot="ar-button" class="btn btn-accent" style="position:absolute;bottom:16px;right:16px;">
                        <i class="fa-solid fa-cube"></i> View in your space
                    </button>
                </model-viewer>
                <div class="ar-hint">
                    <i class="fa-solid fa-circle-info"></i>
                    AR is supported on compatible mobile devices. Open this page on your phone and tap
                    “View in your space”.
                </div>
            <?php else: ?>
                <div class="viewer-fallback">
                    <i class="fa-solid fa-cube" style="font-size:2.2rem;margin-bottom:10px;"></i>
                    <p>No 3D model has been uploaded for this product yet.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="info-panel">
            <span class="product-category"><?= h($product['category_name'] ?? 'Uncategorized') ?></span>
            <h1><?= h($product['name']) ?></h1>
            <div class="price-tag"><?= format_price((float) $product['price'], $product['currency']) ?></div>

            <?php if (!empty($product['description'])): ?>
                <p><?= nl2br(h($product['description'])) ?></p>
            <?php endif; ?>

            <?php if ($product['width_cm'] || $product['height_cm'] || $product['depth_cm']): ?>
                <table class="dimensions-table">
                    <?php if ($product['width_cm']): ?>
                        <tr><td>Width</td><td><?= h(number_format((float) $product['width_cm'], 1)) ?> cm</td></tr>
                    <?php endif; ?>
                    <?php if ($product['height_cm']): ?>
                        <tr><td>Height</td><td><?= h(number_format((float) $product['height_cm'], 1)) ?> cm</td></tr>
                    <?php endif; ?>
                    <?php if ($product['depth_cm']): ?>
                        <tr><td>Depth</td><td><?= h(number_format((float) $product['depth_cm'], 1)) ?> cm</td></tr>
                    <?php endif; ?>
                </table>
            <?php endif; ?>

            <div class="hero-qr" data-qr-block style="text-align:left;padding:16px;">
                <div style="display:flex;gap:14px;align-items:center;">
                    <div data-qr-container data-qr-target="<?= h($currentUrl) ?>" style="flex:0 0 110px;">
                        <noscript>Enable JavaScript to see the QR code.</noscript>
                    </div>
                    <div>
                        <strong style="font-size:0.9rem;">Try it on your phone</strong>
                        <p style="margin:4px 0 0;font-size:0.82rem;">Scan this code, or</p>
                        <button type="button" class="btn btn-outline btn-sm" data-copy-link="<?= h($currentUrl) ?>">
                            <i class="fa-regular fa-copy"></i> Copy link
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php endif; ?>

</main>

<?php include __DIR__ . '/includes/_footer.php'; ?>

<script src="<?= h(url('js/qr.js')) ?>"></script>
<script src="<?= h(url('js/app.js')) ?>"></script>
</body>
</html>
