<?php
require_once __DIR__ . '/../config/db.php';

function alerts_save($event_id, $cap_identifier, $cap_xml, $msg_type = 'Alert') {
    global $mysql;
    $stmt = $mysql->prepare("INSERT INTO alerts (event_id, cap_identifier, cap_xml, msg_type) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $event_id, $cap_identifier, $cap_xml, $msg_type);
    $stmt->execute();
    return $stmt->insert_id;
}

function alerts_get_by_id($id) {
    global $mysql;
    $stmt = $mysql->prepare("SELECT a.*, e.title as event_title FROM alerts a JOIN events e ON a.event_id = e.id WHERE a.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function alerts_get_all() {
    global $mysql;
    $result = $mysql->query("SELECT a.*, e.title as event_title FROM alerts a JOIN events e ON a.event_id = e.id ORDER BY a.sent_at DESC");
    $alerts = [];
    while ($row = $result->fetch_assoc()) { $alerts[] = $row; }
    return $alerts;
}

function alerts_delete($id) {
    global $mysql;
    $stmt = $mysql->prepare("DELETE FROM alerts WHERE id = ?");
    $stmt->bind_param("i", $id);
    return $stmt->execute();
}

function alerts_get_by_event_id($event_id) {
    global $mysql;
    $stmt = $mysql->prepare("SELECT * FROM alerts WHERE event_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function alerts_update_msg_type($id, $msg_type, $new_xml) {
    global $mysql;
    $stmt = $mysql->prepare("UPDATE alerts SET msg_type = ?, cap_xml = ? WHERE id = ?");
    $stmt->bind_param("ssi", $msg_type, $new_xml, $id);
    return $stmt->execute();
}

function alerts_get_filtered($event_id = null, $msg_type = null) {
    global $mysql;
    $sql = "SELECT a.*, e.title as event_title, e.severity, e.urgency, e.edit_count, dt.code as type_code, dt.name as type_name 
            FROM alerts a
            JOIN events e ON a.event_id = e.id
            JOIN disaster_types dt ON e.type_id = dt.id
            WHERE 1=1";
    $params = [];
    $types = "";

    if ($event_id) {
        $sql .= " AND a.event_id = ?";
        $params[] = (int)$event_id;
        $types .= "i";
    }
    if ($msg_type) {
        $sql .= " AND a.msg_type = ?";
        $params[] = $msg_type;
        $types .= "s";
    }

    $sql .= " ORDER BY a.sent_at DESC";
    $stmt = $mysql->prepare($sql);
    if (!empty($params)) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}