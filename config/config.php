<?php
// config.php - Setari globale ale aplicatiei
// Aici se configureaza: URL baza, expeditorul alertelor CAP, modul de dezvoltare
// config.php - Constante globale: URL, expeditor CAP, mediu (dev/prod)
// In dev afisam toate erorile, in productie le ascundem

// Mediul aplicatiei: development (cu erori afisate) sau production
define('APP_ENV', 'development');
// URL-ul de baza - se schimba in productie cu domeniul real
define('BASE_URL', 'http://localhost/Web_CoA');

// Expeditorul alertelor CAP - apare in XML-ul generat
define('CAP_SENDER', 'coa@uaic.ro');
define('CAP_SCOPE', 'Public');

// In dev vrem sa vedem toate erorile, in productie le ascundem
if (APP_ENV === 'development') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}