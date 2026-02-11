<?php
require_once '../includes/config.php';
require_once 'check_auth.php';

// Récupérer les statistiques
$stats = [];
$stats['news_count'] = $pdo->query("SELECT COUNT(*) FROM EPI_news")->fetchColumn();
$stats['members_count'] = $pdo->query("SELECT COUNT(*) FROM EPI_members")->fetchColumn();
$stats['gallery_count'] = $pdo->query("SELECT COUNT(*) FROM EPI_gallery")->fetchColumn();
$stats['messages_count'] = $pdo->query("SELECT COUNT(*) FROM EPI_contact_messages WHERE read_status = 0")->fetchColumn();

// Récupérer les derniers messages
$messages = $pdo->query("SELECT * FROM EPI_contact_messages ORDER BY created_at DESC LIMIT 5")->fetchAll();

$page_title = "Tableau de bord";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Administration</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        .admin-sidebar {
            width: 250px;
            background: var(--text-primary);
            color: white;
            padding: 2rem 0;
        }
        .admin-sidebar h2 {
            color: white;
            padding: 0 1.5rem;
            margin-bottom: 2rem;
            font-size: 1.5rem;
        }
        .admin-menu a {
            display: block;
            padding: 1rem 1.5rem;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        .admin-menu a:hover,
        .admin-menu a.active {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left-color: var(--primary-color);
        }
        .admin-content {
            flex: 1;
            padding: 2rem;
            background: var(--background-light);
        }
        .admin-header {
            background: white;
            padding: 1.5rem 2rem;
            margin: -2rem -2rem 2rem;
            box-shadow: var(--shadow-sm);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            padding: 2rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
        }
        .stat-card h3 {
            color: var(--text-secondary);
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        .stat-card .number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        .messages-table {
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            overflow: hidden;
        }
        .messages-table table {
            width: 100%;
            border-collapse: collapse;
        }
        .messages-table th {
            background: var(--background-dark);
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--text-primary);
        }
        .messages-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        .messages-table tr:last-child td {
            border-bottom: none;
        }
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: var(--radius-md);
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-new {
            background: var(--error);
            color: white;
        }
        .badge-read {
            background: var(--background-dark);
            color: var(--text-secondary);
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="admin-sidebar">
            <h2>📊 Admin Panel</h2>
            <nav class="admin-menu">
                <a href="index.php" class="active">🏠 Tableau de bord</a>
                <a href="news.php">📰 Actualités</a>
                <a href="cinema.php">🎬 Cinema</a>
                <a href="members.php">👥 Membres</a>
                <a href="gallery.php">📸 Galerie</a>
                <a href="press.php">📄 Presse</a>
                <a href="videos.php">🎥 Vidéos</a>
                <a href="messages.php">✉️ Messages 
                    <?php if ($stats['messages_count'] > 0): ?>
                        <span class="badge badge-new"><?php echo $stats['messages_count']; ?></span>
                    <?php endif; ?>
                </a>
                <a href="../index.php" target="_blank">🌐 Voir le site</a>
                <a href="?logout=1" style="margin-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1rem;">
                    🚪 Déconnexion
                </a>
            </nav>
        </div>
        
        <!-- Contenu principal -->
        <div class="admin-content">
            <div class="admin-header">
                <h1><?php echo $page_title; ?></h1>
                <div style="color: var(--text-secondary);">
                    Connecté en tant que <strong><?php echo htmlspecialchars($_SESSION['admin_username']); ?></strong>
                </div>
            </div>
            
            <!-- Statistiques -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Actualités</h3>
                    <div class="number"><?php echo $stats['news_count']; ?></div>
                    <a href="news.php" style="color: var(--primary-color); font-size: 0.875rem;">Gérer →</a>
                </div>
                
                <div class="stat-card">
                    <h3>Membres du bureau</h3>
                    <div class="number"><?php echo $stats['members_count']; ?></div>
                    <a href="members.php" style="color: var(--primary-color); font-size: 0.875rem;">Gérer →</a>
                </div>
                
                <div class="stat-card">
                    <h3>Photos</h3>
                    <div class="number"><?php echo $stats['gallery_count']; ?></div>
                    <a href="gallery.php" style="color: var(--primary-color); font-size: 0.875rem;">Gérer →</a>
                </div>
                
                <div class="stat-card">
                    <h3>Nouveaux messages</h3>
                    <div class="number" style="color: var(--error);"><?php echo $stats['messages_count']; ?></div>
                    <a href="messages.php" style="color: var(--primary-color); font-size: 0.875rem;">Voir →</a>
                </div>
            </div>
            
            <!-- Derniers messages -->
            <h2 style="margin-bottom: 1rem;">Derniers messages reçus</h2>
            <div class="messages-table">
                <table>
                    <thead>
                        <tr>
                            <th>Statut</th>
                            <th>Date</th>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Sujet</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($messages)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 2rem; color: var(--text-secondary);">
                                    Aucun message pour le moment
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($messages as $message): ?>
                                <tr>
                                    <td>
                                        <?php if ($message['read_status']): ?>
                                            <span class="badge badge-read">Lu</span>
                                        <?php else: ?>
                                            <span class="badge badge-new">Nouveau</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($message['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($message['name']); ?></td>
                                    <td><?php echo htmlspecialchars($message['email']); ?></td>
                                    <td><?php echo htmlspecialchars($message['subject'] ?: 'Sans objet'); ?></td>
                                    <td>
                                        <a href="messages.php?view=<?php echo $message['id']; ?>" 
                                           class="btn btn-primary" 
                                           style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                            Voir
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if (!empty($messages)): ?>
                <div style="text-align: center; margin-top: 1rem;">
                    <a href="messages.php" class="btn btn-secondary">Voir tous les messages</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>