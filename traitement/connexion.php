<?php
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../connexion.php');
    exit();
}

$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
$mot_de_passe = $_POST['mot_de_passe'] ?? '';

if (empty($email) || empty($mot_de_passe)) {
    header('Location: ../connexion.php?erreur=Veuillez remplir tous les champs');
    exit();
}

try {
    // Vérifier d'abord dans les utilisateurs (candidats)
    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ? AND actif = 1");
    $stmt->execute([$email]);
    $utilisateur = $stmt->fetch();
    
    if ($utilisateur && password_verify($mot_de_passe, $utilisateur['mot_de_passe'])) {
        // Connexion utilisateur réussie
        $_SESSION['utilisateur_id'] = $utilisateur['id'];
        $_SESSION['utilisateur_nom'] = $utilisateur['prenom'] . ' ' . $utilisateur['nom'];
        $_SESSION['utilisateur_email'] = $utilisateur['email'];
        
        // Redirection vers espace utilisateur
        header('Location: ../espace_utilisateur.php');
        exit();
    }
    
    // Vérifier dans les entreprises
    $stmt = $pdo->prepare("SELECT * FROM entreprises WHERE email = ? AND actif = 1");
    $stmt->execute([$email]);
    $entreprise = $stmt->fetch();
    
    if ($entreprise && password_verify($mot_de_passe, $entreprise['mot_de_passe'])) {
        // Connexion entreprise réussie
        $_SESSION['entreprise_id'] = $entreprise['id'];
        $_SESSION['entreprise_nom'] = $entreprise['nom_entreprise'];
        $_SESSION['entreprise_email'] = $entreprise['email'];
        
        // Redirection vers espace entreprise
        header('Location: ../espace_entreprise.php');
        exit();
    }
    
    // Vérifier dans les administrateurs
    $stmt = $pdo->prepare("SELECT * FROM administrateurs WHERE email = ? AND actif = 1");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();
    
    if ($admin && password_verify($mot_de_passe, $admin['mot_de_passe'])) {
        // Connexion admin réussie
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_nom'] = $admin['prenom'] . ' ' . $admin['nom'];
        $_SESSION['admin_email'] = $admin['email'];
        $_SESSION['admin_role'] = $admin['role'];
        
        // Redirection vers dashboard admin
        header('Location: ../admin/dashboard.php');
        exit();
    }
    
    // Si aucun utilisateur trouvé
    header('Location: ../connexion.php?erreur=Email ou mot de passe incorrect');
    exit();
    
} catch (PDOException $e) {
    error_log("Erreur connexion: " . $e->getMessage());
    header('Location: ../connexion.php?erreur=Erreur lors de la connexion. Veuillez réessayer.');
    exit();
}
?>