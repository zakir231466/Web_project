<?php
require_once __DIR__ . '/../includes/auth.php';
requireDeliveryPerson();

$pageTitle = 'Active Delivery';
$basePath = '../';
?>
<?php require __DIR__ . '/../includes/header.php'; ?>
<?php require __DIR__ . '/../includes/navbar.php'; ?>
<?php require __DIR__ . '/../includes/sidebar.php'; ?>

<main>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h3>Active Delivery</h3>
            </div>
            <div class="card-body">
                <div class="empty-state">
                    <div class="empty-state-icon">🚚</div>
                    <h3>No active delivery</h3>
                    <p>Take a delivery assignment from the available deliveries page to start.</p>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>
