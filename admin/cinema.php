<?php
require_once '../includes/config.php';
require_once 'check_auth.php';
require_once '../includes/csrf.php';
require_once '../includes/sanitize.php';

$message = '';
$message_type = '';

// Creer la table si elle n'existe pas
$pdo->exec("CREATE TABLE IF NOT EXISTS EPI_cinema (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    director VARCHAR(150),
    genre VARCHAR(100),
    duration INT,
    session_date DATE NOT NULL,
    session_time VARCHAR(10),
    location VARCHAR(255),
    published BOOLEAN DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Supprimer un film
if (isset($_POST['delete_film'])) {
    csrf_protect();
    $id = (int)$_POST['delete_film'];
    $stmt = $pdo->prepare("SELECT image FROM EPI_cinema WHERE id = ?");
    $stmt->execute([$id]);
    $film = $stmt->fetch();

    if ($film && $film['image']) {
        @unlink('../uploads/cinema/' . $film['image']);
    }

    $stmt = $pdo->prepare("DELETE FROM EPI_cinema WHERE id = ?");
    $stmt->execute([$id]);
    $message = "Film supprime avec succes";
    $message_type = 'success';
}

// Ajouter ou modifier un film
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_film']) && !isset($_POST['admin_logout'])) {
    csrf_protect();
    $id = $_POST['id'] ?? null;
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $director = trim($_POST['director'] ?? '');
    $genre = trim($_POST['genre'] ?? '');
    $duration = (int)($_POST['duration'] ?? 0);
    $session_date = $_POST['session_date'] ?? '';
    $session_time = trim($_POST['session_time'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $published = isset($_POST['published']) ? 1 : 0;
    $current_image = $_POST['current_image'] ?? '';

    $image = $current_image;

    // Upload de l'affiche
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = $_FILES['image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $new_filename = uniqid() . '.' . $ext;

            if (!file_exists('../uploads/cinema')) {
                mkdir('../uploads/cinema', 0755, true);
            }

            if (move_uploaded_file($_FILES['image']['tmp_name'], '../uploads/cinema/' . $new_filename)) {
                if ($current_image && file_exists('../uploads/cinema/' . $current_image)) {
                    @unlink('../uploads/cinema/' . $current_image);
                }
                $image = $new_filename;
            }
        }
    }

    try {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE EPI_cinema SET title=?, description=?, image=?, director=?, genre=?, duration=?, session_date=?, session_time=?, location=?, published=? WHERE id=?");
            $stmt->execute([$title, $description, $image, $director, $genre, $duration ?: null, $session_date, $session_time, $location, $published, $id]);
            $message = "Film modifie avec succes";
        } else {
            $stmt = $pdo->prepare("INSERT INTO EPI_cinema (title, description, image, director, genre, duration, session_date, session_time, location, published) VALUES (?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$title, $description, $image, $director, $genre, $duration ?: null, $session_date, $session_time, $location, $published]);
            $message = "Film ajoute avec succes";
        }
        $message_type = 'success';
    } catch (PDOException $e) {
        error_log("Cinema admin error: " . $e->getMessage());
        $message = "Une erreur est survenue lors de l'enregistrement.";
        $message_type = 'error';
    }
}

// Recuperer un film pour modification
$edit_film = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM EPI_cinema WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_film = $stmt->fetch();
}

// Recuperer tous les films groupes par mois
$all_films = $pdo->query("SELECT * FROM EPI_cinema ORDER BY session_date DESC, session_time ASC")->fetchAll();

// Grouper par mois
$films_by_month = [];
foreach ($all_films as $film) {
    $month_key = date('Y-m', strtotime($film['session_date']));
    $month_label = strftime_fr($film['session_date']);
    if (!isset($films_by_month[$month_key])) {
        $films_by_month[$month_key] = ['label' => $month_label, 'films' => []];
    }
    $films_by_month[$month_key]['films'][] = $film;
}

function strftime_fr($date) {
    $mois = ['janvier','fevrier','mars','avril','mai','juin','juillet','aout','septembre','octobre','novembre','decembre'];
    $m = (int)date('m', strtotime($date)) - 1;
    $y = date('Y', strtotime($date));
    return ucfirst($mois[$m]) . ' ' . $y;
}

// Stats
$total = count($all_films);
$upcoming = 0;
$today = date('Y-m-d');
foreach ($all_films as $f) {
    if ($f['session_date'] >= $today) $upcoming++;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sorties Cinema - Administration</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-container { display: flex; min-height: 100vh; }
        .admin-sidebar { width: 250px; background: var(--text-primary); color: white; padding: 2rem 0; }
        .admin-sidebar h2 { color: white; padding: 0 1.5rem; margin-bottom: 2rem; }
        .admin-menu a {
            display: block; padding: 1rem 1.5rem; color: rgba(255,255,255,0.8);
            text-decoration: none; transition: all 0.3s; border-left: 3px solid transparent;
        }
        .admin-menu a:hover, .admin-menu a.active {
            background: rgba(255,255,255,0.1); color: white; border-left-color: var(--primary-color);
        }
        .admin-content { flex: 1; padding: 2rem; background: var(--background-light); }
        .admin-header {
            background: white; padding: 1.5rem 2rem; margin: -2rem -2rem 2rem;
            box-shadow: var(--shadow-sm); display: flex; justify-content: space-between; align-items: center;
        }

        .stats-row {
            display: flex; gap: 1rem; margin-bottom: 2rem;
        }
        .stat-card {
            background: white; padding: 1.25rem 1.5rem; border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm); flex: 1; text-align: center;
        }
        .stat-card .stat-number {
            font-size: 2rem; font-weight: 700; color: var(--primary-color);
        }
        .stat-card .stat-label {
            color: var(--text-secondary); font-size: 0.85rem; margin-top: 0.25rem;
        }

        .form-image-preview {
            max-width: 150px; margin-top: 0.5rem; border-radius: var(--radius-md);
        }

        .month-section {
            background: white; border-radius: var(--radius-lg); overflow: hidden;
            box-shadow: var(--shadow-md); margin-bottom: 1.5rem;
        }
        .month-header {
            padding: 0.75rem 1.5rem; background: var(--primary-color); color: white;
            font-weight: 600; font-size: 1rem;
            display: flex; justify-content: space-between; align-items: center;
        }
        .month-header .film-count {
            background: rgba(255,255,255,0.25); padding: 0.15rem 0.6rem;
            border-radius: 999px; font-size: 0.8rem;
        }

        .film-row {
            display: flex; gap: 1rem; padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            align-items: center;
        }
        .film-row:last-child { border-bottom: none; }
        .film-row.past { opacity: 0.6; }

        .film-poster {
            width: 60px; height: 85px; border-radius: var(--radius-sm);
            object-fit: cover; flex-shrink: 0;
            background: var(--background-light);
        }
        .film-info { flex: 1; }
        .film-info h4 { margin: 0 0 0.25rem; font-size: 1rem; }
        .film-meta {
            font-size: 0.8rem; color: var(--text-secondary);
            display: flex; flex-wrap: wrap; gap: 0.5rem;
        }
        .film-meta span { display: flex; align-items: center; gap: 0.2rem; }
        .film-date-badge {
            background: var(--secondary-color); color: white;
            padding: 0.25rem 0.6rem; border-radius: var(--radius-sm);
            font-size: 0.8rem; font-weight: 600; white-space: nowrap;
        }
        .film-date-badge.past-date {
            background: var(--text-secondary);
        }
        .film-actions {
            display: flex; gap: 0.5rem; flex-shrink: 0;
        }
        .btn-sm {
            padding: 0.3rem 0.75rem; font-size: 0.8rem; border-radius: var(--radius-sm);
            text-decoration: none; display: inline-block; border: none; cursor: pointer;
        }
        .btn-edit { background: var(--primary-color); color: white; }
        .btn-edit:hover { background: var(--primary-dark); color: white; }
        .btn-del { background: var(--error); color: white; }
        .btn-del:hover { background: #dc2626; color: white; }

        .badge { display: inline-block; padding: 0.15rem 0.5rem; border-radius: var(--radius-sm); font-size: 0.7rem; font-weight: 600; }
        .badge-published { background: #d1fae5; color: var(--success); }
        .badge-draft { background: #fee2e2; color: var(--error); }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-sidebar">
            <h2>📊 Admin Panel</h2>
            <nav class="admin-menu">
                <a href="index.php">🏠 Tableau de bord</a>
                <a href="news.php">📰 Actualités</a>
                <a href="cinema.php" class="active">🎬 Cinema</a>
                <a href="members.php">👥 Membres</a>
                <a href="gallery.php">📸 Galerie</a>
                <a href="press.php">📄 Presse</a>
                <a href="videos.php">🎥 Vidéos</a>
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
                <h1>Sorties Cinema</h1>
            </div>

            <?php if ($message): ?>
                <div class="form-message <?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Stats -->
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total; ?></div>
                    <div class="stat-label">Films au total</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $upcoming; ?></div>
                    <div class="stat-label">Seances a venir</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($films_by_month); ?></div>
                    <div class="stat-label">Mois programmes</div>
                </div>
            </div>

            <!-- Formulaire -->
            <div style="background: white; padding: 2rem; border-radius: var(--radius-lg); margin-bottom: 2rem; box-shadow: var(--shadow-md);">
                <h2 style="margin-bottom: 1.5rem;"><?php echo $edit_film ? 'Modifier le film' : 'Ajouter un film'; ?></h2>
                <form method="POST" enctype="multipart/form-data">
                    <?php echo csrf_field(); ?>
                    <?php if ($edit_film): ?>
                        <input type="hidden" name="id" value="<?php echo $edit_film['id']; ?>">
                        <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($edit_film['image'] ?? ''); ?>">
                    <?php endif; ?>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label class="form-label">Titre du film *</label>
                            <input type="text" name="title" class="form-control"
                                   value="<?php echo htmlspecialchars($edit_film['title'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Realisateur</label>
                            <input type="text" name="director" class="form-control"
                                   value="<?php echo htmlspecialchars($edit_film['director'] ?? ''); ?>"
                                   placeholder="Ex: Christopher Nolan">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label class="form-label">Date de la seance *</label>
                            <input type="date" name="session_date" class="form-control"
                                   value="<?php echo htmlspecialchars($edit_film['session_date'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Heure</label>
                            <input type="text" name="session_time" class="form-control"
                                   value="<?php echo htmlspecialchars($edit_film['session_time'] ?? ''); ?>"
                                   placeholder="Ex: 14h30">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Genre</label>
                            <input type="text" name="genre" class="form-control"
                                   value="<?php echo htmlspecialchars($edit_film['genre'] ?? ''); ?>"
                                   placeholder="Ex: Comedie, Drame..."
                                   list="genres-list">
                            <datalist id="genres-list">
                                <option value="Comedie">
                                <option value="Drame">
                                <option value="Action">
                                <option value="Thriller">
                                <option value="Animation">
                                <option value="Documentaire">
                                <option value="Science-fiction">
                                <option value="Aventure">
                                <option value="Historique">
                                <option value="Romance">
                            </datalist>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Duree (min)</label>
                            <input type="number" name="duration" class="form-control"
                                   value="<?php echo htmlspecialchars($edit_film['duration'] ?? ''); ?>"
                                   placeholder="Ex: 120" min="1">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Lieu</label>
                        <input type="text" name="location" class="form-control"
                               value="<?php echo htmlspecialchars($edit_film['location'] ?? ''); ?>"
                               placeholder="Ex: Cinema Le Bretagne, Brest">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Synopsis / Description</label>
                        <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($edit_film['description'] ?? ''); ?></textarea>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; align-items: start;">
                        <div class="form-group">
                            <label class="form-label">Affiche du film</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                            <?php if ($edit_film && $edit_film['image']): ?>
                                <img src="../uploads/cinema/<?php echo htmlspecialchars($edit_film['image']); ?>"
                                     class="form-image-preview">
                            <?php endif; ?>
                        </div>
                        <div class="form-group" style="padding-top: 1.75rem;">
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                <input type="checkbox" name="published"
                                       <?php echo ($edit_film['published'] ?? 1) ? 'checked' : ''; ?>>
                                <span>Publier cette sortie</span>
                            </label>
                        </div>
                    </div>

                    <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                        <button type="submit" class="btn btn-primary">
                            <?php echo $edit_film ? 'Modifier' : 'Ajouter le film'; ?>
                        </button>
                        <?php if ($edit_film): ?>
                            <a href="cinema.php" class="btn btn-secondary">Annuler</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Liste des films par mois -->
            <h2 style="margin-bottom: 1.5rem;">Programme (<?php echo $total; ?> film<?php echo $total > 1 ? 's' : ''; ?>)</h2>

            <?php if (empty($all_films)): ?>
                <p style="text-align: center; padding: 2rem; color: var(--text-secondary);">Aucun film programme</p>
            <?php else: ?>
                <?php foreach ($films_by_month as $month_key => $month_data): ?>
                    <div class="month-section">
                        <div class="month-header">
                            <span><?php echo htmlspecialchars($month_data['label']); ?></span>
                            <span class="film-count"><?php echo count($month_data['films']); ?> film(s)</span>
                        </div>
                        <?php foreach ($month_data['films'] as $film):
                            $is_past = $film['session_date'] < $today;
                        ?>
                            <div class="film-row <?php echo $is_past ? 'past' : ''; ?>">
                                <?php if ($film['image']): ?>
                                    <img src="../uploads/cinema/<?php echo htmlspecialchars($film['image']); ?>"
                                         alt="<?php echo htmlspecialchars($film['title']); ?>"
                                         class="film-poster">
                                <?php else: ?>
                                    <div class="film-poster" style="display:flex;align-items:center;justify-content:center;font-size:1.5rem;color:var(--text-secondary);">
                                        F
                                    </div>
                                <?php endif; ?>

                                <div class="film-info">
                                    <h4>
                                        <?php echo htmlspecialchars($film['title']); ?>
                                        <?php if (!$film['published']): ?>
                                            <span class="badge badge-draft">Brouillon</span>
                                        <?php endif; ?>
                                    </h4>
                                    <div class="film-meta">
                                        <?php if ($film['director']): ?>
                                            <span>De <?php echo htmlspecialchars($film['director']); ?></span>
                                        <?php endif; ?>
                                        <?php if ($film['genre']): ?>
                                            <span>| <?php echo htmlspecialchars($film['genre']); ?></span>
                                        <?php endif; ?>
                                        <?php if ($film['duration']): ?>
                                            <span>| <?php echo $film['duration']; ?> min</span>
                                        <?php endif; ?>
                                        <?php if ($film['location']): ?>
                                            <span>| <?php echo htmlspecialchars($film['location']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <span class="film-date-badge <?php echo $is_past ? 'past-date' : ''; ?>">
                                    <?php echo date('d/m', strtotime($film['session_date']));
                                    if ($film['session_time']) echo ' - ' . htmlspecialchars($film['session_time']); ?>
                                </span>

                                <div class="film-actions">
                                    <a href="?edit=<?php echo $film['id']; ?>" class="btn-sm btn-edit">Modifier</a>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer ce film ?')">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="delete_film" value="<?php echo (int)$film['id']; ?>">
                                        <button type="submit" class="btn-sm btn-del">Supprimer</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>