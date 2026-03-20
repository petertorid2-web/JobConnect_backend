<?php
require_once __DIR__ . '/../config/database.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Connect - Plateforme de mise en relation professionnelle</title>
    <link rel="stylesheet" href="css/style.css">
    <!-- Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="container">
                <a href="index.php" class="logo">Job Connect</a>
                <ul class="nav-links">
                    <li><a href="index.php">Accueil</a></li>
                    <li><a href="offres.php">Offres d'emploi</a></li>
                    <?php if (!estConnecte()): ?>
                        <li><a href="connexion.php">Connexion</a></li>
                        <li><a href="inscription.php" class="btn-inscription">Inscription</a></li>
                    <?php else: ?>
                        <?php if (isset($_SESSION['utilisateur_id'])): ?>
                            <li><a href="espace_utilisateur.php">Mon espace</a></li>
                        <?php elseif (isset($_SESSION['entreprise_id'])): ?>
                            <li><a href="espace_entreprise.php">Mon espace</a></li>
                        <?php elseif (isset($_SESSION['admin_id'])): ?>
                            <li><a href="admin/dashboard.php">Dashboard Admin</a></li>
                        <?php endif; ?>
                        <li><a href="deconnexion.php">Déconnexion</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </nav>
    </header>
    <main>