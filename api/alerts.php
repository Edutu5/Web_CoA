<?php
session_start();
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/AlertsController.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : null;
    $msg_type = $_GET['msg_type'] ?? null;
    if ($msg_type && !in_array($msg_type, ['Alert', 'Update', 'Cancel'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'msg_type invalid. Valori acceptate: Alert, Update, Cancel']);
        exit;
    }
    echo alerts_show_json($event_id, $msg_type);

} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    auth_require('admin');
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'id required']);
        exit;
    }
    require_once __DIR__ . '/../models/AlertsModel.php';
    require_once __DIR__ . '/../models/EventsModel.php';
    // Gaseste evenimentul asociat si seteaza-l ca resolved
    $alert = alerts_get_by_id($id);
    if ($alert && !empty($alert['event_id'])) {
        $event = events_get_by_id($alert['event_id']);
        if ($event && $event['status'] === 'active') {
            events_update($alert['event_id'], $event['type_id'], $event['title'],
                $event['description'], $event['latitude'], $event['longitude'],
                $event['severity'], 'Past', 'resolved', false);
        }
    }
    $result = alerts_delete($id);
    echo json_encode(['success' => $result]);

} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Doar metodele GET si DELETE sunt permise']);
}