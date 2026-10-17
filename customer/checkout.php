<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireCustomer();

$cartItems = getCurrentCartItems();
$cartSubtotal = getCartSubtotal();
$deliveryTotal = getCartDeliveryTotal();
$cartTotal = getCartTotal();
$checkoutError = $_SESSION['checkout_error'] ?? '';
$deliveryAddress = $_SESSION['checkout_address'] ?? '';
unset($_SESSION['checkout_error'], $_SESSION['checkout_address']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | UIU Canteen</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background: #f5f7fb; }
        .checkout-page { padding: 38px 0 60px; }
        .checkout-box {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 22px;
            padding: 24px;
        }
        .row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .checkout-btn {
            display: inline-block;
            margin-top: 18px;
            background: #4a7ae3;
            color: #fff;
            padding: 12px 18px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 700;
        }
        .address-field { display: grid; gap: 8px; margin-top: 18px; }
        .address-field textarea { width: 100%; min-height: 90px; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font: inherit; resize: vertical; }
        .checkout-error { margin-top: 16px; color: #b91c1c; }
        .empty-state {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 22px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <main class="container checkout-page">
        <h1 style="margin-top:0; margin-bottom:24px;">Checkout</h1>

        <?php if (empty($cartItems)): ?>
            <div class="empty-state">Your cart is empty. Add items before checking out.</div>
        <?php else: ?>
            <div class="checkout-box">
                <?php foreach ($cartItems as $item): ?>
                    <div class="row">
                        <div><?= e($item['name']) ?> × <?= (int) $item['quantity'] ?></div>
                        <div><?= formatCurrency((float) $item['price'] * (int) $item['quantity']) ?></div>
                    </div>
                <?php endforeach; ?>

                <div class="row">
                    <div>Subtotal</div>
                    <div><?= formatCurrency($cartSubtotal) ?></div>
                </div>

                <div class="row">
                    <div>Delivery Fee</div>
                    <div><?= formatCurrency($deliveryTotal) ?></div>
                </div>

                <div class="row" style="font-weight:700; border-bottom:none; margin-top:8px;">
                    <div>Total</div>
                    <div><?= formatCurrency($cartTotal) ?></div>
                </div>

                <?php if ($checkoutError !== ''): ?>
                    <p class="checkout-error" role="alert"><?= e($checkoutError) ?></p>
                <?php endif; ?>

                <form method="POST" action="place-order.php">
                    <div class="address-field">
                        <label for="delivery_address">Delivery address</label>
                        <textarea id="delivery_address" name="delivery_address" maxlength="255" required><?= e($deliveryAddress) ?></textarea>
                    </div>
                    <button type="submit" class="checkout-btn">Place Order</button>
                </form>
            </div>
        <?php endif; ?>
    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
