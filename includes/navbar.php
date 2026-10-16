<?php
// Navbar component - renders appropriate navigation based on user role
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';

$currentUser = getCurrentUser();
$currentRole = getCurrentUserRole();

if (!isset($basePath)) {
    if (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false || strpos($_SERVER['REQUEST_URI'], '/shop-owner/') !== false || strpos($_SERVER['REQUEST_URI'], '/delivery/') !== false || strpos($_SERVER['REQUEST_URI'], '/customer/') !== false) {
        $basePath = '../';
    } else {
        $basePath = './';
    }
}
?>

<?php if ($currentRole === 'customer'): ?>
    <!-- Customer Mobile Bottom Navigation -->
    <nav class="bottom-nav">
        <a href="<?php echo $basePath; ?>customer/shops.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'shops.php' ? 'active' : ''; ?>">
            <span class="icon">🏪</span>
            <span>Shops</span>
        </a>
        <a href="<?php echo $basePath; ?>customer/cart.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'cart.php' ? 'active' : ''; ?>">
            <span class="icon">🛒</span>
            <span>Cart</span>
        </a>
        <a href="<?php echo $basePath; ?>customer/orders.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'orders.php' ? 'active' : ''; ?>">
            <span class="icon">📦</span>
            <span>Orders</span>
        </a>
        <a href="<?php echo $basePath; ?>customer/reports.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'active' : ''; ?>">
            <span class="icon">⚠️</span>
            <span>Reports</span>
        </a>
        <a href="<?php echo $basePath; ?>profile.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : ''; ?>">
            <span class="icon">👤</span>
            <span>Profile</span>
        </a>
    </nav>

<?php else: ?>
    <!-- Top Navbar for Dashboard Users -->
    <nav class="top-navbar">
        <div class="navbar-content">
            <div class="navbar-brand">
                <a href="#">🍽️ UIU Canteen</a>
            </div>
            <div class="navbar-user">
                <span class="user-name"><?php echo e($currentUser['full_name']); ?></span>
                <a href="<?php echo $basePath; ?>logout.php" class="btn-logout">Logout</a>
            </div>
        </div>
    </nav>
<?php endif; ?>
