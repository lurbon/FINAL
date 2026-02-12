<?php
/**
 * Configuration de l'application - FICHIER EXEMPLE
 * Copiez ce fichier vers config.php et remplissez les valeurs.
 *
 * IMPORTANT : Ne jamais committer config.php (il est dans .gitignore)
 */

// === Base de données ===
define('DB_HOST', 'localhost');
define('DB_NAME', 'votre_base_de_donnees');
define('DB_USER', 'votre_utilisateur');
define('DB_PASSWORD', 'votre_mot_de_passe');

// === Email ===
define('ADMIN_EMAIL', 'entraideplusiroise@gmail.com');
define('NOREPLY_EMAIL', 'noreply@entraide-plus-iroise.fr');

// === Sessions (en secondes) ===
define('SESSION_TIMEOUT_ABSOLUTE_SECONDS', 10800);   // 3 heures
define('SESSION_TIMEOUT_INACTIVITY_SECONDS', 3600);   // 1 heure

// === Rate limiting ===
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_SECONDS', 900);  // 15 minutes

// === Connexion PDO automatique ===
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASSWORD,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    error_log("Erreur connexion BDD: " . $e->getMessage());
    die("Erreur de connexion à la base de données. Contactez l'administrateur.");
}
