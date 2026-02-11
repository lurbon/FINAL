<?php
require_once '../includes/config.php';
require_once 'check_auth.php';

if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM contact_messages WHERE id = ?")->execute([$_GET['delete']]);
    header('Location: messages.php');
    exit;
}

if (isset($_GET['read'])) {
    $pdo->prepare("UPDATE contact_messages SET read_status = 1 WHERE id = ?")->execute([$_GET['read']]);
}

$messages = $pdo->query("SELECT * FROM contact_messages ORDER BY created_at DESC")->fetchAll();
$unread = $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE read_status = 0")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Messages - Administration</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-container { display: flex; min-height: 100vh; }
        .admin-sidebar { width: 250px; background: var(--text-primary); color: white; padding: 2rem 0; }
        .admin-sidebar h2 { color: white; padding: 0 1.5rem; margin-bottom: 2rem; }
        .admin-menu a {display: block; padding: 1rem 1.5rem; color: rgba(255,255,255,0.8); text-decoration: none; transition: all 0.3s; border-left: 3px solid transparent;}
        .admin-menu a:hover, .admin-menu a.active {background: rgba(255,255,255,0.1); color: white; border-left-color: var(--primary-color);}
        .admin-content { flex: 1; padding: 2rem; background: var(--background-light); }
        .admin-header {background: white; padding: 1.5rem 2rem; margin: -2rem -2rem 2rem; box-shadow: var(--shadow-sm);}
        table {width: 100%; background: white; border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow-md);}
        table th, table td {padding: 1rem; text-align: left; border-bottom: 1px solid var(--border-color);}
        table th {background: var(--background-dark); font-weight: 600;}
        .unread { background: #fef3c7; }
        .badge {display: inline-block; padding: 0.25rem 0.75rem; border-radius: var(--radius-md); font-size: 0.75rem; font-weight: 600;}
        .badge-new {background: var(--error); color: white;}
        .badge-read {background: var(--background-dark); color: var(--text-secondary);}
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-sidebar">
            <h2>📊 Admin Panel</h2>
            <nav class="admin-menu">
                <a href="index.php">🏠 Tableau de bord</a>
                <a href="news.php">📰 Actualités</a>
                <a href="cinema.php">🎬 Cinema</a>
                <a href="members.php">👥 Membres</a>
                <a href="gallery.php">📸 Galerie</a>
                <a href="press.php">📄 Presse</a>
                <a href="videos.php">🎥 Vidéos</a>
                <a href="messages.php" class="active">✉️ Messages <?php if($unread): ?><span class="badge badge-new"><?php echo $unread; ?></span><?php endif; ?></a>
                <a href="../index.php" target="_blank">🌐 Voir le site</a>
                <a href="?logout=1" style="margin-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1rem;">🚪 Déconnexion</a>
            </nav>
        </div>
        
        <div class="admin-content">
            <div class="admin-header"><h1>Messages de contact (<?php echo count($messages); ?>)</h1></div>
            
            <?php if (empty($messages)): ?>
                <p style="text-align: center; padding: 2rem; color: var(--text-secondary);">Aucun message</p>
            <?php else: ?>
                <table>
                    <tr><th>Statut</th><th>Date</th><th>Nom</th><th>Email</th><th>Sujet</th><th>Message</th><th>Actions</th></tr>
                    <?php foreach ($messages as $msg): ?>
                        <tr class="<?php echo !$msg['read_status'] ? 'unread' : ''; ?>">
                            <td>
                                <?php if ($msg['read_status']): ?>
                                    <span class="badge badge-read">Lu</span>
                                <?php else: ?>
                                    <span class="badge badge-new">Nouveau</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($msg['created_at'])); ?></td>
                            <td><strong><?php echo htmlspecialchars($msg['name']); ?></strong></td>
                            <td><a href="mailto:<?php echo htmlspecialchars($msg['email']); ?>"><?php echo htmlspecialchars($msg['email']); ?></a></td>
                            <td><?php echo htmlspecialchars($msg['subject'] ?: 'Sans objet'); ?></td>
                            <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars(substr($msg['message'], 0, 100)); ?>...</td>
                            <td>
                                <?php if (!$msg['read_status']): ?>
                                    <a href="?read=<?php echo $msg['id']; ?>" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.875rem;">Marquer lu</a>
                                <?php endif; ?>
                                <a href="?delete=<?php echo $msg['id']; ?>" onclick="return confirm('Supprimer?')" class="btn btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.875rem; background: var(--error);">Supprimer</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>