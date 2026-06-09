<?php
require_once __DIR__ . '/../config/db.php';

function earthquakes_get_all($country = null, $min_magnitude = null, $limit = 100, $offset = 0) {
    global $mysql;
    $sql = "SELECT id, country, latitude, longitude, magnitude, depth, occurred_at FROM earthquakes WHERE 1=1";
    $params = []; $types = "";
    if ($country) { $sql .= " AND country = ?"; $params[] = $country; $types .= "s"; }
    if ($min_magnitude !== null) { $sql .= " AND magnitude >= ?"; $params[] = $min_magnitude; $types .= "d"; }
    $sql .= " ORDER BY occurred_at DESC LIMIT ? OFFSET ?";
    $params[] = (int)$limit; $params[] = (int)$offset; $types .= "ii";
    $stmt = $mysql->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $csql = "SELECT COUNT(*) as total FROM earthquakes WHERE 1=1";
    $cparams = []; $ctypes = "";
    if ($country) { $csql .= " AND country = ?"; $cparams[] = $country; $ctypes .= "s"; }
    if ($min_magnitude !== null) { $csql .= " AND magnitude >= ?"; $cparams[] = $min_magnitude; $ctypes .= "d"; }
    $cstmt = $mysql->prepare($csql);
    if (!empty($cparams)) $cstmt->bind_param($ctypes, ...$cparams);
    $cstmt->execute();
    $total = $cstmt->get_result()->fetch_assoc()['total'];
    return ['data' => $data, 'total' => (int)$total, 'page' => (int)($offset / $limit) + 1, 'per_page' => (int)$limit];
}
