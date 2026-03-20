<?php
/**
 * Vérification d'authentification pour les pages protégées
 */

function checkCandidatAuth() {
    if (!isset($_SESSION['utilisateur_id'])) {
        header('Location: ../login.php?error=Please login to access this page');
        exit();
    }
}

function checkEntrepriseAuth() {
    if (!isset($_SESSION['entreprise_id'])) {
        header('Location: ../login.php?error=Please login to access this page');
        exit();
    }
}

function checkAdminAuth() {
    if (!isset($_SESSION['admin_id'])) {
        header('Location: ../login.php?error=Access denied');
        exit();
    }
}

function getCurrentUserId() {
    return $_SESSION['utilisateur_id'] ?? $_SESSION['entreprise_id'] ?? $_SESSION['admin_id'] ?? null;
}

function getUserType() {
    if (isset($_SESSION['utilisateur_id'])) return 'candidat';
    if (isset($_SESSION['entreprise_id'])) return 'entreprise';
    if (isset($_SESSION['admin_id'])) return 'admin';
    return null;
}
?>