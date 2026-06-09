<?php
/* route_auto.php — Endpoint API public pentru calculul rutelor de evacuare. */
session_start();
require_once __DIR__ . '/../models/RouteCalculator.php';

header('Content-Type: application/json; charset=utf-8');

$event_id = (int)($_GET['event_id'] ?? 0);
if (!$event_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'event_id required']);
    exit;
}

$result = calculate_evacuation_routes($event_id);
if (!$result) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Event not found']);
    exit;
}

echo json_encode(['success' => true] + $result);