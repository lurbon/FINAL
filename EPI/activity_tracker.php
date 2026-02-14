<?php
/**
 * FICHIER DESACTIVE - La fonction enregistrerActiviteDB() n'est appelee
 * dans aucun fichier du projet. Le tracking d'activite est gere par
 * SessionManager::updateActivity() dans includes/auth/SessionManager.php.
 *
 * Conserve au cas ou, mais peut etre supprime en toute securite.
 */

/*
function enregistrerActiviteDB($pdo = null) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['connexion_log_id'])) {
        return false;
    }

    $closeConnection = false;

    if ($pdo === null) {
        try {
            require_once(__DIR__ . '/config.php');
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASSWORD,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $closeConnection = true;
        } catch (PDOException $e) {
            error_log("Erreur connexion PDO pour activite: " . $e->getMessage());
            return false;
        }
    }

    try {
        $stmt = $pdo->prepare("
            UPDATE connexions_log
            SET last_activity_db = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$_SESSION['connexion_log_id']]);
        $_SESSION['last_activity'] = time();
        return true;
    } catch (PDOException $e) {
        error_log("Erreur enregistrement activite DB: " . $e->getMessage());
        return false;
    } finally {
        if ($closeConnection && $pdo !== null) {
            $pdo = null;
        }
    }
}
*/
