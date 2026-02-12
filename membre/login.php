<?php
/**
 * PAGE DE CONNEXION - Espace Membre (SÉCURISÉ)
 *
 * Protections :
 * - Configuration sécurisée des sessions (httponly, secure, samesite)
 * - Protection CSRF
 * - Rate limiting (5 tentatives / 15 min)
 * - Timeout de session (3h absolu, 1h inactivité)
 * - Régénération d'ID de session à la connexion
 */

// Configuration sécurisée des sessions AVANT session_start()
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_strict_mode', 1);
ini_set('session.use_only_cookies', 1);

session_start();

require_once '../includes/config.php';
require_once '../includes/csrf.php';
require_once '../includes/sanitize.php';

// Constantes de session pour l'espace membre
define('MEMBRE_SESSION_TIMEOUT_ABSOLUTE', 10800);  // 3 heures
define('MEMBRE_SESSION_TIMEOUT_INACTIVITY', 3600);  // 1 heure
define('MEMBRE_MAX_LOGIN_ATTEMPTS', 5);
define('MEMBRE_LOCKOUT_DURATION', 900); // 15 minutes

// Vérifier timeout si déjà connecté
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
    $now = time();
    $expired = false;

    if (isset($_SESSION['membre_login_time']) && ($now - $_SESSION['membre_login_time']) > MEMBRE_SESSION_TIMEOUT_ABSOLUTE) {
        $expired = true;
    }
    if (isset($_SESSION['membre_last_activity']) && ($now - $_SESSION['membre_last_activity']) > MEMBRE_SESSION_TIMEOUT_INACTIVITY) {
        $expired = true;
    }

    if ($expired) {
        session_destroy();
        session_start();
        $message = "Votre session a expiré. Veuillez vous reconnecter.";
        $message_type = 'error';
    } else {
        header('Location: dashboard.php');
        exit;
    }
}

$message = $message ?? '';
$message_type = $message_type ?? '';

// Rate limiting basé sur l'IP
function checkRateLimit(): bool {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = 'membre_login_attempts_' . md5($ip);

    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'first_attempt' => time()];
    }

    $data = &$_SESSION[$key];

    // Réinitialiser après la période de lockout
    if ((time() - $data['first_attempt']) > MEMBRE_LOCKOUT_DURATION) {
        $data = ['count' => 0, 'first_attempt' => time()];
    }

    if ($data['count'] >= MEMBRE_MAX_LOGIN_ATTEMPTS) {
        return false; // Bloqué
    }

    $data['count']++;
    return true;
}

// Traitement de la connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_protect();

    $email = sanitize_email($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $message = "Veuillez remplir tous les champs";
        $message_type = 'error';
    } elseif (!checkRateLimit()) {
        $message = "Trop de tentatives. Réessayez dans 15 minutes.";
        $message_type = 'error';
    } else {
        // Récupérer l'utilisateur
        $stmt = $pdo->prepare("SELECT ID, user_nicename, user_email, user_pass, user_role
                               FROM EPI_user
                               WHERE user_email = ?
                               LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['user_pass'])) {
            // Régénérer l'ID de session (protection session fixation)
            session_regenerate_id(true);

            // Connexion réussie
            $_SESSION['user_id'] = $user['ID'];
            $_SESSION['user_name'] = $user['user_nicename'];
            $_SESSION['user_email'] = $user['user_email'];
            $_SESSION['user_role'] = $user['user_role'];
            $_SESSION['logged_in'] = true;
            $_SESSION['membre_login_time'] = time();
            $_SESSION['membre_last_activity'] = time();

            // Réinitialiser le compteur de tentatives
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            unset($_SESSION['membre_login_attempts_' . md5($ip)]);

            header('Location: dashboard.php');
            exit;
        } else {
            $message = "Email ou mot de passe incorrect";
            $message_type = 'error';
        }
    }
}

// Message après réinitialisation réussie
if (isset($_GET['message']) && $_GET['message'] === 'password_changed') {
    $message = "Votre mot de passe a été modifié avec succès. Vous pouvez maintenant vous connecter.";
    $message_type = 'success';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Espace Membre</title>
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
        .login-container {
            max-width: 450px;
            width: 100%;
        }
        .login-card {
            background: white;
            padding: 2.5rem;
            border-radius: var(--radius-lg);
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-header h1 {
            margin: 0 0 0.5rem 0;
            color: var(--text-primary);
        }
        .login-header p {
            margin: 0;
            color: var(--text-secondary);
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
        .forgot-password {
            text-align: center;
            margin-top: 1rem;
        }
        .forgot-password a {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.95rem;
        }
        .forgot-password a:hover {
            text-decoration: underline;
        }
        .back-home {
            text-align: center;
            margin-top: 1.5rem;
        }
        .back-home a {
            color: var(--text-secondary);
            text-decoration: none;
        }
        .back-home a:hover {
            color: var(--text-primary);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>🔐 Espace Membre</h1>
                <p>Connectez-vous à votre compte</p>
            </div>
            
            <?php if ($message): ?>
                <div class="alert <?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" autocomplete="on">
                <?php echo csrf_field(); ?>
                <div class="form-group">
                    <label class="form-label">Adresse email</label>
                    <input type="email" 
                           name="email" 
                           class="form-control" 
                           placeholder="votre@email.fr"
                           required 
                           autocomplete="email"
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Mot de passe</label>
                    <input type="password" 
                           name="password" 
                           class="form-control" 
                           placeholder="Votre mot de passe"
                           required 
                           autocomplete="current-password">
                </div>
                
                <button type="submit" class="btn btn-primary">
                    ✓ Se connecter
                </button>
            </form>
            
            <div class="forgot-password">
                <a href="forgot-password.php">Mot de passe oublié ?</a>
            </div>
            
            <div class="back-home">
                <a href="../index.php">← Retour au site</a>
            </div>
        </div>
    </div>
</body>
</html>
