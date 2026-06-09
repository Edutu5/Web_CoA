<?php
// UsersModel.php - Functii CRUD pt tabela users
// Toate query-urile cu prepared statements pt prevenirea SQL injection
// Parolele se stocheaza ca bcrypt hash, nu plain text
// UsersModel.php - Functii CRUD pt tabela users
// Parolele sunt stocate ca bcrypt hash, niciodata plain text
require_once __DIR__ . '/../config/db.php';

// Cauta un user dupa username - folosit la login si la signup (pt verificare duplicat)
function users_find_by_username($username) {
    global $mysql;
    $stmt = $mysql->prepare("SELECT id, username, password_hash, role, created_at FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}
// Creaza user nou - parola vine deja hashuite din controller/signup
function users_create($username, $password_hash, $role) {
    global $mysql;
    $stmt = $mysql->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $password_hash, $role);
    return $stmt->execute();
}

// Lista toti userii pt panoul admin - NU returnam parola!
function users_get_all() {
    global $mysql;
    $result = $mysql->query("SELECT id, username, role, created_at FROM users ORDER BY created_at DESC");
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    return $users;
}

// Schimba rolul unui user (user/authority/admin) - doar adminul poate
function users_update_role($id, $role) {
    global $mysql;
    $stmt = $mysql->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->bind_param("si", $role, $id);
    return $stmt->execute();
}
// Sterge un user din DB - cascadeaza pe events via FK
function users_delete($id) {
    global $mysql;
    $stmt = $mysql->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    return $stmt->execute();
}
