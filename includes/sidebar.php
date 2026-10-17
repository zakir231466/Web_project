<?php
/**
 * Sidebar Component
 * Role-based sidebar for dashboard pages
 */

$currentRole = getCurrentUserRole();
$currentPage = basename($_SERVER['PHP_SELF']);
$basePath = $basePath ?? '../';
?>

<?php if ($currentRole === 'admin'): ?>
    <!-- Admin Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">🍽️ Admin Panel</div>
        <ul class="sidebar-menu">
            <li class="sidebar-item">
                <a href="<?php echo $basePath; ?>admin/dashboard.php" class="sidebar-link <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
                    📊 Dashboard
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?php echo $basePath; ?>admin/shops.php" class="sidebar-link <?php echo $currentPage === 'shops.php' ? 'active' : ''; ?>">
                    🏪 Shop Management
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?php echo $basePath; ?>admin/reports.php" class="sidebar-link <?php echo $currentPage === 'reports.php' ? 'active' : ''; ?>">
                    📋 Customer Reports
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?php echo $basePath; ?>admin/actions.php" class="sidebar-link <?php echo $currentPage === 'actions.php' ? 'active' : ''; ?>">
                    ⚡ Enforcement Actions
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?php echo $basePath; ?>admin/analytics.php" class="sidebar-link <?php echo $currentPage === 'analytics.php' ? 'active' : ''; ?>">
                    📈 Analytics
                </a>
            </li>
            <li class="sidebar-divider"></li>
            <li class="sidebar-item">
                <a href="<?php echo $basePath; ?>profile.php" class="sidebar-link <?php echo $currentPage === 'profile.php' ? 'active' : ''; ?>">
                    👤 Profile
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?php echo $basePath; ?>logout.php" class="sidebar-link">
                    🚪 Logout
                </a>
            </li>
        </ul>
    </aside>

<?php elseif ($currentRole === 'shop_owner'): ?>
    <!-- Shop Owner Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">🏪 Shop Owner</div>
        <ul class="sidebar-menu">
            <li class="sidebar-item">
                <a href="<?php echo $basePath; ?>shop-owner/dashboard.php" class="sidebar-link <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
                    📊 Dashboard
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?php echo $basePath; ?>shop-owner/foods.php" class="sidebar-link <?php echo $currentPage === 'foods.php' ? 'active' : ''; ?>">
                    🍕 Food Management
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?php echo $basePath; ?>shop-owner/orders.php" class="sidebar-link <?php echo $currentPage === 'orders.php' ? 'active' : ''; ?>">
                    📦 Orders
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?php echo $basePath; ?>shop-owner/shops.php" class="sidebar-link <?php echo $currentPage === 'shops.php' ? 'active' : ''; ?>">
                    🏬 Shop Profile
                </a>
            </li>
            <li class="sidebar-divider"></li>
            <li class="sidebar-item">
                <a href="<?php echo $basePath; ?>profile.php" class="sidebar-link <?php echo $currentPage === 'profile.php' ? 'active' : ''; ?>">
                    👤 Profile
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?php echo $basePath; ?>logout.php" class="sidebar-link">
                    🚪 Logout
                </a>
            </li>
        </ul>
    </aside>

<?php elseif ($currentRole === 'delivery_person'): ?>
    <!-- Delivery Person Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">🚗 Delivery</div>
        <ul class="sidebar-menu">
            <li class="sidebar-item">
                <a href="<?php echo $basePath; ?>delivery/dashboard.php" class="sidebar-link <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
                    📊 Dashboard
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?php echo $basePath; ?>delivery/deliveries.php" class="sidebar-link <?php echo $currentPage === 'deliveries.php' ? 'active' : ''; ?>">
                    📍 Available Deliveries
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?php echo $basePath; ?>delivery/active-deliveries.php" class="sidebar-link <?php echo $currentPage === 'active-deliveries.php' ? 'active' : ''; ?>">
                    🔴 Active Delivery
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?php echo $basePath; ?>delivery/earnings.php" class="sidebar-link <?php echo $currentPage === 'earnings.php' ? 'active' : ''; ?>">
                    💰 Earnings
                </a>
            </li>
            <li class="sidebar-divider"></li>
            <li class="sidebar-item">
                <a href="<?php echo $basePath; ?>profile.php" class="sidebar-link <?php echo $currentPage === 'profile.php' ? 'active' : ''; ?>">
                    👤 Profile
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?php echo $basePath; ?>logout.php" class="sidebar-link">
                    🚪 Logout
                </a>
            </li>
        </ul>
    </aside>
<?php endif;
