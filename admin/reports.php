<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$conn = getDbConnection();
$reportsStmt = $conn->prepare(
    'SELECT r.id, r.category, r.status, r.created_at, u.full_name AS customer_name, s.name AS shop_name
     FROM reports r
     INNER JOIN users u ON u.id = r.customer_id
     INNER JOIN shops s ON s.id = r.shop_id
     ORDER BY r.created_at DESC'
);
$reportsStmt->execute();
$reports = $reportsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$reportsStmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports | UIU Canteen</title>
    <style>
        :root { --primary: #5b8def; --primary-soft: #edf4ff; --dark: #1d2433; --muted: #6b7280; --bg: #f5f7fb; --white: #fff; --border: #e5e7eb; --danger: #dc2626; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; background: var(--bg); color: var(--dark); }
        .topbar { display: flex; justify-content: space-between; align-items: center; background: var(--white); border-bottom: 1px solid var(--border); padding: 18px 32px; }
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
        .page-header { margin-bottom: 20px; }
        .page-header h1 { margin: 0; font-size: 2rem; }
        .panel { background: var(--white); border: 1px solid var(--border); border-radius: 18px; padding: 24px; margin-bottom: 24px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; border-bottom: 1px solid var(--border); text-align: left; }
        th { background: var(--bg); }
        .status { display: inline-block; padding: 5px 8px; border-radius: 8px; font-size: 0.8rem; font-weight: 700; }
        .status.submitted { background: #dbeafe; color: #0c4a6e; }
        .status.under_review { background: #fef3c7; color: #92400e; }
        .status.resolved { background: #d1fae5; color: #065f46; }
        .status.rejected { background: #fee2e2; color: #991b1b; }
        .btn { display: inline-block; padding: 9px 14px; border-radius: 10px; background: var(--primary); color: var(--white); text-decoration: none; font-weight: 700; }
        @media (max-width: 820px){ .layout{grid-template-columns:1fr;} .sidebar{border-right:none;border-bottom:1px solid var(--border);} }
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
                <a href="dashboard.php">Dashboard</a>
                <a href="shops.php">Manage Shops</a>
                <a class="active" href="reports.php">Reports</a>
                <a href="actions.php">Admin Actions</a>
                <a href="../profile.php">Profile</a>
            </nav>
        </aside>
        <main class="content">
            <div class="page-header">
                <h1>Customer Reports</h1>
            </div>
            <div class="panel">
                <table>
                    <thead>
                        <tr>
                            <th>Report ID</th>
                            <th>Customer</th>
                            <th>Shop</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports as $report): ?>
                            <tr>
                                <td>#<?= (int) $report['id'] ?></td>
                                <td><?= e($report['customer_name']) ?></td>
                                <td><?= e($report['shop_name']) ?></td>
                                <td><?= e($report['category']) ?></td>
                                <td><span class="status <?= strtolower($report['status']) ?>"><?= e($report['status']) ?></span></td>
                                <td><?= date('M d, Y', strtotime($report['created_at'])) ?></td>
                                <td><a class="btn" href="report-details.php?report_id=<?= (int) $report['id'] ?>">Review</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
