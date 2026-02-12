<?php
require_once '../includes/config.php';
require_once 'check_auth.php';

$message = '';
$message_type = '';

// Supprimer un membre
if (isset($_GET['delete'])) {
    $ID = (int)$_GET['delete'];
    $stmt = $pdo->prepare("SELECT user_photo FROM EPI_user WHERE ID = ?");
    $stmt->execute([$ID]);
    $member = $stmt->fetch();
    
    if ($member && $member['user_photo']) {
        @unlink('../uploads/members/' . $member['user_photo']);
    }
    
    $stmt = $pdo->prepare("DELETE FROM EPI_user WHERE ID = ?");
    $stmt->execute([$ID]);
    $message = "Membre supprimé avec succès";
    $message_type = 'success';
}

// Ajouter ou modifier un membre
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ID = $_POST['ID'] ?? null;
    $name = $_POST['user_nicename'] ?? '';
    $role = $_POST['user_role'] ?? '';
    $bio = $_POST['user_bio'] ?? '';
    $email = $_POST['user_email'] ?? '';
    $phone = $_POST['user_phone'] ?? '';
    $display_order = (int)($_POST['user_rang'] ?? 0);
    $current_photo = $_POST['current_photo'] ?? '';
    $password = $_POST['user_password'] ?? '';
    $password_confirm = $_POST['user_password_confirm'] ?? '';
    
    // Validation du mot de passe
    $password_error = false;
    if (!empty($password)) {
        if ($password !== $password_confirm) {
            $message = "Les mots de passe ne correspondent pas";
            $message_type = 'error';
            $password_error = true;
        } elseif (strlen($password) < 6) {
            $message = "Le mot de passe doit contenir au moins 6 caractères";
            $message_type = 'error';
            $password_error = true;
        }
    }
    
    $photo = $current_photo;
    
    // Upload de la photo
    if (isset($_FILES['user_photo']) && $_FILES['user_photo']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = $_FILES['user_photo']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $new_filename = uniqID() . '.' . $ext;
            
            // Créer le dossier s'il n'existe pas
            if (!file_exists('../uploads/members')) {
                mkdir('../uploads/members', 0755, true);
            }
            
            if (move_uploaded_file($_FILES['user_photo']['tmp_name'], '../uploads/members/' . $new_filename)) {
                if ($current_photo && file_exists('../uploads/members/' . $current_photo)) {
                    @unlink('../uploads/members/' . $current_photo);
                }
                $photo = $new_filename;
            }
        }
    }
    
    try {
        if (!$password_error) {
            if ($ID) {
                // Mise à jour
                if (!empty($password)) {
                    // Avec changement de mot de passe
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE EPI_user SET user_nicename = ?, user_role = ?, user_photo = ?, user_bio = ?, user_email = ?, user_phone = ?, user_rang = ?, user_password = ? WHERE ID = ?");
                    $stmt->execute([$name, $role, $photo, $bio, $email, $phone, $display_order, $hashed_password, $ID]);
                } else {
                    // Sans changement de mot de passe
                    $stmt = $pdo->prepare("UPDATE EPI_user SET user_nicename = ?, user_role = ?, user_photo = ?, user_bio = ?, user_email = ?, user_phone = ?, user_rang = ? WHERE ID = ?");
                    $stmt->execute([$name, $role, $photo, $bio, $email, $phone, $display_order, $ID]);
                }
                $message = "Membre modifié avec succès";
            } else {
                // Insertion - mot de passe obligatoire pour nouveau membre
                if (empty($password)) {
                    $message = "Le mot de passe est obligatoire pour un nouveau membre";
                    $message_type = 'error';
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO EPI_user (user_nicename, user_role, user_photo, user_bio, user_email, user_phone, user_rang, user_password) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$name, $role, $photo, $bio, $email, $phone, $display_order, $hashed_password]);
                    $message = "Membre ajouté avec succès";
                }
            }
            if (!isset($message_type) || $message_type !== 'error') {
                $message_type = 'success';
            }
        }
    } catch (PDOException $e) {
        error_log("Erreur members.php: " . $e->getMessage());
        $message = "Erreur lors de l'enregistrement. Veuillez réessayer.";
        $message_type = 'error';
    }
}

$edit_member = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM EPI_user WHERE ID = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_member = $stmt->fetch();
}

$members = $pdo->query("SELECT * FROM EPI_user ORDER BY user_rang ASC, ID ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="wIDth=device-wIDth, initial-scale=1.0">
    <title>Gestion des membres - Administration</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-container { display: flex; min-height: 100vh; }
        .admin-sIDebar {
            wIDth: 250px;
            background: var(--text-primary);
            color: white;
            padding: 2rem 0;
        }
        .admin-sIDebar h2 {
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
            border-left: 3px solID transparent;
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
            max-wIDth: 150px;
            margin-top: 1rem;
            border-radius: 50%;
            aspect-ratio: 1;
            object-fit: cover;
        }
        table {
            wIDth: 100%;
            background: white;
            border-radius: var(--radius-lg);
            overflow: hIDden;
            box-shadow: var(--shadow-md);
        }
        table th, table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solID var(--border-color);
        }
        table th {
            background: var(--background-dark);
            font-weight: 600;
        }
        table tr:last-child td {
            border-bottom: none;
        }
        table img {
            wIDth: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 50%;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-sIDebar">
            <h2>📊 Admin Panel</h2>
            <nav class="admin-menu">
                <a href="index.php">🏠 Tableau de bord</a>
                <a href="news.php">📰 Actualités</a>
                <a href="cinema.php">🎬 Cinema</a>
                <a href="members.php" class="active">👥 Membres</a>
                <a href="gallery.php">📸 Galerie</a>
                <a href="press.php">📄 Presse</a>
                <a href="vIDeos.php">🎥 VIDéos</a>
                <a href="messages.php">✉️ Messages</a>
                <a href="../index.php" target="_blank">🌐 Voir le site</a>
                <a href="?logout=1" style="margin-top: 2rem; border-top: 1px solID rgba(255,255,255,0.1); padding-top: 1rem;">
                    🚪 Déconnexion
                </a>
            </nav>
        </div>
        
        <div class="admin-content">
            <div class="admin-header">
                <h1>Gestion des membres du bureau</h1>
            </div>
            
            <?php if ($message): ?>
                <div class="form-message <?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <div style="background: white; padding: 2rem; border-radius: var(--radius-lg); margin-bottom: 2rem; box-shadow: var(--shadow-md);">
                <h2><?php echo $edit_member ? 'Modifier le membre' : 'Ajouter un membre'; ?></h2>
                <form method="POST" enctype="multipart/form-data">
                    <?php if ($edit_member): ?>
                        <input type="hIDden" name="ID" value="<?php echo $edit_member['ID']; ?>">
                        <input type="hIDden" name="current_photo" value="<?php echo htmlspecialchars($edit_member['user_photo']); ?>">
                    <?php endif; ?>
                    
                    <div style="display: grID; grID-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label class="form-label">Nom complet *</label>
                            <input type="text" name="user_nicename" class="form-control" 
                                   value="<?php echo htmlspecialchars($edit_member['user_nicename'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Fonction *</label>
                            <input type="text" name="user_role" class="form-control" 
                                   value="<?php echo htmlspecialchars($edit_member['user_role'] ?? ''); ?>" 
                                   placeholder="Ex: PrésIDent(e)" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Biographie</label>
                        <textarea name="user_bio" class="form-control" rows="4"><?php echo htmlspecialchars($edit_member['user_bio'] ?? ''); ?></textarea>
                    </div>
                    
                    <div style="display: grID; grID-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" name="user_email" class="form-control" 
                                   value="<?php echo htmlspecialchars($edit_member['user_email'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Téléphone</label>
                            <input type="text" name="user_phone" class="form-control" 
                                   value="<?php echo htmlspecialchars($edit_member['user_phone'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Ordre d'affichage</label>
                            <input type="number" name="user_rang" class="form-control" 
                                   value="<?php echo htmlspecialchars($edit_member['user_rang'] ?? 0); ?>">
                        </div>
                    </div>
                    
                    <div style="background: #f0f8ff; padding: 1.5rem; border-radius: var(--radius-md); margin: 1.5rem 0; border-left: 4px solID var(--primary-color);">
                        <h3 style="margin-top: 0; color: var(--primary-color); font-size: 1.1rem;">
                            ðŸ"� Mot de passe pour l'espace membre
                        </h3>
                        <div style="display: grID; grID-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label class="form-label">
                                    Mot de passe <?php echo !$edit_member ? '*' : '(laisser vIDe pour ne pas changer)'; ?>
                                </label>
                                <input type="password" name="user_password" class="form-control" 
                                       placeholder="Minimum 6 caractÃ¨res" 
                                       autocomplete="new-password"
                                       <?php echo !$edit_member ? 'required' : ''; ?>>
                                <small style="color: var(--text-secondary); font-size: 0.875rem;">
                                    <?php if ($edit_member): ?>
                                        Laissez vIDe pour conserver le mot de passe actuel
                                    <?php else: ?>
                                        Obligatoire pour créer un nouveau membre
                                    <?php endif; ?>
                                </small>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    Confirmer le mot de passe <?php echo !$edit_member ? '*' : ''; ?>
                                </label>
                                <input type="password" name="user_password_confirm" class="form-control" 
                                       placeholder="Retapez le mot de passe"
                                       autocomplete="new-password"
                                       <?php echo !$edit_member ? 'required' : ''; ?>>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Photo</label>
                        <input type="file" name="user_photo" class="form-control" accept="image/*">
                        <?php if ($edit_member && $edit_member['user_photo']): ?>
                            <img src="../uploads/members/<?php echo htmlspecialchars($edit_member['user_photo']); ?>" 
                                 class="form-image-preview">
                        <?php endif; ?>
                    </div>
                    
                    <div style="display: flex; gap: 1rem;">
                        <button type="submit" class="btn btn-primary">
                            <?php echo $edit_member ? 'Modifier' : 'Ajouter'; ?>
                        </button>
                        <?php if ($edit_member): ?>
                            <a href="members.php" class="btn btn-secondary">Annuler</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <h2>Liste des membres</h2>
            <?php if (empty($members)): ?>
                <p style="text-align: center; padding: 2rem; color: var(--text-secondary);">
                    Aucun membre pour le moment
                </p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Photo</th>
                            <th>Nom</th>
                            <th>Fonction</th>
                            <th>Contact</th>
                            <th>Ordre</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($members as $member): ?>
                            <tr>
                                <td>
                                    <?php if ($member['user_photo']): ?>
                                        <img src="../uploads/members/<?php echo htmlspecialchars($member['user_photo']); ?>">
                                    <?php else: ?>
                                        <div style="wIDth: 60px; height: 60px; background: var(--primary-color); 
                                                    border-radius: 50%; display: flex; align-items: center; 
                                                    justify-content: center; color: white; font-size: 1.5rem;">👤</div>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo htmlspecialchars($member['user_nicename']); ?></strong></td>
                                <td><?php echo htmlspecialchars($member['user_role']); ?></td>
                                <td>
                                    <?php if ($member['user_email']): ?>
                                        <div><?php echo htmlspecialchars($member['user_email']); ?></div>
                                    <?php endif; ?>
                                    <?php if ($member['user_phone']): ?>
                                        <div><?php echo htmlspecialchars($member['user_phone']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $member['user_rang']; ?></td>
                                <td>
                                    <a href="?edit=<?php echo $member['ID']; ?>" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                        Modifier
                                    </a>
                                    <a href="?delete=<?php echo $member['ID']; ?>" 
                                       onclick="return confirm('Supprimer ce membre ?')"
                                       class="btn btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.875rem; background: var(--error);">
                                        Supprimer
                                    </a>
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