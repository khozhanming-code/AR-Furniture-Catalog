<?php
/**
 * admin/product_delete.php
 * Deletes a single product and only the uploaded files that belong to it.
 * Requires POST + CSRF token (see admin/index.php's delete form).
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/products.php';

auth_require();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify()) {
    flash_set('error', 'Invalid request.');
    safe_redirect('admin/index.php');
}

$id = (int) ($_POST['id'] ?? 0);
$product = $id ? products_get_by_id($id) : null;

if (!$product) {
    flash_set('error', 'Product not found.');
    safe_redirect('admin/index.php');
}

try {
    products_delete_files($product);
    products_delete($id);
    flash_set('success', 'Product "' . $product['name'] . '" deleted.');
} catch (Throwable $e) {
    error_log('[AR Furniture admin] ' . $e->getMessage());
    flash_set('error', 'Could not delete the product.');
}

safe_redirect('admin/index.php');
