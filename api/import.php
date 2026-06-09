<?php
// api/import.php - Import fisiere CSV/JSON (doar admin)
// Suporta: adaposturi, crize/scenarii de test, date seismologice
// Limita fisier: 5MB, validare extensie si format
// api/import.php - Import fisiere CSV/JSON cu adaposturi, crize sau cutremure
// Limita: 5MB per fisier, doar admin
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/ImportController.php';

header('Content-Type: application/json; charset=utf-8');

auth_require('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Doar metoda POST este permisa']);
    exit;
}

$entity = $_POST['entity'] ?? '';
$format = $_POST['format'] ?? 'csv';

if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK)
{
    http_response_code(400);
    $upload_errors = [
        UPLOAD_ERR_INI_SIZE   => 'Fisierul depaseste limita din php.ini',
        UPLOAD_ERR_FORM_SIZE  => 'Fisierul depaseste limita din formular',
        UPLOAD_ERR_PARTIAL    => 'Fisierul a fost uploadat partial',
        UPLOAD_ERR_NO_FILE    => 'Niciun fisier selectat',
        UPLOAD_ERR_NO_TMP_DIR => 'Folder-ul lipseste temporar pe server',
        UPLOAD_ERR_CANT_WRITE => 'Nu pot scrie pe disk',
    ];
    $err_code = $_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE;
    $err_msg = $upload_errors[$err_code] ?? 'Eroare necunoscuta la upload';
    echo json_encode(['success' => false, 'error' => $err_msg]);
    exit;
}

$filename = $_FILES['file']['name'];
$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
if ($format === 'csv' && $ext !== 'csv') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Format selectat CSV dar fisierul are extensia .' . $ext]);
    exit;
}
if ($format === 'json' && $ext !== 'json') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Format selectat JSON dar fisierul are extensia .' . $ext]);
    exit;
}

$max_size = 5 * 1024 * 1024;
if ($_FILES['file']['size'] > $max_size)
{
    http_response_code(400);
    $size_mb = round($_FILES['file']['size'] / 1024 / 1024, 1);
    echo json_encode(['success' => false, 'error' => "Fisierul e prea mare ({$size_mb} MB). Limita: 5 MB."]);
    exit;
}

$result = import_data($entity, $format, $_FILES['file']['tmp_name']);

if ($result['success']) {
    http_response_code(200);
} else {
    http_response_code(400);
}
echo json_encode($result);

$log_dir = __DIR__ . '/../logs';
if (!is_dir($log_dir)) mkdir($log_dir, 0755, true);
$log = sprintf("[%s] Import %s %s: %d importate, %d ignorate, user=%s, fisier=%s\n",
    date('Y-m-d H:i:s'),
    $format, $entity,
    $result['imported'] ?? 0, $result['skipped'] ?? 0,
    $_SESSION['username'] ?? 'unknown',
    $_FILES['file']['name'] ?? 'unknown'
);
file_put_contents($log_dir . '/imports.log', $log, FILE_APPEND);