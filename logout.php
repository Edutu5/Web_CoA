<?php
//   Distruge sesiunea si face redirect la pagina de start

session_start();
require_once __DIR__ . '/controllers/AuthController.php';
auth_process_logout();
header('Location: index.php');
exit;
