<?php
require_once '../includes/config.php';
require_once 'check_auth.php';
require_once '../includes/csrf.php';
require_once '../includes/sanitize.php';

$message = '';
$message_type = '';

// Supprimer une actualité (POST uniquement pour sécurité)
if (isset($_POST['delete_news'])) {
    csrf_protect();
    $id = (int)$_POST['delete_news'];
    $stmt = $pdo->prepare("SELECT image FROM EPI_news WHERE id = ?");
    $stmt->execute([$id]);
    $news = $stmt->fetch();

    if ($news && $news['image']) {
        @unlink('../uploads/news/' . $news['image']);
    }

    $stmt = $pdo->prepare("DELETE FROM EPI_news WHERE id = ?");
    $stmt->execute([$id]);
    $message = "Actualité supprimée avec succès";
    $message_type = 'success';
}

// Ajouter ou modifier une actualité
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_news'])) {
    csrf_protect();
    $id = $_POST['id'] ?? null;
    $title = sanitize_text($_POST['title'] ?? '', 255);
    $content = $_POST['content'] ?? '';
    $published = isset($_POST['published']) ? 1 : 0;
    $current_image = $_POST['current_image'] ?? '';

    $image = $current_image;

    // Upload de l'image avec validation MIME
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $upload = validate_upload($_FILES['image']);

        if ($upload['valid']) {
            if (!file_exists('../uploads/news')) {
                mkdir('../uploads/news', 0755, true);
            }
            $new_filename = safe_filename('news', $upload['ext']);
            if (move_uploaded_file($_FILES['image']['tmp_name'], '../uploads/news/' . $new_filename)) {
                // Supprimer l'ancienne image
                if ($current_image && file_exists('../uploads/news/' . $current_image)) {
                    @unlink('../uploads/news/' . $current_image);
                }
                $image = $new_filename;
            }
        } else {
            $message = $upload['error'];
            $message_type = 'error';
        }
    }

    if ($message_type !== 'error') {
        try {
            if ($id) {
                $stmt = $pdo->prepare("UPDATE EPI_news SET title = ?, content = ?, image = ?, published = ? WHERE id = ?");
                $stmt->execute([$title, $content, $image, $published, $id]);
                $message = "Actualité modifiée avec succès";
            } else {
                $stmt = $pdo->prepare("INSERT INTO EPI_news (title, content, image, published) VALUES (?, ?, ?, ?)");
                $stmt->execute([$title, $content, $image, $published]);
                $message = "Actualité créée avec succès";
            }
            $message_type = 'success';
        } catch (PDOException $e) {
            error_log("Erreur news: " . $e->getMessage());
            $message = "Erreur lors de l'enregistrement. Veuillez réessayer.";
            $message_type = 'error';
        }
    }
}

// Récupérer une actualité pour modification
$edit_news = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM EPI_news WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_news = $stmt->fetch();
}

// Récupérer toutes les actualités
$news_list = $pdo->query("SELECT * FROM EPI_news ORDER BY created_at DESC")->fetchAll();

$page_title = "Gestion des actualités";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Administration</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-container { display: flex; min-height: 100vh; }
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
        .form-image-preview {
            max-width: 200px;
            margin-top: 1rem;
            border-radius: var(--radius-md);
        }
        table {
            width: 100%;
            background: white;
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-md);
        }
        table th, table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        table th {
            background: var(--background-dark);
            font-weight: 600;
        }
        table tr:last-child td {
            border-bottom: none;
        }
        table img {
            width: 80px;
            height: 60px;
            object-fit: cover;
            border-radius: var(--radius-sm);
        }
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: var(--radius-md);
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-published {
            background: var(--success);
            color: white;
        }
        .badge-draft {
            background: var(--text-secondary);
            color: white;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="admin-sidebar">
            <h2>📊 Admin Panel</h2>
            <nav class="admin-menu">
                <a href="index.php">🏠 Tableau de bord</a>
                <a href="news.php" class="active">📰 Actualités</a>
                <a href="cinema.php">🎬 Cinema</a>
                <a href="members.php">👥 Membres</a>
                <a href="gallery.php">📸 Galerie</a>
                <a href="press.php">📄 Presse</a>
                <a href="videos.php">🎥 Vidéos</a>
                <a href="messages.php">✉️ Messages</a>
                <a href="../index.php" target="_blank">🌐 Voir le site</a>
                <form method="POST" action="" style="margin-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 0;">
                    <input type="hidden" name="admin_logout" value="1">
                    <button type="submit" style="display: block; width: 100%; padding: 1rem 1.5rem; color: rgba(255,255,255,0.8); text-decoration: none; background: none; border: none; cursor: pointer; text-align: left; font-size: inherit; font-family: inherit;">
                        Deconnexion
                    </button>
                </form>
            </nav>
        </div>
        
        <!-- Contenu -->
        <div class="admin-content">
            <div class="admin-header">
                <h1><?php echo $page_title; ?></h1>
            </div>
            
            <?php if ($message): ?>
                <div class="form-message <?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <!-- Formulaire -->
            <div style="background: white; padding: 2rem; border-radius: var(--radius-lg); margin-bottom: 2rem; box-shadow: var(--shadow-md);">
                <h2><?php echo $edit_news ? 'Modifier l\'actualité' : 'Nouvelle actualité'; ?></h2>
                <form method="POST" enctype="multipart/form-data">
                    <?php echo csrf_field(); ?>
                    <?php if ($edit_news): ?>
                        <input type="hidden" name="id" value="<?php echo (int)$edit_news['id']; ?>">
                        <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($edit_news['image']); ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label class="form-label">Titre *</label>
                        <input type="text" name="title" class="form-control" 
                               value="<?php echo htmlspecialchars($edit_news['title'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Contenu *</label>
                        <textarea name="content" class="form-control" rows="8" required><?php echo htmlspecialchars($edit_news['content'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Image</label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                        <?php if ($edit_news && $edit_news['image']): ?>
                            <img src="../uploads/news/<?php echo htmlspecialchars($edit_news['image']); ?>" 
                                 class="form-image-preview">
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" name="published" 
                                   <?php echo ($edit_news['published'] ?? 1) ? 'checked' : ''; ?>>
                            <span>Publier l'actualité</span>
                        </label>
                    </div>
                    
                    <div style="display: flex; gap: 1rem;">
                        <button type="submit" class="btn btn-primary">
                            <?php echo $edit_news ? 'Modifier' : 'Créer'; ?>
                        </button>
                        <?php if ($edit_news): ?>
                            <a href="news.php" class="btn btn-secondary">Annuler</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <!-- Liste -->
            <h2>Liste des actualités</h2>
            <?php if (empty($news_list)): ?>
                <p style="text-align: center; padding: 2rem; color: var(--text-secondary);">
                    Aucune actualité pour le moment
                </p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Titre</th>
                            <th>Date</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($news_list as $item): ?>
                            <tr>
                                <td>
                                    <?php if ($item['image']): ?>
                                        <img src="../uploads/news/<?php echo htmlspecialchars($item['image']); ?>">
                                    <?php else: ?>
                                        <div style="width: 80px; height: 60px; background: var(--background-dark); 
                                                    border-radius: var(--radius-sm); display: flex; align-items: center; 
                                                    justify-content: center;">📰</div>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo htmlspecialchars($item['title']); ?></strong></td>
                                <td><?php echo date('d/m/Y', strtotime($item['created_at'])); ?></td>
                                <td>
                                    <span class="badge <?php echo $item['published'] ? 'badge-published' : 'badge-draft'; ?>">
                                        <?php echo $item['published'] ? 'Publié' : 'Brouillon'; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="?edit=<?php echo $item['id']; ?>" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                        Modifier
                                    </a>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer cette actualité ?')">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="delete_news" value="<?php echo (int)$item['id']; ?>">
                                        <button type="submit" class="btn btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.875rem; background: var(--error);">
                                            Supprimer
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>