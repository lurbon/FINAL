<?php
/**
 * Fonctions de validation et nettoyage des entrées utilisateur
 *
 * Centralise la validation pour éviter la duplication et assurer la cohérence.
 *
 * Usage :
 *   require_once __DIR__ . '/sanitize.php';
 *   $email = sanitize_email($_POST['email']);
 *   $name  = sanitize_text($_POST['name']);
 */

/**
 * Échappe une valeur pour affichage HTML (protection XSS)
 *
 * @param string|null $value Valeur à échapper
 * @return string Valeur échappée
 */
function e(?string $value): string {
    if ($value === null) {
        return '';
    }
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * Nettoie une chaîne de texte basique
 *
 * @param string|null $value Valeur à nettoyer
 * @param int $maxLength Longueur maximale (0 = pas de limite)
 * @return string Valeur nettoyée
 */
function sanitize_text(?string $value, int $maxLength = 0): string {
    if ($value === null) {
        return '';
    }
    $value = trim($value);
    if ($maxLength > 0) {
        $value = mb_substr($value, 0, $maxLength, 'UTF-8');
    }
    return $value;
}

/**
 * Nettoie et valide une adresse email
 *
 * @param string|null $email Email à valider
 * @return string|false Email nettoyé ou false si invalide
 */
function sanitize_email(?string $email) {
    if ($email === null) {
        return false;
    }
    $email = trim($email);
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : false;
}

/**
 * Nettoie un numéro de téléphone français
 *
 * @param string|null $phone Numéro à nettoyer
 * @return string Numéro nettoyé (chiffres et + uniquement)
 */
function sanitize_phone(?string $phone): string {
    if ($phone === null) {
        return '';
    }
    return preg_replace('/[^0-9+]/', '', trim($phone));
}

/**
 * Nettoie un code postal français (5 chiffres)
 *
 * @param string|null $cp Code postal à valider
 * @return string|false Code postal valide ou false
 */
function sanitize_code_postal(?string $cp) {
    if ($cp === null) {
        return false;
    }
    $cp = preg_replace('/[^0-9]/', '', trim($cp));
    return (strlen($cp) === 5) ? $cp : false;
}

/**
 * Nettoie un entier positif
 *
 * @param mixed $value Valeur à nettoyer
 * @param int $min Valeur minimale
 * @param int $max Valeur maximale
 * @return int|false Entier valide ou false
 */
function sanitize_int($value, int $min = 0, int $max = PHP_INT_MAX) {
    $value = filter_var($value, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => $min, 'max_range' => $max]
    ]);
    return $value !== false ? $value : false;
}

/**
 * Nettoie une date au format Y-m-d
 *
 * @param string|null $date Date à valider
 * @return string|false Date valide ou false
 */
function sanitize_date(?string $date) {
    if ($date === null || $date === '') {
        return false;
    }
    $date = trim($date);
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return ($d && $d->format('Y-m-d') === $date) ? $date : false;
}

/**
 * Nettoie une heure au format HH:MM
 *
 * @param string|null $time Heure à valider
 * @return string|false Heure valide ou false
 */
function sanitize_time(?string $time) {
    if ($time === null || $time === '') {
        return false;
    }
    $time = trim($time);
    if (preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]$/', $time)) {
        return $time;
    }
    return false;
}

/**
 * Valide et sécurise un fichier uploadé
 *
 * @param array $file Élément de $_FILES
 * @param array $allowedMimes Types MIME autorisés
 * @param int $maxSize Taille max en octets (défaut : 10 Mo)
 * @return array ['valid' => bool, 'error' => string, 'ext' => string, 'mime' => string]
 */
function validate_upload(array $file, array $allowedMimes = [], int $maxSize = 10485760): array {
    $result = ['valid' => false, 'error' => '', 'ext' => '', 'mime' => ''];

    // Vérifier les erreurs d'upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE   => 'Fichier trop volumineux (limite serveur)',
            UPLOAD_ERR_FORM_SIZE  => 'Fichier trop volumineux (limite formulaire)',
            UPLOAD_ERR_PARTIAL    => 'Fichier partiellement uploadé',
            UPLOAD_ERR_NO_FILE    => 'Aucun fichier uploadé',
            UPLOAD_ERR_NO_TMP_DIR => 'Répertoire temporaire manquant',
            UPLOAD_ERR_CANT_WRITE => 'Écriture sur disque impossible',
        ];
        $result['error'] = $errors[$file['error']] ?? 'Erreur inconnue lors de l\'upload';
        return $result;
    }

    // Vérifier la taille
    if ($file['size'] > $maxSize) {
        $result['error'] = 'Fichier trop volumineux (max ' . round($maxSize / 1048576, 1) . ' Mo)';
        return $result;
    }

    // Vérifier le type MIME réel du fichier (pas celui envoyé par le client)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $detectedMime = $finfo->file($file['tmp_name']);
    $result['mime'] = $detectedMime;

    // Extensions par MIME type
    $mimeToExt = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];

    // Par défaut : autoriser les images courantes
    if (empty($allowedMimes)) {
        $allowedMimes = array_keys($mimeToExt);
    }

    if (!in_array($detectedMime, $allowedMimes, true)) {
        $result['error'] = 'Type de fichier non autorisé (' . e($detectedMime) . ')';
        return $result;
    }

    // Déterminer l'extension depuis le MIME réel (pas depuis le nom du fichier)
    $result['ext'] = $mimeToExt[$detectedMime] ?? pathinfo($file['name'], PATHINFO_EXTENSION);

    $result['valid'] = true;
    return $result;
}

/**
 * Génère un nom de fichier sécurisé et unique pour un upload
 *
 * @param string $prefix Préfixe optionnel
 * @param string $ext Extension du fichier
 * @return string Nom de fichier sécurisé
 */
function safe_filename(string $prefix = '', string $ext = 'jpg'): string {
    $name = ($prefix ? $prefix . '_' : '') . bin2hex(random_bytes(8));
    $ext = preg_replace('/[^a-z0-9]/', '', strtolower($ext));
    return $name . '.' . $ext;
}

/**
 * Récupère une valeur POST nettoyée
 *
 * @param string $key Clé du tableau POST
 * @param string $default Valeur par défaut
 * @return string Valeur nettoyée
 */
function post(string $key, string $default = ''): string {
    return isset($_POST[$key]) ? sanitize_text($_POST[$key]) : $default;
}

/**
 * Récupère une valeur GET nettoyée
 *
 * @param string $key Clé du tableau GET
 * @param string $default Valeur par défaut
 * @return string Valeur nettoyée
 */
function get(string $key, string $default = ''): string {
    return isset($_GET[$key]) ? sanitize_text($_GET[$key]) : $default;
}
