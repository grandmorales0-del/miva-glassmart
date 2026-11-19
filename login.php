<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
if (!empty($_SESSION['user'])) { header('Location: ' . ($user['role'] === 'customer' ? 'order.php' : 'index.php')); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = $pdo->prepare("SELECT id, full_name, email, password_hash, role FROM users WHERE email = ? AND status = 'Active' LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        unset($user['password_hash']);
        $_SESSION['user'] = $user;
        header('Location: index.php'); exit;
    }
    $error = 'Incorrect email or password, or the account is inactive.';
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>GlassMart | Sign in</title><link rel="stylesheet" href="style.css"></head>
<body class="auth-page"><main class="auth-card"><div class="brand auth-brand"><span class="brand-mark">G</span><div><strong>GLASSMART</strong><small>Sales & Stock Management</small></div></div>
<h1>Welcome back</h1><p class="auth-subtitle">Sign in to continue to your workspace.</p>
<?php if ($error): ?><div class="form-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<form method="post" class="auth-form"><label>Email address<input type="email" name="email" required autocomplete="username"></label><label>Password<input type="password" name="password" required autocomplete="current-password"></label><button class="primary" type="submit">Sign in</button></form>
<p class="auth-foot">Accounts are created by an administrator. Public visitors can place orders through <a href="order.php">the online order page</a>.</p></main></body></html>
