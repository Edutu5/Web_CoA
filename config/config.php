<?php

define('APP_ENV', 'development');
define('BASE_URL', 'http://localhost/Web_CoA');

define('CAP_SENDER', 'coa@uaic.ro');
define('CAP_SCOPE', 'Public');

if (APP_ENV === 'development') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}