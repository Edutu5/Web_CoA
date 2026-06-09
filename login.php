<?php
session_set_cookie_params(['httponly' => true, 'samesite' => 'Strict']);
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/controllers/AuthController.php';
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = auth_process_login($username, $password);
    if ($role === false) {
        $error = true;
    } else {
        if ($role === 'admin') { header('Location: admin.php'); exit; }
        if ($role === 'authority') { header('Location: dashboard.php'); exit; }
        header('Location: index.php'); exit;
    }
}

?><!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Autentificare - CoA</title>
<link rel="stylesheet" href="assets/css/main.css">
</head>
<body class="login-page">
<nav class="navbar">
  <div class="nav-brand"><a href="index.php">CoA</a></div>
  <div class="nav-links"><a href="index.php">Acas&#259;</a></div>
</nav>
<main class="container login-container">
  <div class="login-card">
    <h2>Autentificare</h2>
    <?php if (isset($error)): ?><p class="error-msg">Date incorecte.</p><?php endif; ?>
    <form method="POST">
      <div class="form-group"><label for="username">Utilizator</label><input type="text" id="username" name="username" required autocomplete="username"></div>
      <div class="form-group"><label for="password">Parol&#259;</label><input type="password" id="password" name="password" required autocomplete="current-password"></div>
      <button type="submit" class="btn btn-primary btn-block">Autentificare</button>
    </form>
    <p class="login-hint">Conturi demo: <code>admin</code>/<code>admin123</code>, <code>authority</code>/<code>admin123</code>, <code>user</code>/<code>admin123</code></p>
    <p class="login-hint">Nu ai cont? <a href="signup.php">Înregistrează-te</a></p>
  </div>
</main>
</body>
</html>
