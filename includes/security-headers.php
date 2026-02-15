<?php
/**
 * Headers de sécurité HTTP
 *
 * Ce fichier configure les headers de sécurité pour protéger l'application contre :
 * - XSS (Cross-Site Scripting)
 * - Clickjacking
 * - MIME sniffing
 * - Attaques MITM (Man-In-The-Middle)
 *
 * À inclure au début de chaque fichier PHP avec : require_once('security-headers.php');
 *
 * Documentation :
 * - https://owasp.org/www-project-secure-headers/
 * - https://content-security-policy.com/
 */

// Générer un nonce CSP unique par requête (protection XSS renforcée)
if (!isset($GLOBALS['csp_nonce'])) {
    $GLOBALS['csp_nonce'] = base64_encode(random_bytes(16));
}

/**
 * Retourne le nonce CSP pour les balises <script> inline
 * Usage dans les templates : <script nonce="<?php echo csp_nonce(); ?>">
 *
 * @return string Le nonce CSP encodé en base64
 */
function csp_nonce(): string {
    return $GLOBALS['csp_nonce'] ?? '';
}

// Content Security Policy - Protection contre XSS
// Utilise un nonce pour autoriser les scripts inline de manière sécurisée
$nonce = csp_nonce();
header(
    "Content-Security-Policy: " .
    "default-src 'self'; " .                                      // Par défaut : même origine uniquement
    "script-src 'self' 'nonce-{$nonce}'; " .                      // Scripts : même origine + nonce (pas de unsafe-inline)
    "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; " . // Styles : même origine + Google Fonts
    "font-src 'self' https://fonts.gstatic.com; " .               // Polices : même origine + Google Fonts
    "img-src 'self' data:; " .                                    // Images : même origine + data URIs
    "frame-src 'self' https://www.youtube.com https://www.youtube-nocookie.com https://www.dailymotion.com https://player.vimeo.com; " . // iframes : même origine + vidéos
    "connect-src 'self'; " .                                      // AJAX/Fetch : même origine uniquement
    "frame-ancestors 'self'; " .                                  // Iframes : même origine uniquement
    "base-uri 'self'; " .                                         // Balise <base> : même origine uniquement
    "form-action 'self'"                                          // Formulaires : même origine uniquement
);

// X-Frame-Options - Protection contre le Clickjacking
// Empêche l'affichage du site dans une iframe sur un autre domaine
header("X-Frame-Options: SAMEORIGIN");

// X-Content-Type-Options - Protection contre le MIME sniffing
// Force les navigateurs à respecter le Content-Type déclaré
header("X-Content-Type-Options: nosniff");

// Strict-Transport-Security (HSTS) - Force HTTPS
// Le navigateur utilisera toujours HTTPS pendant 1 an (incluant les sous-domaines)
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
}

// X-XSS-Protection - Protection XSS des navigateurs anciens
// Active la protection XSS intégrée aux navigateurs (pour compatibilité)
header("X-XSS-Protection: 1; mode=block");

// Referrer-Policy - Contrôle des informations de référence
// N'envoie l'URL complète que pour les requêtes vers le même domaine
header("Referrer-Policy: strict-origin-when-cross-origin");

// Permissions-Policy - Désactive les API sensibles
// Désactive l'accès à la géolocalisation, microphone, caméra, etc.
header(
    "Permissions-Policy: " .
    "geolocation=(), " .                   // Pas de géolocalisation
    "microphone=(), " .                    // Pas de microphone
    "camera=(), " .                        // Pas de caméra
    "payment=(), " .                       // Pas d'API de paiement
    "usb=(), " .                           // Pas d'accès USB
    "magnetometer=(), " .                  // Pas de magnétomètre
    "gyroscope=(), " .                     // Pas de gyroscope
    "accelerometer=()"                     // Pas d'accéléromètre
);

// Cache-Control pour les pages authentifiées
// Empêche la mise en cache des pages sensibles
if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user'])) {
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Pragma: no-cache");
    header("Expires: 0");
}

/**
 * Note sur Content-Security-Policy :
 *
 * script-src utilise un nonce CSP généré par requête (voir csp_nonce()).
 * Chaque balise <script> inline DOIT inclure l'attribut nonce :
 *   <script nonce="<?php echo csp_nonce(); ?>">...</script>
 *
 * style-src conserve 'unsafe-inline' car l'extraction de tous les styles
 * inline est un chantier important à faible risque XSS.
 */
