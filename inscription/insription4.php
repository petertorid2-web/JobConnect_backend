<?php
require_once '../config/database.php';

session_start();

if (!isset($_SESSION['signup_data'])) {
    header('Location: ../signup.php?step=1');
    exit();
}

$experiences = $_POST['experiences'] ?? [];
$mot_de_passe = $_POST['mot_de_passe'] ?? '';
$confirmer_mot_de_passe = $_POST['confirmer_mot_de_passe'] ?? '';

// Validation
if (empty($mot_de_passe) || strlen($mot_de_passe) < 6) {
    header('Location: ../signup.php?step=4&error=Password must be at least 6 characters');
    exit();
}

if ($mot_de_passe !== $confirmer_mot_de_passe) {
    header('Location: ../signup.php?step=4&error=Passwords do not match');
    exit();
}

// Filtrer les expériences vides
$valid_experiences = array_filter($experiences, function($exp) {
    return !empty($exp['nom_entreprise']) || !empty($exp['titre_poste']);
});

$data = $_SESSION['signup_data'];

try {
    $pdo->beginTransaction();
    
    // Insérer l'utilisateur
    $stmt = $pdo->prepare("
        INSERT INTO utilisateurs (nom, prenom, genre, email, telephone, adresse, mot_de_passe, date_naissance, est_employe)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $date_naissance = $data['date_naissance'] ?? date('Y-m-d');
    $telephone = $data['telephone1'] ?? '';
    $adresse = ($data['adresse'] ?? '') . ' ' . ($data['ville'] ?? '');
    
    $stmt->execute([
        $data['nom'],
        $data['prenom'],
        $data['genre'],
        $data['email'],
        $telephone,
        $adresse,
        password_hash($mot_de_passe, PASSWORD_DEFAULT),
        $date_naissance,
        false
    ]);
    
    $utilisateur_id = $pdo->lastInsertId();
    
    // Insérer les diplômes
    if (!empty($data['educations'])) {
        $stmt = $pdo->prepare("
            INSERT INTO diplomes (utilisateur_id, intitule, etablissement, date_obtention)
            VALUES (?, ?, ?, ?)
        ");
        
        foreach ($data['educations'] as $edu) {
            if (!empty($edu['intitule'])) {
                $stmt->execute([
                    $utilisateur_id,
                    $edu['intitule'],
                    $edu['etablissement'] ?? '',
                    $edu['date_obtention'] ?? null
                ]);
            }
        }
    }
    
    // Insérer les expériences
    if (!empty($valid_experiences)) {
        $stmt = $pdo->prepare("
            INSERT INTO experiences_professionnelles (utilisateur_id, titre_poste, nom_entreprise, date_debut, date_fin, description)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($valid_experiences as $exp) {
            $stmt->execute([
                $utilisateur_id,
                $exp['titre_poste'] ?? '',
                $exp['nom_entreprise'] ?? '',
                $exp['date_debut'] ?? null,
                $exp['date_fin'] ?? null,
                $exp['description'] ?? ''
            ]);
        }
    }
    
    // Gérer l'upload des documents
    if (!empty($_FILES['documents']['name'][0])) {
        $upload_dir = 'cvs';
        foreach ($_FILES['documents']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['documents']['error'][$key] === 0) {
                $file = [
                    'name' => $_FILES['documents']['name'][$key],
                    'tmp_name' => $tmp_name,
                    'error' => $_FILES['documents']['error'][$key]
                ];
                
                $result = uploadFile($file, $upload_dir);
                if ($result['success']) {
                    // Sauvegarder le chemin du CV dans la base de données
                    $stmt = $pdo->prepare("UPDATE utilisateurs SET cv_path = ? WHERE id = ?");
                    $stmt->execute([$result['file_name'], $utilisateur_id]);
                }
            }
        }
    }
    
    $pdo->commit();
    
    // Envoyer un email de bienvenue
    sendEmail($data['email'], 'Welcome to JobConnect', 
        "Hello {$data['prenom']},\n\nThank you for registering on JobConnect. Your account has been created successfully.\n\nYou can now log in and start your job search.\n\nBest regards,\nThe JobConnect Team");
    
    // Nettoyer la session
    unset($_SESSION['signup_data']);
    
    // Rediriger vers la page de connexion
    header('Location: ../login.php?success=Account created successfully! You can now log in.');
    exit();
    
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Signup error: " . $e->getMessage());
    header('Location: ../signup.php?step=4&error=An error occurred during registration');
    exit();
}
?>