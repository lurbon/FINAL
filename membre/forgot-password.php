<?php
/**
 * PAGE "MOT DE PASSE OUBLIÉ" (UNIFIÉ & SÉCURISÉ)
 * 
 * Génère un token de réinitialisation sécurisé envoyé par email
 * 
 * @version 2.0
 * @author Entraide Plus Iroise
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/sanitize.php';
require_once __DIR__ . '/../includes/auth/PasswordManager.php';
require_once __DIR__ . '/../includes/auth/SessionManager.php';
require_once __DIR__ . '/../includes/auth/RateLimiter.php';

SessionManager::init();

$message = '';
$message_type = '';
$email_sent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_protect();
    
    // Vérifier le rate limiting
    if (RateLimiter::isLocked('forgot_password')) {
        $message = RateLimiter::getErrorMessage('forgot_password');
        $message_type = 'error';
    } else {
        RateLimiter::record('forgot_password', false);
        
        $email = sanitize_email($_POST['email'] ?? '');
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "Veuillez entrer une adresse email valide";
            $message_type = 'error';
        } else {
            try {
                // Vérifier si l'email existe
                $stmt = $pdo->prepare("
                    SELECT ID, user_nicename, user_email 
                    FROM EPI_user 
                    WHERE user_email = ?
                ");
                $stmt->execute([$email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    // Générer le token sécurisé
                    $token_data = PasswordManager::generateResetToken();
                    
                    // Stocker le token hashé dans la base
                    $stmt = $pdo->prepare("
                        UPDATE EPI_user 
                        SET reset_token = ?, reset_expiry = ? 
                        WHERE ID = ?
                    ");
                    $stmt->execute([
                        $token_data['token_hash'],
                        $token_data['expiry'],
                        $user['ID']
                    ]);
                    
                    // Créer le lien de réinitialisation
                    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                    $reset_link = $protocol . "://" . $_SERVER['HTTP_HOST'] 
                                . dirname($_SERVER['PHP_SELF']) 
                                . "/reset-password.php?token=" . $token_data['token'];
                    
                    // Préparer l'email
                    $to = $user['user_email'];
                    $subject = "Réinitialisation de votre mot de passe - Entraide Plus Iroise";
                    
                    $message_body = "Bonjour " . $user['user_nicename'] . ",\n\n";
                    $message_body .= "Vous avez demandé à réinitialiser votre mot de passe.\n\n";
                    $message_body .= "Cliquez sur ce lien pour créer un nouveau mot de passe :\n";
                    $message_body .= $reset_link . "\n\n";
                    $message_body .= "Ce lien est valable pendant 1 heure.\n\n";
                    $message_body .= "Si vous n'avez pas demandé cette réinitialisation, ignorez cet email.\n";
                    $message_body .= "Votre mot de passe actuel reste inchangé.\n\n";
                    $message_body .= "Pour votre sécurité :\n";
                    $message_body .= "- Ne partagez jamais ce lien\n";
                    $message_body .= "- Changez votre mot de passe régulièrement\n";
                    $message_body .= "- Utilisez un mot de passe fort et unique\n\n";
                    $message_body .= "Cordialement,\n";
                    $message_body .= "L'équipe Entraide Plus Iroise";
                    
                    $fromEmail = defined('NOREPLY_EMAIL') ? NOREPLY_EMAIL : 'noreply@entraide-plus-iroise.fr';
                    $headers = "From: " . $fromEmail . "\r\n";
                    $headers .= "Reply-To: " . (defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'contact@entraide-plus-iroise.fr') . "\r\n";
                    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
                    
                    // Envoyer l'email
                    mail($to, $subject, $message_body, $headers);
                    
                    error_log("Reset password token généré pour user ID: " . $user['ID']);
                }
                
                // Message identique que l'email existe ou non (anti-énumération)
                $email_sent = true;
                $message = "Si cette adresse email existe dans notre système, un lien de réinitialisation a été envoyé. Vérifiez votre boîte de réception et vos spams.";
                $message_type = 'success';
                
            } catch (PDOException $e) {
                error_log("Erreur forgot-password: " . $e->getMessage());
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
    <title>Mot de passe oublié - Entraide Plus Iroise</title>
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
        
        .forgot-container {
            max-width: 500px;
            width: 100%;
        }
        
        .forgot-card {
            background: white;
            padding: 2.5rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
        }
        
        .forgot-card h1 {
            margin-top: 0;
            color: var(--text-primary);
            text-align: center;
            margin-bottom: 0.5rem;
            font-size: 1.875rem;
        }
        
        .forgot-card p {
            text-align: center;
            color: var(--text-secondary);
            margin-bottom: 2rem;
            font-size: 0.95rem;
            line-height: 1.5;
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
        
        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
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
        
        .info-box {
            background: #e6f2ff;
            padding: 1rem;
            border-radius: var(--radius-md);
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--info-color);
            font-size: 0.9rem;
            color: #1a365d;
        }
        
        .info-box strong {
            display: block;
            margin-bottom: 0.5rem;
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
            .forgot-card {
                padding: 2rem 1.5rem;
            }
            
            .forgot-card h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="forgot-container">
        <div class="forgot-card">
            <h1>🔑 Mot de passe oublié</h1>
            <p>Entrez votre adresse email pour recevoir un lien de réinitialisation</p>

            <?php if ($message): ?>
                <div class="alert <?php echo htmlspecialchars($message_type); ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if (!$email_sent): ?>
                <div class="info-box">
                    <strong>📧 Comment ça marche ?</strong>
                    Nous vous enverrons un email contenant un lien sécurisé pour créer un nouveau mot de passe. Le lien est valable 1 heure.
                </div>
                
                <form method="POST">
                    <?php echo csrf_field(); ?>
                    <div class="form-group">
                        <label class="form-label">Adresse email</label>
                        <input type="email"
                               name="email"
                               class="form-control"
                               placeholder="votre@email.fr"
                               required
                               autocomplete="email"
                               <?php echo RateLimiter::isLocked('forgot_password') ? 'disabled' : ''; ?>>
                    </div>

                    <button type="submit" 
                            class="btn btn-primary"
                            <?php echo RateLimiter::isLocked('forgot_password') ? 'disabled' : ''; ?>>
                        <?php if (RateLimiter::isLocked('forgot_password')): ?>
                            ⏳ Veuillez patienter...
                        <?php else: ?>
                            📧 Envoyer le lien de réinitialisation
                        <?php endif; ?>
                    </button>
                </form>
            <?php else: ?>
                <div class="info-box">
                    <strong>✉️ Email envoyé !</strong>
                    Si vous ne recevez pas l'email dans quelques minutes :
                    <ul style="margin: 0.5rem 0 0 1.5rem; padding: 0;">
                        <li>Vérifiez votre dossier spam/indésirables</li>
                        <li>Vérifiez que l'adresse email est correcte</li>
                        <li>Contactez-nous si le problème persiste</li>
                    </ul>
                </div>
            <?php endif; ?>

            <a href="login.php" class="back-link">← Retour à la connexion</a>
        </div>
    </div>
</body>
</html>
