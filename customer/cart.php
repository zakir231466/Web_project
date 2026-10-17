<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireCustomer();

$cartItems = getCurrentCartItems();
$cartSubtotal = getCartSubtotal();
$deliveryTotal = getCartDeliveryTotal();
$cartTotal = getCartTotal();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Cart | UIU Canteen</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background: #f5f7fb; }
        .cart-page { padding: 38px 0 60px; }
        .cart-box {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 22px;
            padding: 24px;
        }
        .cart-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            border-bottom: 1px solid #e5e7eb;
            padding: 18px 0;
        }
        .cart-item {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .cart-total {
            margin-top: 20px;
            font-size: 1.4rem;
            font-weight: 700;
            text-align: right;
        }
        .empty-state {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 22px;
            color: #6b7280;
        }
        .remove-btn {
            background: #dc2626;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 8px 12px;
            cursor: pointer;
        }
        .checkout-btn {
            display: inline-block;
            margin-top: 18px;
            background: #4a7ae3;
            color: #fff;
            padding: 12px 18px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <main class="container cart-page">
        <h1 style="margin-top:0; margin-bottom:24px;">My Cart</h1>

        <?php if (empty($cartItems)): ?>
            <div class="empty-state">Your cart is empty. Browse shops and add food to get started.</div>
        <?php else: ?>
            <div class="cart-box">
                <?php foreach ($cartItems as $item): ?>
                    <div class="cart-row">
                        <div class="cart-item">
                            <strong><?= e($item['name']) ?></strong>
                            <span><?= e($item['shop_name']) ?></span>
                            <span>Qty: <?= (int) $item['quantity'] ?></span>
                        </div>
                        <div style="display:flex; align-items:center; gap:12px;">
                            <strong><?= formatCurrency((float) $item['price'] * (int) $item['quantity']) ?></strong>
                            <a href="remove-from-cart.php?food_id=<?= (int) $item['food_id'] ?>" class="remove-btn" style="text-decoration:none; display:inline-block;">Remove</a>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div style="margin-top: 18px; display: grid; gap: 10px; text-align: right;">
                    <div>Subtotal: <?= formatCurrency($cartSubtotal) ?></div>
                    <div>Delivery Fee: <?= formatCurrency($deliveryTotal) ?></div>
                    <div class="cart-total">Total: <?= formatCurrency($cartTotal) ?></div>
                </div>
                <a class="checkout-btn" href="checkout.php">Proceed to Checkout</a>
            </div>
        <?php endif; ?>
    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
