<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
checkAdminAuth();

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    $sql = "SELECT u.*, ic.est_employe 
            FROM utilisateurs u
            LEFT JOIN informations_civiles ic ON u.id = ic.utilisateur_id
            WHERE u.est_employe = 0";
    
    $params = [];
    
    if (!empty($search)) {
        $sql .= " AND (u.nom LIKE ? OR u.prenom LIKE ? OR u.email LIKE ? OR u.telephone LIKE ? OR u.adresse LIKE ?)";
        $search_param = "%$search%";
        $params = array_fill(0, 5, $search_param);
    }
    
    // Compter total
    $count_sql = str_replace("u.*, ic.est_employe", "COUNT(*)", $sql);
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_users = $stmt->fetchColumn();
    
    // Pagination
    $sql .= " ORDER BY u.nom, u.prenom LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll();
    
    $total_pages = ceil($total_users / $limit);
    
} catch (PDOException $e) {
    error_log("Error: " . $e->getMessage());
    $users = [];
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
                <li><a href="unemployed-users.php" class="active"><i class="fas fa-user-clock"></i> Unemployed Users</a></li>
                <li><a href="job-offers.php"><i class="fas fa-briefcase"></i> Job Offers</a></li>
                <li><a href="statistiques.php"><i class="fas fa-chart-bar"></i> Statistics</a></li>
            </ul>
        </nav>
        
        <a href="../logout.php" class="btn-logout">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
    
    <div class="admin-content">
        <div class="content-header">
            <h1>Unemployed Users</h1>
            <p>Manage and view all users currently seeking employment</p>
        </div>
        
        <div class="search-bar">
            <form action="" method="GET" class="search-form">
                <i class="fas fa-search"></i>
                <input type="text" name="search" placeholder="Search by name, email, city, or phone..." 
                       value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn btn-primary">Search</button>
                <?php if (!empty($search)): ?>
                    <a href="unemployed-users.php" class="btn btn-outline">Clear</a>
                <?php endif; ?>
            </form>
        </div>
        
        <div class="table-container">
            <p class="results-info">Showing <?= count($users) ?> of <?= $total_users ?> unemployed users</p>
            
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Gender</th>
                        <th>Phone</th>
                        <th>City</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= ucfirst($user['genre'] ?? 'Not specified') ?></td>
                            <td><?= htmlspecialchars($user['telephone'] ?? '—') ?></td>
                            <td><?= htmlspecialchars(explode(',', $user['adresse'] ?? '')[0] ?? '—') ?></td>
                            <td>
                                <span class="status-badge status-active">Active</span>
                            </td>
                            <td>
                                <a href="view-user.php?id=<?= $user['id'] ?>" class="btn-icon">
                                    <i class="fas fa-eye"></i>
                                </a>
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

<?php include '../includes/footer.php'; ?>