<?php
// ExportController.php - Export date in 3 formate: CSV, JSON, XML CAP
// Toate exporturile necesita rol admin
// CSV-ul include BOM UTF-8 pt compatibilitate cu Excel si diacritice
// ExportController.php - Export CSV (cu BOM pt Excel), JSON, XML CAP
// Toate exporturile necesita rol admin
require_once __DIR__ . '/../models/EventsModel.php';
require_once __DIR__ . '/../models/SheltersModel.php';
require_once __DIR__ . '/../models/EarthquakesModel.php';
require_once __DIR__ . '/../models/AlertsModel.php';
require_once __DIR__ . '/AuthController.php';

function export_csv($entity) {
    auth_require('admin');
    $data = [];
    if ($entity === 'events') $data = events_get_all();
    elseif ($entity === 'shelters') $data = shelters_get_all();
    elseif ($entity === 'earthquakes') { $d = earthquakes_get_all(null, null, 999999, 0); $data = $d['data']; }
    elseif ($entity === 'alerts') $data = alerts_get_all();
    else { http_response_code(400); echo 'Invalid entity'; exit; }
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $entity . '.csv"');

    echo "\xEF\xBB\xBF";
    $fp = fopen('php://output', 'w');
    if (!empty($data)) {
        usort($data, function($a, $b) {
            return ($a['id'] ?? 0) - ($b['id'] ?? 0);
        });
        fputcsv($fp, array_keys($data[0]));
        foreach ($data as $row) fputcsv($fp, $row);
        fclose($fp);
    }
    exit;
}

// Export JSON cu diacritice nealterate
function export_json($entity) {
    auth_require('admin');
    $data = [];
    if ($entity === 'events') $data = events_get_all();
    elseif ($entity === 'shelters') $data = shelters_get_all();
    elseif ($entity === 'earthquakes') { $d = earthquakes_get_all(null, null, 999999, 0); $data = $d['data']; }
    elseif ($entity === 'alerts') $data = alerts_get_all();

    else { http_response_code(400); echo 'Invalid entity'; exit; }
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $entity . '.json"');
    usort($data, function($a, $b) {
        return ($a['id'] ?? 0) - ($b['id'] ?? 0);
    });
    echo json_encode(['exported_at' => date('c'), 'total' => count($data), 'data' => $data], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// Export XML - grupeaza toate alertele CAP intr-un singur fisier
function export_alerts_xml() {
    auth_require('admin');
    $alerts = alerts_get_all();
    $doc = new DOMDocument('1.0', 'UTF-8');
    $root = $doc->createElement('alerts');
    $doc->appendChild($root);
    foreach ($alerts as $a) {
        $axml = new DOMDocument();
        if ($axml->loadXML($a['cap_xml'])) {
            $root->appendChild($doc->importNode($axml->documentElement, true));
        }
    }
    header('Content-Type: application/xml');
    header('Content-Disposition: attachment; filename="alerts.xml"');
    echo $doc->saveXML();
    exit;
}
