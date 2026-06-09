<?php
// db.php - Conexiunea la baza de date MariaDB
// Am folosit mysqli direct (fara PDO) - cerinta proiectului e fara framework-uri
// Charset utf8mb4 ca sa mearga diacriticele romanesti

// Cream conexiunea la serverul MariaDB local, baza de date web_coa
$mysql = new mysqli('localhost', 'root', '', 'web_coa');

// Daca conexiunea esueaza, logam eroarea dar nu afisam detalii tehnice userului
if ($mysql->connect_errno) {
    error_log('DB connect failed: ' . $mysql->connect_error);
    http_response_code(500);
    die('Eroare interna. Incercati mai tarziu.');
}

// Setam charset-ul pe utf8mb4 ca sa mearga si caracterele speciale
$mysql->set_charset('utf8mb4');
