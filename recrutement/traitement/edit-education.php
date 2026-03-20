<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';

checkCandidatAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../education.php');
    exit();
}

$utilisateur_id = $_SESSION['utilisateur_id'];
$diplome_id = $_POST['diplome_id'] ?? 0;

$intitule = trim($_POST['intitule'] ?? '');
$etablissement = trim($_POST['etablissement'] ?? '');
$niveau_etude = $_POST['niveau_etude'] ?? '';
$domaine = trim($_POST['domaine'] ?? '');
$date_obtention = $_POST['date_obtention'] ?? '';
$description = trim($_POST['description'] ?? '');

if (empty($intitule)) {
    header('Location: ../education.php?error=Please enter the diploma title');
    exit();
}

try {
    // Vérifier que le diplôme appartient bien à l'utilisateur
    $stmt = $pdo->prepare("SELECT id FROM diplomes WHERE id = ? AND utilisateur_id = ?");
    $stmt->execute([$diplome_id, $utilisateur_id]);
    
    if (!$stmt->fetch()) {
        header('Location: ../education.php?error=Education record not found');
        exit();
    }
    
    $stmt = $pdo->prepare("
        UPDATE diplomes 
        SET intitule = ?, etablissement = ?, niveau_etude = ?, domaine = ?, date_obtention = ?, description = ?
        WHERE id = ?
    ");
    
    $stmt->execute([
        $intitule,
        $etablissement ?: null,
        $niveau_etude ?: null,
        $domaine ?: null,
        $date_obtention ?: null,
        $description ?: null,
        $diplome_id
    ]);
    
    header('Location: ../education.php?success=Education updated successfully');
    exit();
    
} catch (PDOException $e) {
    error_log("Error updating education: " . $e->getMessage());
    header('Location: ../education.php?error=Failed to update education');
    exit();
}
?>