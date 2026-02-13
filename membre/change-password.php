<?php
/**
 * PAGE DE MODIFICATION DE MOT DE PASSE (UNIFIÉ & SÉCURISÉ)
 * Pour les utilisateurs connectés qui veulent changer leur mot de passe
 * 
 * @version 2.0
 * @author Entraide Plus Iroise
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth/PasswordManager.php';
require_once __DIR__ . '/../includes/auth/SessionManager.php';
require_once __DIR__ . '/../includes/auth/RateLimiter.php';

SessionManager::init();
SessionManager::requireAuth();

$user_id = SessionManager::getUserId();
$user_data = SessionManager::getUserData();

$message = '';
$message_type = '';

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_protect();
    
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $message = "Tous les champs sont obligatoires";
        $message_type = 'error';
    } elseif ($new_password !== $confirm_password) {
        $message = "Les nouveaux mots de passe ne correspondent pas";
        $message_type = 'error';
    } elseif ($current_password === $new_password) {
        $message = "Le nouveau mot de passe doit être différent de l'ancien";
        $message_type = 'error';
    } else {
        // Valider la force du nouveau mot de passe
        $validation = PasswordManager::validateStrength($new_password);
        
        if (!$validation['valid']) {
            $message = implode('<br>', $validation['errors']);
            $message_type = 'error';
        } else {
            try {
                // Récupérer le mot de passe actuel de l'utilisateur
                $stmt = $pdo->prepare("SELECT user_pass FROM EPI_user WHERE ID = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user && PasswordManager::verify($current_password, $user['user_pass'])) {
                    // Le mot de passe actuel est correct
                    
                    // Vérifier qu'il n'a pas été utilisé récemment
                    if (PasswordManager::wasUsedRecently($pdo, $user_id, $new_password)) {
                        $message = "Ce mot de passe a déjà été utilisé récemment. Veuillez en choisir un différent.";
                        $message_type = 'error';
                    } else {
                        // Hasher le nouveau mot de passe
                        $new_hashed_password = PasswordManager::hash($new_password);
                        
                        // Mettre à jour
                        $stmt = $pdo->prepare("
                            UPDATE EPI_user 
                            SET user_pass = ?,
                                password_changed_at = NOW()
                            WHERE ID = ?
                        ");
                        
                        if ($stmt->execute([$new_hashed_password, $user_id])) {
                            // Ajouter à l'historique
                            PasswordManager::addToHistory($pdo, $user_id, $new_hashed_password);
                            
                            // Envoyer un email de notification
                            $to = $user_data['email'];
                            $subject = "Votre mot de passe a été modifié - Entraide Plus Iroise";
                            
                            $message_body = "Bonjour " . $user_data['name'] . ",\n\n";
                            $message_body .= "Votre mot de passe a été modifié avec succès le " . date('d/m/Y à H:i') . ".\n\n";
                            $message_body .= "Si ce n'était pas vous, contactez-nous immédiatement à " . (defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'contact@entraide-plus-iroise.fr') . "\n\n";
                            $message_body .= "Détails de la connexion :\n";
                            $message_body .= "- Adresse IP : " . ($_SERVER['REMOTE_ADDR'] ?? 'inconnue') . "\n";
                            $message_body .= "- Date : " . date('d/m/Y à H:i') . "\n\n";
                            $message_body .= "Cordialement,\n";
                            $message_body .= "L'équipe Entraide Plus Iroise";
                            
                            $fromEmail = defined('NOREPLY_EMAIL') ? NOREPLY_EMAIL : 'noreply@entraide-plus-iroise.fr';
                            $headers = "From: " . $fromEmail . "\r\n";
                            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
                            
                            mail($to, $subject, $message_body, $headers);
                            
                            error_log("Mot de passe modifié pour user ID: $user_id");
                            
                            $message = "Votre mot de passe a été modifié avec succès !";
                            $message_type = 'success';
                            
                            // Optionnel : forcer la reconnexion pour plus de sécurité
                            // SessionManager::logout();
                            // header('Location: login.php?message=password_changed');
                            // exit;
                        } else {
                            $message = "Erreur lors de la modification du mot de passe";
                            $message_type = 'error';
                        }
                    }
                } else {
                    $message = "Le mot de passe actuel est incorrect";
                    $message_type = 'error';
                }
                
            } catch (PDOException $e) {
                error_log("Erreur change-password: " . $e->getMessage());
                $message = "Une erreur est survenue. Veuillez réessayer.";
                $message_type = 'error';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier mon mot de passe - Espace Membre</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        :root {
            --primary-color: #667eea;
            --text-primary: #2d3748;
            --text-secondary: #718096;
            --border-color: #e2e8f0;
            --error-color: #e53e3e;
            --success-color: #38a169;
            --background-light: #f7fafc;
            --radius-md: 8px;
            --radius-lg: 12px;
            --shadow-md: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        body {
            background: var(--background-light);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
        }
        
        .member-container {
            max-width: 600px;
            margin: 3rem auto;
            padding: 0 1rem;
        }
        
        .password-card {
            background: white;
            padding: 2rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
        }
        
        .password-card h1 {
            margin-top: 0;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.75rem;
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
            font-family: inherit;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .password-wrapper {
            position: relative;
        }

        .password-wrapper .form-control {
            padding-right: 3rem;
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--primary-color);
            cursor: pointer;
            font-size: 1.1rem;
            padding: 5px;
            line-height: 1;
        }

        .toggle-password:hover {
            color: var(--secondary-color);
        }

        .btn {
            padding: 0.875rem 1.5rem;
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
            background: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .btn-secondary {
            background: var(--text-secondary);
            color: white;
            margin-left: 0.5rem;
        }
        
        .alert {
            padding: 1rem;
            border-radius: var(--radius-md);
            margin-bottom: 1.5rem;
            line-height: 1.5;
        }
        
        .alert.success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid var(--success-color);
        }
        
        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid var(--error-color);
        }
        
        .password-requirements {
            background: #f0f8ff;
            padding: 1rem;
            border-radius: var(--radius-md);
            margin: 1.5rem 0;
            border-left: 4px solid var(--primary-color);
        }
        
        .password-requirements h3 {
            margin-top: 0;
            font-size: 0.95rem;
            color: var(--primary-color);
        }
        
        .password-requirements ul {
            margin: 0.5rem 0 0 1.5rem;
            padding: 0;
        }
        
        .password-requirements li {
            margin: 0.25rem 0;
            font-size: 0.9rem;
            color: var(--text-secondary);
        }
        
        .user-badge {
            background: #e7f3ff;
            padding: 1rem;
            border-radius: var(--radius-md);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-badge strong {
            color: var(--primary-color);
        }
    </style>
</head>
<body>
    <div class="member-container">
        <div class="password-card">
            <h1>
                🔐 Modifier mon mot de passe
            </h1>
            
            <div class="user-badge">
                👤 <strong><?php echo htmlspecialchars($user_data['name']); ?></strong>
                <span style="color: var(--text-secondary);">·</span>
                <span style="color: var(--text-secondary);"><?php echo htmlspecialchars($user_data['email']); ?></span>
            </div>
            
            <?php if ($message): ?>
                <div class="alert <?php echo htmlspecialchars($message_type); ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" autocomplete="off">
                <?php echo csrf_field(); ?>
                
                <div class="form-group">
                    <label class="form-label">Mot de passe actuel *</label>
                    <div class="password-wrapper">
                        <input type="password"
                               id="current_password"
                               name="current_password"
                               class="form-control"
                               required
                               autocomplete="current-password"
                               placeholder="Entrez votre mot de passe actuel">
                        <button type="button" class="toggle-password" onclick="togglePassword('current_password')" title="Afficher/Masquer">👁️</button>
                    </div>
                </div>
                
                <div class="password-requirements">
                    <h3>📋 Exigences pour le nouveau mot de passe</h3>
                    <ul>
                        <li>Minimum <?php echo PasswordManager::MIN_LENGTH; ?> caractères</li>
                        <?php if (PasswordManager::REQUIRE_UPPERCASE): ?>
                        <li>Au moins une majuscule</li>
                        <?php endif; ?>
                        <?php if (PasswordManager::REQUIRE_LOWERCASE): ?>
                        <li>Au moins une minuscule</li>
                        <?php endif; ?>
                        <?php if (PasswordManager::REQUIRE_DIGIT): ?>
                        <li>Au moins un chiffre</li>
                        <?php endif; ?>
                        <?php if (PasswordManager::REQUIRE_SPECIAL): ?>
                        <li>Au moins un caractère spécial (!@#$%&*...)</li>
                        <?php endif; ?>
                        <li>Différent de votre mot de passe actuel</li>
                        <li>Non utilisé récemment</li>
                    </ul>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Nouveau mot de passe *</label>
                    <div class="password-wrapper">
                        <input type="password"
                               id="new_password"
                               name="new_password"
                               class="form-control"
                               required
                               minlength="<?php echo PasswordManager::MIN_LENGTH; ?>"
                               autocomplete="new-password"
                               placeholder="Minimum <?php echo PasswordManager::MIN_LENGTH; ?> caractères">
                        <button type="button" class="toggle-password" onclick="togglePassword('new_password')" title="Afficher/Masquer">👁️</button>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Confirmer le nouveau mot de passe *</label>
                    <div class="password-wrapper">
                        <input type="password"
                               id="confirm_password"
                               name="confirm_password"
                               class="form-control"
                               required
                               minlength="<?php echo PasswordManager::MIN_LENGTH; ?>"
                               autocomplete="new-password"
                               placeholder="Retapez le nouveau mot de passe">
                        <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')" title="Afficher/Masquer">👁️</button>
                    </div>
                </div>
                
                <div style="margin-top: 2rem;">
                    <button type="submit" class="btn btn-primary">
                        ✓ Modifier mon mot de passe
                    </button>
                    <a href="../EPI/dashboard.php" class="btn btn-secondary">
                        ← Retour au dashboard
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const button = field.nextElementSibling;
            if (field.type === 'password') {
                field.type = 'text';
                button.textContent = '🙈';
            } else {
                field.type = 'password';
                button.textContent = '👁️';
            }
        }

        // Validation côté client
        document.querySelector('form').addEventListener('submit', function(e) {
            const newPassword = document.querySelector('input[name="new_password"]').value;
            const confirmPassword = document.querySelector('input[name="confirm_password"]').value;
            const currentPassword = document.querySelector('input[name="current_password"]').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Les nouveaux mots de passe ne correspondent pas');
                return;
            }
            
            if (newPassword === currentPassword) {
                e.preventDefault();
                alert('Le nouveau mot de passe doit être différent de l\'ancien');
                return;
            }
        });
    </script>
</body>
</html>