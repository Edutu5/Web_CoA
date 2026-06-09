<?php
session_start();
require_once __DIR__ . '/../controllers/SheltersController.php';
header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];
if ($method == 'GET') {
    echo shelters_show_json($_GET['disaster_type_id'] ?? null);
} elseif ($method == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    echo shelters_create_action($data);
} elseif ($method == 'PUT') {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) { http_response_code(400); echo json_encode(['success' => false, 'error' => 'id required']); exit; }
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    echo shelters_update_action($id, $data);
} elseif ($method == 'DELETE') {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) { http_response_code(400); echo json_encode(['success' => false, 'error' => 'id required']); exit; }
    echo shelters_delete_action($id);
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}
