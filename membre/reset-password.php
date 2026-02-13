<?php
/**
 * PAGE DE RÉINITIALISATION DE MOT DE PASSE (UNIFIÉ & SÉCURISÉ)
 * 
 * Utilise le token généré par forgot-password.php
 * 
 * @version 2.0
 * @author Entraide Plus Iroise
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth/PasswordManager.php';
require_once __DIR__ . '/../includes/auth/SessionManager.php';

SessionManager::init();

$message = '';
$message_type = '';
$token_valid = false;
$user = null;

// Vérifier le token
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // Vérifier le token avec PasswordManager
    $user = PasswordManager::verifyResetToken($pdo, $token);
    
    if ($user) {
        $token_valid = true;
    } else {
        $message = "Ce lien de réinitialisation n'est pas valide ou a expiré. Veuillez en demander un nouveau.";
        $message_type = 'error';
    }
} else {
    $message = "Aucun token de réinitialisation fourni.";
    $message_type = 'error';
}

// Traitement du formulaire de réinitialisation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token_valid) {
    csrf_protect();
    
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($new_password) || empty($confirm_password)) {
        $message = "Tous les champs sont obligatoires";
        $message_type = 'error';
    } elseif ($new_password !== $confirm_password) {
        $message = "Les mots de passe ne correspondent pas";
        $message_type = 'error';
    } else {
        // Valider la force du mot de passe
        $validation = PasswordManager::validateStrength($new_password);
        
        if (!$validation['valid']) {
            $message = implode('<br>', $validation['errors']);
            $message_type = 'error';
        } else {
            try {
                // Vérifier qu'il n'a pas été utilisé récemment
                if (PasswordManager::wasUsedRecently($pdo, $user['ID'], $new_password)) {
                    $message = "Ce mot de passe a déjà été utilisé récemment. Veuillez en choisir un différent.";
                    $message_type = 'error';
                } else {
                    // Hasher le nouveau mot de passe
                    $hashed_password = PasswordManager::hash($new_password);
                    
                    // Mettre à jour le mot de passe
                    $stmt = $pdo->prepare("
                        UPDATE EPI_user 
                        SET user_pass = ?, 
                            reset_token = NULL, 
                            reset_expiry = NULL,
                            password_changed_at = NOW()
                        WHERE ID = ?
                    ");
                    
                    if ($stmt->execute([$hashed_password, $user['ID']])) {
                        // Ajouter à l'historique
                        PasswordManager::addToHistory($pdo, $user['ID'], $hashed_password);
                        
                        // Envoyer un email de notification
                        $to = $user['user_email'];
                        $subject = "Votre mot de passe a été modifié - Entraide Plus Iroise";
                        
                        $message_body = "Bonjour " . $user['user_nicename'] . ",\n\n";
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
                        
                        error_log("Mot de passe réinitialisé pour user ID: " . $user['ID']);
                        
                        // Rediriger vers login avec message de succès
                        $_SESSION['success_message'] = "Votre mot de passe a été réinitialisé avec succès ! Vous pouvez maintenant vous connecter.";
                        header('Location: login.php');
                        exit;
                        
                    } else {
                        $message = "Erreur lors de la réinitialisation du mot de passe";
                        $message_type = 'error';
                    }
                }
                
            } catch (PDOException $e) {
                error_log("Erreur reset-password: " . $e->getMessage());
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
    <title>Réinitialiser mon mot de passe - Entraide Plus Iroise</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        :root {
            --primary-color: #667eea;
            --primary-dark: #5568d3;
            --secondary-color: #764ba2;
            --text-primary: #2d3748;
            --text-secondary: #718096;
            --border-color: #e2e8f0;
            --error-color: #e53e3e;
            --success-color: #38a169;
            --info-color: #3182ce;
            --radius-md: 8px;
            --radius-lg: 12px;
            --shadow-md: 0 4px 6px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        
        .reset-container {
            max-width: 500px;
            width: 100%;
        }
        
        .reset-card {
            background: white;
            padding: 2.5rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
        }
        
        .reset-card h1 {
            margin-top: 0;
            color: var(--text-primary);
            text-align: center;
            margin-bottom: 0.5rem;
            font-size: 1.875rem;
        }
        
        .user-info {
            background: #e7f3ff;
            padding: 1rem;
            border-radius: var(--radius-md);
            margin-bottom: 1.5rem;
            text-align: center;
            border-left: 4px solid var(--primary-color);
        }
        
        .password-requirements {
            background: #f0f8ff;
            padding: 1rem;
            border-radius: var(--radius-md);
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--primary-color);
            font-size: 0.9rem;
        }
        
        .password-requirements h3 {
            margin: 0 0 0.5rem 0;
            font-size: 0.95rem;
            color: var(--primary-color);
        }
        
        .password-requirements ul {
            margin: 0.5rem 0 0 1.5rem;
            padding: 0;
        }
        
        .password-requirements li {
            margin: 0.25rem 0;
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
            padding: 0.875rem;
            border: 2px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: 1rem;
            transition: all 0.3s ease;
            font-family: inherit;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
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
            width: 100%;
            padding: 1rem;
            border: none;
            border-radius: var(--radius-md);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        
        .alert {
            padding: 1rem;
            border-radius: var(--radius-md);
            margin-bottom: 1.5rem;
            text-align: center;
            font-size: 0.95rem;
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
        
        .back-link {
            display: block;
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-color);
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.95rem;
            transition: color 0.3s ease;
        }
        
        .back-link:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }
        
        @media (max-width: 480px) {
            .reset-card {
                padding: 2rem 1.5rem;
            }
            
            .reset-card h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-card">
            <h1>🔐 Nouveau mot de passe</h1>
            
            <?php if ($message): ?>
                <div class="alert <?php echo htmlspecialchars($message_type); ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($token_valid): ?>
                <div class="user-info">
                    👤 <strong><?php echo htmlspecialchars($user['user_nicename']); ?></strong><br>
                    <small>Définissez votre nouveau mot de passe</small>
                </div>
                
                <div class="password-requirements">
                    <h3>📋 Exigences</h3>
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
                        <li>Ne doit pas être un mot de passe courant</li>
                        <li>Ne doit pas avoir été utilisé récemment</li>
                    </ul>
                </div>
                
                <form method="POST">
                    <?php echo csrf_field(); ?>
                    
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
                        <label class="form-label">Confirmer le mot de passe *</label>
                        <div class="password-wrapper">
                            <input type="password"
                                   id="confirm_password"
                                   name="confirm_password"
                                   class="form-control"
                                   required
                                   minlength="<?php echo PasswordManager::MIN_LENGTH; ?>"
                                   autocomplete="new-password"
                                   placeholder="Retapez le mot de passe">
                            <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')" title="Afficher/Masquer">👁️</button>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        ✓ Réinitialiser mon mot de passe
                    </button>
                </form>
            <?php else: ?>
                <p style="text-align: center; color: var(--text-secondary); margin: 2rem 0;">
                    <a href="forgot-password.php" class="btn btn-primary">
                        Demander un nouveau lien
                    </a>
                </p>
            <?php endif; ?>
            
            <a href="login.php" class="back-link">← Retour à la connexion</a>
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
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                const newPassword = document.querySelector('input[name="new_password"]').value;
                const confirmPassword = document.querySelector('input[name="confirm_password"]').value;
                
                if (newPassword !== confirmPassword) {
                    e.preventDefault();
                    alert('Les mots de passe ne correspondent pas');
                }
            });
        }
    </script>
</body>
</html>
