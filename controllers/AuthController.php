<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/UsersModel.php';

function auth_process_login($username, $password) {
    $user = users_find_by_username($username);
    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        return $user['role'];
    }
    return false;
}

function auth_process_logout() {
    $_SESSION = [];
    session_destroy();
}

function auth_require($min_role = 'user') {
    if (session_status() === PHP_SESSION_NONE) session_start();

    $is_api = strpos($_SERVER['REQUEST_URI'], '/api/') !== false
           || strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;

    // Utilizator neautentificat
    if (!isset($_SESSION['user_id'])) {
        if ($is_api) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'Autentificare necesara']);
        } else {
            header('Location: ' . BASE_URL . '/login.php');
        }
        exit;
    }

    $hierarchy = ['user' => 0, 'authority' => 1, 'admin' => 2];
    $user_level = $hierarchy[$_SESSION['role'] ?? 'user'] ?? -1;
    $required_level = $hierarchy[$min_role] ?? 0;
    if ($user_level < $required_level) {
        http_response_code(403);
        if ($is_api) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error' => 'Permisiuni insuficiente. Rol necesar: ' . $min_role
            ]);
        } else {
            echo 'Acces interzis. Rolul tau (' . htmlspecialchars($_SESSION['role'], ENT_QUOTES, 'UTF-8') . ') nu are permisiunea necesara: ' . htmlspecialchars($min_role, ENT_QUOTES, 'UTF-8');
        }
        exit;
    }
}