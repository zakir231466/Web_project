<?php
require_once __DIR__ . '/../includes/auth.php';
requireCustomer();

$conn = getDbConnection();
$shopId = isset($_GET['shop_id']) ? (int) $_GET['shop_id'] : 0;
$shop = null;
$errors = [];
$success = '';

$categoryOptions = ['Quality', 'Late Delivery', 'Wrong Item', 'Hygiene', 'Service', 'Other'];

if ($shopId > 0) {
    $shopStmt = $conn->prepare('SELECT id, name, address, status FROM shops WHERE id = ? LIMIT 1');
    $shopStmt->bind_param('i', $shopId);
    $shopStmt->execute();
    $shop = $shopStmt->get_result()->fetch_assoc();
    $shopStmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shopId = isset($_POST['shop_id']) ? (int) $_POST['shop_id'] : 0;
    $category = trim((string) ($_POST['category'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $evidenceImage = trim((string) ($_POST['evidence_image'] ?? ''));

    if ($shopId <= 0) {
        $errors[] = 'Please select a valid shop to report.';
    }

    if (!in_array($category, $categoryOptions, true)) {
        $errors[] = 'Please choose a valid report category.';
    }

    if ($description === '') {
        $errors[] = 'Please describe the issue in detail.';
    }

    if (empty($errors)) {
        $shopStmt = $conn->prepare('SELECT id, name FROM shops WHERE id = ? LIMIT 1');
        $shopStmt->bind_param('i', $shopId);
        $shopStmt->execute();
        $shop = $shopStmt->get_result()->fetch_assoc();
        $shopStmt->close();

        if (!$shop) {
            $errors[] = 'This shop could not be found.';
        } else {
            $stmt = $conn->prepare(
                'INSERT INTO reports (customer_id, shop_id, category, description, evidence_image, status, created_at) VALUES (?, ?, ?, ?, ?, "submitted", NOW())'
            );
            $customerId = currentUserId();
            $stmt->bind_param('iisss', $customerId, $shopId, $category, $description, $evidenceImage);

            if ($stmt->execute()) {
                $success = 'Your report has been submitted successfully. Admin will review it soon.';
                $description = '';
                $category = '';
                $evidenceImage = '';
            } else {
                $errors[] = 'Unable to submit the report. Please try again.';
            }

            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Shop | UIU Canteen</title>
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
            --success: #16a34a;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: var(--bg);
            color: var(--dark);
        }
        .container {
            max-width: 720px;
            margin: 40px auto;
            padding: 0 20px 40px;
        }
        .panel {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 28px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
        }
        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: var(--white);
            border-bottom: 1px solid var(--border);
            padding: 18px 32px;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .brand { font-weight: 800; color: var(--primary); }
        .top-actions { display: flex; gap: 14px; align-items: center; }
        .pill { background: var(--primary-soft); color: var(--primary); border-radius: 999px; padding: 8px 14px; font-size: 0.8rem; font-weight: 700; }
        .logout { color: var(--dark); text-decoration: none; font-weight: 700; }
        .back-link { display: inline-block; margin-bottom: 20px; color: var(--primary); text-decoration: none; font-weight: 700; }
        .alert { padding: 14px 16px; border-radius: 12px; margin-bottom: 20px; font-weight: 600; }
        .alert.success { background: #dcfce7; border: 1px solid #bbf7d0; color: #166534; }
        .alert.error { background: #fee2e2; border: 1px solid #fecaca; color: #991b1b; }
        .shop-summary {
            background: var(--primary-soft);
            border: 1px solid #dbeafe;
            border-radius: 14px;
            padding: 16px 18px;
            margin-bottom: 20px;
        }
        .shop-summary strong { display: block; margin-bottom: 4px; }
        h1 { margin: 0 0 18px; font-size: 2rem; }
        .form-group { display: grid; gap: 8px; margin-bottom: 18px; }
        .form-group label { font-weight: 700; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 1rem;
            font-family: inherit;
        }
        .form-group textarea { min-height: 130px; resize: vertical; }
        .actions { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; margin-top: 8px; }
        .btn {
            border: none;
            border-radius: 10px;
            padding: 12px 18px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary { background: var(--primary); color: var(--white); }
        .btn-secondary { background: #f3f4f6; color: var(--dark); }
        @media (max-width: 640px) {
            .topbar { padding: 16px 20px; }
            .panel { padding: 20px; }
        }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="brand">UIU CANTEEN</div>
        <div class="top-actions">
            <span class="pill"><?= e(strtoupper(getCurrentUserRole())) ?></span>
            <a class="logout" href="/uiu-canteen/logout.php">Logout</a>
        </div>
    </header>

    <main class="container">
        <a href="shops.php" class="back-link">← Back to Shops</a>

        <?php if ($success !== ''): ?>
            <div class="alert success"><?= e($success) ?></div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <?php foreach ($errors as $error): ?>
                <div class="alert error"><?= e($error) ?></div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="panel">
            <h1>Report a Shop</h1>

            <?php if ($shop): ?>
                <div class="shop-summary">
                    <strong>Shop</strong>
                    <?= e($shop['name']) ?>
                    <?php if (!empty($shop['address'])): ?>
                        <div style="margin-top:4px; color: var(--muted);"><?= e($shop['address']) ?></div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <?php if ($shop): ?>
                    <input type="hidden" name="shop_id" value="<?= (int) $shop['id'] ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="shop_name">Shop Name</label>
                    <input id="shop_name" type="text" value="<?= e($shop['name'] ?? '') ?>" readonly>
                </div>

                <div class="form-group">
                    <label for="category">Report Category</label>
                    <select id="category" name="category" required>
                        <option value="">Select a category</option>
                        <?php foreach ($categoryOptions as $option): ?>
                            <option value="<?= e($option) ?>" <?= (isset($_POST['category']) && $_POST['category'] === $option) ? 'selected' : '' ?>><?= e($option) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" placeholder="Please describe the issue clearly..." required><?= e($_POST['description'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label for="evidence_image">Evidence Image URL (optional)</label>
                    <input id="evidence_image" type="url" name="evidence_image" value="<?= e($_POST['evidence_image'] ?? '') ?>" placeholder="https://example.com/evidence.jpg">
                </div>

                <div class="actions">
                    <button type="submit" class="btn btn-primary">Submit Report</button>
                    <a href="shops.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </main>
</body>
</html>
