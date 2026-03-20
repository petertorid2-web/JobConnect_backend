<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';

checkCandidatAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../experiences.php');
    exit();
}

$utilisateur_id = $_SESSION['utilisateur_id'];

$titre_poste = trim($_POST['titre_poste'] ?? '');
$nom_entreprise = trim($_POST['nom_entreprise'] ?? '');
$lieu = trim($_POST['lieu'] ?? '');
$date_debut = $_POST['date_debut'] ?? '';
$date_fin = $_POST['date_fin'] ?? '';
$description = trim($_POST['description'] ?? '');
$est_poste_actuel = isset($_POST['est_poste_actuel']) ? 1 : 0;

if (empty($titre_poste) || empty($nom_entreprise) || empty($date_debut)) {
    header('Location: ../experiences.php?error=Please fill required fields');
    exit();
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO experiences_professionnelles (utilisateur_id, titre_poste, nom_entreprise, lieu, date_debut, date_fin, description, est_poste_actuel)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $utilisateur_id,
        $titre_poste,
        $nom_entreprise,
        $lieu ?: null,
        $date_debut,
        $est_poste_actuel ? null : ($date_fin ?: null),
        $description ?: null,
        $est_poste_actuel
    ]);
    
    header('Location: ../experiences.php?success=Experience added successfully');
    exit();
    
} catch (PDOException $e) {
    error_log("Error adding experience: " . $e->getMessage());
    header('Location: ../experiences.php?error=Failed to add experience');
    exit();
}
?>