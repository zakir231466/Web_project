<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireCustomer();

$categoryFilter = isset($_GET['category']) ? (int) $_GET['category'] : null;
$sortBy = $_GET['sort'] ?? 'name';
if (!in_array($sortBy, ['name', 'rating'], true)) {
    $sortBy = 'name';
}
$flashMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_foods']) && is_array($_POST['selected_foods'])) {
    $addedCount = 0;

    foreach ($_POST['selected_foods'] as $foodId => $selected) {
        if (empty($selected)) {
            continue;
        }

        $quantity = isset($_POST['selected_quantities'][$foodId]) ? max(1, (int) $_POST['selected_quantities'][$foodId]) : 1;
        if (addItemToCart((int) $foodId, $quantity)) {
            $addedCount++;
        }
    }

    $flashMessage = $addedCount > 0 ? 'Selected food items were added to your cart.' : 'Please select at least one item before adding to cart.';
}

$categories = getCategories();
$shops = getAllShops($categoryFilter);

if ($sortBy === 'rating') {
    usort($shops, function ($a, $b) {
        return ((float) ($b['rating'] ?? 0)) <=> ((float) ($a['rating'] ?? 0));
    });
} else {
    usort($shops, function ($a, $b) {
        return strcmp(($a['name'] ?? ''), ($b['name'] ?? ''));
    });
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Shops | UIU Canteen</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background: #f5f7fb; }
        .shop-page { padding: 40px 0 60px; }
        .toolbar {
            background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 18px 20px;
            display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 28px;
        }
        .filters { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; }
        .filters select { padding: 10px 14px; border: 1px solid #d1d5db; border-radius: 10px; background: #fff; font-size: .95rem; }
        .filters button { background: #4a7ae3; color: #fff; border: none; border-radius: 10px; padding: 10px 16px; cursor: pointer; font-weight: 600; }
        .shop-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 22px; }
        .shop-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 20px; overflow: hidden; box-shadow: 0 8px 20px rgba(15, 23, 42, 0.04); }
        .shop-image {
            height: 170px; background: linear-gradient(135deg, #dfeafe, #f6f9ff); display: flex; align-items: center; justify-content: center;
            overflow: hidden; position: relative;
        }
        .shop-image img { width: 100%; height: 100%; object-fit: cover; }
        .shop-logo-fallback {
            width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;
            font-size: 4rem; font-weight: 800; color: #4a7ae3; background: #edf4ff;
        }
        .shop-body { padding: 18px 18px 16px; }
        .shop-topline {
            display: flex; justify-content: space-between; align-items: center; gap: 10px; margin-bottom: 10px;
        }
        .shop-rating {
            display: inline-flex; align-items: center; gap: 6px; padding: 6px 10px; border-radius: 999px;
            background: #edf4ff; color: #2d5ec2; font-size: 0.78rem; font-weight: 700;
        }
        .shop-name { margin: 0 0 10px; font-size: 1.35rem; line-height: 1.2; }
        .shop-name a { color: #111827; text-decoration: none; }
        .shop-meta { color: #6b7280; font-size: 0.95rem; margin-bottom: 12px; }
        .shop-desc { color: #374151; margin: 0 0 14px; min-height: 58px; font-size: 0.95rem; line-height: 1.5; }
        .shop-status {
            display: inline-block; padding: 5px 10px; border-radius: 999px; font-size: 0.75rem; font-weight: 700;
        }
        .shop-status.open { background: #ecfdf5; color: #166534; }
        .shop-status.closed { background: #fef2f2; color: #b91c1c; }
        .category-list { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; }
        .chip {
            background: #edf4ff; color: #2d5ec2; padding: 6px 10px; border-radius: 999px; font-size: 0.76rem; font-weight: 700; border: 1px solid #dfeafe;
        }
        .shop-actions { display: flex; justify-content: space-between; align-items: center; gap: 10px; margin-top: 2px; }
        .btn-link, .btn {
            border: none; border-radius: 10px; padding: 10px 14px; font-weight: 700; text-decoration: none; display: inline-block; cursor: pointer; font-size: 0.92rem;
        }
        .btn-link { background: #4a7ae3; color: #fff; min-width: 90px; text-align: center; }
        .btn-secondary { background: #f3f4f6; color: #111827; border: 1px solid #d5d9e0; }
        .btn-primary { background: #4a7ae3; color: #fff; }
        .btn-light { background: #e5e7eb; color: #111827; }
        .modal-backdrop {
            position: fixed; inset: 0; background: rgba(15, 23, 42, 0.5); display: none; align-items: center; justify-content: center; padding: 20px; z-index: 100;
        }
        .modal-backdrop.show { display: flex; }
        .modal-box {
            background: #fff; width: min(100%, 640px); border-radius: 18px; border: 1px solid #e5e7eb; box-shadow: 0 20px 40px rgba(15, 23, 42, 0.18); overflow: hidden;
        }
        .modal-header, .modal-footer { padding: 18px 20px; display: flex; justify-content: space-between; align-items: center; gap: 12px; }
        .modal-header { border-bottom: 1px solid #eef2f7; }
        .modal-footer { border-top: 1px solid #eef2f7; }
        .modal-body { padding: 18px 20px; max-height: 65vh; overflow-y: auto; }
        .food-option {
            display: grid; grid-template-columns: 72px 1fr auto auto; gap: 12px; align-items: center; padding: 10px 0; border-bottom: 1px solid #f1f5f9;
        }
        .food-option:last-child { border-bottom: none; }
        .food-option img { width: 72px; height: 72px; object-fit: cover; border-radius: 12px; background: #edf4ff; }
        .food-option h4 { margin: 0 0 4px; font-size: 1rem; }
        .food-meta { color: #4b5563; font-size: 0.85rem; }
        .food-option input[type="number"] { width: 72px; padding: 7px 8px; border-radius: 8px; border: 1px solid #d1d5db; }
        .food-option label { display: flex; align-items: center; gap: 8px; font-size: 0.85rem; color: #111827; }
        .empty-state { background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 24px; color: #6b7280; }
        .flash-message {
            margin-bottom: 18px; background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; border-radius: 12px; padding: 12px 14px; font-weight: 600;
        }
        @media (max-width: 560px) {
            .shop-page { padding-top: 24px; }
            .shop-grid { grid-template-columns: 1fr; gap: 18px; }
            .shop-actions { align-items: flex-end; }
            .shop-actions > div { max-width: 145px; }
            .shop-actions > div .btn { padding: 9px 11px; }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <main class="container shop-page">
        <div style="margin-bottom: 28px;">
            <h1 style="margin:0; font-size:2rem;">Browse Shops</h1>
        </div>

        <?php if ($flashMessage !== ''): ?>
            <div class="flash-message"><?= e($flashMessage) ?></div>
        <?php endif; ?>

        <?php if (empty($shops)): ?>
            <div class="empty-state">
                No shops are available right now. Please check back later.
            </div>
        <?php else: ?>
            <div class="shop-grid">
                <?php foreach ($shops as $shop): ?>
                    <?php
                    $shopFoods = getShopFoods((int) $shop['id']);
                    $shopCategories = getShopFoodCategories((int) $shop['id']);
                    $isOpen = isShopOpen($shop);
                    ?>
                    <article class="shop-card">
                        <div class="shop-image">
                            <?php if (!empty($shop['logo_image'])): ?>
                                <img src="<?= e($shop['logo_image']) ?>" alt="<?= e($shop['name']) ?>">
                            <?php else: ?>
                                <div class="shop-logo-fallback"><?= strtoupper(substr((string) ($shop['name'] ?? 'S'), 0, 1)) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="shop-body">
                            <div class="shop-topline">
                                <span class="shop-rating">★ <?= number_format((float) ($shop['rating'] ?? 0), 1) ?></span>
                                <span class="shop-status <?= $isOpen ? 'open' : 'closed' ?>"><?= $isOpen ? 'Open Now' : 'Closed' ?></span>
                            </div>
                            <h2 class="shop-name"><a href="shop-details.php?id=<?= (int) $shop['id'] ?>"><?= e($shop['name'] ?? 'Shop Name') ?></a></h2>
                            <div class="shop-meta">
                                <?= e($shop['category_name'] ?? 'General') ?> · <?= e($shop['address'] ?? 'Address not set') ?>
                            </div>
                            <p class="shop-desc"><?= e($shop['description'] ?? 'No description available.') ?></p>
                            <div class="category-list">
                                <?php foreach (($shopCategories ?: ['General']) as $categoryName): ?>
                                    <span class="chip"><?= e($categoryName) ?></span>
                                <?php endforeach; ?>
                            </div>
                            <div class="shop-actions">
                                <a class="btn-link" href="shop-details.php?id=<?= (int) $shop['id'] ?>">View Shop</a>
                                <div style="display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-end;">
                                    <a class="btn btn-secondary" href="report-shop.php?shop_id=<?= (int) $shop['id'] ?>" style="text-decoration:none; background:#f3f4f6; color:#111827;">Report</a>
                                    <button type="button" class="btn btn-secondary" data-open-modal="shop-modal-<?= (int) $shop['id'] ?>">Add to Cart</button>
                                </div>
                            </div>
                        </div>
                    </article>

                    <div class="modal-backdrop" id="shop-modal-<?= (int) $shop['id'] ?>">
                        <div class="modal-box">
                            <div class="modal-header">
                                <h3>Add items from <?= e($shop['name']) ?></h3>
                                <button type="button" class="btn btn-light close-modal">Close</button>
                            </div>
                            <form method="POST" action="shops.php">
                                <div class="modal-body">
                                    <?php if (empty($shopFoods)): ?>
                                        <div class="empty-state">This shop currently has no available food items.</div>
                                    <?php else: ?>
                                        <?php foreach ($shopFoods as $food): ?>
                                            <div class="food-option">
                                                <?php if (!empty($food['image_url'])): ?>
                                                    <img src="<?= e($food['image_url']) ?>" alt="<?= e($food['name']) ?>">
                                                <?php else: ?>
                                                    <div style="width:72px;height:72px;border-radius:12px;background:linear-gradient(135deg,#dfeafe,#f6f9ff);display:flex;align-items:center;justify-content:center;color:#4a7ae3;font-weight:700;"><?= strtoupper(substr((string) $food['name'], 0, 1)) ?></div>
                                                <?php endif; ?>
                                                <div>
                                                    <h4><?= e($food['name']) ?></h4>
                                                    <div class="food-meta"><?= formatCurrency($food['price']) ?> · <?= !empty($food['is_available']) ? 'Available' : 'Unavailable' ?></div>
                                                </div>
                                                <label>
                                                    <input type="checkbox" name="selected_foods[<?= (int) $food['id'] ?>]" value="1">
                                                    Select
                                                </label>
                                                <input type="number" name="selected_quantities[<?= (int) $food['id'] ?>]" value="1" min="1" max="10">
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-light close-modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary" <?= empty($shopFoods) ? 'disabled' : '' ?>>Add Selected Items</button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <script>
        document.addEventListener('click', function (event) {
            const trigger = event.target.closest('[data-open-modal]');
            if (trigger) {
                const modalId = trigger.getAttribute('data-open-modal');
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.classList.add('show');
                }
            }

            if (event.target.matches('.close-modal')) {
                const modal = event.target.closest('.modal-backdrop');
                if (modal) {
                    modal.classList.remove('show');
                }
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                document.querySelectorAll('.modal-backdrop.show').forEach(function (modal) {
                    modal.classList.remove('show');
                });
            }
        });
    </script>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
