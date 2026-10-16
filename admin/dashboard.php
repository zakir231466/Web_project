<?php
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

$user = getCurrentUser();
$conn = getDbConnection();

// Get shop statistics
$shopsStmt = $conn->prepare('SELECT COUNT(*) as total, SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as active FROM shops');
$shopsStmt->execute();
$shopsResult = $shopsStmt->get_result();
$shopStats = $shopsResult->fetch_assoc();
$shopsStmt->close();

// Get reports statistics
$reportsStmt = $conn->prepare('SELECT COUNT(*) as total, SUM(CASE WHEN status = "submitted" THEN 1 ELSE 0 END) as pending FROM reports');
$reportsStmt->execute();
$reportsResult = $reportsStmt->get_result();
$reportStats = $reportsResult->fetch_assoc();
$reportsStmt->close();

// Get recent shops
$recentShopsStmt = $conn->prepare(
    'SELECT s.id, s.name, s.status, COUNT(DISTINCT f.id) as food_count, u.full_name as owner
     FROM shops s
     LEFT JOIN foods f ON f.shop_id = s.id
     INNER JOIN users u ON u.id = s.owner_id
     GROUP BY s.id
     ORDER BY s.created_at DESC
     LIMIT 8'
);
$recentShopsStmt->execute();
$shopsResult = $recentShopsStmt->get_result();
$recentShops = $shopsResult->fetch_all(MYSQLI_ASSOC);
$recentShopsStmt->close();

// Get pending reports
$pendingReportsStmt = $conn->prepare(
    'SELECT r.id, r.category, r.status, u.full_name as customer, s.name as shop, r.created_at
     FROM reports r
     INNER JOIN users u ON u.id = r.customer_id
     INNER JOIN shops s ON s.id = r.shop_id
     WHERE r.status IN ("submitted", "under_review")
     ORDER BY r.created_at DESC
     LIMIT 5'
);
$pendingReportsStmt->execute();
$reportsResult = $pendingReportsStmt->get_result();
$pendingReports = $reportsResult->fetch_all(MYSQLI_ASSOC);
$pendingReportsStmt->close();

// Get sales summary
$totalSalesStmt = $conn->prepare(
    'SELECT COALESCE(SUM(total_amount), 0) AS total_sales,
            COUNT(*) AS total_orders
     FROM orders
     WHERE status != "cancelled"'
);
$totalSalesStmt->execute();
$totalSalesData = $totalSalesStmt->get_result()->fetch_assoc();
$totalSalesStmt->close();

$topShopStmt = $conn->prepare(
    'SELECT s.name AS shop_name,
            SUM(oi.quantity * oi.price) AS total_sales,
            SUM(oi.quantity) AS total_items
     FROM order_items oi
     INNER JOIN shops s ON s.id = oi.shop_id
     INNER JOIN orders o ON o.id = oi.order_id
     WHERE o.status != "cancelled"
     GROUP BY s.id
     ORDER BY total_sales DESC, total_items DESC
     LIMIT 1'
);
$topShopStmt->execute();
$topShop = $topShopStmt->get_result()->fetch_assoc();
$topShopStmt->close();

// Get daily sales overview
$dailySalesStmt = $conn->prepare(
    'SELECT DATE(o.created_at) AS sale_date,
            COUNT(*) AS order_count,
            SUM(o.total_amount) AS total_sales
     FROM orders o
     WHERE o.status != "cancelled"
     GROUP BY DATE(o.created_at)
     ORDER BY sale_date DESC
     LIMIT 14'
);
$dailySalesStmt->execute();
$dailySales = $dailySalesStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$dailySalesStmt->close();

// Get sales by shop by date
$shopSalesStmt = $conn->prepare(
    'SELECT DATE(o.created_at) AS sale_date,
            s.name AS shop_name,
            SUM(oi.quantity * oi.price) AS total_sales,
            SUM(oi.quantity) AS total_items
     FROM order_items oi
     INNER JOIN shops s ON s.id = oi.shop_id
     INNER JOIN orders o ON o.id = oi.order_id
     WHERE o.status != "cancelled"
     GROUP BY DATE(o.created_at), s.id
     ORDER BY sale_date DESC, total_sales DESC
     LIMIT 20'
);
$shopSalesStmt->execute();
$shopSales = $shopSalesStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$shopSalesStmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | UIU Canteen</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        :root { --primary: #5b8def; --primary-soft: #edf4ff; --dark: #1d2433; --muted: #6b7280; --bg: #f5f7fb; --white: #fff; --border: #e5e7eb; --danger: #dc2626; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; background: var(--bg); color: var(--dark); }
        .topbar { display: flex; align-items: center; justify-content: space-between; background: var(--white); border-bottom: 1px solid var(--border); padding: 18px 32px; position: sticky; top: 0; z-index: 10; }
        .brand { font-weight: 800; color: var(--danger); }
        .top-actions { display: flex; gap: 14px; align-items: center; }
        .pill { background: #fee2e2; color: var(--danger); border-radius: 999px; padding: 8px 14px; font-size: 0.8rem; font-weight: 700; }
        .logout { color: var(--dark); text-decoration: none; font-weight: 700; }
        .layout { display: grid; grid-template-columns: 240px 1fr; min-height: calc(100vh - 76px); }
        .sidebar { background: var(--white); border-right: 1px solid var(--border); padding: 28px 18px; }
        .nav { display: grid; gap: 10px; }
        .nav a { text-decoration: none; color: var(--dark); font-weight: 600; border-radius: 12px; padding: 12px 14px; display: block; border: 1px solid transparent; }
        .nav a.active { background: #fee2e2; color: var(--danger); border-color: #fecaca; }
        .content { padding: 28px; }
        .page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; }
        .page-header h1 { margin: 0; font-size: 2rem; }
        .btn { display: inline-block; padding: 12px 18px; background: var(--primary); color: var(--white); border: none; border-radius: 10px; cursor: pointer; text-decoration: none; font-weight: 700; }
        .btn-danger { background: var(--danger); }
        .card-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 18px; margin-bottom: 28px; }
        .stat-card { background: var(--white); border: 1px solid var(--border); border-radius: 18px; padding: 20px; }
        .stat-card .label { color: var(--muted); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.04em; }
        .stat-card .value { font-size: 2.5rem; font-weight: 800; margin-top: 10px; }
        .panel { background: var(--white); border: 1px solid var(--border); border-radius: 18px; padding: 24px; margin-bottom: 24px; }
        .panel h2 { margin: 0 0 16px; font-size: 1.4rem; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid var(--border); }
        th { background: var(--bg); font-weight: 700; }
        .status { display: inline-block; padding: 4px 8px; border-radius: 6px; font-size: 0.8rem; font-weight: 700; }
        .status.active { background: #d1fae5; color: #065f46; }
        .status.warning { background: #fef3c7; color: #92400e; }
        .status.suspended { background: #fee2e2; color: #991b1b; }
        .status.submitted { background: #dbeafe; color: #0c4a6e; }
        .status.under_review { background: #fef3c7; color: #92400e; }
        @media (max-width: 820px) { .layout { grid-template-columns: 1fr; } .sidebar { border-right: none; border-bottom: 1px solid var(--border); } .card-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="brand">⚙️ UIU CANTEEN - ADMIN</div>
        <div class="top-actions">
            <span class="pill">ADMIN</span>
            <a class="logout" href="/uiu-canteen/logout.php">Logout</a>
        </div>
    </header>

    <div class="layout">
        <aside class="sidebar">
            <nav class="nav">
                <a class="active" href="dashboard.php">Dashboard</a>
                <a href="shops.php">Manage Shops</a>
                <a href="reports.php">Reports</a>
                <a href="actions.php">Admin Actions</a>
                <a href="../profile.php">Profile</a>
            </nav>
        </aside>

        <main class="content">
            <div class="page-header">
                <h1>Admin Dashboard</h1>
            </div>

            <div class="card-grid">
                <div class="stat-card">
                    <div class="label">Total Sales</div>
                    <div class="value">৳<?= number_format((float) ($totalSalesData['total_sales'] ?? 0), 2) ?></div>
                </div>
                <div class="stat-card">
                    <div class="label">Top Selling Shop</div>
                    <div class="value" style="font-size: 1.3rem; line-height:1.5;">
                        <?= e($topShop['shop_name'] ?? 'No sales yet') ?>
                    </div>
                </div>
            </div>

            <div class="card-grid">
                <div class="stat-card">
                    <div class="label">Active Shops</div>
                    <div class="value"><?= $shopStats['active'] ?? 0 ?></div>
                </div>
                <div class="stat-card">
                    <div class="label">Pending Reports</div>
                    <div class="value"><?= $reportStats['pending'] ?? 0 ?></div>
                </div>
            </div>

            <div class="card-grid">
                <div class="stat-card">
                    <div class="label">Total Shops</div>
                    <div class="value"><?= $shopStats['total'] ?? 0 ?></div>
                </div>
                <div class="stat-card">
                    <div class="label">Total Reports</div>
                    <div class="value"><?= $reportStats['total'] ?? 0 ?></div>
                </div>
            </div>

            <div class="panel">
                <h2>Sales by Day</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Orders</th>
                            <th>Total Sales</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($dailySales)): ?>
                            <?php foreach ($dailySales as $day): ?>
                                <tr>
                                    <td><?= date('M d, Y', strtotime($day['sale_date'])) ?></td>
                                    <td><?= (int) $day['order_count'] ?></td>
                                    <td>৳<?= number_format((float) $day['total_sales'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3">No sales data available yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="panel">
                <h2>Sales by Shop</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Shop</th>
                            <th>Units Sold</th>
                            <th>Total Sales</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($shopSales)): ?>
                            <?php foreach ($shopSales as $sale): ?>
                                <tr>
                                    <td><?= date('M d, Y', strtotime($sale['sale_date'])) ?></td>
                                    <td><?= e($sale['shop_name']) ?></td>
                                    <td><?= (int) $sale['total_items'] ?></td>
                                    <td>৳<?= number_format((float) $sale['total_sales'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4">No shop sales data available yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if (!empty($recentShops)): ?>
                <div class="panel">
                    <h2>Recent Shops</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Shop Name</th>
                                <th>Owner</th>
                                <th>Status</th>
                                <th>Foods</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentShops as $shop): ?>
                                <tr>
                                    <td><?= e($shop['name']) ?></td>
                                    <td><?= e($shop['owner']) ?></td>
                                    <td><span class="status <?= strtolower($shop['status']) ?>"><?= e($shop['status']) ?></span></td>
                                    <td><?= $shop['food_count'] ?></td>
                                    <td><a href="shop-details.php?shop_id=<?= $shop['id'] ?>" class="btn">View</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php if (!empty($pendingReports)): ?>
                <div class="panel">
                    <h2>Pending Reports</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Customer</th>
                                <th>Shop</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingReports as $report): ?>
                                <tr>
                                    <td><?= e($report['category']) ?></td>
                                    <td><?= e($report['customer']) ?></td>
                                    <td><?= e($report['shop']) ?></td>
                                    <td><span class="status <?= str_replace('_', '-', strtolower($report['status'])) ?>"><?= e($report['status']) ?></span></td>
                                    <td><?= date('M d, Y', strtotime($report['created_at'])) ?></td>
                                    <td><a href="report-details.php?report_id=<?= $report['id'] ?>" class="btn">Review</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
