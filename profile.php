<?php
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$user = getCurrentUser();
$conn = getDbConnection();

$createdAt = $user['created_at'] ?? null;
$updatedAt = $user['updated_at'] ?? null;

$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = sanitizeText($_POST['full_name'] ?? '');
    $phone = sanitizeText($_POST['phone'] ?? '');
    $email = sanitizeText($_POST['email'] ?? '');
    
    if (empty($fullName) || empty($phone) || empty($email)) {
        $errorMessage = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = 'Invalid email format.';
    } else {
        $userId = currentUserId();
        $updateStmt = $conn->prepare('UPDATE users SET full_name = ?, phone = ?, email = ? WHERE id = ?');
        $updateStmt->bind_param('sssi', $fullName, $phone, $email, $userId);
        
        if ($updateStmt->execute()) {
            $_SESSION['user_name'] = $fullName;
            $successMessage = 'Profile updated successfully!';
        } else {
            $errorMessage = 'Failed to update profile. Please try again.';
        }
        $updateStmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | UIU Canteen</title>
    <style>
        :root {
            --primary: #5b8def;
            --primary-soft: #edf4ff;
            --dark: #1d2433;
            --muted: #6b7280;
            --bg: #f5f7fb;
            --white: #ffffff;
            --border: #e5e7eb;
            --success: #16a34a;
            --danger: #dc2626;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: var(--bg);
            color: var(--dark);
        }
        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: var(--white);
            border-bottom: 1px solid var(--border);
            padding: 18px 32px;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .brand {
            font-weight: 800;
            color: var(--primary);
        }
        .top-actions {
            display: flex;
            gap: 14px;
            align-items: center;
        }
        .pill {
            background: var(--primary-soft);
            color: var(--primary);
            border-radius: 999px;
            padding: 8px 14px;
            font-size: 0.8rem;
            font-weight: 700;
        }
        .logout {
            color: var(--dark);
            text-decoration: none;
            font-weight: 700;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            padding: 0 20px;
        }
        .panel {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 24px;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.03);
        }
        .panel h1 {
            margin: 0 0 24px;
            font-size: 1.8rem;
        }
        .form-group {
            display: grid;
            gap: 6px;
            margin-bottom: 18px;
        }
        .form-group label {
            font-weight: 700;
            font-size: 0.9rem;
            color: var(--dark);
        }
        .form-group input {
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 1rem;
            font-family: inherit;
        }
        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-soft);
        }
        .form-group input[disabled] {
            background: var(--bg);
            color: var(--muted);
        }
        .form-actions {
            display: grid;
            gap: 10px;
            margin-top: 24px;
        }
        .btn {
            padding: 12px 18px;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.2s;
        }
        .btn-primary {
            background: var(--primary);
            color: var(--white);
        }
        .btn-primary:hover {
            opacity: 0.9;
        }
        .alert {
            padding: 14px 16px;
            border-radius: 10px;
            margin-bottom: 18px;
            font-size: 0.95rem;
        }
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-bottom: 24px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border);
        }
        .info-item .label {
            color: var(--muted);
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .info-item .value {
            font-weight: 700;
            margin-top: 4px;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="brand">UIU CANTEEN</div>
        <div class="top-actions">
            <span class="pill"><?= e(strtoupper($user['role_name'])) ?></span>
            <a class="logout" href="/uiu-canteen/logout.php">Logout</a>
        </div>
    </header>

    <div class="container">
        <a href="javascript:history.back()" class="back-link">← Back</a>
        
        <div class="panel">
            <h1>My Profile</h1>

            <?php if ($successMessage): ?>
                <div class="alert alert-success"><?= e($successMessage) ?></div>
            <?php endif; ?>

            <?php if ($errorMessage): ?>
                <div class="alert alert-danger"><?= e($errorMessage) ?></div>
            <?php endif; ?>

            <div class="info-grid">
                <div class="info-item">
                    <div class="label">Account Status</div>
                    <div class="value"><?= e(ucfirst($user['status'])) ?></div>
                </div>
                <div class="info-item">
                    <div class="label">Role</div>
                    <div class="value"><?= e(ucfirst(str_replace('_', ' ', $user['role_name']))) ?></div>
                </div>
                <div class="info-item">
                    <div class="label">Member Since</div>
                    <div class="value"><?= $createdAt ? date('M d, Y', strtotime($createdAt)) : 'N/A' ?></div>
                </div>
                <div class="info-item">
                    <div class="label">Last Updated</div>
                    <div class="value"><?= $updatedAt ? date('M d, Y', strtotime($updatedAt)) : 'N/A' ?></div>
                </div>
            </div>

            <form method="POST">
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" value="<?= e($user['full_name']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" value="<?= e($user['email']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" value="<?= e($user['phone']) ?>" required>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>

            <div style="margin-top: 28px; padding-top: 20px; border-top: 1px solid var(--border);">
                <p style="color: var(--muted); font-size: 0.9rem; margin: 0;">
                    <strong>Note:</strong> Your password and role cannot be changed from this screen. Contact an administrator if you need assistance.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
