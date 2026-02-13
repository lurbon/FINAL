<?php
/**
 * PAGE DE CONNEXION - Espace Membre (UNIFIÉ & SÉCURISÉ)
 * 
 * Utilise les classes centralisées :
 * - PasswordManager : Vérification et hashing
 * - SessionManager : Gestion des sessions
 * - RateLimiter : Protection brute-force
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

// Initialiser la session sécurisée
SessionManager::init();

// Si déjà connecté, rediriger vers le dashboard
if (SessionManager::isLoggedIn()) {
    header('Location: ../EPI/dashboard.php');
    exit;
}

$message = '';
$message_type = '';

// Récupérer les messages de session (après logout ou expiration)
if (isset($_SESSION['error_message'])) {
    $message = $_SESSION['error_message'];
    $message_type = 'error';
    unset($_SESSION['error_message']);
}

if (isset($_SESSION['success_message'])) {
    $message = $_SESSION['success_message'];
    $message_type = 'success';
    unset($_SESSION['success_message']);
}

// Message après réinitialisation réussie
if (isset($_GET['message']) && $_GET['message'] === 'password_changed') {
    $message = "Votre mot de passe a été modifié avec succès. Vous pouvez maintenant vous connecter.";
    $message_type = 'success';
}

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_protect();
    
    $email = sanitize_email($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validation basique
    if (empty($email) || empty($password)) {
        $message = "Veuillez remplir tous les champs";
        $message_type = 'error';
    }
    // Vérifier le rate limiting
    elseif (RateLimiter::isLocked('login')) {
        $message = RateLimiter::getErrorMessage('login');
        $message_type = 'error';
    }
    else {
        try {
            // Récupérer l'utilisateur
            $stmt = $pdo->prepare("
                SELECT ID, user_nicename, user_email, user_pass, user_fonction
                FROM EPI_user
                WHERE user_email = ?
                LIMIT 1
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Vérifier le mot de passe
            if ($user && PasswordManager::verify($password, $user['user_pass'])) {
                // ✅ CONNEXION RÉUSSIE
                
                // Migration automatique si nécessaire (phpass → bcrypt)
                if (PasswordManager::needsRehash($user['user_pass'])) {
                    PasswordManager::migratePassword($pdo, $user['ID'], $password);
                }
                
                // Créer la session
                SessionManager::login($user);
                
                // Réinitialiser le rate limiter
                RateLimiter::reset('login');
                
                // Rediriger vers le dashboard
                header('Location: ../EPI/dashboard.php');
                exit;
                
            } else {
                // ❌ ÉCHEC DE CONNEXION
                RateLimiter::record('login', false);
                
                $attempts_left = RateLimiter::getRemainingAttempts('login');
                
                if ($attempts_left > 0) {
                    $message = "Email ou mot de passe incorrect. ";
                    if ($attempts_left <= 2) {
                        $message .= "Attention : il vous reste $attempts_left tentative(s).";
                    }
                } else {
                    $message = RateLimiter::getErrorMessage('login');
                }
                
                $message_type = 'error';
            }
            
        } catch (PDOException $e) {
            error_log("Erreur login: " . $e->getMessage());
            $message = "Une erreur est survenue. Veuillez réessayer.";
            $message_type = 'error';
        }
    }
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
        :root {
            --primary-color: #667eea;
            --primary-dark: #5568d3;
            --secondary-color: #764ba2;
            --text-primary: #2d3748;
            --text-secondary: #718096;
            --border-color: #e2e8f0;
            --error-color: #e53e3e;
            --success-color: #38a169;
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
        
        .login-container {
            max-width: 450px;
            width: 100%;
        }
        
        .login-card {
            background: white;
            padding: 2.5rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-header h1 {
            margin: 0 0 0.5rem 0;
            color: var(--text-primary);
            font-size: 1.875rem;
        }
        
        .login-header p {
            margin: 0;
            color: var(--text-secondary);
            font-size: 0.95rem;
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
        
        .forgot-password {
            text-align: center;
            margin-top: 1rem;
        }
        
        .forgot-password a {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.95rem;
            transition: color 0.3s ease;
        }
        
        .forgot-password a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }
        
        .back-home {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-color);
        }
        
        .back-home a {
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.95rem;
            transition: color 0.3s ease;
        }
        
        .back-home a:hover {
            color: var(--text-primary);
        }
        
        .security-info {
            background: #f7fafc;
            padding: 1rem;
            border-radius: var(--radius-md);
            margin-top: 1.5rem;
            font-size: 0.85rem;
            color: var(--text-secondary);
            text-align: center;
        }
        
        @media (max-width: 480px) {
            .login-card {
                padding: 2rem 1.5rem;
            }
            
            .login-header h1 {
                font-size: 1.5rem;
            }
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
                <div class="alert <?php echo htmlspecialchars($message_type); ?>">
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
                    <div class="password-wrapper">
                        <input type="password"
                               id="password"
                               name="password"
                               class="form-control"
                               placeholder="Votre mot de passe"
                               required
                               autocomplete="current-password">
                        <button type="button" class="toggle-password" onclick="togglePassword('password')" title="Afficher/Masquer le mot de passe">👁️</button>
                    </div>
                </div>
                
                <button type="submit" 
                        class="btn btn-primary"
                        <?php echo RateLimiter::isLocked('login') ? 'disabled' : ''; ?>>
                    <?php if (RateLimiter::isLocked('login')): ?>
                        ⏳ Veuillez patienter...
                    <?php else: ?>
                        ✓ Se connecter
                    <?php endif; ?>
                </button>
            </form>
            
            <div class="forgot-password">
                <a href="forgot-password.php">Mot de passe oublié ?</a>
            </div>
            
            <div class="security-info">
                🔒 Connexion sécurisée · Vos données sont protégées
            </div>
            
            <div class="back-home">
                <a href="../index.php">← Retour au site</a>
            </div>
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
    </script>
</body>
</html>