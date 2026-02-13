<?php
/**
 * SCRIPT DE NETTOYAGE - LOGS ET SESSIONS
 * À exécuter périodiquement via CRON (recommandé: 1x par jour)
 * 
 * Exemple CRON:
 * 0 2 * * * /usr/bin/php /chemin/vers/cleanup-logs.php
 * 
 * @version 1.0
 * @author Entraide Plus Iroise
 */

require_once __DIR__ . '/../includes/config.php';

echo "=== NETTOYAGE DES LOGS ET SESSIONS ===\n";
echo "Début: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // 1. Supprimer les sessions inactives (> 3 heures)
    echo "1. Nettoyage des sessions inactives...\n";
    $stmt = $pdo->prepare("
        DELETE FROM EPI_active_sessions 
        WHERE last_activity < DATE_SUB(NOW(), INTERVAL 3 HOUR)
    ");
    $stmt->execute();
    $deleted_sessions = $stmt->rowCount();
    echo "   ✓ {$deleted_sessions} session(s) inactive(s) supprimée(s)\n\n";
    
    // 2. Archiver les anciens logs (> 90 jours) - optionnel
    // Vous pouvez créer une table EPI_auth_logs_archive pour conserver l'historique
    
    // 3. Supprimer les très anciens logs (> 1 an)
    echo "2. Suppression des logs de plus d'1 an...\n";
    $stmt = $pdo->prepare("
        DELETE FROM EPI_auth_logs 
        WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR)
    ");
    $stmt->execute();
    $deleted_logs = $stmt->rowCount();
    echo "   ✓ {$deleted_logs} log(s) ancien(s) supprimé(s)\n\n";
    
    // 4. Nettoyer l'historique des mots de passe (garder seulement les 5 derniers par user)
    echo "3. Nettoyage de l'historique des mots de passe...\n";
    $stmt = $pdo->query("
        DELETE ph1 FROM EPI_password_history ph1
        LEFT JOIN (
            SELECT user_id, id
            FROM (
                SELECT user_id, id,
                       ROW_NUMBER() OVER (PARTITION BY user_id ORDER BY changed_at DESC) as rn
                FROM EPI_password_history
            ) AS ranked
            WHERE rn <= 5
        ) ph2 ON ph1.id = ph2.id
        WHERE ph2.id IS NULL
    ");
    $deleted_passwords = $stmt->rowCount();
    echo "   ✓ {$deleted_passwords} ancien(s) hash(s) de mot de passe supprimé(s)\n\n";
    
    // 5. Nettoyer les tokens de reset expirés
    echo "4. Nettoyage des tokens de reset expirés...\n";
    $stmt = $pdo->prepare("
        UPDATE EPI_user 
        SET reset_token = NULL, reset_expiry = NULL 
        WHERE reset_expiry < NOW()
    ");
    $stmt->execute();
    $cleaned_tokens = $stmt->rowCount();
    echo "   ✓ {$cleaned_tokens} token(s) expiré(s) nettoyé(s)\n\n";
    
    // 6. Statistiques après nettoyage
    echo "=== STATISTIQUES ===\n";
    
    // Nombre total de logs restants
    $stmt = $pdo->query("SELECT COUNT(*) FROM EPI_auth_logs");
    $total_logs = $stmt->fetchColumn();
    echo "Logs d'authentification: " . number_format($total_logs) . "\n";
    
    // Sessions actives
    $stmt = $pdo->query("SELECT COUNT(*) FROM EPI_active_sessions");
    $active_sessions = $stmt->fetchColumn();
    echo "Sessions actives: {$active_sessions}\n";
    
    // Historique mots de passe
    $stmt = $pdo->query("SELECT COUNT(*) FROM EPI_password_history");
    $password_history = $stmt->fetchColumn();
    echo "Historique mots de passe: " . number_format($password_history) . "\n";
    
    // Taille de la table auth_logs
    $stmt = $pdo->query("
        SELECT 
            ROUND((data_length + index_length) / 1024 / 1024, 2) as size_mb
        FROM information_schema.TABLES 
        WHERE table_schema = DATABASE()
        AND table_name = 'EPI_auth_logs'
    ");
    $size = $stmt->fetchColumn();
    echo "Taille table auth_logs: {$size} MB\n";
    
    echo "\n=== NETTOYAGE TERMINÉ ===\n";
    echo "Fin: " . date('Y-m-d H:i:s') . "\n";
    
    // Logger l'événement de nettoyage
    error_log("[CLEANUP] Nettoyage effectué - Sessions: {$deleted_sessions}, Logs: {$deleted_logs}, PWD: {$deleted_passwords}, Tokens: {$cleaned_tokens}");
    
} catch (PDOException $e) {
    echo "ERREUR: " . $e->getMessage() . "\n";
    error_log("[CLEANUP] Erreur: " . $e->getMessage());
    exit(1);
}
