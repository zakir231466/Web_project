<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$conn = getDbConnection();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_shop_owner'])) {
    $fullName = sanitizeText($_POST['full_name'] ?? '');
    $phone = sanitizeText($_POST['phone'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    if ($password === '') {
        $password = 'password123';
    }
    $status = sanitizeText($_POST['status'] ?? 'active');

    $baseEmail = strtolower(preg_replace('/[^a-z0-9]+/i', '', $fullName));
    if ($baseEmail === '') {
        $baseEmail = 'shopowner';
    }

    $email = $baseEmail . '@uiu-canteen.local';
    $emailCounter = 2;
    while (true) {
        $emailCheckStmt = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $emailCheckStmt->bind_param('s', $email);
        $emailCheckStmt->execute();
        $existingUser = $emailCheckStmt->get_result()->fetch_assoc();
        $emailCheckStmt->close();

        if (!$existingUser) {
            break;
        }

        $email = $baseEmail . (string) $emailCounter . '@uiu-canteen.local';
        $emailCounter++;
    }

    if ($fullName === '' || $password === '') {
        $error = 'Full name and password are required.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif (!in_array($status, ['active', 'inactive'], true)) {
        $error = 'Invalid account status.';
    } else {
        $roleStmt = $conn->prepare('SELECT id FROM roles WHERE name = "shop_owner" LIMIT 1');
        $roleStmt->execute();
        $roleRow = $roleStmt->get_result()->fetch_assoc();
        $roleStmt->close();

        if (!$roleRow) {
            $error = 'Shop owner role is not configured.';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $insertStmt = $conn->prepare(
                'INSERT INTO users (full_name, email, phone, password, role_id, status) VALUES (?, ?, ?, ?, ?, ?)'
            );
            $roleId = (int) $roleRow['id'];
            $insertStmt->bind_param('ssssis', $fullName, $email, $phone, $hashedPassword, $roleId, $status);

            if ($insertStmt->execute()) {
                $message = 'Shop owner account created successfully. Login email: ' . $email . ' | Password: password123';
            } else {
                $error = 'Unable to create shop owner account.';
            }

            $insertStmt->close();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_delivery_person'])) {
    $fullName = sanitizeText($_POST['full_name'] ?? '');
    $email = strtolower(sanitizeText($_POST['email'] ?? ''));
    $phone = sanitizeText($_POST['phone'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $status = sanitizeText($_POST['status'] ?? 'active');

    if ($fullName === '' || $email === '' || $password === '') {
        $error = 'Full name, email, and password are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif (!in_array($status, ['active', 'inactive'], true)) {
        $error = 'Invalid account status.';
    } else {
        $checkStmt = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $checkStmt->bind_param('s', $email);
        $checkStmt->execute();
        $existingUser = $checkStmt->get_result()->fetch_assoc();
        $checkStmt->close();

        if ($existingUser) {
            $error = 'This delivery email is already registered.';
        } else {
            $roleStmt = $conn->prepare('SELECT id FROM roles WHERE name = "delivery_person" LIMIT 1');
            $roleStmt->execute();
            $roleRow = $roleStmt->get_result()->fetch_assoc();
            $roleStmt->close();

            if (!$roleRow) {
                $error = 'Delivery person role is not configured.';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $insertStmt = $conn->prepare(
                    'INSERT INTO users (full_name, email, phone, password, role_id, status) VALUES (?, ?, ?, ?, ?, ?)'
                );
                $roleId = (int) $roleRow['id'];
                $insertStmt->bind_param('ssssis', $fullName, $email, $phone, $hashedPassword, $roleId, $status);

                if ($insertStmt->execute()) {
                    $message = 'Delivery person account created successfully.';
                } else {
                    $error = 'Unable to create delivery person account.';
                }

                $insertStmt->close();
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['shop_id'], $_POST['action_type'])) {
    $shopId = (int) $_POST['shop_id'];
    $actionType = sanitizeText($_POST['action_type']);
    $fineAmount = isset($_POST['fine_amount']) ? (float) $_POST['fine_amount'] : null;
    $note = sanitizeText($_POST['admin_note'] ?? '');
    $allowed = ['warning', 'fine', 'suspend', 'ban', 'activate', 'remove'];

    if (!in_array($actionType, $allowed, true)) {
        $error = 'Invalid admin action.';
    } else {
        $stmt = $conn->prepare('INSERT INTO admin_actions (admin_id, shop_id, action_type, fine_amount, admin_note) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param('iids', $_SESSION['user_id'], $shopId, $actionType, $fineAmount, $note);
        if ($stmt->execute()) {
            $message = 'Action recorded successfully.';
            if ($actionType === 'suspend' || $actionType === 'ban') {
                $updateShop = $conn->prepare('UPDATE shops SET status = "suspended" WHERE id = ?');
                $updateShop->bind_param('i', $shopId);
                $updateShop->execute();
                $updateShop->close();
            }
            if ($actionType === 'activate') {
                $updateShop = $conn->prepare('UPDATE shops SET status = "active" WHERE id = ?');
                $updateShop->bind_param('i', $shopId);
                $updateShop->execute();
                $updateShop->close();
            }
        } else {
            $error = 'Unable to save admin action.';
        }
        $stmt->close();
    }
}

$actionsStmt = $conn->prepare(
    'SELECT aa.id, aa.action_type, aa.fine_amount, aa.admin_note, aa.action_date, s.name AS shop_name, u.full_name AS admin_name
     FROM admin_actions aa
     INNER JOIN shops s ON s.id = aa.shop_id
     INNER JOIN users u ON u.id = aa.admin_id
     ORDER BY aa.action_date DESC LIMIT 20'
);
$actionsStmt->execute();
$actions = $actionsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$actionsStmt->close();

$shopListStmt = $conn->prepare('SELECT id, name FROM shops ORDER BY name ASC');
$shopListStmt->execute();
$shops = $shopListStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$shopListStmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Actions | UIU Canteen</title>
    <style>
        :root { --primary: #5b8def; --primary-soft: #edf4ff; --dark: #1d2433; --muted: #6b7280; --bg: #f5f7fb; --white: #fff; --border: #e5e7eb; --danger: #dc2626; }
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
        .page-header { margin-bottom: 20px; }
        .page-header h1 { margin: 0; font-size: 2rem; }
        .panel { background: var(--white); border: 1px solid var(--border); border-radius: 18px; padding: 24px; margin-bottom: 24px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; border-bottom: 1px solid var(--border); text-align: left; }
        th { background: var(--bg); }
        .alert { padding: 12px 14px; border-radius: 10px; margin-bottom: 18px; font-weight: 600; }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        form { display: grid; gap: 16px; }
        label { display: block; font-weight: 700; margin-bottom: 8px; }
        select, input, textarea { width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 10px; font-family: inherit; }
        textarea { min-height: 110px; resize: vertical; }
        .btn { display: inline-block; background: var(--primary); color: var(--white); border: none; border-radius: 10px; padding: 12px 18px; cursor: pointer; font-weight: 700; }
        @media (max-width: 820px){ .layout{grid-template-columns:1fr;} .sidebar{border-right:none;border-bottom:1px solid var(--border);} }
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
                <a href="shops.php">Manage Shops</a>
                <a href="reports.php">Reports</a>
                <a class="active" href="actions.php">Admin Actions</a>
                <a href="../profile.php">Profile</a>
            </nav>
        </aside>
        <main class="content">
            <div class="page-header">
                <h1>Admin Actions</h1>
            </div>
            <?php if (isset($message)): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
            <?php if (isset($error)): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

            <div class="panel">
                <h2>Create Shop Owner</h2>
                <form method="POST">
                    <input type="hidden" name="create_shop_owner" value="1">
                    <div>
                        <label for="shop_owner_full_name">Full Name</label>
                        <input id="shop_owner_full_name" name="full_name" type="text" placeholder="e.g. Rahim Ahmed" required>
                    </div>
                    <div>
                        <label for="shop_owner_email">Login Email</label>
                        <input id="shop_owner_email" name="email" type="email" placeholder="Auto-generated from name" readonly>
                    </div>
                    <div>
                        <label for="shop_owner_phone">Phone Number</label>
                        <input id="shop_owner_phone" name="phone" type="text" placeholder="01XXXXXXXXX">
                    </div>
                    <div>
                        <label for="shop_owner_password">Password</label>
                        <input id="shop_owner_password" name="password" type="password" value="password123" placeholder="Minimum 6 characters" required>
                    </div>
                    <div>
                        <label for="shop_owner_status">Status</label>
                        <select id="shop_owner_status" name="status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <button class="btn" type="submit">Create Shop Owner</button>
                </form>
            </div>

            <div class="panel">
                <h2>Create Delivery Person</h2>
                <form method="POST">
                    <input type="hidden" name="create_delivery_person" value="1">
                    <div>
                        <label for="delivery_full_name">Full Name</label>
                        <input id="delivery_full_name" name="full_name" type="text" placeholder="e.g. Nabil Hossain" required>
                    </div>
                    <div>
                        <label for="delivery_email">Email Address</label>
                        <input id="delivery_email" name="email" type="email" placeholder="delivery@uiu-canteen.local" required>
                    </div>
                    <div>
                        <label for="delivery_phone">Phone Number</label>
                        <input id="delivery_phone" name="phone" type="text" placeholder="01XXXXXXXXX">
                    </div>
                    <div>
                        <label for="delivery_password">Password</label>
                        <input id="delivery_password" name="password" type="password" placeholder="Minimum 6 characters" required>
                    </div>
                    <div>
                        <label for="delivery_status">Status</label>
                        <select id="delivery_status" name="status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <button class="btn" type="submit">Create Delivery Person</button>
                </form>
            </div>

            <div class="panel">
                <form method="POST">
                    <div>
                        <label for="shop_id">Shop</label>
                        <select id="shop_id" name="shop_id" required>
                            <option value="">Select shop</option>
                            <?php foreach ($shops as $shop): ?>
                                <option value="<?= (int) $shop['id'] ?>"><?= e($shop['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="action_type">Action Type</label>
                        <select id="action_type" name="action_type" required>
                            <option value="warning">Warning</option>
                            <option value="fine">Fine</option>
                            <option value="suspend">Suspend</option>
                            <option value="ban">Ban</option>
                            <option value="activate">Activate</option>
                            <option value="remove">Remove</option>
                        </select>
                    </div>
                    <div>
                        <label for="fine_amount">Fine Amount</label>
                        <input id="fine_amount" name="fine_amount" type="number" step="0.01" min="0" placeholder="0.00">
                    </div>
                    <div>
                        <label for="admin_note">Note</label>
                        <textarea id="admin_note" name="admin_note" placeholder="Write admin note..."></textarea>
                    </div>
                    <button class="btn" type="submit">Save Action</button>
                </form>
            </div>

            <div class="panel">
                <h2>Recent Actions</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Shop</th>
                            <th>Action</th>
                            <th>Fine</th>
                            <th>Admin</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($actions as $action): ?>
                            <tr>
                                <td><?= e($action['shop_name']) ?></td>
                                <td><?= e($action['action_type']) ?></td>
                                <td><?= $action['fine_amount'] ? '৳' . number_format((float) $action['fine_amount'], 2) : '—' ?></td>
                                <td><?= e($action['admin_name']) ?></td>
                                <td><?= date('M d, Y', strtotime($action['action_date'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    <script>
        const shopOwnerNameInput = document.getElementById('shop_owner_full_name');
        const shopOwnerEmailInput = document.getElementById('shop_owner_email');

        if (shopOwnerNameInput && shopOwnerEmailInput) {
            const setShopOwnerEmail = () => {
                const name = shopOwnerNameInput.value.trim();
                const base = name.toLowerCase().replace(/[^a-z0-9]/g, '');
                shopOwnerEmailInput.value = base ? base + '@uiu-canteen.local' : '';
            };

            shopOwnerNameInput.addEventListener('input', setShopOwnerEmail);
            setShopOwnerEmail();
        }
    </script>
</body>
</html>
