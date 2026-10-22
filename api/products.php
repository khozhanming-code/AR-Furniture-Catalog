<?php
/**
 * api/products.php
 * Public, read-only JSON API for active products.
 *
 * GET api/products.php               -> all active products
 * GET api/products.php?category=SLUG -> active products in one category
 * GET api/products.php?slug=SLUG     -> a single product's full detail
 *
 * Never exposes admin credentials, password hashes, or internal fields.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/products.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if (isset($_GET['slug']) && trim((string) $_GET['slug']) !== '') {
        $product = products_get_by_slug(trim((string) $_GET['slug']));

        if (!$product) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Product not found.']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'product' => [
                'slug'          => $product['slug'],
                'name'          => $product['name'],
                'description'   => $product['description'],
                'price'         => (float) $product['price'],
                'currency'      => $product['currency'],
                'category'      => $product['category_name'],
                'category_slug' => $product['category_slug'],
                'glb_url'       => asset_url($product['glb_path']),
                'usdz_url'      => asset_url($product['usdz_path']),
                'thumb_url'     => asset_url($product['thumb_path']),
                'dimensions_cm' => [
                    'width'  => $product['width_cm']  !== null ? (float) $product['width_cm']  : null,
                    'height' => $product['height_cm'] !== null ? (float) $product['height_cm'] : null,
                    'depth'  => $product['depth_cm']  !== null ? (float) $product['depth_cm']  : null,
                ],
                'product_url' => url('product.php?slug=' . urlencode($product['slug'])),
            ],
        ]);
        exit;
    }

    $categorySlug = isset($_GET['category']) ? trim((string) $_GET['category']) : null;
    $products = products_get_active($categorySlug);

    $out = array_map(static function (array $p): array {
        return [
            'slug'      => $p['slug'],
            'name'      => $p['name'],
            'price'     => (float) $p['price'],
            'currency'  => $p['currency'],
            'category'  => $p['category_name'],
            'thumb_url' => asset_url($p['thumb_path']),
            'product_url' => url('product.php?slug=' . urlencode($p['slug'])),
        ];
    }, $products);

    echo json_encode(['success' => true, 'products' => $out]);

} catch (Throwable $e) {
    error_log('[AR Furniture API] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error.']);
}
