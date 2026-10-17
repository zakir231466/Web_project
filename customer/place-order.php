<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireCustomer();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: cart.php');
    exit();
}

$deliveryAddress = trim((string) ($_POST['delivery_address'] ?? ''));
$addressLength = function_exists('mb_strlen') ? mb_strlen($deliveryAddress, 'UTF-8') : strlen($deliveryAddress);
if ($addressLength < 1 || $addressLength > 255) {
    $_SESSION['checkout_error'] = 'Enter a delivery address of 1 to 255 characters.';
    $_SESSION['checkout_address'] = $deliveryAddress;
    header('Location: checkout.php');
    exit();
}

$orderIds = createOrderFromCart($deliveryAddress);

if (empty($orderIds)) {
    header('Location: cart.php');
    exit();
}

header('Location: /uiu-canteen/customer/orders.php');
exit();
