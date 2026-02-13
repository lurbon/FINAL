<?php
/**
 * GESTION DES MEMBRES - VERSION FUSIONNÉE
 * 
 * Combine :
 * - Gestion des membres de l'équipe (photo, bio, affichage public)
 * - Gestion des comptes utilisateurs (mots de passe sécurisés, rôles)
 * 
 * @version 2.0
 * @author Entraide Plus Iroise
 */

require_once '../includes/config.php';
require_once 'check_auth.php';
require_once '../includes/csrf.php';
require_once '../includes/sanitize.php';
require_once '../includes/auth/PasswordManager.php';

// Pas besoin de vérifier l'authentification, check_auth.php le fait déjà

$message = '';
$message_type = '';

// ============================================
// GÉNÉRATION DE MOT DE PASSE TEMPORAIRE
// ============================================
if (isset($_POST['generate_temp_password'])) {
    csrf_protect();
    
    $user_id = (int)$_POST['user_id'];
    
    try {
        $stmt = $pdo->prepare("SELECT user_nicename, user_email FROM EPI_user WHERE ID = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $temp_password = PasswordManager::generateTempPassword();
            $hash = PasswordManager::hash($temp_password);
            
            $stmt = $pdo->prepare("
                UPDATE EPI_user 
                SET user_pass = ?, 
                    password_changed_at = NOW() 
                WHERE ID = ?
            ");
            $stmt->execute([$hash, $user_id]);
            
            PasswordManager::addToHistory($pdo, $user_id, $hash);
            
            // Logging
            try {
                $stmt_log = $pdo->prepare("
                    INSERT INTO EPI_auth_logs 
                    (user_id, event_type, success, ip_address, user_agent, created_at) 
                    VALUES (?, 'password_reset', 1, ?, ?, NOW())
                ");
                $stmt_log->execute([
                    $user_id,
                    $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 500)
                ]);
            } catch (PDOException $e) {
                error_log("Erreur logging reset password: " . $e->getMessage());
            }
            
            $message = "Mot de passe temporaire généré pour <strong>" . htmlspecialchars($user['user_nicename']) . "</strong> :<br><br>";
            $message .= "<div style='background: #f0f8ff; padding: 1rem; border-radius: 5px; font-family: monospace; font-size: 1.2rem; text-align: center; margin: 1rem 0;'>";
            $message .= "<strong>" . htmlspecialchars($temp_password) . "</strong>";
            $message .= "</div>";
            $message .= "<small>⚠️ Communiquez ce mot de passe de manière sécurisée.</small>";
            $message_type = 'success';
        }
    } catch (PDOException $e) {
        error_log("Erreur génération mot de passe: " . $e->getMessage());
        $message = "Erreur lors de la génération du mot de passe.";
        $message_type = 'error';
    }
}

// ============================================
// CHANGEMENT DE MOT DE PASSE MANUEL
// ============================================
if (isset($_POST['change_password'])) {
    csrf_protect();
    
    $user_id = (int)$_POST['user_id'];
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($new_password)) {
        $message = "Le mot de passe ne peut pas être vide.";
        $message_type = 'error';
    } elseif ($new_password !== $confirm_password) {
        $message = "Les mots de passe ne correspondent pas.";
        $message_type = 'error';
    } else {
        $validation = PasswordManager::validateStrength($new_password);
        
        if (!$validation['valid']) {
            $message = "Le mot de passe ne respecte pas les critères de sécurité :<br>";
            $message .= implode('<br>', $validation['errors']);
            $message_type = 'error';
        } else {
            try {
                if (PasswordManager::wasUsedRecently($pdo, $user_id, $new_password)) {
                    $message = "Ce mot de passe a déjà été utilisé récemment.";
                    $message_type = 'error';
                } else {
                    $stmt = $pdo->prepare("SELECT user_nicename FROM EPI_user WHERE ID = ?");
                    $stmt->execute([$user_id]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $hash = PasswordManager::hash($new_password);
                    
                    $stmt = $pdo->prepare("
                        UPDATE EPI_user 
                        SET user_pass = ?, 
                            password_changed_at = NOW() 
                        WHERE ID = ?
                    ");
                    $stmt->execute([$hash, $user_id]);
                    
                    PasswordManager::addToHistory($pdo, $user_id, $hash);
                    
                    // Logging
                    try {
                        $stmt_log = $pdo->prepare("
                            INSERT INTO EPI_auth_logs 
                            (user_id, event_type, success, ip_address, user_agent, created_at) 
                            VALUES (?, 'password_change', 1, ?, ?, NOW())
                        ");
                        $stmt_log->execute([
                            $user_id,
                            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                            substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 500)
                        ]);
                    } catch (PDOException $e) {
                        error_log("Erreur logging change password: " . $e->getMessage());
                    }
                    
                    $message = "Mot de passe modifié pour <strong>" . htmlspecialchars($user['user_nicename']) . "</strong>.";
                    $message_type = 'success';
                }
            } catch (PDOException $e) {
                error_log("Erreur changement mot de passe: " . $e->getMessage());
                $message = "Erreur lors du changement de mot de passe.";
                $message_type = 'error';
            }
        }
    }
}

// ============================================
// SUPPRIMER UN MEMBRE
// ============================================
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

// ============================================
// AJOUTER OU MODIFIER UN MEMBRE
// ============================================
if (isset($_POST['save_member'])) {
    csrf_protect();
    
    $ID = $_POST['ID'] ?? null;
    $name = sanitize_text($_POST['user_nicename'] ?? '');
    $role = sanitize_text($_POST['user_role'] ?? '');
    $fonction = sanitize_text($_POST['user_fonction'] ?? '');
    $bio = sanitize_text($_POST['user_bio'] ?? '');
    $email = sanitize_email($_POST['user_email'] ?? '');
    $phone = sanitize_text($_POST['user_phone'] ?? '');
    $display_order = (int)($_POST['user_rang'] ?? 0);
    $current_photo = $_POST['current_photo'] ?? '';
    $password = $_POST['user_password'] ?? '';
    $password_confirm = $_POST['user_password_confirm'] ?? '';
    
    $password_error = false;
    $photo = $current_photo;
    
    // Upload de la photo
    if (isset($_FILES['user_photo']) && $_FILES['user_photo']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = $_FILES['user_photo']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $new_filename = uniqid() . '.' . $ext;
            
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
    
    // Validation du mot de passe si fourni
    if (!empty($password)) {
        if ($password !== $password_confirm) {
            $message = "Les mots de passe ne correspondent pas";
            $message_type = 'error';
            $password_error = true;
        } else {
            $validation = PasswordManager::validateStrength($password);
            if (!$validation['valid']) {
                $message = "Le mot de passe ne respecte pas les critères :<br>" . implode('<br>', $validation['errors']);
                $message_type = 'error';
                $password_error = true;
            }
        }
    }
    
    try {
        if (!$password_error) {
            if ($ID) {
                // MISE À JOUR
                if (!empty($password)) {
                    // Avec changement de mot de passe
                    $hash = PasswordManager::hash($password);
                    $stmt = $pdo->prepare("
                        UPDATE EPI_user 
                        SET user_nicename = ?, user_role = ?, user_fonction = ?, user_photo = ?, 
                            user_bio = ?, user_email = ?, user_phone = ?, 
                            user_rang = ?, user_pass = ?, password_changed_at = NOW() 
                        WHERE ID = ?
                    ");
                    $stmt->execute([$name, $role, $fonction, $photo, $bio, $email, $phone, $display_order, $hash, $ID]);
                    
                    PasswordManager::addToHistory($pdo, $ID, $hash);
                } else {
                    // Sans changement de mot de passe
                    $stmt = $pdo->prepare("
                        UPDATE EPI_user 
                        SET user_nicename = ?, user_role = ?, user_fonction = ?, user_photo = ?, 
                            user_bio = ?, user_email = ?, user_phone = ?, 
                            user_rang = ? 
                        WHERE ID = ?
                    ");
                    $stmt->execute([$name, $role, $fonction, $photo, $bio, $email, $phone, $display_order, $ID]);
                }
                $message = "Membre modifié avec succès";
                
            } else {
                // INSERTION - mot de passe obligatoire
                if (empty($password)) {
                    $message = "Le mot de passe est obligatoire pour un nouveau membre";
                    $message_type = 'error';
                } else {
                    $hash = PasswordManager::hash($password);
                    $stmt = $pdo->prepare("
                        INSERT INTO EPI_user 
                        (user_nicename, user_role, user_fonction, user_photo, user_bio, user_email, 
                         user_phone, user_rang, user_pass, password_changed_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([$name, $role, $fonction, $photo, $bio, $email, $phone, $display_order, $hash]);
                    
                    $new_id = $pdo->lastInsertId();
                    PasswordManager::addToHistory($pdo, $new_id, $hash);
                    
                    $message = "Membre ajouté avec succès";
                }
            }
            
            if (!isset($message_type) || $message_type !== 'error') {
                $message_type = 'success';
            }
        }
    } catch (PDOException $e) {
        error_log("Erreur members.php: " . $e->getMessage());
        $message = "Erreur lors de l'enregistrement.";
        $message_type = 'error';
    }
}

// Récupérer le membre à éditer
$edit_member = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM EPI_user WHERE ID = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_member = $stmt->fetch();
}

// Récupérer tous les membres avec statistiques
$members = $pdo->query("
    SELECT 
        u.*,
        (SELECT COUNT(*) FROM EPI_auth_logs WHERE user_id = u.ID AND event_type = 'login' AND success = 1) as login_count,
        (SELECT MAX(created_at) FROM EPI_auth_logs WHERE user_id = u.ID AND event_type = 'login' AND success = 1) as last_login
    FROM EPI_user u
    ORDER BY u.user_rang ASC, u.ID ASC
")->fetchAll();

// Récupérer l'admin connecté
$admin_name = $_SESSION['admin_name'] ?? 'Administrateur';
$admin_email = $_SESSION['admin_email'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des membres - Administration</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        :root {
            --primary-color: #667eea;
            --primary-dark: #5568d3;
            --secondary-color: #764ba2;
            --text-primary: #2d3748;
            --text-secondary: #718096;
            --border-color: #e2e8f0;
            --error: #e53e3e;
            --success-color: #38a169;
            --radius-md: 8px;
            --radius-lg: 12px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
        }
        
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
        }
        
        h1 {
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }
        
        .breadcrumb {
            color: var(--text-secondary);
            margin-bottom: 2rem;
            font-size: 0.875rem;
        }
        
        .breadcrumb a {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .alert {
            padding: 1rem;
            border-radius: var(--radius-md);
            margin-bottom: 1.5rem;
        }
        
        .alert.success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid var(--success-color);
        }
        
        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid var(--error);
        }
        
        .card {
            background: white;
            border-radius: var(--radius-lg);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .card h2 {
            margin-bottom: 1.5rem;
            color: var(--text-primary);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-primary);
            font-size: 0.95rem;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }
        
        .form-image-preview {
            max-width: 200px;
            margin-top: 0.5rem;
            border-radius: var(--radius-md);
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--radius-md);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: var(--text-secondary);
            color: white;
        }
        
        .btn-success {
            background: var(--success-color);
            color: white;
        }
        
        .btn-danger {
            background: var(--error);
            color: white;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        td img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 50%;
        }
        
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .badge-admin {
            background: #e74c3c;
            color: white;
        }
        
        .badge-member {
            background: #3498db;
            color: white;
        }
        
        .badge-benevole {
            background: #27ae60;
            color: white;
        }
        
        .badge-chauffeur {
            background: #f39c12;
            color: white;
        }
        
        .password-section {
            background: #f0f8ff;
            padding: 1.5rem;
            border-radius: var(--radius-md);
            margin: 1.5rem 0;
            border-left: 4px solid var(--primary-color);
        }
        
        .password-section h3 {
            margin-top: 0;
            color: var(--primary-color);
            font-size: 1.1rem;
            margin-bottom: 1rem;
        }
        
        .password-requirements {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }
        
        .password-requirements ul {
            margin: 0.5rem 0 0 1.5rem;
        }
        
        .password-requirements li {
            margin: 0.25rem 0;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        
        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: white;
            border-radius: var(--radius-lg);
            padding: 2rem;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            margin-bottom: 1.5rem;
        }
        
        .modal-header h3 {
            color: var(--text-primary);
        }
        
        .modal-footer {
            margin-top: 1.5rem;
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-sidebar">
            <h2>📊 Administration</h2>
            <nav class="admin-menu">
                <a href="members.php" class="active">👥 Membres</a>
                <a href="auth-logs.php">📊 Logs d'authentification</a>
                <a href="../EPI/dashboard.php">← Retour au site</a>
            </nav>
        </div>
        
        <div class="admin-content">
            <div class="breadcrumb">
                <a href="../EPI/dashboard.php">Tableau de bord</a> / Gestion des membres
            </div>
            
            <h1>👥 Gestion des membres</h1>
            <p style="color: var(--text-secondary); margin-bottom: 2rem;">
                Connecté en tant que : <strong><?= htmlspecialchars($admin_name) ?></strong>
            </p>
            
            <?php if ($message): ?>
            <div class="alert <?= $message_type ?>">
                <?= $message ?>
            </div>
            <?php endif; ?>
            
            <!-- Formulaire ajout/modification -->
            <div class="card">
                <h2><?= $edit_member ? '✏️ Modifier un membre' : '➕ Ajouter un membre' ?></h2>
                
                <form method="POST" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    
                    <?php if ($edit_member): ?>
                        <input type="hidden" name="ID" value="<?= $edit_member['ID'] ?>">
                        <input type="hidden" name="current_photo" value="<?= htmlspecialchars($edit_member['user_photo']) ?>">
                    <?php endif; ?>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label class="form-label">Nom complet *</label>
                            <input type="text" name="user_nicename" class="form-control" 
                                   value="<?= htmlspecialchars($edit_member['user_nicename'] ?? '') ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Rôle *</label>
                            <input type="text" name="user_role" class="form-control" 
                                   value="<?= htmlspecialchars($edit_member['user_role'] ?? '') ?>" 
                                   placeholder="Ex: Président(e), Bénévole, Admin" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Fonction (description du poste)</label>
                        <input type="text" name="user_fonction" class="form-control" 
                               value="<?= htmlspecialchars($edit_member['user_fonction'] ?? '') ?>" 
                               placeholder="Ex: Responsable du transport, Coordinateur bénévoles">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Biographie</label>
                        <textarea name="user_bio" class="form-control" rows="4"><?= htmlspecialchars($edit_member['user_bio'] ?? '') ?></textarea>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" name="user_email" class="form-control" 
                                   value="<?= htmlspecialchars($edit_member['user_email'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Téléphone</label>
                            <input type="text" name="user_phone" class="form-control" 
                                   value="<?= htmlspecialchars($edit_member['user_phone'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Ordre d'affichage</label>
                            <input type="number" name="user_rang" class="form-control" 
                                   value="<?= htmlspecialchars($edit_member['user_rang'] ?? 0) ?>">
                        </div>
                    </div>
                    
                    <div class="password-section">
                        <h3>🔐 Mot de passe pour l'espace membre</h3>
                        
                        <div class="password-requirements">
                            <strong>Exigences de sécurité :</strong>
                            <ul>
                                <li>Minimum 8 caractères</li>
                                <li>Au moins une majuscule et une minuscule</li>
                                <li>Au moins un chiffre</li>
                                <li>Au moins un caractère spécial (!@#$%&*...)</li>
                            </ul>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1rem;">
                            <div class="form-group">
                                <label class="form-label">
                                    Mot de passe <?= !$edit_member ? '*' : '(laisser vide pour ne pas changer)' ?>
                                </label>
                                <input type="password" name="user_password" class="form-control" 
                                       placeholder="Minimum 8 caractères" 
                                       autocomplete="new-password"
                                       <?= !$edit_member ? 'required' : '' ?>>
                                <?php if ($edit_member): ?>
                                <small style="color: var(--text-secondary); font-size: 0.875rem;">
                                    Laissez vide pour conserver le mot de passe actuel
                                </small>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    Confirmer <?= !$edit_member ? '*' : '' ?>
                                </label>
                                <input type="password" name="user_password_confirm" class="form-control" 
                                       placeholder="Retapez le mot de passe"
                                       autocomplete="new-password"
                                       <?= !$edit_member ? 'required' : '' ?>>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Photo</label>
                        <input type="file" name="user_photo" class="form-control" accept="image/*">
                        <?php if ($edit_member && $edit_member['user_photo']): ?>
                            <img src="../uploads/members/<?= htmlspecialchars($edit_member['user_photo']) ?>" 
                                 class="form-image-preview">
                        <?php endif; ?>
                    </div>
                    
                    <div style="display: flex; gap: 1rem;">
                        <button type="submit" name="save_member" class="btn btn-primary">
                            <?= $edit_member ? '✓ Modifier' : '✓ Ajouter' ?>
                        </button>
                        <?php if ($edit_member): ?>
                            <a href="members.php" class="btn btn-secondary">Annuler</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <!-- Liste des membres -->
            <div class="card">
                <h2>📋 Liste des membres (<?= count($members) ?>)</h2>
                
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
                                <th>Rôle</th>
                                <th>Fonction</th>
                                <th>Contact</th>
                                <th>Ordre</th>
                                <th>Connexions</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($members as $member): ?>
                                <tr>
                                    <td>
                                        <?php if ($member['user_photo']): ?>
                                            <img src="../uploads/members/<?= htmlspecialchars($member['user_photo']) ?>" style="width: 50px; height: 50px;">
                                        <?php else: ?>
                                            <div style="width: 50px; height: 50px; background: var(--primary-color); 
                                                        border-radius: 50%; display: flex; align-items: center; 
                                                        justify-content: center; color: white; font-size: 1.2rem;">👤</div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-size: 0.875rem;"><strong><?= htmlspecialchars($member['user_nicename']) ?></strong></td>
                                    <td style="font-size: 0.8rem;">
                                        <?php
                                        $badge_class = 'badge-member';
                                        $role_lower = strtolower($member['user_role']);
                                        if (strpos($role_lower, 'admin') !== false) $badge_class = 'badge-admin';
                                        elseif (strpos($role_lower, 'benevole') !== false || strpos($role_lower, 'bénévole') !== false) $badge_class = 'badge-benevole';
                                        elseif (strpos($role_lower, 'chauffeur') !== false) $badge_class = 'badge-chauffeur';
                                        ?>
                                        <span class="badge <?= $badge_class ?>" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">
                                            <?= htmlspecialchars($member['user_role']) ?>
                                        </span>
                                    </td>
                                    <td style="font-size: 0.8rem; color: var(--text-secondary);">
                                        <?= htmlspecialchars($member['user_fonction'] ?? '-') ?>
                                    </td>
                                    <td style="font-size: 0.8rem;">
                                        <?php if ($member['user_email']): ?>
                                            <div><?= htmlspecialchars($member['user_email']) ?></div>
                                        <?php endif; ?>
                                        <?php if ($member['user_phone']): ?>
                                            <div><?= htmlspecialchars($member['user_phone']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-size: 0.875rem;"><?= $member['user_rang'] ?></td>
                                    <td>
                                        <div style="font-size: 0.75rem;">
                                            <strong><?= $member['login_count'] ?></strong> connexion(s)
                                            <?php if ($member['last_login']): ?>
                                                <br><small style="color: var(--text-secondary); font-size: 0.7rem;">
                                                    <?= date('d/m/Y H:i', strtotime($member['last_login'])) ?>
                                                </small>
                                            <?php else: ?>
                                                <br><small style="color: #95a5a6; font-size: 0.7rem;">Jamais connecté</small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                            <a href="?edit=<?= $member['ID'] ?>" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.875rem; text-align: center;">
                                                ✏️ Modifier
                                            </a>
                                            
                                            <button onclick="openChangePasswordModal(<?= $member['ID'] ?>, '<?= htmlspecialchars($member['user_nicename']) ?>')" 
                                                    class="btn btn-success" style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                                🔐 Changer MDP
                                            </button>
                                            
                                            <form method="POST" style="margin: 0;">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="user_id" value="<?= $member['ID'] ?>">
                                                <button type="submit" name="generate_temp_password" 
                                                        onclick="return confirm('Générer un mot de passe temporaire pour <?= htmlspecialchars($member['user_nicename']) ?> ?')"
                                                        class="btn btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.875rem; width: 100%;">
                                                    🔑 MDP temporaire
                                                </button>
                                            </form>
                                            
                                            <a href="?delete=<?= $member['ID'] ?>" 
                                               onclick="return confirm('Supprimer ce membre ?')"
                                               class="btn btn-danger" style="padding: 0.5rem 1rem; font-size: 0.875rem; text-align: center;">
                                                🗑️ Supprimer
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Modal changement de mot de passe -->
    <div id="changePasswordModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>🔐 Changer le mot de passe</h3>
                <p id="modalUserName" style="color: var(--text-secondary);"></p>
            </div>
            
            <form method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="user_id" id="modalUserId">
                
                <div class="password-requirements">
                    <strong>Exigences :</strong>
                    <ul>
                        <li>Minimum 8 caractères</li>
                        <li>Majuscule + minuscule + chiffre + spécial</li>
                        <li>Ne doit pas avoir été utilisé récemment</li>
                    </ul>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Nouveau mot de passe</label>
                    <input type="password" name="new_password" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Confirmer</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                
                <div class="modal-footer">
                    <button type="button" onclick="closeModal()" class="btn btn-secondary">
                        Annuler
                    </button>
                    <button type="submit" name="change_password" class="btn btn-primary">
                        ✓ Changer
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openChangePasswordModal(userId, userName) {
            document.getElementById('modalUserId').value = userId;
            document.getElementById('modalUserName').textContent = 'Utilisateur : ' + userName;
            document.getElementById('changePasswordModal').classList.add('active');
        }
        
        function closeModal() {
            document.getElementById('changePasswordModal').classList.remove('active');
        }
        
        document.getElementById('changePasswordModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
    </script>
</body>
</html>
