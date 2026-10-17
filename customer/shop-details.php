<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireCustomer();
ensureRatingsTableExists();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rating_target'], $_POST['target_id'], $_POST['rating_value'])) {
    $ratingTarget = strtolower((string) $_POST['rating_target']);
    $targetId = (int) $_POST['target_id'];
    $ratingValue = (int) $_POST['rating_value'];
    $ratingComment = trim((string) ($_POST['rating_comment'] ?? ''));

    if (in_array($ratingTarget, ['shop', 'food'], true) && $targetId > 0 && $ratingValue >= 1 && $ratingValue <= 5) {
        $customerId = currentUserId();
        if ($customerId) {
            $saved = submitRatingForTarget($customerId, $ratingTarget, $targetId, $ratingValue, $ratingComment);
            $flashMessage = $saved ? 'Your rating and comment were saved.' : 'Unable to save your rating right now.';
        } else {
            $flashMessage = 'Please log in to rate this item.';
        }
    }
}

$shopId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$selectedMainCategory = isset($_GET['main_category']) ? trim((string) $_GET['main_category']) : 'all';
$selectedTag = isset($_GET['tag']) ? trim((string) $_GET['tag']) : 'all';
$foodSort = $_GET['sort'] ?? 'name';
if (!in_array($foodSort, ['name', 'price_asc', 'price_desc'], true)) {
    $foodSort = 'name';
}
$flashMessage = '';

$mainCategories = [
    'Breakfast' => ['breakfast', 'egg', 'omelet', 'bread', 'paratha', 'toast', 'milk'],
    'Lunch' => ['lunch', 'rice', 'dal', 'fish', 'chicken', 'polao', 'tehari', 'curry', 'roast'],
    'Snacks' => ['snack', 'fry', 'fries', 'roll', 'pakora', 'toast', 'sandwich', 'biscuit'],
    'Fast Food' => ['burger', 'pizza', 'sandwich', 'fries', 'hotdog', 'shawarma', 'combo'],
    'Dessert' => ['dessert', 'ice cream', 'cake', 'sweet', 'pudding'],
    'Drinks' => ['drink', 'tea', 'coffee', 'juice', 'cold', 'lemon', 'milkshake', 'water'],
];

$foodTags = ['Chicken', 'Egg', 'Fish', 'Rice', 'Dal', 'Vegetables', 'Vorta', 'Bakery', 'Ice Cream'];

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

    $flashMessage = $addedCount > 0 ? 'Selected items were added to your cart.' : 'Please select at least one item before adding to cart.';
}

$shop = null;
$foods = [];
$allFoods = [];

if ($shopId > 0) {
    $conn = getDbConnection();
    $stmt = $conn->prepare('SELECT s.*, c.name AS category_name FROM shops s LEFT JOIN categories c ON c.id = s.category_id WHERE s.id = ? AND s.status = "active" LIMIT 1');
    $stmt->bind_param('i', $shopId);
    $stmt->execute();
    $result = $stmt->get_result();
    $shop = $result->fetch_assoc();
    $stmt->close();

    if ($shop) {
        $allFoods = getShopFoods($shopId);
        $foods = $allFoods;

        if ($selectedMainCategory !== 'all') {
            $foods = array_values(array_filter($foods, function ($food) use ($selectedMainCategory, $mainCategories) {
                $needle = strtolower((string) ($food['name'] ?? '') . ' ' . ($food['description'] ?? ''));
                $keywords = $mainCategories[$selectedMainCategory] ?? [];

                foreach ($keywords as $keyword) {
                    if (strpos($needle, strtolower((string) $keyword)) !== false) {
                        return true;
                    }
                }

                return false;
            }));
        }

        if ($selectedTag !== 'all') {
            $foods = array_values(array_filter($foods, function ($food) use ($selectedTag) {
                $haystack = strtolower((string) ($food['name'] ?? '') . ' ' . ($food['description'] ?? ''));
                return strpos($haystack, strtolower((string) $selectedTag)) !== false;
            }));
        }

        usort($foods, function ($a, $b) use ($foodSort) {
            if ($foodSort === 'price_asc') {
                return ((float) ($a['price'] ?? 0)) <=> ((float) ($b['price'] ?? 0));
            }

            if ($foodSort === 'price_desc') {
                return ((float) ($b['price'] ?? 0)) <=> ((float) ($a['price'] ?? 0));
            }

            return strcmp(($a['name'] ?? ''), ($b['name'] ?? ''));
        });
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop Details | UIU Canteen</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background: #f5f7fb; }
        .shop-detail-page { padding: 36px 0 60px; }
        .shop-header {
            background: #fff; border: 1px solid #e5e7eb; border-radius: 24px; padding: 26px; margin-bottom: 28px;
            display: grid; grid-template-columns: 160px 1fr; gap: 20px; align-items: center;
        }
        .shop-logo {
            width: 160px; height: 160px; border-radius: 20px; background: linear-gradient(135deg, #dfeafe, #f6f9ff);
            display: flex; align-items: center; justify-content: center; overflow: hidden;
        }
        .shop-logo img { width: 100%; height: 100%; object-fit: cover; }
        .shop-logo-fallback { font-size: 3rem; font-weight: 800; color: #4a7ae3; }
        .shop-header h1 { margin: 0 0 8px; }
        .shop-header p { margin: 0; color: #4b5563; }
        .status-badge {
            display: inline-block; margin-top: 10px; padding: 6px 10px; border-radius: 999px; font-size: 0.75rem; font-weight: 800;
        }
        .status-badge.open { background: #ecfdf5; color: #166534; }
        .status-badge.closed { background: #fef2f2; color: #b91c1c; }
        .food-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 22px; }
        .food-card {
            background: linear-gradient(180deg, #f9fbff 0%, #edf5ff 100%);
            border: 1px solid #dfeafc; border-radius: 16px; padding: 0; overflow: hidden;
            box-shadow: 0 6px 16px rgba(86, 110, 180, 0.06);
        }
        .food-image { width: 100%; height: 150px; object-fit: cover; border-radius: 12px; background: #edf4ff; margin-bottom: 14px; }
        .food-avatar {
            width: 68px; height: 68px; margin: 18px auto 8px; border-radius: 50%;
            background: linear-gradient(180deg, #dfeafe 0%, #edf4ff 100%);
            display: flex; align-items: center; justify-content: center;
            color: #2d5ec2; font-size: 2rem; font-weight: 800; border: 1px solid #dde9ff;
        }
        .food-body { padding: 0 12px 12px; }
        .food-name { margin: 0 0 8px; text-align: center; font-size: 1rem; }
        .food-price { color: #2d5ec2; font-weight: 800; font-size: 1rem; margin-bottom: 8px; text-align: center; }
        .food-rating { margin-bottom: 10px; color: #f59e0b; font-weight: 700; text-align: center; }
        .food-desc { color: #4b5563; min-height: 56px; text-align: center; font-size: 0.8rem; }
        .food-meta-row { display: flex; justify-content: center; align-items: center; gap: 10px; margin-top: 12px; }
        .food-meta-row input[type='number'] { width: 48px; padding: 6px 4px; border-radius: 8px; border: 1px solid #d1d5db; text-align: center; }
        .food-meta-row label { font-size: 0.75rem; color: #374151; display: flex; align-items: center; gap: 6px; }
        .filter-bar {
            background: #fff; border: 1px solid #e5e7eb; border-radius: 18px; padding: 18px 20px; margin-bottom: 22px;
            display: flex; justify-content: flex-end; gap: 12px; flex-wrap: wrap; align-items: center;
        }
        .filter-bar select {
            min-width: 220px; padding: 12px 14px; border-radius: 12px; border: 1px solid #d1d5db; background: #fff; font-size: 0.95rem;
        }
        .filter-bar button {
            background: #4a7ae3; color: #fff; border: none; border-radius: 12px; padding: 12px 18px; font-weight: 700; cursor: pointer;
        }
        .facet-section {
            margin-bottom: 18px;
        }
        .facet-content {
            display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 20px; align-items: end; margin-bottom: 24px;
        }
        .facet-actions { display: flex; justify-content: flex-end; align-items: end; padding-bottom: 8px; }
        .facet-title {
            margin: 0 0 12px; font-size: 1.05rem; color: #111827;
        }
        .facet-pills {
            display: flex; flex-wrap: wrap; gap: 10px;
        }
        .facet-pill {
            display: inline-flex; align-items: center; justify-content: center; padding: 9px 14px; border-radius: 999px; border: 1px solid #dbeafe; background: #f8fbff; color: #2d5ec2; text-decoration: none; font-weight: 700; font-size: 0.85rem;
        }
        .facet-pill.active {
            background: #4a7ae3; color: #fff; border-color: #4a7ae3;
        }
        .btn-primary { background: #4a7ae3; color: #fff; border: none; border-radius: 10px; padding: 10px 14px; font-weight: 600; }
        .empty-state { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; padding: 20px; color: #6b7280; }
        .page-actions { margin-bottom: 20px; }
        .flash-message {
            margin-bottom: 18px; background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; border-radius: 12px; padding: 12px 14px; font-weight: 600;
        }
        @media (max-width: 700px) {
            .facet-content { display: block; }
            .facet-actions { justify-content: flex-start; padding-bottom: 0; }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <main class="container shop-detail-page">
        <div class="page-actions" style="display:flex; gap:12px; flex-wrap:wrap; align-items:center;">
            <a href="shops.php" class="btn-primary" style="text-decoration:none;">← Back to Shops</a>
            <?php if ($shop): ?>
                <a href="report-shop.php?shop_id=<?= (int) $shop['id'] ?>" class="btn-primary" style="text-decoration:none; background:#dc2626;">Report Shop</a>
            <?php endif; ?>
        </div>

        <?php if ($flashMessage !== ''): ?>
            <div class="flash-message"><?= e($flashMessage) ?></div>
        <?php endif; ?>

        <?php if (!$shop): ?>
            <div class="empty-state">Shop not found or unavailable.</div>
        <?php else: ?>
            <?php $shopOpen = isShopOpen($shop); ?>
            <?php $shopRating = getAverageRatingForTarget('shop', (int) $shop['id']); ?>
            <section class="shop-header">
                <div class="shop-logo">
                    <?php if (!empty($shop['logo_image'])): ?>
                        <img src="<?= e($shop['logo_image']) ?>" alt="<?= e($shop['name']) ?>">
                    <?php else: ?>
                        <div class="shop-logo-fallback"><?= strtoupper(substr((string) ($shop['name'] ?? 'S'), 0, 1)) ?></div>
                    <?php endif; ?>
                </div>
                <div>
                    <h1><?= e($shop['name']) ?></h1>
                    <p><?= e($shop['category_name'] ?? 'General') ?> · <?= e($shop['address'] ?? 'Address not set') ?></p>
                    <p style="margin-top: 14px;">
                        <strong>Shop Rating:</strong>
                        <?= str_repeat('★', min(5, (int) round((float) $shopRating['avg_rating']))) ?: '☆☆☆☆☆' ?>
                        (<?= number_format((float) $shopRating['avg_rating'], 1) ?> / 5 from <?= (int) $shopRating['review_count'] ?> reviews)
                    </p>
                    <p style="margin-top: 10px;"><?= e($shop['description'] ?? 'No description available.') ?></p>
                    <span class="status-badge <?= $shopOpen ? 'open' : 'closed' ?>"><?= $shopOpen ? 'Open Now' : 'Closed' ?></span>
                </div>
            </section>

            <section class="shop-header" style="margin-top: 20px; background: #f9fafb;">
                <div style="grid-column: 1 / -1; display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
                    <h2 style="margin: 0;">Rate This Shop</h2>
                    <button type="button" class="btn-primary" data-rating-trigger="shop" data-target-id="<?= (int) $shop['id'] ?>" style="padding:8px 12px; font-size:0.8rem; border-radius:8px;">Rate</button>
                </div>
            </section>

            <?php $shopReviews = getRecentRatingsForTarget('shop', (int) $shop['id'], 5); ?>
            <?php if (!empty($shopReviews)): ?>
                <section class="shop-header" style="margin-top: 20px; background: #ffffff;">
                    <div style="grid-column: 1 / -1;">
                        <h2 style="margin: 0 0 12px;">Recent Shop Reviews</h2>
                        <?php foreach ($shopReviews as $review): ?>
                            <div style="border:1px solid #e5e7eb; border-radius:12px; padding:12px; margin-bottom:10px;">
                                <div style="font-weight:700; margin-bottom:6px;">
                                    <?= e($review['customer_name']) ?> · <?= str_repeat('★', min(5, (int) $review['rating'])) ?>
                                </div>
                                <div style="color:#4b5563;">
                                    <?= e($review['comment'] ?: 'No comment provided.') ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <section>
                <div class="filter-bar">
                    <form method="GET" action="shop-details.php" style="display:flex; gap:12px; flex-wrap:wrap; align-items:center; margin:0; width:100%; justify-content:flex-end;">
                        <input type="hidden" name="id" value="<?= (int) $shop['id'] ?>">
                        <select name="sort" aria-label="Sort food items" style="min-width: 230px;">
                            <option value="name" <?= $foodSort === 'name' ? 'selected' : '' ?>>Sort: Name</option>
                            <option value="price_asc" <?= $foodSort === 'price_asc' ? 'selected' : '' ?>>Sort: Low to High</option>
                            <option value="price_desc" <?= $foodSort === 'price_desc' ? 'selected' : '' ?>>Sort: High to Low</option>
                        </select>

                        <button type="submit">Apply</button>
                    </form>
                </div>

                <?php if (!empty($foods)): ?>
                    <form method="POST" action="shop-details.php?id=<?= (int) $shop['id'] ?>">
                <?php endif; ?>
                <div class="facet-content">
                    <div>
                <div class="facet-section">
                    <h3 class="facet-title">Main Categories</h3>
                    <div class="facet-pills">
                        <?php
                        $allCategoryUrl = 'shop-details.php?id=' . (int) $shop['id'];
                        ?>
                        <a class="facet-pill <?= $selectedMainCategory === 'all' ? 'active' : '' ?>" href="<?= $allCategoryUrl ?>">All</a>
                        <?php foreach ($mainCategories as $categoryName => $keywords): ?>
                            <?php
                            $categoryUrl = 'shop-details.php?id=' . (int) $shop['id'] . '&main_category=' . urlencode($categoryName);
                            if ($selectedTag !== 'all') {
                                $categoryUrl .= '&tag=' . urlencode($selectedTag);
                            }
                            if ($foodSort !== 'name') {
                                $categoryUrl .= '&sort=' . urlencode($foodSort);
                            }
                            ?>
                            <a class="facet-pill <?= $selectedMainCategory === $categoryName ? 'active' : '' ?>" href="<?= $categoryUrl ?>">
                                <?= e($categoryName) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="facet-section">
                    <h3 class="facet-title">Food Type / Tags</h3>
                    <div class="facet-pills">
                        <?php
                        $allTagUrl = 'shop-details.php?id=' . (int) $shop['id'];
                        if ($selectedMainCategory !== 'all') {
                            $allTagUrl .= '&main_category=' . urlencode($selectedMainCategory);
                        }
                        if ($foodSort !== 'name') {
                            $allTagUrl .= '&sort=' . urlencode($foodSort);
                        }
                        ?>
                        <a class="facet-pill <?= $selectedTag === 'all' ? 'active' : '' ?>" href="<?= $allTagUrl ?>">All</a>
                        <?php foreach ($foodTags as $tag): ?>
                            <?php
                            $tagUrl = 'shop-details.php?id=' . (int) $shop['id'];
                            if ($selectedMainCategory !== 'all') {
                                $tagUrl .= '&main_category=' . urlencode($selectedMainCategory);
                            }
                            $tagUrl .= '&tag=' . urlencode($tag);
                            if ($foodSort !== 'name') {
                                $tagUrl .= '&sort=' . urlencode($foodSort);
                            }
                            ?>
                            <a class="facet-pill <?= $selectedTag === $tag ? 'active' : '' ?>" href="<?= $tagUrl ?>"><?= e($tag) ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
                    </div>
                    <?php if (!empty($foods)): ?>
                        <div class="facet-actions">
                            <button type="submit" class="btn-primary">Add Selected Items</button>
                        </div>
                    <?php endif; ?>
                </div>

                <h2 style="margin: 24px 0 20px;">Available Food Items</h2>
                <?php if (empty($foods)): ?>
                    <div class="empty-state">This shop has no food items available right now.</div>
                <?php else: ?>
                        <div class="food-grid">
                            <?php foreach ($foods as $food): ?>
                                <?php $foodRating = getAverageRatingForTarget('food', (int) $food['id']); ?>
                                <div class="food-card">
                                    <?php if (!empty($food['image_url'])): ?>
                                        <img class="food-image" src="<?= e($food['image_url']) ?>" alt="<?= e($food['name']) ?>">
                                    <?php else: ?>
                                        <div class="food-avatar"><?= strtoupper(substr((string) $food['name'], 0, 1)) ?></div>
                                    <?php endif; ?>
                                    <div class="food-body">
                                        <h3 class="food-name"><?= e($food['name']) ?></h3>
                                        <div class="food-price"><?= formatCurrency($food['price']) ?></div>
                                        <div class="food-rating">
                                            <?= str_repeat('★', min(5, (int) round((float) $foodRating['avg_rating']))) ?: '☆☆☆☆☆' ?>
                                            (<?= number_format((float) $foodRating['avg_rating'], 1) ?>)
                                        </div>
                                        <div class="food-desc"><?= e($food['description'] ?? 'No description available.') ?></div>
                                        <div class="food-meta-row">
                                            <label>
                                                <input type="checkbox" name="selected_foods[<?= (int) $food['id'] ?>]" value="1">
                                                Select
                                            </label>
                                            <input type="number" name="selected_quantities[<?= (int) $food['id'] ?>]" value="1" min="1" max="10">
                                        </div>
                                        <div style="margin-top:14px; border-top:1px solid #edf2f7; padding-top:12px; display:flex; justify-content:flex-end;">
                                            <button type="button" class="btn-primary" data-rating-trigger="food" data-target-id="<?= (int) $food['id'] ?>" style="padding:8px 12px; font-size:0.8rem; border-radius:8px;">Rate</button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                <?php endif; ?>
                <?php if (!empty($foods)): ?></form><?php endif; ?>
            </section>
        <?php endif; ?>
    </main>

    <div id="ratingModal" style="position:fixed; inset:0; background:rgba(15,23,42,0.45); display:none; align-items:center; justify-content:center; padding:20px; z-index:9999;">
        <div style="background:#fff; border:1px solid #e5e7eb; border-radius:16px; width:min(100%, 420px); padding:20px; box-shadow:0 20px 35px rgba(15,23,42,0.16);">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                <h3 id="ratingTitle" style="margin:0;">Rate</h3>
                <button type="button" id="closeRatingModal" style="border:none; background:#f3f4f6; border-radius:8px; width:32px; height:32px; cursor:pointer; font-size:1.2rem;">×</button>
            </div>
            <form method="POST" action="shop-details.php?id=<?= (int) $shop['id'] ?>">
                <input type="hidden" name="rating_target" id="ratingTargetInput">
                <input type="hidden" name="target_id" id="targetIdInput">
                <div style="display:flex; justify-content:space-between; margin:10px 0 14px;">
                    <?php for ($star = 5; $star >= 1; $star--): ?>
                        <label title="<?= $star ?> star" style="display:flex; flex-direction:column; align-items:center; gap:6px; cursor:pointer; font-size:0.76rem; font-weight:700;">
                            <input type="radio" name="rating_value" value="<?= $star ?>" required>
                            <span>★</span>
                        </label>
                    <?php endfor; ?>
                </div>
                <textarea name="rating_comment" rows="4" placeholder="Write a short comment..." style="width:100%; padding:10px 12px; border:1px solid #d1d5db; border-radius:10px; resize:vertical;"></textarea>
                <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:14px;">
                    <button type="button" id="cancelRatingModal" style="padding:8px 12px; border-radius:8px; border:1px solid #d1d5db; background:#fff; cursor:pointer;">Cancel</button>
                    <button type="submit" class="btn-primary" style="padding:8px 12px; border-radius:8px;">Submit</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const ratingModal = document.getElementById('ratingModal');
        const ratingTargetInput = document.getElementById('ratingTargetInput');
        const targetIdInput = document.getElementById('targetIdInput');
        const ratingTitle = document.getElementById('ratingTitle');
        const closeRatingModal = document.getElementById('closeRatingModal');
        const cancelRatingModal = document.getElementById('cancelRatingModal');

        document.querySelectorAll('[data-rating-trigger]').forEach(function (button) {
            button.addEventListener('click', function () {
                const targetType = button.getAttribute('data-rating-trigger');
                const targetId = button.getAttribute('data-target-id');
                ratingTargetInput.value = targetType;
                targetIdInput.value = targetId;
                ratingTitle.textContent = targetType === 'shop' ? 'Rate This Shop' : 'Rate This Food';
                ratingModal.style.display = 'flex';
            });
        });

        function hideRatingModal() {
            ratingModal.style.display = 'none';
        }

        closeRatingModal.addEventListener('click', hideRatingModal);
        cancelRatingModal.addEventListener('click', hideRatingModal);
        ratingModal.addEventListener('click', function (event) {
            if (event.target === ratingModal) {
                hideRatingModal();
            }
        });
    </script>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
