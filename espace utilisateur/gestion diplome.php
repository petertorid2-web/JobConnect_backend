<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';

checkCandidatAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../education.php');
    exit();
}

$utilisateur_id = $_SESSION['utilisateur_id'];

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
    $stmt = $pdo->prepare("
        INSERT INTO diplomes (utilisateur_id, intitule, etablissement, niveau_etude, domaine, date_obtention, description)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $utilisateur_id,
        $intitule,
        $etablissement ?: null,
        $niveau_etude ?: null,
        $domaine ?: null,
        $date_obtention ?: null,
        $description ?: null
    ]);
    
    header('Location: ../education.php?success=Education added successfully');
    exit();
    
} catch (PDOException $e) {
    error_log("Error adding education: " . $e->getMessage());
    header('Location: ../education.php?error=Failed to add education');
    exit();
}
?>