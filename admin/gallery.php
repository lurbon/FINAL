<?php
// Augmenter les limites pour l'upload de nombreux fichiers
@ini_set('memory_limit', '512M');
@ini_set('max_execution_time', '300');
@ini_set('post_max_size', '512M');
@ini_set('upload_max_filesize', '50M');

require_once '../includes/config.php';
require_once 'check_auth.php';
require_once '../includes/csrf.php';
require_once '../includes/sanitize.php';

$message = '';
$message_type = '';

// ========== GESTION DES ANNÉES ==========

// Ajouter une année
if (isset($_POST['add_year'])) {
    csrf_protect();
    $year = (int)$_POST['year'];
    $description = sanitize_text($_POST['year_description'] ?? '', 500);

    $stmt = $pdo->prepare("INSERT INTO EPI_year (year, description) VALUES (?, ?)");
    try {
        $stmt->execute([$year, $description]);
        $message = "Année $year ajoutée avec succès";
        $message_type = 'success';
    } catch (PDOException $e) {
        $message = "Cette année existe déjà";
        $message_type = 'error';
    }
}

// Supprimer une année (POST uniquement)
if (isset($_POST['delete_year'])) {
    csrf_protect();
    $year_id = (int)$_POST['delete_year'];
    $stmt = $pdo->prepare("DELETE FROM EPI_year WHERE id = ?");
    $stmt->execute([$year_id]);
    $message = "Année supprimée avec succès (ainsi que ses événements et photos)";
    $message_type = 'success';
}

// ========== GESTION DES ÉVÉNEMENTS ==========

// Ajouter un événement
if (isset($_POST['add_event'])) {
    csrf_protect();
    $year_id = (int)$_POST['year_id'];
    $name = sanitize_text($_POST['event_name'] ?? '', 255);
    $description = sanitize_text($_POST['event_description'] ?? '', 2000);
    $event_date = sanitize_date($_POST['event_date'] ?? '') ?: null;

    // Gérer l'image de couverture avec validation MIME
    $cover_image = null;
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === 0) {
        $upload = validate_upload($_FILES['cover_image']);
        if ($upload['valid']) {
            if (!file_exists('../uploads/gallery')) {
                mkdir('../uploads/gallery', 0755, true);
            }
            $cover_image = safe_filename('cover', $upload['ext']);
            move_uploaded_file($_FILES['cover_image']['tmp_name'], '../uploads/gallery/' . $cover_image);
        } else {
            $message = $upload['error'];
            $message_type = 'error';
        }
    }

    if ($message_type !== 'error') {
        $stmt = $pdo->prepare("INSERT INTO EPI_gallery_event (year_id, name, description, event_date, cover_image) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$year_id, $name, $description, $event_date, $cover_image]);
        $message = "Événement ajouté avec succès";
        $message_type = 'success';
    }
}

// Modifier un événement
if (isset($_POST['edit_event'])) {
    csrf_protect();
    $event_id = (int)$_POST['event_id'];
    $name = sanitize_text($_POST['event_name'] ?? '', 255);
    $description = sanitize_text($_POST['event_description'] ?? '', 2000);
    $event_date = sanitize_date($_POST['event_date'] ?? '') ?: null;

    // Récupérer l'image actuelle
    $stmt = $pdo->prepare("SELECT cover_image FROM EPI_gallery_event WHERE id = ?");
    $stmt->execute([$event_id]);
    $current_event = $stmt->fetch();
    $cover_image = $current_event['cover_image'];

    // Gérer la nouvelle image de couverture avec validation MIME
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === 0) {
        $upload = validate_upload($_FILES['cover_image']);
        if ($upload['valid']) {
            if (!file_exists('../uploads/gallery')) {
                mkdir('../uploads/gallery', 0755, true);
            }
            if ($cover_image) {
                @unlink('../uploads/gallery/' . $cover_image);
            }
            $cover_image = safe_filename('cover', $upload['ext']);
            move_uploaded_file($_FILES['cover_image']['tmp_name'], '../uploads/gallery/' . $cover_image);
        }
    }
    
    // Supprimer l'image si demandé
    if (isset($_POST['remove_cover_image']) && $_POST['remove_cover_image'] == '1') {
        if ($cover_image) {
            @unlink('../uploads/gallery/' . $cover_image);
        }
        $cover_image = null;
    }
    
    $stmt = $pdo->prepare("UPDATE EPI_gallery_event SET name = ?, description = ?, event_date = ?, cover_image = ? WHERE id = ?");
    $stmt->execute([$name, $description, $event_date, $cover_image, $event_id]);
    $message = "Événement modifié avec succès";
    $message_type = 'success';
}

// Supprimer un événement (POST uniquement)
if (isset($_POST['delete_event'])) {
    csrf_protect();
    $event_id = (int)$_POST['delete_event'];
    
    // Supprimer l'image de couverture
    $stmt = $pdo->prepare("SELECT cover_image FROM EPI_gallery_event WHERE id = ?");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch();
    if ($event && $event['cover_image']) {
        @unlink('../uploads/gallery/' . $event['cover_image']);
    }
    
    // Supprimer toutes les photos de l'événement
    $stmt = $pdo->prepare("SELECT image FROM EPI_gallery WHERE event_id = ?");
    $stmt->execute([$event_id]);
    $photos = $stmt->fetchAll();
    foreach ($photos as $photo) {
        if ($photo['image']) {
            @unlink('../uploads/gallery/' . $photo['image']);
        }
    }
    
    $stmt = $pdo->prepare("DELETE FROM EPI_gallery_event WHERE id = ?");
    $stmt->execute([$event_id]);
    $message = "Événement supprimé avec succès (ainsi que ses photos)";
    $message_type = 'success';
}

// ========== GESTION DES PHOTOS ==========

// Fonction pour compresser les images
function compressImage($source, $destination, $quality = 80) {
    $info = getimagesize($source);
    
    if ($info === false) {
        return false;
    }
    
    $image = null;
    switch ($info['mime']) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($source);
            break;
        case 'image/png':
            $image = imagecreatefrompng($source);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($source);
            break;
        case 'image/webp':
            $image = imagecreatefromwebp($source);
            break;
        default:
            return false;
    }
    
    if ($image === false) {
        return false;
    }
    
    // Redimensionner si trop grande (max 1920px de largeur)
    $width = imagesx($image);
    $height = imagesy($image);
    $max_width = 1920;
    
    if ($width > $max_width) {
        $new_width = $max_width;
        $new_height = intval($height * ($max_width / $width));
        $resized = imagecreatetruecolor($new_width, $new_height);
        
        // Préserver la transparence pour PNG
        if ($info['mime'] == 'image/png') {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
        }
        
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
        imagedestroy($image);
        $image = $resized;
    }
    
    // Sauvegarder avec compression
    $result = false;
    if ($info['mime'] == 'image/png') {
        $result = imagepng($image, $destination, 9);
    } else {
        $result = imagejpeg($image, $destination, $quality);
    }
    
    imagedestroy($image);
    return $result;
}

// Ajouter des photos à un événement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['event_images'])) {
    csrf_protect();
    $event_id = (int)$_POST['event_id'];
    $uploaded = 0;
    $errors = 0;
    $error_details = [];

    if (!file_exists('../uploads/gallery')) {
        mkdir('../uploads/gallery', 0755, true);
    }

    $files = $_FILES['event_images'];
    $file_count = count($files['name']);

    // Limiter le nombre de fichiers par upload
    $max_files_per_upload = 50;
    if ($file_count > $max_files_per_upload) {
        $message = "Maximum $max_files_per_upload fichiers par upload. Veuillez diviser votre upload.";
        $message_type = 'error';
    } else {
        for ($i = 0; $i < $file_count; $i++) {
            if ($files['error'][$i] !== 0) {
                $error_details[] = e($files['name'][$i]) . " (erreur upload)";
                $errors++;
                continue;
            }

            // Validation MIME avec notre fonction centralisée
            $singleFile = [
                'name'     => $files['name'][$i],
                'type'     => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error'    => $files['error'][$i],
                'size'     => $files['size'][$i],
            ];
            $upload = validate_upload($singleFile);
            if (!$upload['valid']) {
                $error_details[] = e($files['name'][$i]) . " (" . $upload['error'] . ")";
                $errors++;
                continue;
            }

            $filename = $files['name'][$i];
            $new_filename = safe_filename('gallery', $upload['ext']);
            $title = pathinfo($filename, PATHINFO_FILENAME);
            $temp_path = $files['tmp_name'][$i];
            $final_path = '../uploads/gallery/' . $new_filename;
            
            // Essayer de compresser l'image
            $compressed = compressImage($temp_path, $final_path, 85);
            
            // Si la compression échoue, copier le fichier tel quel
            if (!$compressed) {
                $compressed = move_uploaded_file($temp_path, $final_path);
            }
            
            if ($compressed) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO EPI_gallery (title, image, event_id) VALUES (?, ?, ?)");
                    $stmt->execute([$title, $new_filename, $event_id]);
                    $uploaded++;
                } catch (PDOException $e) {
                    $error_details[] = $filename . " (erreur BDD)";
                    @unlink($final_path);
                    $errors++;
                }
            } else {
                $error_details[] = $filename . " (échec sauvegarde)";
                $errors++;
            }
        }
        
        if ($uploaded > 0) {
            $message = "✅ $uploaded photo(s) ajoutée(s) avec succès";
            if ($errors > 0) {
                $message .= " | ⚠️ $errors fichier(s) en erreur";
                if (count($error_details) <= 5) {
                    $message .= ": " . implode(", ", $error_details);
                }
            }
            $message_type = 'success';
        } else {
            $message = "❌ Aucune photo n'a pu être ajoutée";
            if (!empty($error_details) && count($error_details) <= 10) {
                $message .= ": " . implode(", ", $error_details);
            }
            $message_type = 'error';
        }
    }
}

// Supprimer une photo
if (isset($_GET['delete_photo'])) {
    $id = (int)$_GET['delete_photo'];
    $stmt = $pdo->prepare("SELECT image FROM EPI_gallery WHERE id = ?");
    $stmt->execute([$id]);
    $photo = $stmt->fetch();
    
    if ($photo && $photo['image']) {
        @unlink('../uploads/gallery/' . $photo['image']);
    }
    
    $stmt = $pdo->prepare("DELETE FROM EPI_gallery WHERE id = ?");
    $stmt->execute([$id]);
    $message = "Photo supprimée avec succès";
    $message_type = 'success';
}

// ========== RÉCUPÉRATION DES DONNÉES ==========

// Récupérer toutes les années
$years = $pdo->query("SELECT * FROM EPI_year ORDER BY year DESC")->fetchAll();

// Récupérer tous les événements avec compteur de photos
$events_query = "
    SELECT 
        e.*,
        y.year,
        COUNT(g.id) as photo_count
    FROM EPI_gallery_event e
    JOIN EPI_year y ON e.year_id = y.id
    LEFT JOIN EPI_gallery g ON e.id = g.event_id
    GROUP BY e.id
    ORDER BY y.year DESC, e.event_date DESC
";
$events = $pdo->query($events_query)->fetchAll();

// Organiser les événements par année
$events_by_year = [];
foreach ($events as $event) {
    $year = $event['year'];
    if (!isset($events_by_year[$year])) {
        $events_by_year[$year] = [];
    }
    $events_by_year[$year][] = $event;
}

// Statistiques globales
$total_photos = $pdo->query("SELECT COUNT(*) FROM EPI_gallery WHERE event_id IS NOT NULL")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Galerie - Administration</title>
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
        }
        
        .stat-card-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .stat-card-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-top: 0.25rem;
        }
        
        .section-card {
            background: white;
            padding: 2rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            margin-bottom: 2rem;
        }
        
        .section-card h2 {
            margin-bottom: 1.5rem;
            color: var(--text-primary);
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }
        
        .event-item {
            background: var(--background-light);
            padding: 1.25rem;
            border-radius: var(--radius-md);
            margin-bottom: 1rem;
            border: 2px solid transparent;
            transition: border-color 0.2s;
        }
        
        .event-item:hover {
            border-color: var(--primary-color);
        }
        
        .event-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .event-info h4 {
            margin: 0 0 0.5rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .event-cover-preview {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-sm);
            object-fit: cover;
            border: 2px solid white;
            box-shadow: var(--shadow-sm);
        }
        
        .event-meta {
            font-size: 0.85rem;
            color: var(--text-secondary);
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .event-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .btn-small {
            padding: 0.4rem 0.8rem;
            font-size: 0.85rem;
        }
        
        .btn-danger {
            background: var(--error);
            color: white;
        }
        
        .btn-danger:hover {
            background: #dc2626;
        }
        
        .btn-warning {
            background: #f59e0b;
            color: white;
        }
        
        .btn-warning:hover {
            background: #d97706;
        }
        
        .upload-zone {
            border: 2px dashed var(--border-color);
            border-radius: var(--radius-lg);
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: var(--background-light);
            position: relative;
        }
        
        .upload-zone:hover {
            border-color: var(--primary-color);
            background: rgba(37, 99, 235, 0.05);
        }
        
        .upload-zone input[type="file"] {
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            opacity: 0; cursor: pointer;
        }
        
        .photos-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 0.75rem;
            margin-top: 1rem;
        }
        
        .photo-thumb {
            position: relative;
            aspect-ratio: 1;
            border-radius: var(--radius-md);
            overflow: hidden;
        }
        
        .photo-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .photo-thumb-delete {
            position: absolute;
            top: 4px;
            right: 4px;
            background: rgba(0,0,0,0.7);
            color: white;
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .collapsible {
            cursor: pointer;
            user-select: none;
        }
        
        .collapsible::before {
            content: '▼';
            display: inline-block;
            margin-right: 0.5rem;
            transition: transform 0.2s;
        }
        
        .collapsible.collapsed::before {
            transform: rotate(-90deg);
        }
        
        .collapse-content {
            max-height: 5000px;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
        }
        
        .collapse-content.hidden {
            max-height: 0;
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            animation: fadeIn 0.2s;
            overflow-y: auto;
            padding: 2rem 1rem;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .modal-content {
            background-color: white;
            margin: 0 auto;
            padding: 0;
            border-radius: var(--radius-lg);
            width: 90%;
            max-width: 600px;
            box-shadow: var(--shadow-xl);
            animation: slideDown 0.3s;
            position: relative;
        }
        
        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 2rem;
            border-bottom: 2px solid var(--background-light);
            cursor: move;
            user-select: none;
            background: var(--background-light);
            border-radius: var(--radius-lg) var(--radius-lg) 0 0;
        }
        
        .modal-header h3 {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .modal-header h3::before {
            content: '⋮⋮';
            color: var(--text-secondary);
            font-size: 1.2rem;
            letter-spacing: -2px;
            opacity: 0.5;
        }
        
        .modal-header:hover h3::before {
            opacity: 1;
        }
        
        .modal-header::after {
            content: 'Déplacer';
            position: absolute;
            top: -30px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: var(--radius-sm);
            font-size: 0.75rem;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s;
        }
        
        .modal-header:hover::after {
            opacity: 1;
        }
        
        .modal-body {
            padding: 2rem;
            max-height: 70vh;
            overflow-y: auto;
        }
        
        .close-modal {
            font-size: 2rem;
            font-weight: 300;
            color: var(--text-secondary);
            cursor: pointer;
            border: none;
            background: white;
            padding: 0;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.2s;
            flex-shrink: 0;
        }
        
        .close-modal:hover {
            background: var(--error);
            color: white;
            transform: rotate(90deg);
        }
        
        .current-cover-preview {
            max-width: 200px;
            max-height: 150px;
            border-radius: var(--radius-md);
            margin: 0.5rem 0;
        }
        
        .remove-cover-checkbox {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
            padding: 0.5rem;
            background: var(--background-light);
            border-radius: var(--radius-sm);
        }
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
                <a href="gallery.php" class="active">📸 Galerie</a>
                <a href="press.php">📄 Presse</a>
                <a href="videos.php">🎥 Vidéos</a>
                <a href="messages.php">✉️ Messages</a>
                <a href="../index.php" target="_blank">🌐 Voir le site</a>
                <a href="?logout=1" style="margin-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1rem;">🚪 Déconnexion</a>
            </nav>
        </div>

        <div class="admin-content">
            <div class="admin-header">
                <h1>📸 Galerie hiérarchique</h1>
                <a href="../photos.php" target="_blank" class="btn btn-secondary btn-small">Voir la galerie publique</a>
            </div>

            <?php if ($message): ?>
                <div class="form-message <?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Statistiques -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-card-value"><?php echo count($years); ?></div>
                    <div class="stat-card-label">Année(s)</div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-value"><?php echo count($events); ?></div>
                    <div class="stat-card-label">Événement(s)</div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-value"><?php echo $total_photos; ?></div>
                    <div class="stat-card-label">Photo(s)</div>
                </div>
            </div>

            <!-- Ajouter une année -->
            <div class="section-card">
                <h2>➕ Ajouter une année</h2>
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Année *</label>
                            <input type="number" name="year" class="form-control" 
                                   min="1900" max="2100" 
                                   value="<?php echo date('Y'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Description (optionnelle)</label>
                            <input type="text" name="year_description" class="form-control" 
                                   placeholder="Ex: Saison 2024">
                        </div>
                    </div>
                    <button type="submit" name="add_year" class="btn btn-primary">Ajouter l'année</button>
                </form>
            </div>

            <!-- Liste des années et événements -->
            <?php foreach ($years as $year): ?>
                <div class="section-card">
                    <h2 class="collapsible" onclick="toggleCollapse(this)">
                        <?php echo $year['year']; ?>
                        <?php if ($year['description']): ?>
                            <small style="color: var(--text-secondary); font-weight: 400;">
                                - <?php echo htmlspecialchars($year['description']); ?>
                            </small>
                        <?php endif; ?>
                        <small style="color: var(--text-secondary); font-weight: 400; float: right;">
                            <?php echo isset($events_by_year[$year['year']]) ? count($events_by_year[$year['year']]) : 0; ?> événement(s)
                        </small>
                    </h2>
                    
                    <div class="collapse-content">
                        <!-- Ajouter un événement -->
                        <div style="background: var(--background-light); padding: 1.5rem; border-radius: var(--radius-md); margin-bottom: 1.5rem;">
                            <h3 style="margin-bottom: 1rem; font-size: 1.1rem;">➕ Nouvel événement</h3>
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="year_id" value="<?php echo $year['id']; ?>">
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label class="form-label">Nom de l'événement *</label>
                                        <input type="text" name="event_name" class="form-control" 
                                               placeholder="Ex: Tournoi de printemps" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Date</label>
                                        <input type="date" name="event_date" class="form-control">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Description</label>
                                    <input type="text" name="event_description" class="form-control" 
                                           placeholder="Description de l'événement">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Image de couverture</label>
                                    <input type="file" name="cover_image" class="form-control" accept="image/*">
                                </div>
                                <button type="submit" name="add_event" class="btn btn-primary btn-small">
                                    Créer l'événement
                                </button>
                            </form>
                        </div>

                        <!-- Liste des événements -->
                        <?php if (isset($events_by_year[$year['year']])): ?>
                            <?php foreach ($events_by_year[$year['year']] as $event): ?>
                                <div class="event-item">
                                    <div class="event-header">
                                        <div class="event-info" style="flex: 1;">
                                            <h4>
                                                <?php if ($event['cover_image']): ?>
                                                    <img src="../uploads/gallery/<?php echo htmlspecialchars($event['cover_image']); ?>" 
                                                         alt="Cover" class="event-cover-preview">
                                                <?php endif; ?>
                                                <?php echo htmlspecialchars($event['name']); ?>
                                            </h4>
                                            <div class="event-meta">
                                                <?php if ($event['event_date']): ?>
                                                    <span>📅 <?php echo date('d/m/Y', strtotime($event['event_date'])); ?></span>
                                                <?php endif; ?>
                                                <span>📷 <?php echo $event['photo_count']; ?> photo(s)</span>
                                            </div>
                                            <?php if ($event['description']): ?>
                                                <p style="font-size: 0.9rem; color: var(--text-secondary); margin: 0.75rem 0 0 0;">
                                                    <?php echo htmlspecialchars($event['description']); ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="event-actions">
                                        <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($event)); ?>)" 
                                                class="btn btn-warning btn-small">
                                            ✏️ Modifier
                                        </button>
                                        <button onclick="toggleEventPhotos(<?php echo $event['id']; ?>)" 
                                                class="btn btn-secondary btn-small">
                                            📷 Photos (<?php echo $event['photo_count']; ?>)
                                        </button>
                                        <a href="?delete_event=<?php echo $event['id']; ?>" 
                                           onclick="return confirm('⚠️ Supprimer cet événement et toutes ses <?php echo $event['photo_count']; ?> photo(s) ?')"
                                           class="btn btn-danger btn-small">
                                            🗑️ Supprimer
                                        </a>
                                    </div>
                                    
                                    <!-- Zone de gestion des photos (masquée par défaut) -->
                                    <div id="photos-<?php echo $event['id']; ?>" style="display: none; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 2px solid var(--border-color);">
                                        <h4 style="margin-bottom: 1rem;">📤 Ajouter des photos</h4>
                                        
                                        <!-- Message d'information -->
                                        <div style="background: #e3f2fd; border-left: 4px solid var(--primary-color); padding: 1rem; margin-bottom: 1rem; border-radius: 4px;">
                                            <strong>ℹ️ Conseils d'upload :</strong>
                                            <ul style="margin: 0.5rem 0 0 1.5rem; font-size: 0.9rem;">
                                                <li>Maximum <strong>50 fichiers</strong> par upload</li>
                                                <li>Taille max par image : <strong>10 MB</strong></li>
                                                <li>Les images seront automatiquement compressées et optimisées</li>
                                                <li>Pour beaucoup de photos, faites plusieurs uploads successifs</li>
                                            </ul>
                                        </div>
                                        
                                        <form method="POST" enctype="multipart/form-data" id="uploadForm-<?php echo $event['id']; ?>" onsubmit="return handleUpload(this, <?php echo $event['id']; ?>)">
                                            <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                            <div class="upload-zone">
                                                <input type="file" name="event_images[]" multiple accept="image/*" required id="fileInput-<?php echo $event['id']; ?>" onchange="updateFileCount(<?php echo $event['id']; ?>)">
                                                <div style="font-size: 2rem; margin-bottom: 0.5rem;">📷</div>
                                                <div style="color: var(--text-secondary);">
                                                    <strong style="color: var(--primary-color);">Cliquez ou glissez-déposez</strong><br>
                                                    <small>Formats acceptés: JPG, PNG, GIF, WebP</small><br>
                                                    <small id="fileCount-<?php echo $event['id']; ?>" style="color: var(--primary-color); font-weight: 600;"></small>
                                                </div>
                                            </div>
                                            
                                            <!-- Barre de progression -->
                                            <div id="uploadProgress-<?php echo $event['id']; ?>" style="display: none; margin-top: 1rem;">
                                                <div style="background: #e0e0e0; height: 30px; border-radius: 15px; overflow: hidden; position: relative;">
                                                    <div id="progressBar-<?php echo $event['id']; ?>" style="background: linear-gradient(90deg, var(--primary-color), var(--secondary-color)); height: 100%; width: 0%; transition: width 0.3s; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 0.9rem;">
                                                        <span id="progressText-<?php echo $event['id']; ?>">0%</span>
                                                    </div>
                                                </div>
                                                <p id="uploadStatus-<?php echo $event['id']; ?>" style="text-align: center; margin-top: 0.5rem; color: var(--text-secondary); font-size: 0.9rem;">Préparation...</p>
                                            </div>
                                            
                                            <button type="submit" class="btn btn-primary btn-small" style="margin-top: 1rem;" id="uploadBtn-<?php echo $event['id']; ?>">
                                                📤 Ajouter les photos
                                            </button>
                                        </form>
                                        
                                        <!-- Liste des photos existantes -->
                                        <?php
                                        $stmt = $pdo->prepare("SELECT * FROM EPI_gallery WHERE event_id = ? ORDER BY created_at DESC");
                                        $stmt->execute([$event['id']]);
                                        $event_photos = $stmt->fetchAll();
                                        ?>
                                        
                                        <?php if (!empty($event_photos)): ?>
                                            <h4 style="margin: 1.5rem 0 1rem;">📸 Photos de l'événement</h4>
                                            <div class="photos-list">
                                                <?php foreach ($event_photos as $photo): ?>
                                                    <div class="photo-thumb">
                                                        <img src="../uploads/gallery/<?php echo htmlspecialchars($photo['image']); ?>" 
                                                             alt="<?php echo htmlspecialchars($photo['title']); ?>">
                                                        <button class="photo-thumb-delete" 
                                                                onclick="if(confirm('Supprimer cette photo ?')) location.href='?delete_photo=<?php echo $photo['id']; ?>'">
                                                            ×
                                                        </button>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="text-align: center; color: var(--text-secondary); padding: 1rem;">
                                Aucun événement pour cette année
                            </p>
                        <?php endif; ?>
                        
                        <!-- Bouton supprimer l'année -->
                        <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--border-color);">
                            <a href="?delete_year=<?php echo $year['id']; ?>" 
                               onclick="return confirm('⚠️ Supprimer l\'année <?php echo $year['year']; ?> et tous ses événements ?')"
                               class="btn btn-danger btn-small">
                                🗑️ Supprimer l'année <?php echo $year['year']; ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (empty($years)): ?>
                <div class="section-card" style="text-align: center; padding: 3rem;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">📅</div>
                    <p style="color: var(--text-secondary);">Aucune année créée. Commencez par ajouter une année ci-dessus.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal de modification d'événement -->
    <div id="editModal" class="modal" onclick="if(event.target === this) closeEditModal()">
        <div class="modal-content" id="modalContent">
            <div class="modal-header" id="modalHeader">
                <h3>Modifier l'événement</h3>
                <button class="close-modal" onclick="closeEditModal()" type="button" title="Fermer (Échap)">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" enctype="multipart/form-data" id="editEventForm">
                    <input type="hidden" name="event_id" id="edit_event_id">
                    
                    <div class="form-group">
                        <label class="form-label">Nom de l'événement *</label>
                        <input type="text" name="event_name" id="edit_event_name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Date</label>
                        <input type="date" name="event_date" id="edit_event_date" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="event_description" id="edit_event_description" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Image de couverture actuelle</label>
                        <div id="current_cover_container" style="display: none;">
                            <img id="current_cover_image" class="current-cover-preview" alt="Image actuelle">
                            <div class="remove-cover-checkbox">
                                <input type="checkbox" name="remove_cover_image" value="1" id="remove_cover_checkbox">
                                <label for="remove_cover_checkbox" style="margin: 0; cursor: pointer;">Supprimer l'image de couverture</label>
                            </div>
                        </div>
                        <div id="no_cover_message" style="padding: 0.5rem; background: var(--background-light); border-radius: var(--radius-sm); color: var(--text-secondary); font-size: 0.9rem;">
                            Aucune image de couverture
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Nouvelle image de couverture</label>
                        <input type="file" name="cover_image" class="form-control" accept="image/*">
                        <small style="color: var(--text-secondary);">Laissez vide pour conserver l'image actuelle</small>
                    </div>
                    
                    <div style="display: flex; gap: 0.5rem; margin-top: 1.5rem;">
                        <button type="submit" name="edit_event" class="btn btn-primary">💾 Enregistrer</button>
                        <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Annuler</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script nonce="<?php echo csp_nonce(); ?>">
    function toggleCollapse(element) {
        element.classList.toggle('collapsed');
        const content = element.nextElementSibling;
        content.classList.toggle('hidden');
    }
    
    function toggleEventPhotos(eventId) {
        const photosDiv = document.getElementById('photos-' + eventId);
        photosDiv.style.display = photosDiv.style.display === 'none' ? 'block' : 'none';
    }
    
    // Mettre à jour le compteur de fichiers sélectionnés
    function updateFileCount(eventId) {
        const fileInput = document.getElementById('fileInput-' + eventId);
        const fileCount = document.getElementById('fileCount-' + eventId);
        const uploadBtn = document.getElementById('uploadBtn-' + eventId);
        
        if (fileInput.files.length > 0) {
            const count = fileInput.files.length;
            fileCount.textContent = count + ' fichier' + (count > 1 ? 's' : '') + ' sélectionné' + (count > 1 ? 's' : '');
            
            if (count > 50) {
                fileCount.textContent += ' ⚠️ ATTENTION: Maximum 50 fichiers!';
                fileCount.style.color = '#d32f2f';
                uploadBtn.disabled = true;
            } else {
                fileCount.style.color = 'var(--primary-color)';
                uploadBtn.disabled = false;
            }
        } else {
            fileCount.textContent = '';
            uploadBtn.disabled = false;
        }
    }
    
    // Gérer l'upload avec barre de progression
    function handleUpload(form, eventId) {
        const fileInput = document.getElementById('fileInput-' + eventId);
        const files = fileInput.files;
        
        if (files.length > 50) {
            alert('⚠️ Vous ne pouvez pas uploader plus de 50 fichiers à la fois. Veuillez diviser votre upload.');
            return false;
        }
        
        if (files.length === 0) {
            return false;
        }
        
        // Afficher la barre de progression
        const progressDiv = document.getElementById('uploadProgress-' + eventId);
        const progressBar = document.getElementById('progressBar-' + eventId);
        const progressText = document.getElementById('progressText-' + eventId);
        const uploadStatus = document.getElementById('uploadStatus-' + eventId);
        const uploadBtn = document.getElementById('uploadBtn-' + eventId);
        
        progressDiv.style.display = 'block';
        uploadBtn.disabled = true;
        uploadBtn.textContent = '⏳ Upload en cours...';
        
        // Simuler la progression (car on ne peut pas avoir la vraie progression avec un form POST classique)
        let progress = 0;
        const interval = setInterval(() => {
            progress += 2;
            if (progress >= 90) {
                clearInterval(interval);
                progressText.textContent = '90%';
                progressBar.style.width = '90%';
                uploadStatus.textContent = 'Finalisation...';
            } else {
                progressText.textContent = progress + '%';
                progressBar.style.width = progress + '%';
                uploadStatus.textContent = 'Upload de ' + files.length + ' fichier(s) en cours...';
            }
        }, 100);
        
        return true;
    }
    
    function openEditModal(event) {
        document.getElementById('editModal').style.display = 'block';
        document.getElementById('edit_event_id').value = event.id;
        document.getElementById('edit_event_name').value = event.name;
        document.getElementById('edit_event_date').value = event.event_date || '';
        document.getElementById('edit_event_description').value = event.description || '';
        
        // Gérer l'image de couverture
        if (event.cover_image) {
            document.getElementById('current_cover_container').style.display = 'block';
            document.getElementById('no_cover_message').style.display = 'none';
            document.getElementById('current_cover_image').src = '../uploads/gallery/' + event.cover_image;
            document.getElementById('remove_cover_checkbox').checked = false;
        } else {
            document.getElementById('current_cover_container').style.display = 'none';
            document.getElementById('no_cover_message').style.display = 'block';
        }
        
        // Réinitialiser la position du modal
        const modalContent = document.getElementById('modalContent');
        modalContent.style.transform = 'none';
        modalContent.style.top = '';
        modalContent.style.left = '';
        modalContent.style.position = 'relative';
        
        // Empêcher le scroll du body
        document.body.style.overflow = 'hidden';
    }
    
    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
        document.body.style.overflow = '';
    }
    
    // Fermer avec la touche Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modal = document.getElementById('editModal');
            if (modal && modal.style.display === 'block') {
                closeEditModal();
            }
        }
    });
    
    // Rendre le modal déplaçable (drag & drop)
    (function() {
        let isDragging = false;
        let currentX;
        let currentY;
        let initialX;
        let initialY;
        let xOffset = 0;
        let yOffset = 0;
        
        const modalHeader = document.getElementById('modalHeader');
        const modalContent = document.getElementById('modalContent');
        
        if (modalHeader && modalContent) {
            modalHeader.addEventListener('mousedown', dragStart);
            document.addEventListener('mousemove', drag);
            document.addEventListener('mouseup', dragEnd);
            
            // Support tactile
            modalHeader.addEventListener('touchstart', dragStart);
            document.addEventListener('touchmove', drag);
            document.addEventListener('touchend', dragEnd);
        }
        
        function dragStart(e) {
            // Ne pas déplacer si on clique sur le bouton de fermeture
            if (e.target.classList.contains('close-modal') || e.target.closest('.close-modal')) {
                return;
            }
            
            const clientX = e.type === 'touchstart' ? e.touches[0].clientX : e.clientX;
            const clientY = e.type === 'touchstart' ? e.touches[0].clientY : e.clientY;
            
            if (modalContent.style.position !== 'fixed') {
                // Première fois qu'on déplace - convertir en position fixe
                const rect = modalContent.getBoundingClientRect();
                modalContent.style.position = 'fixed';
                modalContent.style.margin = '0';
                modalContent.style.top = rect.top + 'px';
                modalContent.style.left = rect.left + 'px';
                xOffset = 0;
                yOffset = 0;
            }
            
            initialX = clientX - xOffset;
            initialY = clientY - yOffset;
            
            isDragging = true;
            modalHeader.style.cursor = 'grabbing';
        }
        
        function drag(e) {
            if (isDragging) {
                e.preventDefault();
                
                const clientX = e.type === 'touchmove' ? e.touches[0].clientX : e.clientX;
                const clientY = e.type === 'touchmove' ? e.touches[0].clientY : e.clientY;
                
                currentX = clientX - initialX;
                currentY = clientY - initialY;
                
                xOffset = currentX;
                yOffset = currentY;
                
                setTranslate(currentX, currentY, modalContent);
            }
        }
        
        function dragEnd() {
            if (isDragging) {
                initialX = currentX;
                initialY = currentY;
                isDragging = false;
                modalHeader.style.cursor = 'move';
            }
        }
        
        function setTranslate(xPos, yPos, el) {
            el.style.transform = `translate(${xPos}px, ${yPos}px)`;
        }
    })();
    </script>
</body>
</html>
