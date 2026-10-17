<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireCustomer();

$searchTerm = trim((string) ($_GET['q'] ?? ''));
$selectedMainCategory = isset($_GET['main_category']) ? trim((string) $_GET['main_category']) : 'all';
$selectedTag = isset($_GET['tag']) ? trim((string) $_GET['tag']) : 'all';
$sortBy = $_GET['sort'] ?? 'name';
if (!in_array($sortBy, ['name', 'price_asc', 'price_desc'], true)) {
    $sortBy = 'name';
}

$mainCategories = [
    'Breakfast' => ['breakfast', 'egg', 'omelet', 'bread', 'paratha', 'toast', 'milk'],
    'Lunch' => ['lunch', 'rice', 'dal', 'fish', 'chicken', 'polao', 'tehari', 'curry', 'roast'],
    'Snacks' => ['snack', 'fry', 'fries', 'roll', 'pakora', 'toast', 'sandwich', 'biscuit'],
    'Fast Food' => ['burger', 'pizza', 'sandwich', 'fries', 'hotdog', 'shawarma', 'combo'],
    'Dessert' => ['dessert', 'ice cream', 'cake', 'sweet', 'pudding'],
    'Drinks' => ['drink', 'tea', 'coffee', 'juice', 'cold', 'lemon', 'milkshake', 'water'],
];

$foodTags = ['AI', 'Chicken', 'Egg', 'Fish', 'Rice', 'Dal', 'Vegetables', 'Vorta', 'Bakery', 'Ice Cream'];
$flashMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rating_target'], $_POST['target_id'], $_POST['rating_value'])) {
    $ratingSaved = submitRatingForTarget(
        currentUserId(),
        (string) $_POST['rating_target'],
        (int) $_POST['target_id'],
        (int) $_POST['rating_value'],
        (string) ($_POST['rating_comment'] ?? '')
    );
    $flashMessage = $ratingSaved ? 'Your rating was submitted.' : 'Unable to submit your rating right now.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_foods']) && is_array($_POST['selected_foods'])) {
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

$shopResults = getAllShops();
$searchTokens = preg_split('/[\s,]+/', $searchTerm, -1, PREG_SPLIT_NO_EMPTY);
$searchTextTerms = [];
$searchPriceTerms = [];

foreach ($searchTokens as $token) {
    $normalizedToken = strtolower(trim((string) $token));
    if ($normalizedToken === '') {
        continue;
    }

    if (preg_match('/^(\d+(?:\.\d+)?)\s*-\s*(\d+(?:\.\d+)?)$/', $normalizedToken, $rangeMatches)) {
        $searchPriceTerms[] = [
            'min' => (float) $rangeMatches[1],
            'max' => (float) $rangeMatches[2],
        ];
        continue;
    }

    if (preg_match('/^\d+(?:\.\d+)?$/', $normalizedToken)) {
        $searchPriceTerms[] = (float) $normalizedToken;
        continue;
    }

    $searchTextTerms[] = $normalizedToken;
}

$matchedFoods = [];

foreach ($shopResults as $shop) {
    foreach (getShopFoods((int) $shop['id']) as $food) {
        $foodText = strtolower((string) ($food['name'] ?? '') . ' ' . ($food['description'] ?? ''));
        $shopText = strtolower((string) ($shop['name'] ?? '') . ' ' . ($shop['address'] ?? ''));
        $combinedText = $foodText . ' ' . $shopText;

        $matchesSearch = $searchTerm === '';

        if (!$matchesSearch) {
            $matchesSearch = false;

            foreach ($searchTextTerms as $searchTextTerm) {
                if (strpos($combinedText, $searchTextTerm) !== false) {
                    $matchesSearch = true;
                    break;
                }
            }

            if (!$matchesSearch) {
                foreach ($searchPriceTerms as $priceTerm) {
                    $foodPrice = (float) ($food['price'] ?? 0);
                    if (is_array($priceTerm)) {
                        if ($foodPrice >= $priceTerm['min'] && $foodPrice <= $priceTerm['max']) {
                            $matchesSearch = true;
                            break;
                        }
                    } elseif (abs($foodPrice - $priceTerm) < 0.0001) {
                        $matchesSearch = true;
                        break;
                    }
                }
            }
        }

        $matchesCategory = true;
        if ($selectedMainCategory !== 'all') {
            $keywords = $mainCategories[$selectedMainCategory] ?? [];
            $matchesCategory = false;
            foreach ($keywords as $keyword) {
                if (strpos($foodText, strtolower((string) $keyword)) !== false) {
                    $matchesCategory = true;
                    break;
                }
            }
        }

        $matchesTag = true;
        if ($selectedTag !== 'all') {
            $matchesTag = strpos($foodText, strtolower((string) $selectedTag)) !== false;
        }

        if ($matchesSearch && $matchesCategory && $matchesTag) {
            $matchedFoods[] = ['shop' => $shop, 'food' => $food];
        }
    }
}

usort($matchedFoods, function ($a, $b) use ($sortBy) {
    $foodA = $a['food'];
    $foodB = $b['food'];

    if ($sortBy === 'price_asc') {
        return ((float) ($foodA['price'] ?? 0)) <=> ((float) ($foodB['price'] ?? 0));
    }
    if ($sortBy === 'price_desc') {
        return ((float) ($foodB['price'] ?? 0)) <=> ((float) ($foodA['price'] ?? 0));
    }

    return strcmp((string) ($foodA['name'] ?? ''), (string) ($foodB['name'] ?? ''));
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard | UIU Canteen</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background: #eef5ff; }
        .dashboard-page { padding: 24px 0 90px; }
        .top-search {
            background: #f8fbff; border: 1px solid #dfeafc; border-radius: 16px;
            box-shadow: 0 8px 16px rgba(90, 120, 180, 0.06);
            padding: 12px 14px; margin-bottom: 14px;
        }
        .search-box {
            display: flex; align-items: center; gap: 10px; background: #f9fafb; border: 1px solid #d1d5db;
            border-radius: 12px; padding: 10px 12px;
        }
        .search-box input {
            width: 100%; border: none; background: transparent; font-size: 0.96rem; outline: none; color: #111827;
        }
        .search-btn {
            display: inline-block; margin-top: 10px; width: 100%; border: none; border-radius: 12px;
            background: #4a7ae3; color: #fff; padding: 12px 14px; font-weight: 700; font-size: 0.96rem;
        }
        .chip-group { display: flex; flex-wrap: wrap; gap: 8px; margin: 14px 0 10px; }
        .facet-pill {
            display: inline-flex; align-items: center; justify-content: center; padding: 6px 12px;
            border-radius: 999px; border: 1px solid #dbeafe; background: #f8fbff; color: #2d5ec2;
            text-decoration: none; font-weight: 700; font-size: 0.72rem;
        }
        .facet-pill.active { background: #4a7ae3; color: #fff; border-color: #4a7ae3; }
        .result-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 14px; margin-top: 16px;
        }
        .food-card {
            background: linear-gradient(180deg, #f9fbff 0%, #edf5ff 100%);
            border: 1px solid #dfeafc; border-radius: 16px; padding: 0; overflow: hidden;
            box-shadow: 0 6px 16px rgba(86, 110, 180, 0.06);
        }
        .food-thumb {
            width: 68px; height: 68px; margin: 16px auto 8px; border-radius: 50%;
            background: linear-gradient(180deg, #dfeafe 0%, #edf4ff 100%);
            display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: 800;
            color: #2d5ec2; border: 1px solid #dde9ff;
        }
        .food-body { padding: 0 10px 10px; }
        .food-name {
            margin: 0 0 4px; font-size: 0.92rem; font-weight: 800; color: #111827; text-align: center;
        }
        .food-price {
            margin: 0 0 7px; color: #2d5ec2; font-size: 0.86rem; font-weight: 800; text-align: center;
        }
        .food-rating {
            margin: 0 0 10px; color: #f59e0b; font-size: 0.78rem; font-weight: 700; text-align: center;
        }
        .food-desc {
            margin: 0 0 10px; color: #4b5563; font-size: 0.72rem; line-height: 1.35;
            min-height: 32px; text-align: center;
        }
        .food-actions {
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .checkbox-row {
            display: inline-flex; align-items: center; gap: 5px; color: #374151; font-size: 0.7rem;
        }
        .food-actions input[type="number"] {
            width: 42px; padding: 5px 4px; border: 1px solid #d1d5db; border-radius: 8px; text-align: center; font-size: 0.76rem;
        }
        .food-actions input[type="checkbox"] {
            width: 13px; height: 13px;
        }
        .food-card-footer {
            display: flex; justify-content: flex-end; margin-top: 14px; padding-top: 12px;
            border-top: 1px solid #e4edf9;
        }
        .rate-link {
            display: inline-block; padding: 8px 12px; border-radius: 9px; background: #4a7ae3;
            color: #fff; text-decoration: none; font-size: 0.78rem; font-weight: 800;
        }
        .rating-modal {
            position: fixed; inset: 0; display: none; align-items: center; justify-content: center; padding: 20px;
            background: rgba(15, 23, 42, 0.45); z-index: 9999;
        }
        .rating-modal.show { display: flex; }
        .rating-modal-box {
            width: min(100%, 420px); background: #fff; border: 1px solid #e5e7eb; border-radius: 16px;
            padding: 20px; box-shadow: 0 20px 35px rgba(15, 23, 42, 0.16);
        }
        .rating-modal-header, .rating-modal-actions {
            display: flex; justify-content: space-between; align-items: center; gap: 10px;
        }
        .rating-modal-header { margin-bottom: 12px; }
        .rating-modal-header h3 { margin: 0; color: #1f2937; }
        .rating-close {
            width: 32px; height: 32px; border: none; border-radius: 8px; background: #f3f4f6;
            cursor: pointer; font-size: 1.2rem;
        }
        .rating-stars { display: flex; justify-content: space-between; margin: 10px 0 14px; }
        .rating-stars label {
            display: flex; flex-direction: column; align-items: center; gap: 6px; cursor: pointer;
            color: #1f2937; font-size: 0.76rem; font-weight: 700;
        }
        .rating-stars span { font-size: 1rem; }
        .rating-modal textarea {
            width: 100%; box-sizing: border-box; padding: 10px 12px; border: 1px solid #d1d5db;
            border-radius: 10px; resize: vertical; font: inherit;
        }
        .rating-modal-actions { justify-content: flex-end; margin-top: 14px; }
        .rating-modal-actions button { padding: 8px 12px; border-radius: 8px; cursor: pointer; font-weight: 700; }
        .rating-cancel { border: 1px solid #d1d5db; background: #fff; }
        .rating-submit { border: none; background: #4a7ae3; color: #fff; }
        .add-bottom {
            margin: 0 0 12px; display: flex; justify-content: flex-end;
        }
        .add-bottom button {
            background: #4a7ae3; color: #fff; border: none; border-radius: 10px; padding: 10px 16px;
            font-weight: 800; cursor: pointer; font-size: 0.94rem;
        }
        .flash-message {
            margin-bottom: 18px; background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0;
            border-radius: 12px; padding: 12px 14px; font-weight: 600;
        }
        .empty-state {
            background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; padding: 20px; color: #6b7280;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <main class="container dashboard-page">
        <div class="top-search">
            <form method="GET" action="dashboard.php">
                <div class="search-box">
                    <span>🔎</span>
                    <input type="text" name="q" value="<?= e($searchTerm) ?>" placeholder="Search food, shop name, or price (e.g. Burger 120)">
                </div>
                <button type="submit" class="search-btn">Search</button>
            </form>
        </div>

        <?php if ($flashMessage !== ''): ?>
            <div class="flash-message"><?= e($flashMessage) ?></div>
        <?php endif; ?>

        <div>
            <div class="chip-group">
                <?php
                $categoryLinks = ['all' => 'dashboard.php'];
                foreach ($mainCategories as $categoryName => $keywords) {
                    $url = 'dashboard.php?main_category=' . urlencode($categoryName);
                    if ($searchTerm !== '') { $url .= '&q=' . urlencode($searchTerm); }
                    if ($selectedTag !== 'all') { $url .= '&tag=' . urlencode($selectedTag); }
                    if ($sortBy !== 'name') { $url .= '&sort=' . urlencode($sortBy); }
                    $categoryLinks[$categoryName] = $url;
                }
                ?>
                <a class="facet-pill <?= $selectedMainCategory === 'all' ? 'active' : '' ?>" href="<?= e('dashboard.php' . ($searchTerm !== '' ? '?q=' . urlencode($searchTerm) : '')) ?>">All</a>
                <?php foreach ($mainCategories as $categoryName => $keywords): ?>
                    <a class="facet-pill <?= $selectedMainCategory === $categoryName ? 'active' : '' ?>" href="<?= e($categoryLinks[$categoryName]) ?>"><?= e($categoryName) ?></a>
                <?php endforeach; ?>
            </div>

            <div class="chip-group" style="margin-top: 6px;">
                <a class="facet-pill <?= $selectedTag === 'all' ? 'active' : '' ?>" href="dashboard.php<?= $searchTerm !== '' ? '?q=' . urlencode($searchTerm) : '' ?><?= $selectedMainCategory !== 'all' ? '&main_category=' . urlencode($selectedMainCategory) : '' ?>">All Tags</a>
                <?php foreach ($foodTags as $tag): ?>
                    <?php
                    $tagUrl = 'dashboard.php';
                    if ($searchTerm !== '') { $tagUrl .= '?q=' . urlencode($searchTerm); }
                    if ($selectedMainCategory !== 'all') { $tagUrl .= (strpos($tagUrl, '?') === false ? '?' : '&') . 'main_category=' . urlencode($selectedMainCategory); }
                    $tagUrl .= (strpos($tagUrl, '?') === false ? '?' : '&') . 'tag=' . urlencode($tag);
                    ?>
                    <a class="facet-pill <?= $selectedTag === $tag ? 'active' : '' ?>" href="<?= e($tagUrl) ?>"><?= e($tag) ?></a>
                <?php endforeach; ?>
            </div>
        </div>

        <div>
            <?php if (empty($matchedFoods)): ?>
                <div class="empty-state">No food items match your search right now.</div>
            <?php else: ?>
                <form method="POST" action="dashboard.php">
                    <div class="add-bottom">
                        <button type="submit">Add Selected Items</button>
                    </div>
                    <div class="result-grid">
                        <?php foreach ($matchedFoods as $result): ?>
                            <?php
                            $shop = $result['shop'];
                            $food = $result['food'];
                            $initial = strtoupper(substr((string) ($food['name'] ?? 'F'), 0, 1));
                            $foodRating = getAverageRatingForTarget('food', (int) $food['id']);
                            ?>
                            <div class="food-card">
                                <div class="food-thumb"><?= e($initial) ?></div>
                                <div class="food-body">
                                    <h3 class="food-name"><?= e($food['name']) ?></h3>
                                    <p class="food-price"><?= formatCurrency((float) $food['price']) ?></p>
                                    <p class="food-rating">
                                        <?= str_repeat('★', min(5, (int) round((float) $foodRating['avg_rating']))) ?: '☆☆☆☆☆' ?>
                                        (<?= number_format((float) $foodRating['avg_rating'], 1) ?>)
                                    </p>
                                    <p class="food-desc"><?= e($shop['name']) ?> · <?= e($food['description'] ?? 'Freshly prepared.') ?></p>
                                    <div class="food-actions">
                                        <label class="checkbox-row">
                                            <input type="checkbox" name="selected_foods[<?= (int) $food['id'] ?>]" value="1">
                                            Select
                                        </label>
                                        <input type="number" name="selected_quantities[<?= (int) $food['id'] ?>]" value="1" min="1" max="10">
                                    </div>
                                    <div class="food-card-footer">
                                        <button type="button" class="rate-link" data-rating-trigger="food" data-target-id="<?= (int) $food['id'] ?>">Rate</button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </main>

    <div class="rating-modal" id="ratingModal" aria-hidden="true">
        <div class="rating-modal-box" role="dialog" aria-modal="true" aria-labelledby="ratingTitle">
            <div class="rating-modal-header">
                <h3 id="ratingTitle">Rate This Food</h3>
                <button type="button" class="rating-close" id="closeRatingModal" aria-label="Close">×</button>
            </div>
            <form method="POST" action="dashboard.php">
                <input type="hidden" name="rating_target" value="food">
                <input type="hidden" name="target_id" id="ratingTargetId">
                <div class="rating-stars">
                    <?php for ($star = 5; $star >= 1; $star--): ?>
                        <label title="<?= $star ?> star">
                            <input type="radio" name="rating_value" value="<?= $star ?>" required>
                            <span>★</span>
                        </label>
                    <?php endfor; ?>
                </div>
                <textarea name="rating_comment" rows="4" placeholder="Write a short comment..."></textarea>
                <div class="rating-modal-actions">
                    <button type="button" class="rating-cancel" id="cancelRatingModal">Cancel</button>
                    <button type="submit" class="rating-submit">Submit</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const ratingModal = document.getElementById('ratingModal');
        const ratingTargetId = document.getElementById('ratingTargetId');

        document.querySelectorAll('[data-rating-trigger]').forEach(function (button) {
            button.addEventListener('click', function () {
                ratingTargetId.value = button.getAttribute('data-target-id');
                ratingModal.classList.add('show');
                ratingModal.setAttribute('aria-hidden', 'false');
            });
        });

        function hideRatingModal() {
            ratingModal.classList.remove('show');
            ratingModal.setAttribute('aria-hidden', 'true');
        }

        document.getElementById('closeRatingModal').addEventListener('click', hideRatingModal);
        document.getElementById('cancelRatingModal').addEventListener('click', hideRatingModal);
        ratingModal.addEventListener('click', function (event) {
            if (event.target === ratingModal) {
                hideRatingModal();
            }
        });
    </script>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
