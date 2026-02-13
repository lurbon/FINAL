<?php
require_once '../includes/config.php';
require_once '../includes/csrf.php';
require_once '../includes/sanitize.php';

$error = '';

// Si déjà connecté, rediriger
if (isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

// Rate limiting basique par IP (en session)
$maxAttempts = 5;
$lockoutTime = 900; // 15 minutes

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier le token CSRF
    if (!csrf_verify()) {
        $error = "Requête invalide. Veuillez recharger la page.";
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $attemptKey = 'login_attempts_' . md5($ip);

        // Vérifier le rate limiting
        if (isset($_SESSION[$attemptKey])) {
            $attempts = $_SESSION[$attemptKey];
            if ($attempts['count'] >= $maxAttempts && (time() - $attempts['first']) < $lockoutTime) {
                $remaining = ceil(($lockoutTime - (time() - $attempts['first'])) / 60);
                $error = "Trop de tentatives. Réessayez dans $remaining minutes.";
            }
        }

        if (empty($error)) {
            $username = sanitize_text($_POST['username'] ?? '', 100);
            $password = $_POST['password'] ?? '';

            if (!empty($username) && !empty($password)) {
                $stmt = $pdo->prepare("SELECT * FROM EPI_admins WHERE username = ?");
                $stmt->execute([$username]);
                $admin = $stmt->fetch();

                if ($admin && password_verify($password, $admin['password'])) {
                    // Régénérer l'ID de session (protection fixation de session)
                    session_regenerate_id(true);

                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_username'] = $admin['username'];
                    $_SESSION['admin_login_time'] = time();
                    $_SESSION['admin_last_activity'] = time();

                    // Régénérer le token CSRF
                    csrf_regenerate();

                    // Réinitialiser les tentatives
                    $attemptKey = 'login_attempts_' . md5($ip);
                    unset($_SESSION[$attemptKey]);

                    // Mettre à jour last_login
                    $stmt = $pdo->prepare("UPDATE EPI_admins SET last_login = NOW() WHERE id = ?");
                    $stmt->execute([$admin['id']]);

                    header('Location: index.php');
                    exit;
                } else {
                    // Incrémenter les tentatives
                    if (!isset($_SESSION[$attemptKey])) {
                        $_SESSION[$attemptKey] = ['count' => 0, 'first' => time()];
                    }
                    $_SESSION[$attemptKey]['count']++;

                    $error = "Identifiants incorrects";
                }
            } else {
                $error = "Veuillez remplir tous les champs";
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
    <title>Connexion - Administration</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .login-container {
            background: white;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-xl);
            padding: 3rem;
            width: 100%;
            max-width: 450px;
        }
        .login-logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-logo h1 {
            color: var(--primary-color);
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        .login-logo p {
            color: var(--text-secondary);
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
            color: var(--secondary-color, #764ba2);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-logo">
            <h1>Administration</h1>
            <p>Entraide Plus Iroise</p>
        </div>

        <?php if ($error): ?>
            <div class="form-message error" style="margin-bottom: 1.5rem;">
                <?php echo e($error); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['expired'])): ?>
            <div class="form-message error" style="margin-bottom: 1.5rem;">
                Votre session a expir&eacute;. Veuillez vous reconnecter.
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <?php echo csrf_field(); ?>
            <div class="form-group">
                <label for="username" class="form-label">Nom d'utilisateur</label>
                <input type="text"
                       id="username"
                       name="username"
                       class="form-control"
                       required
                       autofocus>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Mot de passe</label>
                <div class="password-wrapper">
                    <input type="password"
                           id="password"
                           name="password"
                           class="form-control"
                           required>
                    <button type="button" class="toggle-password" onclick="togglePassword('password')" title="Afficher/Masquer le mot de passe">👁️</button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; font-size: 1.125rem; margin-top: 1rem;">
                Se connecter
            </button>
        </form>

        <div style="text-align: center; margin-top: 2rem;">
            <a href="../index.php" style="color: var(--text-secondary); font-size: 0.875rem;">
                &larr; Retour au site
            </a>
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