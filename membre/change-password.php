<?php
/**
 * PAGE DE MODIFICATION DE MOT DE PASSE
 * Pour l'espace membre - permet à chaque membre de changer son propre mot de passe
 */

session_start();
require_once '../includes/config.php';

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit;
}

$message = '';
$message_type = '';

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $user_id = $_SESSION['user_id'];
    
    // Validation
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $message = "Tous les champs sont obligatoires";
        $message_type = 'error';
    } elseif ($new_password !== $confirm_password) {
        $message = "Les nouveaux mots de passe ne correspondent pas";
        $message_type = 'error';
    } elseif (strlen($new_password) < 6) {
        $message = "Le nouveau mot de passe doit contenir au moins 6 caractères";
        $message_type = 'error';
    } else {
        // Récupérer le mot de passe actuel de l'utilisateur
        $stmt = $pdo->prepare("SELECT user_password FROM EPI_user WHERE ID = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($current_password, $user['user_password'])) {
            // Le mot de passe actuel est correct, on peut le changer
            $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("UPDATE EPI_user SET user_password = ? WHERE ID = ?");
            if ($stmt->execute([$new_hashed_password, $user_id])) {
                $message = "Votre mot de passe a été modifié avec succès";
                $message_type = 'success';
                
                // Optionnel : forcer la reconnexion
                // session_destroy();
                // header('Location: login.php?message=password_changed');
                // exit;
            } else {
                $message = "Erreur lors de la modification du mot de passe";
                $message_type = 'error';
            }
        } else {
            $message = "Le mot de passe actuel est incorrect";
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
    <title>Modifier mon mot de passe - Espace Membre</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
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
    </style>
</head>
<body>
    <div class="member-container">
        <div class="password-card">
            <h1>
                🔐 Modifier mon mot de passe
            </h1>
            
            <?php if ($message): ?>
                <div class="alert <?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" autocomplete="off">
                <div class="form-group">
                    <label class="form-label">Mot de passe actuel *</label>
                    <input type="password" 
                           name="current_password" 
                           class="form-control" 
                           required 
                           autocomplete="current-password">
                </div>
                
                <div class="password-requirements">
                    <h3>📋 Exigences pour le nouveau mot de passe</h3>
                    <ul>
                        <li>Minimum 6 caractères</li>
                        <li>Différent de votre mot de passe actuel</li>
                        <li>Recommandé : mélangez lettres, chiffres et caractères spéciaux</li>
                    </ul>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Nouveau mot de passe *</label>
                    <input type="password" 
                           name="new_password" 
                           class="form-control" 
                           required 
                           minlength="6"
                           autocomplete="new-password">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Confirmer le nouveau mot de passe *</label>
                    <input type="password" 
                           name="confirm_password" 
                           class="form-control" 
                           required 
                           minlength="6"
                           autocomplete="new-password">
                </div>
                
                <div style="margin-top: 2rem;">
                    <button type="submit" class="btn btn-primary">
                        ✓ Modifier mon mot de passe
                    </button>
                    <a href="espace-membre.php" class="btn btn-secondary">
                        ← Retour
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Validation côté client pour vérifier que les mots de passe correspondent
        document.querySelector('form').addEventListener('submit', function(e) {
            const newPassword = document.querySelector('input[name="new_password"]').value;
            const confirmPassword = document.querySelector('input[name="confirm_password"]').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Les nouveaux mots de passe ne correspondent pas');
            }
        });
    </script>
</body>
</html>
