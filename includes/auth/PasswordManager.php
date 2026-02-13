<?php
/**
 * PASSWORDMANAGER - Gestion centralisée des mots de passe
 * 
 * Fonctionnalités :
 * - Hashing sécurisé avec bcrypt
 * - Support legacy phpass (migration automatique)
 * - Génération mots de passe forts
 * - Validation complexité
 * - Historique (éviter réutilisation)
 * 
 * @version 1.0
 * @author Entraide Plus Iroise
 */

class PasswordManager {
    
    // Configuration
    const MIN_LENGTH = 8;
    const REQUIRE_UPPERCASE = true;
    const REQUIRE_LOWERCASE = true;
    const REQUIRE_DIGIT = true;
    const REQUIRE_SPECIAL = true;
    const BCRYPT_COST = 12;
    const TEMP_PASSWORD_LENGTH = 12;
    const PASSWORD_HISTORY_LIMIT = 5; // Ne pas réutiliser les 5 derniers
    
    // Liste des 100 mots de passe les plus communs à bloquer
    private static $commonPasswords = [
        'password', '123456', '123456789', 'qwerty', 'abc123', 'monkey',
        'letmein', 'trustno1', 'dragon', 'baseball', 'iloveyou', 'master',
        'sunshine', 'ashley', 'bailey', 'shadow', 'superman', 'qazwsx',
        '123123', 'welcome', 'admin', 'password1', '1234567890', 'azerty'
    ];
    
    /**
     * Hasher un mot de passe avec bcrypt
     * 
     * @param string $password
     * @return string Hash bcrypt
     */
    public static function hash(string $password): string {
        return password_hash($password, PASSWORD_BCRYPT, [
            'cost' => self::BCRYPT_COST
        ]);
    }
    
    /**
     * Vérifier un mot de passe (supporte bcrypt et phpass legacy)
     * 
     * @param string $password Mot de passe en clair
     * @param string $hash Hash stocké en base
     * @return bool
     */
    public static function verify(string $password, string $hash): bool {
        // Bcrypt natif (commence par $2y$ ou $2a$)
        if (strpos($hash, '$2y$') === 0 || strpos($hash, '$2a$') === 0) {
            return password_verify($password, $hash);
        }
        
        // Phpass legacy WordPress (commence par $P$ ou $H$)
        if (strpos($hash, '$P$') === 0 || strpos($hash, '$H$') === 0) {
            return self::verifyPhpass($password, $hash);
        }
        
        return false;
    }
    
    /**
     * Vérifier si le hash a besoin d'être rehashé
     * 
     * @param string $hash
     * @return bool
     */
    public static function needsRehash(string $hash): bool {
        // Si c'est du phpass, il faut migrer
        if (strpos($hash, '$P$') === 0 || strpos($hash, '$H$') === 0) {
            return true;
        }
        
        // Si c'est du bcrypt mais avec un cost trop faible
        return password_needs_rehash($hash, PASSWORD_BCRYPT, [
            'cost' => self::BCRYPT_COST
        ]);
    }
    
    /**
     * Valider la force d'un mot de passe
     * 
     * @param string $password
     * @return array ['valid' => bool, 'errors' => array]
     */
    public static function validateStrength(string $password): array {
        $errors = [];
        
        // Longueur minimale
        if (strlen($password) < self::MIN_LENGTH) {
            $errors[] = "Le mot de passe doit contenir au moins " . self::MIN_LENGTH . " caractères";
        }
        
        // Majuscule requise
        if (self::REQUIRE_UPPERCASE && !preg_match('/[A-Z]/', $password)) {
            $errors[] = "Le mot de passe doit contenir au moins une majuscule";
        }
        
        // Minuscule requise
        if (self::REQUIRE_LOWERCASE && !preg_match('/[a-z]/', $password)) {
            $errors[] = "Le mot de passe doit contenir au moins une minuscule";
        }
        
        // Chiffre requis
        if (self::REQUIRE_DIGIT && !preg_match('/[0-9]/', $password)) {
            $errors[] = "Le mot de passe doit contenir au moins un chiffre";
        }
        
        // Caractère spécial requis
        if (self::REQUIRE_SPECIAL && !preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = "Le mot de passe doit contenir au moins un caractère spécial (!@#$%&*...)";
        }
        
        // Vérifier mots de passe communs
        if (in_array(strtolower($password), self::$commonPasswords)) {
            $errors[] = "Ce mot de passe est trop commun. Choisissez-en un plus sécurisé";
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Générer un mot de passe temporaire fort
     * 
     * @param int $length Longueur (minimum 12)
     * @return string
     */
    public static function generateTempPassword(int $length = null): string {
        $length = $length ?? self::TEMP_PASSWORD_LENGTH;
        if ($length < 12) $length = 12;
        
        $uppercase = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lowercase = 'abcdefghjkmnpqrstuvwxyz';
        $digits = '23456789';
        $special = '!@#$%&*';
        
        // Garantir au moins un caractère de chaque type
        $password = '';
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $digits[random_int(0, strlen($digits) - 1)];
        $password .= $special[random_int(0, strlen($special) - 1)];
        
        // Compléter avec des caractères aléatoires
        $all = $uppercase . $lowercase . $digits . $special;
        for ($i = strlen($password); $i < $length; $i++) {
            $password .= $all[random_int(0, strlen($all) - 1)];
        }
        
        // Mélanger pour éviter un pattern prévisible
        return str_shuffle($password);
    }
    
    /**
     * Vérifier si le mot de passe a déjà été utilisé
     * 
     * @param PDO $pdo
     * @param int $user_id
     * @param string $password
     * @return bool True si déjà utilisé
     */
    public static function wasUsedRecently(PDO $pdo, int $user_id, string $password): bool {
        $stmt = $pdo->prepare("
            SELECT password_hash 
            FROM EPI_password_history 
            WHERE user_id = ? 
            ORDER BY changed_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$user_id, self::PASSWORD_HISTORY_LIMIT]);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (self::verify($password, $row['password_hash'])) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Ajouter un mot de passe à l'historique
     * 
     * @param PDO $pdo
     * @param int $user_id
     * @param string $password_hash
     */
    public static function addToHistory(PDO $pdo, int $user_id, string $password_hash): void {
        // Ajouter le nouveau
        $stmt = $pdo->prepare("
            INSERT INTO EPI_password_history (user_id, password_hash, changed_at) 
            VALUES (?, ?, NOW())
        ");
        $stmt->execute([$user_id, $password_hash]);
        
        // Nettoyer l'ancien historique (garder seulement les N derniers)
        $stmt = $pdo->prepare("
            DELETE FROM EPI_password_history 
            WHERE user_id = ? 
            AND id NOT IN (
                SELECT id FROM (
                    SELECT id FROM EPI_password_history 
                    WHERE user_id = ? 
                    ORDER BY changed_at DESC 
                    LIMIT ?
                ) AS recent
            )
        ");
        $stmt->execute([$user_id, $user_id, self::PASSWORD_HISTORY_LIMIT]);
    }
    
    /**
     * Migrer automatiquement un mot de passe phpass vers bcrypt
     * 
     * @param PDO $pdo
     * @param int $user_id
     * @param string $password Mot de passe en clair (uniquement au moment du login)
     * @return bool
     */
    public static function migratePassword(PDO $pdo, int $user_id, string $password): bool {
        $new_hash = self::hash($password);
        
        $stmt = $pdo->prepare("UPDATE EPI_user SET user_pass = ? WHERE ID = ?");
        $success = $stmt->execute([$new_hash, $user_id]);
        
        if ($success) {
            error_log("Migration automatique du mot de passe vers bcrypt pour l'utilisateur ID: $user_id");
        }
        
        return $success;
    }
    
    /**
     * Générer un token de réinitialisation sécurisé
     * 
     * @return array ['token' => string, 'token_hash' => string, 'expiry' => string]
     */
    public static function generateResetToken(): array {
        $token = bin2hex(random_bytes(32)); // 64 caractères
        $token_hash = hash('sha256', $token); // Hash pour stockage DB
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        return [
            'token' => $token,           // À envoyer par email
            'token_hash' => $token_hash, // À stocker en DB
            'expiry' => $expiry
        ];
    }
    
    /**
     * Vérifier un token de réinitialisation
     * 
     * @param PDO $pdo
     * @param string $token Token reçu par email
     * @return array|null User data si valide, null sinon
     */
    public static function verifyResetToken(PDO $pdo, string $token): ?array {
        $token_hash = hash('sha256', $token);
        
        $stmt = $pdo->prepare("
            SELECT ID, user_nicename, user_email, reset_expiry 
            FROM EPI_user 
            WHERE reset_token = ? AND reset_expiry > NOW()
        ");
        $stmt->execute([$token_hash]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    
    /**
     * Invalider un token de réinitialisation
     * 
     * @param PDO $pdo
     * @param int $user_id
     */
    public static function invalidateResetToken(PDO $pdo, int $user_id): void {
        $stmt = $pdo->prepare("
            UPDATE EPI_user 
            SET reset_token = NULL, reset_expiry = NULL 
            WHERE ID = ?
        ");
        $stmt->execute([$user_id]);
    }
    
    // ========== MÉTHODES PRIVÉES ==========
    
    /**
     * Vérifier un mot de passe avec phpass (legacy WordPress)
     * 
     * @param string $password
     * @param string $hash
     * @return bool
     */
    private static function verifyPhpass(string $password, string $hash): bool {
        // Implémentation simplifiée de phpass
        // Pour production, utiliser la vraie lib phpass si nécessaire
        
        $itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        
        if (strlen($hash) != 34) {
            return false;
        }
        
        $count_log2 = strpos($itoa64, $hash[3]);
        $count = 1 << $count_log2;
        $salt = substr($hash, 4, 8);
        
        $hash_check = md5($salt . $password, true);
        do {
            $hash_check = md5($hash_check . $password, true);
        } while (--$count);
        
        $output = substr($hash, 0, 12);
        $output .= self::encode64($hash_check, 16, $itoa64);
        
        return $output === $hash;
    }
    
    /**
     * Encodage base64 pour phpass
     */
    private static function encode64(string $input, int $count, string $itoa64): string {
        $output = '';
        $i = 0;
        
        do {
            $value = ord($input[$i++]);
            $output .= $itoa64[$value & 0x3f];
            
            if ($i < $count) {
                $value |= ord($input[$i]) << 8;
            }
            
            $output .= $itoa64[($value >> 6) & 0x3f];
            
            if ($i++ >= $count) {
                break;
            }
            
            if ($i < $count) {
                $value |= ord($input[$i]) << 16;
            }
            
            $output .= $itoa64[($value >> 12) & 0x3f];
            
            if ($i++ >= $count) {
                break;
            }
            
            $output .= $itoa64[($value >> 18) & 0x3f];
            
        } while ($i < $count);
        
        return $output;
    }
}
