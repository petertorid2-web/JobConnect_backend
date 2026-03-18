<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';

checkCandidatAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../profile.php');
    exit();
}

$utilisateur_id = $_SESSION['utilisateur_id'];

$telephone = trim($_POST['telephone'] ?? '');
$adresse = trim($_POST['adresse'] ?? '');
$ville = trim($_POST['ville'] ?? '');
$date_naissance = $_POST['date_naissance'] ?? '';

try {
    $stmt = $pdo->prepare("
        UPDATE utilisateurs 
        SET telephone = ?, adresse = ?, date_naissance = ?
        WHERE id = ?
    ");
    
    $stmt->execute([
        $telephone ?: null,
        $adresse ?: null,
        $date_naissance ?: null,
        $utilisateur_id
    ]);
    
    header('Location: ../profile.php?success=Profile updated successfully');
    exit();
    
} catch (PDOException $e) {
    error_log("Error updating profile: " . $e->getMessage());
    header('Location: ../profile.php?error=Failed to update profile');
    exit();
}
?>