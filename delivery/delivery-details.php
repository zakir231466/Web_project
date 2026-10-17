<?php
require_once __DIR__ . '/../includes/auth.php';
requireDeliveryPerson();

$user = getCurrentUser();
$conn = getDbConnection();
$deliveryId = isset($_GET['delivery_id']) ? (int) $_GET['delivery_id'] : 0;
$statusTransitions = [
    'pending' => ['assigned', 'failed'],
    'assigned' => ['picked_up', 'failed'],
    'picked_up' => ['delivered', 'failed'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delivery_id'], $_POST['status'])) {
    $deliveryId = (int) $_POST['delivery_id'];
    $status = sanitizeText($_POST['status']);

    if (!in_array($status, ['pending', 'assigned', 'picked_up', 'delivered', 'failed'], true)) {
        $error = 'Invalid delivery status.';
    } else {
        $conn->begin_transaction();
        $lockStmt = $conn->prepare('SELECT d.order_id, d.status, o.status AS order_status FROM deliveries d INNER JOIN orders o ON o.id = d.order_id WHERE d.id = ? AND d.delivery_person_id = ? LIMIT 1 FOR UPDATE');
        if (!$lockStmt) {
            $conn->rollback();
            $existingDelivery = null;
        } else {
            $lockStmt->bind_param('ii', $deliveryId, $user['id']);
            $lockStmt->execute();
            $existingDelivery = $lockStmt->get_result()->fetch_assoc();
            $lockStmt->close();
        }

        if (!$existingDelivery) {
            $conn->rollback();
            $error = 'Delivery not found.';
        } elseif ($existingDelivery['order_status'] === 'cancelled') {
            $conn->rollback();
            $error = 'This order has been cancelled.';
        } elseif (!in_array($status, $statusTransitions[$existingDelivery['status']] ?? [], true)) {
            $conn->rollback();
            $error = 'That status change is not allowed.';
        } else {
            $orderId = (int) $existingDelivery['order_id'];
            $updateStmt = $conn->prepare(
                'UPDATE deliveries
                 SET status = ?,
                     accepted_time = CASE WHEN ? = "assigned" THEN COALESCE(accepted_time, NOW()) ELSE accepted_time END,
                     pickup_time = CASE WHEN ? = "picked_up" THEN NOW() ELSE pickup_time END,
                     delivered_time = CASE WHEN ? = "delivered" THEN NOW() ELSE delivered_time END
                 WHERE id = ? AND delivery_person_id = ?'
            );

            if (!$updateStmt) {
                $conn->rollback();
                $error = 'Unable to update the delivery.';
            } else {
                $updateStmt->bind_param('ssssii', $status, $status, $status, $status, $deliveryId, $user['id']);
                $updated = $updateStmt->execute();
                $updateStmt->close();

                if ($updated && in_array($status, ['picked_up', 'delivered'], true)) {
                    $orderStmt = $conn->prepare('UPDATE orders SET status = ? WHERE id = ? AND status != "cancelled"');
                    if ($orderStmt) {
                        $orderStmt->bind_param('si', $status, $orderId);
                        $updated = $orderStmt->execute();
                        $orderStmt->close();
                    } else {
                        $updated = false;
                    }
                }

                if ($updated && $conn->commit()) {
                    $message = 'Delivery status updated successfully.';
                } else {
                    $conn->rollback();
                    $error = 'Unable to update the delivery.';
                }
            }
        }
    }
}

$deliveryStmt = $conn->prepare(
    'SELECT d.*, o.customer_id, o.delivery_address, o.total_amount, o.status AS order_status, u.full_name AS customer_name
     FROM deliveries d
     INNER JOIN orders o ON o.id = d.order_id
     INNER JOIN users u ON u.id = o.customer_id
    WHERE d.id = ? AND d.delivery_person_id = ? LIMIT 1'
);
$deliveryStmt->bind_param('ii', $deliveryId, $user['id']);
$deliveryStmt->execute();
$delivery = $deliveryStmt->get_result()->fetch_assoc();
$deliveryStmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Details | UIU Canteen</title>
    <style>
        :root { --primary: #5b8def; --success: #16a34a; --dark: #1d2433; --muted: #6b7280; --bg: #f5f7fb; --white: #fff; --border: #e5e7eb; --danger: #dc2626; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; background: var(--bg); color: var(--dark); }
        .topbar { display: flex; justify-content: space-between; align-items: center; background: var(--white); border-bottom: 1px solid var(--border); padding: 18px 32px; }
        .brand { font-weight: 800; color: var(--success); }
        .logout { color: var(--dark); text-decoration: none; font-weight: 700; }
        .content { max-width: 900px; margin: 40px auto; padding: 0 20px; }
        .panel { background: var(--white); border: 1px solid var(--border); border-radius: 18px; padding: 24px; margin-bottom: 24px; }
        .status { display: inline-block; padding: 5px 8px; border-radius: 8px; font-size: 0.8rem; font-weight: 700; }
        .status.pending { background: #dbeafe; color: #0c4a6e; }
        .status.assigned { background: #fef3c7; color: #92400e; }
        .status.picked_up { background: #cffafe; color: #164e63; }
        .status.delivered { background: #d1fae5; color: #065f46; }
        .status.failed { background: #fee2e2; color: #991b1b; }
        .btn { display: inline-block; background: var(--success); color: var(--white); padding: 10px 16px; border: none; border-radius: 10px; cursor: pointer; font-weight: 700; text-decoration: none; }
        .alert { padding: 12px 14px; border-radius: 10px; margin-bottom: 18px; font-weight: 600; }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        form { display: grid; gap: 16px; }
        select { width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 10px; }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="brand">🚚 UIU CANTEEN - DELIVERY</div>
        <div><a class="logout" href="/uiu-canteen/logout.php">Logout</a></div>
    </header>
    <main class="content">
        <a class="btn" href="deliveries.php">← Back to deliveries</a>
        <?php if (isset($message)): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
        <?php if (isset($error)): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
        <?php if (!$delivery): ?>
            <div class="panel"><p>Delivery not found.</p></div>
        <?php else: ?>
            <div class="panel">
                <h1>Order #<?= (int) $delivery['order_id'] ?></h1>
                <p><strong>Customer:</strong> <?= e($delivery['customer_name']) ?></p>
                <p><strong>Address:</strong> <?= e($delivery['delivery_address']) ?></p>
                <p><strong>Total:</strong> ৳<?= number_format((float) $delivery['total_amount'], 2) ?></p>
                <p><strong>Status:</strong> <span class="status <?= strtolower($delivery['status']) ?>"><?= e($delivery['status']) ?></span></p>
            </div>
            <?php if (!empty($statusTransitions[$delivery['status']] ?? [])): ?>
                <div class="panel">
                    <h2>Update Delivery Status</h2>
                    <form method="POST">
                        <input type="hidden" name="delivery_id" value="<?= (int) $delivery['id'] ?>">
                        <select name="status" required>
                            <option value="" selected disabled>Choose next status</option>
                            <?php foreach ($statusTransitions[$delivery['status']] as $nextStatus): ?>
                                <option value="<?= e($nextStatus) ?>"><?= e(ucwords(str_replace('_', ' ', $nextStatus))) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn" type="submit">Save</button>
                    </form>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>
</body>
</html>
