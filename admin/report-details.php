<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$conn = getDbConnection();
$reportId = isset($_GET['report_id']) ? (int) $_GET['report_id'] : 0;
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report_id'])) {
    $reportId = (int) $_POST['report_id'];
    $status = sanitizeText($_POST['status'] ?? 'submitted');
    $response = sanitizeText($_POST['admin_response'] ?? '');
    $allowed = ['submitted', 'under_review', 'resolved', 'rejected'];

    if (!in_array($status, $allowed, true)) {
        $error = 'Invalid report status.';
    } else {
        $stmt = $conn->prepare('UPDATE reports SET status = ?, admin_response = ? WHERE id = ?');
        $stmt->bind_param('ssi', $status, $response, $reportId);
        if ($stmt->execute()) {
            $message = 'Report updated successfully.';
        } else {
            $error = 'Unable to update report.';
        }
        $stmt->close();
    }
}

$reportStmt = $conn->prepare(
    'SELECT r.*, u.full_name AS customer_name, s.name AS shop_name
     FROM reports r
     INNER JOIN users u ON u.id = r.customer_id
     INNER JOIN shops s ON s.id = r.shop_id
     WHERE r.id = ? LIMIT 1'
);
$reportStmt->bind_param('i', $reportId);
$reportStmt->execute();
$report = $reportStmt->get_result()->fetch_assoc();
$reportStmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Details | UIU Canteen</title>
    <style>
        :root { --primary: #5b8def; --bg: #f5f7fb; --white: #fff; --dark: #1d2433; --muted: #6b7280; --border: #e5e7eb; --danger: #dc2626; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; background: var(--bg); color: var(--dark); }
        .topbar { display: flex; justify-content: space-between; align-items: center; background: var(--white); border-bottom: 1px solid var(--border); padding: 18px 32px; }
        .brand { font-weight: 800; color: var(--danger); }
        .logout { color: var(--dark); text-decoration: none; font-weight: 700; }
        .content { max-width: 1000px; margin: 40px auto; padding: 0 20px; }
        .panel { background: var(--white); border: 1px solid var(--border); border-radius: 18px; padding: 24px; margin-bottom: 24px; }
        .status { display: inline-block; padding: 5px 8px; border-radius: 8px; font-size: 0.8rem; font-weight: 700; }
        .status.submitted { background: #dbeafe; color: #0c4a6e; }
        .status.under_review { background: #fef3c7; color: #92400e; }
        .status.resolved { background: #d1fae5; color: #065f46; }
        .status.rejected { background: #fee2e2; color: #991b1b; }
        .btn { display: inline-block; background: var(--primary); color: var(--white); padding: 10px 16px; border: none; border-radius: 10px; font-weight: 700; cursor: pointer; text-decoration: none; }
        .alert { padding: 12px 14px; border-radius: 10px; margin-bottom: 18px; font-weight: 600; }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        form { display: grid; gap: 16px; }
        label { display: block; font-weight: 700; margin-bottom: 8px; }
        select, textarea { width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 10px; font-family: inherit; }
        textarea { min-height: 140px; resize: vertical; }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="brand">⚙️ UIU CANTEEN - ADMIN</div>
        <div><a class="logout" href="/uiu-canteen/logout.php">Logout</a></div>
    </header>
    <main class="content">
        <a class="btn" href="reports.php">← Back to reports</a>
        <?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

        <?php if (!$report): ?>
            <div class="panel"><p>Report not found.</p></div>
        <?php else: ?>
            <div class="panel">
                <h1>Report #<?= (int) $report['id'] ?></h1>
                <p><strong>Customer:</strong> <?= e($report['customer_name']) ?></p>
                <p><strong>Shop:</strong> <?= e($report['shop_name']) ?></p>
                <p><strong>Category:</strong> <?= e($report['category']) ?></p>
                <p><strong>Status:</strong> <span class="status <?= strtolower($report['status']) ?>"><?= e($report['status']) ?></span></p>
                <p><strong>Submitted:</strong> <?= e($report['created_at']) ?></p>
                <p><strong>Issue:</strong></p>
                <div><?= nl2br(e($report['description'])) ?></div>
            </div>

            <div class="panel">
                <h2>Review Report</h2>
                <form method="POST">
                    <input type="hidden" name="report_id" value="<?= (int) $report['id'] ?>">
                    <div>
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="submitted" <?= $report['status'] === 'submitted' ? 'selected' : '' ?>>Submitted</option>
                            <option value="under_review" <?= $report['status'] === 'under_review' ? 'selected' : '' ?>>Under Review</option>
                            <option value="resolved" <?= $report['status'] === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                            <option value="rejected" <?= $report['status'] === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                        </select>
                    </div>
                    <div>
                        <label for="admin_response">Admin Response</label>
                        <textarea id="admin_response" name="admin_response" placeholder="Write the response to the customer..."><?= e($report['admin_response'] ?? '') ?></textarea>
                    </div>
                    <button class="btn" type="submit">Save Review</button>
                </form>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
