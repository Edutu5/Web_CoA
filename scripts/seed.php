<?php
require_once __DIR__ . '/../config/db.php';
$mysql->query("USE web_coa");
$users = [
    ['admin', 'admin123', 'admin'],
    ['authority', 'admin123', 'authority'],
    ['user', 'admin123', 'user']
];
foreach ($users as $u) {
    $hash = password_hash($u[1], PASSWORD_BCRYPT);
    $stmt = $mysql->prepare("INSERT IGNORE INTO users (username, password_hash, role) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $u[0], $hash, $u[2]);
    $stmt->execute();
}
echo "Users seeded.\n";
$types = ['EQ' => 'Cutremur', 'FIRE' => 'Incendiu', 'FLOOD' => 'Inundație'];
foreach ($types as $code => $name) {
    $stmt = $mysql->prepare("INSERT IGNORE INTO disaster_types (code, name) VALUES (?, ?)");
    $stmt->bind_param("ss", $code, $name);
    $stmt->execute();
}
echo "Disaster types seeded.\nDone.\n";
