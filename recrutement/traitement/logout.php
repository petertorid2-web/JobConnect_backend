<?php
require_once '../config/database.php';

// Détruire toutes les variables de session
$_SESSION = array();

// Détruire le cookie de session
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Détruire le cookie remember me
setcookie('remember_token', '', time() - 3600, '/');

// Détruire la session
session_destroy();

// Rediriger vers la page d'accueil
header('Location: ../index.php?message=You have been logged out successfully');
exit();
?>