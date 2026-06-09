<?php
/* SheltersController.php — Logica CRUD pentru adaposturi */
require_once __DIR__ . '/../models/SheltersModel.php';
require_once __DIR__ . '/AuthController.php';

function shelters_show_json($type_id = null) {
    $shelters = shelters_get_all($type_id);
    return json_encode(['data' => $shelters, 'total' => count($shelters)]);
}

function shelters_create_action($data) {
    auth_require('admin');
    if (empty($data['name']) || !isset($data['latitude']) || !isset($data['longitude'])) {
        http_response_code(400);
        return json_encode(['success' => false, 'error' => 'name, latitude, longitude required']);
    }
    $id = shelters_create(
        $data['name'], (float)$data['latitude'], (float)$data['longitude'],
        $data['address'] ?? '',
        !empty($data['disaster_type_id']) ? (int)$data['disaster_type_id'] : null
    );
    http_response_code(201);
    return json_encode(['success' => true, 'id' => $id]);
}

function shelters_update_action($id, $data) {
    auth_require('admin');
    $existing = shelters_get_by_id($id);
    if (!$existing) { http_response_code(404); return json_encode(['success' => false, 'error' => 'Shelter not found']); }
    shelters_update($id,
        $data['name'] ?? $existing['name'],
        (float)($data['latitude'] ?? $existing['latitude']),
        (float)($data['longitude'] ?? $existing['longitude']),
        $data['address'] ?? $existing['address'],
        isset($data['disaster_type_id']) ? (int)$data['disaster_type_id'] : $existing['disaster_type_id']);
    return json_encode(['success' => true]);
}

function shelters_delete_action($id) {
    auth_require('admin');
    $existing = shelters_get_by_id($id);
    if (!$existing) { http_response_code(404); return json_encode(['success' => false, 'error' => 'Shelter not found']); }
    shelters_delete($id);
    return json_encode(['success' => true]);
}
