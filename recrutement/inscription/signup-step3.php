<?php
require_once '../config/database.php';

session_start();

if (!isset($_SESSION['signup_data'])) {
    header('Location: ../signup.php?step=1');
    exit();
}

$educations = $_POST['educations'] ?? [];

// Filtrer les éducations vides
$valid_educations = array_filter($educations, function($edu) {
    return !empty($edu['intitule']) || !empty($edu['etablissement']);
});

$_SESSION['signup_data']['educations'] = $valid_educations;

header('Location: ../signup.php?step=4');
exit();
?>