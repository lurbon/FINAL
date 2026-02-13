<?php
/**
 * RATELIMITER - Protection contre les attaques brute-force
 * 
 * Fonctionnalités :
 * - Limitation par IP
 * - Limitation par compte utilisateur
 * - Système de points avec decay
 * - Lockout progressif
 * 
 * @version 1.0
 * @author Entraide Plus Iroise
 */

class RateLimiter {
    
    // Configuration
    const MAX_ATTEMPTS = 5;
    const LOCKOUT_DURATION = 900;  // 15 minutes
    const ATTEMPT_DECAY = 300;     // 5 minutes (temps avant réduction des tentatives)
    
    /**
     * Vérifier si une action est autorisée (par IP)
     * 
     * @param string $action Type d'action (login, reset, etc.)
     * @return bool True si autorisé
     */
    public static function check(string $action): bool {
        $ip = self::getClientIp();
        $key = self::getKey($action, $ip);
        
        $data = self::getData($key);
        
        // Nettoyer les anciennes tentatives
        $data = self::decayAttempts($data);
        
        // Vérifier si bloqué
        if ($data['count'] >= self::MAX_ATTEMPTS) {
            $time_since_first = time() - $data['first_attempt'];
            
            if ($time_since_first < self::LOCKOUT_DURATION) {
                // Toujours bloqué
                self::logBlock($action, $ip);
                return false;
            } else {
                // Période de lockout terminée, réinitialiser
                self::reset($action);
                return true;
            }
        }
        
        return true;
    }
    
    /**
     * Enregistrer une tentative
     * 
     * @param string $action
     * @param bool $success Si la tentative a réussi
     */
    public static function record(string $action, bool $success = false): void {
        $ip = self::getClientIp();
        $key = self::getKey($action, $ip);
        
        $data = self::getData($key);
        
        if ($success) {
            // Succès : réinitialiser le compteur
            self::reset($action);
        } else {
            // Échec : incrémenter
            $data['count']++;
            $data['last_attempt'] = time();
            
            if ($data['count'] === 1) {
                $data['first_attempt'] = time();
            }
            
            self::setData($key, $data);
            
            if ($data['count'] >= self::MAX_ATTEMPTS) {
                error_log("[RATE_LIMIT] IP bloquée : $ip pour action '$action' (tentatives: {$data['count']})");
            }
        }
    }
    
    /**
     * Réinitialiser le compteur pour une action
     * 
     * @param string $action
     */
    public static function reset(string $action): void {
        $ip = self::getClientIp();
        $key = self::getKey($action, $ip);
        
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }
    
    /**
     * Vérifier et enregistrer (helper)
     * 
     * @param string $action
     * @return bool True si autorisé
     */
    public static function checkAndRecord(string $action): bool {
        if (!self::check($action)) {
            self::record($action, false);
            return false;
        }
        
        self::record($action, false);
        return true;
    }
    
    /**
     * Obtenir le nombre de tentatives restantes
     * 
     * @param string $action
     * @return int
     */
    public static function getRemainingAttempts(string $action): int {
        $ip = self::getClientIp();
        $key = self::getKey($action, $ip);
        
        $data = self::getData($key);
        $data = self::decayAttempts($data);
        
        return max(0, self::MAX_ATTEMPTS - $data['count']);
    }
    
    /**
     * Obtenir le temps restant de lockout (en secondes)
     * 
     * @param string $action
     * @return int 0 si pas bloqué
     */
    public static function getLockoutRemaining(string $action): int {
        $ip = self::getClientIp();
        $key = self::getKey($action, $ip);
        
        $data = self::getData($key);
        
        if ($data['count'] >= self::MAX_ATTEMPTS) {
            $elapsed = time() - $data['first_attempt'];
            $remaining = self::LOCKOUT_DURATION - $elapsed;
            return max(0, $remaining);
        }
        
        return 0;
    }
    
    /**
     * Vérifier si actuellement bloqué
     * 
     * @param string $action
     * @return bool
     */
    public static function isLocked(string $action): bool {
        return self::getLockoutRemaining($action) > 0;
    }
    
    /**
     * Bloquer immédiatement (par exemple après détection d'activité suspecte)
     * 
     * @param string $action
     * @param int $duration Durée du lockout en secondes
     */
    public static function lockNow(string $action, int $duration = null): void {
        $duration = $duration ?? self::LOCKOUT_DURATION;
        $ip = self::getClientIp();
        $key = self::getKey($action, $ip);
        
        $data = [
            'count' => self::MAX_ATTEMPTS,
            'first_attempt' => time(),
            'last_attempt' => time()
        ];
        
        self::setData($key, $data);
        
        error_log("[RATE_LIMIT] Lockout immédiat pour IP: $ip, action: $action, durée: {$duration}s");
    }
    
    // ========== MÉTHODES PRIVÉES ==========
    
    /**
     * Générer une clé unique pour l'action et l'IP
     * 
     * @param string $action
     * @param string $ip
     * @return string
     */
    private static function getKey(string $action, string $ip): string {
        return 'rate_limit_' . $action . '_' . md5($ip);
    }
    
    /**
     * Récupérer les données de rate limiting
     * 
     * @param string $key
     * @return array
     */
    private static function getData(string $key): array {
        if (!isset($_SESSION[$key])) {
            return [
                'count' => 0,
                'first_attempt' => time(),
                'last_attempt' => time()
            ];
        }
        
        return $_SESSION[$key];
    }
    
    /**
     * Stocker les données de rate limiting
     * 
     * @param string $key
     * @param array $data
     */
    private static function setData(string $key, array $data): void {
        $_SESSION[$key] = $data;
    }
    
    /**
     * Réduire le compteur de tentatives avec le temps (decay)
     * 
     * @param array $data
     * @return array
     */
    private static function decayAttempts(array $data): array {
        if ($data['count'] === 0) {
            return $data;
        }
        
        $time_since_last = time() - $data['last_attempt'];
        
        // Toutes les ATTEMPT_DECAY secondes, réduire d'une tentative
        $decay_periods = floor($time_since_last / self::ATTEMPT_DECAY);
        
        if ($decay_periods > 0) {
            $data['count'] = max(0, $data['count'] - $decay_periods);
            
            if ($data['count'] === 0) {
                $data['first_attempt'] = time();
            }
        }
        
        return $data;
    }
    
    /**
     * Obtenir l'IP du client
     * 
     * @return string
     */
    private static function getClientIp(): string {
        // Vérifier les headers de proxy
        $ip_keys = [
            'HTTP_CF_CONNECTING_IP',  // Cloudflare
            'HTTP_X_FORWARDED_FOR',   // Proxies standards
            'HTTP_X_REAL_IP',         // Nginx
            'REMOTE_ADDR'             // IP directe
        ];
        
        foreach ($ip_keys as $key) {
            if (isset($_SERVER[$key]) && filter_var($_SERVER[$key], FILTER_VALIDATE_IP)) {
                return $_SERVER[$key];
            }
        }
        
        return 'unknown';
    }
    
    /**
     * Logger un blocage
     * 
     * @param string $action
     * @param string $ip
     */
    private static function logBlock(string $action, string $ip): void {
        $remaining = self::getLockoutRemaining($action);
        $minutes = ceil($remaining / 60);
        
        error_log("[RATE_LIMIT] Blocage actif - IP: $ip | Action: $action | Restant: {$minutes}min");
    }
    
    /**
     * Obtenir des statistiques (pour admin/monitoring)
     * 
     * @return array
     */
    public static function getStats(): array {
        $stats = [];
        
        foreach ($_SESSION as $key => $value) {
            if (strpos($key, 'rate_limit_') === 0) {
                $stats[$key] = $value;
            }
        }
        
        return $stats;
    }
    
    /**
     * Formater un message d'erreur pour l'utilisateur
     * 
     * @param string $action
     * @return string
     */
    public static function getErrorMessage(string $action): string {
        $remaining = self::getLockoutRemaining($action);
        
        if ($remaining > 0) {
            $minutes = ceil($remaining / 60);
            return "Trop de tentatives. Veuillez réessayer dans $minutes minute(s).";
        }
        
        $attempts_left = self::getRemainingAttempts($action);
        
        if ($attempts_left <= 2) {
            return "Attention : il vous reste $attempts_left tentative(s) avant blocage.";
        }
        
        return "Trop de tentatives. Veuillez réessayer plus tard.";
    }
}
