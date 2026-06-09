<?php
// AuthController.php - Autentificare si control acces pe baza de roluri
// Ierarhia: user < authority < admin (fiecare nivel include permisiunile celor inferioare)
// Pe API-uri returneaza JSON cu 401/403, pe pagini face redirect la login
// AuthController.php - Autentificare si verificare roluri
// Ierarhie: user < authority < admin
// Pe API returneaza 401/403 JSON, pe pagini face redirect

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/UsersModel.php';

// Proceseaza formularul de login
// Compara parola cu hash-ul bcrypt din DB folosind password_verify
function auth_process_login($username, $password) {
    $user = users_find_by_username($username);
    if ($user && password_verify($password, $user['password_hash'])) {
        // Regeneram ID sesiune dupa login reusit - previne session fixation
                // Regeneram ID-ul sesiunii dupa login - previne session fixation attacks
        // Fara asta, un atacator ar putea refolosi un session ID vechi
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        return $user['role'];
    }
    return false;
}

// Distruge complet sesiunea la logout
function auth_process_logout() {
    $_SESSION = [];
    session_destroy();
}

// Verifica daca userul curent are rolul minim necesar
// Folosim un sistem numeric: user=0, authority=1, admin=2
// Daca user_level < required_level -> acces refuzat
function auth_require($min_role = 'user') {
    if (session_status() === PHP_SESSION_NONE) session_start();

        // Detectam daca request-ul vine de la API (ajax) sau de la o pagina normala
    // Pt API returnam JSON, pt pagini facem redirect
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

    // Niveluri numerice pt ierarhia de roluri
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