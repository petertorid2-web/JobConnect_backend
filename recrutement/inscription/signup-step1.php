<?php
require_once '../config/database.php';

session_start();

// Récupérer les données du formulaire
$genre = $_POST['genre'] ?? '';
$prenom = trim($_POST['prenom'] ?? '');
$nom = trim($_POST['nom'] ?? '');
$nationalite = trim($_POST['nationalite'] ?? '');
$id_type = $_POST['id_type'] ?? '';
$id_number = trim($_POST['id_number'] ?? '');
$status_id_key = trim($_POST['status_id_key'] ?? '');

// Validation
if (empty($genre) || empty($prenom) || empty($nom)) {
    header('Location: ../signup.php?step=1&error=Please fill required fields');
    exit();
}

// Stocker dans la session
$_SESSION['signup_data'] = [
    'genre' => $genre,
    'prenom' => $prenom,
    'nom' => $nom,
    'nationalite' => $nationalite,
    'id_type' => $id_type,
    'id_number' => $id_number,
    'status_id_key' => $status_id_key
];

// Passer à l'étape 2
header('Location: ../signup.php?step=2');
exit();
?>