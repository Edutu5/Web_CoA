<?php
// Pagina de start publica
// Afiseaza: statistici rapide, crize active, cutremure recente din Romania
// Nu necesita autentificare - oricine o poate accesa

session_start();
require_once __DIR__ . '/controllers/EventsController.php';
require_once __DIR__ . '/controllers/SheltersController.php';
require_once __DIR__ . '/controllers/AlertsController.php';
require_once __DIR__ . '/controllers/EarthquakesController.php';

$events = events_get_all('active');
$shelters_data = json_decode(shelters_show_json(), true);
$shelters = $shelters_data['data'] ?? [];
$alerts_data = json_decode(alerts_show_json(), true);
$alerts = $alerts_data['data'] ?? [];
$eq_data = earthquakes_get_for_view('RO', null, 5, 0);

$page_title = 'Acasă';
$current_page = 'home';
require __DIR__ . '/views/partials/header.php';
?>
<main class="container">
  <h1>Crisis Containment Service</h1>
  <p>Platformă de gestionare a situațiilor de urgență. Vizualizați evenimentele active, adăposturile disponibile și alertele emise de autorități.</p>

  <section class="section">
    <div class="stats-row">
      <div class="stat-card"><div class="stat-label">Evenimente active</div><div class="stat-number" style="color:var(--color-danger)"><?= count($events) ?></div></div>
      <div class="stat-card"><div class="stat-label">Adăposturi disponibile</div><div class="stat-number" style="color:var(--color-primary)"><?= count($shelters) ?></div></div>
      <div class="stat-card"><div class="stat-label">Alerte emise</div><div class="stat-number" style="color:var(--color-warning)"><?= count($alerts) ?></div></div>
    </div>
  </section>

  <section class="section">
    <h2>Evenimente Active</h2>
    <?php if (!empty($events)): ?>
      <?php require_once __DIR__ . '/views/EventsView.php'; display_events($events); ?>
    <?php else: ?>
      <p class="empty">Nu există evenimente active în acest moment.</p>
    <?php endif; ?>
  </section>

  <section class="section">
    <h2>Ultimele cutremure în România</h2>
    <?php if (!empty($eq_data['data'])): ?>
      <ul class="event-list">
        <?php foreach ($eq_data['data'] as $eq): ?>
          <li class="event-item" style="border-left:4px solid <?= $eq['magnitude'] > 4 ? '#dc3545' : ($eq['magnitude'] > 3 ? '#ffc107' : '#28a745') ?>">
            <h3>Magnitudine <?= htmlspecialchars($eq['magnitude'], ENT_QUOTES, 'UTF-8') ?></h3>
            <p>Adâncime: <?= htmlspecialchars($eq['depth'] ?? '?', ENT_QUOTES, 'UTF-8') ?> km | Coordonate: <?= htmlspecialchars($eq['latitude'], ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars($eq['longitude'], ENT_QUOTES, 'UTF-8') ?></p>
            <time><?= htmlspecialchars($eq['occurred_at'] ?? '', ENT_QUOTES, 'UTF-8') ?></time>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p class="empty">Nu sunt date despre cutremure recente.</p>
    <?php endif; ?>
  </section>

  <section class="section" style="text-align:center;padding:20px 0">
    <a href="map.php" class="btn btn-primary" style="margin:5px">Vezi harta interactivă</a>
    <a href="alerts.php" class="btn btn-primary" style="margin:5px;background:var(--color-warning)">Vezi alertele CAP</a>
    <a href="shelters.php" class="btn btn-primary" style="margin:5px;background:var(--color-success)">Vezi adăposturile</a>
  </section>
</main>
<?php require __DIR__ . '/views/partials/footer.php'; ?>
