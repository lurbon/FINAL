<?php
/**
 * Protection CSRF (Cross-Site Request Forgery)
 *
 * Génère et valide des tokens CSRF pour protéger les formulaires.
 *
 * Usage dans un formulaire :
 *   require_once __DIR__ . '/csrf.php';
 *   // Dans le HTML du formulaire :
 *   <?php echo csrf_field(); ?>
 *
 * Usage pour validation (au début du traitement POST) :
 *   if (!csrf_verify()) {
 *       die('Token CSRF invalide.');
 *   }
 */

/**
 * Démarre la session si elle n'est pas déjà active
 */
function csrf_ensure_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Génère ou récupère le token CSRF de la session courante
 *
 * @return string Token CSRF (64 caractères hexadécimaux)
 */
function csrf_token(): string {
    csrf_ensure_session();

    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf_token'];
}

/**
 * Retourne un champ HTML hidden contenant le token CSRF
 *
 * @return string Balise <input type="hidden"> avec le token
 */
function csrf_field(): string {
    return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Retourne le token sous forme de meta tag (pour les requêtes AJAX)
 *
 * @return string Balise <meta> avec le token
 */
function csrf_meta(): string {
    return '<meta name="csrf-token" content="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Vérifie la validité du token CSRF soumis
 *
 * Supporte les formulaires classiques (POST) et les requêtes AJAX (header X-CSRF-Token)
 *
 * @return bool True si le token est valide
 */
function csrf_verify(): bool {
    csrf_ensure_session();

    if (empty($_SESSION['_csrf_token'])) {
        return false;
    }

    // Chercher le token dans POST ou dans le header AJAX
    $submitted = $_POST['_csrf_token']
        ?? $_SERVER['HTTP_X_CSRF_TOKEN']
        ?? '';

    if (empty($submitted)) {
        return false;
    }

    return hash_equals($_SESSION['_csrf_token'], $submitted);
}

/**
 * Vérifie le CSRF et arrête l'exécution si invalide
 *
 * @param string $errorMessage Message d'erreur personnalisé
 * @return void
 */
function csrf_protect(string $errorMessage = 'Requête invalide. Veuillez recharger la page et réessayer.'): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'DELETE') {
        if (!csrf_verify()) {
            http_response_code(403);

            // Réponse JSON pour les requêtes AJAX
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['error' => true, 'message' => $errorMessage]);
                exit();
            }

            // Réponse HTML sinon
            echo '<div style="color: #c62828; background: #ffebee; padding: 1rem; border-radius: 8px; margin: 1rem; font-family: sans-serif;">';
            echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8');
            echo '</div>';
            exit();
        }
    }
}

/**
 * Régénère le token CSRF (à appeler après un changement de privilèges)
 *
 * @return string Nouveau token
 */
function csrf_regenerate(): string {
    csrf_ensure_session();
    $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['_csrf_token'];
}
