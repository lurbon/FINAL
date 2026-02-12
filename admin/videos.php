<?php
require_once '../includes/config.php';
require_once 'check_auth.php';
require_once '../includes/csrf.php';
require_once '../includes/sanitize.php';

$message = '';
$message_type = '';

// Supprimer une video
if (isset($_POST['delete_video'])) {
    csrf_protect();
    $id = (int)$_POST['delete_video'];
    $stmt = $pdo->prepare("SELECT video_url FROM EPI_videos WHERE id = ?");
    $stmt->execute([$id]);
    $vid = $stmt->fetch();

    // Supprimer le fichier local si c'est un upload
    if ($vid && $vid['video_url'] && file_exists('../' . $vid['video_url'])) {
        @unlink('../' . $vid['video_url']);
    }

    $pdo->prepare("DELETE FROM EPI_videos WHERE id = ?")->execute([$id]);
    $message = "Video supprimee";
    $message_type = 'success';
}

// Ajouter ou modifier une video
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_video']) && !isset($_POST['admin_logout'])) {
    csrf_protect();
    $id = $_POST['id'] ?? null;
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $video_type = $_POST['video_type'] ?? 'youtube';
    $youtube_id = trim($_POST['youtube_id'] ?? '');
    $video_url = trim($_POST['video_url'] ?? '');
    $current_video_url = $_POST['current_video_url'] ?? '';

    // Extraire l'ID YouTube automatiquement si c'est une URL complete
    if ($video_type === 'youtube' && $youtube_id) {
        // Gerer les URLs completes YouTube
        if (preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $youtube_id, $matches)) {
            $youtube_id = $matches[1];
        }
    }

    // Upload de fichier video
    $final_video_url = $current_video_url;
    if ($video_type === 'file' && isset($_FILES['video_file']) && $_FILES['video_file']['error'] === 0) {
        $allowed = ['mp4', 'webm', 'ogg', 'mov'];
        $filename = $_FILES['video_file']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            if (!file_exists('../uploads/videos')) {
                mkdir('../uploads/videos', 0755, true);
            }
            $new_filename = uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['video_file']['tmp_name'], '../uploads/videos/' . $new_filename)) {
                // Supprimer l'ancien fichier
                if ($current_video_url && file_exists('../' . $current_video_url)) {
                    @unlink('../' . $current_video_url);
                }
                $final_video_url = 'uploads/videos/' . $new_filename;
            }
        } else {
            $message = "Format de video non supporte. Formats acceptes : MP4, WebM, OGG, MOV";
            $message_type = 'error';
        }
    } elseif ($video_type === 'url') {
        $final_video_url = $video_url;
    } elseif ($video_type === 'youtube') {
        $final_video_url = '';
    }

    if ($message_type !== 'error') {
        try {
            if ($id) {
                $stmt = $pdo->prepare("UPDATE EPI_videos SET title=?, description=?, youtube_id=?, video_url=? WHERE id=?");
                $stmt->execute([
                    $title,
                    $description,
                    $video_type === 'youtube' ? $youtube_id : '',
                    $final_video_url,
                    $id
                ]);
                $message = "Video modifiee avec succes";
            } else {
                $stmt = $pdo->prepare("INSERT INTO EPI_videos (title, description, youtube_id, video_url) VALUES (?,?,?,?)");
                $stmt->execute([
                    $title,
                    $description,
                    $video_type === 'youtube' ? $youtube_id : '',
                    $final_video_url
                ]);
                $message = "Video ajoutee avec succes";
            }
            $message_type = 'success';
        } catch (PDOException $e) {
            error_log("Videos admin error: " . $e->getMessage());
            $message = "Une erreur est survenue lors de l'enregistrement.";
            $message_type = 'error';
        }
    }
}

// Recuperer une video pour modification
$edit_video = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM EPI_videos WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_video = $stmt->fetch();
}

$videos = $pdo->query("SELECT * FROM EPI_videos ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Videos - Administration</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-container { display: flex; min-height: 100vh; }
        .admin-sidebar { width: 250px; background: var(--text-primary); color: white; padding: 2rem 0; }
        .admin-sidebar h2 { color: white; padding: 0 1.5rem; margin-bottom: 2rem; }
        .admin-menu a {display: block; padding: 1rem 1.5rem; color: rgba(255,255,255,0.8); text-decoration: none; transition: all 0.3s; border-left: 3px solid transparent;}
        .admin-menu a:hover, .admin-menu a.active {background: rgba(255,255,255,0.1); color: white; border-left-color: var(--primary-color);}
        .admin-content { flex: 1; padding: 2rem; background: var(--background-light); }
        .admin-header {background: white; padding: 1.5rem 2rem; margin: -2rem -2rem 2rem; box-shadow: var(--shadow-sm); display: flex; justify-content: space-between; align-items: center;}

        .video-type-selector {
            display: flex; gap: 0.5rem; margin-bottom: 1.5rem;
        }
        .video-type-btn {
            padding: 0.6rem 1.25rem;
            border: 2px solid var(--border-color);
            background: white;
            border-radius: var(--radius-md);
            cursor: pointer;
            font-family: var(--font-main);
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text-secondary);
            transition: all 0.3s;
        }
        .video-type-btn.active {
            border-color: var(--primary-color);
            background: var(--primary-color);
            color: white;
        }
        .video-type-btn:hover:not(.active) {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        .video-fields { display: none; }
        .video-fields.active { display: block; }

        .video-list { display: flex; flex-direction: column; gap: 1rem; }
        .video-item {
            background: white; border-radius: var(--radius-lg); overflow: hidden;
            box-shadow: var(--shadow-sm); display: flex; border: 1px solid var(--border-color);
        }
        .video-item-preview {
            width: 180px; flex-shrink: 0; background: #000;
            display: flex; align-items: center; justify-content: center;
            position: relative; overflow: hidden;
        }
        .video-item-preview img {
            width: 100%; height: 100%; object-fit: cover;
        }
        .video-item-preview .video-type-badge {
            position: absolute; top: 0.5rem; left: 0.5rem;
            background: rgba(0,0,0,0.7); color: white;
            padding: 0.15rem 0.5rem; border-radius: var(--radius-sm);
            font-size: 0.7rem; font-weight: 600;
        }
        .video-item-info {
            padding: 1rem 1.25rem; flex: 1;
            display: flex; flex-direction: column; justify-content: center;
        }
        .video-item-info h4 { margin: 0 0 0.25rem; font-size: 1rem; }
        .video-item-info .video-item-desc {
            font-size: 0.8rem; color: var(--text-secondary);
            margin: 0 0 0.5rem; line-height: 1.4;
        }
        .video-item-info .video-item-source {
            font-size: 0.75rem; color: var(--text-secondary);
            word-break: break-all;
        }
        .video-item-actions {
            display: flex; flex-direction: column; gap: 0.5rem;
            padding: 1rem; justify-content: center;
        }
        .btn-sm {
            padding: 0.35rem 0.75rem; font-size: 0.8rem;
            border-radius: var(--radius-sm); text-decoration: none;
            display: inline-block; border: none; cursor: pointer;
            text-align: center;
        }
        .btn-edit { background: var(--primary-color); color: white; }
        .btn-edit:hover { opacity: 0.9; color: white; }
        .btn-del { background: var(--error); color: white; }
        .btn-del:hover { opacity: 0.9; color: white; }
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
                <a href="videos.php" class="active">🎥 Vidéos</a>
                <a href="messages.php">✉️ Messages</a>
                <a href="../index.php" target="_blank">🌐 Voir le site</a>
                <form method="POST" action="" style="margin-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 0;">
                    <input type="hidden" name="admin_logout" value="1">
                    <button type="submit" style="display: block; width: 100%; padding: 1rem 1.5rem; color: rgba(255,255,255,0.8); text-decoration: none; background: none; border: none; cursor: pointer; text-align: left; font-size: inherit; font-family: inherit;">Deconnexion</button>
                </form>
            </nav>
        </div>

        <div class="admin-content">
            <div class="admin-header">
                <h1>Gestion des videos</h1>
                <span style="color: var(--text-secondary);"><?php echo count($videos); ?> video(s)</span>
            </div>

            <?php if ($message): ?>
                <div class="form-message <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <!-- Formulaire -->
            <div style="background: white; padding: 2rem; border-radius: var(--radius-lg); margin-bottom: 2rem; box-shadow: var(--shadow-md);">
                <h2 style="margin-bottom: 1rem;"><?php echo $edit_video ? 'Modifier la video' : 'Ajouter une video'; ?></h2>

                <form method="POST" enctype="multipart/form-data">
                    <?php echo csrf_field(); ?>
                    <?php if ($edit_video): ?>
                        <input type="hidden" name="id" value="<?php echo $edit_video['id']; ?>">
                        <input type="hidden" name="current_video_url" value="<?php echo htmlspecialchars($edit_video['video_url'] ?? ''); ?>">
                    <?php endif; ?>

                    <?php
                        $current_type = 'youtube';
                        if ($edit_video) {
                            if ($edit_video['youtube_id']) {
                                $current_type = 'youtube';
                            } elseif ($edit_video['video_url'] && strpos($edit_video['video_url'], 'uploads/') === 0) {
                                $current_type = 'file';
                            } elseif ($edit_video['video_url']) {
                                $current_type = 'url';
                            }
                        }
                    ?>

                    <!-- Selecteur de type -->
                    <div class="video-type-selector">
                        <button type="button" class="video-type-btn <?php echo $current_type === 'youtube' ? 'active' : ''; ?>" data-type="youtube">YouTube</button>
                        <button type="button" class="video-type-btn <?php echo $current_type === 'url' ? 'active' : ''; ?>" data-type="url">Lien externe</button>
                        <button type="button" class="video-type-btn <?php echo $current_type === 'file' ? 'active' : ''; ?>" data-type="file">Fichier video</button>
                    </div>
                    <input type="hidden" name="video_type" id="video_type" value="<?php echo $current_type; ?>">

                    <div class="form-group">
                        <label class="form-label">Titre *</label>
                        <input type="text" name="title" class="form-control"
                               value="<?php echo htmlspecialchars($edit_video['title'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($edit_video['description'] ?? ''); ?></textarea>
                    </div>

                    <!-- Champs YouTube -->
                    <div class="video-fields <?php echo $current_type === 'youtube' ? 'active' : ''; ?>" id="fields-youtube">
                        <div class="form-group">
                            <label class="form-label">ID ou URL YouTube</label>
                            <input type="text" name="youtube_id" class="form-control"
                                   value="<?php echo htmlspecialchars($edit_video['youtube_id'] ?? ''); ?>"
                                   placeholder="dQw4w9WgXcQ ou https://www.youtube.com/watch?v=dQw4w9WgXcQ">
                            <small style="color: var(--text-secondary);">Collez l'ID ou l'URL complete, l'ID sera extrait automatiquement</small>
                        </div>
                    </div>

                    <!-- Champs URL externe -->
                    <div class="video-fields <?php echo $current_type === 'url' ? 'active' : ''; ?>" id="fields-url">
                        <div class="form-group">
                            <label class="form-label">URL de la video</label>
                            <input type="url" name="video_url" class="form-control"
                                   value="<?php echo ($current_type === 'url') ? htmlspecialchars($edit_video['video_url'] ?? '') : ''; ?>"
                                   placeholder="https://exemple.com/video.mp4">
                            <small style="color: var(--text-secondary);">URL directe vers un fichier video (MP4, WebM) ou lien Dailymotion, Vimeo, etc.</small>
                        </div>
                    </div>

                    <!-- Champs upload fichier -->
                    <div class="video-fields <?php echo $current_type === 'file' ? 'active' : ''; ?>" id="fields-file">
                        <div class="form-group">
                            <label class="form-label">Fichier video</label>
                            <input type="file" name="video_file" class="form-control" accept="video/mp4,video/webm,video/ogg,video/quicktime">
                            <small style="color: var(--text-secondary);">Formats acceptes : MP4, WebM, OGG, MOV</small>
                            <?php if ($edit_video && $current_type === 'file' && $edit_video['video_url']): ?>
                                <div style="margin-top: 0.5rem; font-size: 0.85rem; color: var(--text-secondary);">
                                    Fichier actuel : <code><?php echo htmlspecialchars(basename($edit_video['video_url'])); ?></code>
                                    <br><small>Laissez vide pour garder le fichier actuel</small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                        <button type="submit" class="btn btn-primary">
                            <?php echo $edit_video ? 'Modifier' : 'Ajouter'; ?>
                        </button>
                        <?php if ($edit_video): ?>
                            <a href="videos.php" class="btn btn-secondary">Annuler</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Liste des videos -->
            <h2 style="margin-bottom: 1rem;">Videos (<?php echo count($videos); ?>)</h2>

            <?php if (empty($videos)): ?>
                <p style="text-align: center; padding: 2rem; color: var(--text-secondary);">Aucune video</p>
            <?php else: ?>
                <div class="video-list">
                    <?php foreach ($videos as $v):
                        $is_youtube = !empty($v['youtube_id']);
                        $is_file = !empty($v['video_url']) && strpos($v['video_url'], 'uploads/') === 0;
                        $is_url = !empty($v['video_url']) && !$is_file;
                        $type_label = $is_youtube ? 'YouTube' : ($is_file ? 'Fichier' : 'Lien externe');
                    ?>
                        <div class="video-item">
                            <div class="video-item-preview">
                                <?php if ($is_youtube): ?>
                                    <img src="https://img.youtube.com/vi/<?php echo htmlspecialchars($v['youtube_id']); ?>/mqdefault.jpg"
                                         alt="<?php echo htmlspecialchars($v['title']); ?>">
                                <?php else: ?>
                                    <div style="color: white; font-size: 2.5rem;">&#9654;</div>
                                <?php endif; ?>
                                <span class="video-type-badge"><?php echo $type_label; ?></span>
                            </div>
                            <div class="video-item-info">
                                <h4><?php echo htmlspecialchars($v['title']); ?></h4>
                                <?php if ($v['description']): ?>
                                    <p class="video-item-desc"><?php echo htmlspecialchars(mb_substr($v['description'], 0, 120)); ?><?php echo mb_strlen($v['description']) > 120 ? '...' : ''; ?></p>
                                <?php endif; ?>
                                <div class="video-item-source">
                                    <?php if ($is_youtube): ?>
                                        ID: <code><?php echo htmlspecialchars($v['youtube_id']); ?></code>
                                    <?php elseif ($is_file): ?>
                                        Fichier: <code><?php echo htmlspecialchars(basename($v['video_url'])); ?></code>
                                    <?php else: ?>
                                        URL: <?php echo htmlspecialchars($v['video_url']); ?>
                                    <?php endif; ?>
                                </div>
                                <div style="font-size: 0.7rem; color: var(--text-secondary); margin-top: 0.25rem;">
                                    Ajoutee le <?php echo date('d/m/Y', strtotime($v['created_at'])); ?>
                                </div>
                            </div>
                            <div class="video-item-actions">
                                <a href="?edit=<?php echo $v['id']; ?>" class="btn-sm btn-edit">Modifier</a>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer cette video ?')">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="delete_video" value="<?php echo (int)$v['id']; ?>">
                                    <button type="submit" class="btn-sm btn-del">Supprimer</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    // Selecteur de type de video
    document.querySelectorAll('.video-type-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var type = this.getAttribute('data-type');
            document.getElementById('video_type').value = type;

            document.querySelectorAll('.video-type-btn').forEach(function(b) { b.classList.remove('active'); });
            this.classList.add('active');

            document.querySelectorAll('.video-fields').forEach(function(f) { f.classList.remove('active'); });
            document.getElementById('fields-' + type).classList.add('active');
        });
    });
    </script>
</body>
</html>