<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$pageTitle = 'Analytics';
$basePath = '../';
?>
<?php require __DIR__ . '/../includes/header.php'; ?>
<?php require __DIR__ . '/../includes/navbar.php'; ?>
<?php require __DIR__ . '/../includes/sidebar.php'; ?>

<main>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h3>Platform Analytics</h3>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <span class="alert-icon">ℹ</span>
                    <div class="alert-content">
                        <p>Analytics dashboard is ready for future metrics and charts.</p>
                    </div>
                </div>
                <div class="card-grid">
                    <div class="card">
                        <div class="card-body">
                            <div class="text-muted">Total Orders</div>
                            <div style="font-size: 2rem; font-weight: 700; color: var(--primary);">0</div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <div class="text-muted">Active Shops</div>
                            <div style="font-size: 2rem; font-weight: 700; color: var(--primary);">0</div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <div class="text-muted">Delivery Success</div>
                            <div style="font-size: 2rem; font-weight: 700; color: var(--primary);">0%</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>
