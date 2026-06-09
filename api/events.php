<?php
// api/events.php - Endpoint REST pt crize
// GET=public, POST=authority+, PUT=authority+, DELETE=admin
// Rutarea se face pe REQUEST_METHOD (switch/case)
// api/events.php - REST endpoint pt crize
// GET=public, POST=authority+, PUT=authority+, DELETE=admin only
session_start();
require_once __DIR__ . '/../controllers/EventsController.php';
header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];
if ($method == 'GET') {
    echo events_show_json($_GET['status'] ?? null, $_GET['type_code'] ?? null);
} elseif ($method == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    echo events_create_action($data);
} elseif ($method == 'PUT') {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) { http_response_code(400); echo json_encode(['success' => false, 'error' => 'id required']); exit; }
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    echo events_update_action($id, $data);
} elseif ($method == 'DELETE') {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) { http_response_code(400); echo json_encode(['success' => false, 'error' => 'id required']); exit; }
    echo events_delete_action($id);
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}
