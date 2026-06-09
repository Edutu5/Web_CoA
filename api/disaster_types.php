<?php
   // Folosit pt dropdown-ul de filtrare pe harta si in formulare

session_start();
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json;charset=utf-8');

if($_SERVER['REQUEST_METHOD']!== 'GET'){
    http_response_code(405);
    echo json_encode(['error' =>'Doar metoda GET este permisa']);
    exit;
}

$result = $mysql->query(" SELECT dt.id, dt.code, dt.name, COUNT(e.id) as active_events FROM disaster_types dt
    LEFT JOIN events e ON e.type_id = dt.id AND e.status = 'active' GROUP BY dt.id, dt.code, dt.name ORDER BY dt.id");
$types = [];
while ($row = $result->fetch_assoc())
{
    $types[] = $row;
}

echo json_encode(['data' => $types, 'total' => count($types)]);