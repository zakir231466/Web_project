<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireShopOwner();

$user = getCurrentUser();
$shopId = isset($_GET['shop_id']) ? (int) $_GET['shop_id'] : 0;
$shop = getOwnerShopById($shopId, $user['id']);

if (!$shop) {
    header('Location: /uiu-canteen/shop-owner/dashboard.php');
    exit();
}

$foods = getOwnerShopFoods($shopId, $user['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($shop['name']) ?> | UIU Canteen</title>
    <style>
        :root { --primary: #5b8def; --primary-soft: #edf4ff; --dark: #1d2433; --muted: #6b7280; --bg: #f5f7fb; --white: #fff; --border: #e5e7eb; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; background: var(--bg); color: var(--dark); }
        .topbar { display: flex; justify-content: space-between; align-items: center; background: var(--white); border-bottom: 1px solid var(--border); padding: 18px 32px; }
        .brand { font-weight: 800; color: var(--primary); }
        .top-actions { display: flex; gap: 14px; align-items: center; }
        .pill { background: var(--primary-soft); color: var(--primary); border-radius: 999px; padding: 8px 14px; font-weight: 700; font-size: 0.8rem; }
        .logout { text-decoration: none; color: var(--dark); font-weight: 700; }
        .layout { display: grid; grid-template-columns: 240px 1fr; min-height: calc(100vh - 76px); }
        .sidebar { background: var(--white); border-right: 1px solid var(--border); padding: 28px 18px; }
        .nav { display: grid; gap: 10px; }
        .nav a { display: block; padding: 12px 14px; border-radius: 12px; text-decoration: none; color: var(--dark); font-weight: 600; }
        .nav a.active { background: var(--primary-soft); color: var(--primary); }
        .content { padding: 28px; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .page-header h1 { margin: 0; font-size: 2rem; }
        .panel { background: var(--white); border: 1px solid var(--border); border-radius: 18px; padding: 24px; margin-bottom: 24px; }
        .info-row { display: grid; grid-template-columns: repeat(2, minmax(180px, 1fr)); gap: 16px; margin-top: 14px; }
        .info-box { background: var(--bg); border: 1px solid var(--border); border-radius: 12px; padding: 14px; }
        .label { color: var(--muted); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.04em; }
        .value { font-size: 1.05rem; font-weight: 700; margin-top: 6px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; border-bottom: 1px solid var(--border); text-align: left; }
        th { background: var(--bg); }
        .tag { display: inline-block; padding: 5px 8px; border-radius: 8px; background: #d1fae5; color: #065f46; font-size: 0.8rem; font-weight: 700; }
        .tag.offline { background: #fee2e2; color: #991b1b; }
        @media (max-width: 820px){ .layout{grid-template-columns:1fr;} .sidebar{border-right:none;border-bottom:1px solid var(--border);} .info-row{grid-template-columns:1fr;} }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="brand">UIU CANTEEN - SHOP OWNER</div>
        <div class="top-actions">
            <span class="pill"><?= e(strtoupper($user['role_name'])) ?></span>
            <a class="logout" href="/uiu-canteen/logout.php">Logout</a>
        </div>
    </header>

    <div class="layout">
        <aside class="sidebar">
            <nav class="nav">
                <a href="dashboard.php">Dashboard</a>
                <a href="shops.php">My Shops</a>
                <a class="active" href="shop-details.php?shop_id=<?= (int) $shop['id'] ?>">Shop Details</a>
                <a href="foods.php">Manage Foods</a>
                <a href="orders.php">Orders</a>
                <a href="../profile.php">Profile</a>
            </nav>
        </aside>

        <main class="content">
            <div class="page-header">
                <h1><?= e($shop['name']) ?></h1>
                <a href="shops.php" style="color: var(--primary); text-decoration: none; font-weight: 700;">← Back to shops</a>
            </div>

            <div class="panel">
                <div class="info-row">
                    <div class="info-box">
                        <div class="label">Status</div>
                        <div class="value"><span class="tag <?= $shop['status'] === 'active' ? '' : 'offline' ?>"><?= e($shop['status']) ?></span></div>
                    </div>
                    <div class="info-box">
                        <div class="label">Category</div>
                        <div class="value"><?= e($shop['category_name'] ?? 'Uncategorized') ?></div>
                    </div>
                    <div class="info-box">
                        <div class="label">Opening Time</div>
                        <div class="value"><?= e($shop['opening_time'] ?? 'N/A') ?></div>
                    </div>
                    <div class="info-box">
                        <div class="label">Closing Time</div>
                        <div class="value"><?= e($shop['closing_time'] ?? 'N/A') ?></div>
                    </div>
                </div>
            </div>

            <div class="panel">
                <h2 style="margin-top:0;">Food Menu</h2>
                <?php if (empty($foods)): ?>
                    <p style="color: var(--muted); margin:0;">No food items added yet for this shop.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Food</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Prep</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($foods as $food): ?>
                                <tr>
                                    <td><?= e($food['name']) ?></td>
                                    <td><?= e($food['category_name'] ?? 'Uncategorized') ?></td>
                                    <td>৳<?= number_format((float) $food['price'], 2) ?></td>
                                    <td><?= (int) ($food['preparation_time'] ?? 0) ?> min</td>
                                    <td><span class="tag <?= (int) $food['is_available'] ? '' : 'offline' ?>"><?= (int) $food['is_available'] ? 'Available' : 'Unavailable' ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
