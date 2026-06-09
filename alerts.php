<?php
// alerts.php - Pagina publica cu alertele CAP emise de autoritati
// Carduri cu badge-uri Alert/Cancel, mesaj "Pericolul a trecut" pt anulate
// Filtru dropdown pe tip alerta
// alerts.php - Pagina publica cu alertele CAP (filtrabila pe tip)
session_start();
$page_title = 'Alerte de urgenta';
$current_page = 'alerts';
require __DIR__ . '/views/partials/header.php';
?>
    <main class="container">
        <h1>Alerte de Urgenta</h1>
        <p class="subtitle">Alertele sunt generate automat de autoritati folosind standardul
            <a href="http://docs.oasis-open.org/emergency/cap/v1.2/CAP-v1.2-os.html" target="_blank">
                Common Alerting Protocol v1.2 (OASIS)</a>.</p>
        <div class="filter-bar">
            <label for="filter-type">Filtreaza:</label>
            <select id="filter-type">
                <option value="">Toate alertele</option>
                <option value="Alert">Alerte active</option>
                <option value="Update">Actualizari</option>
                <option value="Cancel">Anulate</option>
            </select>
        </div>

        <div id="alerts-list">
            <p class="loading">Se incarca alertele...</p>
        </div>
    </main>
<?php
$extra_js = '<script src="assets/js/alerts-public.js?v=' . time() . '"></script>';
require __DIR__ . '/views/partials/footer.php';
?>
