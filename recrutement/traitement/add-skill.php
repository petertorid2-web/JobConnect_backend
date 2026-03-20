<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';

checkCandidatAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../skills.php');
    exit();
}

$utilisateur_id = $_SESSION['utilisateur_id'];
$competence_id = $_POST['competence_id'] ?? 0;
$niveau = $_POST['niveau'] ?? 'Intermédiaire';

if (!$competence_id) {
    header('Location: ../skills.php?error=Please select a skill');
    exit();
}

try {
    // Vérifier si la compétence existe
    $stmt = $pdo->prepare("SELECT id FROM competences WHERE id = ?");
    $stmt->execute([$competence_id]);
    
    if (!$stmt->fetch()) {
        header('Location: ../skills.php?error=Skill not found');
        exit();
    }
    
    // Vérifier si l'utilisateur a déjà cette compétence
    $stmt = $pdo->prepare("SELECT * FROM utilisateur_competences WHERE utilisateur_id = ? AND competence_id = ?");
    $stmt->execute([$utilisateur_id, $competence_id]);
    
    if ($stmt->fetch()) {
        header('Location: ../skills.php?error=You already have this skill');
        exit();
    }
    
    // Ajouter la compétence
    $stmt = $pdo->prepare("
        INSERT INTO utilisateur_competences (utilisateur_id, competence_id, niveau)
        VALUES (?, ?, ?)
    ");
    
    $stmt->execute([$utilisateur_id, $competence_id, $niveau]);
    
    header('Location: ../skills.php?success=Skill added successfully');
    exit();
    
} catch (PDOException $e) {
    error_log("Error adding skill: " . $e->getMessage());
    header('Location: ../skills.php?error=Failed to add skill');
    exit();
}
?>
