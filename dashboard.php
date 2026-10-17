<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$user = getCurrentUser();
if (!$user) {
    header('Location: /uiu-canteen/login.php');
    exit();
}

// Route to role-specific dashboards
$roleName = $user['role_name'];
if ($roleName === 'customer') {
    header('Location: /uiu-canteen/customer/dashboard.php');
    exit();
} elseif ($roleName === 'shop_owner') {
    header('Location: /uiu-canteen/shop-owner/dashboard.php');
    exit();
} elseif ($roleName === 'admin') {
    header('Location: /uiu-canteen/admin/dashboard.php');
    exit();
} elseif ($roleName === 'delivery_person') {
    header('Location: /uiu-canteen/delivery/dashboard.php');
    exit();
}

// Fallback (should not reach here)
header('Location: /uiu-canteen/login.php');
exit();
