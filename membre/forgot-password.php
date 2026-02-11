<?php
/**
 * PAGE "MOT DE PASSE OUBLIÉ"
 * Permet de générer un lien de réinitialisation
 */

require_once '../includes/config.php';

$message = '';
$message_type = '';
$email_sent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $message = "Veuillez entrer votre adresse email";
        $message_type = 'error';
    } else {
        // Vérifier si l'email existe
        $stmt = $pdo->prepare("SELECT ID, user_nicename FROM EPI_user WHERE user_email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Générer un token unique
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Stocker le token dans la base de données
            // NOTE: Il faut d'abord ajouter les colonnes reset_token et reset_expiry à la table
            $stmt = $pdo->prepare("UPDATE EPI_user SET reset_token = ?, reset_expiry = ? WHERE ID = ?");
            $stmt->execute([$token, $expiry, $user['ID']]);
            
            // Créer le lien de réinitialisation
            $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset-password.php?token=" . $token;
            
            // OPTION 1: Envoi par email (nécessite configuration SMTP)
            /*
            $to = $email;
            $subject = "Réinitialisation de votre mot de passe - Entraide Plus Iroise";
            $message_body = "Bonjour " . $user['user_nicename'] . ",\n\n";
            $message_body .= "Vous avez demandé à réinitialiser votre mot de passe.\n\n";
            $message_body .= "Cliquez sur ce lien pour créer un nouveau mot de passe :\n";
            $message_body .= $reset_link . "\n\n";
            $message_body .= "Ce lien est valable pendant 1 heure.\n\n";
            $message_body .= "Si vous n'avez pas demandé cette réinitialisation, ignorez cet email.\n\n";
            $message_body .= "Cordialement,\nL'équipe Entraide Plus Iroise";
            
            $headers = "From: noreply@entraideplus.fr\r\n";
            $headers .= "Reply-To: contact@entraideplus.fr\r\n";
            
            if (mail($to, $subject, $message_body, $headers)) {
                $email_sent = true;
                $message = "Un email de réinitialisation a été envoyé à votre adresse";
                $message_type = 'success';
            } else {
                $message = "Erreur lors de l'envoi de l'email";
                $message_type = 'error';
            }
            */
            
            // OPTION 2: Afficher le lien directement (pour développement/test)
            $email_sent = true;
            $reset_link_display = $reset_link;
            $message = "Lien de réinitialisation généré avec succès";
            $message_type = 'success';
            
        } else {
            // Pour des raisons de sécurité, on ne dit pas si l'email existe ou non
            $message = "Si cet email existe dans notre système, un lien de réinitialisation a été envoyé";
            $message_type = 'success';
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
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .forgot-card h1 {
            margin-top: 0;
            color: var(--text-primary);
            text-align: center;
            margin-bottom: 0.5rem;
        }
        .forgot-card p {
            text-align: center;
            color: var(--text-secondary);
            margin-bottom: 2rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-primary);
        }
        .form-control {
            width: 100%;
            padding: 0.875rem;
            border: 2px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        .btn {
            width: 100%;
            padding: 1rem;
            border: none;
            border-radius: var(--radius-md);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        .alert {
            padding: 1rem;
            border-radius: var(--radius-md);
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .alert.success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 1.5rem;
            color: var(--primary-color);
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .reset-link-box {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: var(--radius-md);
            margin-top: 1rem;
            border: 2px dashed var(--primary-color);
        }
        .reset-link-box a {
            word-break: break-all;
            color: var(--primary-color);
        }
    </style>
</head>
<body>
    <div class="forgot-container">
        <div class="forgot-card">
            <h1>🔐 Mot de passe oublié</h1>
            <p>Entrez votre adresse email pour recevoir un lien de réinitialisation</p>
            
            <?php if ($message): ?>
                <div class="alert <?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($email_sent && isset($reset_link_display)): ?>
                <div class="reset-link-box">
                    <strong>⚠️ MODE DÉVELOPPEMENT</strong><br>
                    Lien de réinitialisation :<br>
                    <a href="<?php echo htmlspecialchars($reset_link_display); ?>" target="_blank">
                        <?php echo htmlspecialchars($reset_link_display); ?>
                    </a>
                    <br><small>En production, ce lien sera envoyé par email</small>
                </div>
            <?php endif; ?>
            
            <?php if (!$email_sent): ?>
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Adresse email</label>
                        <input type="email" 
                               name="email" 
                               class="form-control" 
                               placeholder="votre@email.fr"
                               required 
                               autocomplete="email">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        📧 Envoyer le lien de réinitialisation
                    </button>
                </form>
            <?php endif; ?>
            
            <a href="login.php" class="back-link">← Retour à la connexion</a>
        </div>
    </div>
</body>
</html>
