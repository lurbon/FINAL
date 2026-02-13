<?php
/**
 * SESSIONMANAGER - Gestion centralisée des sessions
 * 
 * Fonctionnalités :
 * - Configuration sécurisée
 * - Timeout absolu et inactivité
 * - Régénération d'ID
 * - Protection session fixation
 * 
 * @version 1.0
 * @author Entraide Plus Iroise
 */

class SessionManager {
    
    // Timeouts (en secondes)
    const TIMEOUT_ABSOLUTE = 10800;     // 3 heures
    const TIMEOUT_INACTIVITY = 3600;    // 1 heure
    const REGENERATE_INTERVAL = 300;    // 5 minutes
    
    /**
     * Initialiser une session sécurisée
     */
    public static function init(): void {
        // Configuration sécurisée AVANT session_start()
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', 1);
            ini_set('session.cookie_samesite', 'Lax');
            ini_set('session.use_strict_mode', 1);
            ini_set('session.use_only_cookies', 1);
            
            session_start();
        }
        
        // Vérifier et gérer les timeouts
        self::checkTimeouts();
        
        // Régénérer périodiquement l'ID
        self::maybeRegenerateId();
    }
    
    /**
     * Créer une nouvelle session utilisateur
     * 
     * @param array $user_data Données utilisateur
     */
    public static function login(array $user_data): void {
        global $pdo;
        
        // Régénérer l'ID pour prévenir session fixation
        session_regenerate_id(true);
        
        // Stocker les données utilisateur
        $_SESSION['user_id'] = $user_data['ID'];
        $_SESSION['user_name'] = $user_data['user_nicename'];
        $_SESSION['user_email'] = $user_data['user_email'];
        $_SESSION['user_fonction'] = $user_data['user_fonction'] ?? 'membre';
        $_SESSION['logged_in'] = true;
        
        // Timestamps
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['last_regeneration'] = time();
        
        // Fingerprint (protection contre vol de session)
        $_SESSION['fingerprint'] = self::generateFingerprint();
        
        // Logger l'événement
        self::logAuthEvent($user_data['ID'], 'login', true);
        
        // Ajouter à la table des sessions actives
        if ($pdo) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO EPI_active_sessions 
                    (session_id, user_id, ip_address, user_agent, login_time, last_activity)
                    VALUES (?, ?, ?, ?, NOW(), NOW())
                    ON DUPLICATE KEY UPDATE
                    last_activity = NOW()
                ");
                
                $stmt->execute([
                    session_id(),
                    $user_data['ID'],
                    $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 500)
                ]);
            } catch (PDOException $e) {
                error_log("Erreur tracking session active: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Détruire la session utilisateur
     */
    public static function logout(): void {
        global $pdo;
        
        $user_id = $_SESSION['user_id'] ?? null;
        $session_id = session_id();
        
        // Logger avant destruction
        if ($user_id) {
            self::logAuthEvent($user_id, 'logout', true);
        }
        
        // Supprimer de la table des sessions actives
        if ($pdo && $session_id) {
            try {
                $stmt = $pdo->prepare("DELETE FROM EPI_active_sessions WHERE session_id = ?");
                $stmt->execute([$session_id]);
            } catch (PDOException $e) {
                error_log("Erreur suppression session active: " . $e->getMessage());
            }
        }
        
        // Détruire toutes les données de session
        $_SESSION = [];
        
        // Détruire le cookie de session
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        // Détruire la session
        session_destroy();
    }
    
    /**
     * Vérifier si l'utilisateur est connecté
     * 
     * @return bool
     */
    public static function isLoggedIn(): bool {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    /**
     * Récupérer l'ID de l'utilisateur connecté
     * 
     * @return int|null
     */
    public static function getUserId(): ?int {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Récupérer les données de l'utilisateur connecté
     * 
     * @return array
     */
    public static function getUserData(): array {
        return [
            'id' => $_SESSION['user_id'] ?? null,
            'name' => $_SESSION['user_name'] ?? null,
            'email' => $_SESSION['user_email'] ?? null,
            'fonction' => $_SESSION['user_fonction'] ?? null,
        ];
    }
    
    /**
     * Vérifier si l'utilisateur a un rôle spécifique
     * 
     * @param string $fonction
     * @return bool
     */
    public static function hasfonction(string $fonction): bool {
        return isset($_SESSION['user_fonction']) && $_SESSION['user_fonction'] === $fonction;
    }
    
    /**
     * Rediriger si non connecté
     * 
     * @param string $redirect_url URL de redirection (par défaut: login.php)
     */
    public static function requireAuth(string $redirect_url = '/membre/login.php'): void {
        if (!self::isLoggedIn()) {
            header('Location: ' . $redirect_url);
            exit;
        }
    }
    
    /**
     * Mettre à jour l'activité
     */
    public static function updateActivity(): void {
        global $pdo;
        
        $_SESSION['last_activity'] = time();
        
        // Mettre à jour en base toutes les 60 secondes (éviter trop de requêtes)
        $last_db_update = $_SESSION['last_db_activity_update'] ?? 0;
        
        if ((time() - $last_db_update) > 60 && $pdo) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE EPI_active_sessions 
                    SET last_activity = NOW() 
                    WHERE session_id = ?
                ");
                $stmt->execute([session_id()]);
                $_SESSION['last_db_activity_update'] = time();
            } catch (PDOException $e) {
                error_log("Erreur mise à jour activité session: " . $e->getMessage());
            }
        }
    }
    
    // ========== MÉTHODES PRIVÉES ==========
    
    /**
     * Vérifier les timeouts de session
     */
    private static function checkTimeouts(): void {
        if (!self::isLoggedIn()) {
            return;
        }
        
        $now = time();
        $expired = false;
        $reason = '';
        
        // Timeout absolu (depuis le login)
        if (isset($_SESSION['login_time'])) {
            if (($now - $_SESSION['login_time']) > self::TIMEOUT_ABSOLUTE) {
                $expired = true;
                $reason = 'Session expirée (timeout absolu)';
            }
        }
        
        // Timeout d'inactivité
        if (isset($_SESSION['last_activity'])) {
            if (($now - $_SESSION['last_activity']) > self::TIMEOUT_INACTIVITY) {
                $expired = true;
                $reason = 'Session expirée (inactivité)';
            }
        }
        
        // Vérifier le fingerprint
        if (isset($_SESSION['fingerprint'])) {
            if ($_SESSION['fingerprint'] !== self::generateFingerprint()) {
                $expired = true;
                $reason = 'Session invalide (fingerprint modifié)';
                error_log("Tentative de vol de session détectée pour user_id: " . ($_SESSION['user_id'] ?? 'unknown'));
            }
        }
        
        if ($expired) {
            $user_id = $_SESSION['user_id'] ?? null;
            self::logout();
            
            // Stocker le message d'erreur pour la page de login
            session_start();
            $_SESSION['error_message'] = $reason;
            
            if ($user_id) {
                self::logAuthEvent($user_id, 'session_expired', false);
            }
        } else {
            // Mettre à jour l'activité
            self::updateActivity();
        }
    }
    
    /**
     * Régénérer l'ID de session périodiquement
     */
    private static function maybeRegenerateId(): void {
        if (!self::isLoggedIn()) {
            return;
        }
        
        $now = time();
        
        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = $now;
        }
        
        // Régénérer tous les 5 minutes
        if (($now - $_SESSION['last_regeneration']) > self::REGENERATE_INTERVAL) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = $now;
        }
    }
    
    /**
     * Générer un fingerprint de session
     * 
     * @return string
     */
    private static function generateFingerprint(): string {
        $components = [
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
            $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '',
        ];
        
        return hash('sha256', implode('|', $components));
    }
    
    /**
     * Logger un événement d'authentification
     * 
     * @param int $user_id
     * @param string $event
     * @param bool $success
     */
    private static function logAuthEvent(int $user_id, string $event, bool $success): void {
        global $pdo;
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        // Logger dans les fichiers PHP
        $log_message = sprintf(
            "[AUTH] User: %d | Event: %s | Success: %s | IP: %s | UA: %s",
            $user_id,
            $event,
            $success ? 'YES' : 'NO',
            $ip,
            substr($user_agent, 0, 100)
        );
        
        error_log($log_message);
        
        // Logger en base de données
        if ($pdo) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO EPI_auth_logs 
                    (user_id, event_type, success, ip_address, user_agent, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                
                $stmt->execute([
                    $user_id,
                    $event,
                    $success ? 1 : 0,
                    $ip,
                    substr($user_agent, 0, 500) // Limiter la taille
                ]);
            } catch (PDOException $e) {
                error_log("Erreur lors du logging en base: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Obtenir le temps restant avant expiration (en secondes)
     * 
     * @return array ['absolute' => int, 'inactivity' => int]
     */
    public static function getTimeRemaining(): array {
        if (!self::isLoggedIn()) {
            return ['absolute' => 0, 'inactivity' => 0];
        }
        
        $now = time();
        
        $absolute_remaining = self::TIMEOUT_ABSOLUTE - ($now - ($_SESSION['login_time'] ?? $now));
        $inactivity_remaining = self::TIMEOUT_INACTIVITY - ($now - ($_SESSION['last_activity'] ?? $now));
        
        return [
            'absolute' => max(0, $absolute_remaining),
            'inactivity' => max(0, $inactivity_remaining)
        ];
    }
}
