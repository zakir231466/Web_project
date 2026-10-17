<?php
require_once __DIR__ . '/includes/auth.php';
redirectIfAuthenticated();

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginInput = trim((string) ($_POST['login'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($loginInput === '' || $password === '') {
        $errors[] = 'Email/Username and password are required.';
    } else {
        $conn = getDbConnection();
        $searchEmail = strtolower($loginInput);
        $searchPattern = $searchEmail;

        if (strpos($searchEmail, '@') === false) {
            $searchPattern = $searchEmail . '@%';
        }

        $stmt = $conn->prepare(
            'SELECT u.id, u.full_name, u.password, u.status, r.name AS role_name
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             WHERE (LOWER(u.email) = ? OR LOWER(u.email) LIKE ?)
             LIMIT 1'
        );

        if (!$stmt) {
            $errors[] = 'System error. Please try again later.';
        } else {
            $stmt->bind_param('ss', $searchEmail, $searchPattern);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($user && password_verify($password, $user['password'])) {
                if (($user['status'] ?? 'inactive') !== 'active') {
                    $errors[] = 'Your account is not active. Please contact an administrator.';
                } else {
                    setAuthenticatedSession((int) $user['id'], $user['full_name'], $user['role_name']);
                    $redirectPath = roleDashboardPath($user['role_name']);
                    header('Location: ' . $redirectPath);
                    exit();
                }
            } else {
                $errors[] = 'Invalid email/username or password.';
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
    <title>UIU Canteen - Login</title>
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
        .auth-shell {
            width: min(100%, 1100px);
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            background: var(--white);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 20px 45px rgba(91, 141, 239, 0.12);
            border: 1px solid var(--border);
        }
        .hero {
            background: linear-gradient(160deg, var(--primary), #7aa9ff);
            color: var(--white);
            padding: 60px 48px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .hero h1 {
            font-size: 2.3rem;
            margin: 0 0 18px;
        }
        .hero p {
            margin: 0 0 32px;
            line-height: 1.6;
            opacity: 0.95;
        }
        .feature-list {
            display: grid;
            gap: 12px;
        }
        .feature-item {
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.22);
            border-radius: 14px;
            padding: 12px 14px;
            font-size: 0.96rem;
        }
        .form-panel {
            padding: 48px 36px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .form-card {
            width: 100%;
            max-width: 420px;
        }
        .brand {
            display: inline-block;
            padding: 8px 14px;
            background: var(--primary-soft);
            color: var(--primary);
            border-radius: 999px;
            font-weight: 700;
            margin-bottom: 18px;
            letter-spacing: 0.04em;
            font-size: 0.8rem;
            text-transform: uppercase;
        }
        h2 {
            margin: 0 0 8px;
            font-size: 2rem;
        }
        .subtext {
            color: var(--muted);
            margin-bottom: 28px;
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
            padding: 14px 16px;
            border-radius: 12px;
            border: 1px solid var(--border);
            font-size: 1rem;
            background: #fff;
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
            transition: 0.2s ease;
        }
        .btn-primary {
            background: var(--primary);
            color: var(--white);
        }
        .btn-primary:hover {
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
        @media (max-width: 820px) {
            .auth-shell {
                grid-template-columns: 1fr;
            }
            .hero {
                padding: 40px 28px;
            }
            .form-panel {
                padding: 32px 20px 40px;
            }
        }
    </style>
</head>
<body>
    <div class="auth-shell">
        <section class="hero">
            <div class="brand">UIU Canteen</div>
            <h1>Smart food ordering for the university community.</h1>
            <p>Order meals, track delivery, and manage campus dining across shops and teams with a clean, secure platform.</p>
            <div class="feature-list">
                <div class="feature-item">• Browse campus shops and menus</div>
                <div class="feature-item">• Place orders and track delivery</div>
                <div class="feature-item">• Secure login for students, faculty, and staff</div>
            </div>
        </section>

        <section class="form-panel">
            <div class="form-card">
                <div class="brand">Access Portal</div>
                <h2>Welcome back</h2>
                <p class="subtext">Sign in to your account</p>

                <?php if (!empty($errors)): ?>
                    <?php foreach ($errors as $error): ?>
                        <div class="alert error"><?php echo e($error); ?></div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php if ($success !== ''): ?>
                    <div class="alert success"><?php echo e($success); ?></div>
                <?php endif; ?>

                <form method="POST" action="login.php" novalidate>
                    <div class="field">
                        <label for="login">Email / Username</label>
                        <input id="login" name="login" type="text" placeholder="Enter your email or username" value="<?php echo e($_POST['login'] ?? ''); ?>" required>
                    </div>

                    <div class="field">
                        <label for="password">Password</label>
                        <input id="password" name="password" type="password" placeholder="Enter your password" required>
                    </div>

                    <button type="submit" class="btn btn-primary">Login</button>
                </form>

                <div class="meta">
                    New here? <a href="register.php">Create customer account</a>
                </div>
            </div>
        </section>
    </div>
</body>
</html>
