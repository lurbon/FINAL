<?php
/**
 * Connexion PDO centralisée
 *
 * Fournit une instance PDO unique (singleton) pour toute l'application.
 * Remplace les connexions PDO dupliquées dans chaque fichier.
 *
 * Usage :
 *   require_once __DIR__ . '/database.php';
 *   $pdo = getDBConnection();
 *
 * Ou depuis EPI/ :
 *   require_once __DIR__ . '/../includes/database.php';
 *   $pdo = getDBConnection();
 */

/**
 * Retourne une instance PDO unique (singleton)
 *
 * @return PDO Instance de connexion à la base de données
 * @throws RuntimeException Si la connexion échoue (sans exposer les détails)
 */
function getDBConnection(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        // Chercher config.php dans les emplacements possibles
        $configPaths = [
            __DIR__ . '/config.php',
            __DIR__ . '/../EPI/config.php',
            __DIR__ . '/../config.php',
        ];

        $configLoaded = false;
        foreach ($configPaths as $path) {
            if (file_exists($path)) {
                require_once $path;
                $configLoaded = true;
                break;
            }
        }

        if (!$configLoaded) {
            error_log("database.php: Fichier config.php introuvable");
            throw new RuntimeException("Erreur de configuration du serveur.");
        }

        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASSWORD,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE  => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES    => false,
                ]
            );
        } catch (PDOException $e) {
            // Logger l'erreur détaillée côté serveur
            error_log("Erreur connexion BDD: " . $e->getMessage());
            // Ne JAMAIS exposer les détails de connexion à l'utilisateur
            throw new RuntimeException("Erreur de connexion à la base de données. Contactez l'administrateur.");
        }
    }

    return $pdo;
}
