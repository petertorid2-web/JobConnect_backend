<?php
require_once '../config/database.php';

session_start();

if (!isset($_SESSION['signup_data'])) {
    header('Location: ../signup.php?step=1');
    exit();
}

$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
$adresse = trim($_POST['adresse'] ?? '');
$ville = trim($_POST['ville'] ?? '');
$telephone1 = trim($_POST['telephone1'] ?? '');
$telephone2 = trim($_POST['telephone2'] ?? '');

// Validation
if (empty($email) || empty($telephone1)) {
    header('Location: ../signup.php?step=2&error=Please fill required fields');
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../signup.php?step=2&error=Invalid email format');
    exit();
}

// Vérifier si l'email existe déjà
try {
    $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        header('Location: ../signup.php?step=2&error=Email already exists');
        exit();
    }
    
    $stmt = $pdo->prepare("SELECT id FROM entreprises WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        header('Location: ../signup.php?step=2&error=Email already exists');
        exit();
    }
    
} catch (PDOException $e) {
    error_log("Error: " . $e->getMessage());
    header('Location: ../signup.php?step=2&error=An error occurred');
    exit();
}

// Mettre à jour la session
$_SESSION['signup_data'] = array_merge($_SESSION['signup_data'], [
    'email' => $email,
    'adresse' => $adresse,
    'ville' => $ville,
    'telephone1' => $telephone1,
    'telephone2' => $telephone2
]);

header('Location: ../signup.php?step=3');
exit();
?>