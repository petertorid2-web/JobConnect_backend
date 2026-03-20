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

try {
    // Vérifier que l'expérience appartient bien à l'utilisateur
    $stmt = $pdo->prepare("SELECT id FROM experiences_professionnelles WHERE id = ? AND utilisateur_id = ?");
    $stmt->execute([$experience_id, $utilisateur_id]);
    
    if (!$stmt->fetch()) {
        header('Location: ../experiences.php?error=Experience record not found');
        exit();
    }
    
    $stmt = $pdo->prepare("DELETE FROM experiences_professionnelles WHERE id = ?");
    $stmt->execute([$experience_id]);
    
    header('Location: ../experiences.php?success=Experience deleted successfully');
    exit();
    
} catch (PDOException $e) {
    error_log("Error deleting experience: " . $e->getMessage());
    header('Location: ../experiences.php?error=Failed to delete experience');
    exit();
}
?>