<?php
if (session_status() === PHP_SESSION_NONE) session_start();
?><!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($page_title ?? 'CoA', ENT_QUOTES, 'UTF-8') ?> - CoA</title>
<link rel="stylesheet" href="assets/css/main.css">
<?= $extra_head ?? '' ?>
</head>
<body<?php if (isset($body_class)) echo ' class="' . htmlspecialchars($body_class, ENT_QUOTES, 'UTF-8') . '"'; ?>>
<nav class="navbar">
  <div class="nav-brand"><a href="index.php">CoA</a></div>
  <button class="nav-toggle" id="nav-toggle" aria-label="Toggle menu">&#9776;</button>
<div class="nav-links" id="nav-links">
    <a href="index.php"<?= ($current_page ?? '') === 'home' ? ' class="active"' : '' ?>>Acasa</a>
    <a href="map.php"<?= ($current_page ?? '') === 'map' ? ' class="active"' : '' ?>>Harta</a>
    <a href="shelters.php"<?= ($current_page ?? '') === 'shelters' ? ' class="active"' : '' ?>>Adaposturi</a>
    <a href="alerts.php"<?= ($current_page ?? '') === 'alerts' ? ' class="active"' : '' ?>>Alerte</a>
    <?php if (isset($_SESSION['user_id'])): ?>
      <?php if (in_array($_SESSION['role'] ?? '', ['authority', 'admin'])): ?>
        <a href="dashboard.php"<?= ($current_page ?? '') === 'dashboard' ? ' class="active"' : '' ?>>Dashboard</a>
      <?php endif; ?>
      <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
        <a href="admin.php"<?= ($current_page ?? '') === 'admin' ? ' class="active"' : '' ?>>Admin</a>
      <?php endif; ?>
      <span class="role-badge"><?= htmlspecialchars($_SESSION['role'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
      <a href="logout.php">Deconectare</a>
    <?php else: ?>
      <a href="login.php">Autentificare</a>
      <a href="signup.php">Înregistrare</a>
    <?php endif; ?>
  </div>
</nav>