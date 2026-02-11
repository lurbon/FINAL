<?php
/**
 * PAGE DE RÉINITIALISATION DE MOT DE PASSE
 * Utilise le token généré par forgot-password.php
 */

require_once '../includes/config.php';

$message = '';
$message_type = '';
$token_valid = false;
$user_id = null;

// Vérifier le token
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // Rechercher le token dans la base de données
    $stmt = $pdo->prepare("SELECT ID, user_nicename, reset_expiry FROM EPI_user WHERE reset_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Vérifier si le token n'a pas expiré
        if (strtotime($user['reset_expiry']) > time()) {
            $token_valid = true;
            $user_id = $user['ID'];
            $user_name = $user['user_nicename'];
        } else {
            $message = "Ce lien de réinitialisation a expiré. Veuillez en demander un nouveau.";
            $message_type = 'error';
        }
    } else {
        $message = "Ce lien de réinitialisation n'est pas valide.";
        $message_type = 'error';
    }
} else {
    $message = "Aucun token de réinitialisation fourni.";
    $message_type = 'error';
}

// Traitement du formulaire de réinitialisation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token_valid) {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($new_password) || empty($confirm_password)) {
        $message = "Tous les champs sont obligatoires";
        $message_type = 'error';
    } elseif ($new_password !== $confirm_password) {
        $message = "Les mots de passe ne correspondent pas";
        $message_type = 'error';
    } elseif (strlen($new_password) < 6) {
        $message = "Le mot de passe doit contenir au moins 6 caractères";
        $message_type = 'error';
    } else {
        // Tout est OK, on peut réinitialiser le mot de passe
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Mettre à jour le mot de passe et supprimer le token
        $stmt = $pdo->prepare("UPDATE EPI_user SET user_password = ?, reset_token = NULL, reset_expiry = NULL WHERE ID = ?");
        
        if ($stmt->execute([$hashed_password, $user_id])) {
            $message = "Votre mot de passe a été réinitialisé avec succès ! Vous pouvez maintenant vous connecter.";
            $message_type = 'success';
            $token_valid = false; // Empêcher une nouvelle soumission
            
            // Optionnel : redirection automatique après 3 secondes
            header("refresh:3;url=login.php");
        } else {
            $message = "Erreur lors de la réinitialisation du mot de passe";
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
    <title>Réinitialiser mon mot de passe - Entraide Plus Iroise</title>
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
        .reset-container {
            max-width: 500px;
            width: 100%;
        }
        .reset-card {
            background: white;
            padding: 2.5rem;
            border-radius: var(--radius-lg);
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .reset-card h1 {
            margin-top: 0;
            color: var(--text-primary);
            text-align: center;
            margin-bottom: 0.5rem;
        }
        .reset-card p {
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
        .user-info {
            background: #e7f3ff;
            padding: 1rem;
            border-radius: var(--radius-md);
            margin-bottom: 1.5rem;
            text-align: center;
            border-left: 4px solid var(--primary-color);
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-card">
            <h1>🔐 Nouveau mot de passe</h1>
            
            <?php if ($message): ?>
                <div class="alert <?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($token_valid): ?>
                <div class="user-info">
                    👤 <strong><?php echo htmlspecialchars($user_name); ?></strong><br>
                    <small>Définissez votre nouveau mot de passe</small>
                </div>
                
                <div class="password-requirements">
                    <h3>📋 Exigences</h3>
                    <ul style="margin: 0.5rem 0 0 1.5rem; padding: 0;">
                        <li>Minimum 6 caractères</li>
                        <li>Mélangez lettres, chiffres et caractères spéciaux (recommandé)</li>
                    </ul>
                </div>
                
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Nouveau mot de passe *</label>
                        <input type="password" 
                               name="new_password" 
                               class="form-control" 
                               required 
                               minlength="6"
                               autocomplete="new-password"
                               placeholder="Minimum 6 caractères">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Confirmer le mot de passe *</label>
                        <input type="password" 
                               name="confirm_password" 
                               class="form-control" 
                               required 
                               minlength="6"
                               autocomplete="new-password"
                               placeholder="Retapez le mot de passe">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        ✓ Réinitialiser mon mot de passe
                    </button>
                </form>
            <?php else: ?>
                <p>
                    <a href="forgot-password.php" class="btn btn-primary">
                        Demander un nouveau lien
                    </a>
                </p>
            <?php endif; ?>
            
            <a href="login.php" class="back-link">← Retour à la connexion</a>
        </div>
    </div>
    
    <script>
        // Validation côté client
        document.querySelector('form')?.addEventListener('submit', function(e) {
            const newPassword = document.querySelector('input[name="new_password"]').value;
            const confirmPassword = document.querySelector('input[name="confirm_password"]').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Les mots de passe ne correspondent pas');
            }
        });
    </script>
</body>
</html>
