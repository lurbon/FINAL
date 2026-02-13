<?php
/**
 * TABLEAU DE BORD DES LOGS D'AUTHENTIFICATION
 * Page d'administration pour surveiller l'activité
 * 
 * @version 1.0
 * @author Entraide Plus Iroise
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth/SessionManager.php';

// Vérifier que l'utilisateur est connecté et admin
SessionManager::init();
SessionManager::requireAuth();

// Vérifier le rôle admin
if (!SessionManager::hasfonction('admin')) {
    die('Accès refusé. Réservé aux administrateurs.');
}

// Paramètres de pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 50;
$offset = ($page - 1) * $per_page;

// Filtres
$filter_user = $_GET['user_id'] ?? '';
$filter_event = $_GET['event_type'] ?? '';
$filter_success = $_GET['success'] ?? '';
$filter_days = isset($_GET['days']) ? intval($_GET['days']) : 7;

// Construction de la requête
$where_clauses = [];
$params = [];

if (!empty($filter_user)) {
    $where_clauses[] = "user_id = ?";
    $params[] = $filter_user;
}

if (!empty($filter_event)) {
    $where_clauses[] = "event_type = ?";
    $params[] = $filter_event;
}

if ($filter_success !== '') {
    $where_clauses[] = "success = ?";
    $params[] = $filter_success;
}

if ($filter_days > 0) {
    $where_clauses[] = "created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
    $params[] = $filter_days;
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Compter le total
$count_sql = "SELECT COUNT(*) FROM EPI_auth_logs $where_sql";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_logs = $stmt->fetchColumn();
$total_pages = ceil($total_logs / $per_page);

// Récupérer les logs
$sql = "
    SELECT 
        l.*,
        u.user_nicename,
        u.user_email
    FROM EPI_auth_logs l
    LEFT JOIN EPI_user u ON l.user_id = u.ID
    $where_sql
    ORDER BY l.created_at DESC
    LIMIT ? OFFSET ?
";

$params[] = $per_page;
$params[] = $offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistiques rapides
$stats_sql = "
    SELECT 
        event_type,
        success,
        COUNT(*) as count
    FROM EPI_auth_logs
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
    GROUP BY event_type, success
    ORDER BY count DESC
";
$stmt = $pdo->prepare($stats_sql);
$stmt->execute([$filter_days]);
$stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Sessions actives
$active_sessions_sql = "
    SELECT 
        s.*,
        u.user_nicename,
        u.user_email,
        TIMESTAMPDIFF(MINUTE, s.last_activity, NOW()) as minutes_idle
    FROM EPI_active_sessions s
    JOIN EPI_user u ON s.user_id = u.ID
    ORDER BY s.last_activity DESC
";
$stmt = $pdo->query($active_sessions_sql);
$active_sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Tentatives suspectes (3+ échecs récents)
$suspicious_sql = "
    SELECT 
        ip_address,
        COUNT(*) as failed_attempts,
        MAX(created_at) as last_attempt,
        GROUP_CONCAT(DISTINCT user_id) as targeted_users
    FROM EPI_auth_logs
    WHERE event_type = 'failed_login'
        AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
    GROUP BY ip_address
    HAVING failed_attempts >= 3
    ORDER BY failed_attempts DESC
";
$stmt = $pdo->query($suspicious_sql);
$suspicious_ips = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs d'Authentification - Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .breadcrumb {
            color: #7f8c8d;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .breadcrumb a {
            color: #3498db;
            text-decoration: none;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .stat-card h3 {
            font-size: 14px;
            color: #7f8c8d;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .stat-success { color: #27ae60; }
        .stat-error { color: #e74c3c; }
        .stat-warning { color: #f39c12; }
        
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .card-header {
            padding: 20px;
            border-bottom: 1px solid #ecf0f1;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-header h2 {
            font-size: 20px;
            color: #2c3e50;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 5px;
            font-size: 13px;
            font-weight: 600;
            color: #555;
        }
        
        .filter-group select,
        .filter-group input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
            font-size: 13px;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
            position: sticky;
            top: 0;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }
        
        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }
        
        .pagination a,
        .pagination span {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-decoration: none;
            color: #2c3e50;
        }
        
        .pagination .active {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        .ip-address {
            font-family: monospace;
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 3px;
        }
        
        .session-idle {
            color: #7f8c8d;
            font-size: 12px;
        }
        
        .session-active {
            color: #27ae60;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="breadcrumb">
            <a href="../EPI/dashboard.php">← Retour au dashboard</a>
        </div>
        
        <h1>📊 Logs d'Authentification</h1>
        <p style="color: #7f8c8d; margin-bottom: 30px;">Surveillance de l'activité et de la sécurité</p>
        
        <!-- Statistiques -->
        <div class="stats-grid">
            <?php
            $total_success = 0;
            $total_failed = 0;
            $total_logins = 0;
            
            foreach ($stats as $stat) {
                if ($stat['event_type'] === 'login' && $stat['success'] == 1) {
                    $total_logins = $stat['count'];
                }
                if ($stat['success'] == 1) {
                    $total_success += $stat['count'];
                } else {
                    $total_failed += $stat['count'];
                }
            }
            ?>
            
            <div class="stat-card">
                <h3>Connexions réussies (<?= $filter_days ?>j)</h3>
                <div class="stat-value stat-success"><?= number_format($total_logins) ?></div>
            </div>
            
            <div class="stat-card">
                <h3>Tentatives échouées (<?= $filter_days ?>j)</h3>
                <div class="stat-value stat-error"><?= number_format($total_failed) ?></div>
            </div>
            
            <div class="stat-card">
                <h3>Sessions actives</h3>
                <div class="stat-value stat-success"><?= count($active_sessions) ?></div>
            </div>
            
            <div class="stat-card">
                <h3>IPs suspectes (1h)</h3>
                <div class="stat-value stat-warning"><?= count($suspicious_ips) ?></div>
            </div>
        </div>
        
        <!-- Alertes -->
        <?php if (!empty($suspicious_ips)): ?>
        <div class="alert alert-danger">
            <strong>⚠️ Activité suspecte détectée !</strong><br>
            <?= count($suspicious_ips) ?> adresse(s) IP avec des tentatives de connexion multiples échouées dans la dernière heure.
        </div>
        <?php endif; ?>
        
        <!-- Sessions actives -->
        <?php if (!empty($active_sessions)): ?>
        <div class="card">
            <div class="card-header">
                <h2>🟢 Sessions Actives</h2>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Utilisateur</th>
                                <th>Email</th>
                                <th>IP</th>
                                <th>Connexion</th>
                                <th>Dernière activité</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($active_sessions as $session): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($session['user_nicename']) ?></strong></td>
                                <td><?= htmlspecialchars($session['user_email']) ?></td>
                                <td><span class="ip-address"><?= htmlspecialchars($session['ip_address']) ?></span></td>
                                <td><?= date('d/m/Y H:i', strtotime($session['login_time'])) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($session['last_activity'])) ?></td>
                                <td>
                                    <?php if ($session['minutes_idle'] < 5): ?>
                                        <span class="session-active">● Actif</span>
                                    <?php else: ?>
                                        <span class="session-idle">Inactif depuis <?= $session['minutes_idle'] ?>min</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- IPs suspectes -->
        <?php if (!empty($suspicious_ips)): ?>
        <div class="card">
            <div class="card-header">
                <h2>🚨 Adresses IP Suspectes</h2>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Adresse IP</th>
                                <th>Tentatives échouées</th>
                                <th>Dernière tentative</th>
                                <th>Utilisateurs ciblés</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($suspicious_ips as $ip): ?>
                            <tr>
                                <td><span class="ip-address"><?= htmlspecialchars($ip['ip_address']) ?></span></td>
                                <td><strong style="color: #e74c3c;"><?= $ip['failed_attempts'] ?></strong></td>
                                <td><?= date('d/m/Y H:i:s', strtotime($ip['last_attempt'])) ?></td>
                                <td><?= htmlspecialchars($ip['targeted_users'] ?? 'N/A') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Filtres et logs -->
        <div class="card">
            <div class="card-header">
                <h2>📋 Journal des événements</h2>
                <span style="color: #7f8c8d; font-size: 14px;"><?= number_format($total_logs) ?> événements</span>
            </div>
            <div class="card-body">
                <form method="GET" class="filters">
                    <div class="filter-group">
                        <label>Type d'événement</label>
                        <select name="event_type">
                            <option value="">Tous</option>
                            <option value="login" <?= $filter_event === 'login' ? 'selected' : '' ?>>Connexion</option>
                            <option value="logout" <?= $filter_event === 'logout' ? 'selected' : '' ?>>Déconnexion</option>
                            <option value="failed_login" <?= $filter_event === 'failed_login' ? 'selected' : '' ?>>Échec connexion</option>
                            <option value="password_change" <?= $filter_event === 'password_change' ? 'selected' : '' ?>>Changement MDP</option>
                            <option value="session_expired" <?= $filter_event === 'session_expired' ? 'selected' : '' ?>>Session expirée</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Statut</label>
                        <select name="success">
                            <option value="">Tous</option>
                            <option value="1" <?= $filter_success === '1' ? 'selected' : '' ?>>Réussi</option>
                            <option value="0" <?= $filter_success === '0' ? 'selected' : '' ?>>Échoué</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Période</label>
                        <select name="days">
                            <option value="1" <?= $filter_days === 1 ? 'selected' : '' ?>>Dernières 24h</option>
                            <option value="7" <?= $filter_days === 7 ? 'selected' : '' ?>>7 derniers jours</option>
                            <option value="30" <?= $filter_days === 30 ? 'selected' : '' ?>>30 derniers jours</option>
                            <option value="90" <?= $filter_days === 90 ? 'selected' : '' ?>>90 derniers jours</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>ID Utilisateur</label>
                        <input type="number" name="user_id" placeholder="Ex: 123" value="<?= htmlspecialchars($filter_user) ?>">
                    </div>
                    
                    <div class="filter-group" style="display: flex; align-items: flex-end;">
                        <button type="submit" class="btn btn-primary" style="width: 100%;">Filtrer</button>
                    </div>
                </form>
                
                <div class="table-responsive" style="margin-top: 20px;">
                    <table>
                        <thead>
                            <tr>
                                <th>Date/Heure</th>
                                <th>Utilisateur</th>
                                <th>Événement</th>
                                <th>Statut</th>
                                <th>IP</th>
                                <th>User Agent</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 40px; color: #7f8c8d;">
                                    Aucun événement trouvé
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td style="white-space: nowrap;">
                                        <?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?>
                                    </td>
                                    <td>
                                        <?php if ($log['user_nicename']): ?>
                                            <strong><?= htmlspecialchars($log['user_nicename']) ?></strong><br>
                                            <small style="color: #7f8c8d;"><?= htmlspecialchars($log['user_email']) ?></small>
                                        <?php else: ?>
                                            <span style="color: #7f8c8d;">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $event_labels = [
                                            'login' => ['Connexion', 'badge-info'],
                                            'logout' => ['Déconnexion', 'badge-info'],
                                            'failed_login' => ['Échec connexion', 'badge-danger'],
                                            'password_change' => ['Changement MDP', 'badge-warning'],
                                            'password_reset' => ['Reset MDP', 'badge-warning'],
                                            'session_expired' => ['Session expirée', 'badge-warning'],
                                            'lockout' => ['Compte bloqué', 'badge-danger']
                                        ];
                                        
                                        $event_info = $event_labels[$log['event_type']] ?? [$log['event_type'], 'badge-info'];
                                        ?>
                                        <span class="badge <?= $event_info[1] ?>"><?= $event_info[0] ?></span>
                                    </td>
                                    <td>
                                        <?php if ($log['success']): ?>
                                            <span class="badge badge-success">✓ Réussi</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">✗ Échoué</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="ip-address"><?= htmlspecialchars($log['ip_address']) ?></span>
                                    </td>
                                    <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        <small style="color: #7f8c8d;"><?= htmlspecialchars($log['user_agent']) ?></small>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>&days=<?= $filter_days ?>&event_type=<?= urlencode($filter_event) ?>&success=<?= urlencode($filter_success) ?>&user_id=<?= urlencode($filter_user) ?>">« Précédent</a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <?php if ($i === $page): ?>
                            <span class="active"><?= $i ?></span>
                        <?php else: ?>
                            <a href="?page=<?= $i ?>&days=<?= $filter_days ?>&event_type=<?= urlencode($filter_event) ?>&success=<?= urlencode($filter_success) ?>&user_id=<?= urlencode($filter_user) ?>"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?= $page + 1 ?>&days=<?= $filter_days ?>&event_type=<?= urlencode($filter_event) ?>&success=<?= urlencode($filter_success) ?>&user_id=<?= urlencode($filter_user) ?>">Suivant »</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
