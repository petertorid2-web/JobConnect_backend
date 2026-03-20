<?php
require_once '../config/database.php';

session_start();

if (!isset($_SESSION['signup_data'])) {
    header('Location: ../signup.php?step=1');
    exit();
}

// Vérifier que la connexion PDO est bien établie
if (!isset($pdo) || !$pdo) {
    error_log("Database connection not established");
    header('Location: ../signup.php?step=4&error=Database connection error');
    exit();
}

$experiences = $_POST['experiences'] ?? [];
$mot_de_passe = $_POST['mot_de_passe'] ?? '';
$confirmer_mot_de_passe = $_POST['confirmer_mot_de_passe'] ?? '';

// Validation du mot de passe
if (empty($mot_de_passe) || strlen($mot_de_passe) < 6) {
    header('Location: ../signup.php?step=4&error=Password must be at least 6 characters');
    exit();
}

if ($mot_de_passe !== $confirmer_mot_de_passe) {
    header('Location: ../signup.php?step=4&error=Passwords do not match');
    exit();
}

// Validation de l'email
if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    header('Location: ../signup.php?step=4&error=Invalid email address');
    exit();
}

// Validation du genre
$allowed_genres = ['homme', 'femme', 'autre'];
$genre = in_array($data['genre'] ?? '', $allowed_genres) ? $data['genre'] : 'autre';

// Filtrer les expériences vides
$valid_experiences = array_filter($experiences, function($exp) {
    return !empty($exp['nom_entreprise']) || !empty($exp['titre_poste']);
});

$data = $_SESSION['signup_data'];

// Définir les fonctions d'upload et d'email
function uploadFile($file, $upload_dir) {
    // Vérifier et créer le dossier s'il n'existe pas
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            return ['success' => false, 'error' => 'Unable to create upload directory'];
        }
    }
    
    // Vérifier les erreurs d'upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'File upload error: ' . $file['error']];
    }
    
    // Vérifier la taille du fichier (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'error' => 'File size must be less than 5MB'];
    }
    
    // Vérifier l'extension
    $allowed_extensions = ['pdf', 'doc', 'docx'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowed_extensions)) {
        return ['success' => false, 'error' => 'Only PDF, DOC, and DOCX files are allowed'];
    }
    
    // Générer un nom de fichier unique
    $file_name = uniqid() . '_' . time() . '.' . $extension;
    $target_path = $upload_dir . '/' . $file_name;
    
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        return ['success' => true, 'file_name' => $file_name];
    }
    
    return ['success' => false, 'error' => 'Failed to move uploaded file'];
}

function sendEmail($to, $subject, $message) {
    // Configuration des headers
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "From: JobConnect <noreply@jobconnect.com>\r\n";
    $headers .= "Reply-To: support@jobconnect.com\r\n";
    
    // Envoyer l'email
    return mail($to, $subject, $message, $headers);
}

try {
    $pdo->beginTransaction();
    
    // Vérifier si l'email existe déjà
    $check_email = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
    $check_email->execute([$data['email']]);
    if ($check_email->fetch()) {
        throw new Exception("Email already exists");
    }
    
    // Insérer l'utilisateur
    $stmt = $pdo->prepare("
        INSERT INTO utilisateurs (nom, prenom, genre, email, telephone, adresse, mot_de_passe, date_naissance, est_employe)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    // Gérer la date de naissance (NULL si non fournie)
    $date_naissance = !empty($data['date_naissance']) ? $data['date_naissance'] : null;
    $telephone = $data['telephone1'] ?? '';
    $adresse = trim(($data['adresse'] ?? '') . ' ' . ($data['ville'] ?? ''));
    
    $stmt->execute([
        $data['nom'] ?? '',
        $data['prenom'] ?? '',
        $genre,
        $data['email'],
        $telephone,
        $adresse,
        password_hash($mot_de_passe, PASSWORD_DEFAULT),
        $date_naissance,
        0  // est_employe = false
    ]);
    
    $utilisateur_id = $pdo->lastInsertId();
    
    // Insérer les diplômes
    if (!empty($data['educations']) && is_array($data['educations'])) {
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
                    !empty($edu['date_obtention']) ? $edu['date_obtention'] : null
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
                !empty($exp['date_debut']) ? $exp['date_debut'] : null,
                !empty($exp['date_fin']) ? $exp['date_fin'] : null,
                $exp['description'] ?? ''
            ]);
        }
    }
    
    // Gérer l'upload des documents (CV uniquement)
    if (isset($_FILES['documents']) && !empty($_FILES['documents']['name'][0])) {
        $upload_dir = '../uploads/cvs';
        
        // Créer le dossier s'il n'existe pas
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Ne prendre que le premier CV (ou gérer plusieurs CVs si nécessaire)
        for ($i = 0; $i < count($_FILES['documents']['name']); $i++) {
            if ($_FILES['documents']['error'][$i] === UPLOAD_ERR_OK) {
                $file = [
                    'name' => $_FILES['documents']['name'][$i],
                    'tmp_name' => $_FILES['documents']['tmp_name'][$i],
                    'error' => $_FILES['documents']['error'][$i],
                    'size' => $_FILES['documents']['size'][$i]
                ];
                
                $result = uploadFile($file, $upload_dir);
                if ($result['success']) {
                    // Si tu veux stocker plusieurs CVs, il faudrait une table séparée
                    // Pour l'instant, on garde seulement le dernier
                    $update_cv = $pdo->prepare("UPDATE utilisateurs SET cv_path = ? WHERE id = ?");
                    $update_cv->execute([$result['file_name'], $utilisateur_id]);
                } else {
                    error_log("CV upload error: " . $result['error']);
                }
            }
        }
    }
    
    $pdo->commit();
    
    // Envoyer un email de bienvenue
    $subject = "Welcome to JobConnect";
    $message = "Hello " . ($data['prenom'] ?? 'User') . ",\n\n" .
               "Thank you for registering on JobConnect. Your account has been created successfully.\n\n" .
               "Login credentials:\n" .
               "Email: " . $data['email'] . "\n" .
               "Password: [the password you chose]\n\n" .
               "You can now log in and start your job search.\n\n" .
               "Best regards,\n" .
               "The JobConnect Team";
    
    // Envoyer l'email (optionnel, ne pas bloquer si échec)
    if (!sendEmail($data['email'], $subject, $message)) {
        error_log("Failed to send welcome email to: " . $data['email']);
    }
    
    // Nettoyer la session
    unset($_SESSION['signup_data']);
    
    // Rediriger vers la page de connexion
    header('Location: ../login.php?success=' . urlencode('Account created successfully! You can now log in.'));
    exit();
    
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Signup PDO error: " . $e->getMessage());
    header('Location: ../signup.php?step=4&error=' . urlencode('An error occurred during registration. Please try again.'));
    exit();
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Signup error: " . $e->getMessage());
    header('Location: ../signup.php?step=4&error=' . urlencode($e->getMessage()));
    exit();
}
?>