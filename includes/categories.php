<?php
/**
 * includes/categories.php
 * All database access related to product categories.
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

/** Returns all active categories, ordered for display in the storefront. */
function categories_get_active(): array
{
    $pdo = get_db();
    $stmt = $pdo->query(
        'SELECT id, slug, name, sort_order
         FROM categories
         WHERE is_active = 1
         ORDER BY sort_order ASC, name ASC'
    );
    return $stmt->fetchAll();
}

/** Returns every category (active or not) — used in the admin panel. */
function categories_get_all(): array
{
    $pdo = get_db();
    $stmt = $pdo->query(
        'SELECT c.id, c.slug, c.name, c.sort_order, c.is_active, c.created_at,
                (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) AS product_count
         FROM categories c
         ORDER BY c.sort_order ASC, c.name ASC'
    );
    return $stmt->fetchAll();
}

function categories_get_by_id(int $id): ?array
{
    $pdo = get_db();
    $stmt = $pdo->prepare('SELECT * FROM categories WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function categories_get_by_slug(string $slug): ?array
{
    $pdo = get_db();
    $stmt = $pdo->prepare('SELECT * FROM categories WHERE slug = :slug AND is_active = 1 LIMIT 1');
    $stmt->execute(['slug' => $slug]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function categories_slug_exists(string $slug, ?int $excludeId = null): bool
{
    $pdo = get_db();
    $sql = 'SELECT COUNT(*) FROM categories WHERE slug = :slug';
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
 * Creates a category. Returns the new category ID.
 * @throws InvalidArgumentException on validation failure.
 */
function categories_create(string $name, string $slug, int $sortOrder, bool $isActive): int
{
    $name = trim($name);
    $slug = slugify($slug !== '' ? $slug : $name);

    if ($name === '' || $slug === '') {
        throw new InvalidArgumentException('Category name is required.');
    }
    if (categories_slug_exists($slug)) {
        throw new InvalidArgumentException('A category with this slug already exists.');
    }

    $pdo = get_db();
    $stmt = $pdo->prepare(
        'INSERT INTO categories (slug, name, sort_order, is_active)
         VALUES (:slug, :name, :sort_order, :is_active)'
    );
    $stmt->execute([
        'slug' => $slug,
        'name' => $name,
        'sort_order' => $sortOrder,
        'is_active' => $isActive ? 1 : 0,
    ]);

    return (int) $pdo->lastInsertId();
}

function categories_update(int $id, string $name, string $slug, int $sortOrder, bool $isActive): void
{
    $name = trim($name);
    $slug = slugify($slug !== '' ? $slug : $name);

    if ($name === '' || $slug === '') {
        throw new InvalidArgumentException('Category name is required.');
    }
    if (categories_slug_exists($slug, $id)) {
        throw new InvalidArgumentException('A category with this slug already exists.');
    }

    $pdo = get_db();
    $stmt = $pdo->prepare(
        'UPDATE categories
         SET slug = :slug, name = :name, sort_order = :sort_order, is_active = :is_active
         WHERE id = :id'
    );
    $stmt->execute([
        'slug' => $slug,
        'name' => $name,
        'sort_order' => $sortOrder,
        'is_active' => $isActive ? 1 : 0,
        'id' => $id,
    ]);
}

/**
 * Deletes a category. If it still has products attached, deletion is
 * refused unless $reassignToId is provided, in which case products are
 * safely reassigned to that category (or set to NULL / "uncategorized").
 */
function categories_delete(int $id, ?int $reassignToId = null): void
{
    $pdo = get_db();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE category_id = :id');
    $stmt->execute(['id' => $id]);
    $count = (int) $stmt->fetchColumn();

    if ($count > 0) {
        if ($reassignToId === null) {
            throw new RuntimeException(
                "This category still has {$count} product(s) attached. " .
                "Reassign or delete those products first, or choose a category to move them into."
            );
        }
        $reassign = $pdo->prepare('UPDATE products SET category_id = :new WHERE category_id = :old');
        $reassign->execute(['new' => $reassignToId, 'old' => $id]);
    }

    $del = $pdo->prepare('DELETE FROM categories WHERE id = :id');
    $del->execute(['id' => $id]);
}
