<?php
require_once __DIR__ . '/../config/db.php';

function users_find_by_username($username) {
    global $mysql;
    $stmt = $mysql->prepare("SELECT id, username, password_hash, role, created_at FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}
function users_create($username, $password_hash, $role) {
    global $mysql;
    $stmt = $mysql->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $password_hash, $role);
    return $stmt->execute();
}

function users_get_all() {
    global $mysql;
    $result = $mysql->query("SELECT id, username, role, created_at FROM users ORDER BY created_at DESC");
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    return $users;
}

function users_update_role($id, $role) {
    global $mysql;
    $stmt = $mysql->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->bind_param("si", $role, $id);
    return $stmt->execute();
}
function users_delete($id) {
    global $mysql;
    $stmt = $mysql->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    return $stmt->execute();
}
