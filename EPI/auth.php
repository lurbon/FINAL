<?php
/**
 * SYSTÈME D'AUTHENTIFICATION - VERSION 2.0
 * Utilise SessionManager pour la gestion des sessions
 * 
 * À inclure au début de chaque page protégée avec : require_once('auth.php');
 * 
 * @version 2.0
 * @author Entraide Plus Iroise
 */

require_once __DIR__ . '/../includes/auth/SessionManager.php';

// Initialiser la session sécurisée
SessionManager::init();

// Vérifier l'authentification (redirige vers login si non connecté)
SessionManager::requireAuth('../membre/login.php');

// ========== FONCTIONS DE COMPATIBILITÉ ==========

/**
 * Retourne les informations de l'utilisateur connecté
 * 
 * @return array Données utilisateur
 */
function getUtilisateurConnecte() {
    $userData = SessionManager::getUserData();
    
    // Format compatible avec l'ancien système
    return [
        'id' => $userData['id'],
        'name' => $userData['name'],
        'username' => $userData['name'],
        'email' => $userData['email'],
        'fonctions' => [$userData['fonction']]
    ];
}

/**
 * Retourne le token de session (pour compatibilité)
 * 
 * @return string Session ID
 */
function getToken() {
    return session_id();
}

/**
 * Retourne le nom d'affichage de l'utilisateur connecté
 * 
 * @return string Nom d'affichage
 */
function getNomUtilisateur() {
    $userData = SessionManager::getUserData();
    return htmlspecialchars($userData['name'] ?? 'Utilisateur', ENT_QUOTES, 'UTF-8');
}

/**
 * Vérifie si l'utilisateur a un rôle spécifique
 * 
 * @param string|array $fonctions Rôle(s) à vérifier
 * @return bool
 */
function hasfonction($fonctions) {
    if (!is_array($fonctions)) {
        $fonctions = [$fonctions];
    }
    
    $userData = SessionManager::getUserData();
    $userfonction = $userData['fonction'] ?? '';
    
    return in_array($userfonction, $fonctions);
}

/**
 * Vérifie si l'utilisateur a l'un des rôles autorisés
 * Redirige vers dashboard si non autorisé
 * 
 * @param string|array $fonctionsAutorises Rôle(s) autorisé(s)
 * @return bool
 */
function verifierfonction($fonctionsAutorises) {
    if (!is_array($fonctionsAutorises)) {
        $fonctionsAutorises = [$fonctionsAutorises];
    }
    
    if (!hasfonction($fonctionsAutorises)) {
        $userData = SessionManager::getUserData();
        
        error_log(sprintf(
            "Tentative d'accès non autorisée - User: %s, Rôles requis: %s, Rôle actuel: %s, Page: %s",
            $userData['name'] ?? 'inconnu',
            implode(',', $fonctionsAutorises),
            $userData['fonction'] ?? 'inconnu',
            $_SERVER['PHP_SELF'] ?? 'inconnue'
        ));
        
        header('Location: dashboard.php?error=' . urlencode('Accès refusé : rôle insuffisant'));
        exit();
    }
    
    return true;
}

/**
 * Retourne l'ID de l'utilisateur connecté
 * 
 * @return int|null
 */
function getUserId() {
    return SessionManager::getUserId();
}

// ========== MISE À JOUR AUTOMATIQUE DE L'ACTIVITÉ ==========
SessionManager::updateActivity();

// Headers de sécurité HTTP
if (file_exists(__DIR__ . '/security-headers.php')) {
    require_once __DIR__ . '/security-headers.php';
}
