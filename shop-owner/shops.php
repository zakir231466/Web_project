<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireShopOwner();

$user = getCurrentUser();
$shops = getOwnerShops($user['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Shops | UIU Canteen</title>
    <style>
        :root { --primary: #5b8def; --primary-soft: #edf4ff; --dark: #1d2433; --muted: #6b7280; --bg: #f5f7fb; --white: #fff; --border: #e5e7eb; --success: #16a34a; }
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
        .page-header { margin-bottom: 20px; }
        .page-header h1 { margin: 0; font-size: 2rem; }
        .panel { background: var(--white); border: 1px solid var(--border); border-radius: 18px; padding: 24px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; border-bottom: 1px solid var(--border); text-align: left; }
        th { background: var(--bg); }
        .status { display: inline-block; padding: 4px 8px; border-radius: 6px; font-size: 0.8rem; font-weight: 700; }
        .status.active { background: #d1fae5; color: #065f46; }
        .status.warning { background: #fef3c7; color: #92400e; }
        .status.suspended { background: #fee2e2; color: #991b1b; }
        .btn { display: inline-block; padding: 9px 14px; background: var(--primary); color: var(--white); border-radius: 10px; text-decoration: none; font-weight: 700; }
        @media (max-width: 820px){ .layout{grid-template-columns:1fr;} .sidebar{border-right:none;border-bottom:1px solid var(--border);} }
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
                <a class="active" href="shops.php">My Shops</a>
                <a href="foods.php">Manage Foods</a>
                <a href="orders.php">Orders</a>
                <a href="../profile.php">Profile</a>
            </nav>
        </aside>

        <main class="content">
            <div class="page-header">
                <h1>My Shops</h1>
            </div>

            <div class="panel">
                <?php if (empty($shops)): ?>
                    <p style="color: var(--muted);">No shops found for this account yet.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Shop</th>
                                <th>Category</th>
                                <th>Address</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($shops as $shop): ?>
                                <tr>
                                    <td>
                                        <strong><?= e($shop['name']) ?></strong><br>
                                        <small style="color: var(--muted);">Phone: <?= e($shop['phone'] ?? 'N/A') ?></small>
                                    </td>
                                    <td><?= e($shop['category_name'] ?? 'Uncategorized') ?></td>
                                    <td><?= e($shop['address'] ?? 'N/A') ?></td>
                                    <td><span class="status <?= strtolower($shop['status']) ?>"><?= e($shop['status']) ?></span></td>
                                    <td>
                                        <a class="btn" href="shop-details.php?shop_id=<?= (int) $shop['id'] ?>">View</a>
                                    </td>
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
