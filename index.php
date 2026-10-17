<?php
require_once __DIR__ . '/includes/auth.php';

if (isset($_SESSION['user_id'])) {
    header('Location: /uiu-canteen/dashboard.php');
    exit();
}

header('Location: /uiu-canteen/login.php');
exit();
