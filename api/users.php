<?php
// api/users.php - CRUD utilizatori (acces doar admin)
// POST=creare, PUT=schimbare rol, DELETE=stergere
// Adminul nu isi poate modifica/sterge propriul cont (protectie in frontend)
// api/users.php - CRUD utilizatori, acces doar admin
session_start();
require_once __DIR__ . '/../controllers/AuthController.php';
auth_require('admin');
require_once __DIR__ . '/../models/UsersModel.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'GET') {
    $users = users_get_all();
    echo json_encode(['data' => $users, 'total' => count($users)]);

} elseif ($method == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    if (empty($data['username']) || empty($data['password'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'username and password required']);
        exit;
    }
    $valid_roles = ['user', 'authority', 'admin'];
    $role = $data['role'] ?? 'user';
    if (!in_array($role, $valid_roles)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'invalid role']);
        exit;
    }
    $hash = password_hash($data['password'], PASSWORD_BCRYPT);
    if (users_create($data['username'], $hash, $role)) {
        http_response_code(201);
        echo json_encode(['success' => true]);
    } else {
        http_response_code(409);
        echo json_encode(['success' => false, 'error' => 'Username already exists']);
    }

} elseif ($method == 'PUT') {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'id required']);
        exit;
    }
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    $valid_roles = ['user', 'authority', 'admin'];
    $role = $data['role'] ?? 'user';
    if (!in_array($role, $valid_roles)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'invalid role']);
        exit;
    }
    users_update_role($id, $role);
    echo json_encode(['success' => true]);

} elseif ($method == 'DELETE') {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'id required']);
        exit;
    }
    users_delete($id);
    echo json_encode(['success' => true]);

} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}