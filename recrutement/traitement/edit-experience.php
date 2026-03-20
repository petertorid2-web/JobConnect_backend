<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';

checkCandidatAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../experiences.php');
    exit();
}

$utilisateur_id = $_SESSION['utilisateur_id'];
$experience_id = $_POST['experience_id'] ?? 0;

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
    // Vérifier que l'expérience appartient bien à l'utilisateur
    $stmt = $pdo->prepare("SELECT id FROM experiences_professionnelles WHERE id = ? AND utilisateur_id = ?");
    $stmt->execute([$experience_id, $utilisateur_id]);
    
    if (!$stmt->fetch()) {
        header('Location: ../experiences.php?error=Experience record not found');
        exit();
    }
    
    $stmt = $pdo->prepare("
        UPDATE experiences_professionnelles 
        SET titre_poste = ?, nom_entreprise = ?, lieu = ?, date_debut = ?, date_fin = ?, description = ?, est_poste_actuel = ?
        WHERE id = ?
    ");
    
    $stmt->execute([
        $titre_poste,
        $nom_entreprise,
        $lieu ?: null,
        $date_debut,
        $est_poste_actuel ? null : ($date_fin ?: null),
        $description ?: null,
        $est_poste_actuel,
        $experience_id
    ]);
    
    header('Location: ../experiences.php?success=Experience updated successfully');
    exit();
    
} catch (PDOException $e) {
    error_log("Error updating experience: " . $e->getMessage());
    header('Location: ../experiences.php?error=Failed to update experience');
    exit();
}
?>