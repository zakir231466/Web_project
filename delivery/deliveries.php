<?php
require_once __DIR__ . '/../includes/auth.php';
requireDeliveryPerson();

$user = getCurrentUser();
$conn = getDbConnection();

$deliveriesStmt = $conn->prepare(
    'SELECT d.id, d.order_id, d.status, d.earning, d.accepted_time, o.customer_id, o.delivery_address, o.total_amount, u.full_name
     FROM deliveries d
     INNER JOIN orders o ON o.id = d.order_id
     INNER JOIN users u ON u.id = o.customer_id
     WHERE d.delivery_person_id = ? AND d.status NOT IN ("failed", "delivered")
     ORDER BY d.created_at DESC'
);
$deliveriesStmt->bind_param('i', $user['id']);
$deliveriesStmt->execute();
$deliveries = $deliveriesStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$deliveriesStmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assigned Deliveries | UIU Canteen</title>
    <style>
        :root { --primary: #5b8def; --success: #16a34a; --dark: #1d2433; --muted: #6b7280; --bg: #f5f7fb; --white: #fff; --border: #e5e7eb; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; background: var(--bg); color: var(--dark); }
        .topbar { display: flex; align-items: center; justify-content: space-between; background: var(--white); border-bottom: 1px solid var(--border); padding: 18px 32px; }
        .brand { font-weight: 800; color: var(--success); }
        .top-actions { display: flex; gap: 14px; align-items: center; }
        .pill { background: #d1fae5; color: var(--success); border-radius: 999px; padding: 8px 14px; font-size: 0.8rem; font-weight: 700; }
        .logout { color: var(--dark); text-decoration: none; font-weight: 700; }
        .layout { display: grid; grid-template-columns: 240px 1fr; min-height: calc(100vh - 76px); }
        .sidebar { background: var(--white); border-right: 1px solid var(--border); padding: 28px 18px; }
        .nav { display: grid; gap: 10px; }
        .nav a { text-decoration: none; color: var(--dark); font-weight: 600; border-radius: 12px; padding: 12px 14px; display: block; border: 1px solid transparent; }
        .nav a.active { background: #d1fae5; color: var(--success); border-color: #a7f3d0; }
        .content { padding: 28px; }
        .page-header { margin-bottom: 20px; }
        .page-header h1 { margin: 0; font-size: 2rem; }
        .panel { background: var(--white); border: 1px solid var(--border); border-radius: 18px; padding: 24px; margin-bottom: 24px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; border-bottom: 1px solid var(--border); text-align: left; }
        th { background: var(--bg); }
        .status { display: inline-block; padding: 5px 8px; border-radius: 8px; font-size: 0.8rem; font-weight: 700; }
        .status.pending { background: #dbeafe; color: #0c4a6e; }
        .status.assigned { background: #fef3c7; color: #92400e; }
        .status.picked_up { background: #cffafe; color: #164e63; }
        .status.delivered { background: #d1fae5; color: #065f46; }
        .status.failed { background: #fee2e2; color: #991b1b; }
        .btn { display: inline-block; background: var(--success); color: var(--white); padding: 10px 16px; border-radius: 10px; text-decoration: none; font-weight: 700; }
        @media (max-width: 820px){ .layout{grid-template-columns:1fr;} .sidebar{border-right:none;border-bottom:1px solid var(--border);} }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="brand">🚚 UIU CANTEEN - DELIVERY</div>
        <div class="top-actions">
            <span class="pill">DELIVERY PERSON</span>
            <a class="logout" href="/uiu-canteen/logout.php">Logout</a>
        </div>
    </header>
    <div class="layout">
        <aside class="sidebar">
            <nav class="nav">
                <a href="dashboard.php">Dashboard</a>
                <a class="active" href="deliveries.php">Assigned Deliveries</a>
                <a href="earnings.php">Earnings</a>
                <a href="../profile.php">Profile</a>
            </nav>
        </aside>
        <main class="content">
            <div class="page-header">
                <h1>Assigned Deliveries</h1>
            </div>
            <div class="panel">
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Address</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($deliveries as $item): ?>
                            <tr>
                                <td>#<?= (int) $item['order_id'] ?></td>
                                <td><?= e($item['full_name']) ?></td>
                                <td><?= e($item['delivery_address']) ?></td>
                                <td>৳<?= number_format((float) $item['total_amount'], 2) ?></td>
                                <td><span class="status <?= strtolower($item['status']) ?>"><?= e($item['status']) ?></span></td>
                                <td><a class="btn" href="delivery-details.php?delivery_id=<?= (int) $item['id'] ?>">View</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
