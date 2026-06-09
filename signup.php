<?php
// Inregistrare utilizator nou (rol: user)
// Validari: username min 3 chars, parola min 6 chars, confirmare parola
// Parola se hash-uieste cu bcrypt inainte de salvare

session_set_cookie_params(['httponly' => true, 'samesite' => 'Strict']);
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/models/UsersModel.php';

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    $errors = [];
    if (strlen($username) < 3) $errors[] = 'Numele de utilizator trebuie sa aiba minim 3 caractere.';
    if (strlen($password) < 6) $errors[] = 'Parola trebuie sa aiba minim 6 caractere.';
    if ($password !== $confirm) $errors[] = 'Parolele nu coincid.';

    if (empty($errors)) {
        $existing = users_find_by_username($username);
        if ($existing) {
            $errors[] = 'Acest nume de utilizator este deja folosit.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            if (users_create($username, $hash, 'user')) {
                $success = true;
            } else {
                $errors[] = 'Eroare la crearea contului.';
            }
        }
    }
}
?><!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Înregistrare - CoA</title>
<link rel="stylesheet" href="assets/css/main.css">
</head>
<body class="login-page">
<nav class="navbar">
  <div class="nav-brand"><a href="index.php">CoA</a></div>
  <div class="nav-links"><a href="index.php">Acas&#259;</a></div>
</nav>
<main class="container login-container">
  <div class="login-card">
    <h2>Înregistrare</h2>
    <?php if (isset($success)): ?>
      <p class="success-msg">Cont creat cu succes! <a href="login.php">Autentifică-te aici</a>.</p>
    <?php else: ?>
      <?php if (!empty($errors)): ?>
        <div class="error-msg">
          <?php foreach ($errors as $err): ?>
            <p><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></p>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      <form method="POST">
        <div class="form-group">
          <label for="username">Utilizator</label>
          <input type="text" id="username" name="username" required minlength="3" autocomplete="username" value="<?= htmlspecialchars($username ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="form-group">
          <label for="password">Parol&#259;</label>
          <input type="password" id="password" name="password" required minlength="6" autocomplete="new-password">
        </div>
        <div class="form-group">
          <label for="confirm">Confirm&#259; parola</label>
          <input type="password" id="confirm" name="confirm" required minlength="6" autocomplete="new-password">
        </div>
        <button type="submit" class="btn btn-primary btn-block">Creaz&#259; cont</button>
      </form>
      <p class="login-hint">Ai deja cont? <a href="login.php">Autentific&#259;-te</a></p>
    <?php endif; ?>
  </div>
</main>
</body>
</html>
