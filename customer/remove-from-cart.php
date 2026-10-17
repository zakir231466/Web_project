<?php
require_once __DIR__ . '/../includes/functions.php';

$foodId = isset($_GET['food_id']) ? (int) $_GET['food_id'] : 0;

if ($foodId > 0) {
    removeItemFromCart($foodId);
}

header('Location: /uiu-canteen/customer/cart.php');
exit();
