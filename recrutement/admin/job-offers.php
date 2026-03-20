<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
checkAdminAuth();

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    $sql = "SELECT o.*, e.nom_entreprise 
            FROM offres_emploi o
            LEFT JOIN entreprises e ON o.entreprise_id = e.id
            WHERE 1=1";
    
    $params = [];
    
    if (!empty($search)) {
        $sql .= " AND (o.titre LIKE ? OR e.nom_entreprise LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    // Compter total
    $count_sql = str_replace("o.*, e.nom_entreprise", "COUNT(*)", $sql);
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_jobs = $stmt->fetchColumn();
    
    // Pagination
    $sql .= " ORDER BY o.date_publication DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $jobs = $stmt->fetchAll();
    
    $total_pages = ceil($total_jobs / $limit);
    
} catch (PDOException $e) {
    error_log("Error: " . $e->getMessage());
    $jobs = [];
    $total_pages = 1;
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
            <h1>Job Offers Management</h1>
            <p>Manage all job offers on the platform</p>
        </div>
        
        <div class="search-bar">
            <form action="" method="GET" class="search-form">
                <i class="fas fa-search"></i>
                <input type="text" name="search" placeholder="Search by job title or company..." 
                       value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn btn-primary">Search</button>
                <?php if (!empty($search)): ?>
                    <a href="job-offers.php" class="btn btn-outline">Clear</a>
                <?php endif; ?>
            </form>
        </div>
        
        <div class="table-container">
            <p class="results-info">Showing <?= count($jobs) ?> of <?= $total_jobs ?> job offers</p>
            
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Company</th>
                        <th>Type</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Posted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($jobs as $job): 
                        $est_active = $job['actif'] && (is_null($job['date_expiration']) || $job['date_expiration'] >= date('Y-m-d'));
                    ?>
                        <tr>
                            <td><?= $job['id'] ?></td>
                            <td><?= htmlspecialchars($job['titre']) ?></td>
                            <td><?= htmlspecialchars($job['nom_entreprise'] ?? '—') ?></td>
                            <td><?= $job['type_contrat'] ?></td>
                            <td><?= htmlspecialchars($job['localisation'] ?? '—') ?></td>
                            <td>
                                <span class="status-badge <?= $est_active ? 'status-active' : 'status-inactive' ?>">
                                    <?= $est_active ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td><?= date('d/m/Y', strtotime($job['date_publication'])) ?></td>
                            <td>
                                <a href="edit-job.php?id=<?= $job['id'] ?>" class="btn-icon">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button class="btn-icon btn-danger" onclick="deleteJob(<?= $job['id'] ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>" 
                           class="page-link <?= $i == $page ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function deleteJob(id) {
    if (confirm('Are you sure you want to delete this job offer?')) {
        window.location.href = 'delete-job.php?id=' + id;
    }
}
</script>

<?php include '../includes/footer.php'; ?>