<?php
session_start();

$page_title = 'Hartă Interactivă';
$current_page = 'map';
$extra_head = '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />'
    . '<style>#map-container{height:70vh;width:100%;border-radius:8px}#map-legend{background:white;padding:10px;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,.15);margin-top:10px}#map-legend label{display:block;margin:4px 0;cursor:pointer}.leaflet-popup-content{margin:8px 12px}</style>';
$extra_js = '<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>'
        . '<script src="assets/js/map.js?v=' . filemtime(__DIR__ . '/assets/js/map.js') . '"></script>';
require __DIR__ . '/views/partials/header.php';
?>
<main class="container">
  <h1>Hartă Interactivă</h1>
  <?php require_once __DIR__ . '/views/MapView.php'; display_map_page(); ?>
</main>
<?php 
require __DIR__ . '/views/partials/footer.php'; 
?>