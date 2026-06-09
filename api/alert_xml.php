<?php
// Se deschide intr-un tab nou cand dai click pe butonul "XML"
session_start();
require_once __DIR__ . '/../controllers/AlertsController.php';
$id = (int)($_GET['id'] ?? 0);
if (!$id) { http_response_code(400); echo 'id required'; exit; }
header('Content-Type: application/xml');
$xml = alerts_show_xml($id);
if ($xml !== 'Alert not found') {
    echo $xml;
} else {
    http_response_code(404);
    echo '<?xml version="1.0"?><error>Alert not found</error>';
}
