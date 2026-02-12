<?php
/**
 * PAGE "MOT DE PASSE OUBLIÉ"
 * Permet de générer un lien de réinitialisation envoyé par email
 */

require_once '../includes/config.php';
require_once '../includes/csrf.php';

// Configuration sécurisée de la session
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_samesite', 'Lax');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Rate limiting : max 3 demandes par IP toutes les 15 minutes
define('FORGOT_MAX_ATTEMPTS', 3);
define('FORGOT_LOCKOUT_DURATION', 900);

function forgotRateLimit(): bool {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = 'forgot_attempts_' . md5($ip);

    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'first_attempt' => time()];
    }

    $data = &$_SESSION[$key];

    if ((time() - $data['first_attempt']) > FORGOT_LOCKOUT_DURATION) {
        $data = ['count' => 0, 'first_attempt' => time()];
    }

    if ($data['count'] >= FORGOT_MAX_ATTEMPTS) {
        return false;
    }

    $data['count']++;
    return true;
}

$message = '';
$message_type = '';
$email_sent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier le token CSRF
    csrf_protect();

    // Vérifier le rate limiting
    if (!forgotRateLimit()) {
        $message = "Trop de demandes. Veuillez réessayer dans 15 minutes.";
        $message_type = 'error';
    } else {
        $email = trim($_POST['email'] ?? '');

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "Veuillez entrer une adresse email valide";
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
                $stmt = $pdo->prepare("UPDATE EPI_user SET reset_token = ?, reset_expiry = ? WHERE ID = ?");
                $stmt->execute([$token, $expiry, $user['ID']]);

                // Créer le lien de réinitialisation (HTTPS si disponible)
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $reset_link = $protocol . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset-password.php?token=" . $token;

                // Envoi par email
                $to = $email;
                $subject = "Réinitialisation de votre mot de passe - Entraide Plus Iroise";
                $message_body = "Bonjour " . $user['user_nicename'] . ",\n\n";
                $message_body .= "Vous avez demandé à réinitialiser votre mot de passe.\n\n";
                $message_body .= "Cliquez sur ce lien pour créer un nouveau mot de passe :\n";
                $message_body .= $reset_link . "\n\n";
                $message_body .= "Ce lien est valable pendant 1 heure.\n\n";
                $message_body .= "Si vous n'avez pas demandé cette réinitialisation, ignorez cet email.\n\n";
                $message_body .= "Cordialement,\nL'équipe Entraide Plus Iroise";

                $fromEmail = defined('NOREPLY_EMAIL') ? NOREPLY_EMAIL : 'noreply@entraide-plus-iroise.fr';
                $headers = "From: " . $fromEmail . "\r\n";
                $headers .= "Reply-To: " . (defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'entraideplusiroise@gmail.com') . "\r\n";
                $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

                mail($to, $subject, $message_body, $headers);
            }

            // Message identique que l'email existe ou non (anti-énumération)
            $email_sent = true;
            $message = "Si cet email existe dans notre système, un lien de réinitialisation a été envoyé. Vérifiez votre boîte de réception et vos spams.";
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
    </style>
</head>
<body>
    <div class="forgot-container">
        <div class="forgot-card">
            <h1>Mot de passe oublié</h1>
            <p>Entrez votre adresse email pour recevoir un lien de réinitialisation</p>

            <?php if ($message): ?>
                <div class="alert <?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if (!$email_sent): ?>
                <form method="POST">
                    <?php echo csrf_field(); ?>
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
                        Envoyer le lien de réinitialisation
                    </button>
                </form>
            <?php endif; ?>

            <a href="login.php" class="back-link">&larr; Retour à la connexion</a>
        </div>
    </div>
</body>
</html>
