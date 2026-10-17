<?php
require_once __DIR__ . '/../includes/auth.php';

requireDeliveryPerson();

$user = getCurrentUser();
$conn = getDbConnection();

// Get delivery person's statistics
$statsStmt = $conn->prepare(
    'SELECT COUNT(*) as total_deliveries,
            SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status IN ("pending", "assigned", "picked_up") THEN 1 ELSE 0 END) as active,
            SUM(earning) as total_earning
     FROM deliveries
     WHERE delivery_person_id = ?'
);
$statsStmt->bind_param('i', $user['id']);
$statsStmt->execute();
$statsResult = $statsStmt->get_result();
$stats = $statsResult->fetch_assoc();
$statsStmt->close();

// Get assigned deliveries
$assignedStmt = $conn->prepare(
    'SELECT d.id, d.order_id, d.status, d.earning, d.accepted_time, o.customer_id, o.delivery_address, o.total_amount, u.full_name
     FROM deliveries d
     INNER JOIN orders o ON o.id = d.order_id
     INNER JOIN users u ON u.id = o.customer_id
     WHERE d.delivery_person_id = ? AND d.status NOT IN ("failed", "delivered")
     ORDER BY d.created_at DESC
     LIMIT 10'
);
$assignedStmt->bind_param('i', $user['id']);
$assignedStmt->execute();
$assignedResult = $assignedStmt->get_result();
$assignments = $assignedResult->fetch_all(MYSQLI_ASSOC);
$assignedStmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Dashboard | UIU Canteen</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        :root { --primary: #5b8def; --primary-soft: #edf4ff; --success: #16a34a; --dark: #1d2433; --muted: #6b7280; --bg: #f5f7fb; --white: #fff; --border: #e5e7eb; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; background: var(--bg); color: var(--dark); }
        .topbar { display: flex; align-items: center; justify-content: space-between; background: var(--white); border-bottom: 1px solid var(--border); padding: 18px 32px; position: sticky; top: 0; z-index: 10; }
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
        .page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; }
        .page-header h1 { margin: 0; font-size: 2rem; }
        .btn { display: inline-block; padding: 12px 18px; background: var(--primary); color: var(--white); border: none; border-radius: 10px; cursor: pointer; text-decoration: none; font-weight: 700; font-size: 0.9rem; }
        .btn-success { background: var(--success); }
        .card-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; margin-bottom: 28px; }
        .stat-card { background: var(--white); border: 1px solid var(--border); border-radius: 18px; padding: 20px; }
        .stat-card .label { color: var(--muted); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.04em; }
        .stat-card .value { font-size: 2rem; font-weight: 800; margin-top: 10px; }
        .panel { background: var(--white); border: 1px solid var(--border); border-radius: 18px; padding: 24px; margin-bottom: 24px; }
        .panel h2 { margin: 0 0 16px; font-size: 1.4rem; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid var(--border); }
        th { background: var(--bg); font-weight: 700; }
        .status { display: inline-block; padding: 4px 8px; border-radius: 6px; font-size: 0.8rem; font-weight: 700; }
        .status.pending { background: #dbeafe; color: #0c4a6e; }
        .status.assigned { background: #fef3c7; color: #92400e; }
        .status.picked_up { background: #cffafe; color: #164e63; }
        .status.delivered { background: #d1fae5; color: #065f46; }
        .delivery-card { background: var(--white); border: 1px solid var(--border); border-radius: 14px; padding: 16px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center; }
        .delivery-info { flex: 1; }
        .delivery-info h3 { margin: 0 0 8px; }
        .delivery-info p { margin: 0 0 4px; color: var(--muted); font-size: 0.9rem; }
        @media (max-width: 820px) { .layout { grid-template-columns: 1fr; } .sidebar { border-right: none; border-bottom: 1px solid var(--border); } .card-grid { grid-template-columns: repeat(2, 1fr); } }
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
                <a class="active" href="dashboard.php">Dashboard</a>
                <a href="deliveries.php">Assigned Deliveries</a>
                <a href="earnings.php">Earnings</a>
                <a href="../profile.php">Profile</a>
            </nav>
        </aside>

        <main class="content">
            <div class="page-header">
                <h1>Welcome, <?= e($user['full_name']) ?></h1>
            </div>

            <div class="card-grid">
                <div class="stat-card">
                    <div class="label">Total Deliveries</div>
                    <div class="value"><?= $stats['total_deliveries'] ?? 0 ?></div>
                </div>
                <div class="stat-card">
                    <div class="label">Completed</div>
                    <div class="value"><?= $stats['completed'] ?? 0 ?></div>
                </div>
                <div class="stat-card">
                    <div class="label">Active</div>
                    <div class="value"><?= $stats['active'] ?? 0 ?></div>
                </div>
                <div class="stat-card">
                    <div class="label">Total Earning</div>
                    <div class="value">৳<?= number_format($stats['total_earning'] ?? 0, 0) ?></div>
                </div>
            </div>

            <?php if (!empty($assignments)): ?>
                <div class="panel">
                    <h2>Assigned Deliveries</h2>
                    <?php foreach ($assignments as $delivery): ?>
                        <div class="delivery-card">
                            <div class="delivery-info">
                                <h3>Order #<?= $delivery['order_id'] ?></h3>
                                <p><strong><?= e($delivery['full_name']) ?></strong></p>
                                <p><?= e($delivery['delivery_address']) ?></p>
                                <p style="color: #6b7280; font-size: 0.85rem;">Total: ৳<?= number_format($delivery['total_amount'], 2) ?></p>
                            </div>
                            <div style="text-align: right;">
                                <div style="margin-bottom: 12px;">
                                    <span class="status <?= str_replace('_', '-', strtolower($delivery['status'])) ?>"><?= e($delivery['status']) ?></span>
                                </div>
                                <a href="delivery-details.php?delivery_id=<?= $delivery['id'] ?>" class="btn btn-success">View Details</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="panel">
                    <p style="color: var(--muted);">No deliveries assigned yet. Check back soon!</p>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
