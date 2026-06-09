<?php
require_once __DIR__ . '/../config/db.php';

function events_get_all($status = null, $type_code = null) {
    global $mysql;
    $sql = "SELECT e.*, dt.code as type_code, dt.name as type_name, u.username as creator_name
            FROM events e
            JOIN disaster_types dt ON e.type_id = dt.id
            LEFT JOIN users u ON e.created_by = u.id
            WHERE 1=1";
    $params = [];
    $types = "";
    if ($status) { $sql .= " AND e.status = ?"; $params[] = $status; $types .= "s"; }
    if ($type_code) { $sql .= " AND dt.code = ?"; $params[] = $type_code; $types .= "s"; }
    $sql .= " ORDER BY e.created_at DESC";
    $stmt = $mysql->prepare($sql);
    if (!empty($params)) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function events_get_by_id($id){
    global $mysql;
    $stmt = $mysql->prepare("SELECT e.*, dt.code as type_code, dt.name as type_name FROM events e JOIN disaster_types dt ON e.type_id = dt.id WHERE e.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function events_create($type_id, $title, $description, $lat, $lng, $severity, $urgency, $user_id) {
    global $mysql;
    $stmt = $mysql->prepare("INSERT INTO events (type_id, title, description, latitude, longitude, severity, urgency, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issdsssi", $type_id, $title, $description, $lat, $lng, $severity, $urgency, $user_id);
    $stmt->execute();
    return $stmt->insert_id;
}

function events_update($id, $type_id, $title, $description, $lat, $lng, $severity, $urgency, $status, $increment_edit = false) {
    global $mysql;
    $edit_sql = $increment_edit ? ", edit_count = edit_count + 1" : "";
    $sql = "UPDATE events SET type_id=?, title=?, description=?, latitude=?, longitude=?, severity=?, urgency=?, status=?{$edit_sql} WHERE id=?";
    $stmt = $mysql->prepare($sql);
    $stmt->bind_param("issdssssi", $type_id, $title, $description, $lat, $lng, $severity, $urgency, $status, $id);
    return $stmt->execute();
}
function events_delete($id) {
    global $mysql;
    $stmt = $mysql->prepare("DELETE FROM events WHERE id = ?");
    $stmt->bind_param("i", $id);
    return $stmt->execute();
}

function disaster_type_exists($id) {
    global $mysql;
    $stmt = $mysql->prepare("SELECT id FROM disaster_types WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() !== null;
}

function suggested_severity($type_code){
    $map = [
        'EQ' => 'high',
        'FIRE' => 'medium',
        'FLOOD'=> 'medium'
    ];
    return $map[$type_code] ?? 'medium';
}
