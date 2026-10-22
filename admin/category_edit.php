<?php
/**
 * admin/category_edit.php
 * Handles both "Add Category" (no ?id=) and "Edit Category" (?id=N).
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/categories.php';

auth_require();

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$existing = $id ? categories_get_by_id($id) : null;

if ($id && !$existing) {
    flash_set('error', 'Category not found.');
    safe_redirect('admin/categories.php');
}

$errors = [];
$formData = $existing ?? ['name' => '', 'slug' => '', 'sort_order' => 0, 'is_active' => 1];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'Your session expired. Please resubmit the form.';
    } else {
        $formData = array_merge($formData, $_POST);
        $formData['is_active'] = isset($_POST['is_active']) ? 1 : 0;

        try {
            if ($existing) {
                categories_update(
                    (int) $existing['id'],
                    (string) $formData['name'],
                    (string) $formData['slug'],
                    (int) $formData['sort_order'],
                    (bool) $formData['is_active']
                );
                flash_set('success', 'Category updated.');
            } else {
                categories_create(
                    (string) $formData['name'],
                    (string) $formData['slug'],
                    (int) $formData['sort_order'],
                    (bool) $formData['is_active']
                );
                flash_set('success', 'Category created.');
            }
            safe_redirect('admin/categories.php');
        } catch (InvalidArgumentException $e) {
            $errors[] = $e->getMessage();
        } catch (Throwable $e) {
            error_log('[AR Furniture admin] ' . $e->getMessage());
            $errors[] = 'An unexpected error occurred while saving the category.';
        }
    }
}

$activeNav = 'categories';
$pageTitle = $existing ? 'Edit Category' : 'Add Category';
require __DIR__ . '/../includes/admin_layout.php';
?>

<?php foreach ($errors as $err): ?>
    <div class="alert alert-error"><?= h($err) ?></div>
<?php endforeach; ?>

<div class="admin-card" style="max-width:520px;">
    <form method="post" action="<?= h(url('admin/category_edit.php' . ($existing ? '?id=' . (int) $existing['id'] : ''))) ?>">
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="name">Category name *</label>
            <input type="text" id="name" name="name" required value="<?= h((string) $formData['name']) ?>">
        </div>
        <div class="form-group">
            <label for="slug">Slug (optional)</label>
            <input type="text" id="slug" name="slug" value="<?= h((string) $formData['slug']) ?>" placeholder="auto-generated from name if left blank">
        </div>
        <div class="form-group">
            <label for="sort_order">Sort order</label>
            <input type="number" id="sort_order" name="sort_order" value="<?= h((string) $formData['sort_order']) ?>">
        </div>
        <div class="form-group">
            <div class="checkbox-row">
                <input type="checkbox" id="is_active" name="is_active" value="1" <?= !empty($formData['is_active']) ? 'checked' : '' ?>>
                <label for="is_active" style="margin:0;">Active (visible in the public catalog)</label>
            </div>
        </div>

        <div class="action-row">
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save category</button>
            <a href="<?= h(url('admin/categories.php')) ?>" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../includes/admin_layout_end.php'; ?>
