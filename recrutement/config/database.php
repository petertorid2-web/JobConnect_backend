<?php
// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'job_connect_db');
define('DB_USER', 'root'); // À modifier selon votre configuration
define('DB_PASS', ''); // À modifier selon votre configuration

// Connexion à la base de données avec PDO
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch(PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fonctions utilitaires
function estConnecte() {
    return isset($_SESSION['utilisateur_id']) || isset($_SESSION['entreprise_id']) || isset($_SESSION['admin_id']);
}

function redirigerSiConnecte() {
    if (estConnecte()) {
        if (isset($_SESSION['utilisateur_id'])) {
            header('Location: espace_utilisateur.php');
        } elseif (isset($_SESSION['entreprise_id'])) {
            header('Location: espace_entreprise.php');
        } elseif (isset($_SESSION['admin_id'])) {
            header('Location: admin/dashboard.php');
        }
        exit();
    }
}

function nettoyerDonnee($donnee) {
    return htmlspecialchars(trim($donnee), ENT_QUOTES, 'UTF-8');
}
?>