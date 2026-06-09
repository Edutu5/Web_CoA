<?php
// Functii CRUD pentru tabela shelters
require_once __DIR__ . '/../config/db.php';

// Returneaza adaposturile, optional filtrate pe tip calamitate
// LEFT JOIN cu disaster_types ca sa stim si tipul fiecarui adapost
function shelters_get_all($type_id = null) {
    global $mysql;
    $sql = "SELECT s.*, dt.code as type_code, dt.name as type_name
            FROM shelters s LEFT JOIN disaster_types dt ON s.disaster_type_id = dt.id WHERE 1=1";
    $params = []; $types = "";
    if ($type_id) { $sql .= " AND s.disaster_type_id = ?"; $params[] = $type_id; $types .= "i"; }
    $sql .= " ORDER BY s.name ASC";
    $stmt = $mysql->prepare($sql);
    if (!empty($params)) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Un singur adapost dupa ID - pt formularul de editare
function shelters_get_by_id($id) {
    global $mysql;
    $stmt = $mysql->prepare("SELECT s.*, dt.code as type_code FROM shelters s LEFT JOIN disaster_types dt ON s.disaster_type_id = dt.id WHERE s.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Adauga adapost nou - coordonate GPS + adresa + tip optional
function shelters_create($name, $lat, $lng, $address, $disaster_type_id = null) {
    global $mysql;
    $stmt = $mysql->prepare("INSERT INTO shelters (name, latitude, longitude, address, disaster_type_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sddsi", $name, $lat, $lng, $address, $disaster_type_id);
    $stmt->execute();
    return $stmt->insert_id;
}

// Actualizeaza un adapost existent
function shelters_update($id, $name, $lat, $lng, $address, $disaster_type_id = null) {
    global $mysql;
    $stmt = $mysql->prepare("UPDATE shelters SET name=?, latitude=?, longitude=?, address=?, disaster_type_id=? WHERE id=?");
    $stmt->bind_param("sddsii", $name, $lat, $lng, $address, $disaster_type_id, $id);
    return $stmt->execute();
}

// Sterge adapost - atentie, poate afecta rutele de evacuare
function shelters_delete($id) {
    global $mysql;
    $stmt = $mysql->prepare("DELETE FROM shelters WHERE id = ?");
    $stmt->bind_param("i", $id);
    return $stmt->execute();
}

// Verifica duplicat la import - daca exista deja un adapost cu acelasi nume la aceleasi coordonate
function shelter_exists_at($name, $lat, $lng) {
    global $mysql;
    $stmt = $mysql->prepare("SELECT id FROM shelters WHERE name = ? AND latitude = ? AND longitude = ?");
    $stmt->bind_param("sdd", $name, $lat, $lng);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() !== null;
}
