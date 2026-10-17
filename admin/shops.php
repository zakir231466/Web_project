<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$conn = getDbConnection();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_shop_owner'])) {
        $shopId = (int) ($_POST['shop_id'] ?? 0);
        $ownerId = (int) ($_POST['owner_id'] ?? 0);

        if ($shopId <= 0 || $ownerId <= 0) {
            $error = 'Please select a valid shop and owner.';
        } else {
            $ownerCheck = $conn->prepare(
                'SELECT u.id FROM users u INNER JOIN roles r ON r.id = u.role_id WHERE u.id = ? AND r.name = "shop_owner" LIMIT 1'
            );
            $ownerCheck->bind_param('i', $ownerId);
            $ownerCheck->execute();
            $ownerExists = $ownerCheck->get_result()->fetch_assoc();
            $ownerCheck->close();

            if (!$ownerExists) {
                $error = 'Selected owner is invalid.';
            } else {
                $updateOwnerStmt = $conn->prepare('UPDATE shops SET owner_id = ? WHERE id = ?');
                $updateOwnerStmt->bind_param('ii', $ownerId, $shopId);
                if ($updateOwnerStmt->execute()) {
                    $message = 'Shop owner updated successfully.';
                } else {
                    $error = 'Unable to update the shop owner.';
                }
                $updateOwnerStmt->close();
            }
        }
    } elseif (isset($_POST['create_shop'])) {
        $name = sanitizeText($_POST['name'] ?? '');
        $ownerId = (int) ($_POST['owner_id'] ?? 0);
        $categoryId = isset($_POST['category_id']) && $_POST['category_id'] !== '' ? (int) $_POST['category_id'] : null;
        $description = sanitizeText($_POST['description'] ?? '');
        $address = sanitizeText($_POST['address'] ?? '');
        $phone = sanitizeText($_POST['phone'] ?? '');
        $status = sanitizeText($_POST['status'] ?? '');

        $allowedStatuses = ['active', 'warning', 'suspended', 'banned'];

        if ($name === '' || $ownerId <= 0) {
            $error = 'Shop name and owner are required.';
        } elseif (!in_array($status, $allowedStatuses, true)) {
            $error = 'Invalid shop status.';
        } else {
            $ownerCheck = $conn->prepare(
                'SELECT u.id FROM users u INNER JOIN roles r ON r.id = u.role_id WHERE u.id = ? AND r.name = "shop_owner" LIMIT 1'
            );
            $ownerCheck->bind_param('i', $ownerId);
            $ownerCheck->execute();
            $ownerExists = $ownerCheck->get_result()->fetch_assoc();
            $ownerCheck->close();

            if (!$ownerExists) {
                $error = 'Selected owner is invalid.';
            } else {
                if ($categoryId !== null) {
                    $stmt = $conn->prepare(
                        'INSERT INTO shops (owner_id, name, description, category_id, address, phone, status, created_at)
                         VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
                    );
                    $stmt->bind_param('ississs', $ownerId, $name, $description, $categoryId, $address, $phone, $status);
                } else {
                    $stmt = $conn->prepare(
                        'INSERT INTO shops (owner_id, name, description, address, phone, status, created_at)
                         VALUES (?, ?, ?, ?, ?, ?, NOW())'
                    );
                    $stmt->bind_param('isssss', $ownerId, $name, $description, $address, $phone, $status);
                }

                if ($stmt->execute()) {
                    $shopId = $stmt->insert_id;
                    $bulkFoods = $_POST['bulk_foods'] ?? [];
                    $foodInsertStmt = $conn->prepare(
                        'INSERT INTO foods (shop_id, category_id, name, description, price, is_available, preparation_time, created_at)
                         VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
                    );

                    foreach ($bulkFoods as $foodData) {
                        $selected = !empty($foodData['selected']);
                        if (!$selected) {
                            continue;
                        }

                        $foodName = sanitizeText($foodData['name'] ?? '');
                        $foodCategoryId = isset($foodData['category_id']) && $foodData['category_id'] !== '' ? (int) $foodData['category_id'] : ($categoryId ?? 0);
                        $foodDescription = sanitizeText($foodData['description'] ?? '');
                        $foodPrice = (float) ($foodData['price'] ?? 0);
                        $foodPrepTime = max(1, (int) ($foodData['preparation_time'] ?? 15));
                        $foodAvailable = isset($foodData['is_available']) ? (int) $foodData['is_available'] : 1;

                        if ($foodName === '' || $foodPrice <= 0) {
                            continue;
                        }

                        $foodInsertStmt->bind_param('iissdii', $shopId, $foodCategoryId, $foodName, $foodDescription, $foodPrice, $foodAvailable, $foodPrepTime);
                        $foodInsertStmt->execute();
                    }

                    $foodInsertStmt->close();
                    $message = 'New shop created successfully.';
                } else {
                    $error = 'Unable to create shop. Please try again.';
                }

                $stmt->close();
            }
        }
    } elseif (isset($_POST['delete_shop'])) {
        $shopId = (int) ($_POST['shop_id'] ?? 0);

        if ($shopId <= 0) {
            $error = 'Invalid shop selected for deletion.';
        } else {
            $deleteStmt = $conn->prepare('DELETE FROM shops WHERE id = ?');
            $deleteStmt->bind_param('i', $shopId);
            if ($deleteStmt->execute()) {
                $message = 'Shop deleted successfully.';
            } else {
                $error = 'Unable to delete shop.';
            }
            $deleteStmt->close();
        }
    }

    if (isset($_POST['shop_id'], $_POST['status']) && !isset($_POST['create_shop']) && !isset($_POST['delete_shop'])) {
        $shopId = (int) $_POST['shop_id'];
        $status = sanitizeText($_POST['status']);
        $allowed = ['active', 'warning', 'suspended', 'banned'];

        if (!in_array($status, $allowed, true)) {
            $error = 'Invalid shop status.';
        } else {
            $stmt = $conn->prepare('UPDATE shops SET status = ? WHERE id = ?');
            $stmt->bind_param('si', $status, $shopId);
            if ($stmt->execute()) {
                $message = 'Shop status updated successfully.';
            } else {
                $error = 'Unable to update shop status.';
            }
            $stmt->close();
        }
    }
}

$shopOwners = $conn->query(
    'SELECT u.id, u.full_name FROM users u INNER JOIN roles r ON r.id = u.role_id WHERE r.name = "shop_owner" ORDER BY u.full_name ASC'
)->fetch_all(MYSQLI_ASSOC);
$categories = getCategories();

$shopsStmt = $conn->prepare(
    'SELECT s.id, s.owner_id, s.name, s.status, s.created_at, u.full_name AS owner_name, c.name AS category_name
     FROM shops s
     INNER JOIN users u ON u.id = s.owner_id
     LEFT JOIN categories c ON c.id = s.category_id
     ORDER BY s.created_at DESC'
);
$shopsStmt->execute();
$shopList = $shopsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$shopsStmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Shops | UIU Canteen</title>
    <style>
        :root { --primary: #5b8def; --primary-soft: #edf4ff; --dark: #1d2433; --muted: #6b7280; --bg: #f5f7fb; --white: #fff; --border: #e5e7eb; --danger: #dc2626; --success: #16a34a; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; background: var(--bg); color: var(--dark); }
        .topbar { display: flex; justify-content: space-between; align-items: center; background: var(--white); border-bottom: 1px solid var(--border); padding: 18px 32px; }
        .brand { font-weight: 800; color: var(--danger); }
        .top-actions { display: flex; gap: 14px; align-items: center; }
        .pill { background: #fee2e2; color: var(--danger); border-radius: 999px; padding: 8px 14px; font-size: 0.8rem; font-weight: 700; }
        .logout { color: var(--dark); text-decoration: none; font-weight: 700; }
        .layout { display: grid; grid-template-columns: 240px 1fr; min-height: calc(100vh - 76px); }
        .sidebar { background: var(--white); border-right: 1px solid var(--border); padding: 28px 18px; }
        .nav { display: grid; gap: 10px; }
        .nav a { text-decoration: none; color: var(--dark); font-weight: 600; border-radius: 12px; padding: 12px 14px; display: block; border: 1px solid transparent; }
        .nav a.active { background: #fee2e2; color: var(--danger); border-color: #fecaca; }
        .content { padding: 28px; }
        .page-header { margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; gap: 16px; }
        .page-header h1 { margin: 0; font-size: 2rem; }
        .panel { background: var(--white); border: 1px solid var(--border); border-radius: 18px; padding: 24px; margin-bottom: 24px; }
        .panel h2 { margin-top: 0; margin-bottom: 18px; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; }
        .field { display: grid; gap: 8px; }
        .field label { font-weight: 700; color: var(--dark); }
        .field input, .field select, .field textarea { width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 10px; font: inherit; }
        .field textarea { min-height: 90px; resize: vertical; }
        .btn { display: inline-block; padding: 9px 14px; background: var(--primary); color: var(--white); border: none; border-radius: 10px; font-weight: 700; cursor: pointer; text-decoration: none; }
        .btn-block { width: 100%; }
        .btn-light { background: #e5e7eb; color: #111827; }
        .btn-danger { background: #dc2626; color: var(--white); }
        .alert { padding: 12px 14px; border-radius: 10px; margin-bottom: 18px; font-weight: 600; }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .new-shop-trigger { background: #e5e7eb; color: #111827; border: none; border-radius: 12px; padding: 12px 18px; font-weight: 700; cursor: pointer; }
        .modal-backdrop {
            position: fixed; inset: 0; background: rgba(15, 23, 42, 0.45); display: none; align-items: center; justify-content: center; padding: 20px; z-index: 100;
        }
        .modal-backdrop.show { display: flex; }
        .modal-box {
            background: var(--white); width: min(100%, 740px); border-radius: 18px; border: 1px solid var(--border); box-shadow: 0 16px 42px rgba(15, 23, 42, 0.18); overflow: hidden;
        }
        .modal-header, .modal-footer { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 18px 20px; }
        .modal-header { border-bottom: 1px solid #edf2f7; }
        .modal-header h3 { margin: 0; font-size: clamp(1.8rem, 2vw, 2.4rem); }
        .modal-footer { border-top: 1px solid #edf2f7; justify-content: flex-end; }
        .modal-body { padding: 14px 20px 18px; }
        .modal-form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; }
        .modal-field { display: grid; gap: 8px; }
        .modal-field label { font-weight: 700; }
        .modal-field input, .modal-field select, .modal-field textarea {
            width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 10px; font: inherit;
        }
        .modal-field textarea { min-height: 90px; resize: vertical; }
        .modal-section { padding: 0 20px 18px; }
        .section-title { margin: 0 0 12px; font-size: 1.2rem; font-weight: 800; }
        .food-option {
            display: grid; grid-template-columns: 64px 1fr auto auto; gap: 12px; align-items: center;
            padding: 14px 0; border-bottom: 1px solid #eef2f7;
        }
        .food-option:last-child { border-bottom: none; }
        .food-avatar {
            width: 56px; height: 56px; border-radius: 12px; background: #dfeafe; color: #2d5ec2; font-weight: 700;
            font-size: 1.7rem; display: flex; align-items: center; justify-content: center;
        }
        .food-copy { min-width: 0; }
        .food-name { font-weight: 700; margin-bottom: 6px; }
        .food-meta { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; color: var(--muted); font-size: 0.9rem; }
        .food-option input[type="checkbox"] { width: 18px; height: 18px; }
        .food-option input[type="number"], .food-option input[type="text"], .food-option select {
            width: 100%; padding: 9px 10px; border: 1px solid var(--border); border-radius: 10px; font: inherit;
        }
        .food-option .price-box { width: 110px; }
        .food-option .qty-box { width: 82px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; border-bottom: 1px solid var(--border); text-align: left; }
        th { background: var(--bg); }
        .status { display: inline-block; padding: 5px 8px; border-radius: 8px; font-size: 0.8rem; font-weight: 700; }
        .status.active { background: #d1fae5; color: #065f46; }
        .status.warning { background: #fef3c7; color: #92400e; }
        .status.suspended { background: #fee2e2; color: #991b1b; }
        .status.banned { background: #e5e7eb; color: #374151; }
        form.inline { display: flex; align-items: center; gap: 10px; }
        select { padding: 9px 12px; border: 1px solid var(--border); border-radius: 10px; min-width: 140px; }
        @media (max-width: 820px){ .layout{grid-template-columns:1fr;} .sidebar{border-right:none;border-bottom:1px solid var(--border);} .page-header{display:block;} }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="brand">⚙️ UIU CANTEEN - ADMIN</div>
        <div class="top-actions">
            <span class="pill">ADMIN</span>
            <a class="logout" href="/uiu-canteen/logout.php">Logout</a>
        </div>
    </header>
    <div class="layout">
        <aside class="sidebar">
            <nav class="nav">
                <a href="dashboard.php">Dashboard</a>
                <a class="active" href="shops.php">Manage Shops</a>
                <a href="reports.php">Reports</a>
                <a href="actions.php">Admin Actions</a>
                <a href="../profile.php">Profile</a>
            </nav>
        </aside>
        <main class="content">
            <div class="page-header">
                <h1>Manage Shops</h1>
                <button type="button" class="new-shop-trigger" data-open-modal="new-shop-modal">Create New Shop</button>
            </div>
            <?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

            <div class="panel">
                <table>
                    <thead>
                        <tr>
                            <th>Shop</th>
                            <th>Owner</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($shopList as $shop): ?>
                            <tr>
                                <td><a href="shop-details.php?shop_id=<?= (int) $shop['id'] ?>"><?= e($shop['name']) ?></a></td>
                                <td><?= e($shop['owner_name']) ?></td>
                                <td><?= e($shop['category_name'] ?? 'General') ?></td>
                                <td><span class="status <?= strtolower($shop['status']) ?>"><?= e($shop['status']) ?></span></td>
                                <td><?= date('M d, Y', strtotime($shop['created_at'])) ?></td>
                                <td>
                                    <form class="inline" method="POST" onsubmit="return confirm('Are you sure you want to delete this shop?');">
                                        <input type="hidden" name="shop_id" value="<?= (int) $shop['id'] ?>">
                                        <select name="status">
                                            <option value="active" <?= $shop['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                            <option value="warning" <?= $shop['status'] === 'warning' ? 'selected' : '' ?>>Warning</option>
                                            <option value="suspended" <?= $shop['status'] === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                                            <option value="banned" <?= $shop['status'] === 'banned' ? 'selected' : '' ?>>Banned</option>
                                        </select>
                                        <button class="btn" type="submit">Save</button>
                                        <button class="btn btn-danger" type="submit" name="delete_shop" value="1">Delete</button>
                                    </form>
                                    <form class="inline" method="POST" style="margin-top: 8px;">
                                        <input type="hidden" name="update_shop_owner" value="1">
                                        <input type="hidden" name="shop_id" value="<?= (int) $shop['id'] ?>">
                                        <select name="owner_id" required>
                                            <option value="">Change owner</option>
                                            <?php foreach ($shopOwners as $owner): ?>
                                                <option value="<?= (int) $owner['id'] ?>" <?= (int) $owner['id'] === (int) $shop['owner_id'] ? 'selected' : '' ?>><?= e($owner['full_name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="btn btn-light" type="submit">Owner</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <div class="modal-backdrop" id="new-shop-modal">
        <div class="modal-box">
            <div class="modal-header">
                <h3>Create New Shop</h3>
                <button type="button" class="new-shop-trigger btn-light close-modal">Close</button>
            </div>
            <form method="POST">
                <input type="hidden" name="create_shop" value="1">
                <div class="modal-body">
                    <div class="modal-form-grid">
                        <div class="modal-field">
                            <label for="shop_name">Shop Name</label>
                            <input id="shop_name" name="name" type="text" placeholder="Example: UIU Canteen" required>
                        </div>
                        <div class="modal-field">
                            <label for="owner_id">Owner</label>
                            <select id="owner_id" name="owner_id" required>
                                <option value="">Select owner</option>
                                <?php foreach ($shopOwners as $owner): ?>
                                    <option value="<?= (int) $owner['id'] ?>"><?= e($owner['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="modal-field">
                            <label for="category_id">Category</label>
                            <select id="category_id" name="category_id">
                                <option value="">General</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= (int) $category['id'] ?>"><?= e($category['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="modal-field">
                            <label for="shop_phone">Phone</label>
                            <input id="shop_phone" name="phone" type="text" placeholder="01XXXXXXXXX">
                        </div>
                        <div class="modal-field">
                            <label for="shop_status">Status</label>
                            <select id="shop_status" name="status" required>
                                <option value="active">Active</option>
                                <option value="warning">Warning</option>
                                <option value="suspended">Suspended</option>
                                <option value="banned">Banned</option>
                            </select>
                        </div>
                        <div class="modal-field" style="grid-column: 1 / -1;">
                            <label for="shop_address">Address</label>
                            <input id="shop_address" name="address" type="text" placeholder="Shop address">
                        </div>
                        <div class="modal-field" style="grid-column: 1 / -1;">
                            <label for="shop_description">Description</label>
                            <textarea id="shop_description" name="description" placeholder="Short description about the shop"></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-section">
                    <h4 class="section-title">Add Menu Items</h4>
                    <div class="food-option">
                        <div class="food-avatar">B</div>
                        <div class="food-copy">
                            <div class="food-name">
                                <input type="text" name="bulk_foods[0][name]" placeholder="Food name" required>
                            </div>
                            <div class="food-meta">
                                <input class="price-box" type="number" name="bulk_foods[0][price]" step="0.01" min="0" placeholder="৳ Price" required>
                                <select name="bulk_foods[0][category_id]">
                                    <option value="">Category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= (int) $category['id'] ?>"><?= e($category['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <select name="bulk_foods[0][is_available]">
                                    <option value="1">Available</option>
                                    <option value="0">Unavailable</option>
                                </select>
                            </div>
                        </div>
                        <label><input type="checkbox" name="bulk_foods[0][selected]" value="1" checked> Select</label>
                        <input class="qty-box" type="number" name="bulk_foods[0][quantity]" value="1" min="1">
                    </div>
                    <div id="new-shop-extra-rows"></div>
                </div>

                <div class="modal-footer">
                    <button type="button" id="add-more-shop-item" class="btn btn-light">+ Add more</button>
                    <button type="button" class="btn btn-light close-modal">Cancel</button>
                    <button type="submit" class="btn">Create Shop</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('click', function (event) {
            const trigger = event.target.closest('[data-open-modal]');
            if (trigger) {
                const modal = document.getElementById(trigger.getAttribute('data-open-modal'));
                if (modal) {
                    modal.classList.add('show');
                }
            }

            if (event.target.matches('.close-modal')) {
                const modal = event.target.closest('.modal-backdrop');
                if (modal) {
                    modal.classList.remove('show');
                }
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                document.querySelectorAll('.modal-backdrop.show').forEach(function (modal) {
                    modal.classList.remove('show');
                });
            }
        });

        const shopExtraRows = document.getElementById('new-shop-extra-rows');
        const addMoreShopItemButton = document.getElementById('add-more-shop-item');
        let shopFoodRowIndex = 1;

        addMoreShopItemButton.addEventListener('click', function () {
            const wrapper = document.createElement('div');
            wrapper.className = 'food-option';
            const letter = String.fromCharCode(65 + (shopFoodRowIndex % 26));
            wrapper.innerHTML = `
                <div class="food-avatar">${letter}</div>
                <div class="food-copy">
                    <div class="food-name">
                        <input type="text" name="bulk_foods[${shopFoodRowIndex}][name]" placeholder="Food name" required>
                    </div>
                    <div class="food-meta">
                        <input class="price-box" type="number" name="bulk_foods[${shopFoodRowIndex}][price]" step="0.01" min="0" placeholder="৳ Price" required>
                        <select name="bulk_foods[${shopFoodRowIndex}][category_id]">
                            <option value="">Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= (int) $category['id'] ?>"><?= e($category['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="bulk_foods[${shopFoodRowIndex}][is_available]">
                            <option value="1">Available</option>
                            <option value="0">Unavailable</option>
                        </select>
                    </div>
                </div>
                <label><input type="checkbox" name="bulk_foods[${shopFoodRowIndex}][selected]" value="1" checked> Select</label>
                <input class="qty-box" type="number" name="bulk_foods[${shopFoodRowIndex}][quantity]" value="1" min="1">
            `;
            shopExtraRows.appendChild(wrapper);
            shopFoodRowIndex++;
        });
    </script>
</body>
</html>
