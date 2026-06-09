<?php
// admin.php - Panoul de administrare complet (doar admin)
// 6 tab-uri: Adaposturi, Crize, Utilizatori, Export, Import, Alerte
// Toate CRUD se fac prin Ajax fara reload; js incarcat din admin.js
session_start();
require_once __DIR__ . '/controllers/AuthController.php';
auth_require('admin');

$page_title = 'Panou Administrare';
$current_page = 'admin';
require __DIR__ . '/views/partials/header.php';
?>
<main class="container">
  <h1>Panou Administrare</h1>
  <?php require_once __DIR__ . '/views/AdminView.php'; display_admin_panel([]); ?>
</main>
<div id="modal-overlay" class="modal-overlay hidden" onclick="if(event.target===this)closeModal()">
  <div class="modal-box">
    <div id="modal-content"></div>
  </div>
</div>
<?php
$extra_js = '<script src="assets/js/admin.js?v=' . time() . '"></script>';
require __DIR__ . '/views/partials/footer.php';
?>
