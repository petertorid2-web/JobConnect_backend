<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Vérifier que l'utilisateur est authentifié
checkCandidatAuth();

// Vérifier que c'est bien une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../profile.php');
    exit();
}

// Vérifier que la connexion PDO est établie
if (!isset($pdo) || !$pdo) {
    error_log("Database connection not established");
    header('Location: ../profile.php?error=Database connection error');
    exit();
}

// Vérifier que l'utilisateur_id existe dans la session
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../login.php');
    exit();
}

$utilisateur_id = $_SESSION['utilisateur_id'];

// Récupérer et nettoyer les données
$telephone = trim($_POST['telephone'] ?? '');
$adresse = trim($_POST['adresse'] ?? '');
$ville = trim($_POST['ville'] ?? '');
$date_naissance = $_POST['date_naissance'] ?? '';

// Validation du téléphone (optionnel mais recommandé)
if (!empty($telephone) && !preg_match('/^[0-9+\-\s()]{8,20}$/', $telephone)) {
    header('Location: ../profile.php?error=Invalid phone number format');
    exit();
}

// Validation de la date de naissance
if (!empty($date_naissance)) {
    $date_obj = DateTime::createFromFormat('Y-m-d', $date_naissance);
    if (!$date_obj || $date_obj->format('Y-m-d') !== $date_naissance) {
        header('Location: ../profile.php?error=Invalid date format');
        exit();
    }
    
    // Vérifier que l'utilisateur a au moins 16 ans
    $today = new DateTime();
    $birthdate = new DateTime($date_naissance);
    $age = $today->diff($birthdate)->y;
    
    if ($age < 16) {
        header('Location: ../profile.php?error=You must be at least 16 years old');
        exit();
    }
    
    if ($age > 120) {
        header('Location: ../profile.php?error=Invalid birth date');
        exit();
    }
}

// Construire l'adresse complète
$adresse_complete = $adresse;
if (!empty($ville)) {
    $adresse_complete = $adresse_complete ? $adresse_complete . ', ' . $ville : $ville;
}
$adresse_complete = $adresse_complete ?: null;

try {
    // Vérifier si l'utilisateur existe
    $check_user = $pdo->prepare("SELECT id FROM utilisateurs WHERE id = ?");
    $check_user->execute([$utilisateur_id]);
    
    if (!$check_user->fetch()) {
        header('Location: ../login.php?error=User not found');
        exit();
    }
    
    // Mettre à jour le profil
    $stmt = $pdo->prepare("
        UPDATE utilisateurs 
        SET telephone = ?, 
            adresse = ?, 
            date_naissance = ?
        WHERE id = ?
    ");
    
    $result = $stmt->execute([
        !empty($telephone) ? $telephone : null,
        $adresse_complete,
        !empty($date_naissance) ? $date_naissance : null,
        $utilisateur_id
    ]);
    
    if ($result) {
        // Optionnel: Journaliser la mise à jour
        error_log("Profile updated successfully for user ID: " . $utilisateur_id);
        header('Location: ../profile.php?success=' . urlencode('Profile updated successfully'));
    } else {
        throw new Exception("Failed to execute update query");
    }
    
    exit();
    
} catch (PDOException $e) {
    error_log("Database error updating profile for user {$utilisateur_id}: " . $e->getMessage());
    header('Location: ../profile.php?error=' . urlencode('Failed to update profile. Please try again.'));
    exit();
} catch (Exception $e) {
    error_log("Error updating profile for user {$utilisateur_id}: " . $e->getMessage());
    header('Location: ../profile.php?error=' . urlencode('An error occurred. Please try again.'));
    exit();
}
?>