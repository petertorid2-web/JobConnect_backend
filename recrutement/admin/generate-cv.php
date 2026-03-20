<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
checkCandidatAuth();

$utilisateur_id = $_SESSION['utilisateur_id'];

try {
    // Récupérer toutes les informations de l'utilisateur
    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ?");
    $stmt->execute([$utilisateur_id]);
    $user = $stmt->fetch();
    
    // Récupérer les diplômes
    $stmt = $pdo->prepare("SELECT * FROM diplomes WHERE utilisateur_id = ? ORDER BY date_obtention DESC");
    $stmt->execute([$utilisateur_id]);
    $educations = $stmt->fetchAll();
    
    // Récupérer les expériences
    $stmt = $pdo->prepare("
        SELECT * FROM experiences_professionnelles 
        WHERE utilisateur_id = ? 
        ORDER BY date_debut DESC
    ");
    $stmt->execute([$utilisateur_id]);
    $experiences = $stmt->fetchAll();
    
    // Récupérer les compétences
    $stmt = $pdo->prepare("
        SELECT c.nom, uc.niveau 
        FROM competences c
        JOIN utilisateur_competences uc ON c.id = uc.competence_id
        WHERE uc.utilisateur_id = ?
        ORDER BY c.categorie, c.nom
    ");
    $stmt->execute([$utilisateur_id]);
    $skills = $stmt->fetchAll();
    
    // Créer le contenu HTML du CV
    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>CV - <?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></title>
        <style>
            body {
                font-family: 'Arial', sans-serif;
                line-height: 1.6;
                color: #333;
                max-width: 800px;
                margin: 0 auto;
                padding: 40px;
            }
            .header {
                text-align: center;
                border-bottom: 2px solid #3498db;
                padding-bottom: 20px;
                margin-bottom: 30px;
            }
            .name {
                font-size: 32px;
                color: #2c3e50;
                margin: 0;
            }
            .title {
                font-size: 18px;
                color: #7f8c8d;
                margin-top: 5px;
            }
            .contact {
                margin-top: 15px;
                color: #7f8c8d;
            }
            .section {
                margin-bottom: 30px;
            }
            .section-title {
                font-size: 20px;
                color: #3498db;
                border-bottom: 1px solid #ddd;
                padding-bottom: 5px;
                margin-bottom: 15px;
            }
            .experience-item, .education-item {
                margin-bottom: 20px;
            }
            .item-title {
                font-weight: bold;
                font-size: 16px;
                margin: 0;
            }
            .item-subtitle {
                color: #7f8c8d;
                margin: 5px 0;
            }
            .item-date {
                color: #95a5a6;
                font-size: 14px;
            }
            .skill-tag {
                display: inline-block;
                background: #ecf0f1;
                padding: 5px 10px;
                margin: 5px;
                border-radius: 3px;
                font-size: 14px;
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h1 class="name"><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></h1>
            <div class="contact">
                <div>Email: <?= htmlspecialchars($user['email']) ?></div>
                <?php if ($user['telephone']): ?>
                    <div>Tél: <?= htmlspecialchars($user['telephone']) ?></div>
                <?php endif; ?>
                <?php if ($user['adresse']): ?>
                    <div>Adresse: <?= htmlspecialchars($user['adresse']) ?></div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (!empty($experiences)): ?>
        <div class="section">
            <h2 class="section-title">Expériences Professionnelles</h2>
            <?php foreach ($experiences as $exp): ?>
                <div class="experience-item">
                    <h3 class="item-title"><?= htmlspecialchars($exp['titre_poste']) ?></h3>
                    <div class="item-subtitle"><?= htmlspecialchars($exp['nom_entreprise']) ?></div>
                    <div class="item-date">
                        <?= date('M Y', strtotime($exp['date_debut'])) ?> - 
                        <?= $exp['est_poste_actuel'] ? 'Présent' : date('M Y', strtotime($exp['date_fin'])) ?>
                    </div>
                    <?php if ($exp['description']): ?>
                        <p><?= nl2br(htmlspecialchars($exp['description'])) ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($educations)): ?>
        <div class="section">
            <h2 class="section-title">Formations</h2>
            <?php foreach ($educations as $edu): ?>
                <div class="education-item">
                    <h3 class="item-title"><?= htmlspecialchars($edu['intitule']) ?></h3>
                    <div class="item-subtitle"><?= htmlspecialchars($edu['etablissement']) ?></div>
                    <?php if ($edu['date_obtention']): ?>
                        <div class="item-date">Obtenu en <?= date('M Y', strtotime($edu['date_obtention'])) ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($skills)): ?>
        <div class="section">
            <h2 class="section-title">Compétences</h2>
            <div>
                <?php foreach ($skills as $skill): ?>
                    <span class="skill-tag">
                        <?= htmlspecialchars($skill['nom']) ?> (<?= $skill['niveau'] ?>)
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </body>
    </html>
    <?php
    $html_content = ob_get_clean();
    
    // Convertir en PDF avec dompdf (si installé)
    // require_once '../vendor/autoload.php';
    // $dompdf = new Dompdf();
    // $dompdf->loadHtml($html_content);
    // $dompdf->setPaper('A4', 'portrait');
    // $dompdf->render();
    // $dompdf->stream("cv_" . $user['prenom'] . "_" . $user['nom'] . ".pdf");
    
    // Pour l'instant, on affiche le HTML
    echo $html_content;
    exit();
    
} catch (PDOException $e) {
    error_log("Error generating CV: " . $e->getMessage());
    header('Location: ../my-space.php?error=Failed to generate CV');
    exit();
}
?>