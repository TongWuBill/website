<?php
require_once __DIR__ . '/../../src/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (is_logged_in()) {
    header('Location: /admin/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($username === 'admin' && $password === 'vv856xhfWT') {
        $_SESSION['admin_logged_in'] = true;
        header('Location: /admin/dashboard.php');
        exit;
    } else {
        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: sans-serif; background: #f4f4f4; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .login-box { background: #fff; padding: 2rem; border: 1px solid #ddd; width: 320px; }
        h1 { font-size: 1.2rem; margin-bottom: 1.5rem; }
        label { display: block; font-size: 0.85rem; margin-bottom: 0.25rem; }
        input { width: 100%; padding: 0.5rem; margin-bottom: 1rem; border: 1px solid #ccc; font-size: 1rem; }
        button { width: 100%; padding: 0.6rem; background: #222; color: #fff; border: none; font-size: 1rem; cursor: pointer; }
        button:hover { background: #444; }
        .error { color: #c00; font-size: 0.85rem; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="login-box">
        <h1>Admin Login</h1>
        <?php if ($error): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <form method="POST">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" autofocus>
            <label for="password">Password</label>
            <input type="password" id="password" name="password">
            <button type="submit">Log in</button>
        </form>
    </div>
</body>
</html>
