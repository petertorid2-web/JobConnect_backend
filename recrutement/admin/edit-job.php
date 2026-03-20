<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
checkAdminAuth();

$job_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$job_id) {
    header('Location: job-offers.php');
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT * FROM offres_emploi WHERE id = ?");
    $stmt->execute([$job_id]);
    $job = $stmt->fetch();
    
    if (!$job) {
        header('Location: job-offers.php?error=Job not found');
        exit();
    }
    
    // Récupérer les entreprises pour le select
    $stmt = $pdo->query("SELECT id, nom_entreprise FROM entreprises ORDER BY nom_entreprise");
    $entreprises = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Error: " . $e->getMessage());
    header('Location: job-offers.php?error=An error occurred');
    exit();
}

include '../includes/header.php';
?>

<div class="admin-layout">
    <div class="admin-sidebar">
        <h2>JobConnect</h2>
        <nav class="admin-nav">
            <ul>
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="employed-users.php"><i class="fas fa-briefcase"></i> Employed Users</a></li>
                <li><a href="unemployed-users.php"><i class="fas fa-user-clock"></i> Unemployed Users</a></li>
                <li><a href="job-offers.php" class="active"><i class="fas fa-briefcase"></i> Job Offers</a></li>
                <li><a href="statistiques.php"><i class="fas fa-chart-bar"></i> Statistics</a></li>
            </ul>
        </nav>
        <a href="../logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
    
    <div class="admin-content">
        <div class="content-header">
            <h1>Edit Job Offer</h1>
            <p>Update job offer details</p>
        </div>
        
        <div class="form-card">
            <form action="update-job.php" method="POST" class="admin-form">
                <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                
                <div class="form-group">
                    <label for="entreprise_id">Company *</label>
                    <select id="entreprise_id" name="entreprise_id" required>
                        <option value="">Select company</option>
                        <?php foreach ($entreprises as $entreprise): ?>
                            <option value="<?= $entreprise['id'] ?>" <?= $job['entreprise_id'] == $entreprise['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($entreprise['nom_entreprise']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="titre">Job Title *</label>
                    <input type="text" id="titre" name="titre" value="<?= htmlspecialchars($job['titre']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description *</label>
                    <textarea id="description" name="description" rows="10" required><?= htmlspecialchars($job['description']) ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="type_contrat">Contract Type *</label>
                        <select id="type_contrat" name="type_contrat" required>
                            <option value="CDI" <?= $job['type_contrat'] == 'CDI' ? 'selected' : '' ?>>CDI</option>
                            <option value="CDD" <?= $job['type_contrat'] == 'CDD' ? 'selected' : '' ?>>CDD</option>
                            <option value="Stage" <?= $job['type_contrat'] == 'Stage' ? 'selected' : '' ?>>Stage</option>
                            <option value="Alternance" <?= $job['type_contrat'] == 'Alternance' ? 'selected' : '' ?>>Alternance</option>
                            <option value="Freelance" <?= $job['type_contrat'] == 'Freelance' ? 'selected' : '' ?>>Freelance</option>
                            <option value="Temps partiel" <?= $job['type_contrat'] == 'Temps partiel' ? 'selected' : '' ?>>Temps partiel</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="localisation">Location</label>
                        <input type="text" id="localisation" name="localisation" value="<?= htmlspecialchars($job['localisation']) ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="salaire_min">Min Salary ($)</label>
                        <input type="number" id="salaire_min" name="salaire_min" value="<?= $job['salaire_min'] ?>" step="1000">
                    </div>
                    
                    <div class="form-group">
                        <label for="salaire_max">Max Salary ($)</label>
                        <input type="number" id="salaire_max" name="salaire_max" value="<?= $job['salaire_max'] ?>" step="1000">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="date_expiration">Expiration Date</label>
                        <input type="date" id="date_expiration" name="date_expiration" value="<?= $job['date_expiration'] ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="actif">Status</label>
                        <select id="actif" name="actif">
                            <option value="1" <?= $job['actif'] ? 'selected' : '' ?>>Active</option>
                            <option value="0" <?= !$job['actif'] ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Job</button>
                    <a href="job-offers.php" class="btn btn-outline">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>