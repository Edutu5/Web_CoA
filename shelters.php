<?php
session_start();
require_once __DIR__ . '/controllers/SheltersController.php';
$shelters_data = json_decode(shelters_show_json(), true);
$shelters = $shelters_data['data'] ?? [];

$page_title = 'Adăposturi';
$current_page = 'shelters';
require __DIR__ . '/views/partials/header.php';
?>
<main class="container">
  <h1>Adăposturi de Urgență</h1>
  <?php require_once __DIR__ . '/views/SheltersView.php'; display_shelters($shelters); ?>
</main>
<?php require __DIR__ . '/views/partials/footer.php'; ?>