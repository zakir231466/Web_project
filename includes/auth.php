<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function sanitizeText($value)
{
    return trim(strip_tags((string) $value));
}

function roleDashboardPath($role)
{
    switch ($role) {
        case 'customer':
            return '/uiu-canteen/customer/dashboard.php';
        case 'shop_owner':
            return '/uiu-canteen/shop-owner/dashboard.php';
        case 'admin':
            return '/uiu-canteen/admin/dashboard.php';
        case 'delivery_person':
            return '/uiu-canteen/delivery/dashboard.php';
        default:
            return '/uiu-canteen/login.php';
    }
}

function currentUserId()
{
    $userId = $_SESSION['user_id'] ?? null;
    return $userId !== null ? (int) $userId : null;
}

function getCurrentUser()
{
    $userId = currentUserId();

    if ($userId === null) {
        return null;
    }

    $conn = getDbConnection();
    $stmt = $conn->prepare(
        'SELECT u.id, u.full_name, u.email, u.phone, u.role_id, r.name AS role_name, u.status, u.created_at, u.updated_at
         FROM users u
         INNER JOIN roles r ON r.id = u.role_id
         WHERE u.id = ? LIMIT 1'
    );

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    return $user ?: null;
}

function getCurrentUserRole()
{
    $user = getCurrentUser();
    return $user['role_name'] ?? ($_SESSION['role_name'] ?? null);
}

function isCustomer()
{
    return getCurrentUserRole() === 'customer';
}

function isShopOwner()
{
    return getCurrentUserRole() === 'shop_owner';
}

function isAdmin()
{
    return getCurrentUserRole() === 'admin';
}

function isDeliveryPerson()
{
    return getCurrentUserRole() === 'delivery_person';
}

function requireLogin()
{
    if (!currentUserId()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '/uiu-canteen/dashboard.php';
        header('Location: /uiu-canteen/login.php');
        exit();
    }
}

function requireRole($role)
{
    requireLogin();

    $user = getCurrentUser();
    if (!$user || ($user['role_name'] ?? null) !== $role) {
        $fallbackRole = $user['role_name'] ?? ($_SESSION['role_name'] ?? null);
        header('Location: ' . roleDashboardPath($fallbackRole ?: 'customer'));
        exit();
    }
}

function requireAnyRole(array $roles)
{
    requireLogin();

    $user = getCurrentUser();
    if (!$user || !in_array(($user['role_name'] ?? null), $roles, true)) {
        $fallbackRole = $user['role_name'] ?? ($_SESSION['role_name'] ?? null);
        header('Location: ' . roleDashboardPath($fallbackRole ?: 'customer'));
        exit();
    }
}

function requireCustomer()
{
    requireRole('customer');
}

function requireShopOwner()
{
    requireRole('shop_owner');
}

function requireAdmin()
{
    requireRole('admin');
}

function requireDeliveryPerson()
{
    requireRole('delivery_person');
}

function redirectIfAuthenticated()
{
    if (currentUserId()) {
        $role = getCurrentUserRole();
        header('Location: ' . roleDashboardPath($role ?: 'customer'));
        exit();
    }
}

function setAuthenticatedSession($userId, $fullName, $roleName)
{
    $_SESSION['user_id'] = (int) $userId;
    $_SESSION['user_name'] = $fullName;
    $_SESSION['role_name'] = $roleName;
    $_SESSION['last_activity'] = time();
}
