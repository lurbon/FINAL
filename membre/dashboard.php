<?php
/**
 * DASHBOARD - Espace Membre
 * Page d'accueil après connexion
 */

session_start();
require_once '../includes/config.php';

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Récupérer les infos du membre
$stmt = $pdo->prepare("SELECT * FROM EPI_user WHERE ID = ?");
$stmt->execute([$user_id]);
$member = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Espace - Entraide Plus Iroise</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background: var(--background-light);
        }
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        .dashboard-header {
            background: white;
            padding: 2rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .dashboard-header h1 {
            margin: 0;
            color: var(--text-primary);
        }
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }
        .dashboard-card {
            background: white;
            padding: 2rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }
        .dashboard-card h2 {
            margin-top: 0;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .dashboard-card p {
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
        }
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: var(--radius-md);
            font-weight: 600;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .btn-secondary {
            background: var(--text-secondary);
        }
        .btn-danger {
            background: #dc3545;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .user-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
        }
        .user-avatar-placeholder {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <div class="user-info">
                <?php if ($member['user_photo']): ?>
                    <img src="../uploads/members/<?php echo htmlspecialchars($member['user_photo']); ?>" 
                         class="user-avatar" 
                         alt="Photo de profil">
                <?php else: ?>
                    <div class="user-avatar-placeholder">👤</div>
                <?php endif; ?>
                <div>
                    <h1>Bonjour, <?php echo htmlspecialchars($user_name); ?> !</h1>
                    <p style="margin: 0; color: var(--text-secondary);">
                        <?php echo htmlspecialchars($member['user_role'] ?? 'Membre'); ?>
                    </p>
                </div>
            </div>
            <a href="?logout=1" class="btn btn-danger">🚪 Déconnexion</a>
        </div>
        
        <?php
        // Gestion de la déconnexion
        if (isset($_GET['logout'])) {
            session_destroy();
            header('Location: login.php');
            exit;
        }
        ?>
        
        <div class="dashboard-grid">
            <!-- Carte Profil -->
            <div class="dashboard-card">
                <h2>👤 Mon Profil</h2>
                <p>Consultez et modifiez vos informations personnelles</p>
                <div style="margin-bottom: 1rem;">
                    <strong>Email :</strong> <?php echo htmlspecialchars($member['user_email'] ?? 'Non renseigné'); ?><br>
                    <strong>Téléphone :</strong> <?php echo htmlspecialchars($member['user_phone'] ?? 'Non renseigné'); ?>
                </div>
                <a href="profil.php" class="btn">Voir mon profil</a>
            </div>
            
            <!-- Carte Sécurité -->
            <div class="dashboard-card">
                <h2>🔐 Sécurité</h2>
                <p>Gérez votre mot de passe et la sécurité de votre compte</p>
                <a href="change-password.php" class="btn">Modifier mon mot de passe</a>
            </div>
            
            <!-- Carte Missions (si applicable) -->
            <div class="dashboard-card">
                <h2>📋 Mes Missions</h2>
                <p>Consultez vos missions et activités bénévoles</p>
                <a href="missions.php" class="btn">Voir mes missions</a>
            </div>
            
            <!-- Carte Messagerie -->
            <div class="dashboard-card">
                <h2>✉️ Messages</h2>
                <p>Communiquez avec les autres membres et l'équipe</p>
                <a href="messages.php" class="btn">Mes messages</a>
            </div>
            
            <!-- Carte Actualités -->
            <div class="dashboard-card">
                <h2>📰 Actualités</h2>
                <p>Restez informé des dernières nouvelles de l'association</p>
                <a href="../index.php#actualites" class="btn btn-secondary">Voir les actualités</a>
            </div>
            
            <!-- Carte Aide -->
            <div class="dashboard-card">
                <h2>❓ Besoin d'aide ?</h2>
                <p>Contactez l'équipe ou consultez la FAQ</p>
                <a href="contact.php" class="btn btn-secondary">Nous contacter</a>
            </div>
        </div>
    </div>
</body>
</html>
