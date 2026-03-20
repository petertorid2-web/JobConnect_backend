<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';

checkCandidatAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../job-offers.php');
    exit();
}

$utilisateur_id = $_SESSION['utilisateur_id'];
$offre_id = $_POST['offre_id'] ?? 0;
$message = trim($_POST['message'] ?? '');

if (!$offre_id) {
    header('Location: ../job-offers.php?error=Invalid job offer');
    exit();
}

try {
    // Vérifier si l'offre existe et est active
    $stmt = $pdo->prepare("SELECT * FROM offres_emploi WHERE id = ? AND actif = 1");
    $stmt->execute([$offre_id]);
    $offre = $stmt->fetch();
    
    if (!$offre) {
        header('Location: ../job-offers.php?error=Job offer not found or inactive');
        exit();
    }
    
    // Vérifier si l'utilisateur a déjà postulé
    $stmt = $pdo->prepare("SELECT id FROM candidatures WHERE utilisateur_id = ? AND offre_id = ?");
    $stmt->execute([$utilisateur_id, $offre_id]);
    
    if ($stmt->fetch()) {
        header('Location: ../job-details.php?id=' . $offre_id . '&error=You have already applied to this job');
        exit();
    }
    
    // Gérer l'upload du CV si fourni
    $cv_path = null;
    if (!empty($_FILES['cv']['name'])) {
        $upload_result = uploadFile($_FILES['cv'], 'candidatures');
        if ($upload_result['success']) {
            $cv_path = $upload_result['file_name'];
        }
    }
    
    // Créer la candidature
    $stmt = $pdo->prepare("
        INSERT INTO candidatures (utilisateur_id, offre_id, message, cv, statut)
        VALUES (?, ?, ?, ?, 'en_attente')
    ");
    
    $stmt->execute([
        $utilisateur_id,
        $offre_id,
        $message ?: null,
        $cv_path
    ]);
    
    // Envoyer un email de confirmation
    $stmt = $pdo->prepare("SELECT email, prenom FROM utilisateurs WHERE id = ?");
    $stmt->execute([$utilisateur_id]);
    $user = $stmt->fetch();
    
    sendEmail($user['email'], 'Application Received', 
        "Dear {$user['prenom']},\n\nYour application for the position '{$offre['titre']}' has been received successfully.\n\nWe will keep you updated on the status.\n\nBest regards,\nThe JobConnect Team");
    
    header('Location: ../applications.php?success=Application submitted successfully');
    exit();
    
} catch (PDOException $e) {
    error_log("Error applying to job: " . $e->getMessage());
    header('Location: ../job-details.php?id=' . $offre_id . '&error=Failed to submit application');
    exit();
}
?>