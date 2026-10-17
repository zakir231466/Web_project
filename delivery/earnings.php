<?php
require_once __DIR__ . '/../includes/auth.php';
requireDeliveryPerson();

$user = getCurrentUser();
$conn = getDbConnection();

$earningsStmt = $conn->prepare(
    'SELECT d.id, d.order_id, d.status, d.earning, d.accepted_time, o.total_amount
     FROM deliveries d
     INNER JOIN orders o ON o.id = d.order_id
     WHERE d.delivery_person_id = ?
     ORDER BY d.created_at DESC'
);
$earningsStmt->bind_param('i', $user['id']);
$earningsStmt->execute();
$earnings = $earningsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$earningsStmt->close();

$totalEarning = 0;
foreach ($earnings as $entry) {
    $totalEarning += (float) ($entry['earning'] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Earnings | UIU Canteen</title>
    <style>
        :root { --primary: #5b8def; --success: #16a34a; --dark: #1d2433; --muted: #6b7280; --bg: #f5f7fb; --white: #fff; --border: #e5e7eb; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; background: var(--bg); color: var(--dark); }
        .topbar { display: flex; justify-content: space-between; align-items: center; background: var(--white); border-bottom: 1px solid var(--border); padding: 18px 32px; }
        .brand { font-weight: 800; color: var(--success); }
        .logout { color: var(--dark); text-decoration: none; font-weight: 700; }
        .layout { display: grid; grid-template-columns: 240px 1fr; min-height: calc(100vh - 76px); }
        .sidebar { background: var(--white); border-right: 1px solid var(--border); padding: 28px 18px; }
        .nav { display: grid; gap: 10px; }
        .nav a { text-decoration: none; color: var(--dark); font-weight: 600; border-radius: 12px; padding: 12px 14px; display: block; border: 1px solid transparent; }
        .nav a.active { background: #d1fae5; color: var(--success); border-color: #a7f3d0; }
        .content { padding: 28px; }
        .page-header { margin-bottom: 20px; }
        .page-header h1 { margin: 0; font-size: 2rem; }
        .stats { display: grid; grid-template-columns: repeat(2, minmax(200px, 1fr)); gap: 18px; margin-bottom: 24px; }
        .card { background: var(--white); border: 1px solid var(--border); border-radius: 18px; padding: 24px; }
        .label { color: var(--muted); font-size: 0.8rem; text-transform: uppercase; }
        .value { font-size: 2rem; font-weight: 800; margin-top: 10px; }
        .panel { background: var(--white); border: 1px solid var(--border); border-radius: 18px; padding: 24px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; border-bottom: 1px solid var(--border); text-align: left; }
        th { background: var(--bg); }
        @media (max-width: 820px){ .layout{grid-template-columns:1fr;} .sidebar{border-right:none;border-bottom:1px solid var(--border);} .stats { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="brand">🚚 UIU CANTEEN - DELIVERY</div>
        <div><a class="logout" href="/uiu-canteen/logout.php">Logout</a></div>
    </header>
    <div class="layout">
        <aside class="sidebar">
            <nav class="nav">
                <a href="dashboard.php">Dashboard</a>
                <a href="deliveries.php">Assigned Deliveries</a>
                <a class="active" href="earnings.php">Earnings</a>
                <a href="../profile.php">Profile</a>
            </nav>
        </aside>
        <main class="content">
            <div class="page-header">
                <h1>Earnings</h1>
            </div>
            <div class="stats">
                <div class="card">
                    <div class="label">Total Earnings</div>
                    <div class="value">৳<?= number_format($totalEarning, 2) ?></div>
                </div>
                <div class="card">
                    <div class="label">Completed Orders</div>
                    <div class="value"><?= count(array_filter($earnings, fn($entry) => $entry['status'] === 'delivered')) ?></div>
                </div>
            </div>
            <div class="panel">
                <table>
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Amount</th>
                            <th>Delivery Fee</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($earnings as $entry): ?>
                            <tr>
                                <td>#<?= (int) $entry['order_id'] ?></td>
                                <td>৳<?= number_format((float) $entry['total_amount'], 2) ?></td>
                                <td>৳<?= number_format((float) ($entry['earning'] ?? 0), 2) ?></td>
                                <td><?= e($entry['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
