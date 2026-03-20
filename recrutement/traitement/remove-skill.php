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

try {
    $stmt = $pdo->prepare("
        DELETE FROM utilisateur_competences 
        WHERE utilisateur_id = ? AND competence_id = ?
    ");
    
    $stmt->execute([$utilisateur_id, $competence_id]);
    
    header('Location: ../skills.php?success=Skill removed successfully');
    exit();
    
} catch (PDOException $e) {
    error_log("Error removing skill: " . $e->getMessage());
    header('Location: ../skills.php?error=Failed to remove skill');
    exit();
}
?>