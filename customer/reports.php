<?php
require_once __DIR__ . '/../includes/auth.php';
requireCustomer();

$conn = getDbConnection();
$currentUserId = currentUserId();

$stmt = $conn->prepare(
    'SELECT r.id, r.category, r.status, r.created_at, r.admin_response, s.name AS shop_name
     FROM reports r
     INNER JOIN shops s ON s.id = r.shop_id
     WHERE r.customer_id = ?
     ORDER BY r.created_at DESC'
);
$stmt->bind_param('i', $currentUserId);
$stmt->execute();
$reports = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reports | UIU Canteen</title>
    <style>
        :root {
            --primary: #5b8def;
            --primary-soft: #edf4ff;
            --dark: #1d2433;
            --muted: #6b7280;
            --bg: #f5f7fb;
            --white: #ffffff;
            --border: #e5e7eb;
            --danger: #dc2626;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: var(--bg);
            color: var(--dark);
        }
        .container {
            max-width: 1100px;
            margin: 35px auto;
            padding: 0 20px 40px;
        }
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }
        h1 { margin: 0; font-size: 2rem; }
        .btn {
            display: inline-block;
            background: var(--primary);
            color: var(--white);
            text-decoration: none;
            padding: 10px 14px;
            border-radius: 10px;
            font-weight: 700;
        }
        .panel {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 18px;
            overflow-x: auto;
        }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 10px; border-bottom: 1px solid var(--border); text-align: left; }
        th { background: #f8fafc; }
        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 700;
        }
        .status.submitted { background: #dbeafe; color: #0c4a6e; }
        .status.under_review { background: #fef3c7; color: #92400e; }
        .status.resolved { background: #d1fae5; color: #065f46; }
        .status.rejected { background: #fee2e2; color: #991b1b; }
        .empty {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
            color: var(--muted);
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <main class="container">
        <div class="page-header">
            <h1>My Reports</h1>
            <a href="shops.php" class="btn">Report a Shop</a>
        </div>

        <?php if (empty($reports)): ?>
            <div class="empty">You have not submitted any shop reports yet.</div>
        <?php else: ?>
            <div class="panel">
                <table>
                    <thead>
                        <tr>
                            <th>Report ID</th>
                            <th>Shop</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Submitted</th>
                            <th>Admin Response</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports as $report): ?>
                            <tr>
                                <td>#<?= (int) $report['id'] ?></td>
                                <td><?= e($report['shop_name']) ?></td>
                                <td><?= e($report['category']) ?></td>
                                <td><span class="status <?= strtolower(str_replace(' ', '_', $report['status'])) ?>"><?= e(ucfirst(str_replace('_', ' ', $report['status']))) ?></span></td>
                                <td><?= date('M d, Y', strtotime($report['created_at'])) ?></td>
                                <td><?= e($report['admin_response'] ?? 'Waiting for review.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
