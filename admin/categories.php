<?php
/**
 * admin/categories.php
 * Lists all categories with edit/delete actions.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/categories.php';

$activeNav = 'categories';
$pageTitle = 'Categories';
require __DIR__ . '/../includes/admin_layout.php';

$categories = categories_get_all();
?>

<div class="admin-card">
    <div class="action-row" style="justify-content:space-between;margin-bottom:14px;">
        <h2 class="mt-0 mb-0" style="font-size:1.1rem;">Categories</h2>
        <a href="<?= h(url('admin/category_edit.php')) ?>" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-plus"></i> Add category
        </a>
    </div>

    <?php if (empty($categories)): ?>
        <p>No categories yet.</p>
    <?php else: ?>
        <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Slug</th>
                    <th>Products</th>
                    <th>Order</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $c): ?>
                    <tr>
                        <td><?= h($c['name']) ?></td>
                        <td><?= h($c['slug']) ?></td>
                        <td><?= (int) $c['product_count'] ?></td>
                        <td><?= (int) $c['sort_order'] ?></td>
                        <td>
                            <?php if ($c['is_active']): ?>
                                <span class="badge badge-success">Active</span>
                            <?php else: ?>
                                <span class="badge badge-muted">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-row">
                                <a href="<?= h(url('admin/category_edit.php?id=' . $c['id'])) ?>" class="btn btn-outline btn-sm">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <a href="<?= h(url('admin/category_delete.php?id=' . $c['id'])) ?>" class="btn btn-danger btn-sm">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
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
