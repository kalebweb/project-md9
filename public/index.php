<?php
// public/index.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Auth.php';

$auth = new Auth();

// Se já está logado, redirecionar para dashboard
if ($auth->isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
} else {
    // Se não está logado, redirecionar para login
    header('Location: login.php');
    exit;
}
?>