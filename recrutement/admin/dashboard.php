<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
checkAdminAuth();

try {
    // Statistiques globales
    $stats = [];
    
    // Total utilisateurs
    $stmt = $pdo->query("SELECT COUNT(*) FROM utilisateurs");
    $stats['total_users'] = $stmt->fetchColumn();
    
    // Utilisateurs précédents (mois dernier)
    $stmt = $pdo->query("
        SELECT COUNT(*) FROM utilisateurs 
        WHERE date_inscription < DATE_SUB(NOW(), INTERVAL 1 MONTH)
    ");
    $stats['previous_users'] = $stmt->fetchColumn();
    
    // Employés
    $stmt = $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE est_employe = 1");
    $stats['employed'] = $stmt->fetchColumn();
    
    // Sans emploi
    $stmt = $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE est_employe = 0");
    $stats['unemployed'] = $stmt->fetchColumn();
    
    // Répartition par genre
    $stmt = $pdo->query("
        SELECT genre, COUNT(*) as count 
        FROM utilisateurs 
        GROUP BY genre
    ");
    $gender_stats = $stmt->fetchAll();
    
    // Offres d'emploi
    $stmt = $pdo->query("SELECT COUNT(*) FROM offres_emploi");
    $stats['total_jobs'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("
        SELECT COUNT(*) FROM offres_emploi 
        WHERE actif = 1 AND (date_expiration IS NULL OR date_expiration >= CURDATE())
    ");
    $stats['active_jobs'] = $stmt->fetchColumn();
    
    // Évolution mensuelle
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(date_inscription, '%Y-%m') as month,
            COUNT(*) as count
        FROM utilisateurs
        WHERE date_inscription >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(date_inscription, '%Y-%m')
        ORDER BY month
    ");
    $monthly_trends = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Admin error: " . $e->getMessage());
    $stats = [];
    $gender_stats = [];
    $monthly_trends = [];
}

include '../includes/header.php';
?>

<div class="admin-layout">
    <div class="admin-sidebar">
        <h2>Admin Dashboard</h2>
        <nav class="admin-nav">
            <ul>
                <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="employed-users.php"><i class="fas fa-briefcase"></i> Employed Users</a></li>
                <li><a href="unemployed-users.php"><i class="fas fa-user-clock"></i> Unemployed Users</a></li>
                <li><a href="job-offers.php"><i class="fas fa-briefcase"></i> Job Offers</a></li>
                <li><a href="statistiques.php"><i class="fas fa-chart-bar"></i> Statistics</a></li>
            </ul>
        </nav>
        
        <a href="../logout.php" class="btn-logout">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
    
    <div class="admin-content">
        <h1>Admin Dashboard</h1>
        <p>Manage users, jobs, and view job information statistics</p>
        
        <div class="admin-stats-grid">
            <div class="admin-stat-card">
                <div class="stat-icon bg-primary">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-details">
                    <span class="stat-value"><?= $stats['total_users'] ?? 0 ?></span>
                    <span class="stat-label">Total Users</span>
                    <span class="stat-change positive">+12% from last month</span>
                </div>
            </div>
            
            <div class="admin-stat-card">
                <div class="stat-icon bg-secondary">
                    <i class="fas fa-user-clock"></i>
                </div>
                <div class="stat-details">
                    <span class="stat-value"><?= $stats['previous_users'] ?? 0 ?></span>
                    <span class="stat-label">Previous Users</span>
                    <span class="stat-percent"><?= $stats['total_users'] ? round(($stats['previous_users'] / $stats['total_users']) * 100, 1) : 0 ?>% of total</span>
                </div>
            </div>
            
            <div class="admin-stat-card">
                <div class="stat-icon bg-success">
                    <i class="fas fa-briefcase"></i>
                </div>
                <div class="stat-details">
                    <span class="stat-value"><?= $stats['employed'] ?? 0 ?></span>
                    <span class="stat-label">Employed</span>
                    <span class="stat-percent"><?= $stats['total_users'] ? round(($stats['employed'] / $stats['total_users']) * 100, 1) : 0 ?>% of total</span>
                </div>
            </div>
            
            <div class="admin-stat-card">
                <div class="stat-icon bg-warning">
                    <i class="fas fa-user-clock"></i>
                </div>
                <div class="stat-details">
                    <span class="stat-value"><?= $stats['unemployed'] ?? 0 ?></span>
                    <span class="stat-label">Unemployed</span>
                    <span class="stat-percent"><?= $stats['total_users'] ? round(($stats['unemployed'] / $stats['total_users']) * 100, 1) : 0 ?>% of total</span>
                </div>
            </div>
        </div>
        
        <div class="admin-charts">
            <div class="chart-card">
                <h3>Gender Distribution</h3>
                <canvas id="genderChart"></canvas>
            </div>
            
            <div class="chart-card">
                <h3>Employment Status</h3>
                <canvas id="employmentChart"></canvas>
            </div>
            
            <div class="chart-card full-width">
                <h3>Monthly Trends</h3>
                <canvas id="trendsChart"></canvas>
            </div>
        </div>
        
        <div class="job-stats-card">
            <h3>Job Offers Management</h3>
            <p class="job-stats-text">
                <?= $stats['active_jobs'] ?? 0 ?> active jobs out of <?= $stats['total_jobs'] ?? 0 ?> total
            </p>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?= $stats['total_jobs'] ? ($stats['active_jobs'] / $stats['total_jobs']) * 100 : 0 ?>%"></div>
            </div>
        </div>
    </div>
</div>

<script>
// Gender Chart
const genderCtx = document.getElementById('genderChart').getContext('2d');
new Chart(genderCtx, {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($gender_stats, 'genre')) ?>,
        datasets: [{
            label: 'Number of Users',
            data: <?= json_encode(array_column($gender_stats, 'count')) ?>,
            backgroundColor: ['#3498db', '#e74c3c', '#95a5a6']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});

// Employment Chart
const employmentCtx = document.getElementById('employmentChart').getContext('2d');
new Chart(employmentCtx, {
    type: 'bar',
    data: {
        labels: ['Employed', 'Unemployed'],
        datasets: [{
            label: 'Number of Users',
            data: [<?= $stats['employed'] ?? 0 ?>, <?= $stats['unemployed'] ?? 0 ?>],
            backgroundColor: ['#2ecc71', '#e74c3c']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});

// Trends Chart
const trendsCtx = document.getElementById('trendsChart').getContext('2d');
new Chart(trendsCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($monthly_trends, 'month')) ?>,
        datasets: [{
            label: 'New Users',
            data: <?= json_encode(array_column($monthly_trends, 'count')) ?>,
            borderColor: '#3498db',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});
</script>

<?php include '../includes/footer.php'; ?>