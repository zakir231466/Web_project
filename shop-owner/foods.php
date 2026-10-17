<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireShopOwner();

$user = getCurrentUser();
$conn = getDbConnection();
$categories = getCategories();
$ownerShops = getOwnerShops($user['id']);
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'toggle_availability') {
        $foodId = (int) ($_POST['food_id'] ?? 0);
        $shopId = (int) ($_POST['shop_id'] ?? 0);
        $newStatus = isset($_POST['is_available']) ? (int) $_POST['is_available'] : 0;

        $shop = getOwnerShopById($shopId, $user['id']);
        if (!$shop) {
            $error = 'Invalid shop selection.';
        } else {
            $stmt = $conn->prepare('UPDATE foods SET is_available = ? WHERE id = ? AND shop_id = ?');
            $stmt->bind_param('iii', $newStatus, $foodId, $shopId);
            if ($stmt->execute()) {
                $message = 'Food availability updated.';
            } else {
                $error = 'Unable to update food status.';
            }
            $stmt->close();
        }
    } elseif (isset($_POST['add_food'])) {
        $shopId = (int) ($_POST['shop_id'] ?? 0);
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $name = sanitizeText($_POST['name'] ?? '');
        $description = sanitizeText($_POST['description'] ?? '');
        $price = (float) ($_POST['price'] ?? 0);
        $preparationTime = (int) ($_POST['preparation_time'] ?? 0);
        $isAvailable = isset($_POST['is_available']) ? 1 : 0;

        if (!$shopId || empty($name) || $price <= 0) {
            $error = 'Please provide a valid shop, food name, and price.';
        } else {
            $shop = getOwnerShopById($shopId, $user['id']);
            if (!$shop) {
                $error = 'You are not allowed to add food to this shop.';
            } else {
                $stmt = $conn->prepare(
                    'INSERT INTO foods (shop_id, category_id, name, description, price, is_available, preparation_time, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
                );
                $stmt->bind_param('iissdii', $shopId, $categoryId, $name, $description, $price, $isAvailable, $preparationTime);
                if ($stmt->execute()) {
                    $message = 'Food item added successfully.';
                } else {
                    $error = 'Failed to add food item.';
                }
                $stmt->close();
            }
        }
    } elseif (isset($_POST['bulk_add_foods'])) {
        $shopId = (int) ($_POST['shop_id'] ?? 0);
        $shop = getOwnerShopById($shopId, $user['id']);

        if (!$shop) {
            $error = 'You are not allowed to add food to this shop.';
        } else {
            $bulkFoods = $_POST['bulk_foods'] ?? [];
            $createdCount = 0;
            $insertStmt = $conn->prepare(
                'INSERT INTO foods (shop_id, category_id, name, description, price, is_available, preparation_time, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
            );

            foreach ($bulkFoods as $foodData) {
                $selected = !empty($foodData['selected']);
                if (!$selected) {
                    continue;
                }

                $name = sanitizeText($foodData['name'] ?? '');
                $categoryId = (int) ($foodData['category_id'] ?? 0);
                $description = sanitizeText($foodData['description'] ?? '');
                $price = (float) ($foodData['price'] ?? 0);
                $preparationTime = max(1, (int) ($foodData['preparation_time'] ?? 15));
                $isAvailable = isset($foodData['is_available']) ? 1 : 0;

                if ($name === '' || $price <= 0) {
                    continue;
                }

                $insertStmt->bind_param('iissdii', $shopId, $categoryId, $name, $description, $price, $isAvailable, $preparationTime);
                if ($insertStmt->execute()) {
                    $createdCount++;
                }
            }

            $insertStmt->close();

            if ($createdCount > 0) {
                $message = $createdCount . ' food item(s) added successfully.';
            } else {
                $error = 'Please select at least one valid food item before submitting.';
            }
        }
    }
}

$selectedShopId = isset($_GET['shop_id']) ? (int) $_GET['shop_id'] : ($ownerShops[0]['id'] ?? 0);
$foods = $selectedShopId ? getOwnerShopFoods($selectedShopId, $user['id']) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Foods | UIU Canteen</title>
    <style>
        :root { --primary: #5b8def; --primary-soft: #edf4ff; --dark: #1d2433; --muted: #6b7280; --bg: #f5f7fb; --white: #fff; --border: #e5e7eb; --success: #16a34a; --danger: #dc2626; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; background: var(--bg); color: var(--dark); }
        .topbar { display: flex; justify-content: space-between; align-items: center; background: var(--white); border-bottom: 1px solid var(--border); padding: 18px 32px; }
        .brand { font-weight: 800; color: var(--primary); }
        .top-actions { display: flex; gap: 14px; align-items: center; }
        .pill { background: var(--primary-soft); color: var(--primary); border-radius: 999px; padding: 8px 14px; font-weight: 700; font-size: 0.8rem; }
        .logout { text-decoration: none; color: var(--dark); font-weight: 700; }
        .layout { display: grid; grid-template-columns: 240px 1fr; min-height: calc(100vh - 76px); }
        .sidebar { background: var(--white); border-right: 1px solid var(--border); padding: 28px 18px; }
        .nav { display: grid; gap: 10px; }
        .nav a { display: block; padding: 12px 14px; border-radius: 12px; text-decoration: none; color: var(--dark); font-weight: 600; }
        .nav a.active { background: var(--primary-soft); color: var(--primary); }
        .content { padding: 28px; }
        .page-header { margin-bottom: 20px; }
        .page-header h1 { margin: 0; font-size: 2rem; }
        .grid { display: grid; grid-template-columns: 360px 1fr; gap: 20px; }
        .panel { background: var(--white); border: 1px solid var(--border); border-radius: 18px; padding: 24px; }
        .field { display: grid; gap: 6px; margin-bottom: 14px; }
        .field label { font-weight: 700; }
        .field input, .field select, .field textarea { width: 100%; padding: 11px 12px; border: 1px solid var(--border); border-radius: 10px; font: inherit; }
        .field textarea { min-height: 90px; resize: vertical; }
        .btn { display: inline-block; padding: 11px 18px; border: none; border-radius: 10px; background: var(--primary); color: var(--white); cursor: pointer; font-weight: 700; text-decoration: none; }
        .btn-secondary { background: var(--success); }
        .btn-light { background: #e5e7eb; color: var(--dark); }
        .btn-inline { padding: 10px 18px; background: #e5e7eb; color: #1f2937; border-radius: 12px; border: none; font-weight: 700; cursor: pointer; }
        .status { display: inline-block; padding: 4px 8px; border-radius: 6px; font-size: 0.8rem; font-weight: 700; }
        .status.available { background: #d1fae5; color: #065f46; }
        .status.unavailable { background: #fee2e2; color: #991b1b; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; border-bottom: 1px solid var(--border); text-align: left; }
        th { background: var(--bg); }
        .quick-add-wrap { display: flex; justify-content: flex-end; margin-bottom: 14px; }
        .bulk-form { display: grid; gap: 12px; }
        .modal-backdrop {
            position: fixed; inset: 0; background: rgba(15, 23, 42, 0.44); display: none; align-items: center; justify-content: center; padding: 20px; z-index: 100;
        }
        .modal-backdrop.show { display: flex; }
        .modal-box {
            background: #fff; width: min(100%, 860px); border-radius: 20px; border: 1px solid var(--border); box-shadow: 0 20px 40px rgba(15, 23, 42, 0.18); overflow: hidden;
        }
        .modal-header, .modal-footer { padding: 18px 20px; display: flex; justify-content: space-between; align-items: center; gap: 12px; }
        .modal-header { border-bottom: 1px solid #eef2f7; }
        .modal-header h3 { margin: 0; font-size: clamp(1.7rem, 2vw, 2.2rem); font-weight: 800; }
        .modal-footer { border-top: 1px solid #eef2f7; justify-content: flex-end; }
        .modal-body { padding: 4px 20px 18px; max-height: 65vh; overflow-y: auto; }
        .food-option {
            display: grid; grid-template-columns: 66px 1fr auto 72px; gap: 12px; align-items: center;
            padding: 14px 0; border-bottom: 1px solid #eef2f7;
        }
        .food-option:last-child { border-bottom: none; }
        .food-avatar {
            width: 60px; height: 60px; border-radius: 12px; background: #dfeafe; color: #2d5ec2; font-weight: 700;
            font-size: 1.75rem; display: flex; align-items: center; justify-content: center;
        }
        .food-copy { min-width: 0; }
        .food-name { font-size: 1.05rem; font-weight: 700; margin: 0 0 4px; }
        .food-meta { color: #6b7280; font-size: 0.9rem; }
        .food-option label {
            display: flex; align-items: center; gap: 8px; font-size: 0.92rem; color: #111827; white-space: nowrap;
        }
        .food-option input[type="checkbox"] { width: 18px; height: 18px; }
        .food-option input[type="number"] {
            width: 72px; padding: 9px 8px; border: 1px solid var(--border); border-radius: 10px; font: inherit; text-align: center;
        }
        .food-option input, .food-option select { font: inherit; }
        .alert { padding: 12px 14px; border-radius: 10px; margin-bottom: 18px; font-weight: 600; }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        @media (max-width: 980px){ .layout {grid-template-columns:1fr;} .grid {grid-template-columns:1fr;} .sidebar{border-right:none;border-bottom:1px solid var(--border);} .food-option { grid-template-columns: 1fr; } .food-avatar { width: 52px; height: 52px; } }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="brand">UIU CANTEEN - SHOP OWNER</div>
        <div class="top-actions">
            <span class="pill"><?= e(strtoupper($user['role_name'])) ?></span>
            <a class="logout" href="/uiu-canteen/logout.php">Logout</a>
        </div>
    </header>

    <div class="layout">
        <aside class="sidebar">
            <nav class="nav">
                <a href="dashboard.php">Dashboard</a>
                <a href="shops.php">My Shops</a>
                <a class="active" href="foods.php">Manage Foods</a>
                <a href="orders.php">Orders</a>
                <a href="../profile.php">Profile</a>
            </nav>
        </aside>

        <main class="content">
            <div class="page-header">
                <h1>Manage Foods</h1>
            </div>

            <?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

            <div class="grid">
                <div class="panel">
                    <h2 style="margin-top:0;">Add Food Item</h2>
                    <form method="POST">
                        <div class="field">
                            <label for="shop_id">Shop</label>
                            <select id="shop_id" name="shop_id" required>
                                <?php foreach ($ownerShops as $shop): ?>
                                    <option value="<?= (int) $shop['id'] ?>" <?= ($selectedShopId === (int) $shop['id']) ? 'selected' : '' ?>><?= e($shop['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="field">
                            <label for="category_id">Category</label>
                            <select id="category_id" name="category_id" required>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= (int) $category['id'] ?>"><?= e($category['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="field">
                            <label for="name">Food Name</label>
                            <input type="text" id="name" name="name" required>
                        </div>

                        <div class="field">
                            <label for="description">Description</label>
                            <textarea id="description" name="description"></textarea>
                        </div>

                        <div class="field">
                            <label for="price">Price (৳)</label>
                            <input type="number" id="price" name="price" step="0.01" min="0" required>
                        </div>

                        <div class="field">
                            <label for="preparation_time">Preparation Time (minutes)</label>
                            <input type="number" id="preparation_time" name="preparation_time" min="1" value="15">
                        </div>

                        <div class="field">
                            <label for="is_available">Available for Ordering</label>
                            <select id="is_available" name="is_available">
                                <option value="1">Available</option>
                                <option value="0">Unavailable</option>
                            </select>
                        </div>

                        <button class="btn btn-secondary" type="submit" name="add_food" value="1">Add Food</button>
                    </form>
                </div>

                <div class="panel">
                    <div class="quick-add-wrap">
                        <button type="button" class="btn btn-light" data-open-modal="bulk-food-modal">Quick Add Items</button>
                    </div>
                    <h2 style="margin-top:0;">Current Menu</h2>
                    <?php if (empty($foods)): ?>
                        <p style="color: var(--muted); margin:0;">No food items found for the selected shop.</p>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Prep</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($foods as $food): ?>
                                    <tr>
                                        <td><?= e($food['name']) ?></td>
                                        <td><?= e($food['category_name'] ?? 'Uncategorized') ?></td>
                                        <td>৳<?= number_format((float) $food['price'], 2) ?></td>
                                        <td><?= (int) ($food['preparation_time'] ?? 0) ?> min</td>
                                        <td><span class="status <?= (int) $food['is_available'] ? 'available' : 'unavailable' ?>"><?= (int) $food['is_available'] ? 'Available' : 'Unavailable' ?></span></td>
                                        <td>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="toggle_availability">
                                                <input type="hidden" name="shop_id" value="<?= (int) $selectedShopId ?>">
                                                <input type="hidden" name="food_id" value="<?= (int) $food['id'] ?>">
                                                <input type="hidden" name="is_available" value="<?= (int) !$food['is_available'] ?>">
                                                <button type="submit" class="btn" style="padding: 8px 12px; font-size: 0.85rem;">
                                                    <?= (int) $food['is_available'] ? 'Mark Unavailable' : 'Mark Available' ?>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <div class="modal-backdrop" id="bulk-food-modal">
        <div class="modal-box">
            <div class="modal-header">
                <h3>Add items from <?= e($ownerShops[0]['name'] ?? 'Your Shop') ?></h3>
                <button type="button" class="btn-inline close-modal">Close</button>
            </div>
            <form method="POST" class="bulk-form">
                <input type="hidden" name="bulk_add_foods" value="1">
                <input type="hidden" name="shop_id" value="<?= (int) $selectedShopId ?>">
                <div class="modal-body">
                    <div class="food-option">
                        <div class="food-avatar">B</div>
                        <div class="food-copy">
                            <div class="food-name">Boiled Egg</div>
                            <div class="food-meta">৳250.00 · Available</div>
                        </div>
                        <label><input type="checkbox" name="bulk_foods[0][selected]" value="1" checked> Select</label>
                        <input type="number" name="bulk_foods[0][quantity]" value="1" min="1">
                    </div>
                    <div id="bulk-food-extra-rows"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="add-more-food-row" class="btn btn-light">+ Add more</button>
                    <button type="submit" class="btn">Add Selected Items</button>
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

        const extraRows = document.getElementById('bulk-food-extra-rows');
        const addMoreButton = document.getElementById('add-more-food-row');
        let bulkRowIndex = 1;

        addMoreButton.addEventListener('click', function () {
            const wrapper = document.createElement('div');
            wrapper.className = 'food-option';
            const letter = String.fromCharCode(65 + (bulkRowIndex % 26));
            wrapper.innerHTML = `
                <div class="food-avatar">${letter}</div>
                <div class="food-copy">
                    <div class="food-name"><input type="text" name="bulk_foods[${bulkRowIndex}][name]" placeholder="Food Name" required style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:10px;"></div>
                    <div class="food-meta">
                        <input type="number" name="bulk_foods[${bulkRowIndex}][price]" step="0.01" min="0" placeholder="৳ Price" required style="width:120px;padding:8px 10px;border:1px solid #d1d5db;border-radius:10px;">
                        <select name="bulk_foods[${bulkRowIndex}][is_available]" style="width:110px;padding:8px 10px;border:1px solid #d1d5db;border-radius:10px;margin-left:8px;">
                            <option value="1">Available</option>
                            <option value="0">Unavailable</option>
                        </select>
                    </div>
                </div>
                <label><input type="checkbox" name="bulk_foods[${bulkRowIndex}][selected]" value="1" checked> Select</label>
                <input type="number" name="bulk_foods[${bulkRowIndex}][quantity]" value="1" min="1">
            `;
            extraRows.appendChild(wrapper);
            bulkRowIndex++;
        });
    </script>
</body>
</html>
