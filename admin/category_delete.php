<?php
/**
 * admin/category_delete.php
 * GET  -> shows a confirmation page (and, if products are attached,
 *         offers to reassign them to another category first).
 * POST -> performs the actual deletion (CSRF protected).
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/categories.php';

auth_require();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$category = $id ? categories_get_by_id($id) : null;

if (!$category) {
    flash_set('error', 'Category not found.');
    safe_redirect('admin/categories.php');
}

$pdo = get_db();
$stmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE category_id = :id');
$stmt->execute(['id' => $id]);
$productCount = (int) $stmt->fetchColumn();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $reassignTo = !empty($_POST['reassign_to']) ? (int) $_POST['reassign_to'] : null;
        try {
            categories_delete($id, $reassignTo);
            flash_set('success', 'Category "' . $category['name'] . '" deleted.');
            safe_redirect('admin/categories.php');
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
    }
}

$otherCategories = array_filter(categories_get_all(), static fn($c) => (int) $c['id'] !== $id);

$activeNav = 'categories';
$pageTitle = 'Delete Category';
require __DIR__ . '/../includes/admin_layout.php';
?>

<?php foreach ($errors as $err): ?>
    <div class="alert alert-error"><?= h($err) ?></div>
<?php endforeach; ?>

<div class="admin-card" style="max-width:520px;">
    <p>Are you sure you want to delete the category <strong><?= h($category['name']) ?></strong>?</p>

    <?php if ($productCount > 0): ?>
        <div class="alert alert-error">
            This category still has <strong><?= $productCount ?></strong> product(s) attached.
            Choose what to do with them below before deleting.
        </div>
        <form method="post" action="<?= h(url('admin/category_delete.php?id=' . $id)) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $id ?>">
            <div class="form-group">
                <label for="reassign_to">Move those products to:</label>
                <select id="reassign_to" name="reassign_to">
                    <option value="">— Set as Uncategorized —</option>
                    <?php foreach ($otherCategories as $c): ?>
                        <option value="<?= (int) $c['id'] ?>"><?= h($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="action-row">
                <button type="submit" class="btn btn-danger"><i class="fa-solid fa-trash"></i> Reassign &amp; delete category</button>
                <a href="<?= h(url('admin/categories.php')) ?>" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    <?php else: ?>
        <form method="post" action="<?= h(url('admin/category_delete.php?id=' . $id)) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $id ?>">
            <div class="action-row">
                <button type="submit" class="btn btn-danger"><i class="fa-solid fa-trash"></i> Delete category</button>
                <a href="<?= h(url('admin/categories.php')) ?>" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/admin_layout_end.php'; ?>
