<?php
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireShopOwner();

$user = getCurrentUser();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['new_status'])) {
    $orderId = (int) $_POST['order_id'];
    $newStatus = sanitizeText($_POST['new_status']);
    $result = updateOrderStatusForOwner($orderId, $user['id'], $newStatus);

    if ($result) {
        $message = 'Order status updated successfully.';
    } else {
        $error = 'Unable to update the order status.';
    }
}

$orders = getOwnerOrders($user['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management | UIU Canteen</title>
    <style>
        :root { --primary: #5b8def; --primary-soft: #edf4ff; --dark: #1d2433; --muted: #6b7280; --bg: #f5f7fb; --white: #fff; --border: #e5e7eb; --success: #16a34a; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; background: var(--bg); color: var(--dark); }
        .topbar { display: flex; justify-content: space-between; align-items: center; background: var(--white); border-bottom: 1px solid var(--border); padding: 18px 32px; }
        .brand { font-weight: 800; color: var(--primary); }
        .top-actions { display: flex; gap: 14px; align-items: center; }
        .pill { background: var(--primary-soft); color: var(--primary); border-radius: 999px; padding: 8px 14px; font-weight: 700; font-size: 0.8rem; }
        .logout { text-decoration: none; color: var(--dark); font-weight: 700; }
        .layout { display: grid; grid-template-columns: 240px 1fr; min-height: calc(100vh - 76px); }
        .sidebar { background: var(--white); border-right: 1px solid var(--border); padding: 28px 18px; }
        .nav { display: grid; gap: 10px; }
        .nav a { display: block; padding: 12px 14px; border-radius: 12px; text-decoration: none; color: var(--dark); font-weight: 600; }
        .nav a.active { background: var(--primary-soft); color: var(--primary); }
        .content { padding: 28px; }
        .page-header { margin-bottom: 20px; }
        .page-header h1 { margin: 0; font-size: 2rem; }
        .panel { background: var(--white); border: 1px solid var(--border); border-radius: 18px; padding: 24px; margin-bottom: 24px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; border-bottom: 1px solid var(--border); text-align: left; }
        th { background: var(--bg); }
        .status { display: inline-block; padding: 5px 8px; border-radius: 8px; font-size: 0.8rem; font-weight: 700; }
        .status.pending { background: #dbeafe; color: #0c4a6e; }
        .status.accepted { background: #fef3c7; color: #92400e; }
        .status.preparing { background: #f3e8ff; color: #6b21a8; }
        .status.ready { background: #dcfce7; color: #166534; }
        .status.picked_up { background: #cffafe; color: #164e63; }
        .status.delivered { background: #d1fae5; color: #065f46; }
        .status.cancelled { background: #fee2e2; color: #991b1b; }
        .muted { color: var(--muted); }
        .btn { display: inline-block; padding: 9px 14px; background: var(--primary); color: var(--white); border: none; border-radius: 10px; font-weight: 700; cursor: pointer; }
        .alert { padding: 12px 14px; border-radius: 10px; margin-bottom: 18px; font-weight: 600; }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        form.inline { display: flex; align-items: center; gap: 10px; }
        select { padding: 9px 12px; border: 1px solid var(--border); border-radius: 10px; min-width: 150px; }
        @media (max-width: 820px){ .layout{grid-template-columns:1fr;} .sidebar{border-right:none;border-bottom:1px solid var(--border);} }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="brand">UIU CANTEEN - SHOP OWNER</div>
        <div class="top-actions">
            <span class="pill"><?= e(strtoupper($user['role_name'])) ?></span>
            <a class="logout" href="/uiu-canteen/logout.php">Logout</a>
        </div>
    </header>

    <div class="layout">
        <aside class="sidebar">
            <nav class="nav">
                <a href="dashboard.php">Dashboard</a>
                <a href="shops.php">My Shops</a>
                <a href="foods.php">Manage Foods</a>
                <a class="active" href="orders.php">Orders</a>
                <a href="../profile.php">Profile</a>
            </nav>
        </aside>

        <main class="content">
            <div class="page-header">
                <h1>Order Management</h1>
            </div>

            <?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

            <div class="panel">
                <?php if (empty($orders)): ?>
                    <p class="muted">No orders found for your shops yet.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Update</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>#<?= (int) $order['id'] ?></td>
                                    <td><?= e($order['customer_name']) ?><br><span class="muted"><?= date('M d, Y H:i', strtotime($order['created_at'])) ?></span></td>
                                    <td>
                                        <?php foreach ($order['items'] as $item): ?>
                                            <div><?= e($item['name']) ?> × <?= (int) $item['quantity'] ?></div>
                                        <?php endforeach; ?>
                                    </td>
                                    <td>৳<?= number_format((float) $order['total_amount'], 2) ?></td>
                                    <td><span class="status <?= strtolower($order['status']) ?>"><?= e($order['status']) ?></span></td>
                                    <td>
                                        <form class="inline" method="POST">
                                            <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                                            <select name="new_status">
                                                <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                                <option value="accepted" <?= $order['status'] === 'accepted' ? 'selected' : '' ?>>Accepted</option>
                                                <option value="preparing" <?= $order['status'] === 'preparing' ? 'selected' : '' ?>>Preparing</option>
                                                <option value="ready" <?= $order['status'] === 'ready' ? 'selected' : '' ?>>Ready</option>
                                                <option value="picked_up" <?= $order['status'] === 'picked_up' ? 'selected' : '' ?>>Picked Up</option>
                                                <option value="delivered" <?= $order['status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                                <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                            </select>
                                            <button type="submit" class="btn">Update</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            setInterval(function () {
                window.location.reload(true);
            }, 8000);
        });
    </script>
</body>
</html>
