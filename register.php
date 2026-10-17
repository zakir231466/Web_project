<?php
require_once __DIR__ . '/includes/auth.php';
redirectIfAuthenticated();

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = sanitizeText($_POST['full_name'] ?? '');
    $email = strtolower(sanitizeText($_POST['email'] ?? ''));
    $phone = sanitizeText($_POST['phone'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if ($fullName === '' || $email === '' || $password === '' || $confirmPassword === '') {
        $errors[] = 'Full name, email, and password fields are required.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $conn = getDbConnection();

        $checkStmt = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        if (!$checkStmt) {
            $errors[] = 'Database error. Please try again later.';
        } else {
            $checkStmt->bind_param('s', $email);
            $checkStmt->execute();
            $existing = $checkStmt->get_result();
            $checkStmt->close();

            if ($existing->num_rows > 0) {
                $errors[] = 'This email is already registered.';
            } else {
                $roleStmt = $conn->prepare('SELECT id FROM roles WHERE name = "customer" LIMIT 1');
                if (!$roleStmt) {
                    $errors[] = 'Customer role is not configured.';
                } else {
                    $roleStmt->execute();
                    $roleResult = $roleStmt->get_result();
                    $roleRow = $roleResult->fetch_assoc();
                    $roleStmt->close();

                    if (!$roleRow) {
                        $errors[] = 'Customer role is not available.';
                    } else {
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                        $insertStmt = $conn->prepare(
                            'INSERT INTO users (full_name, email, phone, password, role_id, status) VALUES (?, ?, ?, ?, ?, "active")'
                        );

                        if (!$insertStmt) {
                            $errors[] = 'Could not create the account.';
                        } else {
                            $roleId = (int) $roleRow['id'];
                            $insertStmt->bind_param('ssssi', $fullName, $email, $phone, $hashedPassword, $roleId);

                            if ($insertStmt->execute()) {
                                $success = 'Registration successful. You can now log in.';
                                $_POST = [];
                            } else {
                                $errors[] = 'Account creation failed. Please try again later.';
                            }

                            $insertStmt->close();
                        }
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UIU Canteen - Register</title>
    <style>
        :root {
            --primary: #5b8def;
            --primary-soft: #edf4ff;
            --accent: #f7a75b;
            --dark: #1d2433;
            --muted: #6b7280;
            --bg: #f5f7fb;
            --white: #ffffff;
            --border: #e5e7eb;
            --danger: #dc2626;
            --success: #16a34a;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: linear-gradient(135deg, var(--primary-soft), #f8fafc);
            color: var(--dark);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 20px;
        }
        .auth-box {
            width: min(100%, 520px);
            background: var(--white);
            border-radius: 24px;
            box-shadow: 0 20px 45px rgba(91, 141, 239, 0.12);
            border: 1px solid var(--border);
            padding: 36px 28px;
        }
        .brand {
            display: inline-block;
            padding: 8px 14px;
            background: var(--primary-soft);
            color: var(--primary);
            border-radius: 999px;
            font-weight: 700;
            letter-spacing: 0.04em;
            font-size: 0.8rem;
            text-transform: uppercase;
            margin-bottom: 20px;
        }
        h1 {
            margin: 0 0 8px;
            font-size: 2rem;
        }
        .subtext {
            color: var(--muted);
            margin-bottom: 24px;
        }
        .alert {
            padding: 12px 14px;
            border-radius: 12px;
            margin-bottom: 18px;
            font-size: 0.95rem;
        }
        .alert.error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: var(--danger);
        }
        .alert.success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: var(--success);
        }
        form {
            display: grid;
            gap: 18px;
        }
        .two-col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .field {
            display: grid;
            gap: 8px;
        }
        .field label {
            font-weight: 600;
            font-size: 0.94rem;
        }
        .field input {
            width: 100%;
            padding: 13px 14px;
            border-radius: 12px;
            border: 1px solid var(--border);
            font-size: 1rem;
        }
        .field input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(91, 141, 239, 0.12);
        }
        .btn {
            border: none;
            border-radius: 12px;
            padding: 14px 18px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            background: var(--primary);
            color: var(--white);
        }
        .btn:hover {
            background: #4d7fe2;
        }
        .meta {
            margin-top: 18px;
            text-align: center;
            color: var(--muted);
            font-size: 0.94rem;
        }
        .meta a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 700;
        }
        @media (max-width: 560px) {
            .two-col {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="auth-box">
        <div class="brand">UIU Canteen</div>
        <h1>Create account</h1>
        <p class="subtext">Public registration is available only for customers.</p>

        <?php if (!empty($errors)): ?>
            <?php foreach ($errors as $error): ?>
                <div class="alert error"><?php echo e($error); ?></div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if ($success !== ''): ?>
            <div class="alert success"><?php echo e($success); ?></div>
        <?php endif; ?>

        <form method="POST" action="register.php" novalidate>
            <div class="field">
                <label for="full_name">Full Name</label>
                <input id="full_name" name="full_name" type="text" placeholder="Your full name" value="<?php echo e($_POST['full_name'] ?? ''); ?>" required>
            </div>

            <div class="two-col">
                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" placeholder="you@uiu.edu" value="<?php echo e($_POST['email'] ?? ''); ?>" required>
                </div>

                <div class="field">
                    <label for="phone">Phone</label>
                    <input id="phone" name="phone" type="tel" placeholder="01XXXXXXXXX" value="<?php echo e($_POST['phone'] ?? ''); ?>">
                </div>
            </div>

            <div class="two-col">
                <div class="field">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" placeholder="Minimum 6 characters" required>
                </div>

                <div class="field">
                    <label for="confirm_password">Confirm Password</label>
                    <input id="confirm_password" name="confirm_password" type="password" placeholder="Re-enter password" required>
                </div>
            </div>

            <button type="submit" class="btn">Create Customer Account</button>
        </form>

        <div class="meta">
            Already have an account? <a href="login.php">Login</a>
        </div>
    </div>
</body>
</html>
