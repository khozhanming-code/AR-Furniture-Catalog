<?php
/**
 * admin/index.php
 * Admin dashboard: quick stats + full product management table.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/products.php';
require_once __DIR__ . '/../includes/categories.php';

$activeNav = 'products';
$pageTitle = 'Dashboard';
require __DIR__ . '/../includes/admin_layout.php';

$stats = products_stats();
$products = products_get_all();
?>

<div class="stat-grid">
    <div class="stat-card">
        <div class="num"><?= (int) $stats['products_total'] ?></div>
        <div class="label">Total products</div>
    </div>
    <div class="stat-card">
        <div class="num"><?= (int) $stats['products_active'] ?></div>
        <div class="label">Active products</div>
    </div>
    <div class="stat-card">
        <div class="num"><?= (int) $stats['categories_total'] ?></div>
        <div class="label">Categories</div>
    </div>
</div>

<div class="admin-card">
    <div class="action-row" style="justify-content:space-between;margin-bottom:14px;">
        <h2 class="mt-0 mb-0" style="font-size:1.1rem;">Products</h2>
        <a href="<?= h(url('admin/product_edit.php')) ?>" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-plus"></i> Add product
        </a>
    </div>

    <?php if (empty($products)): ?>
        <p>No products yet. Click "Add product" to create your first one.</p>
    <?php else: ?>
        <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Thumb</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>3D Model</th>
                    <th>Status</th>
                    <th>Order</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                    <tr>
                        <td>
                            <?php if (!empty($p['thumb_path'])): ?>
                                <img src="<?= h(asset_url($p['thumb_path'])) ?>" alt="" style="width:44px;height:44px;object-fit:cover;border-radius:8px;">
                            <?php else: ?>
                                <i class="fa-solid fa-couch" style="color:#ccc;font-size:1.4rem;"></i>
                            <?php endif; ?>
                        </td>
                        <td><?= h($p['name']) ?><br><small style="color:#999;"><?= h($p['slug']) ?></small></td>
                        <td><?= h($p['category_name'] ?? '—') ?></td>
                        <td><?= format_price((float) $p['price'], $p['currency']) ?></td>
                        <td>
                            <?php if (!empty($p['glb_path'])): ?>
                                <span class="badge badge-success">Uploaded</span>
                            <?php else: ?>
                                <span class="badge badge-muted">None</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($p['is_active']): ?>
                                <span class="badge badge-success">Active</span>
                            <?php else: ?>
                                <span class="badge badge-muted">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td><?= (int) $p['sort_order'] ?></td>
                        <td>
                            <div class="action-row">
                                <a href="<?= h(url('admin/product_edit.php?id=' . $p['id'])) ?>" class="btn btn-outline btn-sm">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <a href="<?= h(url('product.php?slug=' . urlencode($p['slug']))) ?>" target="_blank" class="btn btn-outline btn-sm">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                <form method="post" action="<?= h(url('admin/product_delete.php')) ?>"
                                      onsubmit="return confirm('Delete &quot;<?= h(addslashes($p['name'])) ?>&quot;? This cannot be undone.');" style="display:inline;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/admin_layout_end.php'; ?>
