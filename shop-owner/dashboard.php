<?php
require_once __DIR__ . '/../includes/auth.php';

requireShopOwner();

$user = getCurrentUser();
$conn = getDbConnection();

// Get shop owner's shops
$shopsStmt = $conn->prepare('SELECT id, name, status, created_at FROM shops WHERE owner_id = ? ORDER BY created_at DESC');
$shopsStmt->bind_param('i', $user['id']);
$shopsStmt->execute();
$shopsResult = $shopsStmt->get_result();
$shops = $shopsResult->fetch_all(MYSQLI_ASSOC);
$shopsStmt->close();

// Get recent orders for this owner's shops
$ordersStmt = $conn->prepare(
    'SELECT DISTINCT o.id, o.customer_id, o.total_amount, o.status, o.created_at, u.full_name
     FROM orders o
     INNER JOIN order_items oi ON oi.order_id = o.id
     INNER JOIN shops s ON s.id = oi.shop_id
     INNER JOIN users u ON u.id = o.customer_id
     WHERE s.owner_id = ?
     ORDER BY o.created_at DESC
     LIMIT 10'
);
$ordersStmt->bind_param('i', $user['id']);
$ordersStmt->execute();
$ordersResult = $ordersStmt->get_result();
$recentOrders = $ordersResult->fetch_all(MYSQLI_ASSOC);
$ordersStmt->close();

// Get shop stats
$statsStmt = $conn->prepare(
    'SELECT COUNT(DISTINCT s.id) as shop_count, COUNT(f.id) as food_count
     FROM shops s
     LEFT JOIN foods f ON f.shop_id = s.id
     WHERE s.owner_id = ?'
);
$statsStmt->bind_param('i', $user['id']);
$statsStmt->execute();
$statsResult = $statsStmt->get_result();
$stats = $statsResult->fetch_assoc();
$statsStmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop Owner Dashboard | UIU Canteen</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        :root { --primary: #5b8def; --primary-soft: #edf4ff; --dark: #1d2433; --muted: #6b7280; --bg: #f5f7fb; --white: #fff; --border: #e5e7eb; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; background: var(--bg); color: var(--dark); }
        .topbar { display: flex; align-items: center; justify-content: space-between; background: var(--white); border-bottom: 1px solid var(--border); padding: 18px 32px; position: sticky; top: 0; z-index: 10; }
        .brand { font-weight: 800; color: var(--primary); }
        .top-actions { display: flex; gap: 14px; align-items: center; }
        .pill { background: var(--primary-soft); color: var(--primary); border-radius: 999px; padding: 8px 14px; font-size: 0.8rem; font-weight: 700; }
        .logout { color: var(--dark); text-decoration: none; font-weight: 700; }
        .layout { display: grid; grid-template-columns: 240px 1fr; min-height: calc(100vh - 76px); }
        .sidebar { background: var(--white); border-right: 1px solid var(--border); padding: 28px 18px; }
        .nav { display: grid; gap: 10px; }
        .nav a { text-decoration: none; color: var(--dark); font-weight: 600; border-radius: 12px; padding: 12px 14px; display: block; border: 1px solid transparent; }
        .nav a.active { background: var(--primary-soft); color: var(--primary); border-color: #dbeafe; }
        .content { padding: 28px; }
        .page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; }
        .page-header h1 { margin: 0; font-size: 2rem; }
        .btn { display: inline-block; padding: 12px 18px; background: var(--primary); color: var(--white); border: none; border-radius: 10px; cursor: pointer; text-decoration: none; font-weight: 700; }
        .card-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 18px; margin-bottom: 28px; }
        .info-card { background: var(--white); border: 1px solid var(--border); border-radius: 18px; padding: 20px; }
        .info-card .label { color: var(--muted); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.04em; }
        .info-card .value { font-size: 1.5rem; font-weight: 800; margin-top: 10px; }
        .panel { background: var(--white); border: 1px solid var(--border); border-radius: 18px; padding: 24px; margin-bottom: 24px; }
        .panel h2 { margin: 0 0 16px; font-size: 1.4rem; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid var(--border); }
        th { background: var(--bg); font-weight: 700; }
        .status { display: inline-block; padding: 4px 8px; border-radius: 6px; font-size: 0.8rem; font-weight: 700; }
        .status.active { background: #d1fae5; color: #065f46; }
        .status.pending { background: #fef3c7; color: #92400e; }
        @media (max-width: 820px) { .layout { grid-template-columns: 1fr; } .sidebar { border-right: none; border-bottom: 1px solid var(--border); } .card-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="brand">UIU CANTEEN - SHOP OWNER</div>
        <div class="top-actions">
            <span class="pill">SHOP OWNER</span>
            <a class="logout" href="/uiu-canteen/logout.php">Logout</a>
        </div>
    </header>

    <div class="layout">
        <aside class="sidebar">
            <nav class="nav">
                <a class="active" href="dashboard.php">Dashboard</a>
                <a href="shops.php">My Shops</a>
                <a href="foods.php">Manage Foods</a>
                <a href="orders.php">Orders</a>
                <a href="../profile.php">Profile</a>
            </nav>
        </aside>

        <main class="content">
            <div class="page-header">
                <h1>Welcome, <?= e($user['full_name']) ?></h1>
            </div>

            <div class="card-grid">
                <div class="info-card">
                    <div class="label">My Shops</div>
                    <div class="value"><?= $stats['shop_count'] ?? 0 ?></div>
                </div>
                <div class="info-card">
                    <div class="label">Food Items</div>
                    <div class="value"><?= $stats['food_count'] ?? 0 ?></div>
                </div>
            </div>

            <?php if (!empty($shops)): ?>
                <div class="panel">
                    <h2>Your Shops</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Shop Name</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($shops as $shop): ?>
                                <tr>
                                    <td><?= e($shop['name']) ?></td>
                                    <td><span class="status <?= strtolower($shop['status']) ?>"><?= e($shop['status']) ?></span></td>
                                    <td><?= date('M d, Y', strtotime($shop['created_at'])) ?></td>
                                    <td><a href="shop-details.php?shop_id=<?= $shop['id'] ?>" class="btn">View</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php if (!empty($recentOrders)): ?>
                <div class="panel">
                    <h2>Recent Orders</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentOrders as $order): ?>
                                <tr>
                                    <td>#<?= $order['id'] ?></td>
                                    <td><?= e($order['full_name']) ?></td>
                                    <td>৳<?= number_format($order['total_amount'], 2) ?></td>
                                    <td><span class="status <?= strtolower($order['status']) ?>"><?= e($order['status']) ?></span></td>
                                    <td><?= date('M d, Y H:i', strtotime($order['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="panel">
                    <p style="color: var(--muted);">No orders yet. Create a shop and add food items to start receiving orders.</p>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
