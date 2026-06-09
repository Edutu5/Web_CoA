<?php

$mysql = new mysqli('localhost', 'root', '', 'web_coa');

if ($mysql->connect_errno) {
    error_log('DB connect failed: ' . $mysql->connect_error);
    http_response_code(500);
    die('Eroare interna. Incercati mai tarziu.');
}

$mysql->set_charset('utf8mb4');
