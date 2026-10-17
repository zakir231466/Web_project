<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireCustomer();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: shops.php');
    exit();
}

$foodId = isset($_POST['food_id']) ? (int) $_POST['food_id'] : 0;
$quantity = isset($_POST['quantity']) ? max(1, (int) $_POST['quantity']) : 1;
$redirect = $_POST['redirect'] ?? 'shops.php';

if ($foodId > 0) {
    addItemToCart($foodId, $quantity);
}

header('Location: /uiu-canteen/customer/' . ltrim($redirect, '/'));
exit();
