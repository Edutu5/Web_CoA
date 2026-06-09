<?php
// Date cutremure din Kaggle cu filtrare si paginare
//   datele sunt read-only, importate o singura data
session_start();
require_once __DIR__ . '/../controllers/EarthquakesController.php';
header('Content-Type: application/json');
echo earthquakes_show_json(
    $_GET['country'] ?? null,
    isset($_GET['min_magnitude']) ? (float)$_GET['min_magnitude'] : null,
    (int)($_GET['limit'] ?? 100),
    (int)($_GET['offset'] ?? 0)
);
