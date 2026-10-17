<?php
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireCustomer();

$orders = getCustomerOrders();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders | UIU Canteen</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background: #f5f7fb; }
        .orders-page { padding: 38px 0 60px; }
        .order-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            padding: 20px;
            margin-bottom: 18px;
        }
        .order-head {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }
        .status-pill {
            background: #edf4ff;
            color: #2d5ec2;
            border-radius: 999px;
            padding: 6px 10px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .item-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .item-list li {
            padding: 6px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .empty-state {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 24px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <main class="container orders-page">
        <h1 style="margin-top:0; margin-bottom:24px;">My Orders</h1>

        <?php if (empty($orders)): ?>
            <div class="empty-state">You have no orders yet.</div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-head">
                        <div>
                            <strong>Order #<?= (int) $order['id'] ?></strong>
                            <div style="color:#6b7280; font-size:0.9rem;">Created: <?= e($order['created_at']) ?></div>
                        </div>
                        <span class="status-pill"><?= e($order['status']) ?></span>
                    </div>

                    <ul class="item-list">
                        <?php foreach ($order['items'] as $item): ?>
                            <li>
                                <?= e($item['name']) ?> × <?= (int) $item['quantity'] ?> — <?= formatCurrency((float) $item['price'] * (int) $item['quantity']) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <div style="margin-top:12px; font-weight:700; text-align:right;">
                        Total: <?= formatCurrency((float) $order['total_amount']) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            setInterval(function () {
                window.location.reload(true);
            }, 8000);
        });
    </script>
</body>
</html>
