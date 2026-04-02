<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/experiment-model.php';

require_login();

const ADMIN_PASSWORD = 'vv856xhfWT';

$id  = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$exp = $id ? get_experiment_by_id($id) : false;

if (!$exp) {
    http_response_code(404);
    echo '<p>Experiment not found. <a href="/admin/experiments.php">Back</a></p>';
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    if ($password !== ADMIN_PASSWORD) {
        $error = 'Incorrect password. Delete cancelled.';
    } else {
        delete_experiment($id);
        header('Location: /admin/dashboard.php?tab=experiments');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delete Experiment — Admin</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: sans-serif; background: #f4f4f4; color: #222; padding: 2rem; }
        .box { background: #fff; border: 1px solid #ddd; padding: 1.5rem; max-width: 480px; }
        h1 { font-size: 1.1rem; margin-bottom: 0.75rem; }
        p { font-size: 0.9rem; color: #555; margin-bottom: 1.25rem; }
        strong { color: #222; }
        label { display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.3rem; }
        input[type="password"] { width: 100%; padding: 0.45rem 0.6rem; border: 1px solid #ccc; font-size: 0.9rem; margin-bottom: 1rem; }
        input[type="password"]:focus { outline: 2px solid #c00; border-color: transparent; }
        .error { font-size: 0.85rem; color: #c00; margin-bottom: 1rem; }
        .actions { display: flex; gap: 0.75rem; align-items: center; }
        button { padding: 0.5rem 1.2rem; background: #c00; color: #fff; border: none; font-size: 0.9rem; cursor: pointer; }
        button:hover { background: #a00; }
        a { padding: 0.5rem 1rem; font-size: 0.9rem; text-decoration: none; border: 1px solid #222; color: #222; }
        a:hover { background: #222; color: #fff; }
    </style>
</head>
<body>
    <div class="box">
        <h1>Delete Experiment</h1>
        <p>You are about to permanently delete <strong><?= htmlspecialchars($exp['title']) ?></strong> and all its media. This cannot be undone.</p>

        <?php if ($error): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="POST">
            <label for="password">Enter your password to confirm</label>
            <input type="password" id="password" name="password" autofocus>
            <div class="actions">
                <button type="submit">Delete</button>
                <a href="/admin/dashboard.php?tab=experiments">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>
