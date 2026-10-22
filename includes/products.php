<?php
/**
 * includes/products.php
 * All database access related to products.
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

/**
 * Returns active products for the public storefront, optionally filtered
 * by category slug. Uses prepared statements only.
 */
function products_get_active(?string $categorySlug = null): array
{
    $pdo = get_db();

    $sql = 'SELECT p.id, p.slug, p.name, p.price, p.currency, p.thumb_path,
                   c.name AS category_name, c.slug AS category_slug
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            WHERE p.is_active = 1 AND (p.category_id IS NULL OR c.is_active = 1)';
    $params = [];

    if ($categorySlug !== null && $categorySlug !== 'all' && $categorySlug !== '') {
        $sql .= ' AND c.slug = :slug AND c.is_active = 1';
        $params['slug'] = $categorySlug;
    }

    $sql .= ' ORDER BY p.sort_order ASC, p.name ASC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/** Fetches one active product (with category info) by its slug. */
function products_get_by_slug(string $slug): ?array
{
    $pdo = get_db();
    $stmt = $pdo->prepare(
        'SELECT p.*, c.name AS category_name, c.slug AS category_slug
         FROM products p
         LEFT JOIN categories c ON c.id = p.category_id
         WHERE p.slug = :slug AND p.is_active = 1
           AND (p.category_id IS NULL OR c.is_active = 1)
         LIMIT 1'
    );
    $stmt->execute(['slug' => $slug]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/** Fetches any product by ID (active or not) — used by the admin panel. */
function products_get_by_id(int $id): ?array
{
    $pdo = get_db();
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/** Returns all products (active or not) for the admin listing. */
function products_get_all(): array
{
    $pdo = get_db();
    $stmt = $pdo->query(
        'SELECT p.*, c.name AS category_name
         FROM products p
         LEFT JOIN categories c ON c.id = p.category_id
         ORDER BY p.sort_order ASC, p.name ASC'
    );
    return $stmt->fetchAll();
}

function products_slug_exists(string $slug, ?int $excludeId = null): bool
{
    $pdo = get_db();
    $sql = 'SELECT COUNT(*) FROM products WHERE slug = :slug';
    $params = ['slug' => $slug];
    if ($excludeId !== null) {
        $sql .= ' AND id != :id';
        $params['id'] = $excludeId;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn() > 0;
}

/**
 * Simple counts used on the admin dashboard.
 */
function products_stats(): array
{
    $pdo = get_db();
    $total = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
    $active = (int) $pdo->query('SELECT COUNT(*) FROM products WHERE is_active = 1')->fetchColumn();
    $categories = (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
    return [
        'products_total'  => $total,
        'products_active' => $active,
        'categories_total' => $categories,
    ];
}

/**
 * Data shape expected by products_create() / products_update().
 * All numeric fields may be null.
 */
function products_create(array $data): int
{
    $data = products_validate($data);

    $pdo = get_db();
    $stmt = $pdo->prepare(
        'INSERT INTO products
            (category_id, slug, name, description, price, currency,
             glb_path, usdz_path, thumb_path,
             width_cm, height_cm, depth_cm, glb_x_m, glb_y_m, glb_z_m,
             is_active, sort_order)
         VALUES
            (:category_id, :slug, :name, :description, :price, :currency,
             :glb_path, :usdz_path, :thumb_path,
             :width_cm, :height_cm, :depth_cm, :glb_x_m, :glb_y_m, :glb_z_m,
             :is_active, :sort_order)'
    );
    $stmt->execute($data);
    return (int) $pdo->lastInsertId();
}

function products_update(int $id, array $data): void
{
    $data = products_validate($data, $id);
    $data['id'] = $id;

    $pdo = get_db();
    $stmt = $pdo->prepare(
        'UPDATE products SET
            category_id = :category_id,
            slug = :slug,
            name = :name,
            description = :description,
            price = :price,
            currency = :currency,
            glb_path = :glb_path,
            usdz_path = :usdz_path,
            thumb_path = :thumb_path,
            width_cm = :width_cm,
            height_cm = :height_cm,
            depth_cm = :depth_cm,
            glb_x_m = :glb_x_m,
            glb_y_m = :glb_y_m,
            glb_z_m = :glb_z_m,
            is_active = :is_active,
            sort_order = :sort_order
         WHERE id = :id'
    );
    $stmt->execute($data);
}

/**
 * Deletes a product row. Caller is responsible for removing the
 * associated uploaded files (see products_delete_files()) — kept
 * separate so tests / callers can control file-system side effects.
 */
function products_delete(int $id): void
{
    $pdo = get_db();
    $stmt = $pdo->prepare('DELETE FROM products WHERE id = :id');
    $stmt->execute(['id' => $id]);
}

/**
 * Safely deletes only the files that belong to this specific product
 * (never touches any other product's files).
 */
function products_delete_files(array $product): void
{
    $root = dirname(__DIR__);
    foreach (['glb_path', 'usdz_path', 'thumb_path'] as $field) {
        $relative = $product[$field] ?? null;
        if (!$relative) {
            continue;
        }
        // Guard against path traversal / unexpected paths.
        $normalized = str_replace('\\', '/', $relative);
        if (strpos($normalized, '..') !== false) {
            continue;
        }
        $full = $root . '/' . ltrim($normalized, '/');
        $allowedDirs = [
            realpath($root . '/models'),
            realpath($root . '/uploads/thumbs'),
        ];
        $realFull = realpath($full);
        if ($realFull && in_array(dirname($realFull), $allowedDirs, true) && is_file($realFull)) {
            @unlink($realFull);
        }
    }
}

/**
 * Validates + normalizes raw form data into the shape the SQL statements
 * expect. Throws InvalidArgumentException with a user-friendly message.
 */
function products_validate(array $data, ?int $excludeId = null): array
{
    $name = trim((string) ($data['name'] ?? ''));
    $slug = slugify((string) ($data['slug'] ?? '') !== '' ? (string) $data['slug'] : $name);

    if ($name === '') {
        throw new InvalidArgumentException('Product name is required.');
    }
    if ($slug === '') {
        throw new InvalidArgumentException('Could not generate a valid slug from the product name.');
    }
    if (products_slug_exists($slug, $excludeId)) {
        throw new InvalidArgumentException('A product with this slug already exists.');
    }

    $price = (float) ($data['price'] ?? 0);
    if ($price < 0) {
        throw new InvalidArgumentException('Price cannot be negative.');
    }

    $toNullableFloat = static function ($v) {
        if ($v === null || $v === '') {
            return null;
        }
        return (float) $v;
    };

    $categoryId = ($data['category_id'] ?? '') !== '' ? (int) $data['category_id'] : null;

    return [
        'category_id' => $categoryId,
        'slug'        => $slug,
        'name'        => $name,
        'description' => (string) ($data['description'] ?? ''),
        'price'       => $price,
        'currency'    => trim((string) ($data['currency'] ?? 'RM')) ?: 'RM',
        'glb_path'    => $data['glb_path'] ?? null,
        'usdz_path'   => $data['usdz_path'] ?? null,
        'thumb_path'  => $data['thumb_path'] ?? null,
        'width_cm'    => $toNullableFloat($data['width_cm'] ?? null),
        'height_cm'   => $toNullableFloat($data['height_cm'] ?? null),
        'depth_cm'    => $toNullableFloat($data['depth_cm'] ?? null),
        'glb_x_m'     => $toNullableFloat($data['glb_x_m'] ?? null),
        'glb_y_m'     => $toNullableFloat($data['glb_y_m'] ?? null),
        'glb_z_m'     => $toNullableFloat($data['glb_z_m'] ?? null),
        'is_active'   => !empty($data['is_active']) ? 1 : 0,
        'sort_order'  => (int) ($data['sort_order'] ?? 0),
    ];
}
