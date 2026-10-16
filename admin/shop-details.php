<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$shopId = isset($_GET['shop_id']) ? (int) $_GET['shop_id'] : 0;
$conn = getDbConnection();

$shop = null;
$foods = [];

if ($shopId > 0) {
    $stmt = $conn->prepare(
        'SELECT s.*, u.full_name AS owner_name, c.name AS category_name
         FROM shops s
         INNER JOIN users u ON u.id = s.owner_id
         LEFT JOIN categories c ON c.id = s.category_id
         WHERE s.id = ? LIMIT 1'
    );
    $stmt->bind_param('i', $shopId);
    $stmt->execute();
    $shop = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($shop) {
        $foodStmt = $conn->prepare(
            'SELECT f.id, f.name, f.price, f.is_available, f.preparation_time, c.name AS category_name
             FROM foods f
             LEFT JOIN categories c ON c.id = f.category_id
             WHERE f.shop_id = ?
             ORDER BY f.name ASC'
        );
        $foodStmt->bind_param('i', $shopId);
        $foodStmt->execute();
        $foods = $foodStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $foodStmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop Details | UIU Canteen</title>
    <style>
        :root { --primary: #5b8def; --dark: #1d2433; --muted: #6b7280; --bg: #f5f7fb; --white: #fff; --border: #e5e7eb; --danger: #dc2626; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; background: var(--bg); color: var(--dark); }
        .topbar { display: flex; justify-content: space-between; align-items: center; background: var(--white); border-bottom: 1px solid var(--border); padding: 18px 32px; }
        .brand { font-weight: 800; color: var(--danger); }
        .logout { color: var(--dark); text-decoration: none; font-weight: 700; }
        .content { max-width: 1100px; margin: 40px auto; padding: 0 20px; }
        .panel { background: var(--white); border: 1px solid var(--border); border-radius: 18px; padding: 24px; margin-bottom: 24px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; border-bottom: 1px solid var(--border); text-align: left; }
        th { background: var(--bg); }
        .status { display: inline-block; padding: 5px 8px; border-radius: 8px; font-size: 0.8rem; font-weight: 700; }
        .status.active { background: #d1fae5; color: #065f46; }
        .status.inactive { background: #dbeafe; color: #0c4a6e; }
        .status.suspended { background: #fee2e2; color: #991b1b; }
        .muted { color: var(--muted); }
        .btn { display: inline-block; background: var(--primary); color: var(--white); padding: 10px 16px; border-radius: 10px; text-decoration: none; font-weight: 700; }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="brand">⚙️ UIU CANTEEN - ADMIN</div>
        <div><a class="logout" href="/uiu-canteen/logout.php">Logout</a></div>
    </header>
    <main class="content">
        <a class="btn" href="shops.php">← Back to shops</a>
        <?php if (!$shop): ?>
            <div class="panel"><p class="muted">Shop not found.</p></div>
        <?php else: ?>
            <div class="panel">
                <h1><?= e($shop['name']) ?></h1>
                <p><strong>Owner:</strong> <?= e($shop['owner_name']) ?></p>
                <p><strong>Category:</strong> <?= e($shop['category_name'] ?? 'General') ?></p>
                <p><strong>Status:</strong> <span class="status <?= strtolower($shop['status']) ?>"><?= e($shop['status']) ?></span></p>
                <p><strong>Address:</strong> <?= e($shop['address'] ?? 'N/A') ?></p>
                <p><strong>Phone:</strong> <?= e($shop['phone'] ?? 'N/A') ?></p>
                <p><strong>Opening Hours:</strong> <?= e($shop['opening_time'] ?? 'N/A') ?> - <?= e($shop['closing_time'] ?? 'N/A') ?></p>
            </div>

            <div class="panel">
                <h2>Menu Items</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Prep Time</th>
                            <th>Available</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($foods as $food): ?>
                            <tr>
                                <td><?= e($food['name']) ?></td>
                                <td><?= e($food['category_name'] ?? 'General') ?></td>
                                <td>৳<?= number_format((float) $food['price'], 2) ?></td>
                                <td><?= (int) $food['preparation_time'] ?> min</td>
                                <td><?= $food['is_available'] ? 'Yes' : 'No' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
