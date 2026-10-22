<?php
/**
 * admin/product_edit.php
 * Handles both "Add Product" (no ?id=) and "Edit Product" (?id=N).
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/products.php';
require_once __DIR__ . '/../includes/categories.php';
require_once __DIR__ . '/../includes/uploads.php';

auth_require();

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$existing = $id ? products_get_by_id($id) : null;

if ($id && !$existing) {
    flash_set('error', 'Product not found.');
    safe_redirect('admin/index.php');
}

$errors = [];
$formData = $existing ?? [
    'category_id' => '', 'slug' => '', 'name' => '', 'description' => '',
    'price' => '', 'currency' => 'RM', 'width_cm' => '', 'height_cm' => '',
    'depth_cm' => '', 'glb_x_m' => '', 'glb_y_m' => '', 'glb_z_m' => '',
    'is_active' => 1, 'sort_order' => 0,
    'glb_path' => null, 'usdz_path' => null, 'thumb_path' => null,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'Your session expired. Please resubmit the form.';
    } else {
        $formData = array_merge($formData, $_POST);

        try {
            // Start with existing file paths; replace only if a new file was uploaded.
            $glbPath   = $existing['glb_path']   ?? null;
            $usdzPath  = $existing['usdz_path']  ?? null;
            $thumbPath = $existing['thumb_path'] ?? null;

            if (!empty($_FILES['glb_file']['name'])) {
                $glbPath = upload_model_file($_FILES['glb_file']);
            }
            if (!empty($_FILES['usdz_file']['name'])) {
                $usdzPath = upload_model_file($_FILES['usdz_file']);
            }
            if (!empty($_FILES['thumb_file']['name'])) {
                $thumbPath = upload_thumbnail_file($_FILES['thumb_file']);
            }

            $payload = $_POST;
            $payload['glb_path']   = $glbPath;
            $payload['usdz_path']  = $usdzPath;
            $payload['thumb_path'] = $thumbPath;

            if ($existing) {
                products_update((int) $existing['id'], $payload);
                flash_set('success', 'Product updated successfully.');
            } else {
                products_create($payload);
                flash_set('success', 'Product created successfully.');
            }
            safe_redirect('admin/index.php');

        } catch (InvalidArgumentException | RuntimeException $e) {
            $errors[] = $e->getMessage();
        } catch (Throwable $e) {
            error_log('[AR Furniture admin] ' . $e->getMessage());
            $errors[] = 'An unexpected error occurred while saving the product.';
        }
    }
}

$categories = categories_get_all();

$activeNav = 'products';
$pageTitle = $existing ? 'Edit Product' : 'Add Product';
require __DIR__ . '/../includes/admin_layout.php';
?>

<?php foreach ($errors as $err): ?>
    <div class="alert alert-error"><?= h($err) ?></div>
<?php endforeach; ?>

<div class="admin-card">
    <form method="post" enctype="multipart/form-data"
          action="<?= h(url('admin/product_edit.php' . ($existing ? '?id=' . (int) $existing['id'] : ''))) ?>">
        <?= csrf_field() ?>

        <div class="form-grid">
            <div class="form-group">
                <label for="name">Product name *</label>
                <input type="text" id="name" name="name" required value="<?= h((string) $formData['name']) ?>">
            </div>
            <div class="form-group">
                <label for="slug">Slug (URL-friendly, optional)</label>
                <input type="text" id="slug" name="slug" value="<?= h((string) ($formData['slug'] ?? '')) ?>" placeholder="auto-generated from name if left blank">
            </div>

            <div class="form-group">
                <label for="category_id">Category</label>
                <select id="category_id" name="category_id">
                    <option value="">— Uncategorized —</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int) $c['id'] ?>" <?= (string) ($formData['category_id'] ?? '') === (string) $c['id'] ? 'selected' : '' ?>>
                            <?= h($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="sort_order">Sort order</label>
                <input type="number" id="sort_order" name="sort_order" value="<?= h((string) $formData['sort_order']) ?>">
            </div>

            <div class="form-group">
                <label for="price">Price *</label>
                <input type="number" step="0.01" min="0" id="price" name="price" required value="<?= h((string) $formData['price']) ?>">
            </div>
            <div class="form-group">
                <label for="currency">Currency</label>
                <input type="text" id="currency" name="currency" value="<?= h((string) $formData['currency']) ?>" maxlength="8">
            </div>

            <div class="form-group full">
                <label for="description">Description</label>
                <textarea id="description" name="description"><?= h((string) $formData['description']) ?></textarea>
            </div>

            <div class="form-group">
                <label for="width_cm">Width (cm)</label>
                <input type="number" step="0.1" id="width_cm" name="width_cm" value="<?= h((string) ($formData['width_cm'] ?? '')) ?>">
            </div>
            <div class="form-group">
                <label for="height_cm">Height (cm)</label>
                <input type="number" step="0.1" id="height_cm" name="height_cm" value="<?= h((string) ($formData['height_cm'] ?? '')) ?>">
            </div>
            <div class="form-group">
                <label for="depth_cm">Depth (cm)</label>
                <input type="number" step="0.1" id="depth_cm" name="depth_cm" value="<?= h((string) ($formData['depth_cm'] ?? '')) ?>">
            </div>

            <div class="form-group full">
                <p class="helper-text" style="margin-top:-8px;">
                    <i class="fa-solid fa-circle-info"></i>
                    Optional: record the GLB model's own measured size (in meters) so you can verify it
                    matches the real-world dimensions above before publishing.
                </p>
            </div>
            <div class="form-group">
                <label for="glb_x_m">GLB width (m)</label>
                <input type="number" step="0.0001" id="glb_x_m" name="glb_x_m" value="<?= h((string) ($formData['glb_x_m'] ?? '')) ?>">
            </div>
            <div class="form-group">
                <label for="glb_y_m">GLB height (m)</label>
                <input type="number" step="0.0001" id="glb_y_m" name="glb_y_m" value="<?= h((string) ($formData['glb_y_m'] ?? '')) ?>">
            </div>
            <div class="form-group">
                <label for="glb_z_m">GLB depth (m)</label>
                <input type="number" step="0.0001" id="glb_z_m" name="glb_z_m" value="<?= h((string) ($formData['glb_z_m'] ?? '')) ?>">
            </div>

            <div class="form-group">
                <label for="glb_file">3D Model (.glb / .gltf)</label>
                <input type="file" id="glb_file" name="glb_file" accept=".glb,.gltf">
                <?php if (!empty($formData['glb_path'])): ?>
                    <p class="helper-text"><i class="fa-solid fa-check"></i> Current: <?= h((string) $formData['glb_path']) ?></p>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label for="usdz_file">iOS AR Model (.usdz) — optional</label>
                <input type="file" id="usdz_file" name="usdz_file" accept=".usdz">
                <?php if (!empty($formData['usdz_path'])): ?>
                    <p class="helper-text"><i class="fa-solid fa-check"></i> Current: <?= h((string) $formData['usdz_path']) ?></p>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label for="thumb_file">Thumbnail image</label>
                <input type="file" id="thumb_file" name="thumb_file" accept=".jpg,.jpeg,.png,.webp">
                <?php if (!empty($formData['thumb_path'])): ?>
                    <img src="<?= h(asset_url((string) $formData['thumb_path'])) ?>" class="thumb-preview" alt="Current thumbnail">
                <?php endif; ?>
            </div>

            <div class="form-group full">
                <div class="checkbox-row">
                    <input type="checkbox" id="is_active" name="is_active" value="1" <?= !empty($formData['is_active']) ? 'checked' : '' ?>>
                    <label for="is_active" style="margin:0;">Active (visible in the public catalog)</label>
                </div>
            </div>
        </div>

        <div class="action-row">
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save product</button>
            <a href="<?= h(url('admin/index.php')) ?>" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../includes/admin_layout_end.php'; ?>
