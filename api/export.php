<?php
// export date in CSV/JSON/XML, doar admin
session_start();
require_once __DIR__ . '/../controllers/ExportController.php';

$type   = $_GET['type'] ?? '';
$entity = $_GET['entity'] ?? '';

if (empty($type) || empty($entity)) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Parametri lipsa. Foloseste: ?type=csv|json|xml&entity=events|shelters|earthquakes|alerts'
    ]);
    exit;
}

if ($type === 'csv' && $entity) {
    export_csv($entity);
} elseif ($type === 'json' && $entity) {
    export_json($entity);
} elseif ($type === 'xml' && $entity === 'alerts') {
    export_alerts_xml();
} else {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Combinatie type/entity invalida']);
}