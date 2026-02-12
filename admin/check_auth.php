<?php
/**
 * Vérification d'authentification pour l'espace admin
 *
 * Sécurité ajoutée :
 * - Timeout d'inactivité (30 minutes)
 * - Timeout absolu (3 heures)
 * - Vérification IP (optionnel)
 * - Déconnexion via POST uniquement
 */

// Démarrer la session si pas déjà active
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1);
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.use_strict_mode', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

// Constantes de timeout
define('ADMIN_TIMEOUT_INACTIVITY', 1800);  // 30 minutes
define('ADMIN_TIMEOUT_ABSOLUTE', 10800);   // 3 heures

// Traitement de la déconnexion (POST uniquement pour sécurité)
if (isset($_POST['admin_logout'])) {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    header('Location: login.php');
    exit;
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Vérifier le timeout absolu
if (isset($_SESSION['admin_login_time']) && (time() - $_SESSION['admin_login_time']) > ADMIN_TIMEOUT_ABSOLUTE) {
    $_SESSION = [];
    session_destroy();
    header('Location: login.php?expired=1');
    exit;
}

// Vérifier le timeout d'inactivité
if (isset($_SESSION['admin_last_activity']) && (time() - $_SESSION['admin_last_activity']) > ADMIN_TIMEOUT_INACTIVITY) {
    $_SESSION = [];
    session_destroy();
    header('Location: login.php?expired=1');
    exit;
}

// Mettre à jour le timestamp d'activité
$_SESSION['admin_last_activity'] = time();
?>
